<?php
// Шапка мен мәзір админдікімен бірдей (adm.berik.c/block/menu.php): сол CSS/JS, сол класстар, телефонда төменгі мәзір.
// Бетте: $menu (белсенді пункт), $page_title, керек болса $page_sub (тақырып асты), $page_back = ['/url/', 'Атауы'], $page_css = ['sign'].
$A = '/assets/'; // админнің assets/ папкасы (dev сол сайттың алиасы)
$av = function ($f) { return '?v=' . @filemtime(DEV_ADM . '/assets/' . $f); };
// Мәзір: [кілт, сілтеме, иконка, атауы (en), атауы (kz), рұқсат]
$nav = [
	['my', '/my/', 'fa-handshake', 'My partnership', 'Менің серіктестігім', 'partner'],
	['home', '/home/', 'fa-chart-pie', 'Overview', 'Шолу', 'home'],
	['companies', '/companies/', 'fa-building', 'Companies', 'Компаниялар', 'companies'],
	['money', [
		['payments', '/payments/', 'fa-file-invoice-dollar', 'Payments', 'Төлемдер', 'payments'],
		['tariffs', '/tariffs/', 'fa-tags', 'Tariffs', 'Тарифтер', 'tariffs'],
		['ai', '/ai/', 'fa-robot', 'AI costs', 'ЖИ шығыны', 'ai'],
	], 'fa-wallet', 'Finance', 'Қаржы'],
	['ref', '/ref/', 'fa-user-friends', 'Referrers', 'Шақырушылар', 'ref'],
];
// рұқсаты жоқ пункттер алынады; тобында бір пункт қалса, жай батырма болады
$nav = array_values(array_filter(array_map(function ($n) {
	if (!is_array($n[1])) return dev::can($n[5]) ? $n : null;
	$n[1] = array_values(array_filter($n[1], function ($x) { return dev::can($x[5]); }));
	return !$n[1] ? null : (count($n[1]) == 1 ? $n[1][0] : $n);
}, $nav)));
$page_h = @$page_title ?: t::w('Developer panel', 'Әзірлеуші панелі');
?><!DOCTYPE html>
<html lang="<?=$ui_lang == 'kz' ? 'kk' : $ui_lang?>" class="html">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="robots" content="noindex, nofollow">
	<meta name="theme-color" content="#0EA768">
	<title><?=dev::h($page_h)?> | BERIK DEV</title>
	<link rel="icon" href="<?=$A?>img/logo/favicon-32.png" type="image/png">
	<link rel="icon" type="image/svg+xml" href="<?=$A?>img/logo/berik-icon.svg">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800;900&display=swap">
	<link rel="stylesheet" href="<?=$A?>pl/fontawesome/css/all.min.css">
	<? foreach (array_merge(['anim', 'norm', 'main'], $page_css ?? [], ['berik_admin', 'berik_mobile']) as $c): ?>
	<link rel="stylesheet" href="<?=$A?>css/<?=$c?>.css<?=$av("css/$c.css")?>">
	<? endforeach ?>
	<link rel="stylesheet" href="/assets/css/dev.css?v=<?=@filemtime(DEV_ADM . '/assets/css/dev.css')?>">
	<script src="<?=$A?>pl/jquery.min.js"></script>
	<script src="<?=$A?>pl/jquery.lazy.min.js"></script>
	<script src="<?=$A?>pl/jquery.mask.min.js"></script>
	<script src="<?=$A?>js/fun.js<?=$av('js/fun.js')?>"></script>
	<script>window.CSRF = <?=json_encode($csrf)?>; window.ui_lang = <?=json_encode($ui_lang)?>; window.bw = <?=json_encode(t::all(), JSON_UNESCAPED_UNICODE)?>;</script>
</head>
<body id="body" class="body">
<? if ($user_id): ?>
	<div class="aheader">
		<div class="bl_c">
			<div class="aheader_c">
				<? if (!empty($page_back)): ?>
					<a class="aheader_bk" href="<?=dev::h($page_back[0])?>"><i class="far fa-arrow-left"></i><span><?=dev::h($page_back[1])?></span></a>
				<? endif ?>
				<div class="aheader_r">
					<div class="mp_top">
						<div class="mp_topc">
							<? foreach ($nav as $n): if (is_array($n[1])): $act = in_array(@$menu, array_column($n[1], 0)); ?>
								<div class="mp_dd <?=$act ? 'mp_dd_act' : ''?>">
									<a class="mp_dd_t" href="<?=$n[1][0][1]?>" tabindex="0"><i class="fal <?=$n[2]?>"></i><span><?=t::w($n[3], $n[4])?></span><i class="far fa-angle-down mp_dd_ar"></i></a>
									<div class="mp_dd_c">
										<? foreach ($n[1] as $x): ?><a class="mp_dd_i <?=@$menu == $x[0] ? 'mp_dd_i_act' : ''?>" href="<?=$x[1]?>"><i class="fal <?=$x[2]?>"></i><span><?=t::w($x[3], $x[4])?></span></a><? endforeach ?>
									</div>
									<? foreach ($n[1] as $x): ?><a class="mp_topi mp_dd_m <?=@$menu == $x[0] ? 'mp_topi_act' : ''?>" href="<?=$x[1]?>"><i class="fal <?=$x[2]?>"></i><span><?=t::w($x[3], $x[4])?></span></a><? endforeach ?>
								</div>
							<? else: ?>
								<a class="mp_topi <?=@$menu == $n[0] ? 'mp_topi_act' : ''?>" href="<?=$n[1]?>"><i class="fal <?=$n[2]?>"></i><span><?=t::w($n[3], $n[4])?></span></a>
							<? endif; endforeach ?>
						</div>
					</div>
					<?
						$ava_ini = dev::h(mb_strtoupper(mb_substr(trim((string)@$user['name']), 0, 1) . mb_substr(trim((string)@$user['surname']), 0, 1)));
						if ($ava_ini === '') $ava_ini = '<i class="fal fa-user"></i>';
					?>
					<div class="ub1_lx">
						<div class="ub1_lt">
							<div class="ub1_ltf">
								<div><?=dev::h((string)@$user['name'])?> <?=@$user['surname'] ? dev::h(mb_substr($user['surname'], 0, 1)) . '.' : ''?></div>
								<span><?=dev::h(dev::$roles[dev::$role] ?? '')?></span>
							</div>
							<div class="ub1_lti ava_box"><?=$ava_ini?></div>
						</div>
						<div class="menu_c acc_menu">
							<div class="acc_hd">
								<div class="acc_hd_av ava_box"><?=$ava_ini?></div>
								<div class="acc_hd_t">
									<div><?=dev::h(trim(@$user['name'] . ' ' . @$user['surname']))?></div>
									<span><?=dev::h(dev::$roles[dev::$role] ?? '')?><?=@$user['phone'] ? ' · ' . dev::h($user['phone']) : ''?></span>
								</div>
							</div>
							<div class="acc_lang">
								<div class="acc_lang_h"><i class="fal fa-globe"></i> <?=t::w('Language', 'Тіл')?></div>
								<div class="acc_lang_c">
									<? foreach ($langs_short as $lk => $lv): ?>
										<a class="acc_lang_i <?=$ui_lang == $lk ? 'acc_lang_act' : ''?>" href="?<?=dev::h(http_build_query(array_merge($_GET, ['lang' => $lk])))?>" title="<?=$langs[$lk]?>"><?=$lv?></a>
									<? endforeach ?>
								</div>
							</div>
							<div class="acc_list">
								<? if (dev::can('tpl')): ?>
									<a class="menu_ci" href="/tpl/">
										<div class="menu_cin"><i class="fal fa-balance-scale"></i></div>
										<div class="menu_cih"><?=t::w('Recipe templates', 'Граммовка шаблондары')?></div>
									</a>
								<? endif ?>
								<? if (dev::can('wa')): ?>
									<a class="menu_ci" href="/wa/">
										<div class="menu_cin"><i class="fab fa-whatsapp"></i></div>
										<div class="menu_cih">WhatsApp</div>
									</a>
								<? endif ?>
								<? if (dev::can('team')): ?>
									<a class="menu_ci" href="/team/">
										<div class="menu_cin"><i class="fal fa-user-shield"></i></div>
										<div class="menu_cih"><?=t::w('Dev team', 'Команда')?></div>
									</a>
								<? endif ?>
								<a class="menu_ci acc_exit" href="/exit.php">
									<div class="menu_cin"><i class="fal fa-sign-out"></i></div>
									<div class="menu_cih"><?=t::w('Log out', 'Шығу')?></div>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="bl_c">
		<div class="head_c<?=!empty($page_back) ? ' head_c_back' : ''?>">
			<? if (!empty($page_back)): ?><a class="head_back head_back_m" href="<?=dev::h($page_back[0])?>"><i class="far fa-arrow-left"></i><?=dev::h($page_back[1])?></a><? endif ?>
			<div class="dv_ht">
				<h1><?=dev::h($page_h)?></h1>
				<? if (!empty($page_sub)): ?><p class="dv_sub"><?=$page_sub?></p><? endif ?>
			</div>
		</div>
	</div>
	<div class="app">
		<div class="bl_c dv_c">
<? endif ?>
