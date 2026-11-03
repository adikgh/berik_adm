<? include "../config/core.php";
	include "lib.php";

	header('Content-Type: application/json; charset=utf-8');
	function out($ok, $data = []) { exit(json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE)); }

	if (!$user_id || !rv::can($user_right)) out(false, ['msg' => 'Кіру рұқсаты жоқ']);
	rv::install();
	$cmp = (int)$company;
	$now = date('Y-m-d H:i:s');
	$cook = rv::is_cook($user_right);

	// Сақтау (жаңа немесе санап жатқан). post=1 болса бірден аяқтайды.
	if (isset($_GET['save'])) {
		$id = (int)@$_POST['id'];
		if ($id) {
			$rev = rv::get($id, $cmp);
			if (!$rev) out(false, ['msg' => 'Ревизия табылмады']);
			if ($rev['status'] == 1) out(false, ['msg' => 'Ревизия аяқталған, өзгертуге болмайды']);
		}
		$date = @$_POST['rev_date'] && strtotime($_POST['rev_date']) ? date('Y-m-d', strtotime($_POST['rev_date'])) : date('Y-m-d');
		if ($date > date('Y-m-d')) out(false, ['msg' => 'Ревизия күні бүгіннен кейін болмайды']);
		$comment = wb::esc(@$_POST['comment']);

		// жолдар: raw_id => нақты сан ('' болса саналмаған)
		$rows = json_decode((string)@$_POST['items'], true);
		$facts = [];
		if (is_array($rows)) foreach ($rows as $r) {
			$raw = (int)@$r['raw_id'];
			$f = trim((string)@$r['fact']);
			if (!$raw || $f === '') continue;
			$v = round(wb::num($f), 3);
			if ($v < 0) out(false, ['msg' => 'Сан 0-ден кем болмайды']);
			$facts[$raw] = $v;
		}
		if ($facts) {
			$ids = implode(',', array_map('intval', array_keys($facts)));
			$q = db::query("select id from product_raw where company_id = '$cmp' and id in ($ids)");
			$okr = []; while ($q && $r = mysqli_fetch_assoc($q)) $okr[(int)$r['id']] = 1;
			$facts = array_intersect_key($facts, $okr);
		}
		$post = @$_POST['post'] == 1;
		if ($post && !$facts) out(false, ['msg' => 'Кемінде бір шикізатты санаңыз']);

		mysqli_begin_transaction(db::$connect);
		if ($id) {
			$ok = db::query("update stock_revision set rev_date = '$date', comment = '$comment', upd_dt = '$now' where id = '$id' and status = 0");
		} else {
			$num = rv::next_number($cmp);
			$ok = db::query("insert into stock_revision (company_id, branch_id, number, rev_date, comment, user_id)
				values ('$cmp', '".(int)$branch."', '$num', '$date', '$comment', '".(int)$user_id."')");
			$id = (int)mysqli_insert_id(db::$connect);
		}
		if ($ok) $ok = db::query("delete from stock_revision_item where revision_id = '$id'");
		if ($ok) foreach ($facts as $raw => $v) {
			if (!db::query("insert into stock_revision_item (revision_id, raw_id, qty_fact) values ('$id', '$raw', '$v')")) { $ok = false; break; }
		}
		if ($ok) mysqli_commit(db::$connect); else { mysqli_rollback(db::$connect); out(false, ['msg' => 'Сақтау кезінде қате шықты']); }

		if ($post) {
			if (!rv::post($id, $cmp, $user_id)) out(false, ['msg' => rv::$err, 'id' => $id]);
			out(true, ['msg' => 'Ревизия аяқталды, қалдық жаңарды', 'id' => $id]);
		}
		out(true, ['msg' => 'Сақталды', 'id' => $id]);
	}

	// Аяқтауды болдырмау: тек админ, тек соңғы ревизия
	if (isset($_GET['unpost'])) {
		if ($cook) out(false, ['msg' => 'Болдырмауды тек админ жасай алады']);
		$rev = rv::get((int)@$_POST['id'], $cmp);
		if (!$rev || $rev['status'] != 1) out(false, ['msg' => 'Ревизия табылмады']);
		$later = db::query("select id from stock_revision where company_id = '$cmp' and branch_id = '".(int)$rev['branch_id']."' and status = 1 and id != '".(int)$rev['id']."'
			and (rev_date > '".wb::esc($rev['rev_date'])."' or (rev_date = '".wb::esc($rev['rev_date'])."' and id > '".(int)$rev['id']."')) limit 1");
		if ($later && mysqli_num_rows($later)) out(false, ['msg' => 'Бұдан кейін аяқталған ревизия бар. Алдымен соны болдырмаңыз.']);
		if (!rv::unpost($rev['id'], $cmp)) out(false, ['msg' => 'Қате шықты']);
		out(true, ['msg' => 'Болдырмадық, қалдық бұрынғы күйіне қайтты']);
	}

	// Өшіру: тек аяқталмаған
	if (isset($_GET['delete'])) {
		$rev = rv::get((int)@$_POST['id'], $cmp);
		if (!$rev) out(false, ['msg' => 'Ревизия табылмады']);
		if ($rev['status'] == 1) out(false, ['msg' => 'Аяқталған ревизияны өшіруге болмайды. Алдымен болдырмаңыз.']);
		db::query("delete from stock_revision_item where revision_id = '".(int)$rev['id']."'");
		db::query("delete from stock_revision where id = '".(int)$rev['id']."'");
		out(true, ['msg' => 'Өшірілді']);
	}

	out(false, ['msg' => 'Белгісіз сұраныс']);
