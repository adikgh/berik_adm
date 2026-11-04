<? if (!@$user_id) return; // Аккаунт поп-апы: мәзірдегі «Аккаунт» басқанда ашылады (аты-жөні + пароль ауыстыру), 2026-11-04
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$acp_ph = (string)@$user['phone'];
	$acp_ph = strlen($acp_ph) == 10 ? '+7 ('.substr($acp_ph, 0, 3).') '.substr($acp_ph, 3, 3).'-'.substr($acp_ph, 6, 2).'-'.substr($acp_ph, 8, 2) : $acp_ph;
	$acp_v = @filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/account.js').'.'.@filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/account.css');
?>
<link rel="stylesheet" href="/assets/css/account.css?v=<?=$acp_v?>">
<div class="acp" data-csrf="<?=$_SESSION['csrf']?>" aria-hidden="true">
	<div class="acp_bg acp_x"></div>
	<div class="acp_c" role="dialog" aria-modal="true">
		<div class="acp_hd">
			<div class="acp_av ava_box"><?=(@$ava_html ?: '<i class="fal fa-user"></i>')?></div>
			<div class="acp_hd_t">
				<div class="acp_nm"><?=htmlspecialchars(trim(@$user['name'].' '.@$user['surname']))?></div>
				<span><?=htmlspecialchars($acp_ph)?></span>
			</div>
			<div class="acp_close acp_x"><i class="fal fa-times"></i></div>
		</div>

		<div class="acp_tabs">
			<div class="acp_tab acp_tab_act" data-t="prof"><?=t::w('Profile', 'Жеке деректер')?></div>
			<div class="acp_tab" data-t="pass"><?=t::w('Password', 'Пароль')?></div>
		</div>

		<form class="acp_f acp_prof" data-t="prof" autocomplete="off">
			<label><span>Аты</span><input type="text" name="name" maxlength="50" required value="<?=htmlspecialchars((string)@$user['name'])?>"></label>
			<label><span>Тегі</span><input type="text" name="surname" maxlength="50" value="<?=htmlspecialchars((string)@$user['surname'])?>"></label>
			<label><span>Телефон (кіру логині)</span><input type="text" value="<?=htmlspecialchars($acp_ph)?>" disabled></label>
			<button type="submit" class="acp_btn"><i class="fal fa-check"></i> Сақтау</button>
		</form>

		<form class="acp_f acp_pass dsp_n" data-t="pass" autocomplete="off">
			<label><span>Қазіргі пароль</span><div class="acp_pw"><input type="password" name="old" autocomplete="current-password" required><i class="far fa-eye-slash acp_eye"></i></div></label>
			<label><span>Жаңа пароль</span><div class="acp_pw"><input type="password" name="new" autocomplete="new-password" minlength="6" required><i class="far fa-eye-slash acp_eye"></i></div></label>
			<label><span>Жаңа парольді қайталаңыз</span><div class="acp_pw"><input type="password" name="new2" autocomplete="new-password" minlength="6" required><i class="far fa-eye-slash acp_eye"></i></div></label>
			<div class="acp_hint">Кемінде 6 таңба. Басқа құрылғылардағы кіру өзі жабылады.</div>
			<button type="submit" class="acp_btn"><i class="fal fa-key"></i> Парольді ауыстыру</button>
		</form>
	</div>
</div>
<script src="/assets/js/account.js?v=<?=$acp_v?>" defer></script>
