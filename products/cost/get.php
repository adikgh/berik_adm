<? include "../../config/core.php";
	include "lib.php";
	// Себес: баптауды сақтау / қайта есептеу
	if (!$user_id) exit('error');
	$cmp = (int)$company;
	if (!cost::ready()) exit('error');

	if (isset($_GET['setting'])) {
		$f = ['orders_day', 'salary', 'rent', 'utility', 'delivery', 'ads'];
		$v = [];
		foreach ($f as $k) $v[$k] = (int)preg_replace('/\D+/', '', (string)@$_POST[$k]);
		$m = round(min(500, max(0, (float)str_replace(',', '.', (string)@$_POST['markup']))), 2);
		$q = db::query("insert into cost_setting (company_id, ".implode(', ', $f).", markup, upd_dt) values ('$cmp', '".implode("', '", $v)."', '$m', NOW())
			on duplicate key update ".implode(', ', array_map(function ($k) { return "$k = values($k)"; }, $f)).", markup = values(markup), upd_dt = NOW()");
		exit($q ? 'yes' : 'error');
	}

	// Дайындаманың рецепті: атауы, өлшемі, шығымы және құрамы (шикізат немесе басқа дайындама)
	if (isset($_GET['prep_save'])) {
		$id = (int)@$_POST['id'];
		$name = trim(strip_tags((string)@$_POST['name']));
		$unit = in_array(@$_POST['unit'], ['кг', 'шт', 'л']) ? $_POST['unit'] : 'шт';
		$y = round((float)str_replace(',', '.', (string)@$_POST['yield']), 3);
		$parts = json_decode((string)@$_POST['parts'], true);
		if ($name === '') exit('name');
		if ($y <= 0) exit('yield');
		if (!is_array($parts) || !$parts) exit('parts');
		$raw = []; $q = db::query("select id from product_raw where company_id = '$cmp'"); while ($r = mysqli_fetch_assoc($q)) $raw[$r['id']] = 1;
		$cmpi = []; $q = db::query("select id from product_comp where company_id = '$cmp'"); while ($r = mysqli_fetch_assoc($q)) $cmpi[$r['id']] = 1;
		if ($id && !isset($cmpi[$id])) exit('error');
		$ok = [];
		foreach ($parts as $p) {
			$pid = (int)@$p['id']; $pq = round((float)@$p['q'], 4);
			if ($pq <= 0) continue;
			if (@$p['t'] === 'r' && isset($raw[$pid])) $ok[] = ['r', $pid, $pq];
			elseif (@$p['t'] === 'c' && isset($cmpi[$pid])) { if ($pid == $id) exit('self'); $ok[] = ['c', $pid, $pq]; }
		}
		if (!$ok) exit('parts');
		$en = mysqli_real_escape_string(db::$connect, $name);
		$dup = db::query("select id from product_comp where company_id = '$cmp' and (name_ru = '$en' or name_kz = '$en') and id <> '$id' limit 1");
		if ($dup && mysqli_num_rows($dup)) exit('exists');
		mysqli_begin_transaction(db::$connect);
		if ($id) $r1 = db::query("update product_comp set name_kz = if(name_kz is null or name_kz = '' or name_kz = name_ru, '$en', name_kz), name_ru = '$en', unit = '$unit', yield_qty = '$y', ubd_dt = NOW() where id = '$id' and company_id = '$cmp'");
		else { $r1 = db::query("insert into product_comp (company_id, name_kz, name_ru, unit, yield_qty, kind, price) values ('$cmp', '$en', '$en', '$unit', '$y', 'semi', 0)"); $id = (int)mysqli_insert_id(db::$connect); }
		$r2 = $r1 && db::query("delete from product_comp_part where comp_id = '$id'");
		foreach ($ok as $p) if ($r2) $r2 = db::query("insert into product_comp_part (comp_id, ".($p[0] == 'r' ? 'raw_id' : 'sub_comp_id').", quantity) values ('$id', '{$p[1]}', '{$p[2]}')");
		if (!$r2 || !$id) { mysqli_rollback(db::$connect); exit('error'); }
		mysqli_commit(db::$connect);
		cost::recalc($cmp);
		exit('yes');
	}

	if (isset($_GET['recalc'])) { cost::recalc($cmp); exit('yes'); }
	exit('error');
