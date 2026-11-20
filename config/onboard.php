<?php
// «Жұмысты бастау» тізімі: жаңа компанияға баптау қадамдары (2026-11-20).
// Әр қадам базадағы деректен өзі «орындалды» болады. Виджет: block/onboard.php, толық беті: /start/.
// Мәтіндер: config/onboard_words.php → site_word-қа 5 тілде өзі жазылады (бар сөз өзгермейді).
class onboard {

	const VER = 'ob:_v1';
	public static $W = null;

	// мәтін: таңдалған тіл → қазақша
	public static function w($k) {
		if (self::$W === null) {
			self::$W = include __DIR__.'/onboard_words.php';
			self::seed();
		}
		$def = isset(self::$W[$k]) ? self::$W[$k]['kz'] : $k;
		return t::w($k, $def);
	}

	// бірінші ашылғанда сөздерді site_word-қа салады
	private static function seed() {
		try {
			if (!mysqli_num_rows(db::query("SHOW TABLES LIKE 'site_word'"))) return;
			if (mysqli_num_rows(db::query("select id from site_word where name = '".self::VER."' and lang = 'kz'"))) return;
			$vals = [];
			foreach (self::$W as $k => $tr) foreach ($tr as $lg => $txt) if ($txt !== '')
				$vals[] = "('".mysqli_real_escape_string(db::$connect, $k)."', '$lg', '".mysqli_real_escape_string(db::$connect, $txt)."')";
			$vals[] = "('".self::VER."', 'kz', '1')";
			foreach (array_chunk($vals, 100) as $ch) db::query("INSERT IGNORE INTO site_word (`name`, `lang`, `txt`) VALUES ".implode(', ', $ch));
			t::$words = null;
		} catch (Throwable $e) { error_log('onboard seed: '.$e->getMessage()); }
	}

	private static function tb($t) {
		static $c = [];
		if (!isset($c[$t])) { try { $c[$t] = mysqli_num_rows(db::query("SHOW TABLES LIKE '$t'")) > 0; } catch (Throwable $e) { $c[$t] = false; } }
		return $c[$t];
	}

	private static function col($t, $col) {
		try { return self::tb($t) && mysqli_num_rows(db::query("SHOW COLUMNS FROM `$t` LIKE '$col'")) > 0; } catch (Throwable $e) { return false; }
	}

	// сұрау бір жол қайтарса — true
	private static function any($sql) {
		try { $q = db::query($sql); return $q && mysqli_num_rows($q) > 0; } catch (Throwable $e) { return false; }
	}

	// кассаның адресі: live (*.gprog.kz) → posb.gprog.kz, локал → pos.berik.c
	public static function pos_url() {
		return preg_match('~gprog\.kz$~i', (string)($_SERVER['HTTP_HOST'] ?? '')) ? 'https://posb.gprog.kz' : 'http://pos.berik.c';
	}

	// қадамдар: [key, title, desc, url, icon, done]
	public static function steps($cmp, $uid, $fresh = false) {
		$cmp = (int)$cmp; $uid = (int)$uid;
		if (!$cmp) return [];
		// әр бетте 9 сұрау жібермеу үшін 60 секунд сессияда сақталады
		$ck = 'ob_'.$cmp;
		if (!$fresh && isset($_SESSION[$ck]) && $_SESSION[$ck]['t'] > time() - 60) $done = $_SESSION[$ck]['d'];
		else {
			$c = []; try { $c = mysqli_fetch_assoc(db::query("select * from company where id = '$cmp'")) ?: []; } catch (Throwable $e) {}
			$done = [
				'company' => self::col('company_branch', 'address') && self::any("select id from company_branch where company_id = '$cmp' and trim(coalesce(address, '')) <> '' limit 1"),
				'catalog' => self::tb('product_catalog') && self::any("select id from product_catalog where company_id = '$cmp' and (arh is null or arh = 0) limit 1"),
				'products' => self::tb('product') && self::any("select id from product where company_id = '$cmp' and arh = 0 limit 1"),
				'grammovka' => self::tb('product_item') && self::any("select i.id from product_item i join product p on p.id = i.product_id where p.company_id = '$cmp' and i.comp_id > 0 limit 1"),
				'team' => self::tb('company_staff') && self::any("select id from company_staff where company_id = '$cmp' and archive = 0 and positions_id <> 1 and user_id <> '$uid' limit 1"),
				'receipt' => self::tb('company_receipt') && self::any("select company_id from company_receipt where company_id = '$cmp' limit 1"),
				'qr' => self::tb('company_menu') && self::any("select company_id from company_menu where company_id = '$cmp' and is_on = 1 limit 1"),
				'sale' => self::tb('retail_orders') && self::any("select id from retail_orders where company_id = '$cmp' and number is not null limit 1"),
			];
			// жеткізу қосулы болса ғана аймақ қадамы шығады
			if (!empty($c['delivery'])) $done['zones'] = self::tb('company_delivery_zone') && self::any("select id from company_delivery_zone where company_id = '$cmp' limit 1");
			$_SESSION[$ck] = ['t' => time(), 'd' => $done];
		}

		$list = [
			['company', '/company/', 'fa-store'],
			['catalog', '/products/catalog/', 'fa-folder'],
			['products', '/products/', 'fa-utensils'],
			['grammovka', '/products/cost2/', 'fa-calculator'],
			['zones', '/delivery/', 'fa-map-marked-alt'],
			['team', '/team/', 'fa-user-headset'],
			['receipt', '/receipt/', 'fa-print'],
			['qr', '/qr/', 'fa-qrcode'],
			['sale', self::pos_url(), 'fa-cash-register'],
		];
		$r = [];
		foreach ($list as $s) {
			if (!array_key_exists($s[0], $done)) continue;
			$r[] = ['key' => $s[0], 'title' => self::w('ob:'.$s[0]), 'desc' => self::w('ob:'.$s[0].' d'), 'url' => $s[1], 'icon' => $s[2], 'done' => (bool)$done[$s[0]], 'ext' => strpos($s[1], 'http') === 0];
		}
		return $r;
	}

	// виджет көріне ме: кірген, кассир емес, компаниясы бар, жасырылмаған
	public static function show($cmp, $right) {
		if (!$cmp || @$right['positions_id'] == 3) return false;
		return empty($_COOKIE['ob_hide_'.(int)$cmp]);
	}
}
