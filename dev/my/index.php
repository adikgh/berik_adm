<?php include "../config/core.php";
	dev_only('partner');
	$menu = 'my'; $page_title = t::w('My partnership', 'Менің серіктестігім'); $page_sub = t::w('Companies you brought and your bonus', 'Сіз әкелген компаниялар мен бонусыңыз');
	$ref = $user_right['ref_id'] ? dev::row("select * from company_ref where id = '" . (int)$user_right['ref_id'] . "'") : null;
	$edit = false;
?>
<? include "../block/header.php"; ?>
	<? if (!$ref): ?><div class="card empty"><?=t::w('No referral code is linked to you yet. Contact BERIK POS.', 'Сізге әлі шақыру коды байланбаған. BERIK POS-қа хабарласыңыз.')?></div>
	<? else: include "../block/ref_view.php"; endif ?>
<? include "../block/footer.php"; ?>
