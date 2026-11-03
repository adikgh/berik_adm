<? include "../config/core.php";
	include "lib.php";

	//
	if (!$user_id) { header('location: /'); exit(); }
	if ($user_right['positions_id'] == 3) { header('location: /kassa/'); exit(); }

	$company = intval($company);
	rnp_table();


	// ---------- Кезең: күндік / апталық / айлық ----------
	$now_dt = date('Y-m-d H:i:s');
	$today_bd = date('Y-m-d', time() - 6 * 3600); // бүгінгі жұмыс күні (06:00-ден)
	extract(rnp_period($today_bd, $now_dt));


	// ---------- Жолдар ----------
	$types = rnp_types($company);
	$rows = rnp_rows($types);

	// $val[баған][key] — мәндер (күн, апта не ай бойынша), $plan[key] — айлық жоспар (тек күндік көріністе)
	$val = [];
	foreach ($cols as $d) $val[$d] = [];
	$plan = [];


	// ---------- Базадан: сатылым (Статистика бетімен бірдей ереже) ----------
	$ok_st = "order_status in (1, 8, 2, 11, 3, 9, 4)";
	$q = db::query("select $b_ord as d, order_type as t,
			SUM(IF($ok_st, 1, 0)) as cnt,
			SUM(IF($ok_st, total, 0)) + SUM(IF(order_status in (6, 10), kept_sum, 0)) as sm
		from retail_orders
		where company_id = '$company' and number is not null and ins_dt >= '$p_start' and ins_dt < '$p_to'
		group by d, t");
	while ($q && $r = mysqli_fetch_assoc($q)) {
		$d = $r['d']; $t = intval($r['t']);
		if (!isset($val[$d]) || !in_array($t, $types)) continue;
		$val[$d]['s_t'.$t] = intval($r['sm']);
		$val[$d]['c_t'.$t] = intval($r['cnt']);
	}

	// курьер жалақысы: сақталған күндік есеп болса — содан, әлі сақталмаған күн/курьер — тапсырыстардан (Кассадағыдай: жеткізу ақысы + компанияның жеткізу бағасы)
	$cdr = mysqli_num_rows(db::query("SHOW TABLES LIKE 'courier_day_report'")) > 0;
	$saved = []; // 'күн|курьер' => true
	if ($cdr) {
		$q = db::query("select $b_pnl as d, `day` as dd, courier_id as u, salary from courier_day_report where company_id = '$company' and `day` >= '$r_start' and `day` < '$r_end'");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$saved[$r['dd'].'|'.$r['u']] = true;
			if (isset($val[$r['d']])) $val[$r['d']]['courier'] = intval(@$val[$r['d']]['courier']) + intval($r['salary']);
		}
	}
	$dprice = intval(@mysqli_fetch_assoc(db::query("select delivery_price from company where id = '$company'"))['delivery_price']);
	$q = db::query("select $b_ord as d, $bd as dd, `сourier_id` as u, COUNT(*) as n, SUM(pay_delivery) as pd
		from retail_orders
		where company_id = '$company' and number is not null and `сourier_id` > 0 and order_status in (1, 8, 2, 11, 3, 4)
			and ins_dt >= '$p_start' and ins_dt < '$p_to'
		group by d, dd, u");
	while ($q && $r = mysqli_fetch_assoc($q)) {
		if (!isset($val[$r['d']]) || isset($saved[$r['dd'].'|'.$r['u']])) continue;
		$val[$r['d']]['courier'] = intval(@$val[$r['d']]['courier']) + intval($r['pd']) + intval($r['n']) * $dprice;
	}

	// ---------- Қолмен енгізілгендер ----------
	$q = db::query("select $b_pnl as d, row_key, SUM(amount) as amount from company_pnl where company_id = '$company' and `plan` = 0 and `day` >= '$r_start' and `day` < '$r_end' group by d, row_key");
	while ($q && $r = mysqli_fetch_assoc($q)) if (isset($val[$r['d']])) $val[$r['d']][$r['row_key']] = intval($r['amount']);
	if (false) { // айлық жоспар енгізу алынды: тек күндік толтыру
		$q = db::query("select row_key, amount from company_pnl where company_id = '$company' and `plan` = 1 and `day` = '$r_start'");
		while ($q && $r = mysqli_fetch_assoc($q)) $plan[$r['row_key']] = intval($r['amount']);
	}

	// «Запис қосу» терезесі үшін бүгінгі қолмен жазылған мәндер
	$today_vals = [];
	$q = db::query("select row_key, amount from company_pnl where company_id = '$company' and `plan` = 0 and `day` = '$today_bd'");
	while ($q && $r = mysqli_fetch_assoc($q)) $today_vals[$r['row_key']] = intval($r['amount']);
	$can_pop = $user_right['positions_id'] != 3 && $company;

	// Тек «Күн» көрінісінде толтырылады; Апта, Ай, Жыл — тек қарау мен анализ үшін
	$can_edit = $user_right['positions_id'] != 3 && $company && $mode != 'y' && $by != 'w'; // Күн/Апта/Ай: сұр ұяшыққа тікелей жазылады (Жылда — тек қарау)


	// site setting
	$menu_name = 'opiu';
	$page_name = 'ОПиУ';
	$css = ['stats', 'opiu'];
	$js = ['opiu', 'st_pick'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c st_page rnp_page" data-ajx-nav=".st_page">

		<div class="st_bar">
			<?=$mode_seg?>
			<div class="st_nav">
				<? if ($mode != 'f'): ?><a class="st_nav_b" href="<?=$prev_link?>"><i class="far fa-chevron-left"></i></a><? endif ?>
				<div class="st_nav_t"<? if ($mode != 'f'): ?> data-pick="1" data-mode="<?=$mode?>" data-d="<?=$r_start?>" data-today="<?=$today_bd?>" data-q="<?=$by == 'w' ? '&by=w' : ''?>"<? endif ?>><?=$title?></div>
				<? if ($mode != 'f'): ?><a class="st_nav_b" href="<?=$next_link?>"><i class="far fa-chevron-right"></i></a><? endif ?>
			</div>
			<div class="rnp_info">
				<button type="button" class="rnp_info_b" aria-label="Түсіндірме" title="Түсіндірме">i</button>
				<div class="rnp_info_p">
				<div class="rnp_info_t">Кестені қалай оқу керек</div>
				<span><i class="rnp_lg rnp_lg_auto"></i>базадан өзі толады</span>
				<? if ($mode != 'y'): ?>
					<span><i class="rnp_lg rnp_lg_in"></i>сұр ұяшыққа сол күннің шығынын тікелей жазасыз</span>
				<? else: ?>
					<span>Жылда тек қарау. Жазу үшін Күн, Апта не Ай</span>
				<? endif ?>
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
			'noplan' => true,
			'fact_label' => 'Барлығы',
			'single' => $single,
			'fill' => '',
			'pop' => $can_pop && $mode != 'f',
			'mode' => $mode,
			'today_bd' => $today_bd,
			'today_vals' => (object)$today_vals,
			'title' => $title,
			'rows' => $rows,
			'val' => $val,
			'plan' => (object)$plan,
			'edit' => $can_edit,
		], JSON_UNESCAPED_UNICODE)?>;
	</script>

<? include "../block/footer.php"; ?>
