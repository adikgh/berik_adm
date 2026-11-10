<? include "../../config/core.php";
	include_once "lib.php";
	// Граммовка шаблондары: AJAX. Жауап JSON.
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); };
	if (!$user_id || !$company) $out(['error' => 'auth']);
	if (!gtpl::ready()) $out(['error' => 'notable']);
	$cmp = (int)$company;
	$is_dev = (int)@$user_right['positions_id'] === 1; // шаблондарды тек әзірлеуші (Создатель) өзгертеді

	$pack = function ($list) {
		$r = [];
		foreach ($list as $t) { $t['vars'] = gtpl::vars($t['id']); $r[] = $t; }
		return $r;
	};

	// Тағамға сай шаблондар (немесе ?q= бойынша іздеу)
	if (isset($_GET['match'])) {
		$pid = (int)@$_GET['pid'];
		$d = mysqli_fetch_assoc(db::query("select p.name_ru, p.name_kz, c.name_ru cat from product p left join product_catalog c on c.id = p.catalog_id where p.id = '$pid' and p.company_id = '$cmp'"));
		if (!$d) $out(['error' => 'error']);
		$q = trim((string)@$_GET['q']);
		if ($q !== '') {
			$n = gtpl::norm($q); $list = [];
			foreach (gtpl::all() as $t) if (mb_strpos(gtpl::norm($t['name_ru'].' '.$t['cat'].' '.$t['keys_ru']), $n) !== false) $list[] = ['id' => (int)$t['id'], 'name' => $t['name_ru'], 'cat' => $t['cat']];
			$list = array_slice($list, 0, 6);
		} else $list = gtpl::match($d['name_ru'] ?: $d['name_kz'], (string)$d['cat'], 3);
		$out(['tpls' => $pack($list), 'has' => gtpl::has_items($pid), 'names' => gtpl::$var_names]);
	}

	// Бір тағамға қолдану
	if (isset($_GET['apply'])) {
		$cr = 0;
		$r = gtpl::apply($cmp, (int)@$_POST['pid'], (int)@$_POST['tpl'], (int)@$_POST['variant'], !empty($_POST['replace']), $cr);
		$out(['result' => $r, 'created' => $cr]);
	}

	// Себес: барлық бос тағамға (алдымен ?all&dry=1 — алдын ала тізім)
	if (isset($_GET['all'])) {
		$v = (int)@$_POST['variant'];
		if (!isset(gtpl::$var_names[$v])) $out(['error' => 'variant']);
		$out(gtpl::apply_all($cmp, $v, !empty($_POST['dry'])));
	}

	// ---- әзірлеуші: шаблондарды басқару ----
	if (isset($_GET['tpl_save'])) {
		if (!$is_dev) $out(['error' => 'right']);
		$id = (int)@$_POST['id'];
		$e = function ($s) { return mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$s))); };
		$name = $e(@$_POST['name']); $cat = $e(@$_POST['cat']); $keys = $e(@$_POST['keys']);
		if ($name === '') $out(['error' => 'name']);
		$items = json_decode((string)@$_POST['items'], true);
		if (!is_array($items)) $out(['error' => 'items']);
		if ($id) db::query("update grammovka_tpl set name_ru = '$name', cat = '$cat', keys_ru = '$keys', upd_dt = NOW() where id = '$id'");
		else {
			$n = (int)@mysqli_fetch_row(db::query("select max(number) from grammovka_tpl"))[0] + 1;
			db::query("insert into grammovka_tpl (name_ru, cat, keys_ru, number) values ('$name', '$cat', '$keys', '$n')");
			$id = (int)mysqli_insert_id(db::$connect);
		}
		if (!$id) $out(['error' => 'error']);
		db::query("delete from grammovka_tpl_item where tpl_id = '$id'");
		$k = 0;
		foreach ($items as $it) {
			$nm = $e(@$it['name']); $u = in_array(@$it['unit'], ['кг', 'л', 'шт']) ? $it['unit'] : 'кг';
			if ($nm === '') continue;
			$k++;
			foreach ([1, 2, 3] as $v) {
				$qn = round((float)str_replace(',', '.', (string)@$it['q'][$v - 1]), 3);
				if ($qn > 0) db::query("insert into grammovka_tpl_item (tpl_id, variant, name_ru, unit, quantity, number) values ('$id', '$v', '$nm', '$u', '$qn', '$k')");
			}
		}
		$out(['result' => 'yes', 'id' => $id]);
	}
	if (isset($_GET['tpl_del'])) {
		if (!$is_dev) $out(['error' => 'right']);
		$id = (int)@$_POST['id'];
		$out(['result' => db::query("update grammovka_tpl set arh = 1, upd_dt = NOW() where id = '$id'") ? 'yes' : 'error']);
	}
	$out(['error' => 'unknown']);
