// QR меню: баптау, QR код дизайны (түс, нүкте, бұрыш, логотип, жақтау), PNG және үстелдерді басып шығару
(function () {
	function page() { return document.querySelector('.qr_page'); }
	if (!page()) return;

	var logoImg = null, logoSrc = '';

	// Дизайн формадан
	function style() {
		var f = page().querySelector('.qr_design');
		if (!f) return {};
		var e = f.elements;
		return { color: e.color.value, bg: e.bg.value, frame_color: e.frame_color.value, dots: e.dots.value, eyes: e.eyes.value,
			frame: e.frame.checked, title: e.title.value, text: e.text.value, logo: e.logo.value };
	}

	function rr(x, c, X, Y, W, H, R) {
		R = Math.min(R, W / 2, H / 2);
		x.beginPath(); x.moveTo(X + R, Y); x.arcTo(X + W, Y, X + W, Y + H, R); x.arcTo(X + W, Y + H, X, Y + H, R);
		x.arcTo(X, Y + H, X, Y, R); x.arcTo(X, Y, X + W, Y, R); x.closePath(); x.fillStyle = c; x.fill();
	}

	// QR код суреті: url → canvas (size px, жақтаусыз шаршы бөлігі)
	function draw(cv, url, st, size, bottom) {
		if (typeof qrcode === 'undefined') return;
		var logo = st.logo && logoImg && logoImg.complete && logoImg.naturalWidth;
		var q = qrcode(0, logo ? 'H' : 'M'); q.addData(url); q.make();
		var n = q.getModuleCount(), frame = !!st.frame;
		var pad = Math.round(size * 0.06), cell = (size - pad * 2) / n;
		var top = frame ? Math.round(size * 0.16) : 0, bot = frame ? Math.round(size * 0.16) : 0, fr = frame ? Math.round(size * 0.05) : 0;
		var W = size + fr * 2, H = size + top + bot + fr * 2;
		cv.width = W; cv.height = H;
		var x = cv.getContext('2d');
		x.clearRect(0, 0, W, H);
		if (frame) {
			rr(x, st.frame_color, 0, 0, W, H, size * 0.07);
			x.fillStyle = '#fff'; x.textAlign = 'center'; x.textBaseline = 'middle';
			x.font = '800 ' + Math.round(size * 0.075) + 'px Nunito, Arial, sans-serif';
			fit(x, st.title, W / 2, top / 2 + fr * 0.6, W - fr * 4);
			x.font = '700 ' + Math.round(size * (bottom ? 0.085 : 0.06)) + 'px Nunito, Arial, sans-serif';
			fit(x, bottom || st.text, W / 2, H - bot / 2 - fr * 0.6, W - fr * 4);
		}
		var ox = fr, oy = fr + top;
		rr(x, st.bg, ox, oy, size, size, frame ? size * 0.05 : 0);
		var X0 = ox + pad, Y0 = oy + pad;

		// Логотип орны (ортасы бос)
		var lz = logo ? Math.floor(n * 0.24) | 1 : 0, l0 = (n - lz) / 2, l1 = l0 + lz;
		function eye(r, c) { return (r < 7 && c < 7) || (r < 7 && c >= n - 7) || (r >= n - 7 && c < 7); }

		x.fillStyle = st.color;
		for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) {
			if (!q.isDark(r, c) || eye(r, c)) continue;
			if (logo && r >= l0 - 1 && r < l1 + 1 && c >= l0 - 1 && c < l1 + 1) continue;
			var px = X0 + c * cell, py = Y0 + r * cell;
			if (st.dots === 'dot') { x.beginPath(); x.arc(px + cell / 2, py + cell / 2, cell * 0.45, 0, 7); x.fill(); }
			else if (st.dots === 'round') rr(x, st.color, px + 0.3, py + 0.3, cell - 0.6, cell - 0.6, cell * 0.35);
			else x.fillRect(px, py, cell + 0.5, cell + 0.5);
		}
		// Бұрыштағы үш көз
		[[0, 0], [0, n - 7], [n - 7, 0]].forEach(function (p) {
			var ex = X0 + p[1] * cell, ey = Y0 + p[0] * cell, R = st.eyes === 'circle' ? 3.5 * cell : (st.eyes === 'round' ? 2 * cell : 0);
			rr(x, st.color, ex, ey, 7 * cell, 7 * cell, R);
			rr(x, st.bg, ex + cell, ey + cell, 5 * cell, 5 * cell, R * 0.7);
			rr(x, st.color, ex + 2 * cell, ey + 2 * cell, 3 * cell, 3 * cell, st.eyes === 'square' ? 0 : (st.eyes === 'circle' ? 1.5 * cell : cell));
		});
		if (logo) {
			var lx = X0 + l0 * cell, ly = Y0 + l0 * cell, ls = lz * cell, k = Math.min(ls / logoImg.naturalWidth, ls / logoImg.naturalHeight);
			var w = logoImg.naturalWidth * k, h = logoImg.naturalHeight * k;
			x.drawImage(logoImg, lx + (ls - w) / 2, ly + (ls - h) / 2, w, h);
		}
	}

	function fit(x, t, cx, cy, maxw) {
		t = String(t || '');
		while (t.length > 1 && x.measureText(t).width > maxw) t = t.slice(0, -2) + '…';
		x.fillText(t, cx, cy);
	}

	function tableUrl(p, n) {
		var b = p.querySelector('.qr_branch');
		return p.dataset.qr + '/?c=' + p.dataset.code + (b ? '&b=' + b.value : '') + '&t=' + n;
	}

	function withLogo(done) {
		var st = style();
		if (!st.logo) { logoImg = null; logoSrc = ''; return done(); }
		if (logoSrc === st.logo && logoImg && logoImg.complete) return done();
		logoSrc = st.logo; logoImg = new Image();
		logoImg.onload = logoImg.onerror = done;
		logoImg.src = st.logo;
	}

	function render() {
		withLogo(function () {
			var p = page(); if (!p) return;
			var st = style(), cv = p.querySelector('.qr_cv');
			if (cv) draw(cv, cv.dataset.url, st, 520);
			var list = p.querySelector('.qr_list');
			if (!list) return;
			var n = +list.dataset.tables || 0;
			list.innerHTML = '';
			for (var i = 1; i <= n; i++) {
				var a = document.createElement('a'); a.className = 'qr_t'; a.href = tableUrl(p, i); a.target = '_blank';
				var c = document.createElement('canvas'); draw(c, tableUrl(p, i), st, 300, 'Үстел ' + i);
				a.appendChild(c); list.appendChild(a);
			}
			p.querySelector('.qr_tables_e').classList.toggle('dsp_n', n > 0);
			p.querySelector('.qr_print').classList.toggle('dsp_n', n === 0);
			var ff = p.querySelector('.qr_frame_f'); if (ff) ff.classList.toggle('dsp_n', !st.frame);
		});
	}

	function post(act, d, done) {
		d.append('csrf', page().dataset.csrf);
		fetch('/qr/get.php?' + act, { method: 'POST', body: d })
			.then(function (r) { return r.json(); })
			.catch(function () { return { ok: false, msg: 'Байланыс қатесі, қайта көріңіз' }; })
			.then(done);
	}

	$(document).on('submit', '.qr_form', function (e) {
		e.preventDefault();
		var p = page(), form = this, btn = form.querySelector('.qr_save');
		btn.classList.add('cl_btn_wait');
		post('save', new FormData(form), function (r) {
			btn.classList.remove('cl_btn_wait');
			mess(r.ok ? 'Сәтті сақталды' : (r.msg || 'Қате'));
			if (r.ok) { p.querySelector('.qr_list').dataset.tables = form.elements.tables.value; render(); }
		});
	});

	// Мәзір көрінісі (company_menu.look)
	// Шаблон таңдалса, түс әлі әдепкі болса, шаблонның өз түсі қойылады
	$(document).on('change', '.qr_tpl input', function () {
		var a = this.form.elements.accent, def = { 0: '#0ea768', 1: '#ff4d6d', 2: '#ff6a2b' };
		if (a && ['#0ea768', '#ff4d6d', '#ff6a2b'].indexOf(a.value.toLowerCase()) >= 0) a.value = def[this.value];
	});
	$(document).on('submit', '.qr_look', function (e) {
		e.preventDefault();
		var btn = this.querySelector('.qr_lsave');
		btn.classList.add('cl_btn_wait');
		post('look', new FormData(this), function (r) {
			btn.classList.remove('cl_btn_wait');
			mess(r.ok ? 'Сәтті сақталды' : (r.msg || 'Қате'));
		});
	});

	// Дизайн: өзгерген сайын алдын ала көрініс, «Сақтау» базаға (файлға) жазады
	$(document).on('input change', '.qr_design', render);
	$(document).on('submit', '.qr_design', function (e) {
		e.preventDefault();
		var btn = this.querySelector('.qr_dsave');
		btn.classList.add('cl_btn_wait');
		post('style', new FormData(this), function (r) {
			btn.classList.remove('cl_btn_wait');
			mess(r.ok ? 'Сәтті сақталды' : (r.msg || 'Қате'));
		});
	});

	$(document).on('change', '.qr_logo_up input', function () {
		var f = this.files[0]; if (!f) return;
		var d = new FormData(); d.append('file', f);
		post('logo', d, function (r) {
			mess(r.ok ? 'Сәтті сақталды: логотип' : (r.msg || 'Қате'));
			if (!r.ok) return;
			var p = page();
			p.querySelector('.qr_design').elements.logo.value = r.url;
			p.querySelector('.qr_logo_del').classList.remove('dsp_n');
			render();
		});
		this.value = '';
	});

	$(document).on('click', '.qr_logo_del', function () {
		var b = this;
		ask('Логотипті өшіру керек пе?', function () {
			post('logo_del', new FormData(), function (r) {
				mess(r.ok ? 'Сәтті өшірілді' : (r.msg || 'Қате'));
				if (!r.ok) return;
				page().querySelector('.qr_design').elements.logo.value = '';
				b.classList.add('dsp_n');
				render();
			});
		}, { danger: true, ok: 'Өшіру' });
	});

	$(document).on('change', '.qr_branch', render);
	$(document).on('click', '.qr_copy', function () { copytext(this.dataset.url); });

	// PNG: үлкен өлшемде
	$(document).on('click', '.qr_png', function () {
		withLogo(function () {
			var p = page(), cv = document.createElement('canvas');
			draw(cv, p.querySelector('.qr_cv').dataset.url, style(), 1400);
			var a = document.createElement('a'); a.href = cv.toDataURL('image/png'); a.download = 'qr-menu-' + p.dataset.code + '.png'; a.click();
		});
	});

	// Үстелдер: A4 бетке 4-тен, сол дизайнмен
	$(document).on('click', '.qr_print', function () {
		withLogo(function () {
			var p = page(), n = +p.querySelector('.qr_list').dataset.tables || 0, st = style(), h = '';
			if (!n) return;
			for (var i = 1; i <= n; i++) {
				var cv = document.createElement('canvas'); draw(cv, tableUrl(p, i), st, 900, 'Үстел ' + i);
				h += '<div class="t"><img src="' + cv.toDataURL('image/png') + '"></div>';
			}
			var w = window.open('', '_blank');
			if (!w) return mess('Терезе ашылмады, браузерде рұқсат беріңіз');
			w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>QR · ' + $('<div>').text(p.dataset.name).html() + '</title><style>' +
				'@page{size:A4;margin:8mm}body{margin:0}.g{display:grid;grid-template-columns:1fr 1fr;gap:8mm}' +
				'.t{break-inside:avoid;text-align:center}.t img{width:85mm;max-width:100%}' +
				'</style></head><body><div class="g">' + h + '</div><script>onload=function(){print()}<\/script></body></html>');
			w.document.close();
		});
	});

	render();
	if (document.fonts && document.fonts.ready) document.fonts.ready.then(render);
	$(document).on('ajx:done', render);
})();
