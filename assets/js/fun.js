// Хабарлама кассадағыдай (2026-11-03)
// Хабарлама (toast): оң жақ жоғарыдан жылжып шығады, × жабады, 4 сек кейін өзі кетеді, тышқан үстінде тұрса тоқтайды
// Түрі мәтінге қарай: сәтті (жасыл), қате (қызыл), қалғаны ескерту (сары). Қажет болса: mess('...', 'ok' | 'err' | 'warn')
function mess(e, type) {
   var txt = $('<div>').html(e).text()
   if (!type) type = /сәтті|cәтті|сақтал|көшірілді|успешно/i.test(txt) ? 'ok' : (/қате|ошибка|error|байланыс/i.test(txt) ? 'err' : 'warn')
   var ico = { ok: 'fa-check-circle', err: 'fa-times-circle', warn: 'fa-exclamation-triangle' }[type]
   var box = $('.tst_box'); if (!box.length) box = $('<div class="tst_box"></div>').appendTo('body')
   box.find('.tst').filter(function(){ return $(this).data('t') === txt }).remove() // бірдей хабар қайталанбайды
   var t = $('<div class="tst tst_' + type + '"><i class="far ' + ico + ' tst_i"></i><div class="tst_t"></div><div class="tst_x"><i class="far fa-times"></i></div><span class="tst_bar"></span></div>')
   t.data('t', txt).find('.tst_t').html(e)
   box.append(t); if (box.children().length > 3) box.children().first().remove()
   var left = 4000, start = Date.now(), timer
   function go(){ start = Date.now(); t.removeClass('tst_pause'); timer = setTimeout(close, left) }
   function close(){ clearTimeout(timer); t.addClass('tst_out'); setTimeout(function(){ t.remove() }, 250) }
   t.on('mouseenter', function(){ clearTimeout(timer); left -= Date.now() - start; t.addClass('tst_pause') }).on('mouseleave', go)
   t.on('click', '.tst_x', close)
   requestAnimationFrame(function(){ t.addClass('tst_in') }); go()
}









// 
function copytext(e) {
	var $tmp = $("<input>");
	$("body").append($tmp);
	$tmp.val(e).select();
	document.execCommand("copy");
	$tmp.remove();
   mess('Cәтті көшірілді!');
}


// 
function clcBack(e) {
	history.back();
}
function clcPP(e) {
	window.history.forward();
}
function clcP2(e) {
	history.go();
}

// Растау терезесі (браузердің confirm орнына): ask('Сұрақ', function(){ ... }, {ok: 'Иә', no: 'Жоқ', danger: false})
function ask(q, yes, o) {
   o = o || {}
   $('.ask_bg').remove()
   var bg = $('<div class="ask_bg"><div class="ask"><div class="ask_i"><i class="far ' + (o.danger ? 'fa-undo' : 'fa-check-circle') + '"></i></div><div class="ask_q"></div><div class="ask_b"><span class="ask_no" tabindex="0"></span><span class="ask_ok" tabindex="0"></span></div></div></div>')
   if (o.danger) bg.addClass('ask_dg')
   bg.find('.ask_q').html(q)
   bg.find('.ask_no').text(o.no || 'Жоқ')
   bg.find('.ask_ok').text(o.ok || 'Иә')
   $('body').append(bg)
   requestAnimationFrame(function(){ bg.addClass('ask_on') })
   function close(){ bg.removeClass('ask_on'); $(document).off('keydown.ask'); setTimeout(function(){ bg.remove() }, 200) }
   bg.on('click', function(e){ if (e.target === this) close() })
   bg.on('click', '.ask_no', close)
   bg.on('click', '.ask_ok', function(){ close(); if (yes) yes() })
   $(document).on('keydown.ask', function(e){ if (e.key === 'Escape') close(); if (e.key === 'Enter') { e.preventDefault(); bg.find('.ask_ok').click() } })
   setTimeout(function(){ bg.find('.ask_ok').focus() }, 30)
}


// ===== Бетті жаңартпай жаңарту (AJAX), 2026-11-04 =====
// ajx('.tm_c') — қазіргі бетті фонда қайта алып, тек көрсетілген блоктарды ауыстырады (бет жаңармайды, scroll орнында қалады)
// ajx('.a, .b', function(doc){ ... }, url) — url берілсе сол адрестен алады, doc — жаңа беттің мазмұны
function ajx(sels, done, url) {
   return $.ajax({ url: url || location.href, type: 'GET', dataType: 'html', headers: { 'X-Ajx': '1' } })
      .done(function (html) { ajx.html = html; var doc = ajxDoc(html); ajxPut(doc, sels); if (done) done(doc) })
      .fail(function () { mess('Байланыс қатесі, қайта көріңіз', 'err') })
}
function ajxDoc(html) { return $('<div>').append($.parseHTML(html)) }
// жаңа блоктарды орнына қою
function ajxPut(doc, sels) {
   $.each((sels || '.app').split(','), function (_, s) {
      s = $.trim(s); if (!s) return
      var nw = doc.find(s)
      $(s).each(function (i) { if (nw[i]) { $(this).replaceWith(nw[i]); ajxInit(nw[i]) } })
   })
   $(document).trigger('ajx:done', [doc])
}
// жаңа блоктағы суреттер мен маскалар
function ajxInit(el) {
   var e = $(el)
   function q(c) { return e.find(c).addBack(c) }
   if ($.fn.lazy) q('.lazy_img').lazy({ effect: 'fadeIn', effectTime: 300, threshold: 0 })
   if (window.nameEd) q('.nm_ed').each(function () { nameEd.init(this) }) // тағам атауының редакторы
   if ($.fn.mask) {
      q('.fr_phone').mask('8 (700) 000-00-00'); q('.fr_code').mask('0000')
      q('.fr_price').mask('# ##0 тг', { reverse: true }); q('.fr_number').mask('# ##0', { reverse: true })
      q('.fr_number2').mask('#0', { reverse: true }); q('.fr_number3').mask('# ##0 шт', { reverse: true })
   }
}
// адресті өзгертіп блоктарды жүктеу (сүзгі, кезең ауыстыру): артқа батырмасы да жұмыс істейді
function ajxGo(url, sels) {
   if (!history.state || !history.state.ajx) history.replaceState({ ajx: sels }, '', location.href)
   history.pushState({ ajx: sels }, '', url)
   return ajx(sels, null, url)
}
// бет блогын жаңарту: элемент data-ajx-nav ішінде тұрса сол блок AJAX-пен жаңарады, болмаса бет жаңарады
function ajxArea(el) { return $(el).closest('[data-ajx-nav]').attr('data-ajx-nav') || $('[data-ajx-nav]').first().attr('data-ajx-nav') }
function ajxReload(el, done) { var a = ajxArea(el); if (a) return ajx(a, done); location.reload() }
function ajxUrl(el, url) { var a = ajxArea(el); if (a) return ajxGo(String(url), a); location.href = url }
window.addEventListener('popstate', function (e) { if (e.state && e.state.ajx) ajx(e.state.ajx) })
// <a data-ajx=".блок"> немесе <div data-ajx-nav=".блок"> ішіндегі сілтемелер бетті жаңартпай ашылады
$(document).on('click', 'a[data-ajx], [data-ajx-nav] a[href]', function (e) {
   if (e.ctrlKey || e.metaKey || e.shiftKey || this.target == '_blank') return
   var sels = $(this).attr('data-ajx') || $(this).closest('[data-ajx-nav]').attr('data-ajx-nav')
   var h = $(this).attr('href'); if (!h || h[0] == '#' || h.indexOf('javascript') == 0) return
   if (this.pathname != location.pathname || this.host != location.host) return // басқа бетке сілтеме — әдеттегідей ашылады
   e.preventDefault(); ajxGo(this.href, sels)
})
// <form data-ajx=".блок"> бет жаңармай жіберіледі: GET — сүзгі (адрес өзгереді), POST — сақтайды да блокты жаңартады
// data-ajx-msg=".lc_msg" — жауаптағы хабарды toast қылып көрсетеді. onchange үшін this.form.requestSubmit() жазыңыз
// батырмада data-ask="Сұрақ" болса, алдымен өз тереземіз сұрайды (confirm орнына)
$(document).on('submit', 'form[data-ajx]', function (e) {
   e.preventDefault()
   var f = this, sels = $(f).attr('data-ajx'), act = $(f).attr('action') || location.pathname + location.search
   var sb = e.originalEvent && e.originalEvent.submitter, q = sb && $(sb).attr('data-ask')
   if (q && !sb._ok) return ask(q, function () { sb._ok = 1; f.requestSubmit(sb) }, { ok: $.trim($(sb).text()) || 'Иә', danger: $(sb).is('[data-ask-danger]') })
   if (sb) sb._ok = 0
   if ((f.getAttribute('method') || 'get').toLowerCase() != 'post') return ajxGo(act.split('?')[0] + '?' + $(f).serialize(), sels)
   var fd = new FormData(f); if (sb && sb.name) fd.append(sb.name, sb.value || '')
   var btn = $(f).find('[type=submit], button:not([type])').prop('disabled', true), xr
   $.ajax({ url: act, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'html', headers: { 'X-Ajx': '1' },
      xhr: function () { xr = new XMLHttpRequest(); return xr } })
      .done(function (html) {
         ajx.html = html
         var doc = ajxDoc(html), ms = $(f).attr('data-ajx-msg'), t = ms ? $.trim(doc.find(ms).first().text()) : ''
         if (ms) doc.find(ms).remove()
         // сервер басқа адреске жіберсе (мысалы жаңа ревизия ?id=5), адрес жолы да соған ауысады
         var u = xr && xr.responseURL
         if (u && u.split('#')[0] != location.href.split('#')[0]) { if (!history.state || !history.state.ajx) history.replaceState({ ajx: sels }, '', location.href); history.pushState({ ajx: sels }, '', u) }
         ajxPut(doc, sels); mess(t || 'Сақталды')
      })
      .fail(function () { mess('Байланыс қатесі, қайта көріңіз', 'err') })
      .always(function () { btn.prop('disabled', false) })
})
