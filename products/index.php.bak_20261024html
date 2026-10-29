<? include "../config/core.php";

	//
	if (!$user_id) { header('location: /'); exit(); }


	// тағамдар каталог бойынша топталады, каталогтар өз ретімен (number)
	$catalogs = [];
	$cq = db::query("select * from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc, id asc");
	while ($c = mysqli_fetch_assoc($cq)) { $c['items'] = []; $catalogs[$c['id']] = $c; }
	$cat_list = $catalogs; // попаптағы каталог тізімі үшін
	$no_catalog = ['id' => 0, 'name_kz' => 'Каталогсыз', 'name_ru' => 'Каталогсыз', 'color' => '#b3bac7', 'items' => []];
	$product = db::query("select * from product where company_id = '$company' and arh = 0 order by COALESCE(NULLIF(TRIM(name_kz), ''), name_ru) asc, id asc");
	$page_result = mysqli_num_rows($product);
	while ($pr_d = mysqli_fetch_assoc($product)) {
		if ($pr_d['catalog_id'] && isset($catalogs[$pr_d['catalog_id']])) $catalogs[$pr_d['catalog_id']]['items'][] = $pr_d;
		else $no_catalog['items'][] = $pr_d;
	}
	if ($no_catalog['items']) $catalogs[0] = $no_catalog;


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'main';
	$page_name = 'Тағамдар';
	$page_key = 'Dishes';
	$css = ['products/main', 'products/catalog'];
	$js = ['products/main'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c">

		<div class="ucours_t">
			<div class="ucours_tl">
				<div class="ucours_tm">
					<div class="btn btn_cl product_add_pop">
						<i class="fal fa-plus"></i>
						<span><?=t::w('Add dish', 'Тағам қосу')?></span>
					</div>
				</div>
			</div>
		</div>

		<? include "aheader.php"; ?>

		<? if ($page_result): ?>

			<div class="ctg_c">
				<div class="pr_tools">
					<div class="form_im pr_srch">
						<input type="text" placeholder="<?=t::w('Search dish', 'Тағам іздеу')?>" class="form_txt pr_search">
						<i class="fal fa-search form_icon"></i>
					</div>
					<div class="k_br pr_cat_chips">
						<a class="k_br_i k_br_act" href="#" data-cat=""><span><?=t::w('All', 'Барлығы')?></span></a>
						<? foreach ($catalogs as $cat): if (!$cat['items']) continue; ?>
							<a class="k_br_i" href="#cat=<?=$cat['id']?>" data-cat="<?=$cat['id']?>"><span><?=htmlspecialchars($cat['name_'.$lang] ?: $cat['name_ru'])?></span><small class="pr_cat_cnt"><?=count($cat['items'])?></small></a>
						<? endforeach ?>
					</div>
				</div>

				<? $number = 0; foreach ($catalogs as $cat): if (!$cat['items']) continue; $clr = $cat['color'] ?: '#b3bac7'; ?>
					<div class="pr_grp" data-cat="<?=$cat['id']?>">
						<span class="pr_grp_clr" style="background:<?=htmlspecialchars($clr)?>"></span>
						<span class="pr_grp_name"><?=htmlspecialchars($cat['name_'.$lang] ?: $cat['name_ru'])?></span>
						<span class="pr_grp_cnt"><?=count($cat['items'])?></span>
					</div>
					<div class="ctg_list">
						<? foreach ($cat['items'] as $pr_d): $number++; ?>
							<div class="ctg_i pr_row" data-id="<?=$pr_d['id']?>" data-cat="<?=$cat['id']?>"
								data-name="<?=htmlspecialchars(mb_strtolower($pr_d['name_kz'].' '.$pr_d['name_ru']))?>"
								data-name-kz="<?=htmlspecialchars((string)$pr_d['name_kz'])?>"
								data-name-ru="<?=htmlspecialchars((string)$pr_d['name_ru'])?>"
								data-catalog="<?=(int)$pr_d['catalog_id']?>"
								data-price="<?=(int)$pr_d['price']?>">
								<div class="ctg_num"><?=$number?></div>
								<div class="ctg_clr" style="background:<?=htmlspecialchars($clr)?>"></div>
								<a class="ctg_name" href="item/?id=<?=$pr_d['id']?>">
									<div class="ctg_name_kz pr_nm_kz"><?=htmlspecialchars($pr_d['name_kz'] ?: $pr_d['name_ru'])?></div>
									<div class="ctg_name_ru pr_nm_ru"><?=($pr_d['name_ru'] && $pr_d['name_ru'] != $pr_d['name_kz'] ? htmlspecialchars($pr_d['name_ru']) : '')?></div>
								</a>
								<div class="ctg_cnt pr_pr"><?=((int)$pr_d['price'] ? number_format((int)$pr_d['price'], 0, '', ' ').' тг' : '<m>'.t::w('No price', 'Бағасы жоқ').'</m>')?></div>
								<div class="ctg_btns">
									<a class="ctg_b" href="item/?id=<?=$pr_d['id']?>" title="<?=t::w('Ingredients', 'Құрамы')?>"><i class="fal fa-layer-group"></i></a>
									<div class="ctg_b product2_add_pop" data-id="<?=$pr_d['id']?>" title="<?=t::w('Make copy', 'Көшірме жасау')?>"><i class="fal fa-clone"></i></div>
									<div class="ctg_b pr_edit" title="<?=t::w('Edit', 'Өзгерту')?>"><i class="fal fa-pen"></i></div>
									<div class="ctg_b ctg_b_red pr_btn_delete" data-id="<?=$pr_d['id']?>" title="<?=t::w('Send to archive', 'Архивке жіберу')?>"><i class="fal fa-archive"></i></div>
								</div>
							</div>
						<? endforeach ?>
					</div>
				<? endforeach ?>
				<div class="ds_nr pr_none" style="display:none"><i class="fal fa-search"></i><p><?=t::w('Nothing found', 'Ештеңе табылмады')?></p></div>
			</div>

		<? else: ?> <div class="ds_nr"><i class="fal fa-ghost"></i><p><?=t::w('No dishes yet', 'Тағам әлі жоқ')?></p></div> <? endif ?>

	</div>

	<!-- тағамды өзгерту (каталог попапының стилімен) -->
	<div class="pop_bl pop_bl2 pre_block">
		<div class="pop_bl_a pre_back"></div>
		<div class="pop_bl_c">
			<div class="head_c">
				<h4><?=t::w('Edit dish', 'Тағамды өзгерту')?></h4>
				<div class="btn btn_dd pre_back"><i class="fal fa-times"></i></div>
			</div>
			<div class="pop_bl_cl">
				<div class="form_c">
					<div class="form_im">
						<div class="form_span"><?=t::w('Name (kazakh)', 'Атауы (қазақша)')?>:</div>
						<input type="text" class="form_txt pre_kz" maxlength="255">
						<i class="fal fa-text form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Name (russian)', 'Атауы (орысша)')?>:</div>
						<input type="text" class="form_txt pre_ru" maxlength="255" placeholder="<?=t::w('If empty, kazakh name is used', 'Бос қалса, қазақшасы жазылады')?>">
						<i class="fal fa-text form_icon"></i>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Catalog', 'Каталог')?>:</div>
						<input type="hidden" class="pre_cat" value="0">
						<div class="cat_pick">
							<button type="button" class="k_br_i cp_i" data-id="0"><span class="cp_sq" style="background:#b3bac7"></span><span><?=t::w('No catalog', 'Каталогсыз')?></span></button>
							<? foreach ($cat_list as $c): ?>
								<button type="button" class="k_br_i cp_i" data-id="<?=$c['id']?>"><span class="cp_sq" style="background:<?=htmlspecialchars($c['color'] ?: '#b3bac7')?>"></span><span><?=htmlspecialchars($c['name_'.$lang] ?: $c['name_ru'])?></span></button>
							<? endforeach ?>
						</div>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Price', 'Бағасы')?>, тг:</div>
						<input type="text" class="form_txt pre_price" inputmode="numeric" placeholder="0">
						<i class="fal fa-tenge form_icon"></i>
					</div>
				</div>
				<div class="form_c">
					<div class="form_im">
						<div class="btn pre_save"><i class="far fa-check"></i><span><?=t::w('Save', 'Сақтау')?></span></div>
					</div>
				</div>
			</div>
		</div>
	</div>

<? include "../block/footer.php"; ?>

	<? include "pop_add.php"; ?>

	<style>
		.pr_tools{ display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 14px; }
		.pr_srch{ flex: 0 1 280px; margin: 0; }
		.pr_cat_cnt{ font-size: 12px; font-weight: 600; opacity: .55; }
		.pr_grp{ margin-top: 16px; }
		.ctg_c .ctg_list{ margin-top: 8px; }
		.pr_row .ctg_name{ color: inherit; }
		.pr_row .ctg_cnt{ color: #1b1f27; font-weight: 600; }
		.pr_row .ctg_cnt m{ color: #c92a2a; font-weight: 500; }
		.pre_save{ background: var(--bp-green, #0EA768) !important; }
		.pre_save:hover{ background: var(--bp-green-h, #0c8f59) !important; }
		.cat_pick{ display: flex; flex-wrap: wrap; gap: 6px; padding-top: 6px; }
		.cat_pick .cp_i{ height: 40px; padding: 0 12px; border: 1px solid var(--bp-line, #e6e9ef); background: #fff; font: inherit; font-size: 14px; cursor: pointer; }
		.cat_pick .cp_i.k_br_act{ border-color: transparent; }
		.cp_sq{ width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; }
		@media (max-width: 768px){
			.pr_srch{ flex: 1 1 100%; }
			.pr_cat_chips{ flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
			.pr_cat_chips .k_br_i{ flex: 0 0 auto; white-space: nowrap; }
		}
		@media (max-width: 700px){
			.pr_row .ctg_cnt{ display: block; font-size: 13px; }
			.pr_row .product2_add_pop{ display: none; }
		}
	</style>
	<script>
		(function () {
			var T = <?=json_encode([
				'name' => t::w('Enter name', 'Атауын жазыңыз'),
				'saved' => t::w('Saved', 'Сақталды'),
				'err' => t::w('Error, try again', 'Қате, қайта көріңіз'),
			], JSON_UNESCAPED_UNICODE)?>;
			var msg = function (t) { if (window.mess) mess(t); else alert(t); };

			// каталог чиптері + іздеу (қайта жүктемей)
			var chips = document.querySelectorAll('.pr_cat_chips .k_br_i'), inp = document.querySelector('.pr_search');
			var cur = (location.hash.match(/cat=(\d+)/) || [])[1] || '';
			if (cur && !document.querySelector('.pr_cat_chips [data-cat="' + cur + '"]')) cur = '';
			function apply() {
				var q = inp ? inp.value.trim().toLowerCase() : '', shown = {}, any = false;
				chips.forEach(function (c) { c.classList.toggle('k_br_act', c.dataset.cat === cur); });
				document.querySelectorAll('.pr_row').forEach(function (r) {
					var ok = (!cur || r.dataset.cat === cur) && (!q || r.dataset.name.indexOf(q) > -1);
					r.style.display = ok ? '' : 'none';
					if (ok) { shown[r.dataset.cat] = 1; any = true; }
				});
				document.querySelectorAll('.pr_grp[data-cat]').forEach(function (g) { g.style.display = shown[g.dataset.cat] ? '' : 'none'; });
				var none = document.querySelector('.pr_none'); if (none) none.style.display = any ? 'none' : '';
			}
			chips.forEach(function (c) {
				c.addEventListener('click', function (e) {
					e.preventDefault();
					cur = c.dataset.cat;
					history.replaceState(null, '', cur ? '#cat=' + cur : location.pathname + location.search);
					apply();
				});
			});
			if (inp) inp.addEventListener('input', apply);
			if (chips.length) apply();

			// каталог таңдағыш (чиптер, мәні жасырын input-та)
			function pick(box, id) {
				var b = box.querySelector('.cp_i[data-id="' + id + '"]') || box.querySelector('.cp_i[data-id="0"]');
				box.querySelectorAll('.cp_i').forEach(function (x) { x.classList.toggle('k_br_act', x === b); });
				box.previousElementSibling.value = b.dataset.id;
			}
			document.querySelectorAll('.cat_pick').forEach(function (box) {
				box.addEventListener('click', function (e) { var b = e.target.closest('.cp_i'); if (b) pick(box, b.dataset.id); });
			});

			// ✎ → попап бірден ашылады
			var pop = document.querySelector('.pre_block'), row = null, busy = false;
			var f = function (c) { return pop.querySelector(c); };
			document.querySelectorAll('.pr_edit').forEach(function (b) {
				b.addEventListener('click', function () {
					row = b.closest('.pr_row');
					f('.pre_kz').value = row.dataset.nameKz;
					f('.pre_ru').value = row.dataset.nameRu;
					pick(pop.querySelector('.cat_pick'), row.dataset.catalog);
					f('.pre_price').value = row.dataset.price;
					pop.classList.add('pop_bl_act');
					f('.pre_kz').focus();
				});
			});
			pop.querySelectorAll('.pre_back').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
			f('.pre_price').addEventListener('input', function () { this.value = this.value.replace(/\D+/g, ''); });
			f('.pre_save').addEventListener('click', function () {
				if (busy || !row) return;
				if (!f('.pre_kz').value.trim() && !f('.pre_ru').value.trim()) { msg(T.name); return; }
				var fd = new FormData();
				fd.append('card', 1);
				fd.append('id', row.dataset.id);
				fd.append('name_kz', f('.pre_kz').value);
				fd.append('name_ru', f('.pre_ru').value);
				fd.append('catalog', f('.pre_cat').value);
				fd.append('price', f('.pre_price').value);
				busy = true;
				fetch('/products/item/get.php?pr_upd', { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.text(); })
					.then(function (t) {
						busy = false;
						if (t.trim() != 'yes') { msg(T.err); return; }
						msg(T.saved);
						pop.classList.remove('pop_bl_act');
						// каталог ауысса, топтау өзгереді — бетті жаңартамыз
						if (f('.pre_cat').value != row.dataset.catalog) { setTimeout(function () { location.reload(); }, 400); return; }
						var kz = f('.pre_kz').value.trim(), ru = f('.pre_ru').value.trim();
						if (!kz) kz = ru; if (!ru) ru = kz;
						var p = parseInt(f('.pre_price').value || '0', 10);
						row.dataset.nameKz = kz; row.dataset.nameRu = ru; row.dataset.price = p;
						row.dataset.name = (kz + ' ' + ru).toLowerCase();
						row.querySelector('.pr_nm_kz').textContent = kz;
						row.querySelector('.pr_nm_ru').textContent = ru != kz ? ru : '';
						row.querySelector('.pr_pr').textContent = p ? p.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' тг' : '—';
					})
					.catch(function () { busy = false; msg(T.err); });
			});
		})();
	</script>
