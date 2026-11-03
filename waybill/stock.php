<? include "../config/core.php";
	include "lib.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Шикізат қалдығы: кірді (накладной) − сатылды (граммовка) ± ревизия = қалдық
	wb::install();
	$cmp = (int)$company;
	$br = wb::branch($cmp, $branch);
	$br_name = '';
	$bq = db::query("select name from company_branch where id = '$br' and company_id = '$cmp'");
	if ($bq && $b = mysqli_fetch_assoc($bq)) $br_name = $b['name'];

	// сатылымды азайту қай күннен басталады
	if (isset($_POST['sales_from']) && strtotime($_POST['sales_from'])) {
		if (hash_equals((string)@$_SESSION['wb_csrf'], (string)@$_POST['csrf'])) wb::sales_from_set($cmp, $_POST['sales_from']);
		header('location: /waybill/stock.php'); exit;
	}
	if (empty($_SESSION['wb_csrf'])) $_SESSION['wb_csrf'] = bin2hex(random_bytes(16));

	$sync = wb::sales_sync($cmp);
	$q = trim((string)@$_GET['q']);

	$where = "r.company_id = '$cmp'";
	if ($q !== '') { $qs = wb::esc($q); $where .= " and (r.name_kz like '%$qs%' or r.name_ru like '%$qs%')"; }
	$only_neg = !empty($_GET['neg']);
	if ($only_neg) $where .= " and IFNULL(st.quantity, 0) < 0";

	$list = db::query("select r.id, r.name_kz, r.name_ru, r.unit, r.price, IFNULL(st.quantity, 0) qty,
			IFNULL(m.q_in, 0) q_in, IFNULL(m.q_sale, 0) q_sale, IFNULL(m.q_rev, 0) q_rev
		from product_raw r
		left join product_raw_stock st on st.raw_id = r.id and st.company_id = '$cmp' and st.branch_id = '$br'
		left join (select raw_id, SUM(IF(type in ('in', 'unpost'), qty, 0)) q_in, SUM(IF(type = 'sale', -qty, 0)) q_sale, SUM(IF(type = 'rev', qty, 0)) q_rev
			from product_raw_move where company_id = '$cmp' and branch_id = '$br' group by raw_id) m on m.raw_id = r.id
		where $where order by (IFNULL(st.quantity, 0) < 0) desc, (IFNULL(st.quantity, 0) <> 0 or m.raw_id is not null) desc, r.name_kz");

	$rows = []; $value = 0; $has = 0; $neg = 0;
	while ($list && $r = mysqli_fetch_assoc($list)) {
		foreach (['qty', 'q_in', 'q_sale', 'q_rev', 'price'] as $k) $r[$k] = round((float)$r[$k], 3);
		$rows[] = $r;
		if ($r['qty'] > 0) { $has++; $value += $r['qty'] * $r['price']; }
		if ($r['qty'] < 0) $neg++;
	}

	// граммовкасы жоқ тағамдар (бастау күнінен бері сатылғандары)
	$miss = [];
	if (wb::$sale_missing) {
		$mq = db::query("select id, name_ru, name_kz from product where id in (".implode(',', array_map('intval', array_keys(wb::$sale_missing))).")");
		while ($mq && $m = mysqli_fetch_assoc($mq)) $miss[] = ['id' => (int)$m['id'], 'name' => trim(strip_tags($m['name_ru'] ?: $m['name_kz'])), 'n' => wb::$sale_missing[(int)$m['id']]];
		usort($miss, function ($a, $b) { return $b['n'] <=> $a['n']; });
	}

	// site setting
	$menu_name = 'waybill';
	$page_name = 'Шикізат қалдығы';
	$wb_tab = 'stock';
	$css = ['waybill'];
	$js = ['waybill'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c wb_page" data-ajx-nav=".wb_page">

	<? include "tabs.php"; ?>

	<div class="wb_cards">
		<div class="wb_card">
			<div class="wb_card_h">Қоймада бар</div>
			<div class="wb_card_v"><?=$has?></div>
			<div class="wb_card_s"><?=wb::h($br_name ?: 'филиал '.$br)?></div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Қалдықтың құны</div>
			<div class="wb_card_v"><?=wb::sum($value)?></div>
			<div class="wb_card_s">соңғы сатып алу бағасымен</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Минуста</div>
			<div class="wb_card_v <?=($neg ? 'wb_c_red' : '')?>"><?=$neg?></div>
			<div class="wb_card_s">кірісі жоқ не граммовкасы артық</div>
		</div>
		<div class="wb_card">
			<div class="wb_card_h">Сатылым азайтылады</div>
			<form method="post" class="wbs_from" data-ajx=".wb_page">
				<input type="hidden" name="csrf" value="<?=wb::h($_SESSION['wb_csrf'])?>">
				<input type="date" name="sales_from" value="<?=wb::h($sync['from'])?>" class="wb_inp" onchange="this.form.requestSubmit()" aria-label="Сатылым қай күннен азайтылады">
			</form>
			<div class="wb_card_s">осы күннен бергі «Аяқталды» тапсырыстар (<?=(int)$sync['orders']?>)</div>
		</div>
	</div>

	<? if ($neg): ?>
		<div class="wbm_neg" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin:0 0 12px;padding:12px 16px;border-radius:12px;background:#fdecec;color:#c0392b;font-weight:700;font-size:14px">
			<span><i class="fal fa-exclamation-triangle"></i> <?=$neg?> шикізат минуста — накладной енгізіңіз немесе ревизия жасаңыз</span>
			<a class="wb_btn <?=($only_neg ? 'wb_btn_red' : 'wb_btn_light')?>" style="height:38px" href="<?=($only_neg ? '/waybill/stock.php' : '/waybill/stock.php?neg=1')?>"><?=($only_neg ? 'Барлығын көрсету' : 'Тек минустар')?></a>
		</div>
	<? endif ?>

	<div class="wb_bar">
		<div class="wb_small">Кірді — қабылданған накладной. Сатылды — тағам граммовкасы бойынша. Отказ болған тапсырыс қайта қосылады.</div>
		<form class="wb_range" method="get" action="/waybill/stock.php" data-ajx=".wb_page">
			<input type="text" name="q" value="<?=wb::h($q)?>" class="wb_inp wb_search" placeholder="Шикізатты іздеу">
		</form>
	</div>

	<div class="wb_box wb_table">
		<div class="wb_tr wb_th">
			<div class="wb_td wb_td_name">Шикізат</div>
			<div class="wb_td">Кірді</div>
			<div class="wb_td">Сатылды</div>
			<div class="wb_td">Ревизия</div>
			<div class="wb_td">Қалдық</div>
			<div class="wb_td">Соңғы баға</div>
			<div class="wb_td">Сомасы</div>
		</div>
		<? foreach ($rows as $r): $u = wb::h(fun::unit_norm($r['unit'])); $idle = !$r['q_in'] && !$r['q_sale'] && !$r['q_rev'] && !$r['qty']; ?>
			<a class="wb_tr <?=($idle ? 'wb_tr_mut' : '')?>" href="/waybill/raw.php?id=<?=$r['id']?>"<?=($r['qty'] < 0 ? ' style="background:#fff6f6"' : '')?>>
				<div class="wb_td wb_td_name"><div class="wb_name"><?=wb::h($r['name_kz'] ?: $r['name_ru'])?><?=wb::stock_badge($r['qty'], $r['q_in'])?></div><div class="wb_small"><?=$u?></div></div>
				<div class="wb_td" data-h="Кірді"><?=($r['q_in'] ? '+'.wb::qty($r['q_in']) : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td" data-h="Сатылды"><?=($r['q_sale'] ? '−'.wb::qty($r['q_sale']) : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td" data-h="Ревизия"><?=($r['q_rev'] ? '<span class="'.($r['q_rev'] < 0 ? 'wb_c_red' : 'wb_c_green').'">'.($r['q_rev'] > 0 ? '+' : '').wb::qty($r['q_rev']).'</span>' : '<span class="wb_small">—</span>')?></div>
				<div class="wb_td" data-h="Қалдық"><b class="<?=($r['qty'] < 0 ? 'wb_c_red' : '')?>"><?=wb::qty($r['qty'])?></b> <span class="wb_small"><?=$u?></span></div>
				<div class="wb_td" data-h="Соңғы баға"><?=($r['price'] ? wb::up($r['price']) : '—')?></div>
				<div class="wb_td" data-h="Сомасы"><?=($r['qty'] > 0 && $r['price'] ? wb::sum($r['qty'] * $r['price']) : '—')?></div>
			</a>
		<? endforeach ?>
		<? if (!$rows): ?>
			<div class="wb_empty"><i class="fal fa-boxes-alt"></i><div>Шикізат табылмады</div></div>
		<? endif ?>
	</div>

	<? if ($miss || wb::$sale_missing_comp): ?>
		<div class="wb_box">
			<div class="wb_box_h"><span>Граммовкасы жоқ тағамдар</span><a class="wb_small" href="/products/cost/">Себес бетінде толтыру →</a></div>
			<div class="wb_small" style="margin-bottom:10px">Бұлар сатылды, бірақ құрамы жазылмағандықтан шикізаттан азайтылмады.</div>
			<div class="wbs_miss">
				<? foreach ($miss as $m): ?><a class="wb_st wb_st_draft" href="/products/item/?id=<?=$m['id']?>"><?=wb::h($m['name'])?> · <?=wb::qty($m['n'])?> дана</a><? endforeach ?>
				<? foreach (wb::$sale_missing_comp as $cid => $cn): ?><a class="wb_st wb_st_draft" href="/products/cost/#prep">Дайындама «<?=wb::h($cn)?>» шикізатқа байланбаған</a><? endforeach ?>
			</div>
		</div>
	<? endif ?>

</div>

<? include "../block/footer.php"; ?>
