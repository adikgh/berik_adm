<? include "../../config/core.php";

	// 
	if (!$user_id) { header('location: /'); exit(); }

	// product
	if (intval(@$_GET['id'])) {
		$product_id = intval($_GET['id']);
		$product = db::query("select * from product where id = '$product_id' and company_id = '$company'");
		if (mysqli_num_rows($product)) {
			$product_d = mysqli_fetch_assoc($product);

			// catalog
			$catalog_id = $product_d['catalog_id'];
			$catalog_d = product::pr_catalog($product_d['catalog_id']);
			
		} else { header('location: /products/'); exit(); }
	} else { header('location: /products/'); exit(); }


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'main';
	$page_name = fun::name_text($product_d['name_kz'] ?: $product_d['name_ru']);
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['atops'] = true;
	$site_set['footer'] = false;
	$site_set['utop_bk'] = 'products';
	$site_set['utop_type'] = 'item';
	$site_set['utop_nm'] = $page_name;
	$css = ['products/main', 'products/item', 'products/photo'];
	$js = ['products/main', 'products/item', 'name_ed', 'products/photo'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c it_page" data-ajx-nav=".it_page">

		<? include "../aheader.php"; ?>

		<? // сет пе: ішінде басқа тағамдар бар (product_set_item). Сетте «Тағам құрамы» мен граммовка ескертуі көрсетілмейді.
			$is_set = false;
			$stq = db::query("show tables like 'product_set_item'");
			if ($stq && mysqli_num_rows($stq)) { $stq = db::query("select id from product_set_item where set_id = '$product_id' limit 1"); $is_set = $stq && mysqli_num_rows($stq) > 0; }
		?>
		<? // граммовкасы жоқ болса — ескерту (толтырылғанда өзі жоғалады)
			if (!class_exists('wb')) @include_once "../../waybill/lib.php";
			$nc_st = class_exists('wb') ? (wb::comp_check($company)['dish'][$product_id] ?? '') : '';
			if ($nc_st && !$is_set): ?>
			<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:6px 0 12px;padding:12px 16px;border-radius:12px;background:<?=($nc_st == 'none' ? '#fdecec' : '#fff8e6')?>;color:<?=($nc_st == 'none' ? '#c0392b' : '#8a6100')?>;font-weight:700;font-size:14px">
				<i class="fal fa-exclamation-triangle"></i>
				<span><?=($nc_st == 'none' ? 'Граммовка жоқ: бұл тағам сатылғанда шикізат азаймайды. Құрамын төменде толтырыңыз.' : 'Граммовка толық емес: құрамындағы кейбір дайындама шикізатқа байланбаған (Себес → Дайындамалар).')?></span>
			</div>
		<? endif ?>

		<div class="pitem">

			<? $pc_cats = db::query("select id, name_kz, name_ru, color from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc, id asc"); ?>
			<div class="pcard" data-id="<?=$product_id?>">
				<div class="pcard_top">
					<div class="pcard_clr pph<?=(!empty($product_d['img']) ? ' pph_on' : '')?>" data-id="<?=$product_id?>" title="<?=t::w('Click to add photo', 'Басып фото қосыңыз')?>" style="background:<?=htmlspecialchars(($catalog_d['color'] ?? '') ?: '#b3bac7')?>"><? if (!empty($product_d['img'])): ?><img class="pph_img" src="<?=(preg_match('~^https?://~', $product_d['img']) ? htmlspecialchars($product_d['img']) : '/assets/uploads/products/'.rawurlencode($product_d['img']))?>" alt="" onerror="this.parentNode.classList.remove('pph_on');this.remove()"><? endif ?><i class="fal fa-camera pph_def"></i><span class="pph_ic"><i class="fal fa-camera"></i></span><span class="pph_del" title="<?=t::w('Delete photo', 'Фотоны өшіру')?>"><i class="fal fa-trash-alt"></i></span></div>
					<div class="pcard_ttl">
						<div class="pcard_name"><?=fun::name_html($product_d['name_kz'] ?: $product_d['name_ru'])?></div>
						<div class="pcard_sub">
							<span><i class="fal fa-folder"></i> <?=($catalog_d ? htmlspecialchars($catalog_d['name_'.$lang] ?: $catalog_d['name_ru']) : t::w('No catalog', 'Каталогсыз'))?></span>
							<span class="pcard_price"><?=number_format((int)$product_d['price'], 0, '', ' ')?> тг</span>
							<? if ($product_d['arh']): ?><span class="pcard_arh"><i class="fal fa-archive"></i> <?=t::w('In archive', 'Архивте')?></span><? endif ?>
						</div>
					</div>
				</div>
				<div class="pcard_form">
					<div class="pcard_f">
						<span class="pcard_l"><?=t::w('Name (kazakh)', 'Атауы (қазақша)')?></span>
						<div class="nm_ed">
							<div class="nm_tb"><button type="button" class="nm_red"><?=t::w('Bold red', 'Қалың қызыл')?></button><button type="button" class="nm_clr"><?=t::w('Clear', 'Тазалау')?></button></div>
							<div class="nm_ce" contenteditable="true"><?=fun::name_html($product_d['name_kz'])?></div>
							<input type="hidden" class="pc_name_kz" value="<?=htmlspecialchars(fun::name_save($product_d['name_kz']))?>">
						</div>
					</div>
					<div class="pcard_f">
						<span class="pcard_l"><?=t::w('Name (russian)', 'Атауы (орысша)')?></span>
						<div class="nm_ed">
							<div class="nm_tb"><button type="button" class="nm_red"><?=t::w('Bold red', 'Қалың қызыл')?></button><button type="button" class="nm_clr"><?=t::w('Clear', 'Тазалау')?></button></div>
							<div class="nm_ce" contenteditable="true" data-ph="<?=t::w('If empty, kazakh name is used', 'Бос қалса, қазақшасы жазылады')?>"><?=fun::name_html($product_d['name_ru'])?></div>
							<input type="hidden" class="pc_name_ru" value="<?=htmlspecialchars(fun::name_save($product_d['name_ru']))?>">
						</div>
					</div>
					<div class="pcard_f pcard_f_wide">
						<span class="pcard_l"><?=t::w('Catalog', 'Каталог')?></span>
						<? $pc_sel = 0; $pc_html = ''; while ($pc = mysqli_fetch_assoc($pc_cats)) {
							$on = $pc['id'] == $product_d['catalog_id']; if ($on) $pc_sel = (int)$pc['id'];
							$pc_html .= '<button type="button" class="k_br_i cp_i'.($on ? ' k_br_act' : '').'" data-id="'.$pc['id'].'"><span class="cp_sq" style="background:'.htmlspecialchars($pc['color'] ?: '#b3bac7').'"></span><span>'.htmlspecialchars($pc['name_'.$lang] ?: $pc['name_ru']).'</span></button>';
						} ?>
						<input type="hidden" class="pc_catalog" value="<?=$pc_sel?>">
						<div class="cat_pick">
							<button type="button" class="k_br_i cp_i<?=(!$pc_sel ? ' k_br_act' : '')?>" data-id="0"><span class="cp_sq" style="background:#b3bac7"></span><span><?=t::w('No catalog', 'Каталогсыз')?></span></button>
							<?=$pc_html?>
						</div>
					</div>
					<label class="pcard_f">
						<span class="pcard_l"><?=t::w('Price', 'Бағасы')?>, тг</span>
						<input type="text" class="pcard_in pc_price" inputmode="numeric" value="<?=(int)$product_d['price']?>">
					</label>
					<? if (fun::has_col('product', 'description')): ?>
						<label class="pcard_f pcard_f_wide">
							<span class="pcard_l"><?=t::w('Description', 'Сипаттамасы')?></span>
							<textarea class="pcard_in pcard_ta pc_desc" rows="3" maxlength="1000" placeholder="<?=t::w('Description hint', 'Мысалы: тауық еті, сыр, помидор, соус. Кассада тағамды ұзақ басқанда көрінеді.')?>"><?=htmlspecialchars((string)$product_d['description'])?></textarea>
						</label>
					<? endif ?>
				</div>
				<div class="pcard_btns">
					<div class="btn pc_save"><i class="far fa-check"></i><span><?=t::w('Save', 'Сақтау')?></span></div>
					<? if (!$product_d['arh']): ?><div class="btn btn_cl pit_del pr_delete" data-id="<?=$product_id?>"><i class="fal fa-archive"></i><span><?=t::w('Send to archive', 'Архивке жіберу')?></span></div><? endif ?>
				</div>
			</div>


			<?
				// құрам: тек осы компанияның product_comp жазбалары (product_item.comp_id → product_comp.id)
				$cu = fun::has_col('product_comp', 'unit');
				$comps = [];
				$cq = db::query("select id, name_kz, name_ru, price".($cu ? ", unit" : "")." from product_comp where company_id = '$company' order by name_kz asc, id asc");
				while ($c = mysqli_fetch_assoc($cq)) {
					$u = fun::unit_norm($c['unit'] ?? '');
					// кг/л бағасы 1 кг/л үшін, ал мөлшер г/мл-мен жазылады (÷1000)
					$c['qu'] = ['кг' => 'г', 'л' => 'мл'][$u] ?? ($u !== '' ? $u : 'шт');
					$c['div'] = in_array($u, ['кг', 'л']) ? 1000 : 1;
					$c['pu'] = $u !== '' ? $u : 'шт';
					$c['nm'] = $c['name_kz'] ?: $c['name_ru'];
					$comps[$c['id']] = $c;
				}
				// рецепті бар құрамдар (дайындама): атауын басқанда Себес бетінде рецепті ашылады
				$ci_prep = [];
				$tq = db::query("show tables like 'product_comp_part'");
				if ($tq && mysqli_num_rows($tq)) { $pq = db::query("select distinct cp.comp_id from product_comp_part cp join product_comp c on c.id = cp.comp_id where c.company_id = '$company'"); while ($x = mysqli_fetch_assoc($pq)) $ci_prep[$x['comp_id']] = 1; }
				$ci = db::query("select * from product_item where product_id = '$product_id' and comp_id > 0 order by ins_dt asc, id asc");
				$ci_rows = []; $ci_cost = 0;
				while ($r = mysqli_fetch_assoc($ci)) {
					$c = $comps[$r['comp_id']] ?? null;
					$r['c'] = $c;
					$r['cost'] = $c ? round((float)$r['quantity'] * (int)$c['price'] / $c['div']) : 0;
					$ci_cost += $r['cost'];
					$ci_rows[] = $r;
				}
				$d_price = (int)$product_d['price'];
				$profit = $d_price - $ci_cost;
				$fmt = function ($n) { return number_format((int)$n, 0, '', ' '); };
				// 0.500 → 0,5; 2.000 → 2
				$qfmt = function ($q) { return str_replace('.', ',', rtrim(rtrim(number_format((float)$q, 3, '.', ''), '0'), '.')); };
			?>
			<style>.ci_lnk{ color: inherit; } .ci_lnk:hover{ color: var(--bp-blue, #1f75a8); text-decoration: underline; } .ci_lnk i{ font-size: 12px; color: var(--bp-blue, #1f75a8); margin-left: 4px; }</style>
			<? if (!$is_set || $ci_rows): // сетте тағам құрамы жасырылады (бұрын жазылған болса ғана көрінеді) ?>
			<div class="item_c ci_c">
				<div class="ci_head">
					<div class="item_is"><?=t::w('Dish composition', 'Тағам құрамы')?></div>
					<? if ($ci_rows): ?>
						<div class="ci_sum">
							<span><?=t::w('Cost', 'Өзіндік құны')?>: <b><?=$fmt($ci_cost)?> тг</b></span>
							<span><?=t::w('Price', 'Бағасы')?>: <b><?=$fmt($d_price)?> тг</b></span>
							<span class="<?=($profit >= 0 ? 'ci_pos' : 'ci_neg')?>"><?=t::w('Profit', 'Пайда')?>: <b><?=$fmt($profit)?> тг<?=($d_price ? ' ('.round($profit * 100 / $d_price).'%)' : '')?></b></span>
						</div>
					<? endif ?>
				</div>

				<? if ($ci_rows): ?>
					<div class="ci_tbl">
						<div class="ci_tr ci_th">
							<div>#</div><div><?=t::w('Name', 'Атауы')?></div><div><?=t::w('Quantity', 'Саны')?></div><div><?=t::w('Cost', 'Өзіндік құны')?></div><div></div>
						</div>
						<? foreach ($ci_rows as $i => $r): $c = $r['c']; ?>
							<div class="ci_tr" data-id="<?=$r['id']?>" data-comp="<?=(int)$r['comp_id']?>" data-qty="<?=$qfmt($r['quantity'])?>">
								<div class="ci_n"><?=$i + 1?></div>
								<div class="ci_nm"><?=($c ? '<a class="ci_lnk" href="'.(isset($ci_prep[$c['id']]) ? '/products/cost/?prep='.(int)$c['id'].'#prep' : '/products/comp/?q='.rawurlencode($c['nm'])).'" title="'.(isset($ci_prep[$c['id']]) ? 'Рецептін ашу' : 'Бағасын ашу').'">'.htmlspecialchars($c['nm']).(isset($ci_prep[$c['id']]) ? ' <i class="fal fa-layer-group"></i>' : '').'</a>' : '<m>'.t::w('Deleted item', 'Өшірілген құрам').'</m>')?>
									<? if ($c): ?><small><?=$fmt($c['price'])?> тг / <?=htmlspecialchars($c['pu'])?></small><? endif ?></div>
								<div class="ci_q"><b><?=$qfmt($r['quantity'])?></b> <?=htmlspecialchars($c['qu'] ?? 'шт')?></div>
								<div class="ci_cost"><?=$fmt($r['cost'])?> тг</div>
								<div class="ci_btns">
									<? if ($c): ?><div class="ctg_b ci_edit" title="<?=t::w('Edit', 'Өзгерту')?>"><i class="fal fa-pen"></i></div><? endif ?>
									<div class="ctg_b ctg_b_red ci_del" title="<?=t::w('Remove', 'Алып тастау')?>"><i class="fal fa-times"></i></div>
								</div>
							</div>
						<? endforeach ?>
						<div class="ci_tr ci_tf">
							<div></div><div><?=t::w('Total cost', 'Барлығы өзіндік құны')?>:</div><div></div><div><b><?=$fmt($ci_cost)?> тг</b></div><div></div>
						</div>
					</div>
					<div class="ci_hint"><i class="fal fa-info-circle"></i> <?=t::w('cost hint', 'Өзіндік құны = саны × құрамның бағасы. Бағасы 1 кг (1 л) үшін жазылса, мөлшері граммен (мл) жазылады және 1000-ға бөлінеді.')?></div>
					<div class="ci_add_w"><div class="btn ci_add_pop"><i class="far fa-plus"></i><span><?=t::w('Add to composition', 'Құрамға қосу')?></span></div></div>
				<? else: ?>
					<div class="ci_empty">
						<i class="fal fa-layer-group"></i>
						<p><?=t::w('composition empty', 'Тағамның құрамын қосыңыз: қандай өнім, қанша мөлшерде кетеді. Сонда өзіндік құны мен пайда автоматты есептеледі.')?></p>
						<div class="btn ci_add_pop"><i class="far fa-plus"></i><span><?=t::w('Add to composition', 'Құрамға қосу')?></span></div>
					</div>
				<? endif ?>
			</div>

			<? endif ?>
			<?
				// Сет құрамы (product_set_item): тағамдар + бөлек заттар (қорап, сашэ). Себес бетімен бірдей есептеледі.
				$si_on = false;
				if (!class_exists('cost')) include "../cost/lib.php";
				if (cost::ready()) {
					$si_comps = cost::comps($company);
					$si_dishes = cost::dishes($company, $si_comps);
					$si_rows = []; $si_cost = 0;
					$q = db::query("select si.*, d.name_ru d_ru, d.name_kz d_kz from product_set_item si left join product d on d.id = si.product_id where si.set_id = '$product_id' order by si.id");
					while ($r = mysqli_fetch_assoc($q)) {
						if ($r['product_id']) { $u = $si_dishes[$r['product_id']] ?? 0; $r['nm'] = strip_tags((string)($r['d_ru'] ?: $r['d_kz'])); $r['unit_c'] = $u; }
						else { $c = $si_comps[$r['comp_id']] ?? null; $u = $c ? $c['cost'] : 0; $r['nm'] = $c ? $c['name_ru'] : '?'; $r['unit_c'] = $u; }
						$r['cost'] = $u * $r['quantity'];
						$si_cost += $r['cost'];
						$si_rows[] = $r;
					}
					// блок сет құрамы бар тағамда, не тағам құрамы бос тағамда көрінеді
					$si_on = $si_rows || !$ci_rows;
					if ($si_on) {
						$si_st = cost::setting($company); $si_over = cost::overhead($si_st);
						$si_full = $si_cost + ($si_rows ? $si_over : 0);
						$si_profit = $d_price - $si_full;
						$si_opts = db::query("select id, name_ru, name_kz from product where company_id = '$company' and arh = 0 and id <> '$product_id' order by name_ru");
					}
				}
			?>
			<? if ($si_on): ?>
			<div class="item_c ci_c si_c">
				<div class="ci_head">
					<div class="item_is">Сет құрамы</div>
					<? if ($si_rows): ?>
						<div class="ci_sum">
							<span>Тағамдар: <b><?=$fmt($si_cost)?> тг</b></span>
							<span>Толық себес: <b><?=$fmt($si_full)?> тг</b></span>
							<span><?=t::w('Price', 'Бағасы')?>: <b><?=$fmt($d_price)?> тг</b></span>
							<span class="<?=($si_profit >= 0 ? 'ci_pos' : 'ci_neg')?>"><?=t::w('Profit', 'Пайда')?>: <b><?=$fmt($si_profit)?> тг<?=($d_price ? ' ('.round($si_profit * 100 / $d_price).'%)' : '')?></b></span>
						</div>
					<? endif ?>
				</div>
				<? if ($si_rows): ?>
					<div class="ci_tbl">
						<div class="ci_tr ci_th"><div>#</div><div><?=t::w('Name', 'Атауы')?></div><div><?=t::w('Quantity', 'Саны')?></div><div><?=t::w('Cost', 'Өзіндік құны')?></div><div></div></div>
						<? foreach ($si_rows as $i => $r): ?>
							<div class="ci_tr" data-id="<?=$r['id']?>" data-qty="<?=$qfmt($r['quantity'])?>">
								<div class="ci_n"><?=$i + 1?></div>
								<div class="ci_nm"><?=($r['product_id'] ? '<a href="/products/item/?id='.(int)$r['product_id'].'">'.htmlspecialchars($r['nm']).'</a>' : htmlspecialchars($r['nm']))?>
									<small><?=$fmt($r['unit_c'])?> тг / шт</small></div>
								<div class="ci_q"><b><?=$qfmt($r['quantity'])?></b> шт</div>
								<div class="ci_cost"><?=$fmt($r['cost'])?> тг</div>
								<div class="ci_btns">
									<div class="ctg_b si_edit" title="<?=t::w('Edit', 'Өзгерту')?>"><i class="fal fa-pen"></i></div>
									<div class="ctg_b ctg_b_red si_del" title="<?=t::w('Remove', 'Алып тастау')?>"><i class="fal fa-times"></i></div>
								</div>
							</div>
						<? endforeach ?>
						<div class="ci_tr ci_tf"><div></div><div>Тағамдар себесі:</div><div></div><div><b><?=$fmt($si_cost)?> тг</b></div><div></div></div>
						<div class="ci_tr ci_tf"><div></div><div>+ бір заказға шығын (айлық, аренда, к.у., реклама):</div><div></div><div><b><?=$fmt($si_over)?> тг</b></div><div></div></div>
						<div class="ci_tr ci_tf"><div></div><div>Толық себес:</div><div></div><div><b><?=$fmt($si_full)?> тг</b></div><div></div></div>
					</div>
				<? else: ?>
					<div class="ci_hint"><i class="fal fa-info-circle"></i> Бұл сет болса, ішіндегі тағамдар мен заттарды (қорап, сашэ) осында қосыңыз. Себесі Себес бетіндегідей есептеледі.</div>
				<? endif ?>
				<form class="si_add">
					<select class="si_sel" required>
						<option value="">Тағам немесе зат таңдаңыз</option>
						<optgroup label="Тағамдар">
							<? while ($o = mysqli_fetch_assoc($si_opts)): ?><option value="p<?=$o['id']?>"><?=htmlspecialchars(strip_tags($o['name_ru'] ?: $o['name_kz']))?></option><? endwhile ?>
						</optgroup>
						<optgroup label="Құрамдар (қорап, сашэ, мәзірде жоқ тағам)">
							<? foreach ($si_comps as $c): ?><option value="c<?=$c['id']?>"><?=htmlspecialchars($c['name_ru'] ?: $c['name_kz'])?></option><? endforeach ?>
						</optgroup>
					</select>
					<input class="si_qty" type="text" inputmode="decimal" value="1" title="Саны">
					<button type="submit" class="btn si_add_b"><i class="far fa-plus"></i><span>Сетке қосу</span></button>
				</form>
			</div>
			<style>
				.si_add{ display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
				.si_sel{ flex: 1; min-width: 220px; height: 46px; padding: 0 12px; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 10px; background: #fff; font: inherit; font-size: 15px; }
				.si_qty{ width: 80px; height: 46px; padding: 0 12px; border: 1px solid var(--bp-line, #e7e7e7); border-radius: 10px; font: inherit; font-size: 15px; text-align: center; }
				.si_add_b{ height: 46px; border: 0; cursor: pointer; font: inherit; font-weight: 700; display: flex; align-items: center; gap: 8px; background: var(--bp-green, #0EA768) !important; color: #fff; }
				.si_c .ci_nm a{ color: inherit; }
				.si_c .ci_nm a:hover{ color: var(--bp-blue, #1f75a8); }
			</style>
			<script>
				(function () {
					// оқиғалар document-те: блок AJAX-пен жаңарса да жұмыс істейді
					var PID = <?=(int)$product_id?>;
					var msg = function (t) { if (window.mess) mess(t); else alert(t); };
					var send = function (act, data) {
						var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
						return fetch('/products/item/get.php?' + act, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) {
							t = t.trim(); if (t == 'yes') ajx('.it_page'); else msg(t == 'qty' ? 'Санын дұрыс жазыңыз' : 'Қате, қайта көріңіз');
						});
					};
					document.addEventListener('submit', function (e) {
						var fm = e.target.closest('.si_add'); if (!fm) return;
						e.preventDefault();
						var v = fm.querySelector('.si_sel').value; if (!v) return;
						send('si_add', { id: PID, item: v, quantity: fm.querySelector('.si_qty').value });
					});
					document.addEventListener('click', function (e) {
						var b = e.target.closest('.si_c .si_edit');
						if (b) { var tr = b.closest('.ci_tr'), q = prompt('Саны', tr.dataset.qty); if (q === null) return; send('si_qty', { id: tr.dataset.id, quantity: q }); return; }
						b = e.target.closest('.si_c .si_del');
						if (b) ask('Сет құрамынан алып тастау керек пе?', function () { send('si_del', { id: b.closest('.ci_tr').dataset.id }); }, { ok: 'Алып тастау' });
					});
				})();
			</script>
			<? endif ?>

		</div>
	</div>

<? include "../../block/footer.php"; ?>

   <!-- тағам карточкасын сақтау (ескі «Обновить вид товара» панелінің орнына) -->
   <script>
      (function () {
         // оқиғалар document-те: карточка AJAX-пен жаңарса да жұмыс істейді
         var card = document.querySelector('.pcard'), busy = false;
         if (!card) return;
         var msg = function (t) { if (window.mess) mess(t); else alert(t); };
         var val = function (c) { return card.querySelector(c).value; };
         document.addEventListener('click', function (e) {
            var b = e.target.closest('.pcard .cat_pick .cp_i'); if (!b) return;
            var box = b.closest('.cat_pick'); card = b.closest('.pcard');
            box.querySelectorAll('.cp_i').forEach(function (x) { x.classList.toggle('k_br_act', x === b); });
            card.querySelector('.pc_catalog').value = b.dataset.id;
         });
         document.addEventListener('input', function (e) { if (e.target.closest('.pcard .pc_price')) e.target.value = e.target.value.replace(/\D+/g, ''); });
         document.addEventListener('click', function (e) {
            var sb = e.target.closest('.pcard .pc_save'); if (!sb) return;
            card = sb.closest('.pcard');
            if (busy) return;
            if (!nameEd.text(val('.pc_name_kz')) && !nameEd.text(val('.pc_name_ru'))) { msg(<?=json_encode(t::w('Enter name', 'Атауын жазыңыз'), JSON_UNESCAPED_UNICODE)?>); return; }
            var fd = new FormData();
            fd.append('card', 1);
            fd.append('id', card.dataset.id);
            fd.append('name_kz', val('.pc_name_kz'));
            fd.append('name_ru', val('.pc_name_ru'));
            fd.append('catalog', val('.pc_catalog'));
            fd.append('price', val('.pc_price'));
            if (card.querySelector('.pc_desc')) fd.append('description', val('.pc_desc'));
            busy = true;
            fetch('/products/item/get.php?pr_upd', { method: 'POST', body: fd, credentials: 'same-origin' })
               .then(function (r) { return r.text(); })
               .then(function (t) {
                  busy = false;
                  if (t.trim() == 'yes') { msg(<?=json_encode(t::w('Saved', 'Сақталды'), JSON_UNESCAPED_UNICODE)?>); ajx('.it_page'); }
                  else msg(<?=json_encode(t::w('Error, try again', 'Қате, қайта көріңіз'), JSON_UNESCAPED_UNICODE)?>);
               })
               .catch(function () { busy = false; msg(<?=json_encode(t::w('Error, try again', 'Қате, қайта көріңіз'), JSON_UNESCAPED_UNICODE)?>); });
         });
      })();
   </script>


   <!-- құрамға қосу / санын өзгерту -->
   <div class="pop_bl pop_bl2 ci_block">
      <div class="pop_bl_a ci_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4 class="ci_title"><?=t::w('Add to composition', 'Құрамға қосу')?></h4>
            <div class="btn btn_dd ci_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <form class="ci_form" autocomplete="off">
               <div class="form_c ci_pick_w">
                  <div class="form_span"><?=t::w('Choose ingredient', 'Құрамды таңдаңыз')?>:</div>
                  <? if ($comps): ?>
                     <div class="form_im ci_srch"><input type="text" class="form_txt ci_find" placeholder="<?=t::w('Search', 'Іздеу')?>"><i class="fal fa-search form_icon"></i></div>
                     <div class="ci_list">
                        <? foreach ($comps as $c): ?>
                           <button type="button" class="ci_opt" data-id="<?=$c['id']?>" data-qu="<?=htmlspecialchars($c['qu'])?>" data-nm="<?=htmlspecialchars(mb_strtolower($c['nm']))?>">
                              <span class="ci_opt_n"><?=htmlspecialchars($c['nm'])?></span>
                              <span class="ci_opt_p"><?=($c['price'] ? $fmt($c['price']).' тг / '.htmlspecialchars($c['pu']) : htmlspecialchars($c['pu']))?></span>
                           </button>
                        <? endforeach ?>
                        <div class="ci_none" style="display:none"><?=t::w('Nothing found', 'Ештеңе табылмады')?></div>
                     </div>
                  <? else: ?>
                     <div class="ci_none"><?=t::w('No ingredients yet', 'Әлі құрам жоқ')?></div>
                  <? endif ?>
                  <div class="ci_new_t"><i class="far fa-plus"></i> <?=t::w('Add new', 'Жаңасын қосу')?></div>
                  <div class="ci_new" style="display:none">
                     <input type="text" class="form_txt ci_new_n" maxlength="255" placeholder="<?=t::w('For example: Cheese', 'Мысалы: Сыр')?>">
                     <input type="text" class="form_txt ci_new_p" inputmode="numeric" placeholder="<?=t::w('Price', 'Бағасы')?>, тг">
                     <button type="button" class="btn ci_new_b"><?=t::w('Create', 'Жасау')?></button>
                  </div>
               </div>
               <div class="form_c">
                  <div class="form_im">
                     <div class="form_span"><?=t::w('Quantity', 'Саны')?>:</div>
                     <div class="ci_q_w">
                        <input type="text" class="form_txt ci_qty" inputmode="decimal" placeholder="0">
                        <span class="ci_sfx">шт</span>
                     </div>
                  </div>
                  <div class="form_im">
                     <button type="submit" class="btn ci_save"><i class="far fa-check"></i><span class="ci_save_t"><?=t::w('Add', 'Қосу')?></span></button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
   <script>
      (function () {
         var PID = <?=(int)$product_id?>;
         var T = <?=json_encode([
            'add_t' => t::w('Add to composition', 'Құрамға қосу'),
            'upd_t' => t::w('Change quantity', 'Санын өзгерту'),
            'add_b' => t::w('Add', 'Қосу'),
            'save_b' => t::w('Save', 'Сақтау'),
            'pick' => t::w('Choose ingredient', 'Құрамды таңдаңыз'),
            'qty' => t::w('Enter quantity', 'Санын жазыңыз'),
            'name' => t::w('Enter name', 'Атауын жазыңыз'),
            'del' => t::w('Remove from composition?', 'Құрамнан алып тастайық па?'),
            'ok' => t::w('Saved', 'Сақталды'),
            'err' => t::w('Error, try again', 'Қате, қайта көріңіз'),
         ], JSON_UNESCAPED_UNICODE)?>;
         var pop = document.querySelector('.ci_block'), f = function (c) { return pop.querySelector(c); };
         var msg = function (t) { if (window.mess) mess(t); else alert(t); };
         var mode = 'add', rowId = 0, comp = 0, busy = false;
         var post = function (url, data) {
            var fd = new FormData(); for (var k in data) fd.append(k, data[k]);
            return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (t) { return t.trim(); });
         };
         function select(id) {
            comp = 0;
            pop.querySelectorAll('.ci_opt').forEach(function (o) {
               var on = o.dataset.id == id; o.classList.toggle('ci_opt_act', on);
               if (on) { comp = +id; f('.ci_sfx').textContent = o.dataset.qu || 'шт'; }
            });
         }
         function open(row) {
            mode = row ? 'qty' : 'add'; rowId = row ? +row.dataset.id : 0;
            f('.ci_title').textContent = row ? T.upd_t : T.add_t;
            f('.ci_save_t').textContent = row ? T.save_b : T.add_b;
            f('.ci_pick_w').style.display = row ? 'none' : '';
            f('.ci_qty').value = row ? row.dataset.qty : '';
            f('.ci_sfx').textContent = 'шт';
            select(row ? row.dataset.comp : 0);
            pop.classList.add('pop_bl_act');
            setTimeout(function () { (row ? f('.ci_qty') : (f('.ci_find') || f('.ci_qty'))).focus(); }, 50);
         }
         // тізім AJAX-пен жаңарады, сондықтан батырмалар document арқылы ұсталады
         document.addEventListener('click', function (e) {
            if (e.target.closest('.ci_add_pop')) return open(null);
            var b = e.target.closest('.ci_edit'); if (b) return open(b.closest('.ci_tr'));
            b = e.target.closest('.ci_del'); if (b) ask(T.del, function () {
               post('/products/item/get.php?ci_del', { id: b.closest('.ci_tr').dataset.id }).then(function (t) {
                  if (t == 'yes') { msg(T.ok); ajx('.it_page'); } else msg(T.err);
               }).catch(function () { msg(T.err); });
            }, { ok: <?=json_encode(t::w('Remove', 'Алып тастау'), JSON_UNESCAPED_UNICODE)?> });
         });
         pop.querySelectorAll('.ci_back').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
         pop.addEventListener('click', function (e) { var o = e.target.closest('.ci_opt'); if (o) { select(o.dataset.id); f('.ci_qty').focus(); } });
         pop.addEventListener('input', function (e) {
            var find = e.target.closest('.ci_find');
            if (find) {
               var q = find.value.trim().toLowerCase(), any = false;
               pop.querySelectorAll('.ci_opt').forEach(function (o) { var ok = !q || o.dataset.nm.indexOf(q) > -1; o.style.display = ok ? '' : 'none'; if (ok) any = true; });
               f('.ci_list .ci_none').style.display = any ? 'none' : '';
            }
            if (e.target.closest('.ci_new_p')) e.target.value = e.target.value.replace(/\D+/g, '');
         });
         // саны: цифрлар және бір бөлгіш (үтір не нүкте)
         f('.ci_qty').addEventListener('input', function () {
            var v = this.value.replace(/[^\d.,]/g, ''), i = v.search(/[.,]/);
            if (i > -1) v = v.slice(0, i + 1) + v.slice(i + 1).replace(/[.,]/g, '');
            this.value = v;
         });

         // жаңа құрам жасау (бар ?comp_add арқылы), бет жаңарған соң сол таңдалып тұрады
         pop.addEventListener('click', function (e) { if (!e.target.closest('.ci_new_t')) return; var n = f('.ci_new'); n.style.display = n.style.display == 'none' ? '' : 'none'; if (n.style.display == '') f('.ci_new_n').focus(); });
         pop.addEventListener('click', function (e) {
            if (!e.target.closest('.ci_new_b')) return;
            var n = f('.ci_new_n').value.trim();
            if (!n) { msg(T.name); return; }
            post('/products/get.php?comp_add', { name: n, price: f('.ci_new_p').value }).then(function (t) {
               if (t == 'yes' || t == 'exists') {
                  // құрам тізімін бет жаңартпай қайта аламыз да, жаңасын таңдаймыз
                  ajx('.ci_pick_w', function () {
                     var o = Array.prototype.find.call(pop.querySelectorAll('.ci_opt'), function (x) { return x.dataset.nm == n.toLowerCase(); });
                     if (o) { select(o.dataset.id); o.scrollIntoView({ block: 'nearest' }); }
                     f('.ci_qty').focus();
                  });
               }
               else msg(T.err);
            }).catch(function () { msg(T.err); });
         });
         var m = location.hash.match(/ci_new=([^&]+)/);
         if (m) {
            var nm = decodeURIComponent(m[1]);
            history.replaceState(null, '', location.pathname + location.search);
            open(null);
            var o = Array.prototype.find.call(pop.querySelectorAll('.ci_opt'), function (x) { return x.dataset.nm == nm; });
            if (o) { select(o.dataset.id); o.scrollIntoView({ block: 'nearest' }); f('.ci_qty').focus(); }
         }

         f('.ci_form').addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;
            if (mode == 'add' && !comp) { msg(T.pick); return; }
            var q = f('.ci_qty').value.replace(',', '.').replace(/\.$/, '');
            if (!(parseFloat(q) > 0)) { msg(T.qty); f('.ci_qty').focus(); return; }
            busy = true;
            var req = mode == 'add' ? post('/products/item/get.php?ci_add', { id: PID, comp: comp, quantity: q }) : post('/products/item/get.php?ci_qty', { id: rowId, quantity: q });
            req.then(function (t) {
               busy = false;
               if (t == 'yes') { pop.classList.remove('pop_bl_act'); msg(T.ok); ajx('.it_page'); }
               else msg(t == 'qty' ? T.qty : T.err);
            }).catch(function () { busy = false; msg(T.err); });
         });
      })();
   </script>




   <!-- upd item -->
	<div class="pop_bl pop_bl2 pitem_upd_block">
      <div class="pop_bl_a pitem_upd_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Обновить вид товара</h4>
            <div class="btn btn_dd pitem_upd_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl lazy_c"></div>
      </div>
   </div>

	<!-- upd item quantity -->
	<div class="pop_bl pop_bl2 pitem_updq_block">
      <div class="pop_bl_a pitem_updq_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Корректировка количество</h4>
            <div class="btn btn_dd pitem_updq_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl lazy_c"></div>
      </div>
   </div>

   <!-- view_add_pop -->
   <div class="pop_bl pop_bl2 view_add_block">
      <div class="pop_bl_a view_add_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить вид товара</h4>
            <div class="btn btn_dd view_add_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl">
            <div class="form_c">
               <div class="form_im form_sel">
                  <div class="form_span">Склады:</div>
                  <i class="fal fa-warehouse-alt form_icon"></i>
                  <div class="form_im_txt sel_clc views_warehouses" data-val="">Выберите склад</div>
                  <i class="fal fa-caret-down form_icon_sel"></i>
                  <div class="form_im_sel sel_clc_i">
                     <? $warehouses = db::query("select * from product_warehouses"); ?>
                     <? while ($warehouses_d = mysqli_fetch_assoc($warehouses)): ?>
                        <div class="form_im_seli" data-val="<?=$warehouses_d['id']?>"><?=$warehouses_d['name']?></div>
                     <? endwhile ?>
                  </div>
               </div>
               <div class="form_im">
                  <div class="form_span">Количество:</div>
                  <i class="fal fa-hashtag form_icon"></i>
                  <input type="tel" class="form_im_txt fr_number views_quantity" placeholder="0" data-lenght="1">
               </div>
               <div class="form_im">
                  <div class="form_span">Комментарий:</div>
                  <i class="fal fa-text form_icon"></i>
                  <input type="text" class="form_im_txt views_comment" placeholder="" data-lenght="1">
               </div>
            </div>
            <div class="form_c">
					<div class="form_im">
						<div class="btn view_add" data-product-id="<?=$product_id?>" data-item-id=""><span>Добавить</span></div>
					</div>
				</div>
         </div>
      </div>
   </div>


   <!-- imgs b -->
   <div class="pop_bl pop_bl2 imgs_add_block">
      <div class="pop_bl_a imgs_add_back"></div>
      <div class="pop_bl_c">
         <div class="head_c">
            <h4>Добавить фото товара</h4>
            <div class="btn btn_dd imgs_add_back"><i class="fal fa-times"></i></div>
         </div>
         <div class="pop_bl_cl lazy_c">
            <div class="form_c">
               <div class="form_head">Добавить изображение товара:</div>
               <div class="uacc_ic">
                  <div class="upl_logo upl_logo2">
                     <input type="file" class="item_file2 file" multiple accept=".png, .jpeg, .jpg" data-id="<?=$product_id?>" data-item-id="">
                     <div class="upl_logo_c item_ava_clc2">Добавить фото</div>
                  </div>
                  <div class="upl_lv lazy_c"></div>
               </div>
            </div>
         </div>
      </div>
   </div>
