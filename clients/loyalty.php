<? include "../config/core.php";
	include "lib.php";
	require_once "../config/loyalty.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Лоялдық баптауы: кэшбэк пайызы және N-ші тапсырысқа сыйлық
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$ready = loyalty::on();
	$msg = '';

	if ($ready && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $msg = 'Бетті жаңартып, қайта көріңіз';
		else {
			$old = loyalty::cfg($cmp);
			$pct = round(max(0, min(50, (float)str_replace(',', '.', (string)@$_POST['cashback_percent']))), 2);
			$every = max(0, min(100, (int)@$_POST['gift_every']));
			if ($every == 1) $every = 2; // әр тапсырысқа сыйлық болмайды
			$gname = mb_substr(trim(strip_tags((string)@$_POST['gift_name'])), 0, 255);
			if ($gname === '') $every = 0;
			$e = function ($v) { return mysqli_real_escape_string(db::$connect, $v); };
			$now = date('Y-m-d H:i:s');
			$set = ["cashback_percent = '$pct'", "gift_every = '$every'", "gift_name = ".($gname !== '' ? "'".$e($gname)."'" : "NULL")];
			// 0-ден қосылса — осы сәттен бастап есептеледі (ескі тапсырыстарға кэшбэк жоқ)
			if ($pct > 0 && !$old['cashback']) $set[] = "cashback_from = '$now'";
			if ($every > 1 && !$old['gift']) $set[] = "gift_from = '$now'";
			// санауды нөлдеу (мысалы жаңа ай)
			if ($every > 1 && @$_POST['gift_reset']) $set[] = "gift_from = '$now'";
			db::query("update company set ".implode(', ', $set)." where id = '$cmp'");
			header('location: /clients/loyalty.php?saved=1'); exit;
		}
	}
	if (@$_GET['saved']) $msg = 'Сақталды';

	$cfg = loyalty::cfg($cmp);
	if ($ready) loyalty::sync($cmp);

	// Қысқа есеп
	$st = ['balance' => 0, 'clients' => 0, 'earn_m' => 0, 'spend_m' => 0, 'gifts_m' => 0, 'gifts' => 0];
	$moves = false;
	if ($ready) {
		$m0 = date('Y-m-01 00:00:00');
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(cashback_balance), 0) b, sum(cashback_balance > 0) n from company_client where company_id = '$cmp'"));
		$st['balance'] = (int)$r['b']; $st['clients'] = (int)$r['n'];
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(case when type in ('earn','cancel') then amount end), 0) e, coalesce(-sum(case when type in ('spend','refund') then amount end), 0) s from company_client_cashback where company_id = '$cmp' and ins_dt >= '$m0'"));
		$st['earn_m'] = (int)$r['e']; $st['spend_m'] = (int)$r['s'];
		$r = mysqli_fetch_assoc(db::query("select count(*) n, sum(ins_dt >= '$m0') m from company_client_gift where company_id = '$cmp'"));
		$st['gifts'] = (int)$r['n']; $st['gifts_m'] = (int)$r['m'];
		$moves = db::query("select k.*, c.name, c.phone, o.number from company_client_cashback k join company_client c on c.id = k.client_id left join retail_orders o on o.id = k.order_id
			where k.company_id = '$cmp' order by k.id desc limit 50");
	}
	$tnames = ['earn' => 'Түсті', 'spend' => 'Жұмсалды', 'cancel' => 'Қайтарылды (статус)', 'refund' => 'Клиентке қайтты', 'manual' => 'Қолмен'];

	// site setting
	$menu_name = 'clients';
	$page_name = 'Лоялдық: кэшбэк және сыйлық';
	$css = ['clients', 'loyalty'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page lc_page">

	<a class="cl_back" href="/clients/"><i class="fal fa-arrow-left"></i> Клиенттер</a>

	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Базаға кэшбэк кестелері әлі қосылмаған (berik_cashback.sql).</div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg"><?=htmlspecialchars($msg)?></div><? endif ?>

	<form class="lc_grid" method="post" autocomplete="off">
		<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">

		<div class="cl_box lc_set">
			<div class="cl_box_h"><div><i class="fal fa-coins"></i> Кэшбэк</div><span class="cl_seg <?=($cfg['cashback'] ? 'cl_seg_loyal' : 'cl_seg_archive')?>"><?=($cfg['cashback'] ? 'Қосулы' : 'Өшірулі')?></span></div>
			<label class="cl_lbl">Әр тапсырыстан қанша пайыз</label>
			<div class="lc_inp_w"><input type="number" name="cashback_percent" class="cl_inp" min="0" max="50" step="0.5" value="<?=rtrim(rtrim(number_format($cfg['percent'], 2, '.', ''), '0'), '.')?>"><span>%</span></div>
			<div class="cl_small">0 болса кэшбэк мүлде жоқ: жиналмайды, кассада да, клиент кабинетінде де көрінбейді.
				Тағам сомасынан есептеледі (доставка кірмейді), тапсырыс «Аяқталды» болғанда түседі. Кэшбэкті тек осы компанияда жұмсауға болады.</div>
			<? if ($cfg['cashback'] && $cfg['from']): ?><div class="cl_small">Басталды: <?=clients::dt($cfg['from'])?></div><? endif ?>
		</div>

		<div class="cl_box lc_set">
			<div class="cl_box_h"><div><i class="fal fa-gift"></i> N-ші тапсырысқа сыйлық</div><span class="cl_seg <?=($cfg['gift'] ? 'cl_seg_loyal' : 'cl_seg_archive')?>"><?=($cfg['gift'] ? 'Қосулы' : 'Өшірулі')?></span></div>
			<label class="cl_lbl">Нешінші тапсырыс тегін сыйлықпен</label>
			<div class="lc_inp_w"><input type="number" name="gift_every" class="cl_inp" min="0" max="100" value="<?=$cfg['gift_every']?>"><span>-ші</span></div>
			<label class="cl_lbl">Сыйлық</label>
			<input type="text" name="gift_name" class="cl_inp" maxlength="255" value="<?=htmlspecialchars($cfg['gift_name'])?>" placeholder="Мысалы: Пепперони тегін">
			<label class="lc_chk"><input type="checkbox" name="gift_reset" value="1"> Санауды бүгіннен бастау (жаңа ай)</label>
			<div class="cl_small">0 болса сыйлық жүйесі жоқ. Сыйлықты кез келген уақытта ауыстыруға болады. Кассада N-ші тапсырыста «Сыйлық» белгісі шығады.</div>
			<? if ($cfg['gift'] && $cfg['gift_from']): ?><div class="cl_small">Санау басталды: <?=clients::dt($cfg['gift_from'])?></div><? endif ?>
		</div>

		<div class="lc_save"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> Сақтау</button></div>
	</form>

	<div class="cl_cards cl_cards_3">
		<div class="cl_card"><div class="cl_card_h">Клиенттердегі кэшбэк</div><div class="cl_card_v cl_c_green"><?=clients::sum($st['balance'])?></div><div class="cl_card_s"><?=$st['clients']?> клиентте</div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айда түсті / жұмсалды</div><div class="cl_card_v cl_v_sm"><?=clients::sum($st['earn_m'])?> / <?=clients::sum($st['spend_m'])?></div></div>
		<div class="cl_card"><div class="cl_card_h">Берілген сыйлық</div><div class="cl_card_v"><?=$st['gifts_m']?></div><div class="cl_card_s">осы айда · барлығы <?=$st['gifts']?></div></div>
	</div>

	<div class="cl_box cl_table">
		<div class="cl_box_h"><div>Соңғы қозғалыстар</div></div>
		<div class="cl_tr cl_th">
			<div class="cl_td">Күні</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td">Түрі</div>
			<div class="cl_td">Сома</div>
			<div class="cl_td">Балансы</div>
		</div>
		<? $has = false; while ($moves && $k = mysqli_fetch_assoc($moves)): $has = true; ?>
			<div class="cl_tr">
				<div class="cl_td" data-h="Күні"><?=clients::dt($k['ins_dt'])?></div>
				<div class="cl_td cl_td_name"><a href="/clients/item.php?id=<?=$k['client_id']?>"><?=($k['name'] ? htmlspecialchars($k['name']) : clients::phone_view($k['phone']))?></a></div>
				<div class="cl_td" data-h="Тапсырыс"><?=($k['number'] ? '#'.$k['number'] : '—')?></div>
				<div class="cl_td" data-h="Түрі"><?=@$tnames[$k['type']] ?: htmlspecialchars($k['type'])?></div>
				<div class="cl_td" data-h="Сома"><b class="<?=($k['amount'] > 0 ? 'cl_c_green' : '')?>"><?=($k['amount'] > 0 ? '+' : '−').clients::sum(abs($k['amount']))?></b></div>
				<div class="cl_td" data-h="Балансы"><?=clients::sum($k['balance_after'])?></div>
			</div>
		<? endwhile ?>
		<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
	</div>

	<? endif ?>
</div>

<? include "../block/footer.php"; ?>
