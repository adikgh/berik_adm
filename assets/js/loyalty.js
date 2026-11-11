// Кэшбэк және сыйлық беттері. Тыңдаушылар document-те: admin AJAX блокты ауыстырса да жұмыс істейді
(function () {
	if (window.lc_js) return; window.lc_js = 1

	// Қазақша реттік жұрнақ: 5-ші, 6-шы, 10-шы, 20-шы
	var ord = function (n) { n = parseInt(n) || 0; if (n <= 0 || n % 100 == 0) return '-ші'; var d = n % 10
		if (d) return (d == 6 || d == 9) ? '-шы' : '-ші'; return [5, 7, 8].indexOf((n % 100) / 10) >= 0 ? '-ші' : '-шы' }

	document.addEventListener('change', function (e) {
		var t = e.target
		// Қосу/өшіру ауыстырғышы: баптаулар бірден ашылады/жабылады
		if (t.closest('.lc_sw') && t.type == 'checkbox') {
			var box = t.closest('.lc_tg')
			box.classList.toggle('lc_off', !t.checked)
			var tx = t.closest('.lc_sw').querySelector('.lc_sw_t'); if (tx) tx.textContent = t.checked ? 'Қосулы' : 'Өшірулі'
			return
		}
		// Тағам таңдалса — саны ашылады, жеңілдік жабылады
		if (t.classList.contains('lc_t_prod')) {
			var r = t.closest('.lc_tier'), hp = t.value != '0'
			r.classList.toggle('lc_tier_prod', hp)
			r.querySelector('.lc_t_q').disabled = !hp
			r.querySelectorAll('.lc_t_d').forEach(function (x) { x.disabled = hp; if (hp) x.value = '' })
		}
	})

	document.addEventListener('input', function (e) {
		var t = e.target
		if (t.classList.contains('lc_t_every')) t.closest('.lc_tier').querySelector('.lc_t_ord').textContent = ord(t.value)
		// «Басқа» шегі: санды жасырын өріске жазу
		if (t.closest('.lc_pm_x')) {
			var v = Math.max(0, Math.min(99, parseInt(t.value) || 0))
			t.closest('.lc_chips').querySelector('input[name=cashback_pay_max]').value = v
		}
	})

	// Төлеу шегінің чиптері; сыйлықты жою/қайтару
	document.addEventListener('click', function (e) {
		var d = e.target.closest('.lc_del, .lc_undo')
		if (d) {
			e.preventDefault()
			var row = d.closest('.lc_tier'), del = d.classList.contains('lc_del')
			row.classList.toggle('lc_tier_del', del)
			row.querySelector('.lc_del_v').value = del ? 1 : 0
			return
		}
		var c = e.target.closest('.lc_chip'); if (!c) return
		var w = c.closest('.lc_chips'), hid = w.querySelector('input[name=cashback_pay_max]'), x = w.querySelector('.lc_pm_x')
		w.querySelectorAll('.lc_chip').forEach(function (b) { b.classList.toggle('lc_chip_on', b == c) })
		if (c.dataset.v == 'x') {
			x.hidden = false
			var inp = x.querySelector('input'); inp.focus()
			hid.value = Math.max(0, Math.min(99, parseInt(inp.value) || 0))
		} else { x.hidden = true; hid.value = c.dataset.v }
	})
})()
