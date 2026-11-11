<?php include "../config/core.php";
	// Граммовка шаблондарын сақтау/өшіру (JSON). Логика adm.berik.c/products/tpl/get.php-дан көшірілді.
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); };
	if (!$user_id || !dev::can('tpl')) $out(['error' => 'right']);
	if (!dev_post_ok()) $out(['error' => t::w('Refresh the page', 'Бетті жаңартыңыз')]);

	// Шаблонды сақтау. $arh: null = өзгертпеу (жаңасы 0), 0 = бекітілген, 2 = ЖИ ұсынысы (компанияларға әлі көрінбейді)
	function tpl_store($id, $name, $cat, $keys, array $items, $arh = null, $note = null) {
		$e = function ($s) { return mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$s))); };
		$id = (int)$id; $name = $e($name); $cat = $e($cat); $keys = $e($keys);
		if ($name === '') return 0;
		$set = $arh === null ? '' : ", arh = '" . (int)$arh . "'";
		if ($note !== null && tpl_note_col()) $set .= ", ai_note = '" . $e(mb_substr($note, 0, 500)) . "'";
		if ($id) db::query("update grammovka_tpl set name_ru = '$name', cat = '$cat', keys_ru = '$keys', upd_dt = NOW()$set where id = '$id'");
		else {
			$n = (int)@mysqli_fetch_row(db::query("select max(number) from grammovka_tpl"))[0] + 1;
			db::query("insert into grammovka_tpl set name_ru = '$name', cat = '$cat', keys_ru = '$keys', number = '$n'" . ($arh === null ? '' : $set));
			$id = (int)mysqli_insert_id(db::$connect);
		}
		if (!$id) return 0;
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
		return $id;
	}
	// ЖИ ұсынысының түсініктемесі үшін баған (бір рет қосылады, бар деректерге тимейді)
	function tpl_note_col() {
		static $ok = null;
		if ($ok === null) {
			$ok = mysqli_num_rows(db::query("show columns from grammovka_tpl like 'ai_note'")) > 0;
			if (!$ok) $ok = (bool)@db::query("alter table grammovka_tpl add ai_note varchar(500) null");
		}
		return $ok;
	}

	if (isset($_GET['tpl_save'])) {
		$items = json_decode((string)@$_POST['items'], true);
		if (trim((string)@$_POST['name']) === '') $out(['error' => t::w('Enter the title', 'Атауын жазыңыз')]);
		if (!is_array($items)) $out(['error' => 'items']);
		// ұсынысты бекіту: approve=1 → arh 0 (компанияларға көрінеді)
		$id = tpl_store(@$_POST['id'], @$_POST['name'], @$_POST['cat'], @$_POST['keys'], $items, isset($_POST['approve']) ? 0 : null);
		$out($id ? ['result' => 'yes', 'id' => $id] : ['error' => 'error']);
	}
	if (isset($_GET['ai_suggest'])) {
		@set_time_limit(320);
		require __DIR__ . '/ai.php';
		$names = json_decode((string)@$_POST['dishes'], true);
		if (!is_array($names) || !$names) $out(['error' => t::w('Pick dishes', 'Тағам таңдаңыз')]);
		$miss = []; foreach (tpl_ai::missing() as $m) $miss[gtpl::norm($m['name'])] = $m;
		$dishes = [];
		foreach (array_slice($names, 0, 3) as $n) if (isset($miss[$k = gtpl::norm($n)])) $dishes[] = ['name' => $miss[$k]['name'], 'cat' => $miss[$k]['cat']];
		if (!$dishes) $out(['error' => t::w('These dishes already have templates', 'Бұл тағамдардың шаблоны бар')]);
		$cats = []; $q = db::query("select distinct cat from grammovka_tpl where arh = 0 and cat <> ''"); while ($r = mysqli_fetch_row($q)) $cats[] = $r[0];
		$res = tpl_ai::suggest($dishes, $cats);
		if ($res === null) $out(['error' => claude::$err]);
		$saved = [];
		foreach ($res as $t) {
			// мәзірдегі атауы іздеу сөзіне қосылады: сол тағам шаблонды бірден табады
			$keys = array_filter(array_map('trim', explode('|', $t['keys'])));
			if ($t['dish'] !== '' && !in_array(gtpl::norm($t['dish']), array_map(['gtpl', 'norm'], $keys))) $keys[] = $t['dish'];
			$id = tpl_store(0, $t['name'], $t['cat'], implode('|', $keys), $t['items'], 2, $t['note']);
			if ($id) $saved[] = $id;
		}
		$out(['result' => 'yes', 'n' => count($saved), 'usd' => round(tpl_ai::$usd, 3)]);
	}
	if (isset($_GET['tpl_del'])) {
		$id = (int)@$_POST['id'];
		$out(['result' => db::query("update grammovka_tpl set arh = 1, upd_dt = NOW() where id = '$id'") ? 'yes' : 'error']);
	}
	$out(['error' => 'unknown']);
