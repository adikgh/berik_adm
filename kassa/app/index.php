<? include "../../config/core.php";

	if (!$user_id) { header('location: /'); exit; }

	// «Касса программасы» (2026-11-25): онлайн кассаны ашу, Windows программасын жүктеу, орнату нұсқаулығы.
	// Мәтіндер: kassa/app/words.php → site_word-қа 5 тілде өзі жазылады (ka:_v1, бар сөз өзгермейді).
	require_once "../../config/poswin.php";
	$KA = include __DIR__.'/words.php';
	try {
		if (mysqli_num_rows(db::query("SHOW TABLES LIKE 'site_word'")) && !mysqli_num_rows(db::query("select id from site_word where name = 'ka:_v1' and lang = 'kz'"))) {
			$vals = [];
			foreach ($KA as $k => $tr) foreach ($tr as $lg => $txt) if ($txt !== '')
				$vals[] = "('".mysqli_real_escape_string(db::$connect, $k)."', '$lg', '".mysqli_real_escape_string(db::$connect, $txt)."')";
			$vals[] = "('ka:_v1', 'kz', '1')";
			foreach (array_chunk($vals, 100) as $ch) db::query("INSERT IGNORE INTO site_word (`name`, `lang`, `txt`) VALUES ".implode(', ', $ch));
			t::$words = null;
		}
	} catch (Throwable $e) { error_log('kassa app words: '.$e->getMessage()); }
	$w = function ($k) use ($KA) { return t::w($k, isset($KA[$k]) ? $KA[$k]['kz'] : $k); };
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };

	// касса адресі мен компания коды (block/menu.php «Кассаға кіру» сияқты)
	$pos_base = preg_match('~gprog\.kz$~i', (string)@$_SERVER['HTTP_HOST']) ? 'https://posb.gprog.kz' : 'http://pos.berik.c';
	$pos_code = ltrim((string)(@$my_companies[$company]['code'] ?: @mysqli_fetch_row(db::query("select code from company where id = '".(int)@$company."'"))[0]), '#');
	$pos_link = $pos_base.'/'.($pos_code !== '' ? '?code='.rawurlencode($pos_code) : '');
	$pos_host = preg_replace('~^https?://~', '', $pos_base);
	$is_cashier = @$user_right['positions_id'] == 3;

	$app = poswin::latest();
	$app_meta = [];
	if ($app && $app['ver'] !== '') $app_meta[] = str_replace('{v}', $app['ver'], $w('ka:Version {v}'));
	if ($app && $app['size']) $app_meta[] = round($app['size'] / 1048576).' '.$w('ka:MB');

	// site setting
	$menu_name = 'kassa_app';
	$page_name = $w('ka:Cash desk app');
	$css = ['kassa_app'];
	$js = [];
?>
<? include "../../block/header.php"; ?>

<div class="bl_c ka_page">
	<p class="ka_intro"><?=$h($w('ka:intro'))?></p>

	<div class="ka_cards">
		<div class="ka_card">
			<div class="ka_ic"><i class="fal fa-globe"></i></div>
			<div class="ka_ct"><?=$h($w('ka:Online cash desk'))?></div>
			<div class="ka_cd"><?=$h($w('ka:online d'))?></div>
			<div class="ka_cf">
				<a class="ka_btn ka_btn_line" href="<?=$h($pos_link)?>" target="_blank" rel="noopener"><?=$h($w('ka:Open cash desk'))?> <i class="far fa-external-link"></i></a>
				<span class="ka_meta"><?=$h($pos_host)?></span>
			</div>
		</div>

		<div class="ka_card ka_card_main">
			<div class="ka_ic ka_ic_g"><i class="fab fa-windows"></i></div>
			<div class="ka_ct"><?=$h($w('ka:Windows app'))?></div>
			<div class="ka_cd"><?=$h($w('ka:app d'))?></div>
			<div class="ka_cf">
				<a class="ka_btn" href="/kassa/app/dl.php" rel="noopener"><i class="far fa-arrow-to-bottom"></i> <?=$h($w('ka:Download app'))?></a>
				<? if ($app_meta): ?><span class="ka_meta"><?=$h(implode(' · ', $app_meta))?></span><? endif ?>
			</div>
		</div>
	</div>

	<? if ($pos_code !== ''): ?>
		<div class="ka_code">
			<div class="ka_code_t">
				<div class="ka_code_h"><?=$h($w('ka:Company code'))?></div>
				<div class="ka_code_d"><?=$h($w('ka:code d'))?></div>
			</div>
			<div class="ka_code_r">
				<b class="ka_code_v"><?=$h($pos_code)?></b>
				<button type="button" class="ka_copy" data-v="<?=$h($pos_code)?>" data-ok="<?=$h($w('ka:Copied'))?>"><i class="far fa-copy"></i> <span><?=$h($w('ka:Copy'))?></span></button>
			</div>
		</div>
	<? endif ?>

	<div class="ka_box">
		<div class="ka_h2"><?=$h($w('ka:Install and set up'))?></div>
		<div class="ka_steps">
			<div class="ka_st">
				<span class="ka_n">1</span>
				<div class="ka_stt">
					<div class="ka_stn"><?=$h($w('ka:s1'))?></div>
					<div class="ka_std"><?=$h($w('ka:s1 d'))?></div>
					<div class="ka_note"><i class="fal fa-info-circle"></i> <?=$h($w('ka:s1 n'))?></div>
				</div>
			</div>
			<div class="ka_st">
				<span class="ka_n">2</span>
				<div class="ka_stt">
					<div class="ka_stn"><?=$h($w('ka:s2'))?></div>
					<div class="ka_std"><?=$h(str_replace('{server}', $pos_base, $w('ka:s2 d')))?></div>
				</div>
			</div>
			<div class="ka_st">
				<span class="ka_n">3</span>
				<div class="ka_stt">
					<div class="ka_stn"><?=$h($w('ka:s3'))?></div>
					<div class="ka_std"><?=$h($w('ka:s3 d'))?></div>
					<div class="ka_note"><i class="fal fa-exclamation-circle"></i> <?=$h($w('ka:s3 n'))?></div>
				</div>
			</div>
			<div class="ka_st">
				<span class="ka_n">4</span>
				<div class="ka_stt">
					<div class="ka_stn"><?=$h($w('ka:s4'))?></div>
					<div class="ka_std"><?=$h($w('ka:s4 d'))?></div>
					<? if (!$is_cashier): ?>
						<div class="ka_links">
							<a class="ka_lnk" href="/company/#c_pos"><i class="fal fa-cog"></i> <?=$h($w('ka:Cash desk settings'))?></a>
							<a class="ka_lnk" href="/receipt/"><i class="fal fa-print"></i> <?=$h($w('ka:Receipt'))?></a>
						</div>
					<? endif ?>
				</div>
			</div>
			<div class="ka_st">
				<span class="ka_n">5</span>
				<div class="ka_stt">
					<div class="ka_stn"><?=$h($w('ka:s5'))?></div>
					<div class="ka_std"><?=$h($w('ka:s5 d'))?></div>
				</div>
			</div>
		</div>
	</div>

	<div class="ka_help">
		<div class="ka_help_h"><i class="fal fa-life-ring"></i> <?=$h($w('ka:If receipt does not print'))?></div>
		<div class="ka_std"><?=$h($w('ka:help d'))?></div>
	</div>
</div>

<script>
	// компания кодын көшіру
	$(document).on('click', '.ka_copy', function () {
		var b = $(this), v = String(b.data('v')), done = function () { b.addClass('ka_copy_ok').find('span').text(b.data('ok')); setTimeout(function () { b.removeClass('ka_copy_ok').find('span').text(b.data('t')); }, 1600); };
		if (!b.data('t')) b.data('t', b.find('span').text());
		if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v).then(done, function () { fb(); });
		else fb();
		function fb() { var t = $('<textarea>').val(v).css({ position: 'fixed', opacity: 0 }).appendTo('body'); t[0].select(); try { document.execCommand('copy'); done(); } catch (e) {} t.remove(); }
	});
</script>

<? include "../../block/footer.php"; ?>
