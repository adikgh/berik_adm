<?
// Шикізат ТҮРІ: бір зат әртүрлі бренд / накладной нұсқаларымен («Ун» ▸ Цесна 2 кг, Алтын дән 25 кг …), қолдану аймағымен (Тесто, Соус, Сусын …).
// Нұсқалар бөлек product_raw болып қалады (накладной, қалдық өзгермейді), product_raw.type_id түрге байлайды.
// Рецепттегі кез келген нұсқа ТҮР бағасымен есептеледі (cost::comps → c2type::raw_prices): price_mode = last — соңғы накладной (не соңғы жаңартылған) бағасы,
// avg — нұсқалардың бағасы бар орташасы. Жаңа шикізат (ЖИ, шаблон, накладной) алдымен түрді атауы бойынша табады (c2type::find).
class c2type {
	public static function ready() {
		static $ok = null;
		if ($ok !== null) return $ok;
		$t = db::query("show tables like 'company_raw_type'");
		$c = db::query("show columns from product_raw like 'type_id'");
		return $ok = $t && mysqli_num_rows($t) && $c && mysqli_num_rows($c);
	}
	// [type_id => ['id', 'name', 'unit', 'area', 'mode', 'price', 'n']]
	public static function all($cmp) {
		$cmp = (int)$cmp; $out = [];
		if (!self::ready()) return $out;
		$q = db::query("select * from company_raw_type where company_id = '$cmp' and (arh is null or arh = 0) order by name");
		while ($q && $r = mysqli_fetch_assoc($q)) $out[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => $r['name'], 'unit' => (string)$r['unit'], 'area' => (string)$r['area'], 'mode' => $r['price_mode'] ?: 'last', 'price' => 0, 'n' => 0];
		if (!$out) return $out;
		// нұсқалардың бағасы: соңғы накладной күні (болмаса ubd_dt) бойынша
		$q = db::query("select r.id, r.type_id, r.price, (select max(w.doc_date) from waybill_item i join waybill w on w.id = i.waybill_id where i.raw_id = r.id) wd, r.ubd_dt
			from product_raw r where r.company_id = '$cmp' and r.type_id in (".implode(',', array_keys($out)).")");
		$v = [];
		while ($q && $r = mysqli_fetch_assoc($q)) { $v[(int)$r['type_id']][] = $r; $out[(int)$r['type_id']]['n']++; }
		foreach ($out as $id => &$t) {
			$ps = array_values(array_filter($v[$id] ?? [], function ($r) { return (float)$r['price'] > 0; }));
			if (!$ps) continue;
			if ($t['mode'] == 'avg') $t['price'] = round(array_sum(array_column($ps, 'price')) / count($ps), 2);
			else { usort($ps, function ($a, $b) { return [(string)$b['wd'], (string)$b['ubd_dt'], (int)$b['id']] <=> [(string)$a['wd'], (string)$a['ubd_dt'], (int)$a['id']]; }); $t['price'] = (float)$ps[0]['price']; }
		}
		unset($t);
		return $out;
	}
	// cost::comps үшін: [raw_id => түр бағасы] (түрге байланған, түр бағасы бар шикізат)
	public static function raw_prices($cmp) {
		$cmp = (int)$cmp; $out = [];
		$types = self::all($cmp); if (!$types) return $out;
		$q = db::query("select id, type_id from product_raw where company_id = '$cmp' and type_id > 0");
		while ($q && $r = mysqli_fetch_assoc($q)) if (!empty($types[(int)$r['type_id']]['price'])) $out[(int)$r['id']] = $types[(int)$r['type_id']]['price'];
		return $out;
	}
	// атауы бойынша түр: түр кілті (c2raw::key) шикізат кілтінің сөздерінде толық болса; біреу ғана болса
	public static function find($cmp, $name) {
		if (!self::ready()) return 0;
		require_once __DIR__.'/rawdup.php';
		$w = explode(' ', c2raw::key($name)); $hit = [];
		$q = db::query("select id, name from company_raw_type where company_id = '".(int)$cmp."' and (arh is null or arh = 0)");
		while ($q && $r = mysqli_fetch_assoc($q)) { $tw = array_filter(explode(' ', c2raw::key($r['name']))); if ($tw && !array_diff($tw, $w)) $hit[(int)$r['id']] = 1; }
		// түр басқаша аталса («Ун»): нұсқаларының бірінші сөзі бойынша («Мука Цесна» → «Мука …» нұсқалары бар түр)
		if (!$hit) {
			$f = self::first($name);
			$q = db::query("select r.type_id, r.name_ru, r.name_kz from product_raw r join company_raw_type t on t.id = r.type_id and (t.arh is null or t.arh = 0) where r.company_id = '".(int)$cmp."'");
			while ($q && $r = mysqli_fetch_assoc($q)) if ($f !== '' && self::first($r['name_ru'] ?: $r['name_kz']) === $f) $hit[(int)$r['type_id']] = 1;
		}
		return count($hit) == 1 ? (int)key($hit) : 0;
	}
	public static function first($name) {
		require_once __DIR__.'/rawdup.php';
		$f = mb_strtolower(preg_split('/[\s,]+/u', c2raw::strip($name))[0] ?? '', 'UTF-8');
		return mb_strlen($f) >= 3 ? str_replace('ё', 'е', $f) : '';
	}
	// түрдің бір нұсқасы (рецептке қойылатын): сатып алынғаны > бағасы бары > ескісі
	public static function variant($type_id) {
		$r = mysqli_fetch_row(db::query("select r.id from product_raw r where r.type_id = '".(int)$type_id."' order by exists (select 1 from waybill_item i where i.raw_id = r.id) desc, (r.price > 0) desc, r.id limit 1"));
		return $r ? (int)$r[0] : 0;
	}
	// жаңа шикізатты түрге байлау (атауы сәйкес болса)
	public static function attach($cmp, $raw_id, $name) {
		if (!self::ready() || !$raw_id) return 0;
		$t = self::find($cmp, $name);
		if ($t) db::query("update product_raw set type_id = '$t' where id = '".(int)$raw_id."' and company_id = '".(int)$cmp."' and (type_id is null or type_id = 0)");
		return $t;
	}

	// «Бір түрге жинау» ұсыныстары: түрі жоқ шикізат атауының бірінші сөзі (көлемсіз, синониммен) бойынша; кілті бірдейлері бұл жерде емес (олар — біріктіру)
	public static function candidates($cmp) {
		$cmp = (int)$cmp; $by = [];
		if (!self::ready()) return [];
		require_once __DIR__.'/rawdup.php';
		$q = db::query("select r.id, r.name_ru, r.name_kz, r.unit, r.price from product_raw r where r.company_id = '$cmp' and (r.type_id is null or r.type_id = 0) order by r.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$nm = trim($r['name_ru'] ?: $r['name_kz']); $f = self::first($nm);
			if ($f === '') continue;
			$by[$f][] = ['id' => (int)$r['id'], 'name' => $nm, 'unit' => (string)$r['unit'], 'price' => (float)$r['price'], 'key' => c2raw::key($nm)];
		}
		$out = [];
		foreach ($by as $f => $it) {
			if (count(array_unique(array_column($it, 'key'))) < 2) continue; // бәрі бірдей кілт — бұл біріктіру
			$out[] = ['first' => $f, 'name' => mb_strtoupper(mb_substr($f, 0, 1)).mb_substr($f, 1), 'items' => $it];
		}
		usort($out, function ($a, $b) { return count($b['items']) - count($a['items']); });
		return $out;
	}
	// түрге жинау: аты бар түр болса соған, болмаса жаңа түр; нұсқалар өзгермейді
	public static function group($cmp, $first, $ids = null, $area = '') {
		$cmp = (int)$cmp; $g = null;
		foreach (self::candidates($cmp) as $x) if ($x['first'] === $first) { $g = $x; break; }
		if (!$g) return 0;
		$items = $ids ? array_values(array_filter($g['items'], function ($i) use ($ids) { return in_array($i['id'], $ids); })) : $g['items'];
		if (count($items) < 2) return 0;
		$e = function ($s) { return mysqli_real_escape_string(db::$connect, (string)$s); };
		$t = mysqli_fetch_row(db::query("select id from company_raw_type where company_id = '$cmp' and lower(name) = lower('".$e($g['name'])."') limit 1"));
		$tid = $t ? (int)$t[0] : 0;
		if (!$tid) {
			$u = array_count_values(array_filter(array_column($items, 'unit'))); arsort($u);
			if (!db::query("insert into company_raw_type (company_id, name, unit, area, price_mode) values ('$cmp', '".$e($g['name'])."', '".$e(key($u) ?: 'кг')."', '".$e($area)."', 'last')")) return 0;
			$tid = (int)mysqli_insert_id(db::$connect);
		}
		$ids = implode(',', array_map(function ($i) { return (int)$i['id']; }, $items));
		db::query("update product_raw set type_id = '$tid' where company_id = '$cmp' and id in ($ids)");
		return count($items);
	}
	public static function set($cmp, $id, $f, $v) {
		$cmp = (int)$cmp; $id = (int)$id; $e = mysqli_real_escape_string(db::$connect, trim(strip_tags((string)$v)));
		if ($f == 'name' && $e !== '') return db::query("update company_raw_type set name = '$e' where id = '$id' and company_id = '$cmp'");
		if ($f == 'area') return db::query("update company_raw_type set area = '".mb_substr($e, 0, 40)."' where id = '$id' and company_id = '$cmp'");
		if ($f == 'mode') return db::query("update company_raw_type set price_mode = '".($e == 'avg' ? 'avg' : 'last')."' where id = '$id' and company_id = '$cmp'");
		if ($f == 'ungroup') return db::query("update product_raw set type_id = null where type_id = '$id' and company_id = '$cmp'") && db::query("update company_raw_type set arh = 1 where id = '$id' and company_id = '$cmp'");
		return false;
	}
}
