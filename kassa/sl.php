<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');

   	$type = @$_GET['type'];

	if (@$_GET['time']) {
		$time_sort = $_GET['time'];
		$start_cdate = date('Y-m-d 06:00:00', strtotime("$date $time_sort day"));
		$end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
	}


	if (@$_GET['status'] && @$_GET['staff']) {
		$status = $_GET['status'];
		$staff = $_GET['staff'];
		if ($staff == 'off') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and сourier_id is null and company_id = '$company' order by number desc");
		elseif ($staff == 'soboi') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and order_type = 2 and company_id = '$company' order by number desc");
		else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and сourier_id  = '$staff'  and company_id = '$company' order by number desc");
	} elseif (@$_GET['status']) {
		$status = $_GET['status'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status'  and company_id = '$company' order by number desc");
	} elseif (@$_GET['staff']) {
		$staff = $_GET['staff'];
		if ($staff == 'off') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_type = 1 and сourier_id is null and company_id = '$company' order by number desc");
		elseif ($staff == 'soboi') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_type = 2 and company_id = '$company' order by number desc");
		else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and сourier_id  = '$staff'  and company_id = '$company' order by number desc");
	} else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate'  and company_id = '$company' order by number desc");


	$allorder['total'] = 0;
	$allorder['pay_qr'] = 0;
	$allorder['pay_cash'] = 0;
	$allorder['pay_delivery'] = 0;


	// site setting
	$menu_name = 'orders';
	$pod_menu_name = 'main';
	$css = ['orders'];
	$js = ['orders'];
?>
<? include "../block/header.php"; ?>

	<div class="flex_clm_rev">

		<div class="bl_c">

			<div class="uc_u">

				<? if ($orders != ''): ?>
					<? if (mysqli_num_rows($orders) != 0): ?>
						<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
							<? $order_sts = fun::order_sts($buy_d['order_status']); ?>
							<?	
								if ($buy_d['order_status'] != 5 && $buy_d['order_status'] != 6) {
									$allorder['total'] = $allorder['total'] + $buy_d['total'];
									$allorder['pay_qr'] = $allorder['pay_qr'] + $buy_d['pay_qr'];
									$allorder['pay_cash'] = $allorder['pay_cash'] + $buy_d['pay_cash'];
									$allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
								}
							?>

						<? endwhile ?>
					<? else: ?> <div class="ds_nr"><i class="fal fa-none"></i><p>демалыс</p></div> <? endif ?>
				<? else: ?> <div class="ds_nr"><i class="fal fa-none"></i><p>демалыс</p></div> <? endif ?>

			</div>

		</div>

		<div class="bl_c">

			<div class="">
				<div class="uc_ui uc_ui69">
					<div class="uc_uin_other">
						<select name="status" class="on_sort_time" data-order-id="<?=$buy_d['id']?>" >
							<option data-id="" value="" data-val="0" <?=(@$time_sort == 0?'selected':'')?>>Бүгін (<?=date('d', strtotime("$date"))?>)</option>
							<option data-id="" value="" data-val="-1" <?=(@$time_sort == -1?'selected':'')?>>Кеше (<?=date('d', strtotime("$date -1 day"))?>)</option>
							<option data-id="" value="" data-val="-2" <?=(@$time_sort == -2?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -2 day"))?>)</option>
						</select>
					</div>
					<div class="uc_uin_other">
						<select name="status" class="on_sort_status" data-order-id="<?=$buy_d['id']?>" >
							<option data-id="" value="">Барлығы</option>
							<? $orders_status = db::query("select * from retail_orders_status"); ?>
							<? while ($orders_status_d = mysqli_fetch_assoc($orders_status)): ?>
								<option data-id="<?=$orders_status_d['id']?>" <?=(@$_GET['status'] == $orders_status_d['id']?'selected':'')?> value="" ><?=$orders_status_d['name_kz']?></option>
							<? endwhile ?>
						</select>
					</div>
					<div class="uc_uin_other">
						<select name="staff" class="on_sort_staff" data-order-id="<?=$buy_d['id']?>" >
							<option data-id="" value="">Барлығы</option>
							<option data-id="soboi" <?=(@$_GET['staff'] == 'soboi'?'selected':'')?> value="">Собой</option>
							<option data-id="off" <?=(@$_GET['staff'] == 'off'?'selected':'')?> value="">Таңдалмаған</option>
							<? $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$company'"); ?>
							<? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
								<? $staff_user_d = fun::user($staff_d['user_id']); ?>
								<option data-id="<?=$staff_d['user_id']?>" <?=(@$_GET['staff'] == $staff_d['user_id']?'selected':'')?> value=""><?=$staff_user_d['name']?></option>
							<? endwhile ?>
						</select>
					</div>
				</div>
			</div>
            <br><br>
            <div class="">
				<div class="uc_ui uc_ui69">
					<div class="uc_uin_other">Жалпы: <?=$allorder['total']?> тг</div>
					<div class="uc_uin_other">QR: <?=$allorder['pay_qr']?> тг</div>
					<div class="uc_uin_other">Нал: <?=$allorder['pay_cash']?> тг</div>
				</div>
			</div>

		</div>

	</div>

<? include "../block/footer.php"; ?>