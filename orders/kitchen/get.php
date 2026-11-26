<? include "../../config/core.php";
	// Кухня экранының әрекеттері (orders/kitchen/index.php, assets/js/kitchen.js). Тек өз компаниясының тапсырысы.
	if (!$user_id) exit('none');
	$cid = (int)$company;

	// Статус тарихы (кассадағыдай, тек оқу)
	if (isset($_GET['history'])) {
		require_once __DIR__.'/../dist/hist.php';
		$o = mysqli_fetch_assoc(db::query("select * from retail_orders where id = '".(int)@$_POST['id']."' and company_id = '$cid' limit 1"));
		if ($o) echo dist_hist_html($o);
		exit();
	}

	// Кезектегі тапсырыстар id тізімі (автожаңарту мен дыбыс үшін)
	if (isset($_GET['queue'])) {
		$ids = [];
		$q = db::query("select id from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$cid' and `order_type` in (1, 2, 4) and `order_status` in (1, 8) order by id asc");
		while ($r = mysqli_fetch_assoc($q)) $ids[] = (int)$r['id'];
		$sig = mysqli_fetch_assoc(db::query("select count(*) c, max(upd_dt) u from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and company_id = '$cid' and `order_type` in (1, 2, 4)"));
		header('Content-Type: application/json');
		echo json_encode(['ids' => $ids, 'sig' => $sig['c'].'|'.$sig['u']]);
		exit();
	}

	// Жаңа (1) → Дайындалуда (8) → Дайын (2). Қате басылса: Дайын (2) → Дайындалуда (8)
	if (isset($_GET['kitchen_status'])) {
		$id = (int)@$_POST['id']; $st = (int)@$_POST['st'];
		$o = mysqli_fetch_assoc(db::query("select order_status, order_type from retail_orders where id = '$id' and company_id = '$cid' limit 1"));
		$cur = $o ? (int)$o['order_status'] : 0;
		$ok = $o && in_array((int)$o['order_type'], [1, 2, 4]) && (($st == 8 && in_array($cur, [1, 2])) || ($st == 2 && in_array($cur, [1, 8])));
		if (!$ok) exit('none');
		db::query("UPDATE `retail_orders` SET order_status = '$st', upd_dt = '$datetime' WHERE id = '$id' and order_status = '$cur'");
		db::query("INSERT INTO `retail_orders_log` (order_id, field, old_value, new_value, user_id, ins_dt) VALUES ('$id', 'status', '$cur', '$st', '".(int)$user_id."', '$datetime')");
		exit('yes');
	}

	echo 'none';
