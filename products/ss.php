<?php include "../config/core.php";

	// 
	if (!$user_id) header('location: /');
	header('location: all.php');

	// 
	$product = db::query("select * from product order by ins_dt desc limit 50");
	$filter = 0;


	// site setting
	$menu_name = 'products';
	// $site_set['footer'] = false;
	$css = ['products/main'];
	$js = ['products/main'];
?>
<? include "../block/header.php"; ?>

<? include "../block/footer.php"; ?>