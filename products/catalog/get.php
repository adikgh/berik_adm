<? include "../../config/core.php";

	// кірмеген адамға ештеңе жоқ
	if (!$user_id || !$company) { echo 'error'; exit(); }

	function ctg_esc($v) { return mysqli_real_escape_string(db::$connect, trim(strip_tags($v))); }
	function ctg_color($v) { return preg_match('/^#[0-9a-fA-F]{3,6}$/', $v) ? $v : '#666666'; }
	// Мәзірдегі санат суреті: product_catalog.img (фото файлы) және icon (эмодзи). Бағандар жоқ болса, өзі қосылады (тек қосу, ештеңе өшпейді).
	function ctg_cols() {
		if (!fun::has_col('product_catalog', 'img')) db::query("ALTER TABLE `product_catalog` ADD `img` varchar(255) NULL DEFAULT NULL");
		if (!fun::has_col('product_catalog', 'icon')) db::query("ALTER TABLE `product_catalog` ADD `icon` varchar(16) NULL DEFAULT NULL");
	}
	// бір эмодзи ғана (әріп, сан, тег емес), 16 байтқа дейін
	function ctg_icon($v) {
		$v = trim((string)$v);
		if ($v === '' || strlen($v) > 16 || preg_match('/[\p{L}\p{N}<>"\'\s]/u', $v)) return '';
		return $v;
	}


	// қосу / өзгерту
	if (isset($_GET['save'])) {
		$id = intval(@$_POST['id']);
		$name_kz = ctg_esc(@$_POST['name_kz']);
		$name_ru = ctg_esc(@$_POST['name_ru']);
		$color = ctg_color(@$_POST['color']);
		if ($name_kz == '') { echo 'empty'; exit(); }
		if ($name_ru == '') $name_ru = $name_kz;
		ctg_cols();
		$icon = mysqli_real_escape_string(db::$connect, ctg_icon(@$_POST['icon']));
		$icon = $icon === '' ? 'NULL' : "'$icon'";

		if ($id) {
			$upd = db::query("UPDATE `product_catalog` SET `name_kz` = '$name_kz', `name_ru` = '$name_ru', `color` = '$color', `icon` = $icon, `ubd_dt` = '".date('Y-m-d H:i:s')."' WHERE id = '$id' and company_id = '$company'");
		} else {
			$new_id = (mysqli_fetch_assoc(db::query("SELECT max(id) m FROM `product_catalog`")))['m'] + 1;
			$number = (mysqli_fetch_assoc(db::query("SELECT max(number) m FROM `product_catalog` where company_id = '$company' and (arh is null or arh = 0)")))['m'] + 1;
			$upd = db::query("INSERT INTO `product_catalog` (`id`, `name_kz`, `name_ru`, `number`, `company_id`, `type`, `color`, `level`, `icon`) VALUES ('$new_id', '$name_kz', '$name_ru', '$number', '$company', 'main', '$color', 1, $icon)");
			$id = $new_id;
		}
		echo $upd ? 'yes:'.$id : 'error'; // id: жаңа санатқа фотоны бірден жүктеу үшін
		exit();
	}


	// Санат фотосы: ?img (жүктеу) және ?img_del. Файл assets/uploads/catalog/k{id}_{уақыт}.jpg, 480px-ке дейін кішірейеді. Жауап: JSON.
	if (isset($_GET['img']) || isset($_GET['img_del'])) {
		header('Content-Type: application/json; charset=utf-8');
		$out = function ($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); };
		ctg_cols();
		$id = intval(@$_POST['id']);
		$q = db::query("select id, img from product_catalog where id = '$id' and company_id = '$company'");
		if (!$q || !mysqli_num_rows($q)) $out(['ok' => 0, 'err' => 'error']);
		$old = (string)mysqli_fetch_assoc($q)['img'];
		$dir = __DIR__.'/../../assets/uploads/catalog/';
		$rm_old = function () use ($old, $id, $dir) { if (preg_match('/^k'.$id.'_\d+\.(jpg|png|webp)$/', $old) && is_file($dir.$old)) @unlink($dir.$old); };

		if (isset($_GET['img_del'])) {
			db::query("update product_catalog set img = NULL where id = '$id' and company_id = '$company'");
			$rm_old();
			$out(['ok' => 1]);
		}

		$f = @$_FILES['file'];
		if (!$f || !empty($f['error']) || !is_uploaded_file($f['tmp_name'])) $out(['ok' => 0, 'err' => 'file']);
		if ($f['size'] > 15 * 1024 * 1024) $out(['ok' => 0, 'err' => 'size']);
		$info = @getimagesize($f['tmp_name']);
		$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
		if (!$info || !isset($types[$info[2]])) $out(['ok' => 0, 'err' => 'type']);
		if (!is_dir($dir)) @mkdir($dir, 0775, true);

		$name = 'k'.$id.'_'.time();
		$saved = false;
		// GD бар болса: шаршы етіп ортасынан қиып, 480px JPEG (мәзірде дөңгелек болып шығады)
		if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
			$src = @imagecreatefromstring(file_get_contents($f['tmp_name']));
			if ($src) {
				$w = imagesx($src); $h = imagesy($src); $s = min($w, $h); $n = min(480, $s);
				$dst = imagecreatetruecolor($n, $n);
				imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // мөлдір фон ақ болады
				imagecopyresampled($dst, $src, 0, 0, (int)(($w - $s) / 2), (int)(($h - $s) / 2), $n, $n, $s, $s);
				$name .= '.jpg';
				$saved = imagejpeg($dst, $dir.$name, 85);
				imagedestroy($src); imagedestroy($dst);
			}
		}
		if (!$saved) {
			$name = 'k'.$id.'_'.time().'.'.$types[$info[2]];
			$saved = move_uploaded_file($f['tmp_name'], $dir.$name);
		}
		if (!$saved) $out(['ok' => 0, 'err' => 'save']);
		$n = mysqli_real_escape_string(db::$connect, $name);
		if (!db::query("update product_catalog set img = '$n' where id = '$id' and company_id = '$company'")) { @unlink($dir.$name); $out(['ok' => 0, 'err' => 'db']); }
		if ($old !== $name) $rm_old();
		$out(['ok' => 1, 'img' => $name, 'url' => '/assets/uploads/catalog/'.$name]);
	}


	// QR мәзірдегі «Ұсыныс»: бүкіл санатты белгілеу / алу (product_catalog.recommend)
	if (isset($_GET['rec'])) {
		$id = intval(@$_POST['id']); $on = (int)!empty($_POST['on']);
		$upd = db::query("UPDATE `product_catalog` SET `recommend` = '$on' WHERE id = '$id' and company_id = '$company'");
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
