<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Шикізат карточкасы: бағасы қалай өзгерді (әр жеткізушіден, шикізаттың 1 өлшеміне шаққанда)
	wb::install();
	$cmp = (int)$company;
	$id = (int)@$_GET['id'];
	$rq = db::query("select * from product_raw where id = '$id' and company_id = '$cmp' limit 1");
	$raw = $rq && mysqli_num_rows($rq) ? mysqli_fetch_assoc($rq) : null;
	if (!$raw) { header('location: /waybill/prices.php'); exit; }
	$rname = $raw['name_kz'] ?: $raw['name_ru'];
	$unit = fun::unit_norm($raw['unit']);

	$per = wb::per2('a', true);
	$buys = wb::raw_buys($cmp, $id, $per['pf'], $per['pt']);
	$all = wb::raw_buys($cmp, $id); // соңғы баға мен алдыңғысы кезеңге қарамай

	// қалдық қозғалысы (кезеңде, жаңасы басында)
	$br = wb::branch($cmp, $branch);
	$st = mysqli_fetch_assoc(db::query("select quantity from product_raw_stock where company_id = '$cmp' and branch_id = '$br' and raw_id = '$id'"));
	$stock = $st ? (float)$st['quantity'] : 0;
	$moves = [];
	$mq = db::query("select * from product_raw_move where company_id = '$cmp' and branch_id = '$br' and raw_id = '$id'
		and dt between '{$per['pf']} 00:00:00' and '{$per['pt']} 23:59:59' order by dt desc, id desc limit 300");
	while ($mq && $m = mysqli_fetch_assoc($mq)) $moves[] = $m;

	$last = $all ? end($all) : null;
	$prev_b = count($all) > 1 ? $all[count($all) - 2] : null;
	$ups = array_column($buys, 'up');
	$qty = array_sum(array_column($buys, 'bq'));
	$sum = array_sum(array_column($buys, 'sum'));

	// жеткізуші түстері
	$colors = ['#1f75a8', '#0EA768', '#e67e22', '#8e44ad', '#c0392b', '#16a085', '#7f8c8d'];
	$sup_c = [];
	foreach ($buys as $b) { $k = $b['sname'] ?: '—'; if (!isset($sup_c[$k])) $sup_c[$k] = $colors[count($sup_c) % count($colors)]; }

	// SVG график: әр жеткізушіге бөлек сызық, бір күнгі сатып алулары — орташасы.
	// Стильдер атрибутпен жазылады (CSS жүктелмесе де дұрыс көрінеді).
	$svg = '';
	if ($buys) {
		$ser = []; // жеткізуші => күн => сатып алулар
		foreach ($buys as $b) $ser[$b['sname'] ?: '—'][$b['doc_date']][] = $b;
		$vals = []; $dates = [];
		foreach ($ser as $s => $days) foreach ($days as $d => $bs) { $vals[] = array_sum(array_column($bs, 'up')) / count($bs); $dates[$d] = 1; }
		ksort($dates); $dates = array_keys($dates);
		$W = 1000; $H = 320; $l = 76; $r = 34; $t = 18; $bt = 44;
		// дөңгелек бөліктер
		$nice = function ($x) { if ($x <= 0) return 1; $p = pow(10, floor(log10($x))); $f = $x / $p; return ($f <= 1 ? 1 : ($f <= 2 ? 2 : ($f <= 5 ? 5 : 10))) * $p; };
		$mn = min($vals); $mx = max($vals);
		$step = $nice($mx > $mn ? ($mx - $mn) / 4 : max($mx * .05, 1));
		$y0 = floor($mn / $step) * $step; $y1 = ceil($mx / $step) * $step;
		if ($y1 - $y0 < $step * 2) { $y0 -= $step; $y1 += $step; }
		$y0 = max(0, $y0);
		$d0 = strtotime($dates[0]); $d1 = strtotime(end($dates));
		$X = function ($d) use ($W, $l, $r, $d0, $d1) { return round($d1 > $d0 ? $l + (strtotime($d) - $d0) / ($d1 - $d0) * ($W - $l - $r) : ($l + $W - $r) / 2, 1); };
		$Y = function ($v) use ($H, $t, $bt, $y0, $y1) { return round($t + (1 - ($v - $y0) / ($y1 - $y0)) * ($H - $t - $bt), 1); };
		$txt = 'font-size="12" font-weight="600" fill="#8a8f98" font-family="Nunito, Arial, sans-serif"';
		$svg .= '<svg class="wbr_chart" viewBox="0 0 '.$W.' '.$H.'" width="100%" style="display:block;width:100%;height:auto;overflow:visible" role="img" aria-label="Баға динамикасы">';
		for ($v = $y0; $v <= $y1 + $step / 2; $v += $step) {
			$y = $Y($v);
			$svg .= '<line x1="'.$l.'" x2="'.($W - $r).'" y1="'.$y.'" y2="'.$y.'" stroke="#eef0f2" stroke-width="1"/>';
			$svg .= '<text x="'.($l - 10).'" y="'.($y + 4).'" text-anchor="end" '.$txt.'>'.number_format($v, $step < 1 ? 2 : 0, '.', ' ').' ₸</text>';
		}
		// күн белгілері: бір-біріне тиіп кетсе өткізіп жібереміз, соңғысы әрқашан көрінеді
		$lab = []; $last_x = -999;
		foreach ($dates as $k => $d) {
			$x = $X($d);
			if ($x - $last_x < 60) { if ($k == count($dates) - 1 && $lab) array_pop($lab); else continue; }
			$lab[] = [$x, date('d.m', strtotime($d))]; $last_x = $x;
		}
		foreach ($lab as $lb) $svg .= '<text x="'.$lb[0].'" y="'.($H - 14).'" text-anchor="middle" '.$txt.'>'.$lb[1].'</text>';
		foreach ($ser as $s => $days) {
			$c = $sup_c[$s]; $pts = [];
			foreach ($days as $d => $bs) $pts[] = [$X($d), $Y(array_sum(array_column($bs, 'up')) / count($bs)), $d, $bs];
			if (count($pts) > 1) $svg .= '<polyline points="'.implode(' ', array_map(function ($p) { return $p[0].','.$p[1]; }, $pts)).'" fill="none" stroke="'.$c.'" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>';
			foreach ($pts as $p) {
				$tip = wb::dt($p[2]).' · '.$s;
				foreach ($p[3] as $b) $tip .= "\n".$b['name'].': '.wb::qty($b['quantity']).' '.$b['unit'].' × '.wb::sum($b['price']).' = '.wb::up($b['up']).' / '.$unit;
				$svg .= '<circle cx="'.$p[0].'" cy="'.$p[1].'" r="5.5" fill="'.$c.'" stroke="#fff" stroke-width="2"><title>'.wb::h($tip).'</title></circle>';
			}
		}
		$svg .= '</svg>';
	}

	// site setting
	$menu_name = 'waybill';
	$page_name = $rname;
	$wb_tab = 'prices';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<a class="wb_back" href="/waybill/prices.php"><i class="far fa-arrow-left"></i> Бағалар</a>

	<div class="wbr_head">
		<div>
			<div class="wbr_name"><?=wb::h($rname)?><?=wb::stock_badge($stock, $all ? 1 : 0)?></div>
			<? if ($stock < 0): ?><div class="wb_small wb_c_red">Қалдық <?=wb::qty($stock)?> <?=wb::h($unit)?>: накладной енгізіңіз немесе ревизия жасаңыз</div><? endif ?>
			<div class="wb_small">Баға 1 <?=wb::h($unit)?>-ге шаққанда · <?=wb::h(wb::$groups[$raw['grp'] ?: wb::grp_guess($rname)] ?? '')?></div>
		</div>
		<? if ($last): ?>
			<div class="wbr_last">
				<div class="wbr_last_v"><?=wb::up($last['up'])?> <span>/ <?=wb::h($unit)?></span></div>
				<div class="wb_small">соңғы баға, <?=wb::dt($last['doc_date'])?><?=($prev_b ? ' · алдыңғысы '.wb::up($prev_b['up']).' '.wb::delta($last['up'], $prev_b['up']) : '')?></div>
			</div>
		<? endif ?>
	</div>

	<?=wb::per2_bar($per, '&id='.$id)?>

	<div class="wb_cards">
		<div class="wb_card">
			<div class="wb_card_h">Ең арзан</div>
			<div class="wb_card_v wb_c_green"><?=($ups ? wb::up(min($ups)) : '—')?></div>
			<div class="wb_card_s">кезеңде, 1 <?=wb::h($unit)?></div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Ең қымбат</div>
			<div class="wb_card_v wb_c_red"><?=($ups ? wb::up(max($ups)) : '—')?></div>
			<div class="wb_card_s">кезеңде, 1 <?=wb::h($unit)?></div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Орташа баға</div>
			<div class="wb_card_v"><?=($qty > 0 ? wb::up($sum / $qty) : '—')?></div>
			<div class="wb_card_s">салмақталған (сома ÷ саны)</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Сатып алынды</div>
			<div class="wb_card_v"><?=wb::qty($qty)?> <?=wb::h($unit)?></div>
			<div class="wb_card_s"><?=count($buys)?> рет · <?=wb::sum($sum)?></div>
		</div>
	</div>

	<? if (!$buys): ?>
		<div class="wb_box"><div class="wb_empty"><i class="fal fa-chart-line"></i><div>Бұл кезеңде сатып алу жоқ</div></div></div>
	<? else: ?>
		<div class="wb_box">
			<div class="wb_box_h"><span>Баға динамикасы, 1 <?=wb::h($unit)?></span>
				<span class="wbr_legend" style="display:flex;flex-wrap:wrap;gap:6px 16px;font-size:13px;font-weight:600"><? foreach ($sup_c as $s => $c): ?><span style="display:inline-flex;align-items:center;gap:6px"><i style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?=$c?>"></i><?=wb::h($s)?></span><? endforeach ?></span>
			</div>
			<?=$svg?>
		</div>

		<div class="wb_box wb_table">
			<div class="wb_box_h"><span>Сатып алулар</span></div>
			<div class="wb_tr wb_th">
				<div class="wb_td">Күні</div>
				<div class="wb_td wb_td_name">Жеткізуші, тауар</div>
				<div class="wb_td">Саны × баға</div>
				<div class="wb_td">1 <?=wb::h($unit)?></div>
				<div class="wb_td">Өзгеріс</div>
			</div>
			<? for ($k = count($buys) - 1; $k >= 0; $k--): $b = $buys[$k]; $pb = $k > 0 ? $buys[$k - 1] : null; ?>
				<div class="wb_tr">
					<div class="wb_td" data-h="Күні"><?=wb::dt($b['doc_date'])?><div class="wb_small"><a href="/waybill/item.php?id=<?=$b['wid']?>">№<?=$b['number']?><?=($b['doc_no'] ? ' ('.wb::h($b['doc_no']).')' : '')?></a></div></div>
					<div class="wb_td wb_td_name">
						<div class="wb_name"><i class="wbr_dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?=$sup_c[$b['sname'] ?: '—']?>"></i> <?=wb::h($b['sname'] ?: 'Жеткізуші көрсетілмеген')?></div>
						<div class="wb_small"><?=wb::h($b['name'])?><?=(wb::f($b) != 1 ? ' · 1 '.wb::h($b['unit']).' = '.wb::qty(wb::f($b)).' '.wb::h($unit) : '')?></div>
					</div>
					<div class="wb_td" data-h="Саны × баға"><?=wb::qty($b['quantity'])?> <?=wb::h($b['unit'])?> × <?=wb::sum($b['price'])?></div>
					<div class="wb_td" data-h="1 <?=wb::h($unit)?>"><b><?=wb::up($b['up'])?></b></div>
					<div class="wb_td" data-h="Өзгеріс"><?=($pb ? wb::delta($b['up'], $pb['up']) : '<span class="wb_small">алғашқы</span>')?></div>
				</div>
			<? endfor ?>
		</div>
	<? endif ?>

	<? if ($moves): ?>
		<div class="wb_box wb_table">
			<div class="wb_box_h"><span>Қалдық қозғалысы</span><span class="wb_small">қазір <?=wb::qty($stock)?> <?=wb::h($unit)?></span></div>
			<div class="wb_tr wb_th">
				<div class="wb_td">Күні</div>
				<div class="wb_td wb_td_name">Түрі</div>
				<div class="wb_td">Саны</div>
				<div class="wb_td">Сомасы</div>
			</div>
			<? foreach ($moves as $m): $mt = ['in' => 'Кіріс', 'unpost' => 'Кіріс болдырылмады', 'sale' => 'Сатылым', 'rev' => 'Ревизия'][$m['type']] ?? $m['type']; ?>
				<div class="wb_tr">
					<div class="wb_td" data-h="Күні"><?=wb::dt($m['dt'], $m['type'] == 'sale')?></div>
					<div class="wb_td wb_td_name">
						<div class="wb_name"><?=$mt?><?=($m['type'] == 'sale' ? ' · тапсырыс #'.(int)$m['order_id'] : '')?></div>
						<div class="wb_small">
							<? if ($m['ref_type'] == 'waybill'): ?><a href="/waybill/item.php?id=<?=(int)$m['ref_id']?>">накладной</a> · <?=wb::h($m['note'])?>
							<? elseif ($m['ref_type'] == 'revision'): ?><a href="/waybill/revision.php?id=<?=(int)$m['ref_id']?>"><?=wb::h($m['note'])?></a>
							<? else: ?><?=wb::h($m['note'])?><? endif ?>
						</div>
					</div>
					<div class="wb_td" data-h="Саны"><b class="<?=($m['qty'] < 0 ? 'wb_c_red' : 'wb_c_green')?>"><?=($m['qty'] > 0 ? '+' : '').wb::qty($m['qty'])?></b> <span class="wb_small"><?=wb::h($unit)?></span></div>
					<div class="wb_td" data-h="Сомасы"><?=wb::sum(abs($m['qty'] * $m['price']))?></div>
				</div>
			<? endforeach ?>
		</div>
	<? endif ?>

</div>

<? include "../block/footer.php"; ?>
