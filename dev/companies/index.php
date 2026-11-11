<?php include "../config/core.php";
	dev_only('companies');
	$menu = 'companies'; $page_title = t::w('Companies', 'Компаниялар'); $page_sub = t::w('All client companies, their subscription and activity', 'Барлық клиент компания, жазылымы мен белсенділігі');
	$arh_on = @$_GET['st'] === 'arh';
	$cs = dev::companies($arh_on);
	$arh_n = count(dev::companies(true));
	$tar = dev::tariffs(true);
	$refs = dev::rows("select id, nick from company_ref", 'id');
	$f = in_array(@$_GET['st'], ['paid', 'trial', 'soon', 'debt', 'none', 'arh']) ? $_GET['st'] : '';
	$cn = ['' => 0, 'paid' => 0, 'trial' => 0, 'soon' => 0, 'debt' => 0, 'none' => 0, 'arh' => $arh_n];
	if (!$arh_on) { $cn[''] = count($cs); foreach ($cs as $c) $cn[$c['st'][0]]++; } else { $act = dev::companies(); $cn[''] = count($act); foreach ($act as $c) $cn[$c['st'][0]]++; }
	$tabs = ['' => t::w('All', 'Барлығы'), 'paid' => t::w('Paid', 'Төленген'), 'trial' => t::w('On trial', 'Сынақта'), 'soon' => t::w('Ends soon', 'Жақында бітеді'), 'debt' => t::w('Overdue', 'Мерзімі өткен'), 'none' => t::w('Without subscription', 'Жазылымсыз'), 'arh' => t::w('Archive', 'Архив')];
?>
<? include "../block/header.php"; ?>
	<div class="tools">
		<div class="tabs">
			<? foreach ($tabs as $k => $t): ?><a href="?st=<?=$k?>" class="<?=$f === $k ? 'on' : ''?>"><?=$t?><i><?=$cn[$k]?></i></a><? endforeach ?>
		</div>
		<div style="display:flex;gap:10px;flex-wrap:wrap;flex:1;justify-content:flex-end">
			<input type="search" placeholder="<?=t::w('Search: name, city, code', 'Іздеу: атауы, қала, код')?>" data-filter=".cs_t tbody tr">
			<? if (dev::can('company_add')): ?><a class="btn btn_g" href="?add=1" data-pop=".cn_block"><i class="fal fa-plus"></i><?=t::w('Register company', 'Компания тіркеу')?></a><? endif ?>
		</div>
	</div>
	<div class="card">
		<div class="tbl_w"><table class="tbl cs_t">
			<thead><tr><th><?=t::w('Company', 'Компания')?></th><th><?=t::w('Tariff', 'Тариф')?></th><th><?=t::w('Subscription', 'Жазылым')?></th><th><?=t::w('Paid until', 'Төленген мерзімі')?></th><th class="num"><?=t::w('Branch', 'Филиал')?></th><th class="num"><?=t::w('Employees', 'Жұмысшы')?></th><th class="num"><?=t::w('Orders, 30 days', 'Тапсырыс, 30 күн')?></th><th><?=t::w('Last order', 'Соңғы тапсырыс')?></th><th><?=t::w('Referrer', 'Шақырушы')?></th></tr></thead>
			<tbody>
			<? foreach ($cs as $c): if ($f !== '' && !$arh_on && $c['st'][0] !== $f) continue; ?>
				<tr class="lnk" data-href="item.php?id=<?=$c['id']?>">
					<td class="nm"><b><?=dev::h($c['name'])?></b><small><?=dev::h(trim($c['city'] . ' · ' . $c['code'], ' ·'))?></small></td>
					<td><?=isset($tar[$c['tariff_id']]) ? dev::h($tar[$c['tariff_id']]['name']) : '<span class="mut">—</span>'?></td>
					<td><span class="st st_<?=$c['st'][0]?>"><?=$c['st'][1]?></span></td>
					<td class="nm"><b><?=dev::d($c['paid_until'])?></b><small><?=dev::days_txt($c['st'][2])?></small></td>
					<td class="num"><?=$c['branches']?></td>
					<td class="num"><?=$c['staff']?></td>
					<td class="num"><?=number_format($c['n30'], 0, '.', ' ')?></td>
					<td><?=dev::ago($c['last_dt'])?></td>
					<td><?=isset($refs[$c['ref_id']]) ? '<span class="code">' . dev::h($refs[$c['ref_id']]['nick']) . '</span>' : '<span class="mut">—</span>'?></td>
				</tr>
			<? endforeach ?>
			</tbody>
		</table></div>
	</div>
<? if (dev::can('company_add')) $page_pop = ['company_new']; ?>
<? include "../block/footer.php"; ?>
