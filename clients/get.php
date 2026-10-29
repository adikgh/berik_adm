<? include "../config/core.php";
	include "lib.php";

	if (!$user_id || @$user_right['positions_id'] == 3) exit('error');
	$cmp = (int)$company;
	$esc = function ($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$v))); };

	// Клиентті сақтау
	if (isset($_GET['client_save'])) {
		$id = (int)@$_POST['id'];
		$name = $esc(@$_POST['name']);
		$address = $esc(@$_POST['address']);
		$note = $esc(@$_POST['note']);
		$upd = db::query("update company_client set
				name = ".($name !== '' ? "'$name'" : "NULL").",
				address = ".($address !== '' ? "'$address'" : "NULL").",
				note = ".($note !== '' ? "'$note'" : "NULL").",
				upd_dt = '$datetime'
			where id = '$id' and company_id = '$cmp'");
		exit($upd ? 'yes' : 'error');
	}

	// Архив (толық өшірілмейді)
	if (isset($_GET['client_archive'])) {
		$id = (int)@$_POST['id'];
		$v = (int)@$_POST['v'] ? 1 : 0;
		$upd = db::query("update company_client set archive = '$v', upd_dt = '$datetime' where id = '$id' and company_id = '$cmp'");
		exit($upd ? 'yes' : 'error');
	}
