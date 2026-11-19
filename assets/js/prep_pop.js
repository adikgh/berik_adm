// «Дайындама» попапы (products/cost/prep_pop.php). Кез келген бетте:
//   openPrep(null, { name: 'Соус спайси', onSave: function (r) {} })  — жаңа дайындама
//   openPrep(123)                                                       — бар дайындаманы өзгерту
// Сақталғанда: onSave(r) және document-ке CustomEvent 'prep:saved', e.detail = { id, name, unit, cost } (cost — 1 бірлік себесі, тг)
(function () {
	var pop = document.querySelector('.prp'); if (!pop) return;
	var $ = function (s) { return pop.querySelector(s); };
	var form = $('.prp_c'), rows = $('.prp_rows'), st = $('.prp_st'), tq = $('.prp_tq'), dd = $('.prp_dd');
	var P = null, cur = 0, opt = {}, idx = {}, ddItems = [], ddSel = -1, tm = 0, busy = false;
	var w = function (k, d) { return (window.bw && window.bw[k]) || d; };
	var msg = function (t, k) { if (window.mess) mess(t, k); else alert(t); };
	var esc = function (t) { return String(t == null ? '' : t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
	var W = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' || u == 'л'; };
	var uLab = function (u) { u = (u || '').toLowerCase().trim(); return u == 'кг' ? 'г' : (u == 'л' ? 'мл' : (u || 'шт')); };
	var fmt = function (n) { return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' '); };
	var num = function (v) { v = parseFloat(String(v).replace(',', '.').replace(/\s+/g, '')); return v > 0 ? v : 0; };
	var TPL = '/products/tpl/get.php', COST = '/products/cost/get.php';

	function post(url, data) {
		var fd = new FormData(); Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
			try { return JSON.parse(t); } catch (e) { return { result: t.trim() }; }
		});
	}
	function status(t, kind) {
		if (!st) return;
		st.hidden = !t; st.className = 'prp_st' + (kind ? ' ' + kind : ''); st.textContent = t || '';
		form.classList.toggle('busy', kind == 'load'); busy = kind == 'load';
		var ai = $('.prp_ai'); if (ai) ai.disabled = busy;
	}
	function setData(d) {
		P = d; idx = {};
		P.raw.forEach(function (x) { idx['r' + x.id] = x; });
		P.comp.forEach(function (x) { idx['c' + x.id] = x; });
	}
	function load() {
		return fetch(COST + '?prep_data', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(setData);
	}
	function opts() {
		return '<option value="">' + esc(w('pp Choose', 'Таңдаңыз')) + '</option><optgroup label="' + esc(w('pp Raw', 'Шикізат')) + '">' +
			P.raw.map(function (x) { return '<option value="r' + x.id + '">' + esc(x.n) + '</option>'; }).join('') +
			'</optgroup><optgroup label="' + esc(w('pp Semi or comp', 'Дайындама / құрам')) + '">' +
			P.comp.filter(function (x) { return x.id != cur; }).map(function (x) { return '<option value="c' + x.id + '">' + esc(x.n) + (x.s ? ' ●' : '') + '</option>'; }).join('') + '</optgroup>';
	}
	function addRow(v, q) {
		var r = document.createElement('div'); r.className = 'prp_row';
		r.innerHTML = '<select class="prp_in prp_it">' + opts() + '</select><input class="prp_in prp_q" inputmode="decimal"><span class="prp_u"></span><span class="prp_rc"></span>' +
			'<button type="button" class="prp_del" title="' + esc(w('pp Remove', 'Алып тастау')) + '"><i class="fal fa-times"></i></button>';
		var sel = r.querySelector('.prp_it'); sel.value = v || '';
		var it = idx[sel.value];
		if (q !== undefined && it) r.querySelector('.prp_q').value = String(W(it.u) ? Math.round(q * 1000 * 10) / 10 : q).replace('.', ',');
		rows.appendChild(r); upd(r);
	}
	function upd(r) {
		var it = idx[r.querySelector('.prp_it').value], q = num(r.querySelector('.prp_q').value), rc = r.querySelector('.prp_rc');
		r.querySelector('.prp_u').textContent = it ? uLab(it.u) : '';
		if (!it) { rc.textContent = ''; return; }
		rc.classList.toggle('np', !(it.p > 0));
		rc.textContent = it.p > 0 ? fmt((W(it.u) ? q / 1000 : q) * it.p) + ' тг' : w('pp No price', 'бағасыз');
	}
	function sum() {
		var s = 0, np = 0;
		rows.querySelectorAll('.prp_row').forEach(function (r) {
			upd(r);
			var it = idx[r.querySelector('.prp_it').value], q = num(r.querySelector('.prp_q').value);
			if (!it || !q) return;
			if (!(it.p > 0)) np++;
			s += (W(it.u) ? q / 1000 : q) * (it.p || 0);
		});
		var y = num($('.prp_yield').value), u = $('.prp_unit').value;
		$('.prp_sum').innerHTML = s ? esc(w('pp Total', 'Барлығы')) + ': <b>' + fmt(s) + ' тг</b>' + (y ? ' · ' + esc(w('pp Cost per', 'себесі')) + ' <b>' + fmt(s / y) + ' тг / ' + esc(u) + '</b>' : '') +
			(np ? ' · <span style="color:#b45309">' + np + ' ' + esc(w('pp items without price', 'заттың бағасы жоқ')) + '</span>' : '') : '';
	}
	function fill(f) {
		// бар дайындаманы өзгертіп отырса — сол қалады; жаңасы болса, аттас бос құрам сол күйі толтырылады
		if (f.id && !opt.edit) cur = f.id;
		if (!opt.edit || !$('.prp_name').value) $('.prp_name').value = f.name; $('.prp_unit').value = f.unit || 'кг'; $('.prp_yu').textContent = $('.prp_unit').value;
		$('.prp_yield').value = String(f.yield || '').replace('.', ',');
		rows.innerHTML = ''; f.parts.forEach(function (p) { addRow(p.t + p.id, p.q); }); if (!f.parts.length) addRow();
		sum();
	}
	function open(id, o) {
		opt = o || {}; cur = +id || 0; opt.edit = !!cur;
		status('');
		var go = function () {
			var d = cur && P.prep[cur] ? P.prep[cur] : { n: opt.name || '', u: 'кг', y: '', parts: [] };
			if (cur && !P.prep[cur]) { var c = idx['c' + cur]; if (c) { d.n = c.n; d.u = c.u || 'кг'; } }
			$('.prp_t').textContent = cur ? d.n : w('pp New semi', 'Жаңа дайындама');
			$('.prp_name').value = '';
			fill({ name: d.n, unit: d.u || 'кг', yield: d.y, parts: d.parts });
			if (tq) { tq.value = ''; dd.classList.remove('on'); }
			pop.hidden = false;
			setTimeout(function () { (tq && !cur ? tq : $('.prp_name')).focus(); }, 30);
			// жаңа дайындама атымен ашылса: шаблоны болса бірден толтырамыз
			if (!cur && opt.name && tq) { tq.value = opt.name; search(true); }
		};
		(P ? Promise.resolve() : load()).then(go).catch(function () { msg(w('pp Error', 'Қате, қайта көріңіз'), 'err'); });
	}
	function close() { pop.hidden = true; }

	// ---- шаблон іздеу
	function search(auto) {
		var q = tq.value.trim();
		fetch(TPL + '?semi_list&q=' + encodeURIComponent(q), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
			ddItems = (d.tpls || []).map(function (t) { return { id: t.id, n: t.name, sub: (t.yield ? t.yield + ' ' + t.unit : '') + (t.draft ? ' · ' + w('pp AI draft', 'ЖИ, тексерілмеген') : '') }; });
			if (q && d.ai) ddItems.push({ ai: 1, n: q });
			if (auto) {
				var ex = ddItems.filter(function (x) { return !x.ai && x.n.toLowerCase() == q.toLowerCase(); })[0];
				if (ex) return pick(ex);
			}
			ddSel = ddItems.length && !ddItems[0].ai ? 0 : -1; draw();
		}).catch(function () {});
	}
	function draw() {
		dd.innerHTML = ddItems.map(function (o, i) {
			return o.ai ? '<div class="prp_o prp_oai' + (i == ddSel ? ' act' : '') + '" data-i="' + i + '"><span><i class="fal fa-sparkles"></i> «' + esc(o.n) + '» — ' + esc(w('pp AI fill', 'ЖИ толтырсын')) + '</span><small>' + esc(w('pp AI no template', 'шаблон жоқ')) + '</small></div>'
				: '<div class="prp_o' + (i == ddSel ? ' act' : '') + '" data-i="' + i + '"><span>' + esc(o.n) + '</span><small>' + esc(o.sub) + '</small></div>';
		}).join('') || '<div class="prp_o"><small>' + esc(w('pp Nothing found', 'Табылмады')) + '</small></div>';
		dd.classList.add('on');
	}
	function after(d, note) {
		if (d.data) setData(d.data);
		fill(d.form);
		var ok = w('pp Filled from template', 'Шаблоннан толтырылды') + (d.form.created ? ' · ' + w('pp New items', 'жаңа зат ашылды') + ': ' + d.form.created : '') + (note || '');
		if (d.form.note) ok += ' · ' + d.form.note;
		if (d.noprice && d.noprice.length) {
			status(w('pp Pricing', 'Жаңа заттардың бағасы анықталуда (база, ЖИ)…') + ' ' + d.noprice.length, 'load');
			post(TPL + '?prices', { ids: JSON.stringify(d.noprice) }).then(function (r) {
				return load().then(function () {
					sum(); rows.querySelectorAll('.prp_row').forEach(upd);
					status(ok + ' · ' + w('pp Prices filled', 'бағасы толды') + ': ' + (r.n || 0) + (r.error ? ' (' + r.error + ')' : ''), r.error ? 'err' : '');
				});
			}).catch(function () { status(ok, ''); });
		} else status(ok, '');
	}
	function pick(o) {
		dd.classList.remove('on');
		if (busy) return;
		var v = $('.prp_var').value;
		if (o.ai) return ai(o.n);
		tq.value = o.n;
		status(w('pp Loading', 'Толтырылуда…'), 'load');
		post(TPL + '?semi_fill', { tpl: o.id, variant: v }).then(function (d) {
			if (d.result != 'yes') { status(d.error || w('pp Error', 'Қате, қайта көріңіз'), 'err'); return; }
			after(d);
		}).catch(function () { status(w('pp Connection error', 'Байланыс қатесі'), 'err'); });
	}
	function ai(name) {
		name = (name || tq.value || $('.prp_name').value).trim();
		if (!name) { msg(w('pp Type a name', 'Дайындаманың атын жазыңыз'), 'warn'); (tq || $('.prp_name')).focus(); return; }
		status(w('pp AI working', 'ЖИ рецепт құрастыруда: базадағы ұқсас рецептер мен интернет… (30-90 сек)'), 'load');
		post(TPL + '?semi_ai', { name: name, variant: $('.prp_var').value }).then(function (d) {
			if (d.result != 'yes') { status((d.error || w('pp Error', 'Қате, қайта көріңіз')) + ' · ' + w('pp Fill by hand', 'құрамын қолмен толтыруға болады'), 'err'); return; }
			after(d, d.ai ? ' · ' + w('pp AI made', 'ЖИ жаңа шаблон жасады') + ' (' + d.ai + ')' : '');
		}).catch(function () { status(w('pp AI timeout', 'ЖИ жауап бермеді, қайталаңыз') + ' · ' + w('pp Fill by hand', 'құрамын қолмен толтыруға болады'), 'err'); });
	}
	if (tq) {
		tq.addEventListener('focus', function () { search(); });
		tq.addEventListener('input', function () { clearTimeout(tm); tm = setTimeout(search, 250); });
		tq.addEventListener('keydown', function (e) {
			if (e.key == 'ArrowDown') { ddSel = Math.min(ddSel + 1, ddItems.length - 1); draw(); e.preventDefault(); }
			else if (e.key == 'ArrowUp') { ddSel = Math.max(ddSel - 1, 0); draw(); e.preventDefault(); }
			else if (e.key == 'Enter') { e.preventDefault(); var o = ddItems[Math.max(ddSel, 0)]; if (o) pick(o); }
			else if (e.key == 'Escape') dd.classList.remove('on');
		});
		dd.addEventListener('click', function (e) { var o = e.target.closest('.prp_o[data-i]'); if (o) pick(ddItems[+o.dataset.i]); });
		$('.prp_ai').addEventListener('click', function () { ai(); });
	}
	pop.addEventListener('click', function (e) {
		if (tq && !e.target.closest('.prp_srch')) dd.classList.remove('on');
		var d = e.target.closest('.prp_del'); if (d) { d.closest('.prp_row').remove(); sum(); }
	});
	pop.addEventListener('change', function (e) { if (e.target.closest('.prp_row') || e.target.classList.contains('prp_yield')) sum(); });
	pop.addEventListener('input', function (e) { if (e.target.classList.contains('prp_q') || e.target.classList.contains('prp_yield')) sum(); });
	$('.prp_unit').addEventListener('change', function () { $('.prp_yu').textContent = this.value; sum(); });
	pop.querySelectorAll('.prp_bg, .prp_x').forEach(function (b) { b.addEventListener('click', close); });
	document.addEventListener('keydown', function (e) { if (e.key == 'Escape' && !pop.hidden && !(dd && dd.classList.contains('on'))) close(); });
	$('.prp_add').addEventListener('click', function () { addRow(); });

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		if (busy) return;
		var parts = [], bad = false;
		rows.querySelectorAll('.prp_row').forEach(function (r) {
			var v = r.querySelector('.prp_it').value, q = num(r.querySelector('.prp_q').value);
			if (!v) return;
			if (!q) { bad = true; return; }
			parts.push({ t: v[0], id: +v.slice(1), q: W(idx[v] && idx[v].u) ? q / 1000 : q });
		});
		if (bad) { msg(w('pp Enter amount', 'Мөлшерін жазыңыз'), 'warn'); return; }
		post(COST + '?prep_save&json', { id: cur, name: $('.prp_name').value, unit: $('.prp_unit').value, yield: $('.prp_yield').value, parts: JSON.stringify(parts) }).then(function (r) {
			if (r.result == 'yes') {
				close(); msg(w('pp Saved', 'Сақталды')); P = null; // келесі ашқанда тізім жаңарады
				if (opt.onSave) opt.onSave(r);
				document.dispatchEvent(new CustomEvent('prep:saved', { detail: { id: r.id, name: r.name, unit: r.unit, cost: r.cost } }));
			} else msg({ name: w('pp Enter title', 'Атауын жазыңыз'), exists: w('pp Name exists', 'Мұндай атау бар'), yield: w('pp Enter yield', 'Шығымын жазыңыз'),
				parts: w('pp Add one item', 'Кем дегенде бір зат қосыңыз'), self: w('pp Self', 'Дайындама өз құрамына кіре алмайды') }[r.result] || w('pp Error', 'Қате, қайта көріңіз'), 'warn');
		}).catch(function () { msg(w('pp Connection error', 'Байланыс қатесі'), 'err'); });
	});

	window.openPrep = open;
})();
