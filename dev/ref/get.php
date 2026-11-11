<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id) out(['ok' => false, 'msg' => t::w('Please sign in again', 'Қайта кіріңіз')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);

	if (isset($_GET['ref_save'])) {
		if (!dev::can('ref')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$id = (int)@$_POST['id'];
		$nick = dev::nick_clean(@$_POST['nick']);
		if (strlen($nick) < 2) out(['ok' => false, 'msg' => t::w('Code: at least 2 characters (latin letters, digits, _)', 'Код кемінде 2 таңба: латын әріп, сан, _')]);
		if (dev::row("select id from company_ref where nick = '" . dev::e($nick) . "' and id <> '$id'")) out(['ok' => false, 'msg' => t::w('This code is taken, choose another', 'Бұл код бос емес, басқасын таңдаңыз')]);
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		$cid = (int)@$_POST['company_id'];
		$v = "nick = '" . dev::e($nick) . "', name = '" . dev::e(mb_substr((string)@$_POST['name'], 0, 250)) . "', phone = " . ($phone ? "'$phone'" : "NULL") .
			", company_id = " . ($cid ? "'$cid'" : "NULL") . ", comment = '" . dev::e(mb_substr((string)@$_POST['comment'], 0, 250)) . "'";
		if ($id) db::query("update company_ref set $v where id = '$id'");
		else { db::query("insert into company_ref set $v"); $id = (int)mysqli_insert_id(db::$connect); }
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды'), 'go' => '/ref/item.php?id=' . $id]);
	}

	if (isset($_GET['ref_arh'])) {
		if (!dev::can('ref')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		db::query("update company_ref set arh = 1 - arh where id = '" . (int)@$_POST['id'] . "'");
		out(['ok' => true]);
	}

	// Бонусты ақшалай беру
	if (isset($_GET['ref_out'])) {
		if (!dev::can('ref_pay')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$rid = (int)@$_POST['ref_id'];
		$amount = (int)preg_replace('/\D/', '', (string)@$_POST['amount']);
		if (!dev::row("select id from company_ref where id = '$rid'")) out(['ok' => false, 'msg' => t::w('Referrer not found', 'Шақырушы табылмады')]);
		if ($amount <= 0) out(['ok' => false, 'msg' => t::w('Enter the amount', 'Соманы жазыңыз')]);
		if ($amount > dev::ref_balance($rid)['left']) out(['ok' => false, 'msg' => t::w('More than the balance', 'Қалдықтан көп')]);
		db::query("insert into company_ref_out (ref_id, amount, kind, comment, user_id) values ('$rid', '$amount', 'cash', '" . dev::e(mb_substr((string)@$_POST['comment'], 0, 250)) . "', '$user_id')");
		out(['ok' => true, 'msg' => t::w('Recorded', 'Жазылды')]);
	}
	if (isset($_GET['out_del'])) {
		if (!dev::can('ref_pay')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		db::query("delete from company_ref_out where id = '" . (int)@$_POST['id'] . "' and kind = 'cash'");
		out(['ok' => true, 'msg' => t::w('Deleted', 'Өшірілді')]);
	}
	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
