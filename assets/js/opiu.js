// ОПиУ мен РНП беттері: кестені салу, қайта есептеу, сұр ұяшықтарды сақтау
// Кезең ауысқанда не «Запис қосу» жабылғанда бет жаңармайды: .st_page AJAX-пен келеді де, rnpInit() жаңа деректермен қайта салады
function rnpInit() {
	var R = window.rnp;
	if (!R) return;
	$('.rnp_fab').remove();

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
	// R.noplan — жоспар бағаны жоқ (РНП), R.fact_label — «Факт» бағанының аты
	// R.single — бір күннің көрінісі (Толтыру не Күн): тек сол күннің бағаны, басқа дизайн
	var cols = R.single ? R.days.slice() : (R.noplan ? [] : ['plan']).concat(['fact'], R.days);
	if (R.single) tb.classList.add('rnp_tb_single');

	function build() {
		var h = '<thead><tr><th class="rnp_c0">Көрсеткіштер</th>' + (R.single ? '' : (R.noplan ? '' : '<th class="rnp_cp">Жоспар</th>') + '<th class="rnp_cf">' + (R.fact_label || 'Факт') + '</th>');
		R.days.forEach(function (d) {
			// R.heads — бағандардың дайын аттары (РНП: апта, ай); жоқ болса күн мен апта күні
			var hd = R.heads && R.heads[d];
			if (!hd) { var dt = new Date(d + 'T00:00:00'), w = dt.getDay(); hd = [d.substr(8, 2) + '.' + d.substr(5, 2), wd[w], w === 0 || w === 6]; }
			var cl = (d === R.today ? ' rnp_today' : '') + (hd[2] ? ' rnp_we' : '');
			if (R.single) hd = [R.title || hd[0], ''];
			// Қарауда күнді басса — сол күнді толтыруға өтеді (R.fill — Толтыру сілтемесі)
			var go = R.fill && !R.single && /^\d{4}-\d{2}-\d{2}$/.test(d);
			h += '<th class="rnp_cd' + cl + (go ? ' rnp_go' : '') + '"' + (go ? ' data-go="' + d + '" title="Осы күнді толтыру"' : '') + '>' + (go ? '<i class="fal fa-pen rnp_go_i"></i>' : '') + esc(hd[0]) + '<span>' + esc(hd[1]) + '</span></th>';
		});
		h += '</tr></thead><tbody>';

		rows.forEach(function (r) {
			if (r.hide) return; // есептеуге ғана керек жасырын жол
			if (r.t === 'gap') { h += '<tr class="rnp_gap"><td colspan="' + (cols.length + 1) + '"></td></tr>'; return; }
			if (r.t === 'title') { h += '<tr class="rnp_title"><td class="rnp_c0">' + esc(r.n) + '</td><td colspan="' + cols.length + '"></td></tr>'; return; }
			if (r.t === 'sec') { h += '<tr class="rnp_sec"><td class="rnp_c0">' + esc(r.n) + '</td><td colspan="' + cols.length + '"></td></tr>'; return; }
			h += '<tr class="rnp_r rnp_' + r.st + ' rnp_t_' + r.t + '" data-k="' + r.k + '"><td class="rnp_c0">' + esc(r.n) + (r.t === 'auto' ? ' <i class="fal fa-database rnp_auto_i" title="Базадан"></i>' : '') + '</td>';
			cols.forEach(function (c) {
				var cls = c === 'plan' ? 'rnp_cp' : (c === 'fact' ? 'rnp_cf' : 'rnp_cd' + (c === R.today ? ' rnp_today' : ''));
				// болашақ күндер толтырылмайды (бүгіннен кейін)
				var editable = R.edit && ((r.t === 'in' && c !== 'fact' && !(R.today_bd && c > R.today_bd)) || (r.t === 'auto' && c === 'plan'));
				if (editable) {
					var raw = c === 'plan' ? R.plan[r.k] : R.val[c][r.k];
					h += '<td class="' + cls + ' rnp_in"><input type="text" inputmode="numeric" data-k="' + r.k + '" data-c="' + c + '" value="' + fmtIn(raw) + '"></td>';
				} else {
					var spread = false; // айлық соманы бөлу алынды: тек күндік толтыру
					var go2 = R.fill && !R.single && r.t === 'in' && /^\d{4}-\d{2}-\d{2}$/.test(c);
					h += '<td class="' + cls + (spread ? ' rnp_spread' : '') + (go2 ? ' rnp_go' : '') + '" data-k="' + r.k + '" data-c="' + c + '"' + (go2 ? ' data-go="' + c + '" title="Осы күнді толтыру"' : '') + '></td>';
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
			td.textContent = (r.t === 'auto' || r.t === 'in') && c !== 'plan' && c !== 'fact' && !R.single && !v ? '' : fmt(v, r.fmt);
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
			url: (R.url || '/opiu/get.php') + '?save', type: 'POST', dataType: 'json',
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

	// Қарау: күнді не сұр жолдың ұяшығын басса — сол күннің Толтыру бетіне өтеді
	tb.addEventListener('click', function (e) {
		var el = e.target.closest('[data-go]');
		if (!el) return;
		if (R.pop) openPop(el.getAttribute('data-go')); else location.href = R.fill + el.getAttribute('data-go');
	});

	// ---------- «Запис қосу»: оң жақ төменгі батырма + терезе (тек толтыратын жолдар) ----------
	var popChanged = false;
	var mn = ['қаңтар', 'ақпан', 'наурыз', 'сәуір', 'мамыр', 'маусым', 'шілде', 'тамыз', 'қыркүйек', 'қазан', 'қараша', 'желтоқсан'];
	function ymd(t) { var z = function (n) { return (n < 10 ? '0' : '') + n; }; return t.getFullYear() + '-' + z(t.getMonth() + 1) + '-' + z(t.getDate()); }
	function addDays(d, n) { var t = new Date(d + 'T00:00:00'); t.setDate(t.getDate() + n); return ymd(t); }
	function dayName(d) { var t = new Date(d + 'T00:00:00'); return t.getDate() + ' ' + mn[t.getMonth()]; }
	function popShell(inner) {
		var pop = document.createElement('div');
		pop.className = 'rnp_pop';
		pop.innerHTML = '<div class="rnp_pop_bg"></div><div class="rnp_pop_box">' + inner + '</div>';
		document.body.appendChild(pop);
		document.documentElement.classList.add('rnp_pop_on');
		pop.close = function () { pop.remove(); document.documentElement.classList.remove('rnp_pop_on'); };
		pop.querySelector('.rnp_pop_bg').onclick = function () { pop.close(); };
		var x = pop.querySelector('.rnp_pop_x'); if (x) x.onclick = function () { pop.close(); };
		return pop;
	}
	// 1-қадам: қай күнді толтыру (тек бүгін не өткен күн)
	function pickDay() {
		var def = R.today_bd;
		var h = '<div class="rnp_pop_h"><div><div class="rnp_pop_t">Қай күнді толтырасыз?</div><div class="rnp_pop_d">Тек бүгін не өткен күн</div></div><a class="rnp_pop_x" title="Жабу">&times;</a></div><div class="rnp_pop_b">'
			+ '<div class="rnp_pick_q">';
		[['Бүгін', 0], ['Кеше', -1], ['Алдыңғы күн', -2]].forEach(function (q) { var d = addDays(R.today_bd, q[1]); h += '<a class="rnp_pick_i" data-d="' + d + '">' + q[0] + '<span>' + dayName(d) + '</span></a>'; });
		h += '</div><label class="rnp_pick_l">Басқа күн<input type="date" class="rnp_pick_in" max="' + R.today_bd + '" value="' + def + '"></label></div>'
			+ '<div class="rnp_pop_f"><span class="rnp_pop_s"></span><a class="rnp_pop_ok">Әрі қарай</a></div>';
		var pop = popShell(h), inp = pop.querySelector('.rnp_pick_in'), st = pop.querySelector('.rnp_pop_s');
		function go(d) {
			if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) { st.textContent = 'Күнді таңдаңыз'; st.className = 'rnp_pop_s rnp_save_err'; return; }
			if (d > R.today_bd) { st.textContent = 'Болашақ күнді толтыруға болмайды'; st.className = 'rnp_pop_s rnp_save_err'; return; }
			pop.close(); openPop(d);
		}
		Array.prototype.forEach.call(pop.querySelectorAll('.rnp_pick_i'), function (a) { a.onclick = function () { go(a.dataset.d); }; });
		pop.querySelector('.rnp_pop_ok').onclick = function () { go(inp.value); };
	}
	// 2-қадам: сол күннің толтыратын жолдары (мәндер серверден алынады)
	function openPop(d) {
		if (d > R.today_bd) return;
		$.ajax({ url: (R.url || '/opiu/get.php') + '?day=' + d, dataType: 'json', success: function (r) { showPop(d, (r && r.ok && r.vals && !Array.isArray(r.vals)) ? r.vals : {}); }, error: function () { showPop(d, {}); } });
	}
	function showPop(d, vals) {
		var h = '<div class="rnp_pop_h"><div><div class="rnp_pop_t">Запис қосу</div><div class="rnp_pop_d">' + dayName(d) + (d === R.today_bd ? ' · бүгін' : '') + '</div></div><a class="rnp_pop_x" title="Жабу">&times;</a></div><div class="rnp_pop_b">';
		var grp = '', titled = false, lastPg = '';
		rows.forEach(function (r) {
			if (r.t === 'title') { if (!r.hide) { h += '<div class="rnp_pop_g rnp_pop_gt">' + esc(r.n) + '</div>'; lastPg = ''; } return; } // РНП: компания атауы
			if (r.t !== 'in') { if (!titled && (r.st === 'grp' || /^hd_/.test(r.st || ''))) grp = r.n; return; }
			// РНП: жолдың өз тобы (pg) — терезеде сол атаумен топталады
			if (r.pg) { titled = true; if (r.pg !== lastPg) { grp = r.pg; lastPg = r.pg; } }
			// сұр жолдың өзі топ болса (мысалы «Агрегатор»), атауы тақырып болады, ал жолы — «Сома»
			var lbl = r.n;
			if (r.st === 'grp' || /^hd_/.test(r.st || '')) { grp = r.n; lbl = 'Сома'; }
			if (grp) { h += '<div class="rnp_pop_g">' + esc(grp) + '</div>'; grp = ''; }
			h += '<label class="rnp_pop_r"><span>' + esc(lbl) + '</span><input type="text" inputmode="numeric" data-k="' + r.k + '" value="' + fmtIn(vals[r.k]) + '" placeholder="0"></label>';
		});
		h += '</div><div class="rnp_pop_f"><span class="rnp_pop_s"></span><a class="rnp_pop_ok">Дайын</a></div>';
		var pop = popShell(h);
		var st = pop.querySelector('.rnp_pop_s');
		function close() { pop.close(); if (popChanged) { popChanged = false; ajx('.st_page'); } }
		pop.querySelector('.rnp_pop_bg').onclick = close;
		pop.querySelector('.rnp_pop_x').onclick = close;
		pop.querySelector('.rnp_pop_ok').onclick = close;
		var ins = Array.prototype.slice.call(pop.querySelectorAll('input'));
		ins.forEach(function (el, i) {
			el.addEventListener('focus', function () { el.value = el.value.replace(/\s/g, ''); el.dataset.old = el.value; setTimeout(function () { el.select(); }, 0); });
			el.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') { close(); return; }
				if (e.key === 'Enter' || e.key === 'ArrowDown') { e.preventDefault(); if (ins[i + 1]) ins[i + 1].focus(); else el.blur(); }
				if (e.key === 'ArrowUp') { e.preventDefault(); if (ins[i - 1]) ins[i - 1].focus(); }
			});
			el.addEventListener('blur', function () {
				var v = parse(el.value);
				if (v === null) { el.value = fmtIn(el.dataset.old); st.textContent = 'Тек сан жазыңыз'; st.className = 'rnp_pop_s rnp_save_err'; return; }
				el.value = fmtIn(v);
				if (String(v) === String(el.dataset.old)) return;
				var k = el.dataset.k;
				if (v === '') delete vals[k]; else vals[k] = v;
				popChanged = true;
				st.textContent = 'Сақталуда...'; st.className = 'rnp_pop_s rnp_save_w';
				$.ajax({
					url: (R.url || '/opiu/get.php') + '?save', type: 'POST', dataType: 'json',
					data: { items: JSON.stringify([{ d: d, k: k, v: v, p: 0 }]) },
					success: function (r) { if (r && r.ok) { st.textContent = 'Сақталды'; st.className = 'rnp_pop_s rnp_save_ok'; } else { st.textContent = 'Қате: ' + ((r && r.err) || 'сақталмады'); st.className = 'rnp_pop_s rnp_save_err'; } },
					error: function () { st.textContent = 'Қате: интернетті тексеріңіз'; st.className = 'rnp_pop_s rnp_save_err'; }
				});
			});
		});
		if (ins[0]) ins[0].focus();
	}
	if (R.pop) {
		var fab = document.createElement('a');
		fab.className = 'rnp_fab';
		fab.innerHTML = '<i class="fal fa-pen"></i>Запис қосу';
		// Күн көрінісінде — сол күн (болашақ болса бүгін); Апта/Ай/Жылда — алдымен күн таңдалады
		fab.onclick = function () { if (R.mode === 'd') openPop(R.days[0] > R.today_bd ? R.today_bd : R.days[0]); else pickDay(); };
		document.body.appendChild(fab);
	}

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

	// бет скроллында кесте тақырыбы жоғарғы мәзірдің астында тұрады
	function topOffset() {
		var hd = document.querySelector('.aheader');
		var top = hd && getComputedStyle(hd).position === 'fixed' ? Math.max(0, hd.getBoundingClientRect().bottom) : 0;
		tb.style.setProperty('--rnp-top', top + 'px');
	}
	topOffset();
	window.addEventListener('resize', topOffset);

	// бүгінгі күнге айналдыру
	var t = tb.querySelector('th.rnp_today');
	// көлденең скролл тек кесте блогында
	var wrap = tb.parentNode;
	if (t && wrap) wrap.scrollLeft = Math.max(0, t.offsetLeft - wrap.clientWidth / 2);
	// жолақ жасырын: тышқанмен ұстап солға-оңға сүйреу
	if (wrap) {
		var dx = null, sl = 0, moved = false;
		wrap.addEventListener('mousedown', function (e) {
			if (e.button !== 0 || e.target.closest('input, a') || wrap.scrollWidth <= wrap.clientWidth) return;
			dx = e.pageX; sl = wrap.scrollLeft; moved = false;
		});
		window.addEventListener('mousemove', function (e) {
			if (dx === null) return;
			var d = e.pageX - dx;
			if (Math.abs(d) > 4) { moved = true; wrap.classList.add('rnp_drag'); }
			if (moved) wrap.scrollLeft = sl - d;
		});
		window.addEventListener('mouseup', function () { dx = null; setTimeout(function () { wrap.classList.remove('rnp_drag'); moved = false; }, 0); });
		// сүйреп болғанда ұяшық басылмасын
		wrap.addEventListener('click', function (e) { if (moved) { e.stopPropagation(); e.preventDefault(); } }, true);
	}
	// ---------- ⓘ түсіндірме ----------
	var info = document.querySelector('.rnp_info');
	if (info) {
		info.querySelector('.rnp_info_b').addEventListener('click', function (e) { e.stopPropagation(); info.classList.toggle('open'); });
		document.addEventListener('click', function (e) { if (!info.contains(e.target)) info.classList.remove('open'); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') info.classList.remove('open'); });
	}
}
rnpInit();
// жаңа беттің ішіндегі window.rnp деректерін алу (скрипт AJAX-пен іске қосылмайды)
function rnpData(html) {
	var m = String(html || '').match(/window\.rnp\s*=\s*(\{[\s\S]*?\});\s*<\/script>/);
	if (!m) return null;
	try { return JSON.parse(m[1]); } catch (e) { return null; }
}
$(document).on('ajx:done', function () {
	if (!document.getElementById('rnp_tb')) return;
	var n = rnpData(ajx.html);
	if (n) { window.rnp = n; rnpInit(); } else location.reload();
});
