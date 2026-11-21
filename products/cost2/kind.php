<?
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
		if (!$ids) return ['ok' => 1, 'n' => 0];
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
		$n = 0; foreach ($by as $k => $l) if ($l) $n += self::set($cmp, $l, $k, 'ai');
		return ['ok' => 1, 'n' => $n, 'by' => array_map('count', $by)];
	}
}
