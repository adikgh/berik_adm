<? include "../../../config/core.php";


   // view quantity
	if(isset($_GET['pitem_qn'])) {
		$quantity = strip_tags($_POST['quantity']);
		$id = strip_tags($_POST['id']);
      if ($quantity >= 0) {
         $ubd = db::query("UPDATE `product_item_quantity` SET quantity = '$quantity', upd_dt = '$datetime' WHERE id = '$id'");
         echo 'yes';
      } else echo 'null';
      exit();
	}

   
   // pitem_minus
	if(isset($_GET['pitem_minus'])) {
		$id = strip_tags($_POST['id']);
      $view_d = product::pr_item_view($id);
      if ($view_d['quantity'] > 0) {
         $ubd = db::query("UPDATE `product_item_quantity` SET quantity = quantity - 1, upd_dt = '$datetime' WHERE `id`='$id'");
         echo 'yes';
      } else echo 'null';
      exit();
	}
   // pitem_plus
	if(isset($_GET['pitem_plus'])) {
		$id = strip_tags($_POST['id']);
      $upd = db::query("UPDATE `product_item_quantity` SET quantity = quantity + 1, upd_dt = '$datetime' WHERE `id`='$id'");
      if ($upd) echo 'yes'; else echo 'error';
      exit();
	}
   // pitem_delete
	if(isset($_GET['pitem_delete'])) {
		$id = strip_tags($_POST['id']);
      $del = db::query("DELETE FROM `product_item_quantity` WHERE `id`='$id'");
      if ($del) echo 'yes'; else echo 'error';
      exit();
   }


   // view_add 
	if(isset($_GET['view_add'])) {
		$product_id = strip_tags($_POST['product']);
		$item_id = strip_tags($_POST['item']);
      $warehouses_id = strip_tags($_POST['warehouses']);
		$quantity = strip_tags($_POST['quantity']);
		$comment = strip_tags($_POST['comment']);

      $view = db::query("SELECT * FROM `product_item_quantity` WHERE warehouses_id = '$warehouses_id' and item_id = '$item_id'");
      if (mysqli_num_rows($view)) $upd_view = db::query("UPDATE `product_item_quantity` SET quantity = quantity + '$quantity' WHERE warehouses_id = '$warehouses_id' and item_id = '$item_id'");
      else {
         $id = (mysqli_fetch_assoc(db::query("SELECT max(id) FROM `product_item_quantity`")))['max(id)'] + 1;
         $ins = db::query("INSERT INTO `product_item_quantity`(`id`, `product_id`, `item_id`, `warehouses_id`, `quantity`) VALUES ('$id', '$product_id', '$item_id', '$warehouses_id', '$quantity')");
      }
      if ($comment) $upd = db::query("UPDATE `product_item_quantity` SET `comment` = '$comment' WHERE id = '$id'");

      if ($ins || $upd_view) echo 'yes'; else echo 'error';
      exit();
	}


   // pitem_upd 
	if(isset($_GET['pitem_upd'])) {
		$id = strip_tags($_POST['id']);
      $warehouses_id = strip_tags($_POST['warehouses']);
		$quantity = strip_tags($_POST['quantity']);
		$comment = strip_tags($_POST['comment']);

      if ($warehouses_id) $upd = db::query("UPDATE `product_item_quantity` SET `warehouses_id` = '$warehouses_id' WHERE id = '$id'");
      if ($quantity) $upd = db::query("UPDATE `product_item_quantity` SET `quantity` = '$quantity' WHERE id = '$id'");
      if ($comment) $upd = db::query("UPDATE `product_item_quantity` SET `comment` = '$comment' WHERE id = '$id'");

      echo 'yes';
      exit();
	}
   


   // item price update
	if(isset($_GET['item_price_upd'])) {
		$id = strip_tags($_POST['id']);
		$price = strip_tags($_POST['price']);
      $ubd = db::query("UPDATE `product_item` SET price = '$price', upd_dt = '$datetime' WHERE id = '$id'");
      if ($ubd) echo 'yes'; else echo 'none';
      exit();
	}
