<?php include "../config/core.php";
	// Компания баптаулары (dev): админ /company/ бетіндегі барлық баптау + админде тек көрінетін не жоқ өрістер.
	// Сақтау: get.php?cs_save (company), ?br_save (точка), ?br_add (жаңа точка). Рұқсат: dev::can('company_edit').
	dev_only('companies');
	$menu = 'companies';
	$id = (int)@$_GET['id'];
	$c = dev::row("select * from company where id = '$id'");
	if (!$c) { header('location: /companies/'); exit(); }
	$F = include __DIR__ . '/fields.php';
	$cc = dev_cs_cols('company');
	$bc = dev_cs_cols('company_branch');
	$edit = dev::can('company_edit');
	$branches = dev::rows("select * from company_branch where company_id = '$id' order by number is null, number, id");
	$page_title = $c['name'];
	$page_back = ['/companies/', t::w('Companies', 'Компаниялар')];
	$page_sub = dev::h(trim($c['city'] . ' · ' . $c['code'] . ' · id ' . $c['id'], ' ·'));
	$L = function ($l) { return t::w($l[0], $l[1]); };

	// Тізімде жоқ бағандар → «Басқа өрістер»
	$known = array_merge($F['skip']['company'], $F['ro']['company']);
	foreach ($F['company'] as $s) $known = array_merge($known, array_keys($s[2]));
	$other = array_values(array_diff(array_keys($cc), $known));
	$bknown = array_merge($F['skip']['branch'], $F['ro']['branch'], array_keys($F['branch']));
	$bother = array_values(array_diff(array_keys($bc), $bknown));

	// Бір өрістің HTML-і
	$field = function ($col, $m, $info, $v, $edit) use ($L) {
		$t = isset($m['t']) ? $m['t'] : $info['type'];
		$lab = isset($m['l']) ? $L($m['l']) : $col;
		$note = isset($m['n']) ? '<p class="hint">' . dev::h($L($m['n'])) . '</p>' : '';
		$dis = $edit ? '' : ' disabled';
		if ($t === 'bool') return '<input type="hidden" name="' . $col . '" value="0"><label class="chk mb"><input type="checkbox" name="' . $col . '" value="1"' . ((int)$v ? ' checked' : '') . $dis . '> ' . dev::h($lab) . '</label>' . $note;
		if ($t === 'pays') {
			$on = trim((string)$v) === '' ? array_keys($m['o']) : array_map('trim', explode(',', $v));
			$h = '<div class="mb"><label>' . dev::h($lab) . '</label><div class="cs_chk">';
			foreach ($m['o'] as $k => $l) $h .= '<label class="chk"><input type="checkbox" name="' . $col . '[]" value="' . dev::h($k) . '"' . (in_array($k, $on) ? ' checked' : '') . $dis . '> ' . dev::h($L($l)) . '</label>';
			return $h . '</div>' . $note . '</div>';
		}
		if ($t === 'sel') {
			$h = '<label class="mb">' . dev::h($lab) . '<select name="' . $col . '"' . $dis . '>';
			if ($info['null'] || !isset($m['o'][(string)$v])) $h .= '<option value="">—</option>';
			foreach ($m['o'] as $k => $l) $h .= '<option value="' . dev::h($k) . '"' . ((string)$v === (string)$k ? ' selected' : '') . '>' . dev::h($L($l)) . '</option>';
			return $h . '</select></label>' . $note;
		}
		$it = ['int' => 'number', 'dec' => 'number', 'dt' => 'datetime-local', 'date' => 'date'];
		$type = isset($it[$info['type']]) ? $it[$info['type']] : 'text';
		$ex = '';
		if ($info['type'] === 'dec') $ex .= ' step="0.01"';
		if (isset($m['min'])) $ex .= ' min="' . $m['min'] . '"';
		if (isset($m['max'])) $ex .= ' max="' . $m['max'] . '"';
		if ($type === 'text') $ex .= ' maxlength="' . $info['len'] . '"';
		if (!empty($m['req'])) $ex .= ' required';
		return '<label class="mb">' . dev::h($lab) . '<input type="' . $type . '" name="' . $col . '" value="' . dev::h(dev_cs_inp($info, $v)) . '"' . $ex . $dis . '></label>' . $note;
	};
	$hid = function ($sec, $bid = 0) use ($id) {
		return '<input type="hidden" name="company_id" value="' . $id . '"><input type="hidden" name="sec" value="' . $sec . '">' . ($bid ? '<input type="hidden" name="id" value="' . $bid . '">' : '');
	};
	$save = $edit ? '<button class="btn btn_g" type="submit"><i class="fal fa-check"></i>' . t::w('Save', 'Сақтау') . '</button>' : '';
?>
<? include "../block/header.php"; ?>
	<style>
		.dv_c .cs_chk { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 6px 0 4px; }
		.dv_c .cs_chk label.chk { margin: 0; font-weight: 500; }
		.dv_c .cs_ro { margin: 0 0 14px; }
		.dv_c .cs_h2 { margin: 24px 0 12px; font-size: 20px; }
	</style>
	<div class="tools">
		<div class="tabs">
			<a href="item.php?id=<?=$id?>"><?=t::w('Subscription', 'Жазылым')?></a>
			<a href="settings.php?id=<?=$id?>" class="on"><?=t::w('Company settings', 'Компания баптаулары')?></a>
		</div>
	</div>
	<? if (!$edit): ?><p class="note"><?=t::w('You can only view. Only the owner, developer and admin can change settings.', 'Тек көре аласыз. Баптауды иесі, әзірлеуші және админ өзгертеді.')?></p><? endif ?>

	<div class="grid g2">
		<? foreach ($F['company'] as $sec => $s): $has = array_intersect_key($s[2], $cc); if (!$has) continue; ?>
		<form class="card" data-post="get.php?cs_save">
			<?=$hid($sec)?>
			<div class="card_h"><h2><i class="fal <?=$s[0]?>"></i> <?=dev::h($L($s[1]))?></h2></div>
			<? if ($sec === 'main'): ?><dl class="dl cs_ro"><dt>ID</dt><dd><?=$id?></dd><? if (isset($cc['ins_dt'])): ?><dt><?=t::w('Added', 'Қосылған күні')?></dt><dd><?=dev::h($c['ins_dt'])?></dd><? endif ?></dl><? endif ?>
			<? foreach ($has as $col => $m) echo $field($col, $m, $cc[$col], $c[$col], $edit); ?>
			<?=$save?>
		</form>
		<? endforeach ?>

		<? if ($other): ?>
		<form class="card" data-post="get.php?cs_save">
			<?=$hid('other')?>
			<div class="card_h"><h2><i class="fal fa-sliders-h"></i> <?=t::w('Other fields', 'Басқа өрістер')?></h2></div>
			<p class="hint" style="margin-bottom:12px"><?=t::w('Database columns that have no section yet', 'Базадағы әлі бөлімі жоқ бағандар')?></p>
			<? foreach ($other as $col) echo $field($col, [], $cc[$col], $c[$col], $edit); ?>
			<?=$save?>
		</form>
		<? endif ?>
	</div>

	<? // Kaspi онлайн төлем (xpayment.kz): кілт, касса аты, вебхук құпиясы, қосу/өшіру — тек осында. Предоплата мен төлем түрлері — админ ▸ Компания баптаулары
	$xp_ok = (bool)dev::row("SHOW TABLES LIKE 'company_xpay'");
	$xp = $xp_ok ? dev::row("select * from company_xpay where company_id = '$id'") : null;
	$xp_mask = function ($k) { return $k === '' ? '' : substr($k, 0, 9) . '…' . substr($k, -4); };
	$xp_dis = $edit ? '' : ' disabled'; ?>
	<h2 class="cs_h2"><i class="fal fa-credit-card"></i> <?=t::w('Kaspi online payment', 'Kaspi онлайн төлем')?></h2>
	<div class="grid g2">
		<? if (!$xp_ok): ?>
		<div class="card"><p class="hint"><?=t::w('Add the xpay SQL to the database first.', 'Алдымен базаға xpay SQL-ын қосыңыз (company_xpay).')?></p></div>
		<? else: ?>
		<form class="card" data-post="get.php?xpay_save" autocomplete="off">
			<input type="hidden" name="company_id" value="<?=$id?>">
			<div class="card_h"><h2><i class="fal fa-key"></i> xpayment</h2><small><?=($xp && (int)$xp['is_on'] ? t::w('On', 'Қосулы') : t::w('Off', 'Өшік'))?></small></div>
			<input type="hidden" name="is_on" value="0"><label class="chk mb"><input type="checkbox" name="is_on" value="1"<?=($xp && (int)$xp['is_on'] ? ' checked' : '')?><?=$xp_dis?>> <?=t::w('Kaspi payment in the menu (QR / cabinet)', 'Мәзірде Kaspi төлем (QR / кабинет)')?></label>
			<label class="mb"><?=t::w('API v2 key (xapi_…)', 'API v2 кілті (xapi_…)')?><input name="api_key" placeholder="<?=dev::h($xp ? $xp_mask((string)$xp['api_key']) : 'xapi_…')?>"<?=$xp_dis?>></label>
			<p class="hint"><?=t::w('xpayment cabinet → API keys → API v2 keys. Leave empty to keep the saved key.', 'xpayment кабинеті → «API-ключи» → «Ключи API v2». Бос қалса, сақталған кілт өзгермейді.')?></p>
			<label class="mb"><?=t::w('Cashbox name (device_name)', 'Касса аты (device_name)')?><input name="device_name" maxlength="32" value="<?=dev::h($xp ? $xp['device_name'] : '')?>" placeholder="Kassa1"<?=$xp_dis?>></label>
			<label class="mb"><?=t::w('Webhook secret', 'Вебхук құпиясы (secret)')?><input name="hook_secret" placeholder="<?=($xp && $xp['hook_secret'] !== '' ? '••••••' : '')?>"<?=$xp_dis?>></label>
			<p class="hint"><?=t::w('Webhook URL in xpayment:', 'xpayment-тегі вебхук URL:')?> <b>https://myb.gprog.kz/xpay_hook.php</b> · payment.*</p>
			<?=$save?>
		</form>
		<? if ($xp && $xp['api_key'] !== ''): ?>
		<form class="card" data-post="get.php?xpay_check">
			<input type="hidden" name="company_id" value="<?=$id?>">
			<div class="card_h"><h2><i class="fal fa-plug"></i> <?=t::w('Check connection', 'Байланысты тексеру')?></h2></div>
			<p class="hint mb"><?=t::w('Lists the cashboxes of this key. No payment is made.', 'Кілттің кассаларын көрсетеді. Төлем жасалмайды.')?></p>
			<button class="btn btn_l" type="submit"><i class="fal fa-plug"></i><?=t::w('Check', 'Тексеру')?></button>
		</form>
		<? endif ?>
		<? endif ?>
	</div>

	<h2 class="cs_h2"><?=t::w('Branches', 'Филиалдар')?> · <?=count($branches)?></h2>
	<div class="grid g2">
		<? foreach ($branches as $b): $bid = (int)$b['id']; ?>
		<form class="card" data-post="get.php?br_save">
			<?=$hid('branch', $bid)?>
			<div class="card_h"><h2><i class="fal fa-store"></i> <?=dev::h($b['name'] ?: '#' . $bid)?></h2><small>id <?=$bid?></small></div>
			<? foreach ($F['branch'] as $col => $m) if (isset($bc[$col])) echo $field($col, $m, $bc[$col], $b[$col], $edit); ?>
			<? foreach ($bother as $col) echo $field($col, [], $bc[$col], $b[$col], $edit); ?>
			<?=$save?>
		</form>
		<? endforeach ?>

		<? if ($edit): ?>
		<form class="card" data-post="get.php?br_add" data-ask="<?=dev::h(t::w('Add a new branch to this company?', 'Компанияға жаңа точка қосамыз ба?'))?>">
			<input type="hidden" name="company_id" value="<?=$id?>">
			<div class="card_h"><h2><i class="fal fa-plus"></i> <?=t::w('Add branch', 'Точка қосу')?></h2></div>
			<label class="mb"><?=t::w('Branch name', 'Точка атауы')?><input name="name" maxlength="100" required></label>
			<? if (isset($bc['address'])): ?><label class="mb"><?=t::w('Address', 'Мекенжайы')?><input name="address" maxlength="255"></label><? endif ?>
			<button class="btn btn_l" type="submit"><i class="fal fa-plus"></i><?=t::w('Add branch', 'Точка қосу')?></button>
		</form>
		<? endif ?>
	</div>
<? include "../block/footer.php"; ?>
