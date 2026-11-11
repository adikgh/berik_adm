<? include "config/core.php";

	// Кірген адам бірден Аналитикаға (Статистика) барады.
	// Кассир (рөл 3) үшін /stats/ өзі /kassa/-ға жібереді.
	$sign_home = '/stats/';
	if ($user_id) { header('location: '.$sign_home); exit(); }


	// site setting
	$menu_name = 'main';
	$site_set['menu'] = false;
	$site_set['footer'] = false;
	$css = ['sign'];
	$js = ['sign'];
?>
<? include "block/header.php"; ?>

	<div class="u_sign">
		<div class="lg_wrap">

			<!-- Сол жақ: бренд (телефонда жоғарғы жасыл жолақ) -->
			<div class="lg_side">
				<div class="lg_brand">
					<span class="lg_logo"><img src="/assets/img/logo/berik-icon.svg" alt="BERIK POS" width="44" height="44"></span>
					<span class="lg_name"><span>BERIK <b>POS</b></span><small>Admin</small></span>
				</div>
				<div class="lg_side_t">
					<h2 class="lg_slogan">Кәсібіңізді бір жерден басқарыңыз</h2>
					<ul class="lg_feats">
						<li><i class="fal fa-chart-line"></i><span>Аналитика мен статистика</span></li>
						<li><i class="fal fa-cash-register"></i><span>Касса және курьер есебі</span></li>
						<li><i class="fal fa-receipt"></i><span>Тапсырыстар мен тағамдар</span></li>
					</ul>
				</div>
				<div class="lg_side_f">© Berik POS, <?=date('Y')?></div>
			</div>

			<!-- Оң жақ: кіру формасы -->
			<div class="lg_main">
				<div class="usign_c">
					<div class="lg_head">
						<span class="lg_badge"><i class="far fa-shield-check"></i> Админ</span>
						<h1 class="lg_h">Жүйеге кіру</h1>
						<p class="lg_p">Телефон нөміріңіз бен парольді жазыңыз</p>
					</div>
					<? include "block/sign_form.php"; ?>
				</div>
				<a class="lg_made" href="https://gprog.kz" target="_blank">#gprog-та <i class="fas fa-heart"></i> жасалған</a>
			</div>

		</div>
	</div>

<? include "block/footer.php"; ?>
