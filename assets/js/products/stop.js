// стоп-лист беті: стопқа қою / алу, сатуға рұқсат, автомат стоп, іздеу
$(document).ready(function() {

	function slSet(el, mode) {
		var id = el.attr('data-id');
		el.addClass('sl_busy');
		$.ajax({ url: '/products/stop/get.php?set', type: 'POST', data: { id: id, mode: mode }, dataType: 'json',
			success: function (r) {
				if (r && r.ok) { mess(mode == 1 ? 'Стопқа қойылды' : (mode == 2 ? 'Сатуға рұқсат берілді' : 'Стоптан алынды')); ajx('.sl_page', slFilter); }
				else { el.removeClass('sl_busy'); mess('Қате!', 'err'); }
			},
			error: function () { el.removeClass('sl_busy'); mess('Байланыс қатесі', 'err'); } });
	}

	$(document).on('click', '.sl_btn', function () {
		var el = $(this), mode = el.attr('data-mode'), q = el.attr('data-ask');
		if (q) ask(q, function () { slSet(el, mode) });
		else slSet(el, mode);
	});

	$(document).on('change', '.sl_auto_in', function () {
		var on = this.checked ? 1 : 0, el = $(this);
		$.ajax({ url: '/products/stop/get.php?auto', type: 'POST', data: { on: on }, dataType: 'json',
			success: function (r) {
				if (r && r.ok) { mess(on ? 'Автомат стоп қосылды' : 'Автомат стоп өшірілді'); ajx('.sl_page', slFilter); }
				else { el.prop('checked', !on); mess('Қате!', 'err'); }
			},
			error: function () { el.prop('checked', !on); mess('Байланыс қатесі', 'err'); } });
	});

	// ескерту тізімі: барлығын көрсету
	$(document).on('click', '.sl_more_btn', function () { $(this).prev('.sl_list_fold').addClass('sl_list_open'); $(this).remove(); });

	// іздеу (бет жаңарғанда сақталады)
	var slQ = '';
	function slFilter() {
		$('.sl_search_in').val(slQ);
		var any = false;
		$('.sl_cat').each(function () {
			var vis = 0;
			$(this).find('.sl_row').each(function () {
				var ok = !slQ || $(this).attr('data-s').indexOf(slQ) >= 0;
				$(this).toggle(ok); if (ok) vis++;
			});
			$(this).toggle(vis > 0); if (vis) any = true;
		});
		$('.sl_nf').prop('hidden', any);
	}
	$(document).on('input', '.sl_search_in', function () { slQ = $.trim($(this).val()).toLowerCase(); slFilter(); });
});
