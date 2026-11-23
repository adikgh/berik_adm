<?php include "../config/core.php";
	// Жүйе кілттері базада (sys_setting): файл өзгертпей осы жерден ауыстырылады. Құпия мән толық көрсетілмейді.
	dev_only('keys');
	require __DIR__ . '/lib.php';
	$menu = 'keys'; $page_title = t::w('Keys', 'Кілттер');
	$page_sub = t::w('API keys and secrets of the whole system. They are kept in the database, not in files or git.', 'Бүкіл жүйенің API кілттері мен құпиялары. Олар базада сақталады, файлда да, git-те де жоқ.');
	$tbl_ok = (bool)dev::row("SHOW TABLES LIKE 'sys_setting'");
	$meta = $tbl_ok ? dkeys_meta() : [];
	$gtok = json_decode(sys_set('gdrive_token'), true);
	$keep = t::w('leave empty to keep', 'өзгертпесеңіз бос қалдырыңыз');
	$dt = function ($k) use ($meta) { return !empty($meta[$k]['upd_dt']) && sys_set($k) !== '' ? date('d.m.Y H:i', strtotime($meta[$k]['upd_dt'])) : ''; };
	$warn = [
		'link' => t::w('Changing the link secret turns off all client links sent earlier.', 'Сілтеме құпиясын өзгертсеңіз, бұрын жіберілген барлық клиент сілтемелері жұмыс істемей қалады.'),
		'auth' => t::w('A new key logs out everybody on that site: they sign in again.', 'Жаңа кілт жасалса, сол сайттағылардың бәрі шығып кетеді де, қайта кіреді.'),
	];
?>
<? include "../block/header.php"; ?>
<style>
	.ky_page{display:flex;flex-direction:column;gap:16px;max-width:860px}
	.ky_page .card{margin:0;display:flex;flex-direction:column;gap:12px}
	.ky_page .card_h{margin-bottom:0}
	.ky_f{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}
	.ky_f label{display:flex;flex-direction:column;gap:4px;min-width:0}
	.ky_f small{color:var(--mut);font-weight:600}
	.ky_row{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:10px 0;border-top:1px solid var(--line)}
	.ky_row:first-of-type{border-top:0}
	.ky_row b{display:block}.ky_row span{color:var(--mut);font-size:13px;font-weight:600}
	.ky_b{display:flex;gap:8px;flex-wrap:wrap}
	.ky_warn{color:#b9770e;font-size:13px;font-weight:600;margin:0}
	.ky_ok{color:#1a9b4b}.ky_no{color:var(--mut)}
</style>
<div class="ky_page">
	<? if (!$tbl_ok): ?><p class="note"><?=t::w('Add the SQL to the database first (sys_setting table).', 'Алдымен базаға SQL қосыңыз (sys_setting кестесі).')?></p><? else: ?>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-file-import"></i> <?=t::w('Take from old files', 'Ескі файлдардан алу')?></h2></div>
		<p class="hint"><?=t::w('Reads the old config files (ai.php, auth_key.php, wa_conf.php, map_key.php…) and fills only the keys that are empty here. The files are not changed.', 'Ескі config файлдарын (ai.php, auth_key.php, wa_conf.php, map_key.php…) оқып, мұнда бос тұрған кілттерді ғана толтырады. Файлдар өзгермейді.')?></p>
		<div><button class="btn btn_l" type="button" data-act="get.php?import"><i class="fal fa-file-import"></i><?=t::w('Take from files', 'Файлдардан алу')?></button></div>
	</div>

	<? foreach (dkeys_groups() as $gk => $g): ?>
	<form class="card" data-post="get.php?save" autocomplete="off"<?=$gk == 'link' ? ' data-ask="' . dev::h($warn['link']) . '"' : ''?>>
		<input type="hidden" name="grp" value="<?=$gk?>">
		<div class="card_h"><h2><i class="fal <?=$g[1]?>"></i> <?=dev::h($g[0])?></h2>
			<? if ($gk == 'ai'): ?><small class="<?=claude::ready() ? 'ky_ok' : 'ky_no'?>"><?=claude::ready() ? t::w('Connected', 'Қосулы') : t::w('Not connected', 'Қосылмаған')?></small><? endif ?>
		</div>
		<? if ($gk == 'auth'): ?>
			<p class="ky_warn"><i class="fal fa-exclamation-triangle"></i> <?=dev::h($warn['auth'])?></p>
			<? foreach ($g[2] as $k => $d): $v = sys_set($k); ?>
				<div class="ky_row">
					<div><b><?=dev::h($d[0])?></b><span><?=$v !== '' ? dev::h(dkeys_mask($v)) . ($dt($k) ? ' · ' . $dt($k) : '') : t::w('none: made at the first sign-in', 'жоқ: алғашқы кіргенде өзі жасалады')?></span></div>
					<button class="btn btn_r" type="button" data-act="get.php?regen" data-id="<?=$k?>" data-ask="<?=dev::h($warn['auth'])?>"><i class="fal fa-sync"></i><?=t::w('Make again', 'Қайта жасау')?></button>
				</div>
			<? endforeach ?>
		<? else: ?>
			<? if ($gk == 'link'): ?><p class="ky_warn"><i class="fal fa-exclamation-triangle"></i> <?=dev::h($warn['link'])?></p><? endif ?>
			<div class="ky_f">
				<? foreach ($g[2] as $k => $d): $v = sys_set($k); ?>
					<label><?=dev::h($d[0])?>
						<? if ($d[1] == 'model'): ?>
							<select name="<?=$k?>"><? foreach (dkeys_models() as $m): ?><option value="<?=$m?>" <?=sys_set($k, 'auto') == $m ? 'selected' : ''?>><?=$m == 'auto' ? t::w('auto (cheap first)', 'auto (алдымен арзаны)') : $m?></option><? endforeach ?></select>
						<? elseif ($d[1] == 'secret'): ?>
							<input name="<?=$k?>" placeholder="<?=dev::h($v !== '' ? dkeys_mask($v) . ' (' . $keep . ')' : '')?>">
						<? else: ?>
							<input name="<?=$k?>" value="<?=dev::h($v)?>" placeholder="<?=dev::h(['gdrive_redirect' => 'http://localhost', 'gdrive_root' => 'Berik POS', 'cabinet_url' => 'https://myb.gprog.kz'][$k] ?? '')?>">
						<? endif ?>
						<? if ($dt($k)): ?><small><?=t::w('Changed', 'Өзгерді')?>: <?=$dt($k)?><? if ($d[1] == 'secret'): ?> · <a href="#" data-act="get.php?del" data-id="<?=$k?>" data-ask="<?=dev::h(t::w('Delete this value?', 'Бұл мәнді өшіреміз бе?'))?>"><?=t::w('Delete', 'Өшіру')?></a><? endif ?></small><? endif ?>
					</label>
				<? endforeach ?>
			</div>
			<? if ($gk == 'gdrive'): ?>
				<div class="ky_row">
					<div><b><?=t::w('Drive access', 'Drive рұқсаты')?></b><span class="<?=!empty($gtok['refresh_token']) ? 'ky_ok' : ''?>"><?=!empty($gtok['refresh_token']) ? t::w('connected', 'қосулы') . (!empty($gtok['email']) ? ': ' . dev::h($gtok['email']) : '') : t::w('none', 'жоқ')?></span></div>
					<? if (!empty($gtok['refresh_token'])): ?><button class="btn btn_r" type="button" data-act="get.php?drive_off" data-ask="<?=dev::h(t::w('Disconnect Google Drive? Files stay on Drive, new ones are not saved until you connect again.', 'Google Drive-ты ажыратамыз ба? Файлдар Drive-та қалады, қайта қосқанша жаңалары сақталмайды.'))?>"><i class="fal fa-unlink"></i><?=t::w('Disconnect', 'Ажырату')?></button><? endif ?>
				</div>
			<? endif ?>
			<div><button class="btn btn_g" type="submit"><i class="fal fa-check"></i><?=t::w('Save', 'Сақтау')?></button></div>
		<? endif ?>
	</form>
	<? endforeach ?>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-external-link"></i> <?=t::w('On their own pages', 'Өз беттерінде')?></h2></div>
		<? if (dev::can('wa')): ?><div class="ky_row"><div><b>WhatsApp</b><span><?=t::w('confirmation code channel and tokens', 'растау коды арнасы мен токендері')?></span></div><a class="btn btn_l" href="/wa/"><i class="fab fa-whatsapp"></i><?=t::w('Open', 'Ашу')?></a></div><? endif ?>
		<? if (dev::can('tg')): ?><div class="ky_row"><div><b><?=t::w('Telegram bot', 'Telegram бот')?></b><span><?=t::w('bot token and webhook', 'бот токені мен webhook')?></span></div><a class="btn btn_l" href="/tg/"><i class="fab fa-telegram-plane"></i><?=t::w('Open', 'Ашу')?></a></div><? endif ?>
	</div>
	<? endif ?>
</div>
<? include "../block/footer.php"; ?>
