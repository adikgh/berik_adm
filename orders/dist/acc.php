<? include "../../config/core.php";
	// Раздача: Аккаунт беті (бұрынғы курьер сайтындағы «Тағы» / acc/dist.php): профиль мен фото, тіл, бүгінгі смена, курьерлер, Оплата, құпиясөз, көмек, шығу.
	// Аты-жөні мен құпиясөз — аккаунт поп-апы (block/acc_pop.php, /acc/get.php). Фото — orders/dist/get.php?acc_img
	if (!$user_id) { header('location: /'); exit; }
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

	$cmp = (int)$company;
	$company_d = fun::company($company);
	$pos_nm = [1 => t::w('Creator', 'Создатель'), 2 => t::w('Director', 'Директор'), 3 => t::w('Manager', 'Басқарушы'), 8 => t::w('Dispatch', 'Раздача')];
	$u_img = (@$user['img'] && is_file($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/users/'.basename($user['img']))) ? '/assets/uploads/users/'.rawurlencode(basename($user['img'])) : '';
	$u_nm = trim(@$user['name'].' '.@$user['surname']);
	$u_ph = (string)@$user['phone'];
	$u_ph = strlen($u_ph) == 10 ? '8 ('.substr($u_ph, 0, 3).') '.substr($u_ph, 3, 3).'-'.substr($u_ph, 6, 2).'-'.substr($u_ph, 8, 2) : $u_ph;

	// Бүгінгі смена: бүгін касса сменасын ашқан, тапсырыс статусын өзгерткен немесе тапсырыс алған курьер
	$d0 = date('Y-m-d 00:00:00'); $d1 = date('Y-m-d 00:00:00', strtotime('-1 day'));
	$sh_q = db::query("SELECT u.id, u.name, u.surname, MIN(x.t) t, MAX(x.op) op,
			(SELECT s.positions_id FROM company_staff s WHERE s.user_id = u.id AND s.company_id = '$cmp' ORDER BY s.id LIMIT 1) pos
		FROM (
			SELECT user_id uid, ins_dt t, (close_dt IS NULL) op FROM retail_shifts WHERE company_id = '$cmp' AND (ins_dt >= '$d0' OR close_dt IS NULL)
			UNION ALL SELECT l.user_id, l.ins_dt, 0 FROM retail_orders o JOIN retail_orders_log l ON l.order_id = o.id WHERE o.company_id = '$cmp' AND o.ins_dt >= '$d1' AND l.ins_dt >= '$d0'
			UNION ALL SELECT `сourier_id`, ins_dt, 0 FROM retail_orders WHERE company_id = '$cmp' AND ins_dt >= '$d0' AND `сourier_id` > 0
		) x JOIN user u ON u.id = x.uid GROUP BY u.id ORDER BY t");
	$sh_n = $sh_q ? mysqli_num_rows($sh_q) : 0;
	$sh_pos = [];
	$pq = db::query("SELECT id, name_kz, name_ru FROM company_staff_positions");
	while ($pr = mysqli_fetch_assoc($pq)) $sh_pos[$pr['id']] = (t::lang() == 'kz' ? $pr['name_kz'] : '') ?: $pr['name_ru'];
	$cr_n = (int)@mysqli_fetch_row(db::query("SELECT COUNT(*) FROM company_staff WHERE positions_id = 6 AND company_id = '$cmp'"))[0];
	$wa = preg_replace('/\D/', '', (string)@$site['whatsapp']);

	// site setting
	$menu_name = 'dist_acc';
	$dist_menu = 'acc';
	$page_name = t::w('Account', 'Аккаунт');
	$css = ['dist'];
	$js = ['dist'];
?>
<? include "../../block/header.php"; ?>
	<div class="dsp">
	<div class="bp_page bp_acc">
		<div class="bl_c dsp_acc">

			<div class="bp_prof prof_ed" data-csrf="<?=$_SESSION['csrf']?>"
				data-e-type="<?=t::h('js:img_type', 'Тек JPG, PNG немесе WEBP фото')?>" data-e-size="<?=t::h('js:img_size', 'Фото 8 МБ-тан аспауы керек')?>" data-e-save="<?=t::h('js:not_saved', 'Сақталмады, қайта көріңіз')?>">
				<label class="prof_av" title="<?=t::h('Change photo', 'Фотоны өзгерту')?>">
					<div class="bp_prof_av"<?=($u_img ? ' style="background-image:url(\''.htmlspecialchars($u_img).'\')"' : '')?>><? if (!$u_img): ?><?=htmlspecialchars(mb_strtoupper(mb_substr($u_nm, 0, 1)))?><? endif ?></div>
					<span class="prof_av_b"><i class="far fa-camera"></i></span>
					<input type="file" class="prof_img" accept="image/jpeg,image/png,image/webp" aria-label="<?=t::h('Change photo', 'Фотоны өзгерту')?>">
				</label>
				<div class="bp_prof_t">
					<div class="bp_prof_n acp_nm"><?=htmlspecialchars($u_nm)?></div>
					<div class="bp_prof_p"><?=htmlspecialchars($u_ph)?></div>
					<div class="bp_prof_r">
						<? if (isset($pos_nm[(int)@$user_right['positions_id']])): ?><span><?=$pos_nm[(int)$user_right['positions_id']]?></span><? endif ?>
						<? if (@$company_d['name']): ?><span><?=htmlspecialchars($company_d['name'])?></span><? endif ?>
					</div>
				</div>
				<button class="prof_ed_btn ac_open" type="button" title="<?=t::h('Edit', 'Өзгерту')?>" aria-label="<?=t::h('Edit', 'Өзгерту')?>"><i class="far fa-pen"></i></button>
			</div>

			<div class="bp_box">
				<div class="bp_box_h"><i class="far fa-globe"></i> <?=t::w('Language', 'Тіл')?></div>
				<div class="bp_lang">
					<? foreach ($langs_short as $lk => $lv): ?>
						<a class="bp_lang_i <?=($ui_lang == $lk ? 'bp_lang_act' : '')?>" href="?lang=<?=$lk?>" title="<?=htmlspecialchars((string)@$langs[$lk])?>"><?=$lv?></a>
					<? endforeach ?>
				</div>
			</div>

			<div class="bp_box bp_list">
				<div class="bp_box_h"><i class="far fa-user-friends"></i> <?=t::w('Staff', 'Жұмысшылар')?></div>
				<div class="bp_sub"><?=t::w('Today shift', 'Бүгінгі смена')?><? if ($sh_n): ?> <b><?=$sh_n?></b><? endif ?></div>
				<? while ($sh_q && ($sh = mysqli_fetch_assoc($sh_q))): $s_nm = trim($sh['name'].' '.$sh['surname']); ?>
					<div class="bp_li bp_sh">
						<span class="bp_li_ic"><?=htmlspecialchars(mb_strtoupper(mb_substr($s_nm, 0, 1)))?></span>
						<span class="bp_li_t"><?=htmlspecialchars($s_nm)?><small><?=htmlspecialchars((string)@$sh_pos[$sh['pos']])?> · <?=date('H:i', strtotime($sh['t']))?></small></span>
						<? if ($sh['op']): ?><span class="bp_sh_on" title="<?=t::h('Shift open', 'Смена ашық')?>"></span><? endif ?>
					</div>
				<? endwhile ?>
				<? if (!$sh_n): ?><div class="bp_li bp_sh bp_sh_e"><span class="bp_li_t"><small><?=t::w('Nobody on shift yet', 'Бүгін әзірге ешкім жоқ')?></small></span></div><? endif ?>
				<a class="bp_li" href="/orders/dist/couriers.php">
					<span class="bp_li_ic"><i class="far fa-motorcycle"></i></span>
					<span class="bp_li_t"><?=t::w('Couriers', 'Курьерлер')?></span>
					<? if ($cr_n): ?><b class="bp_li_n"><?=$cr_n?></b><? endif ?>
					<i class="far fa-chevron-right bp_li_ar"></i>
				</a>
			</div>

			<div class="bp_box bp_list">
				<a class="bp_li" href="/pay/">
					<span class="bp_li_ic"><i class="far fa-wallet"></i></span>
					<span class="bp_li_t"><?=t::w('Plan payment', 'Оплата')?></span>
					<i class="far fa-chevron-right bp_li_ar"></i>
				</a>
				<a class="bp_li ac_open" href="javascript:;" data-tab="pass">
					<span class="bp_li_ic"><i class="far fa-key"></i></span>
					<span class="bp_li_t"><?=t::w('Change password', 'Құпиясөзді өзгерту')?></span>
					<i class="far fa-chevron-right bp_li_ar"></i>
				</a>
				<? if (strlen($wa) >= 10): ?>
					<a class="bp_li" href="https://wa.me/<?=$wa?>" target="_blank" rel="noopener">
						<span class="bp_li_ic"><i class="fab fa-whatsapp"></i></span>
						<span class="bp_li_t"><?=t::w('Help (WhatsApp)', 'Көмек (WhatsApp)')?></span>
						<i class="far fa-chevron-right bp_li_ar"></i>
					</a>
				<? endif ?>
			</div>

			<a class="bp_exit" href="/exit.php">
				<i class="far fa-sign-out"></i>
				<span><?=t::w('Log out', 'Шығу')?></span>
			</a>

		</div>
	</div>
	</div>
<? include "../../block/footer.php"; ?>
