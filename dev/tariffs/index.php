<?php include "../config/core.php";
	dev_only('tariffs');
	$menu = 'tariffs'; $page_title = t::w('Tariffs', 'Тарифтер');
	$page_sub = t::w('Monthly price. No discounts: a longer payment gets gift months (6 → +1, 12 → +2, 24 → +6).', 'Айлық баға. Жеңілдік жоқ: ұзақ төлесе сыйлық ай қосылады (6 → +1, 12 → +2, 24 → +6).');
	$tar = dev::tariffs(true);
	$used = dev::rows("select tariff_id, count(*) n from company_sub where paid_until >= curdate() group by tariff_id", 'tariff_id');
	$mo = t::w('mo.', 'ай');
	$mods = dev::modules(); $tmods = dev::tariff_modules(); $lnames = dev::lim_names();
	// Модуль белгілері + 4 лимит (0 = шексіз). $tm — [code => 1], $lim — [lim_* => n]
	$plan_fields = function ($tm, $lim) use ($mods, $lnames) { ?>
		<div class="mb"><b style="font-size:13px"><?=t::w('Modules', 'Модульдер')?></b>
			<div style="display:grid;gap:6px;margin-top:6px">
			<? foreach ($mods as $c => $m): ?>
				<label class="chk" style="font-weight:500"><input type="checkbox" name="mod[]" value="<?=dev::h($c)?>" <?=isset($tm[$c]) ? 'checked' : ''?>> <?=dev::h($m['name'])?></label>
			<? endforeach ?></div>
		</div>
		<div class="f_row" style="grid-template-columns:1fr 1fr">
		<? foreach ($lnames as $k => $n): ?>
			<label><?=$n?><input name="<?=$k?>" inputmode="numeric" value="<?=(int)@$lim[$k]?>"></label>
		<? endforeach ?></div>
		<p class="note" style="margin:-4px 0 12px"><?=t::w('0 = unlimited', '0 = шексіз')?></p>
	<? };
	$trial_n = 0; foreach (dev::trial_ids() as $cid => $x) if (dev::one("select paid_until from company_sub where company_id = '" . (int)$cid . "' and paid_until >= curdate()")) $trial_n++;
?>
<? include "../block/header.php"; ?>
	<div class="card">
		<div class="card_h"><h2><i class="fal fa-calendar-alt"></i> <?=t::w('Payment for 1, 6, 12 and 24 months', '1, 6, 12 және 24 айлық төлем')?></h2></div>
		<div class="tbl_w"><table class="tbl">
			<thead><tr><th><?=t::w('Tariff', 'Тариф')?></th>
				<? foreach ([1, 6, 12, 24] as $m): $b = dev::bonus_for($m); ?><th class="num"><?=$m?> <?=$mo?><?=$b ? ' <span style="color:var(--g)">+' . $b . '</span>' : ''?></th><? endforeach ?>
			</tr></thead>
			<tbody>
				<tr><td class="nm"><b><?=t::w('Plan free', 'Free')?></b><small><?=sprintf(t::w('Plan free days', '%d күн тегін'), dev::$trial_days)?> · 0 ₸</small></td><td class="num" colspan="4"><small><?=t::w('Once per company, only for a new company with no payments', 'Әр компанияға бір рет, тек төлемі жоқ жаңа компанияға')?></small></td></tr>
				<? foreach ($tar as $t): if ($t['arh']) continue; ?>
				<tr><td class="nm"><b><?=dev::h($t['name'])?></b><small><?=dev::money($t['price'])?> / <?=$mo?></small></td>
				<? foreach ([1, 6, 12, 24] as $m): $b = dev::bonus_for($m); ?>
					<td class="num"><b><?=dev::money($t['price'] * $m)?></b><small style="display:block"><?=$b ? sprintf(t::w('%d months of use', '%d ай қолданады'), $m + $b) . ' · ' . dev::money(round($t['price'] * $m / ($m + $b))) . '/' . $mo : '&nbsp;'?></small></td>
				<? endforeach ?></tr>
			<? endforeach ?></tbody>
		</table></div>
		<p class="note"><?=t::w('The client pays for the months in the column; gift months are added for free. The last line shows the real monthly price.', 'Клиент бағандағы ай үшін төлейді, сыйлық айлар тегін қосылады. Астында бір айдың нақты бағасы.')?></p>
	</div>
	<div class="grid g3 mt">
		<form class="card" data-post="get.php?trial" style="margin:0;border:2px solid var(--bl2, #1f75a8)">
			<div class="card_h"><h2><i class="fal fa-gift"></i> <?=t::w('Plan free', 'Free')?></h2><small><?=sprintf(t::w('%d active', '%d белсенді'), $trial_n)?></small></div>
			<div class="kpi" style="padding:0;box-shadow:none"><b>0 ₸</b><small><?=sprintf(t::w('Plan free days', '%d күн тегін'), dev::$trial_days)?></small></div>
			<p class="note mb"><?=t::w('Chosen as a tariff when a company is registered or paid. Given once per company: not again after a trial, a payment or a set subscription. The paid period starts after the trial.', 'Компания тіркегенде не төлем енгізгенде тариф ретінде таңдалады. Әр компанияға бір рет: сынақ, төлем не жазылым мерзімі болған компанияға берілмейді. Ақылы мерзім сынақ біткен соң басталады.')?></p>
			<? $plan_fields(@$tmods[0] ?: [], dev::trial_lims()); ?>
			<button class="btn btn_l" type="submit"><?=t::w('Save', 'Сақтау')?></button>
		</form>
	<? foreach ($tar as $t): ?>
		<form class="card" data-post="get.php?save" style="margin:0;<?=$t['arh'] ? 'opacity:.55' : ''?>">
			<input type="hidden" name="id" value="<?=$t['id']?>">
			<div class="card_h"><h2><?=dev::h($t['name'])?></h2><small><?=sprintf(t::w('%d active', '%d белсенді'), (int)@$used[$t['id']]['n'])?></small></div>
			<label class="mb"><?=t::w('Title', 'Атауы')?><input name="name" value="<?=dev::h($t['name'])?>"></label>
			<label class="mb"><?=t::w('Per month', 'Айына')?>, ₸<input name="price" inputmode="numeric" value="<?=(int)$t['price']?>"></label>
			<label class="mb"><?=t::w('Per month', 'Айына')?>, $<input name="price_usd" inputmode="numeric" value="<?=(int)@$t['price_usd']?>"></label>
			<label class="mb"><?=t::w('Description', 'Сипаттамасы')?><input name="descr" value="<?=dev::h($t['descr'])?>"></label>
			<? $plan_fields(@$tmods[(int)$t['id']] ?: [], $t); ?>
			<label class="chk mb"><input type="checkbox" name="arh" value="1" <?=$t['arh'] ? 'checked' : ''?>> <?=t::w('Hidden (not offered for new payments)', 'Жасырын (жаңа төлемге шықпайды)')?></label>
			<button class="btn btn_l" type="submit"><?=t::w('Save', 'Сақтау')?></button>
		</form>
	<? endforeach ?>
		<form class="card" data-post="get.php?save" style="margin:0;box-shadow:none;border:2px dashed var(--line);background:transparent">
			<div class="card_h"><h2><?=t::w('New tariff', 'Жаңа тариф')?></h2></div>
			<label class="mb"><?=t::w('Title', 'Атауы')?><input name="name" required></label>
			<label class="mb"><?=t::w('Per month', 'Айына')?>, ₸<input name="price" inputmode="numeric" required></label>
			<button class="btn btn_g" type="submit"><i class="fal fa-plus"></i><?=t::w('Add', 'Қосу')?></button>
		</form>
	</div>
	<p class="note"><?=t::w('A price change does not affect past payments, only new ones.', 'Бағаны өзгерту бұрынғы төлемдерге әсер етпейді, тек жаңа төлемге.')?></p>
<? include "../block/footer.php"; ?>
