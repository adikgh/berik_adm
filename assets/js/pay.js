// жалақы беті: төлем жазу, өшіру
$(document).ready(function() {

	function zpChip(box, val) {
		$(box).find('.tm_chip').removeClass('tm_chip_act');
		$(box).find('.tm_chip[data-val="' + val + '"]').addClass('tm_chip_act');
	}
	function zpClose() { $('.zp_block').removeClass('pop_bl_act'); $('#html').removeClass('ovr_h'); }

	$('html').on('click', '.zp_add', function(){
		$('.zp_in_user').val($(this).attr('data-user') || '');
		$('.zp_in_amount, .zp_in_comment').val('');
		zpChip('.zp_in_type', 1);
		zpChip('.zp_in_method', 'cash');
		$('.zp_block').addClass('pop_bl_act');
		$('#html').addClass('ovr_h');
		setTimeout(function(){ $($('.zp_in_user').val() ? '.zp_in_amount' : '.zp_in_user').focus() }, 200);
	});
	$('.zp_back').on('click', zpClose);
	$('html').on('click', '.zp_block .tm_chip', function(){ zpChip($(this).parent(), $(this).attr('data-val')) });

	// сома: 150000 → 150 000
	$('.zp_in_amount').on('input', function(){
		var v = $(this).val().replace(/\D/g, '');
		$(this).val(v ? Number(v).toLocaleString('ru-RU').replace(/,/g, ' ') : '');
	});

	var errs = { user: 'Жұмысшыны таңдаңыз', amount: 'Соманы жазыңыз' };
	var busy = false;
	$('.zp_save').on('click', function(){
		if (busy) return;
		busy = true;
		$.ajax({
			url: '/team/pay_get.php?save', type: 'POST', dataType: 'html',
			data: {
				user: $('.zp_in_user').val(), amount: $('.zp_in_amount').val(),
				type: $('.zp_in_type .tm_chip_act').attr('data-val'), method: $('.zp_in_method .tm_chip_act').attr('data-val'),
				date: $('.zp_in_date').val(), comment: $('.zp_in_comment').val(),
			},
			beforeSend: function(){ mess('Сақталуда..') },
			success: function(data){
				if (data == 'yes') { mess('Сақталды'); setTimeout(function(){ location.reload() }, 400); return }
				busy = false;
				mess(errs[data] || 'Қате!'); console.log(data);
			},
			error: function(){ busy = false; mess('Қате!') },
		});
	});

	$('html').on('click', '.zp_del', function(){
		var row = $(this).closest('.wb_tr');
		if (!confirm('Бұл төлемді өшірейік па?')) return;
		$.ajax({
			url: '/team/pay_get.php?remove', type: 'POST', dataType: 'html',
			data: { id: row.attr('data-id') },
			success: function(data){
				if (data == 'yes') { mess('Өшірілді'); setTimeout(function(){ location.reload() }, 300) }
				else { mess('Қате!'); console.log(data) }
			},
		});
	});

});
