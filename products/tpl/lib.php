<?
// Граммовка шаблондары: барлық компанияға ортақ (grammovka_tpl, grammovka_tpl_item).
// Компания тағамына шаблон қолданғанда құрам аттары компанияның product_comp-ымен атымен сәйкестенеді,
// жоқ болса жаңа құрам ашылады (бағасы 0, Себесте толтырылады).
class gtpl {
	public static $var_names = [1 => 'Эконом', 2 => 'Стандарт', 3 => 'Премиум'];

	public static function ready() {
		static $ok = null;
		if ($ok === null) { $q = db::query("show tables like 'grammovka_tpl_item'"); $ok = $q && mysqli_num_rows($q) > 0; }
		return $ok;
	}

	public static function norm($s) {
		$s = mb_strtolower(trim(html_entity_decode(strip_tags((string)$s), ENT_QUOTES, 'UTF-8')), 'UTF-8');
		$s = str_replace(['ё', '–', '—'], ['е', '-', '-'], $s);
		return preg_replace('/\s+/u', ' ', $s);
	}

	public static function all() {
		static $all = null;
		if ($all !== null) return $all;
		$all = [];
		$q = db::query("select id, name_ru, cat, keys_ru from grammovka_tpl where arh = 0 order by number, id");
		while ($r = mysqli_fetch_assoc($q)) $all[$r['id']] = $r;
		return $all;
	}

	// Тағам атына сай шаблондар, ең жақыны бірінші. $cat = тағамның каталог аты (ұпай қосады)
	public static function match($name, $cat = '', $limit = 3) {
		$n = self::norm($name); $c = self::norm($cat); $res = [];
		if ($n === '') return [];
		foreach (self::all() as $t) {
			$best = 0;
			$keys = array_filter(array_map('trim', explode('|', (string)$t['keys_ru'])));
			$keys[] = $t['name_ru'];
			foreach ($keys as $k) {
				$k = self::norm($k);
				if ($k === '') continue;
				$re = '/(^|[^\p{L}\d])' . str_replace('\.\*', '.*', preg_quote($k, '/')) . '/u';
				if (@preg_match($re, $n, $m)) $best = max($best, mb_strlen($k) + ($n === $k ? 50 : 0));
			}
			if (!$best) continue;
			if ($c !== '' && $t['cat'] && mb_strpos($c, self::norm($t['cat'])) !== false) $best += 5;
			$res[] = ['id' => (int)$t['id'], 'name' => $t['name_ru'], 'cat' => $t['cat'], 'score' => $best];
		}
		// тағамның каталогы шаблон санатына сай болса (Пицца, Роллы...), тек сол санаттағылар
		if ($c !== '') {
			$same = array_values(array_filter($res, function ($x) use ($c) { return $x['cat'] && mb_strpos($c, gtpl::norm($x['cat'])) !== false; }));
			$cats = false; foreach (self::all() as $t) if ($t['cat'] && mb_strpos($c, self::norm($t['cat'])) !== false) { $cats = true; break; }
			if ($cats) $res = $same;
		}
		usort($res, function ($a, $b) { return $b['score'] - $a['score']; });
		return array_slice($res, 0, $limit);
	}

	// Шаблонның 3 нұсқасы: [variant => [[name, unit, qty], ...]]
	public static function vars($tpl_id) {
		$tpl_id = (int)$tpl_id; $v = [1 => [], 2 => [], 3 => []];
		$q = db::query("select variant, name_ru, unit, quantity from grammovka_tpl_item where tpl_id = '$tpl_id' order by variant, number, id");
		while ($r = mysqli_fetch_assoc($q)) $v[(int)$r['variant']][] = ['name' => $r['name_ru'], 'unit' => $r['unit'], 'qty' => (float)$r['quantity']];
		return $v;
	}

	public static function has_items($pid) {
		$pid = (int)$pid;
		return mysqli_num_rows(db::query("select id from product_item where product_id = '$pid' and comp_id > 0 limit 1")) > 0;
	}

	// Компанияның құрамын атымен табу, жоқ болса ашу
	public static function comp_id($cmp, $name, $unit, &$created = 0) {
		$cmp = (int)$cmp; $e = mysqli_real_escape_string(db::$connect, (string)$name);
		$q = db::query("select id from product_comp where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e')) order by id limit 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) return (int)$r['id'];
		$u = in_array($unit, ['кг', 'л', 'шт']) ? $unit : 'шт';
		if (!db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price) values ('$cmp', '$e', '$e', '$u', 'raw', 0)")) return 0;
		$created++;
		return (int)mysqli_insert_id(db::$connect);
	}

	// Бір тағамға шаблон қолдану. $replace = бар құрамын ауыстыру
	public static function apply($cmp, $pid, $tpl_id, $variant, $replace = false, &$created = 0) {
		$cmp = (int)$cmp; $pid = (int)$pid; $variant = (int)$variant;
		if (!isset(self::$var_names[$variant])) return 'variant';
		if (!mysqli_num_rows(db::query("select id from product where id = '$pid' and company_id = '$cmp'"))) return 'error';
		$items = self::vars($tpl_id)[$variant] ?? [];
		if (!$items) return 'empty';
		if (self::has_items($pid)) {
			if (!$replace) return 'has';
			db::query("delete from product_item where product_id = '$pid' and comp_id > 0");
		}
		foreach ($items as $it) {
			$cid = self::comp_id($cmp, $it['name'], $it['unit'], $created);
			if (!$cid) continue;
			$qty = round($it['qty'], 3);
			db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '$qty')");
		}
		return 'yes';
	}

	// Граммовкасы бос тағамдар (сет пен архив емес)
	public static function empty_dishes($cmp) {
		$cmp = (int)$cmp; $rows = [];
		$set = '';
		$t = db::query("show tables like 'product_set_item'");
		if ($t && mysqli_num_rows($t)) $set = " and not exists (select 1 from product_set_item s where s.set_id = p.id)";
		$q = db::query("select p.id, p.name_ru, p.name_kz, c.name_ru cat from product p left join product_catalog c on c.id = p.catalog_id
			where p.company_id = '$cmp' and (p.arh is null or p.arh = 0)
			and not exists (select 1 from product_item i where i.product_id = p.id and i.comp_id > 0)$set
			order by c.number, p.number, p.id");
		while ($r = mysqli_fetch_assoc($q)) {
			// сеттер шаблонсыз: олардың құрамы «Сет құрамында»
			$nn = gtpl::norm($r['name_ru'] ?: $r['name_kz']);
			if (preg_match('/(^|\s)сет/u', gtpl::norm($r['cat'])) || preg_match('/^сет\b/u', $nn) || preg_match('/\s\+\s?|\+\s/u', $nn)) continue; // «… + Кола» комбо да сет
			$rows[] = $r;
		}
		return $rows;
	}

	// Барлық бос тағамға таңдалған нұсқаны қою. $dry = тек тізім (не болатынын көрсету)
	public static function apply_all($cmp, $variant, $dry = false) {
		$res = ['ok' => [], 'none' => [], 'created' => 0];
		foreach (self::empty_dishes($cmp) as $d) {
			$nm = trim(strip_tags($d['name_ru'] ?: $d['name_kz']));
			$m = self::match($nm, (string)$d['cat'], 1);
			// сусын: құрамы — өзі, 1 дана (Кола 0.5 → «Кола 0.5» 1 шт)
			if (!$m && preg_match('/напит|сусын|ішімдік/u', gtpl::norm($d['cat']))) {
				if (!$dry) {
					$cr = 0; $cid = self::comp_id($cmp, $nm, 'шт', $cr); $res['created'] += $cr; $pid = (int)$d['id'];
					if ($cid) db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', 1)");
				}
				$res['ok'][] = ['id' => (int)$d['id'], 'name' => $nm, 'tpl' => 'Сусын (1 шт)'];
				continue;
			}
			if (!$m) { $res['none'][] = ['id' => (int)$d['id'], 'name' => $nm]; continue; }
			if ($dry && !(self::vars($m[0]['id'])[(int)$variant] ?? [])) { $res['none'][] = ['id' => (int)$d['id'], 'name' => $nm]; continue; }
			if (!$dry) {
				$cr = 0;
				if (self::apply($cmp, $d['id'], $m[0]['id'], $variant, false, $cr) !== 'yes') { $res['none'][] = ['id' => (int)$d['id'], 'name' => $nm]; continue; }
				$res['created'] += $cr;
			}
			$res['ok'][] = ['id' => (int)$d['id'], 'name' => $nm, 'tpl' => $m[0]['name']];
		}
		return $res;
	}
}
