<? include "../../config/core.php";
	// Кухня экраны: тапсырыстар кезегі (Жаңа → Дайындалуда → Дайын). Бұрынғы Кухня сайтынан (ktc) көшті.
	// Кухня хостында (ktc.* / ktcb.*) басты бет, админде де ашылады: /orders/kitchen/. Әрекеттер: get.php.
	if (!$user_id) { header('location: /'); exit(); }

	// Кезекте: Жаңа (1) + Дайындалуда (8). Дайын: бүгін дайын болғандар (2 және одан әрі).
	$sort = @$_GET['sort'] == 'ready' ? 'ready' : 'queue';
	$cid = (int)$company;
	$types = "1, 2, 4"; // Жеткізу, Өзімен, Кафе (Сервис кухняға келмейді)
	if ($sort == 'queue') {
		$orders = db::query("select * from retail_orders where ins_dt BETWEEN '$start_cdate' and '$end_cdate' and `paid` = 1 and company_id = '$cid' and `order_type` in ($types) and `order_status` in (1, 8) order by ins_dt asc, number asc");
	} else {
		$orders = db::query("select o.* from retail_orders o
			join (select order_id, max(ins_dt) dt from retail_orders_log where field = 'status' and new_value = '2' group by order_id) l on l.order_id = o.id
			where o.ins_dt BETWEEN '$start_cdate' and '$end_cdate' and o.`paid` = 1 and o.company_id = '$cid' and o.`order_type` in ($types) and o.`order_status` not in (1, 8, 5, 6, 10)
			order by l.dt desc limit 60");
	}

	// Дайын болған уақыт (Дайын табы үшін)
	$ready_dt = [];
	if ($sort == 'ready') {
		$rq = db::query("select l.order_id, max(l.ins_dt) dt from retail_orders_log l join retail_orders o on o.id = l.order_id where o.company_id = '$cid' and o.ins_dt BETWEEN '$start_cdate' and '$end_cdate' and l.field = 'status' and l.new_value = '2' group by l.order_id");
		while ($r = mysqli_fetch_assoc($rq)) $ready_dt[(int)$r['order_id']] = $r['dt'];
	}

	$menu_name = 'kitchen';
	$page_name = $sort == 'queue' ? t::w('In queue', 'Кезекте') : t::w('Ready today', 'Бүгін дайын');
	$css = ['dist', 'kitchen'];
	$js = ['dist', 'kitchen'];
	$o_cnt = $orders ? mysqli_num_rows($orders) : 0;
	$tp_nm = [1 => t::w('Delivery', 'Жеткізу'), 2 => t::w('Pickup', 'Өзімен'), 4 => t::w('Cafe', 'Кафе')];
	$tp_ic = [1 => 'fa-car', 2 => 'fa-walking', 4 => 'fa-utensils'];
	$st_nm = [1 => t::st(1), 8 => t::st(8), 2 => t::st(2), 11 => t::st(11), 3 => t::st(3), 9 => t::st(9), 4 => t::st(4)];
?>
<? include "../../block/header.php"; ?>
	<div class="dsp">

	<div class="bp_page kt_page" data-sort="<?=$sort?>">

		<div class="bp_head">
			<div class="bl_c">
				<div class="bp_head_t">
					<div class="bp_tabs kt_tabs"><span class="bp_tabs_sl"></span>
						<a class="bp_tab <?=($sort == 'queue' ? 'bp_tab_act' : '')?>" href="/orders/kitchen/"><?=t::w('In queue', 'Кезекте')?></a>
						<a class="bp_tab <?=($sort == 'ready' ? 'bp_tab_act' : '')?>" href="/orders/kitchen/?sort=ready"><?=t::w('Ready', 'Дайын')?></a>
					</div>
					<div class="kt_head_r">
						<div class="kt_snd on_snd" title="<?=t::h('New order sound', 'Жаңа тапсырыс дыбысы')?>"><i class="far fa-volume-mute"></i></div>
						<div class="bp_cnt">
							<span><?=t::w('Orders count', 'Тапсырыс')?></span>
							<b class="pp_number"><?=$o_cnt?></b>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="bl_c">
			<div class="uc_u kt_list">
				<? if ($o_cnt): ?>
					<? while ($o = mysqli_fetch_assoc($orders)): ?>
						<? $branch_d = $o['branch_id'] ? fun::branch($o['branch_id']) : 0;
							$ot = (int)$o['order_type']; $st = (int)$o['order_status'];
							$nm = (string)@$branch_d['code'].$o['number'];
							$pre = trim((string)$o['preorder_dt']);
							$note = trim(strip_tags((string)$o['additional'])); $cmt = trim(strip_tags((string)$o['comment'])); ?>
						<div class="uc_ui kt_card kt_st<?=$st?>" data-id="<?=(int)$o['id']?>">
							<div class="uc_uil2">
								<div class="kt_top">
									<div class="kt_nmb"><?=htmlspecialchars($nm)?></div>
									<div class="kt_info">
										<div class="kt_tags">
											<span class="kt_tp kt_tp<?=$ot?>"><i class="far <?=@$tp_ic[$ot]?>"></i> <?=@$tp_nm[$ot]?></span>
											<? if (isset($st_nm[$st])): ?><span class="bp_st bp_st<?=$st?>"><?=$st_nm[$st]?></span><? endif ?>
										</div>
										<div class="bp_tm">
											<b class="bp_time"><?=date("H:i", strtotime($o['ins_dt']))?></b>
											<? if ($sort == 'queue'): ?><span class="kt_wait" data-t="<?=strtotime($o['ins_dt'])?>"></span><? endif ?>
											<? if ($sort == 'ready' && isset($ready_dt[(int)$o['id']])): ?><span class="kt_done"><i class="far fa-check"></i> <?=date("H:i", strtotime($ready_dt[(int)$o['id']]))?> · <?=max(0, (int)round((strtotime($ready_dt[(int)$o['id']]) - strtotime($o['ins_dt'])) / 60))?> <?=t::w('min', 'мин')?></span><? endif ?>
											<i class="far fa-history bp_hist_ic on_hist" data-id="<?=(int)$o['id']?>" data-nm="<?=htmlspecialchars($nm)?>" title="<?=t::h('Status history', 'Статус тарихы')?>"></i>
										</div>
									</div>
								</div>
								<? if ($pre !== ''): ?><div class="kt_pre"><i class="far fa-bell"></i> <?=t::w('Preorder', 'Предзаказ')?>: <b><?=htmlspecialchars($pre)?></b></div><? endif ?>

								<div class="kt_items">
									<? $ps = db::query("select * from retail_orders_products where order_id = '".(int)$o['id']."' order by ins_dt asc"); ?>
									<? while ($p = mysqli_fetch_assoc($ps)): $pd = product::product($p['product_id']); ?>
										<div class="kt_it">
											<b class="kt_q"><?=(float)$p['quantity'] + 0?></b>
											<span class="kt_n"><?=fun::name_html(@$pd['name_ru'])?></span>
										</div>
									<? endwhile ?>
								</div>

								<? if ($note !== '' || $cmt !== ''): ?>
									<div class="kt_note"><i class="far fa-comment-alt-lines"></i> <span><?=htmlspecialchars(trim($note.($note !== '' && $cmt !== '' ? ' · ' : '').$cmt))?></span></div>
								<? endif ?>

								<div class="kt_act">
									<? if ($st == 1): ?>
										<div class="btn kt_btn kt_btn_go on_kst" data-id="<?=(int)$o['id']?>" data-st="8"><i class="far fa-fire"></i> <?=t::w('Cook', 'Дайындау')?></div>
									<? endif ?>
									<? if ($st == 1 || $st == 8): ?>
										<div class="btn kt_btn kt_btn_ok on_kst" data-id="<?=(int)$o['id']?>" data-st="2"><i class="far fa-check"></i> <?=t::w('Ready', 'Дайын')?></div>
									<? elseif ($st == 2): ?>
										<div class="btn kt_btn kt_btn_back on_kst" data-id="<?=(int)$o['id']?>" data-st="8" data-ask="<?=t::h('Return the order to cooking?', 'Тапсырысты қайта «Дайындалуда» күйіне қайтару керек пе?')?>"><i class="far fa-undo"></i> <?=t::w('Return', 'Қайтару')?></div>
									<? endif ?>
								</div>
							</div>
						</div>
					<? endwhile ?>
				<? else: ?>
					<div class="ds_nr"><i class="fal fa-inbox"></i><p><?=($sort == 'queue' ? t::w('Queue is empty', 'Кезекте тапсырыс жоқ') : t::w('No orders', 'Тапсырыс жоқ'))?></p></div>
				<? endif ?>
			</div>
		</div>

	</div>

	<div class="bp_dr bp_hist_sh">
		<div class="bp_dr_bg"></div>
		<div class="bp_dr_c">
			<div class="bp_dr_grab"></div>
			<div class="bp_sh_h"><span><?=t::w('History', 'Тарих')?> <b class="bp_hist_nm"></b></span> <span class="bp_dr_x"><i class="far fa-times"></i></span></div>
			<div class="oh_list"></div>
		</div>
	</div>

	</div>
	<script>
		window.DSP_GET = '/orders/kitchen/get.php?'; // assets/js/dist.js: статус тарихы осы адрестен
		window.KT = { min: <?=json_encode(t::w('min', 'мин'))?>, err: <?=json_encode(t::w('Status changed, page refreshed', 'Статус өзгерді, бет жаңартылды'))?> };
	</script>

<? include "../../block/footer.php"; ?>
