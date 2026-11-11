<?php include "../config/core.php";
	header('Content-Type: application/json; charset=utf-8');
	function out($a) { exit(json_encode($a, JSON_UNESCAPED_UNICODE)); }
	if (!$user_id || !dev::can('wa')) out(['ok' => false, 'msg' => t::w('No access', 'Рұқсат жоқ')]);
	if (!dev_post_ok()) out(['ok' => false, 'msg' => t::w('Refresh the page and try again', 'Бетті жаңартып, қайта көріңіз')]);
	require __DIR__ . '/lib.php';
	function wa_str($k, $max = 600) { return mb_substr(trim(str_replace(["\r", "\n"], '', (string)@$_POST[$k])), 0, $max); }

	// Баптауды сақтау → админнің config/wa_conf.php
	if (isset($_GET['save'])) {
		$old = dwa_conf();
		$c = wa_conf_default();
		$c['channel'] = in_array(@$_POST['channel'], ['off', 'test', 'green', 'meta'], true) ? $_POST['channel'] : 'off';
		$c['green_url'] = wa_str('green_url') ?: 'https://api.green-api.com';
		$c['green_instance'] = preg_replace('/\D/', '', wa_str('green_instance'));
		$c['green_token'] = wa_str('green_token') !== '' ? wa_str('green_token') : $old['green_token'];
		$c['text'] = wa_str('text', 300);
		if (strpos($c['text'], '%s') === false) $c['text'] = wa_conf_default()['text'];
		$c['meta_token'] = wa_str('meta_token', 1000) !== '' ? wa_str('meta_token', 1000) : $old['meta_token'];
		$c['meta_phone_id'] = preg_replace('/\D/', '', wa_str('meta_phone_id'));
		$c['meta_template'] = preg_replace('/[^a-z0-9_]/', '', strtolower(wa_str('meta_template')));
		$c['meta_lang'] = preg_replace('/[^A-Za-z_]/', '', wa_str('meta_lang')) ?: 'ru';
		$c['meta_button'] = empty($_POST['meta_button']) ? 0 : 1;
		foreach (array_keys($c['login']) as $k) $c['login'][$k] = empty($_POST['login'][$k]) ? 0 : 1;
		$c['trust_days'] = max(1, min(365, (int)@$_POST['trust_days']));
		if ($c['channel'] == 'test' && !in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) out(['ok' => false, 'msg' => t::w('Test mode works only locally (the code is shown on screen)', 'Тест режимі тек локалда (код экранда көрінеді)')]);
		$c['owners'] = $old['owners'];
		if (!dwa_write($c)) out(['ok' => false, 'msg' => t::w('Could not write config/wa_conf.php', 'config/wa_conf.php файлына жазу мүмкін болмады')]);
		out(['ok' => true, 'msg' => t::w('Saved', 'Сақталды')]);
	}

	// Тест код жіберу (сақталған баптаумен)
	if (isset($_GET['test'])) {
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		if (strlen($phone) != 10) out(['ok' => false, 'msg' => t::w('Enter the full number', 'Нөмірді толық жазыңыз')]);
		$c = dwa_conf();
		if ($c['channel'] == 'off') out(['ok' => false, 'msg' => t::w('The channel is off. Choose a channel and save first', 'Арна өшірулі. Алдымен арнаны таңдап, сақтаңыз')]);
		if (rl_over('test|' . $phone, 5, 900)) out(['ok' => false, 'msg' => t::w('Too many tests. Try in 15 minutes', 'Тым көп тест. 15 минуттан кейін')]);
		rl_hit('test|' . $phone);
		$code = (string)random_int(1000, 9999);
		if (wa_send_code($phone, $code, $c)) out(['ok' => true, 'msg' => $c['channel'] == 'test' ? sprintf(t::w('Test mode: code %s (not sent anywhere)', 'Тест режимі: код %s (ешқайда жіберілмеді)'), $code) : sprintf(t::w('Sent. Check WhatsApp, code: %s', 'Жіберілді. WhatsApp-ты тексеріңіз, код: %s'), $code)]);
		out(['ok' => false, 'msg' => t::w('Not sent.', 'Жіберілмеді.') . ' ' . (wa_last_error() ?: t::w('Check the settings', 'Баптауды тексеріңіз'))]);
	}

	// Барлық ашық парольді шифрлау
	if (isset($_GET['hash_all'])) {
		$q = db::query("select id, `password` from `user` where `password` is not null and `password` <> '' and `password` not like '\$2y\$%'");
		$n = 0;
		while ($r = mysqli_fetch_assoc($q)) {
			if (pass_is_hash($r['password'])) continue;
			$h = dev::e(pass_make($r['password']));
			if (db::query("update `user` set `password` = '$h' where id = '" . (int)$r['id'] . "' and `password` = '" . dev::e($r['password']) . "'")) $n++;
		}
		if (function_exists('auth_relogin')) auth_relogin($user_id);
		out(['ok' => true, 'msg' => sprintf(t::w('Encrypted: %d', 'Шифрланды: %d'), $n), 'left' => dwa_plain()]);
	}
	out(['ok' => false, 'msg' => t::w('Unknown action', 'Белгісіз әрекет')]);
