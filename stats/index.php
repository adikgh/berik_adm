<? include "../config/core.php";

	//
	if (!$user_id) { header('location: /'); exit(); }
	if ($user_right['positions_id'] == 3) { header('location: /kassa/'); exit(); }

	$company = intval($company);


	// ---------- Кезең: ай немесе жыл ----------
	$mode = in_array(@$_GET['mode'], ['d', 'm', 'y'], true) ? $_GET['mode'] : 'm';
	$now_ts = time();
	$now_dt = date('Y-m-d H:i:s', $now_ts);
	$today_bd = date('Y-m-d', $now_ts - 6 * 3600); // бүгінгі жұмыс күні (06:00-ден)

	$m_names = ['', 'Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'];
	$m_short = ['', 'Қаң', 'Ақп', 'Нау', 'Сәу', 'Мам', 'Мау', 'Шіл', 'Там', 'Қыр', 'Қаз', 'Қар', 'Жел'];
	$m_low = ['', 'қаңтар', 'ақпан', 'наурыз', 'сәуір', 'мамыр', 'маусым', 'шілде', 'тамыз', 'қыркүйек', 'қазан', 'қараша', 'желтоқсан'];
	$w_names = [1 => 'Дүйсенбі', 2 => 'Сейсенбі', 3 => 'Сәрсенбі', 4 => 'Бейсенбі', 5 => 'Жұма', 6 => 'Сенбі', 7 => 'Жексенбі'];

	if ($mode == 'd') {
		$dd = (preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)@$_GET['d']) && strtotime($_GET['d'])) ? $_GET['d'] : $today_bd;
		$p_start_d = $dd;
		$p_end_d   = date('Y-m-d', strtotime("$dd +1 day"));
		$prev_d    = date('Y-m-d', strtotime("$dd -1 day"));
		$step      = '1 day';
		$title     = intval(substr($dd, 8, 2)).' '.$m_low[intval(substr($dd, 5, 2))].' '.substr($dd, 0, 4).($dd == $today_bd ? ', бүгін' : '');
		$prev_link = '?mode=d&d='.$prev_d;
		$next_link = '?mode=d&d='.$p_end_d;
		$prev_name = 'өткен күн';
	} elseif ($mode == 'm') {
		$ym = preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', (string)@$_GET['m']) ? $_GET['m'] : substr($today_bd, 0, 7);
		$p_start_d = $ym.'-01';
		$p_end_d   = date('Y-m-d', strtotime("$p_start_d +1 month"));
		$prev_d    = date('Y-m-d', strtotime("$p_start_d -1 month"));
		$step      = '1 month';
		$title     = $m_names[intval(substr($ym, 5, 2))].' '.substr($ym, 0, 4);
		$prev_link = '?mode=m&m='.substr($prev_d, 0, 7);
		$next_link = '?mode=m&m='.substr($p_end_d, 0, 7);
		$prev_name = 'өткен ай';
	} else {
		$yy = preg_match('/^20\d{2}$/', (string)@$_GET['y']) ? $_GET['y'] : substr($today_bd, 0, 4);
		$p_start_d = $yy.'-01-01';
		$p_end_d   = ($yy + 1).'-01-01';
		$prev_d    = ($yy - 1).'-01-01';
		$step      = '1 year';
		$title     = $yy.' жыл';
		$prev_link = '?mode=y&y='.($yy - 1);
		$next_link = '?mode=y&y='.($yy + 1);
		$prev_name = 'өткен жыл';
	}

	// жұмыс күні 06:00-ден басталады
	$p_start = "$p_start_d 06:00:00";
	$p_end   = "$p_end_d 06:00:00";
	$is_current = ($now_dt >= $p_start && $now_dt < $p_end);
	$is_future  = ($now_dt < $p_start);

	// ағымдағы кезеңде тек болған заказдар (алдағы предзаказдар бөлек)
	$p_to = $is_current ? $now_dt : $p_end;

	// салыстыру: өткен кезеңнің дәл осы уақытқа дейінгі бөлігі
	$c_start = "$prev_d 06:00:00";
	$c_to = $is_current ? date('Y-m-d H:i:s', strtotime("$now_dt -$step")) : $p_start;
	if ($c_to > $p_start) $c_to = $p_start; // 31-күн сияқты жағдайлар үшін


	// ---------- Точкалар ----------
	$branches = [];
	$br_q = db::query("select * from company_branch where company_id = '$company' order by number asc");
	while ($br_q && $br_d = mysqli_fetch_assoc($br_q)) $branches[$br_d['id']] = $br_d['name'];
	$f_branch = intval(@$_GET['branch']);
	if (!isset($branches[$f_branch])) $f_branch = 0;
	$br_cond = $f_branch ? " and branch_id = '$f_branch'" : "";
	$q_branch = $f_branch ? '&branch='.$f_branch : '';
	$q_period = $mode == 'd' ? 'mode=d&d='.$dd : ($mode == 'm' ? 'mode=m&m='.$ym : 'mode=y&y='.$yy);


	// ---------- Бағандар бар-жоғы (жаңа бағандар барлық базада болмауы мүмкін) ----------
	$cols = [];
	$col_q = db::query("SHOW COLUMNS FROM `retail_orders`");
	while ($col_q && $col_d = mysqli_fetch_assoc($col_q)) $cols[$col_d['Field']] = 1;
	$sum = function ($c) use ($cols) { return isset($cols[$c]) ? "COALESCE(SUM(`$c`), 0)" : "0"; };

	$ok_st  = "order_status in (1, 8, 2, 11, 3, 9, 4)";
	$otk_st = "order_status in (5, 6, 10)"; // 6, 10: отказ, бірақ бізде қалған ақша (kept_sum) жалпы сомаға қосылады
	$base   = "company_id = '$company' and number is not null $br_cond";
	$bd     = "DATE(DATE_SUB(ins_dt, INTERVAL 6 HOUR))"; // жұмыс күні

	function st_row($sql) {
		$q = db::query($sql);
		return ($q && $r = mysqli_fetch_assoc($q)) ? $r : [];
	}
	function st_money($n) { return number_format(intval($n), 0, '.', ' ').' ₸'; }
	function st_num($n) { return number_format(intval($n), 0, '.', ' '); }
	function st_delta($cur, $prev) {
		if (!$prev) return '';
		$p = round(($cur - $prev) / $prev * 100);
		$cls = $p > 0 ? 'st_up' : ($p < 0 ? 'st_down' : 'st_eq');
		$ic = $p > 0 ? 'fa-arrow-up' : ($p < 0 ? 'fa-arrow-down' : 'fa-equals');
		return '<span class="st_delta '.$cls.'"><i class="far '.$ic.'"></i>'.abs($p).'%</span>';
	}


	// ---------- Негізгі сандар ----------
	$cur = st_row("select COUNT(*) as cnt, COALESCE(SUM(total), 0) as total,
		".$sum('pay_qr')." as pay_qr, ".$sum('pay_cash')." as pay_cash, ".$sum('pay_transfer')." as pay_transfer,
		".$sum('pay_red')." as pay_red, ".$sum('pay_prepay')." as pay_prepay, ".$sum('pay_delivery')." as pay_delivery
		from retail_orders where $base and $ok_st and ins_dt >= '$p_start' and ins_dt < '$p_to'");
	$prv = st_row("select COUNT(*) as cnt, COALESCE(SUM(total), 0) as total
		from retail_orders where $base and $ok_st and ins_dt >= '$c_start' and ins_dt < '$c_to'");
	$otk = st_row("select COUNT(*) as cnt, COALESCE(SUM(total), 0) as total, COALESCE(SUM(IF(order_status in (6, 10), kept_sum, 0)), 0) as kept
		from retail_orders where $base and $otk_st and ins_dt >= '$p_start' and ins_dt < '$p_to'");
	$otk_prv = st_row("select COUNT(*) as cnt from retail_orders where $base and $otk_st and ins_dt >= '$c_start' and ins_dt < '$c_to'");
	$future = $is_current ? st_row("select COUNT(*) as cnt, COALESCE(SUM(total), 0) as total
		from retail_orders where $base and $ok_st and ins_dt >= '$now_dt' and ins_dt < '$p_end'") : [];

	$cnt = intval(@$cur['cnt']);
	$total = intval(@$cur['total']) + intval(@$otk['kept']); // + отказтан бізде қалған ақша
	$avg = $cnt ? intval($total / $cnt) : 0;
	$prv_avg = @$prv['cnt'] ? intval($prv['total'] / $prv['cnt']) : 0;

	// күніне орташа (ағымдағы айда өткен күндерге бөлінеді)
	$days_all = (strtotime($p_end_d) - strtotime($p_start_d)) / 86400;
	$days_pass = $is_current ? (strtotime($today_bd) - strtotime($p_start_d)) / 86400 + 1 : $days_all;
	$per_day = $days_pass > 0 ? intval($total / $days_pass) : 0;


	// ---------- График: ай → күндер, жыл → айлар ----------
	$series = [];
	if ($mode == 'd') {
		// сағат бойынша: 06:00-ден келесі күннің 05:00-іне дейін
		$cur_h = $is_current ? intval(date('G', $now_ts)) : -1;
		for ($i = 0; $i < 24; $i++) {
			$h = ($i + 6) % 24;
			$series[$h] = ['label' => sprintf('%02d', $h), 'cnt' => 0, 'total' => 0, 'link' => '/orders/?time='.$dd, 'name' => sprintf('%02d:00–%02d:00', $h, ($h + 1) % 24), 'today' => $h == $cur_h, 'wk' => false];
		}
		$g_q = db::query("select HOUR(ins_dt) as k, SUM($ok_st) as cnt, COALESCE(SUM(IF($ok_st, total, IF(order_status in (6, 10), kept_sum, 0))), 0) as total, SUM($otk_st) as otk from retail_orders
			where $base and ($ok_st or $otk_st) and ins_dt >= '$p_start' and ins_dt < '$p_to' group by k");
	} elseif ($mode == 'm') {
		for ($t = strtotime($p_start_d); $t < strtotime($p_end_d); $t += 86400) {
			$d = date('Y-m-d', $t);
			$series[$d] = ['label' => intval(date('j', $t)), 'cnt' => 0, 'total' => 0, 'link' => '?mode=d&d='.$d.$q_branch, 'name' => date('j', $t).' '.$m_low[intval(date('n', $t))], 'today' => $d == $today_bd, 'wk' => date('N', $t) >= 6];
		}
		$g_q = db::query("select $bd as k, SUM($ok_st) as cnt, COALESCE(SUM(IF($ok_st, total, IF(order_status in (6, 10), kept_sum, 0))), 0) as total, SUM($otk_st) as otk from retail_orders
			where $base and ($ok_st or $otk_st) and ins_dt >= '$p_start' and ins_dt < '$p_to' group by k");
	} else {
		for ($i = 1; $i <= 12; $i++) {
			$k = sprintf('%s-%02d', $yy, $i);
			$series[$k] = ['label' => $m_short[$i], 'cnt' => 0, 'total' => 0, 'link' => '?mode=m&m='.$k.$q_branch, 'name' => $m_names[$i], 'today' => $k == substr($today_bd, 0, 7), 'wk' => false];
		}
		$g_q = db::query("select DATE_FORMAT($bd, '%Y-%m') as k, SUM($ok_st) as cnt, COALESCE(SUM(IF($ok_st, total, IF(order_status in (6, 10), kept_sum, 0))), 0) as total, SUM($otk_st) as otk from retail_orders
			where $base and ($ok_st or $otk_st) and ins_dt >= '$p_start' and ins_dt < '$p_to' group by k");
	}
	while ($g_q && $g_d = mysqli_fetch_assoc($g_q)) {
		if (isset($series[$g_d['k']])) { $series[$g_d['k']]['cnt'] = intval($g_d['cnt']); $series[$g_d['k']]['total'] = intval($g_d['total']); $series[$g_d['k']]['otk'] = intval($g_d['otk']); }
	}
	$g_max = 0; $best = null;
	foreach ($series as $k => $s) { if ($s['total'] > $g_max) { $g_max = $s['total']; $best = $k; } }

	// ---------- Тренд (сызықтық регрессия) ----------
	// Ағымдағы кезеңде тек толық өткен нүктелер алынады (бүгінгі/осы айдың толмаған мәні трендті бұзбасын)
	$g_n = count($series);
	$tr_pts = [];
	$i = 0;
	foreach ($series as $k => $s) {
		if ($is_current && $s['today']) break;
		$tr_pts[] = [$i, $s['total']];
		$i++;
	}
	$trend = null;
	if (count($tr_pts) >= 3 && $g_max) {
		$tn = count($tr_pts); $sx = $sy = $sxy = $sxx = 0;
		foreach ($tr_pts as $pt) { $sx += $pt[0]; $sy += $pt[1]; $sxy += $pt[0] * $pt[1]; $sxx += $pt[0] * $pt[0]; }
		$den = $tn * $sxx - $sx * $sx;
		if ($den) {
			$tb = ($tn * $sxy - $sx * $sy) / $den;
			$ta = ($sy - $tb * $sx) / $tn;
			$y_pct = function ($x) use ($ta, $tb, $g_max) { return max(0, min(100, ($ta + $tb * $x) / $g_max * 100)); };
			$mean = $sy / $tn;
			$trend = [
				'x1' => 0.5 / $g_n * 1000, 'y1' => 100 - $y_pct(0),
				'x2' => ($g_n - 0.5) / $g_n * 1000, 'y2' => 100 - $y_pct($g_n - 1),
				'pct' => $mean ? round($tb * ($tn - 1) / $mean * 100) : 0,
			];
		}
	}
	$g_minw = $mode == 'y' ? 44 : 22; // бағанның ең аз ені + аралық


	// ---------- Төлем түрлері ----------
	$pays = [
		['name' => 'Kaspi QR',   'val' => intval(@$cur['pay_qr']),       'clr' => '#e5484d'],
		['name' => 'Қолма-қол',  'val' => intval(@$cur['pay_cash']),     'clr' => '#0EA768'],
		['name' => 'Аударым',    'val' => intval(@$cur['pay_transfer']), 'clr' => '#1f75a8'],
		['name' => 'RED',        'val' => intval(@$cur['pay_red']),      'clr' => '#d9480f'],
		['name' => 'Предоплата', 'val' => intval(@$cur['pay_prepay']),   'clr' => '#8e6cd8'],
	];
	$pay_sum = array_sum(array_column($pays, 'val'));


	// ---------- Заказ түрлері ----------
	$types = [
		1 => ['name' => 'Жеткізу', 'icon' => 'fa-car'],
		2 => ['name' => 'Өзімен',  'icon' => 'fa-shopping-bag'],
		4 => ['name' => 'Кафе',    'icon' => 'fa-utensils'],
		3 => ['name' => 'Сервис',  'icon' => 'fa-motorcycle'],
	];
	foreach ($types as $k => $v) { $types[$k]['cnt'] = 0; $types[$k]['total'] = 0; }
	$t_q = db::query("select order_type, COUNT(*) as cnt, COALESCE(SUM(total), 0) as total from retail_orders
		where $base and $ok_st and ins_dt >= '$p_start' and ins_dt < '$p_to' group by order_type");
	while ($t_q && $t_d = mysqli_fetch_assoc($t_q)) {
		$k = isset($types[$t_d['order_type']]) ? $t_d['order_type'] : 1;
		$types[$k]['cnt'] += $t_d['cnt']; $types[$k]['total'] += $t_d['total'];
	}


	// ---------- Точкалар бойынша (барлығы таңдалса) ----------
	$by_branch = [];
	if (!$f_branch && count($branches) > 1) {
		foreach ($branches as $id => $name) $by_branch[$id] = ['name' => $name, 'cnt' => 0, 'total' => 0];
		$b_q = db::query("select branch_id, COUNT(*) as cnt, COALESCE(SUM(total), 0) as total from retail_orders
			where $base and $ok_st and ins_dt >= '$p_start' and ins_dt < '$p_to' group by branch_id");
		while ($b_q && $b_d = mysqli_fetch_assoc($b_q)) {
			if (isset($by_branch[$b_d['branch_id']])) { $by_branch[$b_d['branch_id']]['cnt'] = $b_d['cnt']; $by_branch[$b_d['branch_id']]['total'] = $b_d['total']; }
		}
	}


	// ---------- Апта күндері (күніне орташа) ----------
	$wdays = [];
	foreach ($w_names as $k => $n) $wdays[$k] = ['name' => $n, 'total' => 0, 'days' => 0];
	foreach ($series as $k => $s) {
		if ($mode != 'm') break;
		if ($k > $today_bd) continue;
		$w = date('N', strtotime($k));
		$wdays[$w]['total'] += $s['total']; $wdays[$w]['days']++;
	}
	if ($mode == 'y') {
		$w_q = db::query("select WEEKDAY($bd) + 1 as w, COUNT(DISTINCT $bd) as days, COALESCE(SUM(total), 0) as total from retail_orders
			where $base and $ok_st and ins_dt >= '$p_start' and ins_dt < '$p_to' group by w");
		while ($w_q && $w_d = mysqli_fetch_assoc($w_q)) { $wdays[$w_d['w']]['total'] = $w_d['total']; $wdays[$w_d['w']]['days'] = $w_d['days']; }
	}
	$w_max = 0;
	foreach ($wdays as $k => $w) { $wdays[$k]['avg'] = $w['days'] ? intval($w['total'] / $w['days']) : 0; $w_max = max($w_max, $wdays[$k]['avg']); }


	// ---------- Ең көп сатылған тағамдар ----------
	$top = [];
	$tp_q = db::query("select op.product_id, COALESCE(SUM(op.quantity), 0) as qty, COALESCE(SUM(op.quantity * op.price), 0) as total,
		MAX(p.name_kz) as name_kz, MAX(p.name_ru) as name_ru
		from retail_orders_products op
		join retail_orders o on o.id = op.order_id
		left join product p on p.id = op.product_id
		where o.company_id = '$company' and o.number is not null and o.order_status in (1, 8, 2, 11, 3, 9, 4) ".($f_branch ? " and o.branch_id = '$f_branch'" : "")."
			and o.ins_dt >= '$p_start' and o.ins_dt < '$p_to'
		group by op.product_id order by total desc limit 10");
	while ($tp_q && $tp_d = mysqli_fetch_assoc($tp_q)) $top[] = $tp_d;
	$top_max = $top ? max(1, intval($top[0]['total'])) : 1;


	// site setting
	$menu_name = 'stats';
	$page_name = 'Статистика';
	$css = ['stats'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c st_page">

		<!-- сүзгілер -->
		<div class="st_bar">
			<div class="st_seg">
				<a class="st_seg_i <?=$mode=='d'?'st_seg_act':''?>" href="?mode=d<?=$q_branch?>">Күн</a>
				<a class="st_seg_i <?=$mode=='m'?'st_seg_act':''?>" href="?mode=m<?=$q_branch?>">Ай</a>
				<a class="st_seg_i <?=$mode=='y'?'st_seg_act':''?>" href="?mode=y<?=$q_branch?>">Жыл</a>
			</div>
			<div class="st_nav">
				<a class="st_nav_b" href="<?=$prev_link.$q_branch?>"><i class="far fa-chevron-left"></i></a>
				<div class="st_nav_t"><?=$title?></div>
				<a class="st_nav_b" href="<?=$next_link.$q_branch?>"><i class="far fa-chevron-right"></i></a>
			</div>
			<? if (count($branches) > 1): ?>
				<div class="st_seg">
					<a class="st_seg_i <?=!$f_branch?'st_seg_act':''?>" href="?<?=$q_period?>">Барлығы</a>
					<? foreach ($branches as $id => $name): ?>
						<a class="st_seg_i <?=$f_branch==$id?'st_seg_act':''?>" href="?<?=$q_period?>&branch=<?=$id?>"><?=$name?></a>
					<? endforeach ?>
				</div>
			<? endif ?>
		</div>

		<? if ($is_future): ?>
			<div class="st_card st_empty">Бұл кезең әлі басталған жоқ.</div>
		<? else: ?>

		<!-- негізгі сандар -->
		<div class="st_kpis" id="st_kpis">
			<div class="st_kpi" data-m="cnt">
				<div class="st_kpi_h"><i class="fal fa-receipt"></i>Сатылым саны</div>
				<div class="st_kpi_v"><?=st_num($cnt)?> <?=st_delta($cnt, @$prv['cnt'])?></div>
				<div class="st_kpi_s"><?=$prev_name?>: <?=st_num(@$prv['cnt'])?></div>
			</div>
			<div class="st_kpi st_kpi_main st_kpi_act" data-m="total">
				<div class="st_kpi_h"><i class="fal fa-wallet"></i>Жалпы сумма</div>
				<div class="st_kpi_v"><?=st_money($total)?> <?=st_delta($total, @$prv['total'])?></div>
				<div class="st_kpi_s"><? if ($mode == 'd'): ?><?=$prev_name?>: <?=st_money(@$prv['total'])?><? else: ?>күніне орташа: <?=st_money($per_day)?><? endif ?></div>
			</div>
			<div class="st_kpi" data-m="avg">
				<div class="st_kpi_h"><i class="fal fa-shopping-basket"></i>Орташа чек</div>
				<div class="st_kpi_v"><?=st_money($avg)?> <?=st_delta($avg, $prv_avg)?></div>
				<div class="st_kpi_s"><?=$prev_name?>: <?=st_money($prv_avg)?></div>
			</div>
			<div class="st_kpi" data-m="otk">
				<div class="st_kpi_h"><i class="fal fa-ban"></i>Отказ</div>
				<div class="st_kpi_v"><?=st_num(@$otk['cnt'])?> <span class="st_kpi_vs"><?=st_money(@$otk['total'])?><?=(@$otk['kept'] ? ' · бізде қалды '.st_money($otk['kept']) : '')?></span></div>
				<div class="st_kpi_s"><?=$prev_name?>: <?=st_num(@$otk_prv['cnt'])?></div>
			</div>
		</div>

		<? if (@$future['cnt']): ?>
			<div class="st_note"><i class="fal fa-clock"></i>Алдағы предзаказдар: <b><?=st_num($future['cnt'])?></b> заказ, <b><?=st_money($future['total'])?></b>. Олар уақыты келгенде есепке қосылады.</div>
		<? endif ?>
		<? if ($is_current): ?>
			<div class="st_note st_note_l">Салыстыру: <?=$prev_name?>дың осы уақытқа дейінгі бөлігімен.</div>
		<? endif ?>

		<!-- график -->
		<div class="st_card">
			<div class="st_card_h">
				<div><?=$mode=='d'?'Сағат бойынша':($mode=='m'?'Күндер бойынша':'Айлар бойынша')?> <span id="st_g_m" class="st_g_m"></span></div>
				<div class="st_card_hr">
					<? if ($trend): ?><div class="st_card_hs st_trend_lg"><span></span>Тренд: <?=$trend['pct'] > 0 ? 'өсу +'.$trend['pct'].'%' : ($trend['pct'] < 0 ? 'төмендеу '.$trend['pct'].'%' : 'тұрақты')?></div><? endif ?>
					<? if ($best !== null && $g_max): ?><div class="st_card_hs" id="st_g_best">Ең жақсы: <?=$series[$best]['name']?>, <?=st_money($g_max)?></div><? endif ?>
				</div>
			</div>
			<div class="st_chart st_chart_<?=$mode?>" id="st_chart">
			<div class="st_chart_in" style="min-width:<?=$g_n * $g_minw?>px">
				<? if ($trend): ?>
					<svg class="st_trend" viewBox="0 0 1000 100" preserveAspectRatio="none"><line x1="<?=round($trend['x1'], 2)?>" y1="<?=round($trend['y1'], 2)?>" x2="<?=round($trend['x2'], 2)?>" y2="<?=round($trend['y2'], 2)?>" vector-effect="non-scaling-stroke" /></svg>
				<? endif ?>
				<? foreach ($series as $k => $s): ?>
					<a class="st_col <?=$s['today']?'st_col_today':''?> <?=$s['wk']?'st_col_wk':''?>" href="<?=$s['link']?>" data-n="<?=htmlspecialchars($s['name'])?>" data-cnt="<?=$s['cnt']?>" data-total="<?=$s['total']?>" data-avg="<?=$s['cnt'] ? intval($s['total'] / $s['cnt']) : 0?>" data-otk="<?=intval(@$s['otk'])?>">
						<div class="st_col_bar">
							<div class="st_col_f" style="height:<?=$g_max ? max($s['total'] ? 2 : 0, round($s['total'] / $g_max * 100)) : 0?>%"></div>
						</div>
						<div class="st_col_l"><?=$s['label']?></div>
						<div class="st_tip"><b><?=$s['name']?></b><span><?=st_money($s['total'])?></span><span><?=st_num($s['cnt'])?> заказ</span><span>орташа: <?=st_money($s['cnt'] ? $s['total'] / $s['cnt'] : 0)?></span><span>отказ: <?=st_num(@$s['otk'])?></span></div>
					</a>
				<? endforeach ?>
			</div>
			</div>
		</div>

		<div class="st_grid">

			<!-- төлем түрлері -->
			<div class="st_card">
				<div class="st_card_h"><div>Төлем түрлері</div></div>
				<? if ($pay_sum): ?>
					<div class="st_stack">
						<? foreach ($pays as $p): if (!$p['val']) continue; ?>
							<div style="width:<?=round($p['val'] / $pay_sum * 100, 2)?>%;background:<?=$p['clr']?>"></div>
						<? endforeach ?>
					</div>
				<? endif ?>
				<div class="st_list">
					<? foreach ($pays as $p): ?>
						<div class="st_li">
							<div class="st_li_n"><span class="st_dot" style="background:<?=$p['clr']?>"></span><?=$p['name']?></div>
							<div class="st_li_p"><?=$pay_sum ? round($p['val'] / $pay_sum * 100) : 0?>%</div>
							<div class="st_li_v"><?=st_money($p['val'])?></div>
						</div>
					<? endforeach ?>
					<div class="st_li st_li_sub">
						<div class="st_li_n"><i class="fal fa-truck"></i>Жеткізу ақысы</div>
						<div class="st_li_p"></div>
						<div class="st_li_v"><?=st_money(@$cur['pay_delivery'])?></div>
					</div>
				</div>
			</div>

			<!-- заказ түрлері -->
			<div class="st_card">
				<div class="st_card_h"><div>Заказ түрлері</div></div>
				<div class="st_list">
					<? foreach ($types as $t): ?>
						<div class="st_li st_li_bar">
							<div class="st_li_n"><i class="fal <?=$t['icon']?>"></i><?=$t['name']?></div>
							<div class="st_li_p"><?=st_num($t['cnt'])?> шт</div>
							<div class="st_li_v"><?=st_money($t['total'])?></div>
							<div class="st_li_line"><div style="width:<?=$total ? round($t['total'] / $total * 100, 1) : 0?>%"></div></div>
						</div>
					<? endforeach ?>
				</div>
			</div>

			<? if ($by_branch): ?>
				<!-- точкалар -->
				<div class="st_card">
					<div class="st_card_h"><div>Точкалар</div></div>
					<div class="st_list">
						<? foreach ($by_branch as $id => $b): ?>
							<a class="st_li st_li_bar" href="?<?=$q_period?>&branch=<?=$id?>">
								<div class="st_li_n"><i class="fal fa-store"></i><?=$b['name']?></div>
								<div class="st_li_p"><?=st_num($b['cnt'])?> шт</div>
								<div class="st_li_v"><?=st_money($b['total'])?></div>
								<div class="st_li_line"><div style="width:<?=$total ? round($b['total'] / $total * 100, 1) : 0?>%"></div></div>
							</a>
						<? endforeach ?>
					</div>
				</div>
			<? endif ?>

			<? if ($mode != 'd'): ?>
			<!-- апта күндері -->
			<div class="st_card">
				<div class="st_card_h"><div>Апта күндері</div><div class="st_card_hs">күніне орташа сумма</div></div>
				<div class="st_list">
					<? foreach ($wdays as $w): ?>
						<div class="st_li st_li_bar">
							<div class="st_li_n"><?=$w['name']?></div>
							<div class="st_li_p"></div>
							<div class="st_li_v"><?=st_money($w['avg'])?></div>
							<div class="st_li_line st_li_line_b"><div style="width:<?=$w_max ? round($w['avg'] / $w_max * 100, 1) : 0?>%"></div></div>
						</div>
					<? endforeach ?>
				</div>
			</div>
			<? endif ?>

		</div>

		<!-- тағамдар -->
		<div class="st_card">
			<div class="st_card_h"><div>Ең көп сатылған 10 тағам</div></div>
			<? if ($top): ?>
				<div class="st_list">
					<? foreach ($top as $i => $tp): ?>
						<div class="st_li st_li_bar">
							<div class="st_li_n"><span class="st_rank"><?=$i + 1?></span><?=htmlspecialchars(trim(strip_tags(html_entity_decode($tp['name_kz'] ?: ($tp['name_ru'] ?: '#'.$tp['product_id'])))))?></div>
							<div class="st_li_p"><?=st_num($tp['qty'])?> шт</div>
							<div class="st_li_v"><?=st_money($tp['total'])?></div>
							<div class="st_li_line"><div style="width:<?=round($tp['total'] / $top_max * 100, 1)?>%"></div></div>
						</div>
					<? endforeach ?>
				</div>
			<? else: ?>
				<div class="st_empty">Бұл кезеңде сатылым жоқ.</div>
			<? endif ?>
		</div>

		<? endif ?>

		<br><br>
	</div>

	<style>
		.st_kpis .st_kpi { cursor: pointer; transition: box-shadow .15s, border-color .15s; }
		.st_kpis .st_kpi:hover { box-shadow: 0 2px 10px rgba(0,0,0,.08); }
		.st_kpis .st_kpi_act { box-shadow: 0 0 0 2px #1f75a8 inset; }
		.st_g_m { font-weight: 400; opacity: .6; }
		.st_chart_otk .st_col_f { background: #e5484d; }
	</style>
	<script>
	(function () {
		var box = document.getElementById('st_kpis'), chart = document.getElementById('st_chart');
		if (!box || !chart) return;
		var names = {cnt: 'сатылым саны', total: '', avg: 'орташа чек', otk: 'отказ'};
		var money = {total: 1, avg: 1};
		var best = document.getElementById('st_g_best'), bestHtml = best ? best.innerHTML : '';
		var trend = chart.querySelector('.st_trend'), trendLg = document.querySelector('.st_trend_lg');
		function fmt(n, m) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + (money[m] ? ' ₸' : ''); }
		function show(m) {
			var cols = chart.querySelectorAll('.st_col'), max = 0, bi = null;
			cols.forEach(function (c) { var v = +c.dataset[m]; if (v > max) { max = v; bi = c; } });
			cols.forEach(function (c) {
				var v = +c.dataset[m];
				c.querySelector('.st_col_f').style.height = (max ? Math.max(v ? 2 : 0, Math.round(v / max * 100)) : 0) + '%';
			});
			chart.classList.toggle('st_chart_otk', m == 'otk');
			if (trend) trend.style.display = m == 'total' ? '' : 'none';
			if (trendLg) trendLg.style.display = m == 'total' ? '' : 'none';
			if (best) {
				best.innerHTML = m == 'total' ? bestHtml : (bi ? (m == 'otk' ? 'Ең көп: ' : 'Ең жоғары: ') + bi.dataset.n + ', ' + fmt(max, m) : '');
				best.style.display = best.innerHTML ? '' : 'none';
			}
			document.getElementById('st_g_m').textContent = names[m] ? '· ' + names[m] : '';
			box.querySelectorAll('.st_kpi').forEach(function (k) { k.classList.toggle('st_kpi_act', k.dataset.m == m); });
		}
		box.addEventListener('click', function (e) { var k = e.target.closest('.st_kpi'); if (k && k.dataset.m) show(k.dataset.m); });
	})();
	</script>

<? include "../block/footer.php"; ?>
