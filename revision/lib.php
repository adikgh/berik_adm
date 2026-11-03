<?
	// Ревизия: қоймадағы шикізатты нақты санап, базадағы қалдықпен салыстыру.
	// Әр жол: бастапқы қалдық (өткен ревизия) + закуп (накладной) − нақты = шығын.
	// Аяқталған ревизия базадағы қалдықты (product_raw_stock) нақты санға теңестіреді.
	// Ревизияны аспазшы (9) да, админ де жаза алады.

	include_once __DIR__.'/../waybill/lib.php';

	class rv {

		const COOK = 9;

		public static function install() {
			wb::install();
			db::query("CREATE TABLE IF NOT EXISTS `stock_revision` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) NOT NULL DEFAULT 1,
				`number` int(11) NOT NULL,
				`rev_date` date NOT NULL,
				`status` tinyint(1) NOT NULL DEFAULT 0,
				`comment` text DEFAULT NULL,
				`user_id` int(11) DEFAULT NULL,
				`posted_user_id` int(11) DEFAULT NULL,
				`posted_dt` datetime DEFAULT NULL,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `company_branch_date` (`company_id`, `branch_id`, `rev_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

			// qty_start, qty_in, qty_db аяқталған сәтте сақталады (кейін өзгермейді)
			db::query("CREATE TABLE IF NOT EXISTS `stock_revision_item` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`revision_id` int(11) NOT NULL,
				`raw_id` int(11) NOT NULL,
				`unit` varchar(20) DEFAULT NULL,
				`qty_start` decimal(12,3) NOT NULL DEFAULT 0,
				`qty_in` decimal(12,3) NOT NULL DEFAULT 0,
				`qty_db` decimal(12,3) NOT NULL DEFAULT 0,
				`qty_fact` decimal(12,3) NOT NULL DEFAULT 0,
				`price` decimal(12,2) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `rev_raw` (`revision_id`, `raw_id`),
				KEY `raw_id` (`raw_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		}

		// кім кіре алады: аспазшы, директор, құрушы (кассир кірмейді)
		public static function can($right) { return $right && (int)@$right['positions_id'] != 3; }
		public static function is_cook($right) { return (int)@$right['positions_id'] == self::COOK; }

		public static function status($r) {
			if ($r['status'] == 1) return ['ok', 'Аяқталды'];
			return ['draft', 'Санап жатыр'];
		}

		public static function get($id, $cmp) {
			$id = (int)$id; $cmp = (int)$cmp;
			$q = db::query("select * from stock_revision where id = '$id' and company_id = '$cmp' limit 1");
			return $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
		}

		// raw_id => жол
		public static function items($id) {
			$id = (int)$id; $r = [];
			$q = db::query("select * from stock_revision_item where revision_id = '$id'");
			while ($q && $i = mysqli_fetch_assoc($q)) $r[(int)$i['raw_id']] = $i;
			return $r;
		}

		public static function next_number($cmp) {
			$cmp = (int)$cmp;
			$r = mysqli_fetch_assoc(db::query("select IFNULL(MAX(number), 0) + 1 n from stock_revision where company_id = '$cmp'"));
			return (int)$r['n'];
		}

		// осы ревизиядан бұрынғы соңғы аяқталған ревизия
		public static function prev($cmp, $br, $rev) {
			$cmp = (int)$cmp; $br = (int)$br; $id = (int)@$rev['id'];
			$d = wb::esc($rev['rev_date']);
			$q = db::query("select * from stock_revision where company_id = '$cmp' and branch_id = '$br' and status = 1 and id != '$id'
				and (rev_date < '$d' or (rev_date = '$d' and id < '$id'))
				order by rev_date desc, id desc limit 1");
			return $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
		}

		// Әр шикізат бойынша қазіргі сандар: бастапқы, закуп, базада, соңғы баға.
		// Бастапқы — осы шикізат саналған соңғы аяқталған ревизиядағы нақты сан,
		// закуп — сол ревизия күнінен кейін (сол күн кірмейді) осы ревизия күніне дейін қабылданған накладной.
		public static function live($cmp, $br, $rev) {
			$cmp = (int)$cmp; $br = (int)$br; $id = (int)@$rev['id'];
			$prev = self::prev($cmp, $br, $rev);
			$to = wb::esc($rev['rev_date']);

			$start = []; $since = [];
			$q = db::query("select i.raw_id, i.qty_fact, v.rev_date from stock_revision_item i join stock_revision v on v.id = i.revision_id
				where v.company_id = '$cmp' and v.branch_id = '$br' and v.status = 1 and v.id != '$id'
				and (v.rev_date < '$to' or (v.rev_date = '$to' and v.id < '$id'))
				order by v.rev_date desc, v.id desc");
			while ($q && $r = mysqli_fetch_assoc($q)) {
				$k = (int)$r['raw_id'];
				if (isset($start[$k])) continue;
				$start[$k] = (float)$r['qty_fact']; $since[$k] = $r['rev_date'];
			}
			$in = [];
			$q = db::query("select i.raw_id, w.doc_date, SUM(i.quantity) q from waybill_item i join waybill w on w.id = i.waybill_id
				where w.company_id = '$cmp' and w.branch_id = '$br' and w.status = 1 and i.raw_id > 0 and w.doc_date <= '$to'
				group by i.raw_id, w.doc_date");
			while ($q && $r = mysqli_fetch_assoc($q)) {
				$k = (int)$r['raw_id'];
				if (isset($since[$k]) && $r['doc_date'] <= $since[$k]) continue;
				$in[$k] = ($in[$k] ?? 0) + (float)$r['q'];
			}

			$rows = [];
			$q = db::query("select r.id, r.name_kz, r.name_ru, r.unit, r.price, IFNULL(st.quantity, 0) qty
				from product_raw r
				left join product_raw_stock st on st.raw_id = r.id and st.company_id = '$cmp' and st.branch_id = '$br'
				where r.company_id = '$cmp' order by r.name_kz, r.name_ru");
			while ($q && $r = mysqli_fetch_assoc($q)) {
				$id = (int)$r['id'];
				$rows[$id] = [
					'raw_id' => $id,
					'name' => trim($r['name_kz'] ?: $r['name_ru']),
					'unit' => fun::unit_norm($r['unit']),
					'price' => (float)$r['price'],
					'qty_start' => $start[$id] ?? 0,
					'qty_in' => $in[$id] ?? 0,
					'qty_db' => (float)$r['qty'],
				];
			}
			return ['prev' => $prev, 'rows' => $rows];
		}

		// Аяқтау: нақты санды сақтап, базадағы қалдықты соған теңестіру
		public static $err = '';
		public static function post($id, $cmp, $uid) {
			self::$err = '';
			$rev = self::get($id, $cmp);
			if (!$rev || $rev['status'] == 1) { self::$err = 'Ревизия табылмады немесе аяқталған'; return false; }
			$items = self::items($id);
			if (!$items) { self::$err = 'Кемінде бір шикізатты санаңыз'; return false; }
			$live = self::live($cmp, $rev['branch_id'], $rev)['rows'];
			$br = (int)$rev['branch_id']; $now = date('Y-m-d H:i:s');
			$ok = true;
			mysqli_begin_transaction(db::$connect);
			foreach ($items as $raw => $i) {
				$l = $live[$raw] ?? null;
				if (!$l) { db::query("delete from stock_revision_item where id = '".(int)$i['id']."'"); continue; }
				$fact = round((float)$i['qty_fact'], 3);
				$ok = db::query("update stock_revision_item set unit = '".wb::esc($l['unit'])."', qty_start = '".$l['qty_start']."', qty_in = '".$l['qty_in']."',
					qty_db = '".$l['qty_db']."', price = '".$l['price']."' where id = '".(int)$i['id']."'");
				if ($ok) $ok = db::query("insert into product_raw_stock (company_id, branch_id, raw_id, quantity, upd_dt)
					values ('".(int)$cmp."', '$br', '".(int)$raw."', '$fact', '$now')
					on duplicate key update quantity = values(quantity), upd_dt = values(upd_dt)");
				if (!$ok) break;
			}
			if ($ok) $ok = db::query("update stock_revision set status = 1, posted_dt = '$now', posted_user_id = '".(int)$uid."', upd_dt = '$now'
				where id = '".(int)$id."' and status = 0") && mysqli_affected_rows(db::$connect) == 1;
			if ($ok) mysqli_commit(db::$connect); else { mysqli_rollback(db::$connect); if (!self::$err) self::$err = 'Сақтау кезінде қате шықты'; }
			return $ok;
		}

		// Аяқтауды болдырмау: қалдыққа айырманы қайтару (кейін келген накладнойлар сақталады)
		public static function unpost($id, $cmp) {
			$rev = self::get($id, $cmp);
			if (!$rev || $rev['status'] != 1) return false;
			$ok = true;
			mysqli_begin_transaction(db::$connect);
			foreach (self::items($id) as $raw => $i) {
				$back = round((float)$i['qty_db'] - (float)$i['qty_fact'], 3);
				if ($back != 0 && !wb::stock_add($cmp, $rev['branch_id'], $raw, $back)) { $ok = false; break; }
			}
			if ($ok) $ok = db::query("update stock_revision set status = 0, posted_dt = NULL, posted_user_id = NULL, upd_dt = '".date('Y-m-d H:i:s')."'
				where id = '".(int)$id."' and status = 1") && mysqli_affected_rows(db::$connect) == 1;
			if ($ok) mysqli_commit(db::$connect); else mysqli_rollback(db::$connect);
			return $ok;
		}

		// бетке шығатын жолдар: аяқталғанда сақталған сандар, әйтпесе қазіргі
		public static function rows($cmp, $rev) {
			$items = $rev['id'] ? self::items($rev['id']) : [];
			$live = self::live($cmp, $rev['branch_id'], $rev);
			$rows = [];
			foreach ($live['rows'] as $raw => $l) {
				$i = $items[$raw] ?? null;
				if ($rev['status'] == 1) {
					if (!$i) continue;
					$l = array_merge($l, ['unit' => $i['unit'] ?: $l['unit'], 'qty_start' => (float)$i['qty_start'], 'qty_in' => (float)$i['qty_in'],
						'qty_db' => (float)$i['qty_db'], 'price' => (float)$i['price']]);
				}
				$l['fact'] = $i ? (float)$i['qty_fact'] : null;
				$rows[$raw] = $l;
			}
			return ['prev' => $live['prev'], 'rows' => $rows];
		}

		// шығын (қолданылғаны) = бастапқы + закуп − нақты; айырма = нақты − базада
		public static function calc($r) {
			if ($r['fact'] === null) return ['used' => null, 'diff' => null];
			return ['used' => round($r['qty_start'] + $r['qty_in'] - $r['fact'], 3), 'diff' => round($r['fact'] - $r['qty_db'], 3)];
		}

		public static function qs($v) { $v = round((float)$v, 3); return ($v > 0 ? '+' : '').wb::qty($v); }
	}
