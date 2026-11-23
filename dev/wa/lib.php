<?php
// WhatsApp баптауы бүкіл жүйеге ортақ: базада sys_setting.wa_conf (JSON). Базада жоқ болса ескі админ config/wa_conf.php оқылады; сақтау тек базаға.
function dwa_path() { return DEV_ADM . '/config/wa_conf.php'; }
function dwa_conf() {
	require_once DEV_ADM . '/config/sys_set.php';
	$c = wa_conf_default();
	$x = json_decode(sys_set('wa_conf'), true);
	if (!is_array($x) && is_file(dwa_path())) $x = include dwa_path();
	if (is_array($x)) { $login = array_merge($c['login'], (array)@$x['login']); $c = array_merge($c, $x); $c['login'] = $login; }
	return $c;
}
function dwa_write($c) {
	require_once DEV_ADM . '/config/sys_set.php';
	return sys_set_put('wa_conf', json_encode($c, JSON_UNESCAPED_UNICODE), (int)($GLOBALS['user_id'] ?? 0));
}
function dwa_mask($s) { $s = (string)$s; return $s === '' ? '' : (strlen($s) > 8 ? substr($s, 0, 4) . '••••' . substr($s, -4) : '••••'); }
function dwa_plain() { return (int)dev::one("select count(*) from `user` where `password` is not null and `password` <> '' and `password` not like '\$2y\$%'"); }
