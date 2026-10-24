<? include "../../../config/core.php"; ?>
   
   <!--  -->
   <? if (isset($_GET['pitem_d'])): ?>
		<? $id = strip_tags($_POST['id']); ?>
      <? $pitem_d = product::pr_item_view($id); ?>

         <div class="form_c">
            <div class="form_im form_sel">
               <div class="form_span">Склады:</div>
               <i class="fal fa-warehouse-alt form_icon"></i>
               <div class="form_im_txt sel_clc viewu_warehouses" data-val="<?=$pitem_d['warehouses_id']?>"><?=(product::pr_warehouses($pitem_d['warehouses_id']))['name']?></div>
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
               <input type="tel" class="form_im_txt fr_number viewu_quantity" placeholder="0" data-lenght="1" value="<?=$pitem_d['quantity']?>" />
            </div>
            <div class="form_im">
               <div class="form_span">Комментарий:</div>
               <i class="fal fa-text form_icon"></i>
               <input type="text" class="form_im_txt viewu_comment" placeholder="" data-lenght="1" value="<?=$pitem_d['comment']?>" />
            </div>
         </div>

         <div class="form_c">
            <div class="form_im">
               <div class="btn view_upd" data-id="<?=$id?>"><span>Обновить</span></div>
            </div>
         </div>

		<? exit(); ?>
	<? endif ?>