// «Жұмысты бастау» виджеті мен /start/ беті (block/onboard.php, start/index.php)
(function () {
	function setCookie(n, v) { document.cookie = n + '=' + v + '; path=/; max-age=' + (v ? 31536000 : 0) }
	function ls(k, v) { try { if (v === undefined) return localStorage.getItem(k); if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v) } catch (e) { return null } }

	var w = document.getElementById('ob_w');
	if (w) {
		var cmp = w.getAttribute('data-cmp'), key = 'ob_col_' + cmp, hd = w.querySelector('.ob_hd');
		// жию күйі сақталады; алғаш телефонда жиылып тұрады
		var col = ls(key);
		if (col === '1' || (col === null && window.innerWidth <= 768)) w.classList.add('ob_col');
		hd.setAttribute('aria-expanded', w.classList.contains('ob_col') ? 'false' : 'true');
		w.hidden = false;
		function toggle() {
			var c = w.classList.toggle('ob_col');
			ls(key, c ? '1' : '0');
			hd.setAttribute('aria-expanded', c ? 'false' : 'true');
		}
		hd.addEventListener('click', toggle);
		hd.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle() } });
		var hide = w.querySelector('.ob_hide');
		if (hide) hide.addEventListener('click', function () { setCookie('ob_hide_' + cmp, '1'); w.remove() });
	}

	// /start/: «Әр бетте көрсету» ауыстырғышы
	var pg = document.querySelector('.ob_page'), sw = pg && pg.querySelector('.ob_show');
	if (sw) sw.addEventListener('change', function () {
		var c = pg.getAttribute('data-cmp');
		setCookie('ob_hide_' + c, sw.checked ? '' : '1');
		if (sw.checked) ls('ob_col_' + c, '0');
	});
})();
