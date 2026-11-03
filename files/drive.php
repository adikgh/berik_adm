<? include "../config/core.php";
	include "../config/cfile.php";

	// Баптау беті: Google Drive-ты қосу/ажырату.
	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	$msg = ''; $err = '';
	// Google кіруден кейін: ?code=... (live, Web client) немесе қойылған сілтеме/код (локал, Desktop client)
	$code = (string)@$_GET['code'];
	if (!$code && isset($_POST['code'])) {
		$v = trim((string)$_POST['code']);
		if (preg_match('~[?&]code=([^&#\s]+)~', $v, $m)) $code = urldecode($m[1]); else $code = $v;
		if (preg_match('~[?&]state=([^&#\s]+)~', $v, $m)) $_GET['state'] = urldecode($m[1]);
	}
	if ($code !== '' && cfile::drive_conf()) {
		if (empty($_SESSION['gd_state']) || (string)@$_GET['state'] !== $_SESSION['gd_state']) $err = 'Сілтеме ескірген. «Google Drive қосу» батырмасын қайта басыңыз.';
		elseif (cfile::connect($code)) { $msg = 'Google Drive қосылды'; unset($_SESSION['gd_state']); }
		else $err = cfile::$err;
	}
	if (isset($_POST['disconnect'])) { cfile::disconnect(); $msg = 'Google Drive ажыратылды'; }
	if (isset($_GET['connect']) && cfile::drive_conf()) {
		$_SESSION['gd_state'] = bin2hex(random_bytes(12));
		header('location: '.cfile::auth_url($_SESSION['gd_state'])); exit;
	}
	$tok = cfile::tok();
	$local_redirect = defined('GDRIVE_REDIRECT') && !preg_match('~/files/drive\.php~', GDRIVE_REDIRECT);

	$menu_name = 'waybill';
	$page_name = 'Google Drive';
	$css = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">
	<? if ($msg): ?><div class="wb_box wbs_ok"><i class="fal fa-check"></i> <?=htmlspecialchars($msg)?></div><? endif ?>
	<? if ($err): ?><div class="wb_box wbs_err"><i class="fal fa-exclamation-triangle"></i> <?=htmlspecialchars($err)?></div><? endif ?>

	<div class="wb_box">
		<div class="wb_box_h"><div><i class="fab fa-google-drive"></i> Google Drive</div></div>
		<? if (!cfile::drive_conf()): ?>
			<p>Қосылмаған. <b>config/ai.php</b> файлына GDRIVE_CLIENT_ID мен GDRIVE_CLIENT_SECRET жазыңыз (үлгі: config/ai.example.php).</p>
		<? elseif (!cfile::drive_on()): ?>
			<p>Кілттер бар, бірақ Drive әлі қосылмаған. Батырманы басып, файлдар сақталатын Google аккаунтымен кіріңіз.</p>
			<a class="wb_btn wb_btn_green" href="?connect=1"><i class="fab fa-google"></i> Google Drive қосу</a>
			<? if ($local_redirect && !empty($_SESSION['gd_state'])): ?>
				<form method="post" class="wbs_paste">
					<p class="wb_small">Google-да рұқсат бергеннен кейін браузер «localhost» бетіне өтеді (ол ашылмауы мүмкін, бұл қалыпты). Сол беттің мекенжайын (address bar) толық көшіріп, осында қойыңыз:</p>
					<input type="text" name="code" class="wb_inp" placeholder="http://localhost/?state=...&code=..." autocomplete="off">
					<button class="wb_btn"><i class="fal fa-check"></i> Растау</button>
				</form>
			<? endif ?>
		<? else: ?>
			<p>Қосулы<?=(!empty($tok['email']) ? ': <b>'.htmlspecialchars($tok['email']).'</b>' : '')?>. Файлдар Drive-та «<?=htmlspecialchars(GDRIVE_ROOT)?>» папкасына жиналады, хостингте сақталмайды.</p>
			<form method="post" onsubmit="return confirm('Google Drive-ты ажыратамыз ба? Бұрынғы файлдар Drive-та қалады, бірақ сайт оларды аша алмайды.')">
				<button class="wb_btn wb_btn_red" name="disconnect" value="1"><i class="fal fa-unlink"></i> Ажырату</button>
			</form>
		<? endif ?>
	</div>

</div>

<style>
	.wbs_ok { color: #1a7f37; } .wbs_err { color: #c0392b; }
	.wbs_paste { margin-top: 16px; display: grid; gap: 8px; max-width: 640px; }
	.wb_box p { margin: 8px 0 12px; line-height: 1.5; }
</style>

<? include "../block/footer.php"; ?>
