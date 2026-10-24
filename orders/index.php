<? include "../config/core.php";

	//
	if (!$user_id) { header('location: /'); exit(); }
	if ($user_right['positions_id'] == 3) { header('location: /kassa/'); exit(); }


	// сүзгілер (бәрі бір WHERE-ге жиналады)
	$f_status = intval(@$_GET['status']);
	$f_type = intval(@$_GET['type']);
	$f_staff = @$_GET['staff'];

	$where = "ins_dt BETWEEN '$start_cdate' and '$end_cdate' and company_id = '$company'";
	if ($f_status) $where .= " and order_status = '$f_status'";
	if ($f_type) $where .= " and order_type = '$f_type'";
	if (@$_GET['by_ubd']) $where .= " and ubd = 1";
	$f_branch = intval(@$_GET['branch']);
	if ($f_branch) $where .= " and branch_id = '$f_branch'";
	$o_branches = [];
	$obq = db::query("select id, name from company_branch where company_id = '$company' order by number asc");
	while ($ob = mysqli_fetch_assoc($obq)) $o_branches[] = $ob;
	if ($f_staff == 'off') $where .= " and order_type = 1 and сourier_id is null";
	elseif ($f_staff == 'soboi') $where .= " and order_type = 2";
	elseif ($f_staff) $where .= " and сourier_id = '".intval($f_staff)."'";

	// ұрылмаған (нөмірі жоқ) заказдар ең басында тұрады
	$orders = db::query("select * from retail_orders where $where order by (number is null) desc, number desc, id desc");


	// заказ түрлері (кассадағыдай кодтар)
	$types = [
		1 => ['name' => 'Жеткізу', 'key' => 'Жеткізу', 'icon' => 'fa-car'],
		2 => ['name' => 'Өзімен',  'key' => 'Собой',   'icon' => 'fa-shopping-bag'],
		3 => ['name' => 'Сервис',  'key' => 'Сервис',  'icon' => 'fa-motorcycle'],
		4 => ['name' => 'Кафе',    'key' => 'Кофе',    'icon' => 'fa-utensils'],
	];

	// статус түстері (кассадағыдай), тізімде жоқ болса базадағы түс
	$st_colors = ['Жаңа'=>'#1f75a8', 'Дайын'=>'#e9b949', 'Курьерде'=>'#d9480f', 'Аяқталған'=>'#0EA768', 'Отказ'=>'#e5484d', 'Отказ при получение'=>'#e86fa0', 'Вопрос'=>'#9aa0a6'];

	// төлем түрлерінің аттары
	$pay_names = ['qr' => 'Kaspi QR', 'cash' => 'Қолма-қол', 'transfer' => 'Аударым', 'red' => 'RED', 'mixed' => 'Аралас', 'mix' => 'Аралас'];

	$allorder = ['count' => 0, 'total' => 0, 'pay_qr' => 0, 'pay_cash' => 0, 'pay_transfer' => 0, 'pay_delivery' => 0, 'pay_red' => 0, 'pay_prepay' => 0];


	// site setting
	$menu_name = 'orders';
	$pod_menu_name = 'main';
	$page_name = 'Тапсырыстар';
	$page_key = 'Orders';
	$site_set['view_dt'] = 1;
	$css = ['orders'];
	$js = ['orders'];
?>
<? include "../block/header.php"; ?>

	<div class="flex_clm_rev">

		<div class="bl_c">

			<div class="uc_u">

				<? if ($orders && mysqli_num_rows($orders) != 0): ?>
					<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
						<?
							$order_sts = fun::order_sts($buy_d['order_status']);
							$st_name = trim(@$order_sts['name_ru']);
							$st_clr = $st_colors[$st_name] ?? ($st_colors[trim(@$order_sts['name_kz'])] ?? @$order_sts['clr']);
							$type_d = $types[$buy_d['order_type']] ?? $types[1];

							$pay_red = intval($buy_d['pay_red'] ?? 0);
							$pay_transfer = intval($buy_d['pay_transfer'] ?? 0);
							$pay_prepay = intval($buy_d['pay_prepay'] ?? 0);
							$prepay_method = $buy_d['prepay_method'] ?? '';
							$service = $buy_d['service'] ?? '';
							$pm = $buy_d['payment_method'];
							$is_future = strtotime($buy_d['ins_dt']) > time() + 600;
							$is_draft = ($buy_d['number'] === null || $buy_d['number'] === '');
						?>

						<div class="uc_ui or_card <?=($is_draft?'or_draft':'')?>" style="--st:<?=$st_clr?>;">
							<div class="uc_uil2" >
								<div class="uc_uil2_top">
									<? if ($is_draft): ?>
										<div class="uc_uil2_nmb or_draft_nmb" title="Әлі сақталмаған"><i class="fal fa-pen"></i></div>
										<div class="or_status or_draft_st"><?=t::w('Not saved', 'Сақталмаған')?></div>
									<? else: ?>
										<div class="uc_uil2_nmb" style="background:<?=$st_clr?>"><?=$buy_d['number']?></div>
										<div class="or_status" style="background-color:<?=$st_clr?>;"><?=t::w('st:'.$st_name, $order_sts['name_kz'] ?: $st_name)?></div>
									<? endif ?>
								</div>

								<div class="or_meta">
									<div class="or_tag"><i class="fal <?=$type_d['icon']?>"></i><span><?=t::w('type:'.$type_d['key'], $type_d['name'])?><?=($service?': '.htmlspecialchars($service):'')?></span></div>
									<? if ($is_future || $buy_d['preorder_dt']): ?>
										<div class="or_tag or_tag_pre"><i class="fal fa-calendar-alt"></i><span>Предзаказ<?=($buy_d['preorder_dt']?' '.htmlspecialchars($buy_d['preorder_dt']):'')?></span></div>
									<? endif ?>
								</div>

								<div class="uc_uil2_date">
									<div class=""><i class="fal fa-clock"></i> <?=date("d.m.Y H:i", strtotime($buy_d['ins_dt']))?></div>
								</div>
								<? if ($buy_d['upd_dt']): ?>
									<div class="uc_uil2_date or_upd">
										<div class=""><i class="fal fa-pen"></i> <?=date("d.m.Y H:i", strtotime($buy_d['upd_dt']))?></div>
									</div>
								<? endif ?>

								<? if ($buy_d['order_type'] == 1): ?>
									<div class="uc_uil2_raz">
										<div class="uc_uil2_date1">
											<? if ($buy_d['сourier_id']): $сourier_d = fun::user($buy_d['сourier_id']); ?>
												<i class="far fa-user"></i> <?=$сourier_d['name']?> <span class="fr_phone"><?=$сourier_d['phone']?></span>
											<? else: ?>
												<i class="far fa-car"></i> <?=t::w('Courier not chosen', 'Курьер таңдалмаған')?>
											<? endif ?>
										</div>
										<? if ($buy_d['сourier_id'] && !$is_draft && !in_array($buy_d['order_status'], [5, 6])) echo fun::cpay_block($buy_d); ?>
									</div>
								<? endif ?>

								<? if ($buy_d['address'] || $buy_d['phone'] || $buy_d['additional']): ?>
									<div class="uc_uil2_raz">
										<? if ($buy_d['address']): ?>
											<div class="uc_uil2_mi"><div class="uc_uil2_mi1">Адрес:</div> <div class="uc_uil2_mi2"><?=htmlspecialchars($buy_d['address'])?></div></div>
										<? endif ?>
										<? if ($buy_d['phone']): ?>
											<div class="uc_uil2_mi"><div class="uc_uil2_mi1">Номер:</div> <div class="uc_uil2_mi2 fr_phone"><?=htmlspecialchars($buy_d['phone'])?></div></div>
										<? endif ?>
										<? if ($buy_d['additional']): ?>
											<div class="uc_uil2_mi"><div class="uc_uil2_mi1">Қосымша:</div> <div class="uc_uil2_mi2"><?=htmlspecialchars($buy_d['additional'])?></div></div>
										<? endif ?>
									</div>
								<? endif ?>

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
											$number = 0;
										?>
										<? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
											<? $number++; $product_d = product::product($sel_d['product_id']); ?>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1"><?=$number?>. <?=$product_d['name_ru']?></div>
												<div class="uc_uil2_trt2"><?=$sel_d['quantity']?> шт</div>
												<div class="uc_uil2_trt3 fr_price"><?=$sel_d['quantity'] * $sel_d['price']?></div>
											</div>
										<? endwhile ?>

										<? if ($buy_d['pay_delivery']): ?>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1">Жеткізу</div>
												<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_delivery']?></div>
											</div>
										<? endif ?>
										<? if ($pay_red): ?>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1">RED (қызмет)</div>
												<div class="uc_uil2_trt3 fr_price"><?=$pay_red?></div>
											</div>
										<? endif ?>
									</div>
									<div class="uc_uil2_trb">
										<div class="uc_uil2_trt1">Жалпы</div>
										<div class="uc_uil2_trt2"></div>
										<div class="uc_uil2_trt3 fr_price"><?=$buy_d['total']?></div>
									</div>

									<div class="uc_uil2_trc or_pay">
										<? if ($buy_d['order_type'] == 3): ?>
											<div class="uc_uil2_trt">
												<div class="uc_uil2_trt1">Төлем</div>
												<div class="uc_uil2_trt3"><?=($service?htmlspecialchars($service):'Сервис')?> арқылы</div>
											</div>
										<? else: ?>
											<? if ($pay_prepay): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1">Предоплата<?=($prepay_method?' ('.($pay_names[$prepay_method] ?? $prepay_method).')':'')?></div>
													<div class="uc_uil2_trt3 fr_price"><?=$pay_prepay?></div>
												</div>
											<? endif ?>
											<? if ($pay_transfer): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1">Аударым</div>
													<div class="uc_uil2_trt3 fr_price"><?=$pay_transfer?></div>
												</div>
											<? endif ?>
											<? if ($buy_d['pay_qr']): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1">Kaspi QR</div>
													<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_qr']?></div>
												</div>
											<? endif ?>
											<? if ($buy_d['pay_cash']): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=($buy_d['order_type'] == 1?'Курьерге (нал)':'Қолма-қол')?></div>
													<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_cash']?></div>
												</div>
											<? endif ?>
											<? if ($pm): ?>
												<div class="uc_uil2_trt or_pm">
													<div class="uc_uil2_trt1">Төлем түрі</div>
													<div class="uc_uil2_trt3"><?=($pay_names[$pm] ?? htmlspecialchars($pm))?></div>
												</div>
											<? endif ?>
										<? endif ?>
									</div>
								</div>
							</div>
						</div>

						<?
							// отказ (5, 6) есепке кірмейді
							if (!$is_draft && $buy_d['order_status'] != 5 && $buy_d['order_status'] != 6) {
								$allorder['count']++;
								$allorder['total'] += $buy_d['total'];
								$allorder['pay_qr'] += $buy_d['pay_qr'];
								$allorder['pay_cash'] += $buy_d['pay_cash'];
								$allorder['pay_transfer'] += $pay_transfer;
								$allorder['pay_delivery'] += $buy_d['pay_delivery'];
								$allorder['pay_red'] += $pay_red;
								$allorder['pay_prepay'] += $pay_prepay;
							}
						?>

					<? endwhile ?>
				<? else: ?> <div class="ds_nr"><i class="fal fa-inbox"></i><p><?=t::w('No orders this day', 'Бұл күні тапсырыс жоқ')?></p></div> <? endif ?>

			</div>

		</div>

		<div class="bl_c">

			<div class="uc_ui69">
				<div class="">
					<input class="form_dt on_sort_time" type="date" value="<?=date('Y-m-d', strtotime("$start_cdate"))?>">
				</div>
				<? if (count($o_branches) >= 2): ?>
				<div class="k_br">
					<a class="k_br_i <?=(!$f_branch?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['branch' => 0])))?>"><i class="fal fa-store"></i><span><?=t::w('Branch: all', 'Барлық точка')?></span></a>
					<? foreach ($o_branches as $ob): ?>
						<a class="k_br_i <?=($f_branch == $ob['id']?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['branch' => $ob['id']])))?>"><span><?=htmlspecialchars($ob['name'])?></span></a>
					<? endforeach ?>
				</div>
				<? endif ?>
				<div class="">
					<select class="on_sort_type">
						<option data-id="" value=""><?=t::w('Type: all', 'Барлық формат')?></option>
						<? foreach ($types as $tid => $t): ?>
							<option data-id="<?=$tid?>" <?=($f_type == $tid?'selected':'')?> value=""><?=t::w('type:'.$t['key'], $t['name'])?></option>
						<? endforeach ?>
					</select>
				</div>
				<div class="">
					<select name="status" class="on_sort_status">
						<option data-id="" value=""><?=t::w('Status: all', 'Барлық статус')?></option>
						<? $orders_status = db::query("select * from retail_orders_status order by number asc"); ?>
						<? while ($orders_status_d = mysqli_fetch_assoc($orders_status)): ?>
							<? if (trim($orders_status_d['name_ru']) == 'Вопрос' && $f_status != $orders_status_d['id']) continue; // базада қалады, тізімде шықпайды ?>
							<option data-id="<?=$orders_status_d['id']?>" <?=($f_status == $orders_status_d['id']?'selected':'')?> value="" ><?=t::w('st:'.trim($orders_status_d['name_ru']), $orders_status_d['name_kz'] ?: $orders_status_d['name_ru'])?></option>
						<? endwhile ?>
					</select>
				</div>
				<div class="">
					<select name="staff" class="on_sort_staff">
						<option data-id="" value=""><?=t::w('Courier: all', 'Барлық курьер')?></option>
						<option data-id="off" <?=($f_staff == 'off'?'selected':'')?> value=""><?=t::w('Courier: none', 'Таңдалмаған')?></option>
						<? $staff = db::query("select * from user_staff where positions_id = 6 and company_id = '$company'"); ?>
						<? while ($staff_d = mysqli_fetch_assoc($staff)): ?>
							<? $staff_user_d = fun::user($staff_d['user_id']); ?>
							<option data-id="<?=$staff_d['user_id']?>" <?=($f_staff == $staff_d['user_id']?'selected':'')?> value=""><?=$staff_user_d['name']?></option>
						<? endwhile ?>
					</select>
				</div>
			</div>

			<div class="or_sum">
				<div class="or_sum_i or_sum_main"><span><?=t::w('Total', 'Жалпы')?> (<?=$allorder['count']?>)</span><b class="fr_price"><?=$allorder['total']?></b></div>
				<div class="or_sum_i"><span>Kaspi QR</span><b class="fr_price"><?=$allorder['pay_qr']?></b></div>
				<div class="or_sum_i"><span><?=t::w('Cash', 'Қолма-қол')?></span><b class="fr_price"><?=$allorder['pay_cash']?></b></div>
				<? if ($allorder['pay_transfer']): ?><div class="or_sum_i"><span><?=t::w('Transfer', 'Аударым')?></span><b class="fr_price"><?=$allorder['pay_transfer']?></b></div><? endif ?>
				<? if ($allorder['pay_delivery']): ?><div class="or_sum_i"><span><?=t::w('Delivery', 'Жеткізу')?></span><b class="fr_price"><?=$allorder['pay_delivery']?></b></div><? endif ?>
				<? if ($allorder['pay_red']): ?><div class="or_sum_i"><span>RED</span><b class="fr_price"><?=$allorder['pay_red']?></b></div><? endif ?>
				<? if ($allorder['pay_prepay']): ?><div class="or_sum_i"><span><?=t::w('Prepayment', 'Предоплата')?></span><b class="fr_price"><?=$allorder['pay_prepay']?></b></div><? endif ?>
			</div>

		</div>

	</div>

<? include "../block/footer.php"; ?>
