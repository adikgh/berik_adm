<?
	// Лоялдық: кэшбэк және N-ші тапсырысқа сыйлық.
	// Баптау компанияда: cashback_percent (0 = кэшбэк жоқ), gift_every (0 = сыйлық жоқ), gift_name.
	// Баланс: company_client.cashback_balance. Қозғалыс: company_client_cashback. Сыйлықтар: company_client_gift.
	// Бұл файл pos.berik.c және admin.pf.c config/ ішінде бірдей тұрады.

	class loyalty {

		const CLOSED_BAD = [5, 6, 10]; // отказдар: кэшбэк түспейді, жұмсалғаны қайтады
		const DONE = 4;                // Аяқталды: кэшбэк осы кезде түседі

		private static $on = null;
		private static $cmp = [];

		// SQL іске қосылған ба (бағандар мен кестелер бар ма)
		public static function on() {
			if (self::$on === null) {
				self::$on = mysqli_num_rows(db::query("SHOW COLUMNS FROM `company` LIKE 'gift_every'")) > 0
					&& mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_client_gift'")) > 0
					&& mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE 'cashback_sum'")) > 0;
			}
			return self::$on;
		}

		// Компания баптауы: ['percent', 'from', 'gift_every', 'gift_name', 'gift_from', 'cashback' => bool, 'gift' => bool]
		public static function cfg($company) {
			$company = (int)$company;
			if (isset(self::$cmp[$company])) return self::$cmp[$company];
			$r = ['percent' => 0, 'from' => null, 'gift_every' => 0, 'gift_name' => '', 'gift_from' => null, 'cashback' => false, 'gift' => false];
			if (self::on()) {
				$c = mysqli_fetch_assoc(db::query("select cashback_percent, cashback_from, gift_every, gift_name, gift_from from company where id = '$company'"));
				if ($c) {
					$r['percent'] = (float)$c['cashback_percent'];
					$r['from'] = $c['cashback_from'];
					$r['gift_every'] = (int)$c['gift_every'];
					$r['gift_name'] = trim((string)$c['gift_name']);
					$r['gift_from'] = $c['gift_from'];
					$r['cashback'] = $r['percent'] > 0;
					$r['gift'] = $r['gift_every'] > 1 && $r['gift_name'] !== '';
					// Басталған уақыты жоқ болса — қазір: ескі тапсырыстарға кэшбэк/санау кетпейді
					if ($r['cashback'] && !$r['from']) { $r['from'] = date('Y-m-d H:i:s'); db::query("update company set cashback_from = '{$r['from']}' where id = '$company' and cashback_from is null"); }
					if ($r['gift'] && !$r['gift_from']) { $r['gift_from'] = date('Y-m-d H:i:s'); db::query("update company set gift_from = '{$r['gift_from']}' where id = '$company' and gift_from is null"); }
				}
			}
			return self::$cmp[$company] = $r;
		}

		public static function any($company) { $c = self::cfg($company); return $c['cashback'] || $c['gift']; }

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
		// сыйлық тапсырысы отказ болса сыйлықты өшіру
		public static function sync_order($o) {
			if (!is_array($o)) $o = mysqli_fetch_assoc(db::query("select id, company_id, client_id, order_status, paid, pay_cashback, cashback_sum, ins_dt from retail_orders where id = '".(int)$o."'"));
			if (!$o || !$o['client_id'] || !$o['company_id']) return;
			$id = (int)$o['id']; $cmp = (int)$o['company_id']; $cl = (int)$o['client_id']; $st = (int)$o['order_status'];
			$cfg = self::cfg($cmp);
			$bad = in_array($st, self::CLOSED_BAD);

			// 1. Жұмсалған кэшбэк
			$want = ($bad || !$o['paid']) ? 0 : (int)$o['pay_cashback'];
			$have = self::spent($id);
			if ($want != $have) self::move($cmp, $cl, $id, $want > $have ? 'spend' : 'refund', $have - $want, 0, 0, $bad ? 'отказ' : null);

			// 2. Түскен кэшбэк
			$got = (int)$o['cashback_sum'];
			if ($st == self::DONE && !$got && $cfg['cashback'] && $o['ins_dt'] >= $cfg['from']) {
				$base = max(0, self::food_sum($id) - (int)$o['pay_cashback']);
				$amt = (int)floor($base * $cfg['percent'] / 100);
				if ($amt > 0 && self::move($cmp, $cl, $id, 'earn', $amt, $cfg['percent'], $base) !== false)
					db::query("update retail_orders set cashback_sum = '$amt' where id = '$id'");
			} elseif ($st != self::DONE && $got) {
				if (self::move($cmp, $cl, $id, 'cancel', -$got, 0, 0, 'статус өзгерді') !== false)
					db::query("update retail_orders set cashback_sum = 0 where id = '$id'");
			}

			// 3. Сыйлық тапсырысы отказ болса — сыйлық қайта күтіп тұрады
			if ($bad) {
				db::query("delete from company_client_gift where order_id = '$id'");
				db::query("update retail_orders set gift_name = NULL where id = '$id' and gift_name is not null");
			}
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
			$w = [];
			// аяқталды, бірақ кэшбэгі әлі түспеген
			if ($cfg['cashback']) $w[] = "(o.order_status = ".self::DONE." and o.cashback_sum = 0 and o.ins_dt >= '".mysqli_real_escape_string(db::$connect, $cfg['from'])."')";
			// кэшбэк түскен, бірақ енді аяқталмаған
			$w[] = "(o.order_status <> ".self::DONE." and o.cashback_sum > 0)";
			// кэшбэкпен төленген (жұмсалғаны сәйкес пе — төменде тексеріледі)
			$w[] = "(o.pay_cashback > 0 and o.ins_dt > NOW() - INTERVAL 30 DAY)";
			// сыйлық берілген тапсырыс отказ болды
			$w[] = "(o.gift_name is not null and o.order_status in ($bad))";
			$q = db::query("select o.id, o.company_id, o.client_id, o.order_status, o.paid, o.pay_cashback, o.cashback_sum, o.ins_dt from retail_orders o
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

		// Сыйлыққа санауыш: соңғы сыйлықтан (не gift_from-нан) кейінгі аяқталған тапсырыстар
		public static function gift_count($company, $client_id, $except_order = 0) {
			$cfg = self::cfg($company); $client_id = (int)$client_id;
			$last = mysqli_fetch_assoc(db::query("select max(order_id) m from company_client_gift where client_id = '$client_id'".($except_order ? " and order_id <> '".(int)$except_order."'" : "")));
			$w = "client_id = '$client_id' and company_id = '".(int)$company."' and order_status = ".self::DONE;
			if ($cfg['gift_from']) $w .= " and ins_dt >= '".mysqli_real_escape_string(db::$connect, $cfg['gift_from'])."'";
			if (@$last['m']) $w .= " and id > '".(int)$last['m']."'";
			if ($except_order) $w .= " and id <> '".(int)$except_order."'";
			$r = mysqli_fetch_assoc(db::query("select count(*) c from retail_orders where $w"));
			return (int)@$r['c'];
		}

		// Касса үшін клиент ақпараты: баланс, сыйлық күйі. $order_id — қазір ашық тапсырыс (өзгертуде).
		public static function client_info($company, $client, $order_id = 0) {
			$cfg = self::cfg($company);
			$res = ['cashback' => $cfg['cashback'], 'percent' => $cfg['percent'], 'balance' => 0, 'available' => 0, 'used' => 0,
				'gift' => $cfg['gift'], 'gift_every' => $cfg['gift_every'], 'gift_name' => $cfg['gift_name'], 'gift_cnt' => 0, 'gift_due' => false, 'gift_given' => false];
			if (!$client) return $res;
			$o = $order_id ? mysqli_fetch_assoc(db::query("select pay_cashback, gift_name from retail_orders where id = '".(int)$order_id."'")) : null;
			$used = $order_id ? self::spent($order_id) : 0; // осы тапсырысқа бұрын жұмсалғаны
			$res['balance'] = (int)$client['cashback_balance'];
			$res['used'] = (int)@$o['pay_cashback'];
			$res['available'] = max(0, $res['balance'] + $used);
			if ($cfg['gift']) {
				$res['gift_given'] = $o && $o['gift_name'] !== null && $o['gift_name'] !== '';
				$res['gift_cnt'] = self::gift_count($company, $client['id'], $res['gift_given'] ? $order_id : 0);
				$res['gift_due'] = $res['gift_given'] || $res['gift_cnt'] >= $cfg['gift_every'] - 1;
				if ($res['gift_given']) $res['gift_name'] = $o['gift_name'];
			}
			return $res;
		}

		// Кассада тапсырыс сақталғанда: кэшбэкпен төлеу сомасы және сыйлық белгісі.
		// Қайтарады: нақты жазылған кэшбэк сомасы (сұралғаннан аз болуы мүмкін).
		public static function order_save($company, $order_id, $want_cashback, $want_gift, $staff_id = null) {
			if (!self::on()) return 0;
			$order_id = (int)$order_id;
			$o = mysqli_fetch_assoc(db::query("select id, company_id, client_id, order_status, paid, pay_cashback, cashback_sum, gift_name, ins_dt, pay_delivery from retail_orders where id = '$order_id'"));
			if (!$o) return 0;
			$cfg = self::cfg($company);
			$cl = $o['client_id'] ? mysqli_fetch_assoc(db::query("select * from company_client where id = '".(int)$o['client_id']."'")) : null;
			$info = self::client_info($company, $cl, $order_id);

			// Кэшбэк: баланстан және тапсырыс сомасынан аспайды; клиент жоқ не кэшбэк өшірулі болса 0
			$amt = 0;
			if ($cl && $cfg['cashback'] && $want_cashback > 0) $amt = min((int)$want_cashback, $info['available'], self::food_sum($order_id) + (int)$o['pay_delivery']);
			if ($amt != (int)$o['pay_cashback']) db::query("update retail_orders set pay_cashback = '$amt' where id = '$order_id'");

			// Сыйлық
			if ($cl && $cfg['gift'] && $want_gift && $info['gift_due'] && !$info['gift_given']) {
				$gn = mysqli_real_escape_string(db::$connect, $cfg['gift_name']);
				db::query("insert into company_client_gift (company_id, client_id, order_id, gift_every, gift_name, staff_id)
					values ('".(int)$company."', '".(int)$cl['id']."', '$order_id', '".(int)$cfg['gift_every']."', '$gn', ".($staff_id ? "'".(int)$staff_id."'" : 'NULL').")");
				db::query("update retail_orders set gift_name = '$gn' where id = '$order_id'");
			} elseif (!$want_gift && $info['gift_given']) {
				db::query("delete from company_client_gift where order_id = '$order_id'");
				db::query("update retail_orders set gift_name = NULL where id = '$order_id'");
			}

			self::sync_order($order_id);
			return $amt;
		}

		public static function tg($v) { return number_format((int)$v, 0, '', ' ').' тг'; }
	}
