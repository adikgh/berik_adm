<? include "../config/core.php";
	include "lib.php";
	require_once "../config/loyalty.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Сыйлық баптауы: әр N-ші тапсырысқа сыйлық. Кэшбэкке тиіспейді (ол — cashback.php)
	// Ауыстырғыш өшірулі: деңгейлер active = 2 (тоқтатылған) болып сақталады, қосқанда қайта active = 1.
	// Касса мен кабинет тек active = 1-ді оқиды.
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$ready = loyalty::on();
	$tiers_on = $ready && loyalty::tiers_on();
	$msg = '';

	if ($ready && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $msg = 'Бетті жаңартып, қайта көріңіз';
		else {
			$old = loyalty::cfg($cmp);
			$on = !empty($_POST['gift_on']);
			$e = function ($v) { return mysqli_real_escape_string(db::$connect, $v); };
			$set = [];
			if ($tiers_on) {
				$act = $on ? 1 : 2;
				foreach ((array)@$_POST['tier'] as $t) {
					$tid = (int)@$t['id'];
					$every = max(0, min(500, (int)@$t['every']));
					$name = mb_substr(trim(strip_tags((string)@$t['name'])), 0, 255);
					$pid = (int)@$t['product_id'];
					if ($pid && !mysqli_num_rows(db::query("select id from product where id = '$pid' and company_id = '$cmp'"))) $pid = 0;
					$qty = max(1, min(20, (int)@$t['quantity']));
					$dp = max(0, min(100, (int)@$t['discount_percent']));
					$dm = max(0, (int)@$t['discount_max']);
					$del = !empty($t['del']) || $every < 2 || $name === '' || (!$pid && !$dp);
					if ($tid) {
						if ($del) db::query("update company_gift_tier set active = 0 where id = '$tid' and company_id = '$cmp'");
						else db::query("update company_gift_tier set every = '$every', name = '".$e($name)."', product_id = ".($pid ?: 'NULL').", quantity = '".($pid ? $qty : 0)."',
							discount_percent = '".($pid ? 0 : $dp)."', discount_max = '".($pid ? 0 : $dm)."', active = '$act' where id = '$tid' and company_id = '$cmp'");
					} elseif (!$del) {
						db::query("insert into company_gift_tier (company_id, every, name, product_id, quantity, discount_percent, discount_max, active)
							values ('$cmp', '$every', '".$e($name)."', ".($pid ?: 'NULL').", '".($pid ? $qty : 0)."', '".($pid ? 0 : $dp)."', '".($pid ? 0 : $dm)."', '$act')");
					}
				}
				// формада болмаған деңгейлер де ауыстырғышқа ереді
				db::query("update company_gift_tier set active = '$act' where company_id = '$cmp' and active in (1, 2)");
				$gift_on = $on && mysqli_num_rows(db::query("select id from company_gift_tier where company_id = '$cmp' and active = 1 and every > 1")) > 0;
			} else {
				// ескі бір деңгейлі баптау: өшсе тек нөмір 0, атауы сақталады
				$every = max(0, min(100, (int)@$_POST['gift_every']));
				if ($every == 1) $every = 2; // әр тапсырысқа сыйлық болмайды
				$gname = mb_substr(trim(strip_tags((string)@$_POST['gift_name'])), 0, 255);
				if ($gname === '' || !$on) $every = 0;
				$set[] = "gift_every = '$every'"; $set[] = "gift_name = ".($gname !== '' ? "'".$e($gname)."'" : "NULL");
				$gift_on = $every > 1;
			}
			if ($gift_on && (!$old['gift'] || @$_POST['gift_reset'])) $set[] = "gift_from = '".date('Y-m-d H:i:s')."'"; // қосылғанда не «бүгіннен бастау»
			if ($set) db::query("update company set ".implode(', ', $set)." where id = '$cmp'");
			$q = $on && !$gift_on ? '?saved=2' : '?saved=1';
			header('location: /clients/gift.php'.$q); exit;
		}
	}
	if (@$_GET['saved']) $msg = $_GET['saved'] == 2 ? 'Сақталды. Сыйлық қосылуы үшін кемі бір сыйлық толтырыңыз' : 'Сақталды';

	$cfg = loyalty::cfg($cmp);
	if ($ready) loyalty::sync($cmp);
	$tl = []; $prods = []; $paused = false;
	if ($tiers_on) {
		// белсенді және тоқтатылған деңгейлер (ауыстырғыш өшірулі кезде де көрінеді)
		$q = db::query("select * from company_gift_tier where company_id = '$cmp' and active in (1, 2) and every > 1 order by every asc, id asc");
		while ($q && $t = mysqli_fetch_assoc($q)) { $tl[] = $t; if ($t['active'] == 2) $paused = true; }
		$tl[] = ['id' => 0, 'every' => '', 'name' => '', 'product_id' => 0, 'quantity' => 1, 'discount_percent' => 0, 'discount_max' => 0];
		$q = db::query("select id, name_kz, name_ru, price from product where company_id = '$cmp' and arh = 0 order by name_kz, name_ru");
		while ($q && $p = mysqli_fetch_assoc($q)) $prods[] = $p;
	}
	$sw = $cfg['gift']; // ауыстырғыш: кемі бір белсенді сыйлық бар ма

	// Қысқа есеп
	$st = ['gifts_m' => 0, 'gifts' => 0, 'tiers' => count($cfg['tiers'])];
	$last = false;
	if ($ready) {
		$m0 = date('Y-m-01 00:00:00');
		$r = mysqli_fetch_assoc(db::query("select count(*) n, sum(ins_dt >= '$m0') m from company_client_gift where company_id = '$cmp'"));
		$st['gifts'] = (int)$r['n']; $st['gifts_m'] = (int)$r['m'];
		$last = db::query("select g.*, c.name, c.phone, o.number from company_client_gift g join company_client c on c.id = g.client_id left join retail_orders o on o.id = g.order_id
			where g.company_id = '$cmp' order by g.id desc limit 30");
	}

	// Қазақша реттік жұрнақ: 5-ші, 6-шы, 10-шы, 20-шы, 50-ші
	$ord = function ($n) { $n = (int)$n; if ($n <= 0) return '-ші'; if ($n % 100 == 0) return '-ші'; $d = $n % 10;
		if ($d) return in_array($d, [6, 9]) ? '-шы' : '-ші'; return in_array(($n % 100) / 10, [5, 7, 8]) ? '-ші' : '-шы'; };

	// site setting
	$menu_name = 'clients';
	$page_name = 'Сыйлық';
	$page_back = ['/clients/', 'Клиенттер'];
	$css = ['clients', 'loyalty'];
	$js = ['loyalty'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page lc_page">

	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Базаға кэшбэк кестелері әлі қосылмаған (berik_cashback.sql).</div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg"><?=htmlspecialchars($msg)?></div><? endif ?>

	<form method="post" autocomplete="off" data-ajx=".lc_page" data-ajx-msg=".lc_msg">
		<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">

		<div class="cl_box lc_set lc_tg<?=($sw ? '' : ' lc_off')?>">
			<div class="cl_box_h">
				<div><i class="fal fa-gift"></i> Әр N-ші тапсырысқа сыйлық</div>
				<label class="lc_sw"><input type="hidden" name="gift_on" value="0"><input type="checkbox" name="gift_on" value="1"<?=($sw ? ' checked' : '')?>><i></i><span class="lc_sw_t"><?=($sw ? 'Қосулы' : 'Өшірулі')?></span></label>
			</div>
			<div class="lc_off_note">Сыйлық өшірулі.<?=($paused ? ' Сыйлықтар сақталып тұр, қосқанда қайта іске қосылады.' : ' Қосу үшін ауыстырғышты басып, «Сақтау» басыңыз.')?></div>

			<div class="lc_body">
			<? if ($tiers_on): ?>
				<div class="lc_tiers">
					<div class="lc_tier lc_tier_h"><div>Әр нешінші</div><div>Сыйлық атауы</div><div>Тегін тағам</div><div>Саны</div><div>Не жеңілдік</div><div>Ең көбі, тг</div><div></div></div>
					<? foreach ($tl as $i => $t): $hp = $t['product_id'] > 0; ?>
					<? if (!$t['id']): ?><div class="lc_tier_cap"><i class="fal fa-plus"></i> Жаңа сыйлық қосу</div><? endif ?>
					<div class="lc_tier<?=($t['id'] ? '' : ' lc_tier_new')?><?=($hp ? ' lc_tier_prod' : '')?>">
						<input type="hidden" name="tier[<?=$i?>][id]" value="<?=(int)$t['id']?>">
						<div class="lc_num"><input type="number" name="tier[<?=$i?>][every]" class="cl_inp lc_t_every" min="2" max="500" value="<?=$t['every']?>" placeholder="N"><span class="lc_t_ord"><?=$ord($t['every'])?></span></div>
						<input type="text" name="tier[<?=$i?>][name]" class="cl_inp" maxlength="255" value="<?=htmlspecialchars((string)$t['name'])?>" placeholder="<?=($t['id'] ? '' : 'Мысалы: Цезарь тегін')?>">
						<select name="tier[<?=$i?>][product_id]" class="cl_inp lc_t_prod">
							<option value="0">— тағамсыз (жеңілдік) —</option>
							<? if ($t['product_id'] && !in_array($t['product_id'], array_column($prods, 'id'))): $pa = mysqli_fetch_assoc(db::query("select name_kz, name_ru from product where id = '".(int)$t['product_id']."'")); ?>
								<option value="<?=(int)$t['product_id']?>" selected><?=htmlspecialchars(($pa ? ($pa['name_kz'] ?: $pa['name_ru']) : '#'.$t['product_id']).' (мәзірде жоқ)')?></option>
							<? endif ?>
							<? foreach ($prods as $p): ?><option value="<?=$p['id']?>"<?=($p['id'] == $t['product_id'] ? ' selected' : '')?>><?=htmlspecialchars($p['name_kz'] ?: $p['name_ru'])?> · <?=(int)$p['price']?> тг</option><? endforeach ?>
						</select>
						<input type="number" name="tier[<?=$i?>][quantity]" class="cl_inp lc_t_q" min="1" max="20" value="<?=max(1, (int)$t['quantity'])?>"<?=($hp ? '' : ' disabled')?>>
						<div class="lc_num"><input type="number" name="tier[<?=$i?>][discount_percent]" class="cl_inp lc_t_d" min="0" max="100" value="<?=($t['discount_percent'] ?: '')?>"<?=($hp ? ' disabled' : '')?>><span>%</span></div>
						<input type="number" name="tier[<?=$i?>][discount_max]" class="cl_inp lc_t_d" min="0" value="<?=($t['discount_max'] ?: '')?>" placeholder="шексіз"<?=($hp ? ' disabled' : '')?>>
						<? if ($t['id']): ?><input type="hidden" name="tier[<?=$i?>][del]" value="0" class="lc_del_v"><button type="button" class="lc_del" aria-label="Сыйлықты жою"><i class="fal fa-trash"></i></button>
						<div class="lc_del_note">Сақтағанда жойылады · <a href="#" class="lc_undo">Қайтару</a></div><? else: ?><div></div><? endif ?>
					</div>
					<? endforeach ?>
				</div>
				<label class="lc_chk"><input type="checkbox" name="gift_reset" value="1"> Санауды бүгіннен бастау (жаңа ай)</label>
				<div class="cl_small">Мысалы 5 жазсаңыз, клиенттің әр 5-ші тапсырысына сыйлық беріледі. <span class="lc_q" tabindex="0">?<span>Отказдар саналмайды. Бір тапсырысқа бірнеше сыйлық сәйкес келсе, үлкені беріледі. Тағам таңдалса, ол тапсырысқа 0 тг болып қосылады; таңдалмаса, жеңілдік беріледі. Жою үшін себетті белгілеп сақтаңыз.</span></span></div>
			<? else: $t0 = @$cfg['tiers'][0]; ?>
				<div class="lc_row">
					<label class="cl_lbl">Нешінші тапсырыс тегін сыйлықпен</label>
					<div class="lc_num"><input type="number" name="gift_every" class="cl_inp" min="0" max="100" value="<?=(int)@$t0['every']?>"><span>-ші</span></div>
				</div>
				<div class="lc_row">
					<label class="cl_lbl">Сыйлық</label>
					<input type="text" name="gift_name" class="cl_inp lc_w_name" maxlength="255" value="<?=htmlspecialchars((string)@$t0['name'])?>" placeholder="Мысалы: Пепперони тегін">
				</div>
				<label class="lc_chk"><input type="checkbox" name="gift_reset" value="1"> Санауды бүгіннен бастау (жаңа ай)</label>
			<? endif ?>
				<? if ($cfg['gift'] && $cfg['gift_from']): ?><div class="cl_small">Санау басталды: <?=clients::dt($cfg['gift_from'])?></div><? endif ?>
			</div>
			<div class="lc_save"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> Сақтау</button></div>
		</div>
	</form>

	<div class="cl_cards cl_cards_3 lc_cards">
		<div class="cl_card"><div class="cl_card_h">Осы айда берілді</div><div class="cl_card_v cl_c_green"><?=$st['gifts_m']?></div><div class="cl_card_s">сыйлық</div></div>
		<div class="cl_card"><div class="cl_card_h">Барлығы берілді</div><div class="cl_card_v"><?=$st['gifts']?></div><div class="cl_card_s">сыйлық</div></div>
		<div class="cl_card"><div class="cl_card_h">Белсенді сыйлық түрі</div><div class="cl_card_v"><?=$st['tiers']?></div><div class="cl_card_s"><?=($cfg['gift'] ? 'қосулы' : 'өшірулі')?></div></div>
	</div>

	<div class="cl_box cl_table lc_tbl lc_gtbl">
		<div class="cl_box_h"><div><i class="fal fa-list"></i> Соңғы берілген сыйлықтар</div></div>
		<div class="cl_tr cl_th">
			<div class="cl_td">Күні</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td">Сыйлық</div>
			<div class="cl_td lc_r">Нешінші</div>
		</div>
		<? $has = false; while ($last && $g = mysqli_fetch_assoc($last)): $has = true; ?>
			<div class="cl_tr">
				<div class="cl_td" data-h="Күні"><?=clients::dt($g['ins_dt'])?></div>
				<div class="cl_td cl_td_name"><a href="/clients/item.php?id=<?=$g['client_id']?>"><?=($g['name'] ? htmlspecialchars($g['name']) : clients::phone_view($g['phone']))?></a></div>
				<div class="cl_td" data-h="Тапсырыс"><?=($g['number'] ? '#'.htmlspecialchars($g['number']) : '—')?></div>
				<div class="cl_td" data-h="Сыйлық"><?=htmlspecialchars((string)$g['gift_name'])?><?=((int)$g['discount'] > 0 ? ' <small>−'.clients::sum($g['discount']).'</small>' : '')?></div>
				<div class="cl_td lc_r" data-h="Нешінші"><?=($g['order_no'] ? (int)$g['order_no'].$ord($g['order_no']) : '—')?></div>
			</div>
		<? endwhile ?>
		<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
	</div>

	<? endif ?>
</div>

<? include "../block/footer.php"; ?>
