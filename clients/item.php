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

	// Лоялдық: кэшбэк балансы, қозғалысы, сыйлыққа санауыш (компанияда қосулы болса ғана)
	require_once "../config/loyalty.php";
	$lc = loyalty::on() ? loyalty::cfg($cmp) : null;
	$lc_info = null; $lc_moves = false; $lc_gifts = false; $lc_msg = '';
	// Кэшбэкті қолмен қосу (+) не алу (−)
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	if ($lc && isset($_POST['cb_adjust'])) {
		$amt = (int)preg_replace('/[^\d-]/', '', (string)@$_POST['cb_amount']);
		if (@$_POST['cb_sign'] == '-') $amt = -abs($amt); else $amt = abs($amt);
		$note = trim(strip_tags((string)@$_POST['cb_note']));
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $lc_msg = 'Бетті жаңартып, қайта көріңіз';
		elseif ($note === '') $lc_msg = 'Себебін жазыңыз';
		elseif (loyalty::manual($cmp, $id, $amt, $note, $user_id) === false) $lc_msg = 'Сақталмады: сома дұрыс емес не баланс жетпейді';
		else { header('location: /clients/item.php?id='.$id.'&cb=1'); exit; }
	}
	if (@$_GET['cb']) $lc_msg = 'Кэшбэк балансы өзгерді';
	if ($lc && ($lc['cashback'] || $lc['gift'] || (int)@$c['cashback_balance'])) {
		loyalty::sync($cmp);
		$c = mysqli_fetch_assoc(db::query("select * from company_client where id = '$id'"));
		$lc_info = loyalty::client_info($cmp, $c);
		$lc_moves = db::query("select k.*, o.number from company_client_cashback k left join retail_orders o on o.id = k.order_id where k.client_id = '$id' order by k.id desc limit 50");
		$lc_gifts = db::query("select g.*, o.number from company_client_gift g left join retail_orders o on o.id = g.order_id where g.client_id = '$id' order by g.id desc limit 20");
	}
	$lc_t = ['earn' => 'Түсті', 'spend' => 'Жұмсалды', 'cancel' => 'Қайтарылды (статус)', 'refund' => 'Клиентке қайтты', 'manual' => 'Қолмен'];

	// site setting
	$menu_name = 'clients';
	$page_name = 'Клиент';
	$page_back = ['/clients/', 'Клиенттер'];
	$css = ['clients', 'loyalty'];
	$js = ['clients'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page">


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

			<? if ($c['user_id']): include_once "../config/client_link.php"; $cab = client_link((int)$c['user_id']); ?>
				<div class="cl_form cl_cab">
					<label class="cl_lbl">Жеке кабинет сілтемесі</label>
					<input type="text" class="cl_inp" value="<?=htmlspecialchars($cab)?>" readonly onclick="this.select()">
					<div class="cl_form_btns">
						<div class="cl_btn cl_btn_green" onclick="var i=this.parentNode.previousElementSibling;i.select();document.execCommand('copy');this.innerHTML='<i class=&quot;fal fa-check&quot;></i> Көшірілді'"><i class="fal fa-copy"></i> Көшіру</div>
						<a class="cl_btn cl_btn_light" target="_blank" rel="noopener" href="https://wa.me/7<?=htmlspecialchars($c['phone'])?>?text=<?=rawurlencode('Сіздің жеке кабинетіңіз: '.$cab)?>"><i class="fab fa-whatsapp"></i> WhatsApp</a>
					</div>
				</div>
			<? endif ?>
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

	<? if ($lc_info): ?>
	<div class="cl_two">
		<div class="cl_box lc_cl_box">
			<div class="cl_box_h"><div><i class="fal fa-coins"></i> Кэшбэк<?=($lc['cashback'] ? ' · '.$lc['label'] : '')?></div><div class="cl_card_v cl_c_green"><?=clients::sum($c['cashback_balance'])?></div></div>
			<? $has = false; while ($lc_moves && $k = mysqli_fetch_assoc($lc_moves)): $has = true; ?>
				<div class="cl_li">
					<div><?=@$lc_t[$k['type']] ?: htmlspecialchars($k['type'])?><?=($k['number'] ? ' · #'.$k['number'] : '')?> <span class="cl_small"><?=clients::dt($k['ins_dt'])?></span>
						<? if ($k['type'] == 'earn' && $k['base_sum'] > 0): ?><div class="cl_small lc_mv_s">Тапсырыс <?=clients::sum($k['base_sum'])?> · <?=rtrim(rtrim(number_format($k['percent'], 2, '.', ''), '0'), '.')?>%</div><? endif ?>
						<? if ($k['type'] == 'manual' && $k['note']): ?><div class="cl_small lc_mv_s"><?=htmlspecialchars($k['note'])?></div><? endif ?></div>
					<div class="cl_small"><b class="<?=($k['amount'] > 0 ? 'cl_c_green' : '')?>"><?=($k['amount'] > 0 ? '+' : '−').clients::sum(abs($k['amount']))?></b> · <?=clients::sum($k['balance_after'])?></div>
				</div>
			<? endwhile ?>
			<? if (!$has): ?><div class="cl_empty">Әзірге қозғалыс жоқ</div><? else: $tt = mysqli_fetch_assoc(db::query("select coalesce(sum(case when amount > 0 then amount end), 0) p, coalesce(-sum(case when amount < 0 then amount end), 0) m, coalesce(sum(case when type = 'earn' then base_sum end), 0) b, sum(type = 'earn') n from company_client_cashback where client_id = '$id'")); ?>
				<div class="cl_li lc_mv_total">
					<div><b>Барлығы</b> <span class="cl_small"><?=(int)$tt['n']?> тапсырыс · <?=clients::sum($tt['b'])?></span></div>
					<div class="cl_small"><b class="cl_c_green">+<?=clients::sum($tt['p'])?></b><?=($tt['m'] ? ' · −'.clients::sum($tt['m']) : '')?> · <b><?=clients::sum($c['cashback_balance'])?></b></div>
				</div>
			<? endif ?>
		</div>
		<div class="cl_box lc_cl_box">
			<? if ($lc['gift']): $nx = $lc_info['next']; $cnt = (int)$lc_info['gift_cnt'];
				// Белгілер: толық цикл 1..ең үлкен деңгей (мысалы 1–20). Жасалғаны жасыл ✓, сыйлық орындары сыйлық белгісімен
				$tg_k = $lc_info['gift_due'] ? $lc_info['gift_k'] : ($nx ? $nx['k'] : 0);
				$mx = 0; foreach ($lc['tiers'] as $t) $mx = max($mx, $t['every']);
				$from = $mx ? (int)floor($cnt / $mx) * $mx : 0;
				$got = []; // берілген сыйлықтар: нешінші тапсырыста
				if (loyalty::tiers_on()) { $gq = db::query("select order_no from company_client_gift where client_id = '$id' and order_no is not null"); while ($gq && $gr = mysqli_fetch_assoc($gq)) $got[(int)$gr['order_no']] = true; } ?>
				<div class="cl_box_h"><div><i class="fal fa-gift"></i> Сыйлық</div><div class="cl_small"><b><?=$cnt?></b> тапсырыс саналды</div></div>
				<? if ($tg_k): ?>
					<div class="lc_stamps">
						<? for ($i = $from + 1; $i <= $from + $mx; $i++): $ti = loyalty::tier_for($cmp, $i); $done = $i <= $cnt; ?>
							<span class="lc_stamp <?=($done ? 'lc_stamp_on' : '').($ti ? ' lc_stamp_gift' : '').($ti && $i == $lc_info['gift_k'] && $lc_info['gift_due'] ? ' lc_stamp_due' : '').(isset($got[$i]) ? ' lc_stamp_got' : '')?>" title="<?=$i?><?=($ti ? ': '.htmlspecialchars($ti['name']).(isset($got[$i]) ? ' (алды)' : '') : '')?>"><?=($ti ? '<i class="fal fa-gift"></i><small>'.$i.'</small>' : ($done ? '✓' : $i))?></span>
						<? endfor ?>
					</div>
					<? if ($lc_info['gift_due']): ?>
						<div class="lc_gift_now">Келесі (<?=$lc_info['gift_k']?>-ші) тапсырысы — сыйлық: <b><?=htmlspecialchars($lc_info['gift_name'])?></b></div>
						<? if ($nx): ?><div class="cl_small">Одан кейін <?=$nx['k']?>-ші тапсырысқа: <?=htmlspecialchars($nx['tier']['name'])?></div><? endif ?>
					<? else: ?>
						<div class="cl_small"><?=$nx['k']?>-ші тапсырысқа: <b><?=htmlspecialchars($nx['tier']['name'])?></b> · тағы <?=($nx['k'] - $cnt)?> тапсырыс</div>
					<? endif ?>
				<? endif ?>
			<? else: ?>
				<div class="cl_box_h"><div><i class="fal fa-gift"></i> Сыйлықтар</div></div>
			<? endif ?>
			<? while ($lc_gifts && $g = mysqli_fetch_assoc($lc_gifts)): ?>
				<div class="cl_li"><div><?=htmlspecialchars($g['gift_name'])?><?=($g['number'] ? ' · #'.$g['number'] : '')?></div><div class="cl_small"><?=clients::dt($g['ins_dt'])?></div></div>
			<? endwhile ?>
		</div>
	</div>
	<? endif ?>

	<div class="cl_two">
		<div class="cl_box">
			<div class="cl_box_h"><div>Сүйікті тағамдары</div></div>
			<? $has = false; while ($t = mysqli_fetch_assoc($top)): $has = true; ?>
				<div class="cl_li">
					<div><?=htmlspecialchars(trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)($t['name_kz'] ?: ($t['name_ru'] ?: '#'.$t['product_id'])), ENT_QUOTES, 'UTF-8')))))?></div>
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
