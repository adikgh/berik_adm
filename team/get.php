<? include "../config/core.php";

	// тек басшылық (1 құрушы, 2 директор)
	if (!$user_id || !$company || !in_array(@$user_right['positions_id'], [1, 2])) { echo 'error'; exit(); }

	function tm_esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags($v))); }

	// адамның компаниялардағы орны. here — осы компаниядағы лауазымы, other — басқа компанияда жұмыс істейді,
	// lead — басқа компаниядағы лауазымы: мұнда да тек сол лауазыммен қосылады (лауазым өзгермейді).
	// Басқа лауазым керек болса, алдымен ол компания бұл кісіні өз тізімінен шығарады.
	function tm_other($uid, $company) {
		$r = ['here' => 0, 'other' => 0, 'lead' => 0];
		$rank = [2, 3, 7, 8, 5, 9, 10, 6, 4]; // бірнеше компанияда әртүрлі болса — ең жоғарысы
		$q = db::query("select company_id, positions_id, archive from company_staff where user_id = '".(int)$uid."'");
		while ($s = mysqli_fetch_assoc($q)) {
			$p = (int)$s['positions_id'];
			if ($s['company_id'] == $company) { $r['here'] = $p ?: -1; continue; }
			if (!$s['company_id'] || $s['archive'] || $p == 1) continue; // Создатель мен архивтегілер есепке алынбайды
			$r['other'] = 1;
			$i = array_search($p, $rank);
			if ($i !== false && (!$r['lead'] || $i < array_search($r['lead'], $rank))) $r['lead'] = $p;
		}
		return $r;
	}


	// нөмір бойынша базадағы адамды табу (қосу формасы үшін)
	if (isset($_GET['find'])) {
		header('Content-Type: application/json; charset=utf-8');
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		if (strlen($phone) < 10) { echo '{}'; exit(); }
		$u = mysqli_fetch_assoc(db::query("select id, name, surname from user where phone = '$phone' limit 1"));
		if (!$u) { echo '{}'; exit(); }
		$o = tm_other($u['id'], $company);
		echo json_encode([
			'name' => $u['name'], 'surname' => (string)$u['surname'],
			'staff' => $o['here'] ? 'here' : ($o['lead'] ? 'lead' : ($o['other'] ? 'other' : '')),
			'pos' => $o['here'] ? max(0, $o['here']) : $o['lead'],
		], JSON_UNESCAPED_UNICODE);
		exit();
	}


	// қосу / өзгерту
	if (isset($_GET['save'])) {
		$staff_id = intval(@$_POST['id']);
		$name = tm_esc(@$_POST['name']);
		$surname = tm_esc(@$_POST['surname']);
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		$pos = intval(@$_POST['pos']);
		$branch_id = intval(@$_POST['branch']);
		$code = preg_replace('/\D/', '', @$_POST['code']);
		$pass = tm_esc(@$_POST['pass']);
		if ($name == '') { echo 'name'; exit(); }
		if (strlen($phone) < 10) { echo 'phone'; exit(); }
		if ($pos < 2 || !mysqli_num_rows(db::query("select id from company_staff_positions where id = '$pos'"))) { echo 'pos'; exit(); }
		if ($code != '' && strlen($code) != 4) { echo 'code'; exit(); }
		$br = $branch_id ? "'$branch_id'" : "NULL";
		$set_code = $code != '' ? ", `code` = '$code'" : "";
		$set_pass = $pass != '' ? ", `password` = '".pass_sql(@$_POST['pass'])."'" : "";

		if ($staff_id) {
			// өзгерту: тек өз компаниясындағы жұмысшы
			$st = mysqli_fetch_assoc(db::query("select * from company_staff where id = '$staff_id' and company_id = '$company' and positions_id != 1"));
			if (!$st) { echo 'error'; exit(); }
			$uid = $st['user_id'];
			$o = tm_other($uid, $company);
			if ($o['lead'] && $pos != $o['lead']) { echo 'pos_lock'; exit(); }
			// басқа компанияда да жұмыс істейтін адамның, не басқа директордың аккаунтын (аты, нөмірі, құпиясөзі) өзгертпейміз
			$lock = $o['other'] || ($st['positions_id'] == 2 && @$user_right['positions_id'] != 1 && $uid != $user_id);
			if (!$lock) {
				$other = mysqli_fetch_assoc(db::query("select id from user where phone = '$phone' and id != '$uid' limit 1"));
				if ($other) { echo 'phone_busy'; exit(); }
				db::query("UPDATE `user` SET `name` = '$name', `surname` = '$surname', `phone` = '$phone' $set_pass, `upd_dt` = '".date('Y-m-d H:i:s')."' WHERE id = '$uid'");
			}
			// POS коды company_staff кестесінде
			$upd = db::query("UPDATE `company_staff` SET `positions_id` = '$pos', `branch_id` = $br $set_code WHERE id = '$staff_id'");
			echo $upd ? 'yes' : 'error';
			exit();
		}

		// жаңа: нөмір базада бар болса, сол адамды қосамыз
		$u = mysqli_fetch_assoc(db::query("select * from user where phone = '$phone' limit 1"));
		if ($u) {
			$uid = $u['id'];
			$o = tm_other($uid, $company);
			if ($o['here']) { echo 'already'; exit(); }
			if ($o['other'] && !$o['lead']) { echo 'other_company'; exit(); }
			if ($o['lead'] && $pos != $o['lead']) { echo 'pos_lock'; exit(); }
			// басқа компаниядағы басшы: аккаунты сол күйі қалады, тек осы компанияға жол қосылады
			if (!$o['other']) db::query("UPDATE `user` SET `name` = '$name', `surname` = '$surname', `right` = 1 $set_pass WHERE id = '$uid'");
		} else {
			$uid = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `user`")))['m'] + 1;
			$ins = db::query("INSERT INTO `user` (`id`, `name`, `surname`, `phone`, `password`, `right`) VALUES ('$uid', '$name', '$surname', '$phone', ".($pass != '' ? "'".pass_sql(@$_POST['pass'])."'" : "NULL").", 1)");
			if (!$ins) { echo 'error'; exit(); }
		}
		if ($code == '') $code = rand(1000, 9999);
		$sid = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `company_staff`")))['m'] + 1;
		$ins = db::query("INSERT INTO `company_staff` (`id`, `user_id`, `positions_id`, `code`, `company_id`, `branch_id`) VALUES ('$sid', '$uid', '$pos', '$code', '$company', $br)");
		echo $ins ? 'yes' : 'error';
		exit();
	}


	// тізімнен шығару: тек компаниямен байланысы алынады, адамның аккаунты өшпейді
	if (isset($_GET['remove'])) {
		$staff_id = intval(@$_POST['id']);
		$st = mysqli_fetch_assoc(db::query("select * from company_staff where id = '$staff_id' and company_id = '$company' and positions_id != 1"));
		if (!$st) { echo 'error'; exit(); }
		if ($st['user_id'] == $user_id) { echo 'self'; exit(); }
		$del = db::query("DELETE FROM `company_staff` WHERE id = '$staff_id'");
		echo $del ? 'yes' : 'error';
		exit();
	}
