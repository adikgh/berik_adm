<? include "../config/core.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $msg = '') { exit(json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE)); }

	if (!$user_id || @$user_right['positions_id'] == 3) out(false, 'Рұқсат жоқ');
	if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) out(false, 'Бетті жаңартып, қайта көріңіз');
	$cmp = (int)$company;

	// QR дизайны: assets/uploads/qr/c<id>.json, логотип c<id>_logo.<ext>
	$dir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/qr/';
	$sf = $dir.'c'.$cmp.'.json';
	function st_load($sf) { $j = is_file($sf) ? json_decode((string)file_get_contents($sf), true) : null; return is_array($j) ? $j : []; }
	function st_save($sf, $st) { if (!is_dir(dirname($sf))) @mkdir(dirname($sf), 0775, true); return file_put_contents($sf, json_encode($st, JSON_UNESCAPED_UNICODE)) !== false; }

	if (isset($_GET['style'])) {
		$hex = function ($v, $d) { return preg_match('/^#[0-9a-f]{6}$/i', (string)$v) ? strtolower($v) : $d; };
		$pick = function ($v, $a, $d) { return in_array($v, $a, true) ? $v : $d; };
		$txt = function ($v) { return mb_substr(trim(strip_tags((string)$v)), 0, 40); };
		$st = st_load($sf);
		$st = array_merge($st, [
			'color' => $hex(@$_POST['color'], '#111111'),
			'bg' => $hex(@$_POST['bg'], '#ffffff'),
			'frame_color' => $hex(@$_POST['frame_color'], '#0ea768'),
			'dots' => $pick(@$_POST['dots'], ['square', 'round', 'dot'], 'round'),
			'eyes' => $pick(@$_POST['eyes'], ['square', 'round', 'circle'], 'round'),
			'frame' => (int)!empty($_POST['frame']),
			'title' => $txt(@$_POST['title']),
			'text' => $txt(@$_POST['text']),
		]);
		out(st_save($sf, $st), 'Сақталды');
	}

	if (isset($_GET['logo'])) {
		$f = @$_FILES['file'];
		if (!$f || $f['error'] !== UPLOAD_ERR_OK) out(false, 'Файл жүктелмеді');
		if ($f['size'] > 2 * 1024 * 1024) out(false, 'Файл 2 МБ-тан аспасын');
		$info = @getimagesize($f['tmp_name']);
		$ext = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? '';
		if (!$ext) out(false, 'Тек PNG, JPG не WEBP сурет');
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		foreach (glob($dir.'c'.$cmp.'_logo.*') ?: [] as $old) @unlink($old);
		$name = 'c'.$cmp.'_logo.'.$ext;
		if (!move_uploaded_file($f['tmp_name'], $dir.$name)) out(false, 'Сақталмады');
		$st = st_load($sf); $st['logo'] = $name; st_save($sf, $st);
		exit(json_encode(['ok' => true, 'msg' => 'Логотип жүктелді', 'url' => '/assets/uploads/qr/'.$name.'?v='.time()], JSON_UNESCAPED_UNICODE));
	}

	if (isset($_GET['logo_del'])) {
		foreach (glob($dir.'c'.$cmp.'_logo.*') ?: [] as $old) @unlink($old);
		$st = st_load($sf); $st['logo'] = ''; st_save($sf, $st);
		out(true, 'Логотип өшірілді');
	}

	if (!mysqli_num_rows(db::query("SHOW TABLES LIKE 'company_menu'"))) out(false, 'company_menu кестесі жоқ');
	mysqli_report(MYSQLI_REPORT_OFF);

	// Мәзір көрінісі: company_menu.look (JSON)
	if (isset($_GET['look'])) {
		if (!mysqli_num_rows(db::query("SHOW COLUMNS FROM company_menu LIKE 'look'"))) out(false, 'Базаға berik_004_menu_look.sql іске қосу керек');
		$look = [
			'tpl' => in_array((int)@$_POST['tpl'], [1, 2], true) ? (int)$_POST['tpl'] : 0,
			'accent' => preg_match('/^#[0-9a-f]{6}$/i', (string)@$_POST['accent']) ? strtolower($_POST['accent']) : '#0ea768',
			'layout' => in_array(@$_POST['layout'], ['list', 'grid'], true) ? $_POST['layout'] : 'list',
			'photos' => (int)!empty($_POST['photos']),
			'search' => (int)!empty($_POST['search']),
			'card' => (int)!empty($_POST['card']),
			'upsell' => (int)!empty($_POST['upsell']),
		];
		$j = json_encode($look);
		$st = mysqli_prepare(db::$connect, "insert into company_menu (company_id, look, upd_dt) values (?, ?, now()) on duplicate key update look = values(look), upd_dt = now()");
		mysqli_stmt_bind_param($st, 'is', $cmp, $j);
		out(mysqli_stmt_execute($st), 'Сақталды');
	}

	// QR меню баптауын сақтау
	if (isset($_GET['save'])) {
		$v = [];
		foreach (['is_on', 'order_on', 'delivery_on', 'pickup_on', 'table_on'] as $k) $v[$k] = (int)!empty($_POST[$k]);
		$tables = max(0, min(300, (int)@$_POST['tables']));
		$min = max(0, min(1000000, (int)@$_POST['min_sum']));
		$note = mb_substr(trim(strip_tags((string)@$_POST['note'])), 0, 500);
		$note = $note !== '' ? $note : null;
		$st = mysqli_prepare(db::$connect, "insert into company_menu (company_id, is_on, order_on, delivery_on, pickup_on, table_on, tables, min_sum, note, upd_dt)
			values (?, ?, ?, ?, ?, ?, ?, ?, ?, now())
			on duplicate key update is_on = values(is_on), order_on = values(order_on), delivery_on = values(delivery_on), pickup_on = values(pickup_on),
				table_on = values(table_on), tables = values(tables), min_sum = values(min_sum), note = values(note), upd_dt = now()");
		mysqli_stmt_bind_param($st, 'iiiiiiiis', $cmp, $v['is_on'], $v['order_on'], $v['delivery_on'], $v['pickup_on'], $v['table_on'], $tables, $min, $note);
		out(mysqli_stmt_execute($st), 'Сақталды');
	}

	out(false, 'Қате сұраныс');
