<?
	// Ескі сілтеме: лоялдық беті екіге бөлінді — /clients/cashback.php және /clients/gift.php
	$m = preg_match('/^\d{4}-\d{2}$/', (string)@$_GET['m']) ? '?m='.$_GET['m'] : '';
	header('location: /clients/cashback.php'.$m, true, 301); exit;
