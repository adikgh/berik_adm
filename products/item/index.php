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
	$page_name = $product_d['name_ru'] ?: $product_d['name_kz'];
	$site_set['header'] = true;
	$site_set['menu'] = true;
	$site_set['atops'] = true;
	$site_set['footer'] = false;
	$site_set['utop_bk'] = 'products';
	$site_set['utop_type'] = 'item';
	$site_set['utop_nm'] = $product_d['name_ru'];
	$css = ['products/main', 'products/item'];
	$js = ['products/main', 'products/item'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c">

		<? include "../aheader.php"; ?>

		<div class="pitem">

			<? $pc_cats = db::query("select id, name_kz, name_ru, color from product_catalog where company_id = '$company' and (arh is null or arh = 0) order by number asc, id asc"); ?>
			<div class="pcard" data-id="<?=$product_id?>">
				<div class="pcard_top">
					<div class="pcard_clr" style="background:<?=htmlspecialchars(($catalog_d['color'] ?? '') ?: '#b3bac7')?>"><i class="fal fa-utensils"></i></div>
					<div class="pcard_ttl">
						<div class="pcard_name"><?=htmlspecialchars($product_d['name_kz'] ?: $product_d['name_ru'])?></div>
						<div class="pcard_sub">
							<span><i class="fal fa-folder"></i> <?=($catalog_d ? htmlspecialchars($catalog_d['name_'.$lang] ?: $catalog_d['name_ru']) : t::w('No catalog', 'Каталогсыз'))?></span>
							<span class="pcard_price"><?=number_format((int)$product_d['price'], 0, '', ' ')?> тг</span>
							<? if ($product_d['arh']): ?><span class="pcard_arh"><i class="fal fa-archive"></i> <?=t::w('In archive', 'Архивте')?></span><? endif ?>
						</div>
					</div>
				</div>
				<div class="pcard_form">
					<label class="pcard_f">
						<span class="pcard_l"><?=t::w('Name (kazakh)', 'Атауы (қазақша)')?></span>
						<input type="text" class="pcard_in pc_name_kz" maxlength="255" value="<?=htmlspecialchars((string)$product_d['name_kz'])?>">
					</label>
					<label class="pcard_f">
						<span class="pcard_l"><?=t::w('Name (russian)', 'Атауы (орысша)')?></span>
						<input type="text" class="pcard_in pc_name_ru" maxlength="255" value="<?=htmlspecialchars((string)$product_d['name_ru'])?>" placeholder="<?=t::w('If empty, kazakh name is used', 'Бос қалса, қазақшасы жазылады')?>">
					</label>
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
					$u = mb_strtolower(trim((string)($c['unit'] ?? '')));
					// кг/л бағасы 1 кг/л үшін, ал мөлшер г/мл-мен жазылады (÷1000)
					$c['qu'] = ['кг' => 'г', 'л' => 'мл'][$u] ?? ($u !== '' ? $u : 'дана');
					$c['div'] = in_array($u, ['кг', 'л']) ? 1000 : 1;
					$c['pu'] = $u !== '' ? $u : 'дана';
					$c['nm'] = $c['name_kz'] ?: $c['name_ru'];
					$comps[$c['id']] = $c;
				}
				$ci = db::query("select * from product_item where product_id = '$product_id' and comp_id > 0 order by ins_dt asc, id asc");
				$ci_rows = []; $ci_cost = 0;
				while ($r = mysqli_fetch_assoc($ci)) {
					$c = $comps[$r['comp_id']] ?? null;
					$r['c'] = $c;
					$r['cost'] = $c ? round((int)$r['quantity'] * (int)$c['price'] / $c['div']) : 0;
					$ci_cost += $r['cost'];
					$ci_rows[] = $r;
				}
				$d_price = (int)$product_d['price'];
				$profit = $d_price - $ci_cost;
				$fmt = function ($n) { return number_format((int)$n, 0, '', ' '); };
			?>
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
							<div class="ci_tr" data-id="<?=$r['id']?>" data-comp="<?=(int)$r['comp_id']?>" data-qty="<?=(int)$r['quantity']?>">
								<div class="ci_n"><?=$i + 1?></div>
								<div class="ci_nm"><?=($c ? htmlspecialchars($c['nm']) : '<m>'.t::w('Deleted item', 'Өшірілген құрам').'</m>')?>
									<? if ($c): ?><small><?=$fmt($c['price'])?> тг / <?=htmlspecialchars($c['pu'])?></small><? endif ?></div>
								<div class="ci_q"><b><?=(int)$r['quantity']?></b> <?=htmlspecialchars($c['qu'] ?? 'дана')?></div>
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

		</div>
	</div>

<? include "../../block/footer.php"; ?>

   <!-- тағам карточкасын сақтау (ескі «Обновить вид товара» панелінің орнына) -->
   <script>
      (function () {
         var card = document.querySelector('.pcard'), busy = false;
         if (!card) return;
         var msg = function (t) { if (window.mess) mess(t); else alert(t); };
         var val = function (c) { return card.querySelector(c).value; };
         var box = card.querySelector('.cat_pick');
         if (box) box.addEventListener('click', function (e) {
            var b = e.target.closest('.cp_i'); if (!b) return;
            box.querySelectorAll('.cp_i').forEach(function (x) { x.classList.toggle('k_br_act', x === b); });
            card.querySelector('.pc_catalog').value = b.dataset.id;
         });
         card.querySelector('.pc_price').addEventListener('input', function () { this.value = this.value.replace(/\D+/g, ''); });
         card.querySelector('.pc_save').addEventListener('click', function () {
            if (busy) return;
            if (!val('.pc_name_kz').trim() && !val('.pc_name_ru').trim()) { msg(<?=json_encode(t::w('Enter name', 'Атауын жазыңыз'), JSON_UNESCAPED_UNICODE)?>); return; }
            var fd = new FormData();
            fd.append('card', 1);
            fd.append('id', card.dataset.id);
            fd.append('name_kz', val('.pc_name_kz'));
            fd.append('name_ru', val('.pc_name_ru'));
            fd.append('catalog', val('.pc_catalog'));
            fd.append('price', val('.pc_price'));
            busy = true;
            fetch('/products/item/get.php?pr_upd', { method: 'POST', body: fd, credentials: 'same-origin' })
               .then(function (r) { return r.text(); })
               .then(function (t) {
                  busy = false;
                  if (t.trim() == 'yes') { msg(<?=json_encode(t::w('Saved', 'Сақталды'), JSON_UNESCAPED_UNICODE)?>); setTimeout(function () { location.reload(); }, 600); }
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
                        <input type="text" class="form_txt ci_qty" inputmode="numeric" placeholder="0">
                        <span class="ci_sfx">дана</span>
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
               if (on) { comp = +id; f('.ci_sfx').textContent = o.dataset.qu || 'дана'; }
            });
         }
         function open(row) {
            mode = row ? 'qty' : 'add'; rowId = row ? +row.dataset.id : 0;
            f('.ci_title').textContent = row ? T.upd_t : T.add_t;
            f('.ci_save_t').textContent = row ? T.save_b : T.add_b;
            f('.ci_pick_w').style.display = row ? 'none' : '';
            f('.ci_qty').value = row ? row.dataset.qty : '';
            f('.ci_sfx').textContent = 'дана';
            select(row ? row.dataset.comp : 0);
            pop.classList.add('pop_bl_act');
            setTimeout(function () { (row ? f('.ci_qty') : (f('.ci_find') || f('.ci_qty'))).focus(); }, 50);
         }
         document.querySelectorAll('.ci_add_pop').forEach(function (b) { b.addEventListener('click', function () { open(null); }); });
         document.querySelectorAll('.ci_edit').forEach(function (b) { b.addEventListener('click', function () { open(b.closest('.ci_tr')); }); });
         pop.querySelectorAll('.ci_back').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
         pop.addEventListener('click', function (e) { var o = e.target.closest('.ci_opt'); if (o) { select(o.dataset.id); f('.ci_qty').focus(); } });
         var find = f('.ci_find');
         if (find) find.addEventListener('input', function () {
            var q = find.value.trim().toLowerCase(), any = false;
            pop.querySelectorAll('.ci_opt').forEach(function (o) { var ok = !q || o.dataset.nm.indexOf(q) > -1; o.style.display = ok ? '' : 'none'; if (ok) any = true; });
            f('.ci_list .ci_none').style.display = any ? 'none' : '';
         });
         [f('.ci_qty'), f('.ci_new_p')].forEach(function (i) { i.addEventListener('input', function () { this.value = this.value.replace(/\D+/g, ''); }); });

         // жаңа құрам жасау (бар ?comp_add арқылы), бет жаңарған соң сол таңдалып тұрады
         f('.ci_new_t').addEventListener('click', function () { var n = f('.ci_new'); n.style.display = n.style.display == 'none' ? '' : 'none'; if (n.style.display == '') f('.ci_new_n').focus(); });
         f('.ci_new_b').addEventListener('click', function () {
            var n = f('.ci_new_n').value.trim();
            if (!n) { msg(T.name); return; }
            post('/products/get.php?comp_add', { name: n, price: f('.ci_new_p').value }).then(function (t) {
               if (t == 'yes' || t == 'exists') { location.hash = 'ci_new=' + encodeURIComponent(n.toLowerCase()); location.reload(); }
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
            var q = parseInt(f('.ci_qty').value || '0', 10);
            if (!q) { msg(T.qty); f('.ci_qty').focus(); return; }
            busy = true;
            var req = mode == 'add' ? post('/products/item/get.php?ci_add', { id: PID, comp: comp, quantity: q }) : post('/products/item/get.php?ci_qty', { id: rowId, quantity: q });
            req.then(function (t) {
               busy = false;
               if (t == 'yes') { pop.classList.remove('pop_bl_act'); msg(T.ok); setTimeout(function () { location.reload(); }, 400); }
               else msg(t == 'qty' ? T.qty : T.err);
            }).catch(function () { busy = false; msg(T.err); });
         });
         document.querySelectorAll('.ci_del').forEach(function (b) {
            b.addEventListener('click', function () {
               if (!confirm(T.del)) return;
               post('/products/item/get.php?ci_del', { id: b.closest('.ci_tr').dataset.id }).then(function (t) {
                  if (t == 'yes') location.reload(); else msg(T.err);
               }).catch(function () { msg(T.err); });
            });
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