<? include "../config/core.php";

   if (!$user_id) header('location: /');


   	$type = @$_GET['type'];

	// $start_cdate = date('Y-m-d 06:00:00', strtotime("20.01.2025"));
	// $end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));

    // кезең: күн / ай / жыл (?time-ды core.php өңдейді)
    include "period.php";


    // 
	$oprs = db::query("select user_id, COUNT(*) AS paid from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and company_id = '$company' GROUP BY user_id order by paid desc");
    $number_p1 = 0;


	// site setting
	$menu_name = 'order_user';
    $page_name = 'Көрсеткіштер';
	$site_set['view_dt'] = 1;
	$css = ['order_product'];
	$js = ['order_product'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">
        
		<? include "aheader.php"; ?>

	</div>


	<div class="">
        <div class="bl_c">
            <div class="uc_u">
        
                <div class="uc_uh">
                    <div class="uc_uhn">
                        <div class="uc_uh_number">#</div>
                        <div class="uc_uh_name">Оператор</div>
                        <!-- <div class="uc_uh_other">Точка</div> -->
                        <div class="uc_uh_other">Продажа</div>
                    </div>
                </div>
                <div class="uc_uc">

                    <? while ($cashboxp_d = mysqli_fetch_assoc($oprs)): ?>
                        <? $number_p1++; ?>
                        <? $users_d = fun::user($cashboxp_d['user_id']) ?>

                        <div class="uc_ui">
                            <div class="uc_uil">
                                <div class="uc_ui_number"><?=$number_p1?></div>
                                <div class="uc_uiln" >
                                    <div class="uc_uinu">
                                        <div class="uc_ui_name"><?=$users_d['name']?></div>
                                    </div>
                                </div>
                                <!-- <div class="uc_uin_other"><?=$catalog_d['name_ru']?></div> -->
                                <div class="uc_uin_other "><?=$cashboxp_d['paid']?></div>
                            </div>
                        </div>
                    <? endwhile ?>

                </div>
            </div>
        </div>
    </div>

<? include "../block/footer.php"; ?>