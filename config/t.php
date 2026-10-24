<?php

// Тіл жүйесі (Aisary әдісімен): `site_word` кестесі — name (кілт), lang (kz/en/uz/ky/ru), txt (мәтін).
// Әр тіл — жеке жол, бағанға бөлінбейді: жаңа тіл қосу үшін тек жолдар қосылады.
class t {

	public static $words = null;
	public static $ok = null;

	public function __construct() { }

	// Интерфейс тілі: kz (стандарт), en, uz, ky, ru
	public static function lang() {
		global $ui_lang;
		if (!empty($ui_lang)) return $ui_lang;
		if (isset($_SESSION['lang'])) return $_SESSION['lang'];
		return 'kz';
	}

	// Ағымдағы тіл + қазақша (fallback) сөздерін бір сұраумен жүктеп, кэште ұстаймыз
	public static function load() {
		if (self::$words !== null) return self::$words;
		self::$words = ['kz' => [], 'cur' => []];
		self::$ok = mysqli_num_rows(db::query("SHOW TABLES LIKE 'site_word'")) > 0
			&& mysqli_num_rows(db::query("SHOW COLUMNS FROM `site_word` LIKE 'lang'")) > 0;
		if (!self::$ok) return self::$words;
		$lang = self::lang();
		$q = db::query("select `name`, `lang`, `txt` from `site_word` where `lang` in ('kz', '$lang')");
		while ($r = mysqli_fetch_assoc($q)) {
			if ($r['lang'] == 'kz') self::$words['kz'][$r['name']] = $r['txt'];
			if ($r['lang'] == $lang) self::$words['cur'][$r['name']] = $r['txt'];
		}
		return self::$words;
	}

	// t::w('Orders', 'Тапсырыстар') → таңдалған тілдегі мәтін.
	// Аударма жоқ болса — қазақшасы, ол да жоқ болса — $def (не кілттің өзі).
	// Автоматты: кодта жаңа кілт қолданылса, ол базаға қазақшасымен өзі жазылады.
	public static function w($name, $def = null) {
		$w = self::load();
		if (isset($w['cur'][$name]) && $w['cur'][$name] !== '') return $w['cur'][$name];
		if (isset($w['kz'][$name]) && $w['kz'][$name] !== '') return $w['kz'][$name];
		if ($def !== null && self::$ok && !isset($w['kz'][$name])) {
			$n = mysqli_real_escape_string(db::$connect, $name);
			$d = mysqli_real_escape_string(db::$connect, $def);
			db::query("INSERT IGNORE INTO `site_word` (`name`, `lang`, `txt`) VALUES ('$n', 'kz', '$d')");
			self::$words['kz'][$name] = $def;
		}
		return $def !== null ? $def : $name;
	}

	// JS үшін: барлық сөздер таңдалған тілде { name: txt }
	public static function all() {
		$w = self::load();
		return array_merge(array_filter($w['kz'], 'strlen'), array_filter($w['cur'], 'strlen'));
	}

	// word block
	public static function wb($l) {
		$sql = mysqli_fetch_array(db::query("select * from `word_block` where txt = '$l'"));
		if (isset($_SESSION['lang'])) $lang = $_SESSION['lang']; else $lang = 'ru';
		return $sql['txt_'.$lang];
	}

	// info
	public static function info($id) {
		$sql = mysqli_fetch_array(db::query("select * from `info` where id = '$id'"));
		if (isset($_SESSION['lang'])) $lang = $_SESSION['lang']; else $lang = 'ru';
		return $sql['name_'.$lang];
	}

}
