<? include "../config/core.php";


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
		$name = strip_tags($_POST['name']);
		$catalog = strip_tags($_POST['catalog']);
		$price = strip_tags($_POST['price']);
		
		$id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product`")))['max(id)'] + 1;
		$ins = db::query("INSERT INTO `product`(`id`, `company_id`) VALUES ('$id', '$company')");
		if ($ins) {
         	if ($name) $sql = db::query("UPDATE `product` SET `name_kz` = '$name', `name_ru` = '$name' WHERE `id`='$id'");
         	if ($catalog) $sql = db::query("UPDATE `product` SET `catalog_id`='$catalog' WHERE `id`='$id'");
         	if ($price) $sql = db::query("UPDATE `product` SET `price`='$price' WHERE `id`='$id'");

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