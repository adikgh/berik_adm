<? include "../config/core.php";

	if (!$user_id) { header('location: /'); exit; }
	if (@$user_right['positions_id'] == 3) { header('location: /kassa/'); exit; }

	// «Жұмысты бастау»: барлық баптау қадамдары толық (config/onboard.php). Виджеттің «Барлық қадамдар» батырмасы осында әкеледі.
	require_once "../config/onboard.php";
	$steps = onboard::steps($company, $user_id, true);
	$m = count($steps);
	$n = count(array_filter($steps, function ($s) { return $s['done']; }));
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
	$shown = onboard::show($company, $user_right);

	// site setting
	$menu_name = 'start';
	$page_name = onboard::w('ob:Get started');
	$css = ['onboard'];
	$js = ['onboard'];
?>
<? include "../block/header.php"; ?>

<div class="bl_c ob_page" data-cmp="<?=(int)$company?>">
	<div class="ob_box">
		<div class="ob_ph">
			<div>
				<div class="ob_sub"><?=$h(str_replace(['{n}', '{m}'], [$n, $m], onboard::w('ob:Done {n} of {m}')))?></div>
				<p class="ob_hint"><?=$h(onboard::w('ob:page hint'))?></p>
			</div>
			<label class="ob_sw"><input type="checkbox" class="ob_show"<?=($shown ? ' checked' : '')?>><i></i><span><?=$h(onboard::w('ob:Show on every page'))?></span></label>
		</div>
		<div class="ob_bar"><i style="width: <?=($m ? round($n / $m * 100) : 0)?>%"></i></div>

		<div class="ob_steps">
			<? foreach ($steps as $i => $s): ?>
				<div class="ob_st<?=($s['done'] ? ' ob_st_done' : '')?>">
					<span class="ob_n"><? if ($s['done']): ?><i class="fas fa-check"></i><? else: ?><?=$i + 1?><? endif ?></span>
					<div class="ob_stt">
						<div class="ob_stn"><?=$h($s['title'])?></div>
						<div class="ob_std"><?=$h($s['desc'])?></div>
					</div>
					<? if ($s['done']): ?>
						<a class="ob_go ob_go_done" href="<?=$h($s['url'])?>"<?=($s['ext'] ? ' target="_blank" rel="noopener"' : '')?>><?=$h(onboard::w('ob:Done'))?></a>
					<? else: ?>
						<a class="ob_go" href="<?=$h($s['url'])?>"<?=($s['ext'] ? ' target="_blank" rel="noopener"' : '')?>><?=$h(onboard::w('ob:Open'))?> <i class="far fa-angle-right"></i></a>
					<? endif ?>
				</div>
			<? endforeach ?>
		</div>
	</div>
</div>

<? include "../block/footer.php"; ?>
