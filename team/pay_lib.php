<?
	// Жалақы: жұмысшыларға берілген ақша (бөлек кесте `staff_pay`).
	// Операторлар, аспазшылар және басқалар: төлемді қолмен жазады.
	// Курьерлер: жеткізу бойынша (по факту) өзі есептеледі, қосымша төлем ғана жазылады.
	// ОПИУ (/opiu/) осы кестеден және курьер есебінен жалақыны алады.

	class zp {

		// лауазымдар: 5 Кассир (оператор), 9 Аспазшы, 6 Курьер
		const OPER = 5;
		const COOK = 9;
		const COURIER = 6;

		// ОПИУ мен Жалақы бетіндегі топтар
		public static $groups = [
			'oper'    => ['name' => 'Операторлар', 'icon' => 'fa-headset', 'pos' => [5]],
			'cook'    => ['name' => 'Аспазшылар', 'icon' => 'fa-hat-chef', 'pos' => [9]],
			'courier' => ['name' => 'Курьерлер', 'icon' => 'fa-car', 'pos' => [6]],
			'other'   => ['name' => 'Басқа жұмысшылар', 'icon' => 'fa-users', 'pos' => []],
		];

		public static $types = [1 => 'Жалақы', 2 => 'Аванс', 3 => 'Бонус'];
		public static $methods = ['cash' => 'Қолма-қол', 'kaspi' => 'Kaspi'];

		private static $installed = false;

		// Кестені жасау (бір рет, кейін ештеңе істемейді)
		public static function install() {
			if (self::$installed) return;
			self::$installed = true;
			db::query("CREATE TABLE IF NOT EXISTS `staff_pay` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) DEFAULT NULL,
				`user_id` int(11) NOT NULL,
				`positions_id` int(11) NOT NULL DEFAULT 0,
				`type` tinyint(1) NOT NULL DEFAULT 1,
				`amount` int(11) NOT NULL DEFAULT 0,
				`method` varchar(10) NOT NULL DEFAULT 'cash',
				`pay_date` date NOT NULL,
				`comment` varchar(255) DEFAULT NULL,
				`by_user` int(11) DEFAULT NULL,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				KEY `company_date` (`company_id`, `pay_date`),
				KEY `user_id` (`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		}

		// лауазым қай топқа жатады
		public static function group_of($pos) {
			foreach (self::$groups as $k => $g) if (in_array((int)$pos, $g['pos'])) return $k;
			return 'other';
		}

		public static function can() {
			global $user_right;
			return in_array((int)@$user_right['positions_id'], [1, 2]);
		}

		// компания жұмысшылары (құрушыдан басқа): [user_id => жол]
		public static function staff($cmp) {
			$cmp = (int)$cmp; $l = [];
			$q = db::query("select s.id staff_id, s.user_id, s.positions_id, s.branch_id, u.name, u.surname, u.phone, u.img
				from company_staff s inner join user u on u.id = s.user_id
				where s.company_id = '$cmp' and s.positions_id != 1 order by u.name asc");
			while ($r = mysqli_fetch_assoc($q)) $l[$r['user_id']] = $r;
			return $l;
		}

		// кезеңдегі төлемдер жиыны: [user_id => [sum, cnt]]
		public static function paid_map($cmp, $from, $to) {
			self::install();
			$cmp = (int)$cmp; $m = [];
			$q = db::query("select user_id, SUM(amount) s, COUNT(*) c from staff_pay
				where company_id = '$cmp' and pay_date between '$from' and '$to' group by user_id");
			while ($r = mysqli_fetch_assoc($q)) $m[$r['user_id']] = ['sum' => (int)$r['s'], 'cnt' => (int)$r['c']];
			return $m;
		}

		// кезеңдегі төлемдер, топтар бойынша (ОПИУ үшін): [group => sum]
		public static function paid_by_group($cmp, $from, $to) {
			self::install();
			$cmp = (int)$cmp;
			$r = array_fill_keys(array_keys(self::$groups), 0);
			$q = db::query("select positions_id, SUM(amount) s from staff_pay
				where company_id = '$cmp' and pay_date between '$from' and '$to' group by positions_id");
			while ($d = mysqli_fetch_assoc($q)) $r[self::group_of($d['positions_id'])] += (int)$d['s'];
			return $r;
		}

		// Курьерлердің жеткізу бойынша табысы (касса есебімен бірдей: pay_delivery + компанияның жеткізу бағасы).
		// Жұмыс күні 06:00-ден басталады. Қайтарады: [user_id => [orders, sum]]
		public static function courier_earn($cmp, $from, $to) {
			$cmp = (int)$cmp; $m = [];
			$start = date('Y-m-d 06:00:00', strtotime($from));
			$end = date('Y-m-d 06:00:00', strtotime("$to +1 day"));
			$c = mysqli_fetch_assoc(db::query("select delivery_price from company where id = '$cmp'"));
			$dprice = intval(@$c['delivery_price']);
			$q = db::query("select сourier_id uid, COUNT(*) n, COALESCE(SUM(pay_delivery), 0) pd from retail_orders
				where company_id = '$cmp' and сourier_id is not null and сourier_id != 0 and number is not null
				and order_status in (1, 8, 2, 11, 3, 4) and ins_dt >= '$start' and ins_dt < '$end' group by сourier_id");
			while ($q && $r = mysqli_fetch_assoc($q)) $m[$r['uid']] = ['orders' => (int)$r['n'], 'sum' => (int)$r['pd'] + (int)$r['n'] * $dprice];
			return $m;
		}

		public static function esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$v))); }
		public static function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
		public static function money($v) { return number_format(intval($v), 0, '.', ' ').' ₸'; }
	}
