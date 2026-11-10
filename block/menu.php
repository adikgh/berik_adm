<? if ($site_set['menu']): ?>
	<div class="aheader">
		<div class="bl_c">
			<div class="aheader_c">
				<? if (!empty($page_back)): // «← Артқа» батырмасы: бетте $page_back = ['/url/', 'Атауы'] берілсе, мәзір қатарының сол жағында ?>
					<style>
						.aheader_bk{ margin-right: auto; height: 66px; display: inline-flex; align-items: center; gap: 12px; padding: 0 24px 0 20px; background: #fff; border-radius: 14px; box-shadow: var(--bp-shadow, 0 2px 12px rgba(0,0,0,.06)); font-size: 15px; font-weight: 700; color: var(--bp-text, #222); white-space: nowrap; transition: .15s; }
						.aheader_bk i{ font-size: 15px; transition: .15s; }
						.aheader_bk:hover{ background: var(--bp-pill, #f5f5f5); }
						.aheader_bk:hover i{ transform: translateX(-3px); }
						.head_c .head_back_m{ display: none; }
						@media (max-width: 768px) { .aheader_bk{ display: none; } .head_c .head_back_m{ display: inline-flex; } }
					</style>
					<a class="aheader_bk" href="<?=$page_back[0]?>"><i class="far fa-arrow-left"></i><span><?=$page_back[1]?></span></a>
				<? endif ?>
				<!-- <div class="aheader_l">
					<div class="alogo" href="/">
						<div class="alogo_i lazy_logo" data-src="/assets/img/logo/logo.png"></div>
					</div>
				</div> -->
				<div class="aheader_r">
					<? if (count(@$my_companies ?: []) > 1): // компания таңдау: тек 2+ компанияға кіре алатын адамға (config/core.php) ?>
						<div class="head_rk">
							<select name="company" class="on_sort_company" >
								<? // рет company.number бойынша (config/core.php). data-val — norm.js осыны оқиды ?>
								<? foreach ($my_companies as $cid => $cd): ?>
									<? $ccode = htmlspecialchars(ltrim((string)$cd['code'], '#')); ?>
									<option value="<?=$ccode?>" data-val="<?=$ccode?>" data-sub="<?=htmlspecialchars(ucfirst((string)$cd['city']))?>" <?=($company == $cid?'selected':'')?>><?=htmlspecialchars($cd['name'])?></option>
								<? endforeach ?>
							</select>
						</div>
					<? endif ?>
					<div class="mp_top">
						<div class="mp_topc">

							<? if ($user_right['positions_id'] == 3): ?>
								<a class="mp_topi <?=($menu_name=='kassa'?'mp_topi_act':'')?>" href="/kassa/?time=<?=$time_sort?>"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
							<? else: ?>
								<? // Аналитика беттері арасында ауысқанда таңдалған күн мен кезең (Күн/Апта/Ай/Жыл) сақталады
									$an_d = null; $an_p = 'day';
									if (@$menu_name == 'stats' && !empty($p_start_d) && isset($mode)) { $an_p = ['d' => 'day', 'w' => 'week', 'm' => 'month', 'y' => 'year'][$mode] ?? 'day'; $an_d = $mode == 'd' ? ($dd ?? $p_start_d) : ($mode == 'w' ? ($wd0 ?? $p_start_d) : $p_start_d); }
									elseif (in_array(@$menu_name, ['order_product', 'order_comp', 'order_user']) && isset($period)) { $an_p = $period; $an_d = $time_pick ?? $time_sort; }
									$an_q = 'time='.urlencode($an_d ?: $time_sort).($an_d && $an_p != 'day' ? '&period='.$an_p : '');
									$an_sq = !$an_d ? '' : '?'.($an_p == 'week' ? 'mode=w&w='.$an_d : ($an_p == 'month' ? 'mode=m&m='.substr($an_d, 0, 7) : ($an_p == 'year' ? 'mode=y&y='.substr($an_d, 0, 4) : 'mode=d&d='.$an_d))); ?>
								<? $an_act = in_array($menu_name, ['order_product', 'order_comp', 'order_user', 'stats']) || strpos((string)$menu_name, 'client') === 0; ?>
								<!-- Аналитика мен Статистика: компьютерде бір батырма, ішінен екі бет ашылады. Телефондағы төменгі мәзір .mp_topi сілтемелерін бөлек көшіреді. -->
								<div class="mp_dd <?=$an_act?'mp_dd_act':''?>">
									<a class="mp_dd_t" href="/stats/<?=htmlspecialchars($an_sq)?>" tabindex="0"><i class="fal fa-chart-pie"></i><span><?=t::w('Analytics', 'Аналитика')?></span><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<!-- Статистика негізгі бет: Аналитиканы басқанда осы ашылады -->
										<a class="mp_dd_i <?=($menu_name=='stats'?'mp_dd_i_act':'')?>" href="/stats/<?=htmlspecialchars($an_sq)?>"><i class="fal fa-chart-line"></i><span><?=t::w('Statistics', 'Статистика')?></span></a>
										<div class="mp_dd_sep"></div>
										<a class="mp_dd_i <?=($menu_name=='order_product'?'mp_dd_i_act':'')?>" href="/dashboard/order_product.php?<?=htmlspecialchars($an_q)?>"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='order_comp'?'mp_dd_i_act':'')?>" href="/dashboard/order_comp.php?<?=htmlspecialchars($an_q)?>"><i class="fal fa-layer-group"></i><span><?=t::w('Ingredients', 'Құрамы')?></span></a>
										<div class="mp_dd_sep"></div>
										<a class="mp_dd_i <?=(strpos((string)$menu_name, 'client') === 0?'mp_dd_i_act':'')?>" href="/clients/"><i class="fal fa-users"></i><span><?=t::w('Clients', 'Клиенттер')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='order_user'?'mp_dd_i_act':'')?>" href="/dashboard/order_user.php?<?=htmlspecialchars($an_q)?>"><i class="fal fa-user-headset"></i><span><?=t::w('Operator', 'Оператор')?></span></a>
									</div>
									<!-- телефондағы төменгі мәзір үшін (компьютерде көрінбейді) -->
									<a class="mp_topi mp_dd_m <?=(in_array($menu_name, ['order_product', 'order_comp', 'order_user'])?'mp_topi_act':'')?>" href="/stats/<?=htmlspecialchars($an_sq)?>"><i class="fal fa-chart-bar"></i><span><?=t::w('Analytics', 'Аналитика')?></span></a>
									<a class="mp_topi mp_dd_m <?=($menu_name=='stats'?'mp_topi_act':'')?>" href="/stats/<?=htmlspecialchars($an_sq)?>"><i class="fal fa-chart-line"></i><span><?=t::w('Statistics', 'Статистика')?></span></a>
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
								<!-- Касса: компьютерде бір батырма, ішінен Касса, ОПиУ, РНП ашылады -->
								<div class="mp_dd <?=(in_array($menu_name, ['kassa', 'opiu', 'rnp'])?'mp_dd_act':'')?>">
									<a class="mp_dd_t" href="/kassa/?time=<?=$time_sort?>" tabindex="0"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<a class="mp_dd_i <?=($menu_name=='kassa'?'mp_dd_i_act':'')?>" href="/kassa/?time=<?=$time_sort?>"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
										<div class="mp_dd_sep"></div>
										<a class="mp_dd_i <?=($menu_name=='opiu'?'mp_dd_i_act':'')?>" href="/opiu/"><i class="fal fa-table"></i><span><?=t::w('PnL report', 'ОПиУ')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='rnp'?'mp_dd_i_act':'')?>" href="/rnp/"><i class="fal fa-heartbeat"></i><span><?=t::w('Pulse report', 'РНП')?></span></a>
									</div>
									<!-- телефондағы төменгі мәзір үшін -->
									<a class="mp_topi mp_dd_m <?=($menu_name=='kassa'?'mp_topi_act':'')?>" href="/kassa/?time=<?=$time_sort?>"><i class="fal fa-cash-register"></i><span><?=t::w('Cashbox', 'Касса')?></span></a>
									<a class="mp_topi mp_dd_m <?=($menu_name=='opiu'?'mp_topi_act':'')?>" href="/opiu/"><i class="fal fa-table"></i><span><?=t::w('PnL report', 'ОПиУ')?></span></a>
									<a class="mp_topi mp_dd_m <?=($menu_name=='rnp'?'mp_topi_act':'')?>" href="/rnp/"><i class="fal fa-heartbeat"></i><span><?=t::w('Pulse report', 'РНП')?></span></a>
								</div>
								<!-- Тағамдар: компьютерде бір батырма, ішінен бөлімдері ашылады (Аналитика сияқты) -->
								<? $pm = @$pod_menu_name;
									// қызыл санауыштар: граммовкасы жоқ сатылған тағам, минустағы шикізат
									if (!class_exists('wb')) @include_once $_SERVER['DOCUMENT_ROOT'].'/waybill/lib.php';
									$wbm = class_exists('wb') ? wb::menu_counts(@$company, @$branch) : ['dish' => 0, 'neg' => 0];
									$wbm_b = function ($n, $t) { return $n ? '<b class="mp_cnt" title="'.$t.'" style="min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#e74c3c;color:#fff;font-size:11px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;line-height:1">'.$n.'</b>' : ''; }; ?>
								<div class="mp_dd <?=($menu_name=='products'?'mp_dd_act':'')?>">
									<a class="mp_dd_t" href="javascript:;" tabindex="0"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span><?=$wbm_b($wbm['dish'], 'Граммовкасы жоқ сатылған тағам')?><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<a class="mp_dd_i <?=($menu_name=='products' && ($pm=='main' || $pm=='item')?'mp_dd_i_act':'')?>" href="/products/"><i class="fal fa-list-ul"></i><span><?=t::w('Menu: sets and items', 'Мәзір')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='catalog'?'mp_dd_i_act':'')?>" href="/products/catalog/"><i class="fal fa-folder"></i><span><?=t::w('Catalog', 'Каталог')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='comp'?'mp_dd_i_act':'')?>" href="/products/comp/"><i class="fal fa-layer-group"></i><span><?=t::w('Ingredients', 'Құрамы')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='raw'?'mp_dd_i_act':'')?>" href="/products/raw/"><i class="fal fa-box"></i><span><?=t::w('Raw and goods', 'Шикізат (тауар)')?></span></a>
										<a class="mp_dd_i <?=($menu_name=='products' && $pm=='cost'?'mp_dd_i_act':'')?>" href="/products/cost/#missing"><i class="fal fa-calculator"></i><span><?=t::w('Cost sheet', 'Себес')?></span><?=$wbm_b($wbm['dish'], 'Граммовкасы жоқ сатылған тағам')?></a>
									</div>
									<!-- телефондағы төменгі мәзір үшін -->
									<a class="mp_topi mp_dd_m <?=($menu_name=='products'?'mp_topi_act':'')?>" href="/products/"><i class="fal fa-utensils"></i><span><?=t::w('Dishes', 'Тағамдар')?></span></a>
								</div>
								<!-- Закуп: накладнойлар бөлімі (тек осы жерде) -->
								<a class="mp_topi <?=($menu_name=='waybill'?'mp_topi_act':'')?>" href="/waybill/"><i class="fal fa-shopping-basket"></i><span><?=t::w('Purchases', 'Закуп')?></span><?=$wbm_b($wbm['neg'], 'Минустағы шикізат')?></a>
								<!-- <a class="mp_topi <?=($menu_name=='opiu'?'mp_topi_act':'')?>" href="/opiu/?time=<?=$time_sort?>">Есеп</a> -->
								<!-- <a class="mp_topi <?=($menu_name=='products'?'mp_topi_act':'')?>" href="/products/?time=<?=$time_sort?>">Тағамдар</a> -->
								<!-- Клиенттер компьютерде Аналитика ішінде; бұл жасырын сілтеме тек телефонның «Тағы» бөлімі үшін -->
								<a class="mp_topi mp_dd_m <?=(strpos((string)$menu_name, 'client') === 0?'mp_topi_act':'')?>" href="/clients/"><i class="fal fa-users"></i><span><?=t::w('Clients', 'Клиенттер')?></span></a>
								<!-- <a class="mp_topi <?=($menu_name=='staff'?'mp_topi_act':'')?>" href="/staff/?time=<?=$time_sort?>">Жұмысшылар</a> -->
							<? endif ?>

						</div>
					</div>
					<div class="ub1_lx">
<?
								// аватар: файл бар болса сурет, жоқ болса аты-жөнінің бас әріптері
								$ava_url = (@$user['img'] && is_file($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/users/'.basename($user['img']))) ? '/assets/uploads/users/'.rawurlencode(basename($user['img'])) : '';
								$ava_ini = htmlspecialchars(mb_strtoupper(mb_substr(trim(@$user['name']), 0, 1).mb_substr(trim(@$user['surname']), 0, 1)));
								if ($ava_ini === '') $ava_ini = '<i class="fal fa-user"></i>';
								$ava_html = $ava_url ? '<img src="'.$ava_url.'" alt="">' : $ava_ini;
							?>
						<div class="ub1_lt" href="/user/">
							<div class="ub1_ltf">
								<div class=""><?=htmlspecialchars((string)@$user['name'])?> <?=(@$user['surname']?htmlspecialchars(mb_substr($user['surname'],0,1)).'.':'')?></div>
								<span><?//=fun::user_staff_name($user_right['staff_id'])?></span>
							</div>
							<div class="ub1_lti ava_box"><?=$ava_html?></div>
						</div>
						<div class="menu_c acc_menu">
							<div class="acc_hd">
								<div class="acc_hd_av ava_box"><?=$ava_html?></div>
								<div class="acc_hd_t">
									<div><?=htmlspecialchars(trim(@$user['name'].' '.@$user['surname']))?></div>
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
								<? if (@$user_right['positions_id'] != 3): ?>
									<a class="menu_ci" href="/delivery/">
										<div class="menu_cin"><i class="fal fa-map-marked-alt"></i></div>
										<div class="menu_cih"><?=t::w('Delivery zones', 'Жеткізу аймақтары')?></div>
									</a>
									<a class="menu_ci" href="/qr/">
										<div class="menu_cin"><i class="fal fa-qrcode"></i></div>
										<div class="menu_cih"><?=t::w('QR menu', 'QR меню')?></div>
									</a>
									<a class="menu_ci" href="/receipt/">
										<div class="menu_cin"><i class="fal fa-print"></i></div>
										<div class="menu_cih"><?=t::w('Receipt', 'Чек')?></div>
									</a>
								<? endif ?>
								<? if (function_exists('wa_owner_ok') && wa_owner_ok($user_id, @$user_right['positions_id'])): ?>
									<a class="menu_ci" href="/wa/">
										<div class="menu_cin"><i class="fab fa-whatsapp"></i></div>
										<div class="menu_cih">WhatsApp</div>
									</a>
								<? endif ?>
								<a class="menu_ci ac_open" href="/acc/">
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
		<div class="head_c<?=(!empty($page_back) ? ' head_c_back' : '')?>">
			<? if (!empty($page_back)): // телефонда жоғарғы мәзір жасырын, сондықтан мұнда ?><a class="head_back head_back_m" href="<?=$page_back[0]?>"><i class="far fa-arrow-left"></i><?=$page_back[1]?></a><? endif ?>
			<h1><?=(isset($page_key) ? t::w($page_key, $page_name) : $page_name)?></h1>
			
		</div>
	</div>
<? endif ?>