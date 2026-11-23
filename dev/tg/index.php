<?php include "../config/core.php";
	// Telegram бот: токен базада (sys_setting), файл өзгертпей осы жерден ауыстырылады. Бот бұзылса:
	// BotFather → /revoke (не жаңа бот) → жаңа токенді осында қойып сақтау → webhook өзі қайта тіркеледі.
	dev_only('tg');
	require __DIR__ . '/lib.php';
	$menu = 'tg'; $page_title = t::w('Telegram bot', 'Telegram бот');
	$page_sub = t::w('One bot for the whole system: orders go to each company group.', 'Бүкіл жүйеге бір бот: тапсырыстар әр компанияның тобына барады.');
	$tbl_ok = (bool)dev::row("SHOW TABLES LIKE 'sys_setting'");
	$cols_ok = tg_cols_ok();
	$tok = sys_set('tg_token');
	$bot = sys_set('tg_bot_name');
	$info = $tok !== '' ? tg_api('getWebhookInfo') : null;
	$hook = $info && !empty($info['ok']) ? $info['result'] : null;
	$mask = $tok === '' ? '' : substr($tok, 0, strpos($tok, ':') ?: 6) . ':…' . substr($tok, -4);
	$linked = $cols_ok ? dev::rows("select id, name, chat_id, chat_send, tg_title from company where chat_id is not null and chat_id <> '' order by name") : [];
	$edit = dev::can('tg');
?>
<? include "../block/header.php"; ?>
<style>
	.tg_page{display:flex;flex-direction:column;gap:16px;max-width:860px}
	.tg_page .card{margin:0;display:flex;flex-direction:column;gap:12px}
	.tg_page ol{margin:0;padding-left:20px;line-height:1.7}
	.tg_ok{color:#1a9b4b}.tg_bad{color:#c0392b}
</style>
<div class="tg_page">
	<? if (!$tbl_ok || !$cols_ok): ?><p class="note"><?=t::w('Add the Telegram SQL to the database first (sys_setting table, company.tg_* columns).', 'Алдымен базаға Telegram SQL-ын қосыңыз (sys_setting кестесі, company.tg_* бағандары).')?></p><? endif ?>

	<form class="card" data-post="get.php?save" autocomplete="off" data-ask="<?=dev::h(t::w('Save the bot? The webhook is registered again.', 'Ботты сақтаймыз ба? Webhook қайта тіркеледі.'))?>">
		<div class="card_h"><h2><i class="fab fa-telegram-plane" style="color:#229ED9"></i> <?=t::w('Bot', 'Бот')?></h2><small><?=($bot !== '' ? '@' . dev::h($bot) : t::w('Not set', 'Қойылмаған'))?></small></div>
		<label class="mb"><?=t::w('Bot token from BotFather', 'BotFather берген бот токені')?><input name="token" placeholder="<?=dev::h($mask !== '' ? $mask . ' (' . t::w('leave empty to keep', 'өзгертпесеңіз бос қалдырыңыз') . ')' : '123456789:AA…')?>"<?=$edit ? '' : ' disabled'?>></label>
		<p class="hint"><?=t::w('The bot name is read from Telegram automatically. Nothing is written to files or git.', 'Бот аты Telegram-нан өзі алынады. Файлға да, git-ке де ештеңе жазылмайды.')?></p>
		<? if ($edit): ?><div><button class="btn btn_g" type="submit"><i class="fal fa-check"></i><?=t::w('Save', 'Сақтау')?></button></div><? endif ?>
	</form>

	<? if ($tok !== ''): ?>
	<div class="card">
		<div class="card_h"><h2><i class="fal fa-plug"></i> <?=t::w('Connection', 'Байланыс')?></h2></div>
		<dl class="dl">
			<dt>Webhook</dt><dd><?=$hook && $hook['url'] !== '' ? dev::h($hook['url']) : t::w('none (local: use «Check»)', 'жоқ (локал: «Тексеру» басыңыз)')?></dd>
			<? if ($hook && !empty($hook['last_error_message'])): ?><dt><?=t::w('Last error', 'Соңғы қате')?></dt><dd class="tg_bad"><?=dev::h($hook['last_error_message'])?> · <?=date('d.m H:i', (int)$hook['last_error_date'])?></dd><? endif ?>
			<? if ($hook): ?><dt><?=t::w('Waiting updates', 'Күтіп тұрған')?></dt><dd><?=(int)$hook['pending_update_count']?></dd><? endif ?>
			<? if (!$hook): ?><dt><?=t::w('Status', 'Күйі')?></dt><dd class="tg_bad"><?=t::w('Telegram refused the token', 'Telegram токенді қабылдамады')?></dd><? endif ?>
		</dl>
		<div style="display:flex;gap:8px;flex-wrap:wrap">
			<button class="btn btn_l" type="button" data-act="get.php?poll"><i class="fal fa-sync"></i><?=t::w('Check', 'Тексеру')?></button>
			<? if ($edit): ?><button class="btn btn_l" type="button" data-act="get.php?hook"><i class="fal fa-link"></i><?=t::w('Register webhook again', 'Webhook-ты қайта тіркеу')?></button><? endif ?>
		</div>
	</div>
	<? endif ?>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-users"></i> <?=t::w('Company groups', 'Компания топтары')?></h2><small><?=count($linked)?></small></div>
		<? if ($linked): ?>
		<div class="tbl_w"><table class="tbl">
			<thead><tr><th><?=t::w('Company', 'Компания')?></th><th><?=t::w('Group', 'Топ')?></th><th>Chat ID</th><th><?=t::w('Sending', 'Жіберу')?></th></tr></thead>
			<tbody>
			<? foreach ($linked as $l): ?>
				<tr data-href="/companies/settings.php?id=<?=(int)$l['id']?>#tg"><td><?=dev::h($l['name'])?></td><td><?=dev::h($l['tg_title'] ?: '—')?></td><td><?=dev::h($l['chat_id'])?></td><td class="<?=(int)$l['chat_send'] ? 'tg_ok' : 'tg_bad'?>"><?=(int)$l['chat_send'] ? t::w('On', 'Қосулы') : t::w('Off', 'Өшік')?></td></tr>
			<? endforeach ?>
			</tbody>
		</table></div>
		<? else: ?><p class="hint"><?=t::w('No company has a group yet.', 'Әзірге ешбір компанияға топ қосылмаған.')?></p><? endif ?>
	</div>

	<div class="card">
		<div class="card_h"><h2><i class="fal fa-shield-alt"></i> <?=t::w('If the bot is abused', 'Бот бұзылса не токен тарап кетсе')?></h2></div>
		<ol>
			<li><?=t::w('BotFather → /revoke → choose the bot (or /newbot for a new one).', 'BotFather → /revoke → ботты таңдаңыз (не жаңа бот үшін /newbot).')?></li>
			<li><?=t::w('Paste the new token above and save.', 'Жаңа токенді жоғарыға қойып, «Сақтау» басыңыз.')?></li>
			<li><?=t::w('If it is a new bot, add it to each group again with «Add to group» in company settings. The group ID is saved by itself.', 'Жаңа бот болса, компания баптауындағы «Топқа қосу» батырмасымен әр топқа қайта қосыңыз. Топ ID-ы өзі жазылады.')?></li>
		</ol>
		<p class="hint"><?=t::w('The bot answers only the one-time link code in a group. Other commands and chats are ignored.', 'Бот тек топтағы бір реттік қосу кодына жауап береді. Басқа командалар мен чаттар еленбейді.')?></p>
	</div>
</div>
<? include "../block/footer.php"; ?>
