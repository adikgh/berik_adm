<?php
// Әзірлеуші панелі: админ сайтының алиасы. Хост dev.* / devb.* болса .htaccess бәрін осы dev/ папкаға жібереді
// (dev.berik.c → adm.berik.c, devb.gprog.kz → admb папкасы). Дизайн, тіл, кіру формасы, база - админдікі.
// Кіру компания жұмысшыларынан бөлек: dev_staff кестесі, 5 рөл (config/lib.php → dev::$roles, dev::$access).

// Қате болса ақ экран емес, қысқа себебі шығады (live-та 500 себебін көру үшін). Пароль мен толық жол көрсетілмейді.
function dev_fatal_show($msg, $file, $line) {
	if (!headers_sent()) { http_response_code(500); header('Content-Type: text/html; charset=utf-8'); }
	$msg = str_replace(dirname(__DIR__, 2), '…', (string)$msg);
	echo '<pre style="margin:20px;padding:16px;background:#fff3f3;border:1px solid #f0b4b4;border-radius:8px;font:14px/1.5 monospace;white-space:pre-wrap">'
		. 'Қате: ' . htmlspecialchars($msg) . "\n" . htmlspecialchars(basename((string)$file)) . ':' . (int)$line . '</pre>';
}
set_exception_handler(function ($e) { dev_fatal_show(get_class($e) . ': ' . $e->getMessage(), $e->getFile(), $e->getLine()); });
register_shutdown_function(function () {
	$e = error_get_last();
	if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) dev_fatal_show($e['message'], $e['file'], $e['line']);
});

define('DEV_ADM', dirname(__DIR__, 2)); // админ папкасы (dev/ папкасының ата-анасы)
require_once DEV_ADM . '/config/db.php';
require_once DEV_ADM . '/config/auth.php'; // кіру cookie-і, WhatsApp коды (админмен бір файл)
require_once __DIR__ . '/lib.php';
require_once DEV_ADM . '/config/t.php'; // тіл жүйесі админмен ортақ: t::w('Key', 'Қазақша'), site_word кестесі

class fun {
	// auth.php осыны шақырады: positions_id орнына dev рөлі қайтады
	public static function user_staffw($id) {
		$id = (int)$id;
		$r = dev::row("select * from dev_staff where user_id = '$id' and arh = 0 limit 1");
		if ($r) return ['positions_id' => (int)$r['role'], 'ref_id' => $r['ref_id']];
		// Алғашқы кіру: иесі әлі жоқ болса, жүйенің Создателі (company_staff, рөл 1) иесі болып жазылады
		if (!(int)dev::one("select count(*) from dev_staff where role = 1 and arh = 0")
			&& dev::row("select id from company_staff where user_id = '$id' and positions_id = 1 limit 1")) {
			db::query("insert into dev_staff (user_id, role, comment) values ('$id', 1, 'Алғашқы кіру')");
			return ['positions_id' => 1, 'ref_id' => null];
		}
		return 0;
	}
}

new db;
mysqli_set_charset(db::$connect, 'utf8mb4');
date_default_timezone_set('Asia/Aqtau');
// Cookie-лер host-only (domain жоқ): админ мен dev бір браузерде бөлек-бөлек кіріп тұрады. Сессия аты да бөлек.
session_name('DEVSESS');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

// Кестелер жоқ болса өзі жасалады (тек жаңа кестелер, бар деректерге тиіспейді)
dev::install();

// ---------- тіл: админдегідей 5 тіл, ?lang=kz|en|uz|ky|ru, cookie ui_lang ----------
$langs = ['kz' => 'Қазақша', 'en' => 'English', 'uz' => 'O‘zbekcha', 'ky' => 'Кыргызча', 'ru' => 'Русский'];
$langs_short = ['kz' => 'ҚАЗ', 'en' => 'ENG', 'uz' => 'UZB', 'ky' => 'KGZ', 'ru' => 'РУС'];
$ui_lang = 'kz';
if (isset($_GET['lang']) && isset($langs[$_GET['lang']])) {
	setcookie('ui_lang', $_GET['lang'], time() + 3600 * 24 * 365, '/');
	$q = $_GET; unset($q['lang']);
	header('Location: ' . explode('?', $_SERVER['REQUEST_URI'])[0] . ($q ? '?' . http_build_query($q) : '')); exit();
} elseif (isset($_COOKIE['ui_lang']) && isset($langs[$_COOKIE['ui_lang']])) $ui_lang = $_COOKIE['ui_lang'];
// dev сөздері (config/words.php): site_word-та жоқтары осыдан алынады, базаға ештеңе жазылмайды
t::load();
foreach (require __DIR__ . '/words.php' as $k => $v) {
	if (!isset(t::$words['kz'][$k])) t::$words['kz'][$k] = $v['kz'];
	if ($ui_lang != 'kz' && !isset(t::$words['cur'][$k]) && !empty($v[$ui_lang])) t::$words['cur'][$k] = $v[$ui_lang];
}
dev::i18n();

$date = date('Y-m-d');
$datetime = date('Y-m-d H:i:s');

// ---------- кім кірген ----------
$user = [];
$user_id = 0;
$user_right = 0;
if (isset($_COOKIE['uph'], $_COOKIE['ups'])) {
	$ph = substr(preg_replace('/\D/', '', (string)$_COOKIE['uph']), -10);
	$u = $ph ? dev::row("select * from `user` where phone = '" . dev::e($ph) . "' limit 1") : null;
	if ($u && $u['right'] && auth_check($u, $_COOKIE['ups'])) {
		$r = fun::user_staffw($u['id']);
		if ($r) { $user = $u; $user_id = (int)$u['id']; $user_right = $r; dev::$role = (int)$r['positions_id']; }
	}
}
function dev_logout() {
	foreach (['uph', 'ups', 'utd'] as $k) setcookie($k, '', time() - 3600, '/');
}
// Бастапқы бет рөлге қарай
function dev_home() {
	if (dev::can('home')) return '/home/';
	if (dev::can('partner')) return '/my/';
	return '/exit.php';
}
// Бетке рұқсат: dev_only('companies')
function dev_only($k = 'home') {
	global $user_id;
	if (!$user_id) { header('location: /'); exit(); }
	if (!dev::can($k)) { header('location: ' . dev_home()); exit(); }
}

// POST сұраулар үшін CSRF
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
function dev_post_ok() {
	return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)@$_POST['csrf']);
}
