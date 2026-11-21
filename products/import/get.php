<? include "../../config/core.php";

	// ЖИ мәзір импорты: AJAX, жауап JSON.
	// ?read  — фото/PDF беттері (f[]) не Instagram (Apify) / интернет (web search) → мәзір тізімі (базаға әлі жазылмайды)
	// ?add   — таңдалған жолдарды қосу: жоқ каталогтар ашылады, тек ЖОҚ тағамдар қосылады (барына тимейді), фото қиылады
	// ?fill_one — позицияға граммовка (шаблон → болмаса ЖИ рецепт), бірлік тексеріледі (кг/г қатесі түзетіледі)
	// ?fill_set — сеттің құрамы: алдымен сипаттамасынан арзан модель, сенімсіз болса фотосымен
	// ЖИ әрқашан ең арзан модельден басталады (config/ai.php CLAUDE_MODEL = auto), нашар болса қымбатына ауысады.
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); };
	if (!$user_id || !$company) $out(['ok' => 0, 'error' => 'Кіру керек']);
	require_once __DIR__.'/../../config/claude.php';
	claude::unlock(); // Apify/ЖИ ұзақ: сессияны босатамыз, басқа вкладкалар күтпесін (бұл файл $_SESSION-ға жазбайды)
	$cmp = (int)$company;
	$e = function ($s) { return mysqli_real_escape_string(db::$connect, (string)$s); };
	$txt = function ($s) { return trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$s)))); };
	// атауды салыстыру үшін: кіші әріп, ё→е, тыныс белгісіз
	$nk = function ($s) use ($txt) {
		$s = mb_strtolower(html_entity_decode($txt($s), ENT_QUOTES, 'UTF-8'), 'UTF-8');
		$s = str_replace('ё', 'е', $s);
		return trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s)));
	};
	$dir = __DIR__.'/../../assets/uploads/import/';
	$ladder = function () { $m = claude::model(); return $m == 'auto' ? [claude::CHEAP, claude::MID, claude::TOP] : [$m]; };

	// компанияның бар тағамдары (архивтегілері де): нормаланған атау → id
	$existing = function () use ($cmp, $nk) {
		$m = []; $list = []; $pimg = [];
		$q = db::query("select id, name_kz, name_ru, arh, img from product where company_id = '$cmp'");
		while ($r = mysqli_fetch_assoc($q)) {
			foreach ([$r['name_ru'], $r['name_kz']] as $n) { $k = $nk($n); if ($k !== '' && !isset($m[$k])) $m[$k] = (int)$r['id']; }
			if (!$r['arh']) $list[(int)$r['id']] = $r['name_ru'] ?: $r['name_kz'];
			$pimg[(int)$r['id']] = (string)$r['img']; // компанияның барлық тағамы: id -> фото (бос = фотосы жоқ)
		}
		return [$m, $list, $pimg];
	};

	// ---------------- 0. Instagram: Apify-да посттарды жинау (фонда), JS әр 3 секунд сайын санын сұрайды ----------------
	$apify = function ($path, $body = null) {
		$ch = curl_init('https://api.apify.com/v2/'.$path.(strpos($path, '?') ? '&' : '?').'token='.urlencode(APIFY_TOKEN));
		curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_HTTPHEADER => ['content-type: application/json']]);
		if ($body !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
		$res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
		if ($code >= 300) error_log('apify '.$code.' '.preg_replace('/\?.*/', '', $path).': '.substr((string)$res, 0, 300));
		return [json_decode((string)$res, true), $code];
	};
	// жиналған посттар: қатесіз, жаңасы алдымен (бір сет бірнеше рет жарияланса, соңғы бағасы дұрыс)
	$ig_posts = function ($ds, $fields = '') use ($apify) {
		if ($ds === '') return [null, 0];
		list($d, $code) = $apify('datasets/'.$ds.'/items?clean=1&limit=1000'.($fields ? '&fields='.$fields : ''));
		if (!is_array($d) || $code >= 300) return [null, $code];
		$d = array_values(array_filter($d, function ($p) { return is_array($p) && empty($p['error']) && (!empty($p['timestamp']) || !empty($p['caption']) || !empty($p['displayUrl'])); }));
		usort($d, function ($a, $b) { return strcmp((string)@$b['timestamp'], (string)@$a['timestamp']); });
		return [$d, $code];
	};
	if (isset($_GET['ig_start']) || isset($_GET['ig_poll'])) {
		if (!defined('APIFY_TOKEN') || APIFY_TOKEN === '') $out(['ok' => 0, 'error' => 'Instagram үшін Apify қосылмаған: config/ai.php ішіне APIFY_TOKEN жазу керек']);
		if (isset($_GET['ig_start'])) {
			$acc = trim((string)@$_POST['insta']);
			if (preg_match('~instagram\.com/([A-Za-z0-9._]+)~', $acc, $m)) $acc = $m[1];
			$acc = preg_replace('/[^A-Za-z0-9._]/', '', ltrim($acc, '@'));
			if ($acc === '') $out(['ok' => 0, 'error' => 'Instagram аккаунтын жазыңыз']);
			$n = 40; // соңғы 40 пост жеткілікті
			list($d, $code) = $apify('acts/apify~instagram-scraper/runs?timeout=600', ['directUrls' => ['https://www.instagram.com/'.$acc.'/'], 'resultsType' => 'posts', 'resultsLimit' => $n, 'addParentData' => false]);
			if ($code >= 300 || empty($d['data']['id'])) $out(['ok' => 0, 'error' => $code == 401 ? 'Apify токені қате' : ($code == 402 ? 'Apify балансы бітті' : 'Instagram-ды ашу мүмкін болмады, сәлден соң қайталаңыз')]);
			$out(['ok' => 1, 'run' => $d['data']['id'], 'ds' => $d['data']['defaultDatasetId'], 'acc' => $acc, 'n' => $n]);
		}
		// ig_poll: күйі + жиналған посттардың қысқаша тізімі (нөмір, күн, бірінші жол)
		$run = preg_replace('/[^A-Za-z0-9]/', '', (string)@$_POST['run']);
		list($d, $code) = $apify('actor-runs/'.$run);
		if ($code >= 300 || empty($d['data'])) $out(['ok' => 0, 'error' => 'Apify күйі оқылмады']);
		$st = (string)$d['data']['status'];
		list($posts) = $ig_posts((string)$d['data']['defaultDatasetId'], 'timestamp,caption,type,displayUrl,error');
		$list = [];
		foreach ((array)$posts as $p) {
			$t = trim(preg_replace('/\s+/u', ' ', strtok(trim((string)@$p['caption']) ?: ' ', "\n")));
			$list[] = ['d' => substr((string)@$p['timestamp'], 0, 10), 't' => mb_substr($t, 0, 80), 'v' => @$p['type'] == 'Video' ? 1 : 0];
		}
		$out(['ok' => 1, 'status' => $st, 'done' => !in_array($st, ['READY', 'RUNNING']), 'fail' => in_array($st, ['FAILED', 'ABORTED', 'TIMED-OUT', 'TIMING-OUT', 'ABORTING']) && !$list, 'posts' => $list]);
	}

	// ---------------- 1. оқу ----------------
	if (isset($_GET['read'])) {
		@set_time_limit(420);
		if (!claude::ready()) $out(['ok' => 0, 'error' => 'ЖИ қосылмаған: config/ai.php ішінде ANTHROPIC_API_KEY жоқ']);
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		foreach ((array)glob($dir.'*') as $f) if (is_file($f) && filemtime($f) < time() - 86400) @unlink($f); // кешегі уақытша файлдар
		$tok = bin2hex(random_bytes(8));
		$imgs = []; // [bytes, mime]
		$src = (string)@$_POST['src']; $note_src = '';

		if ($src == 'insta') {
			// посттар ig_start/ig_poll арқылы Apify-да жиналған; JS оларды бөліп (off, 20-дан) оқытады
			$ds = preg_replace('/[^A-Za-z0-9]/', '', (string)@$_POST['ds']);
			$off = max(0, (int)@$_POST['off']); $lim = min(30, max(1, (int)@$_POST['lim'] ?: 20));
			if (preg_match('/^[a-f0-9]{16}$/', (string)@$_POST['token'])) $tok = $_POST['token'];
			list($posts, $code) = $ig_posts($ds);
			if ($posts === null) $out(['ok' => 0, 'error' => $code == 401 ? 'Apify токені қате' : 'Instagram посттары оқылмады, қайталаңыз']);
			if (!$posts) $out(['ok' => 0, 'error' => 'Аккаунт табылмады не жабық (private)']);
			$total = count($posts);
			$posts = array_slice($posts, $off, $lim);
			if (!$posts) $out(['ok' => 1, 'token' => $tok, 'items' => [], 'total' => $total]);
			// әр пост: мәтіні + суреттері, барлығы 40 суретке дейін: алдымен әр посттың мұқабасы, қалғаны карусельден (3-ке дейін)
			$pp = []; $urls = []; $all = [];
			foreach ($posts as $p) {
				$c = trim((string)@$p['caption']); $us = [];
				foreach (array_merge([(string)@$p['displayUrl']], (array)@$p['images'], array_map(function ($x) { return (string)@$x['displayUrl']; }, (array)@$p['childPosts'])) as $u)
					if ($u && !in_array($u, $us)) $us[] = $u;
				if ($us || $c !== '') { $pp[] = ['date' => substr((string)@$p['timestamp'], 0, 10), 'caption' => mb_substr($c, 0, 1500), 'urls' => []]; $all[] = $us; }
			}
			for ($j = 0; $j < 3; $j++) foreach ($all as $n => $us)
				if (isset($us[$j]) && !isset($urls[$us[$j]]) && count($urls) < 40) { $pp[$n]['urls'][] = $us[$j]; $urls[$us[$j]] = 1; }
			// суреттерді параллель жүктеу
			$mh = curl_multi_init(); $hs = [];
			foreach (array_keys($urls) as $u) {
				$h = curl_init($u);
				curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_USERAGENT => 'Mozilla/5.0']);
				curl_multi_add_handle($mh, $h); $hs[$u] = $h;
			}
			do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1); } while ($run && $st == CURLM_OK);
			$got = [];
			foreach ($hs as $u => $h) { $got[$u] = (string)curl_multi_getcontent($h); curl_multi_remove_handle($mh, $h); curl_close($h); }
			curl_multi_close($mh);
			// Claude-қа: «Пост N (күні)» мәтіні, артынан сол посттың суреттері (нөмірі $imgs индексімен бірдей)
			$ig = [];
			foreach ($pp as $n => $p) {
				$ig[] = ['type' => 'text', 'text' => 'Пост '.($off + $n + 1).' ('.$p['date'].')'.($p['caption'] !== '' ? ":\n".$p['caption'] : ': без подписи')];
				foreach ($p['urls'] as $j => $u) {
					$b = $got[$u];
					if (!$b || strlen($b) > 8e6 || !($i = @getimagesizefromstring($b)) || !in_array($i['mime'], ['image/jpeg', 'image/png', 'image/webp'])) continue;
					$imgs[($off + $n) * 3 + $j] = [$b, $i['mime'], count($ig)]; $ig[] = null; // орны: сурет блогы кейін қойылады
				}
			}
			$note_src = '@'.preg_replace('/[^A-Za-z0-9._]/', '', (string)@$_POST['acc']);
		} elseif ($src == 'web') {
			// сайт / 2GIS / Wolt / Glovo сілтемесі не атауы: ЖИ интернеттен іздейді (суретсіз)
			$q0 = mb_substr(trim((string)@$_POST['insta']), 0, 300);
			if ($q0 === '') $out(['ok' => 0, 'error' => 'Сілтеме не мекеме атауын жазыңыз']);
			$system = "Ты помогаешь кафе в Казахстане перенести меню в учётную систему. Найди в интернете (web_search) АКТУАЛЬНОЕ меню заведения: сайт, Taplink, 2GIS, Wolt, Glovo, Яндекс Еда, Chocofood. Возьми самый полный источник.\n"
				."Ответь в конце ТОЛЬКО блоком ```json {\"quality\":\"good|medium|poor\",\"note\":\"источник\",\"items\":[{\"name\":\"...\",\"name_kz\":\"\",\"price\":0,\"cat\":\"...\",\"set\":false,\"desc\":\"состав\",\"set_items\":[\"...\"],\"img_url\":\"\"}]} ```\n"
				."price — целое в тенге; set=true для сетов/комбо/наборов (тогда set_items — что входит, как в меню). img_url — прямая ссылка на фото блюда, если есть, иначе пусто. Ничего не выдумывай.";
			$d = null;
			foreach ($ladder() as $mdl) {
				$d = claude::web($system, "Заведение: ".$q0, 'menu_web', 6, $mdl, 'medium', 16000);
				if ($d && count((array)@$d['items']) >= 5 && @$d['quality'] != 'poor') break;
			}
			if (!$d || !@$d['items']) $out(['ok' => 0, 'error' => 'Мәзір интернеттен табылмады. Мәзір фотосын не скринін салыңыз']);
			$items = (array)$d['items']; $note_src = (string)@$d['note'];
		} else {
			foreach ((array)@$_FILES['f']['tmp_name'] as $i => $t) {
				if (!$t || !is_uploaded_file($t) || $_FILES['f']['size'][$i] > 15e6) continue;
				$b = file_get_contents($t); $inf = @getimagesizefromstring($b);
				if ($inf && in_array($inf['mime'], ['image/jpeg', 'image/png', 'image/webp'])) $imgs[] = [$b, $inf['mime']];
				elseif (substr($b, 0, 4) == '%PDF') $imgs[] = [$b, 'application/pdf'];
				if (count($imgs) >= 16) break;
			}
			if (!$imgs) $out(['ok' => 0, 'error' => 'Мәзір фотосын не PDF файлын таңдаңыз']);
		}

		list($ex, $list, $pimg) = $existing();
		if ($src != 'web') {
			// суреттер уақытша сақталады (тағам фотосын қию үшін), Claude-қа сурет/PDF блоктары
			$content = [];
			foreach ($imgs as $k => $im) {
				if ($im[1] == 'application/pdf') { $content[] = claude::pdf_block($im[0]); continue; }
				@file_put_contents($dir.$cmp.'_'.$tok.'_'.$k.'.img', $im[0]);
				$bl = [['type' => 'text', 'text' => 'Изображение №'.$k.':'], claude::image_block($im[0], $im[1])];
				if (isset($im[2])) $ig[$im[2]] = $bl; else array_push($content, ...$bl);
			}
			// Instagram: посттар ретімен, әр мәтіннің артынан өз суреттері
			if ($src == 'insta') foreach ($ig as $b) { if (!$b) continue; if (isset($b['type'])) $content[] = $b; else array_push($content, ...$b); }
			if (!$content) $out(['ok' => 0, 'error' => 'Instagram-да сурет табылмады']);
			$have = []; foreach ($list as $id => $n) $have[] = $id.' | '.$txt($n);
			$system = "Ты переносишь МЕНЮ кафе доставки еды (Казахстан) в учётную систему. Прочитай ВСЁ меню на изображениях".($src == 'insta' ? " и в подписях постов Instagram" : "")." полностью, ничего не пропускай.\n"
				."Правила:\n- Одна позиция = одно блюдо с ценой. Варианты размера (25/30/35 см, 0.5/1 л) — отдельные позиции с размером в названии.\n"
				."- name — как в меню (по-русски, если меню на русском), name_kz — казахское название, если есть, иначе пусто. Без кавычек и лишних слов.\n"
				."- price — целое число тенге; если цены нет — 0. cat — раздел меню (Роллы, Пицца, Сеты, Напитки…).\n"
				."- set = true для сетов/комбо/наборов/ассорти (несколько разных блюд); тогда set_items — что входит, как написано (например \"Филадельфия 8 шт\"). Иначе set_items пусто.\n"
				."- desc — состав/описание из меню (коротко), иначе пусто.\n"
				."- img — номер изображения, где есть ФОТО именно этого блюда, и box — рамка фото блюда [x, y, w, h] в долях от 0 до 1 от размера изображения; если фото блюда нет — img = -1, box = [0,0,0,0].\n"
				."- same — id из списка УЖЕ ЕСТЬ, если это то же блюдо (то же название, может отличаться написание), иначе 0.\n"
				."- quality: good — всё читается; medium — часть неразборчива; poor — плохо видно или это не меню. note — 1 предложение.\n\n"
				.($src == 'insta' ? "\nInstagram: посты идут от НОВЫХ к старым, после текста поста — его изображения. Подпись и фото одного поста описывают одно и то же.\n"
					."- Одно блюдо/сет из нескольких постов (повтор, видео) — ОДНА позиция; цена — из самого нового поста, где она есть.\n"
					."- Состав сета (роллы, пиццы, допы внутри сета) — только в set_items, НЕ отдельными позициями. Отдельная позиция — только если её продают отдельно (есть своя цена в меню/посте).\n"
					."- Розыгрыши, отзывы, вакансии, контакты — не позиции. img — фото из того же поста.\n\n" : '')
				."УЖЕ ЕСТЬ В СИСТЕМЕ (id | название):\n".($have ? implode("\n", array_slice($have, 0, 600)) : '(пусто)');
			$content[] = ['type' => 'text', 'text' => 'Перечисли все позиции меню.'];
			$schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['items', 'quality', 'note'], 'properties' => [
				'quality' => ['type' => 'string', 'enum' => ['good', 'medium', 'poor']], 'note' => ['type' => 'string'],
				'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false,
					'required' => ['name', 'name_kz', 'price', 'cat', 'set', 'set_items', 'desc', 'img', 'box', 'same'], 'properties' => [
					'name' => ['type' => 'string'], 'name_kz' => ['type' => 'string'], 'price' => ['type' => 'integer'], 'cat' => ['type' => 'string'],
					'set' => ['type' => 'boolean'], 'set_items' => ['type' => 'array', 'items' => ['type' => 'string']], 'desc' => ['type' => 'string'],
					'img' => ['type' => 'integer'], 'box' => ['type' => 'array', 'items' => ['type' => 'number']], 'same' => ['type' => 'integer']]]]]];
			claude::$feature = 'menu_import';
			$d = null; $used = '';
			foreach ($ladder() as $mdl) {
				$r = claude::json($system, $content, $schema, 'medium', $mdl, 48000);
				if (!$r) { if (preg_match('~кілт|баланс|қосылмаған~u', claude::$err)) break; continue; }
				$d = $r; $used = $mdl;
				$n = count((array)$r['items']); $np = count(array_filter((array)$r['items'], function ($x) { return (int)@$x['price'] > 0; }));
				// арзан модель жеткілікті: оқылды, бағасының көбі бар
				if ($n && $r['quality'] != 'poor' && $np >= $n * 0.6) break;
			}
			if (!$d) $out(['ok' => 0, 'error' => claude::$err ?: 'ЖИ мәзірді оқи алмады, қайталаңыз']);
			$items = (array)$d['items']; $note_src = trim($note_src.' '.(string)@$d['note']);
		}

		// бар тағамдарды белгілеу (атауы дәл сәйкес не ЖИ «same» деп тапқан)
		$res = []; $seen = [];
		foreach ($items as $it) {
			$name = mb_substr($txt(@$it['name']), 0, 200); if ($name === '') continue;
			$k = $nk($name); if (isset($seen[$k])) continue; $seen[$k] = 1;
			$same = $ex[$k] ?? ($ex[$nk(@$it['name_kz'])] ?? 0);
			if (!$same && ($s = (int)@$it['same']) && isset($list[$s])) $same = $s;
			$box = array_map('floatval', array_slice(array_pad((array)@$it['box'], 4, 0), 0, 4));
			$res[] = ['name' => $name, 'name_kz' => mb_substr($txt(@$it['name_kz']), 0, 200), 'price' => max(0, (int)@$it['price']), 'cat' => mb_substr($txt(@$it['cat']), 0, 100),
				'set' => !empty($it['set']) ? 1 : 0, 'set_items' => array_values(array_filter(array_map($txt, (array)@$it['set_items']))), 'desc' => mb_substr($txt(@$it['desc']), 0, 800),
				'img' => isset($it['img']) ? (int)$it['img'] : -1, 'box' => $box, 'img_url' => (string)@$it['img_url'],
				'same' => $same, 'same_name' => $same ? (string)@$list[$same] : '', 'same_img' => $same && @$pimg[$same] !== '' ? 1 : 0];
		}
		$out(['ok' => 1, 'token' => $tok, 'items' => $res, 'note' => $note_src, 'model' => (string)@$used, 'imgs' => count($imgs), 'total' => (int)@$total]);
	}

	// ---------------- 2. қосу ----------------
	if (isset($_GET['add'])) {
		@set_time_limit(300);
		$items = json_decode((string)@$_POST['items'], true);
		if (!is_array($items) || !$items) $out(['ok' => 0, 'error' => 'Тізім бос']);
		$tok = preg_replace('/[^a-f0-9]/', '', (string)@$_POST['token']); $photo = !empty($_POST['photo']);
		$kind_col = fun::has_col('product', 'is_set');
		list($ex, , $pimg) = $existing();
		// каталогтар
		$cats = [];
		$q = db::query("select id, name_kz, name_ru from product_catalog where company_id = '$cmp' and (arh is null or arh = 0)");
		while ($r = mysqli_fetch_assoc($q)) foreach ([$r['name_ru'], $r['name_kz']] as $n) if ($nk($n) !== '') $cats[$nk($n)] = (int)$r['id'];
		$colors = ['#E8505B', '#F9A826', '#14B8A6', '#3B82F6', '#8B5CF6', '#EC4899', '#22C55E', '#F97316', '#0EA5E9', '#A855F7'];
		$new_cat = 0;
		$cat_id = function ($name) use (&$cats, $cmp, $e, $nk, $colors, &$new_cat) {
			$k = $nk($name); if ($k === '') return 0;
			if (isset($cats[$k])) return $cats[$k];
			$id = (int)mysqli_fetch_row(db::query("select max(id) from product_catalog"))[0] + 1;
			$num = (int)mysqli_fetch_row(db::query("select max(number) from product_catalog where company_id = '$cmp' and (arh is null or arh = 0)"))[0] + 1;
			$n = $e(mb_substr($name, 0, 100)); $c = $colors[$new_cat % count($colors)];
			if (!db::query("insert into product_catalog (id, name_kz, name_ru, number, company_id, type, color, level) values ('$id', '$n', '$n', '$num', '$cmp', 'main', '$c', 1)")) return 0;
			$new_cat++;
			return $cats[$k] = $id;
		};
		// фото: мәзір суретінен қию не сілтемеден жүктеу → assets/uploads/products/p{id}_{time}.jpg (800px)
		$pdir = __DIR__.'/../../assets/uploads/products/';
		if (!is_dir($pdir)) @mkdir($pdir, 0775, true);
		$save_img = function ($pid, $bytes, $box = null) use ($pdir) {
			if (!function_exists('imagecreatefromstring') || !($src = @imagecreatefromstring($bytes))) return '';
			$W = imagesx($src); $H = imagesy($src); $x = 0; $y = 0; $w = $W; $h = $H;
			if ($box) {
				list($bx, $by, $bw, $bh) = $box;
				if ($bw < 0.04 || $bh < 0.04 || $bx < 0 || $by < 0 || $bx + $bw > 1.02 || $by + $bh > 1.02) { imagedestroy($src); return ''; }
				$x = max(0, (int)(($bx - $bw * 0.03) * $W)); $y = max(0, (int)(($by - $bh * 0.03) * $H));
				$w = min($W - $x, (int)($bw * 1.06 * $W)); $h = min($H - $y, (int)($bh * 1.06 * $H));
				if ($w < 60 || $h < 60) { imagedestroy($src); return ''; }
			}
			$k = min(1, 800 / max($w, $h)); $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
			$dst = imagecreatetruecolor($nw, $nh); imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
			imagecopyresampled($dst, $src, 0, 0, $x, $y, $nw, $nh, $w, $h);
			$name = 'p'.$pid.'_'.time().'.jpg'; $ok = imagejpeg($dst, $pdir.$name, 85);
			imagedestroy($src); imagedestroy($dst);
			return $ok ? $name : '';
		};
		// тағамға фото: мәзір суретінен қию не сілтемеден
		$put_photo = function ($id, $it) use ($tok, $dir, $cmp, $save_img, $e) {
			$img = ''; $k = (int)@$it['img'];
			if ($tok && $k >= 0 && is_file($f = $dir.$cmp.'_'.$tok.'_'.$k.'.img')) $img = $save_img($id, file_get_contents($f), array_map('floatval', (array)@$it['box']));
			elseif (preg_match('~^https://~', (string)@$it['img_url'])) {
				$b = @file_get_contents($it['img_url'], false, stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: Mozilla/5.0\r\n"]]));
				if ($b && strlen($b) < 6e6 && @getimagesizefromstring($b)) $img = $save_img($id, $b);
			}
			if ($img) db::query("update product set img = '".$e($img)."' where id = '".(int)$id."'");
			return $img;
		};
		$res = []; $skip = 0; $ph = 0; $ph_ex = 0;
		foreach ($items as $it) {
			$name = mb_substr($txt(@$it['name']), 0, 200); if ($name === '') continue;
			$sid = $ex[$nk($name)] ?? (int)@$it['same'];
			if ($sid) { // бар тағамды қайта қоспаймыз; белгіленсе тек фотосын қоямыз, басқасына тимейміз
				$skip++;
				if ($photo && !empty($it['photo_only']) && isset($pimg[$sid]) && $put_photo($sid, $it)) $ph_ex++;
				continue;
			}
			$kz = mb_substr($txt(@$it['name_kz']), 0, 200) ?: $name;
			$set = !empty($it['set']) ? 1 : 0;
			$desc = mb_substr($txt(@$it['desc']), 0, 800);
			$si = array_values(array_filter(array_map($txt, (array)@$it['set_items'])));
			if ($set && $si) $desc = trim('Құрамы: '.implode(', ', $si).($desc !== '' ? '. '.$desc : ''));
			$cid = $cat_id((string)@$it['cat']);
			$id = (int)mysqli_fetch_row(db::query("select max(id) from product"))[0] + 1;
			$ok = db::query("insert into product (id, company_id, name_kz, name_ru, catalog_id, price, description, arh".($kind_col ? ", is_set" : "").")
				values ('$id', '$cmp', '".$e($kz)."', '".$e($name)."', ".($cid ? "'$cid'" : "NULL").", '".max(0, (int)@$it['price'])."', '".$e($desc)."', 0".($kind_col ? ", '$set'" : "").")");
			if (!$ok) { error_log('menu import insert: '.mysqli_error(db::$connect)); continue; }
			$ex[$nk($name)] = $id;
			$img = $photo ? $put_photo($id, $it) : '';
			if ($img) $ph++;
			$res[] = ['id' => $id, 'name' => $name, 'set' => $set, 'cat' => (string)@$it['cat'], 'img' => $img ? 1 : 0];
		}
		$out(['ok' => 1, 'added' => $res, 'skip' => $skip, 'cats' => $new_cat, 'photos' => $ph, 'photos_ex' => $ph_ex]);
	}

	// ---------------- 3. тізбек: позиция граммовкасы ----------------
	include_once __DIR__.'/../tpl/lib.php';
	$noprice = function () { return array_values(array_unique(gtpl::$noprice)); };

	// Бірлік тексеру: тағамда кг/л заттың граммы (product_item.quantity грамм/мл болуы керек), дайындамада кг (product_comp_part)
	$fix_units = function ($pid) use ($cmp) {
		$pid = (int)$pid; $fixed = 0;
		$rows = []; $q = db::query("select i.id, i.quantity, c.unit, c.id cid, c.kind, c.yield_qty from product_item i join product_comp c on c.id = i.comp_id where i.product_id = '$pid' and i.comp_id > 0");
		while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
		$kg = array_filter($rows, function ($r) { return in_array($r['unit'], ['кг', 'л']) && (float)$r['quantity'] > 0; });
		// бәрі 1-ден аз: кг-мен жазылған (0.15 кг = 150 г) → граммға
		if ($kg && !array_filter($kg, function ($r) { return (float)$r['quantity'] >= 1; })) {
			foreach ($kg as $r) { db::query("update product_item set quantity = '".round($r['quantity'] * 1000, 3)."' where id = '{$r['id']}'"); $fixed++; }
		}
		// дайындама құрамы: шығымнан 20 есе көп кг → граммен жазылған → кг-ға
		$seen = [];
		$chk = function ($cid, $depth) use (&$chk, &$seen, &$fixed) {
			if ($depth > 3 || isset($seen[$cid])) return; $seen[$cid] = 1;
			$c = mysqli_fetch_assoc(db::query("select yield_qty, unit from product_comp where id = '".(int)$cid."'"));
			$y = max(1, (float)@$c['yield_qty']);
			$q = db::query("select p.id, p.quantity, p.sub_comp_id, coalesce(r.unit, s.unit) u from product_comp_part p left join product_raw r on r.id = p.raw_id left join product_comp s on s.id = p.sub_comp_id where p.comp_id = '".(int)$cid."'");
			$ps = []; while ($p = mysqli_fetch_assoc($q)) $ps[] = $p;
			// кг заттардың жиыны шығымның 1%-ынан аз: ЖИ кг жазған, шаблон тағы 1000-ға бөлген → кг-ға қайтару
			$kgs = array_filter($ps, function ($p) { return in_array($p['u'], ['кг', 'л']) && (float)$p['quantity'] > 0; });
			$sum = array_sum(array_map(function ($p) { return (float)$p['quantity']; }, $kgs));
			if ($kgs && in_array(@$c['unit'], ['кг', 'л']) && $sum < $y * 0.01) { foreach ($kgs as $p) { db::query("update product_comp_part set quantity = '".round($p['quantity'] * 1000, 4)."' where id = '{$p['id']}'"); $fixed++; } $kgs = []; }
			foreach ($ps as $p) {
				if ($kgs && in_array($p['u'], ['кг', 'л']) && (float)$p['quantity'] > $y * 20) { db::query("update product_comp_part set quantity = '".round($p['quantity'] / 1000, 4)."' where id = '{$p['id']}'"); $fixed++; }
				if ($p['sub_comp_id']) $chk($p['sub_comp_id'], $depth + 1);
			}
		};
		foreach ($rows as $r) if ($r['kind'] == 'semi') $chk($r['cid'], 0);
		return $fixed;
	};

	if (isset($_GET['fill_one'])) {
		@set_time_limit(300);
		if (!gtpl::ready()) $out(['ok' => 0, 'error' => 'Граммовка шаблондары орнатылмаған']);
		$pid = (int)@$_POST['pid']; $v = (int)@$_POST['variant']; if (!isset(gtpl::$var_names[$v])) $v = 2;
		$d = mysqli_fetch_assoc(db::query("select p.name_ru, p.name_kz, c.name_ru cat from product p left join product_catalog c on c.id = p.catalog_id where p.id = '$pid' and p.company_id = '$cmp'"));
		if (!$d) $out(['ok' => 0, 'error' => 'Тағам табылмады']);
		if (gtpl::has_items($pid)) $out(['ok' => 1, 'result' => 'has']);
		$nm = $txt($d['name_ru'] ?: $d['name_kz']); $cr = 0; $how = ''; $usd = 0;
		// бөтелкедегі сусын: құрамы — өзі, 1 дана
		if (preg_match('/напит|сусын|ішімдік|drink/u', gtpl::norm($d['cat'])) && !preg_match('/чай|кофе|лимонад|смузи|коктейл|морс|шейк|какао|латте|капучино/u', gtpl::norm($nm))) {
			$cid = gtpl::comp_id($cmp, $nm, 'шт', $cr, $v, -1);
			if ($cid) db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', 1)");
			$r = $cid ? 'yes' : 'error'; $how = 'Сусын 1 шт';
		} else {
			$m = gtpl::match($nm, (string)$d['cat'], 1);
			$r = $m ? gtpl::apply($cmp, $pid, $m[0]['id'], $v, false, $cr) : 'none';
			if ($r == 'yes') $how = 'Шаблон: '.$m[0]['name'];
			else {
				include_once __DIR__.'/../tpl/ai.php';
				if (!claude::ready()) $out(['ok' => 0, 'error' => 'Шаблон жоқ, ЖИ қосылмаған']);
				$t = gtpl_ai::recipe($nm, 'dish', (string)$d['cat']); $usd = claude::$usd;
				if (!$t) $out(['ok' => 0, 'error' => gtpl_ai::err()]);
				$r = gtpl::apply($cmp, $pid, $t['id'], $v, false, $cr); $how = $t['new'] ? 'ЖИ рецепт (жаңа)' : 'ЖИ рецепт';
			}
		}
		$fx = $r == 'yes' ? $fix_units($pid) : 0;
		if ($fx) gtpl::recalc($cmp);
		$out(['ok' => $r == 'yes' ? 1 : 0, 'result' => $r, 'how' => $how, 'created' => $cr, 'fixed' => $fx, 'noprice' => $noprice(), 'usd' => round($usd, 3), 'error' => $r == 'yes' ? '' : 'Граммовка жазылмады']);
	}

	// ---------------- 4. тізбек: сет құрамы ----------------
	if (isset($_GET['fill_set'])) {
		@set_time_limit(300);
		if (!claude::ready()) $out(['ok' => 0, 'error' => 'ЖИ қосылмаған']);
		$sid = (int)@$_POST['pid'];
		$set = mysqli_fetch_assoc(db::query("select * from product where id = '$sid' and company_id = '$cmp'"));
		if (!$set) $out(['ok' => 0, 'error' => 'Сет табылмады']);
		if (mysqli_num_rows(db::query("select id from product_set_item where set_id = '$sid' limit 1"))) $out(['ok' => 1, 'result' => 'has']);
		$kind_col = fun::has_col('product', 'is_set');
		$menu = []; $lines = [];
		$q = db::query("select p.id, p.name_ru, p.name_kz, p.price, c.name_ru cat".($kind_col ? ", p.is_set" : "")." from product p left join product_catalog c on c.id = p.catalog_id
			where p.company_id = '$cmp' and p.arh = 0 and p.id <> '$sid' and not exists (select 1 from product_set_item s where s.set_id = p.id) order by c.number, p.name_ru");
		while ($r = mysqli_fetch_assoc($q)) {
			if ($kind_col && (int)$r['is_set'] === 1) continue;
			$menu['p'.$r['id']] = (int)$r['id'];
			$lines[] = 'p'.$r['id'].' | '.$txt($r['name_ru'] ?: $r['name_kz']).' | '.($r['cat'] ?: '-').' | '.(int)$r['price'].' тг';
		}
		$desc = $txt($set['description']);
		$img = null;
		if ($set['img'] && is_file($f = __DIR__.'/../../assets/uploads/products/'.basename($set['img']))) $img = claude::image_block(file_get_contents($f), 'image/jpeg');
		if ($desc === '' && !$img) $out(['ok' => 0, 'error' => 'Сипаттамасы да, фотосы да жоқ']);
		$system = "Заполни состав СЕТА (комбо) кафе доставки по описанию из меню".($img ? " и фото" : "").". Количество — в порциях меню (1 ролл = 1 позиция, обычно 8 шт; 4 шт = 0.5).\n"
			."Выбирай позицию из МЕНЮ (ref = p<id>) по названию; если блюда нет в меню — ref = \"\" и короткое название по-русски. sure: high/medium/low.\n\nМЕНЮ (ref | название | категория | цена):\n".implode("\n", $lines);
		$schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['items', 'sure'], 'properties' => [
			'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'required' => ['ref', 'name', 'qty'], 'properties' => [
				'ref' => ['type' => 'string'], 'name' => ['type' => 'string'], 'qty' => ['type' => 'number']]]],
			'sure' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']]]];
		claude::$feature = 'menu_import_set';
		$d = null; $L = $ladder();
		foreach ($L as $i => $mdl) {
			// алдымен тек мәтін (арзан); сенімсіз болса келесі модельге фотосымен
			$content = [];
			if ($img && ($i > 0 || $desc === '')) $content[] = $img;
			$content[] = ['type' => 'text', 'text' => 'Сет «'.$txt($set['name_ru'] ?: $set['name_kz']).'», цена '.(int)$set['price'].' тг. '.($desc !== '' ? 'Описание: '.$desc : 'Описания нет.')];
			$r = claude::json($system, $content, $schema, 'low', $mdl);
			if (!$r) continue;
			$d = $r;
			if ($r['items'] && $r['sure'] != 'low') break;
		}
		if (!$d || !$d['items']) $out(['ok' => 0, 'error' => claude::$err ?: 'Сет құрамы анықталмады']);
		$n = 0; $new = 0;
		foreach ((array)$d['items'] as $it) {
			$qty = min(50, round((float)@$it['qty'], 3)); if ($qty <= 0) continue;
			$ref = trim((string)@$it['ref']);
			if (isset($menu[$ref])) { if (db::query("insert into product_set_item (set_id, product_id, quantity) values ('$sid', '{$menu[$ref]}', '$qty')")) $n++; continue; }
			$nm = mb_substr($txt(@$it['name']), 0, 200); if ($nm === '') continue;
			$en = $e($nm);
			$c = mysqli_fetch_row(db::query("select id from product_comp where company_id = '$cmp' and (lower(name_ru) = lower('$en') or lower(name_kz) = lower('$en')) limit 1"));
			if ($c) $cid = (int)$c[0];
			else { db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price) values ('$cmp', '$en', '$en', 'шт', 'dish', '0')"); $cid = (int)mysqli_insert_id(db::$connect); $new++; }
			if ($cid && db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '$cid', '$qty')")) $n++;
		}
		if ($kind_col) db::query("update product set is_set = 1 where id = '$sid' and company_id = '$cmp'");
		gtpl::recalc($cmp);
		$out(['ok' => $n ? 1 : 0, 'n' => $n, 'new' => $new, 'sure' => (string)$d['sure'], 'error' => $n ? '' : 'Сет құрамы жазылмады']);
	}

	$out(['ok' => 0, 'error' => 'unknown']);
