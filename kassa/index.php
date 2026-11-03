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

	// курьерлердің күндік есебі (сақталғандар)
	include_once "cday_lib.php";
	include_once "ksnap_lib.php";
	$cd_ready = cday::ready() && !in_array(@$_GET['mode'], ['w', 'm', 'y'], true); // сақтау тек күндік көріністе
	$cd_day = $time_sort;
	$cd_map = $cd_ready ? cday::day_map($company, $cd_day) : [];
	$cd_unsaved = 0;
    



	// Кезең: d — күн (әдепкі), w — апта (дүйсенбіден), m — ай, y — жыл. Күн ?time= арқылы таңдалады.
	$k_mode = in_array(@$_GET['mode'], ['d', 'w', 'm', 'y'], true) ? $_GET['mode'] : 'd';
	if ($k_mode != 'd') {
		$k_t = strtotime($time_sort);
		if ($k_mode == 'w') $k_from = date('Y-m-d', strtotime('-'.(intval(date('N', $k_t)) - 1).' day', $k_t));
		elseif ($k_mode == 'm') $k_from = date('Y-m-01', $k_t);
		else $k_from = date('Y-01-01', $k_t);
		$start_cdate = "$k_from 06:00:00";
		$end_cdate = date('Y-m-d 06:00:00', strtotime($k_from.' +1 '.['w' => 'week', 'm' => 'month', 'y' => 'year'][$k_mode]));
	}
	$k_daily = $k_mode == 'd'; // курьердің шығын / қолма-қол енгізу тек күндік көріністе
	$k_modes = ['d' => t::w('Daily', 'Күндік'), 'w' => t::w('Weekly', 'Апталық'), 'm' => t::w('Monthly', 'Айлық'), 'y' => t::w('Yearly', 'Жылдық')];
	$k_next = ['d' => 'w', 'w' => 'm', 'm' => 'y', 'y' => 'd'][$k_mode];
	$k_period = ['d' => 'day', 'w' => 'week', 'm' => 'month', 'y' => 'year'][$k_mode];


	// Жалпы есеп: формат бойынша жолдар, төлем түрлері бойынша бағандар
	$branch_id = intval(@$_GET['branch']);
	$br_cond = $branch_id ? " and branch_id = '$branch_id'" : "";
	$k_rows = [
		'all'  => ['name' => t::w('All', 'Барлығы'),  'cond' => "order_status in (1, 8, 2, 11, 3, 9, 4, 6, 10)"],
		'yo'   => ['name' => t::w('type:Собой', 'Өзімен'),   'cond' => "order_type = 2 and order_status in (1, 8, 2, 11, 3, 9, 4, 6, 10)"],
		'dl'   => ['name' => t::w('type:Жеткізу', 'Жеткізу'),  'cond' => "order_type = 1 and order_status in (1, 8, 2, 11, 3, 9, 4, 6, 10)"],
		'cf'   => ['name' => t::w('Cafe', 'Кафе'),     'cond' => "order_type = 4 and order_status in (1, 8, 2, 11, 3, 9, 4, 6, 10)"],
		'sv'   => ['name' => t::w('type:Сервис', 'Сервис'),   'cond' => "order_type = 3 and order_status in (1, 8, 2, 11, 3, 9, 4, 6, 10)"],
		'otk'  => ['name' => t::w('st:Отказ', 'Отказ'),    'cond' => "order_status in (5, 6, 10)"],
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

	// Есеп: өткен күндер — 06:00-де сақталған суреттен (company_kassa_day), бүгін — тапсырыстардан тірі есеп
	$k_today = ksnap::today();
	$k_from = date('Y-m-d', strtotime($start_cdate));
	$k_to = date('Y-m-d', strtotime($end_cdate));
	$k_all = ksnap::agg($company, $branch_id, $k_from, $k_to, 'all');
	$k_d = isset($k_all['all']) ? $k_all['all'] : ksnap::empty_day();
	$k_is_today = $k_daily && $k_from == $k_today; // енгізу тек бүгін
	$k_info = ($k_daily && $k_from < $k_today) ? ksnap::info($company, $branch_id, $k_from) : false;
	$k_changed = false;
	if ($k_info) { // сақталғаннан кейін тапсырыстар өзгерді ме
		$k_chk = ksnap::calc($company, $branch_id, $k_from, $k_to);
		$k_chk = isset($k_chk[$k_from]) ? $k_chk[$k_from]['f'] : ksnap::empty_day()['f'];
		foreach (['all', 'otk'] as $kk) if ($k_chk[$kk]['number'] != $k_d['f'][$kk]['number'] || $k_chk[$kk]['total'] != $k_d['f'][$kk]['total']) $k_changed = true;
	}

	$k_sum = [];
	foreach ($k_rows as $key => $row) {
		$r = $k_d['f'][$key];
		$r['srd'] = $r['number'] ? intval($r['total'] / $r['number']) : 0;
		$k_sum[$key] = $r;
	}

	// Курьерлер: компания курьерлері + осы кезеңде есебі барлар
	$k_cour = [];
	$staff = db::query("select * from company_staff where positions_id = 6 and company_id = '$company'");
	while ($staff && $staff_d = mysqli_fetch_assoc($staff)) $k_cour[$staff_d['user_id']] = '';
	$k_staff = $k_cour;
	foreach ($k_d['c'] as $uid => $cr) if (!isset($k_cour[$uid])) $k_cour[$uid] = $cr['name'];
	$k_names = ksnap::names(array_keys($k_cour));

	// күндік көріністе: курьердің қолма-қол тапсырыстарының тізімі (ашылатын жол)
	$k_clist = [];
	if ($k_daily && $has_cpay) {
		$q = db::query("select * from retail_orders where ins_dt >= '$start_cdate' and ins_dt < '$end_cdate' and company_id = '$company' and number is not null and order_status in (1, 8, 2, 11, 3, 4) and pay_cash > 0 $br_cond order by number desc");
		while ($q && $o = mysqli_fetch_assoc($q)) $k_clist[intval($o['сourier_id'])][] = $o;
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


	// site setting
	$menu_name = 'kassa';
	$page_name = 'Касса';
	$page_key = 'Cashbox';
	$site_set['view_dt'] = 1;
	$css = ['kassa', 'opiu', 'kassa_cday', 'ksnap'];
	$js = ['kassa', 'kassa_cday', 'ksnap'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c k_page" data-ajx-nav=".k_page">

			<div class="">
				<div class="uc_ui69">

					<!-- бір батырма: басқан сайын Күндік → Апталық → Айлық → Жылдық ауысады -->
					<div class="rnp_seg"><? foreach (['d' => 'Күн', 'w' => 'Апта', 'm' => 'Ай', 'y' => 'Жыл'] as $k => $n): ?><a class="rnp_seg_i<?=$k == $k_mode ? ' rnp_seg_act' : ''?>" href="?<?=http_build_query(array_merge($_GET, ['mode' => $k]))?>"><?=$n?></a><? endforeach ?></div>

                	<div class="">
						<input class="form_dt on_sort_time" type="date" data-period="<?=$k_period?>" name="" id="" value="<?=date('Y-m-d', strtotime("$start_cdate"))?>">
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

            <!-- есеп қайдан: сақталған (06:00) не тірі -->
            <div class="ks_info">
                <? if ($k_info): ?>
                    <span class="ks_saved"><i class="fal fa-lock"></i> <?=t::w('Saved report', 'Сақталған есеп')?> · <?=date('d.m H:i', strtotime($k_info['ins_dt']))?><?=($k_info['auto'] ? ' · '.t::w('auto', 'автомат') : '')?></span>
                    <? if ($k_changed): ?><span class="ks_chg"><i class="fal fa-exclamation-triangle"></i> <?=t::w('Orders changed after saving', 'Сақталғаннан кейін тапсырыс өзгерді')?></span><? endif ?>
                    <? if (@$user_right['positions_id'] != 3): ?><span class="ks_resave" data-day="<?=$k_from?>"><i class="fal fa-sync"></i> <?=t::w('Re-save', 'Қайта сақтау')?></span><? endif ?>
                <? elseif ($k_is_today): ?>
                    <span class="ks_live"><i class="fal fa-circle"></i> <?=t::w('Live · saved automatically at 06:00', 'Тірі есеп · 06:00-де өзі сақталады')?></span>
                <? elseif (!$k_daily): ?>
                    <span class="ks_live"><i class="fal fa-database"></i> <?=t::w('Past days from saved reports', 'Өткен күндер — сақталған есептен')?><?=(($k_today >= $k_from && $k_today < $k_to) ? t::w(', today live', ', бүгін — тірі') : '')?></span>
                <? endif ?>
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
                        <? foreach ($k_cour as $staff_id => $staff_nm): ?>
                            <? $cr = isset($k_d['c'][$staff_id]) ? $k_d['c'][$staff_id] : array_fill_keys(ksnap::$nums, 0); ?>
                            <? $staff_name = @$k_names[$staff_id] ?: $staff_nm; ?>
                            <? $c_list = @$k_clist[$staff_id] ?: []; ?>
                            <? $cd_s = @$cd_map[$staff_id]; ?>
                            <? $c_box = $cr['pay_cash'] - $cr['pay_delivery']; // кассаға ?>
                            <? $c_kaspi = $c_box - $cr['expenses'] - $cr['cash']; ?>
                            <?
                                $onw['number'] += $cr['number'];
                                $onw['total'] += $cr['total'];
                                $onw['pay_qr'] += $cr['pay_qr'];
                                $onw['pay_cash'] += $cr['pay_cash'];
                                $onw['pay_delivery'] += $cr['pay_delivery'];
                                $onw['rask'] += $cr['expenses'];
                                $onw['cash'] += $cr['cash'];
                                $onw['kaspi'] += $c_kaspi;
                            ?>

                            <tr class="<?=($cd_s?'cd_saved_row':'')?>">
                                <td><? if ($has_cpay && $c_list): ?><div class="k_cr_tg" data-id="<?=$staff_id?>"><i class="fal fa-angle-down"></i> <?=$staff_name?></div><? else: ?><?=$staff_name?><? endif ?></td>
                                <td><?=$cr['number']?></td>
                                <td class="fr_price"><?=$cr['total']?></td>
                                <td class="fr_price"><?=$cr['pay_qr']?></td>
                                <td>
                                    <div class="fr_price"><?=$cr['pay_cash']?></div>
                                    <? if ($has_cpay && $cr['pay_cash']): ?>
                                        <div class="k_cr_sub">
                                            <? if ($cr['c_cash']): ?><span class="k_cr_c">нал <?=number_format($cr['c_cash'], 0, '', ' ')?></span><? endif ?>
                                            <? if ($cr['c_kaspi']): ?><span class="k_cr_k">kaspi <?=number_format($cr['c_kaspi'], 0, '', ' ')?></span><? endif ?>
                                            <? if ($cr['c_none']): ?><span class="k_cr_n">? <?=number_format($cr['c_none'], 0, '', ' ')?></span><? endif ?>
                                        </div>
                                    <? endif ?>
                                </td>
                                <td class="fr_price"><?=$cr['pay_delivery']?></td>
                                <td class="fr_price btype_start" data-rask="0" data-start="<?=$c_box?>"><?=$c_box?></td>
                                <? if ($k_is_today): // бүгін: шығын мен қолма-қолды енгізуге болады ?>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_rask" <?=($cd_s?'disabled':'')?> placeholder="0" data-val="<?=$cr['expenses']?>" value="<?=($cr['expenses']?:'')?>">
                                </td>
                                <td class="">
							        <input type="tel" data-id="<?=$cashbox_id?>" data-user-id="<?=$staff_id?>" class="form_txt fr_price btype_cash" <?=($cd_s?'disabled':'')?> placeholder="0" data-val="<?=$cr['cash']?>" value="<?=($cr['cash']?:'')?>">
                                </td>
                                <? else: // өткен күн / апта / ай / жыл: сақталған есептен ?>
                                <td class="fr_price"><?=$cr['expenses']?></td>
                                <td class="fr_price"><?=$cr['cash']?></td>
                                <? endif ?>
                                <td class="fr_price btype_kaspi"><?=$c_kaspi?></td>
                                <td class="cd_cell">
                                    <? $cd_live_box = $c_box; $cd_live_n = $cr['number']; ?>
                                    <? $cd_attr = 'data-id="'.$cashbox_id.'" data-user-id="'.$staff_id.'" data-day="'.$cd_day.'" data-name="'.htmlspecialchars($staff_name).'"'; ?>
                                    <? if (!$cd_ready): ?>
                                    <? elseif ($cd_s): ?>
                                        <span class="cd_ok"><i class="fas fa-check-circle"></i> <?=t::w('Saved', 'Сақталды')?> <span><?=($cd_s['accepted_by'] ? '' : t::w('auto', 'авто').' ')?><?=date('H:i', strtotime($cd_s['upd_dt'] ?: $cd_s['ins_dt']))?></span></span><i class="fal fa-undo cd_undo" title="<?=t::w('Reopen', 'Ашу')?>" <?=$cd_attr?>></i>
                                        <? if (!$branch_id && ($cd_live_n != $cd_s['orders'] || $cd_live_box != $cd_s['to_cashbox'])): ?>
                                            <span class="cd_chg"><i class="fal fa-exclamation-triangle"></i> <?=t::w('Orders changed', 'Тапсырыс өзгерді')?> · <span class="cd_save" <?=$cd_attr?>><?=t::w('Re-save', 'Қайта сақтау')?></span></span>
                                        <? endif ?>
                                    <? elseif ($branch_id): ?>
                                        <span class="cd_off"><?=t::w('Choose all branches', 'Барлық точка таңдаңыз')?></span>
                                    <? elseif (!isset($k_staff[$staff_id])): // курьер тізімінде жоқ ?>
                                    <? elseif ($cd_live_n || $cr['expenses'] || $cr['cash']): $cd_unsaved++; ?>
                                        <span class="cd_save" <?=$cd_attr?>><i class="fal fa-save"></i> <?=t::w('Save', 'Сақтау')?></span>
                                    <? endif ?>
                                </td>
                            </tr>
                            <? if ($has_cpay && $c_list): ?>
                                <tr class="k_cr_det" data-id="<?=$staff_id?>">
                                    <td colspan="11">
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
                        <? endforeach ?>
                    
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
                            <td><?=t::w('Report', 'Есеп')?></td>
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
                            <td class="cd_cell">
                                <? if ($cd_ready && !$branch_id && $cd_unsaved): ?><span class="cd_save_all" data-id="<?=$cashbox_id?>" data-day="<?=$cd_day?>"><i class="fal fa-save"></i> <?=t::w('Save all', 'Барлығын сақтау')?></span><? endif ?>
                                <a class="cd_hist" href="/kassa/couriers.php"><i class="fal fa-history"></i> <?=t::w('History', 'Тарих')?></a>
                            </td>
                        </tr>
                    </thead>
                </table>

			</div>

            <br><br><br>
        
	</div>

<? include "../block/footer.php"; ?>