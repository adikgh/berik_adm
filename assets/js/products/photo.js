// Тағам фотосы: .pph шаршысын басқанда файл таңдалады да, бірден жүктеледі (бет қайта жүктелмейді).
// .pph_del — фотоны өшіру. Сервер: /products/item/photo.php
// Google Drive қосулы болса, фото браузерден тікелей Drive-қа кетеді (gdrive_up.js), базада тек сілтемесі.
(function () {
	var ui = window.ui_lang === 'ru' ? 'ru' : 'kz';
	var T = {
		kz: { saved: 'Фото сақталды', del: 'Фото өшірілді', err: 'Қате, қайта көріңіз', type: 'Тек JPG, PNG немесе WEBP сурет', size: 'Файл тым үлкен (15 МБ дейін)', ask: 'Фотоны өшіресіз бе?', yes: 'Өшіру', no: 'Жоқ', load: 'Жүктелуде…' },
		ru: { saved: 'Фото сохранено', del: 'Фото удалено', err: 'Ошибка, попробуйте ещё раз', type: 'Только JPG, PNG или WEBP', size: 'Файл слишком большой (до 15 МБ)', ask: 'Удалить фото?', yes: 'Удалить', no: 'Нет', load: 'Загрузка…' }
	}[ui];
	var msg = function (t) { if (window.mess) mess(t); };

	var inp = document.createElement('input');
	inp.type = 'file'; inp.accept = 'image/jpeg,image/png,image/webp'; inp.style.display = 'none';
	document.body.appendChild(inp);
	var cur = null;

	function setImg(box, url) {
		var im = box.querySelector('.pph_img');
		if (url) {
			if (!im) { im = document.createElement('img'); im.className = 'pph_img'; im.alt = ''; box.insertBefore(im, box.firstChild); }
			im.onerror = function () { this.remove(); box.classList.remove('pph_on'); };
			im.src = url;
			box.classList.add('pph_on');
		} else {
			if (im) im.remove();
			box.classList.remove('pph_on');
		}
		// бір тағамның басқа шаршылары да (тізім/карточка) жаңарсын
		document.querySelectorAll('.pph[data-id="' + box.dataset.id + '"]').forEach(function (b) {
			if (b !== box) { var x = b.querySelector('.pph_img'); if (x) x.remove(); b.classList.remove('pph_on'); if (url) setImg(b, url); }
		});
	}

	// gdrive_up.js керек кезде ғана жүктеледі
	var gdLoad = null;
	function gd() {
		if (window.gdUpload) return Promise.resolve();
		if (!gdLoad) gdLoad = new Promise(function (ok, fail) {
			var s = document.createElement('script'); s.src = '/assets/js/gdrive_up.js'; s.onload = ok;
			s.onerror = function () { gdLoad = null; fail(); }; document.head.appendChild(s);
		});
		return gdLoad;
	}
	// алдымен Drive, ол қосылмаса — бұрынғыдай хостингке
	function upload(box, file) {
		box.classList.add('pph_busy');
		return gd().then(function () { return window.gdUpload(file, 'product'); }).then(function (r) {
			if (r.ok) {
				box.classList.remove('pph_busy');
				var fd = new FormData(); fd.append('id', box.dataset.id); fd.append('file_id', r.file.id);
				box.classList.add('pph_busy');
				return fetch('/products/item/photo.php?drive', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (x) { return x.json(); })
					.then(function (d) { box.classList.remove('pph_busy'); return d; });
			}
			box.classList.remove('pph_busy');
			if (r.err === 'off') return send(box, 'up', file);
			return { ok: 0, err: 'drive', msg: r.msg };
		}, function () { box.classList.remove('pph_busy'); return send(box, 'up', file); })
			.catch(function () { box.classList.remove('pph_busy'); return { ok: 0, err: 'error' }; });
	}

	function send(box, act, file) {
		var fd = new FormData();
		fd.append('id', box.dataset.id);
		if (file) fd.append('file', file);
		box.classList.add('pph_busy');
		return fetch('/products/item/photo.php?' + act, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (d) { box.classList.remove('pph_busy'); return d; })
			.catch(function () { box.classList.remove('pph_busy'); return { ok: 0, err: 'error' }; });
	}

	document.addEventListener('click', function (e) {
		var del = e.target.closest('.pph_del');
		if (del) {
			e.preventDefault(); e.stopPropagation();
			var box = del.closest('.pph');
			if (!box || box.classList.contains('pph_busy')) return;
			if (del.dataset.ask !== '1') { // 1-басу: сұрау, 2-басу: өшіру (alert/confirm жоқ)
				del.dataset.ask = '1'; del.classList.add('pph_del_ask'); del.title = T.ask; msg(T.ask);
				setTimeout(function () { del.dataset.ask = ''; del.classList.remove('pph_del_ask'); }, 3000);
				return;
			}
			del.dataset.ask = ''; del.classList.remove('pph_del_ask');
			send(box, 'del').then(function (d) { if (d.ok) { setImg(box, ''); msg(T.del); } else msg(T.err); });
			return;
		}
		var b = e.target.closest('.pph');
		if (!b || b.classList.contains('pph_busy')) return;
		e.preventDefault(); e.stopPropagation();
		cur = b; inp.value = ''; inp.click();
	}, true);

	inp.addEventListener('change', function () {
		var file = inp.files && inp.files[0], box = cur;
		if (!file || !box) return;
		if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { msg(T.type); return; }
		if (file.size > 15 * 1024 * 1024) { msg(T.size); return; }
		upload(box, file).then(function (d) {
			if (d.ok) { setImg(box, /^https?:/.test(d.url) ? d.url : d.url + '?v=' + Date.now()); msg(T.saved); }
			else msg(d.err === 'type' ? T.type : d.err === 'size' ? T.size : (d.msg || T.err));
		});
	});
})();
