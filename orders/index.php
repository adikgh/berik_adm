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
	$o_br_code = [];
	$obq = db::query("select * from company_branch where company_id = '$company' order by number asc");
	while ($ob = mysqli_fetch_assoc($obq)) { $o_branches[] = $ob; $o_br_code[$ob['id']] = (string)@$ob['code']; }
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
	if (!isset($st_colors_db)) { $st_colors_db = []; $stc_q = db::query("select name_ru, clr from retail_orders_status"); while ($stc = mysqli_fetch_assoc($stc_q)) $st_colors_db[trim($stc["name_ru"])] = $stc["clr"]; } $st_colors = $st_colors_db; // түстер базадан: retail_orders_status.clr

	// төлем түрлерінің аттары
	$pay_names = ['qr' => 'Kaspi QR', 'cash' => 'Қолма-қол', 'transfer' => 'Аударым', 'red' => 'RED', 'mixed' => 'Аралас', 'mix' => 'Аралас', 'unpaid' => t::w('Unpaid', 'Төленбеген')];

	// карточка үшін (кассадағыдай): түрлер, статустар, курьерлер, тарих кестесі
	$cmp = mysqli_fetch_assoc(db::query("select * from company where id = '".(int)$company."'"));
	$t_ico = [1 => 'fa-car', 2 => 'fa-walking', 3 => 'fa-backpack', 4 => 'fa-utensils'];
	$o_types = []; $otq = db::query("select * from retail_orders_type order by number asc");
	while ($o_t = mysqli_fetch_assoc($otq)) { $o_t['on'] = !$cmp || !isset($cmp[$o_t['nms']]) || $cmp[$o_t['nms']] == 1; $o_types[] = $o_t; }
	$st_all = []; $q_st = db::query("select * from retail_orders_status order by number asc"); while ($r_st = mysqli_fetch_assoc($q_st)) $st_all[] = $r_st;
	$o_couriers = []; $ocq = db::query("select * from company_staff where positions_id = 6 and company_id = '$company'");
	while ($oc = mysqli_fetch_assoc($ocq)) { $ocu = fun::user($oc['user_id']); $o_couriers[] = ['id' => $oc['user_id'], 'name' => (string)@$ocu['name'], 'phone' => (string)@$ocu['phone']]; }
	$or_lg_on = (bool)mysqli_num_rows(db::query("SHOW TABLES LIKE 'retail_orders_log'"));

	$allorder = ['count' => 0, 'total' => 0, 'pay_qr' => 0, 'pay_cash' => 0, 'pay_transfer' => 0, 'pay_delivery' => 0, 'pay_red' => 0, 'pay_prepay' => 0];


	// site setting
	$menu_name = 'orders';
	$pod_menu_name = 'main';
	$page_name = 'Тапсырыстар';
	$page_key = 'Orders';
	$site_set['view_dt'] = 1;
	$css = ['orders', 'orcard'];
	$js = ['orders', 'orcard'];
?>
<? include "../block/header.php"; ?>

	<div class="flex_clm_rev">

		<div class="bl_c">

			<div class="uc_u adm_or">

				<? if ($orders && mysqli_num_rows($orders) != 0): ?>
					<? while ($buy_d = mysqli_fetch_assoc($orders)): ?>
						<?
							$order_sts = fun::order_sts($buy_d['order_status']);
							$st_name = trim(@$order_sts['name_ru']);
							$st_clr = $st_colors[$st_name] ?? ($st_colors[trim(@$order_sts['name_kz'])] ?? @$order_sts['clr']);
							$ot = (int)$buy_d['order_type']; // 1 Жеткізу, 2 Өзімен, 3 Сервис, 4 Кафе

							$pay_red = intval($buy_d['pay_red'] ?? 0);
							$pay_transfer = intval($buy_d['pay_transfer'] ?? 0);
							$pay_prepay = intval($buy_d['pay_prepay'] ?? 0);
							$service = $buy_d['service'] ?? '';
							$pm = $buy_d['payment_method'];
							$is_draft = ($buy_d['number'] === null || $buy_d['number'] === '');
							$is_closed = in_array((int)$buy_d['order_status'], [4, 5, 6, 10]);
						?>

						<div class="uc_ui or_card or_t<?=$ot?><?=($is_draft?' or_draft':'')?><?=($pm == 'unpaid' ? ' or_unpaid_c' : '')?>" data-oid="<?=$buy_d['id']?>" style="--st:<?=$st_clr?>;">
							<div class="uc_uil2">
								<div class="uc_uil2_top">
									<? if ($is_draft): ?>
										<div class="uc_uil2_nmb or_draft_nmb" title="<?=t::w('Not saved', 'Сақталмаған')?>"><i class="fal fa-pen"></i></div>
									<? else: ?>
										<div class="uc_uil2_nmb"><?=htmlspecialchars((string)@$o_br_code[$buy_d['branch_id']])?><?=$buy_d['number']?></div>
									<? endif ?>
									<div class="uc_uil2_topc">
										<div class="uc_uil2_date or_meta">
											<? // Предзаказ: негізгі уақыт — қабылданған уақыт (тарихтағы «Жасалды»), жанында таймер + предзаказ уақыты
												$or_acc = $buy_d['ins_dt']; $or_pre = (string)@$buy_d['preorder_dt']; $or_acc_ok = false;
												if ($or_pre !== '' && $or_lg_on) { $or_lg = mysqli_fetch_assoc(db::query("select ins_dt from retail_orders_log where order_id = '".(int)$buy_d['id']."' and field = 'created' order by id asc limit 1")); if ($or_lg) { $or_acc = $or_lg['ins_dt']; $or_acc_ok = true; } }
												$or_pre_ts = $or_pre !== '' ? strtotime($or_pre) : 0; ?>
											<div class="or_dt<?=($or_pre !== '' && !$or_acc_ok ? ' or_dt_pre' : '')?>"<?=($or_pre !== '' ? ' title="'.t::w('Preorder', 'Предзаказ').'"' : '')?>><div class="or_tm_r"><b class="or_time"><?=date("H:i", strtotime($or_acc))?></b><? if ($or_acc_ok): ?><span class="or_pre_tm" title="<?=t::w('Preorder', 'Предзаказ')?>"><i class="far fa-stopwatch"></i><?=date("H:i", $or_pre_ts)?><? if (date('Y-m-d', $or_pre_ts) != date('Y-m-d', strtotime($or_acc))): ?><small><?=date("d.m", $or_pre_ts)?></small><? endif ?></span><? endif ?></div><span class="or_date"><?=date("d-m-Y", strtotime($or_acc))?></span></div>
											<span class="or_meta_r"><i class="far fa-history or_hist" data-id="<?=$buy_d['id']?>" title="<?=t::w('Status history', 'Статус тарихы')?>"></i></span>
										</div>
									</div>
								</div>
								<div class="uc_uil2_topc1 or_sel">
									<div class="or_type">
										<select class="on_type on_stype" data-order-id="<?=$buy_d['id']?>" <?=($is_draft ? 'disabled' : '')?>>
											<? foreach ($o_types as $o_t): if (!$o_t['on'] && (int)$o_t['id'] != $ot) continue; ?>
												<option data-id="<?=$o_t['id']?>" data-ico="<?=@$t_ico[(int)$o_t['id']]?>" <?=((int)$o_t['id'] == $ot ? 'selected' : '')?> value=""><?=t::w('type:'.$o_t['name'], $o_t['name'])?></option>
											<? endforeach ?>
										</select>
									</div>
									<div class="or_status" style="background-color:<?=$st_clr?>;">
										<? if ($is_draft): ?>
											<select class="on_status" disabled><option><?=t::w('Not saved', 'Сақталмаған')?></option></select>
										<? else: // Admin: кез келген статусты қоя алады (аяқталған, жолдағы да) ?>
										<select class="on_status" data-order-id="<?=$buy_d['id']?>" title="<?=t::w('Change status (admin)', 'Статусты өзгерту (admin)')?>">
											<? foreach ($st_all as $s_o): $s_id = (int)$s_o['id']; $s_cur = $s_id == (int)$buy_d['order_status']; if (!$s_cur && ($s_id == 7 || !in_array((string)$ot, explode(',', (string)$s_o['types'])))) continue; $s_clr = $st_colors[trim($s_o['name_ru'])] ?? $s_o['clr']; ?>
												<option data-id="<?=$s_id?>" data-clr="<?=htmlspecialchars((string)$s_clr)?>" <?=(in_array($s_id, [5, 6, 10]) ? 'data-refuse="1"' : '')?> <?=($s_id == 10 ? 'data-kept="'.(int)($pay_prepay > 0 ? $pay_prepay : $buy_d['pay_qr']).'"' : '')?> <?=($s_cur ? 'selected' : '')?> value=""><?=htmlspecialchars(t::w('st:'.trim($s_o['name_ru']), $s_o['name_kz'] ?: $s_o['name_ru']))?></option>
											<? endforeach ?>
										</select>
										<? endif ?>
									</div>
								</div>
								<? if ($ot == 1): // Жеткізу: курьер ?>
									<div class="or_courier">
										<div class="or_courier_l"><i class="far fa-motorcycle"></i><span><?=t::w('Courier', 'Курьер')?></span></div>
										<div class="uc_uil2_date1">
											<select class="on_staff" data-order-id="<?=$buy_d['id']?>" <?=($is_draft ? 'disabled' : '')?>>
												<option value="" data-id="" <?=(!$buy_d['сourier_id'] ? 'selected' : '')?>><?=t::w('Courier: none', 'Курьер: таңдалмаған')?></option>
												<? foreach ($o_couriers as $oc): ?>
													<option value="" data-id="<?=$oc['id']?>" data-name="<?=htmlspecialchars($oc['name'])?>" data-phone="<?=htmlspecialchars((string)$oc['phone'])?>" <?=($buy_d['сourier_id'] == $oc['id'] ? 'selected' : '')?>><?=htmlspecialchars($oc['name'])?></option>
												<? endforeach ?>
											</select>
										</div>
									</div>
									<? if ($buy_d['сourier_id'] && !$is_draft && !in_array((int)$buy_d['order_status'], [5, 6, 10])): ?><div class="or_cpay"><?=fun::cpay_block($buy_d)?></div><? endif ?>
								<? endif ?>
								<? if ($ot == 3 && $service): ?>
								<div class="uc_uil2_raz">
									<div class="uc_uil2_mi">
										<div class="uc_uil2_mi1"><?=t::w('Service:', 'Сервис:')?></div>
										<div class="uc_uil2_mi2"><?=htmlspecialchars($service)?></div>
									</div>
								</div>
								<? endif ?>
								<div class="uc_uil2_raz or_items">
									<div class="uc_uil2_trt">
										<div class="uc_uil2_trt1"><?=t::w('Name', 'Атауы')?></div>
										<div class="uc_uil2_trt2"><?=t::w('Qty', 'Саны')?></div>
										<div class="uc_uil2_trt3"><?=t::w('Price', 'Бағасы')?></div>
									</div>
									<div class="uc_uil2_trc">
										<?
											$cashbox_id = $buy_d['id'];
											$cashboxp = db::query("select * from retail_orders_products where order_id = '$cashbox_id' order by ins_dt asc");
											$number = 0; $it_cnt = mysqli_num_rows($cashboxp); $it_fold = $it_cnt > 3 && $ot != 4; // 3-еуге дейін бәрі көрінеді
										?>
										<? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
											<? $number++; $product_d = product::product($sel_d['product_id']); ?>
											<div class="uc_uil2_trt<?=($it_fold && $number > 2 ? ' or_more_i' : '')?>">
												<div class="uc_uil2_trt1"><?=$number?>. <?=$product_d['name_ru']?></div>
												<div class="uc_uil2_trt2"><?=$sel_d['quantity']?> шт</div>
												<div class="uc_uil2_trt3 fr_price"><?=$sel_d['quantity'] * $sel_d['price']?></div>
											</div>
										<? endwhile ?>
										<? if ($it_fold): ?>
										<div class="or_more" data-more="+<?=$number - 2?> <?=t::w('more', 'тағы')?>" data-less="<?=t::w('Hide', 'Жасыру')?>"><span>+<?=$number - 2?> <?=t::w('more', 'тағы')?></span><i class="far fa-chevron-down"></i></div>
										<? endif ?>
										<? if ($ot == 1): ?>
										<div class="uc_uil2_trt">
											<div class="uc_uil2_trt1"><?=t::w('Delivery fee', 'Доставка')?></div>
											<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_delivery']?></div>
										</div>
										<? endif ?>
										<? if ($pay_red): ?>
										<div class="uc_uil2_trt">
											<div class="uc_uil2_trt1">RED</div>
											<div class="uc_uil2_trt3 fr_price"><?=$pay_red?></div>
										</div>
										<? endif ?>
									</div>
									<div class="or_paybox">
										<div class="uc_uil2_trb">
											<div class="uc_uil2_trt1"><?=t::w('Total', 'Жалпы')?></div>
											<div class="uc_uil2_trt2"></div>
											<div class="uc_uil2_trt3 fr_price"><?=$buy_d['total']?></div>
										</div>
										<div class="uc_uil2_trc">
											<? if ($ot == 3): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=t::w('Payment', 'Төлем')?></div>
													<div class="uc_uil2_trt2"></div>
													<div class="uc_uil2_trt3"><?=($service ? htmlspecialchars($service) : t::w('Service', 'Сервис'))?></div>
												</div>
											<? else: ?>
												<? if ($ot == 1 || $ot == 2 || $pay_prepay): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=t::w('Prepayment', 'Предоплата')?><?=(@$buy_d['prepay_method'] ? ' ('.($pay_names[$buy_d['prepay_method']] ?? htmlspecialchars($buy_d['prepay_method'])).')' : '')?></div>
													<div class="uc_uil2_trt2">-</div>
													<div class="uc_uil2_trt3 fr_price"><?=($pay_prepay ?: (int)$buy_d['pay_qr'])?></div>
												</div>
												<? endif ?>
												<? if ($ot == 1 || $ot == 2): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=($ot == 1 ? t::w('To courier (cash)', 'Курьерге (нал)') : t::w('On pickup', 'Алғанда төлейді'))?></div>
													<div class="uc_uil2_trt2"></div>
													<div class="uc_uil2_trt3 fr_price"><?=$buy_d['pay_cash']?></div>
												</div>
												<? endif ?>
												<? if ($pay_transfer): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=t::w('Transfer', 'Аударым')?></div>
													<div class="uc_uil2_trt2"></div>
													<div class="uc_uil2_trt3 fr_price"><?=$pay_transfer?></div>
												</div>
												<? endif ?>
												<? if ($pm): ?>
												<div class="uc_uil2_trt">
													<div class="uc_uil2_trt1"><?=t::w('Payment', 'Төлем')?></div>
													<div class="uc_uil2_trt2"></div>
													<div class="uc_uil2_trt3"><?=($pay_names[$pm] ?? htmlspecialchars($pm))?></div>
												</div>
												<? endif ?>
											<? endif ?>
										</div>
									</div>
									<? $rf_r = trim((string)@$buy_d['refuse_reason']); $rf_k = (int)@$buy_d['kept_sum'];
									if (in_array((int)$buy_d['order_status'], [5, 6, 10]) && ($rf_r !== '' || $rf_k > 0)): // Отказ: себебі және бізде қалған сома ?>
									<div class="or_refuse">
										<span class="or_rf_ic"><i class="far fa-ban"></i></span>
										<div class="or_rf_tx">
											<small><?=t::w('Refusal reason', 'Отказ себебі')?></small>
											<b><?=($rf_r !== '' ? htmlspecialchars($rf_r) : '—')?></b>
											<? if ($rf_k > 0): ?><span class="or_rf_k"><i class="far fa-wallet"></i><?=t::w('Kept by us', 'Бізде қалды')?>: <b><?=number_format($rf_k, 0, '', ' ')?> тг</b></span><? endif ?>
										</div>
									</div>
									<? endif ?>
								</div>
								<? $show_addr = $ot == 1 && trim((string)$buy_d['address']) !== ''; $show_ph = trim((string)$buy_d['phone']) !== ''; $show_add = trim((string)$buy_d['additional']) !== ''; ?>
								<? if ($show_addr || $show_ph || $show_add): ?>
								<div class="uc_uil2_raz or_addr">
									<div class="oa_box">
										<? if ($show_addr): $fa = trim((string)@$buy_d['floor_apt']); ?>
										<div class="oa_ad"><i class="far fa-map-marker-alt oa_ic"></i><div class="oa_txt"><b><?=htmlspecialchars($buy_d['address'])?></b><? if ($fa !== ''): ?><small><?=htmlspecialchars(str_replace(', ', ' · ', $fa))?></small><? endif ?></div></div>
										<? endif ?>
										<? if ($show_ph): ?>
										<div class="oa_ph"><i class="far fa-phone-alt oa_ic"></i><b class="fr_phone"><?=htmlspecialchars($buy_d['phone'])?></b></div>
										<? endif ?>
										<? if ($show_add): ?>
										<div class="oa_ph oa_note"><i class="far fa-comment-alt-lines oa_ic"></i><div class="oa_txt"><b><?=htmlspecialchars($buy_d['additional'])?></b></div></div>
										<? endif ?>
									</div>
								</div>
								<? endif ?>
							</div>
						</div>

						<?
							// «Отказ» (5) есепке кірмейді; 6, 10 — тек бізде қалған ақша
							if (!$is_draft && in_array((int)$buy_d['order_status'], [6, 10])) $allorder['total'] += (int)$buy_d['kept_sum'];
							elseif (!$is_draft && $buy_d['order_status'] != 5) {
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
				<div class="k_br or_ftp">
					<a class="k_br_i <?=(!$f_type?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['type' => 0])))?>"><i class="far fa-layer-group"></i><span><?=t::w('Type: all', 'Барлық формат')?></span></a>
					<? foreach ($o_types as $o_t): if (!$o_t['on'] && $f_type != $o_t['id']) continue; ?>
						<a class="k_br_i <?=($f_type == $o_t['id']?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['type' => $o_t['id']])))?>" title="<?=htmlspecialchars(t::w('type:'.$o_t['name'], $o_t['name']))?>"><i class="far <?=@$t_ico[(int)$o_t['id']]?>"></i><span><?=t::w('type:'.$o_t['name'], $o_t['name'])?></span></a>
					<? endforeach ?>
				</div>
				<div class="">
					<select name="status" class="on_sort_status">
						<option data-id="" value=""><?=t::w('Status: all', 'Барлық статус')?></option>
						<? $orders_status = db::query("select * from retail_orders_status order by number asc"); ?>
						<? while ($orders_status_d = mysqli_fetch_assoc($orders_status)): ?>
							<? if (trim($orders_status_d['name_ru']) == 'Вопрос' && $f_status != $orders_status_d['id']) continue; // базада қалады, тізімде шықпайды ?>
							<option data-id="<?=$orders_status_d['id']?>" data-clr="<?=htmlspecialchars((string)($st_colors[trim($orders_status_d['name_ru'])] ?? $orders_status_d['clr']))?>" <?=($f_status == $orders_status_d['id']?'selected':'')?> value="" ><?=t::w('st:'.trim($orders_status_d['name_ru']), $orders_status_d['name_kz'] ?: $orders_status_d['name_ru'])?></option>
						<? endwhile ?>
					</select>
				</div>
				<div class="">
					<select name="staff" class="on_sort_staff">
						<option data-id="" value=""><?=t::w('Courier: all', 'Барлық курьер')?></option>
						<option data-id="off" <?=($f_staff == 'off'?'selected':'')?> value=""><?=t::w('Courier: none', 'Таңдалмаған')?></option>
						<? $staff = db::query("select * from company_staff where positions_id = 6 and company_id = '$company'"); ?>
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
