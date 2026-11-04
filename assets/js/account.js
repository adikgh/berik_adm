// Аккаунт поп-апы: мәзірдегі «Аккаунт» басқанда ашылады. Аты-жөнін сақтау, парольді ауыстыру.
(function () {
	if (window.acpReady) return; window.acpReady = 1;
	var $p = function () { return $('.acp'); };

	function open(tab) {
		$('.ub1_lx').parent().removeClass('menu_act');
		tabTo(tab || 'prof');
		$p().addClass('acp_on').attr('aria-hidden', 'false');
		$('#html').addClass('ovr_h');
	}
	function close() {
		$p().removeClass('acp_on').attr('aria-hidden', 'true');
		$('#html').removeClass('ovr_h');
		$('.acp_pass')[0] && $('.acp_pass')[0].reset();
	}
	function tabTo(t) {
		$('.acp_tab').removeClass('acp_tab_act').filter('[data-t="' + t + '"]').addClass('acp_tab_act');
		$('.acp_f').addClass('dsp_n').filter('[data-t="' + t + '"]').removeClass('dsp_n');
	}
	window.acpOpen = open;

	$(document).on('click', '.ac_open', function (e) { e.preventDefault(); open(); });
	$(document).on('click', '.acp_x', close);
	$(document).on('keydown', function (e) { if (e.key === 'Escape' && $p().hasClass('acp_on')) close(); });
	$(document).on('click', '.acp_tab', function () { tabTo(this.dataset.t); });

	function send(form, what) {
		var btn = form.querySelector('button[type=submit]'), d = new FormData(form);
		d.append('csrf', $p().attr('data-csrf') || '');
		btn.classList.add('acp_wait');
		return fetch('/acc/get.php?' + what, { method: 'POST', body: d })
			.then(function (r) { return r.json(); })
			.catch(function () { return { ok: false, msg: 'Байланыс қатесі, қайта көріңіз' }; })
			.then(function (r) { btn.classList.remove('acp_wait'); mess(r.msg || 'Қате', r.ok ? 'ok' : 'err'); return r; });
	}

	$(document).on('submit', '.acp_prof', function (e) {
		e.preventDefault();
		var f = this;
		send(f, 'profile').then(function (r) {
			if (!r.ok) return;
			var n = f.elements.name.value.trim(), s = f.elements.surname.value.trim();
			$('.acp_nm, .acc_hd_t > div:first-child').text((n + ' ' + s).trim());
			$('.ub1_ltf > div:first-child').text(n + (s ? ' ' + s.charAt(0) + '.' : ''));
		});
	});

	$(document).on('submit', '.acp_pass', function (e) {
		e.preventDefault();
		var f = this;
		if (f.elements['new'].value !== f.elements.new2.value) return mess('Жаңа парольдер сәйкес емес', 'err');
		send(f, 'pass').then(function (r) { if (r.ok) { f.reset(); close(); } });
	});

	$(document).on('click', '.acp_eye', function () {
		var i = this.previousElementSibling, show = i.type === 'password';
		i.type = show ? 'text' : 'password';
		this.classList.toggle('fa-eye', show);
		this.classList.toggle('fa-eye-slash', !show);
	});

	// /acc/ сілтемесімен келсе (?acc=1), бірден ашылады
	if (/[?&]acc=1/.test(location.search)) $(function () { open(); });
})();
