// Кезең атауын (.st_nav_t[data-pick]) басқанда кезең таңдау терезесі ашылады:
// Апта — күнтізбе (апта жолын басасыз), Ай — айлар, Жыл — жылдар, Күн — күнтізбе. Болашақ кезеңдер жабық.
// AJAX-пен кезең ауысқанда (fun.js ajx) жаңа атауға қайта қосылады
function stPick() {
	var el = document.querySelector('.st_nav_t[data-pick]');
	if (!el) return;
	var mode = el.dataset.mode, cur = el.dataset.d, today = el.dataset.today, kind = el.dataset.kind || 'd', extra = el.dataset.q || '';
	var mn = ['Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'];
	var ms = ['Қаң', 'Ақп', 'Нау', 'Сәу', 'Мам', 'Мау', 'Шіл', 'Там', 'Қыр', 'Қаз', 'Қар', 'Жел'];
	function z(n) { return (n < 10 ? '0' : '') + n; }
	function ymd(t) { return t.getFullYear() + '-' + z(t.getMonth() + 1) + '-' + z(t.getDate()); }
	function dt(s) { return new Date(s + 'T00:00:00'); }
	function monday(s) { var t = dt(s), w = (t.getDay() + 6) % 7; t.setDate(t.getDate() - w); return ymd(t); }

	// сілтеме: ОПиУ/РНП — ?mode=..&d=..; Статистика — ?mode=w&w=.., ?mode=m&m=YYYY-MM, ?mode=y&y=YYYY, ?mode=d&d=..
	function url(d) {
		var p = 'd=' + d;
		if (kind === 'stats') p = mode === 'w' ? 'w=' + d : mode === 'm' ? 'm=' + d.substr(0, 7) : mode === 'y' ? 'y=' + d.substr(0, 4) : 'd=' + d;
		return '?mode=' + mode + '&' + p + extra;
	}

	el.classList.add('st_pick_t');
	el.title = 'Кезеңді таңдау';
	var box = null, view = dt(cur || today);

	function close() { if (box) { box.remove(); box = null; } document.removeEventListener('mousedown', outside, true); }
	function outside(e) { if (box && !box.contains(e.target) && e.target !== el) close(); }

	function render() {
		var h = '';
		if (mode === 'y') {
			var ty = +today.substr(0, 4), sy = +(cur || today).substr(0, 4);
			h += '<div class="st_pk_h"><b>Жыл</b></div><div class="st_pk_g st_pk_g3">';
			for (var y = ty - 8; y <= ty; y++) h += '<a class="st_pk_i' + (y === sy ? ' st_pk_act' : '') + '" data-go="' + y + '-01-01">' + y + '</a>';
			h += '</div>';
		} else if (mode === 'm') {
			var vy = view.getFullYear(), tm = today.substr(0, 7), sm = (cur || today).substr(0, 7);
			h += '<div class="st_pk_h"><a class="st_pk_n" data-nav="-12">&lsaquo;</a><b>' + vy + '</b><a class="st_pk_n' + (vy >= +today.substr(0, 4) ? ' st_pk_off' : '') + '" data-nav="12">&rsaquo;</a></div><div class="st_pk_g st_pk_g3">';
			for (var m = 0; m < 12; m++) {
				var k = vy + '-' + z(m + 1);
				h += '<a class="st_pk_i' + (k === sm ? ' st_pk_act' : '') + (k > tm ? ' st_pk_off' : '') + '" data-go="' + k + '-01">' + ms[m] + '</a>';
			}
			h += '</div>';
		} else {
			// күнтізбе: Апта — бүкіл жол таңдалады, Күн — бір күн
			var y0 = view.getFullYear(), m0 = view.getMonth(), first = new Date(y0, m0, 1);
			var st = dt(monday(ymd(first))), sel = mode === 'w' ? monday(cur || today) : (cur || today);
			var nextOff = ymd(new Date(y0, m0 + 1, 1)) > today;
			h += '<div class="st_pk_h"><a class="st_pk_n" data-nav="-1">&lsaquo;</a><b>' + mn[m0] + ' ' + y0 + '</b><a class="st_pk_n' + (nextOff ? ' st_pk_off' : '') + '" data-nav="1">&rsaquo;</a></div>';
			h += '<div class="st_pk_cal"><div class="st_pk_wd"><span>Дс</span><span>Сс</span><span>Ср</span><span>Бс</span><span>Жм</span><span>Сб</span><span>Жс</span></div>';
			for (var r = 0; r < 6; r++) {
				var wk = ymd(st);
				if (r > 0 && st.getMonth() !== m0 && st > first) break;
				h += '<div class="st_pk_row' + (mode === 'w' ? ' st_pk_wk' + (wk === sel ? ' st_pk_act' : '') + (wk > today ? ' st_pk_off' : '') : '') + '"' + (mode === 'w' ? ' data-go="' + wk + '"' : '') + '>';
				for (var i = 0; i < 7; i++) {
					var d = ymd(st), cls = 'st_pk_d' + (st.getMonth() !== m0 ? ' st_pk_out' : '') + (d === today ? ' st_pk_today' : '');
					if (mode !== 'w') cls += (d === sel ? ' st_pk_act' : '') + (d > today ? ' st_pk_off' : '');
					h += '<span class="' + cls + '"' + (mode !== 'w' ? ' data-go="' + d + '"' : '') + '>' + st.getDate() + '</span>';
					st.setDate(st.getDate() + 1);
				}
				h += '</div>';
			}
			h += '</div>';
		}
		box.innerHTML = h;
	}

	function open() {
		if (box) { close(); return; }
		view = dt(cur || today);
		box = document.createElement('div');
		box.className = 'st_pk';
		el.parentNode.appendChild(box);
		render();
		box.addEventListener('click', function (e) {
			var n = e.target.closest('[data-nav]');
			if (n) { if (n.classList.contains('st_pk_off')) return; view = new Date(view.getFullYear(), view.getMonth() + (+n.dataset.nav), 1); render(); return; }
			var g = e.target.closest('[data-go]');
			if (g && !g.classList.contains('st_pk_off')) { close(); ajxUrl(el, url(g.dataset.go)); }
		});
		setTimeout(function () { document.addEventListener('mousedown', outside, true); }, 0);
	}
	el.addEventListener('click', open);
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
}
stPick();
$(document).on('ajx:done', stPick);
