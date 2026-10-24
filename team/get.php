<? include "../config/core.php";

	// тек басшылық (1 құрушы, 2 директор)
	if (!$user_id || !$company || !in_array(@$user_right['positions_id'], [1, 2])) { echo 'error'; exit(); }

	function tm_esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags($v))); }


	// қосу / өзгерту
	if (isset($_GET['save'])) {
		$staff_id = intval(@$_POST['id']);
		$name = tm_esc(@$_POST['name']);
		$surname = tm_esc(@$_POST['surname']);
		$phone = preg_replace('/\D/', '', @$_POST['phone']);
		$pos = intval(@$_POST['pos']);
		$branch_id = intval(@$_POST['branch']);
		$code = preg_replace('/\D/', '', @$_POST['code']);
		$pass = tm_esc(@$_POST['pass']);
		if ($name == '') { echo 'name'; exit(); }
		if (strlen($phone) < 10) { echo 'phone'; exit(); }
		if ($pos < 2 || !mysqli_num_rows(db::query("select id from user_staff_positions where id = '$pos'"))) { echo 'pos'; exit(); }
		if ($code != '' && strlen($code) != 4) { echo 'code'; exit(); }
		$br = $branch_id ? "'$branch_id'" : "NULL";
		$set_code = $code != '' ? ", `code` = '$code'" : "";
		$set_pass = $pass != '' ? ", `password` = '$pass'" : "";

		if ($staff_id) {
			// өзгерту: тек өз компаниясындағы жұмысшы
			$st = mysqli_fetch_assoc(db::query("select * from user_staff where id = '$staff_id' and company_id = '$company' and positions_id != 1"));
			if (!$st) { echo 'error'; exit(); }
			$uid = $st['user_id'];
			$other = mysqli_fetch_assoc(db::query("select id from user where phone = '$phone' and id != '$uid' limit 1"));
			if ($other) { echo 'phone_busy'; exit(); }
			db::query("UPDATE `user` SET `name` = '$name', `surname` = '$surname', `phone` = '$phone' $set_code $set_pass, `upd_dt` = '".date('Y-m-d H:i:s')."' WHERE id = '$uid'");
			$upd = db::query("UPDATE `user_staff` SET `positions_id` = '$pos', `branch_id` = $br WHERE id = '$staff_id'");
			echo $upd ? 'yes' : 'error';
			exit();
		}

		// жаңа: нөмір базада бар болса, сол адамды қосамыз
		$u = mysqli_fetch_assoc(db::query("select * from user where phone = '$phone' limit 1"));
		if ($u) {
			$uid = $u['id'];
			$busy = mysqli_fetch_assoc(db::query("select * from user_staff where user_id = '$uid' limit 1"));
			if ($busy) { echo ($busy['company_id'] == $company ? 'already' : 'other_company'); exit(); }
			db::query("UPDATE `user` SET `name` = '$name', `surname` = '$surname', `right` = 1 $set_code $set_pass WHERE id = '$uid'");
		} else {
			$uid = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `user`")))['m'] + 1;
			if ($code == '') $code = rand(1000, 9999);
			$ins = db::query("INSERT INTO `user` (`id`, `name`, `surname`, `phone`, `password`, `code`, `right`) VALUES ('$uid', '$name', '$surname', '$phone', '$pass', '$code', 1)");
			if (!$ins) { echo 'error'; exit(); }
		}
		$sid = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `user_staff`")))['m'] + 1;
		$ins = db::query("INSERT INTO `user_staff` (`id`, `user_id`, `positions_id`, `company_id`, `branch_id`) VALUES ('$sid', '$uid', '$pos', '$company', $br)");
		echo $ins ? 'yes' : 'error';
		exit();
	}


	// тізімнен шығару: тек компаниямен байланысы алынады, адамның аккаунты өшпейді
	if (isset($_GET['remove'])) {
		$staff_id = intval(@$_POST['id']);
		$st = mysqli_fetch_assoc(db::query("select * from user_staff where id = '$staff_id' and company_id = '$company' and positions_id != 1"));
		if (!$st) { echo 'error'; exit(); }
		if ($st['user_id'] == $user_id) { echo 'self'; exit(); }
		$del = db::query("DELETE FROM `user_staff` WHERE id = '$staff_id'");
		echo $del ? 'yes' : 'error';
		exit();
	}
