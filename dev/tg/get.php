<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id || !dev::can('tg')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);
	require __DIR__ . '/lib.php';
	if (!dev::row("SHOW TABLES LIKE 'sys_setting'")) out(['ok' => false, 'msg' => t::w('Add the SQL first', 'Алдымен SQL қосыңыз') . ' (sys_setting)']);

	// Токенді сақтау: getMe арқылы тексеріп, бот атын алады, webhook-ты қайта тіркейді
	if (isset($_GET['save'])) {
		$tok = trim(preg_replace('/\s+/', '', (string)@$_POST['token']));
		$old = sys_set('tg_token');
		if ($tok === '') $tok = $old;
		if (!preg_match('/^\d{5,15}:[A-Za-z0-9_-]{30,60}$/', $tok)) out(['ok' => false, 'msg' => t::w('This does not look like a bot token', 'Бұл бот токеніне ұқсамайды (123456789:AA…)')]);
		$me = tg_api('getMe', [], $tok);
		if (empty($me['ok'])) out(['ok' => false, 'msg' => t::w('Telegram refused the token', 'Telegram токенді қабылдамады') . ': ' . (string)@$me['description']]);
		if ($tok !== $old) {
			if ($old !== '') tg_api('deleteWebhook', [], $old); // ескі бот бұдан былай сайтқа жазбайды
			sys_set_put('tg_offset', '0', $user_id);
			sys_set_put('tg_hook_secret', bin2hex(random_bytes(16)), $user_id);
		}
		sys_set_put('tg_token', $tok, $user_id);
		sys_set_put('tg_bot_name', (string)$me['result']['username'], $user_id);
		$h = tg_hook_set();
		if (!$h['ok']) out(['ok' => false, 'msg' => t::w('Saved, but the webhook failed', 'Сақталды, бірақ webhook тіркелмеді') . ': ' . $h['msg']]);
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды') . ': @' . $me['result']['username']]);
	}

	if (isset($_GET['hook'])) {
		$h = tg_hook_set();
		out(['ok' => $h['ok'], 'msg' => $h['ok'] ? ($h['msg'] === 'local' ? t::w('Local: webhook is off, use «Check»', 'Локал: webhook жоқ, «Тексеру» басыңыз') : 'Webhook: ' . $h['msg']) : $h['msg']]);
	}

	// Локалда (webhook жоқ) жиналған хабарларды алу. Live-та webhook бар болса Telegram getUpdates бермейді — бұл қалыпты.
	if (isset($_GET['poll'])) {
		$n = tg_poll();
		if (!is_int($n)) out(strpos($n, 'webhook') !== false ? ['ok' => true, 'msg' => t::w('Webhook is on: updates come by themselves', 'Webhook қосулы: хабарлар өзі келеді')] : ['ok' => false, 'msg' => $n]);
		out(['ok' => true, 'msg' => t::w('Updates processed', 'Өңделді') . ': ' . $n]);
	}
	out(['ok' => false, 'msg' => 'none']);
