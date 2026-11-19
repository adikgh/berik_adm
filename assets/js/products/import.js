// ЖИ мәзір импорты (/products/import/): 1 оқу → 2 тексеру → 3 қосу → 4 тізбекті толтыру (позиция граммовкасы, сет құрамы, шикізат бағасы)
(function () {
	var page = document.querySelector('.mi_page');
	if (!page) return;
	var $ = function (s) { return page.querySelector(s); };
	var say = function (t) { if (window.mess) mess(t); else alert(t); };
	var h = function (s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); };
	var API = '/products/import/get.php';
	var src = 'files', variant = 2, files = [], items = [], token = '';

	function post(url, data) {
		var fd = data instanceof FormData ? data : new FormData();
		if (!(data instanceof FormData)) for (var k in data) fd.append(k, data[k]);
		return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) {
			return r.text().then(function (t) { try { return JSON.parse(t); } catch (e) { return { ok: 0, error: 'Сервер қатесі (' + r.status + ')' }; } });
		});
	}

	// сегмент батырмалары
	page.addEventListener('click', function (e) {
		var b = e.target.closest('.mi_src .mi_seg_b');
		if (b) {
			src = b.dataset.src;
			page.querySelectorAll('.mi_src .mi_seg_b').forEach(function (x) { x.classList.toggle('mi_on', x === b); });
			page.querySelectorAll('.mi_box').forEach(function (x) { x.hidden = x.dataset.box !== src; });
		}
		var v = e.target.closest('.mi_var .mi_seg_b');
		if (v) { variant = +v.dataset.v; page.querySelectorAll('.mi_var .mi_seg_b').forEach(function (x) { x.classList.toggle('mi_on', x === v); }); }
		var x = e.target.closest('.mi_th_x');
		if (x) { files.splice(+x.dataset.i, 1); thumbs(); }
		var k = e.target.closest('.mi_kind');
		if (k) { var it = items[+k.dataset.i]; it.set = it.set ? 0 : 1; k.className = 'mi_kind' + (it.set ? ' mi_kind_set' : ''); k.textContent = it.set ? 'Сет' : 'Позиция'; }
	});

	// ---- файлдар: суретті 2000px-ке кішірейту, PDF беттерін суретке айналдыру ----
	function load(src) { return new Promise(function (ok, no) { if (window.pdfjsLib) return ok(); var s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = no; document.head.appendChild(s); }); }
	function toJpeg(canvas) { return new Promise(function (ok) { canvas.toBlob(function (b) { ok(b); }, 'image/jpeg', 0.86); }); }
	function shrink(file) {
		return new Promise(function (ok) {
			var img = new Image(), url = URL.createObjectURL(file);
			img.onload = function () {
				var k = Math.min(1, 2000 / Math.max(img.width, img.height)), c = document.createElement('canvas');
				c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
				var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); g.drawImage(img, 0, 0, c.width, c.height);
				URL.revokeObjectURL(url); toJpeg(c).then(function (b) { ok({ blob: b, name: file.name, url: URL.createObjectURL(b) }); });
			};
			img.onerror = function () { ok(null); };
			img.src = url;
		});
	}
	function pdfPages(file) {
		return load('/assets/pl/pdfjs/pdf.min.js').then(function () {
			pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/pl/pdfjs/pdf.worker.min.js';
			return file.arrayBuffer();
		}).then(function (buf) { return pdfjsLib.getDocument({ data: new Uint8Array(buf) }).promise; }).then(function (pdf) {
			var out = [], n = Math.min(pdf.numPages, 12), p = Promise.resolve();
			for (var i = 1; i <= n; i++) (function (i) {
				p = p.then(function () { return pdf.getPage(i); }).then(function (pg) {
					var v0 = pg.getViewport({ scale: 1 }), vp = pg.getViewport({ scale: Math.min(3, 1800 / Math.max(v0.width, v0.height)) });
					var c = document.createElement('canvas'); c.width = vp.width; c.height = vp.height;
					var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
					return pg.render({ canvasContext: g, viewport: vp }).promise.then(function () { return toJpeg(c); });
				}).then(function (b) { out.push({ blob: b, name: file.name + ' · ' + i, url: URL.createObjectURL(b) }); });
			})(i);
			return p.then(function () { return out; });
		});
	}
	$('.mi_file').addEventListener('change', function () {
		var list = Array.prototype.slice.call(this.files); this.value = '';
		var p = Promise.resolve();
		list.forEach(function (f) {
			p = p.then(function () {
				if (f.type == 'application/pdf' || /\.pdf$/i.test(f.name)) return pdfPages(f).then(function (a) { files = files.concat(a); }).catch(function () { say('PDF ашылмады: ' + f.name); });
				return shrink(f).then(function (x) { if (x) files.push(x); });
			}).then(thumbs);
		});
	});
	function thumbs() {
		if (files.length > 16) { files = files.slice(0, 16); say('16 суретке дейін'); }
		$('.mi_thumbs').innerHTML = files.map(function (f, i) { return '<div class="mi_th"><img src="' + f.url + '" alt=""><button type="button" class="mi_th_x" data-i="' + i + '"><i class="fal fa-times"></i></button></div>'; }).join('');
	}

	// ---- 1. оқу ----
	$('.mi_read').addEventListener('click', function () {
		var b = this, fd = new FormData();
		fd.append('src', src);
		if (src == 'files') { if (!files.length) return say('Мәзір фотосын не PDF таңдаңыз'); files.forEach(function (f, i) { fd.append('f[]', f.blob, 'p' + i + '.jpg'); }); }
		else { var v = (src == 'insta' ? $('.mi_insta') : $('.mi_web')).value.trim(); if (!v) return say(src == 'insta' ? 'Instagram аккаунтын жазыңыз' : 'Сілтеме не атауын жазыңыз'); fd.append('insta', v); }
		var sp = b.querySelector('span'), t0 = sp.textContent; b.disabled = true;
		sp.textContent = src == 'insta' ? 'Instagram оқылып жатыр (1-3 мин)...' : 'ЖИ оқып жатыр...';
		post(API + '?read', fd).then(function (d) {
			b.disabled = false; sp.textContent = t0;
			if (!d.ok) return say(d.error || 'Қате');
			token = d.token || ''; items = d.items || [];
			if (!items.length) return say('Мәзірден тағам табылмады');
			table(d);
		}).catch(function () { b.disabled = false; sp.textContent = t0; say('Байланыс қатесі, қайталаңыз'); });
	});

	// ---- 2. тексеру кестесі ----
	function table(d) {
		var nw = items.filter(function (x) { return !x.same; }).length;
		$('.mi_sum').textContent = items.length + ' тағам: ' + nw + ' жаңа, ' + (items.length - nw) + ' бұрыннан бар';
		$('.mi_note').textContent = d.note || '';
		$('.mi_tbl tbody').innerHTML = items.map(function (it, i) {
			var ex = !!it.same;
			return '<tr class="' + (ex ? 'mi_ex' : '') + '" data-i="' + i + '">'
				+ '<td><input type="checkbox" class="mi_on_i"' + (ex ? ' disabled' : ' checked') + '></td>'
				+ '<td><input type="text" class="mi_in mi_nm" value="' + h(it.name) + '"' + (ex ? ' disabled' : '') + '>' + (it.set && it.set_items.length ? '<small>' + h(it.set_items.join(', ')) + '</small>' : (it.desc ? '<small>' + h(it.desc) + '</small>' : '')) + '</td>'
				+ '<td><input type="text" class="mi_in mi_ct" value="' + h(it.cat) + '"' + (ex ? ' disabled' : '') + '></td>'
				+ '<td><input type="text" class="mi_in mi_pr" inputmode="numeric" value="' + (it.price || '') + '"' + (ex ? ' disabled' : '') + '></td>'
				+ '<td><button type="button" class="mi_kind' + (it.set ? ' mi_kind_set' : '') + '" data-i="' + i + '"' + (ex ? ' disabled' : '') + '>' + (it.set ? 'Сет' : 'Позиция') + '</button></td>'
				+ '<td>' + (ex ? '<span class="mi_tag mi_tag_ex">Бар' + (it.same_name && it.same_name != it.name ? ': ' + h(it.same_name) : '') + '</span>' : '<span class="mi_tag">Жаңа</span>') + (it.img >= 0 || it.img_url ? ' <i class="fal fa-image" title="Фотосы бар"></i>' : '') + '</td></tr>';
		}).join('');
		page.querySelector('.mi_step3').hidden = false; cnt();
		page.querySelector('.mi_step3').scrollIntoView({ behavior: 'smooth' });
	}
	function cnt() { var n = page.querySelectorAll('.mi_on_i:checked').length; $('.mi_add span').textContent = 'Мәзірге қосу (' + n + ')'; $('.mi_add').disabled = !n; }
	page.addEventListener('change', function (e) {
		if (e.target.classList.contains('mi_all')) page.querySelectorAll('.mi_on_i:not(:disabled)').forEach(function (c) { c.checked = e.target.checked; });
		if (e.target.classList.contains('mi_on_i') || e.target.classList.contains('mi_all')) cnt();
	});
	page.addEventListener('input', function (e) { if (e.target.classList.contains('mi_pr')) e.target.value = e.target.value.replace(/\D+/g, ''); });

	// ---- 3. қосу ----
	$('.mi_add').addEventListener('click', function () {
		var sel = [];
		page.querySelectorAll('.mi_tbl tbody tr').forEach(function (tr) {
			if (!tr.querySelector('.mi_on_i:checked')) return;
			var it = Object.assign({}, items[+tr.dataset.i]);
			it.name = tr.querySelector('.mi_nm').value.trim(); it.cat = tr.querySelector('.mi_ct').value.trim(); it.price = +tr.querySelector('.mi_pr').value || 0;
			if (it.name) sel.push(it);
		});
		if (!sel.length) return;
		var b = this; b.disabled = true; b.querySelector('span').textContent = 'Қосылып жатыр...';
		post(API + '?add', { items: JSON.stringify(sel), token: token, photo: $('.mi_photo').checked ? 1 : '' }).then(function (d) {
			if (!d.ok) { b.disabled = false; cnt(); return say(d.error || 'Қате'); }
			page.querySelector('.mi_step3').hidden = true;
			var s4 = page.querySelector('.mi_step4'); s4.hidden = false; s4.scrollIntoView({ behavior: 'smooth' });
			log('ok', d.added.length + ' тағам қосылды' + (d.cats ? ', ' + d.cats + ' жаңа каталог' : '') + (d.photos ? ', ' + d.photos + ' фото' : '') + (d.skip ? ', ' + d.skip + ' бұрыннан бар (тимедік)' : ''));
			if ($('.mi_fill').checked && d.added.length) chain(d.added); else finish();
		}).catch(function () { b.disabled = false; cnt(); say('Байланыс қатесі, қайталаңыз'); });
	});

	// ---- 4. тізбек: алдымен позициялар (сеттер соларға сілтейді), сосын сеттер, соңында бағасыз шикізат ----
	function log(cls, t) { var d = document.createElement('div'); d.className = 'mi_l mi_l_' + cls; d.innerHTML = t; $('.mi_log').appendChild(d); d.scrollIntoView({ block: 'nearest' }); }
	function chain(added) {
		var ones = added.filter(function (x) { return !x.set; }), sets = added.filter(function (x) { return x.set; });
		var all = ones.length + sets.length, done = 0, fails = 0, fixed = 0, usd = 0;
		var bar = function (t) { done++; $('.mi_bar_i').style.width = Math.round(done / (all + 1) * 100) + '%'; $('.mi_stat').textContent = t; };
		var run = function (list, url, label) {
			var i = 0;
			return new Promise(function (ok) {
				var next = function () {
					if (i >= list.length) return ok();
					var x = list[i++];
					$('.mi_stat').textContent = label + ': ' + i + ' / ' + list.length + ' · ' + x.name;
					post(url, { pid: x.id, variant: variant }).then(function (d) {
						if (d.ok) {
							fixed += d.fixed || 0; usd += d.usd || 0;
							log('ok', '<b>' + h(x.name) + '</b>: ' + (d.result == 'has' ? 'бұрыннан толтырылған' : (d.how ? h(d.how) : (d.n ? d.n + ' зат' : 'дайын'))) + (d.fixed ? ' <span class="mi_fx">бірлік түзетілді (кг→г)</span>' : '') + (d.sure == 'low' ? ' <span class="mi_fx">тексеріңіз</span>' : ''));
						} else { fails++; log('err', '<b>' + h(x.name) + '</b>: ' + h(d.error || 'қате') + ' · <a href="/products/item/?id=' + x.id + '" target="_blank">қолмен</a>'); }
					}).catch(function () { fails++; log('err', '<b>' + h(x.name) + '</b>: байланыс қатесі'); }).then(function () { bar(label); next(); });
				};
				next();
			});
		};
		run(ones, API + '?fill_one', 'Граммовка').then(function () { return run(sets, API + '?fill_set', 'Сет құрамы'); }).then(function () {
			$('.mi_stat').textContent = 'Шикізат бағалары...';
			var k = 0, price = function () {
				return post('/products/tpl/get.php?prices', {}).then(function (d) {
					if (d.n) log('ok', d.n + ' шикізатқа баға қойылды' + (d.error ? ' · ' + h(d.error) : ''));
					else if (d.error) log('err', 'Баға: ' + h(d.error));
					if (d.n && ++k < 3) return price();
				}).catch(function () {});
			};
			return price();
		}).then(function () {
			$('.mi_bar_i').style.width = '100%';
			finish((fails ? fails + ' тағам толтырылмады (қызыл жолдар). ' : '') + (fixed ? fixed + ' бірлік түзетілді. ' : '') + 'ЖИ бағалаған шикізат бағаларын Себес-2-де тексеріңіз.');
		});
	}
	function finish(t) { $('.mi_stat').textContent = 'Дайын. ' + (t || ''); page.querySelector('.mi_done').hidden = false; }
})();
