<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id) out(['ok' => false, 'msg' => t::w('Please sign in again', 'Қайта кіріңіз')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);

	// Төлем енгізу: мерзім өзі ұзарады, сыйлық айлар қосылады, шақырушыға бонус жазылады
	if (isset($_GET['pay_add'])) {
		if (!dev::can('sub_edit')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		if (@$_POST['tariff_id'] === 'trial') {
			$r = dev::trial_add((int)@$_POST['company_id'], (int)@$_POST['trial_tariff'], $user_id);
			if (!is_int($r)) out(['ok' => false, 'msg' => $r]);
			out(['ok' => true, 'msg' => t::w('Free trial given', 'Тегін сынақ берілді')]);
		}
		$r = dev::pay_add($_POST, $user_id);
		if (!is_int($r)) out(['ok' => false, 'msg' => $r]);
		out(['ok' => true, 'msg' => t::w('Payment added', 'Төлем енгізілді')]);
	}

	// Жаңа компания тіркеу
	if (isset($_GET['company_add'])) {
		if (!dev::can('company_add')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$r = dev::company_add($_POST, $user_id);
		if (!is_int($r)) out(['ok' => false, 'msg' => $r]);
		out(['ok' => true, 'msg' => t::w('Company registered', 'Компания тіркелді'), 'go' => '/companies/item.php?id=' . $r]);
	}

	// Тегін сынақ: 14 күн, әр компанияға бір рет
	if (isset($_GET['trial_add'])) {
		if (!dev::can('sub_edit')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$r = dev::trial_add((int)@$_POST['company_id'], (int)@$_POST['tariff_id'], $user_id);
		if (!is_int($r)) out(['ok' => false, 'msg' => $r]);
		out(['ok' => true, 'msg' => t::w('Free trial given', 'Тегін сынақ берілді')]);
	}

	// Архивке салу / архивтен шығару
	if (isset($_GET['arh']) || isset($_GET['unarh'])) {
		if (!dev::can('company_arh')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$cid = (int)@$_POST['id'];
		if (!dev::row("select id from company where id = '$cid'")) out(['ok' => false, 'msg' => t::w('Company not found', 'Компания табылмады')]);
		$on = isset($_GET['arh']);
		dev::sub_save($cid, ['arh' => $on ? 1 : 0, 'arh_dt' => $on ? date('Y-m-d H:i:s') : null]);
		out(['ok' => true, 'msg' => $on ? t::w('Moved to archive', 'Архивке салынды') : t::w('Returned from archive', 'Архивтен шығарылды')]);
	}

	if (isset($_GET['pay_cancel'])) {
		if (!dev::can('pay_cancel')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$r = dev::pay_cancel((int)@$_POST['id']);
		out(['ok' => $r === 'yes', 'msg' => $r === 'yes' ? t::w('Cancelled', 'Болдырылмады') : $r]);
	}

	// Жазылымды қолмен түзету: тариф, мерзім, шақырушы, ескертпе
	if (isset($_GET['sub_save'])) {
		if (!dev::can('sub_edit')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$cid = (int)@$_POST['company_id'];
		if (!dev::row("select id from company where id = '$cid'")) out(['ok' => false, 'msg' => t::w('Company not found', 'Компания табылмады')]);
		$tid = (int)@$_POST['tariff_id'];
		$pu = (string)@$_POST['paid_until'];
		$pu = $pu !== '' && strtotime($pu) ? date('Y-m-d', strtotime($pu)) : null;
		$rid = (int)@$_POST['ref_id'];
		if ($rid) {
			$ref = dev::row("select * from company_ref where id = '$rid'");
			if (!$ref) out(['ok' => false, 'msg' => t::w('Referrer not found', 'Шақырушы табылмады')]);
			if ((int)$ref['company_id'] === $cid) out(['ok' => false, 'msg' => t::w('A company cannot refer itself', 'Компания өзін-өзі шақыра алмайды')]);
		}
		dev::sub_save($cid, ['tariff_id' => $tid ?: null, 'paid_until' => $pu, 'ref_id' => $rid ?: null, 'note' => mb_substr(trim((string)@$_POST['note']), 0, 250)]);
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}

	// Компания баптаулары (settings.php): админде бұғатталған өрістер де осы жерден өзгереді
	if (isset($_GET['cs_save']) || isset($_GET['br_save']) || isset($_GET['br_add'])) {
		if (!dev::can('company_edit')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
		$cid = (int)@$_POST['company_id'];
		$cmp = dev::row("select * from company where id = '$cid'");
		if (!$cmp) out(['ok' => false, 'msg' => t::w('Company not found', 'Компания табылмады')]);
		$F = include __DIR__ . '/fields.php';
		$L = function ($l) { return t::w($l[0], $l[1]); };
		$bad = function ($col, $m) use ($L) { return sprintf(t::w('Check the field: %s', 'Өрісті тексеріңіз: %s'), isset($m['l']) ? $L($m['l']) : $col); };

		// Жаңа точка
		if (isset($_GET['br_add'])) {
			$bc = dev_cs_cols('company_branch');
			$bn = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)@$_POST['name']))), 0, 100);
			if ($bn === '') out(['ok' => false, 'msg' => t::w('Enter the branch name', 'Точка атауын жазыңыз')]);
			$cols = ['company_id' => "'$cid'", 'name' => "'" . dev::e($bn) . "'"];
			if (isset($bc['number'])) $cols['number'] = "'" . ((int)dev::one("select max(number) from company_branch where company_id = '$cid'") + 1) . "'";
			if (isset($bc['address'])) $cols['address'] = "'" . dev::e(mb_substr(strip_tags((string)@$_POST['address']), 0, 255)) . "'";
			db::query("insert into company_branch (`" . implode('`, `', array_keys($cols)) . "`) values (" . implode(', ', $cols) . ")");
			out(['ok' => true, 'msg' => t::w('Branch added', 'Точка қосылды')]);
		}

		// Точканы сақтау
		if (isset($_GET['br_save'])) {
			$bid = (int)@$_POST['id'];
			if (!dev::row("select id from company_branch where id = '$bid' and company_id = '$cid'")) out(['ok' => false, 'msg' => t::w('Branch not found', 'Точка табылмады')]);
			$bc = dev_cs_cols('company_branch');
			$fl = $F['branch'];
			foreach ($bc as $col => $info) if (!isset($fl[$col]) && !in_array($col, $F['skip']['branch']) && !in_array($col, $F['ro']['branch'])) $fl[$col] = [];
			$set = [];
			foreach ($fl as $col => $m) {
				if (!isset($bc[$col]) || (!array_key_exists($col, $_POST) && @$m['t'] !== 'pays')) continue;
				if (@$m['t'] === 'pays') {
					$pt = array_values(array_intersect(array_keys($m['o']), (array)@$_POST[$col]));
					if (!$pt) out(['ok' => false, 'msg' => t::w('Tick at least one payment type', 'Кем дегенде бір төлем түрін белгілеңіз')]);
					$set[$col] = count($pt) == count($m['o']) ? 'NULL' : "'" . implode(',', $pt) . "'";
					continue;
				}
				$v = dev_cs_val($col, $m, $bc[$col], $_POST[$col]);
				if (is_array($v)) out(['ok' => false, 'msg' => $bad($col, $m)]);
				if ($v === null) continue;
				if (!empty($m['req']) && ($v === "''" || $v === 'NULL')) out(['ok' => false, 'msg' => $bad($col, $m)]);
				$set[$col] = $v;
			}
			// Стандарт төлем таңдалған төлем түрлерінің бірі болсын
			if (isset($set['pay_default']) && $set['pay_default'] !== 'NULL' && isset($set['pay_types']) && $set['pay_types'] !== 'NULL'
				&& !in_array(trim($set['pay_default'], "'"), explode(',', trim($set['pay_types'], "'")))) $set['pay_default'] = 'NULL';
			if ($set) {
				$q = []; foreach ($set as $k => $v) $q[] = "`$k` = $v";
				try { $ok = db::query("update company_branch set " . implode(', ', $q) . " where id = '$bid' and company_id = '$cid'"); } catch (Throwable $e) { $ok = false; }
				if (!$ok) out(['ok' => false, 'msg' => t::w('Error', 'Қате') . ': ' . db::$connect->error]);
			}
			out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
		}

		// Компания бөлімін сақтау
		$cc = dev_cs_cols('company');
		$sec = preg_replace('/[^a-z]/', '', (string)@$_POST['sec']);
		if ($sec === 'other') {
			$known = array_merge($F['skip']['company'], $F['ro']['company']);
			foreach ($F['company'] as $s) $known = array_merge($known, array_keys($s[2]));
			$fl = array_fill_keys(array_diff(array_keys($cc), $known), []);
		} elseif (isset($F['company'][$sec])) $fl = $F['company'][$sec][2];
		else out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
		$set = [];
		foreach ($fl as $col => $m) {
			if (!isset($cc[$col]) || !array_key_exists($col, $_POST)) continue;
			$v = dev_cs_val($col, $m, $cc[$col], $_POST[$col]);
			if (is_array($v)) out(['ok' => false, 'msg' => $bad($col, $m)]);
			if ($v === null) continue;
			if (!empty($m['req']) && ($v === "''" || $v === 'NULL')) out(['ok' => false, 'msg' => $bad($col, $m)]);
			$set[$col] = $v;
		}
		// Тапсырыс түрлері: кем дегенде біреуі қосулы, стандарт түрі қосулылардың бірі
		if ($sec === 'types') {
			$map = ['delivery' => 1, 'yourself' => 2, 'coffee' => 4, 'service' => 3];
			$on = [];
			foreach ($map as $col => $tid) if (isset($cc[$col]) && (isset($set[$col]) ? $set[$col] === "'1'" : (int)$cmp[$col])) $on[] = $tid;
			if (!$on) out(['ok' => false, 'msg' => t::w('Turn on at least one order type', 'Кем дегенде бір тапсырыс түрін қосыңыз')]);
			if (isset($cc['type_def'])) { $td = isset($set['type_def']) ? (int)trim($set['type_def'], "'") : (int)$cmp['type_def']; if (!in_array($td, $on)) $td = $on[0]; $set['type_def'] = "'$td'"; }
		}
		if ($set) {
			$q = []; foreach ($set as $k => $v) $q[] = "`$k` = $v";
			try { $ok = db::query("update company set " . implode(', ', $q) . " where id = '$cid'"); } catch (Throwable $e) { $ok = false; }
			if (!$ok) out(['ok' => false, 'msg' => t::w('Error', 'Қате') . ': ' . db::$connect->error]);
		}
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}

	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
