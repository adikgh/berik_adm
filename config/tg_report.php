<?php
// Telegram есептері (касса күні, курьер, смена) компанияның есеп тобына барады.
// Топ: есеп тобы (company.report_chat_send = 1, report_chat_id), ол жоқ болса тапсырыс тобы (chat_send = 1, chat_id), әйтпесе жіберілмейді.
// Токен базада: sys_setting.tg_token (dev ▸ Telegram бот). Қате болса error_log-қа жазады, бетті тоқтатпайды.
require_once __DIR__ . '/sys_set.php';

if (!function_exists('tg_report_send')) {

	// Компанияның есеп жіберілетін чаты ('' = жібермейміз)
	function tg_report_chat($company_id) {
		$cid = (int)$company_id;
		$q = db::query("select * from company where id = '$cid' limit 1");
		$c = $q ? mysqli_fetch_assoc($q) : null;
		if (!$c) return '';
		$rep = trim((string)@$c['report_chat_id']);
		if ((int)@$c['report_chat_send'] === 1 && $rep !== '') return $rep;
		$ord = trim((string)@$c['chat_id']);
		if ((int)@$c['chat_send'] === 1 && $ord !== '') return $ord;
		return '';
	}

	// Есепті жіберу. $html: parse_mode=html мәтіні (пайдаланушы мәтіні tgr_h() арқылы қорғалған болуы керек)
	function tg_report_send($company_id, $html) {
		try {
			$chat = tg_report_chat($company_id);
			if ($chat === '') return false;
			// Локал (OpenServer, *.c): базада live топтарының ID-сы тұр, сондықтан әдепкіде жібермейміз.
			// Локалда тексеру үшін: sys_setting.tg_report_local = 1
			$host = (string)@$_SERVER['HTTP_HOST'];
			$local = $host !== '' ? (bool)preg_match('~\.c(:\d+)?$~i', $host) : DIRECTORY_SEPARATOR === '\\';
			if ($local && (string)sys_set('tg_report_local') !== '1') { error_log('tg_report: local, not sent (company ' . (int)$company_id . ')'); return false; }
			$token = (string)sys_set('tg_token');
			if ($token === '' || !function_exists('curl_init')) { error_log('tg_report: no token or curl'); return false; }
			$ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
			curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10, CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ['chat_id' => $chat, 'parse_mode' => 'html', 'disable_web_page_preview' => 'true', 'text' => mb_substr((string)$html, 0, 4000)]]);
			$r = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
			$j = json_decode((string)$r, true);
			if (empty($j['ok'])) { error_log('tg_report: company ' . (int)$company_id . ': ' . ($err ?: (string)@$j['description'])); return false; }
			return true;
		} catch (Throwable $e) {
			error_log('tg_report: ' . $e->getMessage());
			return false;
		}
	}

	function tgr_h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
	function tgr_money($v) { $v = (int)$v; return ($v < 0 ? '−' : '') . number_format(abs($v), 0, '', ' ') . ' тг'; }
	function tgr_date($d) { $t = strtotime((string)$d); return $t ? date('d.m.Y', $t) : tgr_h($d); }

	// Тақырып: эмодзи + атауы, компания (· точка), күн
	function tgr_head($emoji, $title, $company_id, $branch_id, $day) {
		$cid = (int)$company_id; $bid = (int)$branch_id;
		$q = db::query("select name from company where id = '$cid' limit 1");
		$cn = $q ? (string)@mysqli_fetch_assoc($q)['name'] : '';
		$bn = '';
		if ($bid) {
			$n = mysqli_fetch_assoc(db::query("select count(*) c from company_branch where company_id = '$cid'"));
			$q = db::query("select name from company_branch where id = '$bid' limit 1");
			if ((int)@$n['c'] > 1 && $q) $bn = (string)@mysqli_fetch_assoc($q)['name'];
		}
		return $emoji . ' <b>' . tgr_h($title) . "</b>\n<b>" . tgr_h($cn) . '</b>' . ($bn !== '' ? ' · ' . tgr_h($bn) : '') . "\n📅 " . tgr_date($day) . "\n";
	}
}
