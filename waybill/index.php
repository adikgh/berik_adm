<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	wb::install();
	$cmp = (int)$company;
	// бұл айда накладной әлі жоқ болса, бос тізім көрсетпей, бұл жылды ашамыз
	if (!isset($_GET['mode'])) {
		$m1 = date('Y-m-01');
		if (!(int)mysqli_fetch_assoc(db::query("select COUNT(*) n from waybill where company_id = '$cmp' and doc_date >= '$m1'"))['n']) $_GET['mode'] = 'y';
	}
	// Күн | Апта | Ай | Жыл (басқа беттердегідей). «Барлығы» тек карточкадан келгенде шығады
	$per = wb::per2('m', @$_GET['mode'] === 'a');
	$pf = $per['pf']; $pt = $per['pt'];

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

	// кезең ауыстырғышы басқан кезде сүзгі сақталады
	$per_q = http_build_query(array_filter(['s' => $sup ?: '', 'st' => (string)$st, 'q' => $q], 'strlen'));
	$per_q = $per_q ? '&'.$per_q : '';
	$st_names = ['' => 'Барлығы', 'ok' => 'Қабылданған', 'draft' => 'Жоба', 'debt' => 'Қарызы бар'];

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Кіріс тауарлары';
	$page_key = 'Incoming goods';
	$wb_tab = 'list';
	$css = ['waybill'];
	$js = ['waybill', 'st_pick'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page" data-ajx-nav=".wb_page">

	<? include "tabs.php"; ?>

	<!-- Кезең -->
	<div class="wb_bar">
		<div class="wb_per">
			<div class="wb_tabs">
				<? foreach (['d' => 'Күн', 'w' => 'Апта', 'm' => 'Ай', 'y' => 'Жыл'] + ($per['all'] ? ['a' => 'Барлығы'] : []) as $k => $v): ?>
					<a class="wb_tab <?=($per['mode'] == $k ? 'wb_tab_act' : '')?>" href="?mode=<?=$k?><?=($k == 'a' ? '' : $per['keep'])?><?=wb::h($per_q)?>"><?=$v?></a>
				<? endforeach ?>
			</div>
			<? if ($per['mode'] != 'a'): ?>
				<div class="wb_tabs wbg_nav wb_per_nav">
					<a class="wb_tab wbg_nav_b" href="?mode=<?=$per['mode']?>&d=<?=$per['prev']?><?=wb::h($per_q)?>" aria-label="Алдыңғы"><i class="far fa-chevron-left"></i></a>
					<div class="st_nav_t wbg_nav_t" data-pick="1" data-mode="<?=$per['mode']?>" data-d="<?=$pf?>" data-today="<?=date('Y-m-d')?>" data-q="<?=wb::h($per_q)?>"><?=$per['title']?></div>
					<? if ($per['next'] <= date('Y-m-d')): ?>
						<a class="wb_tab wbg_nav_b" href="?mode=<?=$per['mode']?>&d=<?=$per['next']?><?=wb::h($per_q)?>" aria-label="Келесі"><i class="far fa-chevron-right"></i></a>
					<? else: ?>
						<span class="wb_tab wbg_nav_b wb_per_off"><i class="far fa-chevron-right"></i></span>
					<? endif ?>
				</div>
			<? endif ?>
		</div>
	</div>

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
		<a class="wb_card wb_card_link" href="<?=wb::url('/waybill/', ['st' => 'debt', 'mode' => 'a', 'd' => null, 'page' => null])?>">
			<div class="wb_card_h">Жеткізушілерге қарыз</div>
			<div class="wb_card_v wb_c_red"><?=wb::sum($debt_all)?></div>
			<div class="wb_card_s">барлық уақыт бойынша</div>
		</a>
		<a class="wb_card wb_card_link" href="<?=wb::url('/waybill/', ['st' => 'draft', 'mode' => 'a', 'd' => null, 'page' => null])?>">
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
		<form class="wb_range" method="get" action="/waybill/" data-ajx=".wb_page">
			<? foreach (['mode', 'd', 'st'] as $k): if (@$_GET[$k]): ?><input type="hidden" name="<?=$k?>" value="<?=wb::h($_GET[$k])?>"><? endif; endforeach ?>
			<select name="s" class="wb_inp" onchange="this.form.requestSubmit()">
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
