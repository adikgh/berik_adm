<?
	// Накладной: жеткізушіден келген тауарды қабылдау.
	// Қабылданған накладной шикізат қалдығына (product_raw_stock) қосылады.
	// Атауы жаңа тауар қабылдағанда product_raw кестесіне өзі қосылады.

	class wb {

		// негізгі жиын fun::$units-пен бірдей (кг, г, л, мл, шт), қаптама өлшемдері қосымша
		public static $units = ['кг', 'г', 'л', 'мл', 'шт', 'қап', 'қорап', 'бума'];

		// Кестелерді жасау (бір рет, кейін ештеңе істемейді)
		public static function install() {
			db::query("CREATE TABLE IF NOT EXISTS `supplier` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`name` varchar(255) NOT NULL,
				`phone` varchar(30) DEFAULT NULL,
				`note` text DEFAULT NULL,
				`archive` tinyint(1) NOT NULL DEFAULT 0,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `company_id` (`company_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			db::query("CREATE TABLE IF NOT EXISTS `waybill` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) NOT NULL DEFAULT 1,
				`number` int(11) NOT NULL,
				`supplier_id` int(11) DEFAULT NULL,
				`doc_no` varchar(64) DEFAULT NULL,
				`doc_date` date NOT NULL,
				`total` decimal(14,2) NOT NULL DEFAULT 0,
				`paid` decimal(14,2) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 0,
				`comment` text DEFAULT NULL,
				`user_id` int(11) DEFAULT NULL,
				`posted_dt` datetime DEFAULT NULL,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `company_date` (`company_id`, `doc_date`),
				KEY `supplier_id` (`supplier_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			db::query("CREATE TABLE IF NOT EXISTS `waybill_item` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`waybill_id` int(11) NOT NULL,
				`raw_id` int(11) DEFAULT NULL,
				`name` varchar(255) NOT NULL,
				`unit` varchar(20) NOT NULL DEFAULT 'кг',
				`quantity` decimal(12,3) NOT NULL DEFAULT 0,
				`price` decimal(12,2) NOT NULL DEFAULT 0,
				`sum` decimal(14,2) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				KEY `waybill_id` (`waybill_id`),
				KEY `raw_id` (`raw_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			db::query("CREATE TABLE IF NOT EXISTS `product_raw_stock` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) NOT NULL DEFAULT 1,
				`raw_id` int(11) NOT NULL,
				`quantity` decimal(12,3) NOT NULL DEFAULT 0,
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `branch_raw` (`company_id`, `branch_id`, `raw_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			if (!fun::has_col('product_raw', 'unit')) {
				db::query("ALTER TABLE `product_raw` ADD COLUMN `unit` varchar(20) DEFAULT NULL AFTER `unit_id`");
				fun::$cols['product_raw.unit'] = true;
			}
		}

		public static function esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$v))); }
		public static function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES); }
		public static function num($v) { return (float)str_replace([' ', ','], ['', '.'], (string)$v); }

		// 12 500 ₸
		public static function sum($v) {
			$v = round((float)$v, 2);
			return number_format($v, (floor($v) == $v ? 0 : 2), '.', ' ').' ₸';
		}
		// 1.5, 2, 0.25
		public static function qty($v) {
			$v = round((float)$v, 3);
			return rtrim(rtrim(number_format($v, 3, '.', ' '), '0'), '.');
		}
		// 23 қаз 2026
		public static function dt($v, $time = false) {
			if (!$v || substr($v, 0, 4) == '0000') return '—';
			$m = ['', 'қаң', 'ақп', 'нау', 'сәу', 'мам', 'мау', 'шіл', 'там', 'қыр', 'қаз', 'қар', 'жел'];
			$t = strtotime($v);
			$s = date('j', $t).' '.$m[(int)date('n', $t)].' '.date('Y', $t);
			if ($time) $s .= ', '.date('H:i', $t);
			return $s;
		}

		public static function status($w) {
			if ($w['status'] == 1) return ['ok', 'Қабылданды'];
			return ['draft', 'Жоба'];
		}
		public static function debt($w) { return max(0, round((float)$w['total'] - (float)$w['paid'], 2)); }

		public static function get($id, $cmp) {
			$id = (int)$id; $cmp = (int)$cmp;
			$q = db::query("select w.*, s.name supplier_name from waybill w left join supplier s on s.id = w.supplier_id
				where w.id = '$id' and w.company_id = '$cmp' limit 1");
			return $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
		}
		public static function items($id) {
			$id = (int)$id; $r = [];
			$q = db::query("select * from waybill_item where waybill_id = '$id' order by id");
			while ($q && $i = mysqli_fetch_assoc($q)) $r[] = $i;
			return $r;
		}

		public static function next_number($cmp) {
			$cmp = (int)$cmp;
			$r = mysqli_fetch_assoc(db::query("select IFNULL(MAX(number), 0) + 1 n from waybill where company_id = '$cmp'"));
			return (int)$r['n'];
		}

		// Шикізатты атауы бойынша табу, болмаса жасау
		public static function raw_find_or_create($cmp, $name, $unit, $price) {
			$cmp = (int)$cmp; $n = self::esc($name); $u = self::esc($unit);
			$q = db::query("select id from product_raw where company_id = '$cmp' and (name_kz = '$n' or name_ru = '$n') limit 1");
			if ($q && mysqli_num_rows($q)) return (int)mysqli_fetch_assoc($q)['id'];
			$p = (int)round($price);
			db::query("insert into product_raw (company_id, name_kz, name_ru, unit, price) values ('$cmp', '$n', '$n', '$u', '$p')");
			return (int)mysqli_insert_id(db::$connect);
		}

		// Қалдықты өзгерту (+ немесе -)
		public static function stock_add($cmp, $branch, $raw_id, $qty) {
			$cmp = (int)$cmp; $branch = (int)$branch; $raw_id = (int)$raw_id; $qty = round((float)$qty, 3);
			$now = date('Y-m-d H:i:s');
			return db::query("insert into product_raw_stock (company_id, branch_id, raw_id, quantity, upd_dt)
				values ('$cmp', '$branch', '$raw_id', '$qty', '$now')
				on duplicate key update quantity = quantity + values(quantity), upd_dt = values(upd_dt)");
		}

		// Қабылдау: әр жолды шикізатқа байланыстырып, қалдыққа қосу
		public static $err = '';

		public static function post($id, $cmp) {
			self::$err = '';
			$w = self::get($id, $cmp);
			if (!$w || $w['status'] == 1) return false;
			$items = self::items($id);
			if (!$items) return false;
			// өлшем сәйкес келмесе (қалдық кг-мен, накладнойда шт), қалдыққа қоспаймыз
			foreach ($items as $i) {
				if (!(int)$i['raw_id']) continue;
				$q = db::query("select r.name_kz, r.name_ru, r.unit, IFNULL(s.quantity, 0) q from product_raw r
					left join product_raw_stock s on s.raw_id = r.id and s.company_id = r.company_id and s.branch_id = '".(int)$w['branch_id']."'
					where r.id = '".(int)$i['raw_id']."' limit 1");
				$r = $q ? mysqli_fetch_assoc($q) : null;
				if (!$r) continue;
				$ru = fun::unit_norm($r['unit']); $iu = fun::unit_norm($i['unit']);
				if ($ru !== '' && $iu !== '' && $ru !== $iu && (float)$r['q'] != 0) {
					self::$err = '«'.($r['name_kz'] ?: $r['name_ru']).'» қалдығы '.$ru.'-мен саналады, накладнойда '.$iu.'. Өлшемін '.$ru.' етіп, санын аударыңыз.';
					return false;
				}
			}
			$ok = true;
			mysqli_begin_transaction(db::$connect);
			foreach ($items as $i) {
				$raw = (int)$i['raw_id'];
				if (!$raw) {
					$raw = self::raw_find_or_create($cmp, $i['name'], $i['unit'], $i['price']);
					if (!$raw) { $ok = false; break; }
					db::query("update waybill_item set raw_id = '$raw' where id = '".(int)$i['id']."'");
				}
				// соңғы сатып алу бағасы мен өлшемі
				$p = (int)round($i['price']); $u = self::esc($i['unit']); $now = date('Y-m-d H:i:s');
				db::query("update product_raw set price = '$p', unit = '$u', ubd_dt = '$now' where id = '$raw' and company_id = '".(int)$cmp."'");
				if (!self::stock_add($cmp, $w['branch_id'], $raw, $i['quantity'])) { $ok = false; break; }
			}
			if ($ok) $ok = db::query("update waybill set status = 1, posted_dt = '".date('Y-m-d H:i:s')."', upd_dt = '".date('Y-m-d H:i:s')."' where id = '".(int)$id."' and status = 0") && mysqli_affected_rows(db::$connect) == 1;
			if ($ok) mysqli_commit(db::$connect); else mysqli_rollback(db::$connect);
			return $ok;
		}

		// Қабылдауды болдырмау: қалдықтан қайта алу
		public static function unpost($id, $cmp) {
			$w = self::get($id, $cmp);
			if (!$w || $w['status'] != 1) return false;
			$ok = true;
			mysqli_begin_transaction(db::$connect);
			foreach (self::items($id) as $i) {
				if (!(int)$i['raw_id']) continue;
				if (!self::stock_add($cmp, $w['branch_id'], $i['raw_id'], -$i['quantity'])) { $ok = false; break; }
			}
			if ($ok) $ok = db::query("update waybill set status = 0, posted_dt = NULL, upd_dt = '".date('Y-m-d H:i:s')."' where id = '".(int)$id."' and status = 1") && mysqli_affected_rows(db::$connect) == 1;
			if ($ok) mysqli_commit(db::$connect); else mysqli_rollback(db::$connect);
			return $ok;
		}

		// Кезең: ?p=month|prev|3m|year|all немесе ?from=&to=
		public static function period() {
			$p = @$_GET['p'] ?: 'month';
			$from = @$_GET['from']; $to = @$_GET['to'];
			$today = date('Y-m-d');
			if ($from && strtotime($from)) {
				$p = 'custom';
				$f = date('Y-m-d', strtotime($from));
				$t = ($to && strtotime($to)) ? date('Y-m-d', strtotime($to)) : $today;
			} elseif ($p == 'prev') {
				$f = date('Y-m-01', strtotime(date('Y-m-01').' -1 month'));
				$t = date('Y-m-t', strtotime($f));
			} elseif ($p == '3m') {
				$f = date('Y-m-d', strtotime($today.' -89 day')); $t = $today;
			} elseif ($p == 'year') {
				$f = date('Y-01-01'); $t = $today;
			} elseif ($p == 'all') {
				$f = '2000-01-01'; $t = $today;
			} else {
				$p = 'month'; $f = date('Y-m-01'); $t = $today;
			}
			return ['p' => $p, 'from' => $f, 'to' => $t];
		}

		public static function url($path, $set = []) {
			$g = array_merge($_GET, $set);
			foreach ($g as $k => $v) if ($v === null || $v === '') unset($g[$k]);
			return $path.($g ? '?'.http_build_query($g) : '');
		}
	}
