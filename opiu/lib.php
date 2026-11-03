<?
	// ОПиУ (күндік кіріс/шығыс) — Google кестедегі «Kuimak / ОПиУ по расчету» құрылымы.
	// Жолдар осы жерде анықталады. Түрлері:
	//   auto — базадан өзі есептеледі (сатылым, тапсырыс саны, курьер жалақысы)
	//   in   — қолмен енгізіледі (сұр ұяшықтар), company_pnl кестесінде сақталады
	//   sum  — басқа жолдардың қосындысы
	//   calc — формула (пайыз, орташа чек т.б.), JS ішінде есептеледі
	// fmt: m — ақша, c — дана, p — пайыз

	// Кесте жоқ болса, өзі құрылады (тек жаңа бос кесте, бар деректерге тимейді)
	function rnp_table() {
		db::query("CREATE TABLE IF NOT EXISTS `company_pnl` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`company_id` int(11) NOT NULL,
			`day` date NOT NULL COMMENT 'жоспар үшін айдың 1-күні',
			`plan` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = айлық жоспар',
			`row_key` varchar(40) NOT NULL,
			`amount` bigint(20) NOT NULL DEFAULT 0,
			`upd_by` int(11) DEFAULT NULL,
			`upd_dt` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `company_plan_day_row` (`company_id`, `plan`, `day`, `row_key`),
			KEY `company_day` (`company_id`, `day`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ОПиУ: күндік шығындар мен жоспар'");
	}

	// Сату арналары: тапсырыс түрі (retail_orders_type) бойынша
	function rnp_channels() {
		return [
			1 => 'Жеткізу',
			2 => 'Собой',
			3 => 'Зал (сервис)',
			4 => 'Кофе',
		];
	}

	// $types — компанияда бар тапсырыс түрлері (сатылымы бар)
	function rnp_rows($types) {
		$ch = rnp_channels();
		$r = [];

		// ---------- Сатылым ----------
		$s = []; $c = [];
		foreach ($types as $t) { $s[] = 's_t'.$t; $c[] = 'c_t'.$t; }
		$s[] = 's_agg'; $c[] = 'c_agg';
		$r[] = ['k' => 'sales', 'n' => 'Жалпы сатылым', 't' => 'sum', 'of' => $s, 'fmt' => 'm', 'st' => 'hd_g'];
		$r[] = ['k' => 'orders', 'n' => 'Тапсырыс саны', 't' => 'sum', 'of' => $c, 'fmt' => 'c', 'st' => 'b'];
		$r[] = ['k' => 'avg', 'n' => 'Орташа чек', 't' => 'calc', 'f' => ['div', 'sales', 'orders'], 'fmt' => 'm', 'st' => 'b'];
		foreach ($types as $t) {
			$r[] = ['k' => 's_t'.$t, 'n' => $ch[$t] ?? ('Түр '.$t), 't' => 'auto', 'fmt' => 'm', 'st' => 'grp'];
			$r[] = ['k' => 'c_t'.$t, 'n' => 'Тапсырыс саны', 't' => 'auto', 'fmt' => 'c', 'st' => 'l1'];
			$r[] = ['k' => 'a_t'.$t, 'n' => 'Орташа чек', 't' => 'calc', 'f' => ['div', 's_t'.$t, 'c_t'.$t], 'fmt' => 'm', 'st' => 'l1'];
		}
		$r[] = ['k' => 's_agg', 'n' => 'Агрегатор (Glovo, Wolt)', 't' => 'in', 'fmt' => 'm', 'st' => 'grp'];
		$r[] = ['k' => 'c_agg', 'n' => 'Тапсырыс саны', 't' => 'in', 'fmt' => 'c', 'st' => 'l1'];
		$r[] = ['k' => 'a_agg', 'n' => 'Орташа чек', 't' => 'calc', 'f' => ['div', 's_agg', 'c_agg'], 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];

		// ---------- Айнымалы шығындар ----------
		$r[] = ['k' => 'var', 'n' => 'Айнымалы шығындар', 't' => 'sum', 'of' => ['gen', 'mkt'], 'fmt' => 'm', 'st' => 'hd_d'];
		$r[] = ['k' => 'gen', 'n' => 'Жалпы шығындар', 't' => 'sum', 'of' => ['ret', 'cost', 'courier', 'var_other'], 'fmt' => 'm', 'st' => 'grp'];
		$r[] = ['k' => 'ret', 'n' => 'Возврат', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'cost', 'n' => 'Себес продукт', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'cost_p', 'n' => 'Себес үлесі, %', 't' => 'calc', 'f' => ['pct', 'cost', 'sales'], 'fmt' => 'p', 'st' => 'l1'];
		$r[] = ['k' => 'courier', 'n' => 'Курьер жалақысы', 't' => 'auto', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'var_other', 'n' => 'Басқада', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];
		$r[] = ['k' => 'mkt', 'n' => 'Маркетинг', 't' => 'sum', 'of' => ['m_t_hall', 'm_t_dlv', 'm_tiktok', 'm_agg', 'm_print', 'm_other'], 'fmt' => 'm', 'st' => 'grp'];
		$r[] = ['k' => 'mkt_cpo', 'n' => 'Бір тапсырыс бағасы', 't' => 'calc', 'f' => ['div', 'mkt', 'orders'], 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_t_hall', 'n' => 'Таргет - зал', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_t_dlv', 'n' => 'Таргет - жеткізу', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_tiktok', 'n' => 'Тик-ток', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_agg', 'n' => 'Glovo реклама', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_print', 'n' => 'Жарнамалық материалдар', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'm_other', 'n' => 'Басқада', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];

		// ---------- Маржа ----------
		$r[] = ['k' => 'margin', 'n' => 'Маржа пайдасы', 't' => 'calc', 'f' => ['sub', 'sales', 'var'], 'fmt' => 'm', 'st' => 'hd_o'];
		$r[] = ['k' => 'margin_p', 'n' => 'Маржинальность, %', 't' => 'calc', 'f' => ['pct', 'margin', 'sales'], 'fmt' => 'p', 'st' => 'b'];
		$r[] = ['k' => 'roi', 'n' => 'ROI (ақталымы)', 't' => 'calc', 'f' => ['pct', 'margin', 'var'], 'fmt' => 'p', 'st' => 'b'];
		$r[] = ['k' => '', 't' => 'gap'];

		// ---------- Тұрақты шығындар ----------
		$r[] = ['k' => 'fix', 'n' => 'Тұрақты шығындар', 't' => 'sum', 'of' => ['f_rent', 'f_util', 'f_net', 'f_bank', 'f_other', 'staff', 'own'], 'fmt' => 'm', 'st' => 'hd_d'];
		$r[] = ['k' => 'f_rent', 'n' => 'Аренда', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'f_util', 'n' => 'Коммуналдық төлемдер', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'f_net', 'n' => 'Байланыс, Internet, ТВ', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'f_bank', 'n' => 'Банктік шығындар', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'f_other', 'n' => 'Басқада', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];
		$r[] = ['k' => 'staff', 'n' => 'Қызметкерлердің жалақысы', 't' => 'sum', 'of' => ['st_agency', 'st_hall', 'st_kitchen', 'st_other'], 'fmt' => 'm', 'st' => 'grp'];
		$r[] = ['k' => 'st_agency', 'n' => 'Маркетинг агенттігі', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'st_hall', 'n' => 'Зал', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'st_kitchen', 'n' => 'Кухня', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'st_other', 'n' => 'Басқада', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];
		$r[] = ['k' => 'own', 'n' => 'Басшылық (иесінің үлесі)', 't' => 'sum', 'of' => ['own_hall', 'own_dlv'], 'fmt' => 'm', 'st' => 'grp'];
		$r[] = ['k' => 'own_hall', 'n' => 'Зал', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => 'own_dlv', 'n' => 'Жеткізу', 't' => 'in', 'fmt' => 'm', 'st' => 'l1'];
		$r[] = ['k' => '', 't' => 'gap'];

		// ---------- Нәтиже ----------
		$r[] = ['k' => 'ebitda', 'n' => 'Операциялық пайда (EBITDA)', 't' => 'calc', 'f' => ['sub', 'margin', 'fix'], 'fmt' => 'm', 'st' => 'hd_o'];
		$r[] = ['k' => 'ebitda_p', 'n' => 'EBITDA маржасы, %', 't' => 'calc', 'f' => ['pct', 'ebitda', 'sales'], 'fmt' => 'p', 'st' => 'b'];
		$r[] = ['k' => '', 't' => 'gap'];
		$r[] = ['k' => 'invest', 'n' => 'Қайта салым (вложение)', 't' => 'in', 'fmt' => 'm', 'st' => 'hd_g'];
		$r[] = ['k' => 'net', 'n' => 'Таза пайда', 't' => 'calc', 'f' => ['add', 'ebitda', 'invest'], 'fmt' => 'm', 'st' => 'hd_n'];
		$r[] = ['k' => 'net_p', 'n' => 'Таза пайда маржасы, %', 't' => 'calc', 'f' => ['pct', 'net', 'sales'], 'fmt' => 'p', 'st' => 'b'];

		return $r;
	}

	// Сақтауға рұқсат етілген кілттер: күнге — тек «in», жоспарға — «in» мен «auto»
	function rnp_keys($rows, $plan) {
		$k = [];
		foreach ($rows as $r) if ($r['t'] == 'in' || ($plan && $r['t'] == 'auto')) $k[$r['k']] = 1;
		return $k;
	}

	// Компанияда сатылымы бар тапсырыс түрлері (1 мен 2 әрдайым көрінеді)
	function rnp_types($company) {
		$types = [1 => 1, 2 => 1];
		$q = db::query("select distinct order_type from retail_orders where company_id = '$company' and order_type is not null");
		while ($q && $d = mysqli_fetch_assoc($q)) if (isset(rnp_channels()[intval($d['order_type'])])) $types[intval($d['order_type'])] = 1;
		ksort($types);
		return array_keys($types);
	}

	// Кезең: d — ай ішіндегі күндер, w — ай ішіндегі апталар (?m=YYYY-MM), m — жыл айлары (?y=YYYY).
	// Қайтарады: mode, cols (баған кілттері), heads (баған аттары), cur (бүгінгі баған), r_start/r_end (күн аралығы),
	// p_start/p_end/p_to (тапсырыс уақыты), b_ord/b_pnl (SQL топтау өрнегі), title, prev_link, next_link, ym
	function rnp_period($today_bd, $now_dt, $fill = true) {
		$m_names = ['', 'Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'];
		$m_low = ['', 'қаңтар', 'ақпан', 'наурыз', 'сәуір', 'мамыр', 'маусым', 'шілде', 'тамыз', 'қыркүйек', 'қазан', 'қараша', 'желтоқсан'];
		$wd = ['Жс', 'Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб'];
		$w_full = ['Жексенбі', 'Дүйсенбі', 'Сейсенбі', 'Сәрсенбі', 'Бейсенбі', 'Жұма', 'Сенбі'];

		// f = Толтыру (бір күн, өзгертуге болады); d, w, m, y = Қарау/анализ (тек оқу)
		// $fill = false — бетте толтыру жоқ (РНП: бәрі базадан мен ОПиУ-дан), тек Қарау
		$mode = in_array(@$_GET['mode'], $fill ? ['f', 'w', 'm', 'y'] : ['w', 'm', 'y'], true) ? $_GET['mode'] : 'w'; // әдепкі — осы апта
		$pm = $mode == 'f' ? 'd' : $mode; // кезең түрі
		$by = ($mode == 'm' && @$_GET['by'] == 'w') ? 'w' : 'd'; // Ай ішінде: күндер не апталар
		$bd = "DATE(DATE_SUB(ins_dt, INTERVAL 6 HOUR))"; // жұмыс күні
		$cols = []; $heads = [];

		// Әр көрініс тек БІР кезеңді көрсетеді (2026-11-03 пайдаланушы талабы):
		// Күн — бір күн, Апта — бір апта (7 күн), Ай — бір ай (күндері), Жыл — бір жыл (айлары).
		// Кезең ?d=YYYY-MM-DD күні арқылы беріледі (ескі ?m=YYYY-MM, ?y=YYYY да жүреді).
		$ad = $today_bd;
		if (preg_match('/^20\d{2}-\d{2}-\d{2}$/', (string)@$_GET['d']) && strtotime($_GET['d'])) $ad = date('Y-m-d', strtotime($_GET['d']));
		elseif (preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', (string)@$_GET['m'])) $ad = $_GET['m'] == substr($today_bd, 0, 7) ? $today_bd : $_GET['m'].'-01';
		elseif (preg_match('/^20\d{2}$/', (string)@$_GET['y'])) $ad = $_GET['y'] == substr($today_bd, 0, 4) ? $today_bd : $_GET['y'].'-01-01';
		$at = strtotime($ad);
		$day_head = function ($t) use ($wd) { $w = intval(date('w', $t)); return [date('d.m', $t), $wd[$w], $w == 0 || $w == 6]; };

		if ($pm == 'd') {
			$r_start = $ad;
			$r_end = date('Y-m-d', strtotime('+1 day', $at));
			$cols[] = $ad; $heads[$ad] = $day_head($at);
			$title = intval(date('j', $at)).' '.$m_low[intval(date('n', $at))].', '.mb_strtolower($w_full[intval(date('w', $at))]).($ad == $today_bd ? ' · бүгін' : '');
			$prev_d = date('Y-m-d', strtotime('-1 day', $at)); $next_d = $r_end;
			$b_ord = $bd; $b_pnl = "`day`";
		} elseif ($pm == 'w') {
			$ws = strtotime('-'.(intval(date('N', $at)) - 1).' day', $at);
			$r_start = date('Y-m-d', $ws);
			$r_end = date('Y-m-d', strtotime('+7 day', $ws));
			for ($i = 0; $i < 7; $i++) { $t = strtotime("+$i day", $ws); $k = date('Y-m-d', $t); $cols[] = $k; $heads[$k] = $day_head($t); }
			$we = strtotime('+6 day', $ws);
			$title = intval(date('j', $ws)).(date('n', $ws) != date('n', $we) ? ' '.$m_low[intval(date('n', $ws))] : '').' – '.intval(date('j', $we)).' '.$m_low[intval(date('n', $we))].($today_bd >= $r_start && $today_bd < $r_end ? ' · осы апта' : '');
			$prev_d = date('Y-m-d', strtotime('-7 day', $ws)); $next_d = $r_end;
			$b_ord = $bd; $b_pnl = "`day`";
		} elseif ($pm == 'm') {
			$r_start = date('Y-m-01', $at);
			$r_end = date('Y-m-d', strtotime("$r_start +1 month"));
			if ($by == 'w') {
				// Ай → Апталар: Дс–Жс апталар, ай шекарасында қиылады (қосындысы айға тең)
				$n = 0;
				for ($t = strtotime($r_start); $t < strtotime($r_end); $t = $e + 86400) {
					$e = strtotime('+'.(7 - intval(date('N', $t))).' day', $t);
					if ($e >= strtotime($r_end)) $e = strtotime($r_end) - 86400;
					$k = 'w'.date('Y-m-d', $t); $n++;
					$cols[] = $k; $heads[$k] = [date('d', $t).'–'.date('d.m', $e), $n.'-апта', false];
					if ($today_bd >= date('Y-m-d', $t) && $today_bd <= date('Y-m-d', $e)) $cur_w = $k;
				}
				$b_ord = "CONCAT('w', GREATEST(DATE_SUB($bd, INTERVAL WEEKDAY($bd) DAY), DATE('$r_start')))";
				$b_pnl = "CONCAT('w', GREATEST(DATE_SUB(`day`, INTERVAL WEEKDAY(`day`) DAY), DATE('$r_start')))";
			} else
			for ($t = strtotime($r_start); $t < strtotime($r_end); $t = strtotime('+1 day', $t)) { $k = date('Y-m-d', $t); $cols[] = $k; $heads[$k] = $day_head($t); }
			$title = $m_names[intval(date('n', $at))].' '.date('Y', $at);
			$prev_d = date('Y-m-d', strtotime("$r_start -1 month")); $next_d = $r_end;
			if ($by != 'w') { $b_ord = $bd; $b_pnl = "`day`"; }
		} else {
			$yy = intval(date('Y', $at));
			$r_start = "$yy-01-01"; $r_end = ($yy + 1).'-01-01';
			for ($i = 1; $i <= 12; $i++) { $k = $yy.'-'.str_pad($i, 2, '0', STR_PAD_LEFT); $cols[] = $k; $heads[$k] = [$m_names[$i], (string)$yy, false]; }
			$title = $yy.' жыл';
			$prev_d = ($yy - 1).'-01-01'; $next_d = $r_end;
			$b_ord = "DATE_FORMAT($bd, '%Y-%m')";
			$b_pnl = "DATE_FORMAT(`day`, '%Y-%m')";
		}
		// бүгін осы кезеңге кірсе, бүгінгі баған белгіленеді
		$cur = $pm == 'y' ? substr($today_bd, 0, 7) : (isset($cur_w) ? $cur_w : $today_bd);
		$q_by = $by == 'w' ? '&by=w' : '';
		$prev_link = "?mode=$mode&d=$prev_d$q_by";
		$next_link = "?mode=$mode&d=$next_d$q_by";
		$single = $pm == 'd';

		// Күн | Апта | Ай | Жыл ауыстырғышы: таңдалған күн сақталады (бүгін кезеңде болса — бүгін)
		$keep = ($today_bd >= $r_start && $today_bd < $r_end) ? $today_bd : $r_start;
		$mode_names = ['w' => 'Апта', 'm' => 'Ай', 'y' => 'Жыл']; // «Күн» көрінісі алынды (пайдаланушы): апта әдепкі
		$q_keep = $keep == $today_bd ? '' : '&d='.$keep;
		// Негізгісі — Қарау (Күн | Апта | Ай | Жыл). Толтыру — оң жақтағы бөлек «Запис қосу» батырмасы, ол әрқашан БҮГІНГІ күнді ашады.
		$mode_seg = '<div class="rnp_seg">';
		foreach ($mode_names as $k => $n) $mode_seg .= '<a class="rnp_seg_i'.($k == $mode ? ' rnp_seg_act' : '').'" href="?mode='.$k.$q_keep.'">'.$n.'</a>';
		$mode_seg .= '</div>';
		// Ай: қалай бөлінеді — Күндер не Апталар
		if ($mode == 'm') $mode_seg .= '<div class="rnp_seg rnp_seg_sub"><a class="rnp_seg_i'.($by != 'w' ? ' rnp_seg_act' : '').'" href="?mode=m'.$q_keep.'">Күндер</a><a class="rnp_seg_i'.($by == 'w' ? ' rnp_seg_act' : '').'" href="?mode=m'.$q_keep.'&by=w">Апталар</a></div>';
		$mode_add = !$fill ? '' : '<a class="rnp_add'.($mode == 'f' ? ' rnp_add_act' : '').'" href="?mode=f"><i class="fal fa-pen"></i>Запис қосу</a>';
		$mode_name = $mode == 'f' ? 'Толтыру' : $mode_names[$mode];
		$ym = substr($r_start, 0, 7);

		$p_start = "$r_start 06:00:00";
		$p_end = "$r_end 06:00:00";
		$p_to = ($now_dt < $p_end) ? $now_dt : $p_end; // алдағы предзаказдар есепке кірмейді
		return compact('mode', 'cols', 'heads', 'cur', 'r_start', 'r_end', 'p_start', 'p_end', 'p_to', 'b_ord', 'b_pnl', 'bd', 'title', 'prev_link', 'next_link', 'ym', 'mode_name', 'mode_seg', 'mode_add', 'single', 'by');
	}
