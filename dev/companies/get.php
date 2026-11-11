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

	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
