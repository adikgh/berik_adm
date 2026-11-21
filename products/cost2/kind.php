<?
if (!class_exists('gtpl')) require_once __DIR__.'/../tpl/lib.php';
// Тағам ТҮРІ (product.kind): 1 = сет, 2 = тағам (позиция), 3 = тікелей шикізат (дайын сатылатын: кола, су), 4 = қатысы жоқ (доплата, пакет, сервис).
// NULL = белгісіз. product.kind_src: user (қолмен — ЖИ өзгертпейді) | ai (ЖИ ұсынысы). Ескі product.is_set синхрон: 1 → 1, 2/3/4 → 0.
// 4-түр Себес тізбегінде (ЖИ-мен жалғастыру, граммовка, сет) мүлде өткізіледі; 3-түрдің граммовкасы = бір шикізат 1 шт.
class pkind {
	public static function ready() {
		static $ok = null;
		if ($ok !== null) return $ok;
		$c = db::query("show columns from product like 'kind_src'");
		return $ok = $c && mysqli_num_rows($c) > 0;
	}
	public static function names() {
		return [1 => t::w('c2 Kind set', 'Сет'), 2 => t::w('c2 Kind dish', 'Тағам'), 3 => t::w('c2 Kind goods', 'Тікелей шикізат'), 4 => t::w('c2 Kind none', 'Қатысы жоқ')];
	}
	// түрін қою (бірнеше id). $k: 1..4 не null (белгісіз). $src = ai болса — қолмен қойылғанына тимейді
	public static function set($cmp, $ids, $k, $src = 'user') {
		$cmp = (int)$cmp; $ids = array_values(array_filter(array_map('intval', (array)$ids)));
		if (!$ids) return 0;
		$k = $k === null || $k === '' ? null : max(1, min(4, (int)$k));
		$is = $k === null ? 'NULL' : ($k == 1 ? '1' : '0');
		$set = self::ready() ? ", kind = ".($k === null ? 'NULL' : "'$k'").", kind_src = ".($k === null ? 'NULL' : "'".($src == 'ai' ? 'ai' : 'user')."'") : '';
		$w = $src == 'ai' && self::ready() ? " and (kind_src is null or kind_src = 'ai')" : '';
		db::query("update product set is_set = $is$set where company_id = '$cmp' and id in (".implode(',', $ids).")$w");
		return mysqli_affected_rows(db::$connect);
	}
	// [id => kind]; kind бағаны жоқ болса is_set-тен
	public static function map($cmp) {
		$cmp = (int)$cmp; $m = [];
		$q = db::query("select id, is_set".(self::ready() ? ", kind" : "")." from product where company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_assoc($q)) $m[(int)$r['id']] = isset($r['kind']) && $r['kind'] !== null ? (int)$r['kind'] : ($r['is_set'] === null ? 0 : ((int)$r['is_set'] ? 1 : 2));
		return $m;
	}
	// түрі белгісіз (архивте емес) тағамдар: [[id, name]]
	public static function unknown($cmp) {
		$out = []; if (!self::ready()) return $out;
		$q = db::query("select id, name_ru, name_kz from product where company_id = '".(int)$cmp."' and (arh is null or arh = 0) and kind is null order by id");
		while ($q && $r = mysqli_fetch_assoc($q)) $out[] = [(int)$r['id'], trim(strip_tags($r['name_ru'] ?: $r['name_kz']))];
		return $out;
	}

	// ЖИ: белгісіз тағамдардың түрі (атауы, каталогы, бағасы, сипаттамасы, сет құрамы бар-жоғы), бір сұраумен 150-ге дейін. Арзан модель, нашар болса Sonnet.
	public static function ai($cmp) {
		$cmp = (int)$cmp;
		$pre = self::apply_rule($cmp); // доплата, пакет … → 4, ЖИ-сіз
		require_once __DIR__.'/../../config/claude.php';
		if (!claude::ready()) return ['error' => 'no_key'];
		$txt = function ($s) { return trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$s)))); };
		$q = db::query("select p.id, p.name_kz, p.name_ru, p.price, p.description, c.name_ru cat, exists (select 1 from product_set_item si where si.set_id = p.id) si,
			exists (select 1 from product_item i where i.product_id = p.id and i.comp_id > 0) pi
			from product p left join product_catalog c on c.id = p.catalog_id where p.company_id = '$cmp' and (p.arh is null or p.arh = 0) and p.kind is null order by p.id limit 150");
		$ids = []; $lines = [];
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$nm = $txt($r['name_ru'] ?: $r['name_kz']); $d = mb_substr($txt($r['description']), 0, 140);
			$ids[(int)$r['id']] = 1;
			$lines[] = $r['id'].' | '.$nm.' | '.($r['cat'] ?: '-').' | '.(int)preg_replace('/\D+/', '', (string)$r['price']).' тг'.($r['si'] ? ' | есть состав сета' : '').($r['pi'] ? ' | есть граммовка' : '').($d !== '' ? ' | '.$d : '');
		}
		if (!$ids) return ['ok' => 1, 'n' => $pre];
		$system = "Ты размечаешь меню кафе доставки еды в Казахстане. Для каждой позиции (id) определи тип kind:\n"
			."1 = СЕТ: набор/комбо/ассорти из нескольких разных блюд меню (в названии «сет», «комбо», «набор», «бокс», «+», или есть состав сета).\n"
			."2 = БЛЮДО: одно блюдо, которое кухня готовит (ролл, пицца, бургер, суп, салат, фри, соус собственного приготовления).\n"
			."3 = ГОТОВЫЙ ТОВАР: продаётся как есть, без приготовления (Кола, Пепси, вода, сок 1 л, энергетик, чипсы, мороженое в упаковке, покупной соус в порционной упаковке).\n"
			."4 = НЕ ЕДА: доплата, сервисный сбор, доставка, пакет, упаковка, коробка, чаевые, депозит, подарочный сертификат, скидка.\n"
			."Ответь по всем id из списка.";
		$schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['items'], 'properties' => [
			'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['id', 'kind'], 'properties' => [
				'id' => ['type' => 'integer'], 'kind' => ['type' => 'integer', 'enum' => [1, 2, 3, 4]]]]]]];
		claude::$feature = 'kind_ai';
		$content = [['type' => 'text', 'text' => "Меню (id | название | категория | цена | признаки):\n".implode("\n", $lines)]];
		$mdl = claude::model();
		$d = claude::json($system, $content, $schema, 'low', $mdl == 'auto' ? claude::CHEAP : $mdl);
		if ($mdl == 'auto' && (!$d || count((array)@$d['items']) < count($ids) * 0.8)) { $d2 = claude::json($system, $content, $schema, 'low', claude::MID); if ($d2) $d = $d2; }
		if (!$d) return ['error' => claude::$err ?: 'json'];
		$by = [1 => [], 2 => [], 3 => [], 4 => []];
		foreach ((array)@$d['items'] as $it) { $id = (int)@$it['id']; $k = (int)@$it['kind']; if (isset($ids[$id], $by[$k])) $by[$k][] = $id; }
		$n = $pre; foreach ($by as $k => $l) if ($l) $n += self::set($cmp, $l, $k, 'ai');
		return ['ok' => 1, 'n' => $n, 'by' => array_map('count', $by)];
	}

	// ЖИ-сіз ереже: атауы доплата / пакет / упаковка / сервис / чаевые / доставка (тағам емес) → 4 (қатысы жоқ)
	public static function rule($name) {
		$n = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags((string)$name))), 'UTF-8');
		$n = str_replace('ё', 'е', $n);
		return preg_match('/^(доплат|доп\.?\s*плат|пакет|упаковк|сервис|обслуживан|чаевы|delivery|доставк|сервисный сбор|service)/u', $n) ? 4 : 0;
	}
	// ережеге сай белгісіз / ЖИ қойған тағамдарға 4 (қолмен қойылғанына тимейді). Жаңа тағам құрылғанда да шақырылады
	public static function apply_rule($cmp, $ids = null) {
		if (!self::ready()) return 0;
		$cmp = (int)$cmp; $w = $ids ? " and id in (".implode(',', array_map('intval', (array)$ids)).")" : '';
		$q = db::query("select id, name_ru, name_kz from product where company_id = '$cmp' and (kind is null or kind_src = 'ai')$w");
		$hit = [];
		while ($q && $r = mysqli_fetch_assoc($q)) if (self::rule($r['name_ru'] ?: $r['name_kz'])) $hit[] = (int)$r['id'];
		return $hit ? self::set($cmp, $hit, 4, 'ai') : 0;
	}

	// ── Тікелей шикізат (3-түр): позиция = бір шикізат × 1 шт. product_item бір жол (шикізат-құрам арқылы).
	// Қазіргі байланыс: ['raw' => id, name, unit, price (түр бағасы болса — сол), stock, type, cid, other, rows, menu_price]
	public static function goods_info($cmp, $pid) {
		$cmp = (int)$cmp; $pid = (int)$pid; $rows = [];
		$q = db::query("select i.id, i.comp_id, i.quantity, c.raw_id, c.name_ru from product_item i join product_comp c on c.id = i.comp_id where i.product_id = '$pid' and i.comp_id > 0 order by i.id");
		while ($q && $r = mysqli_fetch_assoc($q)) $rows[] = $r;
		$lnk = null; foreach ($rows as $r) if ($r['raw_id']) { $lnk = $r; break; }
		$p = mysqli_fetch_assoc(db::query("select price from product where id = '$pid' and company_id = '$cmp'"));
		$out = ['rows' => count($rows), 'menu_price' => (int)preg_replace('/\D+/', '', (string)@$p['price'])];
		if (!$lnk) return $out + ['raw' => 0];
		$tc = gtpl::col('product_raw', 'type_id');
		$r = mysqli_fetch_assoc(db::query("select r.*".($tc ? ", t.name tname" : "")." from product_raw r".($tc ? " left join company_raw_type t on t.id = r.type_id" : "")." where r.id = '".(int)$lnk['raw_id']."'"));
		if (!$r) return $out + ['raw' => 0];
		$price = (float)$r['price'];
		if (is_file(__DIR__.'/rawtype.php')) { require_once __DIR__.'/rawtype.php'; $tp = c2type::raw_prices($cmp); if (isset($tp[(int)$r['id']])) $price = $tp[(int)$r['id']]; }
		$st = @mysqli_fetch_row(db::query("select sum(quantity) from product_raw_stock where raw_id = '".(int)$r['id']."'"));
		return $out + ['raw' => (int)$r['id'], 'name' => trim($r['name_ru'] ?: $r['name_kz']), 'unit' => (string)$r['unit'], 'price' => $price, 'stock' => $st && $st[0] !== null ? (float)$st[0] : null,
			'type' => (string)@$r['tname'], 'cid' => (int)$lnk['comp_id'], 'other' => count($rows) - 1];
	}
	// Шикізаттан іздеу: біріктіру кілті бірдейі жоғарыда, сосын атау сөздері бойынша. [[id, name, unit, price, same]]
	public static function goods_find($cmp, $q, $limit = 12) {
		$cmp = (int)$cmp; require_once __DIR__.'/rawdup.php';
		$k = c2raw::key($q); $w = array_filter(explode(' ', $k)); $res = [];
		$r = db::query("select id, name_ru, name_kz, unit, price from product_raw where company_id = '$cmp'");
		while ($r && $x = mysqli_fetch_assoc($r)) {
			$nm = trim($x['name_ru'] ?: $x['name_kz']); $xk = c2raw::key($nm); $xw = explode(' ', $xk);
			$sc = $xk === $k && $k !== '' ? 100 : 0;
			// сөз: толық сәйкес — 10; басы сәйкес — 6 (қысқа сөз 3 әріптен кем болса тек толық: «су» ≠ «сухое»)
			foreach ($w as $t) foreach ($xw as $u) {
				if ($t === '' || $u === '') continue;
				if ($t === $u) $sc += 10;
				elseif (min(mb_strlen($t), mb_strlen($u)) >= 3 && (mb_strpos($u, $t) === 0 || mb_strpos($t, $u) === 0)) $sc += 6;
			}
			if ($k === '') $sc = 1;
			if ($sc) $res[] = ['id' => (int)$x['id'], 'name' => $nm, 'unit' => (string)$x['unit'], 'price' => (float)$x['price'], 'same' => $sc >= 100 ? 1 : 0, 'sc' => $sc];
		}
		usort($res, function ($a, $b) { return [$b['sc'], $a['name']] <=> [$a['sc'], $b['name']]; });
		return array_slice(array_map(function ($x) { unset($x['sc']); return $x; }, $res), 0, $limit);
	}
	// Байлау: шикізатқа сай рецептсіз құрам (бары не жаңа), граммовка = сол × 1. Басқа жолдар болса — алдымен ['confirm' => n], $force болса ауыстырады
	public static function goods_link($cmp, $pid, $raw, $force = false) {
		$cmp = (int)$cmp; $pid = (int)$pid; $raw = (int)$raw;
		$r = mysqli_fetch_assoc(db::query("select id, name_ru, name_kz, unit, price from product_raw where id = '$raw' and company_id = '$cmp'"));
		if (!$r || !mysqli_num_rows(db::query("select id from product where id = '$pid' and company_id = '$cmp'"))) return ['error' => 'error'];
		$n = (int)@mysqli_fetch_row(db::query("select count(*) from product_item i join product_comp c on c.id = i.comp_id where i.product_id = '$pid' and i.comp_id > 0 and (c.raw_id is null or c.raw_id <> '$raw')"))[0];
		if ($n && !$force) return ['confirm' => $n];
		$c = mysqli_fetch_row(db::query("select c.id from product_comp c where c.company_id = '$cmp' and c.raw_id = '$raw' and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by c.id limit 1"));
		$cid = $c ? (int)$c[0] : 0;
		if (!$cid) {
			$nm = gtpl::e(trim($r['name_ru'] ?: $r['name_kz'])); $u = gtpl::unit($r['unit'] ?: 'шт');
			if (!db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price, raw_id) values ('$cmp', '$nm', '$nm', '$u', 'raw', '".(int)$r['price']."', '$raw')")) return ['error' => 'error'];
			$cid = (int)mysqli_insert_id(db::$connect);
		}
		db::query("delete from product_item where product_id = '$pid' and comp_id > 0");
		db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '1')");
		if (!class_exists('cost')) require_once __DIR__.'/../cost/lib.php';
		cost::recalc($cmp);
		return ['ok' => 1] + self::goods_info($cmp, $pid);
	}
	// Жаңа шикізат (атауы кілтпен бар болса — сол), бағасы базадан / ЖИ-ден (gtpl_ai::prices), сосын байлау
	public static function goods_new($cmp, $pid, $name, $unit = 'шт', $force = false) {
		$p = 0; $src = ''; $new = 0;
		$raw = gtpl::raw_id($cmp, trim(strip_tags((string)$name)), gtpl::unit($unit ?: 'шт'), $p, $src, $new);
		if (!$raw) return ['error' => 'error'];
		$res = self::goods_link($cmp, $pid, $raw, $force);
		if (!empty($res['ok']) && empty($res['price'])) {
			require_once __DIR__.'/../tpl/ai.php';
			if (claude::ready() && !empty($res['cid'])) { gtpl_ai::prices($cmp, [$res['cid']]); if (!class_exists('cost')) require_once __DIR__.'/../cost/lib.php'; cost::recalc($cmp); $res = ['ok' => 1] + self::goods_info($cmp, $pid); }
		}
		return $res;
	}
	// Түр 3-ке ауысқанда: граммовкасы жоқ болса, атауы бойынша бір кандидат (кілті бірдей) табылса — өзі байлайды
	public static function goods_auto($cmp, $pid) {
		$cmp = (int)$cmp; $pid = (int)$pid;
		if (mysqli_num_rows(db::query("select id from product_item where product_id = '$pid' and comp_id > 0 limit 1"))) return 0;
		$p = mysqli_fetch_assoc(db::query("select name_ru, name_kz from product where id = '$pid' and company_id = '$cmp'")); if (!$p) return 0;
		$c = array_values(array_filter(self::goods_find($cmp, trim(strip_tags($p['name_ru'] ?: $p['name_kz']))), function ($x) { return $x['same']; }));
		return count($c) == 1 && !empty(self::goods_link($cmp, $pid, $c[0]['id'])['ok']) ? $c[0]['id'] : 0;
	}
}
