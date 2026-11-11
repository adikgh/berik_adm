<?
	// Акциялар (2026-11-11): админ компанияға % жеңілдік акциясын жасайды (company_promo),
	// касса тапсырысқа таңдайды. Жеңілдік тек тағам сомасынан (жеткізу мен RED-сіз).
	// Тапсырыста: retail_orders.promo_id, promo_name, promo_percent, promo_discount.
	// Бұл файл pos.berik.c, adm.berik.c, dlv.berik.c config/ ішінде БІРДЕЙ.
	class promo {
		private static $ok = null;

		// Кесте мен бағандар бар ма (жоқ болса функция көрінбейді)
		public static function on() {
			if (self::$ok === null) {
				self::$ok = @mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_promo'")) > 0
					&& @mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE 'promo_discount'")) > 0;
			}
			return self::$ok;
		}

		// Кесте мен бағандарды жасау (бар деректерге тимейді). Админ «Акциялар» беті ашылғанда шақырылады.
		public static function install() {
			db::query("CREATE TABLE IF NOT EXISTS `company_promo` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`company_id` int(11) NOT NULL,
				`name` varchar(100) NOT NULL,
				`percent` decimal(5,2) NOT NULL DEFAULT 0,
				`date_from` date DEFAULT NULL,
				`date_to` date DEFAULT NULL,
				`active` tinyint(1) NOT NULL DEFAULT 1,
				`arh` tinyint(1) NOT NULL DEFAULT 0,
				`ins_dt` datetime DEFAULT NULL,
				`upd_dt` datetime DEFAULT NULL,
				PRIMARY KEY (`id`),
				KEY `company_id` (`company_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
			$cols = [
				'promo_id' => "int(11) DEFAULT NULL",
				'promo_name' => "varchar(100) DEFAULT NULL",
				'promo_percent' => "decimal(5,2) NOT NULL DEFAULT 0",
				'promo_discount' => "int(11) NOT NULL DEFAULT 0",
			];
			foreach ($cols as $c => $def) {
				if (!mysqli_num_rows(db::query("SHOW COLUMNS FROM `retail_orders` LIKE '$c'"))) db::query("ALTER TABLE `retail_orders` ADD `$c` $def");
			}
			self::$ok = null;
			return self::on();
		}

		// 10.00 → 10, 7.50 → 7.5
		public static function pct($v) { return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.'); }

		// «Атауы −10%»
		public static function label($name, $pct) { return trim((string)$name).' −'.self::pct($pct).'%'; }

		public static function calc($food, $pct) { return max(0, (int)round((int)$food * (float)$pct / 100)); }

		// Бүгін кассада таңдауға болатын акциялар
		public static function active($company) {
			$res = [];
			if (!self::on()) return $res;
			$d = date('Y-m-d');
			$q = db::query("select * from company_promo where company_id = '".(int)$company."' and active = 1 and arh = 0 and percent > 0
				and (date_from is null or date_from <= '$d') and (date_to is null or date_to >= '$d') order by percent asc, id asc");
			while ($q && $r = mysqli_fetch_assoc($q)) $res[] = $r;
			return $res;
		}

		public static function get($company, $id) {
			if (!self::on() || !(int)$id) return null;
			return mysqli_fetch_assoc(db::query("select * from company_promo where id = '".(int)$id."' and company_id = '".(int)$company."' limit 1")) ?: null;
		}

		// Тапсырыстың тағам сомасы (жеткізусіз)
		public static function food_sum($order_id) {
			$r = mysqli_fetch_row(db::query("select coalesce(sum(price * quantity), 0) from retail_orders_products where order_id = '".(int)$order_id."'"));
			return (int)@$r[0];
		}

		// Кассада сақтағанда: акцияны тапсырысқа жазады, нақты жеңілдік сомасын қайтарады.
		// Өзгертуде бұрын қойылған акция өшірілген болса да сақталады (сол пайызбен).
		public static function order_save($company, $order_id, $promo_id) {
			if (!self::on()) return 0;
			$oid = (int)$order_id; $pid = (int)$promo_id;
			$p = null;
			if ($pid) {
				$old = mysqli_fetch_assoc(db::query("select promo_id, promo_name, promo_percent from retail_orders where id = '$oid'"));
				foreach (self::active($company) as $a) if ((int)$a['id'] == $pid) $p = $a;
				if (!$p && $old && (int)$old['promo_id'] == $pid) $p = ['id' => $pid, 'name' => $old['promo_name'], 'percent' => $old['promo_percent']];
			}
			if (!$p) {
				db::query("update retail_orders set promo_id = NULL, promo_name = NULL, promo_percent = 0, promo_discount = 0 where id = '$oid'");
				return 0;
			}
			$disc = self::calc(self::food_sum($oid), $p['percent']);
			$e = mysqli_real_escape_string(db::$connect, mb_substr((string)$p['name'], 0, 100));
			db::query("update retail_orders set promo_id = '".(int)$p['id']."', promo_name = '$e', promo_percent = '".(float)$p['percent']."', promo_discount = '$disc' where id = '$oid'");
			return $disc;
		}

		// Тапсырыс жолынан көрсетуге: null немесе ['name', 'pct', 'sum', 'label']
		public static function of($o) {
			if (!is_array($o) || empty($o['promo_discount']) && empty($o['promo_id'])) return null;
			if ((int)@$o['promo_discount'] <= 0) return null;
			return ['name' => (string)$o['promo_name'], 'pct' => self::pct($o['promo_percent']), 'sum' => (int)$o['promo_discount'],
				'label' => self::label($o['promo_name'], $o['promo_percent'])];
		}
	}
