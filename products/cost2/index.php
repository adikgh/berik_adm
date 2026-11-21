<? include "../../config/core.php";
	include "../cost/lib.php";
	// Тағамдар ▸ Себес (бұрынғы «Себес-2», папкасы cost2): Google кестесі сияқты бір бетте бәрі —
	// шикізат (закуп бағасы) → дайындамалар → тағамдар (граммовка) → сеттер → қорытынды баға
	if (!$user_id) { header('location: /'); exit(); }
	$cmp = (int)$company;
	$ready = cost::ready();
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
	$f = function ($v) { return cost::fmt($v); };
	$isw = function ($u) { return in_array(mb_strtolower(trim((string)$u)), ['кг', 'л']); };
	// мөлшер: кг/л → г/мл, қалғаны дана
	$qf = function ($qty, $u) use ($isw) {
		$u = trim((string)$u);
		if ($isw($u)) return rtrim(rtrim(number_format($qty * 1000, 1, ',', ' '), '0'), ',').' '.(mb_strtolower($u) == 'л' ? 'мл' : 'г');
		return rtrim(rtrim(number_format($qty, 3, ',', ' '), '0'), ',').' '.($u ?: 'шт');
	};
	// 1 кг / 1 шт бағасы (тиын болса 1 таңбамен)
	$nv = function ($v) { return rtrim(rtrim(number_format((float)$v, 3, ',', ''), '0'), ','); };
	$pf = function ($v) { return $v > 0 && $v < 100 && round($v) != $v ? number_format($v, 1, ',', ' ') : cost::fmt($v); };

	if ($ready) {
		cost::recalc($cmp);
		$st = cost::setting($cmp);
		$over = cost::overhead($st);
		$comps = cost::comps($cmp);

		// Шикізат + соңғы закуп (накладной болса)
		$raw = [];
		require_once __DIR__.'/rawtype.php';
		$tcol = c2type::ready() ? ', type_id' : '';
		$q = db::query("select id, name_ru, name_kz, unit, price$tcol from product_raw where company_id = '$cmp' order by name_ru, name_kz");
		while ($q && $r = mysqli_fetch_assoc($q)) $raw[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => trim($r['name_ru'] ?: $r['name_kz']), 'unit' => (string)$r['unit'], 'price' => (float)$r['price'], 'last_d' => null, 'lastp' => null, 'sup' => '', 'prev' => null, 'used' => 0, 'type' => (int)@$r['type_id']];
		$rtypes = c2type::all($cmp); // шикізат түрлері (Ун ▸ нұсқалар)
		$t = db::query("show tables like 'waybill_item'");
		if ($t && mysqli_num_rows($t)) {
			$hasf = mysqli_num_rows(db::query("show columns from waybill_item like 'factor'")) > 0;
			$q = db::query("select i.raw_id, i.price".($hasf ? ", i.factor" : "").", w.doc_date, s.name sname from waybill_item i join waybill w on w.id = i.waybill_id
				left join supplier s on s.id = w.supplier_id where w.company_id = '$cmp' and i.raw_id > 0 order by w.doc_date, w.id, i.id");
			while ($q && $i = mysqli_fetch_assoc($q)) {
				$k = (int)$i['raw_id']; if (!isset($raw[$k])) continue;
				$fc = $hasf && (float)$i['factor'] > 0 ? (float)$i['factor'] : 1;
				$up = (float)$i['price'] / $fc;
				if ($raw[$k]['last_d'] !== null) $raw[$k]['prev'] = $raw[$k]['lastp'];
				$raw[$k]['lastp'] = $up; $raw[$k]['last_d'] = $i['doc_date']; $raw[$k]['sup'] = (string)$i['sname'];
			}
		}

		// Дайындамалар: рецепті бар құрамдар
		$preps = [];
		foreach ($comps as $id => $c) {
			if (!$c['parts']) continue;
			$rows = []; $sum = 0;
			foreach ($c['parts'] as $p) {
				if ($p['raw_id']) { $x = $raw[(int)$p['raw_id']] ?? null; $nm = $x ? $x['name'] : '?'; $u = $x ? $x['unit'] : ''; $pr = $x ? $x['price'] : 0; $lnk = 'r'.(int)$p['raw_id']; $pe = $x ? ['raw', (int)$p['raw_id'], 'price'] : null; if ($x) $raw[(int)$p['raw_id']]['used']++; }
				else { $x = $comps[(int)$p['sub_comp_id']] ?? null; $nm = $x ? ($x['name_ru'] ?: $x['name_kz']) : '?'; $u = $x ? $x['unit'] : ''; $pr = $x ? $x['cost'] : 0; $lnk = $x && $x['parts'] ? 'p'.(int)$p['sub_comp_id'] : ($x && !empty($x['raw_id']) ? 'r'.(int)$x['raw_id'] : ''); $pe = $x && !$x['parts'] ? ['comp_price', (int)$x['id'], ''] : null; }
				$s = $p['quantity'] * $pr; $sum += $s;
				$rows[] = ['id' => (int)$p['id'], 'name' => $nm, 'qty' => $qf($p['quantity'], $u), 'qv' => $nv($isw($u) ? $p['quantity'] * 1000 : $p['quantity']), 'price' => $pr, 'pe' => $pe, 'sum' => $s, 'lnk' => $lnk];
			}
			$preps[$id] = ['id' => $id, 'name' => $c['name_ru'] ?: $c['name_kz'], 'unit' => $c['unit'] ?: 'шт', 'yield' => (float)$c['yield_qty'], 'sum' => $sum, 'cost' => $c['cost'], 'rows' => $rows, 'kind' => $c['kind']];
		}

		// тағам түрі (products/cost2/kind.php): 1 сет, 2 тағам, 3 тікелей шикізат, 4 қатысы жоқ — Себесте мүлде көрінбейді, есептелмейді
		require_once __DIR__.'/kind.php';
		$km = pkind::map($cmp); $kn = pkind::names(); $kind_on = pkind::ready();
		// Мәзірдегі тағамдар және олардың граммовкасы
		$prd = [];
		$q = db::query("select p.id, p.name_ru, p.name_kz, p.price, p.arh from product p where p.company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_assoc($q)) if (($km[(int)$r['id']] ?? 0) != 4) $prd[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => trim(strip_tags($r['name_ru'] ?: $r['name_kz'])), 'price' => (int)preg_replace('/\D+/', '', (string)$r['price']), 'arh' => (int)$r['arh']];
		$dishes = [];
		$q = db::query("select pi.id, pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0 order by pi.product_id, pi.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$pid = (int)$r['product_id']; $c = $comps[(int)$r['comp_id']] ?? null;
			if (!$c || !isset($prd[$pid])) continue;
			$u = (string)$c['unit']; $d = cost::div($u);
			$pr = $c['cost'];
			$s = $r['quantity'] * $pr / $d;
			// граммен жазылған кг затты кестедегідей г етіп көрсетеміз
			$qt = $d > 1 ? rtrim(rtrim(number_format($r['quantity'], 1, ',', ' '), '0'), ',').' '.(mb_strtolower(trim($u)) == 'л' ? 'мл' : 'г') : rtrim(rtrim(number_format($r['quantity'], 3, ',', ' '), '0'), ',').' '.($u ?: 'шт');
			$lnk = $c['parts'] ? 'p'.(int)$c['id'] : (!empty($c['raw_id']) ? 'r'.(int)$c['raw_id'] : '');
			$pe = $c['parts'] ? null : ['comp_price', (int)$c['id'], ''];
			if (!$c['parts'] && !empty($c['raw_id']) && isset($raw[(int)$c['raw_id']])) $raw[(int)$c['raw_id']]['used']++;
			if (!isset($dishes[$pid])) $dishes[$pid] = ['id' => $pid, 'name' => $prd[$pid]['name'], 'price' => $prd[$pid]['price'], 'sum' => 0, 'rows' => []];
			$dishes[$pid]['sum'] += $s;
			$dishes[$pid]['rows'][] = ['id' => (int)$r['id'], 'name' => $c['name_ru'] ?: $c['name_kz'], 'qty' => $qt, 'qv' => $nv($r['quantity']), 'price' => $pr, 'pe' => $pe, 'sum' => $s, 'lnk' => $lnk];
		}

		// Сеттер
		$sets = [];
		$q = db::query("select si.* from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp' order by si.set_id, si.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$sid = (int)$r['set_id']; if (!isset($prd[$sid])) continue;
			if ($r['product_id'] && ($km[(int)$r['product_id']] ?? 0) == 4) continue; // қатысы жоқ: сет себесіне де кірмейді
			if ($r['product_id']) { $pid = (int)$r['product_id']; $nm = $prd[$pid]['name'] ?? '?'; $u = isset($dishes[$pid]) ? $dishes[$pid]['sum'] : 0; $lnk = isset($prd[$pid]) ? 'd'.$pid : ''; $miss = $u <= 0 && ($km[$pid] ?? 0) != 4; $pe = null; }
			else { $c = $comps[(int)$r['comp_id']] ?? null; $nm = $c ? ($c['name_ru'] ?: $c['name_kz']) : '?'; $u = $c ? $c['cost'] : 0; $lnk = $c && $c['parts'] ? 'p'.(int)$c['id'] : ($c && !empty($c['raw_id']) ? 'r'.(int)$c['raw_id'] : ''); $miss = !$c || $u <= 0; $pe = $c && !$c['parts'] ? ['comp_price', (int)$c['id'], ''] : null; }
			if (!isset($sets[$sid])) $sets[$sid] = ['id' => $sid, 'name' => $prd[$sid]['name'], 'price' => $prd[$sid]['price'], 'arh' => $prd[$sid]['arh'], 'sum' => 0, 'rows' => [], 'miss' => 0];
			$s = $u * $r['quantity'];
			$sets[$sid]['sum'] += $s;
			if ($miss) $sets[$sid]['miss']++;
			$sets[$sid]['rows'][] = ['id' => (int)$r['id'], 'name' => strip_tags($nm), 'qty' => ($r['quantity'] + 0), 'qv' => $nv($r['quantity']), 'price' => $u, 'pe' => $pe, 'sum' => $s, 'lnk' => $lnk, 'miss' => $miss, 'cid' => (int)$r['comp_id']];
		}
		// сет болып табылатын өнім Тағамдар бағанында қайталанбайды; құрамы жоқ тағамдар да шығады (бос блок, «+ Зат қосу»)
		foreach ($sets as $sid => $x) unset($dishes[$sid]);
		foreach ($prd as $pid => $p) if (!$p['arh'] && !isset($sets[$pid]) && !isset($dishes[$pid])) $dishes[$pid] = ['id' => $pid, 'name' => $p['name'], 'price' => $p['price'], 'sum' => 0, 'rows' => []];
		$by_name = function ($a, $b) { return strcasecmp($a['name'], $b['name']); };
		uasort($preps, $by_name);
		uasort($dishes, function ($a, $b) { return [!$a['rows'], mb_strtolower($a['name'])] <=> [!$b['rows'], mb_strtolower($b['name'])]; });
		// «+ Зат қосу» тізімдері: шикізат, құрам/дайындама, мәзір тағамы
		$OPT = ['r' => [], 'c' => [], 'd' => []];
		foreach ($raw as $x) $OPT['r'][] = ['c' => 'r'.$x['id'], 'n' => $x['name'], 'u' => $x['unit']];
		foreach ($comps as $x) if ($x['parts'] || empty($x['raw_id'])) $OPT['c'][] = ['c' => 'c'.$x['id'], 'n' => $x['name_ru'] ?: $x['name_kz'], 'u' => (string)$x['unit']];
		foreach ($prd as $x) if (!$x['arh'] && !isset($sets[$x['id']])) $OPT['d'][] = ['c' => 'd'.$x['id'], 'n' => $x['name'], 'u' => 'шт'];
		$has_pp = is_file(__DIR__.'/../cost/prep_pop.php') && is_file(__DIR__.'/../../assets/js/prep_pop.js');
		uasort($sets, function ($a, $b) { return [$a['arh'], $a['name']] <=> [$b['arh'], $b['name']]; });

		// План | Факт. План — «Баға стандарттары» (cost_setting). Факт (?f=1) — қолмен толтырылатын бөлек мәндер (cost_setting_fact, products/cost2/fact.php).
		// Фактта бос өріс — План мәні (сұр, «жоспар»). Ингредиент — рецепт бойынша, екі режимде бірдей.
		require_once __DIR__.'/fact.php';
		$fact = !empty($_GET['f']);
		$sf = c2fact::get($cmp);
		$fact_empty = true; if ($sf) foreach ($sf as $v) if ($v !== null) $fact_empty = false;
		$stu = $st; $plan_k = [];
		if ($fact) foreach (c2fact::$keys as $k) { if ($sf && $sf[$k] !== null) $stu[$k] = $sf[$k]; else $plan_k[$k] = 1; }
		$over_u = cost::overhead($stu);

		// Қорытынды: сеттің толық себесі мен ұсынылатын бағасы (кестенің оң жағы), таңдалған режимнің мәндерімен
		$mk = $stu['markup'] / 100;
		foreach ($sets as &$x) {
			$x['full'] = $x['sum'] + $over_u;
			$x['mk'] = $x['full'] * $mk;
			$x['total'] = $x['full'] + $x['mk'];
			$x['nice'] = cost::nice($x['total']);
			$x['profit'] = $x['price'] - $x['full'];
			$x['pct'] = $x['price'] ? $x['profit'] / $x['price'] * 100 : 0;
		}
		unset($x);
		$used_raw = $raw;

		// ескі Себес беттегідей: статистика, граммовкасы жоқтар, шаблон батырмалары
		// бағасы 0, себесі жоқ не толық емес (қызыл заты бар) сеттер статистикаға кірмейді — орташа пайданы бұрмаламасын
		$n_bad = 0; $n_low = 0; $sum_p = 0; $n_p = 0;
		foreach ($sets as $x) { if ($x['price'] <= 0 || $x['sum'] <= 0 || $x['miss']) continue; $n_p++; if ($x['profit'] < 0) $n_bad++; elseif ($x['pct'] < 5) $n_low++; $sum_p += $x['pct']; }
		$avg = $n_p ? round($sum_p / $n_p) : 0;
		$n_dish = 0; foreach ($dishes as $x) if ($x['rows']) $n_dish++;
		@include_once __DIR__.'/../tpl/lib.php';
		$gt_all = class_exists('gtpl') && gtpl::ready();
		// «Граммовкасы жоқ» = «Шаблоннан толтыру» терезесімен бір ереже (gtpl::empty_dishes: граммовкасы бос, сет емес, архивте емес позициялар)
		$miss_ids = []; if (class_exists('gtpl')) foreach (gtpl::empty_dishes($cmp) as $r) $miss_ids[(int)$r['id']] = 1;
		$n_miss = count($miss_ids);
		// граммовка анализі: Тағамдар тізімімен бір функция (products/cost2/gram.php), «Қатысы жоқ» кірмейді
		require_once __DIR__.'/gram.php'; $gcnt = c2gram::counts($cmp);
		// шикізатқа жайылған дайындамасы бар тағамдар (рис + уксус + … → «Рис для суши»): «Дайындамаға жинау» батырмасы
		require_once __DIR__.'/semi_fix.php';
		$semi_cand = class_exists('gtpl') && gtpl::semi_ready() ? c2semi::candidates($cmp) : [];
		// «ЖИ-мен жалғастыру»: тізбек үзілген жерлер (products/cost2/cont.php). $brk[түрі] = [[id, атауы]], $bk[түрі][id] = 1
		$brk = []; $bk = [];
		if (!empty($gt_all) && gtpl::semi_ready()) { require_once __DIR__.'/cont.php'; $brk = c2cont::breaks($cmp); foreach ($brk as $k => $l) foreach ($l as $x) $bk[$k][$x[0]] = 1; }
		$n_steps = 0; foreach ($brk as $k => $l) $n_steps += in_array($k, ['kind', 'link', 'price']) ? ($l ? 1 : 0) : count($l);
		$empty_sets = $brk['set'] ?? [];
		// қайталанған шикізат (көлемі әртүрлі болса да бір зат: products/cost2/rawdup.php)
		require_once __DIR__.'/rawdup.php';
		$n_dup = 0; foreach (c2raw::groups($cmp) as $g) $n_dup += count($g['items']) - 1;
		$n_tc = count(c2type::candidates($cmp)); // «Бір түрге жинау» ұсыныстары
		$gt_semi = $gt_all && gtpl::semi_ready();
		// ЖИ толтыру (products/cost2/ai.php): батырмалар және «ЖИ толтырды» белгілері (cost2_ai кестесі)
		require_once __DIR__.'/../../config/claude.php';
		$ai_ok = $gt_all && claude::ready();
		$n_aiset = 0; foreach ($sets as $x) if ($x['miss']) $n_aiset++;
		$aim = ['p' => [], 'r' => [], 'c' => []];
		$t = db::query("show tables like 'cost2_ai'");
		if ($t && mysqli_num_rows($t)) { $q = db::query("select kind, ref_id from cost2_ai where company_id = '$cmp'"); while ($q && $r = mysqli_fetch_row($q)) $aim[$r[0]][(int)$r[1]] = 1; }
	}

	$menu_name = 'products';
	$pod_menu_name = 'cost2';
	$pm = 'cost2';
	$page_name = t::w('Cost sheet', 'Себес');
	$page_key = 'Cost sheet';
	$css = ['stats', 'cost2', 'products/import', 'products/goods']; // products/import: прогресс тізімі (mi_prog, mi_posts) — ЖИ поп-апы // stats: админдегі стандарт сегмент (.st_seg) — План | Факт
	$js = [];
	if (!empty($gt_all)) { $css[] = 'gtpl'; $js[] = 'gtpl'; }
	if (!empty($has_pp)) $js[] = 'prep_pop';
	$js[] = 'products/goods'; // тікелей шикізат: шикізатпен байланыс поп-апы
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c c2_page">
	<? if (!$ready): ?>
		<div class="c2_card c2_note"><?=t::w('c2 Cost tables are not created yet', 'Себес кестелері әлі жасалмаған')?></div>
	<? else: ?>

		<!-- шаблон + қойындылар (бөлімге өту) + іздеу -->
		<div class="c2_top">
			<? if ($gt_all): ?><button type="button" class="btn gt_all_btn c2_gt"><i class="fal fa-magic"></i> <span><?=t::w('c2 Fill from templates', 'Шаблоннан толтыру')?></span></button><? endif ?>
			<div class="c2_tabs">
				<button type="button" class="c2_tab" data-go="c2_sec_sets"><?=t::w('c2 Sets', 'Сеттер')?> <span><?=count($sets)?></span></button>
				<button type="button" class="c2_tab" data-go="c2_col_d"><?=t::w('c2 Dishes', 'Тағамдар')?> <span><?=$n_dish?></span></button>
				<button type="button" class="c2_tab" data-go="c2_col_p"><?=t::w('c2 Semi-finished', 'Дайындамалар')?> <span><?=count($preps)?></span></button>
				<? if ($n_miss): ?><button type="button" class="c2_tab c2_tab_miss" data-go="c2_col_d" data-miss="1"><?=t::w('c2 No grammovka', 'Граммовкасы жоқ')?> <span><?=$n_miss?></span></button><? endif ?>
			</div>
			<? if (!empty($ai_ok) && $n_steps): ?><button type="button" class="c2_semifix c2_cont c2_cont_all" data-k=""><i class="fal fa-sparkles"></i> <span><?=$h(str_replace('{n}', $n_steps, t::w('c2 AI continue all', 'ЖИ-мен жалғастыру ({n})')))?></span></button><? endif ?>
			<label class="c2_srch"><i class="fal fa-search"></i><input type="search" class="c2_q" placeholder="<?=$h(t::w('c2 Search', 'Іздеу'))?>"></label>
		</div>

		<!-- статистика (ескі Себес беттегідей) -->
		<div class="c2_stats">
			<div class="c2_stat"><span><?=t::w('c2 Sets', 'Сеттер')?></span><b><?=count($sets)?></b></div>
			<div class="c2_stat c2_stat_neg"><span><?=t::w('c2 Sold at a loss', 'Шығынмен сатылады')?></span><b><?=$n_bad?></b></div>
			<div class="c2_stat c2_stat_warn"><span><?=t::w('c2 Profit under 5%', 'Пайдасы 5%-дан аз')?></span><b><?=$n_low?></b></div>
			<div class="c2_stat"><span><?=t::w('c2 Average profit', 'Орташа пайда')?></span><b><?=$avg?>%</b></div>
			<div class="c2_stat"><span><?=t::w('c2 Cost per order', 'Бір заказға шығын')?></span><b><?=$f($over_u)?> ₸</b></div>
		</div>

		<!-- граммовка: Тағамдар тізіміндегі сүзгімен бірдей сандар (products/cost2/gram.php) -->
		<div class="c2_gram">
			<span><?=t::w('pl Grammovka', 'Граммовка')?>:</span>
			<a href="/products/" class="c2_gram_i"><?=t::w('All', 'Барлығы')?> <b><?=$gcnt['all']?></b></a>
			<a href="/products/#g=ok" class="c2_gram_i c2_gram_ok"><?=t::w('pl Gram ok', 'Толық')?> <b><?=$gcnt['ok']?></b></a>
			<a href="/products/#g=part" class="c2_gram_i c2_gram_part"><?=t::w('pl Gram part', 'Толық емес')?> <b><?=$gcnt['part']?></b></a>
			<a href="/products/#g=none" class="c2_gram_i c2_gram_none"><?=t::w('pl Gram none', 'Жоқ')?> <b><?=$gcnt['none']?></b></a>
		</div>

		<!-- баға стандарттары: /products/cost/get.php?setting -->
		<div class="c2_card c2_std<?=$fact ? ' c2_std_fact' : ''?>">
			<div class="c2_stdh">
				<div class="c2_h"><?=t::w('c2 Price standards', 'Баға стандарттары')?> <small><?=t::w('c2 per order, tg', 'бір заказға, тг')?></small></div>
				<div class="st_page c2_pfw"><div class="st_seg c2_pf">
					<a class="st_seg_i<?=$fact ? '' : ' st_seg_act'?>" data-ajx=".c2_page" data-m="plan" href="/products/cost2/"><?=t::w('c2 Plan', 'План')?></a><a class="st_seg_i<?=$fact ? ' st_seg_act' : ''?>" data-ajx=".c2_page" data-m="fact" href="/products/cost2/?f=1"><?=t::w('c2 Fact', 'Факт')?></a>
				</div></div>
			</div>
			<? if ($fact): ?>
			<div class="c2_fact_n<?=$fact_empty ? ' c2_fact_e' : ''?>">
				<span><i class="fal fa-<?=$fact_empty ? 'exclamation-circle' : 'pen'?>"></i> <?=$fact_empty ? t::w('c2 Fact empty', 'Факт әлі толтырылмаған: есеп План мәндерімен жүреді. Нақты мәндерді жазып, «Сақтау» басыңыз.') : t::w('c2 Fact manual', 'Факт мәндері қолмен толтырылады, План өзгермейді. Бос өріс — План мәні (сұр).')?></span>
				<button type="button" class="c2_fauto"><i class="fal fa-magic"></i> <?=t::w('c2 Fill from last month', 'Соңғы айдың нақты деректерімен толтыру')?></button>
			</div>
			<? endif ?>
			<form class="c2_stf" autocomplete="off" data-mode="<?=$fact ? 'fact' : 'plan'?>">
			<? foreach (['orders_day' => t::w('c2 Orders per day', 'Заказ / күн'), 'salary' => t::w('c2 Salary', 'Айлық'), 'rent' => t::w('c2 Rent', 'Аренда'), 'utility' => t::w('c2 Utilities', 'К.у.'), 'delivery' => t::w('c2 Delivery', 'Доставка'), 'ads' => t::w('c2 Advertising', 'Реклама'), 'markup' => t::w('c2 Markup', 'Наценка').', %'] as $k => $l): $gp = $fact && isset($plan_k[$k]); ?>
				<label<?=$gp ? ' class="c2_isplan"' : ''?>><span><?=$h($l)?><?=$gp ? ' <i class="c2_pl">'.t::w('c2 plan value', 'жоспар').'</i>' : ''?></span><input class="c2_in" name="<?=$k?>" inputmode="decimal" value="<?=$fact ? ($sf && $sf[$k] !== null ? $h($sf[$k] + 0) : '') : $h($st[$k] + 0)?>"<?=$fact ? ' placeholder="'.$h($st[$k] + 0).'"' : ''?>></label>
			<? endforeach ?>
				<button type="submit" class="c2_std_ok"><i class="far fa-check"></i> <?=t::w('c2 Save', 'Сақтау')?></button>
			</form>
			<div class="c2_std_n"><i class="fal fa-info-circle"></i> <?=$h(str_replace(['{over}', '{mk}'], [$f($over_u), $stu['markup'] + 0], t::w('c2 Standards hint', 'Сеттің толық себесі = тағамдар себесі + {over} тг (айлық, аренда, к.у., доставка, реклама). Ұсынылатын баға = толық себес + {mk}%, сосын 490 / 990-ға дейін дөңгелектенеді.')))?></div>
		</div>

		<!-- 1. Қорытынды кесте (Google кестесінің оң жағы) -->
		<div class="c2_card" id="c2_sec_sets">
			<div class="c2_h"><?=t::w('c2 Sets: final cost', 'Сеттер: қорытынды себес')?> <span><?=count($sets)?></span></div>
			<div class="c2_wrap"><table class="c2_tbl c2_sum">
				<thead><tr>
					<th>№</th><th class="c2_l c2_srt" data-st="t"><?=t::w('c2 Set', 'Сет')?></th><th class="c2_srt" data-st="n"><?=t::w('c2 Ingredients', 'Ингредиент')?></th>
					<th><?=t::w('c2 Salary', 'Айлық')?></th><th><?=t::w('c2 Rent', 'Аренда')?></th><th><?=t::w('c2 Utilities', 'К.у.')?></th><th><?=t::w('c2 Delivery', 'Доставка')?></th><th><?=t::w('c2 Advertising', 'Реклама')?></th>
					<th class="c2_hl c2_srt" data-st="n"><?=t::w('c2 Total cost', 'Толық себес')?></th><th class="c2_srt" data-st="n"><?=t::w('c2 Markup', 'Наценка')?> <?=$stu['markup'] + 0?>%</th><th class="c2_srt" data-st="n"><?=t::w('c2 Total', 'Наценкамен')?></th>
					<th class="c2_hl c2_srt" data-st="n"><?=t::w('c2 Suggested', 'Ұсыныс')?></th><th class="c2_srt" data-st="n"><?=t::w('c2 Current price', 'Қазіргі баға')?></th><th class="c2_srt" data-st="n"><?=t::w('c2 Profit', 'Пайда')?></th>
				</tr></thead>
				<tbody>
				<? $n = 0; foreach ($sets as $x): $n++; $pc = $x['profit'] < 0 ? 'c2_neg' : ($x['pct'] < 5 ? 'c2_warn' : 'c2_pos'); ?>
					<tr class="c2_f" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<td class="c2_mut"><?=$n?></td>
						<td class="c2_l c2_nm" data-v="<?=$h($x['name'])?>"><a href="#s<?=$x['id']?>" class="c2_go"><?=$h($x['name'])?></a><?=$x['arh'] ? ' <span class="c2_tag">'.t::w('c2 archive', 'архив').'</span>' : ''?><?=$x['miss'] ? ' <span class="c2_tag c2_tag_r" title="'.$h(t::w('c2 Some items have no cost', 'Кейбір затының себесі жоқ')).'">!</span>' : ''?></td>
						<td data-v="<?=round($x['sum'], 2)?>"><?=$f($x['sum'])?></td>
						<? foreach (['salary', 'rent', 'utility', 'delivery', 'ads'] as $k): ?><td class="c2_mut<?=$fact && isset($plan_k[$k]) ? ' c2_isplan' : ''?>"><?=$f($stu[$k])?></td><? endforeach ?>
						<td class="c2_hl" data-v="<?=round($x['full'], 2)?>"><b><?=$f($x['full'])?></b></td><td class="c2_mut" data-v="<?=round($x['mk'], 2)?>"><?=$f($x['mk'])?></td><td data-v="<?=round($x['total'], 2)?>"><?=$f($x['total'])?></td>
						<td class="c2_hl" data-v="<?=(int)$x['nice']?>"><b><?=$f($x['nice'])?></b></td><td data-v="<?=(int)$x['price']?>"><?=$f($x['price'])?></td>
						<td data-v="<?=round($x['profit'], 2)?>"><span class="c2_p <?=$pc?>"><?=$f($x['profit'])?> <small><?=(int)round($x['pct'])?>%</small></span></td>
					</tr>
				<? endforeach ?>
				</tbody>
			</table></div>
		</div>

		<!-- 2. Кесте: Шикізат → Дайындамалар → Тағамдар → Сеттер. Сұр астын сызылған мәнді басып өзгертуге болады -->
		<?
			// өзгертілетін ұяшық: [әрекет, id, өріс] + ағымдағы мән
			$E = function ($e, $v) use ($h) { return $e ? ' class="c2_e" data-a="'.$e[0].'" data-id="'.$e[1].'" data-f="'.$e[2].'" data-v="'.$h($v).'"' : ''; };
			$X = function ($a, $id) use ($h) { return '<button type="button" class="c2_x" data-a="'.$a.'" data-id="'.$id.'" title="'.$h(t::w('c2 Delete', 'Өшіру')).'"><i class="fal fa-times"></i></button>'; };
			$ADD = function ($a, $to, $kind) { return '<tr class="c2_add" data-a="'.$a.'" data-to="'.$to.'" data-k="'.$kind.'"><td colspan="4"><button type="button" class="c2_add_b"><i class="far fa-plus"></i> '.t::w('c2 Add item', 'Зат қосу').'</button></td></tr>'; };
			// ЖИ жалғастыру батырмасы (бағандағы, санымен) және жолдағы кішкене ✨
			$CB = function ($k, $n, $txt, $ico = 'fa-sparkles') use ($h, $ai_ok) { return $ai_ok && $n ? '<button type="button" class="c2_semifix c2_cont" data-k="'.$k.'"><i class="fal '.$ico.'"></i> <span>'.$h(str_replace('{n}', $n, $txt)).'</span></button>' : ''; };
			$CS = function ($k, $id) use ($h, $ai_ok) { return $ai_ok ? '<button type="button" class="c2_ai_set c2_cont" data-k="'.$k.'" data-id="'.(int)$id.'" title="'.$h(t::w('c2 AI continue hint', 'ЖИ осы жерден жалғастырады: барын өзгертпейді, жетпегенін толтырады')).'"><i class="fal fa-sparkles"></i> '.t::w('c2 AI continue', 'ЖИ жалғастыру').'</button>' : ''; };
			$AIT = '<span class="c2_aitag" title="'.$h(t::w('c2 AI filled, check', 'ЖИ толтырды, тексеріңіз')).'">ЖИ</span>';
			$NM = function ($r) use ($h, $aim, $AIT) {
				$t = $r['lnk'] ? '<a href="#'.$r['lnk'].'" class="c2_go">'.$h($r['name']).'</a>' : $h($r['name']);
				$k = $r['lnk'] ? $r['lnk'][0] : ''; $i = (int)substr((string)$r['lnk'], 1);
				return $t.(($k == 'd' && isset($aim['p'][$i])) || ($k == 'r' && isset($aim['r'][$i])) || (!empty($r['cid']) && isset($aim['c'][$r['cid']])) ? ' '.$AIT : '');
			};
		?>
		<div class="c2_grid">

			<div class="c2_col">
				<div class="c2_ch c2_ch1"><b>1</b> <?=t::w('c2 Raw materials (purchase)', 'Шикізат (закуп)')?> <span><?=count($used_raw)?></span></div>
				<?=($brk['price'] ?? []) || ($brk['link'] ?? []) ? '<div class="c2_pbtn">'.$CB('price', count($brk['price'] ?? []), t::w('c2 Cont price', 'Бағасы жоқ {n} шикізатқа ЖИ бағасы'), 'fa-tags').$CB('link', count($brk['link'] ?? []), t::w('c2 Cont link', 'Закупқа байланбаған {n} шикізатты байлау'), 'fa-link').'</div>' : ''?>
				<? if ($n_dup || $n_tc): ?><div class="c2_pbtn">
					<? if ($n_dup): ?><button type="button" class="c2_semifix c2_rawdup"><i class="fal fa-clone"></i> <span><?=$h(str_replace('{n}', $n_dup, t::w('c2 Dup btn', 'Қайталанған {n} шикізатты біріктіру')))?></span></button><? endif ?>
					<? if ($n_tc): ?><button type="button" class="c2_semifix c2_rawdup" data-sec="type"><i class="fal fa-folder-tree"></i> <span><?=$h(str_replace('{n}', $n_tc, t::w('c2 Type btn', 'Ұқсас шикізатты {n} түрге жинау')))?></span></button><? endif ?>
				</div><? endif ?>
				<div class="c2_card c2_blk">
					<table class="c2_tbl c2_raw">
						<thead><tr><th class="c2_l"><?=t::w('c2 Name', 'Атауы')?></th><th><?=t::w('c2 Unit', 'Өлш.')?></th><th><?=t::w('c2 Price', 'Бағасы')?></th></tr></thead>
						<tbody>
						<? foreach ($rtypes as $tp): $vs = array_filter($used_raw, function ($r) use ($tp) { return $r['type'] == $tp['id']; }); ?>
							<tr class="c2_rt c2_f" data-t="<?=$tp['id']?>" data-q="<?=$h(mb_strtolower($tp['name'].' '.implode(' ', array_column($vs, 'name'))))?>">
								<td class="c2_l"><button type="button" class="c2_rt_tg" aria-label="▸"><i class="far fa-angle-right"></i></button><b<?=$E(['rtype', $tp['id'], 'name'], $tp['name'])?>><?=$h($tp['name'])?></b>
									<small class="c2_rt_n"><?=$h(str_replace('{n}', count($vs), t::w('c2 Type variants', '{n} нұсқа')))?></small>
									<span class="c2_rt_a"><span<?=$E(['rtype', $tp['id'], 'area'], $tp['area'])?>><?=$tp['area'] !== '' ? $h($tp['area']) : '+ '.t::w('c2 Type area', 'аймақ')?></span></span></td>
								<td><?=$h($tp['unit'] ?: 'кг')?></td>
								<td><b><?=$pf($tp['price'])?></b><button type="button" class="c2_rt_m" data-id="<?=$tp['id']?>" data-m="<?=$tp['mode'] == 'avg' ? 'last' : 'avg'?>" title="<?=$h(t::w('c2 Type mode hint', 'Түр бағасы: соңғы накладной не нұсқалардың орташасы'))?>"><?=$tp['mode'] == 'avg' ? t::w('c2 Type avg', 'орташа') : t::w('c2 Type last', 'соңғы')?></button></td>
							</tr>
						<? endforeach ?>
						<? uasort($used_raw, function ($a, $b) { return [$a['type'] ? 0 : 1, $a['type'], $a['name']] <=> [$b['type'] ? 0 : 1, $b['type'], $b['name']]; }); ?>
						<? foreach ($used_raw as $r): $d = $r['prev'] ? ($r['lastp'] - $r['prev']) / $r['prev'] * 100 : null; ?>
							<tr class="c2_f<?=$r['type'] ? ' c2_rv c2_rv_off' : ''?>"<?=$r['type'] ? ' data-t="'.$r['type'].'"' : ''?> id="r<?=$r['id']?>" data-q="<?=$h(mb_strtolower($r['name']))?>">
								<td class="c2_l c2_nm"><span<?=$E(['raw', $r['id'], 'name'], $r['name'])?>><?=$h($r['name'])?></span><?=isset($aim['r'][$r['id']]) ? ' '.$AIT : ''?>
									<? if ($r['last_d']): ?><a class="c2_sub c2_sub_a" href="/waybill/raw.php?id=<?=$r['id']?>"><?=date('d.m', strtotime($r['last_d']))?><?=$r['sup'] !== '' ? ' · '.$h($r['sup']) : ''?></a><? endif ?></td>
								<td><span<?=$E(['raw', $r['id'], 'unit'], $r['unit'] ?: 'шт')?>><?=$h($r['unit'] ?: 'шт')?></span></td>
								<td><b<?=$E(['raw', $r['id'], 'price'], $nv($r['price']))?>><?=$pf($r['price'])?></b><? if ($d !== null && abs($d) >= 1): ?><div class="c2_sub <?=$d > 0 ? 'c2_up' : 'c2_dn'?>"><?=$d > 0 ? '▲' : '▼'?> <?=abs(round($d))?>%</div><? endif ?></td>
							</tr>
						<? endforeach ?>
						</tbody>
						<tfoot><tr class="c2_radd"><td colspan="3"><button type="button" class="c2_add_b c2_raw_b"><i class="far fa-plus"></i> <?=t::w('c2 Add raw material', 'Шикізат қосу')?></button></td></tr></tfoot>
					</table>
				</div>
			</div>

			<div class="c2_col" id="c2_col_p">
				<div class="c2_ch c2_ch2"><b>2</b> <?=t::w('c2 Semi-finished', 'Дайындамалар')?> <span><?=count($preps)?></span><button type="button" class="c2_ch_b c2_prep_new" title="<?=$h(t::w('c2 Add semi-finished', 'Дайындама қосу'))?>"><i class="far fa-plus"></i></button></div>
				<div class="c2_pbtn">
					<? if ($gt_semi): ?><button type="button" class="btn gt_semi_btn c2_gt"><i class="fal fa-magic"></i> <span><?=t::w('pp Semis from templates', 'Дайындамаларды шаблоннан толтыру')?></span></button><? endif ?>
					<button type="button" class="c2_pnew c2_prep_new"><i class="far fa-plus"></i> <span><?=t::w('c2 Add semi-finished', 'Дайындама қосу')?></span></button>
					<?=$CB('semi', count($brk['semi'] ?? []), t::w('c2 Cont semi', 'Құрамы жоқ {n} дайындаманы ЖИ-мен толтыру'), 'fa-layer-group')?>
					<? if ($semi_cand): ?><button type="button" class="c2_semifix c2_cont" data-k="flat" title="<?=$h(implode(', ', array_slice(array_column($semi_cand, 'name'), 0, 30)))?>"><i class="fal fa-layer-group"></i> <span><?=$h(str_replace('{n}', count($semi_cand), t::w('c2 Semi fix', 'Шикізатқа жайылған {n} тағамды дайындамаға жинау')))?></span></button><? endif ?>
				</div>
				<form class="c2_card c2_newp" hidden autocomplete="off">
					<input class="c2_in" name="name" placeholder="<?=$h(t::w('c2 Name', 'Атауы'))?>" required>
					<div class="c2_newp_r">
						<select class="c2_in" name="unit"><option>кг</option><option>шт</option><option>л</option></select>
						<input class="c2_in" name="yield" inputmode="decimal" placeholder="<?=$h(t::w('c2 Yield', 'Шығымы'))?>" required>
						<button class="c2_ok" type="submit"><i class="far fa-check"></i></button>
					</div>
				</form>
				<? if (!$preps): ?><button type="button" class="c2_card c2_empty c2_prep_new"><i class="far fa-plus"></i> <?=t::w('c2 Add semi-finished', 'Дайындама қосу')?></button><? endif ?>
				<? foreach ($preps as $x): ?>
					<div class="c2_card c2_blk c2_f" id="p<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><b<?=$E(['prep', $x['id'], 'name'], $x['name'])?>><?=$h($x['name'])?></b><span>₸ / <?=$h($x['unit'])?></span><? if (!empty($has_pp)): ?><button type="button" class="c2_pp" data-id="<?=$x['id']?>" title="<?=$h(t::w('c2 Edit', 'Өзгерту'))?>"><i class="fal fa-pen"></i></button><? endif ?></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr><td class="c2_l"><?=$X('part_del', $r['id'])?><?=$NM($r)?></td><td><span<?=$E(['part', $r['id'], ''], $r['qv'])?>><?=$h($r['qty'])?></span></td><td class="c2_mut"><span<?=$E($r['pe'], $nv($r['price']))?>><?=$pf($r['price'])?></span></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							<?=$ADD('part_add', $x['id'], 'rc')?>
							</tbody>
							<tfoot><tr><td class="c2_l"><?=t::w('c2 Yield', 'Шығымы')?> <span<?=$E(['prep', $x['id'], 'yield'], $nv($x['yield']))?>><?=$x['yield'] + 0?></span> <?=$h($x['unit'])?></td><td></td><td class="c2_mut"><?=$f($x['sum'])?></td><td class="c2_hl"><b><?=$pf($x['cost'])?></b></td></tr></tfoot>
						</table>
					</div>
				<? endforeach ?>
			</div>

			<div class="c2_col" id="c2_col_d">
				<div class="c2_ch c2_ch3"><b>3</b> <?=t::w('c2 Dishes (grams)', 'Тағамдар (граммовка)')?> <span><?=count($dishes)?></span></div>
				<?=($brk['dish'] ?? []) || ($brk['kind'] ?? []) || ($brk['goods'] ?? []) ? '<div class="c2_pbtn">'.$CB('kind', count($brk['kind'] ?? []), t::w('c2 Cont kind', 'Түрі белгісіз {n} тағамның түрін ЖИ анықтау'), 'fa-tags').$CB('dish', count($brk['dish'] ?? []), t::w('c2 Cont dish', 'Граммовкасы жоқ {n} позицияны ЖИ-мен толтыру'), 'fa-utensils').$CB('goods', count($brk['goods'] ?? []), t::w('c2 Cont goods', 'Тікелей шикізат {n} позицияны шикізатқа байлау'), 'fa-wine-bottle').'</div>' : ''?>
				<? foreach ($dishes as $x): $pr = $x['price'] ? ($x['price'] - $x['sum']) / $x['price'] * 100 : 0; ?>
					<? $xk = $km[$x['id']] ?? 0; ?>
					<div class="c2_card c2_blk c2_f<?=$x['rows'] ? '' : ' c2_blk_e'?><?=$xk == 4 ? ' c2_blk_none' : ''?>" data-k="<?=$xk?>"<?=isset($miss_ids[$x['id']]) ? ' data-miss="1"' : ''?> id="d<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><? if ($kind_on): ?><input type="checkbox" class="c2_ksel" value="<?=$x['id']?>" aria-label="✓"><? endif ?><a href="/products/item/?id=<?=$x['id']?>"><?=$h($x['name'])?></a><?=isset($aim['p'][$x['id']]) ? ' '.$AIT : ''?><? if ($x['price']): ?><span><?=$f($x['price'])?> ₸</span><? endif ?><?=isset($bk['dish'][$x['id']]) ? $CS('dish', $x['id']) : (isset($bk['flat'][$x['id']]) ? $CS('flat', $x['id']) : '')?><? if ($kind_on): ?><span class="st_page c2_kw"><span class="st_seg c2_kseg" data-id="<?=$x['id']?>"><? foreach ([1 => 'fa-box-full', 2 => 'fa-hamburger', 3 => 'fa-wine-bottle', 4 => 'fa-ban'] as $kk => $ico): ?><button type="button" class="st_seg_i<?=$xk == $kk ? ' st_seg_act' : ''?>" data-k="<?=$kk?>" title="<?=$h($kn[$kk])?>"><i class="fal <?=$ico?>"></i></button><? endforeach ?></span></span><? endif ?><?=$xk == 3 && !$x['rows'] ? '<button type="button" class="c2_ai_set c2_glink" data-id="'.$x['id'].'" data-name="'.$h($x['name']).'"><i class="fal fa-link"></i> '.t::w('gl Link btn', 'Шикізатпен байланыстыру').'</button>' : ''?></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr<?=($x['price'] > 0 && $r['sum'] > $x['price'] * 0.8) || $r['sum'] > 8000 ? ' class="c2_sus" title="'.$h(t::w('c2 Suspicious line', 'Құны тым үлкен: мөлшер не бірлік (г / кг) қате болуы мүмкін')).'"' : ''?>><td class="c2_l"><?=$X('pi_del', $r['id'])?><?=$NM($r)?></td><td><span<?=$E(['pi', $r['id'], ''], $r['qv'])?>><?=$h($r['qty'])?></span></td><td class="c2_mut"><span<?=$E($r['pe'], $nv($r['price']))?>><?=$pf($r['price'])?></span></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							<?=$ADD('pi_add', $x['id'], 'rc')?>
							</tbody>
							<? if ($x['rows']): ?><tfoot><tr><td class="c2_l"><?=t::w('c2 Cost', 'Себес')?></td><td></td><td class="c2_mut"><?=$x['price'] ? round(100 - $pr).'%' : ''?></td><td class="c2_hl"><b><?=$f($x['sum'])?></b></td></tr></tfoot><? endif ?>
						</table>
					</div>
				<? endforeach ?>
			</div>

			<div class="c2_col">
				<div class="c2_ch c2_ch4"><b>4</b> <?=t::w('c2 Sets', 'Сеттер')?> <span><?=count($sets)?></span><button type="button" class="c2_ch_b c2_set_new" title="<?=$h(t::w('c2 Add set', 'Сет қосу'))?>"><i class="far fa-plus"></i></button></div>
				<?=($brk['set'] ?? []) || ($brk['item'] ?? []) ? '<div class="c2_pbtn">'.$CB('set', count($brk['set'] ?? []), t::w('c2 Cont set', 'Құрамы жоқ {n} сетті ЖИ-мен толтыру'), 'fa-images').$CB('item', count($brk['item'] ?? []), t::w('c2 Cont item', 'Позиция емес заты бар {n} сетті ЖИ-мен толтыру'), 'fa-sparkles').'</div>' : ''?>
				<form class="c2_card c2_newp c2_news" hidden autocomplete="off">
					<input class="c2_in" name="set" list="c2_sdl" placeholder="<?=$h(t::w('c2 Menu dish', 'Мәзірдегі тағам'))?>" required><datalist id="c2_sdl"></datalist>
					<div class="c2_newp_r">
						<input class="c2_in" name="item" list="c2_sil" placeholder="<?=$h(t::w('c2 First item', 'Бірінші зат'))?>" required><datalist id="c2_sil"></datalist>
						<input class="c2_in c2_sq" name="q" inputmode="decimal" value="1" required>
						<button class="c2_ok" type="submit"><i class="far fa-check"></i></button>
					</div>
				</form>
				<? foreach ($empty_sets as $es): ?>
					<div class="c2_card c2_blk c2_f c2_blk_e" id="s<?=$es[0]?>" data-q="<?=$h(mb_strtolower($es[1]))?>">
						<div class="c2_bh"><a href="/products/item/?id=<?=$es[0]?>"><?=$h($es[1])?></a><?=$CS('set', $es[0])?></div>
						<div class="c2_es_n"><?=t::w('c2 Set has no items', 'Сеттің құрамы жоқ')?></div>
					</div>
				<? endforeach ?>
				<? if (!$sets && !$empty_sets): ?><button type="button" class="c2_card c2_empty c2_set_new"><i class="far fa-plus"></i> <?=t::w('c2 Add set', 'Сет қосу')?></button><? endif ?>
				<? foreach ($sets as $x): ?>
					<div class="c2_card c2_blk c2_f" id="s<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><a href="/products/item/?id=<?=$x['id']?>"><?=$h($x['name'])?></a><? if ($x['price']): ?><span><?=$f($x['price'])?> ₸</span><? endif ?><? if (isset($bk['item'][$x['id']])): ?><?=$CS('item', $x['id'])?><? endif ?></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr<?=$r['miss'] ? ' class="c2_miss"' : ''?>><td class="c2_l"><?=$X('si_del', $r['id'])?><?=$NM($r)?></td><td>× <span<?=$E(['si', $r['id'], ''], $r['qv'])?>><?=$r['qty']?></span></td><td class="c2_mut"><span<?=$E($r['pe'], $nv($r['price']))?>><?=$f($r['price'])?></span></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							<?=$ADD('si_add', $x['id'], 'drc')?>
							</tbody>
							<tfoot><tr><td class="c2_l"><?=t::w('c2 Ingredients', 'Ингредиент')?></td><td></td><td></td><td class="c2_hl"><b><?=$f($x['sum'])?></b></td></tr></tfoot>
						</table>
					</div>
				<? endforeach ?>
			</div>

		</div>
		<div class="c2_opt" hidden data-o="<?=$h(json_encode($OPT, JSON_UNESCAPED_UNICODE))?>"></div>
	<? endif ?>
	</div>
	<? if (!empty($has_pp)) include __DIR__.'/../cost/prep_pop.php'; ?>

	<script>
	// Себес: іздеу, блокқа өту, ұяшықты басып өзгерту, жол қосу/өшіру. Сақталғаннан кейін .c2_page AJAX-пен жаңарады (fun.js ajx)
	(function (f) { if (document.readyState == 'loading') document.addEventListener('DOMContentLoaded', f); else f(); })(function () {
		if (window.c2Init) return; window.c2Init = 1;
		var T = {
			saved: <?=json_encode(t::w('c2 Saved', 'Сақталды'))?>, err: <?=json_encode(t::w('c2 Error, try again', 'Қате, қайта көріңіз'))?>,
			net: <?=json_encode(t::w('c2 Connection error', 'Байланыс қатесі'))?>, pick: <?=json_encode(t::w('c2 Choose from the list', 'Тізімнен таңдаңыз'))?>,
			qty: <?=json_encode(t::w('c2 Enter quantity', 'Мөлшерін жазыңыз'))?>, name: <?=json_encode(t::w('c2 Enter name', 'Атауын жазыңыз'))?>,
			exists: <?=json_encode(t::w('c2 This name already exists', 'Мұндай атау бар'))?>, yield: <?=json_encode(t::w('c2 Enter yield', 'Шығымын жазыңыз'))?>,
			self: <?=json_encode(t::w('c2 Cannot contain itself', 'Өз құрамына кіре алмайды'))?>, del: <?=json_encode(t::w('c2 Delete this row?', 'Бұл жолды өшіру керек пе? Тағы басыңыз'))?>,
			item: <?=json_encode(t::w('c2 Item', 'Зат'))?>, price: <?=json_encode(t::w('c2 Price', 'Бағасы'))?>, nm: <?=json_encode(t::w('c2 Name', 'Атауы'))?>
		};
		var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
		var W = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' || u == 'л'; };
		var uLab = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' ? 'г' : (u == 'л' ? 'мл' : (u || 'шт')); };
		var qv = function () { var el = document.querySelector('.c2_q'); return el ? el.value : ''; };
		function filter() {
			var v = qv().trim().toLowerCase();
			document.querySelectorAll('.c2_f').forEach(function (el) { el.hidden = v !== '' && el.dataset.q.indexOf(v) < 0; });
		}
		function reload() { var keep = qv(); ajx('.c2_page', function () { var el = document.querySelector('.c2_q'); if (el && keep) { el.value = keep; filter(); } sortApply(); }); }

		// қорытынды кестені сұрыптау: бағанның атауын басу (сандар алдымен кемуі, атау өсуі), таңдау localStorage c2_sort-та
		var SK = 'c2_sort';
		var sortGet = function () { try { return JSON.parse(localStorage.getItem(SK) || 'null'); } catch (e) { return null; } };
		var sortSet = function (v) { try { localStorage.setItem(SK, JSON.stringify(v)); } catch (e) {} };
		function sortApply() {
			var tb = document.querySelector('.c2_sum tbody'), s = sortGet(); if (!tb) return;
			var ths = document.querySelectorAll('.c2_sum thead th');
			ths.forEach(function (th) { th.classList.remove('c2_srt_a', 'c2_srt_d'); });
			if (!s || !ths[s.c] || !ths[s.c].dataset.st) return;
			var txt = ths[s.c].dataset.st == 't', dir = s.d == 'a' ? 1 : -1;
			ths[s.c].classList.add(s.d == 'a' ? 'c2_srt_a' : 'c2_srt_d');
			var rows = Array.prototype.slice.call(tb.rows), val = function (tr) { var td = tr.cells[s.c]; return td ? td.dataset.v || '' : ''; };
			rows.sort(function (a, b) { var x = val(a), y = val(b); return dir * (txt ? x.localeCompare(y, ['kk', 'ru'], { sensitivity: 'base' }) : (parseFloat(x) || 0) - (parseFloat(y) || 0)); });
			rows.forEach(function (tr, i) { tr.cells[0].textContent = i + 1; tb.appendChild(tr); });
		}
		$(document).on('click', '.c2_sum th.c2_srt', function () {
			var c = this.cellIndex, s = sortGet();
			var d = s && s.c == c ? (s.d == 'a' ? 'd' : 'a') : (this.dataset.st == 't' ? 'a' : 'd');
			sortSet({ c: c, d: d }); sortApply();
		});
		sortApply(); $(document).on('ajx:done', sortApply);

		// ескі Себес сілтемелері: ?prep=N — дайындама терезесі, #missing — граммовкасы жоқтар, #prep — дайындамалар
		window.addEventListener('load', function () {
			var m = location.search.match(/[?&]prep=(\d+)/), h = location.hash.slice(1);
			if (m) { if (typeof window.openPrep == 'function') window.openPrep(+m[1]); else { var b = document.getElementById('p' + m[1]); if (b) b.scrollIntoView({ block: 'center' }); } }
			else if (h == 'missing') { var t = document.querySelector('.c2_tab_miss'); if (t) t.click(); }
			else if (h == 'prep') { var c = document.getElementById('c2_col_p'); if (c) c.scrollIntoView({ block: 'start' }); }
			// Тағамдар тізіміндегі белгіден: #d123 тағам, #s123 сет — сол блокқа апарып, жарқ етеді
			else if (/^[ds]\d+$/.test(h)) { var t = document.getElementById(h); if (t) { t.hidden = false; t.scrollIntoView({ block: 'center' }); t.classList.remove('c2_flash'); void t.offsetWidth; t.classList.add('c2_flash'); } }
		});
		function send(a, data, ok) {
			var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
			return fetch('/products/cost2/get.php?a=' + a, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
				t = t.trim();
				if (t == 'yes' || t.indexOf('id:') === 0) { mess(T.saved); if (ok) ok(t); reload(); return true; }
				mess(T[t] || T.err, 'warn'); return false;
			}).catch(function () { mess(T.net, 'err'); return false; });
		}
		$(document).on('input', '.c2_q', filter);
		$(document).on('click', '.c2_go', function (e) {
			var t = document.getElementById(this.getAttribute('href').slice(1));
			if (!t) return;
			e.preventDefault();
			if (t.hidden) { document.querySelector('.c2_q').value = ''; filter(); }
			t.scrollIntoView({ behavior: 'smooth', block: 'center' });
			t.classList.remove('c2_flash'); void t.offsetWidth; t.classList.add('c2_flash');
		});

		// ұяшықты өзгерту: басқанда input шығады, Enter/сыртқа басу — сақтау, Esc — болдырмау
		$(document).on('click', '.c2_e', function (e) {
			var el = this; if (el.querySelector('input,select')) return;
			e.preventDefault(); e.stopPropagation();
			var old = el.innerHTML, f = el.dataset.f, inp;
			if (f == 'unit') { inp = document.createElement('select'); inp.innerHTML = '<option>кг</option><option>шт</option><option>л</option>'; inp.value = el.dataset.v; }
			else { inp = document.createElement('input'); inp.value = el.dataset.v; if (f != 'name') inp.inputMode = 'decimal'; }
			inp.className = 'c2_ein' + (f == 'name' ? ' c2_ein_w' : '');
			el.innerHTML = ''; el.appendChild(inp); inp.focus(); if (inp.select) inp.select();
			var done = false;
			var close = function (save) {
				if (done) return; done = true;
				var v = inp.value.trim();
				if (!save || v === el.dataset.v || v === '') { el.innerHTML = old; return; }
				el.innerHTML = esc(v);
				send(el.dataset.a, { id: el.dataset.id, f: f, v: v }).then(function (ok) { if (!ok) el.innerHTML = old; });
			};
			inp.addEventListener('keydown', function (ev) { if (ev.key == 'Enter') { ev.preventDefault(); close(true); } if (ev.key == 'Escape') close(false); });
			inp.addEventListener('blur', function () { close(true); });
			if (f == 'unit') inp.addEventListener('change', function () { close(true); });
		});

		// жолды өшіру (екінші басу растайды)
		$(document).on('click', '.c2_x', function (e) {
			e.preventDefault(); e.stopPropagation();
			var b = this;
			if (!b.classList.contains('c2_x_ask')) { b.classList.add('c2_x_ask'); b.title = T.del; setTimeout(function () { b.classList.remove('c2_x_ask'); }, 2500); return; }
			send(b.dataset.a, { id: b.dataset.id });
		});

		// «+ Зат қосу»: тізімнен іздеп таңдау + мөлшер
		var dl = 0;
		function opts(k) {
			var O = JSON.parse(document.querySelector('.c2_opt').dataset.o), res = [];
			k.split('').forEach(function (t) { res = res.concat(O[t] || []); });
			return res;
		}
		$(document).on('click', '.c2_add_b:not(.c2_raw_b)', function () {
			var tr = this.closest('.c2_add'), list = opts(tr.dataset.k), id = 'c2dl' + (++dl);
			var td = tr.firstElementChild;
			td.innerHTML = '<div class="c2_addf"><input class="c2_ein c2_ein_w c2_it" list="' + id + '" placeholder="' + esc(T.item) + '"><datalist id="' + id + '">' +
				list.map(function (x) { return '<option value="' + esc(x.n) + '">'; }).join('') + '</datalist>' +
				'<input class="c2_ein c2_aq" inputmode="decimal"><span class="c2_au"></span><button type="button" class="c2_ok"><i class="far fa-check"></i></button></div>';
			var it = td.querySelector('.c2_it'), aq = td.querySelector('.c2_aq'), au = td.querySelector('.c2_au');
			var find = function () { var v = it.value.trim().toLowerCase(); for (var i = 0; i < list.length; i++) if (list[i].n.trim().toLowerCase() == v) return list[i]; return null; };
			var isSet = tr.dataset.a == 'si_add';
			it.addEventListener('input', function () { var x = find(); au.textContent = x ? (isSet ? 'дана' : uLab(x.u)) : ''; });
			it.focus();
			var go = function () {
				var x = find(); if (!x) { mess(T.pick, 'warn'); it.focus(); return; }
				var q = parseFloat(aq.value.replace(',', '.')); if (!(q > 0)) { mess(T.qty, 'warn'); aq.focus(); return; }
				send(tr.dataset.a, { to: tr.dataset.to, item: x.c, q: aq.value });
			};
			td.querySelector('.c2_ok').addEventListener('click', go);
			aq.addEventListener('keydown', function (ev) { if (ev.key == 'Enter') { ev.preventDefault(); go(); } });
			it.addEventListener('keydown', function (ev) { if (ev.key == 'Enter') { ev.preventDefault(); aq.focus(); } });
		});

		// жаңа шикізат
		$(document).on('click', '.c2_raw_b', function () {
			var td = this.parentNode;
			td.innerHTML = '<div class="c2_addf"><input class="c2_ein c2_ein_w c2_rn" placeholder="' + esc(T.nm) + '"><select class="c2_ein c2_ru"><option>кг</option><option>шт</option><option>л</option></select>' +
				'<input class="c2_ein c2_rp" inputmode="decimal" placeholder="' + esc(T.price) + '"><button type="button" class="c2_ok"><i class="far fa-check"></i></button></div>';
			var go = function () { send('raw_add', { name: td.querySelector('.c2_rn').value, unit: td.querySelector('.c2_ru').value, price: td.querySelector('.c2_rp').value }); };
			td.querySelector('.c2_ok').addEventListener('click', go);
			td.querySelector('.c2_rp').addEventListener('keydown', function (ev) { if (ev.key == 'Enter') { ev.preventDefault(); go(); } });
			td.querySelector('.c2_rn').focus();
		});

		// жаңа дайындама: Себес бетінің ортақ терезесі (шаблон, ЖИ) болса — сол, болмаса осы жердегі қысқа форма
		$(document).on('click', '.c2_prep_new', function () {
			if (typeof window.openPrep == 'function') { window.openPrep(null); return; }
			var f = document.querySelector('.c2_newp:not(.c2_news)'); f.hidden = !f.hidden; if (!f.hidden) f.querySelector('[name=name]').focus();
		});
		$(document).on('click', '.c2_pp', function () { if (typeof window.openPrep == 'function') window.openPrep(+this.dataset.id); });
		document.addEventListener('prep:saved', function () { reload(); });
		$(document).on('submit', '.c2_newp:not(.c2_news)', function (e) {
			e.preventDefault();
			var f = this;
			send('prep_add', { name: f.name.value, unit: f.unit.value, yield: f.yield.value }, function (t) {
				var id = t.slice(3);
				$(document).one('ajx:done', function () { var b = document.getElementById('p' + id); if (b) { b.scrollIntoView({ block: 'center' }); var a = b.querySelector('.c2_add_b'); if (a) a.click(); } });
			});
		});

		// қойындылар: бөлімге өту + жарықтандыру; «Граммовкасы жоқ» тек сондай тағамдарды қалдырады (қайта басса — бәрі)
		$(document).on('click', '.c2_tab', function () {
			var g = document.querySelector('.c2_grid'), miss = this.dataset.miss == '1';
			var on = miss && !(g && g.classList.contains('c2_only_miss'));
			if (g) g.classList.toggle('c2_only_miss', on);
			document.querySelectorAll('.c2_tab').forEach(function (t) { t.classList.remove('c2_tab_act'); });
			if (on) this.classList.add('c2_tab_act');
			var t = document.getElementById(this.dataset.go); if (!t) return;
			t.scrollIntoView({ behavior: 'smooth', block: 'start' });
			t.classList.remove('c2_flash'); void t.offsetWidth; t.classList.add('c2_flash');
		});

		// баға стандарттары: сақтау, сосын .c2_page AJAX-пен жаңарады
		// План | Факт режимі (localStorage c2_pf: {m: plan|fact}); бет ашылғанда URL-де f жоқ болса, Факт қайта ашылады
		var pfGet = function () { try { return JSON.parse(localStorage.getItem('c2_pf') || '{}') || {}; } catch (e) { return {}; } };
		var pfSet = function (o) { try { var v = pfGet(); for (var k in o) v[k] = o[k]; localStorage.setItem('c2_pf', JSON.stringify(v)); } catch (e) {} };
		$(document).on('click', '.c2_pf [data-m]', function () { pfSet({ m: this.dataset.m }); });
		(function () { var v = pfGet(); if (v.m == 'fact' && !/[?&]f=/.test(location.search) && window.ajxGo) ajxGo('/products/cost2/?f=1', '.c2_page'); })();
		// Факт: соңғы толық айдың тапсырыс / ОПиУ деректерімен инпуттарды толтыру (сақтамайды)
		$(document).on('click', '.c2_fauto', function () {
			var b = this; b.disabled = true;
			fetch('/products/cost2/get.php?a=fact_auto', { method: 'POST', credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				b.disabled = false;
				if (!d || !d.vals) { mess(T.err, 'warn'); return; }
				var f = document.querySelector('.c2_stf'), n = 0;
				for (var k in d.vals) { var i = f.querySelector('[name="' + k + '"]'); if (i) { i.value = d.vals[k]; n++; } }
				mess(<?=json_encode(t::w('c2 Filled from month', 'Толтырылды: {m}, {o} тапсырыс. Тексеріп, «Сақтау» басыңыз.'))?>.replace('{m}', d.title).replace('{o}', d.orders), n ? 'ok' : 'warn');
			}).catch(function () { b.disabled = false; mess(T.net, 'err'); });
		});
		$(document).on('submit', '.c2_stf', function (e) {
			e.preventDefault();
			var b = this.querySelector('.c2_std_ok'); b.disabled = true;
			fetch(this.dataset.mode == 'fact' ? '/products/cost2/get.php?a=fact_setting' : '/products/cost/get.php?setting', { method: 'POST', body: new FormData(this), credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
				b.disabled = false;
				if (t.trim() == 'yes') { mess(T.saved); reload(); } else mess(T.err, 'warn');
			}).catch(function () { b.disabled = false; mess(T.net, 'err'); });
		});

		// «ЖИ-мен жалғастыру»: кез келген ЖИ батырмасы (.c2_cont: data-k түрі, data-id заты; бос k — бәрі) поп-ап ашады.
		// Қадамдар get.php?a=cont_list-тен келеді (төменнен жоғары), әрқайсысы бөлек кішкене сұрау: cont_do (сет құрамы — products/item/set_ai.php, сосын item).
		// Прогресс: мәзір импортындағы тізім стилі (mi_prog, mi_posts, mi_pbar, mi_tm). «Тоқтату» — ағымдағы сұрау біткен соң; келесі жолы қалғаны жалғасады.
		var CL = <?=json_encode([
			'title' => t::w('c2 Cont title', 'ЖИ-мен жалғастыру'), 'stop' => t::w('c2 Cont stop', 'Тоқтату'), 'close' => t::w('c2 Cont close', 'Жабу'),
			'stopping' => t::w('c2 Cont stopping', 'Ағымдағы қадам біткен соң тоқтайды…'), 'none' => t::w('c2 Cont none', 'Жалғастыратын үзіліс жоқ: тізбек толық.'),
			'done' => t::w('c2 Cont done', 'Толтырылды: {n}'), 'errs' => t::w('c2 Cont errors', 'Қате'), 'stopped' => t::w('c2 Cont stopped', 'Тоқтатылды: келесі жолы қалған жерінен жалғасады.'),
			'guess' => t::w('c2 Cont guess', 'Фотосы жоқ сеттің құрамын атауынан болжау'),
			'g' => ['kind' => t::w('c2 Cont g kind', 'Тағам түрі (ЖИ)'), 'goods' => t::w('c2 Cont g goods', 'Тікелей шикізат: 1 шт'), 'link' => t::w('c2 Cont g link', 'Шикізатты закупқа байлау'), 'price' => t::w('c2 Cont g price', 'Шикізат бағасы (интернет)'), 'semi' => t::w('c2 Cont g semi', 'Дайындама рецепттері'),
				'flat' => t::w('c2 Cont g flat', 'Дайындамаға жинау'), 'dish' => t::w('c2 Cont g dish', 'Позиция граммовкасы'), 'item' => t::w('c2 Cont g item', 'Сеттегі позициялар'), 'set' => t::w('c2 Cont g set', 'Сет құрамы (фотодан)')],
		], JSON_UNESCAPED_UNICODE)?>;
		var contBusy = false;
		function contPost(url, data) {
			var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
			return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return { error: T.net }; });
		}
		var guessOn = function () { var x = document.querySelector('.c2_cm_guess input'); return !x || x.checked; };
		function contRun(k, id, title) {
			if (contBusy) return; contBusy = true;
			var m = document.createElement('div'); m.className = 'gt_modal c2_cmod';
			m.innerHTML = '<div class="gt_mbox"><h4>' + esc(title || CL.title) + '</h4><div class="mi_prog"><div class="mi_prog_h"></div><div class="mi_pbar"><div class="mi_pbar_i"></div></div><ol class="mi_posts"></ol></div>'
				+ '<div class="c2_cm_err"></div><div class="c2_cm_sum"></div><div class="gt_mb"><button type="button" class="btn c2_cm_stop">' + esc(CL.stop) + '</button><button type="button" class="btn c2_cm_close" hidden>' + esc(CL.close) + '</button></div></div>';
			document.body.appendChild(m);
			var hd = m.querySelector('.mi_prog_h'), bar = m.querySelector('.mi_pbar_i'), list = m.querySelector('.mi_posts'), errb = m.querySelector('.c2_cm_err'), sum = m.querySelector('.c2_cm_sum');
			var stopB = m.querySelector('.c2_cm_stop'), closeB = m.querySelector('.c2_cm_close');
			var t0 = Date.now(), stop = false, cur = '';
			var sec = function () { var s = Math.round((Date.now() - t0) / 1000); return Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2); };
			var head = function (txt) { cur = txt; hd.innerHTML = '<span>' + esc(txt) + '</span><span class="mi_tm">' + sec() + '</span>'; };
			var tick = setInterval(function () { var x = hd.querySelector('.mi_tm'); if (x) x.textContent = sec(); }, 1000);
			var finish = function (txt) {
				clearInterval(tick); contBusy = false; stopB.hidden = true; closeB.hidden = false; head(txt);
				list.querySelectorAll('.mi_p_on').forEach(function (x) { x.classList.remove('mi_p_on'); }); // тоқтатылса — айналу тоқтайды
			};
			stopB.onclick = function () { stop = true; stopB.disabled = true; stopB.textContent = CL.stopping; };
			closeB.onclick = function () { m.remove(); reload(); };
			head('…');
			contPost('/products/cost2/get.php?a=cont_list', { k: k || '', id: id || 0 }).then(function (d) {
				var st = (d && d.steps) || [];
				if (!st.length) { bar.style.width = '100%'; finish(CL.none); return; }
				// топтар: түрі бойынша бір жол, ішінде ағымдағы зат пен «3/12»
				var groups = [], gi = {};
				st.forEach(function (s) { if (!(s.k in gi)) { gi[s.k] = groups.length; groups.push({ k: s.k, n: 0, done: 0, bad: 0 }); } groups[gi[s.k]].n++; });
				// фотосыз сет болса: «Атауынан болжау» белгісі (әдепкі қосулы)
				if (gi.set !== undefined) { var gw = document.createElement('label'); gw.className = 'c2_cm_guess'; gw.innerHTML = '<input type="checkbox" checked> ' + esc(CL.guess); m.querySelector('.mi_prog').before(gw); }
				list.innerHTML = groups.map(function (g, i) { return '<li data-k="' + g.k + '"><span class="mi_pn">' + (i + 1) + '.</span><span class="mi_pt"><b>' + esc(CL.g[g.k] || g.k) + '</b> <span class="c2_cm_it"></span></span><span class="mi_pd">0/' + g.n + '</span><i class="mi_ps fal"></i></li>'; }).join('');
				var li = function (k) { return list.querySelector('li[data-k="' + k + '"]'); };
				var i = 0, n = 0, usd = 0, tok = 0, errs = [];
				var next = function () {
					if (i >= st.length || stop) {
						bar.style.width = Math.round(i / st.length * 100) + '%';
						sum.innerHTML = esc(CL.done.replace('{n}', n)) + ' · ' + i + '/' + st.length + (tok ? ' · ' + tok.toLocaleString('ru') + ' tok' : '') + (usd ? ' · $' + usd.toFixed(4) : '') + (stop && i < st.length ? '<br>' + esc(CL.stopped) : '');
						finish(CL.title + ' · ' + i + '/' + st.length);
						return;
					}
					var s = st[i], g = groups[gi[s.k]], el = li(s.k);
					list.querySelectorAll('.mi_p_on').forEach(function (x) { if (x !== el) x.classList.remove('mi_p_on'); });
					el.classList.add('mi_p_on'); el.querySelector('.c2_cm_it').textContent = '· ' + s.name; el.querySelector('.mi_pd').textContent = (g.done + g.bad + 1) + '/' + g.n;
					head((i + 1) + '/' + st.length + ' · ' + (CL.g[s.k] || s.k));
					var p = s.k == 'set'
						? contPost('/products/item/set_ai.php', { id: s.id, guess: guessOn() ? 1 : 0 }).then(function (r) { return r && r.ok ? contPost('/products/cost2/get.php?a=cont_do', { k: 'item', id: s.id }) : { error: (r && r.error) || T.err }; })
						: contPost('/products/cost2/get.php?a=cont_do', { k: s.k, id: s.id });
					p.then(function (r) {
						i++;
						if (r && r.ok) { g.done++; n += r.n || 0; usd += +r.usd || 0; tok += +r.tokens || 0; }
						else { g.bad++; errs.push(s.name + ': ' + ((r && r.error && r.error != 'error') ? r.error : T.err)); errb.innerHTML = '<b>' + esc(CL.errs) + ':</b><ul>' + errs.map(function (e) { return '<li>✗ ' + esc(e) + '</li>'; }).join('') + '</ul>'; }
						if (g.done + g.bad >= g.n) { el.classList.remove('mi_p_on'); el.classList.add(g.bad && !g.done ? 'mi_p_err' : 'mi_p_ok'); el.querySelector('.c2_cm_it').textContent = g.bad ? '· ✗ ' + g.bad : ''; }
						bar.style.width = Math.round(i / st.length * 100) + '%';
						next();
					});
				};
				next();
			});
		}
		// Қайталанған шикізатты біріктіру (get.php?a=dup_list / dup_merge): топтар құсбелгімен, әр топ бөлек сұраумен (транзакция), прогресс сол поп-апта
		var DL = <?=json_encode(['title' => t::w('c2 Dup title', 'Қайталанған шикізатты біріктіру'), 'go' => t::w('c2 Dup go', 'Біріктіру'), 'main' => t::w('c2 Dup main', 'негізгі'),
			'hint' => t::w('c2 Dup hint', 'Көлемі не салмағы әртүрлі болса да бір шикізат болады. Барлық рецепт, накладной, қалдық негізгіге көшеді, қалғаны өшеді. Керек емес топтың белгісін алыңыз.'),
			'done' => t::w('c2 Dup done', 'Біріктірілді: {n} шикізат'), 'none' => t::w('c2 Dup none', 'Қайталанған шикізат жоқ.'),
			'tt' => t::w('c2 Type title', 'Бір түрге жинау'), 'tgo' => t::w('c2 Type go', 'Түрге жинау'), 'thint' => t::w('c2 Type hint', 'Әртүрлі бренд / нұсқа бөлек қалады, бірақ бір түр болып көрінеді (Ун ▸ 3 нұсқа). Рецептте түр бағасы (соңғы накладной не орташа) қолданылады.'),
			'tdone' => t::w('c2 Type done', 'Түрге жиналды: {n} шикізат')], JSON_UNESCAPED_UNICODE)?>;
		$(document).on('click', '.c2_rawdup', function () {
			if (contBusy) return; contBusy = true;
			var m = document.createElement('div'); m.className = 'gt_modal c2_cmod';
			var sec = this.dataset.sec || '';
			m.innerHTML = '<div class="gt_mbox"><h4>' + esc(DL.title) + '</h4><p class="c2_dup_h">' + esc(DL.hint) + '</p><div class="mi_prog"><div class="mi_prog_h"></div><div class="mi_pbar"><div class="mi_pbar_i"></div></div><ol class="mi_posts c2_dup_l"></ol></div>'
				+ '<div class="c2_typ" hidden><h4>' + esc(DL.tt) + '</h4><p class="c2_dup_h">' + esc(DL.thint) + '</p><ol class="mi_posts c2_typ_l"></ol><div class="gt_mb"><button type="button" class="btn c2_typ_go" disabled>' + esc(DL.tgo) + '</button></div></div>'
				+ '<div class="c2_cm_err"></div><div class="c2_cm_sum"></div><div class="gt_mb"><button type="button" class="btn c2_dup_go" disabled>' + esc(DL.go) + '</button><button type="button" class="btn c2_cm_close">' + esc(CL.close) + '</button></div></div>';
			document.body.appendChild(m);
			var hd = m.querySelector('.mi_prog_h'), bar = m.querySelector('.mi_pbar_i'), list = m.querySelector('.c2_dup_l'), go = m.querySelector('.c2_dup_go'), cl = m.querySelector('.c2_cm_close'), sum = m.querySelector('.c2_cm_sum'), errb = m.querySelector('.c2_cm_err');
			var changed = false;
			cl.onclick = function () { m.remove(); contBusy = false; if (changed) reload(); };
			var cnt = function () { var n = list.querySelectorAll('input:checked').length; go.disabled = !n; go.textContent = DL.go + ' (' + n + ')'; };
			hd.innerHTML = '<span>…</span>';
			contPost('/products/cost2/get.php?a=dup_list', {}).then(function (d) {
				var gs = (d && d.groups) || [], ts = (d && d.types) || [];
				// «Бір түрге жинау»: әр топ құсбелгімен (әдепкі белгіленбеген, ал түр батырмасынан ашылса — белгіленген), ішіндегі нұсқалар да құсбелгімен
				if (ts.length) {
					var tb = m.querySelector('.c2_typ'), tl = m.querySelector('.c2_typ_l'), tg = m.querySelector('.c2_typ_go'); tb.hidden = false;
					tl.innerHTML = ts.map(function (g, i) {
						return '<li data-first="' + esc(g.first) + '"><input type="checkbox" class="c2_typ_c"' + (sec == 'type' ? ' checked' : '') + '><span class="mi_pn">' + (i + 1) + '.</span><span class="mi_pt"><b>' + esc(g.name) + '</b> ▸ '
							+ g.items.map(function (x) { return '<label class="c2_typ_v"><input type="checkbox" value="' + x.id + '" checked> ' + esc(x.name) + '</label>'; }).join(' ') + '</span><i class="mi_ps fal"></i></li>';
					}).join('');
					var tcnt = function () { var n = tl.querySelectorAll('.c2_typ_c:checked').length; tg.disabled = !n; tg.textContent = DL.tgo + ' (' + n + ')'; };
					tl.addEventListener('change', tcnt); tcnt();
					if (sec == 'type') tb.scrollIntoView({ block: 'start' });
					tg.onclick = function () {
						var lis = [].slice.call(tl.querySelectorAll('li')).filter(function (l) { return l.querySelector('.c2_typ_c').checked; });
						tg.disabled = true; cl.disabled = true; tl.querySelectorAll('input').forEach(function (x) { x.disabled = true; });
						var i = 0, n = 0;
						(function next() {
							if (i >= lis.length) { changed = true; cl.disabled = false; tg.hidden = true; sum.textContent = DL.tdone.replace('{n}', n); return; }
							var li = lis[i++], ids = [].slice.call(li.querySelectorAll('.c2_typ_v input:checked')).map(function (x) { return +x.value; });
							li.classList.add('mi_p_on');
							contPost('/products/cost2/get.php?a=type_group', { first: li.dataset.first, ids: JSON.stringify(ids) }).then(function (r) {
								li.classList.remove('mi_p_on'); li.classList.add(r && r.ok ? 'mi_p_ok' : 'mi_p_err'); if (r && r.ok) n += r.n || 0;
								next();
							});
						})();
					};
				}
				if (!gs.length) { hd.innerHTML = '<span>' + esc(DL.none) + '</span>'; go.hidden = true; return; }
				hd.innerHTML = '<span>' + esc(DL.title) + ': ' + gs.length + '</span><span class="mi_tm"></span>';
				list.innerHTML = gs.map(function (g, i) {
					return '<li data-key="' + esc(g.key) + '"><input type="checkbox" checked><span class="mi_pn">' + (i + 1) + '.</span><span class="mi_pt"><b>' + esc(g.name) + '</b> ← '
						+ g.items.map(function (x) { return esc(x.name) + (x.id == g.main ? ' <small class="c2_dup_m">★ ' + esc(DL.main) + (x.price ? ', ' + Math.round(x.price) + ' ₸/' + esc(x.unit || '') : '') + '</small>' : ''); }).join(', ') + '</span><i class="mi_ps fal"></i></li>';
				}).join('');
				list.addEventListener('change', cnt); cnt();
			});
			go.onclick = function () {
				var lis = [].slice.call(list.querySelectorAll('li')).filter(function (l) { return l.querySelector('input').checked; });
				if (!lis.length) return;
				go.disabled = true; cl.disabled = true; list.querySelectorAll('input').forEach(function (x) { x.disabled = true; });
				var i = 0, n = 0, errs = [], t0 = Date.now();
				var tick = setInterval(function () { var s = Math.round((Date.now() - t0) / 1000), x = hd.querySelector('.mi_tm'); if (x) x.textContent = Math.floor(s / 60) + ':' + ('0' + s % 60).slice(-2); }, 1000);
				(function next() {
					if (i >= lis.length) { clearInterval(tick); changed = true; cl.disabled = false; go.hidden = true; sum.textContent = DL.done.replace('{n}', n); return; }
					var li = lis[i++]; li.classList.add('mi_p_on');
					contPost('/products/cost2/get.php?a=dup_merge', { key: li.dataset.key }).then(function (r) {
						li.classList.remove('mi_p_on');
						if (r && r.ok) { li.classList.add('mi_p_ok'); n += r.n || 0; } else { li.classList.add('mi_p_err'); errs.push(li.querySelector('b').textContent); errb.textContent = '✗ ' + errs.join(', '); }
						bar.style.width = Math.round(i / lis.length * 100) + '%';
						next();
					});
				})();
			};
		});
		// шикізат түрлері: нұсқаларды түр жолының астына қою, ашу/жабу, баға режимі (rtype)
		function rtPlace() {
			document.querySelectorAll('.c2_raw .c2_rt').forEach(function (t) {
				var after = t;
				document.querySelectorAll('.c2_raw .c2_rv[data-t="' + t.dataset.t + '"]').forEach(function (v) { after.after(v); after = v; });
			});
		}
		rtPlace(); $(document).on('ajx:done', rtPlace);
		$(document).on('click', '.c2_rt_tg', function () {
			var t = this.closest('.c2_rt'), on = !t.classList.contains('c2_rt_on'); t.classList.toggle('c2_rt_on', on);
			document.querySelectorAll('.c2_raw .c2_rv[data-t="' + t.dataset.t + '"]').forEach(function (v) { v.classList.toggle('c2_rv_off', !on); });
		});
		$(document).on('click', '.c2_rt_m', function () { send('rtype', { id: this.dataset.id, f: 'mode', v: this.dataset.m }); });

		// тағам түрі: блоктағы шағын сегмент (бір тағам) және құсбелгімен жаппай (products/get.php?kind, k = 1..4)
		var kindSet = function (ids, k) {
			var fd = new FormData(); fd.append('ids', JSON.stringify(ids)); fd.append('k', k);
			return fetch('/products/get.php?kind', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) { if (t.trim() == 'yes') { mess(T.saved); reload(); } else mess(T.err, 'warn'); }).catch(function () { mess(T.net, 'err'); });
		};
		$(document).on('click', '.c2_glink', function (e) { e.preventDefault(); if (window.goodsPick) goodsPick(+this.dataset.id, this.dataset.name, reload); });
		$(document).on('click', '.c2_kseg .st_seg_i', function (e) { e.preventDefault(); if (this.classList.contains('st_seg_act')) return; kindSet([+this.closest('.c2_kseg').dataset.id], this.dataset.k); });
		// жаппай жолақ body ішінде (.c2_page-тің z-index контекстінен тыс), бір рет жасалады
		var KN = <?=json_encode(isset($kn) ? $kn : [], JSON_UNESCAPED_UNICODE)?>;
		var kbar = function () {
			var b = document.querySelector('body > .c2_kbar');
			if (!b) {
				b = document.createElement('div'); b.className = 'c2_kbar'; b.hidden = true;
				b.innerHTML = '<b class="c2_kbar_n"></b><span class="st_page"><span class="st_seg">' + Object.keys(KN).map(function (k) { return '<button type="button" class="st_seg_i c2_kbar_b" data-k="' + k + '">' + esc(KN[k]) + '</button>'; }).join('') + '</span></span><button type="button" class="c2_kbar_x" aria-label="×"><i class="fal fa-times"></i></button>';
				document.body.appendChild(b);
			}
			var ids = [].slice.call(document.querySelectorAll('.c2_ksel:checked')).map(function (x) { return +x.value; });
			b.hidden = !ids.length; b.querySelector('.c2_kbar_n').textContent = ids.length + ' ✓'; return ids;
		};
		$(document).on('change', '.c2_ksel', kbar);
		$(document).on('click', '.c2_kbar_b', function () { var ids = kbar(); if (ids && ids.length) kindSet(ids, this.dataset.k); });
		$(document).on('click', '.c2_kbar_x', function () { document.querySelectorAll('.c2_ksel:checked').forEach(function (x) { x.checked = false; }); kbar(); });

		$(document).on('click', '.c2_cont', function (e) {
			e.preventDefault();
			var t = this.querySelector('span') ? this.querySelector('span').textContent : this.textContent;
			contRun(this.dataset.k || '', +this.dataset.id || 0, t.trim());
		});

		// жаңа сет: мәзірдегі тағам (әлі сет емес) + бірінші зат → si_add
		$(document).on('click', '.c2_set_new', function () {
			var f = document.querySelector('.c2_news'); f.hidden = !f.hidden; if (f.hidden) return;
			var O = JSON.parse(document.querySelector('.c2_opt').dataset.o);
			f.querySelector('#c2_sdl').innerHTML = (O.d || []).map(function (x) { return '<option value="' + esc(x.n) + '">'; }).join('');
			f.querySelector('#c2_sil').innerHTML = opts('drc').map(function (x) { return '<option value="' + esc(x.n) + '">'; }).join('');
			f.querySelector('[name=set]').focus();
		});
		$(document).on('submit', '.c2_news', function (e) {
			e.preventDefault();
			var f = this, O = JSON.parse(document.querySelector('.c2_opt').dataset.o);
			var find = function (list, v) { v = v.trim().toLowerCase(); for (var i = 0; i < list.length; i++) if (list[i].n.trim().toLowerCase() == v) return list[i]; return null; };
			var d = find(O.d || [], f.set.value); if (!d) { mess(T.pick, 'warn'); f.set.focus(); return; }
			var it = find(opts('drc'), f.item.value); if (!it) { mess(T.pick, 'warn'); f.item.focus(); return; }
			var q = parseFloat(f.q.value.replace(',', '.')); if (!(q > 0)) { mess(T.qty, 'warn'); f.q.focus(); return; }
			var sid = d.c.slice(1);
			send('si_add', { to: sid, item: it.c, q: f.q.value }, function () {
				$(document).one('ajx:done', function () { var b = document.getElementById('s' + sid); if (b) { b.scrollIntoView({ block: 'center' }); var a = b.querySelector('.c2_add_b'); if (a) a.click(); } });
			});
		});
	});
	</script>

<? include "../../block/footer.php"; ?>
