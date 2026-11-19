<? include "../../config/core.php";
	include "../cost/lib.php";
	// Тағамдар ▸ Себес-2: Google кестесі сияқты бір бетте бәрі —
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
	$pf = function ($v) { return $v > 0 && $v < 100 && round($v) != $v ? number_format($v, 1, ',', ' ') : cost::fmt($v); };

	if ($ready) {
		cost::recalc($cmp);
		$st = cost::setting($cmp);
		$over = cost::overhead($st);
		$comps = cost::comps($cmp);

		// Шикізат + соңғы закуп (накладной болса)
		$raw = [];
		$q = db::query("select id, name_ru, name_kz, unit, price from product_raw where company_id = '$cmp' order by name_ru, name_kz");
		while ($q && $r = mysqli_fetch_assoc($q)) $raw[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => trim($r['name_ru'] ?: $r['name_kz']), 'unit' => (string)$r['unit'], 'price' => (float)$r['price'], 'last_d' => null, 'lastp' => null, 'sup' => '', 'prev' => null, 'used' => 0];
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
				if ($p['raw_id']) { $x = $raw[(int)$p['raw_id']] ?? null; $nm = $x ? $x['name'] : '?'; $u = $x ? $x['unit'] : ''; $pr = $x ? $x['price'] : 0; $lnk = 'r'.(int)$p['raw_id']; if ($x) $raw[(int)$p['raw_id']]['used']++; }
				else { $x = $comps[(int)$p['sub_comp_id']] ?? null; $nm = $x ? ($x['name_ru'] ?: $x['name_kz']) : '?'; $u = $x ? $x['unit'] : ''; $pr = $x ? $x['cost'] : 0; $lnk = 'p'.(int)$p['sub_comp_id']; }
				$s = $p['quantity'] * $pr; $sum += $s;
				$rows[] = ['name' => $nm, 'qty' => $qf($p['quantity'], $u), 'price' => $pr, 'sum' => $s, 'lnk' => $lnk];
			}
			$preps[$id] = ['id' => $id, 'name' => $c['name_ru'] ?: $c['name_kz'], 'unit' => $c['unit'] ?: 'шт', 'yield' => (float)$c['yield_qty'], 'sum' => $sum, 'cost' => $c['cost'], 'rows' => $rows, 'kind' => $c['kind']];
		}

		// Мәзірдегі тағамдар және олардың граммовкасы
		$prd = [];
		$q = db::query("select p.id, p.name_ru, p.name_kz, p.price, p.arh from product p where p.company_id = '$cmp'");
		while ($q && $r = mysqli_fetch_assoc($q)) $prd[(int)$r['id']] = ['id' => (int)$r['id'], 'name' => trim(strip_tags($r['name_ru'] ?: $r['name_kz'])), 'price' => (int)preg_replace('/\D+/', '', (string)$r['price']), 'arh' => (int)$r['arh']];
		$dishes = [];
		$q = db::query("select pi.product_id, pi.comp_id, pi.quantity from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and pi.comp_id > 0 order by pi.product_id, pi.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$pid = (int)$r['product_id']; $c = $comps[(int)$r['comp_id']] ?? null;
			if (!$c || !isset($prd[$pid])) continue;
			$u = (string)$c['unit']; $d = cost::div($u);
			$pr = $c['cost'];
			$s = $r['quantity'] * $pr / $d;
			// граммен жазылған кг затты кестедегідей г етіп көрсетеміз
			$qt = $d > 1 ? rtrim(rtrim(number_format($r['quantity'], 1, ',', ' '), '0'), ',').' '.(mb_strtolower(trim($u)) == 'л' ? 'мл' : 'г') : rtrim(rtrim(number_format($r['quantity'], 3, ',', ' '), '0'), ',').' '.($u ?: 'шт');
			$lnk = $c['parts'] ? 'p'.(int)$c['id'] : (!empty($c['raw_id']) ? 'r'.(int)$c['raw_id'] : '');
			if (!$c['parts'] && !empty($c['raw_id']) && isset($raw[(int)$c['raw_id']])) $raw[(int)$c['raw_id']]['used']++;
			if (!isset($dishes[$pid])) $dishes[$pid] = ['id' => $pid, 'name' => $prd[$pid]['name'], 'price' => $prd[$pid]['price'], 'sum' => 0, 'rows' => []];
			$dishes[$pid]['sum'] += $s;
			$dishes[$pid]['rows'][] = ['name' => $c['name_ru'] ?: $c['name_kz'], 'qty' => $qt, 'price' => $pr, 'sum' => $s, 'lnk' => $lnk];
		}

		// Сеттер
		$sets = [];
		$q = db::query("select si.* from product_set_item si join product s on s.id = si.set_id where s.company_id = '$cmp' order by si.set_id, si.id");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$sid = (int)$r['set_id']; if (!isset($prd[$sid])) continue;
			if ($r['product_id']) { $pid = (int)$r['product_id']; $nm = $prd[$pid]['name'] ?? '?'; $u = isset($dishes[$pid]) ? $dishes[$pid]['sum'] : 0; $lnk = isset($dishes[$pid]) ? 'd'.$pid : ''; $miss = !isset($dishes[$pid]); }
			else { $c = $comps[(int)$r['comp_id']] ?? null; $nm = $c ? ($c['name_ru'] ?: $c['name_kz']) : '?'; $u = $c ? $c['cost'] : 0; $lnk = $c && $c['parts'] ? 'p'.(int)$c['id'] : ($c && !empty($c['raw_id']) ? 'r'.(int)$c['raw_id'] : ''); $miss = !$c || $u <= 0; }
			if (!isset($sets[$sid])) $sets[$sid] = ['id' => $sid, 'name' => $prd[$sid]['name'], 'price' => $prd[$sid]['price'], 'arh' => $prd[$sid]['arh'], 'sum' => 0, 'rows' => [], 'miss' => 0];
			$s = $u * $r['quantity'];
			$sets[$sid]['sum'] += $s;
			if ($miss) $sets[$sid]['miss']++;
			$sets[$sid]['rows'][] = ['name' => strip_tags($nm), 'qty' => ($r['quantity'] + 0), 'price' => $u, 'sum' => $s, 'lnk' => $lnk, 'miss' => $miss];
		}
		// сет болып табылатын өнім Тағамдар бағанында қайталанбайды
		foreach ($sets as $sid => $x) unset($dishes[$sid]);
		$by_name = function ($a, $b) { return strcasecmp($a['name'], $b['name']); };
		uasort($preps, $by_name); uasort($dishes, $by_name);
		uasort($sets, function ($a, $b) { return [$a['arh'], $a['name']] <=> [$b['arh'], $b['name']]; });

		// Қорытынды: сеттің толық себесі мен ұсынылатын бағасы (кестенің оң жағы)
		$mk = $st['markup'] / 100;
		foreach ($sets as &$x) {
			$x['full'] = $x['sum'] + $over;
			$x['mk'] = $x['full'] * $mk;
			$x['total'] = $x['full'] + $x['mk'];
			$x['nice'] = cost::nice($x['total']);
			$x['profit'] = $x['price'] - $x['full'];
			$x['pct'] = $x['price'] ? $x['profit'] / $x['price'] * 100 : 0;
		}
		unset($x);
		$used_raw = $raw;
	}

	$menu_name = 'products';
	$pod_menu_name = 'cost2';
	$pm = 'cost2';
	$page_name = t::w('Cost table', 'Себес-2');
	$page_key = 'Cost table';
	$css = ['cost2'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c c2_page">
	<? if (!$ready): ?>
		<div class="c2_card c2_note"><?=t::w('c2 Cost tables are not created yet', 'Себес кестелері әлі жасалмаған')?></div>
	<? else: ?>

		<!-- жоғарғы жолақ: баптаулар + іздеу -->
		<div class="c2_top">
			<div class="c2_set">
				<span><?=t::w('c2 Salary', 'Айлық')?> <b><?=$f($st['salary'])?></b></span>
				<span><?=t::w('c2 Rent', 'Аренда')?> <b><?=$f($st['rent'])?></b></span>
				<span><?=t::w('c2 Utilities', 'К.у.')?> <b><?=$f($st['utility'])?></b></span>
				<span><?=t::w('c2 Delivery', 'Доставка')?> <b><?=$f($st['delivery'])?></b></span>
				<span><?=t::w('c2 Advertising', 'Реклама')?> <b><?=$f($st['ads'])?></b></span>
				<span><?=t::w('c2 Markup', 'Наценка')?> <b><?=$st['markup'] + 0?>%</b></span>
				<span class="c2_set_t"><?=t::w('c2 Per order', 'Бір заказға')?> <b><?=$f($over)?> ₸</b></span>
				<a class="c2_set_ed" href="/products/cost/"><i class="fal fa-pen"></i> <?=t::w('c2 Change', 'Өзгерту')?></a>
			</div>
			<label class="c2_srch"><i class="fal fa-search"></i><input type="search" class="c2_q" placeholder="<?=$h(t::w('c2 Search', 'Іздеу'))?>"></label>
		</div>

		<!-- 1. Қорытынды кесте (Google кестесінің оң жағы) -->
		<div class="c2_card">
			<div class="c2_h"><?=t::w('c2 Sets: final cost', 'Сеттер: қорытынды себес')?> <span><?=count($sets)?></span></div>
			<div class="c2_wrap"><table class="c2_tbl c2_sum">
				<thead><tr>
					<th>№</th><th class="c2_l"><?=t::w('c2 Set', 'Сет')?></th><th><?=t::w('c2 Ingredients', 'Ингредиент')?></th>
					<th><?=t::w('c2 Salary', 'Айлық')?></th><th><?=t::w('c2 Rent', 'Аренда')?></th><th><?=t::w('c2 Utilities', 'К.у.')?></th><th><?=t::w('c2 Delivery', 'Доставка')?></th><th><?=t::w('c2 Advertising', 'Реклама')?></th>
					<th class="c2_hl"><?=t::w('c2 Total cost', 'Толық себес')?></th><th><?=t::w('c2 Markup', 'Наценка')?> <?=$st['markup'] + 0?>%</th><th><?=t::w('c2 Total', 'Наценкамен')?></th>
					<th class="c2_hl"><?=t::w('c2 Suggested', 'Ұсыныс')?></th><th><?=t::w('c2 Current price', 'Қазіргі баға')?></th><th><?=t::w('c2 Profit', 'Пайда')?></th>
				</tr></thead>
				<tbody>
				<? $n = 0; foreach ($sets as $x): $n++; $pc = $x['profit'] < 0 ? 'c2_neg' : ($x['pct'] < 5 ? 'c2_warn' : 'c2_pos'); ?>
					<tr class="c2_f" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<td class="c2_mut"><?=$n?></td>
						<td class="c2_l c2_nm"><a href="#s<?=$x['id']?>" class="c2_go"><?=$h($x['name'])?></a><?=$x['arh'] ? ' <span class="c2_tag">'.t::w('c2 archive', 'архив').'</span>' : ''?><?=$x['miss'] ? ' <span class="c2_tag c2_tag_r" title="'.$h(t::w('c2 Some items have no cost', 'Кейбір затының себесі жоқ')).'">!</span>' : ''?></td>
						<td><?=$f($x['sum'])?></td>
						<td class="c2_mut"><?=$f($st['salary'])?></td><td class="c2_mut"><?=$f($st['rent'])?></td><td class="c2_mut"><?=$f($st['utility'])?></td><td class="c2_mut"><?=$f($st['delivery'])?></td><td class="c2_mut"><?=$f($st['ads'])?></td>
						<td class="c2_hl"><b><?=$f($x['full'])?></b></td><td class="c2_mut"><?=$f($x['mk'])?></td><td><?=$f($x['total'])?></td>
						<td class="c2_hl"><b><?=$f($x['nice'])?></b></td><td><?=$f($x['price'])?></td>
						<td><span class="c2_p <?=$pc?>"><?=$f($x['profit'])?> <small><?=(int)round($x['pct'])?>%</small></span></td>
					</tr>
				<? endforeach ?>
				</tbody>
			</table></div>
		</div>

		<!-- 2. Кесте: Шикізат → Дайындамалар → Тағамдар → Сеттер -->
		<div class="c2_grid">

			<div class="c2_col">
				<div class="c2_ch c2_ch1"><b>1</b> <?=t::w('c2 Raw materials (purchase)', 'Шикізат (закуп)')?> <span><?=count($used_raw)?></span></div>
				<div class="c2_card c2_blk">
					<table class="c2_tbl c2_raw">
						<thead><tr><th class="c2_l"><?=t::w('c2 Name', 'Атауы')?></th><th><?=t::w('c2 Unit', 'Өлш.')?></th><th><?=t::w('c2 Price', 'Бағасы')?></th></tr></thead>
						<tbody>
						<? foreach ($used_raw as $r): $d = $r['prev'] ? ($r['lastp'] - $r['prev']) / $r['prev'] * 100 : null; ?>
							<tr class="c2_f" id="r<?=$r['id']?>" data-q="<?=$h(mb_strtolower($r['name']))?>">
								<td class="c2_l c2_nm"><a href="/waybill/raw.php?id=<?=$r['id']?>"><?=$h($r['name'])?></a>
									<? if ($r['last_d']): ?><div class="c2_sub"><?=date('d.m', strtotime($r['last_d']))?><?=$r['sup'] !== '' ? ' · '.$h($r['sup']) : ''?></div><? endif ?></td>
								<td class="c2_mut"><?=$h($r['unit'] ?: 'шт')?></td>
								<td><b><?=$pf($r['price'])?></b><? if ($d !== null && abs($d) >= 1): ?><div class="c2_sub <?=$d > 0 ? 'c2_up' : 'c2_dn'?>"><?=$d > 0 ? '▲' : '▼'?> <?=abs(round($d))?>%</div><? endif ?></td>
							</tr>
						<? endforeach ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="c2_col">
				<div class="c2_ch c2_ch2"><b>2</b> <?=t::w('c2 Semi-finished', 'Дайындамалар')?> <span><?=count($preps)?></span></div>
				<? foreach ($preps as $x): ?>
					<div class="c2_card c2_blk c2_f" id="p<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><a href="/products/cost/?prep=<?=$x['id']?>#prep" title="<?=$h(t::w('c2 Edit', 'Өзгерту'))?>"><?=$h($x['name'])?></a><span>₸ / <?=$h($x['unit'])?></span></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr><td class="c2_l"><?=$r['lnk'] ? '<a href="#'.$r['lnk'].'" class="c2_go">'.$h($r['name']).'</a>' : $h($r['name'])?></td><td class="c2_mut"><?=$h($r['qty'])?></td><td class="c2_mut"><?=$pf($r['price'])?></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							</tbody>
							<tfoot><tr><td class="c2_l"><?=t::w('c2 Yield', 'Шығымы')?> <?=$x['yield'] + 0?> <?=$h($x['unit'])?></td><td></td><td class="c2_mut"><?=$f($x['sum'])?></td><td class="c2_hl"><b><?=$pf($x['cost'])?></b></td></tr></tfoot>
						</table>
					</div>
				<? endforeach ?>
			</div>

			<div class="c2_col">
				<div class="c2_ch c2_ch3"><b>3</b> <?=t::w('c2 Dishes (grams)', 'Тағамдар (граммовка)')?> <span><?=count($dishes)?></span></div>
				<? foreach ($dishes as $x): $pr = $x['price'] ? ($x['price'] - $x['sum']) / $x['price'] * 100 : 0; ?>
					<div class="c2_card c2_blk c2_f" id="d<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><a href="/products/item/?id=<?=$x['id']?>"><?=$h($x['name'])?></a><? if ($x['price']): ?><span><?=$f($x['price'])?> ₸</span><? endif ?></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr><td class="c2_l"><?=$r['lnk'] ? '<a href="#'.$r['lnk'].'" class="c2_go">'.$h($r['name']).'</a>' : $h($r['name'])?></td><td class="c2_mut"><?=$h($r['qty'])?></td><td class="c2_mut"><?=$pf($r['price'])?></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							</tbody>
							<tfoot><tr><td class="c2_l"><?=t::w('c2 Cost', 'Себес')?></td><td></td><td class="c2_mut"><?=$x['price'] ? round(100 - $pr).'%' : ''?></td><td class="c2_hl"><b><?=$f($x['sum'])?></b></td></tr></tfoot>
						</table>
					</div>
				<? endforeach ?>
			</div>

			<div class="c2_col">
				<div class="c2_ch c2_ch4"><b>4</b> <?=t::w('c2 Sets', 'Сеттер')?> <span><?=count($sets)?></span></div>
				<? foreach ($sets as $x): ?>
					<div class="c2_card c2_blk c2_f" id="s<?=$x['id']?>" data-q="<?=$h(mb_strtolower($x['name']))?>">
						<div class="c2_bh"><a href="/products/item/?id=<?=$x['id']?>"><?=$h($x['name'])?></a><? if ($x['price']): ?><span><?=$f($x['price'])?> ₸</span><? endif ?></div>
						<table class="c2_tbl">
							<tbody>
							<? foreach ($x['rows'] as $r): ?>
								<tr<?=$r['miss'] ? ' class="c2_miss"' : ''?>><td class="c2_l"><?=$r['lnk'] ? '<a href="#'.$r['lnk'].'" class="c2_go">'.$h($r['name']).'</a>' : $h($r['name'])?></td><td class="c2_mut">× <?=$r['qty']?></td><td class="c2_mut"><?=$f($r['price'])?></td><td><?=$f($r['sum'])?></td></tr>
							<? endforeach ?>
							</tbody>
							<tfoot><tr><td class="c2_l"><?=t::w('c2 Ingredients', 'Ингредиент')?></td><td></td><td></td><td class="c2_hl"><b><?=$f($x['sum'])?></b></td></tr></tfoot>
						</table>
					</div>
				<? endforeach ?>
			</div>

		</div>
	<? endif ?>
	</div>

	<script>
	// Себес-2: іздеу және сілтеме бойынша блокқа өту (жарқырап көрсетеді)
	(function () {
		var q = document.querySelector('.c2_q');
		if (q) q.addEventListener('input', function () {
			var v = q.value.trim().toLowerCase();
			document.querySelectorAll('.c2_f').forEach(function (el) { el.hidden = v !== '' && el.dataset.q.indexOf(v) < 0; });
		});
		$(document).on('click', '.c2_go', function (e) {
			var t = document.getElementById(this.getAttribute('href').slice(1));
			if (!t) return;
			e.preventDefault();
			if (t.hidden) { q.value = ''; q.dispatchEvent(new Event('input')); }
			t.scrollIntoView({ behavior: 'smooth', block: 'center' });
			t.classList.remove('c2_flash'); void t.offsetWidth; t.classList.add('c2_flash');
		});
	})();
	</script>

<? include "../../block/footer.php"; ?>
