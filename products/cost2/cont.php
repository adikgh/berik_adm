<?
// Себес: «ЖИ-мен жалғастыру». Тізбек (сет → құрам → позиция → дайындама → шикізат + баға) үзілген жерлерді табады да,
// сол жерден ғана толтырады: барын өзгертпейді, қолмен өзгертілген (ubd_dt) жолдарға тимейді. Әр қадам орындалар алдында шартты
// қайта тексереді (идемпотент): ортада тоқтаса, қайта басқанда қалғаны ғана жасалады.
// Түрлері (төменнен жоғары): link — шикізатқа байлау (ЖИ-сіз), price — бағасыз шикізат (бәрі бір ЖИ сұрауымен), semi — рецепті жоқ дайындама,
// flat — шикізатқа жайылған дайындама (ЖИ-сіз, semi_fix), dish — граммовкасы жоқ позиция, item — сеттегі позиция емес зат (c2ai, бір сет — бір сұрау),
// set — құрамы жоқ сет (products/item/set_ai.php фотодан/сипаттамадан, сосын item). ЖИ шығыны company_ai_log-та feature = cost2_cont.
require_once __DIR__.'/ai.php';
require_once __DIR__.'/semi_fix.php';

class c2cont {
	public static $order = ['link', 'price', 'semi', 'flat', 'dish', 'item', 'set'];

	// [түрі => [[id, name], ...]]
	public static function breaks($cmp) {
		$cmp = (int)$cmp; $b = array_fill_keys(self::$order, []);
		$pc = gtpl::col('product_comp', 'price_src');
		// 6) закупқа байланбаған шикізат-құрам (рецептсіз, kind = raw)
		$q = db::query("select id, name_ru from product_comp c where company_id = '$cmp' and kind = 'raw' and (raw_id is null or raw_id = 0)
			and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by id");
		while ($q && $r = mysqli_fetch_row($q)) $b['link'][] = [(int)$r[0], $r[1]];
		// 5) бағасыз шикізат (қолданылатыны: тағамда, дайындамада не сетте)
		$q = db::query("select c.id, c.name_ru from product_comp c left join product_raw r on r.id = c.raw_id where c.company_id = '$cmp'
			and (c.kind is null or c.kind = 'raw') and not exists (select 1 from product_comp_part p where p.comp_id = c.id)
			and (if(c.raw_id > 0, r.price, c.price) is null or if(c.raw_id > 0, r.price, c.price) = 0)
			and (exists (select 1 from product_item i where i.comp_id = c.id) or exists (select 1 from product_comp_part x where x.sub_comp_id = c.id)
				or exists (select 1 from product_comp_part x where c.raw_id > 0 and x.raw_id = c.raw_id) or exists (select 1 from product_set_item s where s.comp_id = c.id)) order by c.id");
		while ($q && $r = mysqli_fetch_row($q)) $b['price'][] = [(int)$r[0], $r[1]];
		// 4) рецепті жоқ дайындама
		$q = db::query("select id, name_ru from product_comp c where company_id = '$cmp' and kind = 'semi' and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by id");
		while ($q && $r = mysqli_fetch_row($q)) $b['semi'][] = [(int)$r[0], $r[1]];
		// 3) шикізатқа жайылған / граммовкасы жоқ позиция
		foreach (c2semi::candidates($cmp) as $pid => $d) $b['flat'][] = [$pid, $d['name']];
		foreach (gtpl::empty_dishes($cmp) as $d) $b['dish'][] = [(int)$d['id'], trim(strip_tags($d['name_ru'] ?: $d['name_kz']))];
		// 2) сетте позиция емес (бос) зат не граммовкасыз позиция
		$comps = cost::comps($cmp);
		$q = db::query("select distinct s.id, s.name_ru from product s join product_set_item si on si.set_id = s.id where s.company_id = '$cmp' order by s.id");
		while ($q && $r = mysqli_fetch_row($q)) foreach (c2ai::missing($cmp, $r[0], $comps) as $m) if ($m['t'] != 'price') { $b['item'][] = [(int)$r[0], trim(strip_tags($r[1]))]; break; }
		// 1) құрамы мүлде жоқ сет
		$q = db::query("select p.id, p.name_ru from product p where p.company_id = '$cmp' and (p.arh is null or p.arh = 0)".(gtpl::col('product', 'is_set') ? " and p.is_set = 1" : " and 1 = 0")."
			and not exists (select 1 from product_set_item si where si.set_id = p.id) order by p.id");
		while ($q && $r = mysqli_fetch_row($q)) $b['set'][] = [(int)$r[0], trim(strip_tags($r[1]))];
		return $b;
	}
	// ортақ батырманың қадамдары: link, price — бәрі бір қадам; қалғаны — әр зат бөлек
	public static function steps($cmp) {
		$s = [];
		foreach (self::breaks($cmp) as $k => $list) {
			if (!$list) continue;
			if (in_array($k, ['link', 'price'])) $s[] = ['k' => $k, 'id' => 0, 'n' => count($list), 'name' => implode(', ', array_slice(array_column($list, 1), 0, 5))];
			else foreach ($list as $x) $s[] = ['k' => $k, 'id' => $x[0], 'n' => 1, 'name' => $x[1]];
		}
		return $s;
	}

	// Бір қадам. Қайтарады ['ok' => 1, 'n' => толтырылғаны] не ['error' => ...]
	public static function run($cmp, $k, $id) {
		$cmp = (int)$cmp; $id = (int)$id; $n = 0;
		$log0 = (int)@mysqli_fetch_row(db::query("select ifnull(max(id), 0) from company_ai_log"))[0];
		$br = self::breaks($cmp);
		$has = function ($id) use ($br, $k) { foreach ($br[$k] ?? [] as $x) if ($x[0] == $id) return $x; return null; };
		$err = '';
		if ($k == 'link') { foreach ($br['link'] as $x) if ((!$id || $x[0] == $id) && gtpl::link_raw($x[0])) $n++; }
		elseif ($k == 'price') {
			$ids = array_column($br['price'], 0); if ($id) $ids = array_values(array_intersect($ids, [$id]));
			// закупқа байланған, құрамда бағасы бар, шикізатта жоқ: ЖИ-сіз көшіріледі
			foreach ($ids as $c) db::query("update product_raw r join product_comp c on c.raw_id = r.id set r.price = c.price where c.id = '$c' and c.price > 0 and (r.price is null or r.price = 0)");
			if ($ids) { $r = gtpl_ai::prices($cmp, $ids); $n = (int)$r['n']; if (!empty($r['error'])) $err = $r['error']; }
		} elseif ($k == 'semi' && ($x = $has($id))) {
			$tid = gtpl::semi_find($x[1]);
			if (!$tid) { $t = gtpl_ai::recipe($x[1], 'semi'); $tid = $t ? $t['id'] : 0; if (!$t) $err = gtpl_ai::err(); }
			if ($tid && ($t = gtpl::tpl($tid))) {
				$y = (float)$t['yield_qty'] > 0 ? (float)$t['yield_qty'] : 1; $u = gtpl::unit($t['unit'] ?: 'кг'); $cr = 0;
				db::query("update product_comp set unit = '$u', yield_qty = '$y', ubd_dt = NOW() where id = '$id' and company_id = '$cmp'");
				if (gtpl::semi_parts($cmp, $id, $tid, 2, $cr)) $n = 1;
			}
		} elseif ($k == 'flat' && $has($id)) $n = c2semi::fix_dish($cmp, $id) ? 1 : 0;
		elseif ($k == 'dish' && ($x = $has($id))) {
			$tid = c2ai::tpl_exact($x[1]);
			if (!$tid) {
				$cat = (string)@mysqli_fetch_row(db::query("select c.name_ru from product p left join product_catalog c on c.id = p.catalog_id where p.id = '$id'"))[0];
				$t = gtpl_ai::recipe($x[1], 'dish', $cat); $tid = $t ? $t['id'] : 0; if (!$t) $err = gtpl_ai::err();
			}
			$cr = 0;
			if ($tid && gtpl::apply($cmp, $id, $tid, 2, false, $cr, true) == 'yes') $n = 1;
		} elseif ($k == 'item' && $has($id)) { $r = c2ai::fill($cmp, $id); if (!empty($r['error'])) $err = $r['error']; else $n = (int)@$r['n']; }
		// жаңа ашылған бағасыз шикізат — сол жерде бағаланады
		$np = array_values(array_unique(gtpl::$noprice));
		if ($np && $k != 'price') gtpl_ai::prices($cmp, $np);
		cost::recalc($cmp);
		db::query("update company_ai_log set feature = 'cost2_cont' where id > '$log0' and company_id = '$cmp'");
		$u = c2ai::usage($log0);
		return ($err && !$n ? ['error' => $err] : ['ok' => 1, 'n' => $n]) + $u;
	}
}
