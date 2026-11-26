<?php
// Админ сайтының алиастары — бөлек папкасыз, бір код (dev сияқты, бірақ сол беттердің өзі). Хостты config/var.php анықтайды:
//   Раздача: dist.* / dst.* / dstb.*  → $dist
//   Кухня:   ktc.* / ktch.* / ktcb.*  → $ktc
// Әр алиаста: кіре алатын лауазымдар, тариф модулі (PLAN_SITE), басты бет, ашылатын беттер тізімі.
// Тізімде жоқ бет серверде жабық: бет болса басты бетке, get.php / POST болса 403 'none'.

$alias_cfgs = [
	'dist' => [
		'pos' => [1, 2, 3, 8], 'site' => 'dist', 'plan' => 'dst', 'home' => '/orders/dist/', 'name' => ['Dispatch', 'Раздача'],
		'pages' => [
			'/orders/dist/index.php', '/orders/dist/item.php', '/orders/dist/get.php', '/orders/dist/acc.php', '/orders/dist/couriers.php',
			'/kassa/index.php', '/kassa/get.php', '/kassa/cday_get.php', '/kassa/ksnap_get.php', '/kassa/couriers.php',
			'/pay/index.php', '/api/index.php',
		],
		// бұрынғы Раздача сілтемелері (курьер сайтындағы)
		'legacy' => ['/orders/index.php' => '/orders/dist/', '/orders/item.php' => '/orders/dist/item.php', '/users/index.php' => '/orders/dist/couriers.php'],
	],
	'ktc' => [
		'pos' => [1, 2, 3, 9], 'site' => 'ktch', 'plan' => 'ktc', 'home' => '/orders/kitchen/', 'name' => ['Kitchen', 'Кухня'],
		'pages' => [
			'/orders/kitchen/index.php', '/orders/kitchen/get.php',
			'/products/stop/index.php', '/products/stop/get.php',
			'/waybill/index.php', '/waybill/item.php', '/waybill/get.php', '/waybill/ai.php', '/waybill/print.php', '/waybill/revision.php',
			'/pay/index.php',
		],
		// бұрынғы Кухня сайтының сілтемелері
		'legacy' => ['/orders/index.php' => '/orders/kitchen/', '/stop/index.php' => '/products/stop/', '/revision/index.php' => '/waybill/revision.php', '/waybill/form.php' => '/waybill/item.php'],
	],
];
$alias = !empty($dist) ? 'dist' : (!empty($ktc) ? 'ktc' : '');
$alias_cfg = $alias ? $alias_cfgs[$alias] : null;
if ($alias_cfg && !defined('PLAN_SITE')) define('PLAN_SITE', $alias_cfg['plan']);

// core.php шақырады (компания анықталғаннан кейін)
function alias_gate() {
	global $alias, $alias_cfg, $core, $user, $user_id, $user_right, $my_companies, $company;
	if (!$alias_cfg) {
		// Негізгі админ хосты: Раздача мен Кухня экрандары тек өз хостында ашылады
		$self = '/' . ltrim(str_replace('\\', '/', (string)@$_SERVER['SCRIPT_NAME']), '/');
		if (preg_match('~^/orders/(dist|kitchen)/~', $self)) {
			if (!empty($_POST) || basename($self) == 'get.php') { http_response_code(403); exit('none'); }
			header('location: /stats/');
			exit();
		}
		return;
	}

	// лауазым
	if ($user_id && (!$user_right || !in_array((int)$user_right['positions_id'], $alias_cfg['pos'], true))) {
		$core->user_unset();
		$user = []; $user_id = null; $user_right = 0; $my_companies = []; $company = 0;
	}

	// бұрынғы сілтемелер (файл жоқ болса да: Apache 404 → /index.php, сондықтан адрес бойынша)
	$q = (string)@$_SERVER['QUERY_STRING'];
	$q = $q !== '' ? '?' . $q : '';
	$path = (string)parse_url((string)@$_SERVER['REQUEST_URI'], PHP_URL_PATH);
	if (substr($path, -1) == '/') $path .= 'index.php';
	if (isset($alias_cfg['legacy'][$path])) { header('location: ' . $alias_cfg['legacy'][$path] . $q); exit(); }

	// бет
	$self = '/' . ltrim(str_replace('\\', '/', (string)@$_SERVER['SCRIPT_NAME']), '/');
	$open = array_merge(['/index.php', '/get.php', '/exit.php', '/acc/index.php', '/acc/get.php'], $alias_cfg['pages']);
	if (!in_array($self, $open, true)) {
		if (!empty($_POST) || preg_match('~(^|_)get\.php$|^ai\.php$~', basename($self))) { http_response_code(403); exit('none'); }
		header('location: ' . ($user_id ? $alias_cfg['home'] : '/'));
		exit();
	}

	// Кухня: накладной мен ревизия тек жоба (бұрынғы Кухня сайтындағыдай) — қабылдау, төлем, ревизияны аяқтау админде
	if ($alias == 'ktc') {
		if ($self == '/waybill/get.php') {
			foreach (['unpost', 'pay', 'sync_sales', 'supplier_archive'] as $a) if (isset($_GET[$a])) { http_response_code(403); echo json_encode(['ok' => false, 'msg' => 'Кухняда рұқсат жоқ']); exit(); }
			$_POST['post'] = 0;
		}
		if ($self == '/waybill/revision.php') unset($_POST['finish']);
	}
}
