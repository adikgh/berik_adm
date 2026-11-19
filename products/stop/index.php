<? include "../../config/core.php";
	include "../../config/stop_list.php";

	if (!$user_id) { header('location: /'); exit(); }

	// Стоп-лист: қолмен стоп, автомат стоп (шикізат бітсе), «Сатуға рұқсат», ескерту
	$cmp = (int)$company;
	stopl::install();
	$auto = stopl::auto($cmp);
	$own = stopl::own($cmp);
	$ids = stopl::ids($cmp);
	$raw_out = stopl::raw_out($cmp);
	$_SESSION['stopl_raw_'.$cmp] = ['t' => time(), 'd' => $raw_out];

	// компанияның барлық тағамы, каталог бойынша
	$all = []; $cats = [];
	$q = db::query("select p.id, p.name_kz, p.name_ru, p.price, p.img, c.id cid, c.name_kz cname from product p
		join product_catalog c on c.id = p.catalog_id
		where c.company_id = '$cmp' and (c.arh is null or c.arh = 0) and p.arh = 0
		order by c.number, c.id, p.name_kz");
	while ($q && $r = mysqli_fetch_assoc($q)) {
		$r['nm'] = trim(strip_tags($r['name_kz'] ?: $r['name_ru']));
		$all[(int)$r['id']] = $r;
		$cats[(int)$r['cid']]['name'] = strip_tags($r['cname']);
		$cats[(int)$r['cid']]['items'][] = (int)$r['id'];
	}
	// сет неге стопта: ішіндегі стоптағы тағамдар
	$why_set = [];
	$set_ids = array_keys(array_filter($ids, function ($v) { return $v == 2; }));
	if ($set_ids) {
		$q = db::query("select set_id, product_id from product_set_item where set_id in (".implode(',', $set_ids).") and product_id > 0");
		while ($q && $r = mysqli_fetch_assoc($q)) if (!empty($ids[(int)$r['product_id']]) && isset($all[(int)$r['product_id']])) $why_set[(int)$r['set_id']][] = $all[(int)$r['product_id']]['nm'];
	}
	$stopped = array_filter($ids, function ($v, $pid) use ($all) { return isset($all[$pid]); }, ARRAY_FILTER_USE_BOTH);
	$allowed = array_filter($own, function ($r, $pid) use ($all) { return (int)$r['mode'] == 2 && isset($all[$pid]); }, ARRAY_FILTER_USE_BOTH);
	$warn = array_filter($raw_out, function ($m, $pid) use ($all, $ids, $allowed) { return isset($all[$pid]) && empty($ids[$pid]) && !isset($allowed[$pid]); }, ARRAY_FILTER_USE_BOTH);
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
	// біткен шикізат тізімі қысқа: алғашқы 4-і + «тағы N»
	$miss_txt = function ($m) use ($h) { $m = (array)$m; $n = count($m); return $h(implode(', ', array_slice($m, 0, 4))).($n > 4 ? ' '.t::w('and more', 'және тағы').' '.($n - 4) : ''); };
	// біткен шикізаттар (ескерту басында)
	$raw_names = [];
	foreach ($warn as $m) foreach ($m as $nm) $raw_names[$nm] = ($raw_names[$nm] ?? 0) + 1;
	arsort($raw_names);
	$dt = function ($v) { return $v ? date('d.m H:i', strtotime($v)) : ''; };

	// site setting
	$menu_name = 'products';
	$pod_menu_name = 'stop';
	$page_name = 'Стоп-лист';
	$page_key = 'Stop list';
	$css = ['products/main', 'products/stop'];
	$js = ['products/stop'];
?>
<? include "../../block/header.php"; ?>

	<div class="bl_c">

		<!-- a header -->
		<? include "../aheader.php"; ?>

		<div class="sl_page">

			<div class="sl_top">
				<div class="sl_top_l">
					<div class="sl_title"><i class="fal fa-ban"></i> <?=t::w('Stop list', 'Стоп-лист')?></div>
					<div class="sl_sub"><?=t::w('Stop list hint', 'Стоптағы тағам кассада қосылмайды, QR мен онлайн мәзірде «Жоқ» деп тұрады. Стоптағы тағам кірген сет те стопқа түседі.')?></div>
				</div>
				<label class="sl_auto" title="<?=$h(t::w('Auto stop hint', 'Граммовкадағы шикізат қалдығы 0-ге түссе, тағам өзі стопқа түседі, накладной келгенде өзі шығады'))?>">
					<input type="checkbox" class="sl_auto_in" <?=($auto ? 'checked' : '')?>>
					<span class="sl_sw"></span>
					<span><?=t::w('Auto stop when out of stock', 'Шикізат бітсе, автомат стоп')?></span>
				</label>
				<? if (!$auto && count($warn) > 0): ?><div class="sl_auto_warn"><i class="fal fa-info-circle"></i> <?=sprintf(t::w('Auto stop would stop %d', 'Автомат стоп қосылса, қазір %d тағам стопқа түседі. Алдымен ревизия жасап, қалдықты нақтылаған дұрыс.'), count($warn))?></div><? endif ?>
			</div>

			<!-- стопта -->
			<div class="sl_box">
				<div class="sl_box_t"><span><?=t::w('On stop now', 'Қазір стопта')?></span><b class="sl_cnt sl_cnt_red"><?=count($stopped)?></b></div>
				<? if ($stopped): ?>
					<div class="sl_list">
						<? foreach ($stopped as $pid => $kind): $p = $all[$pid]; $o = $own[$pid] ?? null; ?>
							<div class="sl_i sl_i_stop">
								<div class="sl_i_n">
									<div class="sl_i_name"><?=$h($p['nm'])?></div>
									<div class="sl_i_why">
										<? if ($kind == 1): ?><span class="sl_tag sl_tag_red"><?=t::w('Manual', 'Қолмен')?></span> <?=$dt(@$o['ins_dt'])?><?=(!empty($o['note']) ? ' · '.$h($o['note']) : '')?>
										<? elseif ($kind == 3): ?><span class="sl_tag sl_tag_org"><?=t::w('Auto', 'Автомат')?></span> <?=t::w('Out of', 'Бітті')?>: <?=$miss_txt($raw_out[$pid] ?? [])?>
										<? else: ?><span class="sl_tag sl_tag_gr"><?=t::w('Set', 'Сет')?></span> <?=t::w('Inside on stop', 'Ішіндегі стопта')?>: <?=$h(implode(', ', $why_set[$pid] ?? []))?>
										<? endif ?>
									</div>
								</div>
								<div class="sl_i_b">
									<? if ($kind == 1): ?><div class="btn btn_cl sl_btn" data-id="<?=$pid?>" data-mode="0"><i class="fal fa-check"></i><span><?=t::w('Remove from stop', 'Стоптан алу')?></span></div>
									<? elseif ($kind == 3): ?><div class="btn btn_cl sl_btn" data-id="<?=$pid?>" data-mode="2" data-ask="<?=$h(t::w('Allow selling ask', 'Шикізат қалдығына қарамай сата берейік пе?'))?>"><i class="fal fa-unlock"></i><span><?=t::w('Allow selling', 'Сатуға рұқсат')?></span></div>
									<? endif ?>
								</div>
							</div>
						<? endforeach ?>
					</div>
				<? else: ?>
					<div class="sl_empty"><i class="fal fa-smile"></i> <?=t::w('Nothing on stop', 'Стопта ештеңе жоқ, бәрі сатылады')?></div>
				<? endif ?>
			</div>

			<!-- ескерту: шикізаты бітті, бірақ стопта емес -->
			<? if ($warn): ?>
				<div class="sl_box sl_box_warn">
					<div class="sl_box_t"><span><i class="fal fa-exclamation-triangle"></i> <?=t::w('Out of raw warning', 'Шикізаты бітті, бірақ сатылып жатыр')?></span><b class="sl_cnt sl_cnt_org"><?=count($warn)?></b></div>
					<div class="sl_raws"><span><?=t::w('Out of stock raw', 'Қалдығы 0 шикізат')?> (<?=count($raw_names)?>):</span>
						<? foreach ($raw_names as $nm => $c): ?><b class="sl_raw" title="<?=$c?> <?=$h(t::w('dishes', 'тағам'))?>"><?=$h($nm)?></b><? endforeach ?>
					</div>
					<div class="sl_list sl_list_fold">
						<? $wi = 0; foreach ($warn as $pid => $miss): $p = $all[$pid]; $wi++; ?>
							<div class="sl_i<?=($wi > 8 ? ' sl_more' : '')?>">
								<div class="sl_i_n">
									<div class="sl_i_name"><?=$h($p['nm'])?></div>
									<div class="sl_i_why"><?=t::w('Out of', 'Бітті')?>: <?=$miss_txt($miss)?></div>
								</div>
								<div class="sl_i_b"><div class="btn sl_btn sl_btn_red" data-id="<?=$pid?>" data-mode="1"><i class="fal fa-ban"></i><span><?=t::w('Put on stop', 'Стопқа қою')?></span></div></div>
							</div>
						<? endforeach ?>
					</div>
					<? if ($wi > 8): ?><div class="btn btn_cl sl_more_btn"><?=t::w('Show all', 'Барлығын көрсету')?> (<?=$wi?>)</div><? endif ?>
					<div class="sl_note"><?=t::w('Out of raw note', 'Қалдық дұрыс емес болса, Закуп ▸ Ревизия арқылы түзетіңіз.')?></div>
				</div>
			<? endif ?>

			<!-- сатуға рұқсат берілгендер -->
			<? if ($allowed): ?>
				<div class="sl_box">
					<div class="sl_box_t"><span><i class="fal fa-unlock"></i> <?=t::w('Allowed despite stock', 'Қалдыққа қарамай сатылады')?></span><b class="sl_cnt"><?=count($allowed)?></b></div>
					<div class="sl_list">
						<? foreach ($allowed as $pid => $o): $p = $all[$pid]; ?>
							<div class="sl_i">
								<div class="sl_i_n">
									<div class="sl_i_name"><?=$h($p['nm'])?></div>
									<div class="sl_i_why"><?=$dt($o['ins_dt'])?><?=(isset($raw_out[$pid]) ? ' · '.t::w('Out of', 'Бітті').': '.$miss_txt($raw_out[$pid]) : '')?></div>
								</div>
								<div class="sl_i_b"><div class="btn btn_cl sl_btn" data-id="<?=$pid?>" data-mode="0"><i class="fal fa-times"></i><span><?=t::w('Cancel permission', 'Рұқсатты алу')?></span></div></div>
							</div>
						<? endforeach ?>
					</div>
				</div>
			<? endif ?>

			<!-- барлық тағам -->
			<div class="sl_box">
				<div class="sl_box_t"><span><?=t::w('All dishes', 'Барлық тағам')?></span><b class="sl_cnt"><?=count($all)?></b></div>
				<div class="sl_search"><i class="fal fa-search"></i><input type="text" class="sl_search_in" placeholder="<?=$h(t::w('Search dish', 'Тағамды іздеу'))?>"></div>
				<? foreach ($cats as $c): ?>
					<div class="sl_cat">
						<div class="sl_cat_t"><?=$h($c['name'])?></div>
						<? foreach ($c['items'] as $pid): $p = $all[$pid]; $k = $ids[$pid] ?? 0; ?>
							<div class="sl_row<?=($k ? ' sl_row_stop' : '')?>" data-s="<?=$h(mb_strtolower($p['nm'].' '.strip_tags($p['name_ru'])))?>">
								<div class="sl_row_n"><?=$h($p['nm'])?><? if ($k == 2): ?> <span class="sl_tag sl_tag_gr"><?=t::w('Set', 'Сет')?></span><? elseif ($k == 3): ?> <span class="sl_tag sl_tag_org"><?=t::w('Auto', 'Автомат')?></span><? endif ?></div>
								<div class="sl_row_p"><?=number_format((float)$p['price'], 0, '.', ' ')?> тг</div>
								<? if ($k == 1 || $k == 0): ?>
									<div class="sl_tg sl_btn<?=($k == 1 ? ' sl_tg_on' : '')?>" data-id="<?=$pid?>" data-mode="<?=($k == 1 ? 0 : 1)?>" title="<?=$h($k == 1 ? t::w('Remove from stop', 'Стоптан алу') : t::w('Put on stop', 'Стопқа қою'))?>"><span></span><?=t::w('Stop', 'Стоп')?></div>
								<? else: ?><div class="sl_tg sl_tg_on sl_tg_dis"><span></span><?=t::w('Stop', 'Стоп')?></div><? endif ?>
							</div>
						<? endforeach ?>
					</div>
				<? endforeach ?>
				<div class="sl_empty sl_nf" hidden><?=t::w('Nothing found', 'Табылмады')?></div>
			</div>

		</div>

	</div>

<? include "../../block/footer.php"; ?>
