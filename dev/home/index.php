<?php include "../config/core.php";
	dev_only('home');
	$menu = 'home'; $page_title = t::w('Overview', 'Шолу');
	$cs = dev::companies();
	$tar = dev::tariffs(true);
	$cnt = ['paid' => 0, 'trial' => 0, 'soon' => 0, 'debt' => 0, 'none' => 0]; $mrr = 0;
	foreach ($cs as $c) { $cnt[$c['st'][0]]++; if (in_array($c['st'][0], ['paid', 'soon']) && isset($tar[$c['tariff_id']])) $mrr += $tar[$c['tariff_id']]['price']; }
	$m0 = date('Y-m-01');
	$pay_m = (int)dev::one("select coalesce(sum(amount),0) from company_payment where arh = 0 and method <> 'bonus' and paid_dt >= '$m0'");
	$pay_y = (int)dev::one("select coalesce(sum(amount),0) from company_payment where arh = 0 and method <> 'bonus' and paid_dt >= '" . date('Y-01-01') . "'");
	$ref_owed = (int)dev::one("select coalesce(sum(amount),0) from company_ref_bonus") - (int)dev::one("select coalesce(sum(amount),0) from company_ref_out");
	$attn = array_filter($cs, function ($c) { return in_array($c['st'][0], ['soon', 'debt']); });
	uasort($attn, function ($a, $b) { return $a['st'][2] <=> $b['st'][2]; });
	$last = dev::rows("select p.*, c.name cname from company_payment p left join company c on c.id = p.company_id where p.arh = 0 and p.method <> 'trial' order by p.id desc limit 8");
?>
<? include "../block/header.php"; ?>
	<div class="kpis">
		<div class="kpi"><small><?=t::w('Companies', 'Компаниялар')?></small><b><?=count($cs)?></b></div>
		<div class="kpi g"><small><?=t::w('Paying', 'Төлеген')?></small><b><?=$cnt['paid'] + $cnt['soon']?></b></div>
		<div class="kpi b"><small><?=t::w('On free trial', 'Тегін сынақта')?></small><b><?=$cnt['trial']?></b></div>
		<div class="kpi o"><small><?=t::w('Ends soon', 'Жақында бітеді')?></small><b><?=$cnt['soon']?></b></div>
		<div class="kpi r"><small><?=t::w('Overdue', 'Мерзімі өткен')?></small><b><?=$cnt['debt']?></b></div>
		<div class="kpi"><small><?=t::w('Without subscription', 'Жазылымсыз')?></small><b><?=$cnt['none']?></b></div>
	</div>
	<? if (dev::can('payments')): ?>
	<div class="kpis">
		<div class="kpi g"><small><?=t::w('Monthly revenue (active tariffs)', 'Айлық түсім (белсенді тарифтер)')?></small><b><?=dev::money($mrr)?></b></div>
		<div class="kpi"><small><?=t::w('Received this month', 'Осы айда түсті')?></small><b><?=dev::money($pay_m)?></b></div>
		<div class="kpi"><small><?=t::w('Received this year', 'Биыл түсті')?></small><b><?=dev::money($pay_y)?></b></div>
		<div class="kpi o"><small><?=t::w('Bonus owed to referrers', 'Шақырушыларға берілмеген бонус')?></small><b><?=dev::money($ref_owed)?></b></div>
	</div>
	<? endif ?>

	<div class="grid g12">
		<div class="card">
			<div class="card_h"><h2><?=t::w('Needs attention', 'Назар аудару керек')?></h2><a class="link" href="/companies/?st=debt"><?=t::w('All', 'Барлығы')?></a></div>
			<? if (!$attn): ?><div class="empty"><?=t::w('No overdue or ending companies', 'Мерзімі өткен не жақында бітетін компания жоқ')?></div><? else: ?>
			<div class="tbl_w"><table class="tbl"><tbody>
				<? foreach ($attn as $c): ?>
				<tr class="lnk" data-href="/companies/item.php?id=<?=$c['id']?>">
					<td class="nm"><b><?=dev::h($c['name'])?></b><small><?=dev::h($c['city'])?></small></td>
					<td><span class="st st_<?=$c['st'][0]?>"><?=$c['st'][1]?></span></td>
					<td class="num"><?=dev::days_txt($c['st'][2])?></td>
				</tr>
				<? endforeach ?>
			</tbody></table></div>
			<? endif ?>
		</div>
		<? if (dev::can('payments')): ?>
		<div class="card">
			<div class="card_h"><h2><?=t::w('Latest payments', 'Соңғы төлемдер')?></h2><a class="link" href="/payments/"><?=t::w('All', 'Барлығы')?></a></div>
			<? if (!$last): ?><div class="empty"><?=t::w('No payments yet', 'Әзірге төлем енгізілмеген')?></div><? else: ?>
			<div class="tbl_w"><table class="tbl"><tbody>
				<? foreach ($last as $p): ?>
				<tr class="lnk" data-href="/companies/item.php?id=<?=$p['company_id']?>">
					<td class="nm"><b><?=dev::h($p['cname'])?></b><small><?=dev::d($p['paid_dt'])?> · <?=$p['months']?> <?=t::w('mo.', 'ай')?><?=$p['bonus_months'] ? ' +' . $p['bonus_months'] : ''?></small></td>
					<td class="num"><?=dev::money($p['amount'])?></td>
				</tr>
				<? endforeach ?>
			</tbody></table></div>
			<? endif ?>
		</div>
		<? endif ?>
	</div>
<? include "../block/footer.php"; ?>
