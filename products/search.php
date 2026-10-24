<? include "../config/core.php"; ?>

   <!--  -->
   <? if (isset($_GET['product_search'])): ?>
		<? $search = strip_tags($_POST['result']); ?>

         <? $item = db::query("select *, GROUP_CONCAT(product_id) from product_item where arh = 0 and (article like '%$search%') or (barcode like '%$search%') GROUP BY product_id HAVING COUNT(product_id) > 1 order by ins_dt desc limit 50"); ?>
         <? if (mysqli_num_rows($item)): ?>

            <? while ($pr_d = mysqli_fetch_assoc($item)): ?>
               <? $number++; ?>
               <? $product_d = product::product($pr_d['product_id']); ?>

               <div class="uc_ui uc_ui2">
                  <div class="uc_uil">
                     <div class="uc_ui_number"><?=$number?></div>
                     <a class="uc_uiln" href="/products/item/?id=<?=$product_d['id']?>">
                        <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=product::product_img($product_d['id'])?>"><?=(product::product_img($product_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                        <div class="uc_uinu">
                           <div class="uc_ui_name"><?=$product_d['name_ru']?></div>
                           <? if ($product_d['catalog_id'] || $product_d['brand_id']): ?>
                              <div class="uc_ui_cont">
                                 <? if ($product_d['catalog_id']): ?> <div><?=product::pr_catalog_name($product_d['catalog_id'], $lang)?></div> <? endif ?>
                                 <? if ($product_d['brand_id']): ?> <div><?=(product::pr_brand($product_d['brand_id']))['name']?></div> <? endif ?>
                              </div>
                           <? endif ?>
                        </div>
                     </a>
                     <div class="uc_uin_other"><?=product::product_article($product_d['id'])?></div>
                     <div class="uc_uin_other"><?=product::product_warehouses($product_d['id'])?></div>
                     <div class="uc_uin_other"><?=product::product_price($product_d['id'])?></div>
                     <div class="uc_uin_other " product_quantity_add_pop data-id="<?=$product_d['id']?>"><?=product::product_quantity($product_d['id'])?> шт</div>
                  </div>
                  <div class="uc_uib">
                     <div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
                     <div class="menu_c uc_uibs">
                        <a class="menu_ci" target="_blank" href="/products/item/?id=<?=$product_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-external-link"></i></div>
                           <div class="menu_cih">Открыть товар</div>
                        </a>
                        <div class="menu_ci product2_add_pop" data-id="<?=$product_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-clone"></i></div>
                           <div class="menu_cih">Дублировать товар</div>
                        </div>
                        <!-- <div class="menu_ci " data-id="<?=$product_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-archive"></i></div>
                           <div class="menu_cih">Архивировать товар</div>
                        </div> -->
                        <div class="menu_ci uc_uib_del pr_btn_delete" data-title2="Удалить товар" data-id="<?=$product_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
                           <div class="menu_cih">Удалить товар</div>
                        </div>
                     </div>
                  </div>
               </div>
            <? endwhile ?>

         <? else: ?>
            
            <? $product = db::query("select * from product where company_id = '$company' and arh = 0 and (name_ru like '%$search%' or name_kz like '%$search%') order by ins_dt desc limit 50"); ?>
            <? while ($pr_d = mysqli_fetch_assoc($product)): ?>
               <? $number++; ?>
   
               <div class="uc_ui uc_ui2">
                  <div class="uc_uil">
                     <div class="uc_ui_number"><?=$number?></div>
                     <a class="uc_uiln" href="/products/item/?id=<?=$pr_d['id']?>">
                        <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=product::product_img($pr_d['id'])?>"><?=(product::product_img($pr_d['id'])!=null?'':'<i class="fal fa-box"></i>')?></div>
                        <div class="uc_uinu">
                           <div class="uc_ui_name"><?=$pr_d['name_ru']?></div>
                           <? if ($pr_d['catalog_id'] || $pr_d['brand_id']): ?>
                              <div class="uc_ui_cont">
                                 <? if ($pr_d['catalog_id']): ?> <div><?=product::pr_catalog_name($pr_d['catalog_id'], $lang)?></div> <? endif ?>
                                 <? if ($pr_d['brand_id']): ?> <div><?=(product::pr_brand($pr_d['brand_id']))['name']?></div> <? endif ?>
                              </div>
                           <? endif ?>
                        </div>
                     </a>
                     <div class="uc_uin_other"><?=product::product_article($pr_d['id'])?></div>
                     <div class="uc_uin_other"><?=product::product_warehouses($pr_d['id'])?></div>
                     <div class="uc_uin_other"><?=product::product_price($pr_d['id'])?></div>
                     <div class="uc_uin_other " product_quantity_add_pop data-id="<?=$pr_d['id']?>"><?=product::product_quantity($pr_d['id'])?> шт</div>
                  </div>
                  <div class="uc_uib">
                     <div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
                     <div class="menu_c uc_uibs">
                        <a class="menu_ci" target="_blank" href="/products/item/?id=<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-external-link"></i></div>
                           <div class="menu_cih">Открыть товар</div>
                        </a>
                        <div class="menu_ci product2_add_pop" data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-clone"></i></div>
                           <div class="menu_cih">Дублировать товар</div>
                        </div>
                        <!-- <div class="menu_ci " data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-archive"></i></div>
                           <div class="menu_cih">Архивировать товар</div>
                        </div> -->
                        <div class="menu_ci uc_uib_del pr_btn_delete" data-title2="Удалить товар" data-id="<?=$pr_d['id']?>">
                           <div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
                           <div class="menu_cih">Удалить товар</div>
                        </div>
                     </div>
                  </div>
               </div>
            <? endwhile ?>
            
         <? endif ?>
         
		<? exit(); ?>
	<? endif ?>