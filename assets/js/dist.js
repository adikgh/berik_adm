// Раздача экраны (orders/dist/): курьер таңдау, Столда / Берілді, статус тарихы. Сұраулар: /orders/dist/get.php
;(function () {
	var U = '/orders/dist/get.php?'
	var post = function (a, data, ok) {
		var f = new FormData(); for (var k in data) f.append(k, data[k])
		fetch(U + a, { method: 'POST', body: f, credentials: 'same-origin' }).then(function (r) { return r.text() }).then(ok).catch(function () { ok('') })
	}

	// Астынан шығатын панельдер: .dsp ішіндегі контейнерге көшіріледі (стильдер .dsp ішінде), сонда мәзір мен карточкалардың үстінде тұрады
	var host = document.createElement('div'); host.className = 'dsp dsp_sheets'
	var mv = function () { document.body.appendChild(host); document.querySelectorAll('.bp_dr').forEach(function (d) { if (d.parentNode !== host) host.appendChild(d) }) }
	if (document.readyState == 'loading') document.addEventListener('DOMContentLoaded', mv); else mv()
	var sheet = window.bpSheet = function (dr, v) { if (!dr) return; dr.classList.toggle('bp_dr_on', v); document.documentElement.classList.toggle('ovr_h', v) }
	document.addEventListener('keydown', function (e) { if (e.key == 'Escape') document.querySelectorAll('.bp_dr_on').forEach(function (d) { sheet(d, false) }) })

	var order = 0, busy = false
	document.addEventListener('click', function (e) {
		var t = e.target, b

		if (t.closest('.bp_dr_bg, .bp_dr_x')) { sheet(t.closest('.bp_dr'), false); return }

		// Курьер таңдау
		if ((b = t.closest('.on_pick_staff'))) {
			var sh = document.querySelector('.bp_staff_sh'); if (!sh) return
			order = b.dataset.orderId
			sh.querySelectorAll('.bp_staff_i').forEach(function (i) { i.classList.toggle('bp_li_act', i.dataset.id == b.dataset.cur) })
			sh.querySelector('.bp_staff_off').style.display = b.dataset.cur != '0' ? '' : 'none'
			sheet(sh, true); return
		}
		if ((b = t.closest('.bp_staff_i'))) {
			if (!order || busy) return
			busy = true
			post('change_staff', { id: b.dataset.id, order_id: order }, function (r) { busy = false; if (r == 'yes' || r == 'locked') location.reload() })
			return
		}

		// Курьер сүзгісі
		if (t.closest('.on_pick_flt')) { sheet(document.querySelector('.bp_flt_sh'), true); return }

		// Кафе: Столда; Өзімен / Сервис: Берілді
		if ((b = t.closest('.on_table'))) { post('table', { id: b.dataset.id }, function () { location.reload() }); return }
		if ((b = t.closest('.on_check'))) {
			post('check', { id: b.dataset.id }, function (r) { if (r == 'yes') { var c = b.closest('.uc_ui'); if (c) c.remove() } })
			return
		}

		// Статус тарихы
		if ((b = t.closest('.on_hist'))) {
			var hs = document.querySelector('.bp_hist_sh'); if (!hs) return
			hs.querySelector('.bp_hist_nm').textContent = '№' + b.dataset.nm
			hs.querySelector('.oh_list').innerHTML = '<div class="oh_none">…</div>'
			sheet(hs, true)
			post('history', { id: b.dataset.id }, function (r) { hs.querySelector('.oh_list').innerHTML = r })
			return
		}

		// Тағамдар 3-тен көп болса: қалғанын ашу / жасыру
		if ((b = t.closest('.on_more'))) {
			var open = b.parentNode.classList.toggle('bp_more_on')
			b.querySelector('span').textContent = open ? b.dataset.less : b.dataset.more
			return
		}

		// Тарих: «Статус» / «Өзгерістер» табтары
		if ((b = t.closest('.oh_tab'))) {
			var box = b.parentNode.parentNode
			box.querySelectorAll('.oh_tab').forEach(function (x) { x.classList.toggle('oh_tab_act', x === b) })
			box.querySelectorAll('.oh_pane').forEach(function (p) { p.classList.toggle('dsp_n', p.dataset.p != b.dataset.t) })
		}
	})

	// Жаңа / Жолда / Аяқталған: белсенді фон жылжиды
	document.querySelectorAll('.bp_tabs').forEach(function (c) {
		var items = [].slice.call(c.querySelectorAll('.bp_tab')), cur = -1
		items.forEach(function (a, i) { if (a.classList.contains('bp_tab_act')) cur = i })
		c.style.setProperty('--n', items.length)
		var key = 'tb_i_' + items.map(function (a) { return a.getAttribute('href') }).join('|')
		var prev = -1; try { prev = parseInt(sessionStorage.getItem(key)) } catch (e) {}
		if (prev >= 0 && prev < items.length && prev != cur) {
			c.classList.add('pm_noanim'); c.style.setProperty('--i', prev)
			requestAnimationFrame(function () { requestAnimationFrame(function () { c.classList.remove('pm_noanim'); c.style.setProperty('--i', cur) }) })
		} else c.style.setProperty('--i', Math.max(cur, 0))
		items.forEach(function (a, i) { a.addEventListener('click', function () {
			try { sessionStorage.setItem(key, i) } catch (e) {}
			items.forEach(function (x) { x.classList.remove('bp_tab_act') }); a.classList.add('bp_tab_act'); c.style.setProperty('--i', i)
		}) })
		try { sessionStorage.setItem(key, cur) } catch (e) {}
	})
})()
