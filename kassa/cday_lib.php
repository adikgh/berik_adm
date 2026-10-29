<?
	// Курьерлердің күндік есебі: есептеу, сақтау, тарих
	// Кесте: courier_day_report (sql/courier_day_01.sql)

	class cday {

		private static $ready = null;

		// кесте бар ма (миграция іске қосылды ма)
		public static function ready() {
			if (self::$ready === null) {
				$q = @db::query("SHOW TABLES LIKE 'courier_day_report'");
				self::$ready = $q && mysqli_num_rows($q) > 0;
			}
			return self::$ready;
		}

		public static function day($s) {
			return ($s && strtotime($s)) ? date('Y-m-d', strtotime($s)) : date('Y-m-d', time() - 6 * 3600);
		}

		// жұмыс күнінің шегі: 06:00 – келесі күн 06:00
		public static function range($day) {
			$start = date('Y-m-d 06:00:00', strtotime($day));
			$end = date('Y-m-d 06:00:00', strtotime("$start +1 day"));
			return [$start, $end];
		}

		// Касса бетіндегідей есептеу (серверде, клиентке сенбейміз)
		public static function calc($cmp, $uid, $day, $cashbox_id) {
			$cmp = (int)$cmp; $uid = (int)$uid; $cashbox_id = (int)$cashbox_id;
			list($start, $end) = self::range($day);
			$company_d = mysqli_fetch_assoc(db::query("select delivery_price from company where id = '$cmp'"));
			$dprice = intval(@$company_d['delivery_price']);
			$has_cpay = fun::has_col('retail_orders', 'courier_method');

			$r = ['orders' => 0, 'total' => 0, 'pay_qr' => 0, 'pay_cash' => 0, 'c_cash' => 0, 'c_kaspi' => 0, 'salary' => 0];
			$q = db::query("select * from retail_orders where ins_dt BETWEEN '$start' and '$end' and сourier_id = '$uid' and company_id = '$cmp' and number is not null and order_status in (1, 8, 2, 11, 3, 4, 6)");
			while ($o = mysqli_fetch_assoc($q)) {
				if ((int)$o['order_status'] == 6) { // Отказ при получение: тек бізде қалған ақша
					$k = intval($o['kept_sum']); $k_pre = min(intval($o['pay_qr']), $k);
					$r['total'] += $k; $r['pay_qr'] += $k_pre; $r['pay_cash'] += $k - $k_pre;
					continue;
				}
				$r['orders']++;
				$r['total'] += intval($o['total']);
				$r['pay_qr'] += intval($o['pay_qr']);
				$r['pay_cash'] += intval($o['pay_cash']);
				$r['salary'] += intval($o['pay_delivery']) + $dprice;
				if ($has_cpay && $o['pay_cash'] > 0 && !empty($o['courier_method'])) {
					$r['c_cash'] += intval($o['courier_cash']);
					$r['c_kaspi'] += intval($o['courier_kaspi']);
				}
			}

			$rc = fun::report_сourier($cashbox_id, $uid);
			$r['expenses'] = intval(@$rc['expenses']);
			$r['cash'] = intval(@$rc['cash']);
			$r['to_cashbox'] = $r['pay_cash'] - $r['salary'];
			$r['kaspi'] = $r['to_cashbox'] - $r['expenses'] - $r['cash'];
			return $r;
		}

		// сақтау (қайта сақтаса, жаңартады)
		public static function save($cmp, $uid, $day, $cashbox_id, $by) {
			$cmp = (int)$cmp; $uid = (int)$uid; $by = (int)$by; $cashbox_id = (int)$cashbox_id;
			$day = self::day($day);
			$r = self::calc($cmp, $uid, $day, $cashbox_id);
			// қайта сақтау: шығын/қолма-қол бұрынғы есептен алынады (касса жолы нөлденген)
			$old = self::get($cmp, $uid, $day);
			if ($old) {
				$r['expenses'] = intval($old['expenses']); $r['cash'] = intval($old['cash']);
				$r['kaspi'] = $r['to_cashbox'] - $r['expenses'] - $r['cash'];
			}
			if (!$r['orders'] && !$r['expenses'] && !$r['cash']) return false;

			$f = ['orders', 'total', 'pay_qr', 'pay_cash', 'c_cash', 'c_kaspi', 'salary', 'to_cashbox', 'expenses', 'cash', 'kaspi'];
			$cols = ''; $vals = ''; $upd = '';
			foreach ($f as $k) { $v = intval($r[$k]); $cols .= ", `$k`"; $vals .= ", '$v'"; $upd .= ", `$k` = '$v'"; }
			db::query("INSERT INTO courier_day_report (company_id, day, courier_id, cashbox_id, accepted_by $cols)
				VALUES ('$cmp', '$day', '$uid', '$cashbox_id', '$by' $vals)
				ON DUPLICATE KEY UPDATE cashbox_id = '$cashbox_id', accepted_by = '$by', upd_dt = NOW() $upd");

			// касса жолындағы шығын/қолма-қол келесі күнге қалмасын: нөлге түсіреміз (есепте сақталды)
			if ($cashbox_id && !$old) db::query("UPDATE report_сourier SET expenses = 0, cash = 0 WHERE report_id = '$cashbox_id' and сourier_id = '$uid'");
			return true;
		}

		// сақтауды болдырмау: шығын/қолма-қолды кассаға қайтарады
		public static function undo($cmp, $uid, $day, $cashbox_id) {
			$cmp = (int)$cmp; $uid = (int)$uid; $cashbox_id = (int)$cashbox_id;
			$day = self::day($day);
			$s = self::get($cmp, $uid, $day);
			if (!$s) return false;
			if ($cashbox_id && ($s['expenses'] || $s['cash'])) {
				$e = intval($s['expenses']); $c = intval($s['cash']);
				$ex = db::query("select id from report_сourier where report_id = '$cashbox_id' and сourier_id = '$uid' limit 1");
				if (mysqli_num_rows($ex)) db::query("UPDATE report_сourier SET expenses = '$e', cash = '$c' WHERE report_id = '$cashbox_id' and сourier_id = '$uid'");
				else db::query("INSERT INTO report_сourier (сourier_id, report_id, expenses, cash) VALUES ('$uid', '$cashbox_id', '$e', '$c')");
			}
			db::query("DELETE FROM courier_day_report WHERE id = '".intval($s['id'])."'");
			return true;
		}

		public static function get($cmp, $uid, $day) {
			$cmp = (int)$cmp; $uid = (int)$uid; $day = self::day($day);
			$q = db::query("select * from courier_day_report where company_id = '$cmp' and courier_id = '$uid' and day = '$day' limit 1");
			return mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : false;
		}

		// бір күндегі сақталғандар: [courier_id => жол]
		public static function day_map($cmp, $day) {
			$m = [];
			if (!self::ready()) return $m;
			$cmp = (int)$cmp; $day = self::day($day);
			$q = db::query("select * from courier_day_report where company_id = '$cmp' and day = '$day'");
			while ($r = mysqli_fetch_assoc($q)) $m[$r['courier_id']] = $r;
			return $m;
		}

		// компанияның курьерлері
		public static function couriers($cmp) {
			$cmp = (int)$cmp; $l = [];
			$q = db::query("select s.user_id, u.name from company_staff s left join user u on u.id = s.user_id where s.positions_id = 6 and s.company_id = '$cmp' order by u.name");
			while ($r = mysqli_fetch_assoc($q)) $l[$r['user_id']] = $r['name'];
			return $l;
		}

		public static function fmt($v) { return number_format(intval($v), 0, '', ' '); }
	}
