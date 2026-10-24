// Клиенттер
$('.cl_save').on('click', function () {
	var f = $(this).closest('.cl_form'), btn = $(this);
	btn.addClass('cl_btn_wait');
	$.post('/clients/get.php?client_save', {
		id: f.data('id'),
		name: f.find('.cl_f_name').val(),
		address: f.find('.cl_f_address').val(),
		note: f.find('.cl_f_note').val()
	}, function (r) {
		btn.removeClass('cl_btn_wait');
		if (r == 'yes') { mess('Сақталды'); setTimeout(function () { location.reload(); }, 500); }
		else mess('Қате, қайта көріңіз');
	});
});

$('.cl_archive').on('click', function () {
	var f = $(this).closest('.cl_form'), v = $(this).data('v');
	if (v == 1 && !confirm('Клиентті архивке жіберу керек пе? Деректері өшпейді.')) return;
	$.post('/clients/get.php?client_archive', { id: f.data('id'), v: v }, function (r) {
		if (r == 'yes') location.reload();
		else mess('Қате, қайта көріңіз');
	});
});
