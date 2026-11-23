<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id || !dev::can('keys')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);
	require __DIR__ . '/lib.php';
	if (!dev::row("SHOW TABLES LIKE 'sys_setting'")) out(['ok' => false, 'msg' => t::w('Add the SQL first', 'Алдымен SQL қосыңыз') . ' (sys_setting)']);
	$all = dkeys_all();

	// Бір топты сақтау. Құпия өріс бос болса — өзгертпейміз
	if (isset($_GET['save'])) {
		$g = dkeys_groups()[(string)@$_POST['grp']] ?? null;
		if (!$g) out(['ok' => false, 'msg' => t::w('Error', 'Қате')]);
		$put = [];
		foreach ($g[2] as $k => $d) {
			if ($d[1] == 'auth') continue; // кіру кілттері тек «Қайта жасау» батырмасымен
			$v = trim(str_replace(["\r", "\n"], '', (string)@$_POST[$k]));
			if (mb_strlen($v) > 2000) out(['ok' => false, 'msg' => $d[0] . ': ' . t::w('too long', 'тым ұзын')]);
			if ($d[1] == 'secret') { $v = preg_replace('/\s+/', '', $v); if ($v === '') continue; }
			if ($d[1] == 'model' && !in_array($v, dkeys_models(), true)) $v = 'auto';
			if ($d[1] == 'url' && $v !== '' && !preg_match('~^https?://[^\s<>"\']+$~i', $v)) out(['ok' => false, 'msg' => $d[0] . ': ' . t::w('must start with http:// or https://', 'http:// не https:// деп басталуы керек')]);
			if ($k == 'cabinet_url') $v = rtrim($v, '/');
			if ($v !== sys_set($k)) $put[$k] = $v;
		}
		foreach ($put as $k => $v) if (!sys_set_put($k, $v, $user_id)) out(['ok' => false, 'msg' => t::w('Could not save to the database', 'Базаға сақталмады')]);
		out(['ok' => true, 'msg' => $put ? t::w('Saved', 'Сақталды') : t::w('Nothing changed', 'Ештеңе өзгермеді')]);
	}

	// Мәнді өшіру (бос қалады: сайт ескі файлға не әдепкі мәнге қайтады)
	if (isset($_GET['del'])) {
		$k = (string)@$_POST['id'];
		if (!isset($all[$k]) || $all[$k] == 'auth') out(['ok' => false, 'msg' => t::w('Error', 'Қате')]);
		out(sys_set_put($k, '', $user_id) ? ['ok' => true, 'msg' => t::w('Deleted', 'Өшірілді')] : ['ok' => false, 'msg' => t::w('Could not save to the database', 'Базаға сақталмады')]);
	}

	// Кіру кілтін қайта жасау: сол сайтта бәрі қайта кіреді
	if (isset($_GET['regen'])) {
		$k = (string)@$_POST['id'];
		if (($all[$k] ?? '') != 'auth') out(['ok' => false, 'msg' => t::w('Error', 'Қате')]);
		out(sys_set_put($k, bin2hex(random_bytes(32)), $user_id) ? ['ok' => true, 'msg' => t::w('New key made', 'Жаңа кілт жасалды')] : ['ok' => false, 'msg' => t::w('Could not save to the database', 'Базаға сақталмады')]);
	}

	// Google Drive-ты ажырату: токен Google-да кері қайтарылып, базадан өшіріледі
	if (isset($_GET['drive_off'])) {
		require_once DEV_ADM . '/config/cfile.php';
		cfile::disconnect();
		out(['ok' => true, 'msg' => t::w('Google Drive disconnected', 'Google Drive ажыратылды')]);
	}

	// Ескі файлдардан алу (тек базада бос кілттер). Мәндер браузерге берілмейді, тек аттары
	if (isset($_GET['import'])) {
		$done = dkeys_import();
		out(['ok' => true, 'msg' => $done ? t::w('Taken from files', 'Файлдардан алынды') . ': ' . implode(', ', $done) : t::w('Nothing new in the files', 'Файлдарда жаңа мән жоқ')]);
	}

	out(['ok' => false, 'msg' => t::w('Error', 'Қате')]);
