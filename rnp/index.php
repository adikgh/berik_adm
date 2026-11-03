<? include "../config/core.php";
	include "lib.php";

	//
	if (!$user_id) { header('location: /'); exit(); }
	if ($user_right['positions_id'] == 3) { header('location: /kassa/'); exit(); }

	$company = intval($company);
	rnp_table();


	// ---------- Кезең: күндік / апталық / айлық (../opiu/lib.php) ----------
	$now_dt = date('Y-m-d H:i:s');
	$today_bd = date('Y-m-d', time() - 6 * 3600);
	extract(rnp_period($today_bd, $now_dt, false)); // РНП-де толтыру жоқ, тек қарау


	// ---------- Компаниялар мен жолдар ----------
	$companies = rnp_companies($company, $user_right);
	$rows = rnp_rows_all($companies);
	$ids = implode(',', array_keys($companies) ?: [0]);

	$val = [];
	foreach ($cols as $d) $val[$d] = [];


	// ---------- Базадан: айналым мен заказ саны (Статистика бетімен бірдей ереже) ----------
	$ok_st = "order_status in (1, 8, 2, 11, 3, 9, 4)";
	$bd = "DATE(DATE_SUB(ins_dt, INTERVAL 6 HOUR))";
	$q = db::query("select $b_ord as d, company_id as c,
			SUM(IF($ok_st, 1, 0)) as cnt,
			SUM(IF($ok_st, total, 0)) + SUM(IF(order_status in (6, 10), kept_sum, 0)) as sm
		from retail_orders
		where company_id in ($ids) and number is not null and ins_dt >= '$p_start' and ins_dt < '$p_to'
		group by d, c");
	while ($q && $r = mysqli_fetch_assoc($q)) {
		if (!isset($val[$r['d']])) continue;
		$val[$r['d']]['c'.$r['c'].'_s'] = intval($r['sm']);
		$val[$r['d']]['c'.$r['c'].'_c'] = intval($r['cnt']);
	}

	// ---------- Жаңа және қайта келген клиенттер (тапсырысқа байланған клиент бойынша) ----------
	// жаңа — клиенттің осы компаниядағы алғашқы тапсырысы осы бағанда; қайта келген — қалған клиенттер
	$q = db::query("select $b_ord as d, company_id as c, COUNT(DISTINCT client_id) as cc
		from retail_orders
		where company_id in ($ids) and number is not null and $ok_st and client_id > 0 and ins_dt >= '$p_start' and ins_dt < '$p_to'
		group by d, c");
	while ($q && $r = mysqli_fetch_assoc($q)) if (isset($val[$r['d']])) $val[$r['d']]['c'.$r['c'].'_rc'] = intval($r['cc']);
	$q = db::query("select $b_ord as d, c, COUNT(*) as nc from (
			select company_id as c, client_id, MIN(ins_dt) as ins_dt
			from retail_orders
			where company_id in ($ids) and number is not null and $ok_st and client_id > 0
			group by company_id, client_id
		) f where ins_dt >= '$p_start' and ins_dt < '$p_to' group by d, c");
	while ($q && $r = mysqli_fetch_assoc($q)) if (isset($val[$r['d']])) {
		$key = 'c'.$r['c'];
		$val[$r['d']][$key.'_nc'] = intval($r['nc']);
		$val[$r['d']][$key.'_rc'] = max(0, intval(@$val[$r['d']][$key.'_rc']) - intval($r['nc']));
	}

	// ---------- Қолмен жазылатындар: жарнама шығындары (ОПиУ-мен ортақ m_*), көрсетілім/клик/лид (rnp_*) ----------
	$in_map = array_flip(rnp_in_map());
	$in_map['rnp_target'] = 'tx'; // ескі таргет (жасырын, қосындыға кіреді)
	$keys = "'".implode("', '", array_keys($in_map))."'";
	$q = db::query("select company_id as c, $b_pnl as d, row_key as k, SUM(amount) as amount from company_pnl where company_id in ($ids) and `plan` = 0 and row_key in ($keys) and `day` >= '$r_start' and `day` < '$r_end' group by c, d, k");
	while ($q && $r = mysqli_fetch_assoc($q)) if (isset($val[$r['d']])) $val[$r['d']]['c'.$r['c'].'_'.$in_map[$r['k']]] = intval($r['amount']);

	// Қарауда: осы кезеңде сатылымы да, таргеті де жоқ компаниялар көрсетілмейді (таңдалған компаниядан басқа)
	if (count($companies) > 1) {
		$active = [$company => 1];
		foreach ($val as $dv) foreach ($dv as $k => $v) if ($v && preg_match('/^c(\d+)_/', $k, $m)) $active[intval($m[1])] = 1;
		$companies = array_intersect_key($companies, $active);
		$rows = rnp_rows_all($companies);
	}


	// site setting
	$menu_name = 'rnp';
	$page_name = 'РНП';
	$css = ['stats', 'opiu'];
	$js = ['opiu', 'st_pick'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c st_page rnp_page" data-ajx-nav=".st_page">

		<div class="st_bar">
			<?=$mode_seg?>
			<div class="st_nav">
				<a class="st_nav_b" href="<?=$prev_link?>"><i class="far fa-chevron-left"></i></a>
				<div class="st_nav_t"<? if ($mode != 'f'): ?> data-pick="1" data-mode="<?=$mode?>" data-d="<?=$r_start?>" data-today="<?=$today_bd?>" data-q="<?=$by == 'w' ? '&by=w' : ''?>"<? endif ?>><?=$title?></div>
				<a class="st_nav_b" href="<?=$next_link?>"><i class="far fa-chevron-right"></i></a>
			</div>
			<div class="rnp_info">
				<button type="button" class="rnp_info_b" aria-label="Түсіндірме" title="Түсіндірме">i</button>
				<div class="rnp_info_p">
				<div class="rnp_info_t">Кестені қалай оқу керек</div>
				<span><i class="rnp_lg rnp_lg_auto"></i>айналым, заказ, клиенттер базадан өзі толады</span>
				<span><i class="rnp_lg rnp_lg_in"></i>сұр жолдарды күн сайын жазасыз: ұяшыққа тікелей не «Запис қосу» арқылы</span>
				<span><i class="rnp_lg rnp_lg_link">⇄</i>жарнама шығындары ОПиУ-мен ортақ, бір жерде жазсаңыз екеуінде де көрінеді</span>
				<span><i class="rnp_lg rnp_lg_calc">ƒ</i>қалған жолдар (%, бағалар, ДРР, ROMI) өзі есептеледі</span>
				</div>
			</div>
			<div class="rnp_save" id="rnp_save"></div>
		</div>

		<div class="rnp_wrap">
			<table class="rnp_tb" id="rnp_tb"></table>
		</div>

	</div>

	<script>
		window.rnp = <?=json_encode([
			'month' => $ym,
			'days' => $cols,
			'heads' => $heads,
			'today' => $cur,
			'rows' => $rows,
			'val' => $val,
			'plan' => (object)[],
			'noplan' => true,
			'fact_label' => 'Барлығы',
			'url' => '/rnp/get.php',
			// таргетті Апта / Ай-Күндер көрінісінде тікелей не «Запис қосу» арқылы жазуға болады (тек бүгін мен өткен күн)
			'edit' => $user_right['positions_id'] != 3 && $mode != 'y' && $by != 'w',
			'pop' => $user_right['positions_id'] != 3,
			'mode' => $mode,
			'today_bd' => $today_bd,
			'today_vals' => (object)[],
			'single' => $single,
			'title' => $title,
		], JSON_UNESCAPED_UNICODE)?>;
	</script>

<? include "../block/footer.php"; ?>
