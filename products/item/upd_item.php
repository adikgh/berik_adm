<? include "../../config/core.php"; ?>
   
   <!--  -->
   <? $id = strip_tags($_POST['id']); ?>
   <? $product_d = product::product($id); ?>
   
      <? if (isset($_GET['upd'])): ?>
         <div class="form_c">
            <div class="form_im">
               <div class="form_span">Наименование товара:</div>
               <input type="text" class="form_im_txt pr_upd_name" data-lenght="1" placeholder="Введите наименование" value="<?=$product_d['name_'.$lang]?>" />
               <i class="fal fa-text form_icon"></i>
            </div>
            <div class="form_im form_sel">
               <div class="form_span">Категория товара:</div>
               <i class="fal fa-inventory form_icon"></i>
               <div class="form_im_txt sel_clc pr_upd_catalog" data-val=""><?=($product_d['catalog_id']?(product::pr_catalog($product_d['catalog_id']))['name_'.$lang]:'Выберите категорию')?></div>
               <i class="fal fa-caret-down form_icon_sel"></i>
               <div class="form_im_sel sel_clc_i">
                  <? $catalog = db::query("select * from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc"); ?>
                  <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                     <div class="form_im_seli" data-val="<?=$catalog_d['id']?>"><?=$catalog_d['name_'.$lang]?></div>
                  <? endwhile ?>
               </div>
            </div>
            <div class="form_im">
               <div class="form_span">Бренд:</div>
               <input type="text" class="form_im_txt pr_upd_brand" data-lenght="1" placeholder="Введите бренда" value="<?=(product::pr_brand($product_d['brand_id']))['name']?>" />
               <i class="fal fa-copyright form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Страна бренда:</div>
               <input type="text" class="form_im_txt pr_upd_country" data-lenght="1" placeholder="Введите страну" value="<?=(product::pr_country($product_d['brand_country_id']))['name_'.$lang]?>" />
               <i class="fal fa-flag form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Коллекция:</div>
               <input type="text" class="form_im_txt pr_upd_collection" data-lenght="1" placeholder="Введите коллекция" value="<?=(product::pr_collection($product_d['collection_id']))['name_'.$lang]?>" />
               <i class="fal fa-album-collection form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Стиль:</div>
               <input type="text" class="form_im_txt pr_upd_style" data-lenght="1" placeholder="Введите стиль" value="<?=(product::pr_style($product_d['style_id']))['name_'.$lang]?>" />
               <i class="fal fa-palette form_icon"></i>
            </div>
         </div>
      <? endif ?>

      <? if (isset($_GET['upd2'])): ?>
         <div class="form_c">
            <div class="form_head">Общие данные:</div>
            <div class="form_im">
               <div class="form_span">Наименование товара:</div>
               <input type="text" class="form_im_txt pr_upd_name" data-lenght="1" placeholder="Введите наименование" value="<?=$product_d['name_ru']?>" />
               <i class="fal fa-text form_icon"></i>
            </div>
            <div class="form_im form_sel">
               <div class="form_span">Категория товара:</div>
               <i class="fal fa-inventory form_icon"></i>
               <div class="form_im_txt sel_clc pr_upd_catalog" data-val=""><?=($product_d['catalog_id']?(product::pr_catalog($product_d['catalog_id']))['name_ru']:'Выберите категорию')?></div>
               <i class="fal fa-caret-down form_icon_sel"></i>
               <div class="form_im_sel sel_clc_i">
                  <? $catalog = db::query("select * from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc"); ?>
                  <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                     <div class="form_im_seli" data-val="<?=$catalog_d['id']?>"><?=$catalog_d['name_ru']?></div>
                  <? endwhile ?>
               </div>
            </div>
            <div class="form_im">
               <div class="form_span">Бренд:</div>
               <input type="text" class="form_im_txt pr_upd_brand" data-lenght="1" placeholder="Введите бренда"  value="<?=(product::pr_brand($product_d['brand_id']))['name']?>" />
               <i class="fal fa-copyright form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Страна бренда:</div>
               <input type="text" class="form_im_txt pr_upd_country" data-lenght="1" placeholder="Введите страну" value="<?=(product::pr_brand($product_d['country_id']))['name']?>" />
               <i class="fal fa-flag form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Коллекция:</div>
               <input type="text" class="form_im_txt pr_upd_collection" data-lenght="1" placeholder="Введите коллекция" value="<?=(product::pr_brand($product_d['collection_id']))['name']?>" />
               <i class="fal fa-album-collection form_icon"></i>
            </div>
            <div class="form_im">
               <div class="form_span">Стиль:</div>
               <input type="text" class="form_im_txt pr_upd_style" data-lenght="1" placeholder="Введите стиль" value="<?=(product::pr_brand($product_d['style_id']))['name']?>" />
               <i class="fal fa-palette form_icon"></i>
            </div>

            <!-- <div class="form_im">
               <div class="form_span">Артикул товара:</div>
               <i class="fal fa-barcode form_icon"></i>
               <input type="text" class="form_im_txt pitem_article_upd" placeholder="Введите артикул" data-lenght="3" value="<?=$pitem_d['article']?>">
            </div> -->
            <!-- <div class="form_im">
               <div class="form_span">Штрих-код:</div>
               <i class="fal fa-barcode form_icon"></i>
               <input type="tel" class="form_im_txt pitem_barcode_upd" placeholder="Сканируйте код" data-lenght="8" value="<?=$pitem_d['barcode']?>">
            </div> -->
            <!-- <div class="form_im">
               <div class="form_span">Количество:</div>
               <i class="fal fa-hashtag form_icon"></i>
               <input type="tel" class="form_im_txt fr_number pitem_quantity_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['quantity']?>">
            </div> -->
            <!-- <div class="form_im">
               <div class="form_span">Цена продажи:</div>
               <i class="fal fa-tenge form_icon"></i>
               <input type="tel" class="form_im_txt fr_price pitem_price_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['price']?>">
            </div> -->

            <!-- <div class="form_im form_im_toggle">
               <div class="form_span">Доп. цены:</div>
               <input type="checkbox" class="info_inp" data-val="" />
               <div class="form_im_toggle_btn price1_clc"></div>
            </div>
            <div class="price1_bl">
               <div class="form_im">
                  <div class="form_span">Закупочная цена:</div>
                  <i class="fal fa-tenge form_icon"></i>
                  <input type="tel" class="form_im_txt fr_price pitem_purchase_price_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['purchase_price']?>">
               </div>
               <div class="form_im">
                  <div class="form_span">Скидочная цена:</div>
                  <i class="fal fa-tenge form_icon"></i>
                  <input type="tel" class="form_im_txt fr_price pitem_discount_price_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['discount_price']?>">
               </div>
            </div> -->
            
            <!-- <div class="form_im form_sel">
               <div class="form_span">Цвет товара:</div>
               <i class="fal fa-palette form_icon"></i>
               <input type="text" class="form_im_txt pitem_color_upd" placeholder="Выберите цвет" data-txt="Выберите цвет" data-lenght="2" value="<?=(product::pr_color($pitem_d['color_id']))['name_ru']?>">
            </div>
            <div class="form_im form_sel">
               <div class="form_span">Размер товара:</div>
               <i class="fal fa-ruler form_icon"></i>
               <input type="text" class="form_im_txt pitem_size_upd" placeholder="Выберите размер" data-txt="Выберите размер" data-lenght="2" value="<?=(product::pr_color($pitem_d['size_id']))['name_ru']?>">
            </div> -->
         </div>

         <!-- <div class="form_c">
            <div class="form_head">Добавить изображение товара:</div>
            <div class="form_im">
               <input type="file" class="file dsp_n product_img pitem_img_upd" accept=".png, .jpeg, .jpg">
               <div class="form_im_img pitem_img_upd_btn <?=($pitem_d['img']?'form_im_img2':'')?>" data-txt="Обновить изображение" style="background-image: url('/assets/uploads/products/<?=$pitem_d['img']?>')">Выберите с устройства</div>
            </div>
         </div> -->
      <? endif ?>

      <? if (isset($_GET['upd']) || isset($_GET['upd2'])): ?>
         <div class="form_с">
            <div class="form_im">
               <div class="btn pr_upd" data-id="<?=$id?>"><span>Обновить</span></div>
            </div>
         </div>
      <? endif ?>

	<? exit(); ?>