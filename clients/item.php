<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	clients::install();
	$cmp = (int)$company;
	$id = (int)@$_GET['id'];
	$OK = clients::OK;

	$cq = db::query("select * from company_client where id = '$id' and company_id = '$cmp'");
	if (!$cq || !mysqli_num_rows($cq)) { header('location: /clients/'); exit; }
	$c = mysqli_fetch_assoc($cq);
	$sg = clients::segment($c);

	// Орташа аралық (күн)
	$gap = ($c['orders_cnt'] > 1 && $c['first_dt']) ? round((strtotime($c['last_dt']) - strtotime($c['first_dt'])) / 86400 / ($c['orders_cnt'] - 1)) : 0;

	// 12 ай
	$m_start = date('Y-m-01 06:00:00', strtotime(date('Y-m-01').' -11 month'));
	$months = [];
	for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime(date('Y-m-01')." -$i month"))] = ['n' => 0, 's' => 0];
	$mq = db::query("select DATE_FORMAT(ro.ins_dt - INTERVAL 6 HOUR, '%Y-%m') m, COUNT(*) n, SUM(ro.total) s
		from retail_orders ro where ro.client_id = '$id' and $OK and ro.ins_dt >= '$m_start' group by m");
	while ($mq && $r = mysqli_fetch_assoc($mq)) if (isset($months[$r['m']])) $months[$r['m']] = $r;
	$m_max = 1; foreach ($months as $m) $m_max = max($m_max, (int)$m['s']);

	// Сүйікті тағамдар
	$top = db::query("select rop.product_id, SUM(rop.quantity) q, SUM(rop.quantity * rop.price) s, p.name_kz, p.name_ru
		from retail_orders_products rop join retail_orders ro on ro.id = rop.order_id left join product p on p.id = rop.product_id
		where ro.client_id = '$id' and $OK group by rop.product_id order by q desc limit 8");

	// Формат бойынша
	$types = [1 => 'Жеткізу', 2 => 'Өзімен', 3 => 'Сервис', 4 => 'Кафе'];
	$tq = db::query("select ro.order_type t, COUNT(*) n from retail_orders ro where ro.client_id = '$id' and $OK group by ro.order_type order by n desc");

	// Тапсырыстар тарихы
	$orders = db::query("select ro.* from retail_orders ro where ro.client_id = '$id' and ro.paid = 1 order by ro.ins_dt desc limit 100");

	// site setting
	$menu_name = 'clients';
	$page_name = 'Клиент';
	$css = ['clients'];
	$js = ['clients'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page">

	<a class="cl_back" href="/clients/"><i class="fal fa-arrow-left"></i> Клиенттер</a>

	<div class="cl_profile">
		<div class="cl_box cl_pf_card">
			<div class="cl_pf_top">
				<div class="cl_avatar"><i class="fal fa-user"></i></div>
				<div>
					<div class="cl_pf_name"><?=($c['name'] ? htmlspecialchars($c['name']) : 'Аты жазылмаған')?></div>
					<a class="cl_pf_phone" href="tel:8<?=$c['phone']?>"><?=clients::phone_view($c['phone'])?></a>
				</div>
				<span class="cl_seg cl_seg_<?=$sg[0]?>"><?=$sg[1]?></span>
			</div>

			<div class="cl_form" data-id="<?=$c['id']?>">
				<label class="cl_lbl">Аты</label>
				<input type="text" class="cl_inp cl_f_name" value="<?=htmlspecialchars((string)$c['name'])?>" placeholder="Клиенттің аты">
				<label class="cl_lbl">Мекенжай</label>
				<input type="text" class="cl_inp cl_f_address" value="<?=htmlspecialchars((string)$c['address'])?>" placeholder="Мекенжай">
				<label class="cl_lbl">Ескертпе</label>
				<textarea class="cl_inp cl_f_note" rows="3" placeholder="Мысалы: пиязсыз, домофон істемейді"><?=htmlspecialchars((string)$c['note'])?></textarea>
				<div class="cl_form_btns">
					<div class="cl_btn cl_btn_green cl_save"><i class="fal fa-check"></i> Сақтау</div>
					<? if ($c['archive']): ?>
						<div class="cl_btn cl_btn_light cl_archive" data-v="0"><i class="fal fa-undo"></i> Архивтен шығару</div>
					<? else: ?>
						<div class="cl_btn cl_btn_light cl_archive" data-v="1"><i class="fal fa-archive"></i> Архивке</div>
					<? endif ?>
				</div>
			</div>
		</div>

		<div class="cl_pf_right">
			<div class="cl_cards cl_cards_3">
				<div class="cl_card"><div class="cl_card_h">Тапсырыс саны</div><div class="cl_card_v"><?=$c['orders_cnt']?></div></div>
				<div class="cl_card"><div class="cl_card_h">Жалпы сома</div><div class="cl_card_v cl_c_green"><?=clients::sum($c['total_sum'])?></div></div>
				<div class="cl_card"><div class="cl_card_h">Орташа чек</div><div class="cl_card_v"><?=clients::sum($c['orders_cnt'] ? $c['total_sum'] / $c['orders_cnt'] : 0)?></div></div>
				<div class="cl_card"><div class="cl_card_h">Алғашқы тапсырыс</div><div class="cl_card_v cl_v_sm"><?=clients::dt($c['first_dt'], false)?></div><div class="cl_card_s"><?=clients::ago($c['first_dt'])?></div></div>
				<div class="cl_card"><div class="cl_card_h">Соңғы тапсырыс</div><div class="cl_card_v cl_v_sm"><?=clients::dt($c['last_dt'], false)?></div><div class="cl_card_s"><?=clients::ago($c['last_dt'])?></div></div>
				<div class="cl_card"><div class="cl_card_h">Орташа аралық</div><div class="cl_card_v cl_v_sm"><?=($gap ? $gap.' күн' : '—')?></div><div class="cl_card_s">екі тапсырыстың арасы</div></div>
			</div>

			<div class="cl_box">
				<div class="cl_box_h"><div>Соңғы 12 айдағы шығыны</div></div>
				<div class="cl_chart">
					<? foreach ($months as $ym => $m): ?>
						<div class="cl_col" title="<?=clients::month($ym).' '.substr($ym, 0, 4)?>: <?=(int)$m['n']?> тапсырыс, <?=clients::sum($m['s'])?>">
							<div class="cl_col_v"><?=((int)$m['n'] ?: '')?></div>
							<div class="cl_col_bar"><div class="cl_col_back" style="height: <?=round($m['s'] * 100 / $m_max, 1)?>%"></div></div>
							<div class="cl_col_m"><?=clients::month($ym)?></div>
						</div>
					<? endforeach ?>
				</div>
			</div>
		</div>
	</div>

	<div class="cl_two">
		<div class="cl_box">
			<div class="cl_box_h"><div>Сүйікті тағамдары</div></div>
			<? $has = false; while ($t = mysqli_fetch_assoc($top)): $has = true; ?>
				<div class="cl_li">
					<div><?=htmlspecialchars($t['name_kz'] ?: ($t['name_ru'] ?: '#'.$t['product_id']))?></div>
					<div class="cl_small"><b><?=(int)$t['q']?> рет</b> · <?=clients::sum($t['s'])?></div>
				</div>
			<? endwhile ?>
			<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
		</div>
		<div class="cl_box">
			<div class="cl_box_h"><div>Тапсырыс форматы</div></div>
			<? $has = false; while ($t = mysqli_fetch_assoc($tq)): $has = true; ?>
				<div class="cl_li">
					<div><?=(@$types[$t['t']] ?: '—')?></div>
					<div class="cl_small"><b><?=$t['n']?></b> · <?=round($t['n'] * 100 / max(1, $c['orders_cnt']))?>%</div>
				</div>
			<? endwhile ?>
			<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
		</div>
	</div>

	<div class="cl_box cl_table">
		<div class="cl_box_h"><div>Тапсырыстар тарихы</div></div>
		<div class="cl_tr cl_th">
			<div class="cl_td cl_td_n">№</div>
			<div class="cl_td">Күні</div>
			<div class="cl_td">Формат</div>
			<div class="cl_td cl_td_name">Мекенжай</div>
			<div class="cl_td">Сома</div>
			<div class="cl_td cl_td_seg">Статус</div>
		</div>
		<? while ($o = mysqli_fetch_assoc($orders)): ?>
			<? $sts = fun::order_sts($o['order_status']); ?>
			<div class="cl_tr">
				<div class="cl_td cl_td_n"><?=($o['number'] ?: '—')?></div>
				<div class="cl_td" data-h="Күні"><?=clients::dt($o['ins_dt'])?></div>
				<div class="cl_td" data-h="Формат"><?=(@$types[$o['order_type']] ?: '—')?></div>
				<div class="cl_td cl_td_name"><span class="cl_small"><?=htmlspecialchars((string)$o['address'])?></span></div>
				<div class="cl_td" data-h="Сома"><b><?=clients::sum($o['total'])?></b></div>
				<div class="cl_td cl_td_seg"><span class="cl_seg" style="<?=(@$sts['clr'] ? 'color:'.$sts['clr'].';background:'.$sts['clr'].'1a' : '')?>"><?=htmlspecialchars(t::w('st:'.trim((string)@$sts['name_ru']), (string)@$sts['name_kz']))?></span></div>
			</div>
		<? endwhile ?>
	</div>

</div>

<? include "../block/footer.php"; ?>
