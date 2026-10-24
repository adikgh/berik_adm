<? include "../config/core.php";

	// 
	if (!$user_id) header('location: /');


   $filter = false; $id = $_GET['id'];
   $stack = array(); $ln = '';
   $sql = db::query("select item_id from product_item_quantity where warehouses_id = '$id' order by product_id asc");
   if (mysqli_num_rows($sql)) {
      while ($res_d = mysqli_fetch_array($sql)) {
         if (!in_array($res_d['item_id'], $stack)){
            array_push($stack, $res_d['item_id']);
            $ln = $ln . $res_d['item_id'] . ', ';
         }
      }
      $ln = substr($ln, 0 , -2); 
   }
   
   $filter = true;
	
   // filter
	if ($filter) $product_all = db::query("select * from product_item_quantity where warehouses_id = '$id'");
	else $product_all = db::query("select * from product_item_quantity");
	$page_result = mysqli_num_rows($product_all);

	// page number
	$page = 1; if ($_GET['page'] && is_int(intval($_GET['page']))) $page = $_GET['page'];
	$page_age = 50;
	$page_all = ceil($page_result / $page_age);
	if ($page > $page_all) $page = $page_all;
	$page_start = ($page - 1) * $page_age;
	$number = $page_start;

	// filter
	if ($filter) $product = db::query("select * from product_item_quantity where warehouses_id = '$id' order by ins_dt desc limit $page_start, $page_age");
	else $product = db::query("select * from product_item_quantity order by ins_dt desc limit $page_start, $page_age");


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'all';
	// $site_set['header'] = true;
	$css = ['products/main'];
	$js = ['products/main', 'products/view'];
?>
<? include "../block/header.php"; ?>

	<div class="">

      <!--  -->
      <div class="mp_top">
         <div class="mp_topc">
            <? $warehouses = db::query("select * from product_warehouses"); ?>
            <? while ($warehouses_d = mysqli_fetch_assoc($warehouses)): ?>
               <a class="mp_topi <?=($id==$warehouses_d['id']?'mp_topi_act':'')?>" href="<?=$url?>?id=<?=$warehouses_d['id']?>"><?=$warehouses_d['name']?></a>
            <? endwhile ?>
         </div>
      </div>

      <!--  -->
      <div class="ucours_t">
         <div class="ucours_tl">
            <div class="ucours_tm">
               <div class="btn btn_cl product_add_pop">
                  <i class="fal fa-plus"></i>
                  <span>Добавить товар</span>
               </div>
            </div>
         </div>
         <? if ($page_all > 1): ?>
            <div class="ucours_tr">
               <div class="ucours_trn">Страница: <?=$page?>/<?=$page_all?></div>
               <div class="ucours_trnc">
                  <a class="ucours_trnci <?=($page>1?'':'ucours_trnci_ds')?>" href="<?=$url_full?>&page=<?=$page-1?>"><i class="fal fa-angle-left"></i></a>
                  <a class="ucours_trnci <?=($page<$page_all?'':'ucours_trnci_ds')?>" href="<?=$url_full?>&page=<?=$page+1?>"><i class="fal fa-angle-right"></i></a>
               </div>
            </div>
         <? endif ?>
      </div>

      <!-- list -->
      <div class="uc_u">
         <div class="uc_us">
            <div class="form_im uc_usn">
               <input type="text" placeholder="Поиск" class="#product_search">
               <i class="fal fa-search form_icon"></i>
            </div>
         </div>
         <div class="uc_uh">
            <div class="uc_uh2">
               <div class="uc_uh_number">#</div>
               <div class="uc_uh_name">Наименование</div>
					<div class="uc_uh_other">Цвет</div>
					<div class="uc_uh_other">Размер</div>
               <div class="uc_uh_other">Цена продажи</div>
               <div class="uc_uh_other">Количество</div>
            </div>
            <div class="uc_uh_cn"></div>
         </div>

         <? if (mysqli_num_rows($product) && $filter): ?>
				<div class="uc_uc">
					<? while ($view_d = mysqli_fetch_assoc($product)): ?>
						<? $number++; ?>
                  <? $item_d = product::pr_item($view_d['item_id']); ?>
                  <? $pr_d = product::product($view_d['product_id']); ?>
                  <? // $view_d = product::in_stock($item_d['id'], $id); ?>

						<div class="uc_ui uc_ui2">
							<div class="uc_uil">
                        <div class="uc_ui_number"><?=$number?></div>
                        <a class="uc_uiln" href="/products/item/view/?id=<?=$item_d['id']?>">
                           <div class="uc_ui_img lazy_img" data-src="/assets/uploads/products/<?=$item_d['img']?>"><?=($item_d['img']?'':'<i class="fal fa-box"></i>')?></div>
                           <div class="uc_uinu">
                              <div class="uc_ui_name"><?=$item_d['article']?></div>
                              <div class="uc_ui_cont">
                                 <div><?=$pr_d['name_ru']?></div>
                                 <? if ($pr_d['brand_id']): ?> <div><?=(product::pr_brand($pr_d['brand_id']))['name']?></div> <? endif ?>
                              </div>
                           </div>
                        </a>
								<div class="uc_uin_other"><?=(product::pr_color($item_d['color_id']))['name_ru']?></div>
								<div class="uc_uin_other"><?=(product::pr_size($item_d['size_id']))['name']?></div>
								<div class="uc_uin_other"><?=($item_d['price']?$item_d['price'].' тг':'<m>Цена не указана</m>')?></div>
                        <div class="uc_uin_calc">
									<div class="uc_uin_calc2" data-id="<?=$view_d['id']?>" data-quantity="<?=$view_d['quantity']?>">
										<div class="uc_uin_calc_m pitem_minus"><i class="fal fa-minus"></i></div>
										<div class="uc_uin_calc_q "><?=$view_d['quantity']?></div>
										<div class="uc_uin_calc_p pitem_plus"><i class="fal fa-plus"></i></div>
									</div>
								</div>
							</div>
							<div class="uc_uib">
								<div class="uc_uibo"><i class="fal fa-ellipsis-v"></i></div>
								<div class="menu_c uc_uibs">
									<a class="menu_ci" target="_blank" href="/products/item/view/?id=<?=$item_d['id']?>">
										<div class="menu_cin"><i class="fal fa-external-link"></i></div>
										<div class="menu_cih">Открыть товар</div>
									</a>
									<div class="menu_ci product2_add_pop" data-id="<?=$pr_d['id']?>">
										<div class="menu_cin"><i class="fal fa-clone"></i></div>
										<div class="menu_cih">Дублировать товар</div>
									</div>
									<div class="menu_ci uc_uib_del pitem_btn_delete" data-id="<?=$view_d['id']?>">
										<div class="menu_cin"><i class="fal fa-trash-alt"></i></div>
										<div class="menu_cih">Удалить товар</div>
									</div>
								</div>
							</div>
						</div>
					<? endwhile ?>
				</div>
         <? else: ?>
            <div class="ds_nr"><i class="fal fa-ghost"></i><p>НЕТ</p></div>
         <? endif ?>
      </div>

      <? if ($page_all > 1 && $filter): ?>
         <div class="uc_p">
            <? if ($page > 1): ?> <a class="uc_pi" href="<?=$url_full?>&page=<?=$page-1?>"><i class="fal fa-angle-left"></i></a> <? endif ?>
            <a class="uc_pi <?=($page==1?'uc_pi_act':'')?>" href="<?=$url_full?>&page=1">1</a>
            <? for ($pg = 2; $pg < $page_all; $pg++): ?>
               <? if ($pg == $page - 1): ?>
                  <? if ($page - 1 != 2): ?> <div class="uc_pi uc_pi_disp">...</div> <? endif ?>
                  <a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_full?>&page=<?=$pg?>"><?=$pg?></a>
               <? endif ?>
               <? if ($pg == $page): ?> <a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_full?>?page=<?=$pg?>"><?=$pg?></a> <? endif ?>
               <? if ($pg == $page + 1): ?>
                  <a class="uc_pi <?=($page==$pg?'uc_pi_act':'')?>" href="<?=$url_full?>&page=<?=$pg?>"><?=$pg?></a>
                  <? if ($page + 1 != $page_all - 1): ?> <div class="uc_pi uc_pi_disp">...</div> <? endif ?>
               <? endif ?>
            <? endfor ?>
            <a class="uc_pi <?=($page==$page_all?'uc_pi_act':'')?>" href="<?=$url_full?>&page=<?=$page_all?>"><?=$page_all?></a>
            <? if ($page < $page_all): ?> <a class="uc_pi" href="<?=$url_full?>&page=<?=$page+1?>"><i class="fal fa-angle-right"></i></a> <? endif ?>
         </div>
      <? endif ?>

	</div>

<? include "../block/footer.php"; ?>
   <? include "pop_add.php"; ?>