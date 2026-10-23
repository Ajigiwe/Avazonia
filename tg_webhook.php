<?php
/**
 * Telegram webhook endpoint for Avazonia.
 *
 * Receives product catalogs (.csv/.xlsx) and photography from whitelisted
 * Telegram chats, stages them in isolated intake tables, and alerts merchants.
 *
 * Security Model:
 *   - HTTP 503 if TG_WEBHOOK_SECRET is not configured in .env
 *   - HTTP 401 if X-Telegram-Bot-Api-Secret-Token does not match TG_WEBHOOK_SECRET
 *   - Whitelist authorization: Non-whitelisted chats are acknowledged and dropped silently
 *   - Zero direct publishing: Committing to the live store catalogue stays strictly behind
 *     admin authentication and CSRF protection in admin/intake.php
 *
 * CLI equivalent for local development / testing:
 *   php tools/tg_simulate_update.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/intake_service.php';
require_once __DIR__ . '/includes/intake_xlsx.php';

header('Content-Type: application/json; charset=utf-8');

$cfg = intake_config();
if ($cfg['secret'] === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Telegram webhook not configured in .env']);
    exit;
}

$given = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
if ($given === '' || !hash_equals($cfg['secret'], $given)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized secret token']);
    exit;
}

$raw = (string) file_get_contents('php://input');
$update = json_decode($raw, true);
if (!is_array($update)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

// Ingest update (sheet, photo, command)
$reply = intake_dispatch($cfg, $update);
if ($reply !== '') {
    $chatId = (int) ($update['message']['chat']['id'] ?? 0);
    if ($chatId !== 0) {
        tg_send_text($cfg, $chatId, $reply);
    }
}

// Always respond 200 OK once authenticated to satisfy Telegram's webhook retry policy
echo json_encode(['ok' => true]);
