<?php include "../../../config/core.php"; ?>
   
   <!--  -->
   <? if (isset($_GET['pitem_d'])): ?>
		<? $id = strip_tags($_POST['id']); ?>
      <? // $pitem_d = product::pr_item($id); ?>
      <? // $pitem_d = product::pr_item_view($id); ?>
      <? $view = db::query("select * from product_item_quantity where item_id = '$id'"); ?>
		<?	// if mysqli_fetch_array($view); ?>

         <div class="form_head">Автосохранение</div>
         <br>

         <div class="uc_u uc_upop">
            <div class="uc_uh">
               <div class="uc_uh2">
                  <div class="uc_uh_number">#</div>
                  <div class="uc_uh_other">Склад</div>
                  <div class="uc_uh_other">Количество</div>
               </div>
               <? if (mysqli_num_rows($view) > 1): ?> <div class="uc_uh_cn"></div> <? endif ?>
            </div>
            <div class="uc_uc">
               <?	while ($view_d = mysqli_fetch_array($view)): ?>
                  <? $n++; ?>
                  <div class="uc_ui" >
                     <div class="uc_uil">
                        <div class="uc_ui_number"><?=$n?></div>
                        <div class="uc_uin_other viewu_warehouses" data-val="<?=$view_d['warehouses_id']?>"><?=(product::pr_warehouses($view_d['warehouses_id']))['name']?></div>
                        <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_number2 view_updq_qn" data-id="<?=$view_d['id']?>" value="<?=$view_d['quantity']?>" data-lenght="1" /></div>
                     </div>
                     <? if (mysqli_num_rows($view) > 1): ?> <div class="uc_uin_cn pitem_btn_delete" data-id="<?=$view_d['id']?>" data-item-id="<?=$id?>"><i class="fal fa-trash-alt"></i></div> <? endif ?>
                  </div>
               <? endwhile ?>
            </div>
            <div class="uc_ub"><div class="btn btn_back view_add_pop" data-id="<?=$id?>">Добавить склад</div></div>
         </div>

         <!-- <div class="form_c">
            <div class="form_im form_sel">
               <div class="form_span">Склады:</div>
               <i class="fal fa-warehouse-alt form_icon"></i>
               <div class="form_im_txt sel_clc viewu_warehouses" data-val="<?=$view_d['warehouses_id']?>"><?=(product::pr_warehouses($view_d['warehouses_id']))['name']?></div>
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
               <i class="fal fa-hashtag form_icon"></i>
               <input type="tel" class="form_im_txt fr_number viewu_quantity" placeholder="0" data-lenght="1" value="<?=$view_d['quantity']?>" />
            </div>
            <div class="form_im">
               <div class="form_span">Комментарий:</div>
               <i class="fal fa-text form_icon"></i>
               <input type="text" class="form_im_txt viewu_comment" placeholder="" data-lenght="1" value="<?=$view_d['comment']?>" />
            </div>
         </div>

         <div class="form_c">
            <div class="form_im">
               <div class="btn view_upd" data-id="<?=$id?>"><span>Обновить</span></div>
            </div>
         </div> -->

		<? exit(); ?>
	<? endif ?>