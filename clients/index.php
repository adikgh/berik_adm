<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	@set_time_limit(120);
	clients::install();
	clients::sync($company);

	$cmp = (int)$company;
	$OK = clients::OK;
	$per = clients::period();
	$ps = $per['start']; $pe = $per['end'];

	// ---- Кезең карточкалары ----
	$st = mysqli_fetch_assoc(db::query("select COUNT(DISTINCT ro.client_id) act, COUNT(*) cnt, IFNULL(SUM(ro.total), 0) s,
			COUNT(DISTINCT CASE WHEN c.first_dt >= '$ps' THEN ro.client_id END) nw
		from retail_orders ro join client c on c.id = ro.client_id
		where ro.company_id = '$cmp' and ro.client_id > 0 and $OK and ro.ins_dt >= '$ps' and ro.ins_dt < '$pe'"));
	$all_cnt = (int)mysqli_fetch_assoc(db::query("select COUNT(*) n from retail_orders ro where ro.company_id = '$cmp' and $OK and ro.ins_dt >= '$ps' and ro.ins_dt < '$pe'"))['n'];
	$base = mysqli_fetch_assoc(db::query("select COUNT(*) total,
			SUM(orders_cnt >= 2 and last_dt < NOW() - INTERVAL 30 DAY) sleep
		from client where company_id = '$cmp' and archive = 0 and orders_cnt > 0"));
	$back = (int)$st['act'] - (int)$st['nw'];
	$avg = $st['cnt'] ? round($st['s'] / $st['cnt']) : 0;
	$share = $all_cnt ? round($st['cnt'] * 100 / $all_cnt) : 0;

	// ---- 12 айлық график ----
	$m_start = date('Y-m-01 06:00:00', strtotime(date('Y-m-01').' -11 month'));
	$months = [];
	for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime(date('Y-m-01')." -$i month"))] = ['act' => 0, 'nw' => 0, 's' => 0];
	$mq = db::query("select DATE_FORMAT(ro.ins_dt - INTERVAL 6 HOUR, '%Y-%m') m, COUNT(DISTINCT ro.client_id) act,
			COUNT(DISTINCT CASE WHEN DATE_FORMAT(c.first_dt - INTERVAL 6 HOUR, '%Y-%m') = DATE_FORMAT(ro.ins_dt - INTERVAL 6 HOUR, '%Y-%m') THEN ro.client_id END) nw,
			SUM(ro.total) s
		from retail_orders ro join client c on c.id = ro.client_id
		where ro.company_id = '$cmp' and ro.client_id > 0 and $OK and ro.ins_dt >= '$m_start'
		group by m");
	while ($mq && $r = mysqli_fetch_assoc($mq)) if (isset($months[$r['m']])) $months[$r['m']] = $r;
	$m_max = 1; foreach ($months as $m) $m_max = max($m_max, (int)$m['act']);

	// ---- Тізім ----
	$seg = @$_GET['seg'];
	$sort = @$_GET['sort'] ?: 'sum';
	$q = trim((string)@$_GET['q']);
	$page = max(1, (int)@$_GET['page']);
	$lim = 50; $off = ($page - 1) * $lim;

	$where = "c.company_id = '$cmp'";
	$where .= $seg == 'archive' ? " and c.archive = 1" : " and c.archive = 0";
	if ($seg == 'new') $where .= " and c.orders_cnt = 1";
	if ($seg == 'back') $where .= " and c.orders_cnt between 2 and 4 and c.last_dt >= NOW() - INTERVAL 30 DAY";
	if ($seg == 'loyal') $where .= " and c.orders_cnt >= 5 and c.last_dt >= NOW() - INTERVAL 30 DAY";
	if ($seg == 'sleep') $where .= " and c.orders_cnt >= 2 and c.last_dt < NOW() - INTERVAL 30 DAY";
	if ($q !== '') {
		$qd = preg_replace('/\D+/', '', $q);
		if (strlen($qd) == 11) $qd = substr($qd, 1);
		$qs = mysqli_real_escape_string(db::$connect, $q);
		$where .= " and (c.name LIKE '%$qs%' or c.address LIKE '%$qs%'".($qd !== '' ? " or c.phone LIKE '%$qd%'" : '').")";
	}

	// кезеңдегі тапсырыстар (p=all болса барлығы)
	$join = "left join (select ro.client_id, COUNT(*) pc, SUM(ro.total) psum, MAX(ro.ins_dt) pl from retail_orders ro
		where ro.company_id = '$cmp' and ro.client_id > 0 and $OK and ro.ins_dt >= '$ps' and ro.ins_dt < '$pe' group by ro.client_id) p on p.client_id = c.id";
	$in_period = ($per['p'] != 'all' && !$q && !$seg);
	if ($in_period) $where .= " and p.pc > 0";
	else $where .= " and (c.orders_cnt > 0 or c.name is not null or c.archive = 1)";

	$orders = ['sum' => 'IFNULL(p.psum,0) desc, c.total_sum desc', 'cnt' => 'IFNULL(p.pc,0) desc, c.orders_cnt desc', 'last' => 'c.last_dt desc', 'new' => 'c.first_dt desc'];
	if ($per['p'] == 'all' || !$in_period) $orders = ['sum' => 'c.total_sum desc', 'cnt' => 'c.orders_cnt desc', 'last' => 'c.last_dt desc', 'new' => 'c.first_dt desc'];
	if (!isset($orders[$sort])) $sort = 'sum';

	$found = (int)mysqli_fetch_assoc(db::query("select COUNT(*) n from client c $join where $where"))['n'];
	$list = db::query("select c.*, IFNULL(p.pc,0) pc, IFNULL(p.psum,0) psum from client c $join where $where order by {$orders[$sort]} limit $off, $lim");
	$pages = max(1, ceil($found / $lim));

	// URL көмекшісі
	function cl_url($set = []) {
		$g = array_merge($_GET, $set);
		foreach ($g as $k => $v) if ($v === null || $v === '') unset($g[$k]);
		return '/clients/'.($g ? '?'.http_build_query($g) : '');
	}

	$p_names = ['today' => 'Бүгін', 'yday' => 'Кеше', 'week' => '1 апта', 'month' => '1 ай', '3m' => '3 ай', '6m' => '6 ай', '12m' => '12 ай', 'all' => 'Барлығы'];
	$seg_names = ['' => 'Барлығы', 'new' => 'Жаңа', 'back' => 'Қайта келген', 'loyal' => 'Тұрақты', 'sleep' => 'Ұйықтап қалған', 'archive' => 'Архив'];

	// site setting
	$menu_name = 'clients';
	$page_name = 'Клиенттер';
	$css = ['clients'];
	$js = ['clients'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page">

	<!-- Кезең -->
	<div class="cl_bar">
		<div class="cl_tabs">
			<? foreach ($p_names as $k => $v): ?>
				<a class="cl_tab <?=($per['p'] == $k ? 'cl_tab_act' : '')?>" href="<?=cl_url(['p' => $k, 'from' => null, 'to' => null, 'page' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="cl_range" method="get" action="/clients/">
			<input type="date" name="from" value="<?=($per['p'] == 'custom' ? $per['from'] : '')?>" class="cl_inp">
			<span>—</span>
			<input type="date" name="to" value="<?=($per['p'] == 'custom' ? $per['to'] : '')?>" class="cl_inp">
			<button class="cl_btn" type="submit"><i class="fal fa-check"></i></button>
		</form>
	</div>
	<div class="cl_sub"><?=($per['p'] == 'all' ? 'Барлық уақыт' : ($per['from'] == $per['to'] ? clients::dt($per['from'], false) : clients::dt($per['from'], false).' — '.clients::dt($per['to'], false)))?></div>

	<!-- Карточкалар -->
	<div class="cl_cards">
		<div class="cl_card">
			<div class="cl_card_h">Белсенді клиенттер</div>
			<div class="cl_card_v"><?=number_format((int)$st['act'], 0, '', ' ')?></div>
			<div class="cl_card_s">базада барлығы <?=number_format((int)$base['total'], 0, '', ' ')?></div>
		</div>
		<div class="cl_card">
			<div class="cl_card_h">Жаңа клиенттер</div>
			<div class="cl_card_v cl_c_green"><?=number_format((int)$st['nw'], 0, '', ' ')?></div>
			<div class="cl_card_s">алғаш рет тапсырыс берді</div>
		</div>
		<div class="cl_card">
			<div class="cl_card_h">Қайта келгендер</div>
			<div class="cl_card_v cl_c_blue"><?=number_format($back, 0, '', ' ')?></div>
			<div class="cl_card_s"><?=($st['act'] ? round($back * 100 / $st['act']) : 0)?>% белсенділердің ішінде</div>
		</div>
		<div class="cl_card">
			<div class="cl_card_h">Орташа чек</div>
			<div class="cl_card_v"><?=clients::sum($avg)?></div>
			<div class="cl_card_s"><?=number_format((int)$st['cnt'], 0, '', ' ')?> тапсырыс, <?=clients::sum($st['s'])?></div>
		</div>
		<div class="cl_card">
			<div class="cl_card_h">Телефонмен тапсырыс</div>
			<div class="cl_card_v"><?=$share?>%</div>
			<div class="cl_card_s">барлық <?=number_format($all_cnt, 0, '', ' ')?> тапсырыстың ішінде</div>
		</div>
		<a class="cl_card cl_card_link" href="<?=cl_url(['seg' => 'sleep', 'page' => null])?>">
			<div class="cl_card_h">Ұйықтап қалған</div>
			<div class="cl_card_v cl_c_red"><?=number_format((int)$base['sleep'], 0, '', ' ')?></div>
			<div class="cl_card_s">30 күннен көп келмеген</div>
		</a>
	</div>

	<!-- 12 ай -->
	<div class="cl_box">
		<div class="cl_box_h">
			<div>Соңғы 12 ай</div>
			<div class="cl_legend"><span class="cl_lg cl_lg_new"></span>Жаңа <span class="cl_lg cl_lg_back"></span>Қайта келген</div>
		</div>
		<div class="cl_chart">
			<? foreach ($months as $ym => $m): ?>
				<? $nw = (int)$m['nw']; $bk = (int)$m['act'] - $nw; ?>
				<div class="cl_col" title="<?=clients::month($ym).' '.substr($ym, 0, 4)?>: жаңа <?=$nw?>, қайта келген <?=$bk?>, <?=clients::sum($m['s'])?>">
					<div class="cl_col_v"><?=((int)$m['act'] ?: '')?></div>
					<div class="cl_col_bar">
						<div class="cl_col_back" style="height: <?=round($bk * 100 / $m_max, 1)?>%"></div>
						<div class="cl_col_new" style="height: <?=round($nw * 100 / $m_max, 1)?>%"></div>
					</div>
					<div class="cl_col_m"><?=clients::month($ym)?></div>
				</div>
			<? endforeach ?>
		</div>
	</div>

	<!-- Сүзгі -->
	<div class="cl_bar">
		<div class="cl_tabs">
			<? foreach ($seg_names as $k => $v): ?>
				<a class="cl_tab <?=((string)$seg === (string)$k ? 'cl_tab_act' : '')?>" href="<?=cl_url(['seg' => $k, 'page' => null])?>"><?=$v?></a>
			<? endforeach ?>
		</div>
		<form class="cl_range" method="get" action="/clients/">
			<? foreach (['p', 'from', 'to', 'seg', 'sort'] as $k): if (@$_GET[$k]): ?><input type="hidden" name="<?=$k?>" value="<?=htmlspecialchars($_GET[$k])?>"><? endif; endforeach ?>
			<input type="text" name="q" value="<?=htmlspecialchars($q)?>" class="cl_inp cl_search" placeholder="Телефон, аты немесе мекенжай">
			<select class="cl_inp" onchange="location.href=this.value">
				<? foreach (['sum' => 'Сома бойынша', 'cnt' => 'Тапсырыс саны', 'last' => 'Соңғы келгені', 'new' => 'Жаңалары'] as $k => $v): ?>
					<option value="<?=cl_url(['sort' => $k, 'page' => null])?>" <?=($sort == $k ? 'selected' : '')?>><?=$v?></option>
				<? endforeach ?>
			</select>
		</form>
	</div>

	<!-- Тізім -->
	<div class="cl_box cl_table">
		<div class="cl_tr cl_th">
			<div class="cl_td cl_td_n">#</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<? if ($in_period): ?>
				<div class="cl_td">Кезеңде</div>
			<? endif ?>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td">Жалпы сома</div>
			<div class="cl_td">Орташа чек</div>
			<div class="cl_td">Соңғы тапсырыс</div>
			<div class="cl_td cl_td_seg">Сегмент</div>
		</div>
		<? $n = $off; ?>
		<? while ($c = mysqli_fetch_assoc($list)): $n++; ?>
			<? $sg = clients::segment($c); ?>
			<a class="cl_tr" href="/clients/item.php?id=<?=$c['id']?>">
				<div class="cl_td cl_td_n"><?=$n?></div>
				<div class="cl_td cl_td_name">
					<div class="cl_name"><?=($c['name'] ? htmlspecialchars($c['name']) : clients::phone_view($c['phone']))?></div>
					<div class="cl_small"><?=($c['name'] ? clients::phone_view($c['phone']) : '')?><?=($c['address'] ? ($c['name'] ? ' · ' : '').htmlspecialchars(mb_strimwidth($c['address'], 0, 40, '…')) : '')?></div>
				</div>
				<? if ($in_period): ?>
					<div class="cl_td" data-h="Кезеңде"><b><?=$c['pc']?></b> · <?=clients::sum($c['psum'])?></div>
				<? endif ?>
				<div class="cl_td" data-h="Тапсырыс"><?=$c['orders_cnt']?></div>
				<div class="cl_td" data-h="Жалпы сома"><b><?=clients::sum($c['total_sum'])?></b></div>
				<div class="cl_td" data-h="Орташа чек"><?=clients::sum($c['orders_cnt'] ? $c['total_sum'] / $c['orders_cnt'] : 0)?></div>
				<div class="cl_td" data-h="Соңғы"><?=clients::dt($c['last_dt'], false)?><div class="cl_small"><?=clients::ago($c['last_dt'])?></div></div>
				<div class="cl_td cl_td_seg"><span class="cl_seg cl_seg_<?=$sg[0]?>"><?=$sg[1]?></span></div>
			</a>
		<? endwhile ?>
		<? if (!$found): ?>
			<div class="cl_empty">Клиент табылмады</div>
		<? endif ?>
	</div>

	<? if ($pages > 1): ?>
		<div class="cl_pages">
			<? for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++): ?>
				<a class="cl_pg <?=($i == $page ? 'cl_pg_act' : '')?>" href="<?=cl_url(['page' => $i])?>"><?=$i?></a>
			<? endfor ?>
			<span class="cl_small">барлығы <?=$found?></span>
		</div>
	<? endif ?>

</div>

<? include "../block/footer.php"; ?>
