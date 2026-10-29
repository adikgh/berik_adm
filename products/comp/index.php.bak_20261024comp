<? include "../../config/core.php";

	// 
	if (!$user_id) header('location: /');


	// filter
	// if (@$_GET['on'] == 1) $product_all = db::query("select * from product where arh = 0");
	// elseif (@$_GET['off'] == 1) $product_all = db::query("select * from product where arh = 0");
	// elseif (@$_GET['catalog']) {
	// 	$catalog_id = $_GET['catalog'];
	// 	$product_all = db::query("select * from product where catalog_id = '$catalog_id' and arh = 0");
	// } elseif (@$_GET['brand']) {
	// 	$brand_id = $_GET['brand'];
	// 	$product_all = db::query("select * from product where brand_id = '$brand_id' and arh = 0");
	// } else 
	$product_all = db::query("select * from product_comp where company_id = '$company'");
	$page_result = mysqli_num_rows($product_all);

	// page number
	$page = 1; if (@$_GET['page'] && is_int(intval($_GET['page']))) $page = $_GET['page'];
	$page_age = 100;
	$page_all = ceil($page_result / $page_age);
	if ($page > $page_all) $page = $page_all;
	$page_start = ($page - 1) * $page_age;
	$number = $page_start;

	// filter
	// if (@$_GET['on'] == 1) $product = db::query("select * from product where arh = 0 order by ins_dt desc limit $page_start, $page_age");
	// elseif (@$_GET['off'] == 1) $product = db::query("select * from product where arh = 0 order by ins_dt desc limit $page_start, $page_age");
	// elseif (@$_GET['catalog']) $product = db::query("select * from product where catalog_id = '$catalog_id' and arh = 0 order by ins_dt desc limit $page_start, $page_age");
	// elseif (@$_GET['brand']) $product = db::query("select * from product where brand_id = '$brand_id' and arh = 0 order by ins_dt desc limit $page_start, $page_age");
	// else 
	if ($page_result > 0) {
		$product = db::query("select * from product_comp where company_id = '$company' order by name_kz asc limit $page_start, $page_age");
	}


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'comp';
	// $site_set['footer'] = false;
	$css = ['products/main'];
	$js = ['products/main'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c">

			<!--  -->
		   	<div class="ucours_t">
				<div class="ucours_tl">
					<!-- <div class="btn btn_cl product_add_pop"><i class="fal fa-plus"></i><span>Быстрое добавление</span></div> -->
					<div class="ucours_tm">
						<div class="btn btn_cl ">
						<i class="fal fa-plus"></i>
						<span>Добавить товар</span>
						</div>
					</div>
      			</div>
   			</div>
		
			<br>

      <!-- a header -->
		<? include "../aheader.php"; ?>



		<? if ($page_result): ?>
			
			<div class="uc_u">
				<div class="uc_us">
					<div class="form_im uc_usn">
						<input type="text" placeholder="Поиск" class="">
						<i class="fal fa-search form_icon"></i>
					</div>
				</div>

				<div class="tscroll">
					<div class="uc_u2q uc_uc">
						<div class="uc_uh">
							<div class="uc_uhn">
								<div class="uc_uh_number">#</div>
								<div class="uc_uh_other">Наименование</div>
								<div class="uc_uh_other">Категория</div>
								<div class="uc_uh_other">Цена</div>
							</div>
							<div class="uc_uh_cn"></div>
						</div>
						<div class="uc_uc">
							<? while ($pr_d = mysqli_fetch_assoc($product)): ?>
								<? $number++; ?>
								
								<div class="uc_ui uc_ui2">
									<div class="uc_uil">
										<div class="uc_ui_number"><?=$number?></div>
										<div class="uc_uin_other"><?=$pr_d['name_kz']?></div>
										<div class="uc_uin_other"><? if ($pr_d['catalog_id']): ?> <div><?=product::pr_catalog_name($pr_d['catalog_id'], $lang)?></div> <? endif ?></div>
										<div class="uc_uin_other"><?=($pr_d['price']==0?'<m>Цена не указана</m>':$pr_d['price'].' тг')?></div>
									</div>
									<div class="uc_uib"></div>
								</div>
							<? endwhile ?>
						</div>
					</div>
				</div>
				<div class="uc_u2qm  uc_uc dsp_n"></div>
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
			<div class="ds_nr">
				<i class="fal fa-ghost"></i>
				<p>НЕТ</p>
			</div>
		<? endif ?>

	</div>

<? include "../../block/footer.php"; ?>

	<!--  -->
	<div class="pop_bl pop_bl2    ">
      <div class="pop_bl_a    "></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить товар</h4>
            <div class="btn btn_dd    "><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <div class="form_c">
               <div class="form_head">Основные:</div>
               <div class="form_im">
                  <div class="form_span">Наименование товара:</div>
                  <input type="text" class="form_txt pr_name" placeholder="Введите наименование" data-lenght="1">
                  <i class="fal fa-text form_icon"></i>
               </div>
               <div class="form_im">
                  <div class="form_span">Цена:</div>
                  <input type="tel" class="form_txt fr_price pr_price" placeholder="0" data-lenght="1">
                  <i class="fal fa-tenge form_icon"></i>
               </div>
               <div class="form_im form_sel">
                  <div class="form_span">Категория товара:</div>
                  <!-- <i class="fal fa-inventory form_icon"></i> -->
                  <div class="form_im_txt sel_clc pr_catalog" data-val="">Выберите категорию</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <? $catalog = db::query("select * from product_catalog where company_id = '$company' and type = comp"); ?>
                     <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                        <div class="form_im_seli" data-val="<?=$catalog_d['id']?>"><?=$catalog_d['name_ru']?></div>
                     <? endwhile ?>
                  </div>
               </div>
            </div>

            <div class="form_c">
               <div class="form_im">
                  <div class="btn product_add"><span>Добавить</span></div>
               </div>
            </div>

         </div>
      </div>
   </div>