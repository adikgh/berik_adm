<?php
// ЖИ шаблон ұсыныстары: шаблоны жоқ тағамдардың граммовкасын Claude интернеттен (web search) іздеп, 3 нұсқа жасайды.
// Кілт пен шақыру админдегі config/claude.php арқылы (кілт тек серверде). Шығын company_ai_log-қа 'tpl_ai' деп жазылады.
require_once DEV_ADM . '/config/claude.php';
require_once DEV_ADM . '/products/tpl/lib.php';

class tpl_ai {
	public static $usd = 0;

	// Сет, комбо, сусын шаблонсыз қалады (олардың құрамы бөлек)
	public static function skip($name, $cat) {
		$n = gtpl::norm($name); $c = gtpl::norm($cat);
		if (preg_match('/(^|\s)(сет|комбо|набор)/u', $c) || preg_match('/^(сет|комбо|набор)\b/u', $n) || preg_match('/\s\+\s?|\+\s/u', $n)) return true;
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

	// Бар шаблондағы құрам аттары: ЖИ солармен атаса, компанияның шикізатымен оңай сәйкеседі
	public static function known_items() {
		$a = [];
		$q = db::query("select i.name_ru, i.unit, count(*) n from grammovka_tpl_item i join grammovka_tpl t on t.id = i.tpl_id and t.arh = 0 group by i.name_ru, i.unit order by n desc limit 250");
		while ($r = mysqli_fetch_assoc($q)) $a[] = $r['name_ru'] . ' (' . $r['unit'] . ')';
		return $a;
	}

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
		$smp = ''; foreach (array_slice(array_keys($samples), 0, 3) as $c) $smp .= self::sample($c) . "\n";
		$system = "Ты технолог кухни доставки еды (суши, пицца, фастфуд, кафе) в Казахстане. Составляешь технологические карты (граммовку) блюд для расчёта себестоимости.\n"
			. "Для каждого блюда найди в интернете типичные рецепты и техкарты (сайты ресторанов, техкарты, рецепты; несколько источников) и составь 3 варианта одной порции:\n"
			. "1 = Эконом (меньше начинки, более дешёвые ингредиенты), 2 = Стандарт (типичная средняя порция), 3 = Премиум (больше начинки, лучшие ингредиенты).\n"
			. "Правила:\n- Единицы: unit \"кг\" — количество в ГРАММАХ; unit \"л\" — в МИЛЛИЛИТРАХ; unit \"шт\" — в штуках. Пример: рис 120 г → {\"name\":\"Рис\",\"unit\":\"кг\",\"q\":[100,120,140]}.\n"
			. "- Если ингредиента нет в варианте, ставь 0.\n- Называй ингредиенты так же, как в нашем списке ниже, если подходит; иначе коротко, по-русски, с заглавной буквы, без брендов.\n"
			. "- Упаковку (коробки, палочки) не включай.\n- cat: выбери из наших категорий (" . implode(', ', $cats) . "), если подходит.\n"
			. "- keys: 2-5 слов или частей названия через |, по которым блюдо узнаётся в меню (например: цезарь|caesar).\n"
			. "- name: общее название шаблона без названия ресторана и без лишних слов (\"Салат Цезарь с курицей\").\n"
			. "- note: 1 короткое предложение: на чём основана граммовка (какие источники/типичный вес порции).\n"
			. "В конце ответь ТОЛЬКО одним JSON-блоком ```json ... ``` такого вида:\n"
			. "{\"items\":[{\"dish\":\"исходное название\",\"name\":\"...\",\"cat\":\"...\",\"keys\":\"...\",\"note\":\"...\",\"ingredients\":[{\"name\":\"...\",\"unit\":\"кг\",\"q\":[0,0,0]}]}]}\n"
			. "Если блюдо непонятно или это не еда — пропусти его.\n\n"
			. "Наши ингредиенты (используй эти названия): " . implode(', ', self::known_items()) . "\n\nПример нашего шаблона:\n" . $smp;
		$list = implode("\n", array_map(function ($d) { return '- ' . $d['name'] . ($d['cat'] !== '' ? ' (категория меню: ' . $d['cat'] . ')' : ''); }, $dishes));
		$model = claude::TOP;
		$messages = [['role' => 'user', 'content' => "Составь граммовку для блюд:\n" . $list]];
		$txt = '';
		claude::$feature = 'tpl_ai';
		for ($turn = 0; $turn < 4; $turn++) {
			$body = [
				'model' => $model, 'max_tokens' => 16000,
				'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
				'output_config' => ['effort' => 'medium'],
				'tools' => [['type' => 'web_search_20260209', 'name' => 'web_search', 'max_uses' => 4 * count($dishes)]],
				'messages' => $messages, 'fallbacks' => 'default',
			];
			$ch = curl_init(defined('TPL_AI_URL') ? TPL_AI_URL : 'https://api.anthropic.com/v1/messages'); // TPL_AI_URL тек тест үшін
			curl_setopt_array($ch, [
				CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 280, CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . ANTHROPIC_API_KEY, 'anthropic-version: 2026-10-21', 'anthropic-beta: server-side-fallback-2026-07-01'],
				CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
			]);
			$res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
			if ($res === false) { claude::$err = t::w('Could not reach Claude', 'Claude-қа қосылу мүмкін болмады') . ': ' . $cerr; return null; }
			$j = json_decode($res, true);
			if ($code != 200) {
				error_log('tpl_ai ' . $code . ': ' . ($j['error']['message'] ?? substr($res, 0, 300)));
				claude::$err = [401 => t::w('Wrong API key', 'API кілті қате'), 402 => t::w('Anthropic balance is empty', 'Anthropic балансы бітті'), 429 => t::w('Too many requests, try again later', 'Сұрау көп, сәлден соң қайталаңыз'), 529 => t::w('Claude is busy, try again later', 'Claude бос емес, сәлден соң қайталаңыз')][$code] ?? ('Claude ' . $code . ': ' . mb_substr((string)($j['error']['message'] ?? ''), 0, 200));
				return null;
			}
			if (!empty($j['usage'])) { $m = $j['model'] ?? $model; claude::log($m, $j['usage']); self::$usd += claude::usd($m, $j['usage']); }
			foreach ($j['content'] ?? [] as $b) if (($b['type'] ?? '') == 'text') $txt .= $b['text'];
			$stop = $j['stop_reason'] ?? '';
			// іздеу ұзаққа созылса сервер кезекті үзеді: жауапты сол күйі қайта жібереміз, ол жалғастырады
			if ($stop == 'pause_turn') { $messages[] = ['role' => 'assistant', 'content' => $j['content']]; continue; }
			if ($stop == 'refusal') { claude::$err = t::w('Claude refused', 'Claude бас тартты'); return null; }
			if ($stop == 'max_tokens') { claude::$err = t::w('Answer too long, pick fewer dishes', 'Жауап тым ұзын, азырақ тағам таңдаңыз'); return null; }
			break;
		}
		$data = null;
		if (preg_match_all('/```(?:json)?\s*(\{.*?\})\s*```/su', $txt, $m)) $data = json_decode(end($m[1]), true);
		if (!is_array($data) && ($a = strpos($txt, '{')) !== false) $data = json_decode(substr($txt, $a, strrpos($txt, '}') - $a + 1), true);
		if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) { claude::$err = t::w('Could not read the Claude answer', 'Claude жауабы оқылмады'); error_log('tpl_ai bad json: ' . mb_substr($txt, -400)); return null; }
		$out = [];
		foreach ($data['items'] as $t) {
			$items = [];
			foreach ((array)@$t['ingredients'] as $i) {
				$nm = trim((string)@$i['name']); if ($nm === '') continue;
				$u = in_array(@$i['unit'], ['кг', 'л', 'шт']) ? $i['unit'] : 'кг';
				$q = array_map(function ($x) { return max(0, round((float)$x, 3)); }, array_slice(array_pad((array)@$i['q'], 3, 0), 0, 3));
				if (array_sum($q) > 0) $items[] = ['name' => mb_strtoupper(mb_substr($nm, 0, 1)) . mb_substr($nm, 1), 'unit' => $u, 'q' => $q];
			}
			if (!$items || trim((string)@$t['name']) === '') continue;
			$out[] = ['dish' => (string)@$t['dish'], 'name' => trim((string)$t['name']), 'cat' => trim((string)@$t['cat']), 'keys' => trim((string)@$t['keys']), 'note' => trim((string)@$t['note']), 'items' => $items];
		}
		return $out;
	}
}
