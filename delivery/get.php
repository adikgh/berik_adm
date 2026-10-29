<? include "../config/core.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $msg = '') { exit(json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE)); }

	if (!$user_id || @$user_right['positions_id'] == 3) out(false, 'Рұқсат жоқ');
	if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) out(false, 'Бетті жаңартып, қайта көріңіз');

	$cmp = (int)$company;
	$cn = db::$connect;
	mysqli_report(MYSQLI_REPORT_OFF);

	// Точка осы компанияныкі ме (0 = барлық точка, базада NULL)
	function branch_ok($cn, $cmp, $bid) {
		if (!$bid) return true;
		$st = mysqli_prepare($cn, "select 1 from company_branch where id = ? and company_id = ?");
		mysqli_stmt_bind_param($st, 'ii', $bid, $cmp);
		mysqli_stmt_execute($st);
		return (bool)mysqli_stmt_fetch($st);
	}

	// Осы точкада бұл атау бар ма (өзін есептемейміз)
	function name_exists($cn, $cmp, $bid, $name, $id = 0) {
		$st = mysqli_prepare($cn, "select 1 from company_delivery_zone where company_id = ? and IFNULL(branch_id, 0) = ? and name = ? and id <> ? limit 1");
		mysqli_stmt_bind_param($st, 'iisi', $cmp, $bid, $name, $id);
		mysqli_stmt_execute($st);
		return (bool)mysqli_stmt_fetch($st);
	}

	// Сақтау: id = 0 болса жаңа (бірнеше жол = бірнеше көше), әйтпесе өзгерту
	if (isset($_GET['save'])) {
		$id = (int)@$_POST['id'];
		$price = (int)@$_POST['price'];
		$sort = (int)@$_POST['sort'];
		$bid = (int)@$_POST['branch_id'];
		$names = array_values(array_unique(array_filter(array_map(function ($v) {
			return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($v))), 0, 100);
		}, preg_split('/\R/u', (string)@$_POST['name'])), 'strlen')));

		if (!$names) out(false, 'Көшенің атауын жазыңыз');
		if ($price < 0 || $price > 1000000) out(false, 'Бағаны дұрыс жазыңыз');
		if (!branch_ok($cn, $cmp, $bid)) out(false, 'Точка табылмады');
		$bv = $bid ?: null;

		if ($id) {
			if (count($names) > 1) out(false, 'Өзгерткенде бір ғана көше жазыңыз');
			if (name_exists($cn, $cmp, $bid, $names[0], $id)) out(false, 'Бұл точкада мұндай көше бар');
			$st = mysqli_prepare($cn, "update company_delivery_zone set name = ?, price = ?, branch_id = ?, sort = ? where id = ? and company_id = ?");
			mysqli_stmt_bind_param($st, 'siiiii', $names[0], $price, $bv, $sort, $id, $cmp);
			out(mysqli_stmt_execute($st), 'Сақталды');
		}

		$st = mysqli_prepare($cn, "insert into company_delivery_zone (company_id, branch_id, name, price, sort) values (?, ?, ?, ?, ?)");
		$added = 0; $skip = 0;
		foreach ($names as $nm) {
			if (name_exists($cn, $cmp, $bid, $nm)) { $skip++; continue; }
			mysqli_stmt_bind_param($st, 'iisii', $cmp, $bv, $nm, $price, $sort);
			if (mysqli_stmt_execute($st)) $added++; else $skip++;
		}
		if (!$added) out(false, 'Бұл көше(лер) бұрыннан бар');
		out(true, 'Қосылды: '.$added.($skip ? ', бұрыннан бары: '.$skip : ''));
	}

	// Өшіру
	if (isset($_GET['del'])) {
		$id = (int)@$_POST['id'];
		$st = mysqli_prepare($cn, "delete from company_delivery_zone where id = ? and company_id = ?");
		mysqli_stmt_bind_param($st, 'ii', $id, $cmp);
		$ok = mysqli_stmt_execute($st) && mysqli_stmt_affected_rows($st) > 0;
		out($ok, $ok ? 'Өшірілді' : 'Көше табылмады');
	}

	out(false, 'Қате сұраныс');
