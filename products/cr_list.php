<?
	// Құрамы (product_comp) және Шикізат (product_raw) беттеріне ортақ тізім.
	// Шақыратын бет $cr береді: kind (comp|raw), table, add_key, add_kz, empty_key, empty_kz.
	if (!$user_id) { header('location: /'); exit(); }

	$cmp = (int)$company;
	$cr_raw = $cr['kind'] == 'raw';
	$cr_unit = fun::has_col($cr['table'], 'unit'); // product_comp-та unit жоқ
	$units = ['кг', 'г', 'л', 'мл', 'дана'];
	$unit_def = $cr_raw ? 'кг' : 'дана';
	$list = db::query("select * from {$cr['table']} where company_id = '$cmp' order by name_kz asc, id asc");
	$page_result = $list ? mysqli_num_rows($list) : 0;

	$TX = [
		'add_t'  => t::w($cr['add_key'], $cr['add_kz']),
		'upd_t'  => $cr_raw ? t::w('Edit raw material', 'Шикізатты өзгерту') : t::w('Edit ingredient', 'Құрамды өзгерту'),
		'add_b'  => t::w('Add', 'Қосу'),
		'save_b' => t::w('Save', 'Сақтау'),
		'name'   => t::w('Enter name', 'Атауын жазыңыз'),
		'added'  => t::w('Added', 'Қосылды'),
		'saved'  => t::w('Saved', 'Сақталды'),
		'exists' => t::w('Already exists', 'Мұндай атау бар'),
		'err'    => t::w('Error, try again', 'Қате, қайта көріңіз'),
		'per'    => '1',
		'per2'   => t::w('per unit', 'үшін'),
	];

	// site setting
	$menu_name = 'products';
	$pod_menu_name = $cr['kind'];
	$css = ['products/main', 'products/catalog'];
	$js = ['products/main'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c">

		<div class="ucours_t">
			<div class="ucours_tl">
				<div class="ucours_tm">
					<div class="btn btn_cl cr_add_pop">
						<i class="fal fa-plus"></i>
						<span><?=$TX['add_t']?></span>
					</div>
				</div>
			</div>
		</div>

		<? include "../aheader.php"; ?>

		<? if ($page_result): ?>
			<div class="ctg_c">
				<div class="form_im cr_srch">
					<input type="text" placeholder="<?=t::w('Search', 'Іздеу')?>" class="form_txt cr_search">
					<i class="fal fa-search form_icon"></i>
				</div>
				<div class="ctg_list">
					<? $number = 0; while ($r = mysqli_fetch_assoc($list)): $number++; $nm = $r['name_kz'] ?: $r['name_ru']; $un = $cr_unit ? (string)$r['unit'] : ''; ?>
						<div class="ctg_i cr_row" data-id="<?=$r['id']?>" data-nm="<?=htmlspecialchars($nm)?>" data-unit="<?=htmlspecialchars($un)?>" data-price="<?=(int)$r['price']?>"
							data-name="<?=htmlspecialchars(mb_strtolower($r['name_kz'].' '.$r['name_ru']))?>">
							<div class="ctg_num"><?=$number?></div>
							<div class="ctg_clr cr_ic"><i class="fal <?=($cr_raw ? 'fa-box' : 'fa-layer-group')?>"></i></div>
							<div class="ctg_name"><div class="ctg_name_kz"><?=htmlspecialchars($nm)?></div></div>
							<div class="ctg_cnt cr_pr"><?=((int)$r['price'] ? number_format((int)$r['price'], 0, '', ' ').' тг'.($un !== '' ? ' / '.htmlspecialchars($un) : '') : '<m>'.t::w('No price', 'Бағасы жоқ').'</m>')?></div>
							<div class="ctg_btns">
								<div class="ctg_b cr_edit" title="<?=t::w('Edit', 'Өзгерту')?>"><i class="fal fa-pen"></i></div>
							</div>
						</div>
					<? endwhile ?>
				</div>
			</div>
		<? else: ?>
			<div class="ds_nr">
				<i class="fal fa-ghost"></i>
				<p><?=t::w($cr['empty_key'], $cr['empty_kz'])?></p>
			</div>
		<? endif ?>

	</div>

<? include "../../block/footer.php"; ?>

	<div class="pop_bl pop_bl2 cr_block">
		<div class="pop_bl_a cr_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4 class="cr_title"><?=$TX['add_t']?></h4>
				<div class="btn btn_dd cr_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<form class="cr_form" autocomplete="off">
					<div class="form_c">
						<div class="form_im">
							<div class="form_span"><?=t::w('Name', 'Атауы')?>:</div>
							<input type="text" class="form_txt cr_name" maxlength="255" placeholder="<?=($cr_raw ? t::w('For example: Flour', 'Мысалы: Ұн') : t::w('For example: Cheese', 'Мысалы: Сыр'))?>">
							<i class="fal fa-text form_icon"></i>
						</div>
						<? if ($cr_unit): ?>
							<div class="form_im">
								<div class="form_span"><?=t::w('Unit', 'Өлшем бірлігі')?>:</div>
								<div class="cat_pick cr_units">
									<? foreach ($units as $u): ?>
										<button type="button" class="k_br_i cp_i<?=($u == $unit_def ? ' k_br_act' : '')?>" data-id="<?=$u?>"><span><?=$u?></span></button>
									<? endforeach ?>
								</div>
							</div>
						<? endif ?>
						<div class="form_im">
							<div class="form_span"><?=t::w('Price', 'Бағасы')?>:</div>
							<div class="cr_price_w">
								<input type="text" class="form_txt cr_price" inputmode="numeric" placeholder="0">
								<span class="cr_sfx">тг</span>
							</div>
							<div class="cr_hint"><?=$TX['per']?> <b class="cr_hint_u"><?=$unit_def?></b> <?=$TX['per2']?></div>
						</div>
					</div>
					<div class="form_c">
						<div class="form_im">
							<button type="submit" class="btn cr_save"><i class="far fa-check"></i><span class="cr_save_t"><?=$TX['add_b']?></span></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>

	<style>
		.cr_srch{ max-width: 320px; margin: 0 0 12px; }
		.cr_ic{ display: flex; align-items: center; justify-content: center; background: #eef3f8; color: #1f75a8; font-size: 16px; }
		.cr_row .ctg_cnt{ color: #1b1f27; font-weight: 600; }
		.cr_row .ctg_cnt m{ color: #c92a2a; font-weight: 500; }
		.cat_pick{ display: flex; flex-wrap: wrap; gap: 6px; padding-top: 6px; }
		.cat_pick .cp_i{ height: 40px; padding: 0 14px; border: 1px solid var(--bp-line, #e6e9ef); background: #fff; font: inherit; font-size: 14px; cursor: pointer; }
		.cat_pick .cp_i.k_br_act{ border-color: transparent; }
		.cr_price_w{ position: relative; }
		.cr_price_w .cr_price{ padding-right: 44px; }
		.cr_sfx{ position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #8a94a6; font-weight: 600; pointer-events: none; }
		.cr_hint{ margin-top: 6px; font-size: 13px; color: #8a94a6; }
		.cr_save{ width: 100%; border: 0; color: #fff; cursor: pointer; font: inherit; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--bp-green, #0EA768) !important; }
		.cr_save:hover{ background: var(--bp-green-h, #0c8f59) !important; }
		@media (max-width: 700px){ .cr_srch{ max-width: none; } }
	</style>
	<script>
		(function () {
			var K = <?=json_encode($cr['kind'])?>, DEF = <?=json_encode($unit_def, JSON_UNESCAPED_UNICODE)?>;
			var T = <?=json_encode($TX, JSON_UNESCAPED_UNICODE)?>;
			var pop = document.querySelector('.cr_block'), form = pop.querySelector('.cr_form'), units = pop.querySelector('.cr_units');
			var f = function (c) { return pop.querySelector(c); };
			var msg = function (t) { if (window.mess) mess(t); else alert(t); };
			var editId = 0, busy = false;

			function setUnit(u) {
				if (!units) return;
				var b = units.querySelector('.cp_i[data-id="' + u + '"]') || units.querySelector('.cp_i[data-id="' + DEF + '"]');
				units.querySelectorAll('.cp_i').forEach(function (x) { x.classList.toggle('k_br_act', x === b); });
				f('.cr_hint_u').textContent = b.dataset.id;
			}
			function curUnit() { var b = units && units.querySelector('.cp_i.k_br_act'); return b ? b.dataset.id : ''; }
			if (units) units.addEventListener('click', function (e) { var b = e.target.closest('.cp_i'); if (b) setUnit(b.dataset.id); });

			function open(row) {
				editId = row ? +row.dataset.id : 0;
				f('.cr_title').textContent = row ? T.upd_t : T.add_t;
				f('.cr_save_t').textContent = row ? T.save_b : T.add_b;
				f('.cr_name').value = row ? row.dataset.nm : '';
				f('.cr_price').value = row && +row.dataset.price ? row.dataset.price : '';
				setUnit(row && row.dataset.unit ? row.dataset.unit : DEF);
				pop.classList.add('pop_bl_act');
				setTimeout(function () { f('.cr_name').focus(); }, 50);
			}
			document.querySelectorAll('.cr_add_pop').forEach(function (b) { b.addEventListener('click', function () { open(null); }); });
			document.querySelectorAll('.cr_edit').forEach(function (b) { b.addEventListener('click', function () { open(b.closest('.cr_row')); }); });
			pop.querySelectorAll('.cr_back').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
			f('.cr_price').addEventListener('input', function () { this.value = this.value.replace(/\D+/g, ''); });

			form.addEventListener('submit', function (e) {
				e.preventDefault(); // Enter да осы жерге келеді
				if (busy) return;
				var name = f('.cr_name').value.trim();
				if (!name) { msg(T.name); f('.cr_name').focus(); return; }
				var fd = new FormData();
				fd.append('name', name);
				fd.append('price', f('.cr_price').value);
				if (units) fd.append('unit', curUnit());
				if (editId) fd.append('id', editId);
				busy = true;
				fetch('/products/get.php?' + K + (editId ? '_upd' : '_add'), { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.text(); })
					.then(function (t) {
						busy = false; t = t.trim();
						if (t == 'yes') {
							pop.classList.remove('pop_bl_act');
							msg(editId ? T.saved : T.added);
							setTimeout(function () { location.reload(); }, 500);
						} else if (t == 'exists') msg(T.exists);
						else msg(T.err);
					})
					.catch(function () { busy = false; msg(T.err); });
			});

			var s = document.querySelector('.cr_search');
			if (s) s.addEventListener('input', function () {
				var q = s.value.trim().toLowerCase();
				document.querySelectorAll('.cr_row').forEach(function (r) { r.style.display = !q || r.dataset.name.indexOf(q) > -1 ? '' : 'none'; });
			});
		})();
	</script>
