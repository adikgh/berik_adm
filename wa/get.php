<?php
// WhatsApp баптауы әзірлеуші панеліне көшті (dev → WhatsApp, 2026-11-10).
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => false, 'msg' => 'WhatsApp баптауы әзірлеуші панелінде'], JSON_UNESCAPED_UNICODE);
