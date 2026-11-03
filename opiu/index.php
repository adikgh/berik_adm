<? include "../config/core.php";
	include "../team/pay_lib.php";
	include "../waybill/lib.php"; // кезең, сілтеме, күн форматы

	// ОПИУ (кіріс / шығыс): тек басшылық (1 құрушы, 2 директор)
	if (!$user_id) { header('location: /'); exit(); }
	if (!zp::can()) { header('location: /'); exit(); }
	zp::install();
	$cmp = (int)$company;

	$per = wb::period();
	$pf = $per['from']; $pt = $per['to'];
	$p_names = ['month' => 'Бұл ай', 'prev' => 'Өткен ай', '3m' => '3 ай', 'year' => 'Бұл жыл'];

	// жұмыс күні 06:00-ден басталады
	$start = date('Y-m-d 06:00:00', strtotime($pf));
	$end = date('Y-m-d 06:00:00', strtotime("$pt +1 day"));

	// ---------- Кіріс: сату (Статистика бетімен бірдей) ----------
	$base = "company_id = '$cmp' and number is not null and ins_dt >= '$start' and ins_dt < '$end'";
	$sale = mysqli_fetch_assoc(db::query("select COUNT(*) cnt, COALESCE(SUM(total), 0) total from retail_orders where $base and order_status in (1, 8, 2, 11, 3, 9, 4)"));
	$kept = mysqli_fetch_assoc(db::query("select COALESCE(SUM(kept_sum), 0) s from retail_orders where $base and order_status in (6, 10)"));
	$income = (int)$sale['total'] + (int)$kept['s'];

	// ---------- Шығыс: шикізат (қабылданған накладной) ----------
	$raw = 0;
	if (mysqli_num_rows(db::query("SHOW TABLES LIKE 'waybill'"))) {
		$raw = (int)mysqli_fetch_assoc(db::query("select COALESCE(SUM(total), 0) s from waybill where company_id = '$cmp' and status = 1 and doc_date between '$pf' and '$pt'"))['s'];
	}

	// ---------- Шығыс: жалақы ----------
	$pay = zp::paid_by_group($cmp, $pf, $pt);
	$courier_auto = 0; $courier_orders = 0;
	foreach (zp::courier_earn($cmp, $pf, $pt) as $e) { $courier_auto += $e['sum']; $courier_orders += $e['orders']; }
	$salary = array_sum($pay) + $courier_auto;

	$expense = $raw + $salary;
	$profit = $income - $expense;
	$pc = function($v) use ($income) { return $income ? round($v / $income * 100, 1).'%' : ''; };

	$menu_name = 'team';
	$page_name = 'ОПИУ';
	$page_key = 'P&L';
	$tm_tab = 'opiu';
	$css = ['team', 'waybill', 'pay'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c">
	<? include "../team/tabs.php"; ?>
</div>

<div class="bl_c wb_page zp_page">

	<!-- Кезең -->
	<div class="wb_bar">
		<div class="wb_tabs">
			<? foreach ($p_names as $k => $v): ?>
				<a class="wb_tab <?=($per['p'] == $k ? 'wb_tab_act' : '')?>" href="<?=wb::url('/opiu/', ['p' => $k, 'from' => null, 'to' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="wb_range" method="get" action="/opiu/">
			<input type="date" name="from" value="<?=($per['p'] == 'custom' ? $pf : '')?>" class="wb_inp">
			<span>—</span>
			<input type="date" name="to" value="<?=($per['p'] == 'custom' ? $pt : '')?>" class="wb_inp">
			<button class="wb_btn" type="submit"><i class="fal fa-check"></i></button>
		</form>
	</div>
	<div class="wb_sub"><?=wb::dt($pf)?> — <?=wb::dt($pt)?></div>

	<!-- Карточкалар -->
	<div class="wb_cards">
		<div class="wb_card">
			<div class="wb_card_h">Кіріс</div>
			<div class="wb_card_v"><?=zp::money($income)?></div>
			<div class="wb_card_s"><?=(int)$sale['cnt']?> тапсырыс</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Шығыс</div>
			<div class="wb_card_v wb_c_red"><?=zp::money($expense)?></div>
			<div class="wb_card_s">шикізат + жалақы</div>
		</div>
		<a class="wb_card wb_card_link" href="<?=wb::url('/team/pay.php')?>">
			<div class="wb_card_h">Жалақы</div>
			<div class="wb_card_v"><?=zp::money($salary)?></div>
			<div class="wb_card_s">кірістің <?=($pc($salary) ?: '0%')?></div>
		</a>
		<div class="wb_card">
			<div class="wb_card_h">Таза пайда</div>
			<div class="wb_card_v <?=($profit < 0 ? 'wb_c_red' : 'wb_c_green')?>"><?=zp::money($profit)?></div>
			<div class="wb_card_s">маржа <?=($pc($profit) ?: '—')?></div>
		</div>
	</div>

	<!-- Кесте -->
	<div class="wb_box wb_table op_tbl">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_name">Бап</div>
			<div class="wb_td wb_td_v">Сома</div>
			<div class="wb_td wb_td_pc">Кірістен</div>
		</div>

		<div class="wb_tr op_h">
			<div class="wb_td wb_td_name">Кіріс</div>
			<div class="wb_td wb_td_v"><?=zp::money($income)?></div>
			<div class="wb_td wb_td_pc">100%</div>
		</div>
		<div class="wb_tr op_sub">
			<div class="wb_td wb_td_name">Сату (<?=(int)$sale['cnt']?> тапсырыс)</div>
			<div class="wb_td wb_td_v"><?=zp::money($sale['total'])?></div>
			<div class="wb_td wb_td_pc"><?=$pc($sale['total'])?></div>
		</div>
		<? if ((int)$kept['s']): ?>
			<div class="wb_tr op_sub">
				<div class="wb_td wb_td_name">Отказтан қалған ақша</div>
				<div class="wb_td wb_td_v"><?=zp::money($kept['s'])?></div>
				<div class="wb_td wb_td_pc"><?=$pc($kept['s'])?></div>
			</div>
		<? endif ?>

		<div class="wb_tr op_h">
			<div class="wb_td wb_td_name">Шығыс</div>
			<div class="wb_td wb_td_v wb_c_red">− <?=zp::money($expense)?></div>
			<div class="wb_td wb_td_pc"><?=$pc($expense)?></div>
		</div>
		<a class="wb_tr op_sub" href="<?=wb::url('/waybill/', ['st' => 'ok'])?>">
			<div class="wb_td wb_td_name">Шикізат (қабылданған накладной)</div>
			<div class="wb_td wb_td_v"><?=zp::money($raw)?></div>
			<div class="wb_td wb_td_pc"><?=$pc($raw)?></div>
		</a>
		<a class="wb_tr op_sub" href="<?=wb::url('/team/pay.php')?>">
			<div class="wb_td wb_td_name">Жалақы</div>
			<div class="wb_td wb_td_v"><?=zp::money($salary)?></div>
			<div class="wb_td wb_td_pc"><?=$pc($salary)?></div>
		</a>
		<div class="wb_tr op_sub2">
			<div class="wb_td wb_td_name">Операторлар</div>
			<div class="wb_td wb_td_v"><?=zp::money($pay['oper'])?></div>
			<div class="wb_td wb_td_pc"><?=$pc($pay['oper'])?></div>
		</div>
		<div class="wb_tr op_sub2">
			<div class="wb_td wb_td_name">Аспазшылар</div>
			<div class="wb_td wb_td_v"><?=zp::money($pay['cook'])?></div>
			<div class="wb_td wb_td_pc"><?=$pc($pay['cook'])?></div>
		</div>
		<div class="wb_tr op_sub2">
			<div class="wb_td wb_td_name">Курьерлер (<?=$courier_orders?> жеткізу<?=($pay['courier'] ? ' + қосымша '.zp::money($pay['courier']) : '')?>)</div>
			<div class="wb_td wb_td_v"><?=zp::money($courier_auto + $pay['courier'])?></div>
			<div class="wb_td wb_td_pc"><?=$pc($courier_auto + $pay['courier'])?></div>
		</div>
		<? if ($pay['other']): ?>
			<div class="wb_tr op_sub2">
				<div class="wb_td wb_td_name">Басқа жұмысшылар</div>
				<div class="wb_td wb_td_v"><?=zp::money($pay['other'])?></div>
				<div class="wb_td wb_td_pc"><?=$pc($pay['other'])?></div>
			</div>
		<? endif ?>

		<div class="wb_tr op_tot <?=($profit < 0 ? 'op_tot_neg' : '')?>">
			<div class="wb_td wb_td_name">Таза пайда</div>
			<div class="wb_td wb_td_v <?=($profit < 0 ? 'wb_c_red' : 'wb_c_green')?>"><?=zp::money($profit)?></div>
			<div class="wb_td wb_td_pc"><?=$pc($profit)?></div>
		</div>
	</div>

</div>

<? include "../block/footer.php"; ?>
