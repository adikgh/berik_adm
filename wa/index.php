<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (!wa_owner_ok($user_id, @$user_right['positions_id'])) { header('location: /orders/'); exit; }
	wa_owner_claim($user_id);
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

	// WhatsApp растау коды: бүкіл жүйеге ортақ баптау (config/wa_conf.php)
	$c = wa_conf();
	$conf_ok = is_writable(is_file(__DIR__.'/../config/wa_conf.php') ? __DIR__.'/../config/wa_conf.php' : __DIR__.'/../config');
	$plain = (int)mysqli_fetch_assoc(db::query("SELECT COUNT(*) n FROM `user` WHERE `password` IS NOT NULL AND `password` <> '' AND `password` NOT LIKE '\$2y\$%'"))['n'];
	function wa_mask_key($s) { $s = (string)$s; return $s === '' ? '' : (strlen($s) > 8 ? substr($s, 0, 4).'••••'.substr($s, -4) : '••••'); }
	$sites = ['admin' => 'Админ', 'delivery' => 'Курьер (доставка)', 'dist' => 'Раздача', 'ktch' => 'Кухня'];

	$menu_name = 'wa';
	$page_name = 'WhatsApp растау коды';
	$css = ['clients'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page wa_page" data-csrf="<?=$_SESSION['csrf']?>">

	<form class="cl_box wa_box wa_form" autocomplete="off">
		<div class="wa_h"><i class="fab fa-whatsapp"></i> Код қалай жіберіледі</div>
		<div class="wa_ch">
			<? foreach (['off' => ['Өшірулі', 'Код сұралмайды'], 'test' => ['Тест', 'Код экранда шығады, тек локалға'], 'green' => ['Green-API', 'Кәдімгі WhatsApp нөмірі'], 'meta' => ['Meta Cloud API', 'Ресми, бекітілген шаблон']] as $k => $v): ?>
				<label class="wa_chi"><input type="radio" name="channel" value="<?=$k?>" <?=($c['channel'] == $k ? 'checked' : '')?>><span><b><?=$v[0]?></b><small><?=$v[1]?></small></span></label>
			<? endforeach ?>
		</div>

		<div class="wa_grp wa_grp_green">
			<div class="wa_sh">Green-API <a href="https://console.green-api.com" target="_blank">console.green-api.com</a></div>
			<label class="wa_f"><span>API URL</span><input class="cl_inp" name="green_url" value="<?=htmlspecialchars($c['green_url'])?>"></label>
			<label class="wa_f"><span>idInstance</span><input class="cl_inp" name="green_instance" value="<?=htmlspecialchars($c['green_instance'])?>"></label>
			<label class="wa_f"><span>apiTokenInstance</span><input class="cl_inp" name="green_token" placeholder="<?=($c['green_token'] ? wa_mask_key($c['green_token']).' (өзгертпесеңіз бос қалдырыңыз)' : '')?>"></label>
			<label class="wa_f wa_f_w"><span>Хабарлама мәтіні (%s орнына код қойылады)</span><input class="cl_inp" name="text" value="<?=htmlspecialchars($c['text'])?>"></label>
		</div>

		<div class="wa_grp wa_grp_meta">
			<div class="wa_sh">Meta WhatsApp Cloud API <a href="https://business.facebook.com/wa/manage/" target="_blank">WhatsApp Manager</a></div>
			<label class="wa_f wa_f_w"><span>Access token (тұрақты, System User)</span><input class="cl_inp" name="meta_token" placeholder="<?=($c['meta_token'] ? wa_mask_key($c['meta_token']).' (өзгертпесеңіз бос қалдырыңыз)' : '')?>"></label>
			<label class="wa_f"><span>Phone number ID</span><input class="cl_inp" name="meta_phone_id" value="<?=htmlspecialchars($c['meta_phone_id'])?>"></label>
			<label class="wa_f"><span>Шаблон аты (Authentication)</span><input class="cl_inp" name="meta_template" value="<?=htmlspecialchars($c['meta_template'])?>" placeholder="berik_code"></label>
			<label class="wa_f"><span>Шаблон тілі</span><input class="cl_inp" name="meta_lang" value="<?=htmlspecialchars($c['meta_lang'])?>" placeholder="ru / kk / en"></label>
			<label class="wa_chk"><input type="checkbox" name="meta_button" value="1" <?=(!empty($c['meta_button']) ? 'checked' : '')?>> Шаблонда «Кодты көшіру» батырмасы бар</label>
		</div>

		<div class="wa_sh">Қай сайтқа кіргенде код сұралады</div>
		<div class="wa_sites">
			<? foreach ($sites as $k => $v): ?>
				<label class="wa_chk"><input type="checkbox" name="login[<?=$k?>]" value="1" <?=(!empty($c['login'][$k]) ? 'checked' : '')?>> <?=$v?></label>
			<? endforeach ?>
		</div>
		<label class="wa_f wa_f_s"><span>Құрылғыны неше күн есте сақтау</span><input type="number" class="cl_inp" name="trust_days" min="1" max="365" value="<?=(int)$c['trust_days']?>"></label>
		<div class="cl_small">«Парольді ұмыттым» барлық сайтта код арқылы жұмыс істейді (арна қосулы болса). Клиент кабинетінде тіркелу де осы арнамен жүреді.</div>

		<? if (!$conf_ok): ?><div class="wa_warn">config/wa_conf.php файлына жазу мүмкін емес. Папкаға жазу рұқсатын беріңіз.</div><? endif ?>
		<div class="wa_b"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> Сақтау</button></div>
	</form>

	<div class="cl_box wa_box">
		<div class="wa_h"><i class="fal fa-paper-plane"></i> Тексеру</div>
		<div class="cl_small">Сақталған баптаумен осы нөмірге тест код жібереді.</div>
		<div class="wa_row">
			<input type="tel" class="cl_inp wa_tphone" placeholder="8 (700) 000-00-00" value="<?=htmlspecialchars($user['phone'])?>">
			<button type="button" class="cl_btn cl_btn_green wa_test">Жіберу</button>
		</div>
		<div class="wa_res"></div>
	</div>

	<div class="cl_box wa_box">
		<div class="wa_h"><i class="fal fa-lock"></i> Парольдер</div>
		<div class="cl_small">Парольдер bcrypt-пен сақталады. Ескі ашық парольдер адам кірген кезде өзі шифрланады. Базада әлі ашық тұрған пароль саны: <b class="wa_plain"><?=$plain?></b>.</div>
		<? if ($plain): ?>
			<div class="cl_small">Бәрін қазір шифрлауға болады. Бұл қайтарылмайды, сондықтан алдымен базаның көшірмесін алыңыз.</div>
			<div class="wa_b"><button type="button" class="cl_btn cl_btn_light wa_hash">Барлығын қазір шифрлау</button></div>
		<? endif ?>
	</div>

</div>

<style>
	.wa_page input[type=checkbox],.wa_page input[type=radio]{-webkit-appearance:auto;appearance:auto;padding:0;margin:0;min-height:0;max-height:none;width:18px;height:18px;border:0;border-radius:0;flex:none;accent-color:#0EA768;cursor:pointer}
	.wa_page{display:flex;flex-direction:column;gap:16px;max-width:820px}
	.wa_box{padding:18px;display:flex;flex-direction:column;gap:12px}
	.wa_h{font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px}
	.wa_h .fa-whatsapp{color:#25D366}
	.wa_sh{font-weight:700;margin-top:6px;display:flex;gap:10px;align-items:baseline;flex-wrap:wrap}
	.wa_sh a{font-weight:400;font-size:13px;color:#0EA768}
	.wa_ch{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:8px}
	.wa_chi{display:flex;gap:8px;align-items:flex-start;padding:10px 12px;border:1px solid #e3e6ea;border-radius:12px;cursor:pointer}
	.wa_chi:has(input:checked){border-color:#0EA768;background:#EFFAF5}
	.wa_chi input{margin-top:2px}
	.wa_chi span{display:flex;flex-direction:column}
	.wa_chi small{color:#777;font-size:12px}
	.wa_grp{display:none;grid-template-columns:1fr 1fr;gap:10px;padding:12px;border-radius:12px;background:#F6F7F9}
	.wa_grp .wa_sh{grid-column:1/-1;margin:0}
	.wa_page[data-ch="green"] .wa_grp_green,.wa_page[data-ch="meta"] .wa_grp_meta{display:grid}
	.wa_f{display:flex;flex-direction:column;gap:4px;font-size:13px;color:#555}
	.wa_f_w{grid-column:1/-1}
	.wa_f_s{max-width:260px}
	.wa_chk{display:flex;gap:8px;align-items:center;cursor:pointer}
	.wa_chk input{width:17px;height:17px;accent-color:#0EA768}
	.wa_sites{display:flex;flex-wrap:wrap;gap:8px 20px}
	.wa_row{display:flex;gap:8px}
	.wa_row .cl_inp{flex:1;max-width:260px}
	.wa_res{font-size:14px}
	.wa_res.ok{color:#0EA768}.wa_res.err{color:#D93025;word-break:break-word}
	.wa_warn{padding:10px;border-radius:10px;background:#FFF6DA;color:#7A5B00}
	.wa_b{display:flex;justify-content:flex-end}
	@media (max-width:600px){.wa_grp{grid-template-columns:1fr}}
</style>
<script>
(function () {
	var page = document.querySelector('.wa_page'), form = page.querySelector('.wa_form')
	function ch() { var r = form.querySelector('[name=channel]:checked'); page.dataset.ch = r ? r.value : 'off' }
	form.addEventListener('change', ch); ch()
	function post(act, data, done) {
		data.append('csrf', page.dataset.csrf)
		fetch('/wa/get.php?' + act, { method: 'POST', body: data }).then(function (r) { return r.json() }).then(done)
			.catch(function () { done({ ok: false, msg: 'Байланыс қатесі' }) })
	}
	function say(t, k) { if (window.mess) mess(t, k); else alert(t) }
	form.addEventListener('submit', function (e) {
		e.preventDefault()
		post('save', new FormData(form), function (r) { say(r.msg || (r.ok ? 'Сақталды' : 'Қате'), r.ok ? 'ok' : 'err') })
	})
	page.querySelector('.wa_test').addEventListener('click', function () {
		var d = new FormData(), res = page.querySelector('.wa_res'); d.append('phone', page.querySelector('.wa_tphone').value)
		res.className = 'wa_res'; res.textContent = 'Жіберіліп жатыр...'
		post('test', d, function (r) { res.className = 'wa_res ' + (r.ok ? 'ok' : 'err'); res.textContent = r.msg })
	})
	var h = page.querySelector('.wa_hash')
	if (h) h.addEventListener('click', function () {
		if (!confirm('Барлық ашық парольдерді шифрлаймыз ба? Базаның көшірмесі алынды ма?')) return
		post('hash_all', new FormData(), function (r) { say(r.msg, r.ok ? 'ok' : 'err'); if (r.ok) { page.querySelector('.wa_plain').textContent = r.left; h.remove() } })
	})
})()
</script>

<? include "../block/footer.php"; ?>
