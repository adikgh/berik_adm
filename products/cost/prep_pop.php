<?
// «Дайындама» попапы (жаңа / өзгерту): кез келген админ бетіне қосылады.
//   include $_SERVER['DOCUMENT_ROOT'].'/products/cost/prep_pop.php';  (header.php-дан кейін, body ішінде)
// JS: openPrep(id | null, { name: 'атауы', onSave: function (r) {} }) — r = { id, name, unit, cost }.
// Сақталғанда document-ке 'prep:saved' оқиғасы жіберіледі (e.detail = r).
// Жоғарыдағы іздеу: дайындама шаблонын таңдаса атауы, өлшемі, шығымы, құрамы толады (жоқ шикізат/ішкі дайындама ашылады),
// шаблон жоқ болса — «ЖИ толтырсын»: Claude орташа рецепт құрады. Жаңа шикізаттың бағасы базадан не ЖИ-ден толады.
if (!defined('PREP_POP')): define('PREP_POP', 1);
	$pp_v = function ($f) { return @filemtime($_SERVER['DOCUMENT_ROOT'].$f); };
	@include_once __DIR__.'/../tpl/lib.php';
	$pp_tpl = class_exists('gtpl') && gtpl::semi_ready();
?>
	<link rel="stylesheet" href="/assets/css/prep_pop.css?v=<?=$pp_v('/assets/css/prep_pop.css')?>">
	<div class="prp" hidden>
		<div class="prp_bg"></div>
		<form class="prp_c" autocomplete="off" novalidate>
			<div class="prp_h"><b class="prp_t"><?=t::w('pp New semi', 'Жаңа дайындама')?></b><button type="button" class="prp_x" title="<?=t::w('pp Close', 'Жабу')?>"><i class="fal fa-times"></i></button></div>
			<? if ($pp_tpl): ?>
			<div class="prp_tpl">
				<div class="prp_srch">
					<i class="fal fa-magic"></i>
					<input type="text" class="prp_tq" placeholder="<?=t::w('pp Find a template', 'Шаблоннан толтыру: рис для суши, соус спайси, тесто…')?>">
					<div class="prp_dd"></div>
				</div>
				<select class="prp_in prp_var" title="<?=t::w('pp Variant', 'Нұсқа')?>"><option value="1"><?=t::w('Economy', 'Эконом')?></option><option value="2" selected><?=t::w('Standard', 'Стандарт')?></option><option value="3"><?=t::w('Premium', 'Премиум')?></option></select>
				<button type="button" class="prp_ai" title="<?=t::w('pp AI fill hint', 'Шаблон болмаса, ЖИ орташа рецепт құрады')?>"><i class="fal fa-sparkles"></i><span><?=t::w('pp AI fill', 'ЖИ толтырсын')?></span></button>
			</div>
			<div class="prp_st" hidden></div>
			<? endif ?>
			<div class="prp_g">
				<label class="prp_f prp_fw"><span><?=t::w('pp Title', 'Атауы')?></span><input class="prp_in prp_name" maxlength="255" required></label>
				<label class="prp_f"><span><?=t::w('pp Unit', 'Өлшемі')?></span><select class="prp_in prp_unit"><option>кг</option><option>шт</option><option>л</option></select></label>
				<label class="prp_f"><span><?=t::w('pp Yield', 'Шығымы (неше')?> <i class="prp_yu">кг</i> <?=t::w('pp Yield end', 'шығады)')?></span><input class="prp_in prp_yield" inputmode="decimal"></label>
			</div>
			<div class="prp_s"><?=t::w('pp Composition', 'Құрамы')?> <small><?=t::w('pp Grams hint', 'кг/л заттар граммен (мл) жазылады')?></small></div>
			<div class="prp_rows"></div>
			<button type="button" class="prp_add"><i class="far fa-plus"></i> <?=t::w('pp Add item', 'Зат қосу')?></button>
			<div class="prp_sum"></div>
			<div class="prp_ft"><button type="submit" class="prp_save"><i class="far fa-check"></i><span><?=t::w('pp Save', 'Сақтау')?></span></button></div>
		</form>
	</div>
	<script src="/assets/js/prep_pop.js?v=<?=$pp_v('/assets/js/prep_pop.js')?>" defer></script>
<? endif ?>
