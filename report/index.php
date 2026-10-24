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
    


    // 
    $onw['number'] = 0;
    $onw['total'] = 0;
    $onw['pay_qr'] = 0;
    $onw['pay_cash'] = 0;
    $onw['pay_delivery'] = 0;
    $onw['rask'] = 0;
    $onw['cash'] = 0;
    $onw['kaspi'] = 0;
    $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$branch'");

    $number = 0;

    


	// site setting
	$menu_name = 'report';
	$css = ['report'];
	$js = ['kassa'];
?>
<? include "../block/header.php"; ?>

    <div class="">
        <div class="bl_c">
            <div class="uc_u">
        
                <!-- <div class="form_im uc_us">
                    <input type="text" placeholder="Іздеуді қолданыңыз" class="form_im_txt cours_user_search_in" data-cours-id="<?=$cours_id?>" />
                    <i class="fal fa-search form_icon"></i>
                </div> -->
                <div class="uc_uh">
                    <div class="uc_uhn">
                        <div class="uc_uh_number">#</div>
                        <div class="uc_uh_name">Данный</div>
                        <div class="uc_uh_other">Заказ</div>
                        <div class="uc_uh_other">Общий</div>
                        <div class="uc_uh_other">Предоплата</div>
                        <div class="uc_uh_other">Наличный</div>
                        <div class="uc_uh_other">Зарплата</div>
                        <div class="uc_uh_other">Расход</div>
                        <div class="uc_uh_other">Кассаға қалғаны</div>
                        <div class="uc_uh_other">Наличный</div>
                        <div class="uc_uh_other">Каспи</div>
                        <div class="uc_uh_other">Итог</div>
                    </div>
                    <div class="uc_uh_cn"></div>
                </div>
                <div class="uc_uc">
                    <? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
                        <? $number++; ?>
                        <? $user_d = fun::user($staff_d['user_id']); ?>
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
                            $allorder['kassa'] = $allorder['pay_cash'] - $allorder['pay_delivery'] - @$report_сourier_d['expenses'];
                            $allorder['none'] = $allorder['kassa'] - @$report_сourier_d['cash'] - @$report_сourier_d['kaspi'];

                            $onw['number'] = $onw['number'] + mysqli_num_rows($orders);
                            $onw['total'] = $onw['total'] + $allorder['total'];
                            $onw['pay_qr'] = $onw['pay_qr'] + $allorder['pay_qr'];
                            $onw['pay_cash'] = $onw['pay_cash'] + $allorder['pay_cash'];
                            $onw['pay_delivery'] = $onw['pay_delivery'] + $allorder['pay_delivery'];
                        ?>

                        <div class="uc_ui">
                            <div class="uc_uil">
                                <div class="uc_ui_number"><?=$number?></div>
                                <div class="uc_uiln" href="/user/admin/users/item/?id=<?=$user_d['id']?>">
                                    <div class="uc_ui_icon lazy_img" data-src="/assets/uploads/users/<?=$user_d['img']?>"><?=($user_d['img']!=null?'':'<i class="fal fa-user"></i>')?></div>
                                    <div class="uc_uinu">
                                        <div class="uc_ui_name"><?=$user_d['name']?> <?=$user_d['surname']?></div>
                                        <div class="uc_ui_phone"><?=($user_d['phone'] != null?$user_d['phone']:$user_d['mail'])?></div>
                                    </div>
                                </div>
                                <div class="uc_uin_other"><?=mysqli_num_rows($orders)?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['total']?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['pay_qr']?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['pay_cash']?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['pay_delivery']?></div>
                                <div class="uc_uin_other fr_price"><?=@$report_сourier_d['expenses']?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['kassa']?></div>
                                <div class="uc_uin_other fr_price"><?=@$report_сourier_d['cash']?></div>
                                <div class="uc_uin_other fr_price"><?=@$report_сourier_d['qr']?></div>
                                <div class="uc_uin_other fr_price"><?=$allorder['none']?></div>
                            </div>
                            <div class="uc_uib sel_id" data-id="<?=$buy_d['id']?>">
                                <div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
                            </div>
                        </div>
                    <? endwhile ?>

                </div>
            </div>
        </div>
    </div>

<? include "../block/footer.php"; ?>