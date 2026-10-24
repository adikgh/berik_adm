<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	wb::install();
	$cmp = (int)$company;
	$arch = (int)@$_GET['archive'] ? 1 : 0;

	$list = db::query("select s.*, COUNT(w.id) cnt, IFNULL(SUM(w.total), 0) total, IFNULL(SUM(w.paid), 0) paid, MAX(w.doc_date) last_dt
		from supplier s left join waybill w on w.supplier_id = s.id and w.status = 1
		where s.company_id = '$cmp' and s.archive = '$arch'
		group by s.id order by s.name");

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Накладной';
	$wb_tab = 'suppliers';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page">

	<? include "tabs.php"; ?>

	<div class="wb_bar">
		<div class="wb_tabs">
			<a class="wb_tab <?=(!$arch ? 'wb_tab_act' : '')?>" href="/waybill/suppliers.php">Белсенді</a>
			<a class="wb_tab <?=($arch ? 'wb_tab_act' : '')?>" href="/waybill/suppliers.php?archive=1">Архив</a>
		</div>
		<div class="wb_btn wb_sup_new"><i class="fal fa-plus"></i> Жеткізуші қосу</div>
	</div>

	<!-- Жаңа / өзгерту формасы -->
	<div class="wb_box wb_sup_form dsp_n" data-id="0">
		<div class="wb_box_h"><div class="wb_sup_form_h">Жаңа жеткізуші</div></div>
		<div class="wb_grid">
			<label class="wb_fld"><span class="wb_lbl">Аты</span><input type="text" class="wb_inp wb_s_name" placeholder="Мысалы: Метро, Ержан ет"></label>
			<label class="wb_fld"><span class="wb_lbl">Телефон</span><input type="text" class="wb_inp wb_s_phone" placeholder="Міндетті емес"></label>
			<label class="wb_fld wb_fld_2"><span class="wb_lbl">Түсініктеме</span><input type="text" class="wb_inp wb_s_note" placeholder="Міндетті емес"></label>
		</div>
		<div class="wb_actions">
			<div class="wb_btn wb_btn_green wb_sup_save"><i class="fal fa-check"></i> Сақтау</div>
			<div class="wb_btn wb_btn_light wb_sup_cancel">Болдырмау</div>
		</div>
	</div>

	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_name">Жеткізуші</div>
			<div class="wb_td">Накладной</div>
			<div class="wb_td">Жалпы сома</div>
			<div class="wb_td">Төленді</div>
			<div class="wb_td">Қарыз</div>
			<div class="wb_td">Соңғы</div>
			<div class="wb_td wb_td_act"></div>
		</div>
		<? $n = 0; while ($s = mysqli_fetch_assoc($list)): $n++; $d = max(0, $s['total'] - $s['paid']); ?>
			<div class="wb_tr" data-id="<?=$s['id']?>" data-name="<?=wb::h($s['name'])?>" data-phone="<?=wb::h($s['phone'])?>" data-note="<?=wb::h($s['note'])?>">
				<div class="wb_td wb_td_name">
					<a class="wb_name" href="/waybill/?s=<?=$s['id']?>&p=all"><?=wb::h($s['name'])?></a>
					<div class="wb_small"><?=wb::h(trim($s['phone'].($s['phone'] && $s['note'] ? ' · ' : '').$s['note']))?></div>
				</div>
				<div class="wb_td" data-h="Накладной"><?=$s['cnt']?></div>
				<div class="wb_td" data-h="Жалпы сома"><b><?=wb::sum($s['total'])?></b></div>
				<div class="wb_td" data-h="Төленді"><?=wb::sum($s['paid'])?></div>
				<div class="wb_td" data-h="Қарыз"><?=($d > 0 ? '<a class="wb_c_red" href="/waybill/?s='.$s['id'].'&st=debt&p=all"><b>'.wb::sum($d).'</b></a>' : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td" data-h="Соңғы"><?=wb::dt($s['last_dt'])?></div>
				<div class="wb_td wb_td_act">
					<a class="wb_ic" title="Жаңа накладной" href="/waybill/item.php?s=<?=$s['id']?>"><i class="fal fa-file-plus"></i></a>
					<div class="wb_ic wb_sup_edit" title="Өзгерту"><i class="fal fa-pen"></i></div>
					<div class="wb_ic wb_sup_arch" data-v="<?=($arch ? 0 : 1)?>" title="<?=($arch ? 'Архивтен қайтару' : 'Архивке жіберу')?>"><i class="fal <?=($arch ? 'fa-box-open' : 'fa-archive')?>"></i></div>
				</div>
			</div>
		<? endwhile ?>
		<? if (!$n): ?>
			<div class="wb_empty"><i class="fal fa-truck"></i><div><?=($arch ? 'Архив бос' : 'Жеткізуші әлі жоқ. Накладной жасағанда да қосуға болады.')?></div></div>
		<? endif ?>
	</div>

</div>

<? include "../block/footer.php"; ?>
