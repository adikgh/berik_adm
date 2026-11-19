<?
// ЖИ толтыру: шаблоны жоқ дайындама/тағамның орташа рецептін Claude құрады (интернет + базадағы ұқсас рецептер),
// жаңа шикізаттың орташа бағасын табады. Нәтиже ортақ шаблон болып arh = 2 (тексерілмеген) сақталады:
// келесі жолы сол атау ЖИ-сіз шаблоннан келеді, әзірлеуші dev /tpl/ бетінде бекітеді.
// Шығын company_ai_log-та: semi_ai (дайындама), dish_ai (тағам), price_ai (баға).
require_once __DIR__.'/../../config/claude.php';
require_once __DIR__.'/lib.php';

class gtpl_ai {

	public static function err() {
		$e = claude::$err;
		$m = ['no_key' => t::w('tpl AI not connected', 'ЖИ қосылмаған: config/ai.php ішінде ANTHROPIC_API_KEY жоқ'), 'balance' => t::w('tpl AI balance', 'ЖИ балансы бітті'),
			'busy' => t::w('tpl AI busy', 'ЖИ бос емес, сәлден соң қайталаңыз'), 'key' => t::w('tpl AI key', 'ЖИ кілті қате'), 'json' => t::w('tpl AI bad answer', 'ЖИ жауабы оқылмады, қайталаңыз'),
			'refusal' => t::w('tpl AI refused', 'ЖИ бас тартты'), 'long' => t::w('tpl AI bad answer', 'ЖИ жауабы оқылмады, қайталаңыз')];
		return $m[$e] ?? t::w('tpl AI connect', 'ЖИ-ге қосылу мүмкін болмады, қайталаңыз');
	}

	// Атаудың негізгі сөздері (3+ әріп): «Соус спайси острый» → [соус, спайси, острый]
	public static function words($name) {
		return array_values(array_filter(preg_split('/[^\p{L}\d]+/u', gtpl::norm($name)), function ($w) { return mb_strlen($w) >= 3; }));
	}
	public static function like($col, $name) {
		$w = self::words($name);
		if (!$w) return "1 = 0";
		return implode(' and ', array_map(function ($x) use ($col) { return "lower($col) like '%".gtpl::e($x)."%'"; }, array_slice($w, 0, 3)));
	}
	public static function q($qty, $u) {
		return in_array($u, ['кг', 'л']) ? round($qty * 1000).($u == 'л' ? ' мл' : ' г') : ($qty + 0).' '.($u ?: 'шт');
	}

	// Базадағы ұқсас рецептер: архивте емес компаниялардың дайындамалары мен тағам құрамы, шаблондар
	public static function context($name) {
		$out = [];
		$arh = (int)@mysqli_fetch_row(db::query("select count(*) from information_schema.columns where table_schema = database() and table_name = 'company_sub' and column_name = 'arh'"))[0];
		$co = $arh ? " and not exists (select 1 from company_sub s where s.company_id = c.company_id and s.arh = 1)" : '';
		$q = db::query("select c.id, c.name_ru, c.unit, c.yield_qty from product_comp c where ".self::like('c.name_ru', $name)."$co
			and exists (select 1 from product_comp_part p where p.comp_id = c.id) order by c.id desc limit 5");
		while ($q && $c = mysqli_fetch_assoc($q)) {
			$ps = [];
			$p = db::query("select coalesce(r.name_ru, s.name_ru) n, coalesce(r.unit, s.unit) u, p.quantity from product_comp_part p left join product_raw r on r.id = p.raw_id left join product_comp s on s.id = p.sub_comp_id where p.comp_id = '{$c['id']}'");
			while ($x = mysqli_fetch_assoc($p)) $ps[] = $x['n'].' '.self::q($x['quantity'], $x['u']);
			$out[] = 'Заготовка «'.$c['name_ru'].'», выход '.($c['yield_qty'] + 0).' '.$c['unit'].': '.implode(', ', $ps);
		}
		$q = db::query("select p.id, p.name_ru from product p where ".self::like('p.name_ru', $name)." and (p.arh is null or p.arh = 0)
			and exists (select 1 from product_item i where i.product_id = p.id and i.comp_id > 0) order by p.id desc limit 3");
		while ($q && $d = mysqli_fetch_assoc($q)) {
			$ps = [];
			$p = db::query("select c.name_ru n, c.unit u, i.quantity from product_item i join product_comp c on c.id = i.comp_id where i.product_id = '{$d['id']}'");
			while ($x = mysqli_fetch_assoc($p)) $ps[] = $x['n'].' '.(in_array($x['u'], ['кг', 'л']) ? round($x['quantity']).($x['u'] == 'л' ? ' мл' : ' г') : ($x['quantity'] + 0).' шт');
			$out[] = 'Блюдо «'.strip_tags($d['name_ru']).'» (1 порция): '.implode(', ', $ps);
		}
		$q = db::query("select id, name_ru, kind, unit, yield_qty from grammovka_tpl where arh in (0, 2) and ".self::like('name_ru', $name)." order by arh, id limit 3");
		while ($q && $t = mysqli_fetch_assoc($q)) {
			$its = gtpl::var_items($t['id'], 2);
			$out[] = 'Наш шаблон «'.$t['name_ru'].'»'.($t['kind'] == 'semi' ? ', выход '.($t['yield_qty'] + 0).' '.$t['unit'] : ', 1 порция').': '
				.implode(', ', array_map(function ($i) { return $i['name'].' '.($i['unit'] == 'кг' ? $i['qty'].' г' : ($i['unit'] == 'л' ? $i['qty'].' мл' : $i['qty'].' шт')); }, $its));
		}
		return $out;
	}

	// Бар шаблондағы құрам аттары (ЖИ солармен атаса, компанияның шикізатымен сәйкеседі)
	public static function known() {
		$a = [];
		$q = db::query("select i.name_ru, i.unit, count(*) n from grammovka_tpl_item i join grammovka_tpl t on t.id = i.tpl_id and t.arh = 0 group by i.name_ru, i.unit order by n desc limit 200");
		while ($r = mysqli_fetch_assoc($q)) $a[] = $r['name_ru'].' ('.$r['unit'].')';
		return $a;
	}

	public static function items($list) {
		$items = [];
		foreach ((array)$list as $i) {
			$nm = trim((string)@$i['name']); if ($nm === '') continue;
			$u = in_array(@$i['unit'], ['кг', 'л', 'шт']) ? $i['unit'] : 'кг';
			$q = array_map(function ($x) { return max(0, round((float)$x, 3)); }, array_slice(array_pad((array)@$i['q'], 3, 0), 0, 3));
			if (array_sum($q) > 0) $items[] = ['name' => mb_strtoupper(mb_substr($nm, 0, 1)).mb_substr($nm, 1), 'unit' => $u, 'q' => $q];
		}
		return $items;
	}

	// Орташа рецепт. $kind = semi (дайындама) / dish (тағам). Қайтарады: ['id' => шаблон id, 'new' => жаңадан жасалған шаблондар саны] не null
	public static function recipe($name, $kind = 'semi', $cat = '') {
		$name = trim(strip_tags((string)$name));
		if ($name === '') { claude::$err = 'json'; return null; }
		$semi = $kind == 'semi';
		// бұрын жасалған (бекітілген не ЖИ жасаған) шаблон болса — ЖИ-сіз
		if ($semi && ($id = gtpl::semi_find($name))) return ['id' => $id, 'new' => 0];
		if (!$semi && ($m = gtpl::match($name, $cat, 1))) return ['id' => $m[0]['id'], 'new' => 0];
		if (!$semi) {
			$e = gtpl::e(gtpl::norm($name));
			$r = mysqli_fetch_row(db::query("select id from grammovka_tpl where arh = 2 and kind = 'dish' and (lower(name_ru) = '$e' or concat('|', lower(keys_ru), '|') like '%|$e|%') limit 1"));
			if ($r) return ['id' => (int)$r[0], 'new' => 0];
		}
		$ctx = self::context($name);
		$have = array_map(function ($t) { return $t['name_ru'].' ('.$t['unit'].')'; }, array_values(gtpl::semis()));
		$system = "Ты технолог кухни доставки еды (суши, пицца, фастфуд, кафе) в Казахстане. Составляешь техкарты для расчёта себестоимости.\n"
			.($semi ? "Нужна техкарта ЗАГОТОВКИ (полуфабриката): соус, тесто, рис для суши, бульон, маринованное мясо и т.п. Количества — на весь выход (yield).\n"
				: "Нужна граммовка ОДНОЙ ПОРЦИИ блюда.\n")
			."Обязательно сначала поищи в интернете (web_search, 1-2 запроса) типичные рецепты/техкарты (2-3 источника), учти рецепты из нашей базы (ниже) и составь СРЕДНИЙ рецепт в 3 вариантах: 1 = Эконом (дешевле), 2 = Стандарт (типичный), 3 = Премиум (лучше ингредиенты; можно нули, если не отличается).\n"
			."Правила:\n- unit \"кг\": количество в ГРАММАХ; unit \"л\": в МИЛЛИЛИТРАХ; unit \"шт\": в штуках. Если ингредиента нет в варианте — 0.\n"
			."- Воду не включай (она бесплатна), но учитывай её в выходе.\n- Упаковку не включай".($semi ? '' : ', кроме случаев, когда она есть в наших примерах')." .\n"
			."- Если ингредиент сам является заготовкой, которую кухня готовит сама (соус, тесто, рис для суши, бульон, готовая курица, кляр, маринад), поставь \"semi\": true и дай его техкарту в списке semis (со своими ingredients, unit, yield; вложенность до 2 уровней). Если такая заготовка уже есть в нашем списке — просто используй её название, техкарту не давай.\n"
			."- Названия ингредиентов — как в нашем списке, если это тот же продукт (не заменяй продукт другим: спагетти не лапша, бекон не колбаса); иначе коротко по-русски, с заглавной буквы, без брендов.\n"
			."- yield — выход заготовки в её unit (например 1 кг соуса, 2.3 кг риса, 10 л бульона).\n- keys: 2-5 вариантов названия через | для поиска.\n- note: 1 короткое предложение, на чём основано (источники, типичный выход).\n"
			."Ответь в конце ТОЛЬКО одним блоком ```json ... ``` вида:\n"
			."{\"name\":\"...\",".($semi ? "\"unit\":\"кг\",\"yield\":1," : "\"cat\":\"...\",")."\"keys\":\"...\",\"note\":\"...\",\"ingredients\":[{\"name\":\"...\",\"unit\":\"кг\",\"q\":[0,0,0],\"semi\":false}],"
			."\"semis\":[{\"name\":\"...\",\"unit\":\"кг\",\"yield\":1,\"keys\":\"...\",\"note\":\"...\",\"ingredients\":[...]}]}\n\n"
			."Наши ингредиенты: ".implode(', ', self::known())."\n\nНаши готовые заготовки (у них уже есть техкарта): ".implode(', ', $have);
		$user = ($semi ? 'Техкарта заготовки' : 'Граммовка блюда').": «".$name."»".($cat !== '' ? " (категория меню: $cat)" : '')
			."\n\nПохожие рецепты из нашей базы:\n".($ctx ? '- '.implode("\n- ", $ctx) : '(нет)');
		$d = claude::web($system, $user, $semi ? 'semi_ai' : 'dish_ai', 3);
		if (!$d) return null;
		$items = self::items(@$d['ingredients']);
		if (!$items) { claude::$err = 'json'; return null; }
		$new = 0;
		// ішкі дайындамалар алдымен (бізде жоқтары ғана)
		foreach ((array)@$d['semis'] as $s) {
			$sn = trim((string)@$s['name']); $si = self::items(@$s['ingredients']);
			if ($sn === '' || !$si || gtpl::semi_find($sn) || gtpl::norm($sn) === gtpl::norm($name)) continue;
			foreach ((array)@$s['semis'] as $s2) $d['semis'][] = $s2; // 2-деңгей
			if (self::store(0, $sn, 'Дайындама', (string)@$s['keys'], $si, 2, (string)@$s['note'], 'semi', @$s['unit'], @$s['yield'])) $new++;
		}
		$nm = trim((string)@$d['name']) ?: $name;
		$keys = array_filter(array_map('trim', explode('|', (string)@$d['keys'])));
		if (!in_array(gtpl::norm($name), array_map(['gtpl', 'norm'], $keys)) && gtpl::norm($name) !== gtpl::norm($nm)) $keys[] = $name;
		$id = $semi ? self::store(0, $nm, 'Дайындама', implode('|', $keys), $items, 2, (string)@$d['note'], 'semi', @$d['unit'], @$d['yield'])
			: self::store(0, $nm, trim((string)@$d['cat']) ?: $cat, implode('|', $keys), $items, 2, (string)@$d['note'], 'dish');
		if (!$id) { claude::$err = 'json'; return null; }
		return ['id' => $id, 'new' => $new + 1];
	}

	// Шаблонды сақтау (тағам не дайындама). $arh: null = өзгертпеу (жаңасы 0), 0 = бекітілген, 2 = ЖИ ұсынысы. Жолдар дайындама шаблонына атымен байланады.
	public static function store($id, $name, $cat, $keys, array $items, $arh = null, $note = null, $kind = 'dish', $unit = null, $yield = null) {
		$e = function ($s) { return gtpl::e(trim(strip_tags((string)$s))); };
		$id = (int)$id; $name = $e($name); $cat = $e($cat); $keys = $e(mb_substr((string)$keys, 0, 500));
		if ($name === '') return 0;
		$semi = gtpl::semi_ready();
		$set = "name_ru = '$name', cat = '$cat', keys_ru = '$keys'".($arh === null ? '' : ", arh = '".(int)$arh."'");
		if ($note !== null && gtpl::col('grammovka_tpl', 'ai_note')) $set .= ", ai_note = '".$e(mb_substr((string)$note, 0, 500))."'";
		if ($semi) {
			$k = $kind == 'semi' ? 'semi' : 'dish';
			$set .= ", kind = '$k'";
			if ($k == 'semi') $set .= ", unit = '".gtpl::unit($unit ?: 'кг')."', yield_qty = '".max(0.001, round((float)str_replace(',', '.', (string)$yield) ?: 1, 3))."'";
		}
		if ($id) db::query("update grammovka_tpl set $set, upd_dt = NOW() where id = '$id'");
		else {
			$n = (int)@mysqli_fetch_row(db::query("select max(number) from grammovka_tpl"))[0] + 1;
			db::query("insert into grammovka_tpl set $set, number = '$n'");
			$id = (int)mysqli_insert_id(db::$connect);
		}
		if (!$id) return 0;
		db::query("delete from grammovka_tpl_item where tpl_id = '$id'");
		$k = 0;
		foreach ($items as $it) {
			$nm = $e(@$it['name']); $u = in_array(@$it['unit'], ['кг', 'л', 'шт']) ? $it['unit'] : 'кг';
			if ($nm === '') continue;
			$k++;
			foreach ([1, 2, 3] as $v) {
				$qn = round((float)str_replace(',', '.', (string)@$it['q'][$v - 1]), 3);
				if ($qn > 0) db::query("insert into grammovka_tpl_item (tpl_id, variant, name_ru, unit, quantity, number) values ('$id', '$v', '$nm', '$u', '$qn', '$k')");
			}
		}
		if ($semi) self::link();
		gtpl::$semis = null;
		return $id;
	}
	// шаблон жолдарын дайындама шаблондарына атымен байлау (install_semi.sql-дағыдай)
	public static function link() {
		db::query("update grammovka_tpl_item i join grammovka_tpl s on s.kind = 'semi' and s.arh in (0, 2) and lower(s.name_ru) = lower(i.name_ru) and s.id <> i.tpl_id
			set i.semi_id = s.id where i.semi_id is null or i.semi_id = 0");
	}

	// Бағасыз шикізаттың орташа нарық бағасы (Қазақстан, көтерме / HoReCa). Компанияның бар бағасы өзгермейді (тек price = 0 жолдар).
	public static function prices($cmp, array $ids) {
		$cmp = (int)$cmp; $ids = array_slice(array_filter(array_map('intval', $ids)), 0, 60);
		if (!$ids) return ['n' => 0, 'usd' => 0];
		$rows = []; $q = db::query("select id, name_ru, unit from product_comp where company_id = '$cmp' and id in (".implode(',', $ids).") and (price is null or price = 0)");
		while ($r = mysqli_fetch_assoc($q)) $rows[$r['id']] = $r;
		if (!$rows) return ['n' => 0, 'usd' => 0];
		// алдымен базадан (басқа компаниялар, кэш): ЖИ-сіз
		$n = 0; $ask = [];
		foreach ($rows as $id => $r) {
			list($p, $src) = gtpl::price_guess($cmp, $r['name_ru'], $r['unit']);
			if ($p > 0) { self::set_price($id, $p, $src); $n++; } else $ask[$id] = $r;
		}
		$usd = 0;
		if ($ask) {
			$own = []; $q = db::query("select name_ru, unit, price from product_raw where company_id = '$cmp' and price > 0 order by id desc limit 40");
			while ($r = mysqli_fetch_assoc($q)) $own[] = $r['name_ru'].' — '.$r['price'].' тг/'.$r['unit'];
			$system = "Ты закупщик кухни доставки еды в Казахстане (Алматы/Астана). Нужна СРЕДНЯЯ текущая оптовая/HoReCa цена закупки в тенге за 1 единицу (за 1 кг, 1 л или 1 шт).\n"
				."Обязательно поищи в интернете (web_search) каждую позицию: сначала оптовые сайты (opt-product.kz, satu.kz, flagma.kz, оптовые базы), затем магазины (arbuz.kz, small.kz, magnum, kaspi). Цену за упаковку (мешок 25 кг, канистра 5 л, коробка 100 шт) пересчитай за 1 единицу.\n"
				."Бери типичную оптовую цену, а не максимальную розничную; если точного товара нет — цену ближайшего аналога. Сверяйся с закупочными ценами этого заведения ниже (они реальные).\n"
				."Ответь в конце ТОЛЬКО блоком ```json {\"items\":[{\"id\":0,\"name\":\"как в запросе\",\"unit\":\"кг\",\"price\":0,\"note\":\"источник кратко\"}]} ```. price — целое число тенге за 1 unit.";
			$user = "Позиции:\n".implode("\n", array_map(function ($r) { return '- id '.$r['id'].': '.$r['name_ru'].' (за 1 '.$r['unit'].')'; }, $ask))
				."\n\nНаши закупочные цены для ориентира:\n".($own ? implode("\n", $own) : '(нет)');
			$d = claude::web($system, $user, 'price_ai', min(8, 2 + count($ask)));
			$usd = claude::$usd;
			if (!$d) return ['n' => $n, 'usd' => $usd, 'error' => self::err()];
			$by = []; foreach ($ask as $id => $r) $by[gtpl::norm($r['name_ru'])][] = $id;
			foreach ((array)@$d['items'] as $it) {
				$p = (int)round((float)@$it['price']); $k = gtpl::norm(@$it['name']);
				if (!isset($by[$k]) && isset($ask[(int)@$it['id']])) $k = gtpl::norm($ask[(int)$it['id']]['name_ru']); // аты сәл өзгеше қайтса, id бойынша
				if ($p <= 0 || !isset($by[$k])) { if ($p <= 0) error_log('price_ai no price: '.json_encode($it, JSON_UNESCAPED_UNICODE)); continue; }
				foreach ($by[$k] as $id) { self::set_price($id, $p, 'ai'); $n++; }
				$u = $ask[$by[$k][0]]['unit'];
				db::query("insert into grammovka_price (name_ru, unit, price, src, note) values ('".gtpl::e($ask[$by[$k][0]]['name_ru'])."', '".gtpl::e($u)."', '$p', 'ai', '".gtpl::e(mb_substr((string)@$it['note'], 0, 250))."')
					on duplicate key update price = if(src = 'ai', values(price), price), note = if(src = 'ai', values(note), note), upd_dt = NOW()");
			}
		}
		gtpl::recalc($cmp);
		return ['n' => $n, 'usd' => $usd];
	}
	public static function set_price($id, $p, $src) {
		$id = (int)$id; $p = (int)$p; $s = gtpl::e($src);
		db::query("update product_comp set price = '$p'".(gtpl::col('product_comp', 'price_src') ? ", price_src = '$s'" : '').", ubd_dt = NOW() where id = '$id' and (price is null or price = 0)");
	}
}
