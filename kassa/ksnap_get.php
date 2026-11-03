<? include "../config/core.php";
	include_once "ksnap_lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $data = []) { exit(json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE)); }

	if (!$user_id) out(false, ['msg' => 'Кіру рұқсаты жоқ']);
	if (@$user_right['positions_id'] == 3) out(false, ['msg' => 'Рұқсат жоқ']);
	if (!ksnap::ready()) out(false, ['msg' => 'company_kassa_day кестесі құрылмады']);

	// өткен бір күннің касса есебін қайта сақтау (компания + барлық точка)
	if (isset($_GET['save'])) {
		$day = (string)@$_POST['day'];
		if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $day) || !strtotime($day)) out(false, ['msg' => 'Күн қате']);
		if ($day >= ksnap::today()) out(false, ['msg' => 'Бүгінгі есеп 06:00-де сақталады']);
		ksnap::save_company($company, $day, 0);
		out(true);
	}

	out(false, ['msg' => 'Белгісіз сұраныс']);
