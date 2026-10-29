<? include "../config/core.php";
	include "lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $data = []) { exit(json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE)); }

	if (!$user_id || @$user_right['positions_id'] == 3) out(false, ['msg' => 'Кіру рұқсаты жоқ']);
	wb::install();
	$cmp = (int)$company;
	$now = date('Y-m-d H:i:s');

	// Накладнойды сақтау (жаңа немесе жоба). post=1 болса бірден қабылдайды.
	if (isset($_GET['save'])) {
		$id = (int)@$_POST['id'];
		if ($id) {
			$w = wb::get($id, $cmp);
			if (!$w) out(false, ['msg' => 'Накладной табылмады']);
			if ($w['status'] == 1) out(false, ['msg' => 'Накладной қабылданған. Алдымен қабылдауды болдырмаңыз.']);
		}

		// жеткізуші
		$sup = @$_POST['supplier_id'];
		$sup_new = wb::esc(@$_POST['supplier_new']);
		if ($sup === 'new') {
			if ($sup_new === '') out(false, ['msg' => 'Жаңа жеткізушінің атын жазыңыз']);
			$ex = db::query("select id from supplier where company_id = '$cmp' and name = '$sup_new' limit 1");
			if ($ex && mysqli_num_rows($ex)) $sup = (int)mysqli_fetch_assoc($ex)['id'];
			else { db::query("insert into supplier (company_id, name) values ('$cmp', '$sup_new')"); $sup = (int)mysqli_insert_id(db::$connect); }
		} else {
			$sup = (int)$sup;
			if ($sup && !mysqli_num_rows(db::query("select id from supplier where id = '$sup' and company_id = '$cmp'"))) $sup = 0;
		}

		$date = @$_POST['doc_date'] && strtotime($_POST['doc_date']) ? date('Y-m-d', strtotime($_POST['doc_date'])) : date('Y-m-d');
		$doc_no = wb::esc(@$_POST['doc_no']);
		$comment = wb::esc(@$_POST['comment']);
		$paid = max(0, round(wb::num(@$_POST['paid']), 2));

		// жолдар: сома серверде қайта есептеледі
		$rows = json_decode((string)@$_POST['items'], true);
		$items = []; $total = 0;
		if (is_array($rows)) foreach ($rows as $r) {
			$name = trim(strip_tags((string)@$r['name']));
			$qty = round(wb::num(@$r['qty']), 3);
			$price = round(wb::num(@$r['price']), 2);
			if ($name === '' && !$qty && !$price) continue;
			if ($name === '') out(false, ['msg' => 'Тауардың атауын жазыңыз']);
			if ($qty <= 0) out(false, ['msg' => '«'.$name.'» саны 0-ден көп болуы керек']);
			if ($price < 0) out(false, ['msg' => '«'.$name.'» бағасы дұрыс емес']);
			$unit = trim(strip_tags((string)@$r['unit'])) ?: 'кг';
			$sum = round($qty * $price, 2);
			$total += $sum;
			$items[] = ['raw_id' => (int)@$r['raw_id'], 'name' => mb_substr($name, 0, 255), 'unit' => mb_substr($unit, 0, 20), 'qty' => $qty, 'price' => $price, 'sum' => $sum];
		}
		if (!$items) out(false, ['msg' => 'Кемінде бір тауар қосыңыз']);
		$total = round($total, 2);

		// raw_id осы компанияныкі ме
		foreach ($items as &$i) {
			if ($i['raw_id'] && !mysqli_num_rows(db::query("select id from product_raw where id = '{$i['raw_id']}' and company_id = '$cmp'"))) $i['raw_id'] = 0;
		}
		unset($i);

		mysqli_begin_transaction(db::$connect);
		$sup_sql = $sup ? "'$sup'" : 'NULL';
		if ($id) {
			$ok = db::query("update waybill set supplier_id = $sup_sql, doc_no = '$doc_no', doc_date = '$date', total = '$total', paid = '$paid',
				comment = '$comment', upd_dt = '$now' where id = '$id' and company_id = '$cmp' and status = 0");
			$ok = $ok && db::query("delete from waybill_item where waybill_id = '$id'");
		} else {
			$num = wb::next_number($cmp);
			$br = (int)$branch;
			$ok = db::query("insert into waybill (company_id, branch_id, number, supplier_id, doc_no, doc_date, total, paid, comment, user_id, ins_dt)
				values ('$cmp', '$br', '$num', $sup_sql, '$doc_no', '$date', '$total', '$paid', '$comment', '".(int)$user_id."', '$now')");
			$id = (int)mysqli_insert_id(db::$connect);
		}
		if ($ok && $id) {
			$vals = [];
			foreach ($items as $i) {
				$n = wb::esc($i['name']); $u = wb::esc($i['unit']);
				$vals[] = "('$id', ".($i['raw_id'] ? "'{$i['raw_id']}'" : 'NULL').", '$n', '$u', '{$i['qty']}', '{$i['price']}', '{$i['sum']}')";
			}
			$ok = db::query("insert into waybill_item (waybill_id, raw_id, name, unit, quantity, price, sum) values ".implode(',', $vals));
		}
		if (!$ok || !$id) { mysqli_rollback(db::$connect); out(false, ['msg' => 'Сақтау кезінде қате шықты']); }
		mysqli_commit(db::$connect);

		if ((int)@$_POST['post'] == 1) {
			if (!wb::post($id, $cmp)) out(false, ['id' => $id, 'msg' => 'Сақталды, бірақ қабылдау кезінде қате шықты']);
			out(true, ['id' => $id, 'msg' => 'Қабылданды, қалдыққа қосылды']);
		}
		out(true, ['id' => $id, 'msg' => 'Сақталды']);
	}

	// Қабылдауды болдырмау
	if (isset($_GET['unpost'])) {
		$id = (int)@$_POST['id'];
		if (!wb::unpost($id, $cmp)) out(false, ['msg' => 'Болдырмау мүмкін болмады']);
		out(true, ['msg' => 'Қабылдау болдырылмады, қалдық азайды']);
	}

	// Жобаны өшіру (қабылданғанын өшіруге болмайды)
	if (isset($_GET['delete'])) {
		$id = (int)@$_POST['id'];
		$w = wb::get($id, $cmp);
		if (!$w) out(false, ['msg' => 'Накладной табылмады']);
		if ($w['status'] == 1) out(false, ['msg' => 'Алдымен қабылдауды болдырмаңыз']);
		db::query("delete from waybill_item where waybill_id = '$id'");
		db::query("delete from waybill where id = '$id' and company_id = '$cmp' and status = 0");
		out(true, ['msg' => 'Өшірілді']);
	}

	// Төлемді жаңарту (қабылданған накладнойға да болады)
	if (isset($_GET['pay'])) {
		$id = (int)@$_POST['id'];
		$paid = max(0, round(wb::num(@$_POST['paid']), 2));
		if (!wb::get($id, $cmp)) out(false, ['msg' => 'Накладной табылмады']);
		$ok = db::query("update waybill set paid = '$paid', upd_dt = '$now' where id = '$id' and company_id = '$cmp'");
		out((bool)$ok, ['msg' => $ok ? 'Төлем сақталды' : 'Қате шықты']);
	}

	// Жеткізушіні сақтау
	if (isset($_GET['supplier_save'])) {
		$id = (int)@$_POST['id'];
		$name = wb::esc(@$_POST['name']);
		$phone = wb::esc(@$_POST['phone']);
		$note = wb::esc(@$_POST['note']);
		if ($name === '') out(false, ['msg' => 'Атын жазыңыз']);
		if ($id) $ok = db::query("update supplier set name = '$name', phone = '$phone', note = '$note', upd_dt = '$now' where id = '$id' and company_id = '$cmp'");
		else $ok = db::query("insert into supplier (company_id, name, phone, note) values ('$cmp', '$name', '$phone', '$note')");
		out((bool)$ok, ['msg' => $ok ? 'Сақталды' : 'Қате шықты']);
	}

	// Жеткізушіні архивке жіберу / қайтару
	if (isset($_GET['supplier_archive'])) {
		$id = (int)@$_POST['id'];
		$v = (int)@$_POST['v'] ? 1 : 0;
		$ok = db::query("update supplier set archive = '$v', upd_dt = '$now' where id = '$id' and company_id = '$cmp'");
		out((bool)$ok);
	}

	out(false, ['msg' => 'Белгісіз сұрау']);
