<? include "../../config/core.php";
	// Раздача экранының әрекеттері (orders/dist/index.php, assets/js/dist.js). Тек кірген қызметкер, тек өз компаниясының тапсырысы.
	if (!$user_id) exit('none');
	$company = (int)$company;
	$uid = (int)$user_id;

	function dist_order($id) {
		global $company;
		return mysqli_fetch_assoc(db::query("select id, order_status, order_type from retail_orders where id = '".(int)$id."' and company_id = '$company' limit 1"));
	}
	function dist_log($id, $old, $new) {
		global $uid, $datetime;
		if ($old != $new) db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('".(int)$id."', 'status', '".(int)$old."', '".(int)$new."', '$uid', '$datetime')"); // этап уақыты үшін
	}


	// Статус тарихы (кассадағыдай, тек оқу)
	if (isset($_GET['history'])) {
		require_once __DIR__.'/hist.php';
		$o = mysqli_fetch_assoc(db::query("select * from retail_orders where id = '".(int)@$_POST['id']."' and company_id = '$company' limit 1"));
		if ($o) echo dist_hist_html($o);
		exit();
	}

	// Курьер тағайындау / алып тастау. «Жолда»-ны курьер өзі қояды.
	if (isset($_GET['change_staff'])) {
		$o = dist_order(@$_POST['order_id']);
		if (!$o) exit('none');
		$o_st = (int)$o['order_status'];
		if (!in_array($o_st, [1, 8, 2, 11])) exit('locked'); // жолда/жабық тапсырыстың курьері ауыспайды
		if (@$_POST['id'] == 'off') {
			db::query("UPDATE `retail_orders` SET `сourier_id` = null, order_status = 2 WHERE id = '".(int)$o['id']."'");
			$n_st = 2;
		} else {
			$cid = (int)@$_POST['id'];
			if (!mysqli_num_rows(db::query("select id from company_staff where user_id = '$cid' and positions_id = 6 and company_id = '$company' limit 1"))) exit('none');
			db::query("UPDATE `retail_orders` SET `сourier_id` = '$cid', order_status = 11 WHERE id = '".(int)$o['id']."'");
			$n_st = 11;
		}
		dist_log($o['id'], $o_st, $n_st);
		exit('yes');
	}

	// Кафе: клиентке берілді → Столда (9)
	if (isset($_GET['table'])) {
		$o = dist_order(@$_POST['id']);
		if (!$o || (int)$o['order_type'] != 4 || !in_array((int)$o['order_status'], [1, 8, 2])) exit('none');
		db::query("UPDATE `retail_orders` SET order_status = 9, upd_dt = '$datetime' WHERE id = '".(int)$o['id']."'");
		dist_log($o['id'], $o['order_status'], 9);
		exit('yes');
	}

	// Өзімен / Сервис: клиент алды → Аяқталды (4)
	if (isset($_GET['check'])) {
		$o = dist_order(@$_POST['id']);
		if (!$o || !in_array((int)$o['order_type'], [2, 3]) || !in_array((int)$o['order_status'], [1, 8, 2])) exit('none');
		db::query("UPDATE `retail_orders` SET `order_status` = 4, upd_dt = '$datetime' WHERE id = '".(int)$o['id']."'");
		dist_log($o['id'], $o['order_status'], 4);
		exit('yes');
	}

	// ---------- Курьерлер (orders/dist/couriers.php) және профиль фотосы (orders/dist/acc.php): тек POST + CSRF ----------
	if (isset($_GET['cr_add']) || isset($_GET['cr_edit']) || isset($_GET['cr_del']) || isset($_GET['acc_img'])) {
		if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) exit('csrf');
	}
	$cr_phone = function () { $p = preg_replace('/\D/', '', (string)@$_POST['phone']); if (strlen($p) == 11 && ($p[0] == '7' || $p[0] == '8')) $p = substr($p, 1); return $p; };
	$cr_txt = function ($k) { return mb_substr(trim(strip_tags((string)@$_POST[$k])), 0, 60); };

	// Курьер қосу: адам бұрыннан бар болса (клиент т.б.) сол жазба, жоқ болса жаңасы (құпиясөзі 123456)
	if (isset($_GET['cr_add'])) {
		$name = $cr_txt('name'); $surname = $cr_txt('surname'); $phone = $cr_phone();
		if ($name === '') exit('name');
		if (!preg_match('/^7\d{9}$/', $phone)) exit('phone');
		$st = mysqli_prepare(db::$connect, "SELECT id, `right`, password FROM `user` WHERE phone = ? LIMIT 1");
		mysqli_stmt_bind_param($st, 's', $phone); mysqli_stmt_execute($st);
		$u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
		if (!$u) {
			$pw = pass_sql('123456');
			$st = mysqli_prepare(db::$connect, "INSERT INTO `user` (`name`, `surname`, `phone`, `password`, `right`) VALUES (?, ?, ?, ?, 1)");
			mysqli_stmt_bind_param($st, 'ssss', $name, $surname, $phone, $pw);
			if (!mysqli_stmt_execute($st)) exit('error');
			$cid = (int)mysqli_insert_id(db::$connect);
		} else {
			$cid = (int)$u['id'];
			if (!$u['right'] || !$u['password']) db::query("UPDATE `user` SET `right` = 1, `password` = IFNULL(`password`, '".pass_sql('123456')."') WHERE id = '$cid'");
		}
		if (mysqli_num_rows(db::query("SELECT id FROM company_staff WHERE user_id = '$cid' AND company_id = '$company' LIMIT 1"))) exit('busy'); // бұл компанияда басқа лауазыммен бар
		db::query("INSERT INTO `company_staff` (`user_id`, `positions_id`, `company_id`) VALUES ('$cid', 6, '$company')");
		exit('yes');
	}

	// Курьерді өзгерту: аты, тегі, нөмірі, жаңа құпиясөз (бос болса өзгермейді). Тек осы компанияның курьері
	if (isset($_GET['cr_edit'])) {
		$cid = (int)@$_POST['id'];
		if (!mysqli_num_rows(db::query("SELECT id FROM company_staff WHERE user_id = '$cid' AND positions_id = 6 AND company_id = '$company' LIMIT 1"))) exit('none');
		$name = $cr_txt('name'); $surname = $cr_txt('surname'); $phone = $cr_phone(); $pass = trim((string)@$_POST['pass']);
		if ($name === '') exit('name');
		if (!preg_match('/^7\d{9}$/', $phone)) exit('phone');
		if ($pass !== '' && mb_strlen($pass) < 6) exit('pass');
		$st = mysqli_prepare(db::$connect, "SELECT id FROM `user` WHERE phone = ? AND id <> ? LIMIT 1");
		mysqli_stmt_bind_param($st, 'si', $phone, $cid); mysqli_stmt_execute($st);
		if (mysqli_fetch_assoc(mysqli_stmt_get_result($st))) exit('phone_busy');
		$st = mysqli_prepare(db::$connect, "UPDATE `user` SET `name` = ?, `surname` = ?, `phone` = ?, `upd_dt` = ? WHERE id = ?");
		mysqli_stmt_bind_param($st, 'ssssi', $name, $surname, $phone, $datetime, $cid);
		if (!mysqli_stmt_execute($st)) exit('error');
		if ($pass !== '') db::query("UPDATE `user` SET `password` = '".pass_sql($pass)."' WHERE id = '$cid'");
		exit('yes');
	}

	// Курьерді тізімнен шығару: тек компаниямен байланысы алынады, адамның аккаунты өшпейді (team/get.php?remove сияқты)
	if (isset($_GET['cr_del'])) {
		$sid = (int)@$_POST['id'];
		db::query("DELETE FROM company_staff WHERE id = '$sid' AND positions_id = 6 AND company_id = '$company'");
		exit('yes');
	}

	// Профиль фотосы: JPG/PNG/WEBP, 8 МБ дейін, 400x400 шаршы JPG болып сақталады
	if (isset($_GET['acc_img'])) {
		$f = @$_FILES['img'];
		if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) exit('file');
		if ($f['size'] > 8 * 1024 * 1024) exit('size');
		$info = @getimagesize($f['tmp_name']);
		$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
		if (!$info || !isset($types[$info[2]]) || $info[0] < 50 || $info[1] < 50 || $info[0] > 8000 || $info[1] > 8000) exit('type');
		$dir = $_SERVER['DOCUMENT_ROOT'].'/assets/uploads/users/';
		$name = 'u'.$uid.'_'.bin2hex(random_bytes(6));
		if (function_exists('imagecreatetruecolor')) {
			$src = $info[2] == IMAGETYPE_JPEG ? @imagecreatefromjpeg($f['tmp_name']) : ($info[2] == IMAGETYPE_PNG ? @imagecreatefrompng($f['tmp_name']) : @imagecreatefromwebp($f['tmp_name']));
			if (!$src) exit('type');
			if ($info[2] == IMAGETYPE_JPEG && function_exists('exif_read_data')) { // телефон фотосының бұрылысы
				$o = (int)@exif_read_data($f['tmp_name'])['Orientation'];
				if ($o == 3) $src = imagerotate($src, 180, 0); elseif ($o == 6) $src = imagerotate($src, -90, 0); elseif ($o == 8) $src = imagerotate($src, 90, 0);
			}
			$w = imagesx($src); $h = imagesy($src); $side = min($w, $h);
			$dst = imagecreatetruecolor(400, 400);
			imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
			imagecopyresampled($dst, $src, 0, 0, (int)(($w - $side) / 2), (int)(($h - $side) / 2), 400, 400, $side, $side);
			$name .= '.jpg';
			if (!imagejpeg($dst, $dir.$name, 85)) exit('save');
		} else {
			$name .= '.'.$types[$info[2]];
			if (!move_uploaded_file($f['tmp_name'], $dir.$name)) exit('save');
		}
		$old = (string)@$user['img'];
		$st = mysqli_prepare(db::$connect, "UPDATE `user` SET img = ?, upd_dt = ? WHERE id = ?");
		mysqli_stmt_bind_param($st, 'ssi', $name, $datetime, $uid); mysqli_stmt_execute($st);
		if ($old !== '' && preg_match('/^u'.$uid.'_[a-f0-9]{12}\.(jpg|png|webp)$/', $old) && is_file($dir.$old)) @unlink($dir.$old); // тек өзі жүктеген ескі фото
		exit(json_encode(['ok' => 1, 'img' => '/assets/uploads/users/'.$name]));
	}

	echo 'none';
