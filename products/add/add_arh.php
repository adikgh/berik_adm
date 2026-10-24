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
                  <input type="file" class="item_file file dsp_n pr_img" accept=".png, .jpeg, .jpg">
                  <div class="form_im_img lazy_img product_img_add" data-txt="Обновить изображение">Выберите с устройства</div>
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
                     <i class="fal fa-caret-down form_icon_sel sel_clc"></i>
                     <div class="form_im_sel sel_clc_i">
                        <div class="form_im_seli" data-val="">Пустой</div>
                        <? $color = db::query("select * from color"); ?>
                        <? while ($color_d = mysqli_fetch_assoc($color)): ?>
                           <div class="form_im_seli" data-val="<?=$color_d['id']?>"><?=$color_d['name_ru']?></div>
                        <? endwhile ?>
                     </div>
                  </div>
                  <div class="form_im form_sel">
                     <div class="form_span">Размер товара:</div>
                     <i class="fal fa-ruler form_icon"></i>
                     <input type="text" class="form_im_txt size" placeholder="Выберите размер" data-txt="Выберите размер" data-lenght="2">
                     <i class="fal fa-caret-down form_icon_sel sel_clc"></i>
                     <div class="form_im_sel sel_clc_i">
                        <div class="form_im_seli" data-val="">Пустой</div>
                        <? $size = db::query("select * from size"); ?>
                        <? while ($size_d = mysqli_fetch_assoc($size)): ?>
                           <div class="form_im_seli" data-val="<?=$size_d['id']?>"><?=$size_d['name']?></div>
                        <? endwhile ?>
                     </div>
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












      <!-- Что та -->
   <!--  -->
   <div class="pop_bl pop_bl2 product_quantity_add_block">
      <div class="pop_bl_a product_quantity_add_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить количество</h4>
            <div class="btn btn_dd product_quantity_add_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <div class="form_c">
               <div class="form_im form_sel">
                  <div class="form_span">Цвет товара:</div>
                  <i class="fal fa-palette form_icon"></i>
                  <div class="form_im_txt sel_clc color" data-val="" data-txt="Выберите цвет">Выберите цвет</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <div class="form_im_seli" data-val="">Пустой</div>
                     <? $color = db::query("select * from color"); ?>
                     <? while ($color_d = mysqli_fetch_assoc($color)): ?>
                        <div class="form_im_seli" data-val="<?=$color_d['id']?>"><?=$color_d['name_ru']?></div>
                     <? endwhile ?>
                  </div>
               </div>
               <div class="form_im form_sel">
                  <div class="form_span">Размер товара:</div>
                  <i class="fal fa-ruler form_icon"></i>
                  <div class="form_im_txt sel_clc size" data-val="" data-txt="Выберите размер">Выберите размер</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <div class="form_im_seli" data-val="">Пустой</div>
                     <? $size = db::query("select * from product_size"); ?>
                     <? while ($size_d = mysqli_fetch_assoc($size)): ?>
                        <div class="form_im_seli" data-val="<?=$size_d['id']?>"><?=$size_d['name']?></div>
                     <? endwhile ?>
                  </div>
               </div>
               <div class="form_im form_sel">
                  <div class="form_span">Тип товара:</div>
                  <i class="fal fa-vector-square form_icon"></i>
                  <div class="form_im_txt sel_clc type" data-val="" data-txt="Выберите тип">Выберите тип</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <div class="form_im_seli" data-val="">Пустой</div>
                     <? $type = db::query("select * from product_type"); ?>
                     <? while ($type_d = mysqli_fetch_assoc($type)): ?>
                        <div class="form_im_seli" data-val="<?=$type_d['id']?>"><?=$type_d['name']?></div>
                     <? endwhile ?>
                  </div>
               </div>
               <div class="form_im">
                  <div class="form_span">Количество:</div>
                  <i class="fal fa-hashtag form_icon"></i>
                  <input type="tel" class="form_im_txt fr_number quantity" placeholder="0" data-lenght="1">
                  <i class="fal fa-check form_icon_sel quantity_add"></i>
               </div>
               <div class="form_im dsp_n">
                  <div class="form_span">Данные по количеству:</div>
                  <div class="form_table">
                     <div class="form_table_h">
                        <div class="form_table_c">
                           <div class="form_table_hi">Цвет:</div>
                           <div class="form_table_hi">Размер:</div>
                           <div class="form_table_hi">Тип:</div>
                        </div>
                        <div class="form_table_p">Количество:</div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="form_c">
               <div class="form_im">
                  <div class="btn product_quantity_add"><span>Сохранить</span></div>
               </div>
            </div>
         </div>
      </div>
   </div>