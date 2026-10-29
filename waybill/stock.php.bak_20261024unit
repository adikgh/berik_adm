<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	wb::install();
	$cmp = (int)$company;
	$br = (int)$branch;
	$q = trim((string)@$_GET['q']);

	$where = "r.company_id = '$cmp'";
	if ($q !== '') { $qs = wb::esc($q); $where .= " and (r.name_kz like '%$qs%' or r.name_ru like '%$qs%')"; }

	$list = db::query("select r.id, r.name_kz, r.name_ru, r.unit, r.price, IFNULL(st.quantity, 0) qty, st.upd_dt,
			(select MAX(w.doc_date) from waybill_item i join waybill w on w.id = i.waybill_id
				where i.raw_id = r.id and w.status = 1 and w.branch_id = '$br') last_in
		from product_raw r
		left join product_raw_stock st on st.raw_id = r.id and st.company_id = '$cmp' and st.branch_id = '$br'
		where $where order by (IFNULL(st.quantity, 0) > 0) desc, r.name_kz");

	$rows = []; $value = 0; $has = 0;
	while ($list && $r = mysqli_fetch_assoc($list)) {
		$rows[] = $r;
		if ($r['qty'] > 0) { $has++; $value += $r['qty'] * $r['price']; }
	}

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Накладной';
	$wb_tab = 'stock';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<div class="wb_cards wb_cards_3">
		<div class="wb_card">
			<div class="wb_card_h">Шикізат түрі</div>
			<div class="wb_card_v"><?=count($rows)?></div>
			<div class="wb_card_s">қоймада бары <?=$has?></div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Қалдықтың құны</div>
			<div class="wb_card_v"><?=wb::sum($value)?></div>
			<div class="wb_card_s">соңғы сатып алу бағасымен</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Филиал</div>
			<div class="wb_card_v"><?=$br?></div>
			<div class="wb_card_s">қалдық филиал бойынша жүреді</div>
		</div>
	</div>

	<div class="wb_bar">
		<div class="wb_small">Қалдық накладной қабылданғанда көбейеді. Сатылымнан азаю әзірге қосылған жоқ.</div>
		<form class="wb_range" method="get" action="/waybill/stock.php">
			<input type="text" name="q" value="<?=wb::h($q)?>" class="wb_inp wb_search" placeholder="Шикізатты іздеу">
		</form>
	</div>

	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_n">#</div>
			<div class="wb_td wb_td_name">Атауы</div>
			<div class="wb_td">Қалдық</div>
			<div class="wb_td">Соңғы баға</div>
			<div class="wb_td">Құны</div>
			<div class="wb_td">Соңғы келгені</div>
		</div>
		<? foreach ($rows as $k => $r): ?>
			<div class="wb_tr <?=($r['qty'] <= 0 ? 'wb_tr_mut' : '')?>">
				<div class="wb_td wb_td_n"><?=$k + 1?></div>
				<div class="wb_td wb_td_name"><a class="wb_name" href="/waybill/?q=<?=urlencode($r['name_kz'] ?: $r['name_ru'])?>&p=all"><?=wb::h($r['name_kz'] ?: $r['name_ru'])?></a></div>
				<div class="wb_td" data-h="Қалдық"><b class="<?=($r['qty'] < 0 ? 'wb_c_red' : '')?>"><?=wb::qty($r['qty'])?></b> <?=wb::h($r['unit'])?></div>
				<div class="wb_td" data-h="Соңғы баға"><?=($r['price'] ? wb::sum($r['price']) : '—')?></div>
				<div class="wb_td" data-h="Құны"><?=($r['qty'] > 0 && $r['price'] ? wb::sum($r['qty'] * $r['price']) : '—')?></div>
				<div class="wb_td" data-h="Соңғы келгені"><?=wb::dt($r['last_in'])?></div>
			</div>
		<? endforeach ?>
		<? if (!$rows): ?>
			<div class="wb_empty"><i class="fal fa-boxes-alt"></i><div>Шикізат табылмады</div></div>
		<? endif ?>
	</div>

</div>

<? include "../block/footer.php"; ?>
