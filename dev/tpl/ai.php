<?php
// ЖИ шаблон ұсыныстары: шаблоны жоқ тағамдардың граммовкасын Claude интернеттен (web search) іздеп, 3 нұсқа жасайды.
// Кілт пен шақыру админдегі config/claude.php арқылы (кілт тек серверде). Шығын company_ai_log-қа 'tpl_ai' деп жазылады.
require_once DEV_ADM . '/config/claude.php';
require_once DEV_ADM . '/products/tpl/lib.php';
require_once DEV_ADM . '/products/tpl/ai.php'; // gtpl_ai::ask (арзан модельден бастау), gtpl_ai::items (г/мл)

class tpl_ai {
	public static $usd = 0;

	// Сет, комбо, сусын шаблонсыз қалады (олардың құрамы бөлек)
	public static function skip($name, $cat) {
		$n = gtpl::norm($name); $c = gtpl::norm($cat);
		if (preg_match('/(^|\s)(сет|комбо|набор)/u', $c) || preg_match('/^(сет|комбо|набор)(ы|\s|$)/u', $n) || preg_match('/\s\+\s?|\+\s/u', $n)) return true;
		if (preg_match('/напит|сусын|ішімдік|соус|доп|добав/u', $c)) return true;
		return false;
	}

	// Архивте емес компаниялардағы шаблоны да, ұсынысы да жоқ тағамдар (атымен топталған)
	public static function missing() {
		$arh = (int)@mysqli_fetch_row(db::query("select count(*) from information_schema.columns where table_schema = database() and table_name = 'company_sub' and column_name = 'arh'"))[0];
		$q = db::query("select p.name_ru, p.name_kz, c.name_ru cat, co.id cid, co.name cname from product p
			join company co on co.id = p.company_id
			left join product_catalog c on c.id = p.catalog_id" . ($arh ? " left join company_sub s on s.company_id = co.id" : "") . "
			where (p.arh is null or p.arh = 0)" . ($arh ? " and coalesce(s.arh, 0) = 0" : "") . " order by co.id, c.number, p.number");
		// ұсыныс (arh = 2) бар тағамды қайта ұсынбаймыз
		$drafts = [];
		$d = db::query("select name_ru, keys_ru from grammovka_tpl where arh = 2");
		while ($r = mysqli_fetch_assoc($d)) foreach (array_merge([$r['name_ru']], explode('|', (string)$r['keys_ru'])) as $k) if (($k = gtpl::norm($k)) !== '') $drafts[$k] = 1;
		$res = [];
		while ($r = mysqli_fetch_assoc($q)) {
			$nm = trim(strip_tags($r['name_ru'] ?: $r['name_kz'])); $k = gtpl::norm($nm);
			if ($k === '' || self::skip($nm, (string)$r['cat'])) continue;
			if (isset($res[$k])) { $res[$k]['co'][$r['cid']] = $r['cname']; continue; }
			if (isset($drafts[$k]) || gtpl::match($nm, (string)$r['cat'], 1)) continue;
			$res[$k] = ['name' => $nm, 'cat' => (string)$r['cat'], 'co' => [$r['cid'] => $r['cname']]];
		}
		foreach ($res as &$r) { $r['n'] = count($r['co']); $r['co'] = implode(', ', $r['co']); }
		unset($r);
		uasort($res, function ($a, $b) { return $b['n'] - $a['n'] ?: strcmp($a['cat'], $b['cat']); });
		return array_values($res);
	}

	// Шаблоны жоқ дайындамалар: компаниялардағы рецепті бар құрамдар және шаблондардағы дайындамаға ұқсас құрамдар (соус, тесто, бульон…)
	public static function missing_semis() {
		$res = [];
		$arh = (int)@mysqli_fetch_row(db::query("select count(*) from information_schema.columns where table_schema = database() and table_name = 'company_sub' and column_name = 'arh'"))[0];
		$q = db::query("select c.name_ru, co.name cname from product_comp c join company co on co.id = c.company_id" . ($arh ? " left join company_sub s on s.company_id = co.id" : "") . "
			where (c.kind = 'semi' or exists (select 1 from product_comp_part p where p.comp_id = c.id)) and (c.kind is null or c.kind <> 'dish')" . ($arh ? " and coalesce(s.arh, 0) = 0" : "") . " order by co.id, c.name_ru");
		while ($r = mysqli_fetch_assoc($q)) {
			$k = gtpl::norm($r['name_ru']);
			if ($k === '' || gtpl::semi_find($r['name_ru'])) continue;
			if (!isset($res[$k])) $res[$k] = ['name' => trim($r['name_ru']), 'src' => []];
			$res[$k]['src'][$r['cname']] = 1;
		}
		$q = db::query("select i.name_ru, t.name_ru tn from grammovka_tpl_item i join grammovka_tpl t on t.id = i.tpl_id and t.arh in (0, 2) where (i.semi_id is null or i.semi_id = 0) group by i.name_ru, t.name_ru");
		while ($r = mysqli_fetch_assoc($q)) {
			$k = gtpl::norm($r['name_ru']);
			if (!preg_match('/(^|\s)(соус|тесто|бульон|кляр|маринад|заправка|начинка|крем)(\s|$)|готов|отварн|жарен|маринован|сырная смесь|фарш для/u', $k) || gtpl::semi_find($r['name_ru'])) continue;
			if (preg_match('/^(соус|паста) (соевый|рыбный|чили|табаско|шрирача|вустер)|кетчуп|майонез|соевый соус/u', $k)) continue; // покупные соусы
			if (!isset($res[$k])) $res[$k] = ['name' => trim($r['name_ru']), 'src' => []];
			$res[$k]['src'][t::w('tpl Template', 'Шаблон') . ': ' . $r['tn']] = 1;
		}
		foreach ($res as &$r) { $r['n'] = count($r['src']); $r['src'] = implode(', ', array_slice(array_keys($r['src']), 0, 6)) . ($r['n'] > 6 ? ' …' : ''); }
		unset($r);
		uasort($res, function ($a, $b) { return $b['n'] - $a['n'] ?: strcmp($a['name'], $b['name']); });
		return array_values($res);
	}

	// Бар шаблондағы құрам аттары: ЖИ солармен атаса, компанияның шикізатымен оңай сәйкеседі
	public static function known_items() { return gtpl_ai::known(); } // 120 жол, г/мл түрінде

	// Санаттың бір дайын шаблоны үлгі ретінде
	public static function sample($cat) {
		$e = mysqli_real_escape_string(db::$connect, (string)$cat);
		$r = mysqli_fetch_assoc(db::query("select id, name_ru, cat from grammovka_tpl where arh = 0" . ($e !== '' ? " and cat = '$e'" : "") . " order by id limit 1"))
			?: mysqli_fetch_assoc(db::query("select id, name_ru, cat from grammovka_tpl where arh = 0 order by id limit 1"));
		if (!$r) return '';
		$out = $r['name_ru'] . ' [' . $r['cat'] . "]:\n";
		foreach (gtpl::vars($r['id']) as $v => $its) {
			$out .= gtpl::$var_names[$v] . ': ' . implode(', ', array_map(function ($i) { return $i['name'] . ' ' . (float)$i['qty'] . ' ' . ($i['unit'] == 'кг' ? 'г' : ($i['unit'] == 'л' ? 'мл' : 'шт')); }, $its)) . "\n";
		}
		return $out;
	}

	// $dishes = [['name'=>, 'cat'=>], ...]. Қайтарады: [['dish','name','cat','keys','note','items'=>[['name','unit','q'=>[э,с,п]]]]] не null
	public static function suggest(array $dishes, array $cats) {
		claude::$err = ''; self::$usd = 0;
		if (!claude::ready()) { claude::$err = t::w('Claude is not connected: no ANTHROPIC_API_KEY in the admin config/ai.php', 'Claude қосылмаған: админдегі config/ai.php ішінде ANTHROPIC_API_KEY жоқ'); return null; }
		$samples = [];
		foreach ($dishes as $d) { $c = in_array($d['cat'], $cats) ? $d['cat'] : ''; $samples[$c] = 1; }
		$smp = ''; foreach (array_slice(array_keys($samples), 0, 2) as $c) $smp .= self::sample($c) . "\n";
		$system = "Ты технолог кухни доставки еды (суши, пицца, фастфуд, кафе) в Казахстане. Составляешь технологические карты (граммовку) блюд для расчёта себестоимости.\n"
			. "Для каждого блюда найди в интернете типичные рецепты и техкарты (сайты ресторанов, техкарты, рецепты; несколько источников) и составь 3 варианта одной порции:\n"
			. "1 = Эконом (меньше начинки, более дешёвые ингредиенты), 2 = Стандарт (типичная средняя порция), 3 = Премиум (больше начинки, лучшие ингредиенты).\n"
			. "Правила:\n- Единицы ТОЛЬКО \"г\", \"мл\" или \"шт\" (никаких кг/л и дробей). Пример: рис 120 г → {\"name\":\"Рис\",\"unit\":\"г\",\"q\":[100,120,140]}.\n"
			. "- Если ингредиента нет в варианте, ставь 0.\n- Называй ингредиенты так же, как в нашем списке ниже, если подходит; иначе коротко, по-русски, с заглавной буквы, без брендов.\n"
			. "- Упаковку (коробки, палочки) не включай.\n- cat: выбери из наших категорий (" . implode(', ', $cats) . "), если подходит.\n"
			. "- keys: 2-5 слов или частей названия через |, по которым блюдо узнаётся в меню (например: цезарь|caesar).\n"
			. "- name: общее название шаблона без названия ресторана и без лишних слов (\"Салат Цезарь с курицей\").\n"
			. "- note: 1 короткое предложение: на чём основана граммовка (какие источники/типичный вес порции).\n"
			. "В конце ответь ТОЛЬКО одним JSON-блоком ```json ... ``` такого вида:\n"
			. "{\"items\":[{\"dish\":\"исходное название\",\"name\":\"...\",\"cat\":\"...\",\"keys\":\"...\",\"note\":\"...\",\"ingredients\":[{\"name\":\"...\",\"unit\":\"г\",\"q\":[0,0,0]}]}]}\n"
			. "Если блюдо непонятно или это не еда — пропусти его.\n\n"
			. "Наши ингредиенты (используй эти названия): " . implode(', ', self::known_items()) . "\n\nПример нашего шаблона:\n" . $smp;
		$list = implode("\n", array_map(function ($d) { return '- ' . $d['name'] . ($d['cat'] !== '' ? ' (категория меню: ' . $d['cat'] . ')' : ''); }, $dishes));
		// алдымен арзан модель, жауабы оқылмаса / бос болса — орташа (CLAUDE_MODEL = auto). Іздеу: тағамға 2-ден.
		$data = gtpl_ai::ask($system, "Составь граммовку для блюд:\n" . $list, 'tpl_ai', 2 * count($dishes), function ($d) {
			if (!isset($d['items']) || !is_array($d['items'])) return false;
			foreach ($d['items'] as $t) if (gtpl_ai::items_ok(gtpl_ai::items(@$t['ingredients']))) return true;
			return false;
		}, 16000);
		self::$usd = claude::$usd;
		if (!$data) { claude::$err = gtpl_ai::err(); return null; }
		$out = [];
		foreach ($data['items'] as $t) {
			$items = gtpl_ai::items(@$t['ingredients']); // г/мл → кг/л жолы (грамм), кг-мен келсе ×1000
			if (!$items || trim((string)@$t['name']) === '') continue;
			$out[] = ['dish' => (string)@$t['dish'], 'name' => trim((string)$t['name']), 'cat' => trim((string)@$t['cat']), 'keys' => trim((string)@$t['keys']), 'note' => trim((string)@$t['note']), 'items' => $items];
		}
		return $out;
	}
}
