<!-- «Құрамы» попапы: тағамның граммовкасын (не сет құрамын) осы беттен толық реттеу -->
<div class="pop_bl pop_bl2 cpp_block">
	<div class="pop_bl_a cpp_x"></div>
	<div class="pop_bl_c cpp_c">
		<div class="head_c">
			<h4 class="cpp_title">Құрамы</h4>
			<div class="btn btn_dd cpp_x"><i class="fal fa-times"></i></div>
		</div>
		<div class="pop_bl_cl cpp_body">
			<div class="cpp_stats"></div>
			<div class="cpp_tabs">
				<button type="button" class="cpp_tab" data-t="dish">Тағам құрамы (граммовка)</button>
				<button type="button" class="cpp_tab" data-t="set">Сет құрамы</button>
			</div>
			<div class="cpp_list"></div>
			<div class="cpp_add">
				<div class="cpp_srch">
					<i class="fal fa-search"></i>
					<input type="text" class="cpp_q" placeholder="Өнім не дайындама іздеу..." autocomplete="off">
					<div class="cpp_dd"></div>
				</div>
				<div class="cpp_pick" hidden>
					<div class="cpp_pick_nm"></div>
					<input type="text" class="cpp_pick_q" inputmode="decimal" placeholder="0">
					<span class="cpp_pick_u"></span>
					<button type="button" class="btn cpp_pick_ok"><i class="far fa-plus"></i><span>Қосу</span></button>
					<button type="button" class="btn btn_dd cpp_pick_no" title="Болдырмау"><i class="fal fa-times"></i></button>
				</div>
			</div>
			<div class="cpp_hint"></div>
			<a class="cpp_full" href="#"><i class="fal fa-external-link"></i> Тағам бетін толық ашу</a>
		</div>
	</div>
</div>
<style>
	.cpp_block .pop_bl_c{ max-width: 640px; }
	.cpp_title{ white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
	.cpp_stats{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
	.cpp_st{ background: var(--bp-pill, #f5f5f5); border-radius: 12px; padding: 10px 12px; }
	.cpp_st span{ display: block; font-size: 12px; color: var(--bp-muted, #8a8f98); }
	.cpp_st b{ font-size: 17px; }
	.cpp_st.cpp_pos b{ color: var(--bp-green, #0EA768); } .cpp_st.cpp_neg b{ color: #e5484d; }
	.cpp_tabs{ display: flex; gap: 6px; background: var(--bp-pill, #f5f5f5); padding: 4px; border-radius: 12px; margin-bottom: 14px; }
	.cpp_tab{ flex: 1; height: 38px; border: 0; border-radius: 9px; background: transparent; font: inherit; font-size: 14px; font-weight: 600; color: var(--bp-muted, #8a8f98); cursor: pointer; }
	.cpp_tab_on{ background: #fff; color: var(--bp-green, #0EA768); box-shadow: 0 1px 3px rgba(0,0,0,.08); }
	.cpp_list{ border: 1px solid var(--bp-line, #e7e7e7); border-radius: 12px; overflow: hidden; }
	.cpp_r{ display: grid; grid-template-columns: 1fr 118px 84px 34px; gap: 8px; align-items: center; padding: 8px 10px; border-top: 1px solid var(--bp-line, #e7e7e7); }
	.cpp_r:first-child{ border-top: 0; }
	.cpp_r_h{ background: var(--bp-pill, #f5f5f5); font-size: 12px; color: var(--bp-muted, #8a8f98); font-weight: 600; padding: 7px 10px; }
	.cpp_r_f{ background: #fafafa; font-weight: 700; }
	.cpp_r_f.cpp_r_m{ font-weight: 400; color: var(--bp-muted, #8a8f98); font-size: 13px; }
	.cpp_nm{ min-width: 0; }
	.cpp_nm a{ color: inherit; } .cpp_nm a:hover{ color: var(--bp-blue, #1f75a8); text-decoration: underline; }
	.cpp_nm small{ display: block; font-size: 12px; color: var(--bp-muted, #8a8f98); }
	.cpp_nm i{ font-size: 11px; color: var(--bp-blue, #1f75a8); margin-left: 3px; }
	.cpp_qw{ display: flex; align-items: center; gap: 5px; }
	.cpp_qi{ width: 76px; height: 36px; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 8px; text-align: center; font: inherit; font-size: 15px; font-weight: 600; }
	.cpp_qi:focus{ border-color: var(--bp-green, #0EA768); outline: 0; }
	.cpp_qi.cpp_bad{ border-color: #e5484d; }
	.cpp_qi.cpp_ok{ border-color: var(--bp-green, #0EA768); background: #eefaf4; }
	.cpp_u{ font-size: 13px; color: var(--bp-muted, #8a8f98); }
	.cpp_cs{ text-align: right; font-weight: 600; white-space: nowrap; }
	.cpp_del{ width: 32px; height: 32px; border-radius: 8px; border: 0; background: transparent; color: #e5484d; cursor: pointer; font-size: 15px; }
	.cpp_del:hover{ background: #fdecec; }
	.cpp_lk{ color: #b07800 !important; margin-top: 3px; } .cpp_link{ border: 0; background: #fff3d6; color: #8a5d00; border-radius: 6px; padding: 2px 8px; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; margin-left: 4px; } .cpp_link:hover{ background: #ffe7a8; }
	.cpp_empty{ padding: 22px 16px; text-align: center; color: var(--bp-muted, #8a8f98); font-size: 14px; }
	.cpp_add{ margin-top: 12px; }
	.cpp_srch{ position: relative; }
	.cpp_srch > i{ position: absolute; left: 14px; top: 15px; color: var(--bp-muted, #8a8f98); }
	.cpp_q{ width: 100%; height: 46px; padding: 0 12px 0 38px; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 10px; font: inherit; font-size: 15px; }
	.cpp_q:focus{ border-color: var(--bp-green, #0EA768); outline: 0; }
	.cpp_dd{ display: none; position: absolute; left: 0; right: 0; top: 50px; z-index: 5; max-height: 280px; overflow-y: auto; background: #fff; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.1); }
	.cpp_dd_on{ display: block; }
	.cpp_o{ display: flex; justify-content: space-between; gap: 10px; padding: 9px 12px; cursor: pointer; font-size: 14px; }
	.cpp_o:hover, .cpp_o_act{ background: #eefaf4; }
	.cpp_o small{ color: var(--bp-muted, #8a8f98); white-space: nowrap; }
	.cpp_o_g{ padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--bp-muted, #8a8f98); background: #fafafa; }
	.cpp_pick{ display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 10px; border: 1px solid var(--bp-green, #0EA768); border-radius: 10px; background: #f4fbf8; }
	.cpp_pick[hidden], .cpp_srch[hidden], .cpp_tab[hidden]{ display: none; }
	.cpp_pick_nm{ flex: 1; min-width: 150px; font-weight: 600; }
	.cpp_pick_q{ width: 90px; height: 40px; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 8px; text-align: center; font: inherit; font-size: 16px; font-weight: 700; }
	.cpp_pick_ok{ height: 40px; border: 0; display: flex; align-items: center; gap: 6px; font-weight: 700; background: var(--bp-green, #0EA768) !important; color: #fff; cursor: pointer; width: auto !important; }
	.cpp_pick_no{ width: 40px !important; height: 40px; }
	.cpp_hint{ margin-top: 12px; font-size: 13px; color: var(--bp-muted, #8a8f98); line-height: 1.45; }
	.cpp_full{ display: inline-flex; gap: 6px; align-items: center; margin-top: 14px; font-size: 14px; color: var(--bp-blue, #1f75a8); }
	.cpp_load{ opacity: .55; pointer-events: none; }
	@media (max-width: 500px) {
		.cpp_r{ grid-template-columns: 1fr 104px 34px; }
		.cpp_r .cpp_cs{ grid-column: 1 / 2; grid-row: 2; text-align: left; font-size: 13px; color: var(--bp-muted, #8a8f98); }
		.cpp_r_h .cpp_cs{ display: none; }
		.cpp_stats b{ font-size: 15px; }
	}
</style>
<script>
	(function () {
		var pop = document.querySelector('.cpp_block'); if (!pop) return;
		var $ = function (c) { return pop.querySelector(c); };
		var D = null, PID = 0, tab = 'dish', pick = null, ddSel = -1, ddItems = [];
		var msg = function (t) { if (window.mess) mess(t); else alert(t); };
		var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
		var fmt = function (n) { return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' '); };
		var qf = function (q) { return String(Math.round(q * 1000) / 1000).replace('.', ','); };
		var num = function (v) { v = parseFloat(String(v).replace(',', '.').replace(/\s+/g, '')); return v > 0 ? v : 0; };
		var compMap = {};

		var load = function () {
			pop.querySelector('.cpp_body').classList.add('cpp_load');
			return fetch('/products/item/get.php?comp_load&id=' + PID, { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (d) {
					pop.querySelector('.cpp_body').classList.remove('cpp_load');
					if (!d.ok) { msg('Қате, қайта көріңіз'); return; }
					D = d; compMap = {}; d.comps.forEach(function (c) { compMap[c.id] = c; });
					render();
				})
				.catch(function () { pop.querySelector('.cpp_body').classList.remove('cpp_load'); msg('Қате, қайта көріңіз'); });
		};
		var send = function (act, data) {
			var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
			return fetch('/products/item/get.php?' + act, { method: 'POST', body: fd, credentials: 'same-origin' })
				.then(function (r) { return r.text(); })
				.then(function (t) { t = t.trim(); if (t != 'yes') { msg(t == 'qty' ? 'Мөлшерін дұрыс жазыңыз' : 'Қате, қайта көріңіз'); throw 0; } });
		};

		var totals = function () {
			var dish = 0, set = 0;
			D.rows.forEach(function (r) { dish += r.cost; });
			D.set.forEach(function (r) { set += r.cost; });
			var isSet = tab == 'set';
			var cost = isSet ? set + (D.set.length ? D.over : 0) : dish;
			return { dish: dish, set: set, cost: cost };
		};
		var render = function () {
			$('.cpp_title').textContent = D.name;
			$('.cpp_full').href = '/products/item/?id=' + D.id;
			pop.querySelectorAll('.cpp_tab').forEach(function (b) { b.classList.toggle('cpp_tab_on', b.dataset.t == tab); });
			$('.cpp_tab[data-t="set"]').hidden = !D.ready;
			var t = totals(), pr = D.price - t.cost;
			$('.cpp_stats').innerHTML =
				'<div class="cpp_st"><span>Бағасы</span><b>' + fmt(D.price) + ' тг</b></div>' +
				'<div class="cpp_st"><span>' + (tab == 'set' ? 'Толық себес' : 'Себес') + '</span><b>' + fmt(t.cost) + ' тг</b></div>' +
				'<div class="cpp_st ' + (pr >= 0 ? 'cpp_pos' : 'cpp_neg') + '"><span>Пайда</span><b>' + fmt(pr) + ' тг' + (D.price && t.cost ? ' · ' + Math.round(pr * 100 / D.price) + '%' : '') + '</b></div>';
			var h = '';
			if (tab == 'dish') {
				h += '<div class="cpp_r cpp_r_h"><div>Атауы</div><div>Мөлшері</div><div class="cpp_cs">Себес</div><div></div></div>';
				if (!D.rows.length) h += '<div class="cpp_empty">Әзірге бос. Төменнен өнімді іздеп, граммын жазып қосыңыз.</div>';
				D.rows.forEach(function (r) {
					var c = compMap[r.comp];
					var nm = c ? (c.prep ? '<a href="/products/cost/?prep=' + c.id + '#prep" target="_blank" title="Рецептін ашу">' + esc(c.nm) + '<i class="fal fa-layer-group"></i></a>' : esc(c.nm)) : '<m>Өшірілген құрам</m>';
					h += '<div class="cpp_r" data-id="' + r.id + '"><div class="cpp_nm">' + nm + (c ? '<small>' + fmt(c.price) + ' тг / ' + esc(c.pu) + '</small>' : '') + '</div>' +
						'<div class="cpp_qw"><input class="cpp_qi" data-k="ci" value="' + qf(r.qty) + '" inputmode="decimal"><span class="cpp_u">' + esc(c ? c.qu : 'шт') + '</span></div>' +
						'<div class="cpp_cs">' + fmt(r.cost) + ' тг</div><button type="button" class="cpp_del" data-k="ci" title="Алып тастау"><i class="fal fa-times"></i></button></div>';
				});
				if (D.rows.length) h += '<div class="cpp_r cpp_r_f"><div>Барлығы</div><div></div><div class="cpp_cs">' + fmt(t.dish) + ' тг</div><div></div></div>';
				$('.cpp_q').placeholder = 'Өнім не дайындама іздеу...';
				$('.cpp_hint').innerHTML = 'Мөлшерді өзгертіп Enter басыңыз, не басқа жерді басыңыз: бірден сақталады. Бағасы 1 кг (1 л) үшін болса, мөлшері граммен (мл) жазылады.';
			} else {
				h += '<div class="cpp_r cpp_r_h"><div>Атауы</div><div>Саны</div><div class="cpp_cs">Себес</div><div></div></div>';
				if (!D.set.length) h += '<div class="cpp_empty">Сет болса, ішіндегі тағамдарды және қорап, сашэ сияқты заттарды төменнен қосыңыз.</div>';
				D.set.forEach(function (r) {
					var nm = r.pid ? '<a href="#" class="cpp_go" data-id="' + r.pid + '" title="Құрамын ашу">' + esc(r.nm) + '<i class="fal fa-layer-group"></i></a>' : esc(r.nm);
					var lk = r.menu ? '<small class="cpp_lk">Мәзірде бөлек позиция бар: <b>' + esc(r.menu_nm) + '</b> <button type="button" class="cpp_link" data-id="' + r.id + '" data-p="' + r.menu + '"><i class="fal fa-link"></i> Позицияға байлау</button></small>' : '';
					h += '<div class="cpp_r" data-id="' + r.id + '"><div class="cpp_nm">' + nm + '<small>' + fmt(r.unit) + ' тг / шт' + (r.pid ? ' · мәзір позициясы' : '') + '</small>' + lk + '</div>' +
						'<div class="cpp_qw"><input class="cpp_qi" data-k="si" value="' + qf(r.qty) + '" inputmode="decimal"><span class="cpp_u">шт</span></div>' +
						'<div class="cpp_cs">' + fmt(r.cost) + ' тг</div><button type="button" class="cpp_del" data-k="si" title="Алып тастау"><i class="fal fa-times"></i></button></div>';
				});
				if (D.set.length) {
					h += '<div class="cpp_r cpp_r_f cpp_r_m"><div>Тағамдар себесі</div><div></div><div class="cpp_cs">' + fmt(t.set) + ' тг</div><div></div></div>';
					h += '<div class="cpp_r cpp_r_f cpp_r_m"><div>+ бір заказға шығын (айлық, аренда, к.у., реклама)</div><div></div><div class="cpp_cs">' + fmt(D.over) + ' тг</div><div></div></div>';
					h += '<div class="cpp_r cpp_r_f"><div>Толық себес</div><div></div><div class="cpp_cs">' + fmt(t.cost) + ' тг</div><div></div></div>';
				}
				$('.cpp_q').placeholder = 'Позиция, готовка не тауар іздеу (кола, ролл, қорап)...';
				$('.cpp_hint').innerHTML = 'Сетке мәзір позициясын (ролл, кола), готовканы не тауарды тікелей қосуға болады. Позиция атауын бассаңыз, оның граммовкасы ашылады. «Позицияға байлау» басса, сеттегі зат мәзірдегі сол позицияның өзі болады, себесі бір жерден есептеледі.';
			}
			$('.cpp_list').innerHTML = h;
		};

		// мөлшерді өзгерту
		pop.addEventListener('change', function (e) {
			var i = e.target; if (!i.classList.contains('cpp_qi')) return;
			var id = i.closest('.cpp_r').dataset.id, v = num(i.value);
			if (!v) { i.classList.add('cpp_bad'); return; }
			i.classList.remove('cpp_bad');
			send(i.dataset.k + '_qty', { id: id, quantity: String(v) }).then(function () { return load(); }).then(function () {
				var n = pop.querySelector('.cpp_r[data-id="' + id + '"] .cpp_qi'); if (n) { n.classList.add('cpp_ok'); setTimeout(function () { n.classList.remove('cpp_ok'); }, 900); }
			}).catch(function () {});
		});
		pop.addEventListener('keydown', function (e) { if (e.key == 'Enter' && e.target.classList.contains('cpp_qi')) e.target.blur(); });
		pop.addEventListener('click', function (e) {
			var d = e.target.closest('.cpp_del');
			if (d) {
				var r = d.closest('.cpp_r'), nm = r.querySelector('.cpp_nm').childNodes[0].textContent;
				if (!confirm('«' + nm + '» алып тастау керек пе?')) return;
				send(d.dataset.k + '_del', { id: r.dataset.id }).then(load).catch(function () {});
				return;
			}
			var l = e.target.closest('.cpp_link');
			if (l) { send('si_link', { id: l.dataset.id, product: l.dataset.p }).then(load).catch(function () {}); return; }
			var g = e.target.closest('.cpp_go');
			if (g) { e.preventDefault(); open(+g.dataset.id, 'dish'); return; }
			var tb = e.target.closest('.cpp_tab');
			if (tb) { tab = tb.dataset.t; closePick(); render(); return; }
			if (!e.target.closest('.cpp_srch')) $('.cpp_dd').classList.remove('cpp_dd_on');
		});

		// іздеп қосу
		var options = function () {
			if (tab == 'dish') return D.comps.map(function (c) { return { v: c.id, nm: c.nm, sub: fmt(c.price) + ' тг / ' + c.pu + (c.prep ? ' · дайындама' : ''), u: c.qu }; });
			return D.dishes.filter(function (x) { return !x.set; }).map(function (x) { return { v: x.v, nm: x.nm, sub: x.cost ? fmt(x.cost) + ' тг' : 'позиция', u: 'шт', g: 'Мәзір позициялары' }; })
				.concat(D.comps.map(function (c) { return { v: 'c' + c.id, nm: c.nm, sub: fmt(c.price) + ' тг / ' + c.pu, u: 'шт', g: 'Готовкалар мен құрамдар' }; }))
				.concat((D.raws || []).map(function (x) { return { v: x.v, nm: x.nm, sub: fmt(x.cost) + ' тг / ' + x.u, u: 'шт', g: 'Тауарлар (дайын күйінде сетке)' }; }));
		};
		var drawDD = function () {
			var q = $('.cpp_q').value.trim().toLowerCase(), h = '', g = null;
			ddItems = options().filter(function (o) { return !q || o.nm.toLowerCase().indexOf(q) > -1; }).slice(0, 60);
			ddItems.forEach(function (o, i) {
				if (o.g && o.g != g) { g = o.g; h += '<div class="cpp_o_g">' + esc(g) + '</div>'; }
				h += '<div class="cpp_o' + (i == ddSel ? ' cpp_o_act' : '') + '" data-i="' + i + '"><span>' + esc(o.nm) + '</span><small>' + esc(o.sub) + '</small></div>';
			});
			$('.cpp_dd').innerHTML = h || '<div class="cpp_empty">Табылмады</div>';
			$('.cpp_dd').classList.add('cpp_dd_on');
		};
		var choose = function (o) {
			pick = o; $('.cpp_dd').classList.remove('cpp_dd_on');
			$('.cpp_srch').hidden = true; $('.cpp_pick').hidden = false;
			$('.cpp_pick_nm').textContent = o.nm; $('.cpp_pick_u').textContent = o.u;
			$('.cpp_pick_q').value = tab == 'set' ? '1' : ''; $('.cpp_pick_q').focus();
		};
		var closePick = function () { pick = null; $('.cpp_pick').hidden = true; $('.cpp_srch').hidden = false; $('.cpp_q').value = ''; $('.cpp_dd').classList.remove('cpp_dd_on'); };
		$('.cpp_q').addEventListener('focus', function () { ddSel = -1; drawDD(); });
		$('.cpp_q').addEventListener('input', function () { ddSel = 0; drawDD(); });
		$('.cpp_q').addEventListener('keydown', function (e) {
			if (e.key == 'ArrowDown') { ddSel = Math.min(ddSel + 1, ddItems.length - 1); drawDD(); e.preventDefault(); }
			else if (e.key == 'ArrowUp') { ddSel = Math.max(ddSel - 1, 0); drawDD(); e.preventDefault(); }
			else if (e.key == 'Enter' && ddItems[Math.max(ddSel, 0)]) { e.preventDefault(); choose(ddItems[Math.max(ddSel, 0)]); }
			else if (e.key == 'Escape') $('.cpp_dd').classList.remove('cpp_dd_on');
		});
		$('.cpp_dd').addEventListener('click', function (e) { var o = e.target.closest('.cpp_o'); if (o) choose(ddItems[+o.dataset.i]); });
		var add = function () {
			if (!pick) return;
			var v = num($('.cpp_pick_q').value); if (!v) { $('.cpp_pick_q').classList.add('cpp_bad'); $('.cpp_pick_q').focus(); return; }
			$('.cpp_pick_q').classList.remove('cpp_bad');
			var p = tab == 'dish' ? send('ci_add', { id: PID, comp: pick.v, quantity: String(v) }) : send('si_add', { id: PID, item: pick.v, quantity: String(v) });
			p.then(function () { closePick(); return load(); }).then(function () { $('.cpp_q').focus(); }).catch(function () {});
		};
		$('.cpp_pick_ok').addEventListener('click', add);
		$('.cpp_pick_q').addEventListener('keydown', function (e) { if (e.key == 'Enter') add(); else if (e.key == 'Escape') closePick(); });
		$('.cpp_pick_no').addEventListener('click', closePick);

		var open = function (id, t) {
			PID = id; D = null; closePick();
			$('.cpp_title').textContent = 'Құрамы'; $('.cpp_list').innerHTML = ''; $('.cpp_stats').innerHTML = '';
			pop.classList.add('pop_bl_act');
			load().then(function () {
				if (!D) return;
				tab = t || (D.set.length && !D.rows.length ? 'set' : 'dish');
				render();
			});
		};
		pop.querySelectorAll('.cpp_x').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
		document.addEventListener('keydown', function (e) { if (e.key == 'Escape' && pop.classList.contains('pop_bl_act') && !pick) pop.classList.remove('pop_bl_act'); });
		document.addEventListener('click', function (e) {
			var b = e.target.closest('.cpp_open'); if (!b) return;
			e.preventDefault(); open(+b.dataset.id);
		});
	})();
</script>
