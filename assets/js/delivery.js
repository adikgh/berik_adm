// Жеткізу аймақтары: іздеу, қосу, өңдеу, өшіру
(function () {
	var page = document.querySelector('.dz_page');
	if (!page) return;
	var form = page.querySelector(".dz_form"), f = form.elements, q = page.querySelector('.dz_q');
	var rows = page.querySelectorAll('.dz_i'), empty = page.querySelector('.dz_empty');

	function post(act, data, done) {
		data.append('csrf', page.dataset.csrf);
		fetch('/delivery/get.php?' + act, { method: 'POST', body: data })
			.then(function (r) { return r.json(); })
			.then(done)
			.catch(function () { done({ ok: false, msg: 'Байланыс қатесі, қайта көріңіз' }); });
	}

	// Іздеу
	q.addEventListener('input', function () {
		var s = q.value.trim().toLowerCase(), n = 0;
		rows.forEach(function (r) {
			var ok = !s || r.dataset.name.toLowerCase().indexOf(s) > -1;
			r.classList.toggle('dsp_n', !ok);
			if (ok) n++;
		});
		empty.textContent = rows.length ? 'Көше табылмады' : 'Әзірге көше қосылмаған';
		empty.classList.toggle('dsp_n', n > 0);
		page.querySelector('.dz_total span').textContent = n;
	});

	// Форманы ашу: z болса өзгерту, жоқ болса жаңа
	function open(z) {
		form.reset();
		f.id.value = z ? z.id : 0;
		f.name.value = z ? z.name : '';
		f.price.value = z ? z.price : '';
		f.sort.value = z ? z.sort : 0;
		if (z) f.branch_id.value = z.branch;
		f.name.rows = z ? 1 : 3;
		f.name.placeholder = z ? '' : 'Әр жолға бір көше. Бірнеше көшені бірден қосуға болады';
		form.querySelector('.dz_form_h').textContent = z ? 'Көшені өзгерту' : 'Жаңа көше';
		form.classList.remove('dsp_n');
		form.scrollIntoView({ behavior: 'smooth', block: 'center' });
		f.name.focus();
	}

	page.querySelector('.dz_add').addEventListener('click', function () { open(null); });
	form.querySelector('.dz_cancel').addEventListener('click', function () { form.classList.add('dsp_n'); });

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var btn = form.querySelector('.dz_save');
		btn.classList.add('cl_btn_wait');
		post('save', new FormData(form), function (r) {
			btn.classList.remove('cl_btn_wait');
			mess(r.msg || 'Қате');
			if (r.ok) setTimeout(function () { location.reload(); }, 400);
		});
	});

	page.addEventListener('click', function (e) {
		var b = e.target.closest('.dz_edit, .dz_del');
		if (!b) return;
		var r = b.closest('.dz_i');
		if (b.classList.contains('dz_edit')) return open(r.dataset);
		if (!confirm('«' + r.dataset.name + '» көшесін өшіру керек пе?')) return;
		var d = new FormData();
		d.append('id', r.dataset.id);
		post('del', d, function (res) {
			mess(res.msg || 'Қате');
			if (res.ok) r.remove();
		});
	});
})();
