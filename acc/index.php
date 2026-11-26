<? // Аккаунт бөлек бет емес, мәзірдегі поп-ап (block/acc_pop.php). Ескі сілтеме басты бетке барып, поп-апты ашады.
	// Раздача хостында (dist.* / dstb.*) басты бет — Раздача экраны (config/dist.php)
	$acc_home = preg_match('~^(dist|dst|dstb)\.~i', (string)@$_SERVER['HTTP_HOST']) ? '/orders/dist/' : '/stats/';
	header('location: '.$acc_home.'?acc=1');
	exit;
