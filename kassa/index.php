<? include "../config/core.php";

	// 
	if (!$user_id) { header('location: /'); exit(); }


    $company_d = mysqli_fetch_assoc(db::query("SELECT * FROM company WHERE `id` = '$company'"));



    $cashbox = db::query("select * from report_1 where paid = 0 and company_id = '$company' order by id desc limit 1");
	if (mysqli_num_rows($cashbox)) {
		$cashbox_d = mysqli_fetch_assoc($cashbox);
		$cashbox_id = $cashbox_d['id'];
	} else {
		$cashbox_id = (mysqli_fetch_assoc(db::query("SELECT * FROM `report_1` order by id desc")))['id'] + 1;
		$ins = db::query("INSERT INTO `report_1`(`id`, `user_id`, `company_id`) VALUES ('$cashbox_id', '$user_id', '$company')");
	}
	$cashboxp = db::query("select * from report_сourier where report_id = '$cashbox_id' order by ins_dt asc");
    



	// Жалпы есеп: формат бойынша жолдар, төлем түрлері бойынша бағандар
	$branch_id = intval(@$_GET['branch']);
	$br_cond = $branch_id ? " and branch_id = '$branch_id'" : "";
	$k_rows = [
		'all'  => ['name' => t::w('All', 'Барлығы'),  'cond' => "order_status in (1, 2, 3, 4)"],
		'yo'   => ['name' => t::w('type:Собой', 'Өзімен'),   'cond' => "order_type = 2 and order_status in (1, 2, 3, 4)"],
		'dl'   => ['name' => t::w('type:Жеткізу', 'Жеткізу'),  'cond' => "order_type = 1 and order_status in (1, 2, 3, 4)"],
		'cf'   => ['name' => t::w('Cafe', 'Кафе'),     'cond' => "order_type = 4 and order_status in (1, 2, 3, 4)"],
		'sv'   => ['name' => t::w('type:Сервис', 'Сервис'),   'cond' => "order_type = 3 and order_status in (1, 2, 3, 4)"],
		'otk'  => ['name' => t::w('st:Отказ', 'Отказ'),    'cond' => "order_status in (5, 6)"],
	];
	// төлем бағандары: ақша қалай түсті (ретімен)
	$k_cols = [
		'pay_cash'     => t::w('Cash', 'Қолма-қол'),
		'pay_qr'       => t::w('Kaspi QR', 'Kaspi QR'),
		'pay_transfer' => t::w('Transfer', 'Аударым'),
		'pay_prepay'   => t::w('Prepayment', 'Предоплата'),
		'pay_red'      => t::w('RED', 'RED'),
		'pay_delivery' => t::w('Delivery charge', 'Жеткізу ақысы'),
	];
	$has_cpay = fun::has_col('retail_orders', 'courier_method');

	// точкалар (батырма түрінде)
	$branches = [];
	$bq = db::query("select * from company_branch where company_id = '$company' order by number asc");
	while ($b = mysqli_fetch_assoc($bq)) $branches[] = $b;

	$k_sum = [];
	foreach ($k_rows as $key => $row) {
		$r = ['number' => 0, 'total' => 0, 'pay_qr' => 0, 'pay_cash' => 0, 'pay_transfer' => 0, 'pay_red' => 0, 'pay_prepay' => 0, 'pay_delivery' => 0, 'srd' => 0];
		$q = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and number is not null and $row[cond] and company_id = '$company' $br_cond");
		while ($buy_d = mysqli_fetch_assoc($q)) {
			$r['number']++;
			$r['total'] += $buy_d['total'];
			$r['pay_qr'] += $buy_d['pay_qr'];
			$r['pay_cash'] += $buy_d['pay_cash'];
			$r['pay_transfer'] += intval($buy_d['pay_transfer'] ?? 0);
			$r['pay_red'] += intval($buy_d['pay_red'] ?? 0);
			$r['pay_prepay'] += intval($buy_d['pay_prepay'] ?? 0);
			$r['pay_delivery'] += intval($buy_d['pay_delivery']);
		}
		if ($r['number']) $r['srd'] = intval($r['total'] / $r['number']);
		$k_sum[$key] = $r;
	}







    // if (@$_GET['time']) {
    //     $time_sort = $_GET['time'];
    //     $start_cdate = date('Y-m-d 06:00:00', strtotime("$date $time_sort day"));
    //     $end_cdate = date('Y-m-d 06:00:00', strtotime("$start_cdate +1 day"));
    // }



    $onw['number'] = 0;
    $onw['total'] = 0;
    $onw['pay_qr'] = 0;
    $onw['pay_cash'] = 0;
    $onw['pay_delivery'] = 0;
    $onw['rask'] = 0;
    $onw['cash'] = 0;
    $onw['kaspi'] = 0;
    $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$company'");


	// site setting
	$menu_name = 'kassa';
	$page_name = 'Касса';
	$page_key = 'Cashbox';
	$site_set['view_dt'] = 1;
	$css = ['kassa'];
	$js = ['kassa'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

			<div class="">
				<div class="uc_ui69">

                	<div class="">
						<input class="form_dt on_sort_time" type="date" name="" id="" value="<?=date('Y-m-d', strtotime("$start_cdate"))?>">
					</div>

 					<? if (count($branches)): ?>
						<div class="k_br">
							<a class="k_br_i <?=(!$branch_id?'k_br_act':'')?>" href="?<?=http_build_query(array_merge($_GET, ['branch' => 0]))?>"><i class="fal fa-store"></i><span><?=t::w('Branch: all', 'Барлық точка')?></span></a>
							<? foreach ($branches as $branch_d): ?>
								<a class="k_br_i <?=($branch_id == $branch_d['id']?'k_br_act':'')?>" href="?<?=http_build_query(array_merge($_GET, ['branch' => $branch_d['id']]))?>"><span><?=htmlspecialchars($branch_d['name'])?></span></a>
							<? endforeach ?>
						</div>
					<? endif ?>
					
				</div>
			</div>

            <div class="table4">

				<table>
                    <thead>
                        <tr>
                            <td><b><?=t::w('Format', 'Формат')?></b></td>
                            <td><?=t::w('Qty', 'Саны')?></td>
                            <td><?=t::w('Total', 'Жалпы')?></td>
                            <td><?=t::w('Average check', 'Орташа чек')?></td>
                            <? foreach ($k_cols as $ck => $cn): ?><td><?=$cn?></td><? endforeach ?>
                        </tr>
                        <? foreach ($k_rows as $key => $row): $r = $k_sum[$key]; ?>
                        <tr class="<?=($key=='all'?'k_row_all':'')?> <?=($key=='otk'?'k_row_otk':'')?> <?=(!$r['number']?'k_row_zero':'')?>">
                            <td><?=$row['name']?></td>
                            <td><?=$r['number']?></td>
                            <td class="fr_price"><?=$r['total']?></td>
                            <td class="fr_price"><?=$r['srd']?></td>
                            <? foreach ($k_cols as $ck => $cn): ?><td class="fr_price"><?=$r[$ck]?></td><? endforeach ?>
                        </tr>
                        <? endforeach ?>
                    </thead>
                </table>

			</div>

			<div class="table4">

				<table>

                    <tbody>
                        <? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
                            <? $staff_user_d = fun::user($staff_d['user_id']); ?>
                            <? $staff_id = $staff_d['user_id']; ?>
                            <? $orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and сourier_id = '$staff_id' and company_id = '$company' and number is not null and order_status in (1, 2, 3, 4) $br_cond order by number desc"); ?>
                            <? $report_сourier_d = fun::report_сourier($cashbox_id, $staff_id); ?>

                            <?
                                $allorder['total'] = 0;
                                $allorder['pay_qr'] = 0;
                                $allorder['pay_delivery'] = 0;
                                $allorder['pay_cash'] = 0;
                                $allorder['c_cash'] = 0; $allorder['c_kaspi'] = 0; $allorder['c_none'] = 0;
                                $c_list = [];

                                while ($buy_d1 = mysqli_fetch_assoc($orders)){
                                    if ($buy_d1['pay_cash'] > 0) {
                                        $c_list[] = $buy_d1;
                                        if (!empty($buy_d1['courier_method'])) {
                                            $allorder['c_cash'] += intval($buy_d1['courier_cash']);
                                            $allorder['c_kaspi'] += intval($buy_d1['courier_kaspi']);
                                        } else $allorder['c_none'] += $buy_d1['pay_cash'];
                                    }
                                    $allorder['total'] = $allorder['total'] + $buy_d1['total'];
								    $allorder['pay_qr'] = $allorder['pay_qr'] + $buy_d1['pay_qr'];
								    $allorder['pay_cash'] = $allorder['pay_cash'] + $buy_d1['pay_cash'];
								    $allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d1['pay_delivery'] + $company_d['delivery_price'];
                                }

                                $onw['number'] = $onw['number'] + mysqli_num_rows($orders);
                                $onw['total'] = $onw['total'] + $allorder['total'];
                                $onw['pay_qr'] = $onw['pay_qr'] + $allorder['pay_qr'];
                                $onw['pay_cash'] = $onw['pay_cash'] + $allorder['pay_cash'];
                                $onw['pay_delivery'] = $onw['pay_delivery'] + $allorder['pay_delivery'];
                            ?>

                            <tr>
                                <td><? if ($has_cpay && $c_list): ?><div class="k_cr_tg" data-id="<?=$staff_id?>"><i class="fal fa-angle-down"></i> <?=$staff_user_d['name']?></div><? else: ?><?=$staff_user_d['name']?><? endif ?></td>
                                <td><?=mysqli_num_rows($orders)?></td>
                                <td class="fr_price"><?=$allorder['total']?></td>
                                <td class="fr_price"><?=$allorder['pay_qr']?></td>
                                <td>
                                    <div class="fr_price"><?=$allorder['pay_cash']?></div>
                                    <? if ($has_cpay && $allorder['pay_cash']): ?>
                                        <div class="k_cr_sub">
                                            <? if ($allorder['c_cash']): ?><span class="k_cr_c">нал <?=number_format($allorder['c_cash'], 0, '', ' ')?></span><? endif ?>
                                            <? if ($allorder['c_kaspi']): ?><span class="k_cr_k">kaspi <?=number_format($allorder['c_kaspi'], 0, '', ' ')?></span><? endif ?>
                                            <? if ($allorder['c_none']): ?><span class="k_cr_n">? <?=number_format($allorder['c_none'], 0, '', ' ')?></span><? endif ?>
                                        </div>
                                    <? endif ?>
                                </td>
                                <td class="fr_price"><?=$allorder['pay_delivery']?></td>
                                <td class="fr_price btype_start" data-rask="0" data-start="<?=$allorder['pay_cash'] - $allorder['pay_delivery']?>"><?=$allorder['pay_cash'] - $allorder['pay_delivery']?></td>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_rask" placeholder="0" data-val="<?=(@$report_сourier_d['expenses']?$report_сourier_d['expenses']:0)?>" value="<?=@$report_сourier_d['expenses']?>">
                                </td>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_cash" placeholder="0" data-val="<?=(@$report_сourier_d['cash']?$report_сourier_d['cash']:0)?>" value="<?=@$report_сourier_d['cash']?>">
                                </td>
                                <td class="fr_price btype_kaspi"><?=$allorder['pay_cash']- $allorder['pay_delivery'] - @$report_сourier_d['expenses'] - @$report_сourier_d['cash']?></td>
                                <!-- <td class="fr_price btype_kaspi"><div class="btn btn_dd_cm"><i class="far fa-check-circle"></i></div></td> -->
                            </tr>
                            <? if ($has_cpay && $c_list): ?>
                                <tr class="k_cr_det" data-id="<?=$staff_id?>">
                                    <td colspan="10">
                                        <div class="k_cr_list">
                                            <? foreach ($c_list as $co): ?>
                                                <div class="k_cr_o">
                                                    <div class="k_cr_on">№<?=$co['number']?> <span><?=date('H:i', strtotime($co['ins_dt']))?></span></div>
                                                    <?=fun::cpay_block($co)?>
                                                </div>
                                            <? endforeach ?>
                                        </div>
                                    </td>
                                </tr>
                            <? endif ?>

                            <? 
                                $onw['rask'] = $onw['rask'] + @$report_сourier_d['expenses'];
                                $onw['cash'] = $onw['cash'] + @$report_сourier_d['cash'];
                                $onw['kaspi'] = $onw['kaspi'] + ($allorder['pay_cash'] - ($allorder['pay_delivery'] + @$report_сourier_d['expenses'] + @$report_сourier_d['cash']));
                            ?>

                        <? endwhile ?>
                    
                    </tbody>

                    <thead>
                        <tr>
                            <td><b><?=t::w('Courier', 'Курьер')?></b></td>
                            <td><?=t::w('Qty', 'Саны')?></td>
                            <td><?=t::w('Total', 'Жалпы')?></td>
                            <td><?=t::w('Prepaid QR', 'Алдын ала (QR)')?></td>
                            <td><?=t::w('Courier collected', 'Курьер алды')?></td>
                            <td><?=t::w('Salary', 'Жалақы')?></td>
                            <td><?=t::w('To cashbox', 'Кассаға')?></td>
                            <td><?=t::w('Expenses', 'Шығын')?></td>
                            <td><?=t::w('Cash', 'Қолма-қол')?></td>
                            <td><?=t::w('Kaspi', 'Kaspi')?></td>
                            <!-- <td>Статус</td> -->
                        </tr>
                        <tr>
                            <td><?=t::w('All', 'Барлығы')?></td>
                            <td><?=$onw['number']?></td>
                            <td class="fr_price"><?=$onw['total']?></td>
                            <td class="fr_price"><?=$onw['pay_qr']?></td>
                            <td class="fr_price"><?=$onw['pay_cash']?></td>
                            <td class="fr_price"><?=$onw['pay_delivery']?></td>
                            <td class="fr_price"><?=$onw['pay_cash'] - $onw['pay_delivery']?></td>
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