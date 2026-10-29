// Тапсырыс карточкасы кассадағыдай: тип батырмалары, статус/курьер тізімі, статус тарихы, отказ терезесі
$(function () {
	function ph(p){ p = String(p || '').replace(/\D/g, ''); if (p.length == 11) p = p.substr(1); return p.length == 10 ? '8 (' + p.substr(0,3) + ') ' + p.substr(3,3) + '-' + p.substr(6,2) + '-' + p.substr(8,2) : p }
	function esc(t){ return $('<i>').text(t).html() }
	function item(o, staff){
		var n = $(o).attr('data-name'), c = $(o).attr('data-clr')
		if (n) return '<b>' + esc(n) + '</b><small>' + ph($(o).attr('data-phone')) + '</small>'
		var ic = $(o).attr('data-ico'); if (ic) return '<i class="far ' + esc(ic) + ' cr_ico"></i><b>' + esc($.trim($(o).text())) + '</b>'
		if (staff) return '<b class="cr_none">' + esc($(o).text()) + '</b>'
		return (c ? '<em class="cr_dot" style="background:' + esc(c) + '"></em>' : '') + '<b>' + esc($.trim($(o).text())) + '</b>'
	}
	// Ішінде бұрынғы select қалады, change сол арқылы жүреді (orders.js). Admin: құлып жоқ, кез келген статус
	function or_dd(scope){ $(scope).find('select.on_staff, select.on_type, select.on_status').each(function(){
		var sel = $(this)
		if (sel.hasClass('on_type')) {
			var tb = $('<div class="tp_btns"></div>'), dis = sel.prop('disabled')
			tb.append('<span class="tp_sl"></span>').css('--i', sel.prop('selectedIndex')).toggleClass('tp_off', !!dis)
			sel.find('option').each(function(i){ tb.append($('<div class="tp_b"></div>').attr({ 'data-i': i, title: $.trim($(this).text()) }).toggleClass('tp_act', i == sel.prop('selectedIndex')).toggleClass('tp_dis', !!dis).html('<i class="far ' + esc($(this).attr('data-ico') || 'fa-circle') + '"></i>')) })
			tb.on('click', '.tp_b', function(e){ e.stopPropagation(); if ($(this).hasClass('tp_dis')) return; var i = +$(this).attr('data-i'); if (i == sel.prop('selectedIndex')) return; tb.find('.tp_b').removeClass('tp_act'); $(this).addClass('tp_act'); tb.css('--i', i); sel.prop('selectedIndex', i).trigger('change') })
			sel.hide().after(tb); return
		}
		var staff = sel.hasClass('on_staff'), box = $('<div class="cr_dd"><div class="cr_btn"></div><div class="cr_list"></div></div>')
		if (sel.hasClass('on_status')) box.addClass('cr_st')
		if (sel.prop('disabled')) box.addClass('cr_dis')
		sel.find('option').each(function(i){ box.find('.cr_list').append($('<div class="cr_opt"></div>').attr('data-i', i).html(item(this, staff))) })
		function show(){ var o = sel.find('option:selected')[0]; box.find('.cr_btn').html('<span class="cr_v">' + (sel.hasClass('on_status') ? '<b>' + esc($.trim($(o).text())) + '</b>' : item(o, staff)) + '</span><i class="far fa-chevron-down"></i>'); box.find('.cr_opt').removeClass('cr_act').eq(sel.prop('selectedIndex')).addClass('cr_act') }
		show(); sel.hide().after(box)
		box.on('click', '.cr_btn', function(e){ e.stopPropagation(); if (box.hasClass('cr_dis')) return; $('.cr_dd').not(box).removeClass('cr_open'); box.toggleClass('cr_open') })
		box.on('click', '.cr_opt', function(e){ e.stopPropagation(); var i = +$(this).attr('data-i'); box.removeClass('cr_open'); if (i == sel.prop('selectedIndex')) return; sel.prop('selectedIndex', i).trigger('change'); show() })
	}) }
	or_dd($('.or_card'))
	$(document).on('click', function(){ $('.cr_dd').removeClass('cr_open') })

	// Тағамдар тізімі: 3-еуден көп болса ашу/жабу
	$('html').on('click', '.or_more', function () {
		var b = $(this), card = b.closest('.or_card').toggleClass('or_items_open')
		b.find('span').text(card.hasClass('or_items_open') ? b.attr('data-less') : b.attr('data-more'))
	})

	// Статус тарихы: оң жақ жоғарғы батырма, астынан шағын терезе
	var oh_cache = {}, oh_wait = {}
	function oh_get(id, cb){
		if (oh_wait[id]) { oh_wait[id].push(cb); return } oh_wait[id] = [cb]
		$.ajax({ url: "/orders/get.php?status_log", type: "POST", dataType: "html", data: ({ id: id }),
			success: function(html){ var ch = oh_cache[id] !== html; oh_cache[id] = html; $.each(oh_wait[id], function(k, f){ f && f(html, ch) }) },
			complete: function(){ delete oh_wait[id] } })
	}
	$('html').on('click', '.or_hist', function (e) {
		e.stopPropagation()
		var b = $(this), pop = $('.oh_pop')
		if (!pop.length) pop = $('<div class="oh_pop"></div>').appendTo('body')
		if (pop.hasClass('oh_open') && pop.data('id') == b.data('id')) { pop.removeClass('oh_open'); return }
		var r = this.getBoundingClientRect(), w = 280
		var left = Math.max(10, Math.min(r.right - w, window.innerWidth - w - 10))
		var id = b.data('id')
		pop.data('id', id).css({ top: r.bottom + window.scrollY + 8, left: left + window.scrollX, width: w }).html(oh_cache[id] || '<div class="oh_load">...</div>').addClass('oh_open')
		oh_get(id, function(html, changed){ if (pop.data('id') == id && changed) pop.html(html) })
	})
	$('html').on('mouseenter', '.or_hist', function () { var id = $(this).data('id'); if (!oh_cache[id]) oh_get(id) })
	$(document).on('click', function(e){ if (!$(e.target).closest('.oh_pop').length) $('.oh_pop').removeClass('oh_open') })
	$('html').on('click', '.oh_tab', function (e) {
		e.stopPropagation()
		var t = $(this).attr('data-t'), pop = $(this).closest('.oh_pop')
		pop.find('.oh_tab').removeClass('oh_tab_act'); $(this).addClass('oh_tab_act')
		pop.find('.oh_pane').addClass('dsp_n').filter('[data-p="' + t + '"]').removeClass('dsp_n')
	})
	$(window).on('scroll resize', function(){ $('.oh_pop').removeClass('oh_open') })

	// Статус сүзгісі: әр статустың түсті нүктесі (berik_admin.js тізімді жасағаннан кейін)
	setTimeout(function(){
		var s = $('select.on_sort_status'), w = s.closest('.bsel')
		if (!w.length) return
		s.find('option').each(function(i){
			var c = $(this).attr('data-clr'); if (!c) return
			w.find('.bsel_i[data-i="' + i + '"]').prepend($('<em class="cr_dot"></em>').css('background', c))
			if (this.selected) w.find('.bsel_txt').prepend($('<em class="cr_dot"></em>').css('background', c))
		})
	}, 0)
})


// Өзіміздің стильдегі сұрау терезесі (prompt орнына): or_ask({ kind: 'reason' | 'sum', title, text, value, quick, ok }, onOk, onCancel)
function or_ask(o, onOk, onCancel) {
	$('.oask_bg').remove()
	var sum = o.kind == 'sum', esc = function(t){ return $('<div>').text(t || '').html() }
	var bg = $('<div class="oask_bg"><div class="oask' + (sum ? ' oask_sum' : ' oask_ref') + '">'
		+ '<div class="oask_h"><span class="oask_ic"><i class="far ' + (sum ? 'fa-wallet' : 'fa-ban') + '"></i></span><div><b>' + esc(o.title) + '</b>' + (o.text ? '<small>' + esc(o.text) + '</small>' : '') + '</div><span class="oask_x"><i class="far fa-times"></i></span></div>'
		+ (sum ? '<div class="oask_in_w"><input type="tel" class="oask_in" inputmode="numeric" placeholder="0"><span>тг</span></div>'
		       : '<div class="oask_q"></div><textarea class="oask_in" rows="3" placeholder="Себебін жазыңыз..."></textarea>')
		+ '<div class="oask_err"></div><div class="oask_btns"><span class="oask_no">Бас тарту</span><span class="oask_ok">' + esc(o.ok || 'Растау') + '</span></div></div></div>').appendTo('body')
	var inp = bg.find('.oask_in'), fmt = function(v){ v = String(v || '').replace(/\D/g, ''); return v ? (+v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') : '' }
	if (sum) { inp.val(fmt(o.value)); inp.on('input', function(){ inp.val(fmt(inp.val())) }) }
	$.each(o.quick || [], function(i, q){ $('<span class="oask_qb"></span>').text(q).appendTo(bg.find('.oask_q')) })
	bg.on('click', '.oask_qb', function(){ bg.find('.oask_qb').removeClass('on'); $(this).addClass('on'); inp.val($(this).text()).focus(); bg.find('.oask_err').text('') })
	var done = false
	var close = function(ok){
		if (done) return
		var v = $.trim(inp.val())
		if (ok && !sum && !v) { bg.find('.oask_err').text('Себебін жазыңыз'); inp.focus(); return }
		done = true; bg.removeClass('oask_on'); setTimeout(function(){ bg.remove() }, 180)
		if (ok) onOk(sum ? v.replace(/\D/g, '') || '0' : v); else if (onCancel) onCancel()
	}
	bg.on('click', '.oask_ok', function(){ close(true) })
	bg.on('click', '.oask_no, .oask_x', function(){ close(false) })
	bg.on('mousedown', function(e){ if (e.target === bg[0]) close(false) })
	inp.on('keydown', function(e){ if (e.key == 'Enter' && (sum || !e.shiftKey)) { e.preventDefault(); close(true) } if (e.key == 'Escape') close(false) })
	setTimeout(function(){ bg.addClass('oask_on'); inp.focus(); if (sum) inp[0].select() }, 10)
}
