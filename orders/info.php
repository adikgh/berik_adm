<? include "../config/core.php"; ?>

    <? $id = strip_tags($_POST['id']); ?>
	<? $orders = db::query("select * from retail_orders where id = '$id'"); ?>
    <? $buy_d = mysqli_fetch_assoc($orders); ?>

        <div class="uc_uil2_raz">
            <div class="uc_uil2_trt">
                <div class="uc_uil2_trt1">Атауы</div>
                <div class="uc_uil2_trt2">Саны</div>
                <div class="uc_uil2_trt3">Бағасы</div>
            </div>
            <div class="uc_uil2_trc">

                <? 	
                    $cashbox_id = $buy_d['id'];
                    $cashboxp = db::query("select * from retail_orders_products where order_id = '$cashbox_id' order by ins_dt asc");
                    $number = 0; $total = 0;
                ?>
                <? if (mysqli_num_rows($cashboxp) != 0): ?>
                    <? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
                        <? 
                            $number++; 
                            $sum = $sel_d['quantity'] * $sel_d['price']; 
                            $total = $total + $sum;
                            $product_d = product::product($sel_d['product_id']);
                        ?>
                        <div class="uc_uil2_trt">
                            <div class="uc_uil2_trt1"><?=$number?>. <?=$product_d['name_ru']?></div>
                            <div class="uc_uil2_trt2"><?=$sel_d['quantity']?> шт</div>
                            <!-- <div class=""><?=$sel_d['price']?></div> -->
                            <div class="uc_uil2_trt3 fr_price"><?=$sum?></div>
                        </div>
                    <? endwhile ?>
                <? endif ?>
                
                <div class="uc_uil2_trt">
                    <div class="uc_uil2_trt1">Доставка</div>
                    <div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_delivery']?></div>
                </div>
                <div class="uc_uil2_trt">
                    <div class="uc_uil2_trt1">Предоплата</div>
                    <div class="uc_uil2_trt2">-</div>
                    <div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_qr']?></div>
                </div>
            </div>
            <div class="uc_uil2_trb">
                <div class="uc_uil2_trt1">К оплате</div>
                <div class="uc_uil2_trt2"></div>
                <div class="uc_uil2_trt3 fr_price"><?=$buy_d['total'] - $buy_d['pay_qr']?></div>
            </div>
        </div>

	<? exit(); ?>