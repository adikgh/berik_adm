<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');


	// site setting
	$menu_name = 'main';
	// $css = [''];
	$js = ['kassa'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

			<div class="">

                <br><br><br><br>
				
				<table border="1" style="">

                    <style>
                        tr{
                            padding: 10px;
                        }
                        td{
                            padding: 10px;
                        }

                        .form_txt{
                            padding: 5px;
                            min-width: 120px;
                            width: 120px;
                            height: 34px;
                            min-height: 34px;
                        }
                    </style>

                    <tbody>

                        <? 
                            $onw['number'] = 0;
                            $onw['total'] = 0;
                            $onw['pay_qr'] = 0;
                            $onw['pay_delivery'] = 0;
                            $staff = db::query("select * from user_staff where positions_id = 6");
                        ?>
                        <? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
                            <? $staff_user_d = fun::user($staff_d['user_id']); ?>
                            <? $staff_id = $staff_d['user_id']; ?>
                            <!-- <option value="" data-id="<?=$staff_d['user_id']?>" <?=($buy_d['сourier_id'] == $staff_d['user_id']?'selected':'')?>></option> -->
                            
                            <? $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and сourier_id  = '$staff_id' and branch_id = '$branch' order by number desc"); ?>
                            <?
                                $allorder['total'] = 0;
                                $allorder['pay_qr'] = 0;
                                $allorder['pay_delivery'] = 0;
                               
                                while ($buy_d = mysqli_fetch_assoc($orders)){
                                    $allorder['total'] = $allorder['total'] + $buy_d['total'];
								    $allorder['pay_qr'] = $allorder['pay_qr'] + $buy_d['pay_qr'];
								    $allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
                                }

                                $onw['number'] = $onw['number'] + mysqli_num_rows($orders);
                                $onw['total'] = $onw['total'] + $allorder['total'];
                                $onw['pay_qr'] = $onw['pay_qr'] + $allorder['pay_qr'];
                                $onw['pay_delivery'] = $onw['pay_delivery'] + $allorder['pay_delivery'];
                            ?>

                            <tr>
                                <td><?=$staff_user_d['name']?></td>
                                <td><?=mysqli_num_rows($orders)?></td>
                                <td class="fr_price"><?=$allorder['total']?></td>
                                <td class="fr_price"><?=$allorder['pay_qr']?></td>
                                <td class="fr_price"><?=$allorder['total'] - $allorder['pay_qr']?></td>
                                <td class="fr_price"><?=$allorder['pay_delivery']?></td>
                                <td class="fr_price btype_start" data-rask="0" data-start="<?=$allorder['total'] - $allorder['pay_qr'] - $allorder['pay_delivery']?>"><?=$allorder['total'] - $allorder['pay_qr'] - $allorder['pay_delivery']?></td>
                                <td class="">
							        <input type="tel" class="form_txt fr_price btype_rask" placeholder="0" data-val="0" >
                                </td>
                                <td class="">
							        <input type="tel" class="form_txt fr_price btype_cash" placeholder="0" data-val="0">
                                </td>
                                <td class="fr_price btype_kaspi"><?=$allorder['total'] - $allorder['pay_qr'] - $allorder['pay_delivery']?></td>
                            </tr>
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
                        </tr>
                        <tr>
                            <td>Барлыгы</td>
                            <td><?=$onw['number']?></td>
                            <td class="fr_price"><?=$onw['total']?></td>
                            <td class="fr_price"><?=$onw['pay_qr']?></td>
                            <td class="fr_price"><?=$onw['total'] - $onw['pay_qr']?></td>
                            <td class="fr_price"><?=$onw['pay_delivery']?></td>
                            <td class="fr_price"><?=$onw['total'] - $onw['pay_qr'] - $onw['pay_delivery']?></td>
                            <td class="fr_price or_rask"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </thead>
                </table>

			</div>
        <br><br><br>
	</div>

<? include "../block/footer.php"; ?>