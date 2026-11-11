// BERIK DEV: ортақ көмекшілер. Хабар шығару админдікі: mess() (assets/js/fun.js), сөздер window.bw (тіл)
(function () {
	var W = window.bw || {};
	function w(k, d) { return W[k] || d; }
	window.toast = function (s, err) {
		if (typeof window.mess == 'function') return window.mess(s, err ? 'err' : 'ok');
		alert(s);
	};

	// Растау терезесі: админнің ask() (fun.js) терезесі, браузердің confirm орнына. o: {ok: 'батырма', danger: true}
	window.devAsk = function (q, yes, o) {
		o = o || {};
		if (typeof window.ask != 'function') { if (confirm(q)) yes(); return; }
		window.ask(String(q).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }), yes, { ok: o.ok || w('Yes', 'Иә'), no: w('No', 'Жоқ'), danger: !!o.danger });
	};
	function btxt(b) { return b ? b.textContent.replace(/\s+/g, ' ').trim() : ''; }

	// POST → JSON {ok, msg, ...}
	window.devPost = function (url, data) {
		var fd = data instanceof FormData ? data : new FormData();
		if (!(data instanceof FormData)) for (var k in data) fd.append(k, data[k]);
		fd.append('csrf', window.CSRF || '');
		return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
	};

	// <form data-post="/companies/get.php?pay_add" data-ask="..."> — сақтап, бетті жаңартады
	document.addEventListener('submit', function (e) {
		var f = e.target.closest('form[data-post]');
		if (!f) return;
		e.preventDefault();
		var b = f.querySelector('[type=submit]');
		if (f.dataset.ask) return devAsk(f.dataset.ask, function () { send(f, b); }, { ok: btxt(b), danger: f.hasAttribute('data-danger') });
		send(f, b);
	});
	function send(f, b) {
		if (b) b.disabled = true;
		devPost(f.dataset.post, new FormData(f)).then(function (d) {
			if (b) b.disabled = false;
			if (!d.ok) return toast(d.msg || w('Error', 'Қате'), 1);
			toast(d.msg || w('Saved', 'Сақталды'));
			if (d.go) setTimeout(function () { location.href = d.go; }, 400); else setTimeout(function () { location.reload(); }, 600);
		}).catch(function () { if (b) b.disabled = false; toast(w('Network error', 'Желі қатесі'), 1); });
	}

	// <button data-act="/companies/get.php?pay_cancel" data-id="5" data-ask="...">
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-act]');
		if (b) {
			e.preventDefault();
			var go = function () {
				devPost(b.dataset.act, { id: b.dataset.id || '' }).then(function (d) {
					if (!d.ok) return toast(d.msg || w('Error', 'Қате'), 1);
					toast(d.msg || w('Done', 'Дайын')); setTimeout(function () { location.reload(); }, 600);
				});
			};
			if (b.dataset.ask) devAsk(b.dataset.ask, go, { ok: btxt(b), danger: b.classList.contains('btn_r') || b.hasAttribute('data-danger') }); else go();
			return;
		}
		var po = e.target.closest('[data-pop]');
		if (po) { e.preventDefault(); var pb = document.querySelector(po.dataset.pop); if (pb) pb.classList.add('pop_bl_act'); return; }
		var pc = e.target.closest('.pop_close');
		if (pc) { var pp = pc.closest('.pop_bl'); if (pp) pp.classList.remove('pop_bl_act'); return; }
		var c = e.target.closest('.copy');
		if (c) {
			var s = c.dataset.copy || c.textContent.trim();
			(navigator.clipboard ? navigator.clipboard.writeText(s) : Promise.reject()).then(function () { toast(w('Copied', 'Көшірілді')); }, function () { prompt('', s); });
			return;
		}
		var r = e.target.closest('tr[data-href]');
		if (r && !e.target.closest('a,button,input,select')) location.href = r.dataset.href;
	});

	// ?add=1 → терезе бірден ашылады
	if (/[?&]add=1/.test(location.search)) { var ab = document.querySelector('[data-pop]'); if (ab) ab.click(); }
	document.addEventListener('keydown', function (e) { if (e.key == 'Escape') document.querySelectorAll('.pop_bl_act').forEach(function (p) { p.classList.remove('pop_bl_act'); }); });

	// Кестедегі іздеу: <input data-filter=".tbl tbody tr">
	document.querySelectorAll('[data-filter]').forEach(function (i) {
		i.addEventListener('input', function () {
			var q = i.value.trim().toLowerCase();
			document.querySelectorAll(i.dataset.filter).forEach(function (r) { r.style.display = !q || r.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none'; });
		});
	});
})();
