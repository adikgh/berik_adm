<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Онлайн төлемдер: клиенттер мәзірден (QR / жеке кабинет) Kaspi арқылы тікелей төлеген төлемдер (xpayment).
	// Кілт пен касса dev панелінде, предоплата мен төлем түрлері — Компания баптаулары ▸ Онлайн төлем.
	$cmp = (int)$company;
	mysqli_report(MYSQLI_REPORT_OFF);
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
	$ready = mysqli_num_rows(db::query("SHOW TABLES LIKE 'retail_orders_draft'")) > 0;

	// Кезең: Күн | Апта | Ай | Жыл, ‹ › ауыстыру
	$per = in_array(@$_GET['p'], ['d', 'w', 'm', 'y'], true) ? $_GET['p'] : 'd';
	$off = min(0, (int)@$_GET['o']);
	$t = strtotime('today');
	if ($per == 'd') { $from = strtotime("$off day", $t); $to = strtotime('+1 day', $from); $title = date('d.m.Y', $from); }
	elseif ($per == 'w') { $mon = strtotime('monday this week', $t); $from = strtotime(($off * 7).' day', $mon); $to = strtotime('+7 day', $from); $title = date('d.m', $from).' – '.date('d.m.Y', $to - 86400); }
	elseif ($per == 'm') { $from = strtotime("$off month", strtotime(date('Y-m-01', $t))); $to = strtotime('+1 month', $from); $title = date('m.Y', $from); }
	else { $from = strtotime("$off year", strtotime(date('Y-01-01', $t))); $to = strtotime('+1 year', $from); $title = date('Y', $from); }
	$f = date('Y-m-d H:i:s', $from); $tt = date('Y-m-d H:i:s', $to);

	$rows = []; $sum = 0; $cnt = 0; $pre = 0;
	if ($ready && $cmp) {
		$q = db::query("select d.id, d.mode, d.total, d.amount, d.order_id, d.ins_dt, d.paid_dt, o.number, o.phone, o.order_status,
				(select x.status from retail_orders_xpay x where x.draft_id = d.id order by x.id desc limit 1) xst
			from retail_orders_draft d left join retail_orders o on o.id = d.order_id and d.order_id > 0
			where d.company_id = '$cmp' and d.ins_dt >= '$f' and d.ins_dt < '$tt'
			order by d.id desc limit 500");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$rows[] = $r;
			if ((int)$r['order_id'] > 0 || (int)$r['order_id'] == -2) { $sum += (int)$r['amount']; $cnt++; if ($r['mode'] == 'pre') $pre++; }
		}
	}
	$st = function ($r) {
		if ((int)$r['order_id'] > 0) return ['Төленді', '#0EA768'];
		if ((int)$r['order_id'] == -2) return ['Төленді, тапсырыс жасалмады', '#d64545'];
		$x = strtoupper((string)$r['xst']);
		if (in_array($x, ['EXPIRED', 'CANCELLED'])) return ['Төленбеді', '#9aa3a0'];
		if ($x == 'FAILED') return ['Қате', '#d64545'];
		return ['Күтуде', '#e0a100'];
	};
	$url = function ($p, $o) { return '/kaspi/?p='.$p.($o ? '&o='.$o : ''); };

	$menu_name = 'company';
	$page_name = 'Онлайн төлемдер';
	$page_key = 'Online payments';
	$page_back = '/company/#c_pay';
	$css = ['clients', 'loyalty', 'company'];
?>
<? include "../block/header.php"; ?>
<style>
.xp_top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin:0 0 12px}
.xp_seg{display:inline-flex;background:#eef1ef;border-radius:99px;padding:3px}
.xp_seg a{padding:7px 14px;border-radius:99px;color:#3d4642;font-weight:600;font-size:14px}
.xp_seg a.on{background:var(--green,#0EA768);color:#fff}
.xp_nav{display:inline-flex;align-items:center;gap:8px;font-weight:600}
.xp_nav a{display:inline-grid;place-items:center;width:32px;height:32px;border-radius:50%;background:#fff;border:1px solid #e6e9e7;color:#3d4642}
.xp_nav a.off{opacity:.35;pointer-events:none}
.xp_sum{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:0 0 12px}
.xp_sum div{background:#fff;border:1px solid #e6e9e7;border-radius:14px;padding:12px 14px}
.xp_sum span{display:block;font-size:13px;color:#6b7570}
.xp_sum b{font-size:20px;font-weight:600}
.xp_tb{width:100%;border-collapse:collapse;font-size:14px}
.xp_tb th{text-align:left;font-weight:600;color:#6b7570;font-size:13px;padding:8px 10px;border-bottom:1px solid #eef1ef}
.xp_tb td{padding:10px;border-bottom:1px solid #f1f3f2;vertical-align:top}
.xp_tb .r{text-align:right;white-space:nowrap}
.xp_st{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.xp_st i{width:8px;height:8px;border-radius:50%}
@media (max-width:640px){.xp_tb .m{display:none}}
</style>
<div class="bl_c cl_page lc_page">
	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Алдымен базаға xpay SQL-ын қосыңыз.</div></div>
	<? else: ?>
	<div class="xp_top">
		<div class="xp_seg"><? foreach (['d' => 'Күн', 'w' => 'Апта', 'm' => 'Ай', 'y' => 'Жыл'] as $k => $l): ?><a href="<?=$url($k, 0)?>"<?=($per == $k ? ' class="on"' : '')?>><?=$l?></a><? endforeach ?></div>
		<div class="xp_nav"><a href="<?=$url($per, $off - 1)?>"><i class="far fa-angle-left"></i></a><span><?=$h($title)?></span><a href="<?=$url($per, $off + 1)?>"<?=($off >= 0 ? ' class="off"' : '')?>><i class="far fa-angle-right"></i></a></div>
	</div>
	<div class="xp_sum">
		<div><span>Түскен сома</span><b><?=number_format($sum, 0, '.', ' ')?> тг</b></div>
		<div><span>Төлемдер</span><b><?=$cnt?></b></div>
		<div><span>Оның предоплата</span><b><?=$pre?></b></div>
	</div>
	<div class="cl_box">
		<? if (!$rows): ?><div class="cl_empty">Бұл кезеңде онлайн төлем жоқ</div>
		<? else: ?>
		<table class="xp_tb">
			<tr><th>Уақыты</th><th>Тапсырыс</th><th class="m">Түрі</th><th class="r">Төлем</th><th class="r m">Тапсырыс сомасы</th><th>Статус</th></tr>
			<? foreach ($rows as $r): [$sl, $sc] = $st($r); ?>
			<tr>
				<td><?=$h(date('d.m H:i', strtotime($r['paid_dt'] ?: $r['ins_dt'])))?></td>
				<td><?=((int)$r['order_id'] > 0 ? '№'.(int)$r['number'].($r['phone'] ? '<br><small>'.$h($r['phone']).'</small>' : '') : '—')?></td>
				<td class="m"><?=($r['mode'] == 'pre' ? 'Предоплата' : 'Толық')?></td>
				<td class="r"><b><?=number_format((int)$r['amount'], 0, '.', ' ')?> тг</b></td>
				<td class="r m"><?=number_format((int)$r['total'], 0, '.', ' ')?> тг</td>
				<td><span class="xp_st" style="color:<?=$sc?>"><i style="background:<?=$sc?>"></i><?=$sl?></span></td>
			</tr>
			<? endforeach ?>
		</table>
		<? endif ?>
	</div>
	<p class="cl_small">Ақша тікелей компанияның Kaspi шотына түседі. «Күтуде» — клиент төлем бетін ашты, бірақ әлі төлемеді; тапсырыс тек төлемнен кейін жасалады.</p>
	<? endif ?>
</div>
<? include "../block/footer.php"; ?>
