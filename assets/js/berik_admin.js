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

	$('input.on_sort_time').each(function() {
		var inp = $(this);
		if (inp.closest('.bdt').length) return;
		// құрылым: [←] [күнтізбе белгісі + мәтін + жасырын input] [→] [Бүгін]
		var box = $('<div class="bdt"></div>');
		var main = $('<label class="bdt_main"><i class="fal fa-calendar-alt"></i><span class="bdt_txt"></span></label>');
		inp.before(box);
		box.append('<div class="bdt_arr bdt_prev" title="' + w('Previous day', 'Алдыңғы күн') + '"><i class="far fa-angle-left"></i></div>');
		box.append(main);
		main.append(inp);
		box.append('<div class="bdt_arr bdt_next" title="' + w('Next day', 'Келесі күн') + '"><i class="far fa-angle-right"></i></div>');
		var today = workToday();
		if (inp.val() != today) box.append('<div class="bdt_today">' + w('Today', 'Бүгін') + '</div>');
		else box.addClass('bdt_is_today');
		main.find('.bdt_txt').text((inp.val() == today ? w('Today', 'Бүгін') + ' · ' : '') + nice(inp.val()));
		main.on('click', function(e) {
			try { if (inp[0].showPicker) { e.preventDefault(); inp[0].showPicker(); } } catch (err) {}
		});
		function go(v) { inp.val(v).trigger('change'); }
		box.on('click', '.bdt_prev, .bdt_next', function() {
			var d = parse(inp.val()) || new Date();
			d.setDate(d.getDate() + ($(this).hasClass('bdt_next') ? 1 : -1));
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
			$('.bsel').removeClass('bsel_open');
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
