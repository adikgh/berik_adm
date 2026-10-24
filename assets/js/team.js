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
