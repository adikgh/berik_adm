<?php
// Компания баптаулары dev-те: company мен company_branch бағандарының бөлімдері (админ /company/ бетіндегідей топтар).
// Мұнда админде бұғатталғандар да бар (атауы, қала, код, нөмір, ya_city, Telegram, мерзім, точканың offline т.б.).
// Тек базада бар бағандар шығады (SHOW COLUMNS): live пен локал әртүрлі болса да істейді.
// Тізімде жоқ жаңа баған пайда болса, «Басқа өрістер» бөліміне өзі түседі (түрі бағанның түрінен алынады).
// Өріс: 't' түрі (bool | sel | pays; жоқ болса бағанның типінен: int, dec, dt, date, txt), 'l' => [en, kz] атауы, 'n' => [en, kz] түсіндірме,
// 'min'/'max' сан шегі, 'o' => [мән => [en, kz]] таңдау.

if (!function_exists('dev_cs_cols')) {
	// Бағандар: ['name' => ['type' => 'int', 'len' => 255, 'null' => true, 'def' => ...]]
	function dev_cs_cols($tb) {
		$r = [];
		$q = db::query("SHOW COLUMNS FROM `$tb`");
		while ($q && $x = mysqli_fetch_assoc($q)) {
			$t = strtolower($x['Type']);
			$len = preg_match('/\((\d+)\)/', $t, $m) ? (int)$m[1] : 0;
			if (preg_match('/^(tinyint|smallint|mediumint|int|bigint)/', $t)) $tp = 'int';
			elseif (preg_match('/^(decimal|float|double)/', $t)) $tp = 'dec';
			elseif (strpos($t, 'datetime') === 0 || strpos($t, 'timestamp') === 0) $tp = 'dt';
			elseif ($t === 'date') $tp = 'date';
			elseif (strpos($t, 'text') !== false) { $tp = 'txt'; $len = 5000; }
			else $tp = 'txt';
			$r[$x['Field']] = ['type' => $tp, 'len' => $len ?: 255, 'null' => $x['Null'] === 'YES', 'unsigned' => strpos($t, 'unsigned') !== false];
		}
		return $r;
	}

	// Формадан келген мәнді SQL-ге дайындау. Қате болса ['err' => '...'], әйтпесе SQL мәні ('NULL' не 'мән')
	function dev_cs_val($col, $meta, $info, $raw) {
		$t = isset($meta['t']) ? $meta['t'] : $info['type'];
		if ($t === 'bool') return "'" . (!empty($raw) ? 1 : 0) . "'";
		$raw = is_array($raw) ? '' : trim((string)$raw);
		if ($raw === '') {
			if ($info['null']) return 'NULL';
			if ($info['type'] === 'int' || $info['type'] === 'dec') return "'0'";
			if ($info['type'] === 'txt') return "''";
			return null; // міндетті күн бос — өзгертпейміз
		}
		if ($t === 'sel') {
			if (!isset($meta['o'][$raw])) return $info['null'] ? 'NULL' : null;
			return "'" . mysqli_real_escape_string(db::$connect, $raw) . "'";
		}
		switch ($info['type']) {
			case 'int':
				$v = (int)preg_replace('/[^\d-]/', '', $raw);
				if ($info['unsigned'] && $v < 0) $v = 0;
				if (isset($meta['min'])) $v = max($meta['min'], $v);
				if (isset($meta['max'])) $v = min($meta['max'], $v);
				return "'$v'";
			case 'dec':
				$v = round((float)str_replace([' ', ','], ['', '.'], $raw), 2);
				if (isset($meta['min'])) $v = max($meta['min'], $v);
				if (isset($meta['max'])) $v = min($meta['max'], $v);
				return "'$v'";
			case 'dt':
				$ts = strtotime(str_replace('T', ' ', $raw));
				if (!$ts) return ['err' => $col];
				return "'" . date('Y-m-d H:i:s', $ts) . "'";
			case 'date':
				$ts = strtotime($raw);
				if (!$ts) return ['err' => $col];
				return "'" . date('Y-m-d', $ts) . "'";
		}
		$v = mb_substr(strip_tags($raw), 0, $info['len']);
		return "'" . mysqli_real_escape_string(db::$connect, $v) . "'";
	}

	// Input үшін мән
	function dev_cs_inp($info, $v) {
		if ($v === null) return '';
		if ($info['type'] === 'dt') return strtotime($v) ? date('Y-m-d\TH:i', strtotime($v)) : '';
		if ($info['type'] === 'date') return strtotime($v) ? date('Y-m-d', strtotime($v)) : '';
		return (string)$v;
	}
}

$cs_types = [1 => ['Delivery', 'Жеткізу'], 2 => ['Takeaway', 'Өзі алып кету'], 4 => ['Cafe', 'Кафе'], 3 => ['Service', 'Сервис']];
$cs_pays = ['transfer' => ['Transfer', 'Аударым'], 'qr' => ['Kaspi QR', 'Kaspi QR'], 'cash' => ['Cash', 'Қолма-қол'], 'mix' => ['Mixed', 'Аралас'], 'unpaid' => ['Unpaid', 'Төленбеген']];

return [
	// company кестесі
	'company' => [
		'main' => ['fa-building', ['General', 'Негізгі'], [
			'name' => ['l' => ['Company name', 'Компания атауы'], 'req' => 1],
			'city' => ['l' => ['City', 'Қала']],
			'code' => ['l' => ['Company code', 'Компания коды'], 'n' => ['Used in links and sign-in. Change it carefully.', 'Сілтеме мен кіруде қолданылады. Абайлап өзгертіңіз.']],
			'number' => ['l' => ['Number', 'Нөмірі']],
			'ya_city' => ['l' => ['Yandex city', 'Яндекс қаласы'], 'n' => ['City name for Yandex maps and delivery', 'Яндекс карта мен жеткізуге арналған қала атауы']],
		]],
		'types' => ['fa-list-ul', ['Order types', 'Тапсырыс түрлері'], [
			'delivery' => ['t' => 'bool', 'l' => $cs_types[1]],
			'yourself' => ['t' => 'bool', 'l' => $cs_types[2]],
			'coffee' => ['t' => 'bool', 'l' => $cs_types[4]],
			'service' => ['t' => 'bool', 'l' => $cs_types[3]],
			'type_def' => ['t' => 'sel', 'l' => ['Default type', 'Стандарт түрі'], 'o' => $cs_types, 'n' => ['Opens first in the cashbox. Must be one of the enabled types.', 'Кассада бірінші ашылады. Қосулы түрлердің бірі болуы керек.']],
		]],
		'pos' => ['fa-cash-register', ['Cashbox', 'Касса'], [
			'print' => ['t' => 'bool', 'l' => ['Print button', 'Чек басу батырмасы']],
			'show_branch' => ['t' => 'bool', 'l' => ['Show branch', 'Точканы көрсету']],
			'red' => ['l' => ['RED, %', 'RED, %'], 'min' => 0, 'max' => 100, 'n' => ['0 = hidden. In admin only 0, 5 or 10 can be chosen.', '0 = жасырын. Админде тек 0, 5, 10 таңдалады.']],
			'multi_cart' => ['t' => 'bool', 'l' => ['Several carts', 'Бірнеше касса қатары']],
			'multi_cart_max' => ['l' => ['Max carts', 'Ең көп қатар'], 'min' => 2, 'max' => 15],
		]],
		'delivery' => ['fa-car', ['Delivery', 'Жеткізу'], [
			'kv' => ['t' => 'bool', 'l' => ['Address details (flat, entrance)', 'Мекенжай толығы (пәтер, подъезд)']],
			'delivery_price' => ['l' => ['Delivery price, tg', 'Жеткізу бағасы, тг'], 'min' => 0, 'max' => 1000000, 'n' => ['Used in my/QR menu when there are no delivery zones', 'Жеткізу аймағы жоқ болса my/QR мәзірде алынады']],
		]],
		'tg' => ['fa-paper-plane', ['Telegram', 'Telegram'], [
			'chat_send' => ['t' => 'bool', 'l' => ['Send orders to Telegram', 'Тапсырысты Telegram-ға жіберу']],
			'chat_id' => ['l' => ['Chat ID', 'Chat ID'], 'n' => ['Filled in by itself with «Add to group» below. Change by hand only if needed.', 'Төмендегі «Топқа қосу» батырмасымен өзі жазылады. Қолмен тек керек болса өзгертіңіз.']],
			'report_chat_send' => ['t' => 'bool', 'l' => ['Send reports to Telegram', 'Есепті Telegram-ға жіберу']],
			'report_chat_id' => ['l' => ['Reports group Chat ID', 'Есеп тобының Chat ID'], 'n' => ['Filled in by itself with «Add to group» in the reports card. If empty, reports are not sent (never to the orders group).', 'Есеп тобы картасындағы «Топқа қосу» батырмасымен өзі жазылады. Бос болса, есептер жіберілмейді (тапсырыс тобына бармайды).']],
		]],
		'loyal' => ['fa-coins', ['Cashback and gift', 'Кэшбэк пен сыйлық'], [
			'cashback_percent' => ['l' => ['Cashback, %', 'Кэшбэк, %'], 'min' => 0, 'max' => 100, 'n' => ['0 = off', '0 = жоқ']],
			'cashback_pay_max' => ['l' => ['Pay with cashback up to, %', 'Кэшбэкпен төлеу шегі, %'], 'min' => 0, 'max' => 100],
			'cashback_from' => ['l' => ['Cashback from', 'Кэшбэк басталған уақыт']],
			'gift_every' => ['l' => ['Gift every N-th order', 'Әр N-ші тапсырысқа сыйлық'], 'min' => 0, 'n' => ['0 = off', '0 = жоқ']],
			'gift_name' => ['l' => ['Gift', 'Сыйлық']],
			'gift_from' => ['l' => ['Count gifts from', 'Сыйлық санағы басталған уақыт']],
		]],
		'dates' => ['fa-calendar-alt', ['Dates', 'Мерзімдер'], [
			'start_dt' => ['l' => ['Start date', 'Басталған күні']],
			'end_dt' => ['l' => ['End date', 'Аяқталған күні'], 'n' => ['Old field. The subscription term is on the Subscription tab.', 'Ескі өріс. Жазылым мерзімі «Жазылым» бетінде.']],
		]],
	],
	// company_branch кестесі (әр точка)
	'branch' => [
		'name' => ['l' => ['Branch name', 'Точка атауы'], 'req' => 1],
		'number' => ['l' => ['Number', 'Нөмірі']],
		'code' => ['l' => ['Branch code', 'Точка коды']],
		'address' => ['l' => ['Address', 'Мекенжайы']],
		'pay_types' => ['t' => 'pays', 'l' => ['Payment types', 'Төлем түрлері'], 'o' => $cs_pays, 'n' => ['All ticked = all types', 'Бәрі белгіленсе = барлығы']],
		'pay_default' => ['t' => 'sel', 'l' => ['Default payment', 'Стандарт төлем'], 'o' => $cs_pays],
		'print_width' => ['t' => 'sel', 'l' => ['Receipt width', 'Чек ені'], 'o' => ['80' => ['80 mm', '80 мм'], '58' => ['58 mm', '58 мм']]],
		'print_title' => ['l' => ['Receipt title', 'Чек тақырыбы']],
		'offline' => ['t' => 'bool', 'l' => ['Offline cashbox (Windows program)', 'Офлайн касса (Windows программа)']],
	],
	// өзгертілмейтін бағандар
	'skip' => ['company' => ['id', 'tg_code', 'tg_code_dt', 'tg_title', 'report_tg_title'], 'branch' => ['id', 'company_id']],
	'ro' => ['company' => ['ins_dt'], 'branch' => ['ins_dt']],
];
