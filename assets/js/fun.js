// Хабарлама кассадағыдай (2026-11-03)
// Хабарлама (toast): оң жақ жоғарыдан жылжып шығады, × жабады, 4 сек кейін өзі кетеді, тышқан үстінде тұрса тоқтайды
// Түрі мәтінге қарай: сәтті (жасыл), қате (қызыл), қалғаны ескерту (сары). Қажет болса: mess('...', 'ok' | 'err' | 'warn')
function mess(e, type) {
   var txt = $('<div>').html(e).text()
   // «Сақталуда..», «Отправка..», «Фото жүктелуде..» — күту хабары: келесі хабар (нәтиже) шыққанда өзі жоғалады, екеуі қатар тұрмайды
   if (!type && /(\.\.|…)\s*$/.test(txt)) type = 'wait'
   if (!type) type = /сәтті|cәтті|сақтал|көшірілді|успешно/i.test(txt) ? 'ok' : (/қате|ошибка|error|байланыс/i.test(txt) ? 'err' : 'warn')
   var ico = { ok: 'fa-check-circle', err: 'fa-times-circle', warn: 'fa-exclamation-triangle', wait: 'fa-spinner fa-spin' }[type]
   var box = $('.tst_box'); if (!box.length) box = $('<div class="tst_box"></div>').appendTo('body')
   box.find('.tst_wait').remove()
   box.find('.tst').filter(function(){ return $(this).data('t') === txt }).remove() // бірдей хабар қайталанбайды
   var t = $('<div class="tst tst_' + type + '"><i class="far ' + ico + ' tst_i"></i><div class="tst_t"></div><div class="tst_x"><i class="far fa-times"></i></div><span class="tst_bar"></span></div>')
   t.data('t', txt).find('.tst_t').html(e)
   box.append(t); if (box.children().length > 3) box.children().first().remove()
   var left = type == 'wait' ? 15000 : 4000, start = Date.now(), timer
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
   mess('Көшірілді');
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
   // o.input берілсе — мән енгізетін өріс (браузердің prompt() орнына): yes(мән) шақырылады
   var inp = null
   if (o.input !== undefined) {
      if (!document.getElementById('ask_in_css')) { var cs = document.createElement('style'); cs.id = 'ask_in_css'; cs.textContent = '.ask_in{display:block;width:100%;box-sizing:border-box;margin:-6px 0 16px;padding:11px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:16px;text-align:center;outline:none;font-family:inherit}.ask_in:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.2)}'; document.head.appendChild(cs) }
      inp = $('<input class="ask_in" autocomplete="off">').attr({ type: o.type || 'text', inputmode: o.mode || null, placeholder: o.ph || '' }).val(o.input == null ? '' : o.input)
      bg.find('.ask_q').after(inp)
   }
   bg.find('.ask_no').text(o.no || 'Жоқ')
   bg.find('.ask_ok').text(o.ok || 'Иә')
   $('body').append(bg)
   requestAnimationFrame(function(){ bg.addClass('ask_on') })
   function close(){ bg.removeClass('ask_on'); $(document).off('keydown.ask'); setTimeout(function(){ bg.remove() }, 200) }
   bg.on('click', function(e){ if (e.target === this) close() })
   bg.on('click', '.ask_no', close)
   bg.on('click', '.ask_ok', function(){ close(); if (yes) yes(inp ? inp.val() : undefined) })
   $(document).on('keydown.ask', function(e){ if (e.key === 'Escape') close(); if (e.key === 'Enter') { e.preventDefault(); bg.find('.ask_ok').click() } })
   setTimeout(function(){ if (inp) inp.focus().select(); else bg.find('.ask_ok').focus() }, 30)
}
// Мән сұрау (prompt() орнына): askIn('Саны', 1, function (v) { ... }, { ok: 'Сақтау', mode: 'decimal' })
function askIn(q, val, yes, o) {
   o = $.extend({ ok: 'Сақтау', no: 'Болдырмау' }, o || {}); o.input = val == null ? '' : val
   ask(q, yes, o)
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


// ===== ЖИ күту экраны (2026-11-19) =====
// ЖИ (Claude) сұрауы кеткенде бүкіл бетті жабатын «күтіңіз» терезесі шығады: басқа батырмалар басылмайды, бетті жауып/ауысып кетсе ескертеді.
// Өздігінен іске қосылады: fetch пен $.ajax адресі AI_URL-ге сәйкес келсе. Кезекпен жүретін ЖИ (бірінен соң бірі) кезінде экран жабылмай тұрады.
// Қолмен: aiWait.on('мәтін') / aiWait.off() / aiWait.msg('барысы'). Барысы беттегі прогресс жазуынан (AI_PROG) өзі алынады.
;(function () {
   var AI_URL = /\/waybill\/ai\.php(?!\?ping)|\/(set|kind)_ai\.php|\/import\/get\.php\?(read|add|fill_one|fill_set)|\/tpl\/get\.php\?(semi_ai|dish_ai|prices|all|semi_all)|get\.php\?(ai_suggest|semi_suggest)/
   var AI_PROG = '.mi_stat, .gt_prog, .gt_sprog, .wbi_drop_wait b, .prp_st.load, .gt_mp, [data-ai-prog]'
   var n = 0, man = 0, box = null, t0 = 0, tick = 0, hideTm = 0, lastBtn = null, txt = ''
   function lbl(u) {
      u = String(u || '')
      if (/waybill/.test(u)) return 'ЖИ накладнойды оқып жатыр…'
      if (/import\/get\.php\?read/.test(u)) return 'ЖИ мәзірді оқып жатыр…'
      if (/import/.test(u)) return 'ЖИ мәзірді толтырып жатыр…'
      if (/kind_ai/.test(u)) return 'ЖИ тағам түрін анықтап жатыр…'
      if (/set_ai/.test(u)) return 'ЖИ сет құрамын жазып жатыр…'
      if (/prices/.test(u)) return 'Шикізат бағасы анықталуда…'
      if (/\?(semi_all|all)\b/.test(u)) return 'Шаблоннан толтырылуда…'
      return 'ЖИ граммовка құрастырып жатыр…'
   }
   function css() {
      if (document.getElementById('aiw_css')) return
      var s = document.createElement('style'); s.id = 'aiw_css'
      s.textContent = '.aiw{position:fixed;inset:0;z-index:99990;background:rgba(17,24,39,.45);display:flex;align-items:center;justify-content:center;padding:16px;cursor:progress;opacity:0;transition:opacity .2s}' +
         '.aiw_on{opacity:1}.aiw_b{background:#fff;border-radius:16px;padding:24px 22px 18px;max-width:380px;width:100%;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.25);font-family:inherit;color:#111827}' +
         '.aiw_sp{width:46px;height:46px;margin:0 auto 14px;border-radius:50%;border:4px solid #e5e7eb;border-top-color:#16a34a;animation:aiw_r 0.9s linear infinite}' +
         '@keyframes aiw_r{to{transform:rotate(360deg)}}.aiw_t{font-size:16px;font-weight:600;line-height:1.35}.aiw_p{font-size:13px;color:#374151;margin-top:8px;line-height:1.4;word-break:break-word}.aiw_p:empty{display:none}' +
         '.aiw_s{font-size:13px;color:#6b7280;margin-top:10px}.aiw_h{font-size:12px;color:#9ca3af;margin-top:6px}' +
         '.tst_box{z-index:99995!important}.ask_bg{z-index:99996!important}'
      document.head.appendChild(s)
   }
   function prog() {
      var el = null
      document.querySelectorAll(AI_PROG).forEach(function (e) { if (!el && e.offsetParent !== null && e.textContent.trim()) el = e })
      var t = el ? el.textContent.trim() : ''
      if (!t && lastBtn && document.contains(lastBtn)) { t = lastBtn.textContent.trim(); if (t.length > 80) t = '' }
      return txt || t
   }
   function upd() {
      if (!box) return
      var s = Math.round((Date.now() - t0) / 1000), p = prog()
      box.querySelector('.aiw_p').textContent = p && p != box.querySelector('.aiw_t').textContent ? p : ''
      box.querySelector('.aiw_s').textContent = (s >= 60 ? Math.floor(s / 60) + ' мин ' : '') + (s % 60) + ' сек'
   }
   function show(t) {
      clearTimeout(hideTm)
      if (box) { if (t) box.querySelector('.aiw_t').textContent = t; return }
      css(); t0 = Date.now()
      if (document.activeElement && document.activeElement.blur) document.activeElement.blur()
      box = document.createElement('div'); box.className = 'aiw'
      box.innerHTML = '<div class="aiw_b"><div class="aiw_sp"></div><div class="aiw_t"></div><div class="aiw_p"></div><div class="aiw_s">0 сек</div><div class="aiw_h">Күте тұрыңыз, бетті жаппаңыз. Әдетте 10–90 сек.</div></div>'
      box.querySelector('.aiw_t').textContent = t || 'ЖИ жұмыс істеп жатыр…'
      document.body.appendChild(box)
      requestAnimationFrame(function () { box && box.classList.add('aiw_on') })
      tick = setInterval(upd, 500); upd()
   }
   function hide() {
      clearTimeout(hideTm)
      // кезекпен жүретін сұраулар арасында экран жыпылықтамасын
      hideTm = setTimeout(function () {
         if (n > 0 || man > 0 || !box) return
         clearInterval(tick); box.remove(); box = null; txt = ''
      }, 700)
   }
   function start(u) { n++; show(lbl(u)) }
   function end() { n = Math.max(0, n - 1); if (!n) hide() }
   window.aiWait = {
      on: function (t) { man++; show(t) }, off: function () { man = Math.max(0, man - 1); if (!man && !n) hide() },
      msg: function (t) { txt = t || ''; upd() }, busy: function () { return n + man > 0 }, url: AI_URL
   }
   // ЖИ жүріп жатқанда: пернетақта (Enter/Space) мен басулар өтпейді, бетті жапса ескертеді
   document.addEventListener('click', function (e) {
      if (box && !(e.target.closest && e.target.closest('.ask_bg'))) { e.preventDefault(); e.stopPropagation(); return }
      lastBtn = e.target.closest && e.target.closest('button, .btn, a, [class*="_b"], [class*="_ai"]')
   }, true)
   document.addEventListener('keydown', function (e) { if (box && e.key != 'Tab' && !document.querySelector('.ask_bg')) { e.preventDefault(); e.stopPropagation() } }, true)
   window.addEventListener('beforeunload', function (e) { if (box) { e.preventDefault(); e.returnValue = 'ЖИ әлі жұмыс істеп жатыр'; return e.returnValue } })
   // fetch
   if (window.fetch) {
      var of = window.fetch
      window.fetch = function (u) {
         var s = typeof u == 'string' ? u : (u && u.url) || ''
         var o = arguments[1], dry = o && o.body && o.body.get && o.body.get('dry') // алдын ала тексеру (dry) — тез, экран керек емес
         if (!AI_URL.test(s) || dry) return of.apply(this, arguments)
         start(s)
         return of.apply(this, arguments).then(function (r) { end(); return r }, function (e) { end(); throw e })
      }
   }
   // jQuery $.ajax
   if (window.jQuery) {
      jQuery(document).ajaxSend(function (e, x, o) { if (AI_URL.test(o.url || '')) { x._aiw = 1; start(o.url) } })
      jQuery(document).ajaxComplete(function (e, x) { if (x._aiw) { x._aiw = 0; end() } })
   }
})()


/* ₸ белгісі негізгі саннан кіші (75%): мәтіндегі ₸ <span class="tg_sym">-ге оралады (2026-11-20, user) */
;(function(){
	var SKIP = /^(SCRIPT|STYLE|TEXTAREA|SELECT|OPTION|INPUT|NOSCRIPT|TITLE)$/;
	var TG = /\d\s?тг(?![а-яёәіңғүұқөһ])/i, TG_ALL = /(\d)\s?тг(?![а-яёәіңғүұқөһ])/gi;
	function wrap(root){
		if (!root || !document.createTreeWalker) return;
		var w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false), list = [], n;
		while ((n = w.nextNode())) {
			var p = n.parentNode;
			if (TG.test(n.nodeValue) && p && !SKIP.test(p.nodeName)) n.nodeValue = n.nodeValue.replace(TG_ALL, '$1 ₸');   /* «1500 тг» → «1500 ₸» (бір белгі) */
			if (n.nodeValue.indexOf('₸') < 0) continue;
			if (!p || SKIP.test(p.nodeName) || p.isContentEditable || (p.classList && p.classList.contains('tg_sym'))) continue;
			list.push(n);
		}
		list.forEach(function(t){
			var parts = t.nodeValue.split('₸'), f = document.createDocumentFragment();
			parts.forEach(function(s, i){
				if (i < parts.length - 1) s = s.replace(/[  ]+$/, '');   /* ₸ алдындағы бос орын алынады, аралықты .tg_sym margin береді */
				if (s) f.appendChild(document.createTextNode(s));
				if (i < parts.length - 1){ var sp = document.createElement('span'); sp.className = 'tg_sym'; sp.textContent = '₸'; f.appendChild(sp); }
			});
			t.parentNode.replaceChild(f, t);
		});
	}
	function start(){
		wrap(document.body);
		if (window.MutationObserver) new MutationObserver(function(ms){
			ms.forEach(function(m){
				if (m.type === 'characterData') { if (m.target.parentNode) wrap(m.target.parentNode); return; }
				m.addedNodes.forEach(function(a){ if (a.nodeType === 1) wrap(a); else if (a.nodeType === 3 && a.parentNode) wrap(a.parentNode); });
			});
		}).observe(document.body, { childList: true, subtree: true, characterData: true });
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
