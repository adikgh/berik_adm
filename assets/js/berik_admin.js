// Berik admin: ортақ әдемілеу (дата, ашылмалы тізімдер, курьер төлемі)
$(document).ready(function() {

	var L = window.ui_lang || 'kz';
	var W = window.bw || {};
	function w(k, d) { return W[k] || d; }

	// ---------- Дата: ← күн → , Бүгін ----------
	var months = {
		kz: ['қаңтар','ақпан','наурыз','сәуір','мамыр','маусым','шілде','тамыз','қыркүйек','қазан','қараша','желтоқсан'],
		ru: ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'],
		en: ['January','February','March','April','May','June','July','August','September','October','November','December'],
		uz: ['yanvar','fevral','mart','aprel','may','iyun','iyul','avgust','sentabr','oktabr','noyabr','dekabr'],
		ky: ['январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'],
	};
	var days = {
		kz: ['жексенбі','дүйсенбі','сейсенбі','сәрсенбі','бейсенбі','жұма','сенбі'],
		ru: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
		en: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
		uz: ['yakshanba','dushanba','seshanba','chorshanba','payshanba','juma','shanba'],
		ky: ['жекшемби','дүйшөмбү','шейшемби','шаршемби','бейшемби','жума','ишемби'],
	};
	function ymd(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
	function parse(v) { var p = (v || '').split('-'); return p.length == 3 ? new Date(+p[0], +p[1] - 1, +p[2]) : null; }
	// жұмыс күні 06:00-де басталады
	function workToday() { var d = new Date(Date.now() - 6 * 3600 * 1000); return ymd(d); }
	function nice(v) {
		var d = parse(v); if (!d) return v;
		var m = (months[L] || months.kz)[d.getMonth()], dd = (days[L] || days.kz)[d.getDay()];
		return d.getDate() + ' ' + m + ', ' + dd;
	}

	var months_n = {
		kz: ['қаңтар','ақпан','наурыз','сәуір','мамыр','маусым','шілде','тамыз','қыркүйек','қазан','қараша','желтоқсан'],
		ru: ['январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'],
		en: months.en, uz: months.uz, ky: months.ky,
	};
	var wd_short = {
		kz: ['Дс','Сс','Ср','Бс','Жм','Сб','Жс'], ru: ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'], en: ['Mo','Tu','We','Th','Fr','Sa','Su'],
		uz: ['Du','Se','Cho','Pa','Ju','Sha','Ya'], ky: ['Дш','Шш','Шр','Бш','Жм','Иш','Жк'],
	};
	function bcal_close() { $('.bcal').removeClass('pop_on'); $('.bdt').removeClass('bdt_open'); }
	$(document).on('click', bcal_close);
	$(document).on('keydown', function(e) { if (e.key == 'Escape') bcal_close(); });
	$(window).on('resize scroll', bcal_close);

	$('input.on_sort_time').each(function() {
		var inp = $(this);
		if (inp.closest('.bdt').length) return;
		// кезең: day (әдепкі) | month | year — data-period арқылы
		var per = inp.attr('data-period') || 'day';
		function mname(m) { var n = (months_n[L] || months_n.kz)[m]; return n.charAt(0).toUpperCase() + n.slice(1); }
		function key(v) { return per == 'year' ? v.slice(0, 4) : (per == 'month' ? v.slice(0, 7) : v); }
		var t_cur = per == 'year' ? w('This year', 'Биыл') : (per == 'month' ? w('This month', 'Осы ай') : w('Today', 'Бүгін'));
		var t_prev = per == 'year' ? w('Previous year', 'Алдыңғы жыл') : (per == 'month' ? w('Previous month', 'Алдыңғы ай') : w('Previous day', 'Алдыңғы күн'));
		var t_next = per == 'year' ? w('Next year', 'Келесі жыл') : (per == 'month' ? w('Next month', 'Келесі ай') : w('Next day', 'Келесі күн'));
		function label(v) {
			var d = parse(v); if (!d || per == 'day') return nice(v);
			return per == 'year' ? String(d.getFullYear()) : mname(d.getMonth()) + ' ' + d.getFullYear();
		}
		// құрылым: [←] [күнтізбе белгісі + мәтін + жасырын input] [→] [Бүгін]
		var box = $('<div class="bdt"></div>');
		var main = $('<label class="bdt_main"><i class="fal fa-calendar-alt"></i><span class="bdt_txt"></span></label>');
		inp.before(box);
		box.append('<div class="bdt_arr bdt_prev" title="' + t_prev + '"><i class="far fa-angle-left"></i></div>');
		box.append(main);
		main.append(inp);
		box.append('<div class="bdt_arr bdt_next" title="' + t_next + '"><i class="far fa-angle-right"></i></div>');
		var today = workToday(), is_cur = key(inp.val()) == key(today);
		if (!is_cur) box.append('<div class="bdt_today">' + t_cur + '</div>');
		else box.addClass('bdt_is_today');
		main.find('.bdt_txt').text((is_cur ? t_cur + ' · ' : '') + label(inp.val()));
		// Браузердің күнтізбесі емес, өз күнтізбеміз (кассадағыдай). body-ге қойылады — карточкалардың астында қалмайды
		var cal = $('<div class="bcal"></div>'), vm = null, sel = inp.val() || today;
		$('body').append(cal);
		// ай / жыл таңдау: 3 бағанды тор
		function cell(v, txt) {
			var c = 'bcal_d';
			if (key(v) == key(today)) c += ' bcal_tod';
			if (key(v) == key(sel)) c += ' bcal_sel';
			return '<div class="' + c + '" data-v="' + v + '">' + txt + '</div>';
		}
		function draw() {
			var y = vm.getFullYear(), m = vm.getMonth(), mn = mname(m), h;
			if (per != 'day') {
				var y0 = per == 'year' ? y - y % 12 : y;
				h = '<div class="bcal_head"><div class="bcal_nav bcal_pm"><i class="far fa-angle-left"></i></div><b>' + (per == 'year' ? y0 + ' – ' + (y0 + 11) : y) + '</b><div class="bcal_nav bcal_nm"><i class="far fa-angle-right"></i></div></div><div class="bcal_grid bcal_grid3">';
				for (var k = 0; k < 12; k++) h += per == 'year' ? cell((y0 + k) + '-01-01', y0 + k) : cell(y + '-' + ('0' + (k + 1)).slice(-2) + '-01', mname(k));
				cal.html(h + '</div><div class="bcal_foot"><div class="bcal_today">' + t_cur + '</div></div>');
				return;
			}
			h = '<div class="bcal_head"><div class="bcal_nav bcal_pm"><i class="far fa-angle-left"></i></div><b>' + mn + ' ' + y + '</b><div class="bcal_nav bcal_nm"><i class="far fa-angle-right"></i></div></div><div class="bcal_grid">';
			$.each(wd_short[L] || wd_short.kz, function(i, n) { h += '<span class="bcal_wd' + (i > 4 ? ' bcal_we' : '') + '">' + n + '</span>'; });
			var start = new Date(y, m, 1); start.setDate(1 - (start.getDay() + 6) % 7);
			for (var i = 0; i < 42; i++) {
				var d = new Date(start); d.setDate(start.getDate() + i);
				var v = ymd(d), c = 'bcal_d';
				if (d.getMonth() != m) c += ' bcal_out';
				if (v == today) c += ' bcal_tod';
				if (v == sel) c += ' bcal_sel';
				h += '<div class="' + c + '" data-v="' + v + '">' + d.getDate() + '</div>';
				if (i == 34 && d.getMonth() != m) break;
			}
			h += '</div><div class="bcal_foot"><div class="bcal_today">' + w('Today', 'Бүгін') + '</div></div>';
			cal.html(h);
		}
		function place() {
			var r = main[0].getBoundingClientRect(), cw = cal.outerWidth();
			cal.css({ top: r.bottom + 8, left: Math.max(8, Math.min(r.left - 50, window.innerWidth - cw - 8)) });
		}
		main.on('click', function(e) {
			e.preventDefault(); e.stopPropagation();
			var open = cal.hasClass('pop_on');
			bcal_close();
			if (!open) { vm = parse(sel) || new Date(); vm.setDate(1); draw(); place(); cal.addClass('pop_on'); box.addClass('bdt_open'); }
		});
		cal.on('click', function(e) { e.stopPropagation(); });
		cal.on('click', '.bcal_pm, .bcal_nm', function() {
			var k = $(this).hasClass('bcal_nm') ? 1 : -1;
			if (per == 'day') vm.setMonth(vm.getMonth() + k); else vm.setFullYear(vm.getFullYear() + k * (per == 'year' ? 12 : 1));
			draw();
		});
		cal.on('click', '.bcal_d', function() { bcal_close(); go($(this).attr('data-v')); });
		cal.on('click', '.bcal_today', function() { bcal_close(); go(today); });
		function go(v) { inp.val(v).trigger('change'); }
		box.on('click', '.bdt_prev, .bdt_next', function() {
			var d = parse(inp.val()) || new Date(), k = $(this).hasClass('bdt_next') ? 1 : -1;
			if (per == 'day') d.setDate(d.getDate() + k);
			else { d.setDate(1); if (per == 'year') d.setFullYear(d.getFullYear() + k, 0, 1); else d.setMonth(d.getMonth() + k); }
			go(ymd(d));
		});
		box.on('click', '.bdt_today', function() { go(today); });
	});


	// ---------- Ашылмалы тізімдер: бір келкі әдемі ----------
	// Нағыз <select> орнында қалады (жасырын), таңдағанда соған 'change' жіберіледі — бұрынғы логика өзгермейді.
	function enhance(sel) {
		var s = $(sel);
		if (s.data('bsel') || s.prop('multiple')) return;
		s.data('bsel', 1);
		var wrap = $('<div class="bsel"></div>');
		if (s.hasClass('on_sort_company')) wrap.addClass('bsel_company');
		s.after(wrap); wrap.append(s);
		var btn = $('<div class="bsel_btn"><span class="bsel_txt"></span><i class="far fa-angle-down"></i></div>');
		var list = $('<div class="bsel_list"></div>');
		wrap.append(btn).append(list);
		function sync() {
			var o = s.find('option:selected');
			btn.find('.bsel_txt').text(o.text());
			wrap.toggleClass('bsel_on', s.prop('selectedIndex') > 0);
			list.empty();
			s.find('option').each(function(i) {
				list.append($('<div class="bsel_i"></div>').text($(this).text()).attr('data-i', i).toggleClass('bsel_act', this.selected));
			});
		}
		sync();
		btn.on('click', function(e) {
			e.stopPropagation();
			var open = wrap.hasClass('bsel_open');
			$('.bsel').removeClass('bsel_open'); bcal_close();
			if (!open) wrap.addClass('bsel_open');
		});
		list.on('click', '.bsel_i', function(e) {
			e.stopPropagation();
			var i = +$(this).attr('data-i');
			wrap.removeClass('bsel_open');
			if (i == s.prop('selectedIndex')) return;
			s.prop('selectedIndex', i);
			sync();
			s.trigger('change');
		});
		s.on('change.bsel', sync);
	}
	$('.uc_ui69 select, .head_rk select').each(function() { enhance(this); });
	$(document).on('click', function() { $('.bsel').removeClass('bsel_open'); });
	$(document).on('keydown', function(e) { if (e.key == 'Escape') $('.bsel').removeClass('bsel_open'); });


	// ---------- Курьер ақшаны қалай алды ----------
	function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
	$('html').on('click', '.cpay_i', function() {
		var b = $(this), box = b.closest('.cpay'), sum = +box.attr('data-sum'), m = b.attr('data-m'), cash = 0;
		if (b.hasClass('cpay_act')) {
			if (!confirm(w('Remove mark?', 'Белгіні алып тастайық па?'))) return;
			m = '';
		} else if (m == 'mix') {
			var v = prompt(w('Mix cash prompt', 'Қолма-қол қанша алды? Қалғаны Kaspi болып жазылады.') + ' (' + fmt(sum) + ' тг)', '');
			if (v === null) return;
			cash = parseInt(String(v).replace(/\D/g, '')) || 0;
			if (cash <= 0 || cash >= sum) { mess(w('Mix cash wrong', 'Сумма 0 мен жалпы соманың арасында болуы керек')); return; }
		}
		$.ajax({
			url: '/orders/get.php?courier_pay', type: 'POST', dataType: 'json',
			data: { id: box.attr('data-id'), method: m, cash: cash },
			success: function(d) {
				if (!d || !d.ok) { mess(d && d.err == 'sql' ? w('SQL needed', 'Алдымен базаға SQL қосу керек') : w('Error', 'Қате!')); return; }
				box.find('.cpay_i').removeClass('cpay_act');
				if (d.method) box.find('.cpay_i[data-m="' + d.method + '"]').addClass('cpay_act');
				box.toggleClass('cpay_done', !!d.method);
				box.find('.cpay_r').html(d.method ? w('Cash', 'Қолма-қол') + ': <b>' + fmt(d.cash) + ' тг</b> · Kaspi: <b>' + fmt(d.kaspi) + ' тг</b>' : w('Not marked yet', 'Әлі белгіленбеген'));
				mess(w('Saved', 'Сақталды'));
				if ($('.k_cr_det').length) box.closest('.k_cr_det').addClass('k_cr_changed');
			},
			error: function() { mess(w('Error', 'Қате!')) },
		});
	});

	// аккаунт мәзірінің ішін басқанда жабылмасын (сілтемелерден басқа)
	$('html').on('click', '.acc_hd, .acc_lang_h', function(e) { e.stopPropagation(); });
	$(document).on('click', function(e) { if (!$(e.target).closest('.ub1_lx').length) $('.ub1_lx').parent().removeClass('menu_act'); });

	// касса: курьердің заказдарын ашу / жабу
	$('html').on('click', '.k_cr_tg', function() {
		var id = $(this).attr('data-id'), row = $('.k_cr_det[data-id="' + id + '"]');
		if (row.hasClass('k_cr_open') && row.hasClass('k_cr_changed')) { location.reload(); return; }
		row.toggleClass('k_cr_open');
		$(this).toggleClass('k_cr_tg_open');
	});

});
