<? include "../config/core.php";
	include "../clients/lib.php";
	require_once "../config/promo.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Акциялар: % жеңілдік, касса тапсырысқа таңдайды (pos.berik.c cashbox, promo.js)
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	mysqli_report(MYSQLI_REPORT_OFF);
	$ready = promo::on() ?: promo::install(); // кесте бірінші ашылғанда өзі жасалады
	$msg = '';

	if ($ready && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $msg = 'Бетті жаңартып, қайта көріңіз';
		else {
			$id = (int)@$_POST['id'];
			$now = date('Y-m-d H:i:s');
			$e = function ($v) { return mysqli_real_escape_string(db::$connect, $v); };
			$dt = function ($v) { $v = trim((string)$v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? "'$v'" : 'NULL'; };
			$own = $id ? mysqli_fetch_assoc(db::query("select id from company_promo where id = '$id' and company_id = '$cmp' and arh = 0")) : null;
			if (isset($_POST['del']) && $own) {
				db::query("update company_promo set arh = 1, active = 0, upd_dt = '$now' where id = '$id'");
				header('location: /promo/?saved=del'); exit;
			}
			if (isset($_POST['toggle']) && $own) {
				db::query("update company_promo set active = 1 - active, upd_dt = '$now' where id = '$id'");
				header('location: /promo/?saved=1'); exit;
			}
			$name = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)@$_POST['name']))), 0, 100);
			$pct = round(max(0, min(100, (float)str_replace(',', '.', (string)@$_POST['percent']))), 2);
			if ($name === '' || $pct <= 0) $msg = 'Атауы мен пайызын жазыңыз (1–100%)';
			else {
				$f = $dt(@$_POST['date_from']); $t = $dt(@$_POST['date_to']);
				if ($f != 'NULL' && $t != 'NULL' && $t < $f) { $x = $f; $f = $t; $t = $x; }
				if ($own) db::query("update company_promo set name = '".$e($name)."', percent = '$pct', date_from = $f, date_to = $t, upd_dt = '$now' where id = '$id'");
				elseif (!$id) db::query("insert into company_promo (company_id, name, percent, date_from, date_to, active, ins_dt, upd_dt) values ('$cmp', '".$e($name)."', '$pct', $f, $t, 1, '$now', '$now')");
				header('location: /promo/?saved='.($id ? 1 : 'new')); exit;
			}
		}
	}
	$saved = (string)@$_GET['saved'];
	if ($saved == 'new') $msg = 'Акция қосылды. Кассада бірден көрінеді.';
	elseif ($saved == 'del') $msg = 'Акция өшірілді';
	elseif ($saved) $msg = 'Сақталды';

	// Тізім және қолданылуы (отказ емес, төленген тапсырыстар)
	$list = []; $use = []; $st = ['act' => 0, 'n_m' => 0, 'sum_m' => 0, 'n' => 0, 'sum' => 0];
	if ($ready) {
		$q = db::query("select * from company_promo where company_id = '$cmp' and arh = 0 order by active desc, id desc");
		while ($q && $r = mysqli_fetch_assoc($q)) $list[] = $r;
		$m0 = date('Y-m-01 00:00:00');
		$q = db::query("select promo_id, count(*) n, sum(promo_discount) s, sum(ins_dt >= '$m0') n_m, sum(case when ins_dt >= '$m0' then promo_discount else 0 end) s_m
			from retail_orders where company_id = '$cmp' and paid = 1 and promo_discount > 0 and order_status not in (5, 6, 10) group by promo_id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$use[(int)$r['promo_id']] = $r;
			$st['n'] += $r['n']; $st['sum'] += $r['s']; $st['n_m'] += $r['n_m']; $st['sum_m'] += $r['s_m'];
		}
		foreach (promo::active($cmp) as $a) $st['act']++;
	}
	$today = date('Y-m-d');
	$state = function ($p) use ($today) {
		if (!$p['active']) return ['Өшірулі', 'cl_seg_archive'];
		if ($p['date_from'] && $p['date_from'] > $today) return ['Күтуде', 'pm_seg_wait'];
		if ($p['date_to'] && $p['date_to'] < $today) return ['Мерзімі өтті', 'cl_seg_archive'];
		return ['Қосулы', 'cl_seg_new'];
	};
	$dview = function ($d) { return $d ? date('d.m.Y', strtotime($d)) : ''; };

	// site setting
	$menu_name = 'promo';
	$page_name = 'Акциялар';
	$css = ['clients', 'loyalty', 'promo'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page pm_page">

	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Базада акция кестесін жасау мүмкін болмады.</div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg pm_msg"><?=htmlspecialchars($msg)?></div><? endif ?>

	<div class="cl_cards cl_cards_3">
		<div class="cl_card"><div class="cl_card_h">Бүгін кассада</div><div class="cl_card_v cl_c_green"><?=$st['act']?></div><div class="cl_card_s">белсенді акция</div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айда акциямен</div><div class="cl_card_v"><?=$st['n_m']?></div><div class="cl_card_s">тапсырыс · барлығы <?=$st['n']?></div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айдағы жеңілдік</div><div class="cl_card_v cl_v_sm"><?=clients::sum($st['sum_m'])?></div><div class="cl_card_s">барлығы <?=clients::sum($st['sum'])?></div></div>
	</div>

	<form class="cl_box pm_new" method="post" autocomplete="off" data-ajx=".pm_page" data-ajx-msg=".pm_msg">
		<div class="cl_box_h"><div><i class="fal fa-tags"></i> Жаңа акция</div></div>
		<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">
		<input type="hidden" name="id" value="0">
		<div class="pm_row">
			<label><span>Атауы (кассада көрінеді)</span><input type="text" name="name" class="cl_inp" maxlength="100" placeholder="Мысалы: Туған күн" required></label>
			<label><span>Жеңілдік</span><div class="lc_inp_w"><input type="number" name="percent" class="cl_inp" min="1" max="100" step="0.5" placeholder="10" required><span>%</span></div></label>
			<label><span>Басталуы (міндетті емес)</span><input type="date" name="date_from" class="cl_inp"></label>
			<label><span>Аяқталуы (міндетті емес)</span><input type="date" name="date_to" class="cl_inp"></label>
			<button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-plus"></i> Қосу</button>
		</div>
		<div class="cl_small">Жеңілдік тек тағам сомасынан есептеледі (жеткізу мен RED-сіз). Оператор кассада төлем терезесінде «Акция» жолынан таңдайды.</div>
	</form>

	<div class="cl_box pm_list">
		<div class="cl_box_h"><div>Акциялар</div></div>
		<? if (!$list): ?><div class="cl_empty">Әзірге акция жоқ</div><? endif ?>
		<? foreach ($list as $p): [$s_t, $s_c] = $state($p); $u = @$use[(int)$p['id']]; ?>
			<form class="pm_item<?=($p['active'] ? '' : ' pm_off')?>" method="post" autocomplete="off" data-ajx=".pm_page" data-ajx-msg=".pm_msg">
				<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">
				<input type="hidden" name="id" value="<?=(int)$p['id']?>">
				<div class="pm_row">
					<label><span>Атауы</span><input type="text" name="name" class="cl_inp" maxlength="100" value="<?=htmlspecialchars($p['name'])?>" required></label>
					<label><span>Жеңілдік</span><div class="lc_inp_w"><input type="number" name="percent" class="cl_inp" min="1" max="100" step="0.5" value="<?=promo::pct($p['percent'])?>" required><span>%</span></div></label>
					<label><span>Басталуы</span><input type="date" name="date_from" class="cl_inp" value="<?=htmlspecialchars((string)$p['date_from'])?>"></label>
					<label><span>Аяқталуы</span><input type="date" name="date_to" class="cl_inp" value="<?=htmlspecialchars((string)$p['date_to'])?>"></label>
					<button type="submit" class="cl_btn cl_btn_light" title="Сақтау"><i class="fal fa-check"></i> Сақтау</button>
				</div>
				<div class="pm_foot">
					<span class="cl_seg <?=$s_c?>"><?=$s_t?></span>
					<span class="pm_use"><?=($u ? 'Осы айда '.(int)$u["n_m"].' тапсырыс, −'.clients::sum($u['s_m']).' · барлығы '.(int)$u['n'].', −'.clients::sum($u['s']) : 'Әлі қолданылмаған')?></span>
					<button type="submit" name="toggle" value="1" class="pm_lnk"><i class="fal fa-power-off"></i> <?=($p['active'] ? 'Өшіру' : 'Қосу')?></button>
					<button type="submit" name="del" value="1" class="pm_lnk pm_del" data-ask-danger data-ask="«<?=htmlspecialchars($p['name'])?>» акциясын жоямыз ба? Бұрынғы тапсырыстарда сақталады."><i class="fal fa-trash-alt"></i> Жою</button>
				</div>
			</form>
		<? endforeach ?>
	</div>

	<? endif ?>
</div>

<? include "../block/footer.php"; ?>
