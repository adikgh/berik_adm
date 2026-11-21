<?
// ЖИ граммовкасы дайындамалар арқылы: тағамда «тікелей шикізат» болып жайылған дайындаманы (рис + уксус + қант + тұз → «Рис для суши»,
// майонез + чили → «Соус спайси», темпура қоспасы + жұмыртқа → «Кляр») бір дайындама жолына жинайды. Детерминистік, ЖИ-сіз.
// Қолданылады: products/tpl/ai.php (gtpl_ai::recipe — жаңа ЖИ рецепті), products/cost2/ai.php (сетті толтыру),
// products/cost2/get.php?a=semi_fix (бар деректі түзету: тек ЖИ / шаблон толтырған, қолмен өзгертілмеген тағамдар).
if (!class_exists('gtpl')) require_once __DIR__.'/../tpl/lib.php';

class c2semi {
	// дайындама шаблоны => негізгі зат (болмаса ереже қолданылмайды), бірге жиналатындар, мөлшер ережесі
	public static function rules() {
		return [
			['semi' => 'Рис для суши', 'main' => '/^рис(?!\s*для\s*суши)(\s|$)/u', 'with' => '/^(уксус|рисовый уксус|сахар|соль|мирин|заправка для риса|рисовая заправка)/u', 'how' => 'rice'],
			['semi' => 'Соус спайси', 'main' => '/^(соус чили|чили соус|шрирача|sriracha|соус шрирача)/u', 'need' => '/^майонез/u', 'with' => '/^(майонез|масло кунжутное|кунжутное масло)/u', 'how' => 'sum'],
			['semi' => 'Кляр', 'main' => '/^(смесь темпура|смесь для темпуры|темпура смесь|мука темпура|мука для темпуры)/u', 'with' => '/^(яйц|вода|крахмал|соль)/u', 'how' => 'sum'],
		];
	}
	// 1 шт жұмыртқа ≈ 50 г (кляр массасы үшін)
	public static function grams($it, $v) { $q = (float)@$it['q'][$v]; return $it['unit'] == 'шт' ? (preg_match('/^яйц/u', gtpl::norm($it['name'])) ? $q * 50 : 0) : $q; }
	// пісірілмеген күріштен дайындамаға коэффициент (шаблоннан: шығым / күріш), әдепкі 2.3
	public static function rice_k($tid) {
		$t = gtpl::tpl($tid); $y = (float)@$t['yield_qty'] * 1000; $m = 0;
		foreach (gtpl::var_items($tid, 2) as $i) if (preg_match('/^рис/u', gtpl::norm($i['name']))) $m = max($m, (float)$i['qty']);
		return $y > 0 && $m > 0 ? $y / $m : 2.3;
	}

	// $items: [['name', 'unit' => кг|л|шт, 'q' => [э, с, п] грамм/мл/дана, ...қосымша кілттер]]. Қайтарады жаңа тізім; $hit — жиналған дайындамалар
	public static function group(array $items, &$hit = []) {
		$hit = [];
		if (!gtpl::semi_ready()) return $items;
		foreach (self::rules() as $r) {
			$tid = gtpl::semi_find($r['semi']); if (!$tid) continue;
			$main = []; $with = []; $has_need = empty($r['need']);
			foreach ($items as $k => $it) {
				$n = gtpl::norm($it['name']);
				if (gtpl::norm($r['semi']) === $n) { $main = []; break; } // дайындаманың өзі бар — тимейміз
				if (preg_match($r['main'], $n)) $main[] = $k;
				elseif (preg_match($r['with'], $n)) $with[] = $k;
				if (!empty($r['need']) && preg_match($r['need'], $n)) $has_need = true;
			}
			if (!$main || !$has_need) continue;
			$q = [0, 0, 0];
			foreach ([0, 1, 2] as $v) {
				if ($r['how'] == 'rice') {
					// күріш: шикі салмақ (≤ 90 г) → дайындама салмағы шаблон коэффициентімен; > 90 г болса пісірілген салмақ деп аламыз
					$g = 0; foreach ($main as $k) $g += self::grams($items[$k], $v);
					$q[$v] = $g > 0 && $g <= 90 ? $g * self::rice_k($tid) : $g;
				} else foreach (array_merge($main, $with) as $k) $q[$v] += self::grams($items[$k], $v);
				$q[$v] = round($q[$v], 1);
			}
			if (!array_filter($q)) continue;
			$t = gtpl::tpl($tid);
			$abs = array_merge($main, $with);
			$new = ['name' => $t['name_ru'], 'unit' => gtpl::unit($t['unit'] ?: 'кг'), 'q' => $q, 'semi' => $tid, 'absorbed' => array_map(function ($k) use ($items) { return $items[$k]; }, $abs)];
			$first = min($abs); $out = [];
			foreach ($items as $k => $it) { if ($k === $first) $out[] = $new; if (!in_array($k, $abs, true)) $out[] = $it; }
			$items = $out; $hit[] = $t['name_ru'];
		}
		return $items;
	}

	// Тағамның граммовкасы (product_item) — тікелей шикізатқа жайылған ба. Тек қолмен өзгертілмеген (ubd_dt бос) жолдар.
	public static function dish_rows($pid) {
		$rows = []; $q = db::query("select i.id, i.comp_id, i.quantity, i.ubd_dt, c.name_ru, c.name_kz, c.unit, (select count(*) from product_comp_part p where p.comp_id = c.id) parts
			from product_item i join product_comp c on c.id = i.comp_id where i.product_id = '".(int)$pid."' and i.comp_id > 0 order by i.id");
		while ($q && $r = mysqli_fetch_assoc($q)) $rows[] = $r;
		return $rows;
	}
	public static function plan_dish($pid, &$hit = []) {
		$rows = self::dish_rows($pid); $hit = [];
		if (!$rows) return null;
		foreach ($rows as $r) if ($r['ubd_dt']) return null; // қолмен өзгертілген — тимейміз
		$items = array_map(function ($r) { $q = (float)$r['quantity']; return ['name' => trim($r['name_ru'] ?: $r['name_kz']), 'unit' => gtpl::unit($r['unit'] ?: 'шт'), 'q' => [$q, $q, $q], 'pi' => (int)$r['id'], 'parts' => (int)$r['parts']]; }, $rows);
		$new = self::group($items, $hit);
		return $hit ? $new : null;
	}
	// Компанияның жинауға келетін тағамдары: [pid => [name, hit]]
	public static function candidates($cmp) {
		$cmp = (int)$cmp; $res = [];
		$q = db::query("select distinct p.id, p.name_ru, p.name_kz from product p join product_item i on i.product_id = p.id and i.comp_id > 0
			join product_comp c on c.id = i.comp_id where p.company_id = '$cmp' and (p.arh is null or p.arh = 0)
			and not exists (select 1 from product_comp_part x where x.comp_id = c.id)
			and (lower(c.name_ru) regexp '^(рис|соус чили|чили соус|шрирача|смесь темпура|смесь для темпуры|мука темпура|мука для темпуры)')");
		while ($q && $r = mysqli_fetch_assoc($q)) { $hit = []; if (self::plan_dish($r['id'], $hit)) $res[(int)$r['id']] = ['name' => trim(strip_tags($r['name_ru'] ?: $r['name_kz'])), 'hit' => $hit]; }
		return $res;
	}
	// Тағамды түзету: жиналған шикізат жолдары өшіп, орнына компанияның дайындамасы (шаблоннан рецептімен ашылады не бары қолданылады)
	public static function fix_dish($cmp, $pid) {
		$hit = []; $new = self::plan_dish($pid, $hit);
		if (!$new) return 0;
		$pid = (int)$pid; $cr = 0;
		foreach ($new as $it) {
			if (empty($it['semi'])) continue;
			$cid = gtpl::comp_id($cmp, $it['name'], $it['unit'], $cr, 2, $it['semi']);
			if (!$cid) return 0;
			$ids = array_map(function ($a) { return (int)$a['pi']; }, $it['absorbed']);
			db::query("delete from product_item where product_id = '$pid' and id in (".implode(',', $ids).")");
			db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '".round($it['q'][1], 1)."')");
		}
		return count($hit);
	}
	// ЖИ шаблондары (arh = 2, тағам): жайылған жолдарды да дайындамаға жинау — келесі қолдануда дұрыс болсын
	public static function fix_tpls() {
		$n = 0;
		$q = db::query("select id, name_ru, cat, keys_ru from grammovka_tpl where arh = 2".(gtpl::semi_ready() ? " and kind = 'dish'" : ""));
		while ($q && $t = mysqli_fetch_assoc($q)) {
			$v = gtpl::vars($t['id']); $items = []; $idx = [];
			foreach ([1, 2, 3] as $vi) foreach ((array)@$v[$vi] as $i) {
				$k = gtpl::norm($i['name']).'|'.$i['unit'];
				if (!isset($idx[$k])) { $idx[$k] = count($items); $items[] = ['name' => $i['name'], 'unit' => $i['unit'], 'q' => [0, 0, 0]]; }
				$items[$idx[$k]]['q'][$vi - 1] = (float)$i['qty'];
			}
			$hit = []; $new = self::group($items, $hit);
			if (!$hit) continue;
			if (!class_exists('gtpl_ai')) require_once __DIR__.'/../tpl/ai.php';
			if (gtpl_ai::store($t['id'], $t['name_ru'], $t['cat'], $t['keys_ru'], array_map(function ($i) { return ['name' => $i['name'], 'unit' => $i['unit'], 'q' => $i['q']]; }, $new), 2, null, 'dish')) $n++;
		}
		return $n;
	}
}
