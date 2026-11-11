<?php include "../config/core.php";
	dev_only('companies');
	$menu = 'companies';
	$id = (int)@$_GET['id'];
	$c = dev::row("select * from company where id = '$id'");
	if (!$c) { header('location: /companies/'); exit(); }
	$page_title = $c['name'];
	$page_back = ['/companies/', t::w('Companies', 'Компаниялар')];
	$page_sub = dev::h(trim($c['city'] . ' · ' . $c['code'] . ' · id ' . $c['id'], ' ·')) . ' · ' . sprintf(t::w('in the system since %s', 'жүйеде %s бастап'), dev::d($c['start_dt'] ?: $c['ins_dt']));
	$sub = dev::sub($id) ?: ['tariff_id' => null, 'paid_until' => null, 'ref_id' => null, 'note' => ''];
	$is_trial = dev::is_trial($id);
	$st = dev::status($sub['paid_until'], $is_trial);
	$tar = dev::tariffs(true);
	$tar_on = dev::tariffs();
	$refs = dev::rows("select * from company_ref where arh = 0 order by nick", 'id');
	$own_refs = array_filter($refs, function ($r) use ($id) { return (int)$r['company_id'] === $id; });
	$pays = dev::rows("select * from company_payment where company_id = '$id' order by id desc");
	$last_id = 0; foreach ($pays as $p) if (!$p['arh']) { $last_id = (int)$p['id']; break; }
	$paid_total = 0; foreach ($pays as $p) if (!$p['arh'] && !in_array($p['method'], ['bonus', 'trial'])) $paid_total += $p['amount'];
	$trial_can = dev::trial_can($id);
	$branches = dev::rows("select * from company_branch where company_id = '$id' order by number, id");
	$staff = dev::rows("select p.name_kz, p.name_ru, count(distinct s.user_id) n from company_staff s left join company_staff_positions p on p.id = s.positions_id
		where s.company_id = '$id' and s.archive = 0 and (s.positions_id is null or s.positions_id <> 1) group by s.positions_id order by s.positions_id");
	$ord = dev::row("select max(ins_dt) last_dt, sum(ins_dt >= now() - interval 30 day) n30, sum(ins_dt >= now() - interval 7 day) n7, count(*) n from retail_orders where company_id = '$id'");
	$pname = $ui_lang == 'ru' ? 'name_ru' : 'name_kz';
	$def_t = $sub['tariff_id'] && isset($tar_on[$sub['tariff_id']]) ? $sub['tariff_id'] : key($tar_on);
?>
<? include "../block/header.php"; ?>
	<div class="tools">
		<span class="st st_<?=$st[0]?>" style="font-size:14px;padding:8px 14px"><?=$st[1]?><?=$st[2] !== null ? ' · ' . dev::days_txt($st[2]) : ''?></span>
		<? if (!empty($sub['arh'])): ?><span class="st st_none" style="font-size:14px;padding:8px 14px"><i class="fal fa-archive"></i>&nbsp;<?=t::w('In archive', 'Архивте')?><?=$sub['arh_dt'] ? ' · ' . dev::d($sub['arh_dt']) : ''?></span><? endif ?>
		<? if (dev::can('company_arh')): ?>
			<? if (empty($sub['arh'])): ?><button class="btn btn_l btn_s" data-danger data-act="get.php?arh" data-id="<?=$id?>" data-ask="<?=dev::h(t::w('Move this company to the archive? It disappears from lists and stats. Data is not deleted.', 'Компанияны архивке саламыз ба? Тізім мен есептен жасырылады, деректері өшпейді.'))?>"><i class="fal fa-archive"></i><?=t::w('Move to archive', 'Архивке салу')?></button>
			<? else: ?><button class="btn btn_g btn_s" data-act="get.php?unarh" data-id="<?=$id?>"><i class="fal fa-box-open"></i><?=t::w('Return from archive', 'Архивтен шығару')?></button><? endif ?>
		<? endif ?>
	</div>

	<div class="kpis">
		<div class="kpi"><small><?=t::w('Tariff', 'Тариф')?></small><b><?=isset($tar[$sub['tariff_id']]) ? dev::h($tar[$sub['tariff_id']]['name']) : '—'?></b></div>
		<div class="kpi <?=$st[0] == 'debt' ? 'r' : ($st[0] == 'soon' ? 'o' : ($st[0] == 'trial' ? 'b' : 'g'))?>"><small><?=$is_trial ? t::w('Trial until', 'Сынақ мерзімі') : t::w('Paid until', 'Төленген мерзімі')?></small><b><?=dev::d($sub['paid_until'])?></b></div>
		<div class="kpi"><small><?=t::w('Total paid', 'Барлық төлем')?></small><b><?=dev::money($paid_total)?></b></div>
		<div class="kpi"><small><?=t::w('Orders: 7 / 30 days', 'Тапсырыс: 7 / 30 күн')?></small><b><?=(int)$ord['n7']?> / <?=(int)$ord['n30']?></b></div>
		<div class="kpi"><small><?=t::w('Last order', 'Соңғы тапсырыс')?></small><b style="font-size:18px"><?=dev::ago($ord['last_dt'])?></b></div>
	</div>

	<div class="grid g12">
		<div>
			<? if (dev::can('sub_edit')): ?>
			<div class="card">
				<div class="card_h"><h2><?=t::w('Add payment', 'Төлем енгізу')?></h2></div>
				<? if (!$tar_on): ?><div class="empty"><a class="link" href="/tariffs/"><?=t::w('Add a tariff first', 'Алдымен тариф қосыңыз')?></a></div><? else: ?>
				<form data-post="get.php?pay_add" class="pay_f" data-ask="<?=dev::h(t::w('Add this payment?', 'Төлемді енгіземіз бе?'))?>">
					<input type="hidden" name="company_id" value="<?=$id?>">
					<label class="mb"><?=t::w('Tariff', 'Тариф')?><select name="tariff_id">
						<? if ($trial_can): ?><option value="trial" data-price="0" selected><?=t::w('Free trial', 'Тегін сынақ')?> · <?=sprintf(t::w('%d days', '%d күн'), dev::$trial_days)?> · 0 ₸</option><? endif ?>
						<? foreach ($tar_on as $t): ?><option value="<?=$t['id']?>" data-price="<?=$t['price']?>" <?=!$trial_can && $t['id'] == $def_t ? 'selected' : ''?>><?=dev::h($t['name'])?> · <?=dev::money($t['price'])?>/<?=t::w('mo.', 'ай')?></option><? endforeach ?>
					</select></label>
					<? if ($trial_can): ?>
					<div class="pay_trial">
						<label class="mb"><?=t::w('With which tariff', 'Қай тарифпен')?><select name="trial_tariff">
							<? foreach ($tar_on as $t): ?><option value="<?=$t['id']?>" <?=$t['id'] == $def_t ? 'selected' : ''?>><?=dev::h($t['name'])?></option><? endforeach ?>
						</select></label>
						<p class="hint"><?=sprintf(t::w('Free from today until %s. Given once per company.', 'Бүгіннен %s дейін тегін. Әр компанияға бір рет беріледі.'), date('d.m.Y', strtotime('+' . (dev::$trial_days - 1) . ' days')))?></p>
					</div>
					<? endif ?>
					<div class="pay_paid">
					<label><?=t::w('How many months', 'Неше айға')?></label>
					<div class="mon">
						<? foreach (dev::$month_opts as $i => $m): $b = dev::bonus_for($m); ?>
						<label class="mon_i"><input type="radio" name="months" value="<?=$m?>" data-bonus="<?=$b?>" <?=$i == 0 ? 'checked' : ''?>><span><b><?=$m?> <?=t::w('mo.', 'ай')?></b><small><?=$b ? '+' . $b . ' ' . t::w('gift', 'сыйлық') : '&nbsp;'?></small></span></label>
						<? endforeach ?>
					</div>
					<div class="f_row">
						<label><?=t::w('Amount', 'Сома')?>, ₸<input name="amount" inputmode="numeric"></label>
						<label><?=t::w('Payment method', 'Төлем түрі')?><select name="method">
							<? foreach (dev::$methods as $k => $m): if ($k == 'bonus' && !$own_refs) continue; ?><option value="<?=$k?>"><?=$m?></option><? endforeach ?>
						</select></label>
						<label><?=t::w('Payment date', 'Төленген күні')?><input type="date" name="paid_dt" value="<?=date('Y-m-d')?>"></label>
					</div>
					<? if ($own_refs): ?>
					<div class="f_row pay_ref" hidden>
						<label><?=t::w('Whose bonus', 'Кімнің бонусынан')?><select name="ref_pay">
							<? foreach ($own_refs as $r): $bl = dev::ref_balance($r['id']); ?><option value="<?=$r['id']?>"><?=dev::h($r['nick'])?> · <?=t::w('balance', 'қалдығы')?> <?=dev::money($bl['left'])?></option><? endforeach ?>
						</select></label>
					</div>
					<? endif ?>
					<label class="mb"><?=t::w('Note', 'Ескертпе')?><input name="comment" maxlength="250"></label>
					<p class="hint pay_hint"></p>
					</div>
					<? if ($is_trial && $st[2] >= 0): ?><p class="note" style="margin:-4px 0 12px"><?=sprintf(t::w('Trial ends %s. The paid period starts after the trial.', 'Сынақ %s бітеді. Ақылы мерзім сынақ біткен күннен кейін басталады.'), dev::d($sub['paid_until']))?></p><? endif ?>
					<button class="btn btn_g" type="submit"><i class="fal fa-check"></i><span><?=t::w('Save payment', 'Төлемді енгізу')?></span></button>
				</form>
				<? endif ?>
			</div>
			<? endif ?>

			<div class="card">
				<div class="card_h"><h2><?=t::w('Payment history', 'Төлемдер тарихы')?></h2></div>
				<? if (!$pays): ?><div class="empty"><?=t::w('No payments', 'Төлем жоқ')?></div><? else: ?>
				<div class="tbl_w"><table class="tbl">
					<thead><tr><th><?=t::w('Date', 'Күні')?></th><th><?=t::w('Tariff', 'Тариф')?></th><th><?=t::w('Months', 'Айлар')?></th><th><?=t::w('Period', 'Кезең')?></th><th><?=t::w('Method', 'Түрі')?></th><th class="num"><?=t::w('Amount', 'Сома')?></th><th></th></tr></thead>
					<tbody>
					<? foreach ($pays as $p): ?>
						<tr class="<?=$p['arh'] ? 'off' : ''?>">
							<td><?=dev::d($p['paid_dt'])?></td>
							<td><?=dev::h(@$tar[$p['tariff_id']]['name'])?></td>
							<td><?=$p['method'] == 'trial' ? sprintf(t::w('%d days', '%d күн'), dev::$trial_days) : $p['months'] . ($p['bonus_months'] ? ' <small>+' . $p['bonus_months'] . ' ' . t::w('gift', 'сыйлық') . '</small>' : '')?></td>
							<td style="white-space:nowrap"><?=dev::d($p['date_from'])?> – <?=dev::d($p['date_to'])?></td>
							<td><?=dev::h(dev::method_name($p['method']))?></td>
							<td class="num"><b><?=dev::money($p['amount'])?></b><?=$p['comment'] ? '<br><small>' . dev::h($p['comment']) . '</small>' : ''?></td>
							<td><? if ((int)$p['id'] === $last_id && dev::can('pay_cancel')): ?><button class="btn btn_r btn_s" data-act="get.php?pay_cancel" data-id="<?=$p['id']?>" data-ask="<?=dev::h($p['method'] == 'trial' ? t::w('Cancel the free trial?', 'Тегін сынақты болдырмаймыз ба?') : t::w('Cancel this payment? The period goes back and the referrer bonus is removed.', 'Бұл төлемді болдырмаймыз ба? Мерзім кері қайтады, шақырушы бонусы да өшеді.'))?>"><?=t::w('Cancel', 'Болдырмау')?></button><? endif ?></td>
						</tr>
					<? endforeach ?>
					</tbody>
				</table></div>
				<? endif ?>
			</div>
		</div>

		<div>
			<div class="card">
				<div class="card_h"><h2><?=t::w('Subscription', 'Жазылым')?></h2></div>
				<? if (!dev::can('sub_edit')): ?>
				<dl class="dl"><dt><?=t::w('Tariff', 'Тариф')?></dt><dd><?=dev::h(@$tar[$sub['tariff_id']]['name'] ?: '—')?></dd><dt><?=t::w('Paid until', 'Төленген мерзімі')?></dt><dd><?=dev::d($sub['paid_until'])?></dd><dt><?=t::w('Referrer', 'Шақырушы')?></dt><dd><?=dev::h(@$refs[$sub['ref_id']]['nick'] ?: '—')?></dd><dt><?=t::w('Note', 'Ескертпе')?></dt><dd><?=dev::h($sub['note'] ?: '—')?></dd></dl>
				<? else: ?>
				<form data-post="get.php?sub_save">
					<input type="hidden" name="company_id" value="<?=$id?>">
					<div class="f_row">
						<label><?=t::w('Tariff', 'Тариф')?><select name="tariff_id"><option value="">—</option>
							<? foreach ($tar as $t): ?><option value="<?=$t['id']?>" <?=$t['id'] == $sub['tariff_id'] ? 'selected' : ''?>><?=dev::h($t['name'])?></option><? endforeach ?>
						</select></label>
						<label><?=t::w('Paid until', 'Төленген мерзімі')?><input type="date" name="paid_until" value="<?=dev::h($sub['paid_until'])?>"></label>
					</div>
					<label class="mb"><?=t::w('Who referred', 'Кім шақырды')?><select name="ref_id"><option value="">— <?=t::w('nobody', 'ешкім')?> —</option>
						<? foreach ($refs as $r): if ((int)$r['company_id'] === $id) continue; ?><option value="<?=$r['id']?>" <?=$r['id'] == $sub['ref_id'] ? 'selected' : ''?>><?=dev::h($r['nick'] . ($r['name'] ? ' · ' . $r['name'] : ''))?></option><? endforeach ?>
					</select></label>
					<label class="mb"><?=t::w('Note', 'Ескертпе')?><input name="note" maxlength="250" value="<?=dev::h($sub['note'])?>"></label>
					<button class="btn btn_l" type="submit"><?=t::w('Save', 'Сақтау')?></button>
					<p class="note"><?=t::w('Change the date by hand only to fix a mistake. Normally it extends itself when a payment is added.', 'Мерзімді қолмен өзгерту тек түзету үшін. Әдетте ол төлем енгізгенде өзі ұзарады.')?></p>
				</form>
				<? endif ?>
			</div>

			<div class="card">
				<div class="card_h"><h2><?=t::w('Branches', 'Филиалдар')?></h2><small><?=count($branches)?></small></div>
				<? if (!$branches): ?><div class="empty"><?=t::w('No branches', 'Филиал жоқ')?></div><? else: ?>
				<dl class="dl"><? foreach ($branches as $b): ?><dt><?=dev::h($b['name'])?></dt><dd><?=dev::h($b['address'] ?: '—')?><?=$b['offline'] ? ' · ' . t::w('offline', 'офлайн') : ''?></dd><? endforeach ?></dl>
				<? endif ?>
			</div>

			<div class="card">
				<div class="card_h"><h2><?=t::w('Staff', 'Жұмысшылар')?></h2></div>
				<? if (!$staff): ?><div class="empty"><?=t::w('No staff', 'Жұмысшы жоқ')?></div><? else: ?>
				<dl class="dl"><? foreach ($staff as $s): ?><dt><?=dev::h($s[$pname] ?: $s['name_kz'] ?: $s['name_ru'] ?: t::w('No role', 'Рөлсіз'))?></dt><dd><?=(int)$s['n']?></dd><? endforeach ?></dl>
				<? endif ?>
			</div>
		</div>
	</div>

<script>
(function () {
	var f = document.querySelector('.pay_f'); if (!f) return;
	var paidUntil = <?=json_encode($sub['paid_until'])?>, W = window.bw || {};
	function w(k, d) { return W[k] || d; }
	function money(n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₸'; }
	function addMonths(d, m) { var x = new Date(d + 'T00:00:00'); x.setMonth(x.getMonth() + m); x.setDate(x.getDate() - 1); return x; }
	function iso(x) { return x.getFullYear() + '-' + ('0' + (x.getMonth() + 1)).slice(-2) + '-' + ('0' + x.getDate()).slice(-2); }
	function fmt(x) { return ('0' + x.getDate()).slice(-2) + '.' + ('0' + (x.getMonth() + 1)).slice(-2) + '.' + x.getFullYear(); }
	var touched = false;
	f.amount.addEventListener('input', function () { touched = true; calc(); });
	function calc() {
		var tr = f.tariff_id.value === 'trial', pt = f.querySelector('.pay_trial'), pp = f.querySelector('.pay_paid');
		if (pt) pt.hidden = !tr; pp.hidden = tr;
		pp.querySelectorAll('input,select').forEach(function (x) { x.disabled = tr; });
		f.dataset.ask = tr ? w('Give a free trial?', 'Тегін сынақ береміз бе?') : w('Add this payment?', 'Төлемді енгіземіз бе?');
		f.querySelector('[type=submit] span').textContent = tr ? w('Give free trial', 'Тегін сынақ беру') : w('Save payment', 'Төлемді енгізу');
		if (tr) return;
		var t = f.tariff_id.selectedOptions[0], mi = f.querySelector('[name=months]:checked'), m = +mi.value, b = +mi.dataset.bonus, price = +t.dataset.price, mo = w('mo.', 'ай');
		if (!touched) f.amount.value = price * m;
		var pd = f.paid_dt.value || iso(new Date()), from = pd;
		if (paidUntil && paidUntil >= pd) { var x = new Date(paidUntil + 'T00:00:00'); x.setDate(x.getDate() + 1); from = iso(x); }
		var to = addMonths(from, m + b);
		f.querySelector('.pay_hint').textContent = m + ' ' + mo + (b ? ' + ' + b + ' ' + mo + ' ' + w('gift', 'сыйлық') : '') + ' · ' + money(+f.amount.value || 0) + ' · ' + fmt(new Date(from + 'T00:00:00')) + ' – ' + fmt(to);
		var pr = f.querySelector('.pay_ref'); if (pr) pr.hidden = f.method.value !== 'bonus';
	}
	f.addEventListener('change', function (e) { if (e.target.name === 'tariff_id' || e.target.name === 'months') touched = false; calc(); });
	calc();
})();
</script>
<? include "../block/footer.php"; ?>
