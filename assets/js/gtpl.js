// Граммовка шаблондары: тағам картасы (.gt_box) және Себес беті (.gt_all_btn)
(function () {
	var URL = '/products/tpl/get.php';
	function post(act, data) {
		var fd = new FormData(); Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(URL + '?' + act, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function w(k, d) { return (window.bw && window.bw[k]) || d; }
	// бағасыз жаңа шикізатқа баға (база, табылмаса ЖИ), сосын бетті жаңарту
	function prices(ids, el, done) {
		if (!ids || !ids.length) return done();
		if (el) el.textContent = w('pp Pricing', 'Жаңа заттардың бағасы анықталуда (база, ЖИ)…') + ' ' + ids.length;
		post('prices', { ids: JSON.stringify(ids) }).then(function (r) { if (r.error) (window.mess ? mess(r.error, 'err') : alert(r.error)); done(); }).catch(done);
	}
	// браузердің confirm() орнына админнің ask() терезесі (fun.js)
	var cf = function (q, yes, o) { if (window.ask) ask(q, yes, o || { ok: 'Иә, жалғастыру' }); else if (confirm(q)) yes(); };
	var SET_ASK = w('tpl Set ask', 'Бұл сет (жинақ / комбо) сияқты: сеттің құрамы «Сет құрамында» тағамдармен толтырылады. Бәрібір шикізат граммовкасын жазамыз ба?');
	function qty(it) { var q = +it.qty; q = Math.round(q * 1000) / 1000; return it.unit === 'кг' ? q + ' г' : it.unit === 'л' ? q + ' мл' : q + ' шт'; }

	// ---------- тағам картасы ----------
	function itemBox(box) {
		var pid = box.dataset.pid, open = box.dataset.has !== '1';
		box.innerHTML = '<div class="gt_head"><b><i class="fal fa-magic"></i> Граммовка шаблоны</b>' +
			'<input class="gt_q" placeholder="Басқа шаблон іздеу: цезарь, пицца…"></div><div class="gt_list"></div>' +
			'<div class="gt_aib" hidden><select class="gt_av"><option value="1">' + w('Economy', 'Эконом') + '</option><option value="2" selected>' + w('Standard', 'Стандарт') + '</option><option value="3">' + w('Premium', 'Премиум') + '</option></select>' +
			'<button type="button" class="btn gt_ai"><i class="fal fa-sparkles"></i> ' + w('tpl AI dish fill', 'ЖИ құрамын толтырсын') + '</button><span class="gt_ais">' + w('tpl AI dish hint', 'шаблон сай келмесе: ЖИ орташа граммовка құрады, керек дайындамаларды рецептімен ашады') + '</span></div>';
		if (!open) { box.classList.add('gt_closed'); box.querySelector('.gt_head b').insertAdjacentHTML('beforeend', ' <span class="gt_tog">ашу</span>'); }
		var list = box.querySelector('.gt_list'), qi = box.querySelector('.gt_q'), tm;
		box.querySelector('.gt_head b').addEventListener('click', function () { box.classList.toggle('gt_closed'); });
		function load(q) {
			list.innerHTML = '<div class="gt_wait">Жүктелуде…</div>';
			fetch(URL + '?match&pid=' + pid + '&q=' + encodeURIComponent(q || ''), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				box.querySelector('.gt_aib').hidden = !d.ai;
				if (!d.tpls || !d.tpls.length) { list.innerHTML = '<div class="gt_wait">Сай шаблон табылмады. Жоғарыдан іздеп көріңіз' + (d.ai ? ' не «' + w('pp AI fill', 'ЖИ толтырсын') + '» басыңыз' : '') + '.</div>'; return; }
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
			var run = function () {
			b.disabled = true;
			(function go(setOk) {
				post('apply', { pid: pid, tpl: b.dataset.tpl, variant: b.dataset.v, replace: rep ? 1 : 0, set_ok: setOk ? 1 : 0 }).then(function (r) {
					if (r.result === 'set' && !setOk) { b.disabled = false; return cf(SET_ASK, function () { b.disabled = true; go(true); }, { ok: 'Иә, жазу' }); }
					if (r.result === 'yes') prices(r.noprice, b, function () { location.reload(); });
					else { b.disabled = false; if (r.result !== 'set') (window.mess ? mess('Қолдану мүмкін болмады: ' + (r.result || r.error), 'err') : alert('Қолдану мүмкін болмады: ' + (r.result || r.error))); }
				});
			})(false);
			};
			if (rep) cf('Тағамның қазіргі құрамы өшіп, шаблонмен ауыстырылады. Жалғастырамыз ба?', run, { ok: 'Иә, ауыстыру' }); else run();
		});
		box.querySelector('.gt_ai').addEventListener('click', function () {
			var b = this, sp = box.querySelector('.gt_ais'), rep = box.dataset.has === '1';
			var run = function () {
			b.disabled = true;
			(function go(setOk) {
				sp.textContent = w('tpl AI dish working', 'ЖИ граммовка құрастыруда (базадағы ұқсас рецептер + интернет), 30-90 сек…');
				post('dish_ai', { pid: pid, variant: box.querySelector('.gt_av').value, replace: rep ? 1 : 0, set_ok: setOk ? 1 : 0 }).then(function (r) {
					if (r.result === 'set' && !setOk) { b.disabled = false; sp.textContent = ''; return cf(SET_ASK, function () { b.disabled = true; go(true); }, { ok: 'Иә, жазу' }); }
					if (r.result === 'yes') prices(r.noprice, sp, function () { location.reload(); });
					else { b.disabled = false; sp.textContent = (r.result === 'set' ? w('tpl Is set', 'Бұл сет: құрамы «Сет құрамында» толтырылады') : (r.error || r.result || 'Қате') + ' · ' + w('pp Fill by hand', 'құрамын қолмен толтыруға болады')); }
				}).catch(function () { b.disabled = false; sp.textContent = w('pp AI timeout', 'ЖИ жауап бермеді, қайталаңыз'); });
			})(false);
			};
			if (rep) cf('Тағамның қазіргі құрамы өшіп, ЖИ граммовкасымен ауыстырылады. Жалғастырамыз ба?', run, { ok: 'Иә, ауыстыру' }); else run();
		});
		load('');
	}

	// ---------- Себес: барлығын толтыру ----------
	function allBtn() {
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
				prev.innerHTML = '<span class="gt_spin"></span> Тексерілуде…'; go.disabled = true;
				post('all', { variant: v(), dry: 1 }).then(function (r) {
					if (r.error) { prev.innerHTML = 'Қате: ' + esc(r.error); return; }
					prev.innerHTML = '<div><b>' + r.ok.length + '</b> тағамға шаблон табылды' + (r.none.length ? ', <b>' + r.none.length + '</b> тағамға табылмады' : '') + '.</div>' +
						(r.ok.length ? '<details><summary>Тізім</summary><ul>' + r.ok.map(function (x) { return '<li>' + esc(x.name) + ' → ' + esc(x.tpl) + '</li>'; }).join('') + '</ul></details>' : '') +
						(r.none.length ? '<details><summary>Шаблоны жоқтар</summary><ul>' + r.none.map(function (x) { return '<li><a href="/products/item/?id=' + x.id + '">' + esc(x.name) + '</a></li>'; }).join('') + '</ul></details>' : '');
					go.disabled = !r.ok.length;
					// шаблоны жоқтарды ЖИ бір-бірлеп толтырады (позициялар ғана, сеттер кірмейді)
					if (r.none.length && r.ai) {
						var ab = document.createElement('button'); ab.type = 'button'; ab.className = 'btn gt_ai gt_ai_all';
						ab.innerHTML = '<i class="fal fa-sparkles"></i> ' + esc(w('tpl AI fill rest', 'Шаблоны жоқтарды ЖИ толтырсын')) + ': ' + r.none.length;
						m.querySelector('.gt_mb').prepend(ab);
						ab.onclick = function () { aiAll(r.none, v(), prev, ab, go); };
					}
				});
			}
			m.addEventListener('change', preview);
			m.querySelector('.gt_mx').onclick = function () { m.remove(); };
			go.onclick = function () {
				go.disabled = true; go.textContent = 'Толтырылуда…';
				post('all', { variant: v() }).then(function (r) {
					prices(r.ids, prev, function () {
						var t = r.ok.length + ' тағамға граммовка қойылды.' + (r.created ? ' Жаңа құрам / дайындама: ' + r.created + '.' : '');
						if (window.mess) { mess(t, 'ok'); setTimeout(function () { location.reload(); }, 1500); } else { alert(t); location.reload(); }
					});
				});
			};
			preview();
	}

	// Шаблоны жоқ тағамдарға ЖИ граммовкасы: бір-бірлеп, барысын көрсетіп; соңында бағасыз шикізатқа баға
	function aiAll(list, variant, prev, ab, go) {
		if (!aiAll.ok) return cf(w('tpl AI fill rest ask', 'ЖИ әр тағамға орташа граммовка құрады (бір тағамға ~15-60 сек, шығыны «ЖИ шығыны» бетінде). Бастаймыз ба?'), function () { aiAll.ok = 1; aiAll(list, variant, prev, ab, go); aiAll.ok = 0; }, { ok: 'Бастау' });
		ab.disabled = true; go.disabled = true;
		var i = 0, ok = 0, bad = [], ids = [], usd = 0;
		(function next() {
			if (i >= list.length) {
				prev.innerHTML = '<b>' + ok + '</b> ' + esc(w('tpl AI dishes done', 'тағамға ЖИ граммовка қойды')) + (bad.length ? '. ' + esc(w('tpl AI failed', 'Болмады')) + ': ' + bad.map(esc).join(', ') : '') + ' · $' + usd.toFixed(2);
				return prices(ids, null, function () { setTimeout(function () { location.reload(); }, 1200); });
			}
			var d = list[i++];
			prev.innerHTML = '<span class="gt_spin"></span> ' + i + ' / ' + list.length + ': <b>' + esc(d.name) + '</b>';
			post('dish_ai', { pid: d.id, variant: variant }).then(function (r) {
				if (r.result === 'yes') { ok++; ids = ids.concat(r.noprice || []); usd += r.usd || 0; } else bad.push(d.name + (r.result === 'set' ? ' (' + w('tpl set skipped', 'сет, өткізілді') + ')' : r.error ? ' (' + r.error + ')' : ''));
				next();
			}).catch(function () { bad.push(d.name); next(); });
		})();
	}

	// ---------- Себес: рецепті жоқ дайындамаларды шаблоннан толтыру ----------
	function semiBtn() {
		var m = document.createElement('div'); m.className = 'gt_modal';
		m.innerHTML = '<div class="gt_mbox"><div class="gt_mx">&times;</div><h4>' + w('pp Semis from templates', 'Дайындамаларды шаблоннан толтыру') + '</h4>' +
			'<p>' + w('tpl Semi all text', 'Рецепті жоқ дайындамаларға шаблоннан құрам жазылады: жоқ шикізат пен ішкі дайындамалар ашылады, бағасы базадан не ЖИ-ден толады.') + '</p>' +
			'<div class="gt_pick"><label><input type="radio" name="gsv" value="1"> <b>' + w('Economy', 'Эконом') + '</b></label>' +
			'<label><input type="radio" name="gsv" value="2" checked> <b>' + w('Standard', 'Стандарт') + '</b></label>' +
			'<label><input type="radio" name="gsv" value="3"> <b>' + w('Premium', 'Премиум') + '</b></label></div>' +
			'<div class="gt_prev"></div><div class="gt_mb"><button type="button" class="btn gt_go" disabled>' + w('tpl Fill', 'Толтыру') + '</button></div></div>';
		document.body.appendChild(m);
		var prev = m.querySelector('.gt_prev'), go = m.querySelector('.gt_go');
		function v() { return m.querySelector('input[name=gsv]:checked').value; }
		function preview() {
			prev.innerHTML = '<span class="gt_spin"></span> ' + esc(w('Checking', 'Тексерілуде…')); go.disabled = true;
			post('semi_all', { variant: v(), dry: 1 }).then(function (r) {
				if (r.error) { prev.textContent = r.error; return; }
				prev.innerHTML = r.ok.length ? '<div><b>' + r.ok.length + '</b> ' + w('tpl semis found', 'дайындамаға шаблон табылды') + '</div><details open><summary>' + w('tpl List', 'Тізім') + '</summary><ul>' +
					r.ok.map(function (x) { return '<li>' + esc(x.name) + ' → ' + esc(x.tpl) + '</li>'; }).join('') + '</ul></details>'
					: w('tpl No empty semis', 'Рецепті жоқ, шаблоны бар дайындама жоқ. Жаңасын «Дайындама қосу» арқылы шаблоннан не ЖИ-мен қосыңыз.');
				go.disabled = !r.ok.length;
			}).catch(function () { prev.textContent = w('pp Connection error', 'Байланыс қатесі, қайта көріңіз'); });
		}
		m.addEventListener('change', preview);
		m.querySelector('.gt_mx').onclick = function () { m.remove(); };
		go.onclick = function () {
			if (go.disabled) return;
			go.disabled = true; go.textContent = w('pp Loading', 'Толтырылуда…');
			prev.innerHTML = '<span class="gt_spin"></span> ' + esc(w('pp Loading', 'Толтырылуда…'));
			post('semi_all', { variant: v() }).then(function (r) {
				if (r.error) { go.disabled = false; go.textContent = w('tpl Fill', 'Толтыру'); prev.textContent = r.error; return; }
				prices(r.ids, prev, function () { location.hash = 'prep'; location.reload(); });
			}).catch(function () { go.disabled = false; go.textContent = w('tpl Fill', 'Толтыру'); prev.textContent = w('pp Connection error', 'Байланыс қатесі, қайта көріңіз'); });
		};
		preview();
	}

	// өшік «Толтыру» батырмасы анық көрінсін (басуға болмайтыны)
	if (!document.getElementById('gt_dis_css')) { var cs = document.createElement('style'); cs.id = 'gt_dis_css'; cs.textContent = '.gt_modal .gt_go:disabled{opacity:.4;cursor:not-allowed;filter:grayscale(.6)}.gt_spin{display:inline-block;width:14px;height:14px;border:2px solid #d1d5db;border-top-color:#16a34a;border-radius:50%;vertical-align:-2px;animation:gt_r .8s linear infinite}@keyframes gt_r{to{transform:rotate(360deg)}}'; document.head.appendChild(cs); }
	document.querySelectorAll('.gt_box').forEach(itemBox);
	// Себес блогы AJAX-пен жаңарады: батырмалар document арқылы
	document.addEventListener('click', function (e) {
		if (e.target.closest('.gt_all_btn')) allBtn();
		else if (e.target.closest('.gt_semi_btn')) semiBtn();
	});
})();
