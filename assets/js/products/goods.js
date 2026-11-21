// Тікелей шикізат (тағам түрі 3): позицияны бір шикізатпен байлау. Тағам карточкасында ішкі блок (.gl_box[data-pid]),
// Себес бетінде поп-ап (goodsPick(pid, onDone)). Сервер: /products/cost2/get.php?a=goods_info | goods_find | goods_link | goods_new.
(function () {
	if (window.goodsPick) return;
	var W = function (k, d) { return (window.bw && window.bw[k]) || d; };
	var L = {
		title: W('gl Title', 'Шикізатпен байланыс'), linked: W('gl Linked', 'Байланған шикізат'), change: W('gl Change', 'Ауыстыру'),
		find: W('gl Find', 'Шикізатты іздеу: атауы'), link: W('gl Link', 'байлау'), newr: W('gl New', 'Жаңа шикізат ашу'),
		none: W('gl None', 'Шикізат табылмады'), price: W('gl Price', 'Закуп бағасы'), stock: W('gl Stock', 'Қалдық'), type: W('gl Type', 'Түрі'),
		cost: W('gl Cost', 'Себес'), margin: W('gl Margin', 'Маржа'), same: W('gl Same', 'сол зат'),
		repl: W('gl Replace ask', 'Позицияның қазіргі граммовкасы ({n} жол) өшіп, орнына осы шикізат 1 шт жазылады.'), yes: W('gl Replace yes', 'Иә, ауыстыру'), cancel: W('gl Cancel', 'Болдырмау'),
		err: W('gl Error', 'Қате, қайта көріңіз'), saved: W('gl Saved', 'Байланды'), nolink: W('gl Not linked', 'Шикізатпен әлі байланбаған: төменнен іздеп таңдаңыз не жаңасын ашыңыз.')
	};
	var esc = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
	var fmt = function (v) { v = +v || 0; return (Math.round(v * 10) / 10).toLocaleString('ru-RU').replace(/,/g, '.'); };
	var post = function (a, data) {
		var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
		return fetch('/products/cost2/get.php?a=' + a, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return { error: 'net' }; });
	};
	var msg = function (t, k) { if (window.mess) mess(t, k); };

	// box: ішкі контейнер; pid; done(info) — байлағаннан кейін
	function render(box, pid, done) {
		box.innerHTML = '<div class="gl_load">…</div>';
		post('goods_info', { pid: pid }).then(function (d) {
			if (!d || d.error) { box.innerHTML = '<div class="gl_err">' + esc(L.err) + '</div>'; return; }
			var head = '';
			if (d.raw) {
				var mr = d.menu_price ? d.menu_price - d.price : null;
				head = '<div class="gl_cur"><div class="gl_cur_t"><i class="fal fa-wine-bottle"></i><b>' + esc(d.name) + '</b><span class="gl_tag">1 ' + esc(d.unit || 'шт') + '</span>' + (d.type ? '<span class="gl_tag gl_tag_t">' + esc(L.type) + ': ' + esc(d.type) + '</span>' : '') + '</div>'
					+ '<div class="gl_kv"><span>' + esc(L.price) + '<b>' + fmt(d.price) + ' ₸/' + esc(d.unit || 'шт') + '</b></span>' + (d.stock !== null ? '<span>' + esc(L.stock) + '<b>' + fmt(d.stock) + ' ' + esc(d.unit || '') + '</b></span>' : '')
					+ '<span>' + esc(L.cost) + '<b>' + fmt(d.price) + ' ₸</b></span>' + (mr !== null ? '<span>' + esc(L.margin) + '<b class="' + (mr < 0 ? 'gl_neg' : 'gl_pos') + '">' + fmt(mr) + ' ₸ · ' + (d.menu_price ? Math.round(mr / d.menu_price * 100) : 0) + '%</b></span>' : '') + '</div>'
					+ '<button type="button" class="gl_chg">' + esc(L.change) + '</button></div>';
			} else head = '<div class="gl_hint">' + esc(L.nolink) + '</div>';
			box.innerHTML = head + '<div class="gl_pick"' + (d.raw ? ' hidden' : '') + '><input type="search" class="gl_q" placeholder="' + esc(L.find) + '"><ul class="gl_list"></ul><div class="gl_ask" hidden></div></div>';
			var pick = box.querySelector('.gl_pick'), q = box.querySelector('.gl_q'), list = box.querySelector('.gl_list'), ask = box.querySelector('.gl_ask'), tm = 0;
			var chg = box.querySelector('.gl_chg'); if (chg) chg.onclick = function () { pick.hidden = false; q.focus(); };
			var act = function (a, data) {
				ask.hidden = true;
				post(a, data).then(function (r) {
					if (r && r.confirm) {
						ask.hidden = false;
						ask.innerHTML = '<span>' + esc(L.repl.replace('{n}', r.confirm)) + '</span><button type="button" class="gl_yes">' + esc(L.yes) + '</button><button type="button" class="gl_no">' + esc(L.cancel) + '</button>';
						ask.querySelector('.gl_yes').onclick = function () { data.force = 1; act(a, data); };
						ask.querySelector('.gl_no').onclick = function () { ask.hidden = true; };
						return;
					}
					if (!r || !r.ok) { msg(L.err, 'warn'); return; }
					msg(L.saved, 'ok'); render(box, pid, done); if (done) done(r);
				});
			};
			var search = function () {
				post('goods_find', { pid: pid, q: q.value }).then(function (r) {
					var it = (r && r.items) || [], v = q.value.trim();
					list.innerHTML = it.map(function (x) { return '<li><span>' + esc(x.name) + (x.same ? ' <small class="gl_same">' + esc(L.same) + '</small>' : '') + '</span><small>' + fmt(x.price) + ' ₸/' + esc(x.unit || 'шт') + '</small><button type="button" class="gl_ok" data-id="' + x.id + '"><i class="far fa-check"></i> ' + esc(L.link) + '</button></li>'; }).join('')
						+ (v ? '<li class="gl_new"><button type="button" class="gl_nb"><i class="far fa-plus"></i> ' + esc(L.newr) + ': <b>' + esc(v) + '</b></button></li>' : '')
						+ (!it.length && !v ? '<li class="gl_empty">' + esc(L.none) + '</li>' : '');
					list.querySelectorAll('.gl_ok').forEach(function (b) { b.onclick = function () { act('goods_link', { pid: pid, raw: b.dataset.id }); }; });
					var nb = list.querySelector('.gl_nb'); if (nb) nb.onclick = function () { nb.disabled = true; act('goods_new', { pid: pid, name: v, unit: 'шт' }); };
				});
			};
			q.oninput = function () { clearTimeout(tm); tm = setTimeout(search, 250); };
			// бастапқыда — позицияның атауы бойынша ұсыныс
			q.value = box.dataset.name || ''; search();
		});
	}
	// Себес бетіндегі поп-ап (gtpl.css .gt_modal қабығы)
	window.goodsPick = function (pid, name, onDone) {
		var m = document.createElement('div'); m.className = 'gt_modal gl_mod';
		m.innerHTML = '<div class="gt_mbox"><div class="gt_mx">&times;</div><h4>' + esc(L.title) + ': ' + esc(name || '') + '</h4><div class="gl_box"></div></div>';
		document.body.appendChild(m);
		var box = m.querySelector('.gl_box'); box.dataset.name = name || '';
		var changed = false;
		m.querySelector('.gt_mx').onclick = function () { m.remove(); if (changed && onDone) onDone(); };
		render(box, pid, function () { changed = true; });
	};
	window.goodsBox = render;
	// тағам карточкасы: .gl_box[data-pid] өзі толады
	document.querySelectorAll('.gl_box[data-pid]').forEach(function (b) { render(b, b.dataset.pid); });
})();
