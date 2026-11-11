<?php include "config/core.php";
	// Кіру беті админдікімен бірдей: сол дизайн (sign.css), сол форма мен JS (block/sign_form.php, sign.js)
	if ($user_id) { header('location: ' . dev_home()); exit(); }
	$page_title = t::w('Sign in', 'Кіру');
	$page_css = ['sign'];
	$page_js = ['sign'];
	$sign_home = '/';
?>
<? include "block/header.php"; ?>

	<div class="u_sign">
		<div class="lg_wrap">

			<div class="lg_side">
				<div class="lg_brand">
					<span class="lg_logo"><img src="/assets/img/logo/berik-icon.svg" alt="BERIK" width="44" height="44"></span>
					<span class="lg_name"><span>BERIK <b>POS</b></span><small>Developer</small></span>
				</div>
				<div class="lg_side_t">
					<h2 class="lg_slogan"><?=t::w('All companies in one place', 'Барлық компания бір жерде')?></h2>
					<ul class="lg_feats">
						<li><i class="fal fa-building"></i><span><?=t::w('Companies and subscriptions', 'Компаниялар мен жазылымдар')?></span></li>
						<li><i class="fal fa-wallet"></i><span><?=t::w('Payments and tariffs', 'Төлемдер мен тарифтер')?></span></li>
						<li><i class="fal fa-user-friends"></i><span><?=t::w('Referrers and bonuses', 'Шақырушылар мен бонустар')?></span></li>
					</ul>
				</div>
				<div class="lg_side_f">© Berik POS, <?=date('Y')?></div>
			</div>

			<div class="lg_main">
				<div class="usign_c">
					<div class="lg_head">
						<span class="lg_badge"><i class="far fa-shield-check"></i> <?=t::w('Developer', 'Әзірлеуші')?></span>
						<h1 class="lg_h"><?=t::w('Sign in to the system', 'Жүйеге кіру')?></h1>
						<p class="lg_p"><?=t::w('Enter your phone number and password', 'Телефон нөміріңіз бен парольді жазыңыз')?></p>
					</div>
					<? include DEV_ADM . '/block/sign_form.php'; ?>
				</div>
				<a class="lg_made" href="https://gprog.kz" target="_blank">#gprog-та <i class="fas fa-heart"></i> жасалған</a>
			</div>

		</div>
	</div>

<? include "block/footer.php"; ?>
