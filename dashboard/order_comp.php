<? include "../config/core.php";

   if (!$user_id) header('location: /');


   	$type = @$_GET['type'];

	// $start_cdate = date('Y-m-d 06:00:00', strtotime("01.01.2025"));
	// $end_cdate = date('Y-m-d 06:00:00', strtotime("20.03.2025"));

    if (@$_GET['time']) {
		$time_sort = $_GET['time'];
		$start_cdate = date('Y-m-d 06:00:00', strtotime("$time_sort"));
		$end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
	}


    // 
	$oprs = db::query("select user_id, COUNT(*) AS paid from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' GROUP BY user_id order by paid desc");
    $number_p1 = 0;

    // 
	// $cashboxp = db::query("select price, product_id, COUNT(*) AS quantity from retail_orders_products where ins_dt BETWEEN '$start_cdate' and '$end_cdate' GROUP BY product_id order by quantity desc");
    // $number = 0;
  
    // 
	// $cashboxp = db::query("select * from product_item");
	$cashboxp = db::query("select * from product_comp where company_id = '$company'");
    $number = 0;


	// site setting
	$menu_name = 'order_comp';
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
                        <div class="uc_uh_name">Тауар атауы</div>
                        <div class="uc_uh_other">Жалпы саны</div>
                    </div>
                </div>
                <div class="uc_uc">
                    
                    <? while ($cashboxp_d = mysqli_fetch_assoc($cashboxp)): ?>
                        <? $number++; ?>
                        <? $comp_id = $cashboxp_d['id']; ?>
                        <? 
                            $o_qn = 0; $o_qn2 = 0;
                            $item = db::query("select * from product_item where `comp_id` = '$comp_id'");
                            while ($item_d = mysqli_fetch_assoc($item)) {
                                $product_id = $item_d['product_id'];
                                $p_qn = mysqli_fetch_assoc(db::query("select COUNT(*) AS quantity from retail_orders_products where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and product_id = '$product_id'"))['quantity'];
                                $o_qn = $p_qn * @$item_d['quantity'];
                                $o_qn2 = $o_qn2 + $o_qn;
                            }
                        ?>
                        
                        <div class="uc_ui">
                            <div class="uc_uil">
                                <div class="uc_ui_number"><?=$number?></div>
                                <div class="uc_uiln" href="/user/admin/users/item/?id=<?=$user_d['id']?>">
                                    <div class="uc_uinu">
                                        <div class="uc_ui_name"><?=$cashboxp_d['name_kz']?></div>
                                    </div>
                                </div>
                                <div class="uc_uin_other "><?=$o_qn2?> шт</div>
                            </div>
                        </div>
                        
                    <? endwhile ?>

                </div>
            </div>
        </div>
    </div>

<? include "../block/footer.php"; ?>