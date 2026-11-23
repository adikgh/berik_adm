<?php
// Жүйе кілттері: базада sys_setting (k, v). Файл өзгертпей осы беттен ауыстырылады.
// Құпия мән браузерге толық берілмейді: тек басы/соңы (abcd••••wxyz). Бос өріс — «өзгертпеу».
require_once DEV_ADM . '/config/sys_set.php';
require_once DEV_ADM . '/config/claude.php'; // claude::CHEAP/MID/TOP модельдері

// [кілт => [атауы, түрі]]: secret — құпия, text — ашық мән, model — модель тізімі, url — сілтеме
function dkeys_groups() {
	return [
		'ai' => ['Claude AI', 'fa-robot', [
			'anthropic_key' => ['Anthropic API key', 'secret'],
			'claude_model' => [t::w('Model', 'Модель'), 'model'],
		]],
		'apify' => ['Apify (Instagram)', 'fa-spider', [
			'apify_token' => ['Apify token', 'secret'],
		]],
		'gdrive' => ['Google Drive', 'fa-hdd', [
			'gdrive_client_id' => ['Client ID', 'text'],
			'gdrive_client_secret' => ['Client secret', 'secret'],
			'gdrive_redirect' => ['Redirect URI', 'url'],
			'gdrive_root' => [t::w('Root folder', 'Негізгі папка'), 'text'],
		]],
		'link' => [t::w('Client link', 'Клиент сілтемесі'), 'fa-link', [
			'cabinet_url' => [t::w('Cabinet address (live)', 'Кабинет адресі (live)'), 'url'],
			'client_link_secret' => [t::w('Link secret', 'Сілтеме құпиясы'), 'secret'],
		]],
		'auth' => [t::w('Sign-in keys', 'Кіру кілттері'), 'fa-user-lock', [
			'auth_key_adm' => [t::w('Admin', 'Админ'), 'auth'],
			'auth_key_dlv' => [t::w('Courier / dispatch', 'Курьер / раздача'), 'auth'],
			'auth_key_ktc' => [t::w('Kitchen', 'Кухня'), 'auth'],
			'auth_key_my' => [t::w('Client cabinet', 'Клиент кабинеті'), 'auth'],
		]],
		'map' => [t::w('2GIS map', '2GIS карта'), 'fa-map-marked-alt', [
			'map_2gis_key' => ['2GIS API key', 'secret'],
		]],
		'smsc' => ['SMSC', 'fa-sms', [
			'smsc_login' => [t::w('Login', 'Логин'), 'text'],
			'smsc_password' => [t::w('Password', 'Пароль'), 'secret'],
			'smsc_sender' => [t::w('Sender name', 'Жіберуші аты'), 'text'],
		]],
	];
}
function dkeys_all() { $r = []; foreach (dkeys_groups() as $g) foreach ($g[2] as $k => $v) $r[$k] = $v[1]; return $r; }
function dkeys_models() { return ['auto', claude::CHEAP, claude::MID, claude::TOP]; }
function dkeys_mask($s) { $s = (string)$s; return $s === '' ? '' : (strlen($s) > 12 ? substr($s, 0, 4) . '••••' . substr($s, -4) : '••••'); }
function dkeys_meta() { return dev::rows("select k, upd_dt, upd_user from sys_setting", 'k'); }

// ---------- Ескі файлдардан алу: тек базада бос кілттер толтырылады ----------
// PHP файлды include етпей оқиды: const ATЫ = '...'; не define('ATЫ', '...');
function dkeys_file_const($f, $name) {
	if (!is_file($f)) return '';
	$s = (string)@file_get_contents($f);
	if (!preg_match('~(?:const\s+' . $name . '\s*=|define\(\s*[\'"]' . $name . '[\'"]\s*,)\s*\'((?:[^\'\\\\]|\\\\.)*)\'~', $s, $m)) return '';
	return str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1]);
}
function dkeys_file_ret($f) { // <?php return '...'; (auth_key.php, map_key.php, client_link_secret.php)
	if (!is_file($f)) return '';
	$v = (function ($p) { return include $p; })($f);
	return is_string($v) ? trim($v) : '';
}
function dkeys_import() {
	$c = DEV_ADM . '/config/'; $root = dirname(DEV_ADM) . '/';
	$v = [];
	foreach (['anthropic_key' => 'ANTHROPIC_API_KEY', 'claude_model' => 'CLAUDE_MODEL', 'apify_token' => 'APIFY_TOKEN', 'gdrive_client_id' => 'GDRIVE_CLIENT_ID',
		'gdrive_client_secret' => 'GDRIVE_CLIENT_SECRET', 'gdrive_redirect' => 'GDRIVE_REDIRECT', 'gdrive_root' => 'GDRIVE_ROOT'] as $k => $n) $v[$k] = dkeys_file_const($c . 'ai.php', $n);
	if (is_file($c . 'gdrive_token.php')) {
		$raw = (string)@file_get_contents($c . 'gdrive_token.php');
		$t = json_decode(substr($raw, (int)strpos($raw, "\n") + 1), true);
		if (is_array($t) && !empty($t['refresh_token'])) $v['gdrive_token'] = json_encode($t, JSON_UNESCAPED_UNICODE);
	}
	$v['client_link_secret'] = dkeys_file_ret($c . 'client_link_secret.php') ?: dkeys_file_const($c . 'client_link.php', 'CLIENT_LINK_SECRET');
	$v['auth_key_adm'] = dkeys_file_ret($c . 'auth_key.php');
	foreach (['dlv' => 'dlv.berik.c', 'ktc' => 'ktc.berik.c', 'my' => 'my.berik.c'] as $s => $dir) $v['auth_key_' . $s] = dkeys_file_ret($root . $dir . '/config/auth_key.php');
	if (is_file($c . 'wa_conf.php')) { $w = (function ($p) { return include $p; })($c . 'wa_conf.php'); if (is_array($w)) $v['wa_conf'] = json_encode($w, JSON_UNESCAPED_UNICODE); }
	$v['map_2gis_key'] = dkeys_file_ret($root . 'dlv.berik.c/config/map_key.php');
	foreach (['smsc_login' => 'SMSC_LOGIN', 'smsc_password' => 'SMSC_PASSWORD', 'smsc_sender' => 'SMSC_SENDER'] as $k => $n) $v[$k] = dkeys_file_const($root . 'my.berik.c/config/notify.php', $n);

	global $user_id;
	$done = [];
	foreach ($v as $k => $val) {
		if ($val === '' || sys_set($k) !== '') continue;
		if (strpos($k, 'auth_key_') === 0 && strlen($val) < 32) continue;
		if (sys_set_put($k, $val, (int)$user_id)) $done[] = $k;
	}
	return $done;
}
