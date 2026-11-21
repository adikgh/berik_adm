<? include "../config/core.php";
	// Аналитика ▸ ABC анализ: тағамдарды түсім / саны / маржа үлесі бойынша A, B, C топтарына бөлу
	if (!$user_id) { header('location: /'); exit(); }
	if ($user_right['positions_id'] == 3) { header('location: /kassa/'); exit(); }

	$company = intval($company);

	// ---------- Аударма: site_word (t::w), онда жоқ тіл болса — abc/words.php ----------
	include "words.php";
	function abc_w($k, $r = []) {
		global $abc_words;
		$d = isset($abc_words[$k]) ? $abc_words[$k] : [$k];
		$i = array_search(t::lang(), ['kz', 'ru', 'en', 'uz', 'ky'], true);
		$w = t::load();
		if (isset($w['cur'][$k]) && $w['cur'][$k] !== '') $txt = $w['cur'][$k];
		elseif ($i !== false && isset($d[$i]) && $d[$i] !== '') { t::w($k, $d[0]); $txt = $d[$i]; }
		else $txt = t::w($k, $d[0]);
		foreach ($r as $rk => $rv) $txt = str_replace('{'.$rk.'}', $rv, $txt);
		return $txt;
	}
	function abc_h($k, $r = []) { return htmlspecialchars(abc_w($k, $r)); }


	// ---------- Кезең: ай немесе жыл ----------
	$mode = in_array(@$_GET['mode'], ['d', 'w', 'm', 'y'], true) ? $_GET['mode'] : 'd'; // ашылғанда — бүгінгі күн
	$now_ts = time();
	$now_dt = date('Y-m-d H:i:s', $now_ts);
	$today_bd = date('Y-m-d', $now_ts - 6 * 3600); // бүгінгі жұмыс күні (06:00-ден)

	$m_names = ['']; $m_low = [''];
	for ($i = 1; $i <= 12; $i++) { $m_names[$i] = abc_w('Month '.$i); $m_low[$i] = abc_w('Month '.$i.' of'); }

	if ($mode == 'd') {
		$dd = (preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)@$_GET['d']) && strtotime($_GET['d'])) ? $_GET['d'] : $today_bd;
		$p_start_d = $dd;
		$p_end_d   = date('Y-m-d', strtotime("$dd +1 day"));
		$prev_d    = date('Y-m-d', strtotime("$dd -1 day"));
		$step      = '1 day';
		$title     = intval(substr($dd, 8, 2)).' '.$m_low[intval(substr($dd, 5, 2))].' '.substr($dd, 0, 4).($dd == $today_bd ? ', '.abc_w('today') : '');
		$prev_link = '?mode=d&d='.$prev_d;
		$next_link = '?mode=d&d='.$p_end_d;
	} elseif ($mode == 'w') {
		// апта: дүйсенбіден жексенбіге дейін (?w=YYYY-MM-DD — аптаның кез келген күні)
		$wd0 = (preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)@$_GET['w']) && strtotime($_GET['w'])) ? $_GET['w'] : $today_bd;
		$p_start_d = date('Y-m-d', strtotime($wd0) - (date('N', strtotime($wd0)) - 1) * 86400);
		$p_end_d   = date('Y-m-d', strtotime("$p_start_d +7 day"));
		$prev_d    = date('Y-m-d', strtotime("$p_start_d -7 day"));
		$step      = '7 day';
		$w_last    = date('Y-m-d', strtotime("$p_start_d +6 day"));
		$title     = intval(substr($p_start_d, 8, 2)).(substr($p_start_d, 5, 2) != substr($w_last, 5, 2) ? ' '.$m_low[intval(substr($p_start_d, 5, 2))] : '').' – '.intval(substr($w_last, 8, 2)).' '.$m_low[intval(substr($w_last, 5, 2))].($today_bd >= $p_start_d && $today_bd <= $w_last ? ', '.abc_w('this week') : '');
		$prev_link = '?mode=w&w='.$prev_d;
		$next_link = '?mode=w&w='.$p_end_d;
	} elseif ($mode == 'm') {
		$ym = preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', (string)@$_GET['m']) ? $_GET['m'] : substr($today_bd, 0, 7);
		$p_start_d = $ym.'-01';
		$p_end_d   = date('Y-m-d', strtotime("$p_start_d +1 month"));
		$prev_d    = date('Y-m-d', strtotime("$p_start_d -1 month"));
		$step      = '1 month';
		$title     = $m_names[intval(substr($ym, 5, 2))].' '.substr($ym, 0, 4);
		$prev_link = '?mode=m&m='.substr($prev_d, 0, 7);
		$next_link = '?mode=m&m='.substr($p_end_d, 0, 7);
	} else {
		$yy = preg_match('/^20\d{2}$/', (string)@$_GET['y']) ? $_GET['y'] : substr($today_bd, 0, 4);
		$p_start_d = $yy.'-01-01';
		$p_end_d   = ($yy + 1).'-01-01';
		$prev_d    = ($yy - 1).'-01-01';
		$step      = '1 year';
		$title     = abc_w('Year {y}', ['y' => $yy]);
		$prev_link = '?mode=y&y='.($yy - 1);
		$next_link = '?mode=y&y='.($yy + 1);
	}

	// жұмыс күні 06:00-ден басталады
	$p_start = "$p_start_d 06:00:00";
	$p_end   = "$p_end_d 06:00:00";
	$is_current = ($now_dt >= $p_start && $now_dt < $p_end);
	$is_future  = ($now_dt < $p_start);

	// ағымдағы кезеңде тек болған заказдар (алдағы предзаказдар бөлек)
	$p_to = $is_current ? $now_dt : $p_end;


	// ---------- Точкалар ----------
	$branches = [];
	$br_q = db::query("select * from company_branch where company_id = '$company' order by number asc");
	while ($br_q && $br_d = mysqli_fetch_assoc($br_q)) $branches[$br_d['id']] = $br_d['name'];
	$f_branch = intval(@$_GET['branch']);
	if (!isset($branches[$f_branch])) $f_branch = 0;

	// ---------- Критерий: түсім | саны | маржа ----------
	$by_list = ['rev' => abc_w('Revenue'), 'qty' => abc_w('Quantity'), 'mrg' => abc_w('Margin')];
	$by_grp = ['rev' => abc_w('Groups by revenue'), 'qty' => abc_w('Groups by quantity'), 'mrg' => abc_w('Groups by margin')];
	$by = isset($by_list[@$_GET['by']]) ? $_GET['by'] : 'rev';
	$q_branch = $f_branch ? '&branch='.$f_branch : '';
	$q_by = $by != 'rev' ? '&by='.$by : '';
	$q_period = $mode == 'd' ? 'mode=d&d='.$dd : ($mode == 'w' ? 'mode=w&w='.$p_start_d : ($mode == 'm' ? 'mode=m&m='.$ym : 'mode=y&y='.$yy));

	// шекаралар: жиынтық үлес 80% дейін — A, 95% дейін — B, қалғаны — C
	$abc_a = 80; $abc_b = 95;

	function abc_money($n) { return number_format(round($n), 0, '.', ' ').' ₸'; }
	function abc_num($n) { return number_format(round($n), 0, '.', ' '); }
	function abc_pct($n) { return ($n > 0 && $n < 1 ? '<1' : round($n)).'%'; }

	// ---------- Сатылған тағамдар ----------
	$ok_st = "o.order_status in (1, 8, 2, 11, 3, 9, 4)";
	$items = [];
	if (!$is_future) {
		$q = db::query("select op.product_id, COALESCE(SUM(op.quantity), 0) as qty, COALESCE(SUM(op.quantity * op.price), 0) as rev,
			MAX(p.name_kz) as name_kz, MAX(p.name_ru) as name_ru
			from retail_orders_products op
			join retail_orders o on o.id = op.order_id
			left join product p on p.id = op.product_id
			where o.company_id = '$company' and o.number is not null and $ok_st ".($f_branch ? " and o.branch_id = '$f_branch'" : "")."
				and o.ins_dt >= '$p_start' and o.ins_dt < '$p_to'
			group by op.product_id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$nm = trim(strip_tags(html_entity_decode((string)($r['name_kz'] ?: $r['name_ru']))));
			$items[(int)$r['product_id']] = ['id' => (int)$r['product_id'], 'name' => $nm !== '' ? $nm : '#'.$r['product_id'], 'nh' => fun::name_html($r['name_kz'] ?: $r['name_ru']), 'qty' => (float)$r['qty'], 'rev' => (float)$r['rev'], 'cost' => null, 'mrg' => null, 'zero' => false];
		}
	}
	$sold_cnt = count($items);

	// ---------- Мүлдем өтпейтін тағамдар (тек Ай / Жыл): архивте емес, кезеңде бірде-бір рет сатылмаған — бөлек «0» тобы ----------
	$zero_cnt = 0;
	if (!$is_future && $sold_cnt && in_array($mode, ['m', 'y'], true)) {
		$q = db::query("select id, name_kz, name_ru from product where company_id = '$company' and arh = 0");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$id = (int)$r['id'];
			if (isset($items[$id])) continue;
			$nm = trim(strip_tags(html_entity_decode((string)($r['name_kz'] ?: $r['name_ru']))));
			$items[$id] = ['id' => $id, 'name' => $nm !== '' ? $nm : '#'.$id, 'nh' => fun::name_html($r['name_kz'] ?: $r['name_ru']), 'qty' => 0.0, 'rev' => 0.0, 'cost' => 0.0, 'mrg' => 0.0, 'zero' => true];
			$zero_cnt++;
		}
	}

	// ---------- Себес (Тағамдар ▸ Себес): бір данасының азық-түлік құны ----------
	$cost_on = false; $no_cost = 0;
	if ($items) {
		@include_once "../products/cost/lib.php";
		if (class_exists('cost') && cost::ready()) {
			$cost_on = true;
			$comps = cost::comps($company);
			$dishes = cost::dishes($company, $comps);
			$sets = cost::sets($company, $comps, $dishes);
			foreach ($items as $id => $it) {
				if ($it['zero']) continue;
				$u = isset($sets[$id]) ? $sets[$id]['cost'] : (isset($dishes[$id]) ? $dishes[$id] : null);
				if ($u === null || $u <= 0) { $no_cost++; continue; }
				$items[$id]['cost'] = $u * $it['qty'];
				$items[$id]['mrg'] = $it['rev'] - $u * $it['qty'];
			}
		}
	}

	// ---------- ABC: әр критерий бойынша класс ----------
	$tot = ['rev' => 0, 'qty' => 0, 'mrg' => 0, 'cost' => 0];
	foreach ($items as $it) {
		$tot['rev'] += $it['rev']; $tot['qty'] += $it['qty'];
		if ($it['mrg'] !== null) { $tot['cost'] += $it['cost']; if ($it['mrg'] > 0) $tot['mrg'] += $it['mrg']; }
	}
	$mrg_all = 0; foreach ($items as $it) if ($it['mrg'] !== null) $mrg_all += $it['mrg'];

	foreach (array_keys($by_list) as $k) {
		$ids = array_keys($items);
		usort($ids, function ($a, $b) use ($items, $k) { return [$items[$b][$k] === null ? -INF : $items[$b][$k], $items[$a]['name']] <=> [$items[$a][$k] === null ? -INF : $items[$a][$k], $items[$b]['name']]; });
		$cum = 0;
		foreach ($ids as $id) {
			$v = $items[$id][$k];
			if ($items[$id]['zero']) { $items[$id]['c_'.$k] = 'N'; $items[$id]['s_'.$k] = 0; $items[$id]['cum_'.$k] = min(100, $cum); continue; }
			if ($v === null || $v <= 0 || !$tot[$k]) { $items[$id]['c_'.$k] = $v === null ? '' : 'C'; $items[$id]['s_'.$k] = 0; $items[$id]['cum_'.$k] = $cum; continue; }
			$share = $v / $tot[$k] * 100;
			// элемент A болады, егер өзіне дейінгі жиынтық 80%-дан аз болса (ең бірінші тағам әрқашан A)
			$items[$id]['c_'.$k] = $cum < $abc_a ? 'A' : ($cum < $abc_b ? 'B' : 'C');
			$cum += $share;
			$items[$id]['s_'.$k] = $share;
			$items[$id]['cum_'.$k] = min(100, $cum);
		}
	}

	// таңдалған критерий бойынша реттеу
	$rows = array_values($items);
	usort($rows, function ($a, $b) use ($by) { return [$a['zero'], $b[$by] === null ? -INF : $b[$by], $a['name']] <=> [$b['zero'], $a[$by] === null ? -INF : $a[$by], $b['name']]; });

	// топтар қорытындысы
	$grp = [];
	foreach (['A', 'B', 'C', 'N', ''] as $g) $grp[$g] = ['cnt' => 0, 'val' => 0];
	foreach ($rows as $r) { $g = $r['c_'.$by]; $grp[$g]['cnt']++; $grp[$g]['val'] += $r[$by] !== null && $r[$by] > 0 ? $r[$by] : 0; }
	$grp_info = [
		'A' => ['clr' => '#0EA768', 'txt' => abc_w('Main income. Always keep it in stock and keep the quality.')],
		'B' => ['clr' => '#e9a100', 'txt' => abc_w('Average. Ads or combos can lift it to A.')],
		'C' => ['clr' => '#e5484d', 'txt' => abc_w('Brings little. Consider removing it from the menu or revising the price.')],
	];
	if ($zero_cnt) $grp_info['N'] = ['clr' => '#6b7280', 'txt' => abc_w('Not sold at all in this period. Remove from the menu, change the recipe or promote it.')];
	$g_lbl = function ($g) { return $g == 'N' ? '0' : $g; };
	$pcs = abc_w('pcs');
	$fmt = function ($v) use ($by, $pcs) { return $by == 'qty' ? abc_num($v).' '.$pcs : abc_money($v); };


	// site setting
	$menu_name = 'abc';
	$page_name = abc_w('ABC analysis');
	$css = ['stats', 'abc'];
	$js = ['st_pick'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c st_page abc_page" data-ajx-nav=".abc_page">

		<!-- сүзгілер -->
		<div class="st_bar">
			<div class="st_seg">
				<a class="st_seg_i <?=$mode=='d'?'st_seg_act':''?>" href="?mode=d<?=$q_branch.$q_by?>"><?=abc_h('Day')?></a>
				<a class="st_seg_i <?=$mode=='w'?'st_seg_act':''?>" href="?mode=w<?=$q_branch.$q_by?>"><?=abc_h('Week')?></a>
				<a class="st_seg_i <?=$mode=='m'?'st_seg_act':''?>" href="?mode=m<?=$q_branch.$q_by?>"><?=abc_h('Month')?></a>
				<a class="st_seg_i <?=$mode=='y'?'st_seg_act':''?>" href="?mode=y<?=$q_branch.$q_by?>"><?=abc_h('Year')?></a>
			</div>
			<div class="st_nav">
				<a class="st_nav_b" href="<?=$prev_link.$q_branch.$q_by?>"><i class="far fa-chevron-left"></i></a>
				<div class="st_nav_t" data-pick="1" data-kind="stats" data-mode="<?=$mode?>" data-d="<?=$p_start_d?>" data-today="<?=$today_bd?>" data-q="<?=$q_branch.$q_by?>"><?=$title?></div>
				<a class="st_nav_b" href="<?=$next_link.$q_branch.$q_by?>"><i class="far fa-chevron-right"></i></a>
			</div>
			<div class="st_seg">
				<? foreach ($by_list as $k => $n): ?>
					<a class="st_seg_i <?=$by==$k?'st_seg_act':''?>" href="?<?=$q_period.$q_branch.($k != 'rev' ? '&by='.$k : '')?>"><?=$n?></a>
				<? endforeach ?>
			</div>
			<? if (count($branches) > 1): ?>
				<div class="st_seg">
					<a class="st_seg_i <?=!$f_branch?'st_seg_act':''?>" href="?<?=$q_period.$q_by?>"><?=abc_h('All')?></a>
					<? foreach ($branches as $id => $name): ?>
						<a class="st_seg_i <?=$f_branch==$id?'st_seg_act':''?>" href="?<?=$q_period.$q_by?>&branch=<?=$id?>"><?=$name?></a>
					<? endforeach ?>
				</div>
			<? endif ?>
		</div>

		<? if ($is_future): ?>
			<div class="st_card st_empty"><?=abc_h('This period has not started yet.')?></div>
		<? elseif (!$sold_cnt): ?>
			<div class="st_card st_empty"><?=abc_h('No sales in this period.')?></div>
		<? elseif ($by == 'mrg' && !$cost_on): ?>
			<div class="st_card st_empty"><?=abc_h('Margin needs the cost sheet. Fill in Dishes ▸ Cost sheet.')?></div>
		<? else: ?>

		<!-- жалпы -->
		<div class="st_kpis">
			<div class="st_kpi">
				<div class="st_kpi_h"><i class="fal fa-utensils"></i><?=abc_h('Dish types')?></div>
				<div class="st_kpi_v"><?=abc_num($sold_cnt)?></div>
				<div class="st_kpi_s"><?=abc_h('sold')?>: <?=abc_num($tot['qty'])?> <?=htmlspecialchars($pcs)?><?=$zero_cnt ? ' · '.abc_h('not sold: {n}', ['n' => abc_num($zero_cnt)]) : ''?></div>
			</div>
			<div class="st_kpi st_kpi_main">
				<div class="st_kpi_h"><i class="fal fa-wallet"></i><?=abc_h('Revenue')?></div>
				<div class="st_kpi_v"><?=abc_money($tot['rev'])?></div>
				<div class="st_kpi_s"><?=abc_h('at menu prices, without discounts')?></div>
			</div>
			<div class="st_kpi">
				<div class="st_kpi_h"><i class="fal fa-calculator"></i><?=abc_h('Food cost')?></div>
				<div class="st_kpi_v"><?=$cost_on ? abc_money($tot['cost']) : '—'?></div>
				<div class="st_kpi_s"><?=$cost_on && $tot['rev'] ? abc_h('food cost').': '.abc_pct($tot['cost'] / max(1, $tot['rev'] - array_sum(array_map(function ($r) { return $r['mrg'] === null ? $r['rev'] : 0; }, $rows))) * 100) : abc_h('cost sheet is not filled')?></div>
			</div>
			<div class="st_kpi">
				<div class="st_kpi_h"><i class="fal fa-coins"></i><?=abc_h('Margin')?></div>
				<div class="st_kpi_v"><?=$cost_on ? abc_money($mrg_all) : '—'?></div>
				<div class="st_kpi_s"><?=$no_cost ? abc_h('{n} dishes have no cost', ['n' => $no_cost]) : ($cost_on ? abc_h('revenue − cost') : '')?></div>
			</div>
		</div>

		<!-- топтар -->
		<div class="st_card">
			<div class="st_card_h"><div><?=htmlspecialchars($by_grp[$by])?></div><div class="st_card_hs"><?=abc_h('A — {a}%, B — next {b}%, C — remaining {c}%', ['a' => $abc_a, 'b' => $abc_b - $abc_a, 'c' => 100 - $abc_b])?></div></div>
			<? $g_sum = $grp['A']['val'] + $grp['B']['val'] + $grp['C']['val']; ?>
			<? if ($g_sum): ?>
				<div class="st_stack">
					<? foreach ($grp_info as $g => $gi): if (!$grp[$g]['val']) continue; ?>
						<div style="width:<?=round($grp[$g]['val'] / $g_sum * 100, 2)?>%;background:<?=$gi['clr']?>"></div>
					<? endforeach ?>
				</div>
			<? endif ?>
			<div class="abc_grps<?=$zero_cnt ? ' abc_grps_4' : ''?>">
				<? foreach ($grp_info as $g => $gi): ?>
					<div class="abc_grp" data-g="<?=$g?>">
						<div class="abc_grp_h"><span class="abc_b abc_b_<?=$g?>"><?=$g_lbl($g)?></span><? if ($g == 'N'): ?><b><?=abc_h('Not selling at all')?>: <?=abc_num($grp[$g]['cnt'])?></b><? else: ?><b><?=abc_h('{n} dishes', ['n' => abc_num($grp[$g]['cnt'])])?></b><? endif ?><span class="abc_grp_p"><?=abc_h('{p}% of the list', ['p' => count($rows) ? round($grp[$g]['cnt'] / count($rows) * 100) : 0])?></span></div>
						<div class="abc_grp_v"><?=$fmt($grp[$g]['val'])?> <span><?=$g_sum ? round($grp[$g]['val'] / $g_sum * 100) : 0?>%</span></div>
						<div class="abc_grp_t"><?=htmlspecialchars($gi['txt'])?></div>
					</div>
				<? endforeach ?>
			</div>
		</div>

		<!-- тізім -->
		<div class="st_card">
			<div class="st_card_h">
				<div><?=abc_h('Dishes')?></div>
				<div class="abc_flt">
					<span class="abc_flt_i abc_flt_act" data-g=""><?=abc_h('All')?></span>
					<span class="abc_flt_i" data-g="A">A</span>
					<span class="abc_flt_i" data-g="B">B</span>
					<span class="abc_flt_i" data-g="C">C</span>
					<? if ($zero_cnt): ?><span class="abc_flt_i" data-g="N" title="<?=abc_h('Not selling at all')?>">0</span><? endif ?>
				</div>
			</div>
			<div class="abc_list">
				<div class="abc_r abc_r_h">
					<div>#</div>
					<div><?=abc_h('Dish')?></div>
					<div class="abc_n"><?=abc_h('Quantity')?></div>
					<div class="abc_n"><?=abc_h('Revenue')?></div>
					<div class="abc_n"><?=abc_h('Margin')?></div>
					<div class="abc_n"><?=abc_h('Share')?></div>
					<div class="abc_n"><?=abc_h('Cumulative')?></div>
					<div class="abc_c" title="<?=abc_h('Class by revenue · quantity · margin')?>"><?=abc_h('Class')?></div>
				</div>
				<? foreach ($rows as $i => $r): $g = $r['c_'.$by]; ?>
					<div class="abc_r<?=$r['zero'] ? ' abc_r_zero' : ''?>" data-g="<?=$g?>">
						<div class="abc_i"><?=$i + 1?></div>
						<div class="abc_nm"><span class="abc_b abc_b_<?=$g ?: 'x'?>"><?=$g ? $g_lbl($g) : '?'?></span><span class="abc_nm_t"><?=(($r['nh'] ?? '') !== '' ? $r['nh'] : htmlspecialchars($r['name']))?></span></div>
						<div class="abc_n" data-l="<?=abc_h('Quantity')?>"><?=abc_num($r['qty'])?> <?=htmlspecialchars($pcs)?></div>
						<div class="abc_n" data-l="<?=abc_h('Revenue')?>"><?=abc_money($r['rev'])?></div>
						<div class="abc_n <?=$r['mrg'] !== null && $r['mrg'] < 0 ? 'abc_neg' : ''?>" data-l="<?=abc_h('Margin')?>"><?=$r['mrg'] === null ? '<span class="abc_mt" title="'.abc_h('Cost is not filled in').'">'.abc_h('no cost').'</span>' : abc_money($r['mrg']).($r['rev'] ? ' <small>'.round($r['mrg'] / $r['rev'] * 100).'%</small>' : '')?></div>
						<div class="abc_n" data-l="<?=abc_h('Share')?>"><?=abc_pct($r['s_'.$by])?></div>
						<div class="abc_n" data-l="<?=abc_h('Cumulative')?>"><?=round($r['cum_'.$by])?>%</div>
						<div class="abc_c" title="<?=abc_h('Class by revenue · quantity · margin')?>">
							<? foreach (array_keys($by_list) as $k): ?><span class="abc_s abc_s_<?=$r['c_'.$k] ?: 'x'?> <?=$k==$by?'abc_s_on':''?>"><?=$r['c_'.$k] ? $g_lbl($r['c_'.$k]) : '–'?></span><? endforeach ?>
						</div>
					</div>
				<? endforeach ?>
			</div>
			<div class="abc_hint"><i class="fal fa-info-circle"></i> <?=abc_h('abc hint')?></div>
		</div>

		<? endif ?>

		<br><br>
	</div>

	<script>
	// A / B / C бойынша сүзгі (AJAX-тан кейін де істейді)
	$(document).on('click', '.abc_flt_i, .abc_grp', function () {
		var g = this.dataset.g || '';
		if ($(this).hasClass('abc_grp') && $('.abc_flt_i.abc_flt_act').data('g') === g) g = '';
		$('.abc_flt_i').each(function () { $(this).toggleClass('abc_flt_act', (this.dataset.g || '') === g); });
		$('.abc_grp').each(function () { $(this).toggleClass('abc_grp_act', g !== '' && this.dataset.g === g); });
		$('.abc_list .abc_r:not(.abc_r_h)').each(function () { this.hidden = g !== '' && this.dataset.g !== g; });
	});
	</script>

<? include "../block/footer.php"; ?>
