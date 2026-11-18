// Компания баптаулары беті /company/. Тыңдаушылар document-те: admin AJAX блокты ауыстырса да жұмыс істейді
(function () {
	if (window.cmp_js) return; window.cmp_js = 1
	var bw = function (k, d) { return (window.bw && window.bw[k]) || d }

	// Тапсырыс түрлері: өшірілген түр «алдымен таңдалатын» тізімде таңдалмайды
	var tdef_sync = function (form) {
		var sel = form.querySelector('.cmp_tdef'); if (!sel) return
		var first = null
		form.querySelectorAll('input[type=checkbox][data-tg]').forEach(function (c) {
			var o = sel.querySelector('option[value="' + c.dataset.tg.slice(1) + '"]'); if (!o) return
			o.disabled = !c.checked
			if (c.checked && first === null) first = o
		})
		var cur = sel.options[sel.selectedIndex]
		if ((!cur || cur.disabled) && first) first.selected = true
	}
	// Точка: белгіленбеген төлем түрі «алдымен таңдалатын» тізімде жоқ
	var pdef_sync = function (form) {
		var sel = form.querySelector('.cmp_pdef'); if (!sel) return
		form.querySelectorAll('input[name="pay_types[]"]').forEach(function (c) {
			var o = sel.querySelector('option[value="' + c.value + '"]'); if (o) o.disabled = !c.checked
		})
		if (sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].disabled) sel.value = ''
	}

	document.addEventListener('change', function (e) {
		var t = e.target
		if (!t.closest('.cmp_page')) return
		if (t.closest('.lc_sw') && t.type == 'checkbox') {
			var tx = t.closest('.lc_sw').querySelector('.lc_sw_t'); if (tx) tx.textContent = t.checked ? bw('cs:On', 'Қосулы') : bw('cs:Off', 'Өшірулі')
			var box = t.closest('.lc_tg'); if (box && box.querySelector('.lc_sw input[type=checkbox]') == t) box.classList.toggle('lc_off', !t.checked)
			if (t.dataset.tg) tdef_sync(t.form)
			return
		}
		if (t.name == 'pay_types[]') pdef_sync(t.form)
	})

	// RED чиптері
	document.addEventListener('click', function (e) {
		var c = e.target.closest('.cmp_chips .lc_chip'); if (!c) return
		e.preventDefault()
		var w = c.closest('.cmp_chips')
		w.querySelectorAll('.lc_chip').forEach(function (b) { b.classList.toggle('lc_chip_on', b == c) })
		w.querySelector('input[type=hidden]').value = c.dataset.v
	})
})()
