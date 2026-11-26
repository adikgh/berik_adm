<?php
// Раздача режимі: хост dist.* / dst.* / dstb.* (config/var.php → $dist). Код — осы админ сайтының өзі.
// - Кіре алатындар: 1 Создатель, 2 Директор, 3 Басқарушы, 8 Раздача. Басқа лауазымның cookie-і жарамайды.
// - Тариф модулі: dst (config/plan.php → PLAN_SITE).
// - Ашылатын беттер тек DIST_PAGES тізімінде. Қалғаны серверде жабық: бет болса Раздача экранына, get.php болса 'none'.

if (!defined('PLAN_SITE')) define('PLAN_SITE', 'dst');

const DIST_POS = [1, 2, 3, 8];
const DIST_HOME = '/orders/dist/';
const DIST_PAGES = [
	'/index.php', '/get.php', '/exit.php',
	'/orders/dist/index.php', '/orders/dist/item.php', '/orders/dist/get.php',
	'/kassa/index.php', '/kassa/get.php', '/kassa/cday_get.php', '/kassa/ksnap_get.php', '/kassa/couriers.php',
	'/acc/index.php', '/acc/get.php',
	'/pay/index.php',
	'/api/index.php',
];

function dist_gate() {
	global $core, $user, $user_id, $user_right, $my_companies, $company;

	// лауазым
	if ($user_id && (!$user_right || !in_array((int)$user_right['positions_id'], DIST_POS, true))) {
		$core->user_unset();
		$user = []; $user_id = null; $user_right = 0; $my_companies = []; $company = 0;
	}

	// бет
	$self = '/' . ltrim(str_replace('\\', '/', (string)@$_SERVER['SCRIPT_NAME']), '/');
	if (in_array($self, DIST_PAGES, true)) return;

	// бұрынғы Раздача сілтемелері (dlv): /orders/ → /orders/dist/, /orders/item.php → /orders/dist/item.php
	$q = (string)@$_SERVER['QUERY_STRING'];
	$q = $q !== '' ? '?' . $q : '';
	if ($self == '/orders/index.php') { header('location: ' . DIST_HOME . $q); exit(); }
	if ($self == '/orders/item.php') { header('location: ' . DIST_HOME . 'item.php' . $q); exit(); }

	if (basename($self) != 'index.php' || !empty($_POST)) { http_response_code(403); exit('none'); }
	header('location: ' . ($user_id ? DIST_HOME : '/'));
	exit();
}
