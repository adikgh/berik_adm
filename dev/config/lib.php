<?php
// Әзірлеуші панелінің логикасы: тариф, жазылым, төлем, реферал бонустары.

class dev {

	// Сыйлық айлар: неше айға төлесе, неше ай тегін қосылады (жеңілдік жоқ)
	public static $bonus_months = [24 => 6, 12 => 2, 6 => 1];
	// Төлем айлары (таңдау)
	public static $month_opts = [1, 3, 6, 12, 24];
	// Реферал пайызы: шақырған компанияның неше-ші ақылы айы → %
	public static $ref_steps = [[3, 30], [12, 10], [24, 5], [PHP_INT_MAX, 1]];
	public static $methods = ['kaspi' => 'Kaspi', 'cash' => 'Қолма-қол', 'bank' => 'Шот', 'bonus' => 'Бонустан'];
	// Тегін сынақ: әр компанияға бір рет, төлемдер тарихына method = 'trial', сомасы 0 болып жазылады
	public static $trial_days = 14;
	public static function method_name($k) { return $k == 'trial' ? t::w('Free trial', 'Тегін сынақ') : (self::$methods[$k] ?? $k); }

	// Тіл: рөл мен төлем түрінің атаулары таңдалған тілге ауысады (config/core.php шақырады)
	public static function i18n() {
		$r = [1 => ['Owner', 'Иесі'], 2 => ['Developer', 'Әзірлеуші'], 3 => ['Admin / support', 'Админ / қолдау'], 4 => ['Top partner', 'Топ серіктес'], 5 => ['Marketer', 'Маркетолог']];
		foreach ($r as $k => $v) self::$roles[$k] = t::w($v[0], $v[1]);
		$m = ['kaspi' => ['Kaspi', 'Kaspi'], 'cash' => ['Cash', 'Қолма-қол'], 'bank' => ['Bank transfer', 'Шот'], 'bonus' => ['From bonus', 'Бонустан']];
		foreach ($m as $k => $v) self::$methods[$k] = t::w($v[0], $v[1]);
	}
	public static function month($n) {
		$m = [1 => ['January', 'Қаңтар'], ['February', 'Ақпан'], ['March', 'Наурыз'], ['April', 'Сәуір'], ['May', 'Мамыр'], ['June', 'Маусым'],
			['July', 'Шілде'], ['August', 'Тамыз'], ['September', 'Қыркүйек'], ['October', 'Қазан'], ['November', 'Қараша'], ['December', 'Желтоқсан']];
		$n = (int)$n; return isset($m[$n]) ? t::w($m[$n][0], $m[$n][1]) : '';
	}

	// Рөлдер және кім қай бетке кіреді
	public static $roles = [1 => 'Иесі', 2 => 'Әзірлеуші', 3 => 'Админ / қолдау', 4 => 'Топ серіктес', 5 => 'Маркетолог'];
	public static $access = [
		'home' => [1, 2, 3, 5],
		'companies' => [1, 2, 3, 5],   // көру
		'sub_edit' => [1, 3],          // жазылым, төлем енгізу
		'company_add' => [1, 3],       // жаңа компания тіркеу
		'company_arh' => [1, 3],       // компанияны архивке салу / шығару
		'pay_cancel' => [1],
		'payments' => [1, 3],
		'ref' => [1, 5],
		'ref_pay' => [1],              // шақырушыға ақша беру
		'partner' => [4],              // өз шақырған компаниялары мен бонусы
		'ai' => [1, 2],
		'wa' => [1, 2],               // WhatsApp растау коды (бүкіл жүйеге ортақ, админнің config/wa_conf.php)
		'tariffs' => [1],
		'tpl' => [1, 2],
		'team' => [1],
	];
	public static $role = 0;
	public static function can($k) { return in_array(self::$role, self::$access[$k] ?? [], true); }
	public static $sale_url = 'https://berik.gprog.kz/'; // сатылым сайты: шақыру сілтемесі осыған ?code=nick
	public static function ref_link($nick) { return self::$sale_url . '?code=' . rawurlencode($nick); }
	public static $top_partner = 10; // осыдан көп компания әкелсе, «топ серіктес» бола алады

	public static function e($s) { return mysqli_real_escape_string(db::$connect, trim((string)$s)); }
	public static function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
	public static function money($n) { return number_format((float)$n, 0, '.', ' ') . ' ₸'; }
	public static function d($dt) { return $dt ? date('d.m.Y', strtotime($dt)) : '—'; }
	public static function row($sql) { $q = db::query($sql); return $q ? mysqli_fetch_assoc($q) : null; }
	public static function rows($sql, $key = null) {
		$q = db::query($sql); $r = [];
		if ($q) while ($x = mysqli_fetch_assoc($q)) { if ($key) $r[$x[$key]] = $x; else $r[] = $x; }
		return $r;
	}
	public static function one($sql) { $q = db::query($sql); $r = $q ? mysqli_fetch_row($q) : null; return $r ? $r[0] : null; }

	// ---------- кестелер ----------
	public static function install() {
		if (mysqli_num_rows(db::query("SHOW TABLES LIKE 'dev_staff'"))) {
			// 2026-11-10: компанияны архивке салу (тек dev тізімдерінен жасырылады, компанияның өз деректері өзгермейді)
			if (!mysqli_num_rows(db::query("SHOW COLUMNS FROM company_sub LIKE 'arh'"))) db::query("ALTER TABLE company_sub ADD `arh` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'архивте', ADD `arh_dt` datetime DEFAULT NULL");
			return;
		}
		db::query("CREATE TABLE IF NOT EXISTS `company_tariff` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL,
			`price` int(11) NOT NULL DEFAULT 0 COMMENT 'айына, тг',
			`descr` varchar(255) DEFAULT NULL,
			`number` int(11) DEFAULT NULL,
			`arh` tinyint(1) NOT NULL DEFAULT 0,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='BERIK POS тарифтері'");
		if (!(int)self::one("select count(*) from company_tariff"))
			db::query("INSERT INTO company_tariff (name, price, number) VALUES ('Старт', 9990, 1), ('Бизнес', 29990, 2), ('Желі', 59990, 3)");

		db::query("CREATE TABLE IF NOT EXISTS `company_ref` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`nick` varchar(40) NOT NULL COMMENT 'сілтемедегі код: ?code=nick',
			`name` varchar(255) DEFAULT NULL,
			`phone` varchar(10) DEFAULT NULL,
			`company_id` int(11) DEFAULT NULL COMMENT 'өз компаниясы: бонуспен жазылымын төлей алады',
			`comment` varchar(255) DEFAULT NULL,
			`arh` tinyint(1) NOT NULL DEFAULT 0,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			UNIQUE KEY `nick` (`nick`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='шақырушылар (реферал)'");

		db::query("CREATE TABLE IF NOT EXISTS `company_sub` (
			`company_id` int(11) NOT NULL,
			`tariff_id` int(11) DEFAULT NULL,
			`paid_until` date DEFAULT NULL COMMENT 'осы күнді қоса төленген',
			`ref_id` int(11) DEFAULT NULL COMMENT 'кім шақырды',
			`note` varchar(255) DEFAULT NULL,
			`upd_dt` datetime DEFAULT NULL,
			`arh` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'архивте',
			`arh_dt` datetime DEFAULT NULL,
			PRIMARY KEY (`company_id`),
			KEY `ref_id` (`ref_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='компания жазылымы'");

		db::query("CREATE TABLE IF NOT EXISTS `company_payment` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`company_id` int(11) NOT NULL,
			`tariff_id` int(11) DEFAULT NULL,
			`price` int(11) NOT NULL DEFAULT 0 COMMENT 'айына',
			`months` int(11) NOT NULL DEFAULT 1 COMMENT 'ақылы айлар',
			`bonus_months` int(11) NOT NULL DEFAULT 0 COMMENT 'сыйлық айлар',
			`amount` int(11) NOT NULL DEFAULT 0,
			`method` varchar(20) DEFAULT NULL,
			`date_from` date DEFAULT NULL,
			`date_to` date DEFAULT NULL,
			`paid_dt` date DEFAULT NULL,
			`comment` varchar(255) DEFAULT NULL,
			`user_id` int(11) DEFAULT NULL,
			`arh` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = болдырылмады',
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			KEY `company_id` (`company_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='компания төлемдері'");

		db::query("CREATE TABLE IF NOT EXISTS `company_ref_bonus` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`ref_id` int(11) NOT NULL,
			`company_id` int(11) NOT NULL,
			`payment_id` int(11) NOT NULL,
			`month_no` int(11) NOT NULL COMMENT 'шақырылған компанияның неше-ші ақылы айы',
			`pct` decimal(5,2) NOT NULL,
			`amount` int(11) NOT NULL DEFAULT 0,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			KEY `ref_id` (`ref_id`),
			KEY `payment_id` (`payment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='шақырушыға түскен бонус'");

		db::query("CREATE TABLE IF NOT EXISTS `company_ref_out` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`ref_id` int(11) NOT NULL,
			`amount` int(11) NOT NULL DEFAULT 0,
			`kind` varchar(10) NOT NULL DEFAULT 'cash' COMMENT 'cash = ақшалай берілді, sub = жазылымға төленді',
			`payment_id` int(11) DEFAULT NULL,
			`comment` varchar(255) DEFAULT NULL,
			`user_id` int(11) DEFAULT NULL,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			KEY `ref_id` (`ref_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='шақырушыға берілген бонус'");

		// Әзірлеуші панеліне кіретіндер (компания жұмысшыларынан бөлек)
		db::query("CREATE TABLE IF NOT EXISTS `dev_staff` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`user_id` int(11) NOT NULL,
			`role` tinyint(4) NOT NULL COMMENT '1 иесі, 2 әзірлеуші, 3 админ/қолдау, 4 топ серіктес, 5 маркетолог',
			`ref_id` int(11) DEFAULT NULL COMMENT 'топ серіктес: өз шақыру коды',
			`comment` varchar(255) DEFAULT NULL,
			`arh` tinyint(1) NOT NULL DEFAULT 0,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			UNIQUE KEY `user_id` (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='әзірлеуші панелінің командасы'");

		// Claude шақырулары (админдегі config/claude.php жазады)
		db::query("CREATE TABLE IF NOT EXISTS `company_ai_log` (
			`id` int(11) NOT NULL AUTO_INCREMENT, `company_id` int(11) DEFAULT NULL, `user_id` int(11) DEFAULT NULL,
			`feature` varchar(40) DEFAULT NULL, `model` varchar(60) DEFAULT NULL,
			`input_tokens` int(11) NOT NULL DEFAULT 0, `output_tokens` int(11) NOT NULL DEFAULT 0,
			`cache_read` int(11) NOT NULL DEFAULT 0, `cache_write` int(11) NOT NULL DEFAULT 0,
			`usd` decimal(10,6) NOT NULL DEFAULT 0, `ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`), KEY `company_id` (`company_id`), KEY `ins_dt` (`ins_dt`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Claude шақырулары: токен, баға'");
	}

	// ---------- тариф, жазылым ----------
	public static function tariffs($all = false) {
		return self::rows("select * from company_tariff " . ($all ? "" : "where arh = 0 ") . "order by number, id", 'id');
	}
	public static function bonus_for($months) {
		foreach (self::$bonus_months as $m => $b) if ($months >= $m) return $b;
		return 0;
	}
	public static function sub($cid) {
		return self::row("select * from company_sub where company_id = '" . (int)$cid . "'");
	}
	public static function sub_save($cid, $set) {
		$cid = (int)$cid;
		if (!self::sub($cid)) db::query("insert into company_sub (company_id) values ('$cid')");
		$p = [];
		foreach ($set as $k => $v) $p[] = "`$k` = " . ($v === null || $v === '' ? "NULL" : "'" . self::e($v) . "'");
		$p[] = "upd_dt = '" . date('Y-m-d H:i:s') . "'";
		return db::query("update company_sub set " . implode(', ', $p) . " where company_id = '$cid'");
	}
	// Мәртебе: paid / soon (≤5 күн) / debt / none; $trial = соңғы жазба тегін сынақ болса
	public static function status($paid_until, $trial = false) {
		if (!$paid_until) return ['none', t::w('No subscription', 'Жазылым жоқ'), null];
		$days = (int)floor((strtotime($paid_until) - strtotime(date('Y-m-d'))) / 86400);
		if ($trial) return $days < 0 ? ['debt', t::w('Trial ended', 'Сынақ бітті'), $days] : ['trial', t::w('Free trial', 'Тегін сынақ'), $days];
		if ($days < 0) return ['debt', t::w('Expired', 'Мерзімі өтті'), $days];
		if ($days <= 5) return ['soon', t::w('Ends soon', 'Жақында бітеді'), $days];
		return ['paid', t::w('Paid', 'Төленген'), $days];
	}
	public static function days_txt($days) {
		if ($days === null) return '';
		if ($days < 0) return sprintf(t::w('Ended %d days ago', '%d күн бұрын бітті'), abs($days));
		if ($days == 0) return t::w('Ends today', 'бүгін бітеді');
		return sprintf(t::w('%d days left', '%d күн қалды'), $days);
	}

	// Барлық компания: жазылым, филиал, жұмысшы, тапсырыс белсенділігі
	// $arh: false - белсенділер, true - архивтегілер, null - барлығы
	public static function companies($arh = false) {
		$w = $arh === null ? '' : ($arh ? 'where s.arh = 1' : 'where coalesce(s.arh, 0) = 0');
		$list = self::rows("select c.id, c.name, c.code, c.city, c.start_dt, c.ins_dt, s.tariff_id, s.paid_until, s.ref_id, s.note, coalesce(s.arh, 0) arh, s.arh_dt
			from company c left join company_sub s on s.company_id = c.id $w order by c.number is null, c.number, c.id", 'id');
		$br = self::rows("select company_id, count(*) n from company_branch group by company_id", 'company_id');
		$st = self::rows("select company_id, count(distinct user_id) n from company_staff where archive = 0 and (positions_id is null or positions_id <> 1) group by company_id", 'company_id');
		$tr = self::trial_ids();
		$od = self::rows("select company_id, max(ins_dt) last_dt, sum(ins_dt >= now() - interval 30 day) n30 from retail_orders where company_id is not null group by company_id", 'company_id');
		foreach ($list as $id => &$c) {
			$c['branches'] = (int)@$br[$id]['n'];
			$c['staff'] = (int)@$st[$id]['n'];
			$c['last_dt'] = @$od[$id]['last_dt'];
			$c['n30'] = (int)@$od[$id]['n30'];
			$c['trial'] = isset($tr[$id]);
			$c['st'] = self::status($c['paid_until'], $c['trial']);
		}
		return $list;
	}
	public static function ago($dt) {
		if (!$dt) return t::w('No orders', 'тапсырыс жоқ');
		$d = (int)floor((time() - strtotime($dt)) / 86400);
		if ($d <= 0) return t::w('today', 'бүгін');
		if ($d == 1) return t::w('Yesterday', 'кеше');
		if ($d < 31) return sprintf(t::w('%d days ago', '%d күн бұрын'), $d);
		if ($d < 365) return sprintf(t::w('%d months ago', '%d ай бұрын'), floor($d / 30));
		return sprintf(t::w('%d years ago', '%d жыл бұрын'), floor($d / 365));
	}

	// ---------- тегін сынақ ----------
	// Соңғы жазбасы сынақ болып тұрған компаниялар (әлі ақы төлемегендер)
	public static function trial_ids() {
		return self::rows("select p.company_id from company_payment p
			join (select company_id, max(id) mid from company_payment where arh = 0 group by company_id) x on x.mid = p.id
			where p.method = 'trial'", 'company_id');
	}
	public static function is_trial($cid) { return isset(self::trial_ids()[(int)$cid]); }
	// Сынақ әр компанияға бір-ақ рет (user 2026-11-10): бұрын сынақ болса (болдырылмаса да), төлем болса не жазылым мерзімі қойылса, берілмейді
	public static function trial_can($cid) {
		$cid = (int)$cid;
		if ((int)self::one("select count(*) from company_payment where company_id = '$cid' and (arh = 0 or method = 'trial')")) return false;
		return !self::one("select paid_until from company_sub where company_id = '$cid' and paid_until is not null");
	}
	public static function trial_add($cid, $tid, $uid) {
		$cid = (int)$cid;
		if (!self::row("select id from company where id = '$cid'")) return t::w('Company not found', 'Компания табылмады');
		$t = self::row("select * from company_tariff where id = '" . (int)$tid . "' and arh = 0");
		if (!$t) return t::w('Choose a tariff', 'Тарифті таңдаңыз');
		if (!self::trial_can($cid)) return t::w('This company already had a trial or a payment', 'Бұл компанияда сынақ не төлем бұрын болған');
		$from = date('Y-m-d');
		$to = date('Y-m-d', strtotime($from . ' +' . (self::$trial_days - 1) . ' days'));
		db::query("insert into company_payment (company_id, tariff_id, price, months, bonus_months, amount, method, date_from, date_to, paid_dt, comment, user_id)
			values ('$cid', '" . (int)$t['id'] . "', 0, 0, 0, 0, 'trial', '$from', '$to', '$from', NULL, '" . (int)$uid . "')");
		$pid = (int)mysqli_insert_id(db::$connect);
		if (!$pid) return t::w('Not saved', 'Сақталмады') . ': ' . mysqli_error(db::$connect);
		self::sub_save($cid, ['tariff_id' => $t['id'], 'paid_until' => $to]);
		return $pid;
	}

	// ---------- жаңа компания ----------
	// $p: name, city, branch, phone, uname, password, tariff_id, ref_id, trial. Ашылады: company, company_branch, директор (company_staff, рөл 2), company_sub
	public static function company_add($p, $uid) {
		$name = trim(strip_tags((string)@$p['name'])); $city = trim(strip_tags((string)@$p['city']));
		if ($name === '' || $city === '') return t::w('Enter company name and city', 'Компания атауы мен қаласын жазыңыз');
		$phone = substr(preg_replace('/\D/', '', (string)@$p['phone']), -10);
		if (strlen($phone) != 10) return t::w('Enter the director phone in full', 'Директордың телефонын толық жазыңыз');
		$u = self::row("select * from `user` where phone = '$phone' limit 1");
		if (!$u) {
			$un = trim(strip_tags((string)@$p['uname'])); $pass = trim((string)@$p['password']);
			if ($un === '' || mb_strlen($pass) < 6) return t::w('This number is new. Enter the director name and a password (6+ characters)', 'Бұл нөмір базада жоқ. Директордың аты мен паролін (кемінде 6 таңба) жазыңыз');
		}
		if (@$p['tariff_id'] === 'trial') { $p['trial'] = 1; $p['tariff_id'] = @$p['trial_tariff']; } else $p['trial'] = 0; // «Тегін сынақ» тариф тізімінде
		$tid = (int)@$p['tariff_id']; $rid = (int)@$p['ref_id'];
		if ($tid && !self::row("select id from company_tariff where id = '$tid'")) return t::w('Choose a tariff', 'Тарифті таңдаңыз');
		if ($rid && !self::row("select id from company_ref where id = '$rid' and arh = 0")) return t::w('Referrer not found', 'Шақырушы табылмады');
		if (!empty($p['trial']) && !$tid) return t::w('Choose a tariff for the trial', 'Сынақ үшін тарифті таңдаңыз');

		db::query("insert into company (name, city, type_def, start_dt, ins_dt) values ('" . self::e(mb_substr($name, 0, 250)) . "', '" . self::e(mb_substr($city, 0, 250)) . "', '', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "')");
		$cid = (int)mysqli_insert_id(db::$connect);
		if (!$cid) return t::w('Not saved', 'Сақталмады') . ': ' . mysqli_error(db::$connect);
		// сілтемедегі код: #n<id> (админде ?company=n<id>)
		$code = '#n' . $cid; $i = 1;
		while (self::row("select id from company where code = '" . self::e($code) . "' and id <> '$cid'")) $code = '#n' . $cid . '_' . $i++;
		db::query("update company set code = '" . self::e($code) . "' where id = '$cid'");
		$bn = trim(strip_tags((string)@$p['branch'])) ?: $name;
		db::query("insert into company_branch (number, name, company_id) values (1, '" . self::e(mb_substr($bn, 0, 250)) . "', '$cid')");
		if (!$u) {
			db::query("insert into `user` (name, phone, password, `right`, ins_dt) values ('" . self::e(mb_substr($un, 0, 250)) . "', '$phone', '" . pass_sql($pass) . "', 1, '" . date('Y-m-d H:i:s') . "')");
			$u = self::row("select * from `user` where phone = '$phone' limit 1");
		} elseif (!$u['right']) db::query("update `user` set `right` = 1 where id = '" . (int)$u['id'] . "'");
		if ($u) db::query("insert into company_staff (user_id, positions_id, company_id) values ('" . (int)$u['id'] . "', 2, '$cid')");
		self::sub_save($cid, ['tariff_id' => $tid ?: null, 'ref_id' => $rid ?: null]);
		if (!empty($p['trial']) && $tid) self::trial_add($cid, $tid, $uid);
		return $cid;
	}

	// ---------- реферал ----------
	public static function ref_pct($month_no) {
		foreach (self::$ref_steps as $s) if ($month_no <= $s[0]) return $s[1];
		return 0;
	}
	public static function ref_balance($rid) {
		$in = (int)self::one("select coalesce(sum(amount),0) from company_ref_bonus where ref_id = '" . (int)$rid . "'");
		$out = (int)self::one("select coalesce(sum(amount),0) from company_ref_out where ref_id = '" . (int)$rid . "'");
		return ['in' => $in, 'out' => $out, 'left' => $in - $out];
	}
	public static function nick_clean($s) {
		return substr(preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string)$s))), 0, 40);
	}

	// ---------- төлем ----------
	// $p: company_id, tariff_id, months, amount, method, paid_dt, comment, ref_pay (бонустан төлейтін шақырушы id)
	public static function pay_add($p, $uid) {
		$cid = (int)$p['company_id'];
		if (!self::row("select id from company where id = '$cid'")) return t::w('Company not found', 'Компания табылмады');
		$t = self::row("select * from company_tariff where id = '" . (int)$p['tariff_id'] . "'");
		if (!$t) return t::w('Choose a tariff', 'Тарифті таңдаңыз');
		$months = (int)$p['months'];
		if ($months < 1 || $months > 60) return t::w('Wrong number of months', 'Ай саны қате');
		$amount = (int)preg_replace('/\D/', '', (string)@$p['amount']);
		if ($amount <= 0) $amount = $t['price'] * $months;
		$method = isset(self::$methods[@$p['method']]) ? $p['method'] : 'kaspi';
		$paid_dt = strtotime((string)@$p['paid_dt']) ? date('Y-m-d', strtotime($p['paid_dt'])) : date('Y-m-d');
		$bonus = self::bonus_for($months);

		// Бонустан төлеу: шақырушының өз компаниясы болуы және бонусы жетуі керек
		$ref_pay = null;
		if ($method == 'bonus') {
			$ref_pay = self::row("select * from company_ref where id = '" . (int)@$p['ref_pay'] . "' and arh = 0");
			if (!$ref_pay) return t::w('Choose whose bonus', 'Бонус иесін таңдаңыз');
			if ((int)$ref_pay['company_id'] !== $cid) return t::w('Only the referrer own company can pay with bonus', 'Бонуспен тек шақырушының өз компаниясы төлей алады');
			if (self::ref_balance($ref_pay['id'])['left'] < $amount) return t::w('Not enough bonus', 'Бонус жетпейді');
		}

		// Мерзім: бұрынғы мерзімнің жалғасы, ол өтіп кетсе төлем күнінен
		$sub = self::sub($cid);
		$from = $paid_dt;
		if ($sub && $sub['paid_until'] && $sub['paid_until'] >= $paid_dt) $from = date('Y-m-d', strtotime($sub['paid_until'] . ' +1 day'));
		$to = date('Y-m-d', strtotime($from . ' +' . ($months + $bonus) . ' months -1 day'));

		db::query("insert into company_payment (company_id, tariff_id, price, months, bonus_months, amount, method, date_from, date_to, paid_dt, comment, user_id)
			values ('$cid', '" . (int)$t['id'] . "', '" . (int)$t['price'] . "', '$months', '$bonus', '$amount', '$method', '$from', '$to', '$paid_dt', '" . self::e(mb_substr((string)@$p['comment'], 0, 250)) . "', '" . (int)$uid . "')");
		$pid = (int)mysqli_insert_id(db::$connect);
		if (!$pid) return t::w('Not saved', 'Сақталмады') . ': ' . mysqli_error(db::$connect);
		self::sub_save($cid, ['tariff_id' => $t['id'], 'paid_until' => $to]);

		if ($ref_pay) {
			db::query("insert into company_ref_out (ref_id, amount, kind, payment_id, comment, user_id) values ('" . (int)$ref_pay['id'] . "', '$amount', 'sub', '$pid', '" . self::e(t::w('Subscription payment', 'Жазылым төлемі')) . "', '" . (int)$uid . "')");
		} else self::ref_bonus_add($pid);
		return $pid;
	}

	// Шақырушыға бонус: әр ақылы айға өз пайызы
	public static function ref_bonus_add($pid) {
		$pay = self::row("select * from company_payment where id = '" . (int)$pid . "' and arh = 0");
		if (!$pay || in_array($pay['method'], ['bonus', 'trial'])) return;
		$sub = self::sub($pay['company_id']);
		if (!$sub || !$sub['ref_id']) return;
		$rid = (int)$sub['ref_id'];
		$ref = self::row("select * from company_ref where id = '$rid'");
		if (!$ref || (int)$ref['company_id'] === (int)$pay['company_id']) return; // өзін-өзі шақырмайды
		$before = (int)self::one("select coalesce(sum(months),0) from company_payment where company_id = '" . (int)$pay['company_id'] . "' and arh = 0 and method <> 'bonus' and id < '" . (int)$pid . "'");
		$per = $pay['amount'] / max(1, $pay['months']);
		for ($i = 1; $i <= $pay['months']; $i++) {
			$n = $before + $i;
			$pct = self::ref_pct($n);
			$sum = (int)round($per * $pct / 100);
			db::query("insert into company_ref_bonus (ref_id, company_id, payment_id, month_no, pct, amount) values ('$rid', '" . (int)$pay['company_id'] . "', '" . (int)$pid . "', '$n', '$pct', '$sum')");
		}
	}

	// Тек соңғы төлемді болдырмауға болады (мерзім кері қайтады)
	public static function pay_cancel($pid) {
		$pay = self::row("select * from company_payment where id = '" . (int)$pid . "' and arh = 0");
		if (!$pay) return t::w('Payment not found', 'Төлем табылмады');
		$last = (int)self::one("select max(id) from company_payment where company_id = '" . (int)$pay['company_id'] . "' and arh = 0");
		if ($last !== (int)$pay['id']) return t::w('Only the last payment can be cancelled', 'Тек соңғы төлемді болдырмауға болады');
		db::query("update company_payment set arh = 1 where id = '" . (int)$pid . "'");
		db::query("delete from company_ref_bonus where payment_id = '" . (int)$pid . "'");
		db::query("delete from company_ref_out where payment_id = '" . (int)$pid . "'");
		$prev = self::row("select * from company_payment where company_id = '" . (int)$pay['company_id'] . "' and arh = 0 order by id desc limit 1");
		self::sub_save($pay['company_id'], ['paid_until' => $prev ? $prev['date_to'] : null, 'tariff_id' => $prev ? $prev['tariff_id'] : $pay['tariff_id']]);
		return 'yes';
	}
}
