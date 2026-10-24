<? include "../../config/core.php";


   // pitem_minus
	if(isset($_GET['pitem_minus'])) {
		$id = strip_tags($_POST['id']);
      $pitem_d = product::pr_item($id);

      if ($pitem_d['quantity'] > 0) {
         $ubd = db::query("UPDATE `product_item` SET quantity = quantity - 1 WHERE `id`='$id'");
         echo 'yes';
      } else echo 'null';
      exit();
	}
   // pitem_plus
	if(isset($_GET['pitem_plus'])) {
		$id = strip_tags($_POST['id']);
      $upd = db::query("UPDATE `product_item` SET quantity = quantity + 1 WHERE `id`='$id'");
      if ($upd) echo 'yes'; else echo 'error';
      exit();
	}
   // pitem_delete
	if(isset($_GET['pitem_delete'])) {
		$id = strip_tags($_POST['id']);
      $del = db::query("DELETE FROM `product_item` WHERE `id`='$id'");
      if ($del) echo 'yes'; else echo 'error';
      exit();
   }


   // pitem_add 
	if(isset($_GET['pitem_add'])) {
		$product_id = strip_tags($_POST['id']);
		$comp_id = strip_tags($_POST['comp']);
		$quantity = strip_tags($_POST['quantity']);

		$ins = db::query("INSERT INTO `product_item`(`product_id`, `quantity`, `comp_id`) VALUES ('$product_id', '$quantity', '$comp_id')");
		echo 'yes';

      exit();
	}




	if(isset($_GET['pitem_qn'])) {
		$id = strip_tags($_POST['id']);
		$quantity = strip_tags($_POST['quantity']);
		if ($quantity >= 0) {
			$ubd = db::query("UPDATE `product_item` SET quantity = '$quantity' WHERE id = '$id'");
			echo 'yes';
		} else echo 'null';
      exit();
	}



   // pitem_upd 
	if(isset($_GET['pitem_upd'])) {
		$id = strip_tags($_POST['id']);
      $article = strip_tags($_POST['article']);
		$barcode = strip_tags($_POST['barcode']);
		$quantity = strip_tags($_POST['quantity']);
		$price = strip_tags($_POST['price']);
		$purchase_price = strip_tags($_POST['purchase_price']);
		$discount_price = strip_tags($_POST['discount_price']);
      $color = strip_tags($_POST['color']);
		$size = strip_tags($_POST['size']);
		$img = strip_tags($_POST['img']);
		$img2 = strip_tags($_POST['img2']);

		$pitem_d = product::pr_item($id);
		$product_id = $pitem_d['product_id'];
      $product_d = product::product($product_id);

      if ($article) $upd = db::query("UPDATE `product_item` SET `article` = '$article' WHERE id = '$id'");
      if ($barcode) $upd = db::query("UPDATE `product_item` SET `barcode` = '$barcode' WHERE id = '$id'");
      if ($quantity) $upd = db::query("UPDATE `product_item` SET `quantity` = '$quantity' WHERE id = '$id'");
      if ($price) $upd = db::query("UPDATE `product_item` SET `price` = '$price' WHERE id = '$id'");
      if ($purchase_price) $upd = db::query("UPDATE `product_item` SET `purchase_price` = '$purchase_price' WHERE id = '$id'");
      if ($discount_price) $upd = db::query("UPDATE `product_item` SET `discount_price` = '$discount_price' WHERE id = '$id'");
      if ($img) $upd = db::query("UPDATE `product_item` SET `img` = '$img' WHERE id = '$id'");
      if ($img2) $upd = db::query("UPDATE `product_item` SET `img_room` = '$img2' WHERE id = '$id'");

      if ($color) {
         $color_id = product::pr_color_name($color);
         if ($color_id == 0) {
            $color_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_color`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_item_color`(`id`, `name_ru`) VALUES ('$color_id', '$color')");
         }
         $upd = db::query("UPDATE `product_item` SET `color_id` = '$color_id' WHERE id = '$id'");
      }
      if ($size) {
         $size_id = product::pr_size_name($size);
         if ($size_id == 0) {
            $size_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_size`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_item_size`(`id`, `name`) VALUES ('$size_id', '$size')");
         }
         $upd = db::query("UPDATE `product_item` SET `size_id` = '$size_id' WHERE id = '$id'");
      }

      echo 'yes';
      exit();
	}
   


   // pr_upd
	if(isset($_GET['pr_upd'])) {
		$id = strip_tags($_POST['id']);
      $name = strip_tags($_POST['name']);
		$catalog_id = strip_tags($_POST['catalog']);
		$brand = strip_tags($_POST['brand']);
		$country = strip_tags($_POST['country']);
		$collection = strip_tags($_POST['collection']);
		$style = strip_tags($_POST['style']);

      if ($name) $upd = db::query("UPDATE `product` SET `name_kz` = '$name', `name_ru` = '$name', `upd_dt` = '$datetime' WHERE id = '$id'");
      if ($catalog_id) $upd = db::query("UPDATE `product` SET `catalog_id` = '$catalog_id', `upd_dt` = '$datetime' WHERE id = '$id'");
      if ($brand) {
         $brand_id = product::pr_brand_name($brand);
         if ($brand_id == 0) {
            $brand_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_ch_brand`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_ch_brand`(`id`, `name`) VALUES ('$brand_id', '$brand')");
         }
         $upd = db::query("UPDATE `product` SET `brand_id`='$brand_id', `upd_dt` = '$datetime' WHERE id = '$id'");
      }
      if ($country) {
         $country_id = product::pr_country_name($country);
         if ($country_id == 0) {
            $country_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_ch_country`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_ch_country`(`id`, `name_kz`, `name_ru`) VALUES ('$country_id', '$country', '$country')");
         }
         $upd = db::query("UPDATE `product` SET `brand_country_id`='$country_id', `upd_dt` = '$datetime' WHERE id = '$id'");
      }
      if ($collection) {
         $collection_id = product::pr_collection_name($collection);
         if ($collection_id == 0) {
            $collection_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_ch_collection`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_ch_collection`(`id`, `name_kz`, `name_ru`) VALUES ('$collection_id', '$collection', '$collection')");
         }
         $upd = db::query("UPDATE `product` SET `collection_id`='$collection_id', `upd_dt` = '$datetime' WHERE id = '$id'");
      }
      if ($style) {
         $style_id = product::pr_style_name($style);
         if ($style_id == 0) {
            $style_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_ch_style`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_ch_style`(`id`, `name_kz`, `name_ru`) VALUES ('$style_id', '$style', '$style')");
         }
         $upd = db::query("UPDATE `product` SET `style_id`='$style_id', `upd_dt` = '$datetime' WHERE id = '$id'");
      }

      echo 'yes';
      exit();
	}













   // 
	if(isset($_GET['product_img_add'])) {
		$path = '../../assets/uploads/products/';
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
		// header("Access-Control-Allow-Origin: *");
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit();
	}





	// 
	if(isset($_GET['del_imgs'])) {
		$id = strip_tags($_POST['id']);
		$upd = db::query("DELETE FROM `product_img` WHERE id = '$id'");
      if ($upd) echo 'yes'; else echo 'error';
      exit();
	}


	// 
	if(isset($_GET['add_sess'])) {
		$_SESSION['product_id'] = strip_tags($_POST['id']);
		$_SESSION['item_id'] = strip_tags($_POST['item_id']);
		exit();
	}


	// 
	if(isset($_GET['add_item_photo2'])) {
		$id = $_SESSION['product_id'];
		$item_id = $_SESSION['item_id'];

		$input_name = 'file';
		$path = '../../assets/uploads/products/';
		$allow = array();
		$deny = array(
			'phtml', 'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'phps', 'cgi', 'pl', 'asp', 
			'aspx', 'shtml', 'shtm', 'htaccess', 'htpasswd', 'ini', 'log', 'sh', 'js', 'html', 
			'htm', 'css', 'sql', 'spl', 'scgi', 'fcgi', 'exe'
		);
		$data = array();
		$datetime = date('Y-m-d-H-i-s', time());
		$filem = array();

		if (!isset($_FILES[$input_name])) { $error = 'Файлы не загружены.'; }
		else {
			$files = array();
			$diff = count($_FILES[$input_name]) - count($_FILES[$input_name], COUNT_RECURSIVE);
			if ($diff == 0) $files = array($_FILES[$input_name]);
			else {
				foreach($_FILES[$input_name] as $k => $l) {
					foreach($l as $i => $v) {
						$files[$i][$k] = $v;
					}
				}
			}

			foreach ($files as $key=>$file) {
				$error = $success = '';
				if (!empty($file['error']) || empty($file['tmp_name'])) {
					$error = 'Не удалось загрузить файл.';
				} elseif ($file['tmp_name'] == 'none' || !is_uploaded_file($file['tmp_name'])) {
					$error = 'Не удалось загрузить файл.';
				} else {
					$pattern = "[^a-zа-яё0-9,~!@#%^-_\$\?\(\)\{\}\[\]\.]";
					$name = mb_eregi_replace($pattern, '-', $file['name']);
					$name = mb_ereg_replace('[-]+', '-', $name);
					$parts = pathinfo($name);
					$name = $datetime.'-'.$name;
					array_push($filem, $name);
					
					if (empty($name) || empty($parts['extension'])) {
						$error = 'Недопустимый тип файла';
					} elseif (!empty($allow) && !in_array(strtolower($parts['extension']), $allow)) {
						$error = 'Недопустимый тип файла';
					} elseif (!empty($deny) && in_array(strtolower($parts['extension']), $deny)) {
						$error = 'Недопустимый тип файла';
					} else {
						if (move_uploaded_file($file['tmp_name'], $path . $name)) {
							$sql = db::query("INSERT INTO `product_img`(`product_id`, `product_item_id`, `number`, `img`) VALUES ('$id', '$item_id', '$key', '$name')");
							$success = 'Файл «' . $name . '» успешно загружен.';
						} else $error = 'Не удалось загрузить файл.';
					}
				}
			}

			if (!empty($success)) $data[] = '<p style="color: green">' . $success . '</p>';  
			if (!empty($error)) $data[] = '<p style="color: red">' . $error . '</p>';  
		}
				
		$data = array(
			'error'   => $error,
			'success' => $success,
			'file' => $filem,
		);
		
		header('Content-Type: application/json');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit();
	}

