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
		$v = "name = '$name', price = '$price', descr = '" . dev::e(mb_substr((string)@$_POST['descr'], 0, 250)) . "', arh = '" . (empty($_POST['arh']) ? 0 : 1) . "'";
		if ($id) db::query("update company_tariff set $v where id = '$id'");
		else db::query("insert into company_tariff set $v, number = '" . ((int)dev::one("select max(number) from company_tariff") + 1) . "'");
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}
	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
