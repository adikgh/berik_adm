<?
// Себес-2: сеттегі жоқ / граммовкасыз / бағасыз заттарды ЖИ-мен толтыру. Бір сет = бір ЖИ сұрауы (барлық заты бірге).
// Ортақ ЖИ: config/claude.php (claude::web + web_search), модель gtpl_ai::ask арқылы: алдымен Haiku, жауабы жарамсыз болса Sonnet.
// Тағам → products/tpl шаблоны (arh = 2, ЖИ ұсынысы) → gtpl::apply: шикізат пен дайындамалар компанияда атымен табылады не ашылады.
// Сатып алынатын тауар (Кола т.б.) → шикізат (дана бағасымен). ЖИ толтырғандары cost2_ai кестесінде белгіленеді («ЖИ» белгісі).
require_once __DIR__.'/../tpl/ai.php';
if (!class_exists('cost')) require_once __DIR__.'/../cost/lib.php';

class c2ai {
	public static $log = [];

	public static function table() {
		static $ok = null;
		if ($ok !== null) return $ok;
		$t = db::query("show tables like 'cost2_ai'");
		if (!$t || !mysqli_num_rows($t)) @db::query("CREATE TABLE IF NOT EXISTS `cost2_ai` (
			`id` int(11) NOT NULL AUTO_INCREMENT, `company_id` int(11) NOT NULL, `kind` char(1) NOT NULL COMMENT 'p = тағам (product), r = шикізат (product_raw), c = құрам (product_comp)',
			`ref_id` int(11) NOT NULL, `set_id` int(11) DEFAULT NULL, `ins_dt` datetime NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`), UNIQUE KEY `ref` (`kind`, `ref_id`), KEY `company_id` (`company_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Себес-2: ЖИ толтырған позициялар (тексеру үшін белгі)'");
		$t = db::query("show tables like 'cost2_ai'");
		return $ok = $t && mysqli_num_rows($t) > 0;
	}
	public static function mark($cmp, $kind, $id, $sid) {
		if (!$id || !self::table()) return;
		db::query("insert ignore into cost2_ai (company_id, kind, ref_id, set_id) values ('".(int)$cmp."', '".($kind == 'p' ? 'p' : ($kind == 'r' ? 'r' : 'c'))."', '".(int)$id."', '".(int)$sid."')");
	}
	// белгілер: ['p' => [id => 1], 'r' => [...], 'c' => [...]]
	public static function marks($cmp) {
		$m = ['p' => [], 'r' => [], 'c' => []];
		$t = db::query("show tables like 'cost2_ai'");
		if (!$t || !mysqli_num_rows($t)) return $m;
		$q = db::query("select kind, ref_id from cost2_ai where company_id = '".(int)$cmp."'");
		while ($q && $r = mysqli_fetch_row($q)) $m[$r[0]][(int)$r[1]] = 1;
		return $m;
	}

	// Сеттің толтырылатын заттары: dish = граммовкасы жоқ тағам, item = атауы ғана бар бос құрам (тағам не тауар), price = бағасыз шикізат
	public static function missing($cmp, $sid, $comps = null) {
		$cmp = (int)$cmp; $sid = (int)$sid; $comps = $comps ?: cost::comps($cmp);
		$res = []; $seen = [];
		$q = db::query("select si.id, si.product_id, si.comp_id from product_set_item si join product s on s.id = si.set_id where si.set_id = '$sid' and s.company_id = '$cmp' order by si.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			if ($r['product_id']) {
				$pid = (int)$r['product_id'];
				$p = mysqli_fetch_assoc(db::query("select id, name_ru, name_kz from product where id = '$pid' and company_id = '$cmp'"));
				if (!$p) continue;
				$its = []; $iq = db::query("select comp_id, quantity from product_item where product_id = '$pid' and comp_id > 0");
				while ($i = mysqli_fetch_assoc($iq)) $its[] = $i;
				if (!$its) { if (!isset($seen['p'.$pid])) $res[] = ['t' => 'dish', 'si' => (int)$r['id'], 'pid' => $pid, 'name' => trim(strip_tags($p['name_ru'] ?: $p['name_kz']))]; $seen['p'.$pid] = 1; continue; }
				$sum = 0; foreach ($its as $i) $sum += isset($comps[$i['comp_id']]) ? $i['quantity'] * $comps[$i['comp_id']]['cost'] / cost::div($comps[$i['comp_id']]['unit']) : 0;
				if ($sum > 0) continue;
				// граммовкасы бар, бірақ себесі 0: бағасыз заттарына баға
				foreach ($its as $i) { $c = $comps[$i['comp_id']] ?? null; if ($c && !$c['parts'] && $c['cost'] <= 0 && !isset($seen['c'.$c['id']])) { $seen['c'.$c['id']] = 1; $res[] = ['t' => 'price', 'cid' => (int)$c['id'], 'name' => trim($c['name_ru'] ?: $c['name_kz']), 'unit' => $c['unit'] ?: 'шт']; } }
			} elseif ($r['comp_id']) {
				$c = $comps[(int)$r['comp_id']] ?? null;
				if (!$c || $c['cost'] > 0 || $c['parts'] || isset($seen['c'.$c['id']])) continue;
				$seen['c'.$c['id']] = 1;
				$nm = trim($c['name_ru'] ?: $c['name_kz']);
				if (!empty($c['raw_id']) || $c['kind'] == 'raw') $res[] = ['t' => 'price', 'cid' => (int)$c['id'], 'name' => $nm, 'unit' => $c['unit'] ?: 'шт'];
				else $res[] = ['t' => 'item', 'si' => (int)$r['id'], 'cid' => (int)$c['id'], 'name' => $nm];
			}
		}
		return $res;
	}
	// толтыруды керек ететін сеттер
	public static function sets($cmp) {
		$cmp = (int)$cmp; $comps = cost::comps($cmp); $out = [];
		$q = db::query("select distinct si.set_id from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_row($q)) if (self::missing($cmp, $r[0], $comps)) $out[] = (int)$r[0];
		return $out;
	}

	// Бар шаблон (бекітілген не бұрын ЖИ жасаған) — тек атауы дәл сәйкес келсе (шатасып кетпес үшін)
	public static function tpl_exact($name) {
		$n = gtpl::norm($name); $e = gtpl::e($n);
		$r = mysqli_fetch_row(db::query("select id from grammovka_tpl where arh in (0, 2) and ".(gtpl::semi_ready() ? "kind = 'dish' and " : "")."(lower(name_ru) = '$e' or concat('|', lower(keys_ru), '|') like '%|$e|%') order by arh, id limit 1"));
		return $r ? (int)$r[0] : 0;
	}

	// Компанияның санаты: ЖИ атауы бойынша, табылмаса сеттегі басқа тағамдардың ең жиі санаты
	public static function catalog($cmp, $name, $sid) {
		$cmp = (int)$cmp; $n = gtpl::norm($name);
		if ($n !== '') {
			$q = db::query("select id, name_ru, name_kz from product_catalog where company_id = '$cmp' and (arh is null or arh = 0)");
			while ($q && $r = mysqli_fetch_assoc($q)) if (in_array($n, [gtpl::norm($r['name_ru']), gtpl::norm($r['name_kz'])])) return (int)$r['id'];
		}
		$r = mysqli_fetch_row(db::query("select p.catalog_id, count(*) n from product_set_item si join product p on p.id = si.product_id where si.set_id = '".(int)$sid."' and p.catalog_id > 0 group by p.catalog_id order by n desc limit 1"));
		return $r ? (int)$r[0] : 0;
	}
	// мәзірде аттас тағам (сет емес) болса — сол, болмаса жаңа позиция
	public static function product($cmp, $name, $cat_id, $price, &$new = 0) {
		$cmp = (int)$cmp; $e = gtpl::e(trim($name)); $new = 0;
		$set_c = gtpl::col('product', 'is_set');
		$q = db::query("select id from product where company_id = '$cmp' and (lower(name_ru) = lower('$e') or lower(name_kz) = lower('$e'))".($set_c ? " and (is_set is null or is_set = 0)" : "")."
			and not exists (select 1 from product_set_item si where si.set_id = product.id) order by arh, id limit 1");
		if ($q && ($r = mysqli_fetch_row($q))) return (int)$r[0];
		$id = (int)mysqli_fetch_row(db::query("select max(id) from product"))[0] + 1;
		$ok = db::query("insert into product (id, company_id, name_kz, name_ru, catalog_id, price, arh".($set_c ? ", is_set" : "").")
			values ('$id', '$cmp', '$e', '$e', ".($cat_id ? "'".(int)$cat_id."'" : "NULL").", '".max(0, (int)$price)."', 0".($set_c ? ", 0" : "").")");
		if (!$ok) return 0;
		$new = 1;
		return $id;
	}
	// тауар: шикізат (дана) + бағасы
	public static function goods_comp($cmp, $name, $unit, $price) {
		$u = gtpl::unit($unit ?: 'шт'); $p0 = 0; $src = ''; $newr = 0;
		$raw = gtpl::raw_id($cmp, $name, $u, $p0, $src, $newr);
		if (!$raw) return [0, 0];
		if ($price > 0) db::query("update product_raw set price = '".(int)$price."' where id = '$raw' and (price is null or price = 0)");
		return [$raw, $newr];
	}

	// Бір сетті толтыру. Қайтарады: ['ok' => 1, 'n' => толтырылғаны, 'usd', 'models', 'tokens', 'msg'] не ['error' => ...]
	public static function fill($cmp, $sid) {
		$cmp = (int)$cmp; $sid = (int)$sid; self::$log = [];
		$set = mysqli_fetch_assoc(db::query("select id, name_ru, name_kz from product where id = '$sid' and company_id = '$cmp'"));
		if (!$set) return ['error' => 'error'];
		$miss = self::missing($cmp, $sid);
		if (!$miss) return ['ok' => 1, 'n' => 0, 'usd' => 0];
		$raw0 = (int)mysqli_fetch_row(db::query("select ifnull(max(id), 0) from product_raw"))[0];
		$log0 = (int)@mysqli_fetch_row(db::query("select ifnull(max(id), 0) from company_ai_log"))[0];

		// бар шаблоны бар тағамдар ЖИ-сіз
		$ask = [];
		foreach ($miss as $k => $m) {
			if ($m['t'] == 'dish' && ($tid = self::tpl_exact($m['name']))) $miss[$k]['tpl'] = $tid;
			else $ask[$k] = $m;
		}
		$ans = [];
		if ($ask) {
			$raws = []; $q = db::query("select name_ru, unit, price from product_raw where company_id = '$cmp' order by name_ru limit 300");
			while ($r = mysqli_fetch_assoc($q)) $raws[] = $r['name_ru'].' ('.($r['unit'] ?: 'шт').($r['price'] > 0 ? ', '.$r['price'].' тг' : '').')';
			$preps = []; $q = db::query("select c.name_ru, c.unit from product_comp c where c.company_id = '$cmp' and exists (select 1 from product_comp_part p where p.comp_id = c.id) order by c.name_ru limit 150");
			while ($r = mysqli_fetch_assoc($q)) $preps[] = $r['name_ru'].' ('.($r['unit'] ?: 'кг').')';
			$cats = []; $q = db::query("select name_ru from product_catalog where company_id = '$cmp' and (arh is null or arh = 0) order by number, id");
			while ($r = mysqli_fetch_row($q)) if (trim($r[0]) !== '') $cats[] = trim($r[0]);
			$system = "Ты технолог и закупщик кухни доставки еды (суши, пицца, фастфуд) в Казахстане. Считаешь себестоимость сетов.\n"
				."Тебе дают позиции из состава сета. Для каждой (по номеру n):\n"
				."- t=dish или t=item: если это блюдо, которое кухня готовит (ролл, пицца, бургер, салат, фри), дай граммовку ОДНОЙ порции: ingredients [{name, unit, q, price}]. "
				."Если это покупной готовый товар (напиток, бутылка, соус в упаковке, палочки) — goods=true, unit (\"шт\"), price = закупочная цена за 1 unit в тенге, ingredients не нужны. Для t=dish goods ставь false, кроме явных напитков.\n"
				."- для блюда: cat = одна из категорий меню заведения (точное название из списка), menu_price = типичная цена порции в меню доставки Казахстана, тг.\n"
				."- t=price: только price = средняя оптовая/HoReCa цена закупки в тенге за 1 unit (1 кг, 1 л или 1 шт).\n"
				."Правила граммовки: единицы ТОЛЬКО \"г\", \"мл\" или \"шт\" (никаких кг/л и дробей). Воду не включай. Если ингредиент есть в наших списках (сырьё или заготовки) — пиши ТОЧНО то же название. "
				."Готовые заготовки (рис для суши, соусы, кляр) бери из нашего списка заготовок. Для ингредиента, которого нет в наших списках, дай price — закупочная цена за 1 кг / 1 л / 1 шт в тенге; для наших — price 0.\n"
				."Можешь кратко поискать в интернете (web_search) состав/техкарту блюда, если название необычное.\n"
				."Ответь в конце ТОЛЬКО одним блоком ```json ... ``` вида {\"items\":[{\"n\":1,\"goods\":false,\"cat\":\"...\",\"menu_price\":0,\"unit\":\"шт\",\"price\":0,\"ingredients\":[{\"name\":\"...\",\"unit\":\"г\",\"q\":0,\"price\":0}]}]}\n\n"
				."Категории меню: ".($cats ? implode(', ', $cats) : '(нет)')."\n\nНаше сырьё: ".($raws ? implode(', ', $raws) : '(нет)')."\n\nНаши заготовки: ".($preps ? implode(', ', $preps) : '(нет)');
			$lines = [];
			foreach ($ask as $k => $m) $lines[] = ($k + 1).'. '.$m['name'].' — t='.$m['t'].($m['t'] == 'price' ? ' (за 1 '.$m['unit'].')' : '');
			$user = 'Сет «'.trim(strip_tags($set['name_ru'] ?: $set['name_kz'])).'». Позиции:'."\n".implode("\n", $lines);
			$need = count($ask);
			$d = gtpl_ai::ask($system, $user, 'cost2_ai', min(3, 1 + count(array_filter($ask, function ($m) { return $m['t'] != 'price'; }))), function ($d) use ($ask, $need) {
				$n = 0;
				foreach ((array)@$d['items'] as $it) {
					$k = (int)@$it['n'] - 1; if (!isset($ask[$k])) continue;
					if ($ask[$k]['t'] == 'price' || !empty($it['goods'])) { if ((float)@$it['price'] > 0) $n++; }
					elseif (gtpl_ai::items_ok(gtpl_ai::items(array_map(function ($i) { $i['q'] = [0, @$i['q'], 0]; return $i; }, (array)@$it['ingredients'])))) $n++;
				}
				return $n >= ceil($need * 0.7);
			});
			if (!$d) return ['error' => gtpl_ai::err()] + self::usage($log0);
			foreach ((array)@$d['items'] as $it) { $k = (int)@$it['n'] - 1; if (isset($ask[$k])) $ans[$k] = $it; }
		}

		// жаңа шикізат бағасы: ЖИ берген (атауымен)
		$iprice = [];
		foreach ($ans as $it) foreach ((array)@$it['ingredients'] as $i) if ((float)@$i['price'] > 0) $iprice[gtpl::norm(@$i['name'])] = (int)round($i['price']);

		$done = 0; $msgs = [];
		foreach ($miss as $k => $m) {
			$it = $ans[$k] ?? null;
			if ($m['t'] == 'price') {
				$p = (int)round((float)@$it['price']);
				if ($p > 0) { gtpl_ai::set_price($m['cid'], $p, 'ai'); self::mark($cmp, 'c', $m['cid'], $sid); $done++; }
				continue;
			}
			$goods = $it && !empty($it['goods']);
			if ($goods) {
				$p = (int)round((float)@$it['price']);
				if ($m['t'] == 'item') {
					list($raw, $nr) = self::goods_comp($cmp, $m['name'], @$it['unit'], $p);
					if (!$raw) continue;
					$u = gtpl::unit(@$it['unit'] ?: 'шт');
					db::query("update product_comp set kind = 'raw', unit = '$u', raw_id = '$raw', price = '".max(0, $p)."'".(gtpl::col('product_comp', 'price_src') ? ", price_src = 'ai'" : "").", ubd_dt = NOW() where id = '{$m['cid']}' and company_id = '$cmp'");
					self::mark($cmp, 'c', $m['cid'], $sid); if ($nr) self::mark($cmp, 'r', $raw, $sid);
				} else {
					$cr = 0; $cid = gtpl::comp_id($cmp, $m['name'], gtpl::unit(@$it['unit'] ?: 'шт'), $cr, 2, -1);
					if (!$cid) continue;
					if ($p > 0) gtpl_ai::set_price($cid, $p, 'ai');
					db::query("insert into product_item (product_id, comp_id, quantity) values ('{$m['pid']}', '$cid', '1')");
					self::mark($cmp, 'p', $m['pid'], $sid);
				}
				$done++; continue;
			}
			// тағам: шаблон (бар не ЖИ-ден жаңа) → позицияға қолдану
			$tid = (int)@$m['tpl'];
			if (!$tid && $it) {
				$items = gtpl_ai::items(array_map(function ($i) { $i['q'] = [0, @$i['q'], 0]; return $i; }, (array)@$it['ingredients']), true);
				if (gtpl_ai::items_ok($items)) $tid = gtpl_ai::store(0, $m['name'], trim((string)@$it['cat']), $m['name'], $items, 2, 'Себес-2: ЖИ (сет «'.trim(strip_tags($set['name_ru'])).'»)', 'dish');
			}
			if (!$tid) { $msgs[] = $m['name']; continue; }
			$pid = (int)@$m['pid'];
			if ($m['t'] == 'item') {
				$pn = 0; $pid = self::product($cmp, $m['name'], self::catalog($cmp, (string)@$it['cat'], $sid), (int)@$it['menu_price'], $pn);
				if (!$pid) continue;
				db::query("update product_set_item set product_id = '$pid', comp_id = NULL where id = '{$m['si']}'");
			}
			$cr = 0;
			$r = gtpl::apply($cmp, $pid, $tid, 2, false, $cr, true);
			if ($r == 'yes' || $r == 'has') { self::mark($cmp, 'p', $pid, $sid); $done++; }
		}

		// жаңа шикізатқа баға: ЖИ атаған баға, қалғанына gtpl_ai::prices (база / ЖИ)
		$left = [];
		foreach (array_unique(gtpl::$noprice) as $cid) {
			$c = mysqli_fetch_assoc(db::query("select name_ru from product_comp where id = '".(int)$cid."'"));
			$p = $c ? ($iprice[gtpl::norm($c['name_ru'])] ?? 0) : 0;
			if ($p > 0) gtpl_ai::set_price($cid, $p, 'ai'); else $left[] = (int)$cid;
		}
		if ($left) gtpl_ai::prices($cmp, $left);
		$q = db::query("select id from product_raw where company_id = '$cmp' and id > '$raw0'");
		while ($q && $r = mysqli_fetch_row($q)) self::mark($cmp, 'r', $r[0], $sid);
		cost::recalc($cmp);
		return ['ok' => 1, 'n' => $done, 'skip' => $msgs] + self::usage($log0);
	}
	// осы толтырудың ЖИ шығыны (company_ai_log)
	public static function usage($log0) {
		$u = ['usd' => 0, 'tokens' => 0, 'models' => []];
		$q = @db::query("select model, input_tokens + cache_read + cache_write i, output_tokens o, usd from company_ai_log where id > '".(int)$log0."' and company_id = '".(int)$GLOBALS['company']."'");
		while ($q && $r = mysqli_fetch_assoc($q)) { $u['usd'] += (float)$r['usd']; $u['tokens'] += (int)$r['i'] + (int)$r['o']; $u['models'][] = $r['model'].' '.$r['i'].'/'.$r['o']; }
		$u['usd'] = round($u['usd'], 4);
		return $u;
	}
}
