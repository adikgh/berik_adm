<? include "../config/core.php";
	include "lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function rnp_out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); }

	if (!$user_id || $user_right['positions_id'] == 3) rnp_out(['ok' => 0, 'err' => 'Рұқсат жоқ']);
	$company = intval($company);
	rnp_table();

	// Қолмен жазылатын жолдарды сақтау: items = [{d: 'YYYY-MM-DD', k: 'c13_th', v: 12000}, ...] (кілттер rnp_in_map)
	// Бос мән — жолды өшіреді. Тек көруге рұқсат етілген компанияларға жазылады.
	if (isset($_GET['save'])) {
		$items = json_decode((string)@$_POST['items'], true);
		if (!is_array($items) || !count($items) || count($items) > 62) rnp_out(['ok' => 0, 'err' => 'Қате дерек']);

		$companies = rnp_companies($company, $user_right);
		$now = date('Y-m-d H:i:s');
		$uid = intval($user_id);
		$in_map = rnp_in_map(); // суффикс → company_pnl row_key

		foreach ($items as $it) {
			$d = (string)@$it['d'];
			if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $d) || !strtotime($d)) rnp_out(['ok' => 0, 'err' => 'Күн қате']);
			if ($d > date('Y-m-d', time() - 6 * 3600)) rnp_out(['ok' => 0, 'err' => 'Болашақ күнді толтыруға болмайды']);
			// c13_th → m_t_hall т.б. (rnp_in_map) — жарнама шығындары ОПиУ-мен ортақ
			if (!preg_match('/^c(\d+)_([a-z]+)$/', (string)@$it['k'], $m) || !isset($companies[intval($m[1])]) || !isset($in_map[$m[2]])) rnp_out(['ok' => 0, 'err' => 'Жол қате']);
			$cid = intval($m[1]);
			$rk = $in_map[$m[2]];

			$v = trim((string)@$it['v']);
			if ($v === '') {
				db::query("DELETE FROM company_pnl WHERE company_id = '$cid' and `plan` = 0 and `day` = '$d' and row_key = '$rk'");
				continue;
			}
			$v = intval(round(floatval(str_replace([' ', ','], ['', '.'], $v))));
			db::query("INSERT INTO company_pnl (company_id, `day`, `plan`, row_key, amount, upd_by, upd_dt)
				VALUES ('$cid', '$d', 0, '$rk', '$v', '$uid', '$now')
				ON DUPLICATE KEY UPDATE amount = VALUES(amount), upd_by = VALUES(upd_by), upd_dt = VALUES(upd_dt)");
		}
		rnp_out(['ok' => 1]);
	}

	// бір күннің қолмен жазылған мәндері («Запис қосу» терезесі үшін)
	if (isset($_GET['day'])) {
		$d = (string)$_GET['day'];
		if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $d) || !strtotime($d)) rnp_out(['ok' => 0, 'err' => 'Күн қате']);
		$ids = implode(',', array_keys(rnp_companies($company, $user_right)) ?: [0]);
		$vals = [];
		$in_map = array_flip(rnp_in_map());
		$keys = "'".implode("', '", array_keys($in_map))."'";
		$q = db::query("select company_id as c, row_key as k, amount from company_pnl where company_id in ($ids) and `plan` = 0 and `day` = '$d' and row_key in ($keys)");
		while ($q && $r = mysqli_fetch_assoc($q)) $vals['c'.$r['c'].'_'.$in_map[$r['k']]] = intval($r['amount']);
		rnp_out(['ok' => 1, 'vals' => (object)$vals]);
	}

	rnp_out(['ok' => 0, 'err' => 'Белгісіз сұрау']);
