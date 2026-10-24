<? include "../config/core.php";
   
   
   // 
	if(isset($_GET['cashbox_add'])) {
      $pid = strip_tags($_POST['id']);
		$oid = strip_tags($_POST['oid']);
      $pitem_d = product::product($pid);
      if ($pitem_d['price']) $price = $pitem_d['price']; else $price = 0;

      $retailop = db::query("select * from retail_orders_products where order_id = '$oid' and product_id = '$pid'");
      if (mysqli_num_rows($retailop)) $upd = db::query("UPDATE `retail_orders_products` SET quantity = quantity + 1 WHERE `order_id`='$oid' and product_id = '$pid'");
      else $ins = db::query("INSERT INTO `retail_orders_products`(`order_id`, `product_id`, `quantity`, `price`) VALUES ('$oid', '$pid', 1, $price)");
      if (@$upd || @$ins) echo 'yes';
      
      exit();
	}

   // cashbox_search
   if(isset($_GET['cashbox_search'])) {
		$search = strip_tags($_POST['inp']);
		$oid = strip_tags($_POST['oid']);

      $pitem = db::query("select * from product_item where (barcode = '$search' and barcode is not null) or (article = '$search' and article is not null)");
      if (mysqli_num_rows($pitem)) {
         $pitem_d = mysqli_fetch_assoc($pitem);
         $pitem_id = $pitem_d['id'];
         $product_d = product::product($pitem_d['product_id']);
         $product_id = $pitem_d['product_id'];
         if ($pitem_d['price']) $price = $pitem_d['price']; else $price = 0;

         $quantity = db::query("select * from product_item_quantity where item_id = '$pitem_id' and quantity != 0 limit 1");
         if (mysqli_num_rows($quantity)) {
            $quantity = mysqli_fetch_array($quantity);
            $quantity_id = $quantity['id'];

            $retailop = db::query("select * from retail_orders_products where order_id = '$oid' and product_id = '$product_id' and product_item_id = '$pitem_id'");
            if (mysqli_num_rows($retailop))  $upd = db::query("UPDATE `retail_orders_products` SET quantity = quantity + 1 WHERE `order_id`='$oid' and `product_id`='$product_id' and product_item_id = '$pitem_id'");
            else $ins = db::query("INSERT INTO `retail_orders_products`(`order_id`, `product_id`, `product_item_id`, `quantity`, `price`) VALUES ('$oid', '$product_id', '$pitem_id', 1, $price)");
            if ($upd || $ins) echo 'yes';
         } else echo 0;
      } else echo 'none';
      exit();
	}


   // cashbox_remove
	if(isset($_GET['cashbox_remove'])) {
		$id = strip_tags($_POST['id']);
      $del = db::query("DELETE FROM `retail_orders_products` where id = '$id'");
      if ($del) echo 'yes';

      // $retailop_d = fun::retailop($id);
      // $pitem_id = $retailop_d['product_item_id'];
      // $qn = $retailop_d['quantity'];
      // $quantity = db::query("select * from product_item_quantity where item_id = '$pitem_id' limit 1");
      // if (mysqli_num_rows($quantity)) {
      //    $quantity = mysqli_fetch_array($quantity);
      //    $quantity_id = $quantity['id'];
      //    $upd2 = db::query("UPDATE `product_item_quantity` SET quantity = quantity + '$qn' WHERE id = '$quantity_id'");
      // }

      exit();
	}


   // cashbox_pr
	if(isset($_GET['cashbox_pr'])) {
		$id = strip_tags($_POST['id']);
		$pr = strip_tags($_POST['pr']);
      $upd = db::query("UPDATE `retail_orders_products` SET price = '$pr' WHERE `id`='$id'");
      if ($upd) echo 'yes'; else echo 'none';

      exit();
	}

   // cashbox_qn
	if(isset($_GET['cashbox_qn'])) {
		$id = strip_tags($_POST['id']);
		$qn = strip_tags($_POST['qn']);
      $retailop_d = fun::retailop($id);
      $pitem_id = $retailop_d['product_id'];
      $pitem_d = product::product($pitem_id);

      $quantity = db::query("select * from product_item_quantity where product_id = '$pitem_id' limit 1");
      if (mysqli_num_rows($quantity)) {
         $quantity = mysqli_fetch_array($quantity);
         $quantity_id = $quantity['id'];

         // $min_qn = ($quantity['quantity'] + $retailop_d['quantity']) - $qn;
         // $upd = db::query("UPDATE `product_item_quantity` SET quantity = '$min_qn' WHERE id = '$quantity_id'");
         $upd2 = db::query("UPDATE `retail_orders_products` SET quantity = '$qn' WHERE `id`='$id'");
         if ($upd2) echo 'yes'; else echo 'none';
      } else echo 0;

      exit();
	}

   // cashbox_plus
	if(isset($_GET['cashbox_plus'])) {
		$id = strip_tags($_POST['id']);
      $upd1 = db::query("UPDATE `retail_orders_products` SET `quantity` = `quantity` + 1 WHERE id = '$id';");
      if ($upd1) echo 'yes'; else echo 'none';
      exit();
	}

   // cashbox_minus
	if(isset($_GET['cashbox_minus'])) {
		$id = strip_tags($_POST['id']);
      $upd1 = db::query("UPDATE `retail_orders_products` SET `quantity` = `quantity` - 1 WHERE id = '$id';");
      if ($upd1) echo 'yes'; else echo 'none';
      exit();
	}


   // cashbox_pay
	if(isset($_GET['cashbox_pay'])) {
		$id = strip_tags($_POST['id']);
		$nm = strip_tags($_POST['nm']);
		$total = strip_tags($_POST['total']);
		$qr = @strip_tags($_POST['qr']);
		$cash = @strip_tags($_POST['cash']);
		$delivery = @strip_tags($_POST['delivery']);
		$phone = @strip_tags($_POST['phone']);
		$address = @strip_tags($_POST['address']);
		$add = @strip_tags($_POST['add']);
		$preorder = @strip_tags($_POST['preorder']);
      
		$cashbox_number = product::next_number_order($start_cdate, $end_cdate, $branch);

      $upd = db::query("UPDATE `retail_orders` SET `paid` = 1, `total` = '$total', `branch_id` = '$branch', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if (!$nm) $upd = db::query("UPDATE `retail_orders` SET `number` = '$cashbox_number', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($qr) $upd = db::query("UPDATE `retail_orders` SET `pay_qr` = '$qr', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($delivery) $upd = db::query("UPDATE `retail_orders` SET `pay_delivery` = '$delivery', `upd_dt` = '$datetime' WHERE `id`='$id'");
      else $upd = db::query("UPDATE `retail_orders` SET `order_status` = 2, `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($phone) $upd = db::query("UPDATE `retail_orders` SET `phone` = '$phone', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($address) $upd = db::query("UPDATE `retail_orders` SET `address` = '$address', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($add) $upd = db::query("UPDATE `retail_orders` SET `additional` = '$add', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($preorder) $upd = db::query("UPDATE `retail_orders` SET `preorder_dt` = '$preorder', `upd_dt` = '$datetime' WHERE `id`='$id'");
      if ($cash) $upd = db::query("UPDATE `retail_orders` SET `pay_cash` = '$cash', `upd_dt` = '$datetime' WHERE `id`='$id'");
      // } else $upd = db::query("UPDATE `retail_orders` SET `number` = '$cashbox_number', `paid` = 1, `total` = '$total', `pay_qr` = '$qr', `pay_cash` = '$cash', `order_status` = 2, `branch_id` = '$branch', `upd_dt` = '$datetime' WHERE `id`='$id'");


   
      //
      if ($branch == 1) $chat_id = "-1002262540522"; else $chat_id = "-1002461390168";
      $txt = '';
      $arr = array(
			'Номер заказ: ' => $cashbox_number,
			'Телефон: ' => $phone,
			'Адрес: ' => $address,
			'Предоплата: ' => $qr,
			'Наличный: ' => $cash,
			'Жалпы: '   => $total,
		);
		foreach ($arr as $key => $value) {$txt .= "<b>".$key."</b> ".$value."%0A";};
		$sendToTelegram = fopen("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&parse_mode=html&text={$txt}","r");

      // 
      $ins = db::query("INSERT INTO `retail_orders`(`user_id`) VALUES ('$user_id')");
      if ($upd && $ins && $sendToTelegram) echo 'yes'; else echo "error";

      exit();
	}

















   // product_add
	if(isset($_GET['product_add'])) {
      $article = strip_tags($_POST['article']);
		$barcode = strip_tags($_POST['barcode']);
		$quantity = strip_tags($_POST['quantity']);
		$price = strip_tags($_POST['price']);
      $oid = strip_tags($_POST['oid']);
		$name = strip_tags($_POST['name']);
		$warehouses_id = 5;
		
      $id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product`")))['max(id)'] + 1;
      $ins = db::query("INSERT INTO `product`(`id`, `arh`) VALUES ('$id', 1)");
      if ($ins) {
         if ($name) $sql = db::query("UPDATE `product` SET `name_kz` = '$name', `name_ru` = '$name' WHERE `id`='$id'");

			$item_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item`")))['max(id)'] + 1;
         $ins_item = db::query("INSERT INTO `product_item`(`id`, `product_id`, `arh`) VALUES ('$item_id', '$id', 1)");
			if ($ins_item) {
				if ($article) $sql = db::query("UPDATE `product_item` SET `article`='$article' WHERE id = '$item_id'");
				if ($barcode) $sql = db::query("UPDATE `product_item` SET `barcode`='$barcode' WHERE id = '$item_id'");
				if ($price) $sql = db::query("UPDATE `product_item` SET `price`='$price' WHERE id = '$item_id'"); else $price = 0;

				if ($quantity) {
               $view_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_quantity`")))['max(id)'] + 1;
               $ins_view = db::query("INSERT INTO `product_item_quantity`(`id`, `product_id`, `item_id`, `warehouses_id`, `quantity`) VALUES ('$view_id', '$id', '$item_id', '$warehouses_id', '$quantity')");
               $ins = db::query("INSERT INTO `retail_orders_products`(`order_id`, `product_id`, `product_item_id`, `quantity`, `price`) VALUES ('$oid', '$id', '$item_id', '$quantity', '$price')");
            }
			}

         echo 'yes';
      } else echo 'error';

      exit();
	}









   // 
   if(isset($_GET['product_add_arh'])) {
      $catalog = strip_tags($_POST['catalog']);
		$brand = strip_tags($_POST['brand']);
		$purchase_price = strip_tags($_POST['purchase_price']);
		$discount_price = strip_tags($_POST['discount_price']);
		$img = strip_tags($_POST['img']);
		$color = strip_tags($_POST['color']);
		$size = strip_tags($_POST['size']);
		$warehouses_id = strip_tags($_POST['warehouses']);

      if ($catalog) $sql = db::query("UPDATE `product` SET `catalog_id`='$catalog' WHERE `id`='$id'");
      if ($brand) {
         $brand_id = product::pr_brand_name($brand);
         if ($brand_id == 0) {
            $brand_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_ch_brand`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_ch_brand`(`id`, `name`) VALUES ('$brand_id', '$brand')");
         }
         $upd = db::query("UPDATE `product` SET `brand_id`='$brand_id' WHERE `id`='$id'");
      }

      if ($purchase_price) $sql = db::query("UPDATE `product_item` SET `purchase_price`='$purchase_price' WHERE id = '$item_id'");
      if ($discount_price) $sql = db::query("UPDATE `product_item` SET `discount_price`='$discount_price' WHERE id = '$item_id'");
      if ($img) $sql = db::query("UPDATE `product_item` SET `img`='$img' WHERE id = '$item_id'");
      if ($color) {
         $color_id = product::pr_color_name($color);
         if ($color_id == 0) {
            $color_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_color`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_item_color`(`id`, `name_ru`) VALUES ('$color_id', '$color')");
         }
         $upd = db::query("UPDATE `product_item` SET `color_id` = '$color_id' WHERE id = '$item_id'");
      }
      if ($size) {
         $size_id = product::pr_size_name($size);
         if ($size_id == 0) {
            $size_id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_size`")))['max(id)'] + 1;
            $ins = db::query("INSERT INTO `product_item_size`(`id`, `name`) VALUES ('$size_id', '$size')");
         }
         $upd = db::query("UPDATE `product_item` SET `size_id` = '$size_id' WHERE id = '$item_id'");
      }
   
   }