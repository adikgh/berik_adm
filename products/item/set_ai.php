<? include "../../config/core.php";

	// ЖИ: сеттің фотосына (және атауы, сипаттамасына) қарап құрамын жазады → product_set_item.
	// POST id = сет. Бар құрам ауыстырылады. Мәзір позициясына (p<id>) не готовка/тауарға (c<id>) байланады,
	// мәзірде жоқ зат product_comp (kind = 'dish', шт) болып қосылады. Шығын company_ai_log-та: set_ai.
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; };
	if (!$user_id || !$company) $out(['ok' => 0, 'error' => 'Кіру керек']);
	require_once __DIR__.'/../../config/claude.php';
	if (!claude::ready()) $out(['ok' => 0, 'stop' => 1, 'error' => 'ЖИ қосылмаған: config/ai.php ішінде ANTHROPIC_API_KEY жоқ']);

	$cmp = (int)$company; $sid = (int)@$_POST['id'];
	$e = function ($s) { return mysqli_real_escape_string(db::$connect, (string)$s); };
	$txt = function ($s) { return trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$s)))); };

	try {
		$q = db::query("select p.*, c.name_ru cat_ru, c.name_kz cat_kz from product p left join product_catalog c on c.id = p.catalog_id where p.id = '$sid' and p.company_id = '$cmp'");
		$set = $q ? mysqli_fetch_assoc($q) : null;
		if (!$set) $out(['ok' => 0, 'error' => 'Сет табылмады']);
		$name = $txt($set['name_ru'] ?: $set['name_kz']);
		if ($set['name_kz'] && $txt($set['name_kz']) !== $name) $name .= ' / '.$txt($set['name_kz']);
		$desc = $txt(@$set['description']);

		// фото: uploads ішіндегі файл не толық URL
		$img = null;
		if (!empty($set['img'])) {
			$bytes = false;
			if (preg_match('~^https?://~', $set['img'])) $bytes = @file_get_contents($set['img'], false, stream_context_create(['http' => ['timeout' => 15], 'ssl' => ['verify_peer' => false]]));
			else { $f = __DIR__.'/../../assets/uploads/products/'.basename($set['img']); if (is_file($f)) $bytes = @file_get_contents($f); }
			if ($bytes) {
				$fi = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
				$mime = $fi ? finfo_buffer($fi, $bytes) : 'image/jpeg';
				if (strpos((string)$mime, 'image/') === 0) $img = claude::image_block($bytes, $mime);
			}
		}
		if (!$img && $desc === '') $out(['ok' => 0, 'error' => 'Сеттің фотосы да, сипаттамасы да жоқ: алдымен фото қосыңыз']);

		// сеттер (қолмен белгі не құрамы бар) позиция тізіміне кірмейді
		$is_set = [];
		$q = db::query("select distinct si.set_id from product_set_item si join product p on p.id = si.set_id where p.company_id = '$cmp'");
		while ($x = mysqli_fetch_assoc($q)) $is_set[$x['set_id']] = 1;
		$kind_col = fun::has_col('product', 'is_set');

		$menu = []; $lines = [];
		$q = db::query("select p.id, p.name_kz, p.name_ru, p.price, p.description".($kind_col ? ", p.is_set" : "").", c.name_ru cat from product p left join product_catalog c on c.id = p.catalog_id
			where p.company_id = '$cmp' and p.arh = 0 and p.id <> '$sid' order by c.number, p.name_ru");
		while ($r = mysqli_fetch_assoc($q)) {
			$st = $kind_col && $r['is_set'] !== null ? (int)$r['is_set'] === 1 : isset($is_set[$r['id']]);
			if ($st) continue;
			$nm = $txt($r['name_ru'] ?: $r['name_kz']);
			if ($r['name_kz'] && $txt($r['name_kz']) !== $nm) $nm .= ' / '.$txt($r['name_kz']);
			$menu['p'.$r['id']] = $r['id'];
			$d = mb_substr($txt($r['description']), 0, 140);
			$lines[] = 'p'.$r['id'].' | '.$nm.' | '.($r['cat'] ?: '-').' | '.(int)$r['price'].' тг'.($d !== '' ? ' | '.$d : '');
		}
		$comps = []; $clines = [];
		$q = db::query("select id, name_kz, name_ru, unit from product_comp where company_id = '$cmp' order by name_ru limit 400");
		while ($r = mysqli_fetch_assoc($q)) { $comps['c'.$r['id']] = (int)$r['id']; $clines[] = 'c'.$r['id'].' | '.$txt($r['name_ru'] ?: $r['name_kz']).' ('.($r['unit'] ?: 'шт').')'; }

		$system = "Ты помогаешь кафе доставки еды (суши, роллы, пицца, фастфуд) в Казахстане заполнить состав СЕТОВ (комбо) в учётной системе.\n"
			."По фото сета, названию и описанию определи, какие позиции меню входят в сет и в каком количестве.\n"
			."Правила:\n"
			."- Количество — в ПОРЦИЯХ меню (1 ролл = 1 позиция меню, обычно 8 кусочков; если на фото 16 одинаковых кусочков — это 2 порции). Половинки роллов (4 шт) = 0.5.\n"
			."- Выбирай позицию из МЕНЮ (ref = p<id>), если такое блюдо в меню есть: сравнивай по внешнему виду (рыба сверху, икра, кунжут, запечённая шапка, темпура, нори снаружи) и по названию. Описание сета важнее фото.\n"
			."- Напитки (кола и т.п.), соусы, имбирь, васаби, палочки, коробку бери из меню (p<id>) или из списка заготовок/товаров (c<id>), если есть. Палочки, имбирь, васаби и соевый соус добавляй только если они явно указаны в описании или видны отдельно.\n"
			."- Если блюда нет ни в меню, ни в списке — ref = \"\" и дай короткое название по-русски (например «Ролл Калифорния»).\n"
			."- Не выдумывай: если на фото и в описании нельзя различить роллы, всё равно дай лучший вариант, но sure = low.\n"
			."- note: 1 короткое предложение по-русски, что видно на фото и в чём сомнение.\n\n"
			."МЕНЮ (ref | название | категория | цена | описание):\n".implode("\n", $lines)
			."\n\nЗАГОТОВКИ И ТОВАРЫ (ref | название):\n".implode("\n", $clines);
		$content = [];
		if ($img) $content[] = $img;
		$content[] = ['type' => 'text', 'text' => "Сет: «".$name."»".($set['cat_ru'] ? " (категория: ".$set['cat_ru'].")" : '').", цена ".(int)$set['price']." тг."
			.($desc !== '' ? "\nОписание: ".$desc : "\nОписания нет.").($img ? "\nФото сета приложено." : "\nФото нет, только описание.")];
		$schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['items', 'note', 'sure'], 'properties' => [
			'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['ref', 'name', 'qty'], 'properties' => [
				'ref' => ['type' => 'string'], 'name' => ['type' => 'string'], 'qty' => ['type' => 'number']]]],
			'note' => ['type' => 'string'], 'sure' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']]]];
		// модель: ортақ баптау (config/ai.php CLAUDE_MODEL). 'auto' болса алдымен арзан модель, жауап бос не сенімсіз болса қымбатырағы
		claude::$feature = 'set_ai';
		$mdl = claude::model();
		$d = claude::json($system, $content, $schema, 'low', $mdl == 'auto' ? claude::CHEAP : $mdl);
		if ($mdl == 'auto' && (!$d || !@$d['items'] || @$d['sure'] == 'low')) { $d2 = claude::json($system, $content, $schema, 'medium', claude::MID); if ($d2) $d = $d2; }
		if (!$d) $out(['ok' => 0, 'stop' => (int)preg_match('~кілт|баланс|қосылмаған~u', claude::$err), 'error' => claude::$err ?: 'ЖИ жауабы оқылмады, қайталаңыз']);

		// жазу: алдымен ескі құрам өшеді
		$rows = [];
		foreach ((array)@$d['items'] as $it) {
			$qty = round((float)@$it['qty'], 3); if ($qty <= 0) continue; if ($qty > 50) $qty = 50;
			$ref = trim((string)@$it['ref']); $nm = mb_substr($txt(@$it['name']), 0, 200);
			if (isset($menu[$ref])) $rows[] = ['p', $menu[$ref], $qty];
			elseif (isset($comps[$ref])) $rows[] = ['c', $comps[$ref], $qty];
			elseif ($nm !== '') $rows[] = ['n', $nm, $qty];
		}
		if (!$rows) $out(['ok' => 0, 'error' => 'ЖИ сет құрамын анықтай алмады'.(@$d['note'] ? ': '.$d['note'] : '')]);
		db::query("delete from product_set_item where set_id = '$sid'");
		$n = 0; $new = [];
		foreach ($rows as $r) {
			if ($r[0] == 'n') {
				$en = $e($r[1]);
				$cq = db::query("select id from product_comp where company_id = '$cmp' and (lower(name_ru) = lower('$en') or lower(name_kz) = lower('$en')) limit 1");
				if ($cq && ($c = mysqli_fetch_assoc($cq))) $cid = (int)$c['id'];
				else { db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price) values ('$cmp', '$en', '$en', 'шт', 'dish', '0')"); $cid = (int)mysqli_insert_id(db::$connect); $new[] = $r[1]; }
				if (!$cid) continue;
				$ok = db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '$cid', '{$r[2]}')");
			} elseif ($r[0] == 'p') $ok = db::query("insert into product_set_item (set_id, product_id, quantity) values ('$sid', '{$r[1]}', '{$r[2]}')");
			else $ok = db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '{$r[1]}', '{$r[2]}')");
			if ($ok) $n++;
		}
		// құрамы жазылды: енді бұл тағам сет
		if ($kind_col) db::query("update product set is_set = 1 where id = '$sid' and company_id = '$cmp' and is_set is null");
		$out(['ok' => 1, 'n' => $n, 'new' => $new, 'note' => (string)@$d['note'], 'sure' => (string)@$d['sure'], 'photo' => $img ? 1 : 0]);
	} catch (Throwable $ex) { error_log('set_ai: '.$ex->getMessage()); $out(['ok' => 0, 'error' => 'Қате, қайта көріңіз']); }
