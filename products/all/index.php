<? include "../../config/core.php";

	// 
	if (!$user_id) header('location: /');


	// filter
	if ($_GET['on'] == 1) $product_all = db::query("select * from product_item where arh = 0");
	elseif ($_GET['off'] == 1) $product_all = db::query("select * from product_item where arh = 0");
	elseif ($_GET['catalog']) {
		$catalog_id = $_GET['catalog'];
		$product_all = db::query("select * from product_item where catalog_id = '$catalog_id' and arh = 0");
	} elseif ($_GET['brand']) {
		$brand_id = $_GET['brand'];
		$product_all = db::query("select * from product_item where brand_id = '$brand_id' and arh = 0");
	}
	else $product_all = db::query("select * from product_item where arh = 0");
	$page_result = mysqli_num_rows($product_all);

	// page number
	$page = 1; if ($_GET['page'] && is_int(intval($_GET['page']))) $page = $_GET['page'];
	$page_age = 50;
	$page_all = ceil($page_result / $page_age);
	if ($page > $page_all) $page = $page_all;
	$page_start = ($page - 1) * $page_age;
	$number = $page_start;

	// filter
	if ($_GET['on'] == 1) $product = db::query("select * from product_item where arh = 0 order by ins_dt desc limit $page_start, $page_age");
	elseif ($_GET['off'] == 1) $product = db::query("select * from product_item where arh = 0 order by ins_dt desc limit $page_start, $page_age");
	elseif ($_GET['catalog']) $product = db::query("select * from product_item where catalog_id = '$catalog_id' and arh = 0 order by ins_dt desc limit $page_start, $page_age");
	elseif ($_GET['brand']) $product = db::query("select * from product_item where brand_id = '$brand_id' and arh = 0 order by ins_dt desc limit $page_start, $page_age");
	else $product = db::query("select * from product_item where arh = 0 order by ins_dt desc limit $page_start, $page_age");


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'all';
	// $site_set['footer'] = false;
	$css = ['products/main'];
	$js = ['products/main', 'products/item'];
?>
<? include "../../block/header.php"; ?>

	<div class="sss">

      <!-- a header -->
		<? include "../aheader.php"; ?>

		<? // include "../sort-filter.php"; ?>
		
		<? if ($page_result): ?>
			
			<div class="uc_u">
				<div class="uc_us">
					<div class="form_im uc_usn">
						<input type="text" placeholder="Поиск" class="product_item_search">
						<i class="fal fa-search form_icon"></i>
					</div>
				</div>
				<div class="tscroll">
					<table class="uc_u2q uc_uc">
						<thead class="">
							<tr class="thead">
								<td class="td_number">#</td>
								<td class="td_img">Фото</td>
								<td class="td_br"></td>
								<td class="td_name">Наименование</td>
								<td class="td_other">Штрих и артикул</td>
								<td class="td_other">Цвет</td>
								<td class="td_other">Размер</td>
								<td class="td_other">Склад</td>
								<td class="td_other">Цены</td>
								<td class="td_other">Количество</td>
								<td class="uc_uh_cn"></td>
							</tr>
						</thead>
						<tbody class="tbody">
							<? while ($item_d = mysqli_fetch_assoc($product)): ?>
								<? $number++; $item_id = $item_d['id']; ?>
								<? $pr_d = product::product($item_d['product_id']); ?>
								<? $view = db::query("select * from product_item_quantity where item_id = '$item_id'"); ?>
								<? if (mysqli_num_rows($view) == 1) $view_d = mysqli_fetch_assoc($view); else $view_d = null; ?>
		
								<tr class="uc_ui uc_ui2">
									<!-- <div class="uc_uil"> -->
										<td class="td_number"><div class="uc_ui_number"><?=$number?></div></td>
										<td class="td_img">
											<div class="uc_ui_img lazy_img" data-src="https://admin.lighterior.kz/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
										</td>
										<td class="td_br"></td>
										<td class="td_name">
											<a class="" href="/products/item/?id=<?=$pr_d['id']?>">
												<div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
											</a>
										</td>
										<td class="td_other">
											<div class="tc_code">
												<? if ($item_d['article']): ?> <div class="tc_code1"><?=$item_d['article']?></div> <? endif ?>
												<? if ($item_d['barcode']): ?> <div class="tc_code2"><?=$item_d['barcode']?></div> <? endif ?>
											</div>
										</td>
										<td class="td_other">
											<div class=""><?=(product::pr_color($item_d['color_id']))['name_ru']?></div>
										</td>
										<td class="td_other">
											<div class=""><?=(product::pr_size($item_d['size_id']))['name']?></div>
										</td>
										<td class="td_other"><div class="uc_uin_other"><?=product::product_warehouses($pr_d['id'])?></div></td>
										<td class="td_other">
											<div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_price item_upd_pr" data-id="<?=$item_d['id']?>" value="<?=$item_d['price']?>" data-lenght="1" /></div>
										</td>
										<td class="td_other">
											<? if ($view_d): ?> <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_number3 view_updq_qn" data-id="<?=$view_d['id']?>" value="<?=$view_d['quantity']?>" data-lenght="1" /></div>
											<? else: ?> <div class="uc_uin_other pitem_updq_pop cursor_p fr_number3" data-id="<?=$item_id?>"><?=product::pr_item_quantity($item_id)?></div> <? endif ?>
										</td>
										<!-- <div class="uc_uin_other">
											<? if ($item_d['price']): ?> <div class="fr_price"><?=$item_d['price']?></div>
											<? else: ?> <m>Цена не указана</m> <? endif ?>
										</div> -->
										<!-- <div class="uc_uin_other " product_quantity_add_pop data-id="<?=$pr_d['id']?>"><?=product::product_quantity($pr_d['id'])?> шт</div> -->
									<!-- </div> -->
										<td class="uc_uib">
											<div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
											<div class="menu_c uc_uibs">
												<a class="menu_ci" target="_blank" href="/products/item/?id=<?=$pr_d['id']?>">
													<div class="menu_cin"><i class="fal fa-external-link"></i></div>
													<div class="menu_cih">Открыть товар</div>
												</a>
												<a class="menu_ci" target="_blank" href="/barcode/?code=<?=$item_d['barcode']?>&price=<?=$item_d['price']?>&name=<?=$pr_d['name_ru']?>">
													<div class="menu_cin"><i class="fal fa-print"></i></div>
													<div class="menu_cih">Печатать штрих код</div>
												</a>
												<div class="menu_ci product2_add_pop" data-id="<?=$pr_d['id']?>">
													<div class="menu_cin"><i class="fal fa-clone"></i></div>
													<div class="menu_cih">Дублировать товар</div>
												</div>
												<!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
													<div class="menu_cin"><i class="fal fa-handshake"></i></div>
													<div class="menu_cih">Выставить на продажу</div>
												</div> -->
												<!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
													<div class="menu_cin"><i class="fal fa-archive"></i></div>
													<div class="menu_cih">Архивировать товар</div>
												</div> -->
												<div class="menu_ci uc_uib_del pitem_btn_delete" data-title2="Удалить товар" data-id="<?=$pr_d['id']?>">
													<div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
													<div class="menu_cih">Удалить товар</div>
												</div>
											</div>
										</td>
								</tr>
							<? endwhile ?>
						</tbody>
					</table>
					<table class="uc_u2qm uc_uc dsp_n">
						<thead class="">
							<tr class="thead">
								<td class="td_number">#</td>
								<td class="td_img">Фото</td>
								<td class="td_br"></td>
								<td class="td_name">Наименование</td>
								<td class="td_other">Штрих и артикул</td>
								<td class="td_other">Цвет</td>
								<td class="td_other">Размер</td>
								<td class="td_other">Склад</td>
								<td class="td_other">Цены</td>
								<td class="td_other">Количество</td>
								<!-- <div class="uc_uh_cn"></div> -->
							</tr>
						</thead>
						<tbody class="tbody"></tbody>
					</table>
				</div>
				<!-- <div class="uc_u2qm uc_uc dsp_n"></div> -->
			</div>

			<? if ($page_all > 1): ?>
				<div class="uc_p">
					<? if ($page > 1): ?> <a class="uc_pi" href="<?=$url_page?>?&page=<?=$page-1?>"><i class="fal fa-angle-left"></i></a> <? endif ?>
					<a class="uc_pi <?=($page==1?'uc_pi_act':'')?>" href="<?=$url_page?>?&page=1">1</a>
					<? for ($pg = 2; $pg < $page_all; $pg++): ?>
						<? if ($pg == $page - 1): ?>
							<? if ($page - 1 != 2): ?> <div class="uc_pi uc_pi_disp">...</div> <? endif ?>
							<a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_page?>?&page=<?=$pg?>"><?=$pg?></a>
						<? endif ?>
						<? if ($pg == $page): ?> <a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_page?>?&page=<?=$pg?>"><?=$pg?></a> <? endif ?>
						<? if ($pg == $page + 1): ?>
							<a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_page?>?&page=<?=$pg?>"><?=$pg?></a>
							<? if ($page + 1 != $page_all - 1): ?> <div class="uc_pi uc_pi_disp">...</div> <? endif ?>
						<? endif ?>
					<? endfor ?>
					<a class="uc_pi <?=($page==$page_all?'uc_pi_act':'')?>" href="<?=$url_page?>?&page=<?=$page_all?>"><?=$page_all?></a>
					<? if ($page < $page_all): ?> <a class="uc_pi" href="<?=$url_page?>?&page=<?=$page+1?>"><i class="fal fa-angle-right"></i></a> <? endif ?>
				</div>
			<? endif ?>

		<? else: ?>
			<div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div>
		<? endif ?>

	</div>

<? include "../../block/footer.php"; ?>
	<? include "../pop_add.php"; ?>