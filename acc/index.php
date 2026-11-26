<? // Аккаунт бөлек бет емес, мәзірдегі поп-ап (block/acc_pop.php). Ескі сілтеме басты бетке барып, поп-апты ашады.
	// Раздача хостында — өз Аккаунт беті (orders/dist/acc.php), Кухняда — өз экраны (config/alias.php)
	$h = (string)@$_SERVER['HTTP_HOST'];
	if (preg_match('~^(dist|dst|dstb)\.~i', $h)) { header('location: /orders/dist/acc.php'); exit; }
	$acc_home = preg_match('~^(ktc|ktch|ktcb)\.~i', $h) ? '/orders/kitchen/' : '/stats/';
	header('location: '.$acc_home.'?acc=1');
	exit;
