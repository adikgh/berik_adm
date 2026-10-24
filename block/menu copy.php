<? if ($site_set['menu']): ?>

	<div class="ub1_l <?=(@$_SESSION['ubd_menu'] == true?'ub1_ms':'')?>">
		<div class="ub1_lc ">
			<div class="ub1_lm ">
				<div class="ub1_lm_logo">ABDIS</div>
				<div class="ub1_lmq">
					<div class="ub1_lmqc"><i class="far fa-angle-right"></i></div>
				</div>
				<div class="umenu_c">
					<? if ($user_right['positions_id'] == 3): ?>
						<a class="umenu_ci <?=($menu_name=='kassa'?'menu_ci_act':'')?>" href="/kassa/" data-title="Касса">
							<div class="umenu_cin"><i class="fal fa-cash-register"></i></div>
							<div class="umenu_cih">Касса</div>
						</a>
					<? else: ?>
						<div class="umenu_co">Меню</div>
						<a class="umenu_ci <?=($menu_name=='order_product'?'menu_ci_act':'')?>" href="/dashboard/order_product.php" data-title="Көрсеткіштер">
							<div class="umenu_cin"><i class="fal fa-chart-line"></i></div>
							<div class="umenu_cih">Көрсеткіштер</div>
						</a>
						<a class="umenu_ci <?=($menu_name=='orders'?'menu_ci_act':'')?>" href="/orders/" data-title="Тапсырыстар">
							<div class="umenu_cin"><i class="fal fa-list-ol"></i></div>
							<div class="umenu_cih">Тапсырыстар</div>
						</a>
						<a class="umenu_ci <?=($menu_name=='kassa'?'menu_ci_act':'')?>" href="/kassa/" data-title="Касса">
							<div class="umenu_cin"><i class="fal fa-cash-register"></i></div>
							<div class="umenu_cih">Касса</div>
						</a>
						<a class="umenu_ci <?=($menu_name=='report'?'menu_ci_act':'')?>" href="/report/" data-title="Отчет курер">
							<div class="umenu_cin"><i class="fal fa-car"></i></div>
							<div class="umenu_cih">Отчет курер</div>
						</a>
						<a class="umenu_ci <?=($menu_name=='products'?'menu_ci_act':'')?>" href="/products/" data-title="Тауар">
							<div class="umenu_cin"><i class="fal fa-box-full"></i></div>
							<div class="umenu_cih">Тауар</div>
						</a>
						<a class="umenu_ci <?=($menu_name=='users'?'menu_ci_act':'')?>" href="/users/" data-title="Клиенттер">
							<div class="umenu_cin"><i class="fal fa-users"></i></div>
							<div class="umenu_cih">Клиенттер</div>
						</a>
					<? endif ?>
				</div>
				<!-- <div class="umenu_c">
					<a class="umenu_ci <?=($menu_name=='company'?'menu_ci_act':'')?>" href="/admin/company" data-title="Компания">
						<div class="umenu_cin"><i class="fal fa-award"></i></div>
						<div class="umenu_cih">Компания</div>
					</a>
					<a class="umenu_ci" href="#" data-title="Баланс">
						<div class="umenu_cin"><i class="fal fa-wallet"></i></div>
						<div class="umenu_cih">
							<?// if (get_balance()): ?> <?//=get_balance();?> тг
							<?// else: ?> Белгісіз <?// endif ?>
						</div>
					</a>
				</div> -->
			</div>
			<div class="ub1_lx">
				<div class="ub1_lt" href="/admin/" data-title="<?=$user['name']?> <?=($user['surname']?substr($user['surname'],0,1).'.':'')?>">
					<div class="ub1_lti lazy_img" data-src="/assets/uploads/users/<?=$user['img']?>"><? if (!$user['img']): ?><i class="fal fa-user"></i><? endif ?></div>
					<div class="ub1_ltf"><?=$user['name']?> <?=($user['surname']?substr($user['surname'],0,1).'.':'')?></div>
					<div class="ub1_ltic"><i class="fal fa-ellipsis-v"></i></div>
				</div>
				<div class="menu_c">
					<div class="menu_ci user_ph_pop">
						<div class="menu_cin"><i class="fal fa-cog"></i></div>
						<div class="menu_cih">Баптаулар</div>
					</div>
					<div class="menu_ci user_edit_pop">
						<div class="menu_cin"><i class="fal fa-user"></i></div>
						<div class="menu_cih">Менің аккаунтым</div>
					</div>
					<div class="menu_ci user_ph_pop">
						<div class="menu_cin"><i class="fal fa-mobile"></i></div>
						<div class="menu_cih">Телефон номерім</div>
					</div>
					<a class="menu_ci" href="https://wa.me/<?=$site['whatsapp']?>">
						<div class="menu_cin"><i class="fal fa-comment-dots"></i></div>
						<div class="menu_cih">Көмек (Whatsapp)</div>
					</a>
					<a class="menu_ci" href="/exit.php">
						<div class="menu_cin"><i class="fal fa-sign-out"></i></div>
						<div class="menu_cih">Шығу</div>
					</a>
				</div>
			</div>
		</div>

	</div>

	<? if (@$site_set['utop']): ?>
		<div class="uhead">
			<? if ($site_set['utop_bk']): ?> <a class="uhead_lb" href="/admin/<?=$site_set['utop_bk']?>"><i class="fal fa-long-arrow-left"></i></a> <? endif ?>
			<h4 class="uhead_c"><?=$site_set['utop_nm']?></h4>
		</div>
	<? endif ?>






   <div class="pmenu">
		<div class="pmenu_c">
			<? if ($user_right['positions_id'] == 3): ?>
				<a class="pmenu_i <?=($menu_name=='kassa'?'pmenu_i_act':'')?>" href="/kassa/">
					<i class="far fa-landmark"></i>
					<span>Касса</span>
				</a>
				<a class="pmenu_i <?=($menu_name=='all_sl'?'pmenu_i_act':'')?>" href="/kassa/sl.php">
					<i class="far fa-landmark"></i>
					<span>Общый</span>
				</a>
				<a class="pmenu_i <?=($menu_name=='acc'?'pmenu_i_act':'')?>" href="/acc/">
					<i class="far fa-user"></i>
					<span>Аккаунт</span>
				</a>
			<? else: ?>
				<a class="pmenu_i txt_c <?=($menu_name=='orders'?'pmenu_i_act':'')?>" href="/orders/">
					<i class="far fa-list"></i>
					<span>Заказдар</span>
				</a>
				<a class="pmenu_i <?=($menu_name=='kassa'?'pmenu_i_act':'')?>" href="/kassa/">
				<!-- <a class="pmenu_i <?=($menu_name=='kassa'?'pmenu_i_act':'')?>" href="/kassa/ph.php"> -->
					<i class="far fa-landmark"></i>
					<span>Касса</span>
				</a>
				<a class="pmenu_i <?=($menu_name=='acc'?'pmenu_i_act':'')?>" href="/acc/">
					<i class="far fa-user"></i>
					<span>Аккаунт</span>
				</a>
			<? endif ?>
		</div>
   </div>


   <? // if ($site_set['um_menu']): ?>
		<!-- <div class="pmenu">
			<div class="pmenu_c">
					<a class="pmenu_i <?=($menu_name=='list'?'pmenu_i_act':'')?>" href="/admin/list/"><i class="fal fa-graduation-cap"></i></a>
					<a class="pmenu_i <?=($menu_name=='club'?'pmenu_i_act':'')?>" href="/admin/club/"><i class="fal fa-users-class"></i></a>
					<div class="pmenu_i pmenu_is">
						<i class="fal fa-plus"></i>
					</div>
					<a class="pmenu_i <?=($menu_name=='users'?'pmenu_i_act':'')?>" href="/admin/users/"><i class="fal fa-users"></i></a>
				<a class="pmenu_i <?=($menu_name=='user'?'pmenu_i_act':'')?>" href="/admin/acc/"><i class="fal fa-user"></i></a>
			</div>
		</div> -->
	<? // endif ?>

<? endif ?>