// Berik admin: телефонға арналған төменгі мәзір (Telegram сияқты иконкалы батырмалар).
// Жоғарғы мәзірдің (.mp_topi) сілтемелерін көшіріп алады, сондықтан menu.php өзгерсе де өзі жаңарады.
$(function () {

	var W = window.bw || {};
	function w(k, d) { return W[k] || d; }

	var MAX = 4; // төменде «Тағы»-дан басқа қанша батырма тұрады (қалғаны «Тағы» ішінде)
	var links = $('.mp_topc .mp_topi');
	if (!links.length || $('.bm_nav').length) return;

	var nav = $('<nav class="bm_nav"><div class="bm_nav_c"></div></nav>');
	var box = nav.find('.bm_nav_c');
	var main = links.slice(0, MAX);
	var rest = links.slice(main.length);

	main.each(function () { box.append(item($(this))); });

	// «Тағы»: артық сілтемелер + аккаунт мәзірі бір парақта
	var moreTxt = { kz: 'Тағы', ru: 'Ещё', en: 'More', uz: 'Yana', ky: 'Дагы' }[window.ui_lang] || 'Тағы';
	var more = $('<a class="bm_i bm_more" href="javascript:;"><i class="fal fa-bars"></i><span>' + w('More', moreTxt) + '</span></a>');
	box.append(more);
	// белсенді бет төменгі батырмалардың ішінде болмаса, «Тағы» белсенді болады
	if (!main.filter('.mp_topi_act').length) more.addClass('bm_act');

	if (rest.length) {
		var grid = $('<div class="bm_grid"></div>');
		rest.each(function () {
			var a = $(this);
			grid.append(
				$('<a class="bm_tile"></a>').attr('href', a.attr('href'))
					.toggleClass('bm_tile_act', a.hasClass('mp_topi_act'))
					.append(a.find('i').clone())
					.append($('<span></span>').text($.trim(a.text())))
			);
		});
		$('.acc_menu .acc_hd').after(grid);
	}

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

	function item(a) {
		var it = $('<a class="bm_i"></a>').attr('href', a.attr('href'));
		it.append(a.find('i').clone());
		it.append($('<span></span>').text($.trim(a.text())));
		if (a.hasClass('mp_topi_act')) it.addClass('bm_act');
		return it;
	}

	// парақты ашу / жабу (аккаунт мәзірінің өз класы: .aheader_r.menu_act)
	var holder = $('.ub1_lx').parent();
	function open(on) { holder.toggleClass('menu_act', on); sync(); }
	function sync() { $('body').toggleClass('bm_open', holder.hasClass('menu_act') && $(window).width() <= 768); }

	more.on('click', function (e) { e.preventDefault(); e.stopPropagation(); open(!holder.hasClass('menu_act')); });
	$('.bm_back').on('click', function () { open(false); });
	$(document).on('click', function () { setTimeout(sync, 0); });
	$(document).on('keydown', function (e) { if (e.key == 'Escape') open(false); });
	$(window).on('resize', sync);
});
