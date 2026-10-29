// жұмысшылар беті: қосу, өзгерту, тізімнен шығару
$(document).ready(function() {

	function tmChip(box, val) {
		$(box).find('.tm_chip').removeClass('tm_chip_act');
		$(box).find('.tm_chip[data-val="' + val + '"]').addClass('tm_chip_act');
	}
	function tmOpen(id, d) {
		d = d || {};
		$('.tm_title').text(id ? 'Жұмысшыны өзгерту' : 'Жұмысшы қосу');
		$('.tm_save').attr('data-id', id || '');
		$('.tm_in_name').val(d.name || '');
		$('.tm_in_surname').val(d.surname || '');
		$('.tm_in_phone').val(d.phone || '');
		$('.tm_in_code, .tm_in_pass').val('');
		$('.tm_hint').text('').removeClass('tm_hint_warn');
		tmChip('.tm_in_pos', d.pos || 6);
		tmChip('.tm_in_branch', d.branch || 0);
		$('.tm_block').addClass('pop_bl_act');
		$('#html').addClass('ovr_h');
	}
	function tmClose() { $('.tm_block').removeClass('pop_bl_act'); $('#html').removeClass('ovr_h'); }

	$('.tm_add_pop').on('click', function(){ tmOpen() });
	$('.tm_back').on('click', tmClose);
	$('html').on('click', '.tm_chip', function(){ tmChip($(this).parent(), $(this).attr('data-val')) });
	$('html').on('click', '.tm_edit', function(){
		var b = $(this);
		tmOpen(b.closest('.tm_i').attr('data-id'), { name: b.attr('data-name'), surname: b.attr('data-surname'), phone: b.attr('data-phone'), pos: b.attr('data-pos'), branch: b.attr('data-branch') });
	});

	// жаңа жұмысшы: нөмір базада бар болса, сол адамды көрсетеміз (екінші рет жасалмайды)
	var tmLast = '';
	$('.tm_in_phone').on('input change', function(){
		if ($('.tm_save').attr('data-id')) return;
		var ph = $(this).val().replace(/\D/g, '').slice(-10), hint = $('.tm_hint');
		if (ph.length < 10) { tmLast = ''; hint.text('').removeClass('tm_hint_warn'); return; }
		if (ph == tmLast) return;
		tmLast = ph;
		$.post('/team/get.php?find', { phone: ph }, function(d){
			if (ph != tmLast) return;
			hint.text('').removeClass('tm_hint_warn');
			if (!d || !d.name) return;
			var who = (d.name + ' ' + d.surname).trim();
			if (d.staff == 'here') {
				var pos = $('.tm_in_pos .tm_chip[data-val="' + d.pos + '"]').text().trim();
				hint.addClass('tm_hint_warn').text(who + ' тізімде бар' + (pos ? ' (' + pos + ')' : '') + '. Лауазымын ауыстыру үшін тізімнен сол адамды өзгертіңіз.');
			} else if (d.staff == 'other') {
				hint.addClass('tm_hint_warn').text('Бұл нөмір басқа компанияда жұмысшы болып тіркелген.');
			} else {
				hint.text('Базада бар адам: ' + who + '. Жаңасы жасалмайды, осы адам қосылады.');
				if (!$('.tm_in_name').val()) $('.tm_in_name').val(d.name);
				if (!$('.tm_in_surname').val()) $('.tm_in_surname').val(d.surname);
			}
		}, 'json');
	});

	var errs = { name: 'Атын жазыңыз', phone: 'Телефонды дұрыс жазыңыз', pos: 'Лауазымын таңдаңыз', code: 'PIN 4 сан болуы керек',
		phone_busy: 'Бұл нөмір басқа адамда тұр', already: 'Бұл адам тізімде бар', other_company: 'Бұл нөмір басқа компанияда тіркелген' };

	$('.tm_save').on('click', function(){
		var btn = $(this);
		$.ajax({
			url: '/team/get.php?save', type: 'POST', dataType: 'html',
			data: {
				id: btn.attr('data-id'), name: $('.tm_in_name').val(), surname: $('.tm_in_surname').val(), phone: $('.tm_in_phone').val(),
				pos: $('.tm_in_pos .tm_chip_act').attr('data-val'), branch: $('.tm_in_branch .tm_chip_act').attr('data-val') || 0,
				code: $('.tm_in_code').val(), pass: $('.tm_in_pass').val(),
			},
			beforeSend: function(){ mess('Сақталуда..') },
			success: function(data){
				if (data == 'yes') { mess('Сақталды'); setTimeout(function(){ location.reload() }, 400) }
				else { mess(errs[data] || 'Қате!'); console.log(data) }
			},
		});
	});

	$('html').on('click', '.tm_del', function(){
		var row = $(this).closest('.tm_i');
		if (!confirm('Жұмысшыны тізімнен шығарайық па?\nОның аккаунты өшпейді, кейін қайта қосуға болады.')) return;
		$.ajax({
			url: '/team/get.php?remove', type: 'POST', dataType: 'html',
			data: { id: row.attr('data-id') },
			success: function(data){
				if (data == 'yes') { row.remove(); mess('Тізімнен шығарылды') }
				else if (data == 'self') mess('Өзіңізді шығара алмайсыз')
				else { mess('Қате!'); console.log(data) }
			},
		});
	});

});
