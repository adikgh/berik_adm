// РНП беті: кестені салу, қайта есептеу, сұр ұяшықтарды сақтау
(function () {
	var R = window.rnp;
	if (!R) return;

	var tb = document.getElementById('rnp_tb');
	var saveEl = document.getElementById('rnp_save');
	var rows = R.rows;
	var byKey = {};
	rows.forEach(function (r) { if (r.k) byKey[r.k] = r; });

	// бос күндер JSON-да [] болып келеді — объектіге айналдырамыз
	R.days.forEach(function (d) { if (!R.val[d] || Array.isArray(R.val[d])) R.val[d] = {}; });
	if (!R.plan || Array.isArray(R.plan)) R.plan = {};

	var wd = ['Жс', 'Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб'];

	// ---------- есептеу ----------
	// col: 'plan' | 'fact' | 'YYYY-MM-DD'
	function base(k, col) {
		if (col === 'plan') return num(R.plan[k]);
		if (col === 'fact') { var s = 0; R.days.forEach(function (d) { s += num(R.val[d][k]); }); return s; }
		return num(R.val[col][k]);
	}
	function num(v) { v = Number(v); return isFinite(v) ? v : 0; }

	function calc(col) {
		var memo = {};
		function get(k) {
			if (k in memo) return memo[k];
			var r = byKey[k], v = 0;
			if (!r) v = 0;
			else if (r.t === 'auto' || r.t === 'in') v = base(k, col);
			else if (r.t === 'sum') { r.of.forEach(function (x) { var y = get(x); if (y !== null) v += y; }); }
			else if (r.t === 'calc') {
				var a = get(r.f[1]), b = get(r.f[2]);
				if (r.f[0] === 'div') v = b ? a / b : null;
				else if (r.f[0] === 'pct') v = b ? a / b * 100 : null;
				else if (r.f[0] === 'sub') v = a - b;
				else if (r.f[0] === 'add') v = a + b;
			}
			memo[k] = v;
			return v;
		}
		rows.forEach(function (r) { if (r.k) get(r.k); });
		return memo;
	}

	function fmt(v, f) {
		if (v === null || v === undefined) return '—';
		if (f === 'p') return Math.round(v) + '%';
		var s = Math.round(v).toLocaleString('ru-RU').replace(/ /g, ' ');
		return f === 'c' ? s + ' шт' : s;
	}
	function fmtIn(v) { return (v === undefined || v === null || v === '') ? '' : Math.round(v).toLocaleString('ru-RU').replace(/ /g, ' '); }

	// ---------- кестені салу ----------
	var cols = ['plan', 'fact'].concat(R.days);

	function build() {
		var h = '<thead><tr><th class="rnp_c0">Көрсеткіштер</th><th class="rnp_cp">Жоспар</th><th class="rnp_cf">Факт</th>';
		R.days.forEach(function (d) {
			var dt = new Date(d + 'T00:00:00'), w = dt.getDay();
			var cl = (d === R.today ? ' rnp_today' : '') + (w === 0 || w === 6 ? ' rnp_we' : '');
			h += '<th class="rnp_cd' + cl + '">' + d.substr(8, 2) + '.' + d.substr(5, 2) + '<span>' + wd[w] + '</span></th>';
		});
		h += '</tr></thead><tbody>';

		rows.forEach(function (r) {
			if (r.t === 'gap') { h += '<tr class="rnp_gap"><td colspan="' + (cols.length + 1) + '"></td></tr>'; return; }
			h += '<tr class="rnp_r rnp_' + r.st + ' rnp_t_' + r.t + '" data-k="' + r.k + '"><td class="rnp_c0">' + esc(r.n) + (r.t === 'auto' ? ' <i class="fal fa-database rnp_auto_i" title="Базадан"></i>' : '') + '</td>';
			cols.forEach(function (c) {
				var cls = c === 'plan' ? 'rnp_cp' : (c === 'fact' ? 'rnp_cf' : 'rnp_cd' + (c === R.today ? ' rnp_today' : ''));
				var editable = R.edit && ((r.t === 'in' && c !== 'fact') || (r.t === 'auto' && c === 'plan'));
				if (editable) {
					var raw = c === 'plan' ? R.plan[r.k] : R.val[c][r.k];
					h += '<td class="' + cls + ' rnp_in"><input type="text" inputmode="numeric" data-k="' + r.k + '" data-c="' + c + '" value="' + fmtIn(raw) + '"></td>';
				} else {
					var spread = R.edit && r.t === 'in' && c === 'fact';
					h += '<td class="' + cls + (spread ? ' rnp_spread' : '') + '" data-k="' + r.k + '" data-c="' + c + '"' + (spread ? ' title="Айлық соманы күндерге бөлу"' : '') + '></td>';
				}
			});
			h += '</tr>';
		});
		h += '</tbody>';
		tb.innerHTML = h;
		refresh();
	}

	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

	function refresh() {
		var res = {};
		cols.forEach(function (c) { res[c] = calc(c); });
		var cells = tb.querySelectorAll('td[data-k]');
		for (var i = 0; i < cells.length; i++) {
			var td = cells[i], k = td.getAttribute('data-k'), c = td.getAttribute('data-c'), r = byKey[k];
			var v = res[c][k];
			td.textContent = (r.t === 'auto' || r.t === 'in') && c !== 'plan' && c !== 'fact' && !v ? '' : fmt(v, r.fmt);
			td.classList.toggle('rnp_neg', v !== null && v < 0);
		}
	}

	// ---------- сақтау ----------
	var pending = 0;
	function status(t, cls) { saveEl.textContent = t; saveEl.className = 'rnp_save ' + (cls || ''); }
	function save(items, done) {
		pending++;
		status('Сақталуда...', 'rnp_save_w');
		$.ajax({
			url: '/rnp/get.php?save', type: 'POST', dataType: 'json',
			data: { items: JSON.stringify(items) },
			success: function (d) {
				pending--;
				if (d && d.ok) { if (!pending) status('Сақталды', 'rnp_save_ok'); if (done) done(); }
				else status('Қате: ' + ((d && d.err) || 'сақталмады'), 'rnp_save_err');
			},
			error: function () { pending--; status('Қате: интернетті тексеріңіз', 'rnp_save_err'); }
		});
	}

	function parse(s) {
		s = String(s).replace(/\s/g, '').replace(',', '.');
		if (s === '') return '';
		// қарапайым формула да жазуға болады: 1000+2500
		if (/^[-+*/().\d]+$/.test(s)) { try { var v = Function('return (' + s + ')')(); if (isFinite(v)) return Math.round(v); } catch (e) {} }
		var n = parseFloat(s);
		return isFinite(n) ? Math.round(n) : null;
	}

	tb.addEventListener('focusin', function (e) {
		var el = e.target;
		if (el.tagName !== 'INPUT') return;
		el.value = el.value.replace(/\s/g, '');
		el.dataset.old = el.value;
		setTimeout(function () { el.select(); }, 0);
	});
	tb.addEventListener('focusout', function (e) {
		var el = e.target;
		if (el.tagName !== 'INPUT') return;
		var k = el.dataset.k, c = el.dataset.c;
		var v = parse(el.value);
		if (v === null) { el.value = fmtIn(el.dataset.old); status('Тек сан жазыңыз', 'rnp_save_err'); return; }
		el.value = fmtIn(v);
		if (String(v) === String(el.dataset.old)) return;
		var isPlan = c === 'plan';
		if (isPlan) { if (v === '') delete R.plan[k]; else R.plan[k] = v; }
		else { if (v === '') delete R.val[c][k]; else R.val[c][k] = v; }
		refresh();
		save([{ d: isPlan ? R.month + '-01' : c, k: k, v: v, p: isPlan ? 1 : 0 }]);
	});
	// Enter — төмендегі ұяшыққа, Esc — бас тарту
	tb.addEventListener('keydown', function (e) {
		var el = e.target;
		if (el.tagName !== 'INPUT') return;
		if (e.key === 'Escape') { el.value = el.dataset.old; el.blur(); return; }
		if (e.key !== 'Enter' && e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
		e.preventDefault();
		var list = Array.prototype.slice.call(tb.querySelectorAll('input[data-c="' + el.dataset.c + '"]'));
		var i = list.indexOf(el) + (e.key === 'ArrowUp' ? -1 : 1);
		if (list[i]) list[i].focus(); else el.blur();
	});

	// «Факт» ұяшығы: айлық соманы күндерге тең бөлу
	tb.addEventListener('click', function (e) {
		var td = e.target.closest('td.rnp_spread');
		if (!td) return;
		var k = td.dataset.k, r = byKey[k];
		var s = prompt('«' + r.n + '»: айлық соманы жазыңыз. Ол ' + R.days.length + ' күнге тең бөлінеді (бар мәндер ауысады).', '');
		if (s === null) return;
		var total = parse(s);
		if (total === null || total === '') return;
		var n = R.days.length, per = Math.floor(total / n), rest = total - per * n, items = [];
		R.days.forEach(function (d, i) {
			var v = per + (i === n - 1 ? rest : 0);
			R.val[d][k] = v;
			items.push({ d: d, k: k, v: v, p: 0 });
		});
		save(items, function () {});
		var ins = tb.querySelectorAll('input[data-k="' + k + '"]');
		for (var i = 0; i < ins.length; i++) if (ins[i].dataset.c !== 'plan') ins[i].value = fmtIn(R.val[ins[i].dataset.c][k]);
		refresh();
	});

	build();

	// бүгінгі күнге айналдыру
	var t = tb.querySelector('th.rnp_today');
	var wrap = tb.parentNode;
	if (t) wrap.scrollLeft = Math.max(0, t.offsetLeft - wrap.clientWidth / 2);
})();
