<? include "../config/core.php";
	include "lib.php";
	require_once "../config/loyalty.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// Кэшбэк баптауы: қосу/өшіру, пайыз, сома сатылары, кэшбэкпен төлеу шегі. Сыйлыққа тиіспейді (ол — gift.php)
	$cmp = (int)$company;
	if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
	$ready = loyalty::on();
	$msg = '';

	if ($ready && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) $msg = 'Бетті жаңартып, қайта көріңіз';
		else {
			$old = loyalty::cfg($cmp);
			$on = !empty($_POST['cashback_on']);
			$now = date('Y-m-d H:i:s');
			if (!$on) $set = ["cashback_percent = 0"]; // өшірулі: тек пайыз 0, сатылар мен шек сақталады
			else {
				$pct = round(max(0, min(50, (float)str_replace(',', '.', (string)@$_POST['cashback_percent']))), 2);
				if ($pct <= 0) $pct = 1; // қосылса, бірақ пайыз бос — 1%
				$set = ["cashback_percent = '$pct'"];
				if (!$old['cashback']) $set[] = "cashback_from = '$now'"; // 0-ден қосылса — осы сәттен бастап
				// Кэшбэкпен төлеу шегі: жалпы соманың ең көбі неше пайызы (0 не 100 = шек жоқ)
				if (loyalty::paymax_on() && isset($_POST['cashback_pay_max'])) { $pm = max(0, min(100, (int)$_POST['cashback_pay_max'])); if ($pm >= 100) $pm = 0; $set[] = "cashback_pay_max = '$pm'"; }
				// Сатылы кэшбэк: тапсырыс сомасы «асса» қанша пайыз (бос жолдар ескерілмейді)
				// «Сома бойынша сатылар» ауыстырғышы өшсе — сатылар жойылады, бір пайыз қолданылады
				if (loyalty::steps_on() && isset($_POST['steps_on']) && !$_POST['steps_on']) db::query("delete from company_cashback_step where company_id = '$cmp'");
				elseif (loyalty::steps_on() && isset($_POST['step'])) {
					$rows = [];
					foreach ((array)$_POST['step'] as $st) {
						$fs = max(0, (int)preg_replace('/\D+/', '', (string)@$st['from']));
						$sp = round(max(0, min(50, (float)str_replace(',', '.', (string)@$st['percent']))), 2);
						if ($sp > 0 && trim((string)@$st['percent']) !== '') $rows[$fs] = $sp;
					}
					ksort($rows);
					db::query("delete from company_cashback_step where company_id = '$cmp'");
					foreach ($rows as $fs => $sp) db::query("insert into company_cashback_step (company_id, from_sum, percent) values ('$cmp', '$fs', '$sp')");
				}
			}
			db::query("update company set ".implode(', ', $set)." where id = '$cmp'");
			header('location: /clients/cashback.php?saved=1'); exit;
		}
	}
	if (@$_GET['saved']) $msg = 'Сақталды';

	$cfg = loyalty::cfg($cmp);
	if ($ready) loyalty::sync($cmp);
	// Сатылар кэшбэк өшірулі кезде де көрінсін (cfg оларды тек қосулыда оқиды)
	$steps = [];
	if ($ready && loyalty::steps_on()) {
		$q = db::query("select from_sum, percent from company_cashback_step where company_id = '$cmp' order by from_sum asc");
		while ($q && $r = mysqli_fetch_assoc($q)) $steps[] = ['from' => (int)$r['from_sum'], 'percent' => (float)$r['percent']];
	}
	$fmt = function ($v) { return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.'); };

	// Қысқа есеп
	$st = ['balance' => 0, 'clients' => 0, 'earn_m' => 0, 'spend_m' => 0];
	$moves = false;
	if ($ready) {
		$m0 = date('Y-m-01 00:00:00');
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(cashback_balance), 0) b, sum(cashback_balance > 0) n from company_client where company_id = '$cmp'"));
		$st['balance'] = (int)$r['b']; $st['clients'] = (int)$r['n'];
		$r = mysqli_fetch_assoc(db::query("select coalesce(sum(case when type in ('earn','cancel') then amount end), 0) e, coalesce(-sum(case when type in ('spend','refund') then amount end), 0) s from company_client_cashback where company_id = '$cmp' and ins_dt >= '$m0'"));
		$st['earn_m'] = (int)$r['e']; $st['spend_m'] = (int)$r['s'];
		$moves = db::query("select k.*, c.name, c.phone, o.number from company_client_cashback k join company_client c on c.id = k.client_id left join retail_orders o on o.id = k.order_id
			where k.company_id = '$cmp' order by k.id desc limit 50");
	}
	// Кэшбэкпен төленген тапсырыстар: таңдалған ай (?m=2026-11)
	$pm_m = preg_match('/^\d{4}-\d{2}$/', (string)@$_GET['m']) ? $_GET['m'] : date('Y-m');
	$pm_from = $pm_m.'-01 00:00:00'; $pm_to = date('Y-m-01 00:00:00', strtotime($pm_from.' +1 month'));
	$pm_prev = date('Y-m', strtotime($pm_from.' -1 month')); $pm_next = date('Y-m', strtotime($pm_from.' +1 month'));
	$pm_rows = []; $pm_tot = ['n' => 0, 'cb' => 0, 'sum' => 0];
	if ($ready) {
		$q = db::query("select o.id, o.number, o.ins_dt, o.client_id, o.total, o.pay_cashback, o.pay_prepay, o.order_status, c.name, c.phone from retail_orders o
			left join company_client c on c.id = o.client_id
			where o.company_id = '$cmp' and o.pay_cashback > 0 and o.ins_dt >= '$pm_from' and o.ins_dt < '$pm_to' order by o.id desc");
		while ($q && $r = mysqli_fetch_assoc($q)) {
			$r['bad'] = in_array((int)$r['order_status'], loyalty::CLOSED_BAD);
			$r['full'] = (int)$r['total'] + (int)$r['pay_cashback']; // кэшбэк шегерілгенге дейінгі сома
			if (!$r['bad']) { $pm_tot['n']++; $pm_tot['cb'] += (int)$r['pay_cashback']; $pm_tot['sum'] += $r['full']; }
			$pm_rows[] = $r;
		}
	}
	$pm_mn = ['01' => 'Қаңтар', '02' => 'Ақпан', '03' => 'Наурыз', '04' => 'Сәуір', '05' => 'Мамыр', '06' => 'Маусым', '07' => 'Шілде', '08' => 'Тамыз', '09' => 'Қыркүйек', '10' => 'Қазан', '11' => 'Қараша', '12' => 'Желтоқсан'];
	$tnames = ['earn' => 'Түсті', 'spend' => 'Жұмсалды', 'cancel' => 'Қайтарылды (статус)', 'refund' => 'Клиентке қайтты', 'manual' => 'Қолмен'];

	// site setting
	$menu_name = 'clients';
	$page_name = 'Кэшбэк';
	$page_back = ['/clients/', 'Клиенттер'];
	$css = ['clients', 'loyalty'];
	$js = ['loyalty'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c cl_page lc_page">

	<? if (!$ready): ?>
		<div class="cl_box"><div class="cl_empty">Базаға кэшбэк кестелері әлі қосылмаған (berik_cashback.sql).</div></div>
	<? else: ?>

	<? if ($msg): ?><div class="lc_msg"><?=htmlspecialchars($msg)?></div><? endif ?>

	<form method="post" autocomplete="off" data-ajx=".lc_page" data-ajx-msg=".lc_msg">
		<input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>">

		<div class="cl_box lc_set lc_tg<?=($cfg['cashback'] ? '' : ' lc_off')?>">
			<div class="cl_box_h">
				<div><i class="fal fa-coins"></i> Кэшбэк</div>
				<label class="lc_sw"><input type="hidden" name="cashback_on" value="0"><input type="checkbox" name="cashback_on" value="1"<?=($cfg['cashback'] ? ' checked' : '')?>><i></i><span class="lc_sw_t"><?=($cfg['cashback'] ? 'Қосулы' : 'Өшірулі')?></span></label>
			</div>
			<div class="lc_off_note">Кэшбэк өшірулі. Қосу үшін ауыстырғышты басып, «Сақтау» басыңыз.</div>

			<div class="lc_body">
				<div class="lc_row">
					<label class="cl_lbl">Кэшбэк пайызы</label>
					<div class="lc_num"><input type="number" name="cashback_percent" class="cl_inp" min="0" max="50" step="0.5" value="<?=$fmt($cfg['percent'] ?: 1)?>"><span>%</span></div>
					<div class="cl_small">Тапсырыс аяқталғанда клиентке тағам сомасының осынша пайызы түседі.</div>
				</div>

				<? if (loyalty::steps_on()): $sl = $steps; $sl[] = ['from' => '', 'percent' => '']; $sl[] = ['from' => '', 'percent' => '']; ?>
				<div class="lc_row lc_tg lc_tg_sm<?=($steps ? '' : ' lc_off')?>">
					<label class="lc_sw lc_sw_sm"><input type="hidden" name="steps_on" value="0"><input type="checkbox" name="steps_on" value="1"<?=($steps ? ' checked' : '')?>><i></i><span>Сома бойынша сатылар</span></label>
					<div class="lc_body">
					<div class="lc_steps">
						<? foreach ($sl as $i => $sv): ?>
						<div class="lc_step">
							<span>Тағам сомасы</span>
							<div class="lc_num lc_num_sum"><input type="number" name="step[<?=$i?>][from]" class="cl_inp" min="0" value="<?=$sv['from']?>" placeholder="0"></div>
							<span>тг-дан бастап</span>
							<i class="fal fa-arrow-right"></i>
							<div class="lc_num"><input type="number" name="step[<?=$i?>][percent]" class="cl_inp" min="0" max="50" step="0.5" value="<?=($sv['percent'] === '' ? '' : $fmt($sv['percent']))?>"><span>%</span></div>
						</div>
						<? endforeach ?>
					</div>
					<div class="cl_small">Тапсырыс неғұрлым үлкен болса, соғұрлым көп пайыз беріледі.</div>
					</div>
					<div class="lc_off_note">Барлық тапсырысқа бір пайыз.</div>
				</div>
				<? endif ?>

				<? if (loyalty::paymax_on()): $pmv = [0, 10, 20, 30, 50]; $pm = (int)$cfg['pay_max']; $pm_x = !in_array($pm, $pmv); ?>
				<div class="lc_row">
					<label class="cl_lbl">Кэшбэкпен ең көбі қанша төлеуге болады</label>
					<div class="lc_chips">
						<input type="hidden" name="cashback_pay_max" value="<?=$pm?>">
						<? foreach ($pmv as $v): ?><button type="button" class="lc_chip<?=(!$pm_x && $pm == $v ? ' lc_chip_on' : '')?>" data-v="<?=$v?>"><?=($v ? $v.'%' : 'Шексіз')?></button><? endforeach ?>
						<button type="button" class="lc_chip lc_chip_x<?=($pm_x ? ' lc_chip_on' : '')?>" data-v="x">Басқа</button>
						<div class="lc_num lc_pm_x"<?=($pm_x ? '' : ' hidden')?>><input type="number" class="cl_inp" min="1" max="99" value="<?=($pm_x ? $pm : '')?>" placeholder="25"><span>%</span></div>
					</div>
					<div class="cl_small">Тапсырыс сомасының үлесі; мысалы 30% болса, 10 000 тг-ның 3 000 тг-сы ғана кэшбэкпен.</div>
				</div>
				<? endif ?>

				<? if ($cfg['cashback'] && $cfg['from']): ?><div class="cl_small">Басталды: <?=clients::dt($cfg['from'])?></div><? endif ?>
			</div>
			<div class="lc_save"><button type="submit" class="cl_btn cl_btn_green"><i class="fal fa-check"></i> Сақтау</button></div>
		</div>
	</form>

	<div class="cl_cards cl_cards_3 lc_cards">
		<div class="cl_card"><div class="cl_card_h">Клиенттердегі кэшбэк</div><div class="cl_card_v cl_c_green"><?=clients::sum($st['balance'])?></div><div class="cl_card_s"><?=$st['clients']?> клиентте</div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айда түсті</div><div class="cl_card_v"><?=clients::sum($st['earn_m'])?></div></div>
		<div class="cl_card"><div class="cl_card_h">Осы айда жұмсалды</div><div class="cl_card_v"><?=clients::sum($st['spend_m'])?></div></div>
	</div>

	<div class="cl_box cl_table lc_tbl lc_paid">
		<div class="cl_box_h"><div><i class="fal fa-coins"></i> Кэшбэкпен төленген тапсырыстар</div>
			<div class="lc_mnav" data-ajx-nav=".lc_page"><a href="/clients/cashback.php?m=<?=$pm_prev?>">‹</a> <b><?=$pm_mn[substr($pm_m, 5, 2)].' '.substr($pm_m, 0, 4)?></b> <a href="/clients/cashback.php?m=<?=$pm_next?>">›</a></div></div>
		<div class="cl_small lc_tbl_s">Барлығы <?=$pm_tot['n']?> тапсырыс · кэшбэкпен <b><?=clients::sum($pm_tot['cb'])?></b> · тапсырыстар сомасы <?=clients::sum($pm_tot['sum'])?><?=($pm_tot['sum'] ? ' · үлесі '.round($pm_tot['cb'] * 100 / $pm_tot['sum'], 1).'%' : '')?><?=($cfg['pay_max'] ? ' · шек '.$cfg['pay_max'].'%' : '')?></div>
		<div class="cl_tr cl_th">
			<div class="cl_td">Күні</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td lc_r">Сомасы</div>
			<div class="cl_td lc_r">Кэшбэкпен</div>
			<div class="cl_td lc_r">Үлесі</div>
		</div>
		<? foreach ($pm_rows as $r): ?>
			<div class="cl_tr<?=($r['bad'] ? ' lc_bad' : '')?>">
				<div class="cl_td" data-h="Күні"><?=clients::dt($r['ins_dt'])?></div>
				<div class="cl_td cl_td_name"><? if ($r['client_id']): ?><a href="/clients/item.php?id=<?=$r['client_id']?>"><?=($r['name'] ? htmlspecialchars($r['name']) : clients::phone_view($r['phone']))?></a><? else: ?>—<? endif ?></div>
				<div class="cl_td" data-h="Тапсырыс">#<?=htmlspecialchars($r['number'])?><?=($r['bad'] ? ' <small>отказ, кэшбэк қайтты</small>' : '')?><?=((int)$r['pay_prepay'] > 0 ? ' <small>предоплата '.clients::sum($r['pay_prepay']).'</small>' : '')?></div>
				<div class="cl_td lc_r" data-h="Сомасы"><?=clients::sum($r['full'])?></div>
				<div class="cl_td lc_r" data-h="Кэшбэкпен"><b><?=clients::sum($r['pay_cashback'])?></b></div>
				<div class="cl_td lc_r" data-h="Үлесі"><?=($r['full'] ? round($r['pay_cashback'] * 100 / $r['full']) : 0)?>%</div>
			</div>
		<? endforeach ?>
		<? if (!$pm_rows): ?><div class="cl_empty">Бұл айда кэшбэкпен төлем болмаған</div><? endif ?>
	</div>

	<div class="cl_box cl_table lc_tbl">
		<div class="cl_box_h"><div><i class="fal fa-list"></i> Соңғы қозғалыстар</div></div>
		<div class="cl_tr cl_th">
			<div class="cl_td">Күні</div>
			<div class="cl_td cl_td_name">Клиент</div>
			<div class="cl_td">Тапсырыс</div>
			<div class="cl_td">Түрі</div>
			<div class="cl_td lc_r">Сома</div>
			<div class="cl_td lc_r">Балансы</div>
		</div>
		<? $has = false; while ($moves && $k = mysqli_fetch_assoc($moves)): $has = true; ?>
			<div class="cl_tr">
				<div class="cl_td" data-h="Күні"><?=clients::dt($k['ins_dt'])?></div>
				<div class="cl_td cl_td_name"><a href="/clients/item.php?id=<?=$k['client_id']?>"><?=($k['name'] ? htmlspecialchars($k['name']) : clients::phone_view($k['phone']))?></a></div>
				<div class="cl_td" data-h="Тапсырыс"><?=($k['number'] ? '#'.htmlspecialchars($k['number']) : '—')?></div>
				<div class="cl_td" data-h="Түрі"><?=htmlspecialchars(@$tnames[$k['type']] ?: $k['type'])?></div>
				<div class="cl_td lc_r" data-h="Сома"><b class="<?=($k['amount'] > 0 ? 'cl_c_green' : '')?>"><?=($k['amount'] > 0 ? '+' : '−').clients::sum(abs($k['amount']))?></b></div>
				<div class="cl_td lc_r" data-h="Балансы"><?=clients::sum($k['balance_after'])?></div>
			</div>
		<? endwhile ?>
		<? if (!$has): ?><div class="cl_empty">Әзірге жоқ</div><? endif ?>
	</div>

	<? endif ?>
</div>

<? include "../block/footer.php"; ?>
