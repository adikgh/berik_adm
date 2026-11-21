<? include "../config/core.php";

	// QR мәзірдегі «Ұсыныс» жұлдызшасы: тағамды белгілеу / алу (product.recommend)
	if (isset($_GET['rec'])) {
		if (!$user_id || !$company) exit('error');
		$id = (int)@$_POST['id']; $on = (int)!empty($_POST['on']); $cmp = (int)$company;
		$q = db::query("update product set recommend = '$on' where id = '$id' and company_id = '$cmp'");
		exit($q ? 'yes' : 'error');
	}
	// Атаудағы «+ кола / сок / компот…» бөлігін бас әріппен қызыл ету (fun::addon_hl), бүкіл компания бір басуда.
	// dry=1 — тек санап, мысал қайтарады. Өзгертпес бұрын ескі атаулар product_name_bak кестесіне сақталады.
	if (isset($_GET['addon_hl'])) {
		header('Content-Type: application/json; charset=utf-8');
		if (!$user_id || !$company) exit('{"ok":0}');
		$cmp = (int)$company; $dry = !empty($_POST['dry']); $ch = [];
		$q = db::query("select id, name_kz, name_ru from product where company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$kz = fun::addon_hl($r['name_kz']); $ru = fun::addon_hl($r['name_ru']);
			if ($kz !== (string)$r['name_kz'] || $ru !== (string)$r['name_ru']) $ch[] = $r + ['kz' => $kz, 'ru' => $ru];
		}
		if ($dry || !$ch) exit(json_encode(['ok' => 1, 'n' => count($ch), 'ex' => array_map(function ($r) { return fun::name_text($r['name_kz'] ?: $r['name_ru']); }, array_slice($ch, 0, 5))], JSON_UNESCAPED_UNICODE));
		db::query("create table if not exists product_name_bak (id int unsigned not null auto_increment primary key, product_id int not null, name_kz varchar(255) null, name_ru varchar(255) null, ins_dt datetime not null default current_timestamp, key (product_id)) default charset = utf8mb4");
		$e = function ($s) { return $s === null ? 'NULL' : "'".mysqli_real_escape_string(db::$connect, $s)."'"; };
		$n = 0;
		foreach ($ch as $r) {
			$id = (int)$r['id'];
			db::query("insert into product_name_bak (product_id, name_kz, name_ru) values ('$id', ".$e($r['name_kz']).", ".$e($r['name_ru']).")");
			if (db::query("update product set name_kz = ".$e($r['name_kz'] === null ? null : $r['kz']).", name_ru = ".$e($r['name_ru'] === null ? null : $r['ru'])." where id = '$id' and company_id = '$cmp'")) $n++;
		}
		exit(json_encode(['ok' => 1, 'n' => $n], JSON_UNESCAPED_UNICODE));
	}
	// Тағам түрі (products/cost2/kind.php): k = 1 сет, 2 тағам, 3 тікелей шикізат, 4 қатысы жоқ, бос = белгісіз; ids = JSON (жаппай).
	// Ескі v: 1 = сет, 0 = позиция (тағам). product.is_set синхрон қалады. Қолмен қойылған — ЖИ қайта өзгертпейді.
	if (isset($_GET['kind'])) {
		if (!$user_id || !$company || !fun::has_col('product', 'is_set')) exit('error');
		require_once __DIR__.'/cost2/kind.php';
		$cmp = (int)$company; $ids = json_decode((string)@$_POST['ids'], true); if (!is_array($ids)) $ids = [(int)@$_POST['id']];
		if (isset($_POST['k'])) $k = (string)$_POST['k']; else { $v = (string)@$_POST['v']; $k = $v === '' ? '' : ((int)$v ? 1 : 2); }
		pkind::set($cmp, $ids, $k === '' ? null : (int)$k, 'user');
		// тікелей шикізат: атауы бойынша бір кандидат табылса — өзі байланады (граммовкасы бос болса)
		if ((string)$k === '3' && count($ids) == 1) pkind::goods_auto($cmp, $ids[0]);
		exit('yes');
	}
	// Құрам (product_comp) / шикізат (product_raw): қосу және өзгерту
	$cr_act = null;
	foreach (['comp_add', 'raw_add', 'comp_upd', 'raw_upd'] as $k) if (isset($_GET[$k])) $cr_act = $k;
	if ($cr_act) {
		if (!$user_id) exit('error');
		$tbl = strpos($cr_act, 'comp') === 0 ? 'product_comp' : 'product_raw';
		$upd = substr($cr_act, -3) == 'upd';
		$cmp = (int)$company;
		$id = (int)@$_POST['id'];
		$name = trim(strip_tags((string)@$_POST['name']));
		if ($name === '') exit('empty');
		$name = mysqli_real_escape_string(db::$connect, mb_substr($name, 0, 255));
		$price = (int)preg_replace('/\D+/', '', (string)@$_POST['price']);
		try {
			if ($upd && !mysqli_num_rows(db::query("select id from $tbl where id = '$id' and company_id = '$cmp'"))) exit('error');
			if (mysqli_num_rows(db::query("select id from $tbl where company_id = '$cmp' and (name_kz = '$name' or name_ru = '$name')".($upd ? " and id <> '$id'" : "")." limit 1"))) exit('exists');
			$unit = null;
			if (fun::has_col($tbl, 'unit')) {
				$u = mysqli_real_escape_string(db::$connect, fun::unit_norm(strip_tags((string)@$_POST['unit'])));
				$unit = $u !== '' ? "'$u'" : "NULL";
			}
			if ($upd) $q = db::query("update $tbl set name_kz = '$name', name_ru = '$name', price = '$price'".($unit !== null ? ", unit = $unit" : "").", ubd_dt = NOW() where id = '$id' and company_id = '$cmp'");
			else $q = db::query("insert into $tbl (company_id, name_kz, name_ru, price".($unit !== null ? ", unit" : "").") values ('$cmp', '$name', '$name', '$price'".($unit !== null ? ", $unit" : "").")");
			exit($q ? 'yes' : 'error');
		} catch (Throwable $e) { exit('error'); }
	}


	// 
	if(isset($_GET['product_img_add'])) {
		$path = '../assets/uploads/products/';
		$allow = array();
		$deny = array(
			'phtml', 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps', 'cgi', 'pl', 'asp', 
			'aspx', 'shtml', 'shtm', 'htaccess', 'htpasswd', 'ini', 'log', 'sh', 'js', 'html', 
			'htm', 'css', 'sql', 'spl', 'scgi', 'fcgi', 'exe'
		);
		$error = $success = '';
		$datetime = date('Ymd-His', time());

		if (!isset($_FILES['file'])) $error = 'Файлды жүктей алмады';
		else {
			$file = $_FILES['file'];
			if (!empty($file['error']) || empty($file['tmp_name'])) $error = 'Файлды жүктей алмады';
			elseif ($file['tmp_name'] == 'none' || !is_uploaded_file($file['tmp_name'])) $error = 'Файлды жүктей алмады';
			else {
				$pattern = "[^a-zа-яё0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
				$name = mb_eregi_replace($pattern, '-', $file['name']);
				$name = mb_ereg_replace('[-]+', '-', $name);
				$parts = pathinfo($name);
				$name = $datetime.'-'.$name;

				if (empty($name) || empty($parts['extension'])) $error = 'Файлды жүктей алмады';
				elseif (!empty($allow) && !in_array(strtolower($parts['extension']), $allow)) $error = 'Файлды жүктей алмады';
				elseif (!empty($deny) && in_array(strtolower($parts['extension']), $deny)) $error = 'Файлды жүктей алмады';
				else {
					if (move_uploaded_file($file['tmp_name'], $path . $name)) $success = '<p style="color: green">Файл «' . $name . '» успешно загружен.</p>';
					else $error = 'Файлды жүктей алмады';
				}
			}
		}
		
		if (!empty($error)) $error = '<p style="color:red">'.$error.'</p>';
		$data = array(
			'error'   => $error,
			'success' => $success,
			'file' => $name,
		);
		
		header('Content-Type: application/json');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit();
	}
   
   
   // product_add
	if(isset($_GET['product_add'])) {
		$name = mysqli_real_escape_string(db::$connect, fun::addon_hl(trim(strip_tags((string)@$_POST['name'])))); // «+ кола» бірден қызыл
		$catalog = strip_tags($_POST['catalog']);
		$price = strip_tags($_POST['price']);
		
		$id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product`")))['max(id)'] + 1;
		$ins = db::query("INSERT INTO `product`(`id`, `company_id`) VALUES ('$id', '$company')");
		if ($ins) {
         	if ($name) $sql = db::query("UPDATE `product` SET `name_kz` = '$name', `name_ru` = '$name' WHERE `id`='$id'");
         	if ($catalog) $sql = db::query("UPDATE `product` SET `catalog_id`='$catalog' WHERE `id`='$id'");
         	if ($price) $sql = db::query("UPDATE `product` SET `price`='$price' WHERE `id`='$id'");

			// атауы доплата / пакет / сервис … болса — «Қатысы жоқ» (products/cost2/kind.php)
			if (is_file(__DIR__.'/cost2/kind.php')) { require_once __DIR__.'/cost2/kind.php'; pkind::apply_rule($company, [$id]); }
			echo 'yes';
		} else echo 'error';

      	exit();
	}
   
   // product arh
	if(isset($_GET['form_prd_online'])) {
		$id = strip_tags($_POST['id']);
		$val = strip_tags($_POST['val']);
		$upd = db::query("UPDATE `product` SET sale_online = '$val' WHERE id = '$id'");

      if ($upd) echo 'yes'; else echo 'error';
      exit();
	}


   // product arh
	if(isset($_GET['product_delete'])) {
		// толық өшірілмейді: тек архивке түседі (arh = 1)
		$id = intval(@$_POST['id']);
		$upd = db::query("UPDATE `product` SET arh = 1 WHERE id = '$id' and company_id = '$company'");

      if ($upd) echo 'yes'; else echo 'error';
      exit();
	}

	// архивтен шығару (arh = 0)
	if (isset($_GET['product_restore'])) {
		if (!$user_id || !$company) exit('error');
		$id = intval(@$_POST['id']); $cmp = (int)$company;
		$upd = db::query("UPDATE `product` SET arh = 0 WHERE id = '$id' and company_id = '$cmp'");
		exit($upd ? 'yes' : 'error');
	}

   // product_delete
	// if(isset($_GET['product_delete'])) {
	// 	$id = strip_tags($_POST['id']);

   //    $upd = db::query("DELETE FROM `product_item_quantity` WHERE product_id = '$id'");
   //    $upd = db::query("DELETE FROM `product_item` WHERE product_id = '$id'");
   //    $del = db::query("DELETE FROM `product` WHERE id = '$id'");

   //    if ($del) echo 'yes'; else echo 'error';
   //    exit();
	// }




	// product_barcode search
	if(isset($_GET['product_barcode'])) {
		$search = strip_tags($_POST['result']);

		$barcode = db::query("select * from product_item where (barcode = '$search' and barcode is not null) or (article = '$search' and article is not null) limit 1");
		if (mysqli_num_rows($barcode)) {
			$barcode_d = mysqli_fetch_array($barcode);
			echo $barcode_d['product_id'];
		} else echo 'none';

      exit();
	}