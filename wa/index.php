<?php
// WhatsApp баптауы әзірлеуші панеліне көшті (dev → WhatsApp, 2026-11-10). Бұрынғы сілтеме сонда апарады.
$h = strtolower((string)@$_SERVER['HTTP_HOST']);
$dev = strpos($h, 'gprog.kz') !== false ? 'devb.gprog.kz' : 'dev.berik.c';
header('Location: ' . (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https') . '://' . $dev . '/wa/');
exit;
