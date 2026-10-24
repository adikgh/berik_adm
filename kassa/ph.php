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
    


    $onw['number'] = 0;
    $onw['total'] = 0;
    $onw['pay_qr'] = 0;
    $onw['pay_cash'] = 0;
    $onw['pay_delivery'] = 0;
    $onw['rask'] = 0;
    $onw['cash'] = 0;
    $onw['kaspi'] = 0;



	// site setting
	$menu_name = 'kassa';
	$css = ['kassa_ph'];
	$js = ['kassa'];
?>
<? include "../block/header.php"; ?>


    <div class="bl_c">

        <div class="">
            <div class="uc_ui uc_ui69">
                <? if (!$user_right['branch_id']): ?>
                    <div class="uc_uin_other">
                        <select name="status" class="on_sort_branch" data-order-id="<?=$buy_d['id']?>" >
                            <option data-id="" value="" data-val="1" <?=($branch == 1?'selected':'')?>>Банзай</option>
                            <option data-id="" value="" data-val="2" <?=($branch == 2?'selected':'')?>>Мастер</option>
                        </select>
                    </div>
                <? endif ?>
                <!-- <div class="uc_uin_other">
                    <select name="status" class="on_sort_time" data-order-id="<?=$buy_d['id']?>" >
                        <option data-id="" value="" data-val="0" <?=(@$time_sort == 0?'selected':'')?>>Бүгін (<?=date('d', strtotime("$date"))?>)</option>
                        <option data-id="" value="" data-val="-1" <?=(@$time_sort == -1?'selected':'')?>>Кеше (<?=date('d', strtotime("$date -1 day"))?>)</option>
                        <option data-id="" value="" data-val="-2" <?=(@$time_sort == -2?'selected':'')?>>Алдыңғы күні (<?=date('d', strtotime("$date -2 day"))?>)</option>
                    </select>
                </div> -->
                
                <!-- <div class="uc_uin_other">
                    <select name="staff" class="on_sort_staff" data-order-id="<?=$buy_d['id']?>" >
                        <option data-id="" value="">Барлығы</option>
                        <option data-id="soboi" <?=(@$_GET['staff'] == 'soboi'?'selected':'')?> value="">Собой</option>
                        <option data-id="off" <?=(@$_GET['staff'] == 'off'?'selected':'')?> value="">Таңдалмаған</option>
                        <? $staff = db::query("select * from user_staff where positions_id = 6"); ?>
                        <? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
                            <? $staff_user_d = fun::user($staff_d['user_id']); ?>
                            <option data-id="<?=$staff_d['user_id']?>" <?=(@$_GET['staff'] == $staff_d['user_id']?'selected':'')?> value=""><?=$staff_user_d['name']?></option>
                        <? endwhile ?>
                    </select>
                </div> -->
            </div>
        </div>

    </div>

    <div class="flex_clm_rev">
        <div class="bl_c">
            <div class="uc_u">

                <? $staff = db::query("select * from user_staff where positions_id = 6"); ?>
                <? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
                    <? $staff_user_d = fun::user($staff_d['user_id']); ?>
                    <? $staff_id = $staff_d['user_id']; ?>
                    <? $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and сourier_id  = '$staff_id' and branch_id = '$branch' order by number desc"); ?>
                    <? $report_сourier_d = fun::report_сourier($cashbox_id, $staff_id); ?>

                    <?
                        $allorder['total'] = 0;
                        $allorder['pay_qr'] = 0;
                        $allorder['pay_delivery'] = 0;
                        $allorder['pay_cash'] = 0;
                        
                        while ($buy_d = mysqli_fetch_assoc($orders)){
                            $allorder['total'] = $allorder['total'] + $buy_d['total'];
                            $allorder['pay_qr'] = $allorder['pay_qr'] + $buy_d['pay_qr'];
                            $allorder['pay_cash'] = $allorder['pay_cash'] + $buy_d['pay_cash'];
                            $allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
                        }

                        $onw['number'] = $onw['number'] + mysqli_num_rows($orders);
                        $onw['total'] = $onw['total'] + $allorder['total'];
                        $onw['pay_qr'] = $onw['pay_qr'] + $allorder['pay_qr'];
                        $onw['pay_cash'] = $onw['pay_cash'] + $allorder['pay_cash'];
                        $onw['pay_delivery'] = $onw['pay_delivery'] + $allorder['pay_delivery'];

                        $onw['rask'] = $onw['rask'] + @$report_сourier_d['expenses'];
                        $onw['cash'] = $onw['cash'] + @$report_сourier_d['cash'];
                        $onw['kaspi'] = $onw['kaspi'] + ($allorder['pay_cash'] - ($allorder['pay_delivery'] + @$report_сourier_d['expenses'] + @$report_сourier_d['cash']));
                    ?>

                            
                    <!-- <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_rask" placeholder="0" data-val="" value="<?=@$report_сourier_d['expenses']?>">
                    <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_cash" placeholder="0" data-val="0" value="<?=@$report_сourier_d['cash']?>">
                    <td class="fr_price btype_kaspi"><?=$allorder['pay_cash']- $allorder['pay_delivery'] - @$report_сourier_d['expenses'] - @$report_сourier_d['cash']?></td> -->
                    

                    <div class="uc_ui">
                        <div class="uc_uil2" >
                            <div class="uc_uil2_top">
                                <div class="uc_uil2_date">
                                    <div class="uc_uil2_date1"><?=$staff_user_d['name']?></div>
                                </div>
                            </div>
                            <div class="uc_uil2_raz">
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Общий заказ:</div>
                                    <div class="uc_uil2_mi2 fr_number3"><?=mysqli_num_rows($orders)?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Общий сумма заказов:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=$allorder['total']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Предоплата:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=$allorder['pay_qr']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Остаток:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=$allorder['pay_cash']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Зарплата:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=$allorder['pay_delivery']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">На кассу:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=$allorder['pay_cash'] - $allorder['pay_delivery']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Расходы:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=@$report_сourier_d['expenses']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Наличный:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=@$report_сourier_d['cash']?></div>
                                </div>
                                <div class="uc_uil2_mi">
                                    <div class="uc_uil2_mi1">Каспи:</div>
                                    <div class="uc_uil2_mi2 fr_price"><?=@$report_сourier_d['kaspi']?></div>
                                </div>
                            </div>
                            <div class="uc_uil2_raz">
                                <div class="uc_uil2_mib">
                                    <div class="btn btn_phone read_one" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>">Жазу</div>
                                    <div class="btn btn_whatsapp">Тапсырды</div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                <? endwhile ?>
            </div>
        </div>

        <div class="bl_c">
            <div class="uc_u">
                <div class="uc_ui">
                    <div class="uc_uil2" >
                        <div class="uc_uil2_top">
                            <div class="uc_uil2_date">
                                <div class="uc_uil2_date1">Барлыгы</div>
                            </div>
                        </div>
                        <div class="uc_uil2_raz">
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Общий заказ:</div>
                                <div class="uc_uil2_mi2 fr_number3"><?=$onw['number']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Общий сумма заказов:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['total']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Предоплата:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['pay_qr']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Остаток:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['pay_cash']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Зарплата:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['pay_delivery']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">На кассу:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['pay_cash'] - $onw['pay_delivery']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Расходы:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['rask']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Наличный:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['cash']?></div>
                            </div>
                            <div class="uc_uil2_mi">
                                <div class="uc_uil2_mi1">Каспи:</div>
                                <div class="uc_uil2_mi2 fr_price"><?=$onw['kaspi']?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<? include "../block/footer.php"; ?>

    <!--  -->
	<div class="pop_bl pop_bl2 read_block">
		<div class="pop_bl_a read_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4>Сақтау</h4>
				<div class="btn btn_dd read_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="form_c">

                    <div class="form_im">
                        <div class="form_span">Расход:</div>
                        <input type="tel" class="form_txt fr_price btype_rask" placeholder="0" value="" data-val="">
                        <i class="fal fa-tenge form_icon"></i>
                    </div>

                    <br><br>

                    <div class="">
                        <div class="form_im">
                            <div class="form_span">Наличный:</div>
                            <input type="tel" class="form_txt fr_price btype_cash" placeholder="0" value="" data-val="">
                            <i class="fal fa-tenge form_icon"></i>
                        </div>
                        <div class="form_im">
                            <div class="form_span">Каспи:</div>
                            <input type="tel" class="form_txt fr_price btype_kaspi" placeholder="0" value="" data-val="">
                            <i class="fal fa-tenge form_icon"></i>
                        </div>
                    </div>

					<div class="form_im">
						<div class="btn read_pay2" data-id="" data-user-id="">Сақтау</div>
					</div>
				</div>

			</div>
		</div>
	</div>
