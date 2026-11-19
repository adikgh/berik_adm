<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Оплата: компанияның тарифі, мерзімі, тарифтер мен модульдер, төлем QR/сілтемесі (config/pay.php)
	$pay_qr = $pay_link = ''; $pay_wa = '';
	@include "../config/pay.php";
	$cmp = (int)$company;
	$c = mysqli_fetch_assoc(db::query("select id, name from company where id = '$cmp'"));

	$has = mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_tariff_module'")) > 0;
	$tariffs = []; $mods = []; $tmods = [];
	$tq = db::query("select * from company_tariff where arh = 0 order by number, id");
	if ($tq) while ($r = mysqli_fetch_assoc($tq)) $tariffs[(int)$r['id']] = $r;
	if ($has) {
		$q = db::query("select code, name from company_module where arh = 0 order by sort, id");
		while ($r = mysqli_fetch_assoc($q)) $mods[$r['code']] = $r['name'];
		$q = db::query("select tariff_id, module_code from company_tariff_module");
		while ($r = mysqli_fetch_assoc($q)) $tmods[(int)$r['tariff_id']][$r['module_code']] = 1;
	}

	$st = plan::state();
	$sub = plan::$sub;
	$cur_tid = $sub ? (int)$sub['tariff_id'] : 0;
	$cur_name = $cur_tid && isset($tariffs[$cur_tid]) ? $tariffs[$cur_tid]['name'] : (plan::$tariff['name'] ?? '');
	if (plan::$trial) $cur_name = t::w('Free trial', 'Тегін сынақ') . ($cur_name ? ' · ' . $cur_name : '');
	$days = plan::$days;
	$st_txt = [
		'active' => t::w('Active', 'Белсенді'),
		'warn' => t::w('Ends soon', 'Жақында бітеді'),
		'grace' => t::w('Expired', 'Мерзімі өтті'),
		'expired' => t::w('Expired', 'Мерзімі өтті'),
		'none' => t::w('Not connected', 'Қосылмаған'),
	][$st];
	if ($days === null) $days_txt = '—';
	elseif ($days > 0) $days_txt = sprintf(t::w('%d days left', '%d күн қалды'), $days);
	elseif ($days == 0) $days_txt = t::w('Last day today', 'Бүгін соңғы күн');
	else $days_txt = sprintf(t::w('%d days overdue', '%d күн өтті'), -$days);

	$lnames = ['kassa' => t::w('Cash desks', 'Касса'), 'branch' => t::w('Branches', 'Филиал'), 'dish' => t::w('Dishes', 'Тағам'), 'order_month' => t::w('Orders per month', 'Тапсырыс / ай')];
	$lim_txt = function ($n) { return (int)$n ? number_format((int)$n, 0, '.', ' ') : '∞'; };
	$money = function ($n) { return number_format((int)$n, 0, '.', ' ') . ' ₸'; };

	// QR: сайттағы жол не https сілтеме
	$qr_src = '';
	if ($pay_qr !== '' && (preg_match('~^https?://~i', $pay_qr) || is_file($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($pay_qr, '/')))) $qr_src = $pay_qr;
	$link_ok = $pay_link !== '' && preg_match('~^https?://~i', $pay_link);
	$wa = preg_replace('/\D/', '', (string)$pay_wa);
	if (strlen($wa) == 11 && $wa[0] == '8') $wa = '7' . substr($wa, 1);
	$wa_show = strlen($wa) == 11 ? '8 (' . substr($wa, 1, 3) . ') ' . substr($wa, 4, 3) . '-' . substr($wa, 7, 2) . '-' . substr($wa, 9, 2) : $wa;
	$wa_text = 'Сәлеметсіз бе, Berik POS төлемінің чегін жіберемін. Компания: ' . (string)@$c['name'];
	$wa_url = $wa ? 'https://wa.me/' . $wa . '?text=' . rawurlencode($wa_text) : '';

	// site setting
	$menu_name = 'pay';
	$page_name = t::w('Plan payment', 'Оплата');
	$css = ['clients', 'pay'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page pay_page">

	<div class="cl_box pay_cur pay_st_<?=$st?>">
		<div class="cl_box_h"><span><i class="fal fa-id-card"></i> <?=t::w('Your plan', 'Сіздің тарифіңіз')?></span><span class="pay_badge"><?=htmlspecialchars($st_txt)?></span></div>
		<? if (!$sub): ?>
			<p class="pay_mut"><?=t::w('The subscription is not connected yet. Choose a tariff below and pay, we will connect it.', 'Жазылым әлі қосылмаған. Төменнен тарифті таңдап төлеңіз, біз қосып береміз.')?></p>
		<? else: ?>
			<div class="pay_kpi">
				<div><small><?=t::w('Tariff', 'Тариф')?></small><b><?=htmlspecialchars($cur_name ?: '—')?></b></div>
				<div><small><?=t::w('Paid until', 'Төленген мерзім')?></small><b><?=date('d.m.Y', strtotime($sub['paid_until']))?></b></div>
				<div><small><?=t::w('Days left', 'Қалған күн')?></small><b><?=htmlspecialchars($days_txt)?></b></div>
			</div>
		<? endif ?>
	</div>

	<div class="cl_box">
		<div class="cl_box_h"><span><i class="fal fa-layer-group"></i> <?=t::w('Tariffs', 'Тарифтер')?></span></div>
		<div class="pay_tars">
		<? foreach ($tariffs as $tid => $t): $tm = $tmods[$tid] ?? []; ?>
			<div class="pay_tar <?=($sub && !plan::$trial && $tid == $cur_tid) ? 'pay_tar_cur' : ''?>">
				<div class="pay_tar_h"><b><?=htmlspecialchars($t['name'])?></b><? if ($sub && !plan::$trial && $tid == $cur_tid): ?><span class="pay_badge"><?=t::w('Current', 'Қазіргі')?></span><? endif ?></div>
				<div class="pay_price"><?=$money($t['price'])?> <small>/ <?=t::w('mo.', 'ай')?></small></div>
				<? if ($t['descr']): ?><p class="pay_mut"><?=htmlspecialchars($t['descr'])?></p><? endif ?>
				<? if ($has): ?>
					<div class="pay_lims">
						<? foreach ($lnames as $k => $n): ?><span><?=$n?>: <b><?=$lim_txt(@$t['lim_' . $k])?></b></span><? endforeach ?>
					</div>
					<ul class="pay_mods">
						<? foreach ($mods as $code => $mn): ?><li class="<?=isset($tm[$code]) ? 'on' : 'off'?>"><i class="fal <?=isset($tm[$code]) ? 'fa-check' : 'fa-times'?>"></i> <?=htmlspecialchars($mn)?></li><? endforeach ?>
					</ul>
				<? endif ?>
			</div>
		<? endforeach ?>
		</div>
		<p class="pay_mut"><?=t::w('Longer payment gets gift months: 6 → +1, 12 → +2, 24 → +6.', 'Ұзақ мерзімге төлесеңіз сыйлық ай қосылады: 6 ай → +1, 12 ай → +2, 24 ай → +6.')?></p>
	</div>

	<div class="cl_box">
		<div class="cl_box_h"><span><i class="fal fa-wallet"></i> <?=t::w('Payment', 'Төлем')?></span></div>
		<div class="pay_box">
			<div class="pay_qr">
				<? if ($qr_src): ?>
					<img src="<?=htmlspecialchars($qr_src)?>" alt="<?=t::w('Payment QR', 'Төлем QR коды')?>" loading="lazy">
				<? else: ?>
					<div class="pay_ph"><i class="fal fa-qrcode"></i><span><?=t::w('QR code will be added soon', 'QR код жақында қосылады')?></span></div>
				<? endif ?>
			</div>
			<div class="pay_info">
				<p><?=t::w('Scan the QR code or open the payment link. Specify your company name in the comment.', 'QR кодты сканерлеңіз не төлем сілтемесін ашыңыз. Түсініктемеге компания атауын жазыңыз.')?></p>
				<? if ($link_ok): ?>
					<a class="cl_btn pay_btn pay_kaspi" href="<?=htmlspecialchars($pay_link)?>" target="_blank" rel="noopener"><i class="fal fa-external-link"></i> <?=t::w('Pay via Kaspi', 'Kaspi арқылы төлеу')?></a>
				<? else: ?>
					<span class="cl_btn cl_btn_light pay_btn pay_off"><i class="fal fa-link"></i> <?=t::w('Payment link will be added soon', 'Төлем сілтемесі жақында қосылады')?></span>
				<? endif ?>
				<? if ($wa_url): ?>
					<div class="pay_wa">
						<p><b><?=t::w('After paying, send the receipt to WhatsApp', 'Төлегеннен кейін чекті WhatsApp-қа жіберіңіз')?>:</b> <?=htmlspecialchars($wa_show)?>. <?=t::w('We will extend the subscription after checking it.', 'Тексерген соң жазылымды ұзартамыз.')?></p>
						<a class="cl_btn pay_wa_btn" href="<?=htmlspecialchars($wa_url)?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> <?=t::w('Send receipt on WhatsApp', 'Чекті WhatsApp-қа жіберу')?></a>
					</div>
				<? endif ?>
			</div>
		</div>
	</div>

</div>

<? include "../block/footer.php"; ?>
