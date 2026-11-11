<?php include "../config/core.php";
	dev_only('wa');
	require __DIR__ . '/lib.php';
	$menu = 'wa'; $page_title = 'WhatsApp';
	$page_sub = t::w('Confirmation code for sign-in and password reset. One setting for the whole system.', 'Кіру мен парольді қалпына келтіру коды. Бүкіл жүйеге бір баптау.');
	$wc = dwa_conf();
	$conf_ok = is_writable(is_file(dwa_path()) ? dwa_path() : dirname(dwa_path()));
	$plain = dwa_plain();
	$sites = ['admin' => t::w('Admin', 'Админ'), 'delivery' => t::w('Courier (delivery)', 'Курьер (доставка)'), 'dist' => t::w('Dispatch', 'Раздача'), 'ktch' => t::w('Kitchen', 'Кухня')];
	$keep = ' (' . t::w('leave empty to keep', 'өзгертпесеңіз бос қалдырыңыз') . ')';
?>
<? include "../block/header.php"; ?>
<div class="wa_page">
	<form class="card wa_form" data-post="get.php?save" autocomplete="off">
		<div class="card_h"><h2><i class="fab fa-whatsapp" style="color:#25D366"></i> <?=t::w('How the code is sent', 'Код қалай жіберіледі')?></h2></div>
		<div class="wa_ch">
			<? foreach (['off' => [t::w('Off', 'Өшірулі'), t::w('No code is asked', 'Код сұралмайды')], 'test' => [t::w('Test', 'Тест'), t::w('Code is shown on screen, local only', 'Код экранда шығады, тек локалға')], 'green' => ['Green-API', t::w('Regular WhatsApp number', 'Кәдімгі WhatsApp нөмірі')], 'meta' => ['Meta Cloud API', t::w('Official, approved template', 'Ресми, бекітілген шаблон')]] as $k => $v): ?>
				<label class="wa_chi"><input type="radio" name="channel" value="<?=$k?>" <?=$wc['channel'] == $k ? 'checked' : ''?>><span><b><?=$v[0]?></b><small><?=$v[1]?></small></span></label>
			<? endforeach ?>
		</div>
		<div class="wa_grp wa_grp_green">
			<div class="wa_sh">Green-API <a href="https://console.green-api.com" target="_blank">console.green-api.com</a></div>
			<label>API URL<input name="green_url" value="<?=dev::h($wc['green_url'])?>"></label>
			<label>idInstance<input name="green_instance" value="<?=dev::h($wc['green_instance'])?>"></label>
			<label>apiTokenInstance<input name="green_token" placeholder="<?=$wc['green_token'] ? dev::h(dwa_mask($wc['green_token']) . $keep) : ''?>"></label>
			<label class="wa_w"><?=t::w('Message text (%s is replaced by the code)', 'Хабарлама мәтіні (%s орнына код қойылады)')?><input name="text" value="<?=dev::h($wc['text'])?>"></label>
		</div>
		<div class="wa_grp wa_grp_meta">
			<div class="wa_sh">Meta WhatsApp Cloud API <a href="https://business.facebook.com/wa/manage/" target="_blank">WhatsApp Manager</a></div>
			<label class="wa_w">Access token (System User)<input name="meta_token" placeholder="<?=$wc['meta_token'] ? dev::h(dwa_mask($wc['meta_token']) . $keep) : ''?>"></label>
			<label>Phone number ID<input name="meta_phone_id" value="<?=dev::h($wc['meta_phone_id'])?>"></label>
			<label><?=t::w('Template name (Authentication)', 'Шаблон аты (Authentication)')?><input name="meta_template" value="<?=dev::h($wc['meta_template'])?>" placeholder="berik_code"></label>
			<label><?=t::w('Template language', 'Шаблон тілі')?><input name="meta_lang" value="<?=dev::h($wc['meta_lang'])?>" placeholder="ru / kk / en"></label>
			<label class="chk wa_w"><input type="checkbox" name="meta_button" value="1" <?=!empty($wc['meta_button']) ? 'checked' : ''?>> <?=t::w('The template has a «Copy code» button', 'Шаблонда «Кодты көшіру» батырмасы бар')?></label>
		</div>
		<div class="wa_sh"><?=t::w('Which sites ask for the code at sign-in', 'Қай сайтқа кіргенде код сұралады')?></div>
		<div class="wa_sites">
			<? foreach ($sites as $k => $v): ?><label class="chk"><input type="checkbox" name="login[<?=$k?>]" value="1" <?=!empty($wc['login'][$k]) ? 'checked' : ''?>> <?=$v?></label><? endforeach ?>
		</div>
		<label class="wa_s"><?=t::w('Remember the device, days', 'Құрылғыны неше күн есте сақтау')?><input type="number" name="trust_days" min="1" max="365" value="<?=(int)$wc['trust_days']?>"></label>
		<p class="note"><?=t::w('«Forgot password» works by code on all sites when a channel is on. Client sign-up in the cabinet uses the same channel.', '«Парольді ұмыттым» барлық сайтта код арқылы жұмыс істейді (арна қосулы болса). Клиент кабинетінде тіркелу де осы арнамен жүреді.')?></p>
		<? if (!$conf_ok): ?><p class="wa_warn"><?=t::w('Cannot write config/wa_conf.php in the admin folder. Give the folder write permission.', 'Админ папкасындағы config/wa_conf.php файлына жазу мүмкін емес. Папкаға жазу рұқсатын беріңіз.')?></p><? endif ?>
		<div class="wa_b"><button class="btn btn_g" type="submit"><i class="fal fa-check"></i><?=t::w('Save', 'Сақтау')?></button></div>
	</form>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-paper-plane"></i> <?=t::w('Check', 'Тексеру')?></h2></div>
		<p class="note" style="margin-top:0"><?=t::w('Sends a test code to this number with the saved settings.', 'Сақталған баптаумен осы нөмірге тест код жібереді.')?></p>
		<div class="wa_row">
			<input type="tel" class="wa_tphone fr_phone" placeholder="8 (700) 000-00-00" value="<?=dev::h((string)@$user['phone'])?>">
			<button type="button" class="btn btn_g wa_test"><?=t::w('Send', 'Жіберу')?></button>
		</div>
		<div class="wa_res"></div>
	</div>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-lock"></i> <?=t::w('Passwords', 'Парольдер')?></h2></div>
		<p class="note" style="margin-top:0"><?=t::w('Passwords are stored with bcrypt. Old plain passwords are encrypted when the person signs in. Plain passwords left in the database:', 'Парольдер bcrypt-пен сақталады. Ескі ашық парольдер адам кірген кезде өзі шифрланады. Базада әлі ашық тұрған пароль саны:')?> <b class="wa_plain"><?=$plain?></b></p>
		<? if ($plain): ?>
			<p class="note"><?=t::w('You can encrypt them all now. This cannot be undone, so back up the database first.', 'Бәрін қазір шифрлауға болады. Бұл қайтарылмайды, сондықтан алдымен базаның көшірмесін алыңыз.')?></p>
			<div class="wa_b"><button type="button" class="btn btn_l wa_hash"><?=t::w('Encrypt all now', 'Барлығын қазір шифрлау')?></button></div>
		<? endif ?>
	</div>
</div>
<style>
	.wa_page{display:flex;flex-direction:column;gap:16px;max-width:860px}
	.wa_page .card{margin:0;display:flex;flex-direction:column;gap:12px}
	.wa_ch{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:8px}
	.wa_chi{display:flex !important;gap:10px;align-items:flex-start;padding:12px 14px;border:1px solid var(--line);border-radius:12px;cursor:pointer;color:var(--tx) !important;font-weight:400 !important}
	.wa_chi:has(input:checked){border-color:var(--g);background:var(--gl)}
	.dv_c .wa_chi input{-webkit-appearance:auto;appearance:auto;width:18px;height:18px;min-height:0;padding:0;margin:2px 0 0;box-shadow:none;accent-color:var(--g);flex:none}
	.wa_chi span{display:flex;flex-direction:column;font-size:14px}
	.wa_chi b{font-weight:700}
	.wa_chi small{color:var(--mut);font-size:12px}
	.wa_grp{display:none;grid-template-columns:1fr 1fr;gap:12px;padding:14px;border-radius:12px;background:#F6F7F9}
	.wa_page[data-ch="green"] .wa_grp_green,.wa_page[data-ch="meta"] .wa_grp_meta{display:grid}
	.wa_sh{grid-column:1/-1;font-weight:700;display:flex;gap:10px;align-items:baseline;flex-wrap:wrap}
	.wa_sh a{font-weight:400;font-size:13px;color:var(--g)}
	.wa_w{grid-column:1/-1}
	.wa_sites{display:flex;flex-wrap:wrap;gap:10px 22px}
	.wa_s{max-width:280px}
	.wa_row{display:flex;gap:8px}
	.wa_row input{max-width:280px}
	.wa_res{font-size:14px}.wa_res.ok{color:var(--g)}.wa_res.err{color:#D93025;word-break:break-word}
	.wa_warn{padding:10px 12px;border-radius:10px;background:#FFF6DA;color:#7A5B00}
	.wa_b{display:flex;justify-content:flex-end}
	@media (max-width:600px){.wa_grp{grid-template-columns:1fr}}
</style>
<script>
(function () {
	var page = document.querySelector('.wa_page'), form = page.querySelector('.wa_form');
	function w(k, d) { return (window.bw || {})[k] || d; }
	function ch() { var r = form.querySelector('[name=channel]:checked'); page.dataset.ch = r ? r.value : 'off'; }
	form.addEventListener('change', ch); ch();
	page.querySelector('.wa_test').addEventListener('click', function () {
		var res = page.querySelector('.wa_res'); res.className = 'wa_res'; res.textContent = w('Sending...', 'Жіберіліп жатыр...');
		devPost('get.php?test', { phone: page.querySelector('.wa_tphone').value }).then(function (r) { res.className = 'wa_res ' + (r.ok ? 'ok' : 'err'); res.textContent = r.msg; })
			.catch(function () { res.className = 'wa_res err'; res.textContent = w('Network error', 'Желі қатесі'); });
	});
	var h = page.querySelector('.wa_hash');
	if (h) h.addEventListener('click', function () {
		devAsk(w('Encrypt all plain passwords? Is the database backed up?', 'Барлық ашық парольдерді шифрлаймыз ба? Базаның көшірмесі алынды ма?'), function () {
			devPost('get.php?hash_all', {}).then(function (r) { toast(r.msg, !r.ok); if (r.ok) { page.querySelector('.wa_plain').textContent = r.left; h.remove(); } });
		}, { ok: h.textContent.trim(), danger: true });
	});
})();
</script>
<? include "../block/footer.php"; ?>
