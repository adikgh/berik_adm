<?php
// WhatsApp баптауы бүкіл жүйеге ортақ: админнің config/wa_conf.php-сы (git-ке кірмейді). Dev осы файлды оқып-жазады.
function dwa_path() { return DEV_ADM . '/config/wa_conf.php'; }
function dwa_conf() {
	$c = wa_conf_default();
	if (is_file(dwa_path())) {
		$x = include dwa_path();
		if (is_array($x)) { $login = array_merge($c['login'], (array)@$x['login']); $c = array_merge($c, $x); $c['login'] = $login; }
	}
	return $c;
}
function dwa_write($c) {
	$f = dwa_path();
	$php = "<?php\n// WhatsApp растау коды. Әзірлеуші панеліндегі «WhatsApp» бетінен өзгертіледі. Git-ке кірмейді (құпия).\nreturn " . var_export($c, true) . ";\n";
	if (@file_put_contents($f, $php, LOCK_EX) === false) return false;
	if (function_exists('opcache_invalidate')) @opcache_invalidate($f, true);
	return true;
}
function dwa_mask($s) { $s = (string)$s; return $s === '' ? '' : (strlen($s) > 8 ? substr($s, 0, 4) . '••••' . substr($s, -4) : '••••'); }
function dwa_plain() { return (int)dev::one("select count(*) from `user` where `password` is not null and `password` <> '' and `password` not like '\$2y\$%'"); }
