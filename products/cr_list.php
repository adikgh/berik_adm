<?
	// Құрамы (product_comp) және Шикізат (product_raw) беттеріне ортақ тізім.
	// Шақыратын бет $cr береді: kind (comp|raw), table, add_key, add_kz, empty_key, empty_kz.
	if (!$user_id) { header('location: /'); exit(); }

	$cmp = (int)$company;
	$cr_raw = $cr['kind'] == 'raw';
	$cr_unit = fun::has_col($cr['table'], 'unit'); // product_comp-та unit жоқ
	$units = fun::$units; // кг, г, л, мл, шт
	$unit_def = $cr_raw ? 'кг' : 'шт';
	$list = db::query("select * from {$cr['table']} where company_id = '$cmp' order by name_kz asc, id asc");
	$page_result = $list ? mysqli_num_rows($list) : 0;

	// Құрамдар бетінде: топтау (дайындама / сет ішіндегі тағам / өнім), қайда қолданылады, мәзірдегі аттас тағам
	$cr_rows = []; $cr_meta = [];
	if ($list) while ($r = mysqli_fetch_assoc($list)) $cr_rows[] = $r;
	if (!$cr_raw && $cr_rows) {
		$has_parts = ($tq = db::query("show tables like 'product_comp_part'")) && mysqli_num_rows($tq) > 0;
		$has_set = ($tq = db::query("show tables like 'product_set_item'")) && mysqli_num_rows($tq) > 0;
		$prep = []; $use = [];
		if ($has_parts) {
			$q = db::query("select distinct cp.comp_id from product_comp_part cp join product_comp c on c.id = cp.comp_id where c.company_id = '$cmp'");
			while ($x = mysqli_fetch_assoc($q)) $prep[$x['comp_id']] = 1;
			$q = db::query("select cp.sub_comp_id id, count(*) n from product_comp_part cp join product_comp c on c.id = cp.comp_id where c.company_id = '$cmp' and cp.sub_comp_id > 0 group by cp.sub_comp_id");
			while ($x = mysqli_fetch_assoc($q)) $use[$x['id']] = ($use[$x['id']] ?? 0) + $x['n'];
		}
		$q = db::query("select pi.comp_id id, count(distinct pi.product_id) n from product_item pi join product p on p.id = pi.product_id where p.company_id = '$cmp' and p.arh = 0 and pi.comp_id > 0 group by pi.comp_id");
		while ($x = mysqli_fetch_assoc($q)) $use[$x['id']] = ($use[$x['id']] ?? 0) + $x['n'];
		if ($has_set) {
			$q = db::query("select si.comp_id id, count(distinct si.set_id) n from product_set_item si join product p on p.id = si.set_id where p.company_id = '$cmp' and p.arh = 0 and si.comp_id > 0 group by si.comp_id");
			while ($x = mysqli_fetch_assoc($q)) $use[$x['id']] = ($use[$x['id']] ?? 0) + $x['n'];
		}
		$menu = [];
		$q = db::query("select id, name_kz, name_ru from product where company_id = '$cmp' and arh = 0");
		while ($x = mysqli_fetch_assoc($q)) foreach ([$x['name_kz'], $x['name_ru']] as $nm) { $k = mb_strtolower(trim(strip_tags((string)$nm))); if ($k !== '' && !isset($menu[$k])) $menu[$k] = (int)$x['id']; }
		foreach ($cr_rows as $r) {
			$id = $r['id'];
			$g = isset($prep[$id]) ? (($r['kind'] ?? '') == 'dish' ? 'dish' : 'prep') : 'item';
			$mp = 0; foreach ([$r['name_ru'], $r['name_kz']] as $nm) { $k = mb_strtolower(trim((string)$nm)); if ($k !== '' && isset($menu[$k])) { $mp = $menu[$k]; break; } }
			$cr_meta[$id] = ['g' => $g, 'prep' => isset($prep[$id]), 'use' => $use[$id] ?? 0, 'menu' => $mp];
		}
	}
	$cr_groups = $cr_raw || !$cr_meta ? ['all' => ['', '']] : [
		'prep' => ['Готовкалар', 'Өзіміз дайындайтын заттар: соус, суши рисі, ет. Басса, рецепті ашылады.'],
		'dish' => ['Сет ішіндегі тағамдар', 'Сеттің ішіне кіретін роллдар, пицца т.б. Басса, рецепті ашылады.'],
		'item' => ['Құрам заттары', 'Тағамға кететін заттар: күнжіт, ұн, колбаса, нори, қорап. Бағасы шикізаттан (тауардан) алынады. Басса, өзгертесіз.'],
	];

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
				<? $number = 0; foreach ($cr_groups as $gk => $gd): ?>
				<? if ($gd[0]): $gn = 0; foreach ($cr_rows as $r) if ($cr_meta[$r['id']]['g'] == $gk) $gn++; if (!$gn) continue; ?>
					<div class="cr_grp" data-g="<?=$gk?>"><b><?=$gd[0]?></b> <small><?=$gn?></small><span><?=$gd[1]?></span></div>
				<? endif ?>
				<div class="ctg_list">
					<? foreach ($cr_rows as $r): $m = $cr_meta[$r['id']] ?? null; if ($gd[0] && $m['g'] != $gk) continue; $number++; $nm = $r['name_kz'] ?: $r['name_ru']; $un = $cr_unit ? fun::unit_norm($r['unit']) : ''; ?>
						<div class="ctg_i cr_row" data-id="<?=$r['id']?>" data-nm="<?=htmlspecialchars($nm)?>" data-unit="<?=htmlspecialchars($un)?>" data-price="<?=(int)$r['price']?>"<?=(!empty($m['prep']) ? ' data-prep="1"' : '')?>
							data-name="<?=htmlspecialchars(mb_strtolower($r['name_kz'].' '.$r['name_ru']))?>">
							<div class="ctg_num"><?=$number?></div>
							<div class="ctg_clr cr_ic<?=(!empty($m['prep']) ? ' cr_ic_p' : '')?>"><i class="fal <?=($cr_raw ? 'fa-box' : (!empty($m['prep']) ? 'fa-layer-group' : 'fa-cube'))?>"></i></div>
							<div class="ctg_name">
								<div class="ctg_name_kz"><?=htmlspecialchars($nm)?></div>
								<? if ($m): ?><div class="cr_sub">
									<?=($m['use'] ? $m['use'].' жерде қолданылады' : '<span class="cr_off">ешқайда қолданылмайды</span>')?>
									<? if ($m['g'] == 'dish'): ?> · <?=($m['menu'] ? '<a class="cr_menu" href="/products/item/?id='.$m['menu'].'">мәзірде бар, ашу</a>' : 'мәзірде бөлек жоқ, тек сет ішінде')?><? endif ?>
								</div><? endif ?>
							</div>
							<div class="ctg_cnt cr_pr"><?=((int)$r['price'] ? number_format((int)$r['price'], 0, '', ' ').' тг'.($un !== '' ? ' / '.htmlspecialchars($un) : '') : '<m>'.t::w('No price', 'Бағасы жоқ').'</m>')?></div>
							<div class="ctg_btns">
								<? if (!empty($m['prep'])): ?><a class="ctg_b" href="/products/cost/?prep=<?=$r['id']?>#prep" title="Рецептін ашу"><i class="fal fa-layer-group"></i></a><? endif ?>
								<div class="ctg_b cr_edit" title="<?=t::w('Edit', 'Өзгерту')?>"><i class="fal fa-pen"></i></div>
							</div>
						</div>
					<? endforeach ?>
				</div>
				<? endforeach ?>
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
		.cr_row{ cursor: pointer; }
		.cr_row:hover{ box-shadow: 0 0 0 2px #d6efe3; }
		.cr_ic_p{ background: #e3f5ec !important; color: var(--bp-green, #0EA768) !important; }
		.cr_sub{ font-size: 12px; color: #8a94a6; margin-top: 2px; }
		.cr_sub .cr_off{ color: #b0b6c0; }
		.cr_menu{ color: var(--bp-blue, #1f75a8); }
		.cr_grp{ display: flex; flex-wrap: wrap; align-items: baseline; gap: 8px; margin: 18px 4px 8px; }
		.cr_grp b{ font-size: 16px; } .cr_grp small{ font-size: 12px; font-weight: 600; color: #8a94a6; }
		.cr_grp span{ flex-basis: 100%; font-size: 13px; color: #8a94a6; }
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
				units.querySelectorAll('.cp_x').forEach(function (x) { if (x.dataset.id !== u) x.remove(); });
				var b = Array.prototype.find.call(units.querySelectorAll('.cp_i'), function (x) { return x.dataset.id === u; });
				// базада белгісіз өлшем болса, кг-ға ауыстырмай, қосымша чип етіп көрсетеміз
				if (!b && u) { b = document.createElement('button'); b.type = 'button'; b.className = 'k_br_i cp_i cp_x'; b.dataset.id = u; b.appendChild(document.createElement('span')).textContent = u; units.appendChild(b); }
				if (!b) b = units.querySelector('.cp_i[data-id="' + DEF + '"]');
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
			document.querySelectorAll('.cr_edit').forEach(function (b) { b.addEventListener('click', function (e) { e.stopPropagation(); open(b.closest('.cr_row')); }); });
			// жолдың кез келген жерін басса: рецепті бар болса рецепті, болмаса өзгерту попапы ашылады
			document.querySelectorAll('.cr_row').forEach(function (r) {
				r.addEventListener('click', function (e) {
					if (e.target.closest('a, .ctg_b') || (window.getSelection && String(window.getSelection()))) return;
					if (r.dataset.prep) location.href = '/products/cost/?prep=' + r.dataset.id + '#prep'; else open(r);
				});
			});
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
				document.querySelectorAll('.cr_grp').forEach(function (g) { var l = g.nextElementSibling, any = false; l.querySelectorAll('.cr_row').forEach(function (r) { if (r.style.display != 'none') any = true; }); g.style.display = l.style.display = any ? '' : 'none'; });
			});
			// ?q=атауы: тағам картасынан келгенде тізім сүзіліп ашылады
			var qm = location.search.match(/[?&]q=([^&]*)/);
			if (s && qm) { s.value = decodeURIComponent(qm[1]); s.dispatchEvent(new Event('input')); }
		})();
	</script>
