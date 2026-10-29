<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	wb::install();
	$cmp = (int)$company;
	$per = wb::period();
	$pf = $per['from']; $pt = $per['to'];

	// сүзгі
	$sup = (int)@$_GET['s'];
	$st = @$_GET['st'];
	$q = trim((string)@$_GET['q']);

	$where = "w.company_id = '$cmp' and w.doc_date between '$pf' and '$pt'";
	if ($sup) $where .= " and w.supplier_id = '$sup'";
	if ($st === 'draft') $where .= " and w.status = 0";
	if ($st === 'ok') $where .= " and w.status = 1";
	if ($st === 'debt') $where .= " and w.status = 1 and w.total > w.paid";
	if ($q !== '') {
		$qs = wb::esc($q);
		$where .= " and (w.doc_no like '%$qs%' or w.comment like '%$qs%' or w.number = '".(int)$q."'
			or w.id in (select waybill_id from waybill_item where name like '%$qs%'))";
	}

	// карточкалар (тек қабылданғандар)
	$sm = mysqli_fetch_assoc(db::query("select COUNT(*) cnt, IFNULL(SUM(w.total), 0) total, IFNULL(SUM(w.paid), 0) paid
		from waybill w where w.company_id = '$cmp' and w.status = 1 and w.doc_date between '$pf' and '$pt'".($sup ? " and w.supplier_id = '$sup'" : '')));
	$drafts = (int)mysqli_fetch_assoc(db::query("select COUNT(*) n from waybill w where w.company_id = '$cmp' and w.status = 0"))['n'];
	$debt_all = mysqli_fetch_assoc(db::query("select IFNULL(SUM(w.total - w.paid), 0) d from waybill w where w.company_id = '$cmp' and w.status = 1 and w.total > w.paid"))['d'];

	// тізім
	$page = max(1, (int)@$_GET['page']);
	$lim = 50; $off = ($page - 1) * $lim;
	$found = (int)mysqli_fetch_assoc(db::query("select COUNT(*) n from waybill w where $where"))['n'];
	$pages = max(1, ceil($found / $lim));
	$list = db::query("select w.*, s.name supplier_name,
			(select COUNT(*) from waybill_item i where i.waybill_id = w.id) items
		from waybill w left join supplier s on s.id = w.supplier_id
		where $where order by w.doc_date desc, w.id desc limit $off, $lim");

	$suppliers = db::query("select id, name from supplier where company_id = '$cmp' and archive = 0 order by name");

	$p_names = ['month' => 'Бұл ай', 'prev' => 'Өткен ай', '3m' => '3 ай', 'year' => 'Бұл жыл', 'all' => 'Барлығы'];
	$st_names = ['' => 'Барлығы', 'ok' => 'Қабылданған', 'draft' => 'Жоба', 'debt' => 'Қарызы бар'];

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Кіріс тауарлары';
	$page_key = 'Incoming goods';
	$wb_tab = 'list';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<!-- Кезең -->
	<div class="wb_bar">
		<div class="wb_tabs">
			<? foreach ($p_names as $k => $v): ?>
				<a class="wb_tab <?=($per['p'] == $k ? 'wb_tab_act' : '')?>" href="<?=wb::url('/waybill/', ['p' => $k, 'from' => null, 'to' => null, 'page' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="wb_range" method="get" action="/waybill/">
			<? foreach (['s', 'st', 'q'] as $k): if (@$_GET[$k]): ?><input type="hidden" name="<?=$k?>" value="<?=wb::h($_GET[$k])?>"><? endif; endforeach ?>
			<input type="date" name="from" value="<?=($per['p'] == 'custom' ? $pf : '')?>" class="wb_inp">
			<span>—</span>
			<input type="date" name="to" value="<?=($per['p'] == 'custom' ? $pt : '')?>" class="wb_inp">
			<button class="wb_btn" type="submit"><i class="fal fa-check"></i></button>
		</form>
	</div>
	<div class="wb_sub"><?=wb::dt($pf)?> — <?=wb::dt($pt)?></div>

	<!-- Карточкалар -->
	<div class="wb_cards">
		<div class="wb_card">
			<div class="wb_card_h">Қабылданған накладной</div>
			<div class="wb_card_v"><?=(int)$sm['cnt']?></div>
			<div class="wb_card_s">осы кезеңде</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Сатып алу сомасы</div>
			<div class="wb_card_v"><?=wb::sum($sm['total'])?></div>
			<div class="wb_card_s">төленгені <?=wb::sum($sm['paid'])?></div>
		</div>
		<a class="wb_card wb_card_link" href="<?=wb::url('/waybill/', ['st' => 'debt', 'p' => 'all', 'from' => null, 'to' => null, 'page' => null])?>">
			<div class="wb_card_h">Жеткізушілерге қарыз</div>
			<div class="wb_card_v wb_c_red"><?=wb::sum($debt_all)?></div>
			<div class="wb_card_s">барлық уақыт бойынша</div>
		</a>
		<a class="wb_card wb_card_link" href="<?=wb::url('/waybill/', ['st' => 'draft', 'p' => 'all', 'from' => null, 'to' => null, 'page' => null])?>">
			<div class="wb_card_h">Жоба күйінде</div>
			<div class="wb_card_v wb_c_orange"><?=$drafts?></div>
			<div class="wb_card_s">әлі қабылданбаған</div>
		</a>
	</div>

	<!-- Сүзгі -->
	<div class="wb_bar">
		<div class="wb_tabs">
			<? foreach ($st_names as $k => $v): ?>
				<a class="wb_tab <?=((string)$st === (string)$k ? 'wb_tab_act' : '')?>" href="<?=wb::url('/waybill/', ['st' => $k, 'page' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="wb_range" method="get" action="/waybill/">
			<? foreach (['p', 'from', 'to', 'st'] as $k): if (@$_GET[$k]): ?><input type="hidden" name="<?=$k?>" value="<?=wb::h($_GET[$k])?>"><? endif; endforeach ?>
			<select name="s" class="wb_inp" onchange="this.form.submit()">
				<option value="">Барлық жеткізуші</option>
				<? while ($s = mysqli_fetch_assoc($suppliers)): ?>
					<option value="<?=$s['id']?>" <?=($sup == $s['id'] ? 'selected' : '')?>><?=wb::h($s['name'])?></option>
				<? endwhile ?>
			</select>
			<input type="text" name="q" value="<?=wb::h($q)?>" class="wb_inp wb_search" placeholder="Нөмір, тауар немесе түсініктеме">
		</form>
	</div>

	<!-- Тізім -->
	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_n">№</div>
			<div class="wb_td">Күні</div>
			<div class="wb_td wb_td_name">Жеткізуші</div>
			<div class="wb_td">Тауар</div>
			<div class="wb_td">Сома</div>
			<div class="wb_td">Қарыз</div>
			<div class="wb_td wb_td_st">Күйі</div>
		</div>
		<? while ($w = mysqli_fetch_assoc($list)): $s = wb::status($w); $d = wb::debt($w); ?>
			<a class="wb_tr" href="/waybill/item.php?id=<?=$w['id']?>">
				<div class="wb_td wb_td_n"><b><?=$w['number']?></b></div>
				<div class="wb_td" data-h="Күні"><?=wb::dt($w['doc_date'])?><? if ($w['doc_no']): ?><div class="wb_small">№ <?=wb::h($w['doc_no'])?></div><? endif ?></div>
				<div class="wb_td wb_td_name">
					<div class="wb_name"><?=($w['supplier_name'] ? wb::h($w['supplier_name']) : '<span class="wb_small">Жеткізуші көрсетілмеген</span>')?></div>
					<? if ($w['comment']): ?><div class="wb_small"><?=wb::h(mb_strimwidth($w['comment'], 0, 50, '…'))?></div><? endif ?>
				</div>
				<div class="wb_td" data-h="Тауар"><?=$w['items']?> түрі</div>
				<div class="wb_td" data-h="Сома"><b><?=wb::sum($w['total'])?></b></div>
				<div class="wb_td" data-h="Қарыз"><?=($w['status'] == 1 && $d > 0 ? '<span class="wb_c_red">'.wb::sum($d).'</span>' : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td wb_td_st"><span class="wb_st wb_st_<?=$s[0]?>"><?=$s[1]?></span></div>
			</a>
		<? endwhile ?>
		<? if (!$found): ?>
			<div class="wb_empty">
				<i class="fal fa-file-invoice"></i>
				<div>Накладной табылмады</div>
				<a class="wb_btn wb_btn_green" href="/waybill/item.php"><i class="fal fa-plus"></i> Жаңа накладной</a>
			</div>
		<? endif ?>
	</div>

	<? if ($pages > 1): ?>
		<div class="wb_pages">
			<? for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
				<a class="wb_pg <?=($i == $page ? 'wb_pg_act' : '')?>" href="<?=wb::url('/waybill/', ['page' => $i])?>"><?=$i?></a>
			<? endfor ?>
			<span class="wb_small">барлығы <?=$found?></span>
		</div>
	<? endif ?>

</div>

<? include "../block/footer.php"; ?>
