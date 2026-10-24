<? include "../../../config/core.php";

	// 
	if (!$user_right) header('location: /');

	// product item
	$item_id = $_GET['id'];
	$item = db::query("select * from product_item where id = '$item_id'");
	$item_d = mysqli_fetch_assoc($item);

   // 
   $product_d = product::product($item_d['product_id']);
	$product_id = $product_d['id'];
   
	// product item quantity
	$pitem = db::query("select * from product_item_quantity where item_id = '$item_id' order by ins_dt desc");


	// site setting
	$menu_name = 'products_item';
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['atops'] = true;
	$site_set['footer'] = false;
	$site_set['utop_bk'] = 'products/item/?id='.$product_id;
	$site_set['utop_type'] = 'item';
	$site_set['utop_nm'] = $item_d['article'];
	$css = ['products/main', 'products/item'];
	$js = ['products/main', 'products/view'];