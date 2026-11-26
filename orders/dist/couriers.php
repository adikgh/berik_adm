<? include "../../config/core.php";
	// Раздача: курьерлер тізімі — қосу, өзгерту, тізімнен шығару, қоңырау (бұрынғы курьер сайтындағы users/ беті). Әрекеттер: orders/dist/get.php
	if (!$user_id) { header('location: /'); exit; }
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

	$cmp = (int)$company;
	$staff = db::query("select s.id sid, u.id, u.name, u.surname, u.phone, u.img from company_staff s join user u on u.id = s.user_id where s.positions_id = 6 and s.company_id = '$cmp' order by s.ins_dt desc");
	$st_cnt = $staff ? mysqli_num_rows($staff) : 0;

	// site setting
	$menu_name = 'dist_acc';
	$dist_menu = 'acc';
	$page_name = t::w('Couriers', 'Курьерлер');
	$page_back = ['/orders/dist/acc.php', t::w('Account', 'Аккаунт')];
	$css = ['dist'];
	$js = ['dist'];
?>
<? include "../../block/header.php"; ?>
	<div class="dsp">
	<div class="bp_page dsp_cr" data-csrf="<?=$_SESSION['csrf']?>">

		<div class="bp_head">
			<div class="bl_c">
				<div class="bp_head_t">
					<div class="bp_cnt">
						<span><?=t::w('Total count', 'Барлығы')?></span>
						<b><?=$st_cnt?></b>
					</div>
					<button class="bp_add on_cr_add" type="button"><i class="far fa-plus"></i> <span><?=t::w('Add courier', 'Курьер қосу')?></span></button>
				</div>
			</div>
		</div>

		<div class="bl_c">
			<div class="bp_users">
				<? if ($st_cnt): ?>
					<? while ($c = mysqli_fetch_assoc($staff)): $c_nm = trim($c['name'].' '.$c['surname']); $c_ph = substr(preg_replace('/\D/', '', (string)$c['phone']), -10);
						$c_img = ($c['img'] && is_file($_SERVER['DOCUMENT_ROOT'].'/assets/uploads/users/'.basename($c['img']))) ? '/assets/uploads/users/'.rawurlencode(basename($c['img'])) : ''; ?>
						<div class="bp_user">
							<div class="bp_user_m on_cr_edit" data-id="<?=(int)$c['id']?>" data-name="<?=htmlspecialchars((string)$c['name'])?>" data-surname="<?=htmlspecialchars((string)$c['surname'])?>" data-phone="<?=htmlspecialchars($c_ph)?>">
								<div class="bp_user_av"<?=($c_img ? ' style="background-image:url(\''.htmlspecialchars($c_img).'\')"' : '')?>><? if (!$c_img): ?><?=htmlspecialchars(mb_strtoupper(mb_substr($c_nm, 0, 1)))?><? endif ?></div>
								<div class="bp_user_t">
									<div class="bp_user_n"><?=htmlspecialchars($c_nm)?></div>
									<div class="bp_user_p"><?=(strlen($c_ph) == 10 ? '8 ('.substr($c_ph, 0, 3).') '.substr($c_ph, 3, 3).'-'.substr($c_ph, 6, 2).'-'.substr($c_ph, 8, 2) : htmlspecialchars($c_ph))?></div>
								</div>
							</div>
							<? if (strlen($c_ph) == 10): ?><a class="bp_user_b bp_user_call" href="tel:8<?=$c_ph?>" title="<?=t::h('Call', 'Қоңырау')?>" aria-label="<?=t::h('Call', 'Қоңырау')?>"><i class="far fa-phone-alt"></i></a><? endif ?>
							<button type="button" class="bp_user_b bp_user_del on_cr_del" data-id="<?=(int)$c['sid']?>" data-confirm="<?=t::h('Remove the courier from the list?', 'Курьерді тізімнен өшіру керек пе?')?>" title="<?=t::h('Delete', 'Өшіру')?>" aria-label="<?=t::h('Delete', 'Өшіру')?>"><i class="far fa-trash-alt"></i></button>
						</div>
					<? endwhile ?>
				<? else: ?>
					<div class="ds_nr"><i class="fal fa-users"></i><p><?=t::w('No couriers yet', 'Әзірге курьер жоқ')?></p></div>
				<? endif ?>
			</div>
		</div>

	</div>

	<!-- Курьер қосу / өзгерту: астынан шығатын парақ -->
	<div class="bp_dr bp_cr_sh">
		<div class="bp_dr_bg"></div>
		<div class="bp_dr_c">
			<div class="bp_dr_grab"></div>
			<div class="bp_sh_h"><span class="bp_cr_h" data-add="<?=t::h('Add courier', 'Курьер қосу')?>" data-edit="<?=t::h('Edit courier', 'Курьерді өзгерту')?>"></span> <span class="bp_dr_x"><i class="far fa-times"></i></span></div>
			<form class="prof_form bp_cr_form" autocomplete="off">
				<input type="hidden" name="id" value="">
				<label><?=t::w('First name', 'Аты')?><input name="name" maxlength="60" required></label>
				<label><?=t::w('Surname', 'Тегі')?><input name="surname" maxlength="60"></label>
				<label><?=t::w('Phone number', 'Телефон нөмірі')?><input name="phone" type="tel" inputmode="tel" maxlength="20" placeholder="8 (700) 000-00-00" required></label>
				<label class="bp_cr_pass"><?=t::w('New password', 'Жаңа құпиясөз')?><input name="pass" type="text" maxlength="40" autocomplete="new-password" placeholder="<?=t::h('Leave empty to keep it', 'Өзгертпесеңіз бос қалдырыңыз')?>"></label>
				<div class="prof_hint bp_cr_hint"><?=t::w('A new courier logs in with password 123456', 'Жаңа курьер 123456 құпиясөзімен кіреді')?></div>
				<div class="prof_msg"></div>
				<button type="submit"><?=t::w('Save', 'Сақтау')?></button>
			</form>
		</div>
	</div>
	</div>

	<script>window.DSP_W = <?=json_encode([
		'phone' => t::w('Enter the phone number correctly', 'Телефон нөмірін дұрыс жазыңыз'),
		'phone_busy' => t::w('This number belongs to another person', 'Бұл нөмір басқа адамда тіркелген'),
		'name' => t::w('Enter the name', 'Атын жазыңыз'),
		'pass' => t::w('Password must be at least 6 characters', 'Құпиясөз кемінде 6 таңба болсын'),
		'busy' => t::w('This person already works in the company', 'Бұл адам компанияда бұрыннан жұмыс істейді'),
		'err' => t::w('Not saved, try again', 'Сақталмады, қайта көріңіз'),
	], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)?>;</script>
<? include "../../block/footer.php"; ?>
