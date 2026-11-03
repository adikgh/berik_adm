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
			// закуп тобы: raw — тағамға кіреді, pack — қаптама, hoz — шаруашылық (NULL — атауынан анықталады)
			if (!fun::has_col('product_raw', 'grp')) {
				db::query("ALTER TABLE `product_raw` ADD COLUMN `grp` varchar(20) DEFAULT NULL");
				fun::$cols['product_raw.grp'] = true;
			}
			// factor: накладной жолының 1 өлшемінде шикізаттың неше негізгі өлшемі бар (Рис 5 кг, 1 шт → 5)
			if (!fun::has_col('waybill_item', 'factor')) {
				db::query("ALTER TABLE `waybill_item` ADD COLUMN `factor` decimal(12,4) NOT NULL DEFAULT 1 AFTER `unit`");
				fun::$cols['waybill_item.factor'] = true;
			}
			// қалдық қозғалысы: in — накладной, unpost — қабылдау болдырылмады, sale — сатылым, rev — ревизия.
			// product_raw_stock = SUM(qty) әр филиал мен шикізат бойынша
			db::query("CREATE TABLE IF NOT EXISTS `product_raw_move` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) NOT NULL,
				`raw_id` int(11) NOT NULL,
				`dt` datetime NOT NULL,
				`type` varchar(10) NOT NULL,
				`qty` decimal(12,3) NOT NULL DEFAULT 0,
				`price` decimal(12,2) NOT NULL DEFAULT 0,
				`ref_type` varchar(20) DEFAULT NULL,
				`ref_id` int(11) DEFAULT NULL,
				`order_id` int(11) DEFAULT NULL,
				`note` varchar(255) DEFAULT NULL,
				`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				UNIQUE KEY `sale_once` (`type`, `order_id`, `raw_id`),
				KEY `raw_dt` (`company_id`, `raw_id`, `dt`),
				KEY `ref` (`ref_type`, `ref_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			self::sales_tables();
		}

		// жолдың factor-ы (1-ден аз/көп болуы мүмкін, 0 болмайды)
		public static function f($i) { $f = (float)@$i['factor']; return $f > 0 ? $f : 1; }

		public static $groups = ['raw' => 'Шикізат (тағамға кіреді)', 'pack' => 'Қаптама', 'hoz' => 'Шаруашылық', 'other' => 'Басқа'];

		// Тобы белгіленбесе, атауынан анықтаймыз
		public static function grp_guess($name) {
			$n = mb_strtolower((string)$name);
			if (preg_match('/перчат/u', $n)) return 'hoz';
			if (strpos($n, 'краб') === false && preg_match('/бокс|box|посуда|соусниц|коробк|пакет|палочк|хаси|упаковк|контейнер|прк/u', $n)) return 'pack';
			return 'raw';
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

		// Қалдық қозғалысы: журналға жазып, қалдықты өзгертеді
		public static function move($cmp, $branch, $raw_id, $qty, $type, $price = 0, $ref_type = null, $ref_id = null, $order_id = null, $note = '', $dt = null) {
			$cmp = (int)$cmp; $branch = (int)$branch; $raw_id = (int)$raw_id; $qty = round((float)$qty, 3); $price = round((float)$price, 2);
			$t = self::esc($type); $rt = $ref_type ? "'".self::esc($ref_type)."'" : 'NULL'; $ri = $ref_id ? (int)$ref_id : 'NULL';
			$oi = $order_id ? (int)$order_id : 'NULL'; $n = self::esc(mb_substr((string)$note, 0, 255));
			$dt = $dt ? date('Y-m-d H:i:s', strtotime($dt)) : date('Y-m-d H:i:s');
			if (!db::query("insert into product_raw_move (company_id, branch_id, raw_id, dt, type, qty, price, ref_type, ref_id, order_id, note)
				values ('$cmp', '$branch', '$raw_id', '$dt', '$t', '$qty', '$price', $rt, $ri, $oi, '$n')")) return false;
			return self::stock_add($cmp, $branch, $raw_id, $qty);
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
				// factor бағаны бар болса, «1 жол өлшемі = factor шикізат өлшемі» деп саналады (Колбаса 1 шт = 1 кг)
				if (self::f($i) != 1 || fun::has_col('waybill_item', 'factor')) continue;
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
				// соңғы сатып алу бағасы мен өлшемі (factor бар болса — шикізаттың өз өлшемімен)
				$f = self::f($i); $now = date('Y-m-d H:i:s');
				$p = (int)round($i['price'] / $f);
				// шикізаттың өлшемі бұрыннан болса өзгертпейміз (қалдық сол өлшеммен саналады)
				db::query("update product_raw set price = '$p', unit = IF(IFNULL(unit, '') = '', '".self::esc($i['unit'])."', unit), ubd_dt = '$now' where id = '$raw' and company_id = '".(int)$cmp."'");
				if (!self::move($cmp, $w['branch_id'], $raw, $i['quantity'] * $f, 'in', $i['price'] / $f, 'waybill', $id, null, $i['name'], $w['doc_date'].' 12:00:00')) { $ok = false; break; }
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
				if (!self::move($cmp, $w['branch_id'], $i['raw_id'], -$i['quantity'] * self::f($i), 'unpost', $i['price'] / self::f($i), 'waybill', $id, null, $i['name'])) { $ok = false; break; }
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

		// Күн | Апта | Ай | Жыл (+ Барлығы) кезеңі: ?mode=d|w|m|y|a&d=YYYY-MM-DD
		public static function per2($def = 'm', $all = false) {
			$modes = $all ? ['d', 'w', 'm', 'y', 'a'] : ['d', 'w', 'm', 'y'];
			$mode = in_array(@$_GET['mode'], $modes, true) ? $_GET['mode'] : $def;
			$today = date('Y-m-d');
			$ad = (preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)@$_GET['d']) && strtotime($_GET['d'])) ? date('Y-m-d', strtotime($_GET['d'])) : $today;
			$at = strtotime($ad);
			$mn = ['', 'Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'];
			$ml = ['', 'қаң', 'ақп', 'нау', 'сәу', 'мам', 'мау', 'шіл', 'там', 'қыр', 'қаз', 'қар', 'жел'];
			$prev = $next = null;
			if ($mode == 'd') {
				$pf = $pt = $ad;
				$title = date('j', $at).' '.$ml[(int)date('n', $at)].' '.date('Y', $at);
				$prev = date('Y-m-d', strtotime('-1 day', $at)); $next = date('Y-m-d', strtotime('+1 day', $at));
			} elseif ($mode == 'w') {
				$ws = strtotime('-'.((int)date('N', $at) - 1).' day', $at); $we = strtotime('+6 day', $ws);
				$pf = date('Y-m-d', $ws); $pt = date('Y-m-d', $we);
				$title = date('j', $ws).(date('n', $ws) != date('n', $we) ? ' '.$ml[(int)date('n', $ws)] : '').' – '.date('j', $we).' '.$ml[(int)date('n', $we)];
				$prev = date('Y-m-d', strtotime('-7 day', $ws)); $next = date('Y-m-d', strtotime('+7 day', $ws));
			} elseif ($mode == 'y') {
				$pf = date('Y-01-01', $at); $pt = date('Y-12-31', $at);
				$title = date('Y', $at).' жыл';
				$prev = (date('Y', $at) - 1).'-01-01'; $next = (date('Y', $at) + 1).'-01-01';
			} elseif ($mode == 'a') {
				$pf = '2000-01-01'; $pt = '2100-12-31'; $title = 'Барлық уақыт';
			} else {
				$pf = date('Y-m-01', $at); $pt = date('Y-m-t', $at);
				$title = $mn[(int)date('n', $at)].' '.date('Y', $at);
				$prev = date('Y-m-d', strtotime("$pf -1 month")); $next = date('Y-m-d', strtotime("$pf +1 month"));
			}
			$keep = ($mode == 'a' || ($today >= $pf && $today <= $pt)) ? '' : '&d='.$pf;
			return compact('mode', 'pf', 'pt', 'title', 'prev', 'next', 'keep', 'all');
		}

		// Кезең ауыстырғышы (Күн | Апта | Ай | Жыл, ‹ ›). $q — сақталатын басқа параметрлер (мыс. '&id=5')
		public static function per2_bar($p, $q = '') {
			$names = ['d' => 'Күн', 'w' => 'Апта', 'm' => 'Ай', 'y' => 'Жыл'] + ($p['all'] ? ['a' => 'Барлығы'] : []);
			$h = '<div class="wb_bar"><div class="wb_tabs">';
			foreach ($names as $k => $n) $h .= '<a class="wb_tab'.($k == $p['mode'] ? ' wb_tab_act' : '').'" href="?mode='.$k.($k == 'a' ? '' : $p['keep']).$q.'">'.$n.'</a>';
			$h .= '</div><div class="wb_tabs wbg_nav">';
			if ($p['prev']) $h .= '<a class="wb_tab wbg_nav_b" href="?mode='.$p['mode'].'&d='.$p['prev'].$q.'" aria-label="Алдыңғы"><i class="far fa-chevron-left"></i></a>';
			$h .= '<div class="wbg_nav_t">'.$p['title'].'</div>';
			if ($p['next']) $h .= '<a class="wb_tab wbg_nav_b" href="?mode='.$p['mode'].'&d='.$p['next'].$q.'" aria-label="Келесі"><i class="far fa-chevron-right"></i></a>';
			return $h.'</div></div>';
		}

		// Шикізаттың сатып алулары (ескісінен жаңасына), баға шикізаттың 1 өлшеміне
		public static function raw_buys($cmp, $raw_id, $pf = '2000-01-01', $pt = '2100-12-31') {
			$cmp = (int)$cmp; $raw_id = (int)$raw_id; $r = [];
			$q = db::query("select i.*, w.id wid, w.number, w.doc_no, w.doc_date, w.status, s.name sname
				from waybill_item i join waybill w on w.id = i.waybill_id left join supplier s on s.id = w.supplier_id
				where w.company_id = '$cmp' and i.raw_id = '$raw_id' and w.doc_date between '$pf' and '$pt'
				order by w.doc_date, w.id, i.id");
			while ($q && $i = mysqli_fetch_assoc($q)) { $i['up'] = (float)$i['price'] / self::f($i); $i['bq'] = (float)$i['quantity'] * self::f($i); $r[] = $i; }
			return $r;
		}

		// Өзгеріс белгісі: ↑ қымбаттады (қызыл), ↓ арзандады (жасыл)
		public static function delta($new, $old) {
			if (!$old || $new == $old) return '<span class="wb_small">—</span>';
			$p = ($new - $old) / $old * 100;
			$c = $p > 0 ? 'wb_c_red' : 'wb_c_green';
			return '<b class="'.$c.'">'.($p > 0 ? '↑ ' : '↓ ').number_format(abs($p), 1, '.', ' ').'%</b>';
		}

		// баға: 1 450 ₸, кіші бағада тиын көрсетіледі (6.5 ₸)
		public static function up($v) {
			$v = (float)$v;
			return number_format($v, $v < 100 && round($v, 2) != round($v) ? 2 : 0, '.', ' ').' ₸';
		}

		// ---------- Белгілер: граммовкасы жоқ тағамдар, минустағы шикізат ----------

		// Тағамның құрамы шикізатқа дейін жете ме: dish[product_id] = 'none' (мүлде жоқ) | 'part' (бір бөлігі шикізатқа байланбаған),
		// comp[comp_id] = аты (шикізаты да, рецепті де жоқ дайындама, мыс. «Пепси»)
		public static $cc_cache = [];
		public static function comp_check($cmp) {
			$cmp = (int)$cmp;
			if (isset(self::$cc_cache[$cmp])) return self::$cc_cache[$cmp];
			$res = ['dish' => [], 'comp' => []];
			$comps = []; $items = []; $sets = [];
			$q = db::query("select id, name_ru, name_kz, raw_id from product_comp where company_id = '$cmp'");
			while ($q && $r = mysqli_fetch_assoc($q)) { $r['parts'] = []; $comps[(int)$r['id']] = $r; }
			if ($comps) {
				$q = db::query("select comp_id, raw_id, sub_comp_id from product_comp_part where comp_id in (".implode(',', array_keys($comps)).")");
				while ($q && $r = mysqli_fetch_assoc($q)) $comps[(int)$r['comp_id']]['parts'][] = $r;
			}
			$q = db::query("select pi.product_id, pi.comp_id from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0");
			while ($q && $r = mysqli_fetch_assoc($q)) $items[(int)$r['product_id']][] = (int)$r['comp_id'];
			$q = db::query("select si.set_id, si.product_id, si.comp_id from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp'");
			while ($q && $r = mysqli_fetch_assoc($q)) $sets[(int)$r['set_id']][] = $r;

			$cm = [];
			$comp_ok = function ($cid, $d = 0) use (&$comp_ok, &$cm, $comps, &$res) {
				if (isset($cm[$cid])) return $cm[$cid];
				$c = $comps[$cid] ?? null;
				if (!$c || $d > 8) return $cm[$cid] = false;
				$cm[$cid] = true; // циклден қорғау
				if ($c['parts']) {
					$ok = true;
					foreach ($c['parts'] as $p) if (!$p['raw_id'] && !($p['sub_comp_id'] && $comp_ok((int)$p['sub_comp_id'], $d + 1))) $ok = false;
				} else $ok = (bool)$c['raw_id'];
				if (!$ok && !$c['parts']) $res['comp'][$cid] = $c['name_ru'] ?: $c['name_kz'];
				return $cm[$cid] = $ok;
			};
			$dm = [];
			$dish_st = function ($pid, $d = 0) use (&$dish_st, &$dm, $comp_ok, $items, $sets) {
				if (isset($dm[$pid])) return $dm[$pid];
				if ($d > 5) return 'none';
				$dm[$pid] = 'ok';
				if (isset($items[$pid])) {
					$st = 'ok';
					foreach ($items[$pid] as $cid) if (!$comp_ok($cid)) $st = 'part';
				} elseif (isset($sets[$pid])) {
					$st = 'ok';
					foreach ($sets[$pid] as $s) {
						$x = $s['product_id'] ? $dish_st((int)$s['product_id'], $d + 1) : ($comp_ok((int)$s['comp_id']) ? 'ok' : 'part');
						if ($x != 'ok') $st = 'part';
					}
				} else $st = 'none';
				return $dm[$pid] = $st;
			};
			$q = db::query("select id from product where company_id = '$cmp' and arh = 0");
			while ($q && $r = mysqli_fetch_assoc($q)) { $s = $dish_st((int)$r['id']); if ($s != 'ok') $res['dish'][(int)$r['id']] = $s; }
			// тағамға кірмейтін бос дайындамалар да белгіленеді
			foreach ($comps as $cid => $c) $comp_ok($cid);
			return self::$cc_cache[$cmp] = $res;
		}

		// Белгі HTML: тағам аты жанына
		public static function comp_badge($st) {
			if ($st == 'none') return ' <span class="wbm_b" style="display:inline-block;padding:2px 8px;border-radius:20px;background:#fdecec;color:#c0392b;font-size:11px;font-weight:700;white-space:nowrap;vertical-align:middle">Граммовка жоқ</span>';
			if ($st == 'part') return ' <span class="wbm_b" style="display:inline-block;padding:2px 8px;border-radius:20px;background:#fff3d6;color:#b07800;font-size:11px;font-weight:700;white-space:nowrap;vertical-align:middle">Граммовка толық емес</span>';
			return '';
		}

		// Қалдық белгісі: «Закуп жоқ» — мүлде сатып алынбаған, «Минус» — сатылымнан бітіп кеткен
		public static function stock_badge($qty, $q_in) {
			if ($qty >= 0) return '';
			if ($q_in <= 0) return ' <span class="wbm_b" title="Бұл шикізатқа накладной енгізілмеген" style="display:inline-block;padding:2px 8px;border-radius:20px;background:#ece6f7;color:#6c3fb5;font-size:11px;font-weight:700;white-space:nowrap;vertical-align:middle">Закуп жоқ</span>';
			return ' <span class="wbm_b" title="Накладной енгізіңіз немесе ревизия жасаңыз" style="display:inline-block;padding:2px 8px;border-radius:20px;background:#fdecec;color:#c0392b;font-size:11px;font-weight:700;white-space:nowrap;vertical-align:middle">Минус</span>';
		}

		// Мәзірдегі санауыштар (2 минут сессияда сақталады): соңғы 30 күнде сатылған граммовкасы жоқ тағам, минустағы шикізат
		public static function menu_counts($cmp, $branch) {
			$cmp = (int)$cmp; $k = 'wbm_'.$cmp;
			if (!$cmp) return ['dish' => 0, 'neg' => 0];
			if (isset($_SESSION[$k]) && $_SESSION[$k]['t'] > time() - 120) return $_SESSION[$k];
			$miss = self::comp_check($cmp)['dish'];
			$sold = 0;
			if ($miss) {
				$from = date('Y-m-d', strtotime('-30 day'));
				$q = db::query("select COUNT(DISTINCT rop.product_id) n from retail_orders_products rop join retail_orders o on o.id = rop.order_id
					where o.company_id = '$cmp' and o.order_status = 4 and o.ins_dt >= '$from' and rop.product_id in (".implode(',', array_keys($miss)).")");
				$sold = $q ? (int)mysqli_fetch_assoc($q)['n'] : 0;
			}
			$br = self::branch($cmp, $branch);
			$q = db::query("select COUNT(*) n from product_raw_stock where company_id = '$cmp' and branch_id = '$br' and quantity < 0");
			$neg = $q ? (int)mysqli_fetch_assoc($q)['n'] : 0;
			return $_SESSION[$k] = ['dish' => $sold, 'neg' => $neg, 't' => time()];
		}

		// ---------- Сатылымнан шикізатты азайту (граммовка бойынша) ----------

		public static function sales_tables() {
			db::query("CREATE TABLE IF NOT EXISTS `wb_setting` (
				`company_id` int(11) NOT NULL,
				`sales_from` date DEFAULT NULL,
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`company_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			db::query("CREATE TABLE IF NOT EXISTS `revision` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`branch_id` int(11) NOT NULL,
				`number` int(11) NOT NULL,
				`dt` datetime NOT NULL,
				`status` tinyint(1) NOT NULL DEFAULT 0,
				`user_id` int(11) DEFAULT NULL,
				`comment` text DEFAULT NULL,
				`done_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `company_id` (`company_id`, `branch_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
			db::query("CREATE TABLE IF NOT EXISTS `revision_item` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`revision_id` int(11) NOT NULL,
				`raw_id` int(11) NOT NULL,
				`expected` decimal(12,3) NOT NULL DEFAULT 0,
				`counted` decimal(12,3) DEFAULT NULL,
				`diff` decimal(12,3) DEFAULT NULL,
				`price` decimal(12,2) NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `rev_raw` (`revision_id`, `raw_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		}

		// Компанияның филиалы: сессиядағы филиал компанияныкі болмаса, біріншісі
		public static function branch($cmp, $branch) {
			$cmp = (int)$cmp; $ids = [];
			$q = db::query("select id from company_branch where company_id = '$cmp' order by number, id");
			while ($q && $b = mysqli_fetch_assoc($q)) $ids[] = (int)$b['id'];
			return ($ids && !in_array((int)$branch, $ids)) ? $ids[0] : (int)$branch;
		}

		// Сатылым қай күннен бастап азайтылады: баптауда болмаса — бірінші қабылданған накладной күні
		public static function sales_from($cmp) {
			$cmp = (int)$cmp;
			$r = mysqli_fetch_assoc(db::query("select sales_from from wb_setting where company_id = '$cmp'"));
			if ($r && $r['sales_from']) return $r['sales_from'];
			$r = mysqli_fetch_assoc(db::query("select MIN(doc_date) d from waybill where company_id = '$cmp' and status = 1"));
			return $r && $r['d'] ? $r['d'] : null;
		}
		public static function sales_from_set($cmp, $date) {
			$cmp = (int)$cmp; $d = date('Y-m-d', strtotime($date)); $now = date('Y-m-d H:i:s');
			return db::query("insert into wb_setting (company_id, sales_from, upd_dt) values ('$cmp', '$d', '$now')
				on duplicate key update sales_from = values(sales_from), upd_dt = values(upd_dt)");
		}

		// Тағамның шикізат шығыны: product_item → дайындама (кг/л граммен) → рецепт / шикізат; сет → ішіндегі тағамдар
		public static $sale_missing = [];      // product_id => сатылған саны (құрамы жоқ)
		public static $sale_missing_comp = []; // comp_id => аты (шикізатқа байланбаған дайындама)

		public static function sales_sync($cmp) {
			$cmp = (int)$cmp;
			self::$sale_missing = []; self::$sale_missing_comp = [];
			$from = self::sales_from($cmp);
			if (!$from) return ['orders' => 0, 'changed' => 0, 'from' => null];

			// анықтамалықтар
			$raw_p = [];
			$q = db::query("select id, price from product_raw where company_id = '$cmp'");
			while ($q && $r = mysqli_fetch_assoc($q)) $raw_p[(int)$r['id']] = (float)$r['price'];
			$comps = [];
			$q = db::query("select id, name_ru, unit, raw_id, yield_qty from product_comp where company_id = '$cmp'");
			while ($q && $r = mysqli_fetch_assoc($q)) { $r['parts'] = []; $comps[(int)$r['id']] = $r; }
			if ($comps) {
				$q = db::query("select comp_id, raw_id, sub_comp_id, quantity from product_comp_part where comp_id in (".implode(',', array_keys($comps)).")");
				while ($q && $r = mysqli_fetch_assoc($q)) $comps[(int)$r['comp_id']]['parts'][] = $r;
			}
			$items = []; $sets = [];
			$q = db::query("select pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0");
			while ($q && $r = mysqli_fetch_assoc($q)) $items[(int)$r['product_id']][] = $r;
			$q = db::query("select si.set_id, si.product_id, si.comp_id, si.quantity from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp'");
			while ($q && $r = mysqli_fetch_assoc($q)) $sets[(int)$r['set_id']][] = $r;

			$comp_use = function ($cid, $amount, &$out, $depth = 0) use (&$comp_use, $comps) {
				$c = $comps[$cid] ?? null;
				if (!$c || $amount == 0 || $depth > 8) return;
				if ($c['parts']) {
					$y = (float)$c['yield_qty'] > 0 ? (float)$c['yield_qty'] : 1;
					foreach ($c['parts'] as $p) {
						$a = (float)$p['quantity'] * $amount / $y;
						if ($p['raw_id']) $out[(int)$p['raw_id']] = ($out[(int)$p['raw_id']] ?? 0) + $a;
						elseif ($p['sub_comp_id']) $comp_use((int)$p['sub_comp_id'], $a, $out, $depth + 1);
					}
				} elseif ($c['raw_id']) $out[(int)$c['raw_id']] = ($out[(int)$c['raw_id']] ?? 0) + $amount;
				else self::$sale_missing_comp[$cid] = $c['name_ru'];
			};
			$dish_use = function ($pid, $n, &$out, $depth = 0) use (&$dish_use, $comp_use, $items, $sets, $comps) {
				if ($depth > 5) return;
				if (isset($items[$pid])) {
					foreach ($items[$pid] as $i) {
						$c = $comps[(int)$i['comp_id']] ?? null;
						if (!$c) continue;
						$div = in_array(mb_strtolower(trim((string)$c['unit'])), ['кг', 'л']) ? 1000 : 1; // тағамда граммен
						$comp_use((int)$i['comp_id'], $n * (float)$i['quantity'] / $div, $out);
					}
				} elseif (isset($sets[$pid])) {
					foreach ($sets[$pid] as $s) {
						if ($s['product_id']) $dish_use((int)$s['product_id'], $n * (float)$s['quantity'], $out, $depth + 1);
						elseif ($s['comp_id']) $comp_use((int)$s['comp_id'], $n * (float)$s['quantity'], $out);
					}
				} else self::$sale_missing[$pid] = (self::$sale_missing[$pid] ?? 0) + $n;
			};

			// тапсырыстар: тек «Аяқталды» (4) — сатылым; басқасы (отказ т.б.) — шығын жоқ
			$def_br = self::branch($cmp, 0);
			$orders = [];
			$q = db::query("select id, branch_id, order_status, ins_dt from retail_orders where company_id = '$cmp' and ins_dt >= '$from 00:00:00' and number is not null");
			while ($q && $o = mysqli_fetch_assoc($q)) $orders[(int)$o['id']] = $o + ['want' => []];
			if ($orders) {
				$ids = implode(',', array_keys($orders));
				$q = db::query("select order_id, product_id, quantity from retail_orders_products where order_id in ($ids)");
				while ($q && $r = mysqli_fetch_assoc($q)) {
					$o = &$orders[(int)$r['order_id']];
					if ((int)$o['order_status'] == 4 && $r['product_id']) $dish_use((int)$r['product_id'], (float)$r['quantity'], $o['want']);
					unset($o);
				}
			}
			// бар жазбалар
			$have = [];
			$q = db::query("select order_id, raw_id, qty, branch_id, dt from product_raw_move where company_id = '$cmp' and type = 'sale'");
			while ($q && $m = mysqli_fetch_assoc($q)) $have[(int)$m['order_id']][(int)$m['raw_id']] = $m;

			$changed = 0;
			mysqli_begin_transaction(db::$connect);
			// бастау күнінен бұрынғы не жоқ тапсырыстардың жазбалары алынады
			foreach ($have as $oid => $rows) if (!isset($orders[$oid])) {
				foreach ($rows as $rid => $m) { self::stock_add($cmp, $m['branch_id'], $rid, -(float)$m['qty']); }
				db::query("delete from product_raw_move where company_id = '$cmp' and type = 'sale' and order_id = '$oid'");
				$changed++;
			}
			foreach ($orders as $oid => $o) {
				$br = (int)$o['branch_id'] ?: $def_br;
				$want = [];
				foreach ($o['want'] as $rid => $q2) { $v = round($q2, 3); if ($v > 0 && isset($raw_p[$rid])) $want[$rid] = $v; }
				$cur = $have[$oid] ?? [];
				// өзгермесе — өткізіп жібереміз
				$same = count($cur) == count($want);
				if ($same) foreach ($want as $rid => $v) if (!isset($cur[$rid]) || abs(-(float)$cur[$rid]['qty'] - $v) > 0.0005) { $same = false; break; }
				if ($same) continue;
				foreach ($cur as $rid => $m) self::stock_add($cmp, $m['branch_id'], $rid, -(float)$m['qty']);
				if ($cur) db::query("delete from product_raw_move where company_id = '$cmp' and type = 'sale' and order_id = '$oid'");
				foreach ($want as $rid => $v) self::move($cmp, $br, $rid, -$v, 'sale', $raw_p[$rid], 'order', $oid, $oid, '', $o['ins_dt']);
				$changed++;
			}
			mysqli_commit(db::$connect);
			return ['orders' => count($orders), 'changed' => $changed, 'from' => $from];
		}

		public static function url($path, $set = []) {
			$g = array_merge($_GET, $set);
			foreach ($g as $k => $v) if ($v === null || $v === '') unset($g[$k]);
			return $path.($g ? '?'.http_build_query($g) : '');
		}
	}
