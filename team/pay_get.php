<? include "../config/core.php";
	include "pay_lib.php";

	// тек басшылық (1 құрушы, 2 директор)
	if (!$user_id || !$company || !zp::can()) { echo 'error'; exit(); }
	zp::install();
	$cmp = (int)$company;


	// төлем жазу
	if (isset($_GET['save'])) {
		$uid = intval(@$_POST['user']);
		$amount = intval(preg_replace('/\D/', '', (string)@$_POST['amount']));
		$type = intval(@$_POST['type']);
		$method = isset(zp::$methods[@$_POST['method']]) ? $_POST['method'] : 'cash';
		$date = (string)@$_POST['date'];
		$comment = zp::esc(mb_substr((string)@$_POST['comment'], 0, 250));

		$st = mysqli_fetch_assoc(db::query("select * from company_staff where user_id = '$uid' and company_id = '$cmp' and positions_id != 1 limit 1"));
		if (!$st) { echo 'user'; exit(); }
		if ($amount <= 0) { echo 'amount'; exit(); }
		if (!isset(zp::$types[$type])) $type = 1;
		if (!preg_match('/^20\d{2}-\d{2}-\d{2}$/', $date) || !strtotime($date)) $date = date('Y-m-d', time() - 6 * 3600);

		$br = $st['branch_id'] ? "'".(int)$st['branch_id']."'" : "NULL";
		$ins = db::query("INSERT INTO `staff_pay` (`company_id`, `branch_id`, `user_id`, `positions_id`, `type`, `amount`, `method`, `pay_date`, `comment`, `by_user`)
			VALUES ('$cmp', $br, '$uid', '".(int)$st['positions_id']."', '$type', '$amount', '$method', '$date', '$comment', '".(int)$user_id."')");
		echo $ins ? 'yes' : 'error';
		exit();
	}


	// төлемді өшіру
	if (isset($_GET['remove'])) {
		$id = intval(@$_POST['id']);
		$del = db::query("DELETE FROM `staff_pay` WHERE id = '$id' and company_id = '$cmp'");
		echo ($del && mysqli_affected_rows(db::$connect) == 1) ? 'yes' : 'error';
		exit();
	}

	echo 'error';
