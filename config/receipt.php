<?
	// Чек баптауы: company_receipt кестесі (әр компанияға бір жол).
	// Админдегі «Чек» бетінен (/receipt/) өзгереді, кассадағы orders/order_print.php оқиды.
	// Бұл файл adm.berik.c/config мен pos.berik.c/config ішінде БІРДЕЙ болуы керек.
	// Кесте жоқ болса (мысалы, офлайн касса), чек бұрынғыдай шығады.

	class receipt {

		// Әдепкі мәндер = бұрынғы чек (ештеңе өзгермейді)
		public static function defaults() {
			return [
				'width' => 0,            // 0 = филиал баптауы (company_branch.print_width), 80, 58
				'font' => 'm',           // тағамдар жазуы: s / m / l
				'logo_on' => 0, 'logo_w' => 60,
				'title_on' => 1, 'rtitle' => '', 'title_size' => 'm', // бос болса филиалдың print_title
				'head_text' => '',       // тақырып астындағы жолдар (мекенжай, телефон, instagram)
				'show_status' => 1,      // РАСПЕЧАТАНО / ИЗМЕНЕНО
				'show_type' => 1,        // Собой / Кафе
				'show_number' => 1, 'show_date' => 1, 'show_additional' => 1,
				'item_lang' => 'ru', 'item_num' => 1,
				'delivery' => 'always', 'prepay' => 'always', 'show_topay' => 1, // always / nonzero / never
				'show_client' => 1, 'show_phone' => 1, 'show_address' => 1, 'show_cashback' => 1,
				'footer' => '',
				'l_name' => 'Наименование', 'l_qty' => 'Кол.', 'l_sum' => 'Сумма', 'l_delivery' => 'Доставка',
				'l_total' => 'ОБЩИЙ', 'l_prepay' => 'Предоплата', 'l_topay' => 'К ОПЛАТЕ',
				'l_client' => 'Клиент', 'l_ph' => 'Номер', 'l_adr' => 'Адрес',
			];
		}

		public static function table() {
			static $has = null;
			if ($has === null) { $q = db::query("SHOW TABLES LIKE 'company_receipt'"); $has = $q && mysqli_num_rows($q) > 0; }
			return $has;
		}

		public static function create() {
			db::query("CREATE TABLE IF NOT EXISTS `company_receipt` (
				`company_id` INT NOT NULL PRIMARY KEY,
				`cfg` TEXT NULL,
				`logo` MEDIUMTEXT NULL,
				`upd_dt` DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		}

		// Компанияның баптауы ('logo' — data:image/... не бос)
		public static function get($cmp) {
			$c = self::defaults(); $c['logo'] = '';
			if (!self::table()) return $c;
			$q = db::query("select cfg, logo from company_receipt where company_id = '".(int)$cmp."'");
			$r = $q ? mysqli_fetch_assoc($q) : null;
			if ($r) {
				$j = json_decode((string)$r['cfg'], true);
				// ескі кілттер (браузердің автотолтыруына түспеу үшін ауыстырылды)
				if (is_array($j)) foreach (['title' => 'rtitle', 'l_phone' => 'l_ph', 'l_address' => 'l_adr'] as $o => $n) if (isset($j[$o]) && !isset($j[$n])) $j[$n] = $j[$o];
				if (is_array($j)) $c = array_merge($c, array_intersect_key($j, $c));
				$c['logo'] = (string)$r['logo'];
			}
			return $c;
		}

		// Формадан келгенді тексеру (logo мұнда емес)
		public static function clean($p) {
			$d = self::defaults(); $c = [];
			$pick = function ($v, $a, $def) { return in_array((string)$v, $a, true) ? (string)$v : $def; };
			$txt = function ($v, $n) { return mb_substr(trim(str_replace("\r", '', strip_tags((string)$v))), 0, $n); };
			$c['width'] = (int)$pick(@$p['width'], ['0', '80', '58'], '0');
			$c['font'] = $pick(@$p['font'], ['s', 'm', 'l'], 'm');
			$c['title_size'] = $pick(@$p['title_size'], ['s', 'm', 'l'], 'm');
			$c['item_lang'] = $pick(@$p['item_lang'], ['ru', 'kz'], 'ru');
			foreach (['delivery', 'prepay'] as $k) $c[$k] = $pick(@$p[$k], ['always', 'nonzero', 'never'], 'always');
			$c['logo_w'] = max(20, min(100, (int)@$p['logo_w'] ?: 60));
			foreach (['logo_on', 'title_on', 'show_status', 'show_type', 'show_number', 'show_date', 'show_additional', 'item_num',
				'show_topay', 'show_client', 'show_phone', 'show_address', 'show_cashback'] as $k) $c[$k] = (int)!empty($p[$k]);
			$c['rtitle'] = $txt(@$p['rtitle'], 40);
			$c['head_text'] = $txt(@$p['head_text'], 300);
			$c['footer'] = $txt(@$p['footer'], 500);
			foreach ($d as $k => $v) if (strpos($k, 'l_') === 0) { $t = $txt(@$p[$k], 30); $c[$k] = $t !== '' ? $t : $v; }
			return $c;
		}

		// Логотип: тек PNG/JPEG data URL, 400 КБ-қа дейін
		public static function logo_ok($s) {
			$s = (string)$s;
			return strlen($s) < 400000 && preg_match('~^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$~', $s);
		}
	}
