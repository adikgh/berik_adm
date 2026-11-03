<? include "../../config/core.php";
	include "lib.php";
	// Тағамдар ▸ Себес: өзіндік құн және ұсынылатын баға
	if (!$user_id) { header('location: /'); exit(); }
	$cmp = (int)$company;
	$ready = cost::ready();

	if ($ready) {
		cost::recalc($cmp);                // шикізат бағасы өзгерсе, дайындамалар бірден жаңарады
		$st = cost::setting($cmp);
		$over = cost::overhead($st);
		$comps = cost::comps($cmp);
		$dishes = cost::dishes($cmp, $comps);
		$sets = cost::sets($cmp, $comps, $dishes);
		$prd = [];
		$ids = array_unique(array_merge(array_keys($dishes), array_keys($sets)));
		if ($ids) {
			$q = db::query("select id, name_ru, name_kz, price, catalog_id from product where company_id = '$cmp' and id in (".implode(',', array_map('intval', $ids)).") order by name_ru");
			while ($r = mysqli_fetch_assoc($q)) $prd[$r['id']] = $r;
		}
		$row = function ($p, $cost, $with_over) use ($st, $over) {
			$full = $cost + ($with_over ? $over : 0);
			$sug = $full * (1 + $st['markup'] / 100);
			$price = (int)preg_replace('/\D+/', '', (string)$p['price']);
			$profit = $price - $full;
			return ['name' => strip_tags($p['name_ru'] ?: $p['name_kz']), 'id' => $p['id'], 'cost' => $cost, 'full' => $full,
				'sug' => $sug, 'nice' => cost::nice($sug), 'price' => $price, 'profit' => $profit, 'pct' => $price ? $profit / $price * 100 : 0];
		};
		$set_rows = []; foreach ($sets as $id => $s) if (isset($prd[$id])) $set_rows[] = $row($prd[$id], $s['cost'], true) + ['items' => $s['items']];
		$dish_rows = []; foreach ($dishes as $id => $c) if (isset($prd[$id]) && !isset($sets[$id])) $dish_rows[] = $row($prd[$id], $c, false);
		usort($set_rows, function ($a, $b) { return $a['pct'] <=> $b['pct']; });
		usort($dish_rows, function ($a, $b) { return $a['pct'] <=> $b['pct']; });
		// граммовкасы жоқ / толық емес тағамдар (Закуп бөлімінің тексеруі)
		if (!class_exists('wb')) @include_once "../../waybill/lib.php";
		$cc = class_exists('wb') ? wb::comp_check($cmp) : ['dish' => [], 'comp' => []];
		$miss_rows = [];
		if ($cc['dish']) {
			$sold = [];
			$q = db::query("select rop.product_id, SUM(rop.quantity) n from retail_orders_products rop join retail_orders o on o.id = rop.order_id
				where o.company_id = '$cmp' and o.order_status = 4 and o.ins_dt >= '".date('Y-m-d', strtotime('-30 day'))."' and rop.product_id in (".implode(',', array_keys($cc['dish'])).") group by rop.product_id");
			while ($q && $r = mysqli_fetch_assoc($q)) $sold[(int)$r['product_id']] = (int)$r['n'];
			$q = db::query("select id, name_ru, name_kz from product where id in (".implode(',', array_keys($cc['dish'])).")");
			while ($q && $r = mysqli_fetch_assoc($q)) $miss_rows[] = ['id' => (int)$r['id'], 'name' => trim(strip_tags($r['name_ru'] ?: $r['name_kz'])), 'st' => $cc['dish'][(int)$r['id']], 'sold' => $sold[(int)$r['id']] ?? 0];
			usort($miss_rows, function ($a, $b) { return [$b['sold'], $a['name']] <=> [$a['sold'], $b['name']]; });
		}
	}

	$menu_name = 'products';
	$pod_menu_name = 'cost';
	$pm = 'cost';
	$page_name = 'Себес';
	$page_key = 'Cost price';
	$css = ['cost'];
	$js = [];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c cs_page">

		<? if (!$ready): ?>
			<div class="cs_card cs_note">Себес кестелері әлі жасалмаған. sql/cost_01_schema.sql файлын іске қосу керек.</div>
		<? else: ?>
			<?
				$n_bad = 0; $n_low = 0; $sum_p = 0;
				foreach ($set_rows as $r) { if ($r['profit'] < 0) $n_bad++; elseif ($r['pct'] < 5) $n_low++; $sum_p += $r['pct']; }
				$avg = $set_rows ? round($sum_p / count($set_rows)) : 0;
				$pc = function ($r) { return $r['profit'] < 0 ? 'cs_neg' : ($r['pct'] < 5 ? 'cs_warn' : 'cs_pos'); };
			?>

			<? $n_prep = 0; foreach ($comps as $c) if ($c['parts']) $n_prep++; ?>
			<div class="cs_bar">
				<div class="cs_tabs">
				<button type="button" class="cs_tab cs_tab_act" data-tab="sets">Сеттер <span><?=count($set_rows)?></span></button>
				<button type="button" class="cs_tab" data-tab="dishes">Тағамдар <span><?=count($dish_rows)?></span></button>
				<button type="button" class="cs_tab" data-tab="prep">Дайындамалар <span><?=$n_prep?></span></button>
				<? if ($miss_rows || $cc['comp']): ?><button type="button" class="cs_tab" data-tab="missing" style="color:#c0392b">Граммовкасы жоқ <span style="background:#e74c3c;color:#fff"><?=count($miss_rows) + count($cc['comp'])?></span></button><? endif ?>
				</div>
			</div>

			<div class="cs_stats">
				<div class="cs_stat"><span>Сеттер</span><b><?=count($set_rows)?></b></div>
				<div class="cs_stat cs_stat_neg"><span>Шығынмен сатылады</span><b><?=$n_bad?></b></div>
				<div class="cs_stat cs_stat_warn"><span>Пайдасы 5%-дан аз</span><b><?=$n_low?></b></div>
				<div class="cs_stat"><span>Орташа пайда</span><b><?=$avg?>%</b></div>
				<div class="cs_stat"><span>Бір заказға шығын</span><b><?=cost::fmt($over)?> тг</b></div>
			</div>

			<div class="cs_card">
				<div class="cs_h">Баға стандарттары <span>бір заказға, тг</span></div>
				<form class="cs_set" id="cs_set">
					<? $lab = ['orders_day' => 'Заказ / күн', 'salary' => 'Айлық', 'rent' => 'Аренда', 'utility' => 'К.у.', 'delivery' => 'Доставка', 'ads' => 'Реклама', 'markup' => 'Наценка, %']; ?>
					<? foreach ($lab as $k => $n): ?>
						<label class="cs_f"><span><?=$n?></span><input class="cs_in" name="<?=$k?>" value="<?=$st[$k] + 0?>" inputmode="decimal"></label>
					<? endforeach ?>
					<button class="btn cs_save" type="submit"><i class="far fa-check"></i><span>Сақтау</span></button>
				</form>
				<div class="cs_hint"><i class="fal fa-info-circle"></i> Сеттің толық себесі = тағамдар себесі + <?=cost::fmt($over)?> тг (айлық, аренда, к.у., доставка, реклама). Ұсынылатын баға = толық себес + <?=$st['markup'] + 0?>%, сосын 490 / 990-ға дейін дөңгелектенеді.</div>
			</div>

			<div class="cs_card cs_pane" data-pane="sets">
				<div class="cs_h">Сеттер <span><?=count($set_rows)?> · пайдасы аздары жоғарыда</span></div>
				<div class="cs_scroll"><table class="cs_tbl">
					<thead><tr><th>Сет</th><th>Тағамдар</th><th>Толық себес</th><th>+<?=$st['markup'] + 0?>%</th><th>Ұсыныс</th><th>Қазіргі баға</th><th>Пайда</th><th></th></tr></thead>
					<tbody>
					<? foreach ($set_rows as $r): ?>
						<tr>
							<td class="cs_nm"><a href="/products/item/?id=<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></a><?=(isset($cc['dish'][$r['id']]) ? wb::comp_badge($cc['dish'][$r['id']]) : '')?></td>
							<td><?=cost::fmt($r['cost'])?></td><td><?=cost::fmt($r['full'])?></td><td class="cs_mut"><?=cost::fmt($r['sug'])?></td>
							<td><b><?=cost::fmt($r['nice'])?></b></td><td><?=cost::fmt($r['price'])?></td>
							<td><span class="cs_p <?=$pc($r)?>"><?=cost::fmt($r['profit'])?> <small><?=round($r['pct'])?>%</small></span></td>
							<td class="cs_act"><button type="button" class="cs_comp_b" title="Құрамы" data-id="<?=$r['id']?>"><i class="fal fa-layer-group"></i></button></td>
						</tr>
						<? foreach ($r['items'] as $i): ?>
							<tr class="cs_comp_r" data-for="<?=$r['id']?>" hidden><td class="cs_ci"><?=htmlspecialchars($i['name'])?> <span>× <?=$i['qty'] + 0?></span></td><td><?=cost::fmt($i['cost'])?></td><td colspan="6"></td></tr>
						<? endforeach ?>
					<? endforeach ?>
					</tbody></table></div>
			</div>

			<div class="cs_card cs_pane" data-pane="dishes" hidden>
				<div class="cs_h">Тағамдар <span><?=count($dish_rows)?></span></div>
				<div class="cs_scroll"><table class="cs_tbl">
					<thead><tr><th>Тағам</th><th>Себес</th><th>Баға</th><th>Пайда</th></tr></thead>
					<tbody>
					<? foreach ($dish_rows as $r): ?>
						<tr><td class="cs_nm"><a href="/products/item/?id=<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></a><?=(isset($cc['dish'][$r['id']]) ? wb::comp_badge($cc['dish'][$r['id']]) : '')?></td>
							<td><?=cost::fmt($r['cost'])?></td><td><?=cost::fmt($r['price'])?></td>
							<td><span class="cs_p <?=$pc($r)?>"><?=cost::fmt($r['profit'])?> <small><?=round($r['pct'])?>%</small></span></td></tr>
					<? endforeach ?>
					</tbody></table></div>
			</div>

			<div class="cs_card cs_pane" data-pane="prep" hidden>
				<div class="cs_hrow">
					<div class="cs_h">Дайындамалар <span>1 кг немесе 1 шт бағасы</span></div>
					<button type="button" class="btn cs_prep_new"><i class="far fa-plus"></i><span>Дайындама қосу</span></button>
				</div>
				<div class="cs_scroll"><table class="cs_tbl">
					<thead><tr><th>Атауы</th><th>Құрамы</th><th>Шығымы</th><th>Бағасы</th><th></th></tr></thead>
					<tbody>
					<?
						$rawn = []; $q = db::query("select id, name_ru, name_kz, unit, price from product_raw where company_id = '$cmp' order by name_ru"); while ($x = mysqli_fetch_assoc($q)) $rawn[$x['id']] = $x;
						$isw = function ($u) { return in_array(mb_strtolower(trim((string)$u)), ['кг', 'л']); };
						// 0.2 кг → 200 г, 4 шт → 4 шт
						$qf = function ($qty, $u) use ($isw) { $u = trim((string)$u); return $isw($u) ? round($qty * 1000, 1).' '.(mb_strtolower($u) == 'л' ? 'мл' : 'г') : ($qty + 0).' '.($u ?: 'шт'); };
						$P = ['raw' => [], 'comp' => [], 'prep' => []];
						foreach ($rawn as $x) $P['raw'][] = ['id' => (int)$x['id'], 'n' => $x['name_ru'] ?: $x['name_kz'], 'u' => (string)$x['unit']];
						foreach ($comps as $x) $P['comp'][] = ['id' => (int)$x['id'], 'n' => $x['name_ru'] ?: $x['name_kz'], 'u' => (string)$x['unit']];
					?>
					<? foreach ($comps as $c): if (!$c['parts']) continue;
						$P['prep'][$c['id']] = ['n' => $c['name_ru'] ?: $c['name_kz'], 'u' => (string)$c['unit'], 'y' => $c['yield_qty'] + 0,
							'parts' => array_map(function ($p) { return ['t' => $p['raw_id'] ? 'r' : 'c', 'id' => (int)($p['raw_id'] ?: $p['sub_comp_id']), 'q' => $p['quantity'] + 0]; }, $c['parts'])]; ?>
						<tr><td class="cs_nm"><?=htmlspecialchars($c['name_ru'])?><?=$c['kind'] == 'dish' ? ' <span class="cs_tag">мәзірде жоқ</span>' : ''?></td>
							<td><div class="cs_chips"><? foreach ($c['parts'] as $p): $pu = $p['raw_id'] ? ($rawn[$p['raw_id']]['unit'] ?? '') : ($comps[$p['sub_comp_id']]['unit'] ?? ''); ?><span><?=htmlspecialchars($p['raw_id'] ? ($rawn[$p['raw_id']]['name_ru'] ?? '?') : ($comps[$p['sub_comp_id']]['name_ru'] ?? '?'))?> <b><?=$qf($p['quantity'], $pu)?></b></span><? endforeach ?></div></td>
							<td class="cs_nw"><?=$c['yield_qty'] + 0?> <?=htmlspecialchars((string)($c['unit'] ?: 'шт'))?></td>
							<td class="cs_nw"><b><?=cost::fmt($c['cost'])?></b> тг / <?=htmlspecialchars((string)($c['unit'] ?: 'шт'))?></td>
							<td class="cs_act"><button type="button" class="cs_comp_b cs_prep_ed" data-id="<?=$c['id']?>" title="Өзгерту"><i class="fal fa-pen"></i></button></td></tr>
					<? endforeach ?>
					</tbody></table></div>
			</div>

			<? if ($miss_rows || $cc['comp']): ?>
			<div class="cs_card cs_pane" data-pane="missing" hidden>
				<div class="cs_h">Граммовкасы жоқ <span>сатылғанда шикізат азаймайды · соңғы 30 күнде көп сатылғандары жоғарыда</span></div>
				<div class="cs_scroll"><table class="cs_tbl">
					<thead><tr><th>Тағам / сет</th><th>Белгі</th><th>30 күнде сатылды</th><th></th></tr></thead>
					<tbody>
					<? foreach ($miss_rows as $r): ?>
						<tr><td class="cs_nm"><a href="/products/item/?id=<?=$r['id']?>"><?=htmlspecialchars($r['name'])?></a></td>
							<td><?=wb::comp_badge($r['st'])?></td>
							<td><?=($r['sold'] ? '<b>'.$r['sold'].'</b> дана' : '<span class="cs_mut">—</span>')?></td>
							<td class="cs_act"><a class="cs_comp_b" href="/products/item/?id=<?=$r['id']?>" title="Құрамын толтыру"><i class="fal fa-pen"></i></a></td></tr>
					<? endforeach ?>
					<? foreach ($cc['comp'] as $cid => $cn): ?>
						<tr><td class="cs_nm"><?=htmlspecialchars($cn)?> <span class="cs_tag">дайындама</span></td>
							<td><span class="wbm_b" style="display:inline-block;padding:2px 8px;border-radius:20px;background:#fdecec;color:#c0392b;font-size:11px;font-weight:700">Шикізатқа байланбаған</span></td>
							<td class="cs_mut">Дайындамалар қойындысында рецепт жазыңыз не шикізат таңдаңыз</td><td></td></tr>
					<? endforeach ?>
					</tbody></table></div>
			</div>
			<? endif ?>

			<div class="cs_pop" hidden>
				<div class="cs_pop_bg"></div>
				<form class="cs_pop_c" autocomplete="off">
					<div class="cs_pop_h"><b class="cs_pop_t">Дайындама</b><button type="button" class="cs_pop_x"><i class="fal fa-times"></i></button></div>
					<div class="cs_pop_g">
						<label class="cs_f cs_f_w"><span>Атауы</span><input class="cs_in pp_name" maxlength="255" required></label>
						<label class="cs_f"><span>Өлшемі</span><select class="cs_in pp_unit"><option>кг</option><option>шт</option><option>л</option></select></label>
						<label class="cs_f"><span>Шығымы (неше <i class="pp_yu">кг</i> шығады)</span><input class="cs_in pp_yield" inputmode="decimal"></label>
					</div>
					<div class="cs_pop_s">Құрамы <small>кг/л заттар граммен (мл) жазылады</small></div>
					<div class="pp_rows"></div>
					<button type="button" class="pp_add"><i class="far fa-plus"></i> Зат қосу</button>
					<div class="pp_ft"><button type="submit" class="btn cs_save"><i class="far fa-check"></i><span>Сақтау</span></button></div>
				</form>
			</div>
			<div class="cs_pdata" hidden data-p="<?=htmlspecialchars(json_encode($P, JSON_UNESCAPED_UNICODE))?>"></div>

		<? endif ?>
	</div>

	<script>
	// Себес беті. Сақтағанда бет жаңармайды: .cs_page блогы AJAX-пен қайта алынады (fun.js ajx), сосын csInit() қайта іске қосылады
	var csTab = '';
	function csInit() {
		var tabs = document.querySelectorAll('.cs_tab'), panes = document.querySelectorAll('.cs_pane');
		function show(k) {
			var ok = false;
			tabs.forEach(function (t) { var on = t.dataset.tab === k; t.classList.toggle('cs_tab_act', on); if (on) ok = true; });
			if (!ok) return show('sets');
			csTab = k;
			panes.forEach(function (p) { p.hidden = p.dataset.pane !== k; });
		}
		tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); history.replaceState(history.state, '', '#' + t.dataset.tab); }); });
		if (csTab) show(csTab); else if (location.hash) show(location.hash.slice(1));
		document.querySelectorAll('.cs_comp_b').forEach(function (b) {
			b.addEventListener('click', function () {
				var on = !b.classList.contains('cs_comp_on');
				b.classList.toggle('cs_comp_on', on);
				b.closest('tr').classList.toggle('cs_open', on);
				document.querySelectorAll('.cs_comp_r[data-for="' + b.dataset.id + '"]').forEach(function (r) { r.hidden = !on; });
			});
		});
		var set = document.getElementById('cs_set');
		if (set) set.addEventListener('submit', function (e) {
			e.preventDefault();
			fetch('/products/cost/get.php?setting', { method: 'POST', body: new FormData(this), credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
				if (t.trim() == 'yes') { mess('Сақталды'); ajx('.cs_page', csInit); } else mess('Қате', 'err');
			}).catch(function () { mess('Байланыс қатесі', 'err'); });
		});

		var pd = document.querySelector('.cs_pdata'); if (!pd) return;
		var P = JSON.parse(pd.dataset.p || 'null'); if (!P) return;
		var pop = document.querySelector('.cs_pop'), form = pop.querySelector('form'), rows = pop.querySelector('.pp_rows'), cur = 0;
		var W = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' || u == 'л'; };
		var uLab = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' ? 'г' : (u == 'л' ? 'мл' : (u || 'шт')); };
		var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
		var opts = '<option value="">Таңдаңыз</option><optgroup label="Шикізат">' + P.raw.map(function (x) { return '<option value="r' + x.id + '" data-u="' + esc(x.u) + '">' + esc(x.n) + '</option>'; }).join('') +
			'</optgroup><optgroup label="Дайындама / құрам">' + P.comp.map(function (x) { return '<option value="c' + x.id + '" data-u="' + esc(x.u) + '">' + esc(x.n) + '</option>'; }).join('') + '</optgroup>';
		function addRow(v, q) {
			var r = document.createElement('div'); r.className = 'pp_row';
			r.innerHTML = '<select class="cs_in pp_it">' + opts + '</select><input class="cs_in pp_q" inputmode="decimal"><span class="pp_u"></span><button type="button" class="pp_del" title="Алып тастау"><i class="fal fa-times"></i></button>';
			var sel = r.querySelector('.pp_it'); sel.value = v || '';
			var unit = function () { var o = sel.selectedOptions[0]; return o ? (o.dataset.u || '') : ''; };
			var upd = function () { r.querySelector('.pp_u').textContent = sel.value ? uLab(unit()) : ''; };
			if (q !== undefined) r.querySelector('.pp_q').value = String(W(unit()) ? Math.round(q * 1000 * 10) / 10 : q).replace('.', ',');
			sel.addEventListener('change', upd); upd();
			r.querySelector('.pp_del').addEventListener('click', function () { r.remove(); });
			rows.appendChild(r);
		}
		function open(id) {
			cur = id; var d = id ? P.prep[id] : { n: '', u: 'кг', y: '', parts: [] };
			pop.querySelector('.pp_name').value = d.n; pop.querySelector('.pp_unit').value = d.u || 'шт';
			pop.querySelector('.pp_yield').value = String(d.y || '').replace('.', ',');
			pop.querySelector('.pp_yu').textContent = pop.querySelector('.pp_unit').value;
			pop.querySelector('.cs_pop_t').textContent = id ? d.n : 'Жаңа дайындама';
			rows.innerHTML = ''; d.parts.forEach(function (p) { addRow(p.t + p.id, p.q); }); if (!d.parts.length) addRow();
			pop.hidden = false;
		}
		pop.querySelector('.pp_unit').addEventListener('change', function () { pop.querySelector('.pp_yu').textContent = this.value; });
		document.querySelectorAll('.cs_prep_ed').forEach(function (b) { b.addEventListener('click', function () { open(+b.dataset.id); }); });
		var nb = document.querySelector('.cs_prep_new'); if (nb) nb.addEventListener('click', function () { open(0); });
		pop.querySelectorAll('.cs_pop_bg, .cs_pop_x').forEach(function (b) { b.addEventListener('click', function () { pop.hidden = true; }); });
		pop.querySelector('.pp_add').addEventListener('click', function () { addRow(); });
		if (!csInit.done) { var m = location.search.match(/[?&]prep=(\d+)/); if (m && P.prep[m[1]]) open(+m[1]); }
		csInit.done = 1;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var parts = [], bad = false;
			rows.querySelectorAll('.pp_row').forEach(function (r) {
				var sel = r.querySelector('.pp_it'), q = parseFloat(r.querySelector('.pp_q').value.replace(',', '.'));
				if (!sel.value) return;
				if (!(q > 0)) { bad = true; return; }
				var u = sel.selectedOptions[0].dataset.u;
				parts.push({ t: sel.value[0], id: +sel.value.slice(1), q: W(u) ? q / 1000 : q });
			});
			if (bad) { mess('Мөлшерін жазыңыз', 'warn'); return; }
			var fd = new FormData();
			fd.append('id', cur); fd.append('name', pop.querySelector('.pp_name').value);
			fd.append('unit', pop.querySelector('.pp_unit').value); fd.append('yield', pop.querySelector('.pp_yield').value);
			fd.append('parts', JSON.stringify(parts));
			fetch('/products/cost/get.php?prep_save', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
				t = t.trim();
				if (t == 'yes') { pop.hidden = true; mess('Сақталды'); csTab = 'prep'; history.replaceState(history.state, '', location.pathname + location.search + '#prep'); ajx('.cs_page', csInit); }
				else mess({ name: 'Атауын жазыңыз', exists: 'Мұндай атау бар', yield: 'Шығымын жазыңыз', parts: 'Кем дегенде бір зат қосыңыз', self: 'Дайындама өз құрамына кіре алмайды' }[t] || 'Қате, қайта көріңіз', 'warn');
			}).catch(function () { mess('Байланыс қатесі', 'err'); });
		});
	}
	csInit();
	</script>

<? include "../../block/footer.php"; ?>
