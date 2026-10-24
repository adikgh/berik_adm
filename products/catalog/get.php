<? include "../../config/core.php";

	// кірмеген адамға ештеңе жоқ
	if (!$user_id || !$company) { echo 'error'; exit(); }

	function ctg_esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags($v))); }
	function ctg_color($v) { return preg_match('/^#[0-9a-fA-F]{3,6}$/', $v) ? $v : '#666666'; }


	// қосу / өзгерту
	if (isset($_GET['save'])) {
		$id = intval(@$_POST['id']);
		$name_kz = ctg_esc(@$_POST['name_kz']);
		$name_ru = ctg_esc(@$_POST['name_ru']);
		$color = ctg_color(@$_POST['color']);
		if ($name_kz == '') { echo 'empty'; exit(); }
		if ($name_ru == '') $name_ru = $name_kz;

		if ($id) {
			$upd = db::query("UPDATE `product_catalog` SET `name_kz` = '$name_kz', `name_ru` = '$name_ru', `color` = '$color', `ubd_dt` = '".date('Y-m-d H:i:s')."' WHERE id = '$id' and company_id = '$company'");
		} else {
			$new_id = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `product_catalog`")))['m'] + 1;
			$number = (mysqli_fetch_assoc(db::query("SELECT max(number) m FROM `product_catalog` where company_id = '$company' and (arh is null or arh = 0)")))['m'] + 1;
			$upd = db::query("INSERT INTO `product_catalog` (`id`, `name_kz`, `name_ru`, `number`, `company_id`, `type`, `color`, `level`) VALUES ('$new_id', '$name_kz', '$name_ru', '$number', '$company', 'main', '$color', 1)");
		}
		echo $upd ? 'yes' : 'error';
		exit();
	}


	// ретін сақтау: ids[] жаңа ретпен келеді
	if (isset($_GET['sort'])) {
		$ids = @$_POST['ids'];
		if (!is_array($ids)) { echo 'error'; exit(); }
		$n = 0;
		foreach ($ids as $id) {
			$id = intval($id); if (!$id) continue;
			$n++;
			db::query("UPDATE `product_catalog` SET `number` = '$n' WHERE id = '$id' and company_id = '$company'");
		}
		echo 'yes';
		exit();
	}


	// өшіру = архивке жіберу (толық өшпейді). Ішіндегі тағамдар орнында қалады, «Каталогсыз» тобында көрінеді.
	if (isset($_GET['delete'])) {
		$id = intval(@$_POST['id']);
		$upd = db::query("UPDATE `product_catalog` SET `arh` = 1 WHERE id = '$id' and company_id = '$company'");
		echo $upd ? 'yes' : 'error';
		exit();
	}
