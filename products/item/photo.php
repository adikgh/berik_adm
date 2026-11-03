<? include "../../config/core.php";

	include "../../config/cfile.php";

	// Тағам фотосы (product.img): жүктеу (?up), Drive (?drive) және өшіру (?del). Жауап: JSON.
	// Google Drive қосулы болса: браузер фотоны Drive-қа өзі жүктейді, мұнда тек сілтемесі product.img-ге жазылады (?drive).
	// Drive қосылмаса (?up): файл adm.berik.c/assets/uploads/products/ ішіне p{id}_{уақыт}.jpg болып сақталады (800px-ке дейін кішірейтіледі).
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); };

	if (!$user_id) $out(['ok' => 0, 'err' => 'auth']);
	$cmp = (int)$company;
	$id = (int)@$_POST['id'];
	$q = db::query("select id, img from product where id = '$id' and company_id = '$cmp'");
	if (!$q || !mysqli_num_rows($q)) $out(['ok' => 0, 'err' => 'error']);
	$old = (string)mysqli_fetch_assoc($q)['img'];
	$dir = __DIR__.'/../../assets/uploads/products/';

	// ескі файлды тек өзіміз жасаған болса өшіреміз (басқа тағам да қолдануы мүмкін)
	$rm_old = function () use ($old, $id, $dir) {
		if ($old !== '' && preg_match('/^p'.$id.'_\d+\.(jpg|png|webp)$/', $old) && is_file($dir.$old)) @unlink($dir.$old);
	};

	// ескі фото Drive-та болса, оны да өшіреміз (тек осы тағамға жүктелгені)
	$rm_old_drive = function () use ($old, $id, $cmp) {
		if (!preg_match('~^https://lh3\.googleusercontent\.com/d/([A-Za-z0-9_-]+)~', $old, $m)) return;
		cfile::install();
		$q = db::query("select id from company_file where company_id = '$cmp' and kind = 'product' and ref_id = '$id' and drive_id = '".$m[1]."'");
		if ($q && mysqli_num_rows($q) && cfile::drive_delete($m[1])) db::query("delete from company_file where id = '".(int)mysqli_fetch_assoc($q)['id']."'");
	};

	if (isset($_GET['del'])) {
		db::query("update product set img = NULL, upd_dt = NOW() where id = '$id' and company_id = '$cmp'");
		$rm_old(); $rm_old_drive();
		$out(['ok' => 1]);
	}

	if (isset($_GET['drive'])) {
		$f = cfile::get((int)@$_POST['file_id'], $cmp);
		if (!$f || $f['kind'] != 'product' || !$f['drive_id'] || ((int)$f['ref_id'] && (int)$f['ref_id'] != $id)) $out(['ok' => 0, 'err' => 'file']);
		if (strpos((string)$f['mime'], 'image/') !== 0) $out(['ok' => 0, 'err' => 'type']);
		// касса мен сайт ашуы үшін фото «сілтемесі барлар көре алады» болады
		if (!cfile::make_public($f['drive_id'])) $out(['ok' => 0, 'err' => 'drive', 'msg' => cfile::$err]);
		$url = cfile::img_url($f['drive_id']);
		$u = mysqli_real_escape_string(db::$connect, $url);
		if (!db::query("update product set img = '$u', upd_dt = NOW() where id = '$id' and company_id = '$cmp'")) $out(['ok' => 0, 'err' => 'db']);
		db::query("update company_file set ref_id = '$id' where id = '".(int)$f['id']."'");
		if ($old !== $url) { $rm_old(); $rm_old_drive(); }
		$out(['ok' => 1, 'img' => $url, 'url' => $url]);
	}

	if (isset($_GET['up'])) {
		$f = @$_FILES['file'];
		if (!$f || !empty($f['error']) || !is_uploaded_file($f['tmp_name'])) $out(['ok' => 0, 'err' => 'file']);
		if ($f['size'] > 15 * 1024 * 1024) $out(['ok' => 0, 'err' => 'size']);
		$info = @getimagesize($f['tmp_name']);
		$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
		if (!$info || !isset($types[$info[2]])) $out(['ok' => 0, 'err' => 'type']);
		if (!is_dir($dir)) @mkdir($dir, 0775, true);

		$name = 'p'.$id.'_'.time();
		$saved = false;
		// GD бар болса: 800px-ке дейін кішірейтіп, JPEG етіп сақтаймыз (касса/сайт тез ашылуы үшін)
		if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
			$src = @imagecreatefromstring(file_get_contents($f['tmp_name']));
			if ($src) {
				$w = imagesx($src); $h = imagesy($src); $k = min(1, 800 / max($w, $h));
				$nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
				$dst = imagecreatetruecolor($nw, $nh);
				imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // мөлдір фон ақ болады
				imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
				$name .= '.jpg';
				$saved = imagejpeg($dst, $dir.$name, 85);
				imagedestroy($src); imagedestroy($dst);
			}
		}
		if (!$saved) {
			$name = 'p'.$id.'_'.time().'.'.$types[$info[2]];
			$saved = move_uploaded_file($f['tmp_name'], $dir.$name);
		}
		if (!$saved) $out(['ok' => 0, 'err' => 'save']);

		$n = mysqli_real_escape_string(db::$connect, $name);
		if (!db::query("update product set img = '$n', upd_dt = NOW() where id = '$id' and company_id = '$cmp'")) { @unlink($dir.$name); $out(['ok' => 0, 'err' => 'db']); }
		if ($old !== $name) { $rm_old(); $rm_old_drive(); }
		$out(['ok' => 1, 'img' => $name, 'url' => '/assets/uploads/products/'.$name]);
	}

	$out(['ok' => 0, 'err' => 'error']);
