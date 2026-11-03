<? include "../config/core.php";
	include "../config/cfile.php";

	// Google Drive-қа браузерден тікелей жүктеу:
	//  ?ticket   — қысқа мерзімді токен + папка (kind бойынша)
	//  ?register — жүктелген файлды тексеріп, базаға тек сілтемесін жазу
	//  ?link     — жүктелген файлдарды құжатқа байлау (мысалы, сақталған накладнойға)
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); };

	if (!$user_id || @$user_right['positions_id'] == 3) $out(['ok' => 0, 'err' => 'auth', 'msg' => 'Кіру рұқсаты жоқ']);
	$cmp = (int)$company;
	$kind = (string)@$_REQUEST['kind'];
	if (!isset(cfile::$kinds[$kind])) $out(['ok' => 0, 'err' => 'kind']);

	if (isset($_GET['ticket'])) {
		if (!cfile::drive_on()) $out(['ok' => 0, 'err' => 'off', 'msg' => 'Google Drive қосылмаған']);
		$t = cfile::upload_ticket($kind, $cmp);
		if (!$t) $out(['ok' => 0, 'err' => 'drive', 'msg' => cfile::$err]);
		$out(['ok' => 1] + $t);
	}

	if (isset($_GET['register'])) {
		$f = cfile::register((string)@$_POST['drive_id'], $kind, $cmp, 0, $user_id);
		if (!$f) $out(['ok' => 0, 'msg' => cfile::$err]);
		$out(['ok' => 1, 'file' => ['id' => (int)$f['id'], 'name' => $f['name'], 'mime' => $f['mime'], 'url' => cfile::url($f)]]);
	}

	if (isset($_GET['link'])) {
		$ids = array_map('intval', explode(',', (string)@$_POST['ids']));
		$out(['ok' => 1, 'n' => cfile::link($ids, $kind, (int)@$_POST['ref_id'], $cmp)]);
	}

	$out(['ok' => 0, 'err' => 'error']);
