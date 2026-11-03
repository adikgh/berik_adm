<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Бағалар: кезеңде сатып алынған шикізаттың соңғы бағасы алдыңғысымен салыстырылады
	wb::install();
	$cmp = (int)$company;
	$per = wb::per2('m', true);
	$pf = $per['pf']; $pt = $per['pt'];

	// барлық сатып алулар (алдыңғы баға кезеңнен бұрын болуы мүмкін)
	$q = db::query("select i.raw_id, i.price, i.factor, i.quantity, i.sum, w.doc_date, s.name sname,
			r.name_kz, r.name_ru, r.unit r_unit
		from waybill_item i join waybill w on w.id = i.waybill_id
		join product_raw r on r.id = i.raw_id and r.company_id = w.company_id
		left join supplier s on s.id = w.supplier_id
		where w.company_id = '$cmp' and w.doc_date <= '$pt'
		order by w.doc_date, w.id, i.id");
	$raws = [];
	while ($q && $i = mysqli_fetch_assoc($q)) {
		$k = (int)$i['raw_id'];
		$up = (float)$i['price'] / wb::f($i);
		$in = $i['doc_date'] >= $pf;
		if (!isset($raws[$k])) $raws[$k] = ['id' => $k, 'name' => $i['name_kz'] ?: $i['name_ru'], 'unit' => fun::unit_norm($i['r_unit']), 'pts' => [], 'last' => null, 'prev' => null, 'last_d' => null, 'sup' => [], 'sum' => 0, 'n' => 0];
		$r = &$raws[$k];
		$r['prev'] = $r['last']; $r['last'] = $up; $r['last_d'] = $i['doc_date'];
		if ($in) { $r['pts'][] = $up; $r['sum'] += (float)$i['sum']; $r['n']++; if ($i['sname']) $r['sup'][$i['sname']] = 1; }
		unset($r);
	}
	// тек кезеңде сатып алынғандар
	$raws = array_filter($raws, function ($r) { return $r['n'] > 0; });
	foreach ($raws as &$r) $r['d'] = $r['prev'] ? ($r['last'] - $r['prev']) / $r['prev'] * 100 : null;
	unset($r);
	// ең көп қымбаттағандар басында, өзгермегендер — соңында
	uasort($raws, function ($a, $b) {
		$da = $a['d'] ?? -1000; $db = $b['d'] ?? -1000;
		if ($da == $db) return $b['sum'] <=> $a['sum'];
		return $db <=> $da;
	});
	$up_n = count(array_filter($raws, function ($r) { return $r['d'] > 0; }));
	$down_n = count(array_filter($raws, function ($r) { return $r['d'] !== null && $r['d'] < 0; }));

	// кішкентай график
	$spark = function ($pts) {
		if (count($pts) < 2) return '<span class="wb_small">—</span>';
		$W = 110; $H = 30; $mn = min($pts); $mx = max($pts); $n = count($pts);
		$o = [];
		foreach ($pts as $k => $v) $o[] = round($k / ($n - 1) * ($W - 4) + 2, 1).','.round($mx == $mn ? $H / 2 : 2 + (1 - ($v - $mn) / ($mx - $mn)) * ($H - 4), 1);
		$c = end($pts) > $pts[0] ? '#c0392b' : (end($pts) < $pts[0] ? '#0EA768' : '#8a8f98');
		return '<svg class="wbp_spark" viewBox="0 0 '.$W.' '.$H.'" aria-hidden="true"><polyline points="'.implode(' ', $o).'" fill="none" stroke="'.$c.'" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/></svg>';
	};

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Бағалар';
	$wb_tab = 'prices';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<?=wb::per2_bar($per)?>

	<div class="wb_cards wb_cards_3">
		<div class="wb_card">
			<div class="wb_card_h">Сатып алынған шикізат</div>
			<div class="wb_card_v"><?=count($raws)?></div>
			<div class="wb_card_s"><?=wb::dt($per['mode'] == 'a' ? null : $pf)?> — <?=wb::dt($per['mode'] == 'a' ? null : $pt)?></div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Қымбаттады</div>
			<div class="wb_card_v wb_c_red">↑ <?=$up_n?></div>
			<div class="wb_card_s">соңғы баға алдыңғысынан жоғары</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Арзандады</div>
			<div class="wb_card_v wb_c_green">↓ <?=$down_n?></div>
			<div class="wb_card_s">соңғы баға алдыңғысынан төмен</div>
		</div>
	</div>

	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_name">Шикізат</div>
			<div class="wb_td">Соңғы баға</div>
			<div class="wb_td">Алдыңғы</div>
			<div class="wb_td">Өзгеріс</div>
			<div class="wb_td wbp_td_sp">Кезеңде</div>
		</div>
		<? foreach ($raws as $r): ?>
			<a class="wb_tr" href="/waybill/raw.php?id=<?=$r['id']?><?=($per['mode'] != 'a' ? '&mode='.$per['mode'].'&d='.$pf : '')?>">
				<div class="wb_td wb_td_name">
					<div class="wb_name"><?=wb::h($r['name'])?></div>
					<div class="wb_small"><?=$r['n']?> рет · <?=wb::h(implode(', ', array_keys($r['sup'])))?></div>
				</div>
				<div class="wb_td" data-h="Соңғы баға"><b><?=wb::up($r['last'])?></b> <span class="wb_small">/ <?=wb::h($r['unit'])?></span><div class="wb_small"><?=wb::dt($r['last_d'])?></div></div>
				<div class="wb_td" data-h="Алдыңғы"><?=($r['prev'] ? wb::up($r['prev']) : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td" data-h="Өзгеріс"><?=wb::delta($r['last'], $r['prev'])?></div>
				<div class="wb_td wbp_td_sp"><?=$spark($r['pts'])?></div>
			</a>
		<? endforeach ?>
		<? if (!$raws): ?>
			<div class="wb_empty"><i class="fal fa-tags"></i><div>Бұл кезеңде шикізатқа байланған сатып алу жоқ</div></div>
		<? endif ?>
	</div>

</div>

<? include "../block/footer.php"; ?>
