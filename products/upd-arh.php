<? include "../config/core.php";


   // 
   // $product_all = db::query("select * from product");
   // while ($pr_d = mysqli_fetch_assoc($product_all)) {

   // }

   $item = db::query("select * from product_item");
   while ($item_d = mysqli_fetch_assoc($item)) {
      $product_id = $item_d['product_id'];
      $item_id = $item_d['id'];
      $warehouses_id = 5;
      $quantity = $item_d['quantity'];

      $iquantity = db::query("select * from product_item_quantity where item_id = '$item_id'");
      if (mysqli_num_rows($iquantity) == 0) {
         $upd = db::query("INSERT INTO `product_item_quantity`(`product_id`, `item_id`, `warehouses_id`, `quantity`) VALUES ('$product_id', '$item_id', '$warehouses_id', '$quantity')");
      }

   }


