<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id || !dev::can('team')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);

	if (isset($_GET['add'])) {
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		if (strlen($phone) != 10) out(['ok' => false, 'msg' => t::w('Enter the phone in full', 'Телефонды толық жазыңыз')]);
		$role = (int)@$_POST['role'];
		if (!isset(dev::$roles[$role])) out(['ok' => false, 'msg' => t::w('Choose a role', 'Рөлді таңдаңыз')]);
		$rid = (int)@$_POST['ref_id'];
		if ($role == 4 && !$rid) out(['ok' => false, 'msg' => t::w('Choose a referral code for the top partner', 'Топ серіктеске шақыру кодын таңдаңыз')]);
		$u = dev::row("select * from `user` where phone = '$phone' limit 1");
		if (!$u) {
			$name = trim((string)@$_POST['name']); $pass = trim((string)@$_POST['password']);
			if ($name === '' || mb_strlen($pass) < 6) out(['ok' => false, 'msg' => t::w('This number is new. Enter a name and a password (6+ characters)', 'Нөмір базада жоқ. Аты мен парольді (кемінде 6 таңба) жазыңыз')]);
			db::query("insert into `user` (name, phone, password, `right`, ins_dt) values ('" . dev::e(mb_substr($name, 0, 250)) . "', '$phone', '" . pass_sql($pass) . "', 1, '$datetime')");
			$u = dev::row("select * from `user` where phone = '$phone' limit 1");
			if (!$u) out(['ok' => false, 'msg' => t::w('Could not create the person', 'Адам ашылмады')]);
		} elseif (!$u['right']) db::query("update `user` set `right` = 1 where id = '" . (int)$u['id'] . "'");
		$uid = (int)$u['id'];
		$v = "role = '$role', ref_id = " . ($role == 4 ? "'$rid'" : "NULL") . ", arh = 0";
		if (dev::row("select id from dev_staff where user_id = '$uid'")) db::query("update dev_staff set $v where user_id = '$uid'");
		else db::query("insert into dev_staff set user_id = '$uid', $v");
		out(['ok' => true, 'msg' => t::w('Added', 'Қосылды')]);
	}

	if (isset($_GET['arh'])) {
		$id = (int)@$_POST['id'];
		$s = dev::row("select * from dev_staff where id = '$id'");
		if (!$s || (int)$s['user_id'] === $user_id) out(['ok' => false, 'msg' => t::w('You cannot close your own access', 'Өзіңізді жаба алмайсыз')]);
		db::query("update dev_staff set arh = 1 - arh where id = '$id'");
		out(['ok' => true]);
	}
	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
