<? include "../../config/core.php"; ?>

   <!--  -->
   <? if (isset($_GET['product_search'])): ?>
		<? $search = strip_tags($_POST['result']); ?>

         <? $item = db::query("select * from product_item where arh = 0 and (article like '%$search%') or (barcode like '%$search%') order by ins_dt desc limit 30"); ?>
         <? if (mysqli_num_rows($item)): ?>
            <? while ($item_d = mysqli_fetch_assoc($item)): ?>
               <? $number++; $item_id = $item_d['id']; ?>
               <? $pr_d = product::product($item_d['product_id']); ?>
               <? $view = db::query("select * from product_item_quantity where item_id = '$item_id'"); ?>
               <? if (mysqli_num_rows($view) == 1) $view_d = mysqli_fetch_assoc($view); else $view_d = null; ?>

               <tr class="uc_ui uc_ui2">
                  <td class="td_number"><div class="uc_ui_number"><?=$number?></div></td>
                  <td class="td_img">
                     <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                  </td>
                  <td class="td_br"></td>
                  <td class="td_name">
                     <a class="" href="/products/item/?id=<?=$pr_d['id']?>">
                        <div class="uc_uinu">
                           <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                        </div>
                     </a>
                  </td>
                  <td class="td_other">
                     <div class="tc_code">
                        <? if ($item_d['article']): ?> <div class="tc_code1"><?=$item_d['article']?></div> <? endif ?>
                        <? if ($item_d['barcode']): ?> <div class="tc_code2"><?=$item_d['barcode']?></div> <? endif ?>
                     </div>
                  </td>
                  <td class="td_other">
                     <div class=""><?=(product::pr_color($item_d['color_id']))['name_ru']?></div>
                  </td>
                  <td class="td_other">
                     <div class=""><?=(product::pr_size($item_d['size_id']))['name']?></div>
                  </td>
                  <td class="td_other"><div class="uc_uin_other"><?=product::product_warehouses($pr_d['id'])?></div></td>
                  <td class="td_other">
                     <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_price item_upd_pr" data-id="<?=$item_d['id']?>" value="<?=$item_d['price']?>" data-lenght="1" /></div>
                  </td>
                  <td class="td_other">
                     <? if ($view_d): ?> <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_number3 view_updq_qn" data-id="<?=$view_d['id']?>" value="<?=$view_d['quantity']?>" data-lenght="1" /></div>
                     <? else: ?> <div class="uc_uin_other pitem_updq_pop cursor_p fr_number3" data-id="<?=$item_id?>"><?=product::pr_item_quantity($item_id)?></div> <? endif ?>
                  </td>
                  <!-- <div class="uc_uin_other">
                     <? if ($item_d['price']): ?> <div class="fr_price"><?=$item_d['price']?></div>
                     <? else: ?> <m>Цена не указана</m> <? endif ?>
                  </div> -->
                  <!-- <div class="uc_uin_other " product_quantity_add_pop data-id="<?=$pr_d['id']?>"><?=product::product_quantity($pr_d['id'])?> шт</div> -->
                  <td class="uc_uib">
                     <div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
                     <div class="menu_c uc_uibs">
                        <a class="menu_ci" target="_blank" href="/products/item/?id=<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-external-link"></i></div>
                           <div class="menu_cih">Открыть товар</div>
                        </a>
                        <a class="menu_ci" target="_blank" href="/barcode/?code=<?=$item_d['barcode']?>&price=<?=$item_d['price']?>&name=<?=$pr_d['name_ru']?>">
                           <div class="menu_cin"><i class="fal fa-print"></i></div>
                           <div class="menu_cih">Печатать штрих код</div>
                        </a>
                        <div class="menu_ci product2_add_pop" data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-clone"></i></div>
                           <div class="menu_cih">Дублировать товар</div>
                        </div>
                        <!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-handshake"></i></div>
                           <div class="menu_cih">Выставить на продажу</div>
                        </div> -->
                        <!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-archive"></i></div>
                           <div class="menu_cih">Архивировать товар</div>
                        </div> -->
                        <div class="menu_ci uc_uib_del pitem_btn_delete" data-title2="Удалить товар" data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
                           <div class="menu_cih">Удалить товар</div>
                        </div>
                     </div>
                  </td>
               </tr>
            <? endwhile ?>
            
         <? else: ?>
            
            <? $product = db::query("select * from product where arh = 0 and (name_ru like '%$search%') or (name_kz like '%$search%') order by ins_dt desc limit 10"); ?>
            <? while ($pr_d = mysqli_fetch_assoc($product)): ?>
               <? $product_id = $pr_d['id']; ?>
               <? $item = db::query("select * from product_item where product_id = '$product_id'"); ?>
               <? while ($item_d = mysqli_fetch_assoc($item)): ?>
                  <? $number++; $item_id = $item_d['id']; ?>
                  <? $view = db::query("select * from product_item_quantity where item_id = '$item_id'"); ?>
                  <? if (mysqli_num_rows($view) == 1) $view_d = mysqli_fetch_assoc($view); else $view_d = null; ?>
      
                  <tr class="uc_ui uc_ui2">
                     <td class="td_number"><div class="uc_ui_number"><?=$number?></div></td>
                     <td class="td_img">
                        <div class="uc_ui_img lazy_img" data-src="https://lighterior.kz/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                     </td>
                     <td class="td_br"></td>
                     <td class="td_name">
                        <a class="" href="/products/item/?id=<?=$pr_d['id']?>">
                           <div class="uc_uinu">
                              <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                           </div>
                        </a>
                     </td>
                     <td class="td_other">
                        <div class="tc_code">
                           <? if ($item_d['article']): ?> <div class="tc_code1"><?=$item_d['article']?></div> <? endif ?>
                           <? if ($item_d['barcode']): ?> <div class="tc_code2"><?=$item_d['barcode']?></div> <? endif ?>
                        </div>
                     </td>
                     <td class="td_other">
                        <div class=""><?=(product::pr_color($item_d['color_id']))['name_ru']?></div>
                     </td>
                     <td class="td_other">
                        <div class=""><?=(product::pr_size($item_d['size_id']))['name']?></div>
                     </td>
                     <td class="td_other"><div class="uc_uin_other"><?=product::product_warehouses($pr_d['id'])?></div></td>
                     <td class="td_other">
                        <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_price item_upd_pr" data-id="<?=$item_d['id']?>" value="<?=$item_d['price']?>" data-lenght="1" /></div>
                     </td>
                     <td class="td_other">
                        <? if ($view_d): ?> <div class="uc_uin_other"><input type="tel" class="uc_uin_calc_q fr_number3 view_updq_qn" data-id="<?=$view_d['id']?>" value="<?=$view_d['quantity']?>" data-lenght="1" /></div>
                        <? else: ?> <div class="uc_uin_other pitem_updq_pop cursor_p fr_number3" data-id="<?=$item_id?>"><?=product::pr_item_quantity($item_id)?></div> <? endif ?>
                     </td>
                     <!-- <div class="uc_uin_other">
                        <? if ($item_d['price']): ?> <div class="fr_price"><?=$item_d['price']?></div>
                        <? else: ?> <m>Цена не указана</m> <? endif ?>
                     </div> -->
                     <!-- <div class="uc_uin_other " product_quantity_add_pop data-id="<?=$pr_d['id']?>"><?=product::product_quantity($pr_d['id'])?> шт</div> -->
                     <td class="uc_uib">
                        <div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
                        <div class="menu_c uc_uibs">
                           <a class="menu_ci" target="_blank" href="/products/item/?id=<?=$pr_d['id']?>">
                              <div class="menu_cin"><i class="fal fa-external-link"></i></div>
                              <div class="menu_cih">Открыть товар</div>
                           </a>
                           <a class="menu_ci" target="_blank" href="/barcode/?code=<?=$item_d['barcode']?>&price=<?=$item_d['price']?>&name=<?=$pr_d['name_ru']?>">
                              <div class="menu_cin"><i class="fal fa-print"></i></div>
                              <div class="menu_cih">Печатать штрих код</div>
                           </a>
                           <div class="menu_ci product2_add_pop" data-id="<?=$pr_d['id']?>">
                              <div class="menu_cin"><i class="fal fa-clone"></i></div>
                              <div class="menu_cih">Дублировать товар</div>
                           </div>
                           <!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
                              <div class="menu_cin"><i class="fal fa-handshake"></i></div>
                              <div class="menu_cih">Выставить на продажу</div>
                           </div> -->
                           <!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
                              <div class="menu_cin"><i class="fal fa-archive"></i></div>
                              <div class="menu_cih">Архивировать товар</div>
                           </div> -->
                           <div class="menu_ci uc_uib_del pitem_btn_delete" data-title2="Удалить товар" data-id="<?=$pr_d['id']?>">
                              <div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
                              <div class="menu_cih">Удалить товар</div>
                           </div>
                        </div>
                     </td>
                  </tr>
               <? endwhile ?>
            <? endwhile ?>
            
         <? endif ?>
         
		<? exit(); ?>
	<? endif ?>