<?
	// Лоялдық: кэшбэк және деңгейлі сыйлық (әр N-ші тапсырыс).
	// Баптау компанияда: cashback_percent (0 = кэшбэк жоқ). Сыйлық деңгейлері: company_gift_tier (әр компанияның өзі).
	// Баланс: company_client.cashback_balance. Қозғалыс: company_client_cashback. Берілген сыйлықтар: company_client_gift.
	// Бұл файл pos.berik.c және adm.berik.c config/ ішінде бірдей тұрады.

	class loyalty {

		const CLOSED_BAD = [5, 6, 10]; // отказдар: кэшбэк түспейді, жұмсалғаны қайтады
		const DONE = 4;                // Аяқталды: кэшбэк осы кезде түседі

		private static $on = null;
		private static $tiers_on = null; // company_gift_tier кестесі бар ма
		private static $steps_on = null; // company_cashback_step кестесі бар ма
		private static $paymax_on = null; // company.cashback_pay_max бағаны бар ма
		private static $cmp = [];
		public static $gift_discount = 0; // order_save соңғы жазған сыйлық жеңілдігі

		// SQL іске қосылған ба (бағандар мен кестелер бар ма)
		public static function on() {
			if (self::$on === null) {
				self::$on = mysqli_num_rows(db::query("SHOW COLUMNS FROM `company` LIKE 'gift_every'")) > 0
					&& mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_client_gift'")) > 0
					&& mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE 'cashback_sum'")) > 0;
				self::$tiers_on = self::$on && mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_gift_tier'")) > 0
					&& mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE 'gift_discount'")) > 0;
			}
			return self::$on;
		}
		public static function tiers_on() { self::on(); return self::$tiers_on; }
		public static function steps_on() {
			if (self::$steps_on === null) self::$steps_on = self::on() && mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_cashback_step'")) > 0;
			return self::$steps_on;
		}

		public static function paymax_on() {
			if (self::$paymax_on === null) self::$paymax_on = self::on() && mysqli_num_rows(db::query("SHOW COLUMNS FROM `company` LIKE 'cashback_pay_max'")) > 0;
			return self::$paymax_on;
		}

		// Компания баптауы: percent, from, gift_from, tiers (every кемуімен), cashback/gift => bool
		public static function cfg($company) {
			$company = (int)$company;
			if (isset(self::$cmp[$company])) return self::$cmp[$company];
			$r = ['percent' => 0, 'steps' => [], 'label' => '', 'from' => null, 'gift_from' => null, 'tiers' => [], 'cashback' => false, 'gift' => false, 'pay_max' => 0];
			if (self::on()) {
				$pm = self::paymax_on() ? ", cashback_pay_max" : "";
				$c = mysqli_fetch_assoc(db::query("select cashback_percent, cashback_from, gift_every, gift_name, gift_from$pm from company where id = '$company'"));
				if ($c) {
					$r['percent'] = (float)$c['cashback_percent'];
					// Кэшбэкпен төлеудің шегі: тапсырыстың жалпы сомасының ең көбі неше пайызы (0 = шек жоқ)
					$r['pay_max'] = max(0, min(100, (int)@$c['cashback_pay_max'])); if ($r['pay_max'] >= 100) $r['pay_max'] = 0;
					$r['from'] = $c['cashback_from'];
					$r['gift_from'] = $c['gift_from'];
					if (self::$tiers_on) {
						$q = db::query("select * from company_gift_tier where company_id = '$company' and active = 1 and every > 1 order by every desc, id asc");
						while ($q && $t = mysqli_fetch_assoc($q)) $r['tiers'][] = self::tier_row($t);
					} elseif ((int)$c['gift_every'] > 1 && trim((string)$c['gift_name']) !== '') {
						// ескі бір деңгейлі баптау
						$r['tiers'][] = self::tier_row(['id' => 0, 'every' => $c['gift_every'], 'name' => $c['gift_name']]);
					}
					$r['cashback'] = $r['percent'] > 0;
					// Сатылы кэшбэк: тағам сомасы from_sum-ға жетсе (>=) percent (мысалы 0→1%, 7990→3%, 10000→5%, 15000→7%)
					if ($r['cashback'] && self::steps_on()) {
						$q = db::query("select from_sum, percent from company_cashback_step where company_id = '$company' order by from_sum asc");
						while ($q && $st = mysqli_fetch_assoc($q)) $r['steps'][] = ['from' => (int)$st['from_sum'], 'percent' => (float)$st['percent']];
					}
					$f = function ($v) { return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'); };
					if ($r['steps']) { $ps = array_column($r['steps'], 'percent'); $r['label'] = $f(min($ps)).(max($ps) != min($ps) ? '–'.$f(max($ps)) : '').'%'; }
					elseif ($r['cashback']) $r['label'] = $f($r['percent']).'%';
					$r['gift'] = count($r['tiers']) > 0;
					// Басталған уақыты жоқ болса — қазір: ескі тапсырыстарға кэшбэк/санау кетпейді
					if ($r['cashback'] && !$r['from']) { $r['from'] = date('Y-m-d H:i:s'); db::query("update company set cashback_from = '{$r['from']}' where id = '$company' and cashback_from is null"); }
					if ($r['gift'] && !$r['gift_from']) { $r['gift_from'] = date('Y-m-d H:i:s'); db::query("update company set gift_from = '{$r['gift_from']}' where id = '$company' and gift_from is null"); }
				}
			}
			return self::$cmp[$company] = $r;
		}
		private static function tier_row($t) {
			return ['id' => (int)$t['id'], 'every' => (int)$t['every'], 'name' => trim((string)$t['name']),
				'product_id' => (int)@$t['product_id'], 'quantity' => max(1, (int)@$t['quantity']),
				'discount_percent' => (int)@$t['discount_percent'], 'discount_max' => (int)@$t['discount_max']];
		}
		public static function reset_cache() { self::$cmp = []; }

		public static function any($company) { $c = self::cfg($company); return $c['cashback'] || $c['gift']; }

		// k-шы тапсырысқа қай деңгей түседі (бірнешеуі болса every ең үлкені)
		public static function tier_for($company, $k) {
			foreach (self::cfg($company)['tiers'] as $t) if ($k > 0 && $k % $t['every'] == 0) return $t;
			return null;
		}
		// count аяқталған тапсырыстан кейінгі ең жақын сыйлық: ['k' => нешінші, 'left' => қанша қалды, 'tier' => ...]
		public static function next_gift($company, $count) {
			$cfg = self::cfg($company);
			if (!$cfg['gift']) return null;
			$max = 0; foreach ($cfg['tiers'] as $t) $max = max($max, $t['every']);
			for ($k = $count + 1; $k <= $count + $max; $k++) if ($t = self::tier_for($company, $k)) return ['k' => $k, 'left' => $k - $count - 1, 'tier' => $t];
			return null;
		}
		// Деңгейдің жеңілдік сомасы: тағам сомасының discount_max-қа дейінгі бөлігінен %
		public static function tier_discount($tier, $food) {
			if (!$tier || $tier['discount_percent'] <= 0) return 0;
			$base = $tier['discount_max'] > 0 ? min($food, $tier['discount_max']) : $food;
			return (int)floor(max(0, $base) * $tier['discount_percent'] / 100);
		}

		// Сома бойынша кэшбэк пайызы: сатылар болса — сома асқан ең жоғарғы саты, болмаса компанияның бір пайызы
		public static function pct($company, $base) {
			$cfg = self::cfg($company);
			if (!$cfg['cashback']) return 0;
			if (!$cfg['steps']) return $cfg['percent'];
			$p = 0;
			foreach ($cfg['steps'] as $st) if ($base >= $st['from']) $p = $st['percent'];
			return $p;
		}
		// Саты тапсырыстың тағам сомасымен ($sum) анықталады, пайыз төленген бөлігінен ($base) есептеледі
		public static function amount($company, $base, $sum = null) { return (int)floor(max(0, $base) * self::pct($company, $sum === null ? $base : $sum) / 100); }

		// Кэшбэкпен ең көбі қанша төлеуге болады: $grand — төленетін жалпы сома (тағам − сыйлық жеңілдігі + доставка)
		public static function pay_cap($company, $grand) {
			$grand = max(0, (int)$grand);
			$pm = self::cfg($company)['pay_max'];
			return $pm > 0 ? (int)floor($grand * $pm / 100) : $grand;
		}

		// Осы тапсырыс аяқталғанда түсетін кэшбэк (чекке)
		public static function earn_preview($company, $order_id, $pay_cashback = 0, $gift_discount = 0) {
			$cfg = self::cfg($company);
			if (!$cfg['cashback']) return 0;
			$food = self::food_sum($order_id);
			return self::amount($company, $food - (int)$pay_cashback - (int)$gift_discount, $food);
		}

		// Сыйлық тағамын тапсырысқа 0 тг болып қосу / алып тастау
		private static function gift_item($order_id, $product_id, $qty, $on) {
			$order_id = (int)$order_id; $product_id = (int)$product_id; $qty = max(1, (int)$qty);
			if (!$product_id) return;
			$has = mysqli_fetch_assoc(db::query("select id from retail_orders_products where order_id = '$order_id' and product_id = '$product_id' and price = 0 limit 1"));
			if ($on && !$has) db::query("insert into retail_orders_products (order_id, product_id, quantity, price) values ('$order_id', '$product_id', '$qty', 0)");
			if (!$on && $has) db::query("delete from retail_orders_products where id = '".(int)$has['id']."'");
		}

		// Тапсырыстың тағам сомасы (доставка, RED кірмейді)
		public static function food_sum($order_id) {
			$r = mysqli_fetch_assoc(db::query("select coalesce(sum(quantity * price), 0) s from retail_orders_products where order_id = '".(int)$order_id."'"));
			return (int)@$r['s'];
		}

		// Балансқа қозғалыс жазу (бір транзакцияда): + түсті, - кетті
		public static function move($company, $client_id, $order_id, $type, $amount, $percent = 0, $base = 0, $note = null, $staff_id = null) {
			$amount = (int)$amount; $client_id = (int)$client_id;
			if (!$amount || !$client_id) return false;
			db::query("START TRANSACTION");
			$c = mysqli_fetch_assoc(db::query("select cashback_balance from company_client where id = '$client_id' for update"));
			if (!$c) { db::query("ROLLBACK"); return false; }
			$bal = (int)$c['cashback_balance'] + $amount;
			$e = function ($v) { return $v === null || $v === '' ? 'NULL' : "'".mysqli_real_escape_string(db::$connect, (string)$v)."'"; };
			$ok = db::query("update company_client set cashback_balance = '$bal' where id = '$client_id'")
				&& db::query("insert into company_client_cashback (company_id, client_id, order_id, type, percent, base_sum, amount, balance_after, note, staff_id)
					values ('".(int)$company."', '$client_id', ".($order_id ? "'".(int)$order_id."'" : 'NULL').", ".$e($type).", '".(float)$percent."', '".(int)$base."', '$amount', '$bal', ".$e($note).", ".($staff_id ? "'".(int)$staff_id."'" : 'NULL').")");
			db::query($ok ? "COMMIT" : "ROLLBACK");
			return $ok ? $bal : false;
		}

		// Осы тапсырыс бойынша қазір жұмсалып тұрған кэшбэк (spend + refund қосындысы, оң сан)
		public static function spent($order_id) {
			$r = mysqli_fetch_assoc(db::query("select coalesce(-sum(amount), 0) s from company_client_cashback where order_id = '".(int)$order_id."' and type in ('spend', 'refund')"));
			return (int)@$r['s'];
		}

		// Бір тапсырысты реттеу: жұмсалғанын pay_cashback-ке теңестіру, аяқталса кэшбэк түсіру, болмаса қайтару,
		// сыйлық тапсырысы отказ болса сыйлық жазбасын өшіру
		public static function sync_order($o) {
			$gd = self::tiers_on() ? ", gift_discount" : "";
			if (!is_array($o)) $o = mysqli_fetch_assoc(db::query("select id, company_id, client_id, order_status, paid, pay_cashback, cashback_sum, ins_dt$gd from retail_orders where id = '".(int)$o."'"));
			if (!$o || !$o['client_id'] || !$o['company_id']) return;
			$id = (int)$o['id']; $cmp = (int)$o['company_id']; $cl = (int)$o['client_id']; $st = (int)$o['order_status'];
			$cfg = self::cfg($cmp);
			$bad = in_array($st, self::CLOSED_BAD);

			// 1. Жұмсалған кэшбэк
			$want = ($bad || !$o['paid']) ? 0 : (int)$o['pay_cashback'];
			$have = self::spent($id);
			if ($want != $have) self::move($cmp, $cl, $id, $want > $have ? 'spend' : 'refund', $have - $want, 0, 0, $bad ? 'отказ' : null);

			// 2. Түскен кэшбэк (кэшбэкпен төленгені мен сыйлық жеңілдігі кірмейді)
			$got = (int)$o['cashback_sum'];
			if ($st == self::DONE && !$got && $cfg['cashback'] && $o['ins_dt'] >= $cfg['from']) {
				$food = self::food_sum($id);
				$base = max(0, $food - (int)$o['pay_cashback'] - (int)@$o['gift_discount']);
				$amt = self::amount($cmp, $base, $food);
				if ($amt > 0 && self::move($cmp, $cl, $id, 'earn', $amt, self::pct($cmp, $food), $base) !== false)
					db::query("update retail_orders set cashback_sum = '$amt' where id = '$id'");
			} elseif ($st != self::DONE && $got) {
				if (self::move($cmp, $cl, $id, 'cancel', -$got, 0, 0, 'статус өзгерді') !== false)
					db::query("update retail_orders set cashback_sum = 0 where id = '$id'");
			}

			// 3. Сыйлық тапсырысы отказ болса — сыйлық жазбасы өшеді (тапсырыс есептелмейді)
			if ($bad) db::query("delete from company_client_gift where order_id = '$id'");
		}

		// Компанияның өзгерген тапсырыстарын реттеу. Касса мен админ шақырады; 15 секундта бір рет.
		public static function sync($company, $force = false) {
			$company = (int)$company;
			if (!self::on() || !$company) return;
			$k = 'loyalty_sync_'.$company;
			if (!$force && session_status() == PHP_SESSION_ACTIVE && isset($_SESSION[$k]) && $_SESSION[$k] > time() - 15) return;
			if (session_status() == PHP_SESSION_ACTIVE) $_SESSION[$k] = time();
			$cfg = self::cfg($company);
			$bad = implode(',', self::CLOSED_BAD);
			$gd = self::tiers_on() ? ", o.gift_discount" : "";
			$w = [];
			// аяқталды, бірақ кэшбэгі әлі түспеген
			if ($cfg['cashback']) $w[] = "(o.order_status = ".self::DONE." and o.cashback_sum = 0 and o.ins_dt >= '".mysqli_real_escape_string(db::$connect, $cfg['from'])."')";
			// кэшбэк түскен, бірақ енді аяқталмаған
			$w[] = "(o.order_status <> ".self::DONE." and o.cashback_sum > 0)";
			// кэшбэкпен төленген (жұмсалғаны сәйкес пе — төменде тексеріледі)
			$w[] = "(o.pay_cashback > 0 and o.ins_dt > NOW() - INTERVAL 30 DAY)";
			// сыйлық берілген тапсырыс отказ болды
			$w[] = "(o.order_status in ($bad) and exists (select 1 from company_client_gift g where g.order_id = o.id))";
			$q = db::query("select o.id, o.company_id, o.client_id, o.order_status, o.paid, o.pay_cashback, o.cashback_sum, o.ins_dt$gd from retail_orders o
				where o.company_id = '$company' and o.client_id is not null and (".implode(' or ', $w).") limit 500");
			while ($q && $o = mysqli_fetch_assoc($q)) {
				if ($o['pay_cashback'] > 0 && (int)$o['order_status'] != self::DONE && !in_array((int)$o['order_status'], self::CLOSED_BAD) && !$o['cashback_sum']) {
					// тек жұмсалғаны ғана тексерілетін тапсырыс: сәйкес болса өткізіп жібереміз
					if (self::spent($o['id']) == (int)$o['pay_cashback']) continue;
				}
				self::sync_order($o);
			}
			// pay_cashback 0-ге түскен, бірақ жұмсалғаны тұрған тапсырыстар
			$q = db::query("select k.order_id from company_client_cashback k join retail_orders o on o.id = k.order_id
				where k.company_id = '$company' and k.type in ('spend', 'refund') and o.pay_cashback = 0 and k.ins_dt > NOW() - INTERVAL 30 DAY
				group by k.order_id having sum(k.amount) <> 0");
			while ($q && $r = mysqli_fetch_assoc($q)) self::sync_order((int)$r['order_id']);
		}

		// Сыйлыққа санауыш: gift_from-нан кейінгі төленген, отказ болмаған тапсырыстар (осы тапсырыстан басқа).
		// Жолдағы тапсырыс та саналады: әйтпесе бір сыйлық қатар екі тапсырысқа шығып кетеді. Отказ болса санаудан шығады.
		public static function gift_count($company, $client_id, $except_order = 0) {
			$cfg = self::cfg($company); $client_id = (int)$client_id;
			$w = "client_id = '$client_id' and company_id = '".(int)$company."' and paid = 1 and order_status not in (".implode(',', self::CLOSED_BAD).")";
			if ($cfg['gift_from']) $w .= " and ins_dt >= '".mysqli_real_escape_string(db::$connect, $cfg['gift_from'])."'";
			if ($except_order) $w .= " and id <> '".(int)$except_order."'";
			$r = mysqli_fetch_assoc(db::query("select count(*) c from retail_orders where $w"));
			return (int)@$r['c'];
		}

		// Касса үшін клиент ақпараты: баланс, сыйлық күйі. $order_id — қазір ашық тапсырыс.
		public static function client_info($company, $client, $order_id = 0) {
			$cfg = self::cfg($company);
			$res = ['cashback' => $cfg['cashback'], 'percent' => $cfg['percent'], 'label' => $cfg['label'], 'steps' => $cfg['steps'], 'balance' => 0, 'available' => 0, 'used' => 0, 'pay_max' => $cfg['pay_max'],
				'gift' => $cfg['gift'], 'gift_cnt' => 0, 'gift_k' => 0, 'gift_due' => false, 'gift_given' => false,
				'gift_name' => '', 'gift_product' => false, 'gift_discount' => 0, 'next' => null, 'tiers' => []];
			foreach ($cfg['tiers'] as $t) $res['tiers'][] = ['every' => $t['every'], 'name' => $t['name']];
			if (!$client) return $res;
			$o = $order_id ? mysqli_fetch_assoc(db::query("select pay_cashback from retail_orders where id = '".(int)$order_id."'")) : null;
			$used = $order_id ? self::spent($order_id) : 0; // осы тапсырысқа бұрын жұмсалғаны
			$res['balance'] = (int)$client['cashback_balance'];
			$res['used'] = (int)@$o['pay_cashback'];
			$res['available'] = max(0, $res['balance'] + $used);
			if ($cfg['gift']) {
				$g = $order_id ? mysqli_fetch_assoc(db::query("select * from company_client_gift where order_id = '".(int)$order_id."' limit 1")) : null;
				$res['gift_given'] = (bool)$g;
				$res['gift_cnt'] = self::gift_count($company, $client['id'], $order_id);
				$res['gift_k'] = $res['gift_cnt'] + 1; // осы тапсырыс нешінші
				$t = self::tier_for($company, $res['gift_k']);
				if ($t) {
					$res['gift_due'] = true;
					$res['gift_name'] = $g ? $g['gift_name'] : $t['name'];
					$res['gift_product'] = $t['product_id'] > 0;
					$res['gift_discount'] = self::tier_discount($t, self::food_sum_paid($order_id));
				}
				$res['next'] = self::next_gift($company, $res['gift_k']); // осыдан кейінгі сыйлық
			}
			return $res;
		}
		// Ақылы тағамдар сомасы (0 тг сыйлық тағамдарынсыз)
		public static function food_sum_paid($order_id) { return $order_id ? self::food_sum($order_id) : 0; }

		// Кассада тапсырыс сақталғанда: кэшбэкпен төлеу сомасы және сыйлық белгісі.
		// Қайтарады: нақты жазылған кэшбэк сомасы. Сыйлық жеңілдігі: self::$gift_discount.
		public static function order_save($company, $order_id, $want_cashback, $want_gift, $staff_id = null) {
			self::$gift_discount = 0;
			if (!self::on()) return 0;
			$order_id = (int)$order_id;
			$o = mysqli_fetch_assoc(db::query("select id, company_id, client_id, order_status, paid, pay_cashback, cashback_sum, ins_dt, pay_delivery from retail_orders where id = '$order_id'"));
			if (!$o) return 0;
			$cfg = self::cfg($company);
			$cl = $o['client_id'] ? mysqli_fetch_assoc(db::query("select * from company_client where id = '".(int)$o['client_id']."'")) : null;
			$info = self::client_info($company, $cl, $order_id);
			$g = mysqli_fetch_assoc(db::query("select * from company_client_gift where order_id = '$order_id' limit 1"));

			// Сыйлық: деңгей бойынша тағам 0 тг болып қосылады не жеңілдік жазылады
			$tier = $info['gift_due'] ? self::tier_for($company, $info['gift_k']) : null;
			if ($cl && $tier && $want_gift) {
				$disc = self::tier_discount($tier, self::food_sum($order_id));
				if (!$g) {
					$gn = mysqli_real_escape_string(db::$connect, $tier['name']);
					$cols = "company_id, client_id, order_id, gift_every, gift_name, staff_id";
					$vals = "'".(int)$company."', '".(int)$cl['id']."', '$order_id', '".$tier['every']."', '$gn', ".($staff_id ? "'".(int)$staff_id."'" : 'NULL');
					if (self::tiers_on()) { $cols .= ", tier_id, order_no, product_id, quantity, discount"; $vals .= ", ".($tier['id'] ?: 'NULL').", '".$info['gift_k']."', ".($tier['product_id'] ?: 'NULL').", '".($tier['product_id'] ? $tier['quantity'] : 0)."', '$disc'"; }
					db::query("insert into company_client_gift ($cols) values ($vals)");
					db::query("update retail_orders set gift_name = '$gn' where id = '$order_id'");
					self::gift_item($order_id, $tier['product_id'], $tier['quantity'], true);
				} elseif (self::tiers_on()) db::query("update company_client_gift set discount = '$disc' where id = '".(int)$g['id']."'");
				self::$gift_discount = $disc;
			} elseif ($g && !$want_gift) {
				db::query("delete from company_client_gift where id = '".(int)$g['id']."'");
				db::query("update retail_orders set gift_name = NULL where id = '$order_id'");
				self::gift_item($order_id, (int)@$g['product_id'], 1, false);
			}
			if (self::tiers_on()) db::query("update retail_orders set gift_discount = '".self::$gift_discount."' where id = '$order_id'");

			// Кэшбэк: баланстан, төленетін сомадан және компания шегінен (жалпы соманың pay_max %) аспайды; клиент жоқ не кэшбэк өшірулі болса 0
			$amt = 0;
			if ($cl && $cfg['cashback'] && $want_cashback > 0) $amt = min((int)$want_cashback, $info['available'], self::pay_cap($company, max(0, self::food_sum($order_id) - self::$gift_discount) + (int)$o['pay_delivery']));
			if ($amt != (int)$o['pay_cashback']) db::query("update retail_orders set pay_cashback = '$amt' where id = '$order_id'");

			self::sync_order($order_id);
			return $amt;
		}

		// Админ: кэшбэкті қолмен қосу (+) не алу (-). Баланс 0-ден төмен түспейді.
		public static function manual($company, $client_id, $amount, $note, $staff_id) {
			$amount = (int)$amount;
			$c = mysqli_fetch_assoc(db::query("select id, cashback_balance from company_client where id = '".(int)$client_id."' and company_id = '".(int)$company."'"));
			if (!$c || !$amount || abs($amount) > 1000000) return false;
			if ((int)$c['cashback_balance'] + $amount < 0) return false;
			return self::move($company, $c['id'], null, 'manual', $amount, 0, 0, mb_substr(trim((string)$note), 0, 255), $staff_id);
		}

		public static function tg($v) { return number_format((int)$v, 0, '', ' ').' тг'; }
	}
