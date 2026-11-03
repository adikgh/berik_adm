<? include "../config/core.php";
	include "../config/cfile.php";

	// Хостингтегі тағам фотоларын (assets/uploads/products/) Google Drive-қа көшіру.
	// Әр басқанда 5 фото: Drive-қа жүктейді, «сілтемесі барлар көреді» қылады, product.img-ге Drive сілтемесін жазады.
	// Бұрынғы атауы company_file.path-та қалады (қажет болса қайтаруға). Хостингтегі файл әзірге өшірілмейді.
	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }
	$cmp = (int)$company;
	$dir = __DIR__.'/../assets/uploads/products/';

	$left = function () use ($cmp) {
		$q = db::query("select count(*) c from product where company_id = '$cmp' and img is not null and img <> '' and img not like 'http%'");
		return $q ? (int)mysqli_fetch_assoc($q)['c'] : 0;
	};

	if (isset($_GET['run'])) {
		header('Content-Type: application/json; charset=utf-8');
		if (!cfile::drive_on()) exit(json_encode(['ok' => 0, 'msg' => 'Google Drive қосылмаған'], JSON_UNESCAPED_UNICODE));
		@set_time_limit(300);
		$skip = array_filter(array_map('intval', explode(',', (string)@$_POST['skip'])));
		$done = []; $fail = [];
		$q = db::query("select id, name_kz, img from product where company_id = '$cmp' and img is not null and img <> '' and img not like 'http%'".($skip ? " and id not in (".implode(',', $skip).")" : '')." order by id limit 5");
		while ($q && $p = mysqli_fetch_assoc($q)) {
			$pid = (int)$p['id']; $img = basename($p['img']); $path = $dir.$img;
			if (!is_file($path)) { $fail[] = ['id' => $pid, 'n' => $p['name_kz'], 'msg' => 'хостингте файл жоқ']; continue; }
			$bytes = file_get_contents($path);
			$info = @getimagesize($path);
			$mime = $info['mime'] ?? 'image/jpeg';
			$f = cfile::put($bytes, $img, $mime, 'product', $cmp, $pid, $user_id, $p['img']);
			if (!$f || !cfile::make_public($f['drive_id'])) { $fail[] = ['id' => $pid, 'n' => $p['name_kz'], 'msg' => cfile::$err ?: 'Drive қатесі']; continue; }
			$u = mysqli_real_escape_string(db::$connect, cfile::img_url($f['drive_id']));
			$old = mysqli_real_escape_string(db::$connect, $p['img']);
			db::query("update product set img = '$u', upd_dt = NOW() where id = '$pid' and company_id = '$cmp' and img = '$old'");
			$done[] = ['id' => $pid, 'n' => $p['name_kz'], 'url' => cfile::img_url($f['drive_id'], 200)];
		}
		exit(json_encode(['ok' => 1, 'done' => $done, 'fail' => $fail, 'left' => $left()], JSON_UNESCAPED_UNICODE));
	}

	$menu_name = 'products';
	$page_name = 'Фотоларды Drive-қа көшіру';
	$css = ['waybill'];
	$n = $left();
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">
	<div class="wb_box">
		<div class="wb_box_h"><div><i class="fab fa-google-drive"></i> Тағам фотоларын Google Drive-қа көшіру</div></div>
		<? if (!cfile::drive_on()): ?>
			<p>Алдымен Google Drive-ты қосыңыз: <a href="/files/drive.php">/files/drive.php</a></p>
		<? else: ?>
			<p>Хостингте тұрған фото: <b class="mg_left"><?=$n?></b>. Олар «Berik POS / Меню фотолары / <?=htmlspecialchars(cfile::cmp_label($cmp))?>» папкасына көшеді, касса мен админ суретті Drive-тан ашатын болады.</p>
			<? if ($n): ?><div class="wb_btn wb_btn_green mg_go"><i class="fal fa-cloud-upload"></i> Көшіруді бастау</div><? endif ?>
			<div class="mg_log"></div>
		<? endif ?>
	</div>
</div>

<style>
	.wb_box p { margin: 8px 0 12px; line-height: 1.5; }
	.mg_log { margin-top: 14px; display: grid; gap: 6px; }
	.mg_row { display: flex; gap: 10px; align-items: center; font-size: 14px; }
	.mg_row img { width: 40px; height: 40px; object-fit: cover; border-radius: 8px; }
	.mg_bad { color: #c0392b; }
</style>
<script>
	(function () {
		var btn = document.querySelector('.mg_go'), log = document.querySelector('.mg_log'), skip = [], ok = 0;
		if (!btn) return;
		function step() {
			var fd = new FormData(); fd.append('skip', skip.join(','));
			fetch('?run=1', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				if (!d.ok) { log.insertAdjacentHTML('beforeend', '<div class="mg_bad">' + d.msg + '</div>'); btn.classList.remove('wb_btn_wait'); return; }
				d.done.forEach(function (x) { ok++; log.insertAdjacentHTML('afterbegin', '<div class="mg_row"><img src="' + x.url + '" alt=""> ✓ ' + (x.n || ('#' + x.id)) + '</div>'); });
				d.fail.forEach(function (x) { skip.push(x.id); log.insertAdjacentHTML('afterbegin', '<div class="mg_row mg_bad">✗ ' + (x.n || ('#' + x.id)) + ': ' + x.msg + '</div>'); });
				document.querySelector('.mg_left').textContent = d.left;
				if (d.left > skip.length && (d.done.length || d.fail.length)) step();
				else { btn.classList.remove('wb_btn_wait'); btn.innerHTML = 'Дайын: ' + ok + ' фото көшірілді' + (skip.length ? ', ' + skip.length + ' қате' : ''); }
			}).catch(function () { btn.classList.remove('wb_btn_wait'); log.insertAdjacentHTML('afterbegin', '<div class="mg_bad">Байланыс қатесі, қайта басыңыз</div>'); });
		}
		btn.addEventListener('click', function () { if (btn.classList.contains('wb_btn_wait')) return; btn.classList.add('wb_btn_wait'); btn.textContent = 'Көшіріп жатырмыз...'; step(); });
	})();
</script>

<? include "../block/footer.php"; ?>
