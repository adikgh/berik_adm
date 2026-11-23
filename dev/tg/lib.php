<?php
// Telegram бот: токен базада (sys_setting: tg_token, tg_bot_name, tg_hook_secret), dev ▸ Telegram бот (/tg/) өзгертеді.
// Компанияның екі тобы: тапсырыс тобы company.chat_id (+ tg_title), есеп тобы company.report_chat_id (+ report_tg_title).
// Иесі «Топқа қосу» батырмасын басады → t.me/<бот>?startgroup=<код>
// → бот топқа қосылғанда Telegram «/start <код>» жібереді → tg_update() топтың ID-ын сол компанияға жазады.
// Live (https): webhook → /tg/hook.php. Локал (http): webhook болмайды, «Тексеру» getUpdates арқылы алады.
require_once dirname(__DIR__, 2) . '/config/sys_set.php';

function tg_esc($s) { return mysqli_real_escape_string(db::$connect, (string)$s); }
function tg_q1($sql) { $q = db::query($sql); return $q ? mysqli_fetch_assoc($q) : null; }

// Компания кестесінде жаңа бағандар бар ма (SQL қосылған ба)
function tg_cols_ok() {
	static $ok = null;
	if ($ok === null) { try { $q = db::query("SHOW COLUMNS FROM company LIKE 'tg_code'"); $ok = $q && mysqli_num_rows($q) > 0; } catch (Throwable $e) { $ok = false; } }
	return $ok;
}

// Есеп тобының бағандары бар ма (_sql/tg_report_20261123.sql)
function tg_cols_report_ok() {
	static $ok = null;
	if ($ok === null) { try { $q = db::query("SHOW COLUMNS FROM company LIKE 'report_chat_id'"); $ok = $q && mysqli_num_rows($q) > 0; } catch (Throwable $e) { $ok = false; } }
	return $ok;
}

// Telegram API шақыру. Жауап: ['ok' => bool, 'result' => ..., 'description' => ...]
function tg_api($method, $params = [], $token = null) {
	$token = $token === null ? sys_set('tg_token') : $token;
	if ($token === '') return ['ok' => false, 'description' => 'no token'];
	if (!function_exists('curl_init')) return ['ok' => false, 'description' => 'no curl'];
	$ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
	foreach ($params as $k => $v) if (is_array($v)) $params[$k] = json_encode($v);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 20, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params]);
	$r = curl_exec($ch);
	$err = curl_error($ch);
	curl_close($ch);
	$j = json_decode((string)$r, true);
	if (!is_array($j)) return ['ok' => false, 'description' => $err ?: 'no answer'];
	return $j;
}

// Webhook мекенжайы: тек https хостта (live: devb.gprog.kz). http (локал) болса '' — онда getUpdates қолданылады.
function tg_hook_url() {
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || @$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || (int)@$_SERVER['SERVER_PORT'] === 443;
	$host = preg_replace('/[^a-z0-9.\-]/i', '', (string)@$_SERVER['HTTP_HOST']);
	return $https && $host !== '' ? 'https://' . $host . '/tg/hook.php' : '';
}

// Webhook тіркеу (live). Локалда webhook-қа тимейді: бот live-пен ортақ. Қайтарады: ['ok' => bool, 'msg' => '']
function tg_hook_set() {
	$url = tg_hook_url();
	if ($url === '') return ['ok' => true, 'msg' => 'local'];
	$sec = sys_set('tg_hook_secret');
	if ($sec === '') { $sec = bin2hex(random_bytes(16)); sys_set_put('tg_hook_secret', $sec); }
	$r = tg_api('setWebhook', ['url' => $url, 'secret_token' => $sec, 'allowed_updates' => ['message', 'my_chat_member'], 'drop_pending_updates' => 'true']);
	return ['ok' => !empty($r['ok']), 'msg' => !empty($r['ok']) ? $url : (string)@$r['description']];
}

// Бір реттік код (24 сағат): «Топқа қосу» сілтемесі үшін. $kind: 'order' (код c…) не 'report' (код r…)
function tg_link_url($cid, $kind = 'order') {
	$cid = (int)$cid;
	$bot = sys_set('tg_bot_name');
	if ($bot === '' || !tg_cols_ok() || ($kind === 'report' && !tg_cols_report_ok())) return '';
	$code = ($kind === 'report' ? 'r' : 'c') . $cid . '_' . bin2hex(random_bytes(8));
	db::query("UPDATE company SET tg_code = '" . tg_esc($code) . "', tg_code_dt = NOW() WHERE id = '$cid'");
	return 'https://t.me/' . rawurlencode($bot) . '?startgroup=' . rawurlencode($code);
}

function tg_say($chat_id, $txt) { tg_api('sendMessage', ['chat_id' => $chat_id, 'parse_mode' => 'html', 'text' => $txt]); }

// Telegram-нан келген бір жаңарту. Компанияға байланбаған чаттардың командалары еленбейді (жауап жоқ).
function tg_update($u) {
	if (!is_array($u) || !tg_cols_ok()) return;

	// Бот топтан шығарылды → компанияда жіберу өшеді (ID сақталады, қайта қосса жалғасады)
	if (isset($u['my_chat_member'])) {
		$m = $u['my_chat_member'];
		$cid = (string)@$m['chat']['id'];
		$st = (string)@$m['new_chat_member']['status'];
		if ($cid !== '' && in_array($st, ['left', 'kicked'], true)) {
			db::query("UPDATE company SET chat_send = 0 WHERE chat_id = '" . tg_esc($cid) . "'");
			if (tg_cols_report_ok()) db::query("UPDATE company SET report_chat_send = 0 WHERE report_chat_id = '" . tg_esc($cid) . "'");
		}
		return;
	}

	$msg = isset($u['message']) ? $u['message'] : null;
	if (!$msg || !isset($msg['chat']['id'])) return;
	$chat = $msg['chat'];
	$chat_id = (string)$chat['id'];

	// Топ супертопқа айналса ID өзгереді: компанияда жаңасына ауыстырамыз
	if (!empty($msg['migrate_to_chat_id'])) {
		$new = tg_esc($msg['migrate_to_chat_id']);
		db::query("UPDATE company SET chat_id = '$new' WHERE chat_id = '" . tg_esc($chat_id) . "'");
		if (tg_cols_report_ok()) db::query("UPDATE company SET report_chat_id = '$new' WHERE report_chat_id = '" . tg_esc($chat_id) . "'");
		return;
	}

	$text = trim((string)@$msg['text']);
	if (!preg_match('~^/start(?:@(\w+))?\s+([cr]\d+_[0-9a-f]{16})$~i', $text, $mm)) return; // басқасы еленбейді
	$bot = sys_set('tg_bot_name');
	if ($mm[1] !== '' && $bot !== '' && strcasecmp($mm[1], $bot) !== 0) return;
	if (!in_array((string)@$chat['type'], ['group', 'supergroup'], true)) return; // тек топ

	$c = tg_q1("SELECT id, name FROM company WHERE tg_code = '" . tg_esc($mm[2]) . "' AND tg_code_dt > NOW() - INTERVAL 1 DAY LIMIT 1");
	if (!$c) return; // код жоқ не ескірген: үндемейміз
	$title = mb_substr(trim((string)@$chat['title']), 0, 255);
	$name = htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8');
	if (strtolower($mm[2][0]) === 'r') { // есеп тобы
		if (!tg_cols_report_ok()) return;
		db::query("UPDATE company SET report_chat_id = '" . tg_esc($chat_id) . "', report_chat_send = 1, report_tg_title = '" . tg_esc($title) . "', tg_code = NULL, tg_code_dt = NULL WHERE id = '" . (int)$c['id'] . "'");
		tg_say($chat_id, "✅ Есеп тобы қосылды: <b>$name</b>\nКүндік есептер осы топқа келеді.");
		return;
	}
	db::query("UPDATE company SET chat_id = '" . tg_esc($chat_id) . "', chat_send = 1, tg_title = '" . tg_esc($title) . "', tg_code = NULL, tg_code_dt = NULL WHERE id = '" . (int)$c['id'] . "'");
	tg_say($chat_id, "✅ Топ қосылды: <b>$name</b>\nЖаңа тапсырыстар осы топқа келеді.");
}

// Локал үшін: webhook жоқ кезде жиналған жаңартуларды алып өңдейді. Қайтарады: өңделген саны не қате мәтіні
function tg_poll() {
	$off = (int)sys_set('tg_offset', '0');
	$r = tg_api('getUpdates', ['offset' => $off ? $off + 1 : 0, 'timeout' => 0, 'allowed_updates' => ['message', 'my_chat_member']]);
	if (empty($r['ok'])) return (string)@$r['description'];
	$n = 0;
	foreach ((array)$r['result'] as $u) { tg_update($u); $off = max($off, (int)$u['update_id']); $n++; }
	if ($n) sys_set_put('tg_offset', (string)$off);
	return $n;
}
