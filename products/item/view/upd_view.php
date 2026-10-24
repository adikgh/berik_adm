<? include "../../../config/core.php"; ?>
   
   <!--  -->
   <? if (isset($_GET['pitem_d'])): ?>
		<? $id = strip_tags($_POST['id']); ?>
      <? $pitem_d = product::pr_item($id); ?>

         <div class="form_c">
            <div class="form_head">Общие данные:</div>
            <div class="form_im">
               <div class="form_span">Артикул товара:</div>
               <i class="fal fa-barcode form_icon"></i>
               <input type="text" class="form_im_txt pitem_article_upd" placeholder="Введите артикул" data-lenght="3" value="<?=$pitem_d['article']?>">
            </div>
            <div class="form_im">
               <div class="form_span">Штрих-код:</div>
               <i class="fal fa-barcode form_icon"></i>
               <input type="tel" class="form_im_txt pitem_barcode_upd" placeholder="Сканируйте код" data-lenght="8" value="<?=$pitem_d['barcode']?>">
            </div>
            <!-- <div class="form_im">
               <div class="form_span">Количество:</div>
               <i class="fal fa-hashtag form_icon"></i>
               <input type="tel" class="form_im_txt fr_number pitem_quantity_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['quantity']?>">
            </div> -->
            <div class="form_im">
               <div class="form_span">Цена продажи:</div>
               <i class="fal fa-tenge form_icon"></i>
               <input type="tel" class="form_im_txt fr_price pitem_price_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['price']?>">
            </div>

            <div class="form_im form_im_toggle">
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
                  <div class="form_span">Оптовая цена:</div>
                  <i class="fal fa-tenge form_icon"></i>
                  <input type="tel" class="form_im_txt fr_price pitem_discount_price_upd" placeholder="0" data-lenght="1" value="<?=$pitem_d['discount_price']?>">
               </div>
            </div>
            
            <div class="form_im form_sel">
               <div class="form_span">Цвет товара:</div>
               <i class="fal fa-palette form_icon"></i>
               <input type="text" class="form_im_txt pitem_color_upd" placeholder="Выберите цвет" data-txt="Выберите цвет" data-lenght="2" value="<?=(product::pr_color($pitem_d['color_id']))['name_ru']?>">
            </div>
            <div class="form_im form_sel">
               <div class="form_span">Размер товара:</div>
               <i class="fal fa-ruler form_icon"></i>
               <input type="text" class="form_im_txt pitem_size_upd" placeholder="Выберите размер" data-txt="Выберите размер" data-lenght="2" value="<?=(product::pr_size($pitem_d['size_id']))['name']?>">
            </div>
         </div>

         <div class="form_c">
            <div class="form_head">Добавить изображение товара:</div>
            <div class="form_im">
               <input type="file" class="file dsp_n product_img pitem_img_upd" accept=".png, .jpeg, .jpg">
               <div class="form_im_img pitem_img_upd_btn <?=($pitem_d['img']?'form_im_img2':'')?>" data-txt="Обновить изображение" style="background-image: url('/assets/uploads/products/<?=$pitem_d['img']?>')">Выберите с устройства</div>
            </div>
         </div>
         <div class="form_c">
            <div class="form_head">Добавить второй изображение:</div>
            <div class="form_im">
               <input type="file" class="file dsp_n product_img pitem_img_upd2" accept=".png, .jpeg, .jpg">
               <div class="form_im_img pitem_img_upd_btn2 <?=($pitem_d['img']?'form_im_img2':'')?>" data-txt="Обновить изображение" style="background-image: url('/assets/uploads/products/<?=$pitem_d['img_room']?>')">Выберите с устройства</div>
            </div>
         </div>

         <div class="form_c">
            <div class="form_im">
               <div class="btn pitem_upd" data-id="<?=$id?>"><span>Обновить</span></div>
            </div>
         </div>

		<? exit(); ?>
	<? endif ?>