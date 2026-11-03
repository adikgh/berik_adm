<? include "../config/core.php";
	include "lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function rnp_out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); }

	if (!$user_id || $user_right['positions_id'] == 3) rnp_out(['ok' => 0, 'err' => 'Рұқсат жоқ']);
	$company = intval($company);
	if (!$company) rnp_out(['ok' => 0, 'err' => 'Компания таңдалмаған']);
	rnp_table();

	// Мәндерді сақтау: items = [{d: 'YYYY-MM-DD', k: 'cost', v: 12000, p: 0}, ...]
	// p = 1 болса — айлық жоспар (d = айдың 1-күні). Бос мән — жолды өшіреді.
	if (isset($_GET['save'])) {
		$items = json_decode((string)@$_POST['items'], true);
		if (!is_array($items) || !count($items) || count($items) > 62) rnp_out(['ok' => 0, 'err' => 'Қате дерек']);

		$rows = rnp_rows(rnp_types($company));
		$keys_day = rnp_keys($rows, false);
		$keys_plan = rnp_keys($rows, true);
		$now = date('Y-m-d H:i:s');
		$uid = intval($user_id);

		foreach ($items as $it) {
			$d = (string)@$it['d'];
			$k = (string)@$it['k'];
			$p = !empty($it['p']) ? 1 : 0;
			if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $d) || !strtotime($d)) rnp_out(['ok' => 0, 'err' => 'Күн қате']);
			if (!$p && $d > date('Y-m-d', time() - 6 * 3600)) rnp_out(['ok' => 0, 'err' => 'Болашақ күнді толтыруға болмайды']);
			if ($p) { $d = substr($d, 0, 7).'-01'; if (!isset($keys_plan[$k])) rnp_out(['ok' => 0, 'err' => 'Жол қате']); }
			elseif (!isset($keys_day[$k])) rnp_out(['ok' => 0, 'err' => 'Жол қате']);

			$v = trim((string)@$it['v']);
			if ($v === '') {
				db::query("DELETE FROM company_pnl WHERE company_id = '$company' and `plan` = '$p' and `day` = '$d' and row_key = '$k'");
				continue;
			}
			$v = intval(round(floatval(str_replace([' ', ','], ['', '.'], $v))));
			db::query("INSERT INTO company_pnl (company_id, `day`, `plan`, row_key, amount, upd_by, upd_dt)
				VALUES ('$company', '$d', '$p', '$k', '$v', '$uid', '$now')
				ON DUPLICATE KEY UPDATE amount = VALUES(amount), upd_by = VALUES(upd_by), upd_dt = VALUES(upd_dt)");
		}
		rnp_out(['ok' => 1]);
	}

	// Бір күннің қолмен жазылған мәндері («Запис қосу» терезесі үшін)
	if (isset($_GET['day'])) {
		$d = (string)$_GET['day'];
		if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $d) || !strtotime($d)) rnp_out(['ok' => 0, 'err' => 'Күн қате']);
		$vals = [];
		$q = db::query("select row_key, amount from company_pnl where company_id = '$company' and `plan` = 0 and `day` = '$d'");
		while ($q && $r = mysqli_fetch_assoc($q)) $vals[$r['row_key']] = intval($r['amount']);
		rnp_out(['ok' => 1, 'vals' => (object)$vals]);
	}

	rnp_out(['ok' => 0, 'err' => 'Белгісіз сұрау']);
