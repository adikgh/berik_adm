<? // Раздача экраны: тапсырыстарды курьерге бөлу, Өзімен/Сервис/Кафе тапсырыстарын беру.
	// Админде де ашылады (/orders/dist/), Раздача хостында (dist.* / dstb.*) — басты бет (config/alias.php).
	// orders/dist/item.php осыны $item_id-мен шақырады.
	if (!isset($item_id)) include "../../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	$company_d = fun::company($company);

	$sort = in_array(@$_GET['sort'], ['new', 'road', 'history', 'myself', 'myself_yes', 'service', 'service_yes', 'coffee', 'none'], true) ? $_GET['sort'] : 'new';
	// Компанияда өшірулі түрдің беті ашылмайды, бірінші қосулы түрге ауысады
	$sort_on = ['new' => 'delivery', 'road' => 'delivery', 'history' => 'delivery', 'myself' => 'yourself', 'myself_yes' => 'yourself', 'service' => 'service', 'service_yes' => 'service', 'coffee' => 'coffee'];
	if (isset($sort_on[$sort]) && empty($company_d[$sort_on[$sort]])) {
		$sort = !empty($company_d['delivery']) ? 'new' : (!empty($company_d['yourself']) ? 'myself' : (!empty($company_d['service']) ? 'service' : (!empty($company_d['coffee']) ? 'coffee' : 'none')));
	}

	
	// $start_cdate = '2025-03-07';


	// 
	if ($sort == 'new') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `сourier_id` is null and `order_status` in(1, 8, 2) order by number asc");
	} elseif ($sort == 'road' && @$_GET['staff']) {
		$menu_name = 'car';
		$сourier_id = (int)$_GET['staff'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` in (11, 3) and `сourier_id` = '$сourier_id' order by number desc");
	} elseif ($sort == 'road') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` in (11, 3) and `сourier_id` is not null order by number desc");
	} elseif ($sort == 'history' && @$_GET['staff']) {
		$menu_name = 'car';
		$сourier_id = (int)$_GET['staff'];
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 4 and  `сourier_id` = '$сourier_id' order by number desc");
	} elseif ($sort == 'history') {
		$menu_name = 'car';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 1 and `order_status` = 4 and `сourier_id` is not null order by number desc");
	} elseif ($sort == 'myself') {
		$menu_name = 'user';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 2 and `order_status` in(1, 8, 2) order by number desc");
	} elseif ($sort == 'myself_yes') {
		$menu_name = 'user';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 2 and `order_status` = 4 order by number desc");
	} elseif ($sort == 'service') {
		$menu_name = 'service';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 3 and `order_status` in(1, 8, 2) order by number desc");
	} elseif ($sort == 'service_yes') {
		$menu_name = 'service';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 3 and `order_status` = 4 order by number desc");
	} elseif ($sort == 'coffee') {
		$menu_name = 'coffee';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$company' and `order_type` = 4 order by number desc");
	} else {
		$menu_name = 'none';
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1  and company_id = '$company' and `order_status` in(5, 6, 10) order by number desc");
	}



	// «Толығырақ» беті (orders/item.php): тек осы тапсырыс
	if (!empty($item_id)) {
		$orders = db::query("select * from retail_orders where id = '".(int)$item_id."' and company_id = '".(int)$company."' limit 1");
		$item_o = $orders ? mysqli_fetch_assoc($orders) : null;
		if (!$item_o) { header('location: /orders/dist/'); exit(); }
		mysqli_data_seek($orders, 0);
		$sort = 'item';
		$menu_name = [1 => 'car', 2 => 'user', 3 => 'service', 4 => 'coffee'][(int)$item_o['order_type']] ?? 'car';
		if (in_array((int)$item_o['order_status'], [5, 6, 10])) $menu_name = 'none';
	}

	$allorder['total'] = 0;
	$allorder['number'] = 0;
	$allorder['pay_qr'] = 0;
	$allorder['pay_delivery'] = 0;


	// site setting
	$pod_menu_name = $sort;
	$css = ['dist'];
	$js = ['dist'];
	$dist_menu = $menu_name; // block/menu.php: Раздача мәзірі
	$page_name = ['car' => t::w('Delivery', 'Жеткізу'), 'user' => t::w('Pickup', 'Өзімен'), 'service' => t::w('Service', 'Сервис'), 'coffee' => t::w('Cafe', 'Кафе'), 'none' => t::w('Refuse', 'Бас тарту')][$menu_name];
?>
<? include "../../block/header.php"; ?>
	<div class="dsp">

	<? $o_cnt = $orders ? mysqli_num_rows($orders) : 0; ?>
	<div class="bp_page">

	<? if (!empty($item_id)): ?>
		<div class="bl_c ord_page">
			<a class="ord_back" href="javascript:history.length > 1 ? history.back() : location.href='/orders/dist/'"><i class="far fa-long-arrow-left"></i> <?=t::w('Orders', 'Тапсырыстар')?></a>
		</div>
	<? else: ?>
		<div class="bp_head">
			<div class="bl_c">

				<div class="bp_head_t">
					<div class="bp_cnt">
						<span><?=t::w('Orders count', 'Тапсырыс')?></span>
						<b class="pp_number"><?=$o_cnt?></b>
					</div>
				</div>

				<? if ($sort == 'new' || $sort == 'road' || $sort == 'history'): ?>
					<div class="bp_tabs"><span class="bp_tabs_sl"></span>
						<a class="bp_tab <?=($sort == 'new'?'bp_tab_act':'')?>" href="/orders/dist/?sort=new"><?=t::w('New', 'Жаңа')?></a>
						<a class="bp_tab <?=($sort == 'road'?'bp_tab_act':'')?>" href="/orders/dist/?sort=road"><?=t::w('On the way', 'Жолда')?></a>
						<a class="bp_tab <?=($sort == 'history'?'bp_tab_act':'')?>" href="/orders/dist/?sort=history"><?=t::w('Completed', 'Аяқталған')?></a>
					</div>
				<? elseif ($sort == 'myself' || $sort == 'myself_yes'): ?>
					<div class="bp_tabs"><span class="bp_tabs_sl"></span>
						<a class="bp_tab <?=($sort == 'myself'?'bp_tab_act':'')?>" href="/orders/dist/?sort=myself"><?=t::w('New', 'Жаңа')?></a>
						<a class="bp_tab <?=($sort == 'myself_yes'?'bp_tab_act':'')?>" href="/orders/dist/?sort=myself_yes"><?=t::w('Picked up', 'Тапсырылған')?></a>
					</div>
				<? elseif ($sort == 'service' || $sort == 'service_yes'): ?>
					<div class="bp_tabs"><span class="bp_tabs_sl"></span>
						<a class="bp_tab <?=($sort == 'service'?'bp_tab_act':'')?>" href="/orders/dist/?sort=service"><?=t::w('New', 'Жаңа')?></a>
						<a class="bp_tab <?=($sort == 'service_yes'?'bp_tab_act':'')?>" href="/orders/dist/?sort=service_yes"><?=t::w('Handed over', 'Берілген')?></a>
					</div>
				<? endif ?>

				<? if ($sort == 'road' || $sort == 'history'): ?>
					<? $flt_nm = t::w('All couriers', 'Барлық курьерлер'); $flt_list = [];
						$staff = db::query("select * from company_staff where positions_id = 6 and company_id = '$company'");
						while ($staff_d = mysqli_fetch_assoc($staff)) { $su = fun::user($staff_d['user_id']); if (!$su) continue; $flt_list[(int)$staff_d['user_id']] = (string)$su['name']; if (@$сourier_id == $staff_d['user_id']) $flt_nm = $su['name']; } ?>
					<div class="bp_pick bp_pick_flt on_pick_flt<?=(@$сourier_id != '' ? ' bp_flt_on' : '')?>">
						<span class="bp_flt_ic"><i class="far fa-user"></i></span>
						<span class="bp_flt_t"><small><?=t::w('By courier', 'Курьер бойынша')?></small><b><?=htmlspecialchars($flt_nm)?></b></span>
						<i class="far fa-chevron-down bp_flt_ar"></i>
					</div>
					<div class="bp_dr bp_flt_sh">
						<div class="bp_dr_bg"></div>
						<div class="bp_dr_c">
							<div class="bp_dr_grab"></div>
							<div class="bp_sh_h"><?=t::w('By courier', 'Курьер бойынша')?> <span class="bp_dr_x"><i class="far fa-times"></i></span></div>
							<div class="bp_list">
								<a class="bp_li <?=(@$сourier_id == '' ? 'bp_li_act' : '')?>" href="/orders/dist/?sort=<?=$sort?>">
									<span class="bp_li_ic"><i class="far fa-users"></i></span>
									<span class="bp_li_t"><?=t::w('All couriers', 'Барлық курьерлер')?></span>
									<i class="far fa-check bp_li_ok"></i>
								</a>
								<? foreach ($flt_list as $fid => $fnm): ?>
									<a class="bp_li <?=(@$сourier_id == $fid ? 'bp_li_act' : '')?>" href="/orders/dist/?sort=<?=$sort?>&staff=<?=$fid?>">
										<span class="bp_li_ic bp_li_av"><?=htmlspecialchars(mb_strtoupper(mb_substr(trim($fnm), 0, 1)))?></span>
										<span class="bp_li_t"><?=htmlspecialchars($fnm)?></span>
										<i class="far fa-check bp_li_ok"></i>
									</a>
								<? endforeach ?>
							</div>
						</div>
					</div>
				<? endif ?>

			</div>
		</div>

	<? endif ?>
		<div class="bl_c">

			<div class="uc_u">

				<? $st_clr = []; $stc_q = db::query("select id, clr from retail_orders_status"); while ($stc = mysqli_fetch_assoc($stc_q)) $st_clr[(int)$stc['id']] = $stc['clr']; // статус түсі базадан (курьер сайтындағыдай) ?>
				<? if ($orders != ''): ?>
					<? if (mysqli_num_rows($orders) != 0): ?>
						<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
							<? if ($buy_d['сourier_id']) $сourier_d = fun::user($buy_d['сourier_id']); ?>
							<? $branch_d = $buy_d['branch_id'] ? fun::branch($buy_d['branch_id']) : 0; $ot = (int)$buy_d['order_type']; // 1 Жеткізу, 2 Собой, 3 Сервис, 4 Кафе ?>

							<div class="uc_ui" style="--st: <?=htmlspecialchars(@$st_clr[(int)$buy_d['order_status']] ?: '#10a96a')?>">
								<div class="uc_uil2" >
									<div class="uc_uil2_top">
										<div class="uc_uil2_nmb"><?=htmlspecialchars((string)@$branch_d['code'])?><?=$buy_d['number']?></div>
										<div class="uc_uil2_date">
											<? $st_id = (int)$buy_d['order_status'];
												$st_nm = [1 => t::st(1), 8 => t::st(8), 2 => t::st(2), 11 => t::st(11), 3 => t::st(3), 9 => t::st(9), 4 => t::st(4), 5 => t::st(5), 6 => t::st(6), 10 => t::st(10)]; ?>
											<div class="uc_uil2_date1">
												<? if (isset($st_nm[$st_id]) && $st_id != 11): // «Курьер тағайындалды» белгісі шықпайды, курьер аты төменде ?><span class="bp_st bp_st<?=$st_id?>"><?=$st_nm[$st_id]?></span><? endif ?>
											</div>
											<div class="bp_tm"><b class="bp_time"><?=date("H:i", strtotime($buy_d['ins_dt']))?></b><i class="far fa-history bp_hist_ic on_hist" data-id="<?=$buy_d['id']?>" data-nm="<?=htmlspecialchars((string)@$branch_d['code'].$buy_d['number'])?>" title="<?=t::h('Status history', 'Статус тарихы')?>"></i><? if ($buy_d['preorder_dt']): ?> <b class="bp_pre"><i class="far fa-bell"></i> <?=htmlspecialchars($buy_d['preorder_dt'])?></b><? endif ?></div>
										</div>
										<? if ($sort == 'coffee' && in_array((int)$buy_d['order_status'], [1, 8, 2])): // Кафе: клиентке берілді → Столда ?>
											<div class="uc_uil2_chek">
												<div class="btn btn_cl on_table" data-id="<?=$buy_d['id']?>"><i class="far fa-utensils"></i> <?=t::w('At the table', 'Столда')?></div>
											</div>
										<? endif ?>
										<? if (in_array((int)$buy_d['order_status'], [1, 8, 2]) && ($sort == 'myself' || $sort == 'service')): // Собой: клиент алды → Аяқталды ?>
											<div class="uc_uil2_chek">
												<div class="btn btn_cl on_check" data-id="<?=$buy_d['id']?>"><i class="far fa-check"></i> <?=t::w('Hand over', 'Берілді')?></div>
											</div>
										<? endif ?>
									</div>
									<? if ($menu_name == 'car'): // Курьер: кассадағыдай блок, басқанда тізім астынан ашылады
										$cr_nm = $buy_d['сourier_id'] ? (string)@$сourier_d['name'] : '';
										$cr_can = in_array((int)$buy_d['order_status'], [1, 8, 2, 11]); ?>
										<div class="or_courier<?=($cr_can ? '' : ' cr_dis')?>">
											<div class="or_courier_l"><i class="far fa-motorcycle"></i><span><?=t::w('Courier', 'Курьер')?></span></div>
											<div class="cr_btn<?=($cr_can ? ' on_pick_staff' : '')?>" data-order-id="<?=$buy_d['id']?>" data-cur="<?=(int)$buy_d['сourier_id']?>"<?=((int)$buy_d['order_status'] == 3 ? ' title="'.t::h('Courier is on the way, cannot change', 'Курьер жолда, ауыстыруға болмайды').'"' : '')?>>
												<b><?=($cr_nm !== '' ? htmlspecialchars($cr_nm) : t::w('Choose', 'Таңдау'))?></b>
												<i class="far <?=($cr_can ? 'fa-chevron-down' : 'fa-lock')?>"></i>
											</div>
										</div>
									<? endif ?>
									<div class="uc_uil2_raz">
										<div class="uc_uil2_trt">
											<div class="uc_uil2_trt1"><?=t::w('Name', 'Атауы')?></div>
											<div class="uc_uil2_trt2"><?=t::w('Qty', 'Саны')?></div>
											<div class="uc_uil2_trt3"><?=t::w('Price', 'Бағасы')?></div>
										</div>
										<div class="uc_uil2_trc">

											<? 	
												$cashbox_id = $buy_d['id'];
												$cashboxp = db::query("select * from retail_orders_products where order_id = '$cashbox_id' order by ins_dt asc");
												$number = 0; $total = 0; $it_cnt = mysqli_num_rows($cashboxp); $it_fold = $it_cnt >= 3 && empty($item_id); // 3 не одан көп болса: 2 тағам көрінеді, қалғаны батырмамен
											?>
											<? if (mysqli_num_rows($cashboxp) != 0): ?>
												<? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
													<? 
														$number++; 
														$sum = $sel_d['quantity'] * $sel_d['price']; 
														$total = $total + $sum;
														$product_d = product::product($sel_d['product_id']);
													?>
													<div class="uc_uil2_trt<?=($it_fold && $number > 2 ? ' bp_more_i' : '')?>">
														<div class="uc_uil2_trt1"><?=$number?>. <?=$product_d['name_ru']?></div>
														<div class="uc_uil2_trt2"><?=$sel_d['quantity']?> <?=t::w('pcs', 'шт')?></div>
														<!-- <div class=""><?=$sel_d['price']?></div> -->
														<div class="uc_uil2_trt3 fr_price"><?=$sum?></div>
													</div>
												<? endwhile ?>
												<? if ($it_fold): ?>
													<div class="bp_more on_more" data-more="+<?=$it_cnt - 2?> <?=t::h('more', 'тағы')?>" data-less="<?=t::h('Hide', 'Жасыру')?>"><span>+<?=$it_cnt - 2?> <?=t::w('more', 'тағы')?></span><i class="far fa-chevron-down"></i></div>
												<? endif ?>
											<? endif ?>
											
											<? if ($ot == 1): ?>
											<div class="uc_uil2_trt bp_tr_dl">
												<div class="uc_uil2_trt1"><?=t::w('Delivery fee', 'Жеткізу')?></div>
												<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_delivery']?></div>
											</div>
											<? endif ?>
											<? if (!class_exists('promo')) require_once $_SERVER['DOCUMENT_ROOT'].'/config/promo.php'; $pr_p = promo::on() ? promo::of($buy_d) : null; if ($pr_p): // Акция жеңілдігі ?>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1"><i class="far fa-tags" style="color:#e9a23b"></i> <?=htmlspecialchars($pr_p['label'])?></div>
												<div class="uc_uil2_trt2">-</div>
												<div class="uc_uil2_trt3 fr_price"><?=$pr_p['sum']?></div>
											</div>
											<? endif ?>
										</div>
										<div class="uc_uil2_trb">
											<div class="uc_uil2_trt1"><?=t::w('Total', 'Жалпы')?></div>
											<div class="uc_uil2_trt2"></div>
											<div class="uc_uil2_trt3 fr_price"><?=$buy_d['total']?></div>
										</div>
										<? $pm_names = ['transfer' => t::w('Transfer', 'Аударым'), 'qr' => 'Kaspi QR', 'cash' => t::w('Cash', 'Қолма-қол'), 'red' => 'Red', 'mix' => t::w('Mixed', 'Аралас'), 'mixed' => t::w('Mixed', 'Аралас'), 'unpaid' => t::w('Unpaid', 'Төленбеген')]; ?>
										<? if ($ot == 1 || $ot == 2): // Кассадағыдай: предоплата және қалған нал сома ?>
										<? // Предоплата pay_prepay-де (кез келген түрмен); prepay_method бос болса — ескі тапсырыс, сомасы pay_qr-да (кассадағыдай)
											$pre_sum = (string)@$buy_d['prepay_method'] !== '' ? (int)$buy_d['pay_prepay'] : (int)$buy_d['pay_qr']; ?>
										<div class="uc_uil2_trc">
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1"><?=t::w('Prepayment', 'Алдын ала төлем')?><?=(@$pm_names[$buy_d['prepay_method']] ? ' ('.$pm_names[$buy_d['prepay_method']].')' : '')?></div>
												<div class="uc_uil2_trt2">-</div>
												<div class="uc_uil2_trt3 fr_price"><?=$pre_sum?></div>
											</div>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1"><b><?=($ot == 1 ? t::w('To courier (cash)', 'Курьерге (нал)') : t::w('On pickup', 'Алғанда төлейді'))?></b></div>
												<div class="uc_uil2_trt2"></div>
												<div class="uc_uil2_trt3 fr_price"><b><?=$buy_d['pay_cash']?></b></div>
											</div>
										</div>
										<? elseif ($ot == 4 && @$buy_d['payment_method']): ?>
										<div class="uc_uil2_trc">
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1"><?=t::w('Payment', 'Төлем')?></div>
												<div class="uc_uil2_trt2"></div>
												<div class="uc_uil2_trt3"><?=htmlspecialchars(@$pm_names[$buy_d['payment_method']] ?: $buy_d['payment_method'])?></div>
											</div>
										</div>
										<? endif ?>
									</div>
									<? $rf_r = trim((string)@$buy_d['refuse_reason']); $rf_k = (int)@$buy_d['kept_sum']; ?>
									<? if (in_array((int)$buy_d['order_status'], [5, 6, 10]) && ($rf_r !== '' || $rf_k > 0)): // Отказ: себебі және бізде қалған сома ?>
										<div class="uc_uil2_raz">
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1"><?=t::w('Reason', 'Себебі')?>:</div>
												<div class="uc_uil2_mi2"><?=($rf_r !== '' ? htmlspecialchars($rf_r) : '—')?></div>
											</div>
											<? if ($rf_k > 0): ?>
												<div class="uc_uil2_mi">
													<div class="uc_uil2_mi1"><?=t::w('Kept by us', 'Бізде қалды')?>:</div>
													<div class="uc_uil2_mi2 fr_price"><?=$rf_k?></div>
												</div>
											<? endif ?>
										</div>
									<? endif ?>
									<? if ($buy_d['address']): ?>
										<div class="uc_uil2_raz">
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1"><?=t::w('Operator', 'Оператор')?>:</div>
												<div class="uc_uil2_mi2"><?=(fun::user($buy_d['user_id']))['name']?></div>
											</div>
											<div class="uc_uil2_mi">
												<div class="uc_uil2_mi1"><?=t::w('Address', 'Мекенжай')?>:</div>
												<div class="uc_uil2_mi2"><?=htmlspecialchars($buy_d['address'])?></div>
											</div>
											<? $fa = trim((string)@$buy_d['floor_apt']); if ($fa !== ''): ?>
												<div class="uc_uil2_fa"><i class="far fa-building"></i> <?=htmlspecialchars(str_replace(', ', ' · ', $fa))?></div>
											<? endif ?>
											<? if ($menu_name == 'car' && $sort != 'history'): ?>
												<div class="uc_uil2_mib uc_uil2_mib1">
													<a class="btn btn_map" href="https://2gis.kz/<?=rawurlencode(trim((string)@$company_d['city']) ?: 'shymkent')?>/search/<?=rawurlencode($buy_d['address'])?>" target="_blank" rel="noopener"><i class="far fa-map-marked-alt"></i><span><?=t::w('Open on map', 'Картадан ашу')?></span><small>2GIS</small></a>
												</div>
											<? endif ?>
										</div>
									<? endif ?>



									<? if (empty($item_id)): ?>
									<div class="uc_uil2_raz uc_uil2_mib uc_uil2_mib1">
										<a class="btn btn_cl" href="/orders/dist/item.php?id=<?=$buy_d['id']?>"><?=t::w('Details', 'Толығырақ')?></a>
									</div>
									<? else: require_once __DIR__.'/hist.php'; // «Толығырақ»: толық мәлімет
										$i_rows = [
											t::w('Type', 'Түрі') => [1 => t::w('Delivery', 'Жеткізу'), 2 => t::w('Pickup', 'Өзімен'), 3 => t::w('Service', 'Сервис'), 4 => t::w('Cafe', 'Кафе')][(int)$buy_d['order_type']] ?? '',
											t::w('Branch', 'Точка') => (string)@$branch_d['name'],
											t::w('Client phone', 'Клиент нөмірі') => $buy_d['phone'] ? '8 '.preg_replace('/^(\d{3})(\d{3})(\d{2})(\d{2})$/', '($1) $2-$3-$4', preg_replace('/\D/', '', $buy_d['phone'])) : '',
											t::w('Preorder', 'Предзаказ') => (string)$buy_d['preorder_dt'],
											t::w('Payment', 'Төлем') => (string)(@$pm_names[$buy_d['payment_method']] ?: $buy_d['payment_method']),
											t::w('Notes', 'Қосымша') => trim(strip_tags((string)$buy_d['additional'])),
											t::w('Comment', 'Пікір') => trim(strip_tags((string)$buy_d['comment'])),
											t::w('Created', 'Жасалды') => date('H:i d.m.Y', strtotime($buy_d['ins_dt'])),
										]; ?>
									<div class="uc_uil2_raz">
										<div class="ord_box_h"><?=t::w('Full details', 'Толық мәлімет')?></div>
										<? foreach ($i_rows as $ik => $iv): if ($iv === '' || $iv === null) continue; ?>
											<div class="uc_uil2_mi"><div class="uc_uil2_mi1"><?=$ik?>:</div><div class="uc_uil2_mi2"><?=htmlspecialchars($iv)?></div></div>
										<? endforeach ?>
										<? $ph10 = substr(preg_replace('/\D/', '', (string)$buy_d['phone']), -10); if (strlen($ph10) == 10): ?>
											<div class="uc_uil2_mib">
												<a class="btn btn_phone" href="tel:8<?=$ph10?>"><i class="far fa-phone-alt"></i> <?=t::w('Call', 'Қоңырау')?></a>
												<a class="btn btn_whatsapp" href="https://wa.me/7<?=$ph10?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
											</div>
										<? endif ?>
									</div>
									<div class="uc_uil2_raz">
										<div class="ord_box_h"><?=t::w('History', 'Тарих')?></div>
										<?=dist_hist_html($buy_d)?>
									</div>
									<? endif ?>
								</div>
							</div>

							<? 
								$allorder['number'] = $allorder['number'] + 1;
								if ($buy_d['order_status'] != 5 && $buy_d['order_status'] != 6) {
									$allorder['pay_delivery'] = $allorder['pay_delivery'] + $buy_d['pay_delivery'] + 500;
								}
							?>

						<? endwhile ?>
					<? else: ?> <div class="ds_nr"><i class="fal fa-inbox"></i><p><?=t::w('No orders', 'Тапсырыс жоқ')?></p></div> <? endif ?>
				<? else: ?> <div class="ds_nr"><i class="fal fa-inbox"></i><p><?=t::w('No orders', 'Тапсырыс жоқ')?></p></div> <? endif ?>

			</div>

		</div>

	</div>




	<? if ($menu_name == 'car' && $sort != 'history'): ?>
	<div class="bp_dr bp_staff_sh">
		<div class="bp_dr_bg"></div>
		<div class="bp_dr_c">
			<div class="bp_dr_grab"></div>
			<div class="bp_sh_h"><?=t::w('Choose courier', 'Курьер таңдау')?> <span class="bp_dr_x"><i class="far fa-times"></i></span></div>
			<div class="bp_list">
				<div class="bp_li bp_staff_i bp_staff_off" data-id="off">
					<span class="bp_li_ic"><i class="far fa-user-times"></i></span>
					<span class="bp_li_t"><?=t::w('Remove courier', 'Курьерді алып тастау')?></span>
				</div>
				<? $staff = db::query("select * from company_staff where positions_id = 6 and company_id = '$company'"); ?>
				<? while ($staff_d = mysqli_fetch_assoc($staff)): $staff_user_d = fun::user($staff_d['user_id']); if (!$staff_user_d) continue; ?>
					<div class="bp_li bp_staff_i" data-id="<?=(int)$staff_d['user_id']?>">
						<span class="bp_li_ic bp_li_av"><?=htmlspecialchars(mb_strtoupper(mb_substr(trim((string)$staff_user_d['name']), 0, 1)))?></span>
						<span class="bp_li_t"><?=htmlspecialchars((string)$staff_user_d['name'])?></span>
						<i class="far fa-check bp_li_ok"></i>
					</div>
				<? endwhile ?>
			</div>
		</div>
	</div>
	<? endif ?>

	<div class="bp_dr bp_hist_sh">
		<div class="bp_dr_bg"></div>
		<div class="bp_dr_c">
			<div class="bp_dr_grab"></div>
			<div class="bp_sh_h"><span><?=t::w('History', 'Тарих')?> <b class="bp_hist_nm"></b></span> <span class="bp_dr_x"><i class="far fa-times"></i></span></div>
			<div class="oh_list"></div>
		</div>
	</div>

	</div>
<? include "../../block/footer.php"; ?>