<?
	// Себес (өзіндік құн): шикізат → дайындама → тағам → сет
	class cost {

		public static function ready() {
			$q = db::query("show tables like 'product_comp_part'");
			return $q && mysqli_num_rows($q) > 0;
		}

		public static function fmt($v) { return number_format(round($v), 0, '', ' '); }

		// кг/л ингредиент тағамда граммен жазылады (÷1000)
		public static function div($unit) { return in_array(mb_strtolower(trim((string)$unit)), ['кг', 'л']) ? 1000 : 1; }

		public static function setting($cmp) {
			$d = ['orders_day' => 0, 'salary' => 0, 'rent' => 0, 'utility' => 0, 'delivery' => 0, 'ads' => 0, 'markup' => 0];
			$q = db::query("select * from cost_setting where company_id = '$cmp'");
			if ($q && ($r = mysqli_fetch_assoc($q))) foreach ($d as $k => $v) $d[$k] = $r[$k] + 0;
			return $d;
		}
		public static function overhead($s) { return $s['salary'] + $s['rent'] + $s['utility'] + $s['delivery'] + $s['ads']; }

		// 490 / 990-мен аяқталатын ең жақын жоғарғы баға
		public static function nice($v) { return (int)(ceil(($v + 10) / 500) * 500 - 10); }

		// Компанияның барлық ингредиентінің бір бірлік бағасы (рецепт → шикізат → қолмен)
		public static function comps($cmp) {
			$raw = [];
			$q = db::query("select id, price from product_raw where company_id = '$cmp'");
			while ($r = mysqli_fetch_assoc($q)) $raw[$r['id']] = (float)$r['price'];
			$comps = [];
			$q = db::query("select * from product_comp where company_id = '$cmp' order by name_ru, id");
			while ($r = mysqli_fetch_assoc($q)) { $r['parts'] = []; $comps[$r['id']] = $r; }
			if ($comps) {
				$ids = implode(',', array_keys($comps));
				$q = db::query("select * from product_comp_part where comp_id in ($ids) order by id");
				while ($r = mysqli_fetch_assoc($q)) $comps[$r['comp_id']]['parts'][] = $r;
			}
			$memo = [];
			$calc = function ($id, $depth = 0) use (&$calc, &$memo, $comps, $raw) {
				if (isset($memo[$id])) return $memo[$id];
				$c = $comps[$id] ?? null;
				if (!$c) return 0;
				if ($c['parts'] && $depth < 8) {
					$sum = 0;
					foreach ($c['parts'] as $p) {
						if ($p['raw_id']) $sum += $p['quantity'] * ($raw[$p['raw_id']] ?? 0);
						elseif ($p['sub_comp_id']) $sum += $p['quantity'] * $calc($p['sub_comp_id'], $depth + 1);
					}
					$y = (float)$c['yield_qty'];
					$v = $y > 0 ? $sum / $y : $sum;
				} elseif (!empty($c['raw_id']) && isset($raw[$c['raw_id']])) $v = $raw[$c['raw_id']];
				else $v = (float)$c['price'];
				return $memo[$id] = $v;
			};
			foreach ($comps as $id => $c) $comps[$id]['cost'] = $calc($id);
			return $comps;
		}

		// Есептелген бағаны product_comp.price-қа жазады (тағам карточкасы соны көрсетеді)
		public static function recalc($cmp) {
			$n = 0;
			foreach (self::comps($cmp) as $id => $c) {
				if (!$c['parts'] && empty($c['raw_id'])) continue;
				$p = (int)round($c['cost']);
				if ($p != (int)$c['price']) { db::query("update product_comp set price = '$p', ubd_dt = NOW() where id = '$id'"); $n++; }
			}
			return $n;
		}

		// Мәзірдегі тағамдардың себесі: product_id => cost
		public static function dishes($cmp, $comps) {
			$res = [];
			$q = db::query("select pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0");
			while ($r = mysqli_fetch_assoc($q)) {
				$c = $comps[$r['comp_id']] ?? null;
				if (!$c) continue;
				$res[$r['product_id']] = ($res[$r['product_id']] ?? 0) + $r['quantity'] * $c['cost'] / self::div($c['unit']);
			}
			return $res;
		}

		// Сеттер: set_id => [cost, items[]]
		public static function sets($cmp, $comps, $dishes) {
			$res = [];
			$q = db::query("select si.*, d.name_ru d_name from product_set_item si join product s on s.id = si.set_id
				left join product d on d.id = si.product_id where s.company_id = '$cmp' order by si.id");
			while ($r = mysqli_fetch_assoc($q)) {
				if ($r['product_id']) { $u = $dishes[$r['product_id']] ?? 0; $nm = strip_tags((string)$r['d_name']); }
				else { $c = $comps[$r['comp_id']] ?? null; $u = $c ? $c['cost'] : 0; $nm = $c ? $c['name_ru'] : '?'; }
				$s = &$res[$r['set_id']];
				if (!$s) $s = ['cost' => 0, 'items' => []];
				$s['cost'] += $u * $r['quantity'];
				$s['items'][] = ['name' => $nm, 'qty' => $r['quantity'] + 0, 'cost' => $u * $r['quantity']];
				unset($s);
			}
			return $res;
		}
	}
