<? include "../../config/core.php";
	include "../../config/stop_list.php";

	header('Content-Type: application/json; charset=utf-8');
	if (!$user_id || !$company) { echo json_encode(['ok' => 0, 'err' => 'auth']); exit(); }
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => 0]); exit(); }

	// тағамды стопқа қою (1) / сатуға рұқсат (2) / алу (0)
	if (isset($_GET['set'])) {
		$ok = stopl::set($company, (int)@$_POST['id'], (int)@$_POST['mode'], $user_id, (string)@$_POST['note']);
		echo json_encode(['ok' => $ok ? 1 : 0]); exit();
	}

	// автомат стопты қосу / өшіру
	if (isset($_GET['auto'])) {
		$ok = stopl::auto_set($company, (int)@$_POST['on'] == 1);
		echo json_encode(['ok' => $ok ? 1 : 0]); exit();
	}

	echo json_encode(['ok' => 0]);
