// Накладной
(function ($) {
	var say = function (t) { if (typeof mess == 'function') mess(t); else alert(t); };
	var num = function (v) { v = String(v || '').replace(/\s+/g, '').replace(',', '.'); var n = parseFloat(v); return isNaN(n) ? 0 : n; };
	var r2 = function (n) { return Math.round(n * 100) / 100; };
	var money = function (n) {
		n = r2(n);
		var s = (Math.floor(n) == n ? n.toFixed(0) : n.toFixed(2));
		return s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₸';
	};
	var raw = window.wb_raw || [];
	var findRaw = function (name) {
		name = $.trim(name).toLowerCase();
		for (var i = 0; i < raw.length; i++) if (raw[i].n.toLowerCase() == name) return raw[i];
		return null;
	};

	var $doc = $('.wb_doc');

	// ---- Жолдар ----
	function total() {
		var t = 0;
		$doc.find('.wb_row').each(function (k) {
			$(this).find('.wb_row_n').text(k + 1);
			t += num($(this).find('.wb_r_sum').val());
		});
		if ($doc.data('posted') != 1) $doc.find('.wb_total_v').text(money(t));
		var tt = $doc.data('posted') == 1 ? num($doc.find('.wb_total_v').text()) : t;
		$doc.find('.wb_debt_v').text(money(Math.max(0, tt - num($doc.find('.wb_f_paid').val()))));
		return t;
	}

	function newRow() {
		var $last = $doc.find('.wb_row').last();
		var $r = $last.clone();
		$r.find('input').val('');
		$r.find('.wb_r_name').attr('data-raw', 0).data('raw', 0);
		$r.find('.wb_r_unit').val($last.find('.wb_r_unit').val());
		$doc.find('.wb_rows').append($r);
		total();
		$r.find('.wb_r_name').focus();
	}

	$doc.on('click', '.wb_add_row', newRow);

	$doc.on('click', '.wb_x', function () {
		if ($doc.find('.wb_row').length > 1) $(this).closest('.wb_row').remove();
		else $(this).closest('.wb_row').find('input').val('');
		total();
	});

	// атауы шикізатқа сәйкес келсе, өлшемі мен соңғы бағасын қою
	$doc.on('change', '.wb_r_name', function () {
		var $r = $(this).closest('.wb_row'), m = findRaw($(this).val());
		$(this).data('raw', m ? m.id : 0);
		if (m) {
			if (m.u) {
				var $u = $r.find('.wb_r_unit');
				if (!$u.find('option').filter(function () { return $(this).text() == m.u; }).length) $u.append($('<option>').text(m.u));
				$u.val(m.u);
			}
			if (m.p && !num($r.find('.wb_r_price').val())) $r.find('.wb_r_price').val(m.p).trigger('input');
		}
	});

	$doc.on('input', '.wb_r_qty, .wb_r_price', function () {
		var $r = $(this).closest('.wb_row'), q = num($r.find('.wb_r_qty').val()), p = num($r.find('.wb_r_price').val());
		$r.find('.wb_r_sum').val(q && p ? r2(q * p) : '');
		total();
	});
	// сомасын жазса, бағасы өзі есептеледі
	$doc.on('input', '.wb_r_sum', function () {
		var $r = $(this).closest('.wb_row'), q = num($r.find('.wb_r_qty').val()), s = num($(this).val());
		if (q) $r.find('.wb_r_price').val(r2(s / q));
		total();
	});
	// соңғы жолда Enter басса, жаңа жол
	$doc.on('keydown', '.wb_row input', function (e) {
		if (e.key != 'Enter') return;
		e.preventDefault();
		var $r = $(this).closest('.wb_row');
		if ($r.is($doc.find('.wb_row').last())) newRow();
		else $r.next().find('.wb_r_name').focus();
	});

	$doc.on('input', '.wb_f_paid', total);
	$doc.on('click', '.wb_pay_full', function () {
		var t = $doc.data('posted') == 1 ? num($doc.find('.wb_total_v').text()) : total();
		$doc.find('.wb_f_paid').val(r2(t)).trigger('input');
	});

	$doc.on('change', '.wb_f_supplier', function () {
		$doc.find('.wb_f_supplier_new').toggleClass('dsp_n', $(this).val() != 'new');
		if ($(this).val() == 'new') $doc.find('.wb_f_supplier_new').focus();
	});

	// ---- Сақтау / Қабылдау ----
	$doc.on('click', '.wb_save', function () {
		var btn = $(this), post = btn.data('post');
		var items = [];
		$doc.find('.wb_row').each(function () {
			var $r = $(this);
			items.push({
				raw_id: $r.find('.wb_r_name').data('raw') || 0,
				name: $r.find('.wb_r_name').val(),
				unit: $r.find('.wb_r_unit').val(),
				qty: $r.find('.wb_r_qty').val(),
				price: $r.find('.wb_r_price').val()
			});
		});
		if (post == 1 && !confirm('Накладнойды қабылдап, шикізат қалдығына қосамыз ба?')) return;
		$('.wb_save').addClass('wb_btn_wait');
		$.post('/waybill/get.php?save', {
			id: $doc.data('id'),
			supplier_id: $doc.find('.wb_f_supplier').val(),
			supplier_new: $doc.find('.wb_f_supplier_new').val(),
			doc_date: $doc.find('.wb_f_date').val(),
			doc_no: $doc.find('.wb_f_docno').val(),
			comment: $doc.find('.wb_f_comment').val(),
			paid: $doc.find('.wb_f_paid').val(),
			items: JSON.stringify(items),
			post: post
		}, function (r) {
			$('.wb_save').removeClass('wb_btn_wait');
			say(r.msg || (r.ok ? 'Сақталды' : 'Қате шықты'));
			if (r.id) setTimeout(function () { location.href = '/waybill/item.php?id=' + r.id; }, 600);
		}, 'json').fail(function () { $('.wb_save').removeClass('wb_btn_wait'); say('Байланыс қатесі, қайта көріңіз'); });
	});

	$doc.on('click', '.wb_unpost', function () {
		if (!confirm('Қабылдауды болдырмаймыз ба? Бұл тауарлар қалдықтан алынады.')) return;
		$.post('/waybill/get.php?unpost', { id: $doc.data('id') }, function (r) {
			say(r.msg);
			if (r.ok) setTimeout(function () { location.reload(); }, 600);
		}, 'json');
	});

	$doc.on('click', '.wb_delete', function () {
		if (!confirm('Накладнойды өшіреміз бе?')) return;
		$.post('/waybill/get.php?delete', { id: $doc.data('id') }, function (r) {
			say(r.msg);
			if (r.ok) setTimeout(function () { location.href = '/waybill/'; }, 600);
		}, 'json');
	});

	$doc.on('click', '.wb_pay_save', function () {
		$.post('/waybill/get.php?pay', { id: $doc.data('id'), paid: $doc.find('.wb_f_paid').val() }, function (r) {
			say(r.msg);
			if (r.ok) setTimeout(function () { location.reload(); }, 600);
		}, 'json');
	});

	if ($doc.length) total();

	// ---- Жеткізушілер ----
	var $sf = $('.wb_sup_form');
	function supForm(id, name, phone, note) {
		$sf.data('id', id).removeClass('dsp_n');
		$sf.find('.wb_sup_form_h').text(id ? 'Жеткізушіні өзгерту' : 'Жаңа жеткізуші');
		$sf.find('.wb_s_name').val(name || '').focus();
		$sf.find('.wb_s_phone').val(phone || '');
		$sf.find('.wb_s_note').val(note || '');
		$('html, body').animate({ scrollTop: $sf.offset().top - 90 }, 200);
	}
	$('.wb_sup_new').on('click', function () { supForm(0); });
	$('.wb_sup_edit').on('click', function () {
		var $t = $(this).closest('.wb_tr');
		supForm($t.data('id'), $t.data('name'), $t.data('phone'), $t.data('note'));
	});
	$('.wb_sup_cancel').on('click', function () { $sf.addClass('dsp_n'); });
	$('.wb_sup_save').on('click', function () {
		$.post('/waybill/get.php?supplier_save', {
			id: $sf.data('id'), name: $sf.find('.wb_s_name').val(), phone: $sf.find('.wb_s_phone').val(), note: $sf.find('.wb_s_note').val()
		}, function (r) {
			say(r.msg);
			if (r.ok) setTimeout(function () { location.reload(); }, 500);
		}, 'json');
	});
	$('.wb_sup_arch').on('click', function () {
		var id = $(this).closest('.wb_tr').data('id'), v = $(this).data('v');
		if (v == 1 && !confirm('Жеткізушіні архивке жібереміз бе? Накладнойлары өшпейді.')) return;
		$.post('/waybill/get.php?supplier_archive', { id: id, v: v }, function (r) {
			if (r.ok) location.reload(); else say('Қате шықты');
		}, 'json');
	});
})(jQuery);
