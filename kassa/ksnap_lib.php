<?
	// Кассаның күндік суреті (snapshot).
	// Әр жұмыс күні (06:00 – келесі күн 06:00) аяқталғанда толық касса есебі (Формат кестесі + Курьер кестесі)
	// компания бойынша және әр точка бойынша company_kassa_day кестесіне сақталады — смена жабылса да, жабылмаса да.
	// Бүгін — тірі есеп (тапсырыстардан), өткен күндер — осы кестеден.
	//
	// Іске қосылуы:
	//   1) kassa/cron.php — күнде 06:00-де (Windows тапсырмасы не сервер cron);
	//   2) ksnap::auto() — админнің кез келген беті ашылғанда (config/core.php), 06:00 өтіп кетсе де ұмытылмайды.
	// Кесте бірінші рет өзі құрылады (CREATE TABLE IF NOT EXISTS).

	include_once __DIR__.'/cday_lib.php';

	class ksnap {

		const T = 'company_kassa_day';
		private static $ready = null;
		private static $first = [];

		// Формат кестесінің жолдары (kassa/index.php-дегідей)
		public static $f_keys = ['all', 'yo', 'dl', 'cf', 'sv', 'otk'];
		// сақталатын сандар
		public static $nums = ['number', 'total', 'kept', 'refused', 'pay_cash', 'pay_qr', 'pay_transfer', 'pay_prepay', 'pay_red', 'pay_delivery', 'c_cash', 'c_kaspi', 'c_none', 'expenses', 'cash', 'kaspi'];

		public static function ready() {
			if (self::$ready === null) {
				$t = self::T;
				$ex = db::query("SHOW TABLES LIKE '$t'");
				if ($ex && mysqli_num_rows($ex)) return self::$ready = true;
				$ok = db::query("CREATE TABLE IF NOT EXISTS `$t` (
					`id` int unsigned NOT NULL AUTO_INCREMENT,
					`company_id` int NOT NULL,
					`branch_id` int NOT NULL DEFAULT 0 COMMENT '0 = барлық точка',
					`day` date NOT NULL COMMENT 'жұмыс күні 06:00-ден',
					`kind` char(1) NOT NULL COMMENT 'f = формат жолы, c = курьер, r = 06:00 жабу белгісі',
					`row_key` varchar(20) NOT NULL COMMENT 'f: all/yo/dl/cf/sv/otk, c: курьер user_id',
					`name` varchar(150) DEFAULT NULL,
					`number` int NOT NULL DEFAULT 0,
					`total` bigint NOT NULL DEFAULT 0,
					`kept` bigint NOT NULL DEFAULT 0 COMMENT 'отказтан бізде қалған ақша (total ішінде)',
					`refused` bigint NOT NULL DEFAULT 0 COMMENT 'отказ тапсырыстарының сомасы (otk жолы)',
					`pay_cash` bigint NOT NULL DEFAULT 0,
					`pay_qr` bigint NOT NULL DEFAULT 0,
					`pay_transfer` bigint NOT NULL DEFAULT 0,
					`pay_prepay` bigint NOT NULL DEFAULT 0,
					`pay_red` bigint NOT NULL DEFAULT 0,
					`pay_delivery` bigint NOT NULL DEFAULT 0 COMMENT 'f: жеткізу ақысы, c: курьер жалақысы',
					`c_cash` bigint NOT NULL DEFAULT 0,
					`c_kaspi` bigint NOT NULL DEFAULT 0,
					`c_none` bigint NOT NULL DEFAULT 0,
					`expenses` bigint NOT NULL DEFAULT 0,
					`cash` bigint NOT NULL DEFAULT 0,
					`kaspi` bigint NOT NULL DEFAULT 0,
					`auto` tinyint NOT NULL DEFAULT 0 COMMENT '1 = 06:00-де өзі сақталды',
					`ins_dt` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `u_row` (`company_id`, `branch_id`, `day`, `kind`, `row_key`),
					KEY `k_day` (`day`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
				self::$ready = (bool)$ok;
			}
			return self::$ready;
		}

		// бүгінгі жұмыс күні
		public static function today() { return date('Y-m-d', time() - 6 * 3600); }
		public static function next($day, $n = 1) { return date('Y-m-d', strtotime("$day +$n day")); }

		private static function zero() { return array_fill_keys(self::$nums, 0); }


		// ---------- Есептеу (тапсырыстардан, бір сұраныспен бүкіл кезең) ----------
		// Қайтарады: [күн => ['f' => [all.. => сандар], 'c' => [user_id => сандар + name]]]
		public static function calc($cmp, $br, $from, $to) {
			$cmp = (int)$cmp; $br = (int)$br;
			$start = "$from 06:00:00"; $end = "$to 06:00:00";
			$br_cond = $br ? " and branch_id = '$br'" : "";
			$cd = mysqli_fetch_assoc(db::query("select delivery_price from company where id = '$cmp'"));
			$dprice = intval(@$cd['delivery_price']);
			$today = self::today();

			$out = [];
			$f_ok = [1, 8, 2, 11, 3, 9, 4, 6, 10];
			$c_ok = [1, 8, 2, 11, 3, 4];
			$q = db::query("select * from retail_orders where ins_dt >= '$start' and ins_dt < '$end' and number is not null and company_id = '$cmp' $br_cond");
			while ($q && $o = mysqli_fetch_assoc($q)) {
				$day = date('Y-m-d', strtotime($o['ins_dt']) - 6 * 3600);
				if (!isset($out[$day])) $out[$day] = self::empty_day();
				$st = (int)$o['order_status']; $tp = (int)$o['order_type'];

				// Формат кестесі
				$keys = [];
				if (in_array($st, $f_ok)) {
					$keys[] = 'all';
					if ($tp == 2) $keys[] = 'yo'; elseif ($tp == 1) $keys[] = 'dl'; elseif ($tp == 4) $keys[] = 'cf'; elseif ($tp == 3) $keys[] = 'sv';
				}
				if (in_array($st, [5, 6, 10])) $keys[] = 'otk';
				foreach ($keys as $k) {
					$r = &$out[$day]['f'][$k];
					if (in_array($st, [5, 6, 10])) { // отказ: тек бізде қалған ақша (kept_sum)
						if ($k == 'otk') { $r['number']++; $r['refused'] += intval($o['total']); }
						$kp = $st == 5 ? 0 : intval($o['kept_sum']);
						$r['total'] += $kp; $r['kept'] += $kp;
					} else {
						$r['number']++;
						$r['total'] += intval($o['total']);
						$r['pay_qr'] += intval($o['pay_qr']);
						$r['pay_cash'] += intval($o['pay_cash']);
						$r['pay_transfer'] += intval(@$o['pay_transfer']);
						$r['pay_red'] += intval(@$o['pay_red']);
						$r['pay_prepay'] += intval(@$o['pay_prepay']);
						$r['pay_delivery'] += intval($o['pay_delivery']);
					}
					unset($r);
				}

				// Курьер кестесі
				$uid = intval(@$o['сourier_id']);
				if ($uid && in_array($st, $c_ok)) {
					if (!isset($out[$day]['c'][$uid])) $out[$day]['c'][$uid] = self::zero();
					$r = &$out[$day]['c'][$uid];
					$r['number']++;
					$r['total'] += intval($o['total']);
					$r['pay_qr'] += intval($o['pay_qr']);
					$r['pay_cash'] += intval($o['pay_cash']);
					$r['pay_delivery'] += intval($o['pay_delivery']) + $dprice; // жалақы
					if ($o['pay_cash'] > 0) {
						if (!empty($o['courier_method'])) { $r['c_cash'] += intval(@$o['courier_cash']); $r['c_kaspi'] += intval(@$o['courier_kaspi']); }
						else $r['c_none'] += intval($o['pay_cash']);
					}
					unset($r);
				}
			}

			// курьердің шығыны / қолма-қолы: сақталған күндік есептен (courier_day_report), бүгін — ашық кассадан
			$exp = [];
			if (cday::ready()) {
				$q = db::query("select day, courier_id, expenses, cash from courier_day_report where company_id = '$cmp' and day >= '$from' and day < '$to'");
				while ($q && $d = mysqli_fetch_assoc($q)) $exp[$d['day']][$d['courier_id']] = [intval($d['expenses']), intval($d['cash'])];
			}
			if ($today >= $from && $today < $to) {
				$cb = db::query("select id from report_1 where paid = 0 and company_id = '$cmp' order by id desc limit 1");
				$cb_id = ($cb && mysqli_num_rows($cb)) ? intval(mysqli_fetch_assoc($cb)['id']) : 0;
				if ($cb_id) {
					$q = db::query("select * from report_сourier where report_id = '$cb_id'");
					while ($q && $d = mysqli_fetch_assoc($q)) {
						$uid = intval($d['сourier_id']);
						if (!isset($exp[$today][$uid]) && (intval($d['expenses']) || intval($d['cash']))) $exp[$today][$uid] = [intval($d['expenses']), intval($d['cash'])];
					}
				}
			}
			foreach ($exp as $day => $list) foreach ($list as $uid => $ec) {
				if (!$ec[0] && !$ec[1]) continue;
				if (!isset($out[$day])) $out[$day] = self::empty_day();
				if (!isset($out[$day]['c'][$uid])) $out[$day]['c'][$uid] = self::zero();
				$out[$day]['c'][$uid]['expenses'] = $ec[0];
				$out[$day]['c'][$uid]['cash'] = $ec[1];
			}

			// Kaspi = кассаға - шығын - қолма-қол; курьер аттары
			$ids = [];
			foreach ($out as $day => $d) foreach ($d['c'] as $uid => $r) {
				$out[$day]['c'][$uid]['kaspi'] = $r['pay_cash'] - $r['pay_delivery'] - $r['expenses'] - $r['cash'];
				$ids[$uid] = 1;
			}
			$names = self::names(array_keys($ids));
			foreach ($out as $day => $d) foreach ($d['c'] as $uid => $r) $out[$day]['c'][$uid]['name'] = @$names[$uid];
			return $out;
		}

		public static function empty_day() {
			$d = ['f' => [], 'c' => []];
			foreach (self::$f_keys as $k) $d['f'][$k] = self::zero();
			return $d;
		}

		public static function names($ids) {
			$m = [];
			$ids = array_filter(array_map('intval', $ids));
			if (!$ids) return $m;
			$q = db::query("select id, name from user where id in (".implode(',', $ids).")");
			while ($q && $d = mysqli_fetch_assoc($q)) $m[$d['id']] = $d['name'];
			return $m;
		}


		// ---------- Сақтау ----------
		private static function write($cmp, $br, $day, $d, $auto) {
			$cmp = (int)$cmp; $br = (int)$br; $auto = $auto ? 1 : 0; $t = self::T;
			$now = date('Y-m-d H:i:s');
			$cols = '`'.implode('`, `', self::$nums).'`';
			$vals = [];
			$add = function ($kind, $key, $r) use (&$vals, $cmp, $br, $day, $auto, $now) {
				$v = "('$cmp', '$br', '$day', '$kind', '".mysqli_real_escape_string(db::$connect, $key)."', ";
				$v .= isset($r['name']) && $r['name'] !== null ? "'".mysqli_real_escape_string(db::$connect, $r['name'])."'" : 'NULL';
				foreach (self::$nums as $n) $v .= ", '".intval(@$r[$n])."'";
				$vals[] = $v.", '$auto', '$now')";
			};
			foreach (self::$f_keys as $k) $add('f', $k, $d['f'][$k]);
			foreach ($d['c'] as $uid => $r) $add('c', $uid, $r);
			db::query("DELETE FROM `$t` WHERE company_id = '$cmp' and branch_id = '$br' and day = '$day' and kind in ('f', 'c')");
			db::query("INSERT INTO `$t` (company_id, branch_id, day, kind, row_key, name, $cols, auto, ins_dt) VALUES ".implode(', ', $vals));
		}

		// бір күнді (қайта) сақтау; бүгінгі не болашақ күн сақталмайды
		public static function save($cmp, $br, $day, $auto = 0) {
			if (!self::ready() || $day >= self::today()) return false;
			$all = self::calc($cmp, $br, $day, self::next($day));
			self::write($cmp, $br, $day, isset($all[$day]) ? $all[$day] : self::empty_day(), $auto);
			return true;
		}

		// компанияның бір күнін барлық точкасымен қайта сақтау
		public static function save_company($cmp, $day, $auto = 0) {
			$cmp = (int)$cmp;
			$ok = self::save($cmp, 0, $day, $auto);
			$q = db::query("select id from company_branch where company_id = '$cmp'");
			while ($q && $b = mysqli_fetch_assoc($q)) self::save($cmp, $b['id'], $day, $auto);
			return $ok;
		}

		// сақталған күн туралы: ['ins_dt' => ..., 'auto' => 0/1] не false
		public static function info($cmp, $br, $day) {
			if (!self::ready()) return false;
			$cmp = (int)$cmp; $br = (int)$br; $t = self::T;
			$q = db::query("select ins_dt, auto from `$t` where company_id = '$cmp' and branch_id = '$br' and day = '$day' and kind = 'f' and row_key = 'all' limit 1");
			return ($q && mysqli_num_rows($q)) ? mysqli_fetch_assoc($q) : false;
		}

		// компанияның алғашқы тапсырыс күні (одан бұрынғы күндер бос, сақталмайды)
		private static function first_day($cmp) {
			if (!isset(self::$first[$cmp])) {
				$d = mysqli_fetch_assoc(db::query("select min(ins_dt) as m from retail_orders where company_id = '$cmp' and number is not null"));
				self::$first[$cmp] = @$d['m'] ? date('Y-m-d', strtotime($d['m']) - 6 * 3600) : self::today();
			}
			return self::$first[$cmp];
		}

		// кезеңдегі өткен күндердің суреті жоқ болса — тапсырыстардан бір рет есептеп сақтайды
		public static function ensure($cmp, $br, $from, $to) {
			if (!self::ready()) return;
			$cmp = (int)$cmp; $br = (int)$br; $t = self::T;
			$from = max($from, self::first_day($cmp));
			$to = min($to, self::today());
			if ($from >= $to) return;
			$have = [];
			$q = db::query("select day from `$t` where company_id = '$cmp' and branch_id = '$br' and kind = 'f' and row_key = 'all' and day >= '$from' and day < '$to'");
			while ($q && $d = mysqli_fetch_assoc($q)) $have[$d['day']] = 1;
			$miss = [];
			for ($d = $from; $d < $to; $d = self::next($d)) if (!isset($have[$d])) $miss[] = $d;
			if (!$miss) return;
			$all = self::calc($cmp, $br, $miss[0], self::next(end($miss)));
			foreach ($miss as $d) self::write($cmp, $br, $d, isset($all[$d]) ? $all[$d] : self::empty_day(), 0);
		}


		// ---------- Оқу: кезең бойынша қосынды ----------
		// $grp: 'all' — бір қосынды, 'day' — күн бойынша, 'month' — ай бойынша (YYYY-MM).
		// Өткен күндер — сақталған суреттен, бүгін — тірі есептен.
		// Қайтарады: [топ => ['f' => [...], 'c' => [uid => [... , 'name']]]]
		public static function agg($cmp, $br, $from, $to, $grp = 'all') {
			$cmp = (int)$cmp; $br = (int)$br; $t = self::T;
			$today = self::today();
			$gk = function ($day) use ($grp) { return $grp == 'day' ? $day : ($grp == 'month' ? substr($day, 0, 7) : 'all'); };
			$res = [];
			$past_to = min($to, $today);
			if (self::ready() && $from < $past_to) {
				self::ensure($cmp, $br, $from, $past_to);
				$g = $grp == 'day' ? 'day' : ($grp == 'month' ? "DATE_FORMAT(day, '%Y-%m')" : "'all'");
				$sums = '';
				foreach (self::$nums as $n) $sums .= ", SUM(`$n`) as `$n`";
				$q = db::query("select $g as g, kind, row_key, MAX(name) as name $sums from `$t`
					where company_id = '$cmp' and branch_id = '$br' and kind in ('f', 'c') and day >= '$from' and day < '$past_to'
					group by g, kind, row_key");
				while ($q && $d = mysqli_fetch_assoc($q)) {
					if (!isset($res[$d['g']])) $res[$d['g']] = self::empty_day();
					$r = self::zero();
					foreach (self::$nums as $n) $r[$n] = intval($d[$n]);
					if ($d['kind'] == 'c') $r['name'] = $d['name'];
					$res[$d['g']][$d['kind']][$d['row_key']] = $r;
				}
			}
			if ($today >= $from && $today < $to) {
				$live = self::calc($cmp, $br, $today, self::next($today));
				if (isset($live[$today])) self::merge($res, $gk($today), $live[$today]);
			}
			return $res;
		}

		public static function merge(&$res, $g, $day) {
			if (!isset($res[$g])) $res[$g] = self::empty_day();
			foreach (['f', 'c'] as $kind) foreach ($day[$kind] as $k => $r) {
				if (!isset($res[$g][$kind][$k])) $res[$g][$kind][$k] = self::zero();
				foreach (self::$nums as $n) $res[$g][$kind][$k][$n] += intval(@$r[$n]);
				if (isset($r['name'])) $res[$g][$kind][$k]['name'] = $r['name'];
			}
		}


		// ---------- 06:00 жабу ----------
		// Аяқталған жұмыс күнін барлық компания үшін сақтайды:
		//  - $cday = true болса, сақталмаған курьерлердің күндік есебі өзі сақталады (courier_day_report, accepted_by = 0),
		//    сонда ашық кассадағы шығын/қолма-қол келесі күнге ауыспайды;
		//  - толық касса суреті: компания бойынша (branch 0) және әр точка бойынша.
		public static function close($day, $cday = true) {
			$q = db::query("select id from company");
			while ($q && $c = mysqli_fetch_assoc($q)) {
				$cmp = intval($c['id']);
				if ($cday && cday::ready()) {
					$cb = db::query("select id from report_1 where paid = 0 and company_id = '$cmp' order by id desc limit 1");
					$cb_id = ($cb && mysqli_num_rows($cb)) ? intval(mysqli_fetch_assoc($cb)['id']) : 0;
					foreach (cday::couriers($cmp) as $uid => $nm) if (!cday::get($cmp, $uid, $day)) cday::save($cmp, $uid, $day, $cb_id, 0);
				}
				self::save_company($cmp, $day, 1);
			}
			$t = self::T; $now = date('Y-m-d H:i:s');
			db::query("INSERT INTO `$t` (company_id, branch_id, day, kind, row_key, auto, ins_dt) VALUES (0, 0, '$day', 'r', 'run', 1, '$now')
				ON DUPLICATE KEY UPDATE ins_dt = '$now'");
		}

		// Жабылмаған өткен күндерді жабады. Қайтарады: жабылған күндер тізімі.
		public static function auto() {
			if (!self::ready()) return [];
			$t = self::T;
			$y = date('Y-m-d', strtotime(self::today().' -1 day'));
			$q = db::query("select max(day) as m from `$t` where company_id = 0 and kind = 'r'");
			$last = $q ? @mysqli_fetch_assoc($q)['m'] : null;
			if ($last && $last >= $y) return [];

			$lk = db::query("select GET_LOCK('berik_ksnap', 0) as l");
			if (!$lk || !intval(@mysqli_fetch_assoc($lk)['l'])) return [];
			$done = [];
			// қайта тексеру (басқа сұраныс жауып үлгерген болуы мүмкін)
			$q = db::query("select max(day) as m from `$t` where company_id = 0 and kind = 'r'");
			$last = $q ? @mysqli_fetch_assoc($q)['m'] : null;
			if (!$last || $last < $y) {
				// Бірінші іске қосылу: тек кешегі күн, курьер есебін автомат жаппаймыз (ашық касса қай күнге тиесілі белгісіз).
				// Әйтпесе: өткізіп алған күндер (ең көбі 31), курьер есебі тек соңғы аяқталған күнге.
				$from = $last ? max(self::next($last), date('Y-m-d', strtotime("$y -30 day"))) : $y;
				for ($d = $from; $d <= $y; $d = self::next($d)) {
					self::close($d, $last && $d == $y);
					$done[] = $d;
				}
			}
			db::query("select RELEASE_LOCK('berik_ksnap')");
			return $done;
		}
	}
