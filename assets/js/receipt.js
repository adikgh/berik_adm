// Чек редакторы: форма → алдын ала көрініс (кассадағы order_print.php-мен бірдей құрылым), сақтау, логотип
(function () {
	function page() { return document.querySelector('.rc_page'); }
	function form() { return document.querySelector('.rc_form'); }

	var LBL = {
		kz: { l_name: 'Атауы', l_qty: 'Саны', l_sum: 'Сомасы', l_delivery: 'Жеткізу', l_total: 'ЖАЛПЫ', l_prepay: 'Алдын ала төлем', l_topay: 'ТӨЛЕУГЕ', l_client: 'Клиент', l_ph: 'Нөмір', l_adr: 'Мекенжай' },
		ru: { l_name: 'Наименование', l_qty: 'Кол.', l_sum: 'Сумма', l_delivery: 'Доставка', l_total: 'ОБЩИЙ', l_prepay: 'Предоплата', l_topay: 'К ОПЛАТЕ', l_client: 'Клиент', l_ph: 'Номер', l_adr: 'Адрес' }
	};
	var ITEMS = [
		{ ru: 'Филадельфия классик', kz: 'Филадельфия классик', q: 1, s: 3990 },
		{ ru: 'Пицца Пепперони 30 см', kz: 'Пепперони пиццасы 30 см', q: 2, s: 6380 },
		{ ru: 'Кока-кола 1 л', kz: 'Кока-кола 1 л', q: 1, s: 790 }
	];

	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function nl(s) { return esc(s).replace(/\n/g, '<br>'); }

	function cfg() {
		var f = form(), c = {};
		if (!f) return c;
		Array.prototype.forEach.call(f.elements, function (e) {
			if (!e.name) return;
			if (e.type === 'checkbox') c[e.name] = e.checked;
			else if (e.type === 'radio') { if (e.checked) c[e.name] = e.value; }
			else c[e.name] = e.value;
		});
		return c;
	}

	function show(mode, sum) { return mode === 'always' || (mode === 'nonzero' && sum > 0); }

	function render() {
		var p = page(), box = p && p.querySelector('.rc_paper');
		if (!box) return;
		var c = cfg(), d = p.dataset;
		var tp = (p.querySelector('[name=pv_type]:checked') || {}).value || '1';
		var w = c.width === '0' ? +d.bw : +c.width;
		var del = tp === '1' ? 1000 : 0, prepay = 0, sum = 0, h = '';
		ITEMS.forEach(function (i) { sum += i.s; });
		var total = sum + del;

		// Басы
		h += '<div class="head">';
		if (c.logo_on && c.logo) h += '<div class="rc_logo"><img src="' + esc(c.logo) + '" style="width:' + (+c.logo_w || 60) + '%"></div>';
		if (c.title_on) h += '<div class="branch_name">' + esc(c.rtitle || d.title) + '</div>';
		if (c.head_text) h += '<div class="rc_htxt">' + nl(c.head_text) + '</div>';
		if (c.show_status) h += '<div class="pred_order"><div>РАСПЕЧАТАНО</div></div>';
		h += '</div>';
		if (c.show_type && tp !== '1') h += '<div class="soboi">Собой</div>';

		if (c.show_number || c.show_date || c.show_additional) {
			h += '<div class="head2">';
			if (c.show_number) h += '<div class="number">' + esc(d.code) + '125</div>';
			if (c.show_date) { var t = new Date(), z = function (n) { return (n < 10 ? '0' : '') + n; };
				h += '<div class="head_bd"><b>' + z(t.getDate()) + '-' + z(t.getMonth() + 1) + '-' + t.getFullYear() + ', ' + z(t.getHours()) + ':' + z(t.getMinutes()) + '</b></div>'; }
			if (c.show_additional) h += '<div class="additional">Соусы отдельно</div>';
			h += '</div>';
		}

		// Тағамдар
		h += '<table><tr class="tr_border"><td><b>' + esc(c.l_name) + '</b></td><td class="c_q"><b>' + esc(c.l_qty) + '</b></td><td class="c_s"><b>' + esc(c.l_sum) + '</b></td></tr>';
		ITEMS.forEach(function (i, n) {
			h += '<tr class="tr"><td>' + (c.item_num ? (n + 1) + '. ' : '') + esc(i[c.item_lang] || i.ru) + '</td><td class="c_q san">' + i.q + '</td><td class="c_s">' + i.s + '</td></tr>';
		});
		if (show(c.delivery, del)) h += '<tr class="tr"><td>' + esc(c.l_delivery) + '</td><td></td><td class="c_s">' + del + '</td></tr>';
		h += '<tr class="tr_sep2"><td colspan="3"></td></tr><tr class="tr_sep1"><td colspan="3"></td></tr><tr class="tr_sep2"><td colspan="3"></td></tr>';
		h += '<tr class="tr"><td><b>' + esc(c.l_total) + '</b></td><td></td><td class="c_s"><b>' + total + '</b></td></tr>';
		if (show(c.prepay, prepay)) h += '<tr class="tr_sep2"><td colspan="3"></td></tr><tr class="tr"><td>' + esc(c.l_prepay) + '</td><td></td><td class="c_s">- ' + prepay + '</td></tr>';
		if (c.show_topay) h += '<tr class="tr_sep2"><td colspan="3"></td></tr><tr class="tr_sep1"><td colspan="3"></td></tr><tr class="tr_sep2"><td colspan="3"></td></tr>'
			+ '<tr class="tr"><td><b>' + esc(c.l_topay) + '</b></td><td></td><td class="c_s"><b>' + (total - prepay) + '</b></td></tr>';
		h += '</table>';

		// Клиент
		var cl = '';
		if (c.show_phone) cl += '<p>' + esc(c.l_ph) + ': <b>87001234567</b></p>';
		if (c.show_address && tp === '1') cl += '<p>' + esc(c.l_adr) + ': <b>12 мкр, 5 үй</b></p><p><b>3 подъезд, 45 пәтер</b></p>';
		if (c.show_cashback) cl += '<p>Кэшбэк за заказ: <b>+557</b></p><p>Баланс кэшбэка: <b>1 240</b></p>';
		if (c.show_client && cl) h += '<div class="clens"><div class="clens1">' + esc(c.l_client) + '</div><div class="clens2">' + cl + '</div></div>';
		if (c.footer) h += '<div class="rc_foot">' + nl(c.footer) + '</div>';

		box.className = 'rc_paper app p' + w + ' fs_' + c.font + ' ts_' + c.title_size;
		box.innerHTML = h;

		// Тәуелді бөлімдер
		var t = function (s, on) { var e = p.querySelector(s); if (e) e.classList.toggle('rc_off', !on); };
		t('.rc_logo_f', c.logo_on); t('.rc_cl_f', c.show_client);
		var lw = p.querySelector('.rc_lw'); if (lw) lw.textContent = c.logo_w;
	}

	function post(act, data, done) {
		data.append('csrf', page().dataset.csrf);
		fetch('/receipt/get.php?' + act, { method: 'POST', body: data })
			.then(function (r) { return r.json(); })
			.then(done)
			.catch(function () { mess('Байланыс қатесі, қайта көріңіз'); });
	}

	function setLogo(url) {
		var p = page(), f = form();
		f.elements.logo.value = url;
		var img = p.querySelector('.rc_logo_i');
		img.src = url; img.classList.toggle('dsp_n', !url);
		p.querySelector('.rc_logo_del').classList.toggle('dsp_n', !url);
		p.querySelector('.qr_logo_up span').textContent = url ? 'Ауыстыру' : 'Жүктеу';
		if (url) f.elements.logo_on.checked = true;
		render();
	}

	$(document).on('input change', '.rc_form, .rc_pv_t', render);
	$(document).on('ajx:done', render);

	$(document).on('submit', '.rc_form', function (e) {
		e.preventDefault();
		var btn = this.querySelector('.rc_save'); btn.disabled = true;
		post('save', new FormData(this), function (r) {
			btn.disabled = false;
			mess(r.ok ? 'Сәтті сақталды' : (r.msg || 'Қате'));
		});
	});

	$(document).on('click', '.rc_reset', function () {
		ask('Чекті бастапқы күйге қайтарасыз ба? Барлық баптау өшеді.', function () {
			post('reset', new FormData(), function (r) {
				mess(r.ok ? 'Сәтті: әдепкі чек' : (r.msg || 'Қате'));
				if (r.ok) ajx('.rc_page', render);
			});
		}, { ok: 'Қайтару', danger: true });
	});

	$(document).on('click', '.rc_lng', function () {
		var l = LBL[this.dataset.l], f = form();
		for (var k in l) if (f.elements[k]) f.elements[k].value = l[k];
		if (f.elements.item_lang) $(f).find('[name=item_lang][value=' + this.dataset.l + ']').prop('checked', true);
		render();
	});

	// Логотип: браузерде кішірейтіп (ені 512px), ақ фонға қойып, PNG қылады
	$(document).on('change', '.rc_logo_up', function () {
		var file = this.files && this.files[0]; this.value = '';
		if (!file) return;
		if (!/^image\//.test(file.type)) return mess('Тек сурет файлы');
		var rd = new FileReader();
		rd.onload = function () {
			var im = new Image();
			im.onload = function () {
				var k = Math.min(1, 512 / im.naturalWidth), cv = document.createElement('canvas');
				cv.width = Math.max(1, Math.round(im.naturalWidth * k)); cv.height = Math.max(1, Math.round(im.naturalHeight * k));
				var x = cv.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, cv.width, cv.height); x.drawImage(im, 0, 0, cv.width, cv.height);
				var url = cv.toDataURL('image/png');
				if (url.length > 400000) url = cv.toDataURL('image/jpeg', 0.85);
				if (url.length > 400000) return mess('Логотип тым үлкен, кішірек сурет таңдаңыз');
				setLogo(url);
				mess('Логотип қосылды, енді «Сақтау» басыңыз', 'warn');
			};
			im.onerror = function () { mess('Сурет ашылмады'); };
			im.src = rd.result;
		};
		rd.readAsDataURL(file);
	});
	$(document).on('click', '.rc_logo_del', function () { setLogo(''); });

	render();
})();
