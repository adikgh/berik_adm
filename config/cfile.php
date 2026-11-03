<?php
// Файл сақтау тек Google Drive-та: браузер файлды Drive-қа өзі жүктейді, хостингке түспейді.
// Папкалар: Berik POS / Меню фотолары | Аватаркалар | Накладнойлар | Басқа файлдар / <компания> [/ YYYY-MM].
// Базада (company_file) тек сілтемесі (drive_id) жазылады. Ашу: /files/?id=N (рұқсат тексеріледі).
// Drive: OAuth, scope drive.file — сайт тек өзі жасаған файлдарды көреді.

if (is_file(__DIR__.'/ai.php')) require_once __DIR__.'/ai.php';

class cfile {
	public static $err = '';
	const TOKEN = __DIR__.'/gdrive_token.php';
	const SCOPE = 'https://www.googleapis.com/auth/drive.file';

	public static function install() {
		db::query("CREATE TABLE IF NOT EXISTS `company_file` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`company_id` int(11) NOT NULL,
			`kind` varchar(30) NOT NULL,
			`ref_id` int(11) NOT NULL DEFAULT 0,
			`storage` varchar(10) NOT NULL,
			`drive_id` varchar(100) DEFAULT NULL,
			`path` varchar(255) DEFAULT NULL,
			`name` varchar(255) NOT NULL,
			`mime` varchar(100) DEFAULT NULL,
			`size` int(11) NOT NULL DEFAULT 0,
			`user_id` int(11) DEFAULT NULL,
			`ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			KEY `company_kind_ref` (`company_id`, `kind`, `ref_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	// ---------- Google Drive ----------
	public static function drive_conf() { return defined('GDRIVE_CLIENT_ID') && GDRIVE_CLIENT_ID !== '' && GDRIVE_CLIENT_SECRET !== ''; }
	// токен файлы: .php (браузерден ашылмайды), ішінде JSON. include емес — opcache ескі нұсқасын бермесін
	public static function tok() {
		$raw = is_file(self::TOKEN) ? (string)@file_get_contents(self::TOKEN) : '';
		$t = json_decode(substr($raw, (int)strpos($raw, "\n") + 1), true);
		return is_array($t) ? $t : [];
	}
	public static function tok_save($t) { return @file_put_contents(self::TOKEN, "<?php exit; // Google Drive токені, құпия ?>\n".json_encode($t, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false; }
	public static function drive_on() { $t = self::tok(); return self::drive_conf() && !empty($t['refresh_token']); }

	public static function auth_url($state) {
		return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
			'client_id' => GDRIVE_CLIENT_ID, 'redirect_uri' => GDRIVE_REDIRECT, 'response_type' => 'code',
			'scope' => 'openid email '.self::SCOPE, 'access_type' => 'offline', 'prompt' => 'consent', 'state' => $state,
		]);
	}

	private static function http($method, $url, $body = null, $headers = [], $sink = null) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => $sink === null, CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_HTTPHEADER => $headers]);
		if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		if ($sink) curl_setopt($ch, CURLOPT_WRITEFUNCTION, $sink);
		$res = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$e = curl_error($ch);
		curl_close($ch);
		if ($res === false) self::$err = 'Байланыс қатесі: '.$e;
		return [$code, $res];
	}

	// code → refresh_token (бір рет, қосқан кезде)
	public static function connect($code) {
		[$c, $r] = self::http('POST', 'https://oauth2.googleapis.com/token', http_build_query([
			'code' => $code, 'client_id' => GDRIVE_CLIENT_ID, 'client_secret' => GDRIVE_CLIENT_SECRET,
			'redirect_uri' => GDRIVE_REDIRECT, 'grant_type' => 'authorization_code',
		]), ['Content-Type: application/x-www-form-urlencoded']);
		$j = json_decode((string)$r, true);
		if ($c != 200 || empty($j['refresh_token'])) { self::$err = 'Google жауабы: '.($j['error_description'] ?? $j['error'] ?? $c); return false; }
		$t = ['refresh_token' => $j['refresh_token'], 'access_token' => $j['access_token'], 'expires' => time() + (int)$j['expires_in'] - 60, 'folders' => [], 'email' => ''];
		if (!empty($j['id_token'])) { $p = json_decode(base64_decode(strtr(explode('.', $j['id_token'])[1] ?? '', '-_', '+/')), true); $t['email'] = $p['email'] ?? ''; }
		return self::tok_save($t);
	}

	public static function disconnect() {
		$t = self::tok();
		if (!empty($t['refresh_token'])) self::http('POST', 'https://oauth2.googleapis.com/revoke', http_build_query(['token' => $t['refresh_token']]), ['Content-Type: application/x-www-form-urlencoded']);
		@unlink(self::TOKEN);
	}

	private static function access() {
		$t = self::tok();
		if (empty($t['refresh_token'])) { self::$err = 'Google Drive қосылмаған'; return null; }
		if (!empty($t['access_token']) && $t['expires'] > time()) return $t['access_token'];
		[$c, $r] = self::http('POST', 'https://oauth2.googleapis.com/token', http_build_query([
			'refresh_token' => $t['refresh_token'], 'client_id' => GDRIVE_CLIENT_ID, 'client_secret' => GDRIVE_CLIENT_SECRET, 'grant_type' => 'refresh_token',
		]), ['Content-Type: application/x-www-form-urlencoded']);
		$j = json_decode((string)$r, true);
		if ($c != 200 || empty($j['access_token'])) { self::$err = 'Google Drive токені жаңармады: '.($j['error_description'] ?? $j['error'] ?? $c); return null; }
		$t['access_token'] = $j['access_token']; $t['expires'] = time() + (int)$j['expires_in'] - 60;
		self::tok_save($t);
		return $t['access_token'];
	}

	private static function api($method, $url, $body = null, $ctype = 'application/json', $sink = null) {
		$a = self::access(); if (!$a) return [0, null];
		$h = ['Authorization: Bearer '.$a];
		if ($body !== null) $h[] = 'Content-Type: '.$ctype;
		return self::http($method, $url, $body, $h, $sink);
	}

	// папка жолы: ['c13', 'Накладной', '2026-11'] → Drive id (кэшпен)
	private static function folder($path) {
		$t = self::tok(); $key = implode('/', array_merge([defined('GDRIVE_ROOT') ? GDRIVE_ROOT : 'Berik POS'], $path));
		if (!empty($t['folders'][$key])) return $t['folders'][$key];
		$parent = 'root'; $acc = [];
		foreach (array_merge([defined('GDRIVE_ROOT') ? GDRIVE_ROOT : 'Berik POS'], $path) as $name) {
			$acc[] = $name; $k = implode('/', $acc);
			if (!empty($t['folders'][$k])) { $parent = $t['folders'][$k]; continue; }
			$q = "name = '".str_replace(["\\", "'"], ["\\\\", "\\'"], $name)."' and mimeType = 'application/vnd.google-apps.folder' and '$parent' in parents and trashed = false";
			[$c, $r] = self::api('GET', 'https://www.googleapis.com/drive/v3/files?fields=files(id)&q='.rawurlencode($q));
			$j = json_decode((string)$r, true);
			if ($c == 200 && !empty($j['files'][0]['id'])) $id = $j['files'][0]['id'];
			else {
				[$c, $r] = self::api('POST', 'https://www.googleapis.com/drive/v3/files?fields=id', json_encode(['name' => $name, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$parent]], JSON_UNESCAPED_UNICODE));
				$j = json_decode((string)$r, true);
				if ($c != 200 || empty($j['id'])) { self::$err = 'Drive папкасы жасалмады ('.$c.')'; return null; }
				$id = $j['id'];
			}
			$t = self::tok(); $t['folders'][$k] = $id; self::tok_save($t);
			$parent = $id;
		}
		return $parent;
	}

	private static function drive_upload($bytes, $name, $mime, $folder) {
		$b = 'berik'.bin2hex(random_bytes(8));
		$body = "--$b\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n".json_encode(['name' => $name, 'parents' => [$folder]], JSON_UNESCAPED_UNICODE)
			."\r\n--$b\r\nContent-Type: $mime\r\n\r\n".$bytes."\r\n--$b--";
		[$c, $r] = self::api('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id', $body, 'multipart/related; boundary='.$b);
		$j = json_decode((string)$r, true);
		if ($c != 200 || empty($j['id'])) { self::$err = self::$err ?: 'Drive-қа жүктелмеді ('.$c.')'; return null; }
		return $j['id'];
	}

	// ---------- Ортақ ----------
	// Drive-та әр түр өз папкасында: Berik POS / Меню фотолары / <компания> ...
	public static $kinds = ['product' => 'Меню фотолары', 'avatar' => 'Аватаркалар', 'waybill' => 'Накладнойлар', 'other' => 'Басқа файлдар'];
	// айлап бөлінетін түрлер (құжаттар көп жиналады)
	public static $by_month = ['waybill', 'other'];

	// компания папкасының аты: «Yaki Sushi» (аты табылмаса c13)
	public static function cmp_label($cmp) {
		$cmp = (int)$cmp; $n = '';
		$q = db::query("select * from company where id = '$cmp' limit 1");
		if ($q && $r = mysqli_fetch_assoc($q)) foreach (['name', 'name_kz', 'name_ru', 'title'] as $k) if (!empty($r[$k])) { $n = trim($r[$k]); break; }
		$n = trim(preg_replace('~[\\\\/:*?"<>|]+~u', ' ', $n));
		return $n !== '' ? $n : 'c'.$cmp;
	}
	public static function kind_path($kind, $cmp) {
		$p = [self::$kinds[$kind] ?? $kind, self::cmp_label($cmp)];
		if (in_array($kind, self::$by_month)) $p[] = date('Y-m');
		return $p;
	}

	// Браузер файлды Drive-қа өзі жүктеуі үшін: қысқа мерзімді токен (1 сағат, тек drive.file) және папка ID
	public static function upload_ticket($kind, $cmp) {
		self::$err = '';
		if (!self::drive_on()) { self::$err = 'Google Drive қосылмаған. Баптау: /files/drive.php'; return null; }
		$f = self::folder(self::kind_path($kind, $cmp));
		$a = self::access();
		if (!$f || !$a) return null;
		// бұл папка осы компанияныкі екенін есте сақтаймыз (register тексеруі үшін)
		$t = self::tok(); $t['cmp'][(int)$cmp] = array_values(array_unique(array_merge($t['cmp'][(int)$cmp] ?? [], [$f]))); self::tok_save($t);
		return ['token' => $a, 'folder' => $f];
	}

	// Браузер Drive-қа жүктеп болған соң: файлды тексеріп (біздің папкада ма), базаға тек сілтемесін жазамыз
	public static function register($drive_id, $kind, $cmp, $ref_id = 0, $uid = 0) {
		self::install(); self::$err = '';
		$cmp = (int)$cmp;
		if (!preg_match('/^[A-Za-z0-9_-]{10,100}$/', (string)$drive_id)) { self::$err = 'Файл ID қате'; return null; }
		[$c, $r] = self::api('GET', 'https://www.googleapis.com/drive/v3/files/'.$drive_id.'?fields=id,name,mimeType,size,parents');
		$j = json_decode((string)$r, true);
		if ($c != 200 || empty($j['id'])) { self::$err = 'Drive-та файл табылмады'; return null; }
		$t = self::tok();
		$mine = (bool)array_intersect($t['cmp'][$cmp] ?? [], $j['parents'] ?? []);
		if (!$mine) { self::$err = 'Файл бұл компанияның папкасында емес'; return null; }
		$ex = db::query("select id from company_file where company_id = '$cmp' and drive_id = '$drive_id'");
		if ($ex && mysqli_num_rows($ex)) return self::get((int)mysqli_fetch_assoc($ex)['id'], $cmp);
		$e = function ($v) { return "'".mysqli_real_escape_string(db::$connect, (string)$v)."'"; };
		$name = preg_replace('/^\d{8}_\d{6}_/', '', $j['name']);
		$ok = db::query("insert into company_file (company_id, kind, ref_id, storage, drive_id, name, mime, size, user_id) values ('$cmp', ".$e($kind).", '".(int)$ref_id."', 'gdrive', ".$e($drive_id).", ".$e($name).", ".$e($j['mimeType']).", '".(int)($j['size'] ?? 0)."', ".((int)$uid ?: 'NULL').")");
		if (!$ok) { self::$err = 'Базаға жазылмады'; return null; }
		return self::get((int)mysqli_insert_id(db::$connect), $cmp);
	}

	// Сервердің өзі Drive-қа жүктейді (бұрынғы хостингтегі файлдарды көшіру үшін). $old — бұрынғы атауы, path-та сақталады
	public static function put($bytes, $name, $mime, $kind, $cmp, $ref_id = 0, $uid = 0, $old = null) {
		self::install(); self::$err = '';
		if (!self::drive_on()) { self::$err = 'Google Drive қосылмаған'; return null; }
		$f = self::folder(self::kind_path($kind, $cmp)); if (!$f) return null;
		$id = self::drive_upload($bytes, $name, $mime, $f); if (!$id) return null;
		$e = function ($v) { return $v === null ? 'NULL' : "'".mysqli_real_escape_string(db::$connect, (string)$v)."'"; };
		$ok = db::query("insert into company_file (company_id, kind, ref_id, storage, drive_id, path, name, mime, size, user_id) values ('".(int)$cmp."', ".$e($kind).", '".(int)$ref_id."', 'gdrive', ".$e($id).", ".$e($old).", ".$e($name).", ".$e($mime).", '".strlen($bytes)."', ".((int)$uid ?: 'NULL').")");
		if (!$ok) { self::drive_delete($id); self::$err = 'Базаға жазылмады'; return null; }
		return self::get((int)mysqli_insert_id(db::$connect), $cmp);
	}

	// тағам фотосы сайт пен кассада ашылуы үшін: «сілтемесі бар кез келген адам көре алады»
	public static function make_public($drive_id) {
		[$c] = self::api('POST', 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($drive_id).'/permissions', json_encode(['role' => 'reader', 'type' => 'anyone']));
		return $c == 200;
	}
	public static function drive_delete($drive_id) {
		[$c] = self::api('DELETE', 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($drive_id));
		return $c == 204 || $c == 404;
	}
	// ашық файлдың сурет сілтемесі (Google өзі кішірейтеді)
	public static function img_url($drive_id, $w = 800) { return 'https://lh3.googleusercontent.com/d/'.rawurlencode($drive_id).'=w'.(int)$w; }

	public static function get($id, $cmp) {
		self::install();
		$q = db::query("select * from company_file where id = '".(int)$id."' and company_id = '".(int)$cmp."'");
		return $q && mysqli_num_rows($q) ? mysqli_fetch_assoc($q) : null;
	}
	public static function list($kind, $ref_id, $cmp) {
		self::install(); $a = [];
		$q = db::query("select * from company_file where company_id = '".(int)$cmp."' and kind = '".mysqli_real_escape_string(db::$connect, $kind)."' and ref_id = '".(int)$ref_id."' order by id");
		while ($q && $r = mysqli_fetch_assoc($q)) $a[] = $r;
		return $a;
	}
	// жүктелген файлдарды (ref_id = 0) құжатқа байлау
	public static function link($ids, $kind, $ref_id, $cmp) {
		self::install();
		$ids = array_filter(array_map('intval', (array)$ids)); if (!$ids || !(int)$ref_id) return 0;
		db::query("update company_file set ref_id = '".(int)$ref_id."' where company_id = '".(int)$cmp."' and kind = '".mysqli_real_escape_string(db::$connect, $kind)."' and ref_id = 0 and id in (".implode(',', $ids).")");
		return mysqli_affected_rows(db::$connect);
	}
	public static function url($f) { return '/files/?id='.(int)$f['id']; }

	// файлды браузерге беру (Drive-тан ағынмен)
	public static function output($f, $download = false) {
		$mime = $f['mime'] ?: 'application/octet-stream';
		header('Content-Type: '.$mime);
		header('Content-Disposition: '.($download ? 'attachment' : 'inline')."; filename*=UTF-8''".rawurlencode($f['name']));
		header('Cache-Control: private, max-age=86400');
		[$c] = self::api('GET', 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($f['drive_id']).'?alt=media', null, null, function ($ch, $data) { echo $data; return strlen($data); });
		if ($c != 200) error_log('cfile output '.$f['id'].': '.$c.' '.self::$err);
		exit;
	}

	// оқу үшін файлдың мазмұны
	public static function bytes($f) {
		[$c, $r] = self::api('GET', 'https://www.googleapis.com/drive/v3/files/'.rawurlencode($f['drive_id']).'?alt=media');
		return $c == 200 ? $r : false;
	}
}
