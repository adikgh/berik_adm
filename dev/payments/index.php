<?php include "../config/core.php";
	dev_only('payments');
	$menu = 'payments'; $page_title = t::w('Payments', 'Төлемдер'); $page_sub = t::w('Company payments to BERIK POS', 'Компаниялардың BERIK POS-қа төлемдері');
	$m = preg_match('/^\d{4}-\d{2}$/', (string)@$_GET['m']) ? $_GET['m'] : date('Y-m');
	$from = "$m-01"; $to = date('Y-m-01', strtotime("$from +1 month"));
	$tar = dev::tariffs(true);
	$pays = dev::rows("select p.*, c.name cname, u.name uname from company_payment p left join company c on c.id = p.company_id left join user u on u.id = p.user_id
		where p.paid_dt >= '$from' and p.paid_dt < '$to' and p.method <> 'trial' order by p.paid_dt desc, p.id desc");
	$sum = 0; $by = [];
	foreach ($pays as $p) if (!$p['arh']) { if ($p['method'] != 'bonus') $sum += $p['amount']; $by[$p['method']] = ($by[$p['method']] ?? 0) + $p['amount']; }
	// 12 айдың түсімі
	$year = dev::rows("select date_format(paid_dt, '%Y-%m') ym, sum(amount) s from company_payment where arh = 0 and method <> 'bonus' and paid_dt >= '" . date('Y-m-01', strtotime("$from -11 month")) . "' and paid_dt < '$to' group by ym", 'ym');
	$max = 1; foreach ($year as $r) $max = max($max, (int)$r['s']);
?>
<? include "../block/header.php"; ?>
	<div class="tools">
		<div class="tabs">
			<a href="?m=<?=date('Y-m', strtotime("$from -1 month"))?>">‹</a>
			<a class="on"><?=dev::month(date('n', strtotime($from)))?> <?=date('Y', strtotime($from))?></a>
			<a href="?m=<?=date('Y-m', strtotime("$from +1 month"))?>">›</a>
		</div>
	</div>
	<div class="kpis">
		<div class="kpi g"><small><?=t::w('Revenue', 'Түсім')?></small><b><?=dev::money($sum)?></b></div>
		<? foreach (dev::$methods as $k => $t): if (empty($by[$k])) continue; ?><div class="kpi"><small><?=$t?></small><b><?=dev::money($by[$k])?></b></div><? endforeach ?>
		<div class="kpi"><small><?=t::w('Number of payments', 'Төлем саны')?></small><b><?=count(array_filter($pays, function ($p) { return !$p['arh']; }))?></b></div>
	</div>
	<div class="card">
		<div class="card_h"><h2><?=t::w('Last 12 months', 'Соңғы 12 ай')?></h2></div>
		<div style="display:flex;gap:6px;align-items:flex-end;height:140px">
		<? for ($i = 11; $i >= 0; $i--): $ym = date('Y-m', strtotime("$from -$i month")); $v = (int)@$year[$ym]['s']; ?>
			<a href="?m=<?=$ym?>" title="<?=dev::money($v)?>" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end">
				<span style="width:100%;max-width:38px;border-radius:6px 6px 0 0;background:<?=$ym == $m ? 'var(--g)' : '#cdeedd'?>;height:<?=max(2, round($v / $max * 110))?>px"></span>
				<small><?=mb_substr(dev::month(date('n', strtotime("$ym-01"))), 0, 3)?></small>
			</a>
		<? endfor ?>
		</div>
	</div>
	<div class="card">
		<div class="card_h"><h2><?=t::w('Payments', 'Төлемдер')?></h2><input type="search" placeholder="<?=t::w('Search', 'Іздеу')?>" data-filter=".py_t tbody tr" style="max-width:240px"></div>
		<? if (!$pays): ?><div class="empty"><?=t::w('No payments this month', 'Бұл айда төлем жоқ')?></div><? else: ?>
		<div class="tbl_w"><table class="tbl py_t">
			<thead><tr><th><?=t::w('Date', 'Күні')?></th><th><?=t::w('Company', 'Компания')?></th><th><?=t::w('Tariff', 'Тариф')?></th><th><?=t::w('Months', 'Айлар')?></th><th><?=t::w('Period', 'Кезең')?></th><th><?=t::w('Method', 'Түрі')?></th><th><?=t::w('Added by', 'Енгізген')?></th><th class="num"><?=t::w('Amount', 'Сома')?></th></tr></thead>
			<tbody><? foreach ($pays as $p): ?>
				<tr class="lnk <?=$p['arh'] ? 'off' : ''?>" data-href="/companies/item.php?id=<?=$p['company_id']?>">
					<td><?=dev::d($p['paid_dt'])?></td><td><b><?=dev::h($p['cname'])?></b></td><td><?=dev::h(@$tar[$p['tariff_id']]['name'])?></td>
					<td><?=$p['months']?><?=$p['bonus_months'] ? ' <small>+' . $p['bonus_months'] . '</small>' : ''?></td>
					<td style="white-space:nowrap"><?=dev::d($p['date_from'])?> – <?=dev::d($p['date_to'])?></td><td><?=dev::h(@dev::$methods[$p['method']])?></td>
					<td><?=dev::h($p['uname'])?></td><td class="num"><b><?=dev::money($p['amount'])?></b></td>
				</tr>
			<? endforeach ?></tbody>
		</table></div>
		<? endif ?>
	</div>
<? include "../block/footer.php"; ?>
