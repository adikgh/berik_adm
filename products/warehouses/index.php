<? include "../../config/core.php";

	// 
	if (!$user_id) header('location: /');

	// 
	$catalog = db::query("select * from product_warehouses order by number asc limit 50");
	$filter = 0;


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'warehouses';
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['footer'] = false;
	$css = [];
	$js = [];
?>
<? include "../../block/header.php"; ?>

	<div class="">

		<!-- a header -->
		<? include "../aheader.php"; ?>

		<? if (mysqli_num_rows($catalog) != 0): ?>

			<!-- <div class="">
				<div class="">
					<div class="btn"><i class="fal fa-plus"></i><span>Добавить категория</span></div>
				</div>
			</div> -->

			<!-- list -->
			<div class="uc_u">
				<div class="uc_us">
					<div class="uc_usn">
						<input type="text" placeholder="Поиск" class="sub_user_search_in">
						<i class="fal fa-search form_icon"></i>
					</div>
				</div>
				<div class="uc_uh">
					<div class="uc_uh2">
						<div class="uc_uh_number">#</div>
						<div class="uc_uh_name">Название</div>
						<div class="uc_uh_other">Тип</div>
						<!-- <div class="uc_uh_other">Коды</div> -->
						<!-- <div class="uc_uh_other">Цветы</div> -->
					</div>
					<!-- <div class="uc_uh_cn"></div> -->
				</div>
				<div class="uc_uc">
					<? while ($buy_d = mysqli_fetch_assoc($catalog)): ?>
						<? $sum++; ?>
						<div class="uc_ui">
							<div class="uc_uil">
								<div class="uc_ui_number"><?=$buy_d['number']?></div>
								<a class="uc_uiln" href="/products/warehouses.php?id=<?=$buy_d['id']?>">
									<div class="uc_ui_icon lazy_img" data-src="/assets/uploads/products/<?=$buy_d['img']?>"><?=($buy_d['img']!=null?'':'<i class="fal fa-warehouse-alt"></i>')?></div>
									<div class="uc_uinu">
										<div class="uc_ui_name"><?=$buy_d['name']?></div>
									</div>
								</a>
								<div class="uc_uin_other"><?=$buy_d['type']?></div>
								<!-- <div class="uc_uin_other"><?=$buy_d['code']?></div> -->
							</div>
							<!-- <div class="uc_uin_cn"><div class="btn btn_dd " data-id="<?=$buy_d['id']?>"><i class="fal fa-trash-alt"></i></div></div> -->
						</div>
					<? endwhile ?>
				</div>
			</div>

		<? else: ?> <div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div> <? endif ?>

	</div>

<? include "../../block/footer.php"; ?>