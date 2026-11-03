<? include "../../config/core.php";

	// Сет ішіндегі құрамды мәзірдегі позицияға байлау (Кола → «Кола 1л» позициясы). Позицияның құрамы бос болса, 1 шт сол құрам жазылады.
	if (isset($_GET['si_link'])) {
		if (!$user_id) exit('error');
		$cmp = (int)$company; $id = (int)@$_POST['id']; $pid = (int)@$_POST['product'];
		try {
			$q = db::query("select si.id, si.set_id, si.comp_id from product_set_item si join product p on p.id = si.set_id where si.id = '$id' and p.company_id = '$cmp'");
			$si = $q ? mysqli_fetch_assoc($q) : null;
			$q = db::query("select id from product where id = '$pid' and company_id = '$cmp'");
			if (!$si || !$si['comp_id'] || !$q || !mysqli_num_rows($q) || $pid == $si['set_id']) exit('error');
			$cid = (int)$si['comp_id'];
			$q = db::query("select id from product_item where product_id = '$pid' and comp_id > 0 limit 1");
			if (!$q || !mysqli_num_rows($q)) db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '1')");
			$ok = db::query("update product_set_item set product_id = '$pid', comp_id = NULL where id = '$id'");
			exit($ok ? 'yes' : 'error');
		} catch (Throwable $e) { exit('error'); }
	}

	// Сет құрамы (product_set_item): қосу / санын өзгерту / өшіру (тек осы компанияның сеті)
	if (isset($_GET['si_add']) || isset($_GET['si_qty']) || isset($_GET['si_del'])) {
		if (!$user_id) exit('error');
		$cmp = (int)$company;
		$has = function ($sql) { $q = db::query($sql); return $q && mysqli_num_rows($q) > 0; };
		$dec = function ($v) { $v = round((float)str_replace(',', '.', trim((string)$v)), 3); return $v > 0 ? rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') : ''; };
		try {
			if (isset($_GET['si_add'])) {
				$sid = (int)@$_POST['id']; $it = (string)@$_POST['item']; $qty = $dec(@$_POST['quantity']);
				if ($qty === '') exit('qty');
				if (!$has("select id from product where id = '$sid' and company_id = '$cmp'")) exit('error');
				$iid = (int)substr($it, 1);
				if ($it[0] === 'p' && $iid != $sid && $has("select id from product where id = '$iid' and company_id = '$cmp'"))
					$q = db::query("insert into product_set_item (set_id, product_id, quantity) values ('$sid', '$iid', '$qty')");
				elseif ($it[0] === 'r' && $has("select id from product_raw where id = '$iid' and company_id = '$cmp'")) {
					// тауар сетке тікелей: сол тауарға байланған құрам болмаса, өзі жасалады
					$cq = db::query("select id from product_comp where company_id = '$cmp' and raw_id = '$iid' order by id limit 1");
					if ($cq && ($cr = mysqli_fetch_assoc($cq))) $cid = (int)$cr['id'];
					else {
						$rw = mysqli_fetch_assoc(db::query("select name_kz, name_ru, unit, price from product_raw where id = '$iid'"));
						$nk = mysqli_real_escape_string(db::$connect, (string)($rw['name_kz'] ?: $rw['name_ru'])); $nr = mysqli_real_escape_string(db::$connect, (string)($rw['name_ru'] ?: $rw['name_kz']));
						$un = mysqli_real_escape_string(db::$connect, (string)$rw['unit']); $pr = (int)$rw['price'];
						db::query("insert into product_comp (company_id, name_kz, name_ru, unit, kind, price, raw_id) values ('$cmp', '$nk', '$nr', '$un', 'raw', '$pr', '$iid')");
						$cid = (int)mysqli_insert_id(db::$connect);
					}
					$q = $cid ? db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '$cid', '$qty')") : false;
				}
				elseif ($it[0] === 'c' && $has("select id from product_comp where id = '$iid' and company_id = '$cmp'"))
					$q = db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '$iid', '$qty')");
				else exit('error');
			} else {
				$id = (int)@$_POST['id'];
				if (!$has("select si.id from product_set_item si join product p on p.id = si.set_id where si.id = '$id' and p.company_id = '$cmp'")) exit('error');
				if (isset($_GET['si_qty'])) {
					$qty = $dec(@$_POST['quantity']);
					if ($qty === '') exit('qty');
					$q = db::query("update product_set_item set quantity = '$qty' where id = '$id'");
				} else $q = db::query("delete from product_set_item where id = '$id'");
			}
			exit($q ? 'yes' : 'error');
		} catch (Throwable $e) { exit('error'); }
	}

	// Тағамдар тізіміндегі «Құрамы» попапы: тағамның/сеттің құрамын JSON-мен береді
	if (isset($_GET['comp_load'])) {
		header('Content-Type: application/json; charset=utf-8');
		if (!$user_id) exit('{"ok":0}');
		$cmp = (int)$company; $pid = (int)@$_GET['id'];
		try {
			$pq = db::query("select id, name_kz, name_ru, price from product where id = '$pid' and company_id = '$cmp'");
			$p = $pq ? mysqli_fetch_assoc($pq) : null;
			if (!$p) exit('{"ok":0}');
			if (!class_exists('cost')) include __DIR__."/../cost/lib.php";
			$ready = cost::ready();
			$all = $ready ? cost::comps($cmp) : [];
			if (!$ready) { $q = db::query("select * from product_comp where company_id = '$cmp'"); while ($r = mysqli_fetch_assoc($q)) { $r['parts'] = []; $r['cost'] = (float)$r['price']; $all[$r['id']] = $r; } }
			$comps = [];
			foreach ($all as $id => $c) {
				$u = fun::unit_norm($c['unit'] ?? '');
				$comps[$id] = [
					'id' => (int)$id, 'nm' => (string)($c['name_kz'] ?: $c['name_ru']),
					'pu' => $u !== '' ? $u : 'шт', 'qu' => ['кг' => 'г', 'л' => 'мл'][$u] ?? ($u !== '' ? $u : 'шт'),
					'div' => in_array($u, ['кг', 'л']) ? 1000 : 1, 'price' => round((float)$c['cost'], 2), 'prep' => !empty($c['parts']) ? 1 : 0,
				];
			}
			$rows = [];
			$q = db::query("select id, comp_id, quantity from product_item where product_id = '$pid' and comp_id > 0 order by ins_dt asc, id asc");
			while ($r = mysqli_fetch_assoc($q)) {
				$c = $comps[$r['comp_id']] ?? null;
				$rows[] = ['id' => (int)$r['id'], 'comp' => (int)$r['comp_id'], 'qty' => (float)$r['quantity'], 'cost' => $c ? $r['quantity'] * $c['price'] / $c['div'] : 0];
			}
			$set = []; $over = 0; $dishes = []; $raws = [];
			if ($ready) {
				$dc = cost::dishes($cmp, $all);
				// мәзірдегі позициялар атауы бойынша (сет ішіндегі «Кола» → «Кола 1л / КОЛА» позициясы)
				$menu = []; $menu_nm = []; $is_set = [];
				$q = db::query("select distinct si.set_id from product_set_item si join product p on p.id = si.set_id where p.company_id = '$cmp'");
				while ($x = mysqli_fetch_assoc($q)) $is_set[$x['set_id']] = 1;
				$q = db::query("select id, name_kz, name_ru from product where company_id = '$cmp' and arh = 0 and id <> '$pid'");
				while ($x = mysqli_fetch_assoc($q)) { if (isset($is_set[$x['id']])) continue; $menu_nm[$x['id']] = strip_tags((string)($x['name_kz'] ?: $x['name_ru'])); foreach ([$x['name_kz'], $x['name_ru']] as $n) { $k = mb_strtolower(trim(strip_tags((string)$n))); if ($k !== '' && !isset($menu[$k])) $menu[$k] = (int)$x['id']; } }
				$q = db::query("select si.*, d.name_ru d_ru, d.name_kz d_kz from product_set_item si left join product d on d.id = si.product_id where si.set_id = '$pid' order by si.id");
				while ($r = mysqli_fetch_assoc($q)) {
					if ($r['product_id']) { $u = $dc[$r['product_id']] ?? 0; $nm = strip_tags((string)($r['d_kz'] ?: $r['d_ru'])); }
					else { $c = $comps[$r['comp_id']] ?? null; $u = $c ? $c['price'] : 0; $nm = $c ? $c['nm'] : '?'; }
					$mp = 0;
					if (!$r['product_id'] && $c) { $k = mb_strtolower(trim($c['nm'])); $mp = $menu[$k] ?? 0; }
					$set[] = ['id' => (int)$r['id'], 'nm' => $nm, 'pid' => (int)$r['product_id'], 'qty' => (float)$r['quantity'], 'unit' => $u, 'cost' => $u * $r['quantity'], 'menu' => $mp, 'menu_nm' => $mp ? ($menu_nm[$mp] ?? '') : ''];
				}
				$over = cost::overhead(cost::setting($cmp));
				$q = db::query("select id, name_kz, name_ru from product where company_id = '$cmp' and arh = 0 and id <> '$pid' order by name_kz, name_ru");
				while ($r = mysqli_fetch_assoc($q)) $dishes[] = ['v' => 'p'.$r['id'], 'nm' => strip_tags((string)($r['name_kz'] ?: $r['name_ru'])), 'cost' => round($dc[$r['id']] ?? 0), 'set' => isset($is_set[$r['id']]) ? 1 : 0];
				$q = db::query("select id, name_kz, name_ru, unit, price from product_raw where company_id = '$cmp' order by name_ru");
				while ($r = mysqli_fetch_assoc($q)) $raws[] = ['v' => 'r'.$r['id'], 'nm' => (string)($r['name_ru'] ?: $r['name_kz']), 'cost' => (float)$r['price'], 'u' => fun::unit_norm($r['unit']) ?: 'шт'];
			}
			echo json_encode(['ok' => 1, 'ready' => $ready ? 1 : 0, 'id' => $pid, 'name' => strip_tags((string)($p['name_kz'] ?: $p['name_ru'])), 'price' => (int)$p['price'],
				'rows' => $rows, 'comps' => array_values($comps), 'set' => $set, 'over' => $over, 'dishes' => $dishes, 'raws' => $raws], JSON_UNESCAPED_UNICODE);
		} catch (Throwable $e) { echo '{"ok":0}'; }
		exit;
	}

	// Тағам құрамы: қосу / санын өзгерту / өшіру (тек осы компанияның тағамы мен құрамы)
	if (isset($_GET['ci_add']) || isset($_GET['ci_qty']) || isset($_GET['ci_del'])) {
		if (!$user_id) exit('error');
		$cmp = (int)$company;
		$has = function ($sql) { $q = db::query($sql); return $q && mysqli_num_rows($q) > 0; };
		// 0,5 / 0.5 / 1.25 → '0.5' (quantity әлі INT болса, MySQL өзі дөңгелектейді)
		$dec = function ($v) { $v = round((float)str_replace(',', '.', trim((string)$v)), 3); return $v > 0 ? rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') : ''; };
		try {
			if (isset($_GET['ci_add'])) {
				$pid = (int)@$_POST['id']; $cid = (int)@$_POST['comp']; $qty = $dec(@$_POST['quantity']);
				if ($qty === '') exit('qty');
				if (!$has("select id from product where id = '$pid' and company_id = '$cmp'")) exit('error');
				if (!$has("select id from product_comp where id = '$cid' and company_id = '$cmp'")) exit('error');
				$q = db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$cid', '$qty')");
			} else {
				$id = (int)@$_POST['id'];
				if (!$has("select pi.id from product_item pi join product p on p.id = pi.product_id where pi.id = '$id' and p.company_id = '$cmp'")) exit('error');
				if (isset($_GET['ci_qty'])) {
					$qty = $dec(@$_POST['quantity']);
					if ($qty === '') exit('qty');
					$q = db::query("update product_item set quantity = '$qty', ubd_dt = NOW() where id = '$id'");
				} else $q = db::query("delete from product_item where id = '$id'");
			}
			exit($q ? 'yes' : 'error');
		} catch (Throwable $e) { exit('error'); }
	}


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
		// тағам карточкасынан сақтау (атау kz/ru, каталог, баға)
		if (isset($_POST['card'])) {
			$id = intval(@$_POST['id']); $cmp = (int)$company;
			$esc = function ($v) {
				$h = fun::name_save($v);
				if (fun::name_text($h) === '') return '';
				if (mb_strlen($h) > 255) $h = fun::name_save(mb_substr($h, 0, 255));
				return mysqli_real_escape_string(db::$connect, $h);
			};
			$nkz = $esc(@$_POST['name_kz']); $nru = $esc(@$_POST['name_ru']);
			if ($nkz === '' && $nru === '') exit('empty');
			if ($nkz === '') $nkz = $nru;
			if ($nru === '') $nru = $nkz;
			$price = (int)preg_replace('/\D+/', '', (string)@$_POST['price']);
			try {
				$cat = intval(@$_POST['catalog']);
				if ($cat && !mysqli_num_rows(db::query("select id from product_catalog where id = '$cat' and company_id = '$cmp'"))) $cat = 0;
				// сипаттама: тек мәтін (тегтерсіз), 1000 таңбаға дейін, бос болса NULL
				$desc_sql = '';
				if (isset($_POST['description']) && fun::has_col('product', 'description')) {
					$d = trim(preg_replace("/[ 	]+/u", ' ', strip_tags((string)$_POST['description'])));
					$d = mb_substr($d, 0, 1000);
					$desc_sql = ", `description` = ".($d === '' ? "NULL" : "'".mysqli_real_escape_string(db::$connect, $d)."'");
				}
				$upd = db::query("UPDATE `product` SET `name_kz` = '$nkz', `name_ru` = '$nru', `catalog_id` = ".($cat ? "'$cat'" : "NULL").", `price` = '$price'$desc_sql, `upd_dt` = '$datetime' WHERE id = '$id' and company_id = '$cmp'");
				exit($upd ? 'yes' : 'error');
			} catch (Throwable $e) { exit('error'); }
		}
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

