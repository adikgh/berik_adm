<? include "../config/core.php";

	header('Content-Type: application/json; charset=utf-8');
	function wa_out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit(); }

	if (!$user_id || !wa_owner_ok($user_id, @$user_right['positions_id'])) wa_out(['ok' => false, 'msg' => 'Рұқсат жоқ']);
	if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)@$_POST['csrf'])) wa_out(['ok' => false, 'msg' => 'Бетті жаңартып, қайта көріңіз']);

	function wa_str($k, $max = 600) { return mb_substr(trim(str_replace(["\r", "\n"], '', (string)@$_POST[$k])), 0, $max); }

	// Баптауды сақтау → config/wa_conf.php
	if (isset($_GET['save'])) {
		$old = wa_conf();
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
		if ($c['channel'] == 'test' && !in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) wa_out(['ok' => false, 'msg' => 'Тест режимі тек локалда (код экранда көрінеді)']);

		$c['owners'] = $old['owners'] ?: [(int)$user_id];
		if (!wa_conf_write($c)) wa_out(['ok' => false, 'msg' => 'config/wa_conf.php файлына жазу мүмкін болмады']);
		wa_out(['ok' => true, 'msg' => 'Сақталды']);
	}

	// Тест код жіберу
	if (isset($_GET['test'])) {
		$phone = substr(preg_replace('/\D/', '', (string)@$_POST['phone']), -10);
		if (strlen($phone) != 10) wa_out(['ok' => false, 'msg' => 'Нөмірді толық жазыңыз']);
		$c = wa_conf();
		if (!wa_on()) wa_out(['ok' => false, 'msg' => 'Арна өшірулі. Алдымен арнаны таңдап, сақтаңыз']);
		if (rl_over('test|' . $phone, 5, 900)) wa_out(['ok' => false, 'msg' => 'Тым көп тест. 15 минуттан кейін']);
		rl_hit('test|' . $phone);
		$code = (string)random_int(1000, 9999);
		if (wa_send_code($phone, $code)) wa_out(['ok' => true, 'msg' => $c['channel'] == 'test' ? "Тест режимі: код $code (ешқайда жіберілмеді)" : 'Жіберілді. WhatsApp-ты тексеріңіз, код: ' . $code]);
		wa_out(['ok' => false, 'msg' => 'Жіберілмеді. ' . (wa_last_error() ?: 'Баптауды тексеріңіз')]);
	}

	// Барлық ашық парольді шифрлау
	if (isset($_GET['hash_all'])) {
				$q = db::query("SELECT id, `password` FROM `user` WHERE `password` IS NOT NULL AND `password` <> '' AND `password` NOT LIKE '\$2y\$%'");
		$n = 0;
		while ($r = mysqli_fetch_assoc($q)) {
			if (pass_is_hash($r['password'])) continue;
			$h = mysqli_real_escape_string(db::$connect, pass_make($r['password']));
			if (db::query("UPDATE `user` SET `password` = '$h' WHERE id = '" . (int)$r['id'] . "' AND `password` = '" . mysqli_real_escape_string(db::$connect, $r['password']) . "'")) $n++;
		}
		$left = (int)mysqli_fetch_assoc(db::query("SELECT COUNT(*) n FROM `user` WHERE `password` IS NOT NULL AND `password` <> '' AND `password` NOT LIKE '\$2y\$%'"))['n'];
		auth_relogin($user_id);
		wa_out(['ok' => true, 'msg' => "Шифрланды: $n", 'left' => $left]);
	}

	wa_out(['ok' => false, 'msg' => 'Белгісіз әрекет']);
