// Граммовка шаблондары: тағам картасы (.gt_box) және Себес беті (.gt_all_btn)
(function () {
	var URL = '/products/tpl/get.php';
	function post(act, data) {
		var fd = new FormData(); Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(URL + '?' + act, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function qty(it) { var q = +it.qty; q = Math.round(q * 1000) / 1000; return it.unit === 'кг' ? q + ' г' : it.unit === 'л' ? q + ' мл' : q + ' шт'; }

	// ---------- тағам картасы ----------
	function itemBox(box) {
		var pid = box.dataset.pid, open = box.dataset.has !== '1';
		box.innerHTML = '<div class="gt_head"><b><i class="fal fa-magic"></i> Граммовка шаблоны</b>' +
			'<input class="gt_q" placeholder="Басқа шаблон іздеу: цезарь, пицца…"></div><div class="gt_list"></div>';
		if (!open) { box.classList.add('gt_closed'); box.querySelector('.gt_head b').insertAdjacentHTML('beforeend', ' <span class="gt_tog">ашу</span>'); }
		var list = box.querySelector('.gt_list'), qi = box.querySelector('.gt_q'), tm;
		box.querySelector('.gt_head b').addEventListener('click', function () { box.classList.toggle('gt_closed'); });
		function load(q) {
			list.innerHTML = '<div class="gt_wait">Жүктелуде…</div>';
			fetch(URL + '?match&pid=' + pid + '&q=' + encodeURIComponent(q || ''), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				if (!d.tpls || !d.tpls.length) { list.innerHTML = '<div class="gt_wait">Сай шаблон табылмады. Жоғарыдан іздеп көріңіз.</div>'; return; }
				list.innerHTML = d.tpls.map(function (t) {
					return '<div class="gt_tpl"><div class="gt_tn">' + esc(t.name) + ' <span>' + esc(t.cat || '') + '</span></div><div class="gt_vars">' +
						[1, 2, 3].map(function (v) {
							var its = t.vars[v] || [];
							if (!its.length) return '';
							return '<div class="gt_var"><div class="gt_vn">' + esc(d.names[v]) + '</div><ul>' +
								its.map(function (it) { return '<li><span>' + esc(it.name) + '</span><em>' + qty(it) + '</em></li>'; }).join('') +
								'</ul><button type="button" class="btn gt_use" data-tpl="' + t.id + '" data-v="' + v + '">Осыны алу</button></div>';
						}).join('') + '</div></div>';
				}).join('');
				box.dataset.has = d.has ? '1' : '0';
			}).catch(function () { list.innerHTML = '<div class="gt_wait">Қате. Бетті жаңартып көріңіз.</div>'; });
		}
		qi.addEventListener('input', function () { clearTimeout(tm); tm = setTimeout(function () { load(qi.value.trim()); }, 300); });
		list.addEventListener('click', function (e) {
			var b = e.target.closest('.gt_use'); if (!b) return;
			var rep = box.dataset.has === '1';
			if (rep && !confirm('Тағамның қазіргі құрамы өшіп, шаблонмен ауыстырылады. Жалғастырамыз ба?')) return;
			b.disabled = true;
			post('apply', { pid: pid, tpl: b.dataset.tpl, variant: b.dataset.v, replace: rep ? 1 : 0 }).then(function (r) {
				if (r.result === 'yes') location.reload();
				else { b.disabled = false; alert('Қолдану мүмкін болмады: ' + (r.result || r.error)); }
			});
		});
		load('');
	}

	// ---------- Себес: барлығын толтыру ----------
	function allBtn(btn) {
		btn.addEventListener('click', function () {
			var m = document.createElement('div'); m.className = 'gt_modal';
			m.innerHTML = '<div class="gt_mbox"><div class="gt_mx">&times;</div><h4>Шаблоннан толтыру</h4>' +
				'<p>Граммовкасы бос тағамдардың бәріне шаблон қойылады. Қай нұсқаны аламыз?</p>' +
				'<div class="gt_pick"><label><input type="radio" name="gtv" value="1"> <b>Эконом</b><span>аз мөлшер</span></label>' +
				'<label><input type="radio" name="gtv" value="2" checked> <b>Стандарт</b><span>орташа</span></label>' +
				'<label><input type="radio" name="gtv" value="3"> <b>Премиум</b><span>мол мөлшер</span></label></div>' +
				'<div class="gt_prev"></div><div class="gt_mb"><button type="button" class="btn gt_go" disabled>Толтыру</button></div></div>';
			document.body.appendChild(m);
			var prev = m.querySelector('.gt_prev'), go = m.querySelector('.gt_go');
			function v() { return m.querySelector('input[name=gtv]:checked').value; }
			function preview() {
				prev.innerHTML = 'Тексерілуде…'; go.disabled = true;
				post('all', { variant: v(), dry: 1 }).then(function (r) {
					if (r.error) { prev.innerHTML = 'Қате: ' + esc(r.error); return; }
					prev.innerHTML = '<div><b>' + r.ok.length + '</b> тағамға шаблон табылды' + (r.none.length ? ', <b>' + r.none.length + '</b> тағамға табылмады' : '') + '.</div>' +
						(r.ok.length ? '<details><summary>Тізім</summary><ul>' + r.ok.map(function (x) { return '<li>' + esc(x.name) + ' → ' + esc(x.tpl) + '</li>'; }).join('') + '</ul></details>' : '') +
						(r.none.length ? '<details><summary>Шаблоны жоқтар</summary><ul>' + r.none.map(function (x) { return '<li><a href="/products/item/?id=' + x.id + '">' + esc(x.name) + '</a></li>'; }).join('') + '</ul></details>' : '');
					go.disabled = !r.ok.length;
				});
			}
			m.addEventListener('change', preview);
			m.querySelector('.gt_mx').onclick = function () { m.remove(); };
			go.onclick = function () {
				go.disabled = true; go.textContent = 'Толтырылуда…';
				post('all', { variant: v() }).then(function (r) {
					alert(r.ok.length + ' тағамға граммовка қойылды.' + (r.created ? ' Жаңа құрам: ' + r.created + ' (бағасын Себесте толтырыңыз).' : ''));
					location.reload();
				});
			};
			preview();
		});
	}

	document.querySelectorAll('.gt_box').forEach(itemBox);
	document.querySelectorAll('.gt_all_btn').forEach(allBtn);
})();
