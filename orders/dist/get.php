<? include "../../config/core.php";
	// Раздача экранының әрекеттері (orders/dist/index.php, assets/js/dist.js). Тек кірген қызметкер, тек өз компаниясының тапсырысы.
	if (!$user_id) exit('none');
	$company = (int)$company;
	$uid = (int)$user_id;

	function dist_order($id) {
		global $company;
		return mysqli_fetch_assoc(db::query("select id, order_status, order_type from retail_orders where id = '".(int)$id."' and company_id = '$company' limit 1"));
	}
	function dist_log($id, $old, $new) {
		global $uid, $datetime;
		if ($old != $new) db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('".(int)$id."', 'status', '".(int)$old."', '".(int)$new."', '$uid', '$datetime')"); // этап уақыты үшін
	}


	// Статус тарихы (кассадағыдай, тек оқу)
	if (isset($_GET['history'])) {
		require_once __DIR__.'/hist.php';
		$o = mysqli_fetch_assoc(db::query("select * from retail_orders where id = '".(int)@$_POST['id']."' and company_id = '$company' limit 1"));
		if ($o) echo dist_hist_html($o);
		exit();
	}

	// Курьер тағайындау / алып тастау. «Жолда»-ны курьер өзі қояды.
	if (isset($_GET['change_staff'])) {
		$o = dist_order(@$_POST['order_id']);
		if (!$o) exit('none');
		$o_st = (int)$o['order_status'];
		if (!in_array($o_st, [1, 8, 2, 11])) exit('locked'); // жолда/жабық тапсырыстың курьері ауыспайды
		if (@$_POST['id'] == 'off') {
			db::query("UPDATE `retail_orders` SET `сourier_id` = null, order_status = 2 WHERE id = '".(int)$o['id']."'");
			$n_st = 2;
		} else {
			$cid = (int)@$_POST['id'];
			if (!mysqli_num_rows(db::query("select id from company_staff where user_id = '$cid' and positions_id = 6 and company_id = '$company' limit 1"))) exit('none');
			db::query("UPDATE `retail_orders` SET `сourier_id` = '$cid', order_status = 11 WHERE id = '".(int)$o['id']."'");
			$n_st = 11;
		}
		dist_log($o['id'], $o_st, $n_st);
		exit('yes');
	}

	// Кафе: клиентке берілді → Столда (9)
	if (isset($_GET['table'])) {
		$o = dist_order(@$_POST['id']);
		if (!$o || (int)$o['order_type'] != 4 || !in_array((int)$o['order_status'], [1, 8, 2])) exit('none');
		db::query("UPDATE `retail_orders` SET order_status = 9, upd_dt = '$datetime' WHERE id = '".(int)$o['id']."'");
		dist_log($o['id'], $o['order_status'], 9);
		exit('yes');
	}

	// Өзімен / Сервис: клиент алды → Аяқталды (4)
	if (isset($_GET['check'])) {
		$o = dist_order(@$_POST['id']);
		if (!$o || !in_array((int)$o['order_type'], [2, 3]) || !in_array((int)$o['order_status'], [1, 8, 2])) exit('none');
		db::query("UPDATE `retail_orders` SET `order_status` = 4, upd_dt = '$datetime' WHERE id = '".(int)$o['id']."'");
		dist_log($o['id'], $o['order_status'], 4);
		exit('yes');
	}

	echo 'none';
