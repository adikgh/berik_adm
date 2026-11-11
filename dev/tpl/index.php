<?php include "../config/core.php";
	dev_only('tpl');
	// Граммовка шаблондары: барлық компанияға ортақ. Компаниялар админде тағам картасында не Себес бетінде қолданады.
	// Кестелер: grammovka_tpl (arh: 0 = шаблон, 1 = өшірілген, 2 = ЖИ ұсынысы, әлі бекітілмеген), grammovka_tpl_item.
	$menu = 'tpl'; $page_title = t::w('Recipe templates', 'Граммовка шаблондары');
	$ready = mysqli_num_rows(db::query("SHOW TABLES LIKE 'grammovka_tpl_item'")) > 0;
	$tab = @$_GET['t'] === 'ai' ? 'ai' : '';
	$tpls = []; $items = []; $miss = []; $ai_ok = false;
	if ($ready) {
		require __DIR__ . '/ai.php';
		$note = mysqli_num_rows(db::query("show columns from grammovka_tpl like 'ai_note'")) > 0;
		$q = db::query("select id, name_ru, cat, keys_ru, arh" . ($note ? ", ai_note" : "") . " from grammovka_tpl where arh in (0, 2) order by cat, number, id");
		while ($r = mysqli_fetch_assoc($q)) $tpls[$r['id']] = $r;
		$q = db::query("select tpl_id, variant, name_ru, unit, quantity, number from grammovka_tpl_item order by tpl_id, number, variant");
		while ($r = mysqli_fetch_assoc($q)) {
			if (!isset($tpls[$r['tpl_id']])) continue;
			$k = $r['tpl_id'] . '|' . $r['number'];
			if (!isset($items[$r['tpl_id']][$k])) $items[$r['tpl_id']][$k] = ['name' => $r['name_ru'], 'unit' => $r['unit'], 'q' => ['', '', '']];
			$items[$r['tpl_id']][$k]['q'][$r['variant'] - 1] = (float)$r['quantity'];
		}
		$miss = tpl_ai::missing();
		$ai_ok = claude::ready();
	}
	$data = []; $cats = []; $n_tpl = 0; $n_draft = 0;
	foreach ($tpls as $id => $t) {
		$its = array_values($items[$id] ?? []);
		// нұсқа қорытындысы: құрам саны және салмағы (кг/л құрам граммен/мл-мен)
		$sum = [];
		foreach ([0, 1, 2] as $v) {
			$n = 0; $g = 0;
			foreach ($its as $i) if ((float)$i['q'][$v] > 0) { $n++; if ($i['unit'] != 'шт') $g += (float)$i['q'][$v]; }
			$sum[] = [$n, round($g)];
		}
		$data[] = ['id' => (int)$id, 'name' => $t['name_ru'], 'cat' => (string)$t['cat'], 'keys' => (string)$t['keys_ru'], 'draft' => $t['arh'] == 2 ? 1 : 0, 'note' => (string)@$t['ai_note'], 'sum' => $sum, 'items' => $its];
		if ($t['arh'] == 2) $n_draft++; else { $n_tpl++; if ($t['cat'] !== '') $cats[$t['cat']] = ($cats[$t['cat']] ?? 0) + 1; }
	}
	$page_sub = t::w('Shared by all companies: they pick Economy, Standard or Premium for a dish.', 'Барлық компанияға ортақ: компания тағамына Эконом, Стандарт не Премиум нұсқасын таңдайды.');
	$page_pop = ['tpl_pop'];
	$fmt = function ($s) { return $s[0] ? '<b>' . $s[0] . '</b>' . ($s[1] ? ' <span class="mut">· ' . number_format($s[1], 0, '.', ' ') . ' ' . t::w('g', 'г') . '</span>' : '') : '<span class="mut">—</span>'; };
?>
<? include "../block/header.php"; ?>
	<? if (!$ready): ?>
		<div class="card"><p><?=t::w('No table: run grammovka_tpl.sql first.', 'Кесте жоқ: алдымен grammovka_tpl.sql іске қосыңыз.')?></p></div>
	<? else: ?>
	<div class="tools">
		<div class="tabs">
			<a href="./" class="<?=$tab === '' ? 'on' : ''?>"><?=t::w('Templates', 'Шаблондар')?><i><?=$n_tpl?></i></a>
			<a href="?t=ai" class="<?=$tab === 'ai' ? 'on' : ''?>"><i class="fal fa-sparkles" style="margin:0 6px 0 0"></i><?=t::w('AI suggestions', 'ЖИ ұсыныстары')?><i><?=$n_draft + count($miss)?></i></a>
		</div>
		<? if ($tab === ''): ?>
		<div class="gt_tools">
			<select class="gt_cat"><option value=""><?=t::w('All categories', 'Барлық санат')?></option><? foreach ($cats as $c => $n): ?><option value="<?=dev::h($c)?>"><?=dev::h($c)?> · <?=$n?></option><? endforeach ?></select>
			<input type="search" class="gt_q" placeholder="<?=t::w('Search', 'Іздеу')?>">
			<button type="button" class="btn btn_g gt_new"><i class="fal fa-plus"></i><?=t::w('New template', 'Жаңа шаблон')?></button>
		</div>
		<? endif ?>
	</div>

	<? if ($tab === ''): ?>
	<div class="card">
		<div class="tbl_w"><table class="tbl gt_t">
			<thead><tr><th><?=t::w('Dish', 'Тағам')?></th><th><?=t::w('Category', 'Санаты')?></th><th class="num"><?=t::w('Economy', 'Эконом')?></th><th class="num"><?=t::w('Standard', 'Стандарт')?></th><th class="num"><?=t::w('Premium', 'Премиум')?></th></tr></thead>
			<tbody>
			<? foreach ($data as $t): if ($t['draft']) continue; ?>
				<tr class="lnk" data-id="<?=$t['id']?>" data-cat="<?=dev::h($t['cat'])?>" data-s="<?=dev::h(mb_strtolower($t['name'] . ' ' . $t['keys'] . ' ' . $t['cat']))?>">
					<td class="nm"><b><?=dev::h($t['name'])?></b><small><?=dev::h(str_replace('|', ', ', $t['keys']))?></small></td>
					<td><?=$t['cat'] !== '' ? dev::h($t['cat']) : '<span class="mut">—</span>'?></td>
					<? foreach ($t['sum'] as $s): ?><td class="num"><?=$fmt($s)?></td><? endforeach ?>
				</tr>
			<? endforeach ?>
			</tbody>
		</table></div>
		<p class="gt_empty mut" style="display:none"><?=t::w('Nothing found', 'Ештеңе табылмады')?></p>
	</div>
	<p class="cn_note"><?=t::w('Amounts: kg items in grams, l items in ml, pcs in pieces. Columns show the number of ingredients and the weight of one portion.', 'Мөлшер: кг құрам граммен, л құрам мл-мен, шт данамен. Бағандарда құрам саны және бір порция салмағы.')?></p>

	<? else: ?>
	<? if (!$ai_ok): ?><div class="card gt_warn"><i class="fal fa-exclamation-triangle"></i> <?=t::w('Claude is not connected: no ANTHROPIC_API_KEY in the admin config/ai.php', 'Claude қосылмаған: админдегі config/ai.php ішінде ANTHROPIC_API_KEY жоқ')?></div><? endif ?>

	<? if ($n_draft): ?>
	<div class="card">
		<div class="card_h"><h2><?=t::w('Ready to review', 'Тексеруге дайын')?> · <?=$n_draft?></h2><span class="mut"><?=t::w('Companies see a suggestion only after you approve it.', 'Компаниялар ұсынысты сіз бекіткен соң ғана көреді.')?></span></div>
		<div class="tbl_w"><table class="tbl gt_t">
			<thead><tr><th><?=t::w('Dish', 'Тағам')?></th><th><?=t::w('Category', 'Санаты')?></th><th class="num"><?=t::w('Economy', 'Эконом')?></th><th class="num"><?=t::w('Standard', 'Стандарт')?></th><th class="num"><?=t::w('Premium', 'Премиум')?></th></tr></thead>
			<tbody>
			<? foreach ($data as $t): if (!$t['draft']) continue; ?>
				<tr class="lnk" data-id="<?=$t['id']?>">
					<td class="nm"><b><?=dev::h($t['name'])?></b><small><?=dev::h($t['note'] !== '' ? $t['note'] : str_replace('|', ', ', $t['keys']))?></small></td>
					<td><?=$t['cat'] !== '' ? dev::h($t['cat']) : '<span class="mut">—</span>'?></td>
					<? foreach ($t['sum'] as $s): ?><td class="num"><?=$fmt($s)?></td><? endforeach ?>
				</tr>
			<? endforeach ?>
			</tbody>
		</table></div>
	</div>
	<? endif ?>

	<div class="card">
		<div class="card_h"><h2><?=t::w('Dishes without a template', 'Шаблоны жоқ тағамдар')?> · <?=count($miss)?></h2>
			<? if ($miss): ?><div class="gt_aibar"><span class="gt_prog mut"></span><button type="button" class="btn btn_g gt_ai_go" <?=$ai_ok ? '' : 'disabled'?>><i class="fal fa-sparkles"></i><span><?=t::w('Find recipes with AI', 'ЖИ-мен граммовка табу')?></span></button></div><? endif ?>
		</div>
		<? if (!$miss): ?>
			<p class="mut"><?=t::w('Every dish in our companies already has a template.', 'Компаниялардағы барлық тағамның шаблоны бар.')?></p>
		<? else: ?>
		<p class="cn_note" style="margin:0 0 10px"><?=t::w('Dishes from active companies that no template matches (sets, combos and drinks are skipped). Tick dishes: AI searches the internet for typical recipes and makes 3 variants. You review them above before companies see them.', 'Архивте емес компаниялардағы ешбір шаблонға сәйкес келмейтін тағамдар (сет, комбо, сусын кірмейді). Белгілеңіз: ЖИ интернеттен әдеттегі рецептті тауып, 3 нұсқа жасайды. Компанияларға көрсетпес бұрын жоғарыда тексересіз.')?></p>
		<div class="tbl_w"><table class="tbl gt_m">
			<thead><tr><th style="width:36px"><input type="checkbox" class="gt_all"></th><th><?=t::w('Dish', 'Тағам')?></th><th><?=t::w('Menu category', 'Мәзір санаты')?></th><th><?=t::w('Companies', 'Компаниялар')?></th></tr></thead>
			<tbody>
			<? foreach ($miss as $i => $m): ?>
				<tr><td><input type="checkbox" class="gt_pick" value="<?=dev::h($m['name'])?>" <?=$i < 3 ? 'checked' : ''?>></td><td><b><?=dev::h($m['name'])?></b></td><td><?=$m['cat'] !== '' ? dev::h($m['cat']) : '<span class="mut">—</span>'?></td><td class="nm"><b><?=$m['n']?></b><small><?=dev::h($m['co'])?></small></td></tr>
			<? endforeach ?>
			</tbody>
		</table></div>
		<? endif ?>
	</div>
	<? endif ?>
	<? endif ?>
<? include "../block/footer.php"; ?>
<style>
	.gt_tools{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
	.gt_tools select, .gt_tools input{ height:46px; width:220px; border:0; border-radius:12px; padding:0 14px; background:#fff; box-shadow:var(--sh); font:inherit; min-width:180px; }
	.gt_t td.num b{ font-weight:700; }
	.gt_warn{ color:#b45309; background:#fff7ed; }
	.gt_aibar{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
	.gt_aibar .btn[disabled]{ opacity:.5; pointer-events:none; }
	.gt_m input[type=checkbox]{ width:18px; height:18px; accent-color:var(--g, #16a34a); }
	.dv_c .card_h{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
	@media (max-width:760px){ .gt_tools{ width:100%; } .gt_tools select{ flex:1 1 100%; width:auto; } .gt_tools input{ flex:1; width:auto; min-width:0; } }
</style>
<script>
	window.GT = <?=json_encode(['tpl' => array_column($data, null, 'id'), 'cats' => array_keys($cats)], JSON_UNESCAPED_UNICODE)?>;
</script>
<script src="/assets/js/dev_tpl.js?v=<?=@filemtime(DEV_ADM . '/assets/js/dev_tpl.js')?>"></script>
