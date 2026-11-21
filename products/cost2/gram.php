<?
	// Граммовка анализі — Тағамдар тізімі (products/index.php) мен Себес беті (products/cost2/index.php) осы бір функцияны қолданады.
	// Ереже (тағам түрі products/cost2/kind.php):
	//   4 қатысы жоқ — санауға кірмейді (st = skip);
	//   3 тікелей шикізат — шикізатқа байланса толық;
	//   2 тағам — граммовкасы бар, барлық заты шикізатқа/рецептке байланған және себесі > 0 болса толық;
	//   1 сет — құрамы бар, барлық заты позиция / тікелей шикізат және олардың граммовкасы толық болса толық
	//   (сет ішіндегі «Қатысы жоқ» есептелмейді); түрі белгісіз болса құрамына қарап (сет құрамы → сет, граммовка → тағам).
	// Нәтиже: [id => ['k' => түрі (0 белгісіз), 'st' => ok | part | none | skip, 'why' => себеп коды, 'a' => Себестегі якорь, 'cost' => себес]]
	if (!class_exists('cost')) require_once __DIR__.'/../cost/lib.php';
	class c2gram {
		private static $cache = [];
		// себеп коды → белгі мәтіні (5 тілде site_word)
		public static function why_text($w) {
			$t = [
				'noset' => ['c2 Why noset', 'Құрамы жоқ'],
				'nogram' => ['c2 Why nogram', 'Граммовкасы жоқ'],
				'raw' => ['c2 Why raw', 'Шикізатқа байланбаған'],
				'notpos' => ['c2 Why notpos', 'Ішінде позиция емес зат'],
				'cost0' => ['c2 Why cost0', 'Себесі 0'],
				'unk' => ['c2 Why unk', 'Түрі белгісіз'],
			];
			return isset($t[$w]) ? t::w($t[$w][0], $t[$w][1]) : '';
		}
		public static function status($cmp) {
			$cmp = (int)$cmp;
			if (isset(self::$cache[$cmp])) return self::$cache[$cmp];
			$has_k = fun::has_col('product', 'kind'); $has_s = fun::has_col('product', 'is_set');
			$prd = [];
			$q = db::query("select id".($has_k ? ", kind" : "").($has_s ? ", is_set" : "")." from product where company_id = '$cmp' and (arh is null or arh = 0)");
			while ($q && $r = mysqli_fetch_assoc($q)) {
				$k = $has_k && $r['kind'] !== null ? (int)$r['kind'] : 0;
				if (!$k && $has_s && $r['is_set'] !== null && !$has_k) $k = (int)$r['is_set'] === 1 ? 1 : 2;
				$prd[(int)$r['id']] = $k;
			}
			$comps = cost::comps($cmp);
			// заты шикізатқа не рецептке байланған ба (рецепт ішінде де)
			$cm = [];
			$comp_ok = function ($cid, $d = 0) use (&$comp_ok, &$cm, $comps) {
				if (isset($cm[$cid])) return $cm[$cid];
				$c = $comps[$cid] ?? null;
				if (!$c || $d > 8) return $cm[$cid] = false;
				$cm[$cid] = true;
				if ($c['parts']) { $ok = true; foreach ($c['parts'] as $p) if (!$p['raw_id'] && !($p['sub_comp_id'] && $comp_ok((int)$p['sub_comp_id'], $d + 1))) $ok = false; }
				else $ok = !empty($c['raw_id']);
				return $cm[$cid] = $ok;
			};
			$items = [];
			$q = db::query("select pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0");
			while ($q && $r = mysqli_fetch_assoc($q)) $items[(int)$r['product_id']][] = $r;
			$sets = [];
			$t = db::query("show tables like 'product_set_item'");
			if ($t && mysqli_num_rows($t)) {
				$q = db::query("select si.set_id, si.product_id, si.comp_id, si.quantity from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp'");
				while ($q && $r = mysqli_fetch_assoc($q)) $sets[(int)$r['set_id']][] = $r;
			}
			// тағам (2) не тікелей шикізат (3): граммовкасы
			$dish = function ($pid, $k) use ($items, $comps, $comp_ok) {
				if (empty($items[$pid])) return ['st' => 'none', 'why' => $k == 3 ? 'raw' : 'nogram', 'cost' => 0];
				$sum = 0; $bad = false;
				foreach ($items[$pid] as $i) {
					$c = $comps[(int)$i['comp_id']] ?? null;
					if (!$c || !$comp_ok((int)$i['comp_id'])) $bad = true;
					if ($c) $sum += $i['quantity'] * $c['cost'] / cost::div($c['unit']);
				}
				if ($bad) return ['st' => 'part', 'why' => 'raw', 'cost' => $sum];
				if ($sum <= 0) return ['st' => 'part', 'why' => 'cost0', 'cost' => $sum];
				return ['st' => 'ok', 'why' => '', 'cost' => $sum];
			};
			$res = [];
			$one = function ($pid) use (&$one, &$res, $prd, $sets, $items, $comps, $comp_ok, $dish) {
				if (isset($res[$pid])) return $res[$pid];
				$k = $prd[$pid] ?? -1;
				if ($k < 0) return ['k' => -1, 'st' => 'part', 'why' => 'notpos', 'a' => '', 'cost' => 0]; // архивте не басқа компанияда
				if ($k == 4) return $res[$pid] = ['k' => 4, 'st' => 'skip', 'why' => '', 'a' => '', 'cost' => 0];
				$ek = $k ?: (isset($sets[$pid]) ? 1 : (isset($items[$pid]) ? 2 : 0));
				if (!$ek) return $res[$pid] = ['k' => 0, 'st' => 'none', 'why' => 'unk', 'a' => 'd'.$pid, 'cost' => 0];
				if ($ek != 1) return $res[$pid] = ['k' => $k] + $dish($pid, $ek) + ['a' => 'd'.$pid];
				$res[$pid] = ['k' => $k, 'st' => 'part', 'why' => 'notpos', 'a' => 's'.$pid, 'cost' => 0]; // циклден қорғау
				if (empty($sets[$pid])) return $res[$pid] = ['k' => $k, 'st' => 'none', 'why' => 'noset', 'a' => 's'.$pid, 'cost' => 0];
				$sum = 0; $why = ''; $n = 0;
				foreach ($sets[$pid] as $s) {
					if ($s['product_id']) {
						$cid = (int)$s['product_id']; $ck = $prd[$cid] ?? -1;
						if ($ck == 4) continue; // қатысы жоқ: есептелмейді
						$n++;
						$ce = $ck > 0 ? $ck : (isset($items[$cid]) ? 2 : ($ck < 0 ? -1 : (isset($sets[$cid]) ? 1 : 0)));
						if ($ce == 0) { $why = $why ?: 'nogram'; continue; } // түрі белгісіз, граммовкасы да жоқ
						if ($ce != 2 && $ce != 3) { $why = $why ?: 'notpos'; continue; } // сет ішінде сет, архивтегі тағам
						$x = $dish($cid, $ce); $sum += $x['cost'] * $s['quantity'];
						if ($x['st'] != 'ok') $why = $why ?: $x['why'];
					} else {
						$n++; $c = $comps[(int)$s['comp_id']] ?? null;
						if (!$c || !$comp_ok((int)$s['comp_id'])) { $why = $why ?: 'raw'; }
						if ($c) $sum += $c['cost'] * $s['quantity'];
					}
				}
				if (!$n) return $res[$pid] = ['k' => $k, 'st' => 'none', 'why' => 'noset', 'a' => 's'.$pid, 'cost' => 0];
				if (!$why && $sum <= 0) $why = 'cost0';
				return $res[$pid] = ['k' => $k, 'st' => $why ? 'part' : 'ok', 'why' => $why, 'a' => 's'.$pid, 'cost' => $sum];
			};
			foreach ($prd as $pid => $k) $one($pid);
			$out = []; foreach ($prd as $pid => $k) $out[$pid] = $res[$pid];
			return self::$cache[$cmp] = $out;
		}
		// санауыштар: all (қатысы жоқсыз), ok, part, none
		public static function counts($cmp) {
			$n = ['all' => 0, 'ok' => 0, 'part' => 0, 'none' => 0];
			foreach (self::status($cmp) as $x) { if ($x['st'] == 'skip') continue; $n['all']++; $n[$x['st']]++; }
			return $n;
		}
	}
