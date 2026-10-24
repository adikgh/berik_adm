<?php include "apcore.php";

   // 
	$pod_menu_name = 'characteristic';
?>
<? include "../../../block/header.php"; ?>

	<div class="pitem">

		<? include "aheader.php"; ?>

		<div class="ds_nr"><i class="fal fa-ghost"></i><p>В разработке</p></div>

	</div>


<? include "../../../block/footer.php"; ?>

   <!--  -->
   <div class="pop_bl pop_bl2 pitem_add_block">
      <div class="pop_bl_a pitem_add_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить вид товара</h4>
            <div class="btn btn_dd pitem_add_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <div class="form_c">
               <div class="form_head">Общие данные:</div>
               <div class="form_im">
                  <div class="form_span">Артикул товара:</div>
                  <i class="fal fa-barcode form_icon"></i>
                  <input type="text" class="form_im_txt pitem_article" placeholder="Введите артикул" data-lenght="3">
               </div>
               <div class="form_im">
                  <div class="form_span">Штрих-код:</div>
                  <i class="fal fa-barcode form_icon"></i>
                  <input type="tel" class="form_im_txt pitem_barcode" placeholder="Сканируйте код" data-lenght="8">
               </div>
               <div class="form_im">
                  <div class="form_span">Количество:</div>
                  <i class="fal fa-hashtag form_icon"></i>
                  <input type="tel" class="form_im_txt fr_number pitem_quantity" placeholder="0" data-lenght="1">
               </div>
               <div class="form_im">
                  <div class="form_span">Цена продажи:</div>
                  <i class="fal fa-tenge form_icon"></i>
                  <input type="tel" class="form_im_txt fr_price pitem_price" placeholder="0" data-lenght="1">
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
                     <input type="tel" class="form_im_txt fr_price pitem_purchase_price" placeholder="0" data-lenght="1">
                  </div>
                  <div class="form_im">
                     <div class="form_span">Скидочная цена:</div>
                     <i class="fal fa-tenge form_icon"></i>
                     <input type="tel" class="form_im_txt fr_price pitem_discount_price" placeholder="0" data-lenght="1">
                  </div>
               </div>
					
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
            </div>

				<div class="form_c">
               <div class="form_head">Добавить изображение товара:</div>
               <div class="form_im">
                  <input type="file" class="file dsp_n product_img pitem_img" accept=".png, .jpeg, .jpg">
                  <div class="form_im_img lazy_img pitem_img_add" data-txt="Обновить изображение">Выберите с устройства</div>
               </div>
            </div>

            <div class="form_c">
					<div class="form_im">
						<div class="btn pitem_add" data-id="<?=$product_id?>"><span>Добавить</span></div>
					</div>
				</div>
         </div>
      </div>
   </div>



	<!--  -->
	<div class="pop_bl pop_bl2 pitem_upd_block">
      <div class="pop_bl_a pitem_upd_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Обновить вид товара</h4>
            <div class="btn btn_dd pitem_upd_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl lazy_c"></div>
      </div>
   </div>