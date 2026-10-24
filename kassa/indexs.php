<? include "../config/core.php";

	// 
	// if (!$user_id) header('location: /');



    $cashbox = db::query("select * from report_1 where paid = 0 and branch_id = '$branch' order by id desc limit 1");
	if (mysqli_num_rows($cashbox)) {
		$cashbox_d = mysqli_fetch_assoc($cashbox);
		$cashbox_id = $cashbox_d['id'];
	} else {
		$cashbox_id = (mysqli_fetch_assoc(db::query("SELECT * FROM `report_1` order by id desc")))['id'] + 1;
		$ins = db::query("INSERT INTO `report_1`(`id`, `user_id`, `branch_id`) VALUES ('$cashbox_id', '$user_id', '$branch')");
	}
	$cashboxp = db::query("select * from report_сourier where report_id = '$cashbox_id' order by ins_dt asc");
    



    if (@$_GET['time']) {
        $time_sort = $_GET['time'];
        $start_cdate = date('Y-m-d 06:00:00', strtotime("$date $time_sort day"));
        $end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
    }




    $allor = db::query("select *, SUM(total) as total, SUM(pay_qr) as pay_qr, SUM(pay_cash) as pay_cash, SUM(pay_delivery) as pay_delivery, COUNT(*) as paid from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and branch_id = '$branch' and сourier_id is not null GROUP BY paid order by paid desc");
    $allor_d = mysqli_fetch_assoc($allor);


    // $orders = db::query("select *, COUNT(*) AS total, COUNT(*) AS pay_qr, COUNT(*) AS pay_cash, COUNT(*) AS pay_delivery, COUNT(*) AS сourier_id from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and branch_id = '$branch' GROUP BY сourier_id order by сourier_id asc");
    $orders = db::query("select *, SUM(total) as total, SUM(pay_qr) as pay_qr, SUM(pay_cash) as pay_cash, SUM(pay_delivery) as pay_delivery, COUNT(*) as paid from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and branch_id = '$branch' and сourier_id is not null GROUP BY сourier_id order by paid desc");
    // $cashboxp = db::query("select price, product_id, COUNT(*) AS quantity from retail_orders_products where ins_dt BETWEEN '$start_cdate' and '$end_cdate' GROUP BY product_id order by quantity desc");




    $onw['number'] = 0;
    $onw['total'] = 0;
    $onw['pay_qr'] = 0;
    $onw['pay_cash'] = 0;
    $onw['pay_delivery'] = 0;
    $onw['rask'] = 0;
    $onw['cash'] = 0;
    $onw['kaspi'] = 0;
    // $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$branch'");




	// site setting
	$menu_name = 'kassa';
	$css = ['kassa'];
	$js = ['kassa'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c">

        <div class="">
            <!-- <div class="uc_ui uc_ui69">
                <? if (!$user_right['branch_id']): ?>
                    <div class="uc_uin_other">
                        <select name="status" class="on_sort_branch" data-order-id="<?=$buy_d['id']?>" >
                            <option data-id="" value="" data-val="1" <?=($branch == 1?'selected':'')?>>Банзай</option>
                            <option data-id="" value="" data-val="2" <?=($branch == 2?'selected':'')?>>Мастер</option>
                        </select>
                    </div>
                <? endif ?>
            </div> -->
            <div class="uc_ui uc_ui69">
				<div class="uc_uin_other">
					<select name="status" class="on_sort_time" data-order-id="<?=$buy_d['id']?>" >
						<option data-id="" value="" data-val="0" <?=(@$time_sort == 0?'selected':'')?>>Бүгін (<?=date('d', strtotime("$date"))?>)</option>
						<option data-id="" value="" data-val="-1" <?=(@$time_sort == -1?'selected':'')?>>Кеше (<?=date('d', strtotime("$date -1 day"))?>)</option>
						<option data-id="" value="" data-val="-2" <?=(@$time_sort == -2?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -2 day"))?>)</option>
						<option data-id="" value="" data-val="-3" <?=(@$time_sort == -3?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -3 day"))?>)</option>
						<option data-id="" value="" data-val="-4" <?=(@$time_sort == -4?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -4 day"))?>)</option>
						<option data-id="" value="" data-val="-5" <?=(@$time_sort == -5?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -5 day"))?>)</option>
					</select>
				</div>
			</div>
        </div>

    </div>

	<div class="bl_c">

			<div class="table4">

				<table>

                    <tbody>
                        <? while ($orders_d = mysqli_fetch_assoc($orders)): ?>
                            <? $сourier_id = $orders_d['сourier_id']; ?>
                            <? $users_d = fun::user($orders_d['сourier_id']); ?>
                            <? $report_сourier_d = fun::report_сourier($cashbox_id, $сourier_id); ?>
                            
                            <? $na_kass = $orders_d['pay_cash'] - ($orders_d['pay_delivery'] + (500 * $orders_d['paid']))?>

                            <tr>
                                <td><?=$users_d['name']?></td>
                                <td><?=$orders_d['paid']?></td>
                                <td class="fr_price"><?=$orders_d['total']?></td>
                                <td class="fr_price"><?=$orders_d['pay_qr']?></td>
                                <td class="fr_price"><?=$orders_d['pay_cash']?></td>
                                <td class="fr_price"><?=$orders_d['pay_delivery'] + (500 * $orders_d['paid'])?></td>
                                <td class="fr_price btype_start" data-rask="0" data-start="<?=$na_kass?>"><?=$na_kass?></td>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$сourier_id?>" class="form_txt fr_price btype_rask" placeholder="0" data-val="<?=(@$report_сourier_d['expenses']?$report_сourier_d['expenses']:0)?>" value="<?=@$report_сourier_d['expenses']?>">
                                </td>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$сourier_id?>" class="form_txt fr_price btype_cash" placeholder="0" data-val="<?=(@$report_сourier_d['cash']?$report_сourier_d['cash']:0)?>" value="<?=@$report_сourier_d['cash']?>">
                                </td>
                                <td class="fr_price btype_kaspi"><?=$allorder['pay_cash']- $allorder['pay_delivery'] - @$report_сourier_d['expenses'] - @$report_сourier_d['cash']?></td>
                                <td class="fr_price btype_kaspi"><div class="btn btn_dd_cm"><i class="far fa-check-circle"></i></div></td>
                            </tr>

                            <? 
                                // $onw['rask'] = $onw['rask'] + @$report_сourier_d['expenses'];
                                // $onw['cash'] = $onw['cash'] + @$report_сourier_d['cash'];
                                // $onw['kaspi'] = $onw['kaspi'] + ($allorder['pay_cash'] - ($allorder['pay_delivery'] + @$report_сourier_d['expenses'] + @$report_сourier_d['cash']));
                            ?>

                        <? endwhile ?>
                    
                    </tbody>

                    <thead>
                        <tr>
                            <td></td>
                            <td>Саны</td>
                            <td>Общий</td>
                            <td>Предоплата</td>
                            <td>Остаток</td>
                            <td>Зарплата</td>
                            <td>На кассу</td>
                            <td>Расходы</td>
                            <td>Наличный</td>
                            <td>Каспи</td>
                            <td>Статус</td>
                        </tr>
                        <tr>
                            <td>Барлыгы</td>
                            <td><?=$allor_d['paid']?></td>
                            <td class="fr_price"><?=$allor_d['total']?></td>
                            <td class="fr_price"><?=$allor_d['pay_qr']?></td>
                            <td class="fr_price"><?=$allor_d['pay_cash']?></td>
                            <td class="fr_price"><?=$allor_d['pay_delivery'] + (500 * $orders_d['paid'])?></td>
                            <td class="fr_price"><?=$allor_d['pay_cash'] - ($allor_d['pay_delivery'] + (500 * $orders_d['paid']))?></td>
                            <td class="fr_price"><?=$onw['rask']?></td>
                            <td class="fr_price"><?=$onw['cash']?></td>
                            <td class="fr_price"><?=$onw['kaspi']?></td>
                            <!-- <td><div class="btn">Отчетты сақтау</div></td> -->
                        </tr>
                    </thead>
                </table>

			</div>

            <br><br><br>
        
	</div>

<? include "../block/footer.php"; ?>