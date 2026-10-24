<?php include "../config/core.php"; ?>
   
   <!--  -->
   <? if (isset($_GET['clone'])): ?>
		<? $id = strip_tags($_POST['id']); ?>
      <? $product_d = product::product($id); ?>
      <? $pitem_d = product::pr_item_one($id); ?>
      <? $view_d = product::pr_item_view_one($id); ?>

      <div class="form_c">
         <div class="form_head">Основные:</div>
         <div class="form_im">
            <div class="form_span">Наименование товара:</div>
            <input type="text" class="form_im_txt dpr_name" placeholder="Введите наименование" data-lenght="1" value="<?=$product_d['name_ru']?>" />
            <i class="fal fa-text form_icon"></i>
         </div>
         <div class="form_im">
            <div class="form_span">Артикул товара:</div>
            <input type="text" class="form_im_txt dpr_article" placeholder="Введите артикул" data-lenght="3" />
            <i class="fal fa-barcode form_icon"></i>
         </div>
         <div class="form_im">
            <div class="form_span">Штрих-код:</div>
            <input type="tel" class="form_im_txt dpr_barcode" placeholder="Сканируйте код" data-lenght="8" />
            <i class="fal fa-barcode form_icon"></i>
         </div>
         <div class="form_im form_sel">
            <div class="form_span">Склад:</div>
            <i class="fal fa-warehouse-alt form_icon"></i>
            <div class="form_im_txt sel_clc dpr_warehouses" data-val="5">Точка продажа</div>
            <i class="fal fa-caret-down form_icon_sel"></i>
            <div class="form_im_sel sel_clc_i">
               <? $warehouses = db::query("select * from product_warehouses"); ?>
               <? while ($warehouses_d = mysqli_fetch_assoc($warehouses)): ?>
                  <div class="form_im_seli" data-val="<?=$warehouses_d['id']?>"><?=$warehouses_d['name']?></div>
               <? endwhile ?>
            </div>
         </div>
         <div class="form_im">
            <div class="form_span">Количество:</div>
            <input type="tel" class="form_im_txt fr_number dpr_quantity" placeholder="0" data-lenght="1" value="<?=$view_d['quantity']?>" data-val="<?=$view_d['quantity']?>" />
            <i class="fal fa-hashtag form_icon"></i>
         </div>
         <div class="form_im">
            <div class="form_span">Цена продажи:</div>
            <input type="tel" class="form_im_txt fr_price dpr_price" placeholder="0" data-lenght="1" value="<?=$pitem_d['price']?>" data-val="<?=$pitem_d['price']?>" />
            <i class="fal fa-tenge form_icon"></i>
         </div>
         <div class="form_im form_im_toggle">
            <div class="form_span">Доп. цены:</div>
            <input type="checkbox" class="info_inp" data-val="" />
            <div class="form_im_toggle_btn price1_clc"></div>
         </div>
         <div class="price1_bl">
            <div class="form_im">
               <div class="form_span">Закупочная цена:</div>
               <input type="tel" class="form_im_txt fr_price dpr_purchase_price" placeholder="0" data-lenght="1" value="<?=$pitem_d['purchase_price']?>" data-val="<?=$pitem_d['purchase_price']?>" />
               <i class="fal fa-tenge form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Оптовая цена:</div>
               <input type="tel" class="form_im_txt fr_price dpr_discount_price" placeholder="0" data-lenght="1" value="<?=$pitem_d['discount_price']?>" data-val="<?=$pitem_d['discount_price']?>" />
               <i class="fal fa-tenge form_icon"></i>
            </div>
         </div>
      </div>

      <div class="form_c">
         <div class="form_head">Дополнительные:</div>
         <div class="form_im">
            <div class="form_span">Бренд:</div>
            <input type="text" class="form_im_txt dpr_brand" placeholder="Введите бренда" data-lenght="1" value="<?=(product::pr_brand($product_d['brand_id']))['name']?>" />
            <i class="fal fa-text form_icon"></i>
         </div>
         <div class="form_im form_sel">
            <div class="form_span">Категория товара:</div>
            <i class="fal fa-inventory form_icon"></i>
            <div class="form_im_txt sel_clc dpr_catalog" data-val="<?=$product_d['catalog_id']?>"><?=($product_d['catalog_id']?(product::pr_catalog($product_d['catalog_id']))['name_ru']:'Выберите категорию')?></div>
            <i class="fal fa-caret-down form_icon_sel"></i>
            <div class="form_im_sel sel_clc_i">
               <? $catalog = db::query("select * from product_catalog"); ?>
               <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                  <div class="form_im_seli" data-val="<?=$catalog_d['id']?>"><?=$catalog_d['name_ru']?></div>
               <? endwhile ?>
            </div>
         </div>
         <div class="form_im form_sel">
            <div class="form_span">Цвет товара:</div>
            <i class="fal fa-palette form_icon"></i>
            <input type="text" class="form_im_txt dpr_color" placeholder="Введите цвет" data-lenght="2" value="<?=(product::pr_color($pitem_d['color_id']))['name_ru']?>" />
         </div>
         <div class="form_im form_sel">
            <div class="form_span">Размер товара:</div>
            <i class="fal fa-ruler form_icon"></i>
            <input type="text" class="form_im_txt dpr_size" placeholder="Введите размер" data-lenght="2" value="<?=(product::pr_size($pitem_d['size_id']))['name']?>" />
         </div>
      </div>
      
      <div class="form_c">
         <div class="form_head">Добавить изображение товара:</div>
         <div class="form_im">
            <input type="file" class="file dsp_n product_img dpr_img" accept=".png, .jpeg, .jpg">
            <div class="form_im_img lazy_img dpr_img_add" data-txt="Обновить изображение">Выберите с устройства</div>
         </div>
      </div>

      <div class="form_c">
         <div class="form_im">
            <div class="btn dproduct_add">
               <span>Добавить</span>
            </div>
         </div>
      </div>

		<? exit(); ?>
	<? endif ?>