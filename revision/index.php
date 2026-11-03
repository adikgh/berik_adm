<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (!rv::can($user_right)) { header('location: /kassa/'); exit; }

	rv::install();
	$cmp = (int)$company;
	$br = (int)$branch;
	$cook = rv::is_cook($user_right);

	$list = db::query("select v.*, u.name u_name, u.surname u_surname,
			(select COUNT(*) from stock_revision_item i where i.revision_id = v.id) items
		from stock_revision v left join user u on u.id = v.user_id
		where v.company_id = '$cmp' and v.branch_id = '$br'
		order by v.rev_date desc, v.id desc limit 100");
	$rows = []; while ($list && $r = mysqli_fetch_assoc($list)) $rows[] = $r;

	// аяқталған ревизиялардың қорытындысы (айырма құны, шығын құны)
	$sum = [];
	if ($rows) {
		$ids = implode(',', array_map(function ($r) { return (int)$r['id']; }, $rows));
		$q = db::query("select revision_id, SUM((qty_fact - qty_db) * price) diff, SUM((qty_start + qty_in - qty_fact) * price) used
			from stock_revision_item where revision_id in ($ids) group by revision_id");
		while ($q && $r = mysqli_fetch_assoc($q)) $sum[(int)$r['revision_id']] = $r;
	}
	$draft = null; foreach ($rows as $r) if ($r['status'] == 0) { $draft = $r; break; }

	// site setting
	$menu_name = 'revision';
	$page_name = 'Ревизия';
	$wb_tab = 'revision';
	$css = ['waybill', 'revision'];
	$js = [];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? if (!$cook): ?><? include "../waybill/tabs.php"; ?><? endif ?>

	<div class="wb_bar">
		<div class="wb_small">Қоймадағы шикізатты санап, базадағы қалдықпен салыстырамыз. Аяқталғанда базадағы қалдық нақты санға теңеседі.</div>
		<div class="wb_bar_btns">
			<? if ($draft): ?>
				<a class="wb_btn wb_btn_light" href="/revision/item.php?id=<?=$draft['id']?>"><i class="fal fa-pen"></i> Жалғастыру (№ <?=$draft['number']?>)</a>
			<? endif ?>
			<a class="wb_btn wb_btn_green" href="/revision/item.php"><i class="fal fa-plus"></i> Жаңа ревизия</a>
		</div>
	</div>

	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_n">№</div>
			<div class="wb_td">Күні</div>
			<div class="wb_td wb_td_name">Кім санады</div>
			<div class="wb_td">Саналғаны</div>
			<? if (!$cook): ?>
				<div class="wb_td">Шығын құны</div>
				<div class="wb_td">Айырма құны</div>
			<? endif ?>
			<div class="wb_td wb_td_st">Күйі</div>
		</div>
		<? foreach ($rows as $r): $s = rv::status($r); $m = $sum[(int)$r['id']] ?? null; ?>
			<a class="wb_tr" href="/revision/item.php?id=<?=$r['id']?>">
				<div class="wb_td wb_td_n"><b><?=$r['number']?></b></div>
				<div class="wb_td" data-h="Күні"><?=wb::dt($r['rev_date'])?></div>
				<div class="wb_td wb_td_name">
					<div class="wb_name"><?=wb::h(trim($r['u_name'].' '.$r['u_surname']) ?: '—')?></div>
					<? if ($r['comment']): ?><div class="wb_small"><?=wb::h(mb_strimwidth($r['comment'], 0, 50, '…'))?></div><? endif ?>
				</div>
				<div class="wb_td" data-h="Саналғаны"><?=$r['items']?> түрі</div>
				<? if (!$cook): ?>
					<div class="wb_td" data-h="Шығын құны"><?=($r['status'] == 1 && $m ? wb::sum($m['used']) : '<span class="wb_small">—</span>')?></div>
					<div class="wb_td" data-h="Айырма құны"><? if ($r['status'] == 1 && $m): ?><b class="<?=($m['diff'] < 0 ? 'wb_c_red' : 'wb_c_green')?>"><?=wb::sum($m['diff'])?></b><? else: ?><span class="wb_small">—</span><? endif ?></div>
				<? endif ?>
				<div class="wb_td wb_td_st"><span class="wb_st wb_st_<?=$s[0]?>"><?=$s[1]?></span></div>
			</a>
		<? endforeach ?>
		<? if (!$rows): ?>
			<div class="wb_empty">
				<i class="fal fa-clipboard-list-check"></i>
				<div>Әлі ревизия жасалған жоқ</div>
				<a class="wb_btn wb_btn_green" href="/revision/item.php"><i class="fal fa-plus"></i> Бірінші ревизия</a>
			</div>
		<? endif ?>
	</div>

</div>

<? include "../block/footer.php"; ?>
