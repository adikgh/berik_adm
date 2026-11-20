<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id || !dev::can('tariffs')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);
	if (isset($_GET['save'])) {
		$id = (int)@$_POST['id'];
		$name = dev::e(mb_substr((string)@$_POST['name'], 0, 100));
		$price = (int)preg_replace('/\D/', '', (string)@$_POST['price']);
		if ($name === '' || $price <= 0) out(['ok' => false, 'msg' => t::w('Enter name and price', 'Атауы мен бағасын жазыңыз')]);
		$v = "name = '$name', price = '$price', price_usd = '" . (int)preg_replace('/\D/', '', (string)@$_POST['price_usd']) . "', descr = '" . dev::e(mb_substr((string)@$_POST['descr'], 0, 250)) . "', arh = '" . (empty($_POST['arh']) ? 0 : 1) . "'";
		foreach (dev::$lims as $k) $v .= ", $k = '" . max(0, (int)@$_POST[$k]) . "'";
		if ($id) db::query("update company_tariff set $v where id = '$id'");
		else { db::query("insert into company_tariff set $v, number = '" . ((int)dev::one("select max(number) from company_tariff") + 1) . "'"); $id = (int)mysqli_insert_id(db::$connect); }
		if ($id) dev::tariff_modules_save($id, @$_POST['mod']);
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}
	// Тегін сынақ: модульдері (tariff_id = 0) және лимиттері
	if (isset($_GET['trial'])) {
		dev::tariff_modules_save(0, @$_POST['mod']);
		dev::trial_lims_save($_POST);
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}
	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
