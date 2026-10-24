<? include "../../config/core.php"; ?>

   <? if (isset($_GET['product_search'])): ?>
		<? $search = strip_tags($_POST['result']); ?>

         <? $item = db::query("select * from product_item where arh = 0 and (article like '%$search%') or (barcode like '%$search%') order by ins_dt desc limit 30"); ?>
         <? if (mysqli_num_rows($item)): ?>
            <? while ($item_d = mysqli_fetch_assoc($item)): ?>
               <? $number++; $item_id = $item_d['id']; ?>
               <? $pr_d = product::product($item_d['product_id']); ?>
               <? $view = db::query("select * from product_item_quantity where item_id = '$item_id'"); ?>
               <? if (mysqli_num_rows($view) == 1) $view_d = mysqli_fetch_assoc($view); else $view_d = null; ?>

               <tr class="uc_ui uc_ui2 cashbox_add" data-id="<?=$item_id?>">
                  <td class="td_number"><div class="uc_ui_number"><?=$number?></div></td>
                  <td class="td_img">
                     <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                  </td>
                  <td class="td_br"></td>
                  <td class="td_name">
                     <div class="uc_uinu">
                        <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                     </div>
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
                     <div class="uc_uin_other pitem_updq_pop cursor_p fr_number3" data-id="<?=$item_id?>"><?=product::pr_item_quantity($item_id)?> шт</div>
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
      
                  <tr class="uc_ui uc_ui2 cashbox_add">
                     <td class="td_number"><div class="uc_ui_number"><?=$number?></div></td>
                     <td class="td_img">
                        <div class="uc_ui_img lazy_img" data-src="https://lighterior.kz/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                     </td>
                     <td class="td_br"></td>
                     <td class="td_name">
                        <div class="uc_uinu">
                           <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                        </div>
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
                        <div class="uc_uin_other pitem_updq_pop cursor_p fr_number3" data-id="<?=$item_id?>"><?=product::pr_item_quantity($item_id)?> шт</div>
                     </td>
                  </tr>
               <? endwhile ?>
            <? endwhile ?>
            
         <? endif ?>
         
		<? exit(); ?>
	<? endif ?>