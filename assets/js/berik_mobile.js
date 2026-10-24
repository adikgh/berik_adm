// Berik admin: телефонға арналған төменгі мәзір (Telegram сияқты иконкалы батырмалар).
// Жоғарғы мәзірдің (.mp_topi) сілтемелерін көшіріп алады, сондықтан menu.php өзгерсе де өзі жаңарады.
$(function () {

	var W = window.bw || {};
	function w(k, d) { return W[k] || d; }

	var MAX = 3; // төменде «Тағы»-дан басқа қанша батырма тұрады (қалғаны «Тағы» ішінде)
	var links = $('.mp_topc .mp_topi');
	if (!links.length || $('.bm_nav').length) return;

	var nav = $('<nav class="bm_nav"><div class="bm_nav_c"></div></nav>');
	var box = nav.find('.bm_nav_c');

	// Компьютердегі мәзірдің ретімен: әр ашылмалы топ (.mp_dd: Аналитика, Тағамдар ...) телефонда бір батырма,
	// жеке сілтемелер (.mp_topi) өз орнында. Алғашқы MAX батырма төменде, қалғаны «Тағы» ішінде.
	var entries = [];
	var holder = $('.ub1_lx').parent();
	$('.mp_topc').children().each(function () {
		var el = $(this);
		if (el.hasClass('mp_dd')) {
			var sub = el.find('.mp_topi'), t = el.find('.mp_dd_t').first();
			if (!sub.length && !t.length) return;
			var ico = t.find('i').first().length ? t.find('i').first() : sub.first().find('i');
			entries.push({
				href: sub.first().attr('href') || el.find('.mp_dd_i').first().attr('href'),
				icon: ico.clone(),
				text: $.trim(t.find('span').first().text() || sub.first().text()),
				act: el.hasClass('mp_dd_act') || sub.filter('.mp_topi_act').length > 0,
				items: el.find('.mp_dd_c').children() // компьютердегі ашылмалы тізім: телефонда да осы ашылады
			});
		} else if (el.hasClass('mp_topi') && !el.hasClass('mp_dd_m')) {
			entries.push({ href: el.attr('href'), icon: el.find('i').clone(), text: $.trim(el.text()), act: el.hasClass('mp_topi_act') });
		}
	});

	// топ батырма (Аналитика, Тағамдар): басқанда компьютердегі ашылмалы тізім төменнен шығады
	function bindPop(btn, e) {
		if (!e.items || !e.items.filter('.mp_dd_i').length) return;
		var pop = $('<div class="bm_pop"><div class="bm_pop_h"></div><div class="bm_pop_c"></div></div>');
		pop.find('.bm_pop_h').text(e.text);
		e.items.each(function () {
			var x = $(this);
			if (x.hasClass('mp_dd_sep')) { pop.find('.bm_pop_c').append('<div class="bm_pop_sep"></div>'); return; }
			if (!x.hasClass('mp_dd_i')) return;
			pop.find('.bm_pop_c').append(
				$('<a class="bm_pop_i"></a>').attr('href', x.attr('href')).toggleClass('bm_pop_act', x.hasClass('mp_dd_i_act'))
					.append(x.find('i').first().clone()).append($('<span></span>').text($.trim(x.text())))
			);
		});
		// Қалдықтар мен Накладной Тағамдар тобына көшірілді (аккаунт тізімінен алынады)
		if (pop.find('.bm_pop_i[href^="/products/"]').length) {
			var moved = $('.acc_list .menu_ci[href^="/remains/"], .acc_list .menu_ci[href^="/waybill/"]');
			var added = false;
			moved.each(function () {
				var a = $(this), h = a.attr('href');
				a.addClass('bm_moved');
				if (pop.find('.bm_pop_i[href^="' + h + '"]').length) return;
				if (!added) { pop.find('.bm_pop_c').append('<div class="bm_pop_sep"></div>'); added = true; }
				pop.find('.bm_pop_c').append(
					$('<a class="bm_pop_i"></a>').attr('href', h).toggleClass('bm_pop_act', location.pathname.indexOf(h) === 0)
						.append(a.find('i').first().clone()).append($('<span></span>').text($.trim(a.find('.menu_cih').text())))
				);
			});
		}
		$('body').append(pop);
		btn.on('click', function (ev) {
			ev.preventDefault(); ev.stopPropagation();
			var on = !pop.hasClass('bm_pop_on');
			$('.bm_pop').removeClass('bm_pop_on'); holder.removeClass('menu_act');
			pop.toggleClass('bm_pop_on', on);
			$('body').toggleClass('bm_open', on);
		});
	}

	var anyAct = false;
	$.each(entries.slice(0, MAX), function (i, e) {
		var it = $('<a class="bm_i"></a>').attr('href', e.href).append(e.icon).append($('<span></span>').text(e.text));
		if (e.act) { it.addClass('bm_act'); anyAct = true; }
		bindPop(it, e);
		box.append(it);
	});
	var restEntries = entries.slice(MAX);

	// «Тағы»: артық сілтемелер + аккаунт мәзірі бір парақта
	var moreTxt = { kz: 'Тағы', ru: 'Ещё', en: 'More', uz: 'Yana', ky: 'Дагы' }[window.ui_lang] || 'Тағы';
	var more = $('<a class="bm_i bm_more" href="javascript:;"><i class="fal fa-bars"></i><span>' + w('More', moreTxt) + '</span></a>');
	box.append(more);
	// белсенді бет төменгі батырмалардың ішінде болмаса, «Тағы» белсенді болады
	if (!anyAct) more.addClass('bm_act');

	// «Тағы» ішінде: жеке сілтемелер бір торда, ал әр топтың (Тағамдар ...) ішкі пункттері
	// өз атауының астында бірден плитка болып тұрады (бөлек парақ ашылмайды)
	var plain = [], groups = [];
	$.each(restEntries, function (i, e) {
		var sub = e.items ? e.items.filter('.mp_dd_i') : $();
		if (!sub.length) { plain.push(e); return; }
		groups.push({ title: e.text, icon: e.icon, tiles: sub.map(function () {
			var x = $(this);
			return { href: x.attr('href'), icon: x.find('i').first().clone(), text: $.trim(x.text()), act: x.hasClass('mp_dd_i_act') };
		}).get() });
	});
	function makeGrid(list, title, icon) {
		var grid = $('<div class="bm_grid"></div>');
		if (title) grid.append($('<div class="bm_grid_h"></div>').append(icon.clone()).append($('<span></span>').text(title)));
		$.each(list, function (i, e) {
			grid.append($('<a class="bm_tile"></a>').attr('href', e.href).toggleClass('bm_tile_act', !!e.act).append(e.icon).append($('<span></span>').text(e.text)));
		});
		return grid;
	}
	var after = $('.acc_menu .acc_hd');
	if (plain.length) { var g0 = makeGrid(plain); after.after(g0); after = g0; }
	$.each(groups, function (i, gr) { var g = makeGrid(gr.tiles, gr.title, gr.icon); after.after(g); after = g; });

	// компания таңдау (Nevada ...) «Тағы» парағының ішінде: батырмалар, басқанда бұрынғы select-ке 'change' жіберіледі
	var csel = $('select.on_sort_company').first();
	if (csel.length) {
		var cbox = $('<div class="bm_comp"><div class="bm_comp_h"><i class="fal fa-building"></i> ' + w('Company', 'Компания') + '</div><div class="bm_comp_c"></div></div>');
		csel.find('option').each(function (i) {
			cbox.find('.bm_comp_c').append(
				$('<div class="bm_comp_i"></div>').text($(this).text()).attr('data-i', i).toggleClass('bm_comp_act', this.selected)
			);
		});
		cbox.on('click', '.bm_comp_i', function (e) {
			e.stopPropagation();
			var i = +$(this).attr('data-i');
			if (i == csel.prop('selectedIndex')) return;
			cbox.find('.bm_comp_i').removeClass('bm_comp_act'); $(this).addClass('bm_comp_act');
			csel.prop('selectedIndex', i).trigger('change');
		});
		$('.acc_menu .acc_hd').after(cbox);
	}

	$('body').append(nav).append('<div class="bm_back"></div>');
	$('body').addClass('bm_has_nav');


	// парақты ашу / жабу (аккаунт мәзірінің өз класы: .aheader_r.menu_act)
	function open(on) { $('.bm_pop').removeClass('bm_pop_on'); holder.toggleClass('menu_act', on); sync(); }
	function sync() { $('body').toggleClass('bm_open', (holder.hasClass('menu_act') || $('.bm_pop_on').length > 0) && $(window).width() <= 768); }

	more.on('click', function (e) { e.preventDefault(); e.stopPropagation(); open(!holder.hasClass('menu_act')); });
	$('.bm_back').on('click', function () { open(false); });
	$(document).on('click', function (ev) { if (!$(ev.target).closest('.bm_pop').length) $('.bm_pop').removeClass('bm_pop_on'); });
	$(document).on('click', function () { setTimeout(sync, 0); });
	$(document).on('keydown', function (e) { if (e.key == 'Escape') open(false); });
	$(window).on('resize', sync);
});
