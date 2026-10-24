<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');



   	$type = @$_GET['type'];


	if (@$_GET['status'] && @$_GET['staff']) {
		$status = $_GET['status'];
		$staff = $_GET['staff'];
		if ($staff == 'off') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and сourier_id is null and branch_id = '$branch' order by number desc");
		elseif ($staff == 'soboi') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and order_type = 2 and branch_id = '$branch' order by number desc");
		else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status' and сourier_id  = '$staff'  and branch_id = '$branch' order by number desc");
	} elseif (@$_GET['status']) {
		$status = $_GET['status'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_status = '$status'  and branch_id = '$branch' order by number desc");
	} elseif (@$_GET['staff']) {
		$staff = $_GET['staff'];
		if ($staff == 'off') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_type = 1 and сourier_id is null and branch_id = '$branch' order by number desc");
		elseif ($staff == 'soboi') $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and order_type = 2 and branch_id = '$branch' order by number desc");
		else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and сourier_id  = '$staff'  and branch_id = '$branch' order by number desc");
	} else $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate'  and branch_id = '$branch' order by number desc");


	$allorder['total'] = 0;
	$allorder['pay_qr'] = 0;
	$allorder['pay_delivery'] = 0;


	// site setting
	$menu_name = 'orders';
	$pod_menu_name = 'main';
	$css = ['orders2'];
	$js = ['orders'];
?>
<? include "../block/header.php"; ?>

	<div class="">
		<div class="bl_c">

					<!-- <div class="uc_us">
						<div class="form_im uc_usn">
							<input type="text" placeholder="Поиск" class="order_search_in">
							<i class="fal fa-search form_icon"></i>
						</div>
					</div> -->

			<div class="uc_u new_all_up">
				<div class="uc_u2">

					<div class="uc_uh">
						<div class="uc_uh2">
							<div class="uc_uh_number">#</div>
							<div class="uc_ui_dd">
								<div class="uc_uh_cn"></div>
								<div class="uc_uh_cn"></div>
							</div>
							<div class="uc_uiln">
								<div class="uc_uh_other">Статус</div>
								<div class="uc_uh_other">Уақыты</div>
								<div class="uc_uh_other">Номер</div>
								<div class="uc_uh_other">Адрес</div>
								<div class="uc_uh_other">Курьер / Собой</div>
								<div class="uc_uh_other">Общий сумма</div>
								<div class="uc_uh_other">Предоплата (QR)</div>
								<div class="uc_uh_other">Наличный</div>
							</div>
						</div>
					</div>

					<div class="uc_uc">

						<? if ($orders != ''): ?>
						<? if (mysqli_num_rows($orders) != 0): ?>
							<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
								<? if ($buy_d['сourier_id']) $сourier_d = fun::user($buy_d['сourier_id']); ?>

								<div class="uc_ui " >
									<div class="uc_uil2" href="list.php?id=<?=$buy_d['id'].($type=='return'?'&type=return':'')?>">
										<div class="uc_ui_number"><?=$buy_d['number']?></div>
										<div class="uc_ui_dd">
											<a class="uc_uin_cn on_ubd " data-id="<?=$buy_d['id']?>" href="/cashbox/?id=<?=$buy_d['id']?>&type=ubd"><i class="far fa-pen"></i></a>
											<div class="uc_uin_cn on_print " data-id="<?=$buy_d['id']?>"><i class="far fa-print"></i></div>
										</div>
										<div class="uc_uiln">
											<div class="uc_uin_other">
												<select name="" id="" class="on_status" data-order-id="<?=$buy_d['id']?>" >
													<? $orders_status = db::query("select * from retail_orders_status"); ?>
													<? while ($orders_status_d = mysqli_fetch_assoc($orders_status)): ?>
														<option data-id="<?=$orders_status_d['id']?>" <?=($buy_d['order_status'] == $orders_status_d['id']?'selected':'')?> value="" ><?=$orders_status_d['name_kz']?></option>
													<? endwhile ?>
												</select>
											</div>
											<div class="uc_uin_other"><?=date("H:i", strtotime($buy_d['ins_dt']))?></div>
											<div class="uc_uin_other on_info" data-id="<?=$buy_d['id']?>"><?=$buy_d['phone']?></div>
											<div class="uc_uin_other"><?=$buy_d['address']?></div>

											<div class="uc_uin_other">
												<? if ($buy_d['сourier_id']) :?> <?=$сourier_d['name']?>
												<? else: ?>
													<select name="" id="" class="on_stype" data-order-id="<?=$buy_d['id']?>" >
														<option value="" <?=($buy_d['order_type']==1?'selected':'')?>>Курьер</option>
														<option value="" <?=($buy_d['order_type']==2?'selected':'')?>>Собой</option>
													</select>
												<? endif ?>
											</div>
											<div class="uc_uin_other fr_price"><?=$buy_d['total']?></div>
											<div class="uc_uin_other fr_price"><?=$buy_d['pay_qr']?> </div>
											<div class="uc_uin_other fr_price"><?=$buy_d['total'] - $buy_d['pay_qr']?></div>
										</div>
									</div>
								</div>

								<? 
									$allorder['total'] = $allorder['total'] + $buy_d['total'];
									$allorder['pay_qr'] = $allorder['pay_qr'] + $buy_d['pay_qr'];
									$allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
								?>

							<? endwhile ?>
						<? else: ?> <div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div> <? endif ?>
						<? else: ?> div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div> <? endif ?>

					</div>



				</div>
			</div>

		</div>
	</div>

<? include "../block/footer.php"; ?>

	
	<!--  -->
	<div class="pop_bl pop_bl2 on_info_block">
		<div class="pop_bl_a on_info_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4>Инфо</h4>
				<div class="btn btn_dd on_info_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="osigoi">

				</div>
			</div>
		</div>
	</div>