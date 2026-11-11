<?php
// Бір шақырушының беті: $ref (company_ref жолы), $edit (басқару батырмалары)
$b = dev::ref_balance($ref['id']);
$rc = dev::rows("select c.id, c.name, c.city, s.paid_until from company_sub s join company c on c.id = s.company_id where s.ref_id = '" . (int)$ref['id'] . "' order by c.name");
$earn = dev::rows("select company_id, sum(amount) s, max(month_no) mx from company_ref_bonus where ref_id = '" . (int)$ref['id'] . "' group by company_id", 'company_id');
$hist = dev::rows("select b.*, c.name cname from company_ref_bonus b left join company c on c.id = b.company_id where b.ref_id = '" . (int)$ref['id'] . "' order by b.id desc limit 100");
$outs = dev::rows("select * from company_ref_out where ref_id = '" . (int)$ref['id'] . "' order by id desc");
$link = dev::ref_link($ref['nick']);
?>
	<div class="kpis">
		<div class="kpi"><small><?=t::w('Companies brought', 'Әкелген компания')?></small><b><?=count($rc)?></b></div>
		<div class="kpi"><small><?=t::w('Total bonus', 'Барлық бонус')?></small><b><?=dev::money($b['in'])?></b></div>
		<div class="kpi"><small><?=t::w('Paid out / used', 'Берілді / төленді')?></small><b><?=dev::money($b['out'])?></b></div>
		<div class="kpi g"><small><?=t::w('Balance', 'Қалдығы')?></small><b><?=dev::money($b['left'])?></b></div>
	</div>
	<div class="card">
		<div class="card_h"><h2><?=t::w('Referral link', 'Шақыру сілтемесі')?></h2></div>
		<p style="margin:0 0 8px"><span class="code copy" data-copy="<?=dev::h($link)?>" title="<?=t::w('Copy', 'Көшіру')?>"><?=dev::h($link)?> <i class="far fa-copy"></i></span></p>
		<p class="mut" style="margin:0;font-size:14px"><?=sprintf(t::w('If a person leaves a request through this link, or writes the code %s in the request, the company is assigned to you.', 'Адам осы сілтемемен кіріп өтінім қалдырса, не өтінімде %s кодын жазса, компания сізге жазылады.'), '<b>' . dev::h($ref['nick']) . '</b>')?>
		<?=t::w('Bonus: first 3 months 30%, months 4-12 10%, months 13-24 5%, then 1%. It can be taken as cash or used to pay your own company subscription.', 'Бонус: алғашқы 3 ай 30%, 4-12 ай 10%, 13-24 ай 5%, одан кейін 1%. Ақшалай алуға не өз компанияңыздың жазылымына төлеуге болады.')?></p>
	</div>
	<div class="grid g2 mt">
		<div class="card">
			<div class="card_h"><h2><?=t::w('Brought companies', 'Әкелген компаниялар')?></h2></div>
			<? if (!$rc): ?><div class="empty"><?=t::w('None yet', 'Әзірге жоқ')?></div><? else: ?>
			<table class="tbl"><tbody><? foreach ($rc as $c): $st = dev::status($c['paid_until']); ?>
				<tr <?=$edit ? 'class="lnk" data-href="/companies/item.php?id=' . $c['id'] . '"' : ''?>>
					<td class="nm"><b><?=dev::h($c['name'])?></b><small><?=dev::h($c['city'])?><?=isset($earn[$c['id']]) ? ' · ' . sprintf(t::w('paid %d months', '%d ай төледі'), (int)$earn[$c['id']]['mx']) : ''?></small></td>
					<td><span class="st st_<?=$st[0]?>"><?=$st[1]?></span></td>
					<td class="num"><b><?=dev::money(@$earn[$c['id']]['s'])?></b></td>
				</tr>
			<? endforeach ?></tbody></table>
			<? endif ?>
		</div>
		<div class="card">
			<div class="card_h"><h2><?=t::w('Bonus paid out', 'Берілген бонус')?></h2></div>
			<? if ($edit && dev::can('ref_pay')): ?>
			<form data-post="/ref/get.php?ref_out" data-ask="<?=t::w('Record the bonus as paid in cash?', 'Бонусты ақшалай бердік деп жазамыз ба?')?>" class="f_row" style="align-items:end">
				<input type="hidden" name="ref_id" value="<?=$ref['id']?>">
				<label><?=t::w('Amount', 'Сома')?>, ₸<input name="amount" inputmode="numeric" value="<?=max(0, $b['left'])?>"></label>
				<label><?=t::w('Note', 'Ескертпе')?><input name="comment" placeholder="Kaspi"></label>
				<button class="btn btn_g" type="submit"><?=t::w('Cash paid out', 'Ақша берілді')?></button>
			</form>
			<? endif ?>
			<? if (!$outs): ?><div class="empty"><?=t::w('Nothing paid out yet', 'Әзірге берілмеген')?></div><? else: ?>
			<table class="tbl"><tbody><? foreach ($outs as $o): ?>
				<tr><td><?=dev::d($o['ins_dt'])?></td><td><?=$o['kind'] == 'sub' ? t::w('Used for subscription', 'Жазылымға төленді') : t::w('Cash', 'Ақшалай')?><?=$o['comment'] && $o['kind'] != 'sub' ? '<br><small>' . dev::h($o['comment']) . '</small>' : ''?></td>
					<td class="num"><b><?=dev::money($o['amount'])?></b></td>
					<td><? if ($edit && dev::can('ref_pay') && $o['kind'] == 'cash'): ?><button class="btn btn_r btn_s" data-act="/ref/get.php?out_del" data-id="<?=$o['id']?>" data-ask="<?=t::w('Delete?', 'Өшіреміз бе?')?>">×</button><? endif ?></td></tr>
			<? endforeach ?></tbody></table>
			<? endif ?>
		</div>
	</div>
	<div class="card mt">
		<div class="card_h"><h2><?=t::w('Bonus history', 'Бонус тарихы')?></h2><small><?=t::w('last 100', 'соңғы 100')?></small></div>
		<? if (!$hist): ?><div class="empty"><?=t::w('No bonus yet', 'Әзірге бонус түспеген')?></div><? else: ?>
		<div class="tbl_w"><table class="tbl"><thead><tr><th><?=t::w('Date', 'Күні')?></th><th><?=t::w('Company', 'Компания')?></th><th><?=t::w('Month no.', 'Неше-ші ай')?></th><th class="num">%</th><th class="num"><?=t::w('Bonus', 'Бонус')?></th></tr></thead><tbody>
		<? foreach ($hist as $h): ?><tr><td><?=dev::d($h['ins_dt'])?></td><td><?=dev::h($h['cname'])?></td><td><?=(int)$h['month_no']?></td><td class="num"><?=(float)$h['pct']?>%</td><td class="num"><b><?=dev::money($h['amount'])?></b></td></tr><? endforeach ?>
		</tbody></table></div>
		<? endif ?>
	</div>
