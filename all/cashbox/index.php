<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');



	if (@$_GET['id']) {
		$cashbox_id = $_GET['id'];
		$cashbox = db::query("select * from retail_orders where id = '$cashbox_id'");
	} else $cashbox = db::query("select * from retail_orders where user_id = '$user_id' and paid = 0 order by id desc limit 1");
	if (mysqli_num_rows($cashbox)) {
		$cashbox_d = mysqli_fetch_assoc($cashbox);
		$cashbox_id = $cashbox_d['id'];
	} else {
		$cashbox_id = (mysqli_fetch_assoc(db::query("SELECT * FROM `retail_orders` order by id desc")))['id'] + 1;
		$ins = db::query("INSERT INTO `retail_orders`(`id`, `user_id`) VALUES ('$cashbox_id', '$user_id')");
	}
	$cashboxp = db::query("select * from retail_orders_products where order_id = '$cashbox_id' order by ins_dt asc");
	$number = 0; $total = 0;




	// 
	$catalog_id = 1; if (@$_SESSION['catalog_id']) $catalog_id = $_SESSION['catalog_id'];


	// site setting
	$menu_name = 'cashbox';
	$site_set['swiper'] = true;
	$css = ['cashbox'];
	$js = ['cashbox'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="hup_bl">

			<div class="hup_r">
				<div class="">
					<div class="hup_rct">
						<? $catalog = db::query("select * from product_catalog where arh is null order by number asc"); ?>
						<? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
							<div class="hup_rcti catalog_ubd" data-cashbox-id="<?=$cashbox_id?>" data-id="<?=$catalog_d['id']?>"  style="background:<?=$catalog_d['color']?>"><?=$catalog_d['name_'.$lang]?></div>
						<? endwhile ?>
					</div>
				</div>
				<div class="">
					<div class="hup_rcb">
						<? // $product = db::query("select * from product where catalog_id = '$catalog_id' order by number asc"); ?>
						<? $product = db::query("select * from product where catalog_id = '$catalog_id'"); ?>
						<? while ($product_d = mysqli_fetch_assoc($product)): ?>
							<div class="hup_rcbi cashbox_add" data-oid="<?=$cashbox_id?>" data-id="<?=$product_d['id']?>">
								<div class=""><?=$product_d['name_'.$lang]?></div>
								<div class=""><?=$product_d['price']?> тг</div>
							</div>
						<? endwhile ?>
					</div>
				</div>
			</div>

			<div class="cash_bl1">

				<div class="cash_bl1_l">
					<div class="uc_uh">
						<div class="uc_uh2">
							<div class="uc_uiln"Наименование>Атауы</div>
							<div class="uc_uil">
								<div class="uc_uilf1">
									<div class="uc_uilf1c">
										<div class="uc_uh_other"Количество>Бағасы (цена) x cаны</div>
									</div>
								</div>
								<div class="uc_uilf2"></div>
							</div>
						</div>
						<!-- <div class="uc_uh_cn"></div> -->
					</div>
				</div>

				<div class="cash_bl1_r">
					<div class="cash_bl1_rc">
						<div class="uc_u <?=($view_pr?'uc_u2':'')?>">
							<div class="uc_uc lazy_c">
								<? if (mysqli_num_rows($cashboxp) != 0): ?>
									<? while ($sel_d = mysqli_fetch_assoc($cashboxp)): ?>
										<? $number++; $sum = $sel_d['quantity'] * $sel_d['price']; $total = $total + $sum; ?>
										<? $product_d = product::product($sel_d['product_id']); ?>
	
										<div class="uc_ui uc_ui2" data-id="<?=$sel_d['id']?>" data-item-id="<?=$product_d['id']?>" data-pr="<?=$sel_d['price']?>" data-qn="<?=$sel_d['quantity']?>" data-sum="<?=$sum?>">
											<div class="uc_uiln">
												<!-- <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=$pitem_d['img']?>">
													<?=($pitem_d['img']!=null?'':'<i class="fal fa-box"></i>')?>
												</div> -->
												<div class="uc_uinu">
													<div class="uc_ui_name"><?=$number?>. <?=$product_d['name_ru']?></div>
												</div>
											</div>
											<div class="uc_uil">
												<div class="uc_uilf1">
													<div class="uc_uilf1c" data-qn="<?=$sel_d['quantity']?>">
														<!-- <div class="uc_uin_other">
															<input type="tel" class="uc_uin_calc_q cashbox_pr" value="<?=$sel_d['price']?>" data-lenght="1" />
														</div> -->
														<!-- <div class="uc_uin_other" data-max="<?=$quantity?>">
															<input type="tel" class="uc_uin_calc_q fr_number3 cashbox_qn" value="<?=$sel_d['quantity']?>" data-lenght="1" />
														</div> -->
														<div class="uc_uin_other fr_price cashbox_pr"><?=$sel_d['price']?></div>
														<div class="uc_uin_other cashbox_qn">х <?=$sel_d['quantity']?> шт</div>
													</div>
													<div class="uc_uin_other fr_price cashbox_sum"><?=$sum?></div>
												</div>
												<div class="uc_uilf2">
													<div class="uc_uin_cn cashbox_plus" data-id="<?=$sel_d['id']?>"><i class="far fa-plus"></i></div>
													<? if ($sel_d['quantity'] == 1): ?>
														<div class="uc_uin_cn cashbox_remove" data-id="<?=$sel_d['id']?>"><i class="far fa-trash-alt"></i></div>
														<div class="uc_uin_cn cashbox_minus dsp_n" data-id="<?=$sel_d['id']?>"><i class="far fa-minus"></i></div>
													<? else: ?>
														<div class="uc_uin_cn cashbox_remove dsp_n" data-id="<?=$sel_d['id']?>"><i class="far fa-trash-alt"></i></div>
														<div class="uc_uin_cn cashbox_minus " data-id="<?=$sel_d['id']?>"><i class="far fa-minus"></i></div>
													<? endif ?>

												</div>
											</div>
										</div>
									<? endwhile ?>
								<? else: ?> 
									<div class="ds_nr" Пустой список><p>Бос тізім</p></div>
								<? endif ?>
							</div>
						</div>
					</div>
				</div>

				<div class="cash_bl1_rb <?=($total==0?'dsp_n':'')?>">
					<div class="cash_bl1_rbl">
						<div class="cash_bl1_rblin">Қорытынды:</div>
						<div class="cash_bl1_rblip cashbox_total fr_price" data-total="<?=$total?>"><?=$total?></div>
					</div>
					<div class="cash_bl1_rbr">
						<div class="btn cashbox_pay" data-id="<?=$cashbox_id?>">Сақтау</div>
					</div>
				</div>

			</div>

		</div>

	</div>

<? include "../block/footer.php"; ?>

	<!--  -->
	<div class="pop_bl pop_bl2 cashbox_pay_block">
		<div class="pop_bl_a cashbox_pay_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4>Сақтау</h4>
				<div class="btn btn_dd cashbox_pay_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="form_c">

					<div class="">
						<div class="form_im ">
							<div class="form_span">Нөмір:</div>
							<input type="tel" class="form_txt fr_phone btype_phone" placeholder="8 (700) 000-00-00" data-val="0">
							<i class="fal fa-phone-alt form_icon"></i>
						</div>
					</div>

					<br>
					
					<div class="">
						<div class="form_im ">
							<div class="form_span">Мекен-жай (адрес):</div>
							<input type="text" class="form_txt btype_address" placeholder="" data-val="0">
							<i class="fal fa-text form_icon"></i>
						</div>
					</div>

					<br>

					<div class="">
						<div class="form_im ">
							<div class="form_span">Қосымша қажет болса:</div>
							<input type="text" class="form_txt btype_add" placeholder="Мысалы: Замена пицца или суши" data-val="0">
							<i class="fal fa-text form_icon"></i>
						</div>
					</div>

					<br>

					<div class="">
						<div class="form_im ">
							<div class="form_span">Алдын-ала тапсырыс (предзаказ):</div>
							<input type="text" class="form_txt btype_preorder" placeholder="" data-val="0">
							<i class="fal fa-clock form_icon"></i>
						</div>
					</div>

					<br>

					<div class="">
						<div class="form_im ">
							<div class="form_span">Жеткізу (доставка):</div>
							<input type="tel" class="form_txt fr_price btype_delivery" placeholder="0" data-val="0">
							<i class="fal fa-tenge form_icon"></i>
						</div>
					</div>

					<br>

					<div class="form_im">
						<!-- <div class="form_span">Тип оплаты:</div> -->
						<div class="form_im_slo " data-type-name="RED" data-nall="">
							<div class="form_im_slo_i btype_red" form_im_slo_act data-type="10">RED</div>
						</div>
					</div>

					<br><br>
					
					<div class="cashbox_pay_btotol">
						<div class="form_span">Жалпы ақшасы:</div>
						<div class="cashbox_pay_btotol_c fr_price" data-val="<?=$total?>" data-on-val="<?=$total?>"><?=$total?></div>
					</div>

					<div class="cashbox_pay_btype">
						<div class="form_im">
							<div class="form_span">Алдын ала төлем (предоплата):</div>
							<input type="tel" class="form_txt fr_price btype_qr" placeholder="0" value="" data-val="">
							<i class="fal fa-tenge form_icon"></i>
						</div>
					</div>
					
					<div class="cashbox_pay_bsem">
						<div class="form_im">
							<div class="form_span" Наличный>Қолма-қол:</div>
							<div class="cashbox_pay_bsemc btype_cash fr_price"><?=$total?></div>
						</div>
						<!-- <div class="form_im">
							<div class="form_span" Итог>Қорытынды:</div>
							<div class="cashbox_pay_bsemc cashbox_pay_bsemt fr_price"><?=$total?></div>
						</div> -->
					</div>

					<div class="form_im">
						<!-- <div class="btn cashbox_pay2" data-id="<?=$cashbox_id?>">Продать</div> -->
						<div class="btn btn_cl cashbox_pay2 pay_print" data-id="<?=$cashbox_id?>" data-nm="<?=$cashbox_d['number']?>" data-type="check" Продать и распечатать чек>Сату және чек шығару</div>
					</div>
				</div>

			</div>
		</div>
	</div>

