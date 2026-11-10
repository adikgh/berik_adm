<?php
// Граммовка шаблондарын тек әзірлеуші қосады: редактор әзірлеуші панелінде (dev → Граммовка шаблондары, 2026-11-10).
// Себестегі «Шаблоннан толтыру» осы папкадағы get.php мен lib.php арқылы жұмыс істей береді.
$h = strtolower((string)@$_SERVER['HTTP_HOST']);
$dev = strpos($h, 'gprog.kz') !== false ? 'devb.gprog.kz' : 'dev.berik.c';
header('Location: ' . (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $dev . '/tpl/');
exit;
