<?php include "../../config/core.php";

	// 
	if (!$user_id) header('location: /');

	// 
	$product = db::query("select * from product order by ins_dt desc limit 50");
	$filter = 0;


	// site setting
	$menu_name = 'remains';
	$pod_menu_name = 'remains';
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['footer'] = false;
	$css = ['products/main'];
	$js = ['products/main'];
?>
<? include "../../block/header.php"; ?>

	<div class="">

      <!-- a header -->
		<? include "../aheader.php"; ?>

		<? if (mysqli_num_rows($product) != 0): ?>


			<!-- list -->
			<div class="uc_u">
				<div class="uc_us">
               <div class="form_im uc_usn">
                  <input type="text" placeholder="Поиск" class="sub_user_search_in">
                  <i class="fal fa-search form_icon"></i>
               </div>
				</div>
				<div class="uc_uh">
					<div class="uc_uh2">
						<div class="uc_uh_number">#</div>
						<div class="uc_uh_name">Наименование</div>
						<div class="uc_uh_other">Артикул</div>
						<div class="uc_uh_other">Цена продажи</div>
						<div class="uc_uh_other">Количество</div>
					</div>
					<div class="uc_uh_cn"></div>
				</div>
				<div class="uc_uc">
					<? while ($pr_d = mysqli_fetch_assoc($product)): ?>
						<? $sum++; ?>

						<div class="uc_ui">
							<div class="uc_uil">
                        <div class="uc_ui_number"><?=$sum?></div>
                        <a class="uc_uiln" href="/admin/products/item/?id=<?=$pr_d['id']?>">
                           <div class="uc_ui_icon lazy_img" data-src="/assets/uploads/products/<?=fun::product_img($pr_d['id'])?>"><?=(fun::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                           <div class="uc_uinu">
                              <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                              <div class="uc_ui_phone"><?=fun::pr_catalog_name($pr_d['catalog_id'], $lang)?></div>
                           </div>
                        </a>
								<div class="uc_uin_other"><?=fun::product_article($pr_d['id'])?></div>
								<div class="uc_uin_other"><?=fun::product_price($pr_d['id'])?> тг</div>
								<div class="uc_uin_other " product_quantity_add_pop data-id="<?=$pr_d['id']?>"><?=$pr_d['quantity']?> шт</div>
							</div>
							<div class="uc_uib">
								<div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
								<div class="menu_c uc_uibs">
									<a class="menu_ci" target="_blank" href="/admin/products/item/?id=<?=$pr_d['id']?>">
										<div class="menu_cin"><i class="fal fa-external-link"></i></div>
										<div class="menu_cih">Открыть товар</div>
									</a>
									<div class="menu_ci " data-id="<?=$pr_d['id']?>">
										<div class="menu_cin"><i class="fal fa-archive"></i></div>
										<div class="menu_cih">Архивировать товар</div>
									</div>
									<div class="menu_ci uc_uib_del pr_btn_delete" data-title2="Удалить товар" data-id="<?=$pr_d['id']?>">
										<div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
										<div class="menu_cih">Удалить товар</div>
									</div>
								</div>
							</div>
						</div>
					<? endwhile ?>
				</div>
			</div>

		<? else: ?>
			<div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div>
			<div class="">
				<div class="">
					<div class="btn"><i class="fal fa-plus"></i><span>Добавить товар</span></div>
				</div>
			</div>
		<? endif ?>

	</div>

<? include "../../block/footer.php"; ?>


   <!--  -->
   <div class="pop_bl pop_bl2 product_add_block">
      <div class="pop_bl_a product_add_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить товар</h4>
            <div class="btn btn_dd product_add_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <div class="form_c">
               <div class="form_head">Общие данные:</div>
               <div class="form_im">
                  <div class="form_span">Артикул товара:</div>
                  <i class="fal fa-barcode form_icon"></i>
                  <input type="text" class="form_im_txt pr_article" placeholder="Введите артикул" data-lenght="3">
               </div>
               <div class="form_im">
                  <div class="form_span">Штрих-код:</div>
                  <i class="fal fa-barcode form_icon"></i>
                  <input type="tel" class="form_im_txt pr_barcode" placeholder="Сканируйте код" data-lenght="8">
               </div>
               <div class="form_im">
                  <div class="form_span">Наименование товара:</div>
                  <i class="fal fa-text form_icon"></i>
                  <input type="text" class="form_im_txt pr_name" placeholder="Введите наименование" data-lenght="2">
               </div>
               <div class="form_im form_sel">
                  <div class="form_span">Категория товара:</div>
                  <i class="fal fa-inventory form_icon"></i>
                  <div class="form_im_txt sel_clc pr_catalog" data-val="">Выберите категорию</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <? $catalog = db::query("select * from catalog"); ?>
                     <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                        <div class="form_im_seli" data-val="<?=$catalog_d['id']?>"><?=$catalog_d['name_ru']?></div>
                     <? endwhile ?>
                  </div>
               </div>
               <div class="form_im">
                  <div class="form_span">Количество:</div>
                  <i class="fal fa-hashtag form_icon"></i>
                  <input type="tel" class="form_im_txt fr_number pr_quantity" placeholder="0" data-lenght="1">
               </div>
               <div class="form_im">
                  <div class="form_span">Цена продажи:</div>
                  <i class="fal fa-tenge form_icon"></i>
                  <input type="tel" class="form_im_txt fr_price pr_price" placeholder="0" data-lenght="1">
               </div>
               <div class="form_im form_im_toggle">
                  <div class="form_span">Доп. цены:</div>
                  <input type="checkbox" class="info_inp" data-val="" />
                  <div class="form_im_toggle_btn price1_clc"></div>
               </div>
               <div class="price1_bl">
                  <!-- <div class="form_head">Цена товара:</div> -->
                  <div class="form_im">
                     <div class="form_span">Закупочная цена:</div>
                     <i class="fal fa-tenge form_icon"></i>
                     <input type="tel" class="form_im_txt fr_price pr_purchase_price" placeholder="0" data-lenght="1">
                  </div>
                  <div class="form_im">
                     <div class="form_span">Скидочная цена:</div>
                     <i class="fal fa-tenge form_icon"></i>
                     <input type="tel" class="form_im_txt fr_price pr_discount_price" placeholder="0" data-lenght="1">
                  </div>
               </div>
            </div>
            
            <div class="form_c">
               <div class="form_head">Добавить изображение товара:</div>
               <div class="form_im">
                  <input type="file" class="file dsp_n product_img pr_img" accept=".png, .jpeg, .jpg">
                  <div class="form_im_img lazy_img pr_img_add" data-txt="Обновить изображение">Выберите с устройства</div>
               </div>
            </div>

            <div class="form_c">
               <div class="form_im form_im_toggle">
                  <div class="form_span">Добавить параметры:</div>
                  <input type="checkbox" class="info_inp" data-val="" />
                  <div class="form_im_toggle_btn setting1_clc"></div>
               </div>
               <div class="setting1_bl">
                  <div class="form_im form_sel">
                     <div class="form_span">Цвет товара:</div>
                     <i class="fal fa-palette form_icon"></i>
                     <input type="text" class="form_im_txt color" placeholder="Выберите цвет" data-txt="Выберите цвет" data-lenght="2">
                  </div>
                  <div class="form_im form_sel">
                     <div class="form_span">Размер товара:</div>
                     <i class="fal fa-ruler form_icon"></i>
                     <input type="text" class="form_im_txt size" placeholder="Выберите размер" data-txt="Выберите размер" data-lenght="2">
                  </div>
               </div>

               <div class="form_im form_im_toggle">
                  <div class="form_span">Доп. информация:</div>
                  <input type="checkbox" class="info_inp" data-val="" />
                  <div class="form_im_toggle_btn info1_clc"></div>
               </div>
               <div class="info1_bl">
                  <div class="form_im form_sel">
                     <div class="form_span">Тип товара:</div>
                     <i class="fal fa-vector-square form_icon"></i>
                     <input type="text" class="form_im_txt type" placeholder="Введите тип" data-txt="Введите тип" data-lenght="2">
                  </div>
                  <div class="form_im">
                     <div class="form_span">Производитель:</div>
                     <i class="fal fa-pen form_icon"></i>
                     <input type="text" class="form_im_txt manufacturer" placeholder="Введите производителя" data-lenght="3">
                  </div>
                  <div class="form_im">
                     <div class="form_span">Описание:</div>
                     <i class="fal fa-pen form_icon"></i>
                     <input type="text" class="form_im_txt description" placeholder="Введите производителя" data-lenght="3">
                  </div>
               </div>
            </div>

            <div class="form_c"><div class="form_im"><div class="btn product_add"><span>Добавить</span></div></div></div>
         </div>
      </div>
   </div>