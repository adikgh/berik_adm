<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (!rv::can($user_right)) { header('location: /kassa/'); exit; }

	rv::install();
	$cmp = (int)$company;
	$cook = rv::is_cook($user_right);

	$id = (int)@$_GET['id'];
	$rev = $id ? rv::get($id, $cmp) : null;
	if ($id && !$rev) { header('location: /revision/'); exit; }
	if (!$rev) $rev = ['id' => 0, 'number' => rv::next_number($cmp), 'branch_id' => (int)$branch, 'rev_date' => date('Y-m-d'), 'status' => 0, 'comment' => '', 'user_id' => $user_id, 'posted_user_id' => 0, 'posted_dt' => null, 'ins_dt' => null];
	$posted = $rev['status'] == 1;

	$data = rv::rows($cmp, $rev);
	$rows = $data['rows']; $prev = $data['prev'];

	// қорытынды
	$t = ['cnt' => 0, 'used' => 0, 'minus' => 0, 'plus' => 0, 'in' => 0];
	foreach ($rows as $r) {
		$t['in'] += $r['qty_in'] * $r['price'];
		$c = rv::calc($r);
		if ($c['used'] === null) continue;
		$t['cnt']++;
		$t['used'] += $c['used'] * $r['price'];
		if ($c['diff'] < 0) $t['minus'] += $c['diff'] * $r['price']; else $t['plus'] += $c['diff'] * $r['price'];
	}

	$author = $rev['user_id'] ? fun::user($rev['user_id']) : null;
	$poster = $rev['posted_user_id'] ? fun::user($rev['posted_user_id']) : null;
	$st = rv::status($rev);

	// site setting
	$menu_name = 'revision';
	$page_name = 'Ревизия';
	$css = ['waybill', 'revision'];
	$js = ['revision'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<a class="wb_back" href="/revision/"><i class="fal fa-arrow-left"></i> Ревизиялар</a>

	<div class="rv_doc" data-id="<?=$rev['id']?>" data-posted="<?=($posted ? 1 : 0)?>">

		<div class="wb_box">
			<div class="wb_box_h">
				<div>Ревизия № <?=$rev['number']?> <span class="wb_st wb_st_<?=$st[0]?>"><?=$st[1]?></span></div>
				<? if ($rev['id']): ?>
					<div class="wb_small">
						<?=($author ? wb::h(trim($author['name'].' '.$author['surname'])).' · ' : '')?>бастады <?=wb::dt($rev['ins_dt'], true)?>
						<?=($posted ? ' · аяқтады '.($poster ? wb::h(trim($poster['name'].' '.$poster['surname'])).' ' : '').wb::dt($rev['posted_dt'], true) : '')?>
					</div>
				<? endif ?>
			</div>
			<div class="wb_grid">
				<label class="wb_fld">
					<span class="wb_lbl">Санаған күні</span>
					<input type="date" class="wb_inp rv_f_date" value="<?=wb::h($rev['rev_date'])?>" max="<?=date('Y-m-d')?>" <?=($posted ? 'disabled' : '')?>>
				</label>
				<label class="wb_fld wb_fld_2">
					<span class="wb_lbl">Түсініктеме</span>
					<input type="text" class="wb_inp rv_f_comment" value="<?=wb::h($rev['comment'])?>" placeholder="Міндетті емес" <?=($posted ? 'disabled' : '')?>>
				</label>
				<div class="wb_fld">
					<span class="wb_lbl">Бастапқы қалдық</span>
					<div class="rv_prev"><?=($prev ? 'Ревизия № '.$prev['number'].', '.wb::dt($prev['rev_date']) : 'Бұрын ревизия болмаған: 0-ден')?></div>
				</div>
			</div>
		</div>

		<div class="wb_cards <?=($cook ? 'wb_cards_3' : '')?>">
			<div class="wb_card">
				<div class="wb_card_h">Саналды</div>
				<div class="wb_card_v"><span class="rv_cnt"><?=$t['cnt']?></span> / <?=count($rows)?></div>
				<div class="wb_card_s">шикізат түрі</div>
			</div>
			<? if (!$cook): ?>
				<div class="wb_card">
					<div class="wb_card_h">Закуп</div>
					<div class="wb_card_v"><?=wb::sum($t['in'])?></div>
					<div class="wb_card_s"><?=($prev ? 'өткен ревизиядан бері' : 'барлық накладной')?></div>
				</div>
				<div class="wb_card">
					<div class="wb_card_h">Шығын (қолданылды)</div>
					<div class="wb_card_v rv_used_v"><?=wb::sum($t['used'])?></div>
					<div class="wb_card_s">бастапқы + закуп − нақты</div>
				</div>
				<div class="wb_card">
					<div class="wb_card_h">Базамен айырма</div>
					<div class="wb_card_v"><span class="wb_c_red rv_minus_v"><?=wb::sum($t['minus'])?></span></div>
					<div class="wb_card_s">артығы <span class="wb_c_green rv_plus_v"><?=wb::sum($t['plus'])?></span></div>
				</div>
			<? else: ?>
				<div class="wb_card">
					<div class="wb_card_h">Кем шықты</div>
					<div class="wb_card_v wb_c_red rv_minus_n">0</div>
					<div class="wb_card_s">базадағыдан аз</div>
				</div>
				<div class="wb_card">
					<div class="wb_card_h">Артық шықты</div>
					<div class="wb_card_v wb_c_green rv_plus_n">0</div>
					<div class="wb_card_s">базадағыдан көп</div>
				</div>
			<? endif ?>
		</div>

		<div class="wb_bar">
			<div class="wb_tabs rv_flt">
				<div class="wb_tab wb_tab_act" data-f="">Барлығы</div>
				<div class="wb_tab" data-f="todo">Саналмаған</div>
				<div class="wb_tab" data-f="done">Саналған</div>
				<div class="wb_tab" data-f="diff">Айырмасы бар</div>
			</div>
			<input type="text" class="wb_inp wb_search rv_search" placeholder="Шикізатты іздеу">
		</div>

		<div class="wb_box wb_table rv_table">
			<div class="wb_tr wb_th">
				<div class="wb_td wb_td_n">#</div>
				<div class="wb_td wb_td_name">Атауы</div>
				<div class="wb_td">Бастапқы</div>
				<div class="wb_td">Закуп</div>
				<div class="wb_td">Базада</div>
				<div class="wb_td rv_td_fact">Нақты (санадық)</div>
				<div class="wb_td">Айырма</div>
				<div class="wb_td">Шығын</div>
				<? if (!$cook): ?><div class="wb_td">Айырма, ₸</div><? endif ?>
			</div>
			<? $k = 0; foreach ($rows as $r): $c = rv::calc($r); $k++; ?>
				<div class="wb_tr rv_row" data-raw="<?=$r['raw_id']?>" data-start="<?=$r['qty_start']?>" data-in="<?=$r['qty_in']?>" data-db="<?=$r['qty_db']?>" data-price="<?=$r['price']?>" data-name="<?=wb::h(mb_strtolower($r['name']))?>">
					<div class="wb_td wb_td_n"><?=$k?></div>
					<div class="wb_td wb_td_name"><div class="wb_name"><?=wb::h($r['name'])?></div><div class="wb_small"><?=wb::h($r['unit'] ?: '—')?></div></div>
					<div class="wb_td" data-h="Бастапқы"><?=wb::qty($r['qty_start'])?></div>
					<div class="wb_td" data-h="Закуп"><?=($r['qty_in'] ? '+'.wb::qty($r['qty_in']) : '0')?></div>
					<div class="wb_td" data-h="Базада"><b class="<?=($r['qty_db'] < 0 ? 'wb_c_red' : '')?>"><?=wb::qty($r['qty_db'])?></b></div>
					<div class="wb_td rv_td_fact" data-h="Нақты">
						<? if ($posted): ?><b><?=wb::qty($r['fact'])?></b> <?=wb::h($r['unit'])?>
						<? else: ?><input type="text" inputmode="decimal" class="wb_inp rv_r_fact" value="<?=($r['fact'] !== null ? wb::qty($r['fact']) : '')?>" placeholder="<?=wb::h($r['unit'] ?: 'сан')?>"><? endif ?>
					</div>
					<div class="wb_td rv_diff" data-h="Айырма"><?=($c['diff'] === null ? '—' : '<b class="'.($c['diff'] < 0 ? 'wb_c_red' : ($c['diff'] > 0 ? 'wb_c_green' : '')).'">'.rv::qs($c['diff']).'</b>')?></div>
					<div class="wb_td rv_used" data-h="Шығын"><?=($c['used'] === null ? '—' : wb::qty($c['used']))?></div>
					<? if (!$cook): ?><div class="wb_td rv_diff_s" data-h="Айырма, ₸"><?=($c['diff'] === null || !$r['price'] ? '—' : wb::sum($c['diff'] * $r['price']))?></div><? endif ?>
				</div>
			<? endforeach ?>
			<? if (!$rows): ?>
				<div class="wb_empty"><i class="fal fa-boxes-alt"></i><div>Шикізат жоқ. Алдымен «Шикізат» бөліміне немесе накладнойға қосыңыз.</div></div>
			<? endif ?>
		</div>

		<div class="wb_actions">
			<? if (!$posted): ?>
				<div class="wb_btn wb_btn_green rv_save" data-post="1"><i class="fal fa-check-double"></i> Аяқтау (қалдықты теңестіру)</div>
				<div class="wb_btn rv_save" data-post="0"><i class="fal fa-save"></i> Сақтау (кейін жалғастыру)</div>
				<? if ($rev['id']): ?><div class="wb_btn wb_btn_red rv_delete"><i class="fal fa-trash-alt"></i> Өшіру</div><? endif ?>
				<div class="wb_small">Санамаған шикізатты бос қалдырыңыз, оның қалдығы өзгермейді.</div>
			<? elseif (!$cook): ?>
				<div class="wb_btn wb_btn_light rv_unpost"><i class="fal fa-undo"></i> Аяқтауды болдырмау</div>
				<div class="wb_small">Болдырмасаңыз, базадағы қалдық ревизияға дейінгі күйіне қайтады.</div>
			<? endif ?>
		</div>
	</div>

</div>

<? include "../block/footer.php"; ?>
