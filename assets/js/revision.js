// Ревизия
(function ($) {
	var say = function (t) { if (typeof mess == 'function') mess(t); else alert(t); };
	var num = function (v) { v = String(v || '').replace(/\s+/g, '').replace(',', '.'); var n = parseFloat(v); return isNaN(n) ? 0 : n; };
	var r3 = function (n) { return Math.round(n * 1000) / 1000; };
	var qty = function (n) { n = r3(n); return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); };
	var money = function (n) {
		n = Math.round(n * 100) / 100;
		var s = (Math.floor(n) == n ? n.toFixed(0) : n.toFixed(2));
		return s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₸';
	};

	var $doc = $('.rv_doc');
	if (!$doc.length) return;
	var posted = $doc.data('posted') == 1;

	function rowCalc($r) {
		var $i = $r.find('.rv_r_fact');
		var v = $.trim($i.val());
		$r.toggleClass('rv_done', v !== '');
		if (!$i.length) return;
		if (v === '') {
			$r.find('.rv_diff, .rv_used, .rv_diff_s').text('—');
			$r.removeClass('rv_has_diff');
			return;
		}
		var f = num(v), db = num($r.data('db')), p = num($r.data('price'));
		var diff = r3(f - db), used = r3(num($r.data('start')) + num($r.data('in')) - f);
		$r.find('.rv_diff').html($('<b>').addClass(diff < 0 ? 'wb_c_red' : (diff > 0 ? 'wb_c_green' : '')).text((diff > 0 ? '+' : '') + qty(diff)));
		$r.find('.rv_used').text(qty(used));
		$r.find('.rv_diff_s').text(p ? money(diff * p) : '—');
		$r.toggleClass('rv_has_diff', diff != 0);
	}

	function total() {
		var cnt = 0, used = 0, minus = 0, plus = 0, mn = 0, pn = 0;
		$doc.find('.rv_row').each(function () {
			var $r = $(this), $i = $r.find('.rv_r_fact');
			if (!$i.length) return;
			var v = $.trim($i.val());
			if (v === '') return;
			var f = num(v), db = num($r.data('db')), p = num($r.data('price'));
			var diff = f - db;
			cnt++;
			used += (num($r.data('start')) + num($r.data('in')) - f) * p;
			if (diff < 0) { minus += diff * p; mn++; } else if (diff > 0) { plus += diff * p; pn++; }
		});
		if (posted) return;
		$doc.find('.rv_cnt').text(cnt);
		$doc.find('.rv_used_v').text(money(used));
		$doc.find('.rv_minus_v').text(money(minus));
		$doc.find('.rv_plus_v').text(money(plus));
		$doc.find('.rv_minus_n').text(mn);
		$doc.find('.rv_plus_n').text(pn);
	}

	$doc.find('.rv_row').each(function () { rowCalc($(this)); });
	if (posted) $doc.find('.rv_row').each(function () {
		var t = $.trim($(this).find('.rv_diff').text());
		$(this).addClass('rv_done').toggleClass('rv_has_diff', t !== '0' && t !== '—');
	});
	total();
	if (posted) {
		var mn = 0, pn = 0;
		$doc.find('.rv_diff b').each(function () { if ($(this).hasClass('wb_c_red')) mn++; else if ($(this).hasClass('wb_c_green')) pn++; });
		$doc.find('.rv_minus_n').text(mn); $doc.find('.rv_plus_n').text(pn);
	}

	var dirty = false;
	$doc.on('input', '.rv_r_fact', function () { dirty = true; rowCalc($(this).closest('.rv_row')); total(); });
	// Enter басқанда келесі жолға өту
	$doc.on('keydown', '.rv_r_fact', function (e) {
		if (e.key !== 'Enter') return;
		e.preventDefault();
		var $n = $(this).closest('.rv_row').nextAll('.rv_row:visible').first().find('.rv_r_fact');
		if ($n.length) $n.focus().select();
	});
	$(window).on('beforeunload', function () { if (dirty) return 'Сақталмаған өзгеріс бар'; });

	// сүзгі мен іздеу
	var flt = '';
	function filter() {
		var q = $.trim($doc.find('.rv_search').val()).toLowerCase();
		$doc.find('.rv_row').each(function () {
			var $r = $(this), ok = true;
			if (q && String($r.data('name')).indexOf(q) < 0) ok = false;
			if (flt == 'todo' && $r.hasClass('rv_done')) ok = false;
			if (flt == 'done' && !$r.hasClass('rv_done')) ok = false;
			if (flt == 'diff' && !$r.hasClass('rv_has_diff')) ok = false;
			$r.toggle(ok);
		});
	}
	$doc.on('input', '.rv_search', filter);
	$doc.on('click', '.rv_flt .wb_tab', function () {
		$(this).addClass('wb_tab_act').siblings().removeClass('wb_tab_act');
		flt = $(this).data('f'); filter();
	});

	$doc.on('click', '.rv_save', function () {
		var post = $(this).data('post');
		var items = [];
		$doc.find('.rv_row').each(function () {
			var v = $.trim($(this).find('.rv_r_fact').val());
			if (v !== '') items.push({ raw_id: $(this).data('raw'), fact: v });
		});
		if (post == 1) {
			if (!items.length) return say('Кемінде бір шикізатты санаңыз');
			if (!confirm('Ревизияны аяқтаймыз ба? Саналған ' + items.length + ' шикізаттың базадағы қалдығы нақты санға теңеседі.')) return;
		}
		$('.rv_save').addClass('wb_btn_wait');
		$.post('/revision/get.php?save', {
			id: $doc.data('id'),
			rev_date: $doc.find('.rv_f_date').val(),
			comment: $doc.find('.rv_f_comment').val(),
			items: JSON.stringify(items),
			post: post
		}, function (r) {
			$('.rv_save').removeClass('wb_btn_wait');
			say(r.msg || (r.ok ? 'Сақталды' : 'Қате шықты'));
			if (r.ok) dirty = false;
			if (r.id) setTimeout(function () { location.href = '/revision/item.php?id=' + r.id; }, 600);
		}, 'json').fail(function () { $('.rv_save').removeClass('wb_btn_wait'); say('Байланыс қатесі, қайта көріңіз'); });
	});

	$doc.on('click', '.rv_unpost', function () {
		if (!confirm('Аяқтауды болдырмаймыз ба? Базадағы қалдық ревизияға дейінгі күйіне қайтады.')) return;
		$.post('/revision/get.php?unpost', { id: $doc.data('id') }, function (r) {
			say(r.msg);
			if (r.ok) setTimeout(function () { location.reload(); }, 600);
		}, 'json');
	});

	$doc.on('click', '.rv_delete', function () {
		if (!confirm('Ревизияны өшіреміз бе?')) return;
		$.post('/revision/get.php?delete', { id: $doc.data('id') }, function (r) {
			say(r.msg);
			if (r.ok) { dirty = false; setTimeout(function () { location.href = '/revision/'; }, 600); }
		}, 'json');
	});
})(jQuery);
