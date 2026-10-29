<? if ($site_set['menu']): ?>
	<div class="aheader">
		<div class="bl_c">
			<div class="aheader_c">
				<!-- <div class="aheader_l">
					<div class="alogo" href="/">
						<div class="alogo_i lazy_logo" data-src="/assets/img/logo/logo.png"></div>
					</div>
				</div> -->
				<div class="aheader_r">
					<? if ($user_right['positions_id'] == 1): ?>
						<div class="head_rk">
							<select name="status" class="on_sort_company" >
								<? $catl = db::query("select * from company "); ?>
								<? while ($catl_d = mysqli_fetch_assoc($catl)): ?>
									<option data-id="" value="<?=$catl_d['id']?>" <?=(@$company == $catl_d['id']?'selected':'')?>><?=$catl_d['name']?></option>
								<? endwhile ?>
							</select>
						</div>
					<? endif ?>
					<div class="mp_top">
						<div class="mp_topc">

							<? if ($user_right['positions_id'] == 3): ?>
								<a class="mp_topi <?=($menu_name=='kassa'?'mp_topi_act':'')?>" href="/kassa/?time=<?=$time_sort?>"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
							<? else: ?>
								<? $an_act = in_array($menu_name, ['order_product', 'order_comp', 'order_user', 'stats']) || strpos((string)$menu_name, 'client') === 0; ?>
								<!-- Аналитика мен Статистика: компьютерде бір батырма, ішінен екі бет ашылады. Телефондағы төменгі мәзір .mp_topi сілтемелерін бөлек көшіреді. -->
								<div class="mp_dd <?=$an_act?'mp_dd_act':''?>">
									<a class="mp_dd_t" href="javascript:;" tabindex="0"><i class="fal fa-chart-pie"></i><span><?=t::w('Analytics', 'Аналитика')?></span><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<a class="mp_dd_i <?=($menu_name=='order_product'?'mp_dd_i_act':'')?>" href="/dashboard/order_product.php?time=<?=$time_sort?>"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='order_comp'?'mp_dd_i_act':'')?>" href="/dashboard/order_comp.php?time=<?=$time_sort?>"><i class="fal fa-layer-group"></i><span><?=t::w('Ingredients', 'Құрамы')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='order_user'?'mp_dd_i_act':'')?>" href="/dashboard/order_user.php?time=<?=$time_sort?>"><i class="fal fa-user-headset"></i><span><?=t::w('Operator', 'Оператор')?></span></a>
										<div class="mp_dd_sep"></div>
										<a class="mp_dd_i <?=($menu_name=='stats'?'mp_dd_i_act':'')?>" href="/stats/"><i class="fal fa-chart-line"></i><span><?=t::w('Statistics', 'Статистика')?></span></a>
										<a class="mp_dd_i <?=(strpos((string)$menu_name, 'client') === 0?'mp_dd_i_act':'')?>" href="/clients/"><i class="fal fa-users"></i><span><?=t::w('Clients', 'Клиенттер')?></span></a>
									</div>
									<!-- телефондағы төменгі мәзір үшін (компьютерде көрінбейді) -->
									<a class="mp_topi mp_dd_m <?=(in_array($menu_name, ['order_product', 'order_comp', 'order_user'])?'mp_topi_act':'')?>" href="/dashboard/order_product.php?time=<?=$time_sort?>"><i class="fal fa-chart-bar"></i><span><?=t::w('Analytics', 'Аналитика')?></span></a>
									<a class="mp_topi mp_dd_m <?=($menu_name=='stats'?'mp_topi_act':'')?>" href="/stats/"><i class="fal fa-chart-line"></i><span><?=t::w('Statistics', 'Статистика')?></span></a>
								</div>
								<style>
									.mp_dd{ position: relative; display: flex; }
									.mp_dd_t{ display: flex; align-items: center; gap: 10px; height: 56px; padding: 0 18px; border-radius: 10px; font-size: 16px; font-weight: 600; color: var(--bp-text, #222); cursor: pointer; user-select: none; transition: .15s; }
									.mp_dd_t i{ font-size: 18px; line-height: 1; }
									.mp_dd_t .mp_dd_ar{ font-size: 14px; margin-left: -2px; transition: .15s; }
									.mp_dd:hover .mp_dd_t, .mp_dd:focus-within .mp_dd_t{ background: var(--bp-pill, #f5f5f5); }
									.mp_dd_act .mp_dd_t, .mp_dd_act:hover .mp_dd_t{ background: #e3f5ec; color: var(--bp-green, #0EA768); font-weight: 700; }
									.mp_dd:hover .mp_dd_ar, .mp_dd:focus-within .mp_dd_ar{ transform: rotate(180deg); }
									.mp_dd_c{ display: none; position: absolute; top: 100%; left: 0; z-index: 50; min-width: 100%; padding: 6px; background: #fff; border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,.14), 0 1px 3px rgba(0,0,0,.06); flex-direction: column; gap: 2px; margin-top: 10px; }
									.mp_dd_c::before{ content: ''; position: absolute; left: 0; right: 0; top: -10px; height: 10px; }
									.mp_dd:hover .mp_dd_c, .mp_dd:focus-within .mp_dd_c{ display: flex; }
									.mp_dd_i{ display: flex; align-items: center; gap: 10px; height: 48px; padding: 0 16px; border-radius: 10px; font-size: 15px; font-weight: 600; color: var(--bp-text, #222); white-space: nowrap; transition: .15s; }
									.mp_dd_i i{ width: 18px; text-align: center; font-size: 17px; }
									.mp_dd_i:hover{ background: var(--bp-pill, #f5f5f5); }
									.mp_dd_i_act, .mp_dd_i_act:hover{ background: #e3f5ec; color: var(--bp-green, #0EA768); font-weight: 700; }
									.mp_dd_sep{ height: 1px; margin: 4px 8px; background: var(--bp-line, #e7e7e7); }
									.mp_dd_m{ display: none !important; }
									@media (min-width: 769px){ .an_tabs_m{ display: none; } }
									@media (max-width: 1500px){ .mp_dd_t{ padding: 0 12px; gap: 8px; font-size: 15px; } }
								</style>
								<a class="mp_topi <?=($menu_name=='orders'?'mp_topi_act':'')?>" href="/orders/?time=<?=$time_sort?>"><i class="fal fa-receipt"></i><span><?=t::w('Orders', 'Тапсырыстар')?></span></a>
								<a class="mp_topi <?=($menu_name=='kassa'?'mp_topi_act':'')?>" href="/kassa/?time=<?=$time_sort?>"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
								<!-- Тағамдар: компьютерде бір батырма, ішінен бөлімдері ашылады (Аналитика сияқты) -->
								<? $pm = @$pod_menu_name; ?>
								<div class="mp_dd <?=($menu_name=='products'?'mp_dd_act':'')?>">
									<a class="mp_dd_t" href="javascript:;" tabindex="0"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<a class="mp_dd_i <?=($menu_name=='products' && ($pm=='main' || $pm=='item')?'mp_dd_i_act':'')?>" href="/products/"><i class="fal fa-list-ul"></i><span><?=t::w('All dishes', 'Барлық тағамдар')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='catalog'?'mp_dd_i_act':'')?>" href="/products/catalog/"><i class="fal fa-folder"></i><span><?=t::w('Catalog', 'Каталог')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='comp'?'mp_dd_i_act':'')?>" href="/products/comp/"><i class="fal fa-layer-group"></i><span><?=t::w('Ingredients', 'Құрамы')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='raw'?'mp_dd_i_act':'')?>" href="/products/raw/"><i class="fal fa-box"></i><span><?=t::w('Raw materials', 'Шикізат')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='rent'?'mp_dd_i_act':'')?>" href="/products/rent/"><i class="fal fa-shopping-cart"></i><span><?=t::w('Purchase', 'Сатып алу')?></span></a>
									</div>
									<!-- телефондағы төменгі мәзір үшін -->
									<a class="mp_topi mp_dd_m <?=($menu_name=='products'?'mp_topi_act':'')?>" href="/products/"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span></a>
								</div>
								<!-- <a class="mp_topi <?=($menu_name=='opiu'?'mp_topi_act':'')?>" href="/opiu/?time=<?=$time_sort?>">Есеп</a> -->
								<!-- <a class="mp_topi <?=($menu_name=='products'?'mp_topi_act':'')?>" href="/products/?time=<?=$time_sort?>">Тағамдар</a> -->
								<!-- Клиенттер компьютерде Аналитика ішінде; бұл жасырын сілтеме тек телефонның «Тағы» бөлімі үшін -->
								<a class="mp_topi mp_dd_m <?=(strpos((string)$menu_name, 'client') === 0?'mp_topi_act':'')?>" href="/clients/"><i class="fal fa-users"></i><span><?=t::w('Clients', 'Клиенттер')?></span></a>
								<!-- <a class="mp_topi <?=($menu_name=='staff'?'mp_topi_act':'')?>" href="/staff/?time=<?=$time_sort?>">Жұмысшылар</a> -->
							<? endif ?>

						</div>
					</div>
					<div class="ub1_lx">
						<div class="ub1_lt" href="/user/">
							<div class="ub1_ltf">
								<div class=""><?=@$user['name']?> <?=(@$user['surname']?substr(@$user['surname'],0,1).'.':'')?></div>
								<span><?//=fun::user_staff_name($user_right['staff_id'])?></span>
							</div>
							<div class="ub1_lti lazy_img" data-src="/assets/uploads/users/<?=@$user['img']?>"><? if (!@$user['img']): ?><i class="fal fa-user"></i><? endif ?></div>
						</div>
						<div class="menu_c acc_menu">
							<div class="acc_hd">
								<div class="acc_hd_av lazy_img" data-src="/assets/uploads/users/<?=@$user['img']?>"><? if (!@$user['img']): ?><i class="fal fa-user"></i><? endif ?></div>
								<div class="acc_hd_t">
									<div><?=@$user['name']?> <?=@$user['surname']?></div>
									<? if (@$user['phone']): ?><span><?=@$user['phone']?></span><? endif ?>
								</div>
							</div>
							<div class="acc_lang">
								<div class="acc_lang_h"><i class="fal fa-globe"></i> <?=t::w('Language', 'Тіл')?></div>
								<div class="acc_lang_c">
									<? foreach ($langs_short as $lk => $lv): ?>
										<a class="acc_lang_i <?=($ui_lang==$lk?'acc_lang_act':'')?>" href="?<?=http_build_query(array_merge($_GET, ['lang' => $lk]))?>" title="<?=$langs[$lk]?>"><?=$lv?></a>
									<? endforeach ?>
								</div>
							</div>
							<div class="acc_list">
								<a class="menu_ci" href="/team/">
									<div class="menu_cin"><i class="fal fa-user-headset"></i></div>
									<div class="menu_cih"><?=t::w('Staff', 'Жұмысшылар')?></div>
								</a>
								<a class="menu_ci" href="/remains/">
									<div class="menu_cin"><i class="fal fa-boxes-alt"></i></div>
									<div class="menu_cih"><?=t::w('Remains', 'Қалдықтар')?></div>
								</a>
								<a class="menu_ci" href="/waybill/"><div class="menu_cin"><i class="fal fa-file-invoice"></i></div><div class="menu_cih"><?=t::w('Waybills', 'Накладной')?></div></a>
								<a class="menu_ci" href="#/acc/">
									<div class="menu_cin"><i class="fal fa-user"></i></div>
									<div class="menu_cih"><?=t::w('Account', 'Аккаунт')?></div>
								</a>
								<a class="menu_ci acc_exit" href="/exit.php">
									<div class="menu_cin"><i class="fal fa-sign-out"></i></div>
									<div class="menu_cih"><?=t::w('Log out', 'Шығу')?></div>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<? endif ?>

<!-- <? if ($site_set['menu']): ?>
   <div class="pmenu">
      <div class="pmenu_c">
         <a class="pmenu_i <?=($menu_name=='cashbox'?'pmenu_i_act':'')?>" href="/cashbox/">
				<i class="far fa-shopping-bag"></i>
				<span>Касса</span>
			</a>
         <a class="pmenu_i <?=($menu_name=='return'?'pmenu_i_act':'')?>" href="/return/">
				<i class="far fa-undo"></i>
				<span>Возврат</span>
			</a>
         <a class="pmenu_i <?=($menu_name=='orders'?'pmenu_i_act':'')?>" href="/orders/">
				<i class="far fa-list-ul"></i>
				<span>История</span>
			</a>
         <a class="pmenu_i <?=($menu_name=='acc'?'pmenu_i_act':'')?>" href="/acc/">
				<i class="far fa-user"></i>
				<span>Аккаунт</span>
			</a>
      </div>
   </div>
<? endif ?> -->


<? if ($site_set['menu']): ?>
	<div class="bl_c">
		<div class="head_c">
			<h1><?=(isset($page_key) ? t::w($page_key, $page_name) : $page_name)?></h1>
			
		</div>
	</div>
<? endif ?>