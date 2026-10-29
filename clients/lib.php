<?
	// Клиенттер: база, тапсырыстармен байланыс, көмекші функциялар.
	// Клиенттер `company_client` кестесінде, жұмысшылардың `user` кестесінен бөлек сақталады.
	// Тапсырыс клиентке retail_orders.client_id арқылы байланысады (телефон бойынша).

	class clients {

		// Төленген және қайтарылмаған тапсырыс
		const OK = "ro.paid = 1 and ro.order_status not in (5, 6, 10)";

		// Кестені және client_id бағанын жасау (бір рет, кейін ештеңе істемейді)
		public static function install() {
			db::query("CREATE TABLE IF NOT EXISTS `company_client` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`phone` varchar(15) NOT NULL,
				`name` varchar(255) DEFAULT NULL,
				`address` text DEFAULT NULL,
				`note` text DEFAULT NULL,
				`archive` tinyint(1) NOT NULL DEFAULT 0,
				`orders_cnt` int(11) NOT NULL DEFAULT 0,
				`total_sum` int(11) NOT NULL DEFAULT 0,
				`first_dt` datetime DEFAULT NULL,
				`last_dt` datetime DEFAULT NULL,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `company_phone` (`company_id`, `phone`),
				KEY `last_dt` (`last_dt`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			if (!mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE 'client_id'"))) {
				db::query("ALTER TABLE `retail_orders` ADD COLUMN `client_id` int(11) DEFAULT NULL AFTER `phone`");
				db::query("ALTER TABLE `retail_orders` ADD KEY `client_id` (`client_id`)");
			}
		}

		// user_id жоқ клиенттерге user жасап, байлау (user = барлық адам, логин = телефон)
		public static function link_users() {
			db::query("insert ignore into `user` (`name`, `phone`, `ins_dt`)
				select COALESCE(MAX(NULLIF(TRIM(c.name), '')), ''), c.phone, MIN(c.ins_dt)
				from company_client c where c.user_id is null group by c.phone");
			db::query("update company_client c join `user` u on u.phone = c.phone set c.user_id = u.id where c.user_id is null");
		}

		// Телефонды 10 санға келтіру: "8 (705) 893-77-45", "87058937745", "+77058937745" -> "7058937745"
		public static function norm_phone($p) {
			$d = preg_replace('/\D+/', '', (string)$p);
			if (strlen($d) == 11 && ($d[0] == '8' || $d[0] == '7')) $d = substr($d, 1);
			return (strlen($d) == 10 && $d[0] == '7') ? $d : '';
		}

		// Әдемі телефон: 8 (705) 893-77-45
		public static function phone_view($p) {
			$p = (string)$p;
			if (strlen($p) != 10) return htmlspecialchars($p);
			return '8 ('.substr($p, 0, 3).') '.substr($p, 3, 3).'-'.substr($p, 6, 2).'-'.substr($p, 8, 2);
		}

		// Әдемі күн: 23 қаз 2026, 14:05
		public static function dt($v, $time = true) {
			if (!$v || substr($v, 0, 4) == '0000') return '—';
			$m = ['', 'қаң', 'ақп', 'нау', 'сәу', 'мам', 'мау', 'шіл', 'там', 'қыр', 'қаз', 'қар', 'жел'];
			$t = strtotime($v);
			$s = date('j', $t).' '.$m[(int)date('n', $t)];
			if (date('Y', $t) != date('Y')) $s .= ' '.date('Y', $t);
			if ($time) $s .= ', '.date('H:i', $t);
			return $s;
		}
		public static function month($ym) {
			$m = ['', 'Қаң', 'Ақп', 'Нау', 'Сәу', 'Мам', 'Мау', 'Шіл', 'Там', 'Қыр', 'Қаз', 'Қар', 'Жел'];
			return $m[(int)substr($ym, 5, 2)];
		}
		public static function ago($v) {
			if (!$v) return '';
			$d = (int)floor((time() - strtotime($v)) / 86400);
			if ($d <= 0) return 'бүгін';
			if ($d == 1) return 'кеше';
			return $d.' күн бұрын';
		}
		public static function sum($v) { return number_format((int)$v, 0, '', ' ').' ₸'; }

		// Сегмент (барлық уақыт бойынша)
		public static function segment($c) {
			$cnt = (int)$c['orders_cnt'];
			if ($c['archive']) return ['archive', 'Архив'];
			if ($cnt >= 2 && $c['last_dt'] && strtotime($c['last_dt']) < time() - 30 * 86400) return ['sleep', 'Ұйықтап қалған'];
			if ($cnt >= 5) return ['loyal', 'Тұрақты'];
			if ($cnt >= 2) return ['back', 'Қайта келген'];
			return ['new', 'Жаңа'];
		}

		// Тапсырыстарды клиенттерге байланыстыру және кэшті жаңарту
		public static function sync($company) {
			$company = (int)$company;

			// Соңғы 3 күнде телефоны өзгерген тапсырыстарды қайта байланыстыру
			$rc = db::query("select ro.id, ro.phone, ro.client_id, c.phone cphone from retail_orders ro
				left join company_client c on c.id = ro.client_id
				where ro.client_id is not null and ro.ins_dt > NOW() - INTERVAL 3 DAY");
			while ($rc && $r = mysqli_fetch_assoc($rc)) {
				if (self::norm_phone($r['phone']) !== (string)$r['cphone']) db::query("update retail_orders set client_id = NULL where id = '".(int)$r['id']."'");
			}

			// Байланыспаған тапсырыстар: телефондарды жинап, бір сұраумен байланыстыру
			$q = db::query("select id, company_id, phone, ins_dt from retail_orders
				where client_id is null and phone is not null and phone <> '' and company_id is not null");
			if ($q && mysqli_num_rows($q)) {
				db::query("CREATE TEMPORARY TABLE IF NOT EXISTS tmp_cl (oid int NOT NULL PRIMARY KEY, company_id int, ph varchar(15), dt datetime, KEY k (company_id, ph))
					ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
				db::query("TRUNCATE tmp_cl");
				$rows = [];
				while ($r = mysqli_fetch_assoc($q)) {
					$dt = mysqli_real_escape_string(db::$connect, (string)$r['ins_dt']);
					$rows[] = "('".(int)$r['id']."', '".(int)$r['company_id']."', '".self::norm_phone($r['phone'])."', '$dt')";
					if (count($rows) >= 1000) { db::query("insert into tmp_cl values ".implode(',', $rows)); $rows = []; }
				}
				if ($rows) db::query("insert into tmp_cl values ".implode(',', $rows));

				// жаңа клиенттер (ph бос болса телефон дұрыс емес)
				db::query("insert ignore into company_client (company_id, phone, ins_dt)
					select company_id, ph, MIN(dt) from tmp_cl where ph <> '' group by company_id, ph");
				self::link_users();
				// тапсырысқа client_id қою (0 = телефон дұрыс емес)
				db::query("update retail_orders ro
					join tmp_cl t on t.oid = ro.id
					left join company_client c on c.company_id = t.company_id and c.phone = t.ph and t.ph <> ''
					set ro.client_id = IFNULL(c.id, 0)
					where ro.client_id is null");
				db::query("DROP TEMPORARY TABLE IF EXISTS tmp_cl");
			}

			// Кэш: саны, сомасы, алғашқы/соңғы күн, соңғы мекенжай
			db::query("update company_client c left join (
					select ro.client_id, COUNT(*) cnt, SUM(ro.total) s, MIN(ro.ins_dt) f, MAX(ro.ins_dt) l
					from retail_orders ro where ro.company_id = '$company' and ro.client_id > 0 and ".self::OK."
					group by ro.client_id
				) a on a.client_id = c.id
				set c.orders_cnt = IFNULL(a.cnt, 0), c.total_sum = IFNULL(a.s, 0), c.first_dt = a.f, c.last_dt = a.l
				where c.company_id = '$company'");
			db::query("update company_client c set c.address = (
					select ro.address from retail_orders ro where ro.client_id = c.id and ro.address is not null and ro.address <> '' order by ro.ins_dt desc limit 1
				) where c.company_id = '$company' and (c.address is null or c.address = '')");
		}

		// Кезең: ?p=today|yday|week|month|3m|6m|12m|all немесе ?from=&to=
		public static function period() {
			$p = @$_GET['p'] ?: 'month';
			$from = @$_GET['from']; $to = @$_GET['to'];
			$today = date('Y-m-d', time() - 6 * 3600);
			$days = ['week' => 7, 'month' => 30, '3m' => 90, '6m' => 180, '12m' => 365];
			if ($from && strtotime($from)) {
				$p = 'custom';
				$f = date('Y-m-d', strtotime($from));
				$t = ($to && strtotime($to)) ? date('Y-m-d', strtotime($to)) : $today;
			} elseif ($p == 'today') {
				$f = $t = $today;
			} elseif ($p == 'yday') {
				$f = $t = date('Y-m-d', strtotime($today.' -1 day'));
			} elseif ($p == 'all') {
				$f = '2000-01-01'; $t = $today;
			} else {
				if (!isset($days[$p])) $p = 'month';
				$f = date('Y-m-d', strtotime($today.' -'.($days[$p] - 1).' day')); $t = $today;
			}
			// жұмыс күні 06:00-ден басталады
			return [
				'p' => $p, 'from' => $f, 'to' => $t,
				'start' => $f.' 06:00:00',
				'end' => date('Y-m-d 06:00:00', strtotime($t.' +1 day')),
			];
		}
	}
