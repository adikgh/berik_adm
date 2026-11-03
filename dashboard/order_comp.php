<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit(); }

	// кезең: күн / апта / ай / жыл
	include "period.php";

	// Сатылым: тек осы компанияның, нөмірі бар, отказ емес тапсырыстары (Тағамдар бетімен бірдей)
	$cmp = (int)$company;
	$sale_cond = "o.ins_dt BETWEEN '$start_cdate' and '$end_cdate' and o.company_id = '$cmp' and o.number is not null and o.order_status not in (5, 6, 10)";
	$f_branch = intval(@$_GET['branch']);
	if ($f_branch) $sale_cond .= " and o.branch_id = '$f_branch'";
	$o_branches = [];
	$obq = db::query("select id, name from company_branch where company_id = '$cmp' order by number asc");
	while ($ob = mysqli_fetch_assoc($obq)) $o_branches[] = $ob;

	// Шикізат шығыны граммовка бойынша: тағам → құрам (кг/л граммен) → рецепт → шикізат; сет → ішіндегі позициялар
	$raws = [];
	$q = db::query("select id, name_ru, name_kz, unit, price from product_raw where company_id = '$cmp'");
	while ($q && $r = mysqli_fetch_assoc($q)) $raws[(int)$r['id']] = $r;
	$comps = [];
	$q = db::query("select * from product_comp where company_id = '$cmp'");
	while ($q && $r = mysqli_fetch_assoc($q)) { $r['parts'] = []; $comps[(int)$r['id']] = $r; }
	$has_parts = ($tq = db::query("show tables like 'product_comp_part'")) && mysqli_num_rows($tq) > 0;
	$has_set = ($tq = db::query("show tables like 'product_set_item'")) && mysqli_num_rows($tq) > 0;
	if ($comps && $has_parts) {
		$q = db::query("select comp_id, raw_id, sub_comp_id, quantity from product_comp_part where comp_id in (".implode(',', array_keys($comps)).")");
		while ($q && $r = mysqli_fetch_assoc($q)) $comps[(int)$r['comp_id']]['parts'][] = $r;
	}
	$items = []; $sets = [];
	$q = db::query("select pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0");
	while ($q && $r = mysqli_fetch_assoc($q)) $items[(int)$r['product_id']][] = $r;
	if ($has_set) {
		$q = db::query("select si.set_id, si.product_id, si.comp_id, si.quantity from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_assoc($q)) $sets[(int)$r['set_id']][] = $r;
	}

	$use = [];      // raw_id => мөлшер (шикізат бірлігімен)
	$use_nc = [];   // шикізатқа байланбаған құрам: comp_id => мөлшер
	$miss = [];     // граммовкасы жоқ сатылған тағам: product_id => саны
	$comp_use = function ($cid, $amount, $depth = 0) use (&$comp_use, &$use, &$use_nc, $comps) {
		$c = $comps[$cid] ?? null;
		if (!$c || $amount == 0 || $depth > 8) return;
		if ($c['parts']) {
			$y = (float)$c['yield_qty'] > 0 ? (float)$c['yield_qty'] : 1;
			foreach ($c['parts'] as $p) {
				$a = (float)$p['quantity'] * $amount / $y;
				if ($p['raw_id']) $use[(int)$p['raw_id']] = ($use[(int)$p['raw_id']] ?? 0) + $a;
				elseif ($p['sub_comp_id']) $comp_use((int)$p['sub_comp_id'], $a, $depth + 1);
			}
		} elseif (!empty($c['raw_id'])) $use[(int)$c['raw_id']] = ($use[(int)$c['raw_id']] ?? 0) + $amount;
		else $use_nc[$cid] = ($use_nc[$cid] ?? 0) + $amount;
	};
	$dish_use = function ($pid, $n, $depth = 0) use (&$dish_use, $comp_use, $items, $sets, $comps, &$miss) {
		if ($depth > 5) return;
		if (isset($items[$pid])) {
			foreach ($items[$pid] as $i) {
				$c = $comps[(int)$i['comp_id']] ?? null; if (!$c) continue;
				$div = in_array(mb_strtolower(trim((string)$c['unit'])), ['кг', 'л']) ? 1000 : 1; // тағамда граммен
				$comp_use((int)$i['comp_id'], $n * (float)$i['quantity'] / $div);
			}
		} elseif (isset($sets[$pid])) {
			foreach ($sets[$pid] as $s) {
				if ($s['product_id']) $dish_use((int)$s['product_id'], $n * (float)$s['quantity'], $depth + 1);
				elseif ($s['comp_id']) $comp_use((int)$s['comp_id'], $n * (float)$s['quantity']);
			}
		} else $miss[$pid] = ($miss[$pid] ?? 0) + $n;
	};
	$sold_n = 0;
	$q = db::query("select p.product_id, SUM(p.quantity) qn from retail_orders_products p inner join retail_orders o on o.id = p.order_id where $sale_cond and p.product_id > 0 group by p.product_id");
	while ($q && $r = mysqli_fetch_assoc($q)) { $dish_use((int)$r['product_id'], (float)$r['qn']); $sold_n += $r['qn']; }

	// кесте: шикізат + байланбаған құрам, сомасы бойынша
	$rows = []; $total = 0;
	foreach ($use as $rid => $v) {
		$r = $raws[$rid] ?? null; if (!$r) continue;
		$s = $v * (float)$r['price']; $total += $s;
		$rows[] = ['nm' => $r['name_ru'] ?: $r['name_kz'], 'q' => $v, 'u' => fun::unit_norm($r['unit']) ?: 'шт', 'sum' => $s, 'nc' => 0];
	}
	foreach ($use_nc as $cid => $v) {
		$c = $comps[$cid]; $s = $v * (float)$c['price']; $total += $s;
		$rows[] = ['nm' => $c['name_ru'] ?: $c['name_kz'], 'q' => $v, 'u' => fun::unit_norm($c['unit'] ?? '') ?: 'шт', 'sum' => $s, 'nc' => 1];
	}
	usort($rows, function ($a, $b) { return $b['sum'] <=> $a['sum']; });
	$miss_rows = [];
	if ($miss) { $q = db::query("select id, name_kz, name_ru from product where id in (".implode(',', array_keys($miss)).")"); while ($r = mysqli_fetch_assoc($q)) $miss_rows[] = ['id' => $r['id'], 'nm' => strip_tags($r['name_kz'] ?: $r['name_ru']), 'n' => $miss[$r['id']]]; }

	// 0.85 кг → 850 г; 2.5 кг → 2,5 кг; 3 шт → 3 шт
	$qf = function ($v, $u) {
		$n = function ($x, $d) { return str_replace('.', ',', rtrim(rtrim(number_format($x, $d, '.', ' '), '0'), '.')); };
		if (in_array($u, ['кг', 'л'])) return $v < 1 ? $n($v * 1000, 0).' '.($u == 'кг' ? 'г' : 'мл') : $n($v, 2).' '.$u;
		return $n($v, 2).' '.$u;
	};
	$fmt = function ($v) { return number_format(round($v), 0, '', ' '); };

	// site setting
	$menu_name = 'order_comp';
	$page_name = 'Көрсеткіштер';
	$site_set['view_dt'] = 1;
	$css = ['order_product'];
	$js = ['order_product'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
		<? include "aheader.php"; ?>
	</div>

	<div class="bl_c oc_wrap">
		<div class="oc_stats">
			<div class="oc_st"><span>Сатылған тағам</span><b><?=$fmt($sold_n)?> шт</b></div>
			<div class="oc_st"><span>Шикізат шығыны (себес)</span><b><?=$fmt($total)?> тг</b></div>
			<div class="oc_st<?=($miss_rows ? ' oc_warn' : '')?>"<?=($miss_rows ? ' onclick="document.getElementById(\'oc_miss\').scrollIntoView({behavior:\'smooth\'})"' : '')?>><span>Граммовкасы жоқ сатылған</span><b><?=count($miss_rows)?> тағам</b></div>
		</div>

		<div class="oc_box">
			<div class="oc_h">Шикізат шығыны <small>сатылған тағамдардың граммовкасы бойынша есептелді</small></div>
			<? if ($rows): ?>
				<table class="oc_tbl">
					<thead><tr><th>#</th><th>Атауы</th><th>Кетті</th><th>Сомасы</th></tr></thead>
					<tbody>
						<? foreach ($rows as $i => $r): ?>
							<tr><td class="oc_n"><?=$i + 1?></td>
								<td><?=htmlspecialchars($r['nm'])?><?=($r['nc'] ? ' <span class="oc_tag" title="Бұл құрам шикізатқа (тауарға) байланбаған">шикізатқа байланбаған</span>' : '')?></td>
								<td class="oc_q"><b><?=$qf($r['q'], $r['u'])?></b></td>
								<td class="oc_q"><?=$fmt($r['sum'])?> тг</td></tr>
						<? endforeach ?>
					</tbody>
					<tfoot><tr><td></td><td>Барлығы</td><td></td><td class="oc_q"><b><?=$fmt($total)?> тг</b></td></tr></tfoot>
				</table>
			<? else: ?>
				<div class="oc_empty">Бұл кезеңде сатылым жоқ не сатылған тағамдардың граммовкасы жазылмаған.</div>
			<? endif ?>
		</div>

		<? if ($miss_rows): ?>
			<div class="oc_box" id="oc_miss">
				<div class="oc_h">Граммовкасы жоқ сатылған тағамдар <small>бұлардың шығыны есепке кірмеді, құрамын толтырыңыз</small></div>
				<div class="oc_miss">
					<? foreach ($miss_rows as $m): ?><a href="/products/item/?id=<?=$m['id']?>"><?=htmlspecialchars($m['nm'])?> <b>× <?=$m['n'] + 0?></b></a><? endforeach ?>
				</div>
			</div>
		<? endif ?>
	</div>

	<style>
		.oc_wrap{ margin-top: 28px; display: flex; flex-direction: column; gap: 24px; padding-bottom: 40px; }
		.oc_stats{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
		.oc_st{ background: #fff; border-radius: 18px; padding: 20px 24px; box-shadow: var(--bp-shadow, 0 6px 20px rgba(0,0,0,.07)); }
		.oc_st span{ display: block; font-size: 14px; color: var(--bp-muted, #8a8f98); margin-bottom: 6px; }
		.oc_st b{ font-size: 26px; }
		.oc_warn{ cursor: pointer; } .oc_warn b{ color: #c0392b; }
		.oc_box{ background: #fff; border-radius: 18px; padding: 24px 28px; box-shadow: var(--bp-shadow, 0 6px 20px rgba(0,0,0,.07)); }
		.oc_h{ font-size: 18px; font-weight: 700; margin-bottom: 18px; }
		.oc_h small{ display: block; font-size: 13px; font-weight: 400; color: var(--bp-muted, #8a8f98); margin-top: 4px; }
		.oc_tbl{ width: 100%; border-collapse: collapse; font-size: 15px; }
		.oc_tbl th{ text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; color: var(--bp-muted, #8a8f98); font-weight: 600; padding: 12px 14px; background: var(--bp-pill, #f5f5f5); }
		.oc_tbl th:first-child{ border-radius: 10px 0 0 10px; } .oc_tbl th:last-child{ border-radius: 0 10px 10px 0; }
		.oc_tbl td{ padding: 14px; border-bottom: 1px solid var(--bp-line, #eee); }
		.oc_tbl tbody tr:hover td{ background: #fafafa; }
		.oc_tbl tfoot td{ border-bottom: 0; font-weight: 700; padding-top: 18px; font-size: 16px; }
		.oc_tbl th:nth-child(3), .oc_tbl th:nth-child(4), .oc_q{ text-align: right; white-space: nowrap; width: 170px; }
		.oc_n{ width: 48px; color: var(--bp-muted, #8a8f98); }
		.oc_tag{ display: inline-block; margin-left: 8px; padding: 2px 9px; border-radius: 20px; background: #fff3d6; color: #b07800; font-size: 11px; font-weight: 700; vertical-align: middle; }
		.oc_empty{ padding: 32px; text-align: center; color: var(--bp-muted, #8a8f98); }
		.oc_miss{ display: flex; flex-wrap: wrap; gap: 10px; }
		.oc_miss a{ padding: 8px 14px; border-radius: 20px; background: #fdecec; color: #c0392b; font-size: 14px; }
		@media (max-width: 700px){
			.oc_wrap{ margin-top: 16px; gap: 16px; }
			.oc_stats{ grid-template-columns: 1fr; gap: 12px; }
			.oc_box{ padding: 16px; } .oc_tbl{ font-size: 14px; } .oc_tbl td, .oc_tbl th{ padding: 10px 8px; } .oc_q{ width: auto; }
		}
	</style>

<? include "../block/footer.php"; ?>
