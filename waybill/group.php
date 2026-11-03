<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Закуп топтап: кезеңдегі накладной жолдары мақсаты бойынша топталады,
	// бір тауардың жолдары біріктіріледі (raw_id бойынша, болмаса атауы бойынша).
	wb::install();
	$cmp = (int)$company;

	// Кезең: Күн | Апта | Ай | Жыл
	$per = wb::per2('m');
	$pf = $per['pf']; $pt = $per['pt'];

	// Жолдар
	$q = db::query("select i.*, w.id wid, w.number, w.doc_no, w.doc_date, s.name sname,
			r.name_kz r_kz, r.name_ru r_ru, r.grp r_grp, r.unit r_unit
		from waybill_item i
		join waybill w on w.id = i.waybill_id
		left join supplier s on s.id = w.supplier_id
		left join product_raw r on r.id = i.raw_id and r.company_id = w.company_id
		where w.company_id = '$cmp' and w.doc_date between '$pf' and '$pt'
		order by w.doc_date desc, w.id desc, i.id");

	$norm = function ($s) { return trim(preg_replace('/\s+/u', ' ', str_replace(['"', '«', '»', "'"], '', mb_strtolower((string)$s)))); };
	$gr = []; $all = 0;
	foreach (wb::$groups as $k => $v) $gr[$k] = ['sum' => 0, 'items' => []];
	while ($q && $i = mysqli_fetch_assoc($q)) {
		$rname = $i['raw_id'] ? ($i['r_kz'] ?: $i['r_ru']) : '';
		$name = $rname ?: $i['name'];
		$g = $i['r_grp'] ?: wb::grp_guess($rname.' '.$i['name']);
		if (!isset($gr[$g])) $g = 'other';
		// өлшемі бөлек болса, бірге қоспаймыз (кг мен шт)
		// шикізатқа байланған жол шикізат өлшемімен саналады (factor), әйтпесе өз өлшемімен
		$unit = $rname ? (fun::unit_norm($i['r_unit']) ?: $i['unit']) : $i['unit'];
		$bq = $rname ? (float)$i['quantity'] * wb::f($i) : (float)$i['quantity'];
		$key = ($rname ? 'r'.$i['raw_id'] : 'n'.$norm($i['name'])).'|'.$norm($unit);
		$it = &$gr[$g]['items'][$key];
		if (!$it) $it = ['raw_id' => $rname ? (int)$i['raw_id'] : 0, 'name' => $name, 'unit' => $unit, 'qty' => 0, 'sum' => 0, 'sup' => [], 'lines' => []];
		$it['qty'] += $bq;
		$it['sum'] += (float)$i['sum'];
		if ($i['sname']) $it['sup'][$i['sname']] = 1;
		$it['lines'][] = $i;
		unset($it);
		$gr[$g]['sum'] += (float)$i['sum'];
		$all += (float)$i['sum'];
	}
	foreach ($gr as $k => $v) uasort($gr[$k]['items'], function ($a, $b) { return $b['sum'] <=> $a['sum']; });

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Кіріс тауарлары';
	$page_key = 'Incoming goods';
	$wb_tab = 'group';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<!-- Кезең -->
	<?=wb::per2_bar($per)?>

	<div class="wb_cards wb_cards_3">
		<div class="wb_card">
			<div class="wb_card_h">Кезеңдегі закуп</div>
			<div class="wb_card_v"><?=wb::sum($all)?></div>
			<div class="wb_card_s"><?=wb::dt($pf)?> — <?=wb::dt($pt)?></div>
		</div>
		<? foreach (['raw', 'pack'] as $k): ?>
			<div class="wb_card">
				<div class="wb_card_h"><?=wb::$groups[$k]?></div>
				<div class="wb_card_v"><?=wb::sum($gr[$k]['sum'])?></div>
				<div class="wb_card_s"><?=count($gr[$k]['items'])?> түрі</div>
			</div>
		<? endforeach ?>
	</div>

	<? if (!$all): ?>
		<div class="wb_box">
			<div class="wb_empty"><i class="fal fa-box-open"></i><div>Бұл кезеңде закуп жоқ</div></div>
		</div>
	<? endif ?>

	<? foreach ($gr as $k => $g): if (!$g['items']) continue; ?>
		<div class="wb_box wb_table wbg">
			<div class="wb_box_h"><span><?=wb::$groups[$k]?></span><span><?=wb::sum($g['sum'])?></span></div>
			<div class="wb_tr wb_th">
				<div class="wb_td wb_td_name">Тауар</div>
				<div class="wb_td">Саны</div>
				<div class="wb_td">Орташа баға</div>
				<div class="wb_td">Рет</div>
				<div class="wb_td wbg_sum">Сома</div>
			</div>
			<? foreach ($g['items'] as $it): ?>
				<details class="wbg_it">
					<summary class="wb_tr">
						<div class="wb_td wb_td_name">
							<div class="wb_name"><i class="far fa-angle-right wbg_ar"></i> <?=wb::h($it['name'])?></div>
							<div class="wb_small"><?=wb::h(implode(', ', array_keys($it['sup'])))?><? if ($it['raw_id']): ?> · <a href="/waybill/raw.php?id=<?=$it['raw_id']?>">баға динамикасы</a><? endif ?></div>
						</div>
						<div class="wb_td" data-h="Саны"><?=wb::qty($it['qty'])?> <?=wb::h($it['unit'])?></div>
						<div class="wb_td" data-h="Орташа баға"><?=($it['qty'] > 0 ? wb::sum($it['sum'] / $it['qty']) : '—')?></div>
						<div class="wb_td" data-h="Рет"><?=count($it['lines'])?></div>
						<div class="wb_td wbg_sum" data-h="Сома"><b><?=wb::sum($it['sum'])?></b></div>
					</summary>
					<div class="wbg_lines">
						<? foreach ($it['lines'] as $l): ?>
							<div class="wbg_ln">
								<div class="wbg_ln_d"><?=wb::dt($l['doc_date'])?></div>
								<div class="wbg_ln_n">
									<div><?=wb::h($l['name'])?></div>
									<div class="wb_small"><?=($l['sname'] ? wb::h($l['sname']) : 'Жеткізуші көрсетілмеген')?> · <a href="/waybill/item.php?id=<?=$l['wid']?>">№<?=$l['number']?><?=($l['doc_no'] ? ' ('.wb::h($l['doc_no']).')' : '')?></a></div>
								</div>
								<div class="wbg_ln_q"><?=wb::qty($l['quantity'])?> <?=wb::h($l['unit'])?> × <?=wb::sum($l['price'])?></div>
								<div class="wbg_ln_s"><?=wb::sum($l['sum'])?></div>
							</div>
						<? endforeach ?>
					</div>
				</details>
			<? endforeach ?>
		</div>
	<? endforeach ?>

	<? if ($all): ?>
		<div class="wb_box wbg_all"><span>Кезеңнің жалпы закупы</span><b><?=wb::sum($all)?></b></div>
	<? endif ?>

</div>

<? include "../block/footer.php"; ?>
