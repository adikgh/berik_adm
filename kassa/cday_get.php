<? include "../config/core.php";
	include "cday_lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $data = []) { exit(json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE)); }

	if (!$user_id) out(false, ['msg' => 'Кіру рұқсаты жоқ']);
	if (!cday::ready()) out(false, ['msg' => 'courier_day_report кестесі әлі жасалмаған (sql/courier_day_01.sql)']);

	$cmp = (int)$company;
	$day = cday::day(@$_POST['day']);
	$cashbox_id = (int)@$_POST['cashbox_id'];
	// касса осы компанияныкі ме
	if ($cashbox_id && !mysqli_num_rows(db::query("select id from report_1 where id = '$cashbox_id' and company_id = '$cmp'"))) $cashbox_id = 0;
	$couriers = cday::couriers($cmp);

	// бір курьерді немесе барлығын сақтау
	if (isset($_GET['save'])) {
		$ids = @$_POST['all'] ? array_keys($couriers) : [(int)@$_POST['courier_id']];
		$saved = 0;
		foreach ($ids as $uid) {
			if (!isset($couriers[$uid])) continue;
			if (@$_POST['all'] && cday::get($cmp, $uid, $day)) continue; // бұрын сақталғанын қозғамаймыз
			if (cday::save($cmp, $uid, $day, $cashbox_id, $user_id)) $saved++;
		}
		if (!$saved) out(false, ['msg' => 'Сақтайтын дерек жоқ (тапсырыс жоқ)']);
		out(true, ['saved' => $saved]);
	}

	// сақтауды болдырмау
	if (isset($_GET['undo'])) {
		if (@$user_right['positions_id'] == 3) out(false, ['msg' => 'Болдырмауға рұқсат жоқ']);
		$uid = (int)@$_POST['courier_id'];
		if (!cday::undo($cmp, $uid, $day, $cashbox_id)) out(false, ['msg' => 'Есеп табылмады']);
		out(true);
	}

	out(false, ['msg' => 'Белгісіз сұраныс']);
