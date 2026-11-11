// каталог беті: қосу, өзгерту, рет, өшіру
$(document).ready(function() {

	var ctgColor = '#698cc8';
	function ctgSetColor(c) {
		ctgColor = c;
		$('.ctg_color').removeClass('ctg_color_act');
		$('.ctg_color[data-color="' + c + '"]').addClass('ctg_color_act');
		$('.ctg_color_in').val(c.length == 7 ? c : '#666666');
	}

	// мәзірдегі суреті: фото не иконка
	var ctgIcon = '', ctgImg = '', ctgFile = null;
	function ctgPreview() {
		var pv = $('.ctg_pv');
		if (ctgImg) pv.css('background-image', 'url("' + ctgImg + '")').addClass('ctg_pv_img').text('');
		else pv.css('background-image', '').removeClass('ctg_pv_img').text(ctgIcon || '🍽️');
		$('.ctg_img_del').prop('hidden', !ctgImg);
	}
	function ctgSetIcon(v) {
		ctgIcon = v || '';
		$('.ctg_icon').removeClass('ctg_icon_act');
		var m = $('.ctg_icon').filter(function () { return $(this).attr('data-icon') === ctgIcon; });
		m.addClass('ctg_icon_act');
		$('.ctg_icon_in').val(m.length ? '' : ctgIcon);
		ctgPreview();
	}
	$('html').on('click', '.ctg_icon', function(){ ctgSetIcon($(this).attr('data-icon')) });
	$('.ctg_icon_in').on('input', function(){ var v = $.trim($(this).val()); ctgIcon = v; $('.ctg_icon').removeClass('ctg_icon_act'); if (!v) $('.ctg_icon_auto').addClass('ctg_icon_act'); ctgPreview(); });

	// фото: санат бар болса бірден жүктеледі, жаңа санатқа «Сақтау»-дан кейін
	function ctgUpload(id, file, done) {
		var fd = new FormData(); fd.append('id', id); fd.append('file', file);
		mess('Фото жүктелуде..');
		$.ajax({ url: '/products/catalog/get.php?img', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
			success: function (r) {
				if (r && r.ok) { ctgImg = r.url; ctgFile = null; ctgPreview(); mess('Фото сақталды'); }
				else mess(r && r.err == 'type' ? 'Тек JPG, PNG не WEBP' : (r && r.err == 'size' ? 'Файл тым үлкен (15 МБ-қа дейін)' : 'Фото сақталмады!'));
				if (done) done();
			},
			error: function () { mess('Фото сақталмады!'); if (done) done(); } });
	}
	$('.ctg_img_in').on('change', function(){
		var f = this.files && this.files[0], id = $('.ctg_save').attr('data-id');
		this.value = '';
		if (!f) return;
		if (id) { ctgUpload(id, f); return; }
		ctgFile = f; // жаңа санат: алдын ала көрсетіп, сақтағанда жүктейміз
		ctgImg = URL.createObjectURL(f); ctgPreview();
	});
	$('.ctg_img_del').on('click', function(){
		var id = $('.ctg_save').attr('data-id');
		if (!id) { ctgFile = null; ctgImg = ''; ctgPreview(); return; }
		$.ajax({ url: '/products/catalog/get.php?img_del', type: 'POST', data: { id: id }, dataType: 'json',
			success: function (r) { if (r && r.ok) { ctgImg = ''; ctgPreview(); mess('Фото өшірілді'); } else mess('Қате!'); } });
	});

	// попап ашу / жабу
	function ctgOpen(id, kz, ru, color, icon, img) {
		$('.ctg_title').text(id ? 'Каталогты өзгерту' : 'Каталог қосу');
		$('.ctg_save').attr('data-id', id || '');
		$('.ctg_name_kz_in').val(kz || '');
		$('.ctg_name_ru_in').val(ru || '');
		ctgSetColor(color || '#698cc8');
		ctgFile = null; ctgImg = img || ''; ctgSetIcon(icon || '');
		$('.ctg_block').addClass('pop_bl_act');
		$('#html').addClass('ovr_h');
		setTimeout(function(){ $('.ctg_name_kz_in').focus() }, 100);
	}
	function ctgClose() {
		$('.ctg_block').removeClass('pop_bl_act');
		$('#html').removeClass('ovr_h');
		if (ctgChanged) { ctgChanged = false; ajx('.ctg_c'); }
	}
	var ctgChanged = false; // фото жүктелсе, жабқанда тізім жаңарады
	$(document).ajaxSuccess(function (e, x, o) { if (o.url && o.url.indexOf('/products/catalog/get.php?img') === 0) ctgChanged = true; });
	$('.ctg_add_pop').on('click', function(){ ctgOpen() });
	$('.ctg_back').on('click', ctgClose);
	$('html').on('click', '.ctg_edit', function(){
		var b = $(this);
		ctgOpen(b.closest('.ctg_i').attr('data-id'), b.attr('data-name-kz'), b.attr('data-name-ru'), b.attr('data-color'), b.attr('data-icon'), b.attr('data-img'));
	});
	$('html').on('click', '.ctg_color', function(){ ctgSetColor($(this).attr('data-color')) });
	$('.ctg_color_in').on('input change', function(){ ctgSetColor($(this).val()) });

	// сақтау
	$('.ctg_save').on('click', function(){
		var btn = $(this);
		if ($.trim($('.ctg_name_kz_in').val()) == '') { mess('Атауын жазыңыз'); return; }
		$.ajax({
			url: '/products/catalog/get.php?save', type: 'POST', dataType: 'html',
			data: { id: btn.attr('data-id'), name_kz: $('.ctg_name_kz_in').val(), name_ru: $('.ctg_name_ru_in').val(), color: ctgColor, icon: ctgIcon },
			beforeSend: function(){ mess('Сақталуда..') },
			success: function(data){
				if (String(data).indexOf('yes') === 0) {
					var nid = String(data).split(':')[1];
					var fin = function () { mess('Сақталды'); ctgChanged = false; ctgClose(); ajx('.ctg_c') };
					if (ctgFile && nid) ctgUpload(nid, ctgFile, fin); else fin();
				}
				else { mess('Қате!'); console.log(data) }
			},
		});
	});
	$('.ctg_name_kz_in, .ctg_name_ru_in').on('keydown', function(e){ if (e.key == 'Enter') $('.ctg_save').click() });

	// өшіру
	$('html').on('click', '.ctg_del', function(){
		var b = $(this), row = b.closest('.ctg_i');
		var cnt = Number(b.attr('data-cnt'));
		ask('Каталогты архивке жіберейік пе?' + (cnt > 0 ? '<br>Ішіндегі ' + cnt + ' тағам «Каталогсыз» тобына түседі.' : '') + '<br>Толық өшпейді.', function(){ $.ajax({
			url: '/products/catalog/get.php?delete', type: 'POST', dataType: 'html',
			data: { id: row.attr('data-id') },
			success: function(data){
				if (data == 'yes') { row.remove(); ctgRenumber(); ctgSaveSort(); mess('Архивке жіберілді', 'ok') }
				else { mess('Қате!'); console.log(data) }
			},
		}) }, { ok: 'Архивке' });
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
	// ★ QR мәзірде бүкіл санатты ұсыну
	$('html').on('click', '.ctg_rec', function(){
		var b = $(this), on = !b.hasClass('ctg_rec_on');
		if (b.hasClass('ctg_rec_busy')) return;
		b.addClass('ctg_rec_busy');
		$.ajax({
			url: '/products/catalog/get.php?rec', type: 'POST', dataType: 'html',
			data: { id: b.closest('.ctg_i').attr('data-id'), on: on ? 1 : '' },
			success: function(data){
				b.removeClass('ctg_rec_busy');
				if (data != 'yes') { mess('Сақталмады!'); return }
				b.toggleClass('ctg_rec_on', on).find('i').attr('class', (on ? 'fas' : 'fal') + ' fa-star');
				mess(on ? 'Санат QR мәзірге қосылды' : 'Санат QR мәзірден алынды');
			},
			error: function(){ b.removeClass('ctg_rec_busy'); mess('Сақталмады!') },
		});
	});
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
