
<div class="">
   <!-- компьютерде бұл беттер жоғарғы «Аналитика» мәзірінде, бұл қатар тек телефонда көрінеді -->
   <div class="tg1_top an_tabs_m">
      <div class="tg1_topc">
         <? $q_per = 'time='.urlencode($time_sort).(isset($period) && $period != 'day' ? '&period='.$period : ''); // басқа бетке өткенде кезең сақталады ?>
         <a class="tg1_topi <?=($menu_name=='order_product'?'tg1_topi_act':'')?>" href="order_product.php?<?=htmlspecialchars($q_per)?>"><?=t::w('Dishes', 'Тағамдар')?></a>
         <a class="tg1_topi <?=($menu_name=='order_comp'?'tg1_topi_act':'')?>" href="order_comp.php?<?=htmlspecialchars($q_per)?>"><?=t::w('Ingredients', 'Құрамы')?></a>
         <a class="tg1_topi <?=($menu_name=='order_user'?'tg1_topi_act':'')?>" href="order_user.php?<?=htmlspecialchars($q_per)?>"><?=t::w('Operator', 'Оператор')?></a>
      </div>
   </div>
   <div class="">

   </div>
</div>


<br>

      <div class="">
         <div class="uc_ui69" style="display: flex; flex-wrap: wrap; align-items: center; gap: 12px;">

            <? if (isset($period)): // кезең ауыстырғышы: Күн · Ай · Жыл ?>
               <div class="k_br" style="flex: 0 0 auto; width: auto;">
                  <? foreach (['day' => t::w('Day', 'Күн'), 'month' => t::w('Month', 'Ай'), 'year' => t::w('Year', 'Жыл')] as $pk => $pn): ?>
                     <a class="k_br_i <?=($period == $pk?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['period' => $pk, 'time' => $time_pick ?? $time_sort])))?>"><span><?=$pn?></span></a>
                  <? endforeach ?>
               </div>
            <? endif ?>
            <div class="">
               <input class="form_dt on_sort_time" type="date" name="" id="" data-period="<?=htmlspecialchars($period ?? 'day')?>" value="<?=date('Y-m-d', strtotime("$start_cdate"))?>">
            </div>
			<? if (!empty($o_branches) && count($o_branches) >= 2): ?>
				<div class="k_br" style="flex: 0 0 auto; width: auto;">
					<a class="k_br_i <?=(!$f_branch?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['branch' => 0])))?>"><i class="fal fa-store"></i><span><?=t::w('Branch: all', 'Барлық точка')?></span></a>
					<? foreach ($o_branches as $ob): ?>
						<a class="k_br_i <?=($f_branch == $ob['id']?'k_br_act':'')?>" href="?<?=htmlspecialchars(http_build_query(array_merge($_GET, ['branch' => $ob['id']])))?>"><span><?=htmlspecialchars($ob['name'])?></span></a>
					<? endforeach ?>
				</div>
			<? endif ?>
            
         </div>
      </div>