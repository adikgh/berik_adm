<? include "../config/core.php";

   if (!$user_id) header('location: /');


   	$type = @$_GET['type'];

	// $start_cdate = date('Y-m-d 06:00:00', strtotime("20.01.2025"));
	// $end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));

    if (@$_GET['time']) {
		$time_sort = $_GET['time'];
		$start_cdate = date('Y-m-d 06:00:00', strtotime("$time_sort"));
		$end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
	}


    // 
	$oprs = db::query("select *, phone, COUNT(*) AS paid, SUM(total) AS total from retail_orders where company_id = '$company' and phone != '' GROUP BY phone order by paid desc limit 100");
    $number_p1 = 0;


	// site setting
	$menu_name = 'opiu';
	$page_name = 'Есеп - ОПиУ (кіріс/шығыс)';
	$site_set['view_dt'] = 1;
	$css = ['order_product'];
	$js = ['order_product'];
?>
<? include "../block/header.php"; ?>

	<div class="">
        <div class="bl_c">
            <div class="uc_u">
        
                <div class="uc_uh">
                    <div class="uc_uhn">
                        <div class="uc_uh_number">#</div>
                        <div class="uc_uh_name">Номер</div>
                        <div class="uc_uh_other">Продажа</div>
                        <div class="uc_uh_other">Общый сумма</div>
                        <div class="uc_uh_other">Адрес</div>
                    </div>
                </div>
                <div class="uc_uc">

                    <? while ($cashboxp_d = mysqli_fetch_assoc($oprs)): ?>
                        <? $number_p1++; ?>

                        <div class="uc_ui">
                            <div class="uc_uil">
                                <div class="uc_ui_number"><?=$number_p1?></div>
                                <div class="uc_uiln" >
                                    <div class="uc_uinu">
                                        <div class="uc_ui_name fr_phone"><?=$cashboxp_d['phone']?></div>
                                    </div>
                                </div>
                                <div class="uc_uin_other "><?=$cashboxp_d['paid']?> шт</div>
                                <div class="uc_uin_other fr_price"><?=$cashboxp_d['total']?> </div>
                                <div class="uc_uin_other"><?=$cashboxp_d['address']?></div>
                            </div>
                        </div>
                    <? endwhile ?>

                </div>
            </div>
        </div>
    </div>

<? include "../block/footer.php"; ?>