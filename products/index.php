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
	// граммовкасы жоқ тағамдарға белгі (Закуп бөлімінің тексеруі)
	if (!class_exists('wb')) @include_once "../waybill/lib.php";
	$nocomp = class_exists('wb') ? wb::comp_check($company)['dish'] : [];
	// сет пе, жеке позиция ма: product_set_item-та құрамы бар тағам = сет
	$pr_sets = [];
	if (($tq = db::query("show tables like 'product_set_item'")) && mysqli_num_rows($tq)) { $sq = db::query("select distinct si.set_id from product_set_item si join product p on p.id = si.set_id where p.company_id = '$company' and p.arh = 0"); while ($x = mysqli_fetch_assoc($sq)) $pr_sets[$x['set_id']] = 1; }
	while ($pr_d = mysqli_fetch_assoc($product)) {
		if ($pr_d['catalog_id'] && isset($catalogs[$pr_d['catalog_id']])) $catalogs[$pr_d['catalog_id']]['items'][] = $pr_d;
		else $no_catalog['items'][] = $pr_d;
	}
	if ($no_catalog['items']) $catalogs[0] = $no_catalog;
	// QR мәзірдегі «Ұсыныс» (★): тағам өзі не бүкіл санаты белгіленеді
	$rec_on = fun::has_col('product', 'recommend') && fun::has_col('product_catalog', 'recommend');
	$rec_n = 0; $rec_k = 0;
	foreach ($catalogs as $cat) { if (!empty($cat['recommend'])) $rec_k++; foreach ($cat['items'] as $x) if (!empty($x['recommend'])) $rec_n++; }
	$coll = class_exists('Collator') ? new Collator('kk_KZ') : null;
	foreach ($catalogs as &$cat) usort($cat['items'], function ($a, $b) use ($coll) {
		$x = mb_strtolower(fun::name_text($a['name_kz'] ?: $a['name_ru'])); $y = mb_strtolower(fun::name_text($b['name_kz'] ?: $b['name_ru']));
		return $coll ? $coll->compare($x, $y) : strcmp($x, $y);
	});
	unset($cat);


	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'main';
	$page_name = 'Тағамдар';
	$page_key = 'Dishes';
	$css = ['products/main', 'products/catalog', 'products/photo'];
	$js = ['products/main', 'name_ed', 'products/photo'];
?>
<? include "../block/header.php"; ?>

	<div class="bl_c pr_page" data-ajx-nav=".pr_page">

		<? if (!$page_result): // тағам болса, «Тағам қосу» сүзгі блогының ішінде тұрады ?>
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
		<? endif ?>

		<? include "aheader.php"; ?>

		<? if ($page_result): ?>

			<div class="ctg_c">
				<div class="pr_tools">
					<div class="pr_tr">
						<div class="form_im pr_srch">
							<input type="text" placeholder="<?=t::w('Search dish', 'Тағам атауымен іздеу')?>" class="form_txt pr_search">
							<i class="fal fa-search form_icon"></i>
						</div>
						<div class="btn btn_cl product_add_pop pr_add_b"><i class="fal fa-plus"></i><span><?=t::w('Add dish', 'Тағам қосу')?></span></div>
					</div>
					<div class="pr_tr">
						<div class="pr_seg pr_kind">
							<button type="button" class="pr_kind_b pr_seg_b pr_seg_on" data-k="">Барлығы <small><?=$page_result?></small></button>
							<button type="button" class="pr_kind_b pr_seg_b" data-k="set"><i class="fal fa-box-full"></i> Сеттер (жиынтық, комбо) <small><?=count($pr_sets)?></small></button>
							<button type="button" class="pr_kind_b pr_seg_b" data-k="one"><i class="fal fa-hamburger"></i> Позициялар <small><?=max(0, $page_result - count($pr_sets))?></small></button>
						</div>
						<? if ($nocomp): ?>
							<div class="pr_seg" title="Граммовкасы толтырылмаған тағамдарды ғана көрсету">
								<button type="button" class="pr_seg_b pr_seg_on" data-nc="">Барлық тағамдар <small><?=$page_result?></small></button>
								<button type="button" class="pr_seg_b pr_seg_red" data-nc="1"><i class="fal fa-exclamation-circle"></i> Граммовкасы жоқ <small><?=count($nocomp)?></small></button>
							</div>
							<div class="pr_info" tabindex="0">
								<i class="fal fa-info"></i>
								<div class="pr_info_c">
									<div><span class="pr_lg pr_lg_r">Граммовка жоқ</span> құрамы мүлде жазылмаған</div>
									<div><span class="pr_lg pr_lg_y">Граммовка толық емес</span> құрамындағы бір өнімнің бағасы не рецепті жоқ</div>
									<div><i class="fal fa-layer-group"></i> Түзеу үшін тағамның «Құрамы» кнопкасын не белгінің өзін басыңыз</div>
								</div>
							</div>
						<? endif ?>
					</div>
					<? if ($rec_on): ?>
						<div class="pr_tr pr_rec_info">
							<i class="fas fa-star"></i>
							<span>QR мәзірде тек ★ белгіленген тағамдар көрінеді (тағамның өзі не бүкіл санаты): қазір <b class="pr_rec_cn"><?=$rec_n?></b> тағам, <b class="pr_rec_ck"><?=$rec_k?></b> санат. Олар «Тағы қосасыз ба?» ұсынысына да шығады (12-ге дейін). Ештеңе белгіленбесе, мәзірде барлық тағам көрінеді.</span>
						</div>
					<? endif ?>
					<div class="pr_tr">
						<span class="pr_lbl">Каталог:</span>
						<div class="k_br pr_cat_chips">
							<a class="k_br_i k_br_act" href="#" data-cat=""><span><?=t::w('All', 'Барлығы')?></span></a>
							<? foreach ($catalogs as $cat): if (!$cat['items']) continue; ?>
								<a class="k_br_i" href="#cat=<?=$cat['id']?>" data-cat="<?=$cat['id']?>"><span><?=htmlspecialchars($cat['name_'.$lang] ?: $cat['name_ru'])?></span><small class="pr_cat_cnt"><?=count($cat['items'])?></small></a>
							<? endforeach ?>
						</div>
					</div>
				</div>

				<? $number = 0; foreach ($catalogs as $cat): if (!$cat['items']) continue; $clr = $cat['color'] ?: '#b3bac7'; ?>
					<div class="pr_grp" data-cat="<?=$cat['id']?>">
						<span class="pr_grp_clr" style="background:<?=htmlspecialchars($clr)?>"></span>
						<span class="pr_grp_name"><?=htmlspecialchars($cat['name_'.$lang] ?: $cat['name_ru'])?></span>
						<span class="pr_grp_cnt"><?=count($cat['items'])?></span>
						<? if ($rec_on && $cat['id']): ?><button type="button" class="pr_rec pr_rec_k<?=(!empty($cat['recommend']) ? ' pr_rec_on' : '')?>" data-id="<?=$cat['id']?>" title="Бүкіл санатты QR мәзірде көрсету"><i class="fa<?=(!empty($cat['recommend']) ? 's' : 'l')?> fa-star"></i><span>QR мәзірде</span></button><? endif ?>
					</div>
					<div class="ctg_list">
						<? foreach ($cat['items'] as $pr_d): $number++; ?>
							<div class="ctg_i pr_row<?=(!empty($cat['recommend']) ? ' pr_row_rk' : '')?>" data-id="<?=$pr_d['id']?>" data-cat="<?=$cat['id']?>"
								data-name="<?=htmlspecialchars(mb_strtolower(fun::name_text($pr_d['name_kz']).' '.fun::name_text($pr_d['name_ru'])))?>"
								data-name-kz="<?=htmlspecialchars(fun::name_save($pr_d['name_kz']))?>"
								data-name-ru="<?=htmlspecialchars(fun::name_save($pr_d['name_ru']))?>"
								data-catalog="<?=(int)$pr_d['catalog_id']?>"<?=(isset($pr_sets[$pr_d['id']]) ? ' data-set="1"' : '')?><?=(isset($nocomp[$pr_d['id']]) ? ' data-nocomp="1"' : '')?>
								data-price="<?=(int)$pr_d['price']?>"
								data-desc="<?=htmlspecialchars((string)@$pr_d['description'])?>">
								<div class="ctg_num"><?=$number?></div>
								<div class="ctg_clr pph<?=(!empty($pr_d['img']) ? ' pph_on' : '')?>" data-id="<?=$pr_d['id']?>" title="<?=t::w('Click to add photo', 'Басып фото қосыңыз')?>" style="background:<?=htmlspecialchars($clr)?>"><? if (!empty($pr_d['img'])): ?><img class="pph_img" src="<?=(preg_match('~^https?://~', $pr_d['img']) ? htmlspecialchars($pr_d['img']) : '/assets/uploads/products/'.rawurlencode($pr_d['img']))?>" alt="" loading="lazy" onerror="this.parentNode.classList.remove('pph_on');this.remove()"><? endif ?><i class="fal fa-camera pph_def"></i><span class="pph_ic"><i class="fal fa-camera"></i></span></div>
								<a class="ctg_name" href="item/?id=<?=$pr_d['id']?>">
									<div class="ctg_name_kz pr_nm_kz"><?=fun::name_html($pr_d['name_kz'] ?: $pr_d['name_ru'])?><?=(isset($nocomp[$pr_d['id']]) ? wb::comp_badge($nocomp[$pr_d['id']]) : '')?></div>
									<div class="ctg_name_ru pr_nm_ru"><?=($pr_d['name_ru'] && fun::name_text($pr_d['name_ru']) != fun::name_text($pr_d['name_kz']) ? fun::name_html($pr_d['name_ru']) : '')?></div>
								</a>
								<div class="ctg_cnt pr_pr"><?=((int)$pr_d['price'] ? number_format((int)$pr_d['price'], 0, '', ' ').' тг' : '<m>'.t::w('No price', 'Бағасы жоқ').'</m>')?></div>
								<div class="ctg_btns">
									<? if ($rec_on): ?><div class="ctg_b pr_rec pr_rec_p<?=(!empty($pr_d['recommend']) ? ' pr_rec_on' : '')?>" data-id="<?=$pr_d['id']?>" title="QR мәзірде көрсету (★)"><i class="fa<?=(!empty($pr_d['recommend']) ? 's' : 'l')?> fa-star"></i></div><? endif ?>
									<a class="ctg_b cpp_open" href="item/?id=<?=$pr_d['id']?>" data-id="<?=$pr_d['id']?>" title="<?=t::w('Ingredients', 'Құрамы (граммовка)')?>"><i class="fal fa-layer-group"></i></a>
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
						<div class="nm_ed">
							<div class="nm_tb"><button type="button" class="nm_red"><?=t::w('Bold red', 'Қалың қызыл')?></button><button type="button" class="nm_clr"><?=t::w('Clear', 'Тазалау')?></button></div>
							<div class="nm_ce" contenteditable="true"></div>
							<input type="hidden" class="pre_kz" value="">
						</div>
					</div>
					<div class="form_im">
						<div class="form_span"><?=t::w('Name (russian)', 'Атауы (орысша)')?>:</div>
						<div class="nm_ed">
							<div class="nm_tb"><button type="button" class="nm_red"><?=t::w('Bold red', 'Қалың қызыл')?></button><button type="button" class="nm_clr"><?=t::w('Clear', 'Тазалау')?></button></div>
							<div class="nm_ce" contenteditable="true" data-ph="<?=t::w('If empty, kazakh name is used', 'Бос қалса, қазақшасы жазылады')?>"></div>
							<input type="hidden" class="pre_ru" value="">
						</div>
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
					<div class="form_im">
						<div class="form_span"><?=t::w('Description', 'Сипаттамасы')?>:</div>
						<textarea class="form_txt pre_desc" rows="5" maxlength="1000" placeholder="<?=t::w('Description hint', 'Мысалы: тауық еті, сыр, помидор, соус. Кассада тағамды ұзақ басқанда көрінеді.')?>"></textarea>
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
	<? include "comp_pop.php"; ?>

	<style>
		.pr_tools{ background: #fff; border-radius: 16px; box-shadow: var(--bp-shadow, 0 6px 20px rgba(0,0,0,.07)); padding: 14px 16px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 12px; }
		.pr_tr{ display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
		.pr_add_b{ margin-left: auto; height: 48px; display: inline-flex; align-items: center; gap: 8px; padding: 0 18px; border-radius: 12px; background: var(--bp-green, #0EA768) !important; color: #fff !important; font-weight: 700; cursor: pointer; box-shadow: none; }
		.pr_add_b:hover{ background: var(--bp-green-h, #0c8f59) !important; }
		@media (max-width: 768px){ .pr_add_b{ margin-left: 0; width: 100%; justify-content: center; } }
		.pr_srch{ flex: 1 1 320px; max-width: 460px; margin: 0; }
		.pr_seg{ display: inline-flex; gap: 4px; padding: 4px; background: var(--bp-pill, #f5f5f5); border-radius: 12px; }
		.pr_seg_b{ height: 40px; padding: 0 14px; border: 0; border-radius: 9px; background: transparent; font: inherit; font-size: 14px; font-weight: 600; color: #5d6470; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
		.pr_seg_b small{ font-size: 12px; opacity: .6; }
		.pr_seg_on{ background: #fff; color: var(--bp-green, #0EA768); box-shadow: 0 1px 3px rgba(0,0,0,.08); }
		.pr_seg_red.pr_seg_on{ color: #c0392b; }
		.pr_lbl{ font-size: 13px; font-weight: 600; color: var(--bp-muted, #8a8f98); }
		.pr_cat_chips{ margin: 0; }
		.pr_legend{ display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 13px; color: var(--bp-muted, #8a8f98); padding-top: 10px; border-top: 1px solid var(--bp-line, #e7e7e7); }
		.pr_legend > span{ display: inline-flex; align-items: center; gap: 6px; }
		.pr_legend i{ color: var(--bp-blue, #1f75a8); }
		.pr_info{ position: relative; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--bp-line, #e7e7e7); background: #fff; display: inline-flex; align-items: center; justify-content: center; color: #8a8f98; cursor: pointer; flex-shrink: 0; outline: 0; }
		.pr_info > i{ font-size: 15px; }
		.pr_info_c{ display: none; position: absolute; z-index: 20; top: 46px; left: 50%; transform: translateX(-50%); width: 320px; padding: 12px 14px; background: #fff; border-radius: 12px; box-shadow: 0 8px 28px rgba(0,0,0,.14); font-size: 13px; color: #5d6470; line-height: 1.45; cursor: default; }
		.pr_info_c > div{ display: flex; align-items: flex-start; gap: 8px; } .pr_info_c > div + div{ margin-top: 8px; }
		.pr_info_c i{ color: var(--bp-blue, #1f75a8); margin-top: 3px; }
		.pr_info:hover .pr_info_c, .pr_info:focus .pr_info_c, .pr_info:focus-within .pr_info_c{ display: block; }
		.pr_info:hover, .pr_info:focus{ color: var(--bp-blue, #1f75a8); border-color: #cfe0ec; }
		@media (max-width: 768px){ .pr_info_c{ left: auto; right: 0; transform: none; width: 280px; } }
		.pr_lg{ display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
		.pr_lg_r{ background: #fdecec; color: #c0392b; } .pr_lg_y{ background: #fff3d6; color: #b07800; }
		.pr_row .wbm_b{ cursor: pointer; }
		.pr_rec_info{ flex-wrap: nowrap; align-items: flex-start; gap: 8px; font-size: 13px; color: #5d6470; line-height: 1.45; padding: 8px 12px; background: #fff8e1; border-radius: 10px; }
		.pr_rec_info > i{ color: #f5a700; margin-top: 3px; }
		.pr_rec{ cursor: pointer; }
		.pr_rec_p i, .pr_rec_k i{ color: #e0a100; }
		.pr_rec_p.pr_rec_on{ background: #fff3c4; }
		.pr_rec_p.pr_rec_on i, .pr_rec_k.pr_rec_on i{ color: #f5a700; }
		.pr_row_rk .pr_rec_p:not(.pr_rec_on) i{ color: #f5c95c; }
		.pr_rec_k{ margin-left: 10px; height: 30px; padding: 0 10px; border: 1px solid var(--bp-line, #e6e9ef); border-radius: 8px; background: #fff; font: inherit; font-size: 12px; font-weight: 600; color: #5d6470; display: inline-flex; align-items: center; gap: 6px; vertical-align: middle; }
		.pr_rec_k.pr_rec_on{ background: #fff8e1; border-color: #f5d27a; color: #8a6400; }
		.pr_rec_busy{ opacity: .5; pointer-events: none; }
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
			.pr_srch{ flex: 1 1 100%; max-width: none; }
			.pr_seg{ width: 100%; } .pr_seg_b{ flex: 1; justify-content: center; padding: 0 8px; }
			.pr_lbl{ display: none; }
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
			var chips, inp, q0 = '';
			function grab() { chips = document.querySelectorAll('.pr_cat_chips .k_br_i'); inp = document.querySelector('.pr_search'); }
			grab();
			var onlyNc = false;
			// Сеттер | Позициялар
			var kind = '';
			function kindShow() { document.querySelectorAll('.pr_kind_b').forEach(function (x) { x.classList.toggle('pr_seg_on', x.dataset.k === kind); }); }
			document.addEventListener('click', function (e) {
				var b = e.target.closest('.pr_kind_b'); if (!b) return;
				kind = b.dataset.k; kindShow(); apply();
			});
			var cur = (location.hash.match(/cat=(\d+)/) || [])[1] || '';
			if (cur && !document.querySelector('.pr_cat_chips [data-cat="' + cur + '"]')) cur = '';
			function apply() {
				var q = inp ? inp.value.trim().toLowerCase() : '', shown = {}, any = false;
				chips.forEach(function (c) { c.classList.toggle('k_br_act', c.dataset.cat === cur); });
				document.querySelectorAll('.pr_row').forEach(function (r) {
					var ok = (!cur || r.dataset.cat === cur) && (!q || r.dataset.name.indexOf(q) > -1) && (!onlyNc || r.dataset.nocomp) && (!kind || (kind == 'set') == !!r.dataset.set);
					r.style.display = ok ? '' : 'none';
					if (ok) { shown[r.dataset.cat] = 1; any = true; }
				});
				document.querySelectorAll('.pr_grp[data-cat]').forEach(function (g) { g.style.display = shown[g.dataset.cat] ? '' : 'none'; });
				var none = document.querySelector('.pr_none'); if (none) none.style.display = any ? 'none' : '';
			}
			// оқиғалар document-те: тізім AJAX-пен жаңарса да жұмыс істейді
			document.addEventListener('click', function (e) {
				var c = e.target.closest('.pr_cat_chips .k_br_i'); if (!c) return;
				e.preventDefault();
				cur = c.dataset.cat;
				history.replaceState(null, '', cur ? '#cat=' + cur : location.pathname + location.search);
				apply();
			});
			document.addEventListener('input', function (e) { if (e.target.closest('.pr_search')) { q0 = e.target.value; apply(); } });
			// «Граммовкасы жоқ» сүзгісі
			function segShow() { document.querySelectorAll('.pr_seg_b:not(.pr_kind_b)').forEach(function (x) { x.classList.toggle('pr_seg_on', !!x.dataset.nc === onlyNc); }); }
			document.addEventListener('click', function (e) {
				var b = e.target.closest('.pr_seg_b:not(.pr_kind_b)'); if (!b) return;
				onlyNc = !!b.dataset.nc; segShow(); apply();
			});
			// тізім AJAX-пен жаңарғанда (fun.js ajx): іздеу, каталог, граммовка сүзгісі орнында қалады
			$(document).on('ajx:done', function () {
				grab(); if (inp) inp.value = q0;
				if (cur && !document.querySelector('.pr_cat_chips [data-cat="' + cur + '"]')) cur = '';
				if (!document.querySelector('.pr_seg_b')) onlyNc = false;
				segShow(); kindShow(); if (chips.length) apply();
			});
			// белгіні басса, құрамы попапы ашылады
			document.addEventListener('click', function (e) {
				var w = e.target.closest('.pr_row .wbm_b'); if (!w) return;
				e.preventDefault();
				var o = w.closest('.pr_row').querySelector('.cpp_open'); if (o) o.click();
			});
			if (chips.length) apply();

			// ★ Ұсыныс: тағам (products/get.php?rec) не бүкіл санат (catalog/get.php?rec), бет жаңармайды
			function recCnt() {
				var n = document.querySelector('.pr_rec_cn'), k = document.querySelector('.pr_rec_ck');
				if (n) n.textContent = document.querySelectorAll('.pr_rec_p.pr_rec_on').length;
				if (k) k.textContent = document.querySelectorAll('button.pr_rec_k.pr_rec_on').length;
			}
			function recSet(b, on) { b.classList.toggle('pr_rec_on', on); var i = b.querySelector('i'); i.className = (on ? 'fas' : 'fal') + ' fa-star'; }
			document.addEventListener('click', function (e) {
				var b = e.target.closest('.pr_rec'); if (!b || b.classList.contains('pr_rec_busy')) return;
				e.preventDefault(); e.stopPropagation();
				var cat = b.classList.contains('pr_rec_k'), on = !b.classList.contains('pr_rec_on');
				var fd = new FormData(); fd.append('id', b.dataset.id); if (on) fd.append('on', 1);
				b.classList.add('pr_rec_busy');
				fetch(cat ? '/products/catalog/get.php?rec' : '/products/get.php?rec', { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.text(); })
					.then(function (t) {
						b.classList.remove('pr_rec_busy');
						if (t.trim() != 'yes') { msg(T.err); return; }
						recSet(b, on);
						if (cat) document.querySelectorAll('.pr_row[data-cat="' + b.dataset.id + '"]').forEach(function (r) { r.classList.toggle('pr_row_rk', on); });
						recCnt();
					})
					.catch(function () { b.classList.remove('pr_rec_busy'); msg(T.err); });
			});

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
			document.addEventListener('click', function (e) {
					var b = e.target.closest('.pr_edit'); if (!b) return;
					row = b.closest('.pr_row');
					nameEd.set(f('.pre_kz').parentNode, row.dataset.nameKz);
					nameEd.set(f('.pre_ru').parentNode, row.dataset.nameRu);
					pick(pop.querySelector('.cat_pick'), row.dataset.catalog);
					f('.pre_price').value = row.dataset.price;
					f('.pre_desc').value = row.dataset.desc || '';
					pop.classList.add('pop_bl_act');
					f('.pre_kz').parentNode.querySelector('.nm_ce').focus();
			});
			pop.querySelectorAll('.pre_back').forEach(function (b) { b.addEventListener('click', function () { pop.classList.remove('pop_bl_act'); }); });
			f('.pre_price').addEventListener('input', function () { this.value = this.value.replace(/\D+/g, ''); });
			f('.pre_save').addEventListener('click', function () {
				if (busy || !row) return;
				if (!nameEd.text(f('.pre_kz').value) && !nameEd.text(f('.pre_ru').value)) { msg(T.name); return; }
				var fd = new FormData();
				fd.append('card', 1);
				fd.append('id', row.dataset.id);
				fd.append('name_kz', f('.pre_kz').value);
				fd.append('name_ru', f('.pre_ru').value);
				fd.append('catalog', f('.pre_cat').value);
				fd.append('price', f('.pre_price').value);
				fd.append('description', f('.pre_desc').value);
				busy = true;
				fetch('/products/item/get.php?pr_upd', { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function (r) { return r.text(); })
					.then(function (t) {
						busy = false;
						if (t.trim() != 'yes') { msg(T.err); return; }
						msg(T.saved);
						pop.classList.remove('pop_bl_act');
						// каталог ауысса, топтау өзгереді — тізімді бет жаңартпай қайта аламыз
						if (f('.pre_cat').value != row.dataset.catalog) { ajx('.pr_page'); return; }
						var kz = nameEd.clean(f('.pre_kz').value), ru = nameEd.clean(f('.pre_ru').value);
						if (!nameEd.text(kz)) kz = ru; if (!nameEd.text(ru)) ru = kz;
						var p = parseInt(f('.pre_price').value || '0', 10);
						row.dataset.nameKz = kz; row.dataset.nameRu = ru; row.dataset.price = p;
						row.dataset.desc = f('.pre_desc').value.replace(/<[^>]*>/g, '').trim();
						row.dataset.name = (nameEd.text(kz) + ' ' + nameEd.text(ru)).toLowerCase();
						row.querySelector('.pr_nm_kz').innerHTML = kz; // clean() тек рұқсат етілген тегтерді қалдырады
						row.querySelector('.pr_nm_ru').innerHTML = nameEd.text(ru) != nameEd.text(kz) ? ru : '';
						row.querySelector('.pr_pr').textContent = p ? p.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' тг' : '—';
					})
					.catch(function () { busy = false; msg(T.err); });
			});
		})();
	</script>
