<? include "../../config/core.php";

	// ЖИ: түрі белгісіз тағамдарды (product.is_set NULL, сет құрамы да, граммовка да жоқ) Сет / Позиция деп анықтайды.
	// Атауы, каталогы, бағасы, сипаттамасы бойынша, бір сұраумен (150-ге дейін). Шығын company_ai_log-та: kind_ai.
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; };
	if (!$user_id || !$company) $out(['ok' => 0, 'error' => 'Кіру керек']);
	if (!fun::has_col('product', 'is_set')) $out(['ok' => 0, 'error' => 'Базада product.is_set жоқ: set_kind.sql іске қосыңыз']);
	require_once __DIR__.'/../../config/claude.php';
	if (!claude::ready()) $out(['ok' => 0, 'error' => 'ЖИ қосылмаған: config/ai.php ішінде ANTHROPIC_API_KEY жоқ']);
	$cmp = (int)$company;
	$txt = function ($s) { return trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$s)))); };

	// 4 түр (сет / тағам / тікелей шикізат / қатысы жоқ) бағаны бар болса — products/cost2/kind.php (pkind::ai)
	require_once __DIR__.'/../cost2/kind.php';
	if (pkind::ready()) {
		$r = pkind::ai($cmp);
		if (!empty($r['error'])) $out(['ok' => 0, 'error' => $r['error'] == 'no_key' ? 'ЖИ қосылмаған' : (claude::$err ?: 'ЖИ жауабы оқылмады, қайталаңыз')]);
		$out(['ok' => 1, 'set' => (int)@$r['by'][1], 'one' => (int)@$r['by'][2] + (int)@$r['by'][3] + (int)@$r['by'][4], 'by' => @$r['by'], 'left' => count(pkind::unknown($cmp))]);
	}
	try {
		$where = "p.company_id = '$cmp' and p.arh = 0 and p.is_set is null
			and not exists (select 1 from product_set_item si where si.set_id = p.id)
			and not exists (select 1 from product_item i where i.product_id = p.id and i.comp_id > 0)";
		$all = (int)@mysqli_fetch_row(db::query("select count(*) from product p where $where"))[0];
		$q = db::query("select p.id, p.name_kz, p.name_ru, p.price, p.description, c.name_ru cat from product p left join product_catalog c on c.id = p.catalog_id where $where order by p.id limit 150");
		$ids = []; $lines = [];
		while ($r = mysqli_fetch_assoc($q)) {
			$nm = $txt($r['name_ru'] ?: $r['name_kz']);
			if ($r['name_kz'] && $txt($r['name_kz']) !== $nm) $nm .= ' / '.$txt($r['name_kz']);
			$d = mb_substr($txt($r['description']), 0, 160);
			$ids[(int)$r['id']] = 1;
			$lines[] = $r['id'].' | '.$nm.' | '.($r['cat'] ?: '-').' | '.(int)$r['price'].' тг'.($d !== '' ? ' | '.$d : '');
		}
		if (!$ids) $out(['ok' => 1, 'set' => 0, 'one' => 0, 'left' => 0]);

		$system = "Ты помогаешь кафе доставки еды (суши, пицца, фастфуд, кафе) в Казахстане разметить меню.\n"
			."Для каждой позиции меню определи тип:\n"
			."- set = true: СЕТ / набор / комбо / ассорти / «бокс» — несколько РАЗНЫХ блюд меню в одном заказе (например «Сет Филадельфия» из 4 роллов, «Комбо бургер + фри + кола», «Пицца + кола», «Семейный набор»).\n"
			."- set = false: ПОЗИЦИЯ — одно блюдо (ролл, пицца, бургер, суп, салат, напиток, соус, добавка), даже если в нём много ингредиентов или кусочков (8 шт ролла — это позиция).\n"
			."Подсказки: название с «+» (например «Ролл + кола») обычно сет; категория «Сеты/Комбо/Наборы» почти всегда сет; цена сильно выше обычных блюд той же кухни — признак сета.\n"
			."Ответь по всем id из списка.";
		$schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['items'], 'properties' => [
			'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['id', 'set'], 'properties' => [
				'id' => ['type' => 'integer'], 'set' => ['type' => 'boolean']]]]]];
		claude::$feature = 'kind_ai';
		// модель: ортақ баптау (config/ai.php CLAUDE_MODEL). 'auto' болса алдымен арзан модель, тізімнің көбі жауапсыз қалса қымбатырағы
		$content = [['type' => 'text', 'text' => "Меню (id | название | категория | цена | описание):\n".implode("\n", $lines)]];
		$mdl = claude::model();
		$d = claude::json($system, $content, $schema, 'low', $mdl == 'auto' ? claude::CHEAP : $mdl);
		if ($mdl == 'auto' && (!$d || count((array)@$d['items']) < count($ids) * 0.8)) { $d2 = claude::json($system, $content, $schema, 'low', claude::MID); if ($d2) $d = $d2; }
		if (!$d) $out(['ok' => 0, 'error' => claude::$err ?: 'ЖИ жауабы оқылмады, қайталаңыз']);
		$set = []; $one = [];
		foreach ((array)@$d['items'] as $it) {
			$id = (int)@$it['id']; if (!isset($ids[$id])) continue;
			if (!empty($it['set'])) $set[] = $id; else $one[] = $id;
			unset($ids[$id]);
		}
		if ($set) db::query("update product set is_set = 1 where company_id = '$cmp' and is_set is null and id in (".implode(',', $set).")");
		if ($one) db::query("update product set is_set = 0 where company_id = '$cmp' and is_set is null and id in (".implode(',', $one).")");
		$out(['ok' => 1, 'set' => count($set), 'one' => count($one), 'left' => max(0, $all - count($set) - count($one))]);
	} catch (Throwable $ex) { error_log('kind_ai: '.$ex->getMessage()); $out(['ok' => 0, 'error' => 'Қате, қайта көріңіз']); }
