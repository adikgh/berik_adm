// quantity_sel 
	if(isset($_GET['quantity_sel'])) {
		$id = strip_tags($_POST['id']);
      $txt = ''; $color = ''; $size = ''; $type = '';

      $productq = db::query("select * from product_quantity where product_id = '$id'");
      if (mysqli_num_rows($productq) != 0) {
         while ($productq_d = mysqli_fetch_assoc($productq)) {
            if ($productq_d['color_id']) $color = '<div class="form_table_ii">' . (fun::color($productq_d['color_id']))['name_ru'] . '</div>'; else $color = '';
            if ($productq_d['size_id']) $size = '<div class="form_table_ii">' . (fun::product_size($productq_d['size_id']))['name'] . '</div>'; else $size = '';
            if ($productq_d['type_id']) $type = '<div class="form_table_ii">' . (fun::product_type($productq_d['type_id']))['name'] . '</div>'; else $type = '';
            $quantity = '<div class="form_table_p">' . $productq_d['quantity'] . '</div>';
            $txt .= '<div class="form_table_i"><div class="form_table_c">' . $color . $size . $type . '</div>' . $quantity . '</div>';
         }
         echo $txt;
      } else echo 'none';

      exit();
	}

   // quantity_add 
	if(isset($_GET['quantity_add'])) {
		$id = strip_tags($_POST['id']);
		$color_id = strip_tags($_POST['color']);
		$size_id = strip_tags($_POST['size']);
		$type_id = strip_tags($_POST['type']);
		$quantity = strip_tags($_POST['quantity']);


      if ($color_id == '' && $size_id == '' && $type_id == '') {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id is null and size_id is null and type_id is null");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id is null and size_id is null and type_id is null");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `quantity`) VALUES ('$id', '$quantity')"); 
      } else if ($color_id && $size_id == '' && $type_id == '') {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id = '$color_id' and size_id is null and type_id is null");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id = '$color_id' and size_id is null and type_id is null");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `color_id`, `quantity`) VALUES ('$id', '$color_id', '$quantity')");
      } else if ($color_id == '' && $size_id && $type_id == '') {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id is null and size_id = '$size_id' and type_id is null");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id is null and size_id = '$size_id' and type_id is null");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `size_id`, `quantity`) VALUES ('$id', '$size_id', '$quantity')");
      } else if ($color_id == '' && $size_id == '' && $type_id) {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id is null and size_id is null and type_id = '$type_id'");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id is null and size_id is null and type_id = '$type_id'");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `type_id`, `quantity`) VALUES ('$id', '$type_id', '$quantity')");
      } else if ($color_id && $size_id && $type_id == '') {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id = '$color_id' and size_id = '$size_id' and type_id is null");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id = '$color_id' and size_id = '$size_id' and type_id is null");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `color_id`, `size_id`, `quantity`) VALUES ('$id', '$color_id', '$size_id', '$quantity')");
      } else if ($color_id && $size_id == '' && $type_id) {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id = '$color_id' and size_id is null and type_id = '$type_id'");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id = '$color_id' and size_id is null and type_id = '$type_id'");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `color_id`, `type_id`, `quantity`) VALUES ('$id', '$color_id', '$type_id', '$quantity')");
      } else if ($color_id == '' && $size_id && $type_id) {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id is null and size_id = '$size_id' and type_id = '$type_id'");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id is null and size_id = '$size_id' and type_id = '$type_id'");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `size_id`, `type_id`, `quantity`) VALUES ('$id', '$size_id', '$type_id', '$quantity')");
      } else {
         $sel = db::query("select * from product_quantity where product_id = '$id' and color_id = '$color_id' and size_id = '$size_id' and type_id = '$type_id'");
         if (mysqli_num_rows($sel) != 0) $ubd = db::query("update `product_quantity` set `quantity`='$quantity' where product_id = '$id' and color_id = '$color_id' and size_id = '$size_id' and type_id = '$type_id'");
         else $sql = db::query("INSERT INTO `product_quantity`(`product_id`, `color_id`, `size_id`, `type_id`, `quantity`) VALUES ('$id', '$color_id', '$size_id', '$type_id', '$quantity')");
      }


      $txt = ''; $color = ''; $size = ''; $type = '';
      $productq = db::query("select * from product_quantity where product_id = '$id'");
      if (mysqli_num_rows($productq) != 0) {
         while ($productq_d = mysqli_fetch_assoc($productq)):
            if ($productq_d['color_id']) $color = '<div class="form_table_ii">' . (fun::color($productq_d['color_id']))['name_ru'] . '</div>'; else $color = '';
            if ($productq_d['size_id']) $size = '<div class="form_table_ii">' . (fun::product_size($productq_d['size_id']))['name'] . '</div>'; else $size = '';
            if ($productq_d['type_id']) $type = '<div class="form_table_ii">' . (fun::product_type($productq_d['type_id']))['name'] . '</div>'; else $type = '';
            $quantity = '<div class="form_table_p">' . $productq_d['quantity'] . '</div>';
            $txt .= '<div class="form_table_i"><div class="form_table_c">' . $color . $size . $type . '</div>' . $quantity . '</div>';
         endwhile;
         echo $txt;
      } else echo 'none';

      exit();
	}