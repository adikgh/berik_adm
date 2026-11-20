<?
// Себес: Факт мәндері (қолмен толтырылады, cost_setting_fact). План (cost_setting) өзгермейді.
// Бос (NULL) өріс — План мәні қолданылады. auto() — «Соңғы айдың нақты деректерімен толтыру»: өткен толық айдың
// тапсырыстары (retail_orders, ОПиУ-дағыдай статустар), ОПиУ шығындары (company_pnl) және курьер жалақысы. Сақтамайды.
class c2fact {
	public static $keys = ['orders_day', 'salary', 'rent', 'utility', 'delivery', 'ads', 'markup'];

	public static function table() {
		static $ok = null;
		if ($ok !== null) return $ok;
		$t = db::query("show tables like 'cost_setting_fact'");
		if (!$t || !mysqli_num_rows($t)) @db::query("CREATE TABLE IF NOT EXISTS `cost_setting_fact` (
			`company_id` int(11) NOT NULL,
			`orders_day` decimal(10,1) DEFAULT NULL, `salary` int(11) DEFAULT NULL, `rent` int(11) DEFAULT NULL, `utility` int(11) DEFAULT NULL,
			`delivery` int(11) DEFAULT NULL, `ads` int(11) DEFAULT NULL, `markup` decimal(5,2) DEFAULT NULL, `upd_dt` datetime DEFAULT NULL,
			PRIMARY KEY (`company_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Себес: Факт мәндері (қолмен), бос өріс — План'");
		$t = db::query("show tables like 'cost_setting_fact'");
		return $ok = $t && mysqli_num_rows($t) > 0;
	}
	// [өріс => мән не null]; жазба жоқ болса null
	public static function get($cmp) {
		if (!self::table()) return null;
		$r = mysqli_fetch_assoc(db::query("select * from cost_setting_fact where company_id = '".(int)$cmp."'"));
		if (!$r) return null;
		$o = []; foreach (self::$keys as $k) $o[$k] = $r[$k] === null ? null : $r[$k] + 0;
		return $o;
	}
	public static function save($cmp, array $in) {
		if (!self::table()) return false;
		$v = [];
		foreach (self::$keys as $k) {
			$x = trim(str_replace([' ', ','], ['', '.'], (string)@$in[$k]));
			if ($x === '' || !is_numeric($x)) { $v[$k] = 'NULL'; continue; }
			$x = max(0, (float)$x);
			$v[$k] = "'".($k == 'markup' ? round(min(500, $x), 2) : ($k == 'orders_day' ? round($x, 1) : (int)round($x)))."'";
		}
		return db::query("insert into cost_setting_fact (company_id, ".implode(', ', self::$keys).", upd_dt) values ('".(int)$cmp."', ".implode(', ', $v).", NOW())
			on duplicate key update ".implode(', ', array_map(function ($k) { return "$k = values($k)"; }, self::$keys)).", upd_dt = NOW()");
	}

	// Өткен толық айдың нақты деректері: ['vals' => [өріс => мән], 'title', 'orders', 'days', 'empty' => [деректері жоқ өрістер]]
	public static function auto($cmp) {
		$cmp = (int)$cmp;
		$today_bd = date('Y-m-d', time() - 6 * 3600);
		$r_end = date('Y-m-01', strtotime($today_bd)); $r_start = date('Y-m-d', strtotime("$r_end -1 month"));
		$days = (int)date('t', strtotime($r_start));
		$p_start = "$r_start 06:00:00"; $p_to = "$r_end 06:00:00";
		// тапсырыс саны: ОПиУ / Статистикадағыдай (number бар, статус 1, 8, 2, 11, 3, 9, 4; бас тартылғандарсыз), жұмыс күні 06:00-ден
		$orders = (int)@mysqli_fetch_row(db::query("select count(*) from retail_orders o where o.company_id = '$cmp' and o.number is not null
			and o.order_status in (1, 8, 2, 11, 3, 9, 4) and o.ins_dt >= '$p_start' and o.ins_dt < '$p_to'"))[0];
		// ОПиУ баптары (company_pnl, plan = 0)
		$pnl = [];
		$t = db::query("show tables like 'company_pnl'");
		if ($t && mysqli_num_rows($t)) {
			$q = db::query("select row_key, SUM(amount) a from company_pnl where company_id = '$cmp' and `plan` = 0 and `day` >= '$r_start' and `day` < '$r_end' group by row_key");
			while ($q && $r = mysqli_fetch_assoc($q)) $pnl[$r['row_key']] = (float)$r['a'];
		}
		// курьер жалақысы (ОПиУ-дағыдай): сақталған күндік есеп, қалғаны тапсырыстардан (жеткізу ақысы + компанияның жеткізу бағасы)
		$cour = 0; $saved = [];
		$t = db::query("show tables like 'courier_day_report'");
		if ($t && mysqli_num_rows($t)) {
			$q = db::query("select `day`, courier_id, salary from courier_day_report where company_id = '$cmp' and `day` >= '$r_start' and `day` < '$r_end'");
			while ($q && $r = mysqli_fetch_assoc($q)) { $saved[$r['day'].'|'.$r['courier_id']] = 1; $cour += (int)$r['salary']; }
		}
		$dprice = (int)@mysqli_fetch_row(db::query("select delivery_price from company where id = '$cmp'"))[0];
		$q = db::query("select DATE(DATE_SUB(o.ins_dt, INTERVAL 6 HOUR)) dd, o.`сourier_id` u, COUNT(*) n, SUM(o.pay_delivery) pd from retail_orders o
			where o.company_id = '$cmp' and o.number is not null and o.`сourier_id` > 0 and o.order_status in (1, 8, 2, 11, 3, 4) and o.ins_dt >= '$p_start' and o.ins_dt < '$p_to' group by dd, u");
		while ($q && $r = mysqli_fetch_assoc($q)) if (!isset($saved[$r['dd'].'|'.$r['u']])) $cour += (int)$r['pd'] + (int)$r['n'] * $dprice;
		$g = function ($ks) use ($pnl) { $v = 0; foreach ($ks as $k) $v += $pnl[$k] ?? 0; return $v; };
		// Айлық — қызметкерлер (зал, кухня, басқа); К.у. — коммуналдық + байланыс; Реклама — маркетинг баптары + агенттік
		$sum = ['salary' => $g(['st_hall', 'st_kitchen', 'st_other']), 'rent' => $g(['f_rent']), 'utility' => $g(['f_util', 'f_net']), 'delivery' => $cour,
			'ads' => $g(['m_t_hall', 'm_t_dlv', 'm_tiktok', 'm_agg', 'm_print', 'm_other', 'st_agency'])];
		$vals = []; $empty = [];
		if ($orders > 0) $vals['orders_day'] = round($orders / $days, 1); else $empty[] = 'orders_day';
		foreach ($sum as $k => $v) { if ($orders > 0 && $v > 0) $vals[$k] = (int)round($v / $orders); else $empty[] = $k; }
		return ['vals' => $vals, 'empty' => $empty, 'title' => date('m.Y', strtotime($r_start)), 'orders' => $orders, 'days' => $days];
	}
}
