<? include "../../config/core.php";
	include "../cost/lib.php";
	// Себес-2: кестедегі ұяшықты өзгерту, жол қосу / өшіру. Жауап: yes немесе қате коды
	if (!$user_id) exit('error');
	$cmp = (int)$company;
	if (!cost::ready()) exit('error');
	$a = (string)@$_GET['a'];
	$P = function ($k) { return trim((string)@$_POST[$k]); };
	$num = function ($v) { return round((float)str_replace([',', ' '], ['.', ''], (string)$v), 4); };
	$esc = function ($v) { return mysqli_real_escape_string(db::$connect, $v); };
	$one = function ($sql) { $q = db::query($sql); return $q ? mysqli_fetch_assoc($q) : null; };
	$isw = function ($u) { return in_array(mb_strtolower(trim((string)$u)), ['кг', 'л']); };
	$unit = function ($u) { return in_array($u, ['кг', 'л', 'шт']) ? $u : 'шт'; };
	$done = function ($ok) use ($cmp) { if ($ok) cost::recalc($cmp); exit($ok ? 'yes' : 'error'); };

	// компанияның шикізаты / құрамы / өнімі ме
	$raw_of = function ($id) use ($one, $cmp) { return $one("select * from product_raw where id = '".(int)$id."' and company_id = '$cmp'"); };
	$comp_of = function ($id) use ($one, $cmp) { return $one("select * from product_comp where id = '".(int)$id."' and company_id = '$cmp'"); };
	$prd_of = function ($id) use ($one, $cmp) { return $one("select id from product where id = '".(int)$id."' and company_id = '$cmp'"); };

	// шикізатқа байланған құрам (тағам/сет құрамына шикізат тікелей қосылғанда), болмаса жасалады
	$comp_for_raw = function ($rid) use ($one, $cmp, $raw_of, $esc) {
		$r = $raw_of($rid); if (!$r) return 0;
		$c = $one("select c.id from product_comp c where c.company_id = '$cmp' and c.raw_id = '{$r['id']}'
			and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by c.id limit 1");
		if ($c) return (int)$c['id'];
		$n = $esc($r['name_ru'] ?: $r['name_kz']);
		$ok = db::query("insert into product_comp (company_id, name_kz, name_ru, unit, raw_id, kind, price) values ('$cmp', '".$esc($r['name_kz'] ?: $n)."', '$n', '".$esc((string)$r['unit'])."', '{$r['id']}', 'raw', '".(int)$r['price']."')");
		return $ok ? (int)mysqli_insert_id(db::$connect) : 0;
	};
	// r12 / c5 / d7 → [түрі, id]
	$code = function ($v) { return preg_match('/^([rcd])(\d+)$/', (string)$v, $m) ? [$m[1], (int)$m[2]] : [null, 0]; };

	// ── Тікелей шикізат (тағам түрі 3): шикізатпен байланыс (products/cost2/kind.php, assets/js/products/goods.js). Жауап JSON
	if (in_array($a, ['goods_info', 'goods_find', 'goods_link', 'goods_new'], true)) {
		header('Content-Type: application/json; charset=utf-8');
		try {
			require_once __DIR__.'/kind.php';
			$pid = (int)@$_POST['pid']; $force = !empty($_POST['force']);
			if ($a == 'goods_info') exit(json_encode(pkind::goods_info($cmp, $pid), JSON_UNESCAPED_UNICODE));
			if ($a == 'goods_find') exit(json_encode(['items' => pkind::goods_find($cmp, (string)@$_POST['q'])], JSON_UNESCAPED_UNICODE));
			if ($a == 'goods_link') exit(json_encode(pkind::goods_link($cmp, $pid, (int)@$_POST['raw'], $force), JSON_UNESCAPED_UNICODE));
			$n = trim(strip_tags((string)@$_POST['name'])); if ($n === '') exit(json_encode(['error' => 'name']));
			@set_time_limit(120);
			exit(json_encode(pkind::goods_new($cmp, $pid, $n, (string)@$_POST['unit'], $force), JSON_UNESCAPED_UNICODE));
		} catch (Throwable $e) { error_log('cost2 goods: '.$e->getMessage()); exit(json_encode(['error' => 'error'])); }
	}

	// ── Қайталанған шикізат (products/cost2/rawdup.php): dup_list — топтар, dup_merge — бір топты біріктіру (транзакция)
	if ($a == 'dup_list' || $a == 'dup_merge' || $a == 'type_group') {
		header('Content-Type: application/json; charset=utf-8');
		try {
			include_once __DIR__.'/../tpl/lib.php'; require_once __DIR__.'/rawdup.php';
			require_once __DIR__.'/rawtype.php';
			if ($a == 'dup_list') exit(json_encode(['groups' => c2raw::groups($cmp), 'types' => c2type::candidates($cmp)], JSON_UNESCAPED_UNICODE));
			if ($a == 'type_group') { $ids = array_filter(array_map('intval', (array)json_decode((string)@$_POST['ids'], true))); $n = c2type::group($cmp, (string)@$_POST['first'], $ids ?: null); if ($n) cost::recalc($cmp); exit(json_encode($n ? ['ok' => 1, 'n' => $n] : ['error' => 'error'])); }
			$n = c2raw::merge($cmp, (string)@$_POST['key']);
			if ($n) cost::recalc($cmp);
			exit(json_encode($n ? ['ok' => 1, 'n' => $n] : ['error' => 'error']));
		} catch (Throwable $e) { error_log('cost2 dup: '.$e->getMessage()); exit(json_encode(['error' => 'error'])); }
	}

	// ── ЖИ-мен жалғастыру (products/cost2/cont.php): cont_list — қадамдар ([k], [id] сүзгісі), cont_do — бір қадам. Жауап JSON
	if ($a == 'cont_list' || $a == 'cont_do') {
		@set_time_limit(300);
		header('Content-Type: application/json; charset=utf-8');
		try {
			require_once __DIR__.'/cont.php';
			$k = (string)@$_POST['k']; $id = (int)@$_POST['id'];
			if ($a == 'cont_list') {
				$st = c2cont::steps($cmp);
				if ($k !== '') $st = array_values(array_filter($st, function ($x) use ($k, $id) { return $x['k'] == $k && (!$id || $x['id'] == $id || in_array($k, ['kind', 'link', 'price'])); }));
				exit(json_encode(['steps' => $st, 'ai' => claude::ready() ? 1 : 0], JSON_UNESCAPED_UNICODE));
			}
			if (!in_array($k, c2cont::$order, true) || $k == 'set') exit(json_encode(['error' => 'error']));
			if (!in_array($k, ['link', 'flat', 'goods']) && !claude::ready()) exit(json_encode(['error' => gtpl_ai::err()], JSON_UNESCAPED_UNICODE));
			exit(json_encode(c2cont::run($cmp, $k, $id), JSON_UNESCAPED_UNICODE));
		} catch (Throwable $e) { error_log('cost2 cont: '.$e->getMessage()); exit(json_encode(['error' => 'error'])); }
	}

	// ── Шикізатқа жайылған дайындаманы жинау (products/cost2/semi_fix.php): компанияның тағамдары + ЖИ шаблондары (arh = 2)
	if ($a == 'semi_fix') {
		@set_time_limit(120);
		header('Content-Type: application/json; charset=utf-8');
		try {
			require_once __DIR__.'/semi_fix.php';
			$n = 0; $names = [];
			foreach (c2semi::candidates($cmp) as $pid => $d) if (c2semi::fix_dish($cmp, $pid)) { $n++; $names[] = $d['name']; }
			$t = c2semi::fix_tpls();
			cost::recalc($cmp);
			exit(json_encode(['n' => $n, 'tpls' => $t, 'names' => $names], JSON_UNESCAPED_UNICODE));
		} catch (Throwable $e) { error_log('cost2 semi_fix: '.$e->getMessage()); exit(json_encode(['error' => 'error'])); }
	}

	// ── Факт мәндері (қолмен, cost_setting_fact): сақтау және соңғы толық айдың нақты деректері (сақтамайды, JSON)
	if ($a == 'fact_setting' || $a == 'fact_auto') {
		include_once __DIR__.'/fact.php';
		if ($a == 'fact_auto') { header('Content-Type: application/json; charset=utf-8'); exit(json_encode(c2fact::auto($cmp), JSON_UNESCAPED_UNICODE)); }
		exit(c2fact::save($cmp, $_POST) ? 'yes' : 'error');
	}

	// ── ЖИ: сеттегі жоқ / граммовкасыз / бағасыз заттарды толтыру (products/cost2/ai.php). Жауап JSON
	if ($a == 'ai_set' || $a == 'ai_sets') {
		@set_time_limit(300);
		header('Content-Type: application/json; charset=utf-8');
		try {
			include_once __DIR__.'/ai.php';
			if ($a == 'ai_sets') exit(json_encode(['ids' => c2ai::sets($cmp)]));
			if (!claude::ready()) exit(json_encode(['error' => gtpl_ai::err()], JSON_UNESCAPED_UNICODE));
			exit(json_encode(c2ai::fill($cmp, (int)@$_POST['id']), JSON_UNESCAPED_UNICODE));
		} catch (Throwable $e) { error_log('cost2 ai: '.$e->getMessage()); exit(json_encode(['error' => 'error'])); }
	}

	// ── Шикізат түрі (products/cost2/rawtype.php): атауы, қолдану аймағы, баға режимі (last | avg), таратып жіберу
	if ($a == 'rtype') {
		require_once __DIR__.'/rawtype.php';
		$done(c2type::ready() && c2type::set($cmp, (int)@$_POST['id'], $P('f'), $P('v')));
	}

	// ── Шикізат
	if ($a == 'raw') {
		$r = $raw_of(@$_POST['id']); if (!$r) exit('error');
		$f = $P('f'); $v = $P('v');
		if ($f == 'name') { if ($v === '') exit('name'); $v = $esc(strip_tags($v)); $done(db::query("update product_raw set name_kz = if(name_kz is null or name_kz = '' or name_kz = name_ru, '$v', name_kz), name_ru = '$v' where id = '{$r['id']}'")); }
		if ($f == 'unit') $done(db::query("update product_raw set unit = '".$unit($v)."' where id = '{$r['id']}'"));
		if ($f == 'price') $done(db::query("update product_raw set price = '".max(0, $num($v))."' where id = '{$r['id']}'"));
		exit('error');
	}
	if ($a == 'raw_add') {
		$n = strip_tags($P('name')); if ($n === '') exit('name');
		$en = $esc($n);
		if ($one("select id from product_raw where company_id = '$cmp' and (name_ru = '$en' or name_kz = '$en') limit 1")) exit('exists');
		$done(db::query("insert into product_raw (company_id, name_kz, name_ru, unit, price) values ('$cmp', '$en', '$en', '".$unit($P('unit'))."', '".max(0, $num($P('price')))."')"));
	}

	// ── Дайындама (атауы, шығымы) және оның құрамы
	if ($a == 'prep') {
		$c = $comp_of(@$_POST['id']); if (!$c) exit('error');
		$f = $P('f'); $v = $P('v');
		if ($f == 'name') {
			if ($v === '') exit('name'); $v = $esc(strip_tags($v));
			if ($one("select id from product_comp where company_id = '$cmp' and (name_ru = '$v' or name_kz = '$v') and id <> '{$c['id']}' limit 1")) exit('exists');
			$done(db::query("update product_comp set name_kz = if(name_kz is null or name_kz = '' or name_kz = name_ru, '$v', name_kz), name_ru = '$v', ubd_dt = NOW() where id = '{$c['id']}'"));
		}
		if ($f == 'yield') { $y = $num($v); if ($y <= 0) exit('yield'); $done(db::query("update product_comp set yield_qty = '$y', ubd_dt = NOW() where id = '{$c['id']}'")); }
		if ($f == 'unit') $done(db::query("update product_comp set unit = '".$unit($v)."', ubd_dt = NOW() where id = '{$c['id']}'"));
		exit('error');
	}
	if ($a == 'prep_add') {
		$n = strip_tags($P('name')); if ($n === '') exit('name');
		$y = $num($P('yield')); if ($y <= 0) exit('yield');
		$en = $esc($n);
		if ($one("select id from product_comp where company_id = '$cmp' and (name_ru = '$en' or name_kz = '$en') limit 1")) exit('exists');
		$ok = db::query("insert into product_comp (company_id, name_kz, name_ru, unit, yield_qty, kind, price) values ('$cmp', '$en', '$en', '".$unit($P('unit'))."', '$y', 'semi', 0)");
		exit($ok ? 'id:'.(int)mysqli_insert_id(db::$connect) : 'error');
	}
	// құрамның қолмен жазылған бағасы (рецепті де, шикізаты да жоқ болса)
	if ($a == 'comp_price') {
		$c = $comp_of(@$_POST['id']); if (!$c) exit('error');
		if (!empty($c['raw_id']) && $raw_of($c['raw_id'])) $done(db::query("update product_raw set price = '".max(0, $num($P('v')))."' where id = '".(int)$c['raw_id']."' and company_id = '$cmp'"));
		$done(db::query("update product_comp set price = '".max(0, $num($P('v')))."', ubd_dt = NOW() where id = '{$c['id']}'"));
	}
	// дайындама құрамындағы зат: кг/л граммен (мл) келеді
	$part_unit = function ($p) use ($raw_of, $comp_of) { $x = $p['raw_id'] ? $raw_of($p['raw_id']) : $comp_of($p['sub_comp_id']); return $x ? (string)$x['unit'] : ''; };
	if ($a == 'part') {
		$p = $one("select p.* from product_comp_part p join product_comp c on c.id = p.comp_id where p.id = '".(int)@$_POST['id']."' and c.company_id = '$cmp'"); if (!$p) exit('error');
		$q = $num($P('v')); if ($q <= 0) exit('qty');
		if ($isw($part_unit($p))) $q = $q / 1000;
		$done(db::query("update product_comp_part set quantity = '$q' where id = '{$p['id']}'"));
	}
	if ($a == 'part_add') {
		$c = $comp_of(@$_POST['to']); if (!$c) exit('error');
		list($t, $id) = $code($P('item'));
		$q = $num($P('q')); if ($q <= 0) exit('qty');
		if ($t == 'r' && ($x = $raw_of($id))) $col = 'raw_id';
		elseif ($t == 'c' && ($x = $comp_of($id))) { if ($id == $c['id']) exit('self'); $col = 'sub_comp_id'; }
		else exit('item');
		if ($isw($x['unit'])) $q = $q / 1000;
		db::query("update product_comp set kind = if(kind is null or kind = 'raw', 'semi', kind), ubd_dt = NOW() where id = '{$c['id']}'");
		$done(db::query("insert into product_comp_part (comp_id, $col, quantity) values ('{$c['id']}', '$id', '$q')"));
	}
	if ($a == 'part_del') {
		$p = $one("select p.id from product_comp_part p join product_comp c on c.id = p.comp_id where p.id = '".(int)@$_POST['id']."' and c.company_id = '$cmp'"); if (!$p) exit('error');
		$done(db::query("delete from product_comp_part where id = '{$p['id']}'"));
	}

	// ── Тағам граммовкасы (product_item: кг/л заттар граммен сақталады)
	if ($a == 'pi') {
		$p = $one("select pi.id from product_item pi join product p on p.id = pi.product_id where pi.id = '".(int)@$_POST['id']."' and p.company_id = '$cmp'"); if (!$p) exit('error');
		$q = $num($P('v')); if ($q <= 0) exit('qty');
		$done(db::query("update product_item set quantity = '$q', ubd_dt = NOW() where id = '{$p['id']}'"));
	}
	if ($a == 'pi_add') {
		if (!$prd_of(@$_POST['to'])) exit('error');
		$pid = (int)$_POST['to'];
		list($t, $id) = $code($P('item'));
		$q = $num($P('q')); if ($q <= 0) exit('qty');
		if ($t == 'r') $id = $comp_for_raw($id); elseif ($t != 'c' || !$comp_of($id)) exit('item');
		if (!$id) exit('item');
		$done(db::query("insert into product_item (product_id, comp_id, quantity) values ('$pid', '$id', '$q')"));
	}
	if ($a == 'pi_del') {
		$p = $one("select pi.id from product_item pi join product p on p.id = pi.product_id where pi.id = '".(int)@$_POST['id']."' and p.company_id = '$cmp' and pi.comp_id > 0"); if (!$p) exit('error');
		$done(db::query("delete from product_item where id = '{$p['id']}'"));
	}

	// ── Сет құрамы (product_set_item: дана)
	if ($a == 'si') {
		$p = $one("select si.id from product_set_item si join product s on s.id = si.set_id where si.id = '".(int)@$_POST['id']."' and s.company_id = '$cmp'"); if (!$p) exit('error');
		$q = $num($P('v')); if ($q <= 0) exit('qty');
		$done(db::query("update product_set_item set quantity = '$q' where id = '{$p['id']}'"));
	}
	if ($a == 'si_add') {
		if (!$prd_of(@$_POST['to'])) exit('error');
		$sid = (int)$_POST['to'];
		list($t, $id) = $code($P('item'));
		$q = $num($P('q')); if ($q <= 0) exit('qty');
		if ($t == 'd') { if (!$prd_of($id) || $id == $sid) exit('item'); $done(db::query("insert into product_set_item (set_id, product_id, quantity) values ('$sid', '$id', '$q')")); }
		if ($t == 'r') $id = $comp_for_raw($id); elseif ($t != 'c' || !$comp_of($id)) exit('item');
		if (!$id) exit('item');
		$done(db::query("insert into product_set_item (set_id, comp_id, quantity) values ('$sid', '$id', '$q')"));
	}
	if ($a == 'si_del') {
		$p = $one("select si.id from product_set_item si join product s on s.id = si.set_id where si.id = '".(int)@$_POST['id']."' and s.company_id = '$cmp'"); if (!$p) exit('error');
		$done(db::query("delete from product_set_item where id = '{$p['id']}'"));
	}

	exit('error');
