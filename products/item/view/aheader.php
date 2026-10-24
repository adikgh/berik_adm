<div class="atops">
   <? if ($site_set['utop_bk']): ?>  <a class="atops_i atops_bk" href="/<?=$site_set['utop_bk']?>"><i class="fal fa-long-arrow-left"></i></a> <? endif ?>
   <a class="atops_i" href="/products/">Товары</a>
   <? if ($product_d['catalog_id']): ?> <a class="atops_i" href="/products/"><?=product::pr_catalog_name($product_d['catalog_id'], $lang)?></a> <? endif ?>
   <a class="atops_i" href="/products/item/?id=<?=$product_id?>"><?=$product_d['name_ru']?></a>
   <div class="atops_i"><?=$site_set['utop_nm']?></div>
</div>

<div class="item_cn"><?=$item_d['article']?> (<?=$product_d['name_ru']?>)</div>

<div class="mp_top">
   <div class="mp_topc">
      <a class="mp_topi <?=($pod_menu_name=='main'?'mp_topi_act':'')?>" href="/products/item/view/?id=<?=$item_id?>">Количество и склад</a>
      <a class="mp_topi <?=($pod_menu_name=='characteristic'?'mp_topi_act':'')?>" href="/products/item/view/characteristic.php?id=<?=$item_id?>">Характеристика</a>
      <a class="mp_topi <?=($pod_menu_name=='img'?'mp_topi_act':'')?>" href="/products/item/view/img.php?id=<?=$item_id?>">Фото</a>
   </div>
</div>
