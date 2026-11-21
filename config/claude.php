<?php
// Claude (Anthropic API) шақыру. Жобада composer жоқ, сондықтан ресми PHP SDK орнына тікелей HTTP (curl).
// API кілті тек серверде (config/ai.php), браузерге берілмейді.

if (is_file(__DIR__.'/ai.php')) require_once __DIR__.'/ai.php';

class claude {
	public static $err = '';
	public static $usage = null;

	public static function ready() { return defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY !== ''; }

	// $content — user хабарламасының блоктары (сурет, PDF, мәтін). $schema — жауаптың JSON құрылымы.
	// CLAUDE_MODEL = 'auto' (әдепкі): алдымен арзан модель, сапасы нашар болса қымбатына ауысады (waybill/ai.php)
	const CHEAP = 'claude-haiku-5-5', MID = 'claude-sonnet-5-5', TOP = 'claude-opus-5-5';
	public static function model() { return defined('CLAUDE_MODEL') && CLAUDE_MODEL ? CLAUDE_MODEL : 'auto'; }

	// 1 млн токен бағасы $: [кіріс, шығыс]; кэштен оқу кірістің 10%-ы
	public static function usd($model, $u) {
		$P = ['haiku' => [0.1, 0.5], 'sonnet' => [2, 10], 'opus' => [4, 20], 'fable' => [10, 50]];
		$k = preg_match('~(haiku|sonnet|opus|fable)~', (string)$model, $m) ? $m[1] : 'opus';
		$in = ($u['input_tokens'] ?? 0) + ($u['cache_creation_input_tokens'] ?? 0) * 1.25;
		return ($in * $P[$k][0] + ($u['cache_read_input_tokens'] ?? 0) * $P[$k][0] / 10 + ($u['output_tokens'] ?? 0) * $P[$k][1]) / 1e6;
	}

	// Әр шақыру базаға жазылады (company_ai_log): әзірлеуші панелінде «ЖИ шығыны» бетінде көрінеді.
	// $feature: қай функция шақырды (мысалы waybill). Шақырар алдында claude::$feature = '...' деп қоюға болады.
	public static $feature = 'waybill';
	public static function log($model, $u) {
		try {
			global $company, $user_id;
			$f = preg_replace('/[^a-z0-9_]/', '', strtolower((string)self::$feature)) ?: 'other';
			$v = [(int)@$company, (int)@$user_id, $f, preg_replace('/[^a-z0-9.\-]/', '', (string)$model),
				(int)($u['input_tokens'] ?? 0), (int)($u['output_tokens'] ?? 0), (int)($u['cache_read_input_tokens'] ?? 0), (int)($u['cache_creation_input_tokens'] ?? 0),
				round(self::usd($model, $u), 6)];
			$sql = "insert into company_ai_log (company_id, user_id, feature, model, input_tokens, output_tokens, cache_read, cache_write, usd)
				values ('{$v[0]}', '{$v[1]}', '{$v[2]}', '{$v[3]}', '{$v[4]}', '{$v[5]}', '{$v[6]}', '{$v[7]}', '{$v[8]}')";
			$ok = false;
			try { $ok = (bool)@db::query($sql); } catch (Throwable $e) { $ok = false; }
			if (!$ok) {
				@db::query("CREATE TABLE IF NOT EXISTS `company_ai_log` (
					`id` int(11) NOT NULL AUTO_INCREMENT, `company_id` int(11) DEFAULT NULL, `user_id` int(11) DEFAULT NULL,
					`feature` varchar(40) DEFAULT NULL, `model` varchar(60) DEFAULT NULL,
					`input_tokens` int(11) NOT NULL DEFAULT 0, `output_tokens` int(11) NOT NULL DEFAULT 0,
					`cache_read` int(11) NOT NULL DEFAULT 0, `cache_write` int(11) NOT NULL DEFAULT 0,
					`usd` decimal(10,6) NOT NULL DEFAULT 0, `ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
					PRIMARY KEY (`id`), KEY `company_id` (`company_id`), KEY `ins_dt` (`ins_dt`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Claude шақырулары: токен, баға'");
				@db::query($sql);
			}
		} catch (Throwable $e) { error_log('ai log: '.$e->getMessage()); }
	}

	public static function json($system, array $content, array $schema, $effort = 'medium', $model = null, $max_tokens = 16000) {
		self::$err = ''; self::$usage = null;
		if (!self::ready()) { self::$err = 'Claude қосылмаған (config/ai.php ішінде ANTHROPIC_API_KEY жоқ)'; return null; }
		if (!$model) $model = self::model();
		if ($model == 'auto') $model = self::TOP;
		$body = [
			'model' => $model,
			'max_tokens' => (int)$max_tokens,
			// жүйе мәтіні (шикізат тізімі) бірнеше сұрауда бірдей: кэштеледі, арзанырақ
			'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
			'output_config' => ['effort' => $effort, 'format' => ['type' => 'json_schema', 'schema' => $schema]],
			'messages' => [['role' => 'user', 'content' => $content]],
		];
		// сүзгі бас тартса, Anthropic өзі басқа модельмен қайталайды (Haiku-да бұл жоқ)
		$fb = (bool)preg_match('~^claude-(opus|sonnet|fable)-~', $model);
		if ($fb) $body['fallbacks'] = 'default';
		$ch = curl_init('https://api.anthropic.com/v1/messages');
		curl_setopt_array($ch, [
			CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 240, CURLOPT_CONNECTTIMEOUT => 20,
			CURLOPT_HTTPHEADER => array_merge([
				'content-type: application/json',
				'x-api-key: '.ANTHROPIC_API_KEY,
				'anthropic-version: 2026-10-21',
			], $fb ? ['anthropic-beta: server-side-fallback-2026-07-01'] : []),
			CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
		]);
		$res = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$cerr = curl_error($ch);
		curl_close($ch);
		if ($res === false) { self::$err = 'Claude-қа қосылу мүмкін болмады: '.$cerr; return null; }
		$j = json_decode($res, true);
		if ($code != 200) {
			$m = $j['error']['message'] ?? substr($res, 0, 300);
			self::$err = [401 => 'API кілті қате', 402 => 'Anthropic балансы бітті', 429 => 'Сұрау көп, сәлден соң қайталаңыз', 529 => 'Claude бос емес, сәлден соң қайталаңыз'][$code] ?? ('Claude қатесі '.$code);
			error_log('claude '.$code.': '.$m);
			return null;
		}
		self::$usage = $j['usage'] ?? null;
		if (self::$usage) self::$usage['model'] = $j['model'] ?? $model;
		if (self::$usage) self::log(self::$usage['model'], self::$usage);
		$stop = $j['stop_reason'] ?? '';
		if ($stop == 'refusal') { self::$err = 'Claude бұл файлды оқудан бас тартты'; return null; }
		if ($stop == 'max_tokens') { self::$err = 'Құжат тым ұзын, бөліп жіберіңіз'; return null; }
		$txt = '';
		foreach ($j['content'] ?? [] as $b) if (($b['type'] ?? '') == 'text') $txt .= $b['text'];
		$data = json_decode($txt, true);
		if (!is_array($data)) { self::$err = 'Claude жауабы оқылмады'; error_log('claude bad json: '.substr($txt, 0, 300)); return null; }
		return $data;
	}

	// Интернетте іздеп (web search) мәтін жауап алу. Жауаптың соңғы ```json``` блогы массив болып қайтады, қате болса null (self::$err).
	// $feature — company_ai_log-тағы аты, self::$usd — осы шақырудың жалпы бағасы ($)
	public static $usd = 0;
	public static function web($system, $user, $feature, $searches = 3, $model = null, $effort = 'medium', $max_tokens = 12000) {
		self::$err = ''; self::$usd = 0; self::$feature = $feature;
		if (!self::ready()) { self::$err = 'no_key'; return null; }
		$model = $model ?: self::MID;
		$messages = [['role' => 'user', 'content' => $user]]; $txt = '';
		for ($turn = 0; $turn < 4; $turn++) {
			$body = [
				'model' => $model, 'max_tokens' => $max_tokens,
				'system' => [['type' => 'text', 'text' => $system, 'cache_control' => ['type' => 'ephemeral']]],
				'output_config' => ['effort' => $effort], 'messages' => $messages,
			];
			$fb = (bool)preg_match('~^claude-(opus|sonnet|fable)-~', $model); // сервер fallback-і Haiku-да жоқ
			if ($fb) $body['fallbacks'] = 'default';
			if ($searches > 0) $body['tools'] = [['type' => 'web_search_20260209', 'name' => 'web_search', 'max_uses' => (int)$searches]];
			$ch = curl_init(defined('CLAUDE_WEB_URL') ? CLAUDE_WEB_URL : 'https://api.anthropic.com/v1/messages'); // CLAUDE_WEB_URL тек тест үшін
			curl_setopt_array($ch, [
				CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 200, CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_HTTPHEADER => array_merge(['content-type: application/json', 'x-api-key: '.ANTHROPIC_API_KEY, 'anthropic-version: 2026-10-21'], $fb ? ['anthropic-beta: server-side-fallback-2026-07-01'] : []),
				CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
			]);
			$res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch); curl_close($ch);
			if ($res === false) { self::$err = 'connect'; error_log('claude web: '.$cerr); return null; }
			$j = json_decode($res, true);
			if ($code != 200) {
				error_log('claude web '.$feature.' '.$code.': '.($j['error']['message'] ?? substr($res, 0, 300)));
				self::$err = [401 => 'key', 402 => 'balance', 429 => 'busy', 529 => 'busy'][$code] ?? 'http';
				return null;
			}
			if (!empty($j['usage'])) { $m = $j['model'] ?? $model; self::log($m, $j['usage']); self::$usd += self::usd($m, $j['usage']); }
			foreach ($j['content'] ?? [] as $b) {
				if (($b['type'] ?? '') == 'text') $txt .= $b['text'];
				if (($b['type'] ?? '') == 'web_search_tool_result' && isset($b['content']['error_code'])) error_log('claude web '.$feature.' search error: '.$b['content']['error_code']);
			}
			$stop = $j['stop_reason'] ?? '';
			// іздеу ұзаққа созылса сервер кезекті үзеді: жауапты сол күйі қайта жібереміз, ол жалғастырады
			if ($stop == 'pause_turn') { $messages[] = ['role' => 'assistant', 'content' => $j['content']]; continue; }
			if ($stop == 'refusal') { self::$err = 'refusal'; return null; }
			if ($stop == 'max_tokens') { self::$err = 'long'; return null; }
			break;
		}
		$data = null;
		if (preg_match_all('/```(?:json)?\s*(\{.*?\})\s*```/su', $txt, $m)) $data = json_decode(end($m[1]), true);
		if (!is_array($data) && ($a = strpos($txt, '{')) !== false) $data = json_decode(substr($txt, $a, strrpos($txt, '}') - $a + 1), true);
		if (!is_array($data)) { self::$err = 'json'; error_log('claude web '.$feature.' bad json: '.mb_substr($txt, -400)); return null; }
		return $data;
	}

	// Сурет: ұзын жағы 1568px-тен үлкен болса кішірейтеміз (Claude үшін жеткілікті, арзанырақ)
	public static function image_block($bytes, $mime) {
		if (function_exists('imagecreatefromstring') && ($src = @imagecreatefromstring($bytes))) {
			$w = imagesx($src); $h = imagesy($src); $k = min(1, 1568 / max($w, $h));
			if ($k < 1 || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
				$dst = imagecreatetruecolor(max(1, (int)round($w * $k)), max(1, (int)round($h * $k)));
				imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
				imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
				ob_start(); imagejpeg($dst, null, 88); $bytes = ob_get_clean(); $mime = 'image/jpeg';
				imagedestroy($dst);
			}
			imagedestroy($src);
		}
		return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($bytes)]];
	}
	public static function pdf_block($bytes) {
		return ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($bytes)]];
	}
}
