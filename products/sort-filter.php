   <!--  -->
   <div class="ucours_t">
      <div class="ucours_tl">
         <!-- <div class="btn btn_cl product_add_pop"><i class="fal fa-plus"></i><span>Быстрое добавление</span></div> -->
         <div class="ucours_tm">
            <div class="btn btn_cl product_add_pop">
               <i class="fal fa-plus"></i>
               <span>Добавить товар</span>
            </div>
         </div>
         <div class="ucours_tm">
            <div class="ucours_tmas"></div>
            <div class="ucours_tmi <?=($filter==0?'ucours_tm_act':'')?>">
               <i class="fal fa-sort ucours_tmic"></i>
               <span>Сортировка</span>
               <i class="fal fa-angle-down ucours_tmis"></i>
            </div>
            <div class="menu_c ucours_tma">
               <a class="menu_ci" href="?sort=1">
                  <div class="menu_cin"><i class="fal fa-circle"></i></div>
                  <div class="menu_cih">по дата создание</div>
               </a>
               <a class="menu_ci" href="?sort=1">
                  <div class="menu_cin"><i class="fal fa-circle"></i></div>
                  <div class="menu_cih">по названием</div>
               </a>
               <a class="menu_ci" href="?sort=1">
                  <div class="menu_cin"><i class="fal fa-circle"></i></div>
                  <div class="menu_cih">по ценам</div>
               </a>
            </div>
         </div>
         <div class="ucours_tm">
            <div class="ucours_tmas"></div>
            <div class="ucours_tmi <?=($filter==0?'ucours_tm_act':'')?>">
               <i class="fal fa-inventory ucours_tmic"></i>
               <span>Категория</span>
               <i class="fal fa-angle-down ucours_tmis"></i>
            </div>
            <div class="menu_c ucours_tma">
               <? $catalog = db::query("select * from product_catalog"); ?>
               <? while ($catalog_d = mysqli_fetch_assoc($catalog)): ?>
                  <a class="menu_ci" href="?catalog=<?=$catalog_d['id']?>">
                     <div class="menu_cin"><i class="fal fa-square"></i></div>
                     <div class="menu_cih"><?=$catalog_d['name_ru']?></div>
                  </a>
               <? endwhile ?>
            </div>
         </div>
         <div class="ucours_tm">
            <div class="ucours_tmas"></div>
            <div class="ucours_tmi <?=($filter==0?'ucours_tm_act':'')?>">
               <i class="fal fa-copyright ucours_tmic"></i>
               <span>Бренд</span>
               <i class="fal fa-angle-down ucours_tmis"></i>
            </div>
            <div class="menu_c ucours_tma">
               <? $ch_brand = db::query("select * from product_ch_brand"); ?>
               <? while ($ch_brand_d = mysqli_fetch_assoc($ch_brand)): ?>
                  <a class="menu_ci" href="?brand=<?=$ch_brand_d['id']?>">
                     <div class="menu_cin"><i class="fal fa-square"></i></div>
                     <div class="menu_cih"><?=$ch_brand_d['name']?></div>
                  </a>
               <? endwhile ?>
            </div>
         </div>
         <? if ($catalog_id || $brand_id): ?>
            <div class="ucours_tm">
               <a class="ucours_tmi " href="/products/">
                  <i class="fal fa-times ucours_tmic"></i>
                  <span>Сбросить</span>
               </a>
            </div>
         <? else: ?>
            <div class="ucours_tm">
               <div class="ucours_tmas"></div>
               <div class="ucours_tmi <?=($filter==0?'ucours_tm_act':'')?>">
                  <i class="fal fa-warehouse-alt ucours_tmic"></i>
                  <span>Склад</span>
                  <i class="fal fa-angle-down ucours_tmis"></i>
               </div>
               <div class="menu_c ucours_tma">
                  <? $warehouses = db::query("select * from product_warehouses"); ?>
                  <? while ($warehouses_d = mysqli_fetch_assoc($warehouses)): ?>
                     <a class="menu_ci" href="/products/warehouses.php?id=<?=$warehouses_d['id']?>">
                        <div class="menu_cin"><i class="fal fa-square"></i></div>
                        <div class="menu_cih"><?=$warehouses_d['name']?></div>
                     </a>
                  <? endwhile ?>
               </div>
            </div>
            <!-- <div class="ucours_tm">
               <div class="ucours_tmi <?=($filter==0?'ucours_tm_act':'')?>">
                  <i class="fal fa-filter ucours_tmic"></i>
                  <span>Все фильтры</span>
               </div>
            </div> -->
         <? endif ?>
      </div>
      <? if ($page_all > 1): ?>
         <div class="ucours_tr">
            <div class="ucours_trn">Страница: <?=$page?>/<?=$page_all?></div>
            <div class="ucours_trnc">
               <a class="ucours_trnci <?=($page>1?'':'ucours_trnci_ds')?>" href="<?=$url_page?>?&page=<?=$page-1?>"><i class="fal fa-angle-left"></i></a>
               <a class="ucours_trnci <?=($page<$page_all?'':'ucours_trnci_ds')?>" href="<?=$url_page?>?&page=<?=$page+1?>"><i class="fal fa-angle-right"></i></a>
            </div>
         </div>
      <? endif ?>
   </div>