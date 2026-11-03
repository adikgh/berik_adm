// Клиенттер
$(document).on('click', '.cl_save', function () {
	var f = $(this).closest('.cl_form'), btn = $(this);
	btn.addClass('cl_btn_wait');
	$.post('/clients/get.php?client_save', {
		id: f.data('id'),
		name: f.find('.cl_f_name').val(),
		address: f.find('.cl_f_address').val(),
		note: f.find('.cl_f_note').val()
	}, function (r) {
		btn.removeClass('cl_btn_wait');
		if (r == 'yes') { mess('Сақталды'); ajx('.cl_page'); }
		else mess('Қате, қайта көріңіз');
	});
});

$(document).on('click', '.cl_archive', function () {
	var f = $(this).closest('.cl_form'), v = $(this).data('v');
	function go() {
		$.post('/clients/get.php?client_archive', { id: f.data('id'), v: v }, function (r) {
			if (r == 'yes') { mess(v == 1 ? 'Архивке жіберілді' : 'Архивтен шығарылды', 'ok'); ajx('.cl_page'); }
			else mess('Қате, қайта көріңіз');
		});
	}
	if (v == 1) ask('Клиентті архивке жіберу керек пе?<br>Деректері өшпейді.', go, { ok: 'Архивке' }); else go();
});
