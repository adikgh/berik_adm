<?php
// Telegram webhook: https://devb.gprog.kz/tg/hook.php (dev ▸ Telegram бот сақтағанда өзі тіркеледі).
// Кіру керек емес, бірақ Telegram жіберетін құпия тақырып (secret_token) тексеріледі.
define('DEV_ADM', dirname(__DIR__, 2));
require_once DEV_ADM . '/config/db.php';
new db;
mysqli_set_charset(db::$connect, 'utf8mb4');
date_default_timezone_set('Asia/Aqtau');
require_once __DIR__ . '/lib.php';

$sec = sys_set('tg_hook_secret');
if ($sec === '' || !hash_equals($sec, (string)@$_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'])) { http_response_code(403); exit; }
$u = json_decode((string)file_get_contents('php://input'), true);
try { tg_update($u); } catch (Throwable $e) { error_log('tg hook: ' . $e->getMessage()); }
echo 'ok';
