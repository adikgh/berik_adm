<? // «Жұмысты бастау» виджеті: оң жақ төменгі бұрышта (config/onboard.php). Footer-ден қосылады.
	if (!empty($user_id) && !empty($site_set['menu']) && @$menu_name != 'start') {
		require_once __DIR__.'/../config/onboard.php';
		if (onboard::show(@$company, @$user_right)) {
			$ob_s = onboard::steps($company, $user_id);
			$ob_m = count($ob_s);
			$ob_n = count(array_filter($ob_s, function ($s) { return $s['done']; }));
			$ob_h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
			$ob_all = $ob_m && $ob_n == $ob_m;
?>
	<? if ($ob_m): ?>
		<link rel="stylesheet" href="/assets/css/onboard.css?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/css/onboard.css')?>">
		<div class="ob_w<?=($ob_all ? ' ob_w_all' : '')?>" id="ob_w" data-cmp="<?=(int)$company?>" hidden>
			<div class="ob_hd" role="button" tabindex="0" aria-expanded="true">
				<div class="ob_ic"><i class="fal fa-rocket"></i></div>
				<div class="ob_ht">
					<div class="ob_t"><?=$ob_h(onboard::w('ob:Get started'))?></div>
					<div class="ob_sub"><?=$ob_h(str_replace(['{n}', '{m}'], [$ob_n, $ob_m], onboard::w('ob:Done {n} of {m}')))?></div>
				</div>
				<i class="far fa-angle-down ob_ar" title="<?=$ob_h(onboard::w('ob:Collapse'))?>"></i>
			</div>
			<div class="ob_bar"><i style="width: <?=round($ob_n / $ob_m * 100)?>%"></i></div>
			<div class="ob_bd">
				<? if ($ob_all): ?>
					<div class="ob_ok"><i class="fas fa-check-circle"></i><?=$ob_h(onboard::w('ob:All set'))?></div>
					<button type="button" class="ob_btn ob_hide"><?=$ob_h(onboard::w('ob:Hide'))?></button>
				<? else: ?>
					<div class="ob_list">
						<? foreach ($ob_s as $i => $s): ?>
							<a class="ob_i<?=($s['done'] ? ' ob_i_done' : '')?>" href="<?=$ob_h($s['url'])?>"<?=($s['ext'] ? ' target="_blank" rel="noopener"' : '')?>>
								<span class="ob_n"><? if ($s['done']): ?><i class="fas fa-check"></i><? else: ?><?=$i + 1?><? endif ?></span>
								<span class="ob_it"><?=$ob_h($s['title'])?></span>
							</a>
						<? endforeach ?>
					</div>
					<a class="ob_btn" href="/start/"><?=$ob_h(onboard::w('ob:All steps'))?> <i class="far fa-angle-right"></i></a>
				<? endif ?>
			</div>
		</div>
		<script src="/assets/js/onboard.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/onboard.js')?>"></script>
	<? endif ?>
<?		}
	} ?>
