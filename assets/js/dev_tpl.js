// Граммовка шаблондары беті: кесте сүзгісі, өңдеу терезесі, ЖИ ұсыныстары
(function () {
	var G = window.GT || { tpl: {}, cats: [] }, W = window.bw || {}, pop = document.querySelector('.gt_pop'), cur = null;
	function w(k, d) { return W[k] || d; }
	var VN = [w('Economy', 'Эконом'), w('Standard', 'Стандарт'), w('Premium', 'Премиум')];
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function $(s, r) { return (r || document).querySelector(s); }

	// --- кесте сүзгісі
	var fq = $('.gt_q'), fc = $('.gt_cat');
	function filter() {
		var q = fq ? fq.value.trim().toLowerCase() : '', c = fc ? fc.value : '', n = 0;
		document.querySelectorAll('.gt_t tbody tr').forEach(function (r) {
			var ok = (!q || r.dataset.s.indexOf(q) > -1) && (!c || r.dataset.cat === c);
			r.style.display = ok ? '' : 'none'; if (ok) n++;
		});
		var e = $('.gt_empty'); if (e) e.style.display = n ? 'none' : '';
	}
	if (fq) fq.oninput = filter;
	if (fc) fc.onchange = filter;

	// --- өңдеу терезесі
	if (!pop) return;
	$('#gt_cats').innerHTML = G.cats.map(function (c) { return '<option value="' + esc(c) + '">'; }).join('');
	function itemRow(it) {
		it = it || { name: '', unit: 'кг', q: ['', '', ''] };
		return '<tr><td><input class="i_n" placeholder="' + w('Ingredient', 'Құрам') + '" value="' + esc(it.name) + '"></td><td><select class="i_u">' + ['кг', 'л', 'шт'].map(function (u) { return '<option' + (u === it.unit ? ' selected' : '') + '>' + u + '</option>'; }).join('') + '</select></td>' +
			[0, 1, 2].map(function (i) { return '<td><input class="i_q" inputmode="decimal" placeholder="' + VN[i] + '" value="' + esc(it.q[i] === 0 ? '' : it.q[i]) + '"></td>'; }).join('') + '<td><span class="gt_x" title="' + w('Delete', 'Өшіру') + '">&times;</span></td></tr>';
	}
	function sums() {
		[0, 1, 2].forEach(function (v) {
			var g = 0;
			pop.querySelectorAll('.gt_it tbody tr').forEach(function (tr) {
				if (tr.querySelector('.i_u').value === 'шт') return;
				g += parseFloat(String(tr.querySelectorAll('.i_q')[v].value).replace(',', '.')) || 0;
			});
			$('.gt_s' + v, pop).textContent = g ? Math.round(g) + ' ' + w('g', 'г') : '—';
		});
	}
	function open(t) {
		cur = t || { id: 0, name: '', cat: '', keys: '', draft: 0, note: '', items: [] };
		$('.gt_ph', pop).textContent = cur.id ? (cur.draft ? w('AI suggestion', 'ЖИ ұсынысы') : w('Template', 'Шаблон')) : w('New template', 'Жаңа шаблон');
		var nt = $('.gt_note', pop); nt.textContent = cur.note || ''; nt.classList.toggle('on', !!(cur.draft && cur.note));
		$('.t_n', pop).value = cur.name; $('.t_c', pop).value = cur.cat; $('.t_k', pop).value = cur.keys;
		var its = cur.items.length ? cur.items : [null, null, null];
		$('.gt_it tbody', pop).innerHTML = its.map(itemRow).join('');
		$('.gt_sv', pop).style.display = cur.draft ? 'none' : '';
		$('.gt_ok', pop).style.display = cur.draft ? '' : 'none';
		$('.gt_dl', pop).style.display = cur.id ? '' : 'none';
		sums();
		pop.classList.add('pop_bl_act');
		if (!cur.id) setTimeout(function () { $('.t_n', pop).focus(); }, 50);
	}
	document.addEventListener('click', function (e) {
		var tr = e.target.closest('.gt_t tbody tr.lnk');
		if (tr) return open(G.tpl[tr.dataset.id]);
		if (e.target.closest('.gt_new')) return open(null);
	});
	pop.addEventListener('input', sums);
	pop.addEventListener('change', sums);
	pop.addEventListener('click', function (e) {
		if (e.target.closest('.gt_x')) { e.target.closest('tr').remove(); return sums(); }
		if (e.target.closest('.gt_add')) { $('.gt_it tbody', pop).insertAdjacentHTML('beforeend', itemRow()); var l = pop.querySelectorAll('.gt_it tbody .i_n'); l[l.length - 1].focus(); return; }
		if (e.target.closest('.gt_sv')) return save(false);
		if (e.target.closest('.gt_ok')) return save(true);
		if (e.target.closest('.gt_dl')) return devAsk(cur.draft ? w('Reject the suggestion?', 'Ұсынысты өшіреміз бе?') : w('Delete the template?', 'Шаблонды өшіреміз бе?'), function () { post('tpl_del', { id: cur.id }); }, { ok: w('Delete', 'Өшіру'), danger: true });
	});
	function post(act, data, btn) {
		var fd = new FormData(); fd.append('csrf', window.CSRF);
		for (var k in data) fd.append(k, data[k]);
		if (btn) btn.classList.add('loading'), btn.disabled = true;
		return fetch('get.php?' + act, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (x) { return x.json(); }).then(function (d) {
			if (btn) btn.classList.remove('loading'), btn.disabled = false;
			if (d.result !== 'yes') { toast(w('Error', 'Қате') + ': ' + (d.error || d.result), 1); return d; }
			if (act !== 'ai_suggest') { toast(act === 'tpl_del' ? w('Deleted', 'Өшірілді') : w('Saved', 'Сақталды')); setTimeout(function () { location.reload(); }, 500); }
			return d;
		}).catch(function () { if (btn) btn.classList.remove('loading'), btn.disabled = false; toast(w('Error', 'Қате'), 1); return {}; });
	}
	function save(approve) {
		var items = [].map.call(pop.querySelectorAll('.gt_it tbody tr'), function (tr) {
			return { name: tr.querySelector('.i_n').value, unit: tr.querySelector('.i_u').value, q: [].map.call(tr.querySelectorAll('.i_q'), function (i) { return i.value; }) };
		}).filter(function (i) { return i.name.trim(); });
		if (!$('.t_n', pop).value.trim()) { $('.t_n', pop).focus(); return toast(w('Enter the title', 'Атауын жазыңыз'), 1); }
		if (!items.length) return toast(w('Add ingredients', 'Құрам қосыңыз'), 1);
		var d = { id: cur.id || '', name: $('.t_n', pop).value, cat: $('.t_c', pop).value, keys: $('.t_k', pop).value, items: JSON.stringify(items) };
		if (approve) d.approve = 1;
		post('tpl_save', d, $(approve ? '.gt_ok' : '.gt_sv', pop));
	}

	// --- ЖИ: белгіленген тағамдарды 3-тен бөліп жібереміз
	var all = $('.gt_all'), go = $('.gt_ai_go'), prog = $('.gt_prog');
	if (all) all.onchange = function () { document.querySelectorAll('.gt_pick').forEach(function (c) { c.checked = all.checked; }); };
	if (go) go.onclick = function () {
		var names = [].filter.call(document.querySelectorAll('.gt_pick'), function (c) { return c.checked; }).map(function (c) { return c.value; });
		if (!names.length) return toast(w('Pick dishes', 'Тағам таңдаңыз'), 1);
		var parts = []; for (var i = 0; i < names.length; i += 3) parts.push(names.slice(i, i + 3));
		devAsk(w('AI will search recipes for', 'ЖИ граммовка іздейді:') + ' ' + names.length + ' ' + w('dishes', 'тағам') + '. ' + w('About 1-2 minutes per 3 dishes; the cost is logged on the AI cost page.', 'Әр 3 тағамға шамамен 1-2 минут; шығыны «ЖИ шығыны» бетінде жазылады.'), function () {
			var done = 0, made = 0, usd = 0;
			go.disabled = true;
			(function next() {
				if (!parts.length) {
					go.disabled = false;
					prog.textContent = '';
					toast(w('Suggestions ready', 'Ұсыныстар дайын') + ': ' + made + ' · $' + usd.toFixed(2));
					return setTimeout(function () { location.reload(); }, 900);
				}
				var p = parts.shift();
				prog.textContent = w('Searching', 'Ізделуде') + ': ' + p.join(', ') + ' (' + (done + 1) + '/' + (done + 1 + parts.length) + ')';
				post('ai_suggest', { dishes: JSON.stringify(p) }).then(function (d) {
					done++;
					if (d.result === 'yes') { made += d.n; usd += d.usd || 0; next(); }
					else { go.disabled = false; prog.textContent = ''; if (made) setTimeout(function () { location.reload(); }, 1500); }
				});
			})();
		}, { ok: w('Start', 'Бастау') });
	};
})();
