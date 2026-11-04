<? include "../config/core.php";

	// Аккаунт: аты-жөнін сақтау, парольді ауыстыру
	header('Content-Type: application/json; charset=utf-8');
	function ac_out($ok, $msg) { exit(json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE)); }

	if (!$user_id) ac_out(false, 'Қайта кіріңіз');
	if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) ac_out(false, 'Бетті жаңартып, қайта көріңіз');
	$uid = (int)$user_id;

	// Аты, тегі
	if (isset($_GET['profile'])) {
		$name = mb_substr(trim(strip_tags((string)@$_POST['name'])), 0, 50);
		$surname = mb_substr(trim(strip_tags((string)@$_POST['surname'])), 0, 50);
		if ($name === '') ac_out(false, 'Атыңызды жазыңыз');
		$st = mysqli_prepare(db::$connect, "UPDATE `user` SET `name` = ?, `surname` = ?, `upd_dt` = ? WHERE id = ?");
		$dt = date('Y-m-d H:i:s');
		mysqli_stmt_bind_param($st, 'sssi', $name, $surname, $dt, $uid);
		ac_out(mysqli_stmt_execute($st), 'Сәтті сақталды');
	}

	// Пароль: қазіргісі + жаңасы (bcrypt)
	if (isset($_GET['pass'])) {
		$old = (string)@$_POST['old'];
		$new = trim((string)@$_POST['new']);
		$rk = 'accpass|' . $uid;
		if (rl_over($rk, 5, 900)) ac_out(false, 'Тым көп әрекет. 15 минуттан кейін қайта көріңіз');
		$u = mysqli_fetch_assoc(db::query("SELECT id, phone, password FROM `user` WHERE id = '$uid' limit 1"));
		if (!$u || !pass_ok($old, $u['password'])) { rl_hit($rk); ac_out(false, 'Қазіргі пароль қате'); }
		if (mb_strlen($new) < 6) ac_out(false, 'Жаңа пароль кемінде 6 таңба болсын');
		if ($new !== trim((string)@$_POST['new2'])) ac_out(false, 'Жаңа парольдер сәйкес емес');
		if ($new === $old) ac_out(false, 'Жаңа пароль қазіргіден өзгеше болсын');
		rl_clear($rk);
		$trust = trust_ok($u); // бұл құрылғы сенімді болса, сенімді күйде қалады
		db::query("UPDATE `user` SET `password` = '" . pass_sql($new) . "', `upd_dt` = '" . date('Y-m-d H:i:s') . "' WHERE id = '$uid'");
		auth_relogin($uid); // өз cookie-ін жаңарту, басқа құрылғылар шығып қалады
		if ($trust) { $u = mysqli_fetch_assoc(db::query("SELECT id, phone, password FROM `user` WHERE id = '$uid' limit 1")); trust_set($u); }
		ac_out(true, 'Пароль сәтті ауыстырылды');
	}

	ac_out(false, 'Қате сұраныс');
