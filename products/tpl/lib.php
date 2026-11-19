<?
// Граммовка шаблондары: барлық компанияға ортақ (grammovka_tpl, grammovka_tpl_item).
// kind = dish (тағам) не semi (дайындама: unit, yield_qty). Шаблон жолының semi_id-і — сол құрамның дайындама шаблоны.
// Компания тағамына шаблон қолданғанда толық тізбек құрылады: шикізат → дайындама (өз рецептімен) → тағам.
// Құрам аттары компанияның product_comp-ымен атымен сәйкестенеді (үлкен-кіші әріп бәрібір), жоқ болса ашылады:
// дайындама болса — шаблонынан құрамымен, шикізат болса — бағасы компанияның закупынан, басқа компаниялардан не ЖИ-ден (prices()).
class gtpl {
	public static $var_names = [1 => 'Эконом', 2 => 'Стандарт', 3 => 'Премиум'];
	public static $noprice = []; // осы сұрауда бағасыз ашылған шикізат id-лері (ЖИ бағалайды)

	public static function ready() {
		static $ok = null;
		if ($ok === null) { $q = db::query("show tables like 'grammovka_tpl_item'"); $ok = $q && mysqli_num_rows($q) > 0; }
		return $ok;
	}
	// install_semi.sql іске қосылған ба
	public static function semi_ready() {
		static $ok = null;
		if ($ok === null) $ok = self::ready() && mysqli_num_rows(db::query("show columns from grammovka_tpl like 'kind'")) > 0;
		return $ok;
	}
	public static function col($t, $c) {
		static $m = [];
		if (!isset($m[$t.$c])) $m[$t.$c] = mysqli_num_rows(db::query("show columns from `$t` like '$c'")) > 0;
		return $m[$t.$c];
	}
	public static function e($s) { return mysqli_real_escape_string(db::$connect, (string)$s); }

	public static function norm($s) {
		$s = mb_strtolower(trim(html_entity_decode(strip_tags((string)$s), ENT_QUOTES, 'UTF-8')), 'UTF-8');
		$s = str_replace(['ё', '–', '—'], ['е', '-', '-'], $s);
		return preg_replace('/\s+/u', ' ', $s);
	}

	// Тағам шаблондары (бекітілгені)
	public static function all() {
		static $all = null;
		if ($all !== null) return $all;
		$all = [];
		$q = db::query("select id, name_ru, cat, keys_ru from grammovka_tpl where arh = 0".(self::semi_ready() ? " and kind = 'dish'" : "")." order by number, id");
		while ($r = mysqli_fetch_assoc($q)) $all[$r['id']] = $r;
		return $all;
	}

	// Дайындама шаблондары: бекітілгені (arh 0) және ЖИ жасаған, әлі тексерілмегені (arh 2)
	public static $semis = null; // кэш; жаңа шаблон сақталса null қойылады
	public static function semis() {
		if (self::$semis !== null) return self::$semis;
		self::$semis = [];
		if (!self::semi_ready()) return self::$semis;
		$q = db::query("select id, name_ru, keys_ru, unit, yield_qty, ai_note, arh from grammovka_tpl where kind = 'semi' and arh in (0, 2) order by arh, number, id");
		while ($r = mysqli_fetch_assoc($q)) self::$semis[$r['id']] = $r;
		return self::$semis;
	}

	// Атауы бойынша дайындама шаблоны: атауы не іздеу сөзі дәл сәйкес. Бекітілгені бірінші.
	public static function semi_find($name) {
		$n = self::norm($name);
		if ($n === '') return 0;
		$best = 0;
		foreach (self::semis() as $t) {
			if (self::norm($t['name_ru']) === $n) return (int)$t['id'];
			if (!$best) foreach (explode('|', (string)$t['keys_ru']) as $k) if (self::norm($k) === $n) { $best = (int)$t['id']; break; }
		}
		return $best;
	}

	// Дайындама шаблондарын іздеу (попаптағы тізім)
	public static function semi_search($q, $limit = 8) {
		$n = self::norm($q); $res = [];
		foreach (self::semis() as $t) {
			$s = self::norm($t['name_ru'].' '.$t['keys_ru']);
			if ($n !== '' && mb_strpos($s, $n) === false) continue;
			$sc = $n === '' ? 0 : (self::norm($t['name_ru']) === $n ? 100 : (mb_strpos(self::norm($t['name_ru']), $n) === 0 ? 50 : 10));
			$res[] = ['id' => (int)$t['id'], 'name' => $t['name_ru'], 'unit' => (string)$t['unit'], 'yield' => (float)$t['yield_qty'], 'note' => (string)$t['ai_note'], 'draft' => $t['arh'] == 2 ? 1 : 0, 'sc' => $sc - $t['arh']];
		}
		usort($res, function ($a, $b) { return $b['sc'] - $a['sc'] ?: strcmp($a['name'], $b['name']); });
		return array_slice($res, 0, $limit);
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

	// Шаблонның 3 нұсқасы: [variant => [[name, unit, qty, semi], ...]]. semi = дайындама шаблонының id-і (0 = шикізат)
	public static function vars($tpl_id) {
		$tpl_id = (int)$tpl_id; $v = [1 => [], 2 => [], 3 => []];
		$sc = self::semi_ready() ? ', semi_id' : '';
		$q = db::query("select variant, name_ru, unit, quantity$sc from grammovka_tpl_item where tpl_id = '$tpl_id' order by variant, number, id");
		while ($r = mysqli_fetch_assoc($q)) {
			$s = (int)@$r['semi_id'];
			if (!$s && $sc) $s = self::semi_find($r['name_ru']);
			if ($s == $tpl_id) $s = 0;
			$v[(int)$r['variant']][] = ['name' => $r['name_ru'], 'unit' => $r['unit'], 'qty' => (float)$r['quantity'], 'semi' => $s];
		}
		return $v;
	}
	// Таңдалған нұсқа бос болса — Стандарт, ол да бос болса — Эконом (Премиум толтырылмауы мүмкін)
	public static function var_items($tpl_id, $variant) {
		$v = self::vars($tpl_id);
		foreach ([(int)$variant, 2, 1, 3] as $k) if (!empty($v[$k])) return $v[$k];
		return [];
	}
	public static function tpl($id) {
		$id = (int)$id;
		return mysqli_fetch_assoc(db::query("select * from grammovka_tpl where id = '$id'"));
	}

	public static function has_items($pid) {
		$pid = (int)$pid;
		return mysqli_num_rows(db::query("select id from product_item where product_id = '$pid' and comp_id > 0 limit 1")) > 0;
	}

	// Жаңа шикізаттың бағасы: басқа компаниялардағы сол аттас заттың медианасы, болмаса ортақ кэш (бұрын ЖИ бағалаған)
	public static function price_guess($cmp, $name, $unit) {
		$cmp = (int)$cmp; $e = self::e($name); $u = self::e($unit); $p = [];
		// ЖИ бағалаған шикізат (оған байланған құрамның price_src = ai) медианаға кірмейді
		$ai = self::col('product_comp', 'price_src');
		$q = db::query("select r.price from product_raw r where r.company_id <> '$cmp' and lower(r.name_ru) = lower('$e') and r.unit = '$u' and r.price > 0
			".($ai ? "and not exists (select 1 from product_comp a where a.raw_id = r.id and a.price_src = 'ai')" : "")."
			union all select c.price from product_comp c where c.company_id <> '$cmp' and lower(c.name_ru) = lower('$e') and c.unit = '$u' and c.price > 0 and (c.raw_id is null or c.raw_id = 0)
			".($ai ? "and (c.price_src is null or c.price_src <> 'ai')" : "")." and not exists (select 1 from product_comp_part x where x.comp_id = c.id)");
		while ($q && $r = mysqli_fetch_row($q)) $p[] = (int)$r[0];
		if ($p) { sort($p); return [$p[(int)floor((count($p) - 1) / 2)], 'db']; }
		$t = db::query("show tables like 'grammovka_price'");
		if ($t && mysqli_num_rows($t) && ($r = mysqli_fetch_row(db::query("select price from grammovka_price where name_ru = '$e' and unit = '$u' and price > 0")))) return [(int)$r[0], 'ai'];
		return [0, ''];
	}

	// Компанияның құрамын атымен табу, жоқ болса ашу. $semi = дайындама шаблоны (0 болса атымен ізделеді).
	// Дайындама жаңадан ашылса не бұрын бос (бағасыз, рецептсіз) ашылған болса — шаблоннан құрамы толтырылады.
	public static function comp_id($cmp, $name, $unit, &$created = 0, $variant = 2, $semi = 0, $depth = 0) {
		$cmp = (int)$cmp; $name = trim((string)$name); $e = self::e($name);
		if ($name === '') return 0;
		// $semi < 0: міндетті түрде шикізат (дайындама өз шаблонының ішінде қайта дайындама болмайды)
		$semi = (int)$semi < 0 ? 0 : ((int)$semi ?: (self::semi_ready() ? self::semi_find($name) : 0));
		$pc = self::col('product_comp', 'price_src') ? ', price_src' : '';
		$q = db::query("select id, raw_id, price, kind$pc, (select count(*) from product_comp_part p where p.comp_id = c.id) parts from product_comp c
			where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e')) order by parts desc, id limit 1");
		if ($q && ($r = mysqli_fetch_assoc($q))) {
			$id = (int)$r['id'];
			// бұрын закупсыз ашылған шикізат (kind raw, рецептсіз) — закупқа байланады, бағасы сақталады
			if ((!$semi || (int)$r['price']) && !$r['parts'] && !$r['raw_id'] && $r['kind'] == 'raw') self::link_raw($id);
			if ($semi && $depth < 5 && !$r['parts'] && !$r['raw_id'] && !(int)$r['price'] && ($t = self::tpl($semi))) {
				$y = (float)$t['yield_qty'] > 0 ? (float)$t['yield_qty'] : 1; $u = self::unit($t['unit'] ?: $unit);
				db::query("update product_comp set kind = 'semi', unit = '$u', yield_qty = '$y', ubd_dt = NOW() where id = '$id'");
				self::semi_parts($cmp, $id, $semi, $variant, $created, $depth);
			}
			return $id;
		}
		// дайындама: шаблонынан құрамымен
		if ($semi && $depth < 5 && ($t = self::tpl($semi))) {
			$y = (float)$t['yield_qty'] > 0 ? (float)$t['yield_qty'] : 1; $u = self::unit($t['unit'] ?: $unit);
			if (!db::query("insert into product_comp (company_id, name_kz, name_ru, unit, yield_qty, kind, price) values ('$cmp', '$e', '$e', '$u', '$y', 'semi', 0)")) return 0;
			$id = (int)mysqli_insert_id(db::$connect); $created++;
			self::semi_parts($cmp, $id, $semi, $variant, $created, $depth);
			return $id;
		}
		// шикізат: «+ Шикізат қосу» сияқты закупқа (product_raw) жазылады — аттасы болса соған байланады,
		// болмаса жаңасы ашылады, бағасы басқа компаниялардан / кэштен (табылмаса ЖИ бағалайды)
		$u = self::unit($unit); $price = 0; $src = '';
		$raw = self::raw_id($cmp, $name, $u, $price, $src, $new);
		if (!$raw) return 0;
		$sc = $pc && $src !== '' ? ", price_src" : ''; $sv = $sc ? ", '$src'" : '';
		if (!db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price, raw_id$sc) values ('$cmp', '$e', '$e', '$u', 'raw', '$price', '$raw'$sv)")) return 0;
		$id = (int)mysqli_insert_id(db::$connect); $created++;
		if ($new && !$price) self::$noprice[] = $id;
		return $id;
	}
	// Компанияның закуптағы шикізаты атымен, жоқ болса ашылады ($new = 1). $price, $src — бағасы және көзі (жаңасында)
	public static function raw_id($cmp, $name, $unit, &$price = 0, &$src = '', &$new = 0) {
		$cmp = (int)$cmp; $e = self::e(trim((string)$name)); $new = 0; $src = '';
		$r = mysqli_fetch_assoc(db::query("select id, price from product_raw where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e')) order by id limit 1"));
		if ($r) { $price = (int)$r['price']; return (int)$r['id']; }
		$u = self::unit($unit);
		list($price, $src) = self::price_guess($cmp, $name, $u);
		if (!db::query("insert into product_raw (company_id, name_kz, name_ru, unit, price) values ('$cmp', '$e', '$e', '$u', '".(int)$price."')")) return 0;
		$new = 1;
		return (int)mysqli_insert_id(db::$connect);
	}
	public static function unit($u) { return in_array($u, ['кг', 'л', 'шт']) ? $u : 'шт'; }

	// Закупсыз шикізат-құрамды (comp) закупқа байлау: product_raw табылады / ашылады (comp бағасымен),
	// дайындамалардағы сол құрам жолдары шикізат жолына (raw_id) ауысады — қолмен қосылғандай. Қайта іске қосуға болады.
	public static function link_raw($comp_id) {
		$comp_id = (int)$comp_id;
		$c = mysqli_fetch_assoc(db::query("select id, company_id, name_ru, name_kz, unit, price, raw_id from product_comp c where id = '$comp_id'
			and kind = 'raw' and not exists (select 1 from product_comp_part p where p.comp_id = c.id)"));
		if (!$c) return 0;
		$raw = (int)$c['raw_id'];
		if (!$raw) {
			$cmp = (int)$c['company_id']; $nm = trim($c['name_ru'] ?: $c['name_kz']); $e = self::e($nm); $u = self::unit($c['unit']); $p = (int)$c['price'];
			$r = mysqli_fetch_assoc(db::query("select id, price from product_raw where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e')) order by id limit 1"));
			if ($r) { $raw = (int)$r['id']; if (!(int)$r['price'] && $p > 0) db::query("update product_raw set price = '$p' where id = '$raw'"); }
			elseif (db::query("insert into product_raw (company_id, name_kz, name_ru, unit, price) values ('$cmp', '".self::e($c['name_kz'] ?: $nm)."', '$e', '$u', '$p')")) $raw = (int)mysqli_insert_id(db::$connect);
			if (!$raw) return 0;
			db::query("update product_comp set raw_id = '$raw', unit = '$u', ubd_dt = NOW() where id = '$comp_id'");
		}
		db::query("update product_comp_part set raw_id = '$raw', sub_comp_id = null where sub_comp_id = '$comp_id'");
		return $raw;
	}
	// Компанияның шаблон/ЖИ ашқан закупсыз шикізатын түгел байлау ($since — осы күннен ашылғандары)
	public static function link_all($cmp, $since = '2026-11-19') {
		$cmp = (int)$cmp; $s = self::e($since); $n = 0;
		$q = db::query("select id from product_comp c where company_id = '$cmp' and kind = 'raw' and (raw_id is null or raw_id = 0) and ins_dt >= '$s'
			and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by id");
		while ($r = mysqli_fetch_row($q)) if (self::link_raw($r[0])) $n++;
		if ($n) self::recalc($cmp);
		return $n;
	}

	// Дайындаманың құрамын шаблоннан жазу (бар құрамы ауыстырылады). Шикізат закупқа байланса — raw_id, әйтпесе құрам (sub_comp_id)
	public static function semi_parts($cmp, $comp_id, $tpl_id, $variant, &$created = 0, $depth = 0) {
		$comp_id = (int)$comp_id; $items = self::var_items($tpl_id, $variant);
		if (!$items) return 0;
		$rows = [];
		foreach ($items as $it) {
			$sid = self::comp_id($cmp, $it['name'], $it['unit'], $created, $variant, $it['semi'] == $tpl_id ? -1 : ($it['semi'] ?: -1), $depth + 1);
			if (!$sid || $sid == $comp_id) continue;
			$c = mysqli_fetch_assoc(db::query("select raw_id, (select count(*) from product_comp_part p where p.comp_id = c.id) parts from product_comp c where id = '$sid'"));
			$q = round(in_array($it['unit'], ['кг', 'л']) ? $it['qty'] / 1000 : $it['qty'], 3);
			if ($q <= 0) continue;
			$rows[] = $c && $c['raw_id'] && !$c['parts'] ? "('$comp_id', '".(int)$c['raw_id']."', null, '$q')" : "('$comp_id', null, '$sid', '$q')";
		}
		if (!$rows) return 0;
		db::query("delete from product_comp_part where comp_id = '$comp_id'");
		db::query("insert into product_comp_part (comp_id, raw_id, sub_comp_id, quantity) values ".implode(', ', $rows));
		return count($rows);
	}

	// Дайындама шаблонын компанияға дайындау (попап үшін): керек шикізат пен ішкі дайындамалар ашылады,
	// дайындаманың өзі сақталмайды — форма толтырылады, пайдаланушы «Сақтау» басады.
	public static function semi_form($cmp, $tpl_id, $variant) {
		$t = self::tpl($tpl_id);
		if (!$t || $t['kind'] != 'semi') return null;
		$created = 0; $parts = [];
		foreach (self::var_items($tpl_id, $variant) as $it) {
			$sid = self::comp_id($cmp, $it['name'], $it['unit'], $created, $variant, $it['semi'] == $tpl_id ? -1 : ($it['semi'] ?: -1), 1);
			if (!$sid) continue;
			$c = mysqli_fetch_assoc(db::query("select raw_id, (select count(*) from product_comp_part p where p.comp_id = c.id) parts from product_comp c where id = '$sid'"));
			$q = round(in_array($it['unit'], ['кг', 'л']) ? $it['qty'] / 1000 : $it['qty'], 4);
			$parts[] = $c && $c['raw_id'] && !$c['parts'] ? ['t' => 'r', 'id' => (int)$c['raw_id'], 'q' => $q] : ['t' => 'c', 'id' => $sid, 'q' => $q];
		}
		// компанияда аттас құрам бұрыннан болса (бос шикізат болып ашылған), форма соны өзгертеді
		$e = self::e($t['name_ru']); $cmp = (int)$cmp;
		$ex = mysqli_fetch_row(db::query("select id from product_comp where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e')) order by id limit 1"));
		return ['id' => $ex ? (int)$ex[0] : 0, 'name' => $t['name_ru'], 'unit' => self::unit($t['unit'] ?: 'кг'), 'yield' => (float)$t['yield_qty'] ?: 1,
			'parts' => $parts, 'created' => $created, 'note' => (string)@$t['ai_note'], 'draft' => $t['arh'] == 2 ? 1 : 0];
	}

	// Бір тағамға шаблон қолдану. $replace = бар құрамын ауыстыру
	public static function apply($cmp, $pid, $tpl_id, $variant, $replace = false, &$created = 0) {
		$cmp = (int)$cmp; $pid = (int)$pid; $variant = (int)$variant;
		if (!isset(self::$var_names[$variant])) return 'variant';
		if (!mysqli_num_rows(db::query("select id from product where id = '$pid' and company_id = '$cmp'"))) return 'error';
		$items = self::var_items($tpl_id, $variant); // нұсқа бос болса — Стандарт / Эконом
		if (!$items) return 'empty';
		if (self::has_items($pid)) {
			if (!$replace) return 'has';
			db::query("delete from product_item where product_id = '$pid' and comp_id > 0");
		}
		foreach ($items as $it) {
			$cid = self::comp_id($cmp, $it['name'], $it['unit'], $created, $variant, $it['semi']);
			if (!$cid) continue;
			$qty = round($it['qty'], 3);
			db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '$qty')");
		}
		self::recalc($cmp);
		return 'yes';
	}
	// жаңа дайындамалардың бағасы бірден есептелсін (тағам картасы product_comp.price-ты көрсетеді)
	public static function recalc($cmp) {
		if (!class_exists('cost')) @include_once __DIR__.'/../cost/lib.php';
		if (class_exists('cost') && cost::ready()) cost::recalc((int)$cmp);
	}

	// Граммовкасы бос тағамдар (сет пен архив емес)
	public static function empty_dishes($cmp) {
		$cmp = (int)$cmp; $rows = [];
		$set = '';
		$t = db::query("show tables like 'product_set_item'");
		if ($t && mysqli_num_rows($t)) $set = " and not exists (select 1 from product_set_item s where s.set_id = p.id)";
		// позиция / сет белгісі (бағаны болса): сеттің құрамы бөлек толтырылады
		if (self::col('product', 'is_set')) $set .= " and coalesce(p.is_set, 0) = 0";
		foreach (['ptype', 'type', 'kind'] as $c) if (self::col('product', $c)) { $set .= " and coalesce(p.$c, '') <> 'set'"; break; }
		$q = db::query("select p.id, p.name_ru, p.name_kz, c.name_ru cat".(self::col('product', 'is_set') ? ", p.is_set" : "")." from product p left join product_catalog c on c.id = p.catalog_id
			where p.company_id = '$cmp' and (p.arh is null or p.arh = 0)
			and not exists (select 1 from product_item i where i.product_id = p.id and i.comp_id > 0)$set
			order by c.number, p.number, p.id");
		while ($r = mysqli_fetch_assoc($q)) {
			// сеттер шаблонсыз: олардың құрамы «Сет құрамында»
			$nn = gtpl::norm($r['name_ru'] ?: $r['name_kz']);
			if (isset($r['is_set']) && $r['is_set'] === '0') { $rows[] = $r; continue; } // қолмен «позиция» деп белгіленген
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
		$res['noprice'] = count(self::$noprice);
		return $res;
	}

	// Компанияның рецепті жоқ дайындамалары (бұрын бағасыз шикізат болып ашылғандары да), шаблоны барлары
	public static function empty_semis($cmp) {
		$cmp = (int)$cmp; $res = [];
		if (!self::semi_ready()) return $res;
		$q = db::query("select c.id, c.name_ru, c.name_kz, c.kind from product_comp c where c.company_id = '$cmp' and (c.raw_id is null or c.raw_id = 0)
			and (c.price is null or c.price = 0 or c.kind = 'semi') and (c.kind is null or c.kind in ('raw', 'semi', 'manual'))
			and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by c.name_ru");
		while ($r = mysqli_fetch_assoc($q)) {
			$s = self::semi_find($r['name_ru'] ?: $r['name_kz']);
			if ($s) $res[] = ['id' => (int)$r['id'], 'name' => $r['name_ru'] ?: $r['name_kz'], 'tpl' => $s, 'tpl_name' => self::semis()[$s]['name_ru']];
		}
		return $res;
	}
	// Себес «Дайындамаларды шаблоннан толтыру»: бәріне бірден
	public static function semi_all($cmp, $variant, $dry = false) {
		$res = ['ok' => [], 'created' => 0];
		foreach (self::empty_semis($cmp) as $s) {
			if (!$dry) {
				$t = self::tpl($s['tpl']); $cr = 0; $id = (int)$s['id'];
				$y = (float)$t['yield_qty'] > 0 ? (float)$t['yield_qty'] : 1; $u = self::unit($t['unit'] ?: 'кг');
				db::query("update product_comp set kind = 'semi', unit = '$u', yield_qty = '$y', ubd_dt = NOW() where id = '$id'");
				if (!self::semi_parts($cmp, $id, $s['tpl'], $variant, $cr, 0)) continue;
				$res['created'] += $cr;
			}
			$res['ok'][] = ['id' => $s['id'], 'name' => $s['name'], 'tpl' => $s['tpl_name']];
		}
		if (!$dry) self::recalc($cmp);
		$res['noprice'] = count(self::$noprice);
		return $res;
	}

	// Бағасы жоқ шикізат (шаблоннан ашылған, закупқа байланбаған): ЖИ бағалайды
	public static function noprice_ids($cmp) {
		$cmp = (int)$cmp; $ids = [];
		$pc = self::col('product_comp', 'price_src') ? " and (c.price_src is null or c.price_src = '')" : '';
		$q = db::query("select c.id from product_comp c where c.company_id = '$cmp' and (c.price is null or c.price = 0)
			and (c.raw_id is null or c.raw_id = 0 or exists (select 1 from product_raw r where r.id = c.raw_id and (r.price is null or r.price = 0)))
			and (c.kind is null or c.kind = 'raw')$pc and not exists (select 1 from product_comp_part p where p.comp_id = c.id)
			and (exists (select 1 from product_item i where i.comp_id = c.id) or exists (select 1 from product_comp_part p where p.sub_comp_id = c.id)) order by c.id limit 60");
		while ($r = mysqli_fetch_row($q)) $ids[] = (int)$r[0];
		return $ids;
	}
}
