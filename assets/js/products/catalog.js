// каталог беті: қосу, өзгерту, рет, өшіру
$(document).ready(function() {

	var ctgColor = '#698cc8';
	function ctgSetColor(c) {
		ctgColor = c;
		$('.ctg_color').removeClass('ctg_color_act');
		$('.ctg_color[data-color="' + c + '"]').addClass('ctg_color_act');
		$('.ctg_color_in').val(c.length == 7 ? c : '#666666');
	}

	// попап ашу / жабу
	function ctgOpen(id, kz, ru, color) {
		$('.ctg_title').text(id ? 'Каталогты өзгерту' : 'Каталог қосу');
		$('.ctg_save').attr('data-id', id || '');
		$('.ctg_name_kz_in').val(kz || '');
		$('.ctg_name_ru_in').val(ru || '');
		ctgSetColor(color || '#698cc8');
		$('.ctg_block').addClass('pop_bl_act');
		$('#html').addClass('ovr_h');
		setTimeout(function(){ $('.ctg_name_kz_in').focus() }, 100);
	}
	function ctgClose() {
		$('.ctg_block').removeClass('pop_bl_act');
		$('#html').removeClass('ovr_h');
	}
	$('.ctg_add_pop').on('click', function(){ ctgOpen() });
	$('.ctg_back').on('click', ctgClose);
	$('html').on('click', '.ctg_edit', function(){
		var b = $(this);
		ctgOpen(b.closest('.ctg_i').attr('data-id'), b.attr('data-name-kz'), b.attr('data-name-ru'), b.attr('data-color'));
	});
	$('html').on('click', '.ctg_color', function(){ ctgSetColor($(this).attr('data-color')) });
	$('.ctg_color_in').on('input change', function(){ ctgSetColor($(this).val()) });

	// сақтау
	$('.ctg_save').on('click', function(){
		var btn = $(this);
		if ($.trim($('.ctg_name_kz_in').val()) == '') { mess('Атауын жазыңыз'); return; }
		$.ajax({
			url: '/products/catalog/get.php?save', type: 'POST', dataType: 'html',
			data: { id: btn.attr('data-id'), name_kz: $('.ctg_name_kz_in').val(), name_ru: $('.ctg_name_ru_in').val(), color: ctgColor },
			beforeSend: function(){ mess('Сақталуда..') },
			success: function(data){
				if (data == 'yes') { mess('Сақталды'); setTimeout(function(){ location.reload() }, 400) }
				else { mess('Қате!'); console.log(data) }
			},
		});
	});
	$('.ctg_name_kz_in, .ctg_name_ru_in').on('keydown', function(e){ if (e.key == 'Enter') $('.ctg_save').click() });

	// өшіру
	$('html').on('click', '.ctg_del', function(){
		var b = $(this), row = b.closest('.ctg_i');
		var cnt = Number(b.attr('data-cnt'));
		if (!confirm('Каталогты архивке жіберейік пе?' + (cnt > 0 ? '\nІшіндегі ' + cnt + ' тағам «Каталогсыз» тобына түседі.' : '') + '\nТолық өшпейді.')) return;
		$.ajax({
			url: '/products/catalog/get.php?delete', type: 'POST', dataType: 'html',
			data: { id: row.attr('data-id') },
			success: function(data){
				if (data == 'yes') { row.remove(); ctgRenumber(); ctgSaveSort(); mess('Архивке жіберілді') }
				else { mess('Қате!'); console.log(data) }
			},
		});
	});

	// рет
	function ctgRenumber() { $('.ctg_list .ctg_i').each(function(i){ $(this).find('.ctg_num').text(i + 1) }) }
	var sortTimer = null;
	function ctgSaveSort() {
		clearTimeout(sortTimer);
		sortTimer = setTimeout(function(){
			var ids = $('.ctg_list .ctg_i').map(function(){ return $(this).attr('data-id') }).get();
			$.ajax({
				url: '/products/catalog/get.php?sort', type: 'POST', dataType: 'html',
				data: { ids: ids },
				success: function(data){ if (data == 'yes') mess('Рет сақталды'); else { mess('Рет сақталмады!'); console.log(data) } },
				error: function(){ mess('Рет сақталмады!') },
			});
		}, 300);
	}
	$('html').on('click', '.ctg_up', function(){
		var row = $(this).closest('.ctg_i'), prev = row.prev('.ctg_i');
		if (prev.length) { row.insertBefore(prev); ctgRenumber(); ctgSaveSort() }
	});
	$('html').on('click', '.ctg_down', function(){
		var row = $(this).closest('.ctg_i'), next = row.next('.ctg_i');
		if (next.length) { row.insertAfter(next); ctgRenumber(); ctgSaveSort() }
	});

	// сүйреп ауыстыру (тінтуір)
	var dragEl = null;
	$('html').on('dragstart', '.ctg_i', function(e){
		dragEl = this; $(this).addClass('ctg_i_drag');
		e.originalEvent.dataTransfer.effectAllowed = 'move';
		e.originalEvent.dataTransfer.setData('text/plain', $(this).attr('data-id'));
	});
	$('html').on('dragover', '.ctg_i', function(e){
		e.preventDefault();
		if (!dragEl || dragEl === this) return;
		var r = this.getBoundingClientRect();
		if (e.originalEvent.clientY - r.top > r.height / 2) $(this).after(dragEl); else $(this).before(dragEl);
	});
	$('html').on('dragend', '.ctg_i', function(){
		$(this).removeClass('ctg_i_drag');
		if (dragEl) { dragEl = null; ctgRenumber(); ctgSaveSort() }
	});

});
