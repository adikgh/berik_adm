<? // Аккаунт бөлек бет емес, мәзірдегі поп-ап (block/acc_pop.php). Ескі сілтеме басты бетке барып, поп-апты ашады.
	// Раздача / Кухня хостында басты бет — өз экраны (config/alias.php)
	$h = (string)@$_SERVER['HTTP_HOST'];
	$acc_home = preg_match('~^(dist|dst|dstb)\.~i', $h) ? '/orders/dist/' : (preg_match('~^(ktc|ktch|ktcb)\.~i', $h) ? '/orders/kitchen/' : '/stats/');
	header('location: '.$acc_home.'?acc=1');
	exit;
