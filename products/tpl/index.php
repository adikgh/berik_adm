<? include "../../config/core.php";
	include_once "lib.php";
	// Граммовка шаблондары: тек әзірлеуші (Создатель, positions_id 1) өзгертеді. Барлық компанияға ортақ.
	if (!$user_id) header('location: /');
	if ((int)@$user_right['positions_id'] !== 1) header('location: /products/');
	$menu_name = 'products'; $pod_menu_name = 'tpl'; $pm = 'tpl';
	$css = ['gtpl']; $js = [];
	$tpls = []; $items = [];
	if (gtpl::ready()) {
		$q = db::query("select * from grammovka_tpl where arh = 0 order by cat, number, id");
		while ($r = mysqli_fetch_assoc($q)) $tpls[$r['id']] = $r;
		$q = db::query("select tpl_id, variant, name_ru, unit, quantity, number from grammovka_tpl_item order by tpl_id, number, variant");
		while ($r = mysqli_fetch_assoc($q)) {
			$k = $r['tpl_id'] . '|' . $r['number'];
			if (!isset($items[$r['tpl_id']][$k])) $items[$r['tpl_id']][$k] = ['name' => $r['name_ru'], 'unit' => $r['unit'], 'q' => ['', '', '']];
			$items[$r['tpl_id']][$k]['q'][$r['variant'] - 1] = (float)$r['quantity'];
		}
	}
	$data = [];
	foreach ($tpls as $id => $t) $data[] = ['id' => (int)$id, 'name' => $t['name_ru'], 'cat' => $t['cat'], 'keys' => $t['keys_ru'], 'items' => array_values($items[$id] ?? [])];
?>
<? include "../../block/header.php"; ?>
	<div class="bl_c">
		<div class="head_c"><h4>Граммовка шаблондары</h4></div>
		<? if (!gtpl::ready()): ?>
			<p>Кесте жоқ: алдымен grammovka_tpl.sql іске қосыңыз.</p>
		<? else: ?>
		<p class="gt_wait">Барлық компанияға ортақ. Компаниялар тағам картасында не Себес бетінде 3 нұсқаның біреуін таңдайды. Мөлшер: кг құрам граммен, л құрам мл-мен, шт данамен.</p>
		<div class="gt_head" style="margin:10px 0 16px"><input class="gt_q gt_filter" placeholder="Іздеу…"><button type="button" class="btn gt_new"><i class="far fa-plus"></i> Жаңа шаблон</button></div>
		<div class="gt_dev"></div>
		<? endif ?>
	</div>
<? include "../../block/footer.php"; ?>
<style>
	.gt_dev{ display:flex; flex-direction:column; gap:8px; }
	.gt_row{ border:1px solid #e6eaee; border-radius:12px; background:#fff; }
	.gt_rh{ padding:10px 14px; cursor:pointer; display:flex; justify-content:space-between; gap:10px; }
	.gt_rh span{ color:#889; font-size:13px; }
	.gt_ed{ padding:0 14px 14px; display:none; }
	.gt_row.on .gt_ed{ display:block; }
	.gt_ed input, .gt_ed select{ padding:6px 8px; border:1px solid #dde2e7; border-radius:8px; font:inherit; width:100%; box-sizing:border-box; }
	.gt_f{ display:grid; grid-template-columns:2fr 1fr 2fr; gap:8px; margin:8px 0; }
	.gt_it{ width:100%; border-collapse:collapse; font-size:14px; }
	.gt_it td, .gt_it th{ padding:3px; text-align:left; }
	.gt_it th{ font-weight:500; color:#889; font-size:12px; }
	.gt_it td:nth-child(n+3){ width:80px; }
	.gt_btns{ display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
	@media (max-width:760px){ .gt_f{ grid-template-columns:1fr; } }
</style>
<script>
(function () {
	var D = <?=json_encode($data, JSON_UNESCAPED_UNICODE)?>, box = document.querySelector('.gt_dev');
	if (!box) return;
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function itemRow(it) {
		it = it || { name: '', unit: 'кг', q: ['', '', ''] };
		return '<tr><td><input class="i_n" value="' + esc(it.name) + '"></td><td><select class="i_u">' + ['кг', 'л', 'шт'].map(function (u) { return '<option' + (u === it.unit ? ' selected' : '') + '>' + u + '</option>'; }).join('') + '</select></td>' +
			[0, 1, 2].map(function (i) { return '<td><input class="i_q" inputmode="decimal" value="' + esc(it.q[i]) + '"></td>'; }).join('') + '<td><span class="gt_x" style="cursor:pointer;color:#c44">&times;</span></td></tr>';
	}
	function row(t) {
		var el = document.createElement('div'); el.className = 'gt_row'; el.dataset.id = t.id || '';
		el.dataset.s = (t.name + ' ' + (t.cat || '') + ' ' + (t.keys || '')).toLowerCase();
		el.innerHTML = '<div class="gt_rh"><b>' + esc(t.name || 'Жаңа шаблон') + '</b><span>' + esc(t.cat || '') + ' · ' + (t.items || []).length + ' құрам</span></div>' +
			'<div class="gt_ed"><div class="gt_f"><label>Атауы<input class="t_n" value="' + esc(t.name) + '"></label><label>Санаты<input class="t_c" value="' + esc(t.cat) + '"></label>' +
			'<label>Іздеу сөздері (| арқылы)<input class="t_k" value="' + esc(t.keys) + '"></label></div>' +
			'<table class="gt_it"><tr><th>Құрам</th><th>Бірлік</th><th>Эконом</th><th>Стандарт</th><th>Премиум</th><th></th></tr>' + (t.items || []).map(itemRow).join('') + '</table>' +
			'<div class="gt_btns"><button type="button" class="btn btn_cl gt_ai">+ Құрам</button><button type="button" class="btn gt_sv">Сақтау</button>' + (t.id ? '<button type="button" class="btn btn_cl gt_dl">Өшіру</button>' : '') + '</div></div>';
		return el;
	}
	D.forEach(function (t) { box.appendChild(row(t)); });
	document.querySelector('.gt_new').onclick = function () { var r = row({ items: [{ name: '', unit: 'кг', q: ['', '', ''] }] }); box.prepend(r); r.classList.add('on'); };
	document.querySelector('.gt_filter').oninput = function () { var q = this.value.trim().toLowerCase(); box.querySelectorAll('.gt_row').forEach(function (r) { r.style.display = !q || r.dataset.s.indexOf(q) > -1 ? '' : 'none'; }); };
	box.addEventListener('click', function (e) {
		var r = e.target.closest('.gt_row'); if (!r) return;
		if (e.target.closest('.gt_rh')) return r.classList.toggle('on');
		if (e.target.closest('.gt_x')) return e.target.closest('tr').remove();
		if (e.target.closest('.gt_ai')) return r.querySelector('.gt_it tbody, .gt_it').insertAdjacentHTML('beforeend', itemRow());
		var act = e.target.closest('.gt_sv') ? 'tpl_save' : e.target.closest('.gt_dl') ? 'tpl_del' : '';
		if (!act || (act === 'tpl_del' && !confirm('Шаблонды өшіреміз бе?'))) return;
		var fd = new FormData(); fd.append('id', r.dataset.id);
		if (act === 'tpl_save') {
			fd.append('name', r.querySelector('.t_n').value); fd.append('cat', r.querySelector('.t_c').value); fd.append('keys', r.querySelector('.t_k').value);
			fd.append('items', JSON.stringify([].map.call(r.querySelectorAll('.gt_it tr'), function (tr) {
				var n = tr.querySelector('.i_n'); if (!n) return null;
				return { name: n.value, unit: tr.querySelector('.i_u').value, q: [].map.call(tr.querySelectorAll('.i_q'), function (i) { return i.value; }) };
			}).filter(Boolean)));
		}
		fetch('get.php?' + act, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (x) { return x.json(); }).then(function (d) {
			if (d.result !== 'yes') return alert('Қате: ' + (d.error || d.result));
			if (act === 'tpl_del') return r.remove();
			r.dataset.id = d.id; r.querySelector('.gt_rh b').textContent = r.querySelector('.t_n').value; alert('Сақталды');
		});
	});
})();
</script>
