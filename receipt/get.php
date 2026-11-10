<? include "../config/core.php";
	require_once "../config/receipt.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $msg = '') { exit(json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE)); }

	if (!$user_id || @$user_right['positions_id'] == 3) out(false, 'Рұқсат жоқ');
	if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) out(false, 'Бетті жаңартып, қайта көріңіз');
	$cmp = (int)$company;
	if (!$cmp) out(false, 'Компания таңдалмаған');

	// Кесте бірінші сақтағанда өзі жасалады (бар деректерге тимейді)
	receipt::create();
	mysqli_report(MYSQLI_REPORT_OFF);

	// Чек баптауын сақтау
	if (isset($_GET['save'])) {
		$cfg = json_encode(receipt::clean($_POST), JSON_UNESCAPED_UNICODE);
		$logo = (string)@$_POST['logo'];
		if ($logo !== '' && !receipt::logo_ok($logo)) out(false, 'Логотип тек PNG не JPG, 400 КБ-тан аспасын');
		$st = mysqli_prepare(db::$connect, "insert into company_receipt (company_id, cfg, logo, upd_dt) values (?, ?, ?, now())
			on duplicate key update cfg = values(cfg), logo = values(logo), upd_dt = now()");
		mysqli_stmt_bind_param($st, 'iss', $cmp, $cfg, $logo);
		out(mysqli_stmt_execute($st), 'Сақталды');
	}

	// Әдепкі чекке қайтару
	if (isset($_GET['reset'])) {
		out((bool)db::query("delete from company_receipt where company_id = '$cmp'"), 'Сақталды: әдепкі чек');
	}

	out(false, 'Қате сұраныс');
