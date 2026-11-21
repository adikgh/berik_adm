<?
// Шикізат атауының қалыпты кілті және қайталанғандарды біріктіру.
// Ереже (пайдаланушы): көлем / салмақ / саны әртүрлі болса да БІР шикізат («Кола», «Кола 1 л», «Кола 2л», «Кока-кола», «Coca-Cola» → «Кола»).
// key(): кіші әріп, ё → е, көлем/салмақ/дана (1 л, 0.5л, 500 мл, 1кг, 200 г, 12 шт, х12) мен орама сөздері алынады, синонимдер (Coca-Cola = Кока-кола = Кола)
// бір сөзге келтіріледі, сөздер ретке қойылады. Қолданылады: gtpl::raw_id / comp_id (ЖИ, шаблон, импорт), wb::raw_find_or_create (накладной),
// Себес «Қайталанған шикізатты біріктіру» (get.php?a=dup_list / dup_merge).
class c2raw {
	public static function syn() {
		return [
			'кола' => ['coca cola', 'coca-cola', 'кока кола', 'кока-кола', 'кокакола', 'cocacola', 'cola', 'кола'],
			'пепси' => ['pepsi cola', 'pepsi', 'пепси кола', 'пепси'], 'спрайт' => ['sprite'], 'фанта' => ['fanta'],
			'бонаква' => ['bonaqua', 'бон аква'], 'фьюзти' => ['fuse tea', 'fusetea', 'фьюз ти', 'фьюз-ти'], 'липтон' => ['lipton'],
			'миринда' => ['mirinda'], 'севенап' => ['7up', '7 up', 'seven up', 'севен ап'], 'редбулл' => ['red bull', 'redbull', 'ред булл'],
		];
	}
	public static function disp() { return ['кола' => 'Кола', 'пепси' => 'Пепси', 'спрайт' => 'Спрайт', 'фанта' => 'Фанта', 'бонаква' => 'Бонаква', 'фьюзти' => 'Фьюз ти', 'липтон' => 'Липтон', 'миринда' => 'Миринда', 'севенап' => '7Up', 'редбулл' => 'Red Bull']; }

	// көлем / салмақ / дана бөлігі алынған атау (бас әрпі сақталады): «Кола 1 л» → «Кола»
	public static function strip($name) {
		$s = html_entity_decode(strip_tags((string)$name), ENT_QUOTES, 'UTF-8');
		$s = preg_replace('/(?<![\p{L}])[xх×]?\s*\d+([.,]\d+)?\s*(литр\p{L}*|lt|л|l|мл|ml|кг|kg|грамм\p{L}*|гр|г|g|штук\p{L}*|шт|pcs)?(?![\p{L}])/iu', ' ', $s);
		$s = preg_replace('/(?<![\p{L}])(бутылка|бут|банка|ж\/б|пэт|пет|пачка|упаковка|упак|уп|коробка|мешок)(?![\p{L}])\.?/iu', ' ', $s);
		return trim(preg_replace('/\s+/u', ' ', preg_replace('/[«»"()\[\],;]+|\s[-–]\s|\s[-–]$/u', ' ', $s)), " -–.");
	}
	public static function key($name) {
		$s = mb_strtolower(self::strip($name), 'UTF-8');
		$s = str_replace(['ё', '–', '—', '_', '/'], ['е', ' ', ' ', ' ', ' '], $s);
		$s = ' '.preg_replace('/\s+/u', ' ', trim($s)).' ';
		foreach (self::syn() as $k => $vs) { usort($vs, function ($a, $b) { return mb_strlen($b) - mb_strlen($a); }); foreach ($vs as $v) $s = str_replace(' '.$v.' ', ' '.$k.' ', $s); }
		$w = array_values(array_filter(preg_split('/[^\p{L}\d]+/u', $s), 'strlen'));
		sort($w, SORT_STRING);
		return implode(' ', $w);
	}
	// компанияның шикізатынан кілті бірдей бары: сатып алынғаны > бағасы бары > ескісі
	public static function find($cmp, $name) {
		$k = self::key($name); if ($k === '') return 0;
		$q = db::query("select r.id, r.name_ru, r.name_kz, r.price, exists (select 1 from waybill_item i where i.raw_id = r.id) b from product_raw r where r.company_id = '".(int)$cmp."' order by b desc, (r.price > 0) desc, r.id");
		while ($q && $r = mysqli_fetch_assoc($q)) if (self::key($r['name_ru'] ?: $r['name_kz']) === $k) return (int)$r['id'];
		return 0;
	}
	// құрам (рецептсіз шикізат-құрам) кілті бірдей бары
	public static function find_comp($cmp, $name) {
		$k = self::key($name); if ($k === '') return 0;
		$q = db::query("select c.id, c.name_ru, c.name_kz from product_comp c where c.company_id = '".(int)$cmp."' and (c.kind is null or c.kind = 'raw')
			and not exists (select 1 from product_comp_part p where p.comp_id = c.id) order by (c.raw_id > 0) desc, c.id");
		while ($q && $r = mysqli_fetch_assoc($q)) if (self::key($r['name_ru'] ?: $r['name_kz']) === $k) return (int)$r['id'];
		return 0;
	}

	// Қайталанған топтар: [[key, name (жаңа атауы), main, items => [[id, name, unit, price, bought, uses]]]]
	public static function groups($cmp) {
		$cmp = (int)$cmp; $by = [];
		$q = db::query("select r.id, r.name_ru, r.name_kz, r.unit, r.price, exists (select 1 from waybill_item i where i.raw_id = r.id) b,
			(select count(*) from product_comp c where c.raw_id = r.id) + (select count(*) from product_comp_part p where p.raw_id = r.id) u
			from product_raw r where r.company_id = '$cmp' order by r.id");
		while ($q && $r = mysqli_fetch_assoc($q)) { $k = self::key($r['name_ru'] ?: $r['name_kz']); if ($k !== '') $by[$k][] = ['id' => (int)$r['id'], 'name' => trim($r['name_ru'] ?: $r['name_kz']), 'unit' => (string)$r['unit'], 'price' => (float)$r['price'], 'bought' => (int)$r['b'], 'uses' => (int)$r['u']]; }
		$out = [];
		foreach ($by as $k => $it) {
			if (count($it) < 2) continue;
			usort($it, function ($a, $b) { return [$b['bought'], $b['price'] > 0, $a['id']] <=> [$a['bought'], $a['price'] > 0, $b['id']]; }); // сатып алынғаны > бағасы бары > ескісі
			$d = self::disp(); $nm = $d[$k] ?? self::strip($it[0]['name']);
			$out[] = ['key' => $k, 'name' => $nm !== '' ? $nm : $it[0]['name'], 'main' => $it[0]['id'], 'items' => $it];
		}
		usort($out, function ($a, $b) { return count($b['items']) - count($a['items']); });
		return $out;
	}

	// Бір топты біріктіру (транзакция): барлық raw_id сілтемесі негізгіге көшеді, қайталанғандар өшеді.
	// Негізгінің өлшемі қалады; бағасы — накладнойдан болса сол, болмаса өзінікі, ол 0 болса — басқасының соңғы бағасы.
	public static function merge($cmp, $key) {
		$cmp = (int)$cmp; $g = null;
		foreach (self::groups($cmp) as $x) if ($x['key'] === $key) { $g = $x; break; }
		if (!$g) return 0;
		$main = (int)$g['main']; $dup = array_values(array_filter(array_column($g['items'], 'id'), function ($i) use ($main) { return $i != $main; }));
		if (!$dup) return 0;
		$ids = implode(',', array_map('intval', $dup));
		$has = function ($t) { $q = db::query("show tables like '$t'"); return $q && mysqli_num_rows($q) > 0; };
		mysqli_begin_transaction(db::$connect);
		$ok = true;
		$q = function ($sql) use (&$ok) { if ($ok && !db::query($sql)) { $ok = false; error_log('c2raw merge: '.mysqli_error(db::$connect)); } };
		$q("update product_comp set raw_id = '$main' where raw_id in ($ids)");
		$q("update product_comp_part set raw_id = '$main' where raw_id in ($ids)");
		$q("update waybill_item set raw_id = '$main' where raw_id in ($ids)");
		if ($has('supplier_item')) $q("update supplier_item set raw_id = '$main' where raw_id in ($ids)");
		// қалдық: филиал бойынша қосылады
		if ($has('product_raw_stock')) {
			$q("insert into product_raw_stock (company_id, branch_id, raw_id, quantity, upd_dt) select company_id, branch_id, '$main', SUM(quantity), NOW() from product_raw_stock where raw_id in ($ids) group by company_id, branch_id
				on duplicate key update quantity = quantity + values(quantity), upd_dt = values(upd_dt)");
			$q("delete from product_raw_stock where raw_id in ($ids)");
		}
		// қозғалыс журналы (бір тапсырыстың бір шикізаты бір жол — бірдей болса саны қосылады)
		if ($has('product_raw_move')) {
			$q("update ignore product_raw_move set raw_id = '$main' where raw_id in ($ids)");
			$q("update product_raw_move m join product_raw_move d on d.type = m.type and d.order_id = m.order_id and d.raw_id in ($ids) set m.qty = m.qty + d.qty where m.raw_id = '$main'");
			$q("delete from product_raw_move where raw_id in ($ids)");
		}
		if ($has('revision_item')) { $q("update ignore revision_item set raw_id = '$main' where raw_id in ($ids)"); $q("delete from revision_item where raw_id in ($ids)"); }
		if ($has('cost2_ai')) { $q("update ignore cost2_ai set ref_id = '$main' where kind = 'r' and ref_id in ($ids)"); $q("delete from cost2_ai where kind = 'r' and ref_id in ($ids)"); }
		// атауы — көлемсіз қысқа ат; бағасы 0 болса — басқасының соңғы бағасы
		$nm = gtpl::e($g['name']);
		$q("update product_raw set name_ru = '$nm', name_kz = '$nm', price = if(price > 0, price, (select p from (select price p from product_raw where id in ($ids) and price > 0 order by ubd_dt desc, id desc limit 1) x)), ubd_dt = NOW() where id = '$main' and company_id = '$cmp'");
		$q("delete from product_raw where id in ($ids) and company_id = '$cmp'");
		if ($ok) mysqli_commit(db::$connect); else mysqli_rollback(db::$connect);
		return $ok ? count($dup) : 0;
	}
}
