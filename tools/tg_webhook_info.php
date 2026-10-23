<?php
/**
 * CLI Tool: Inspect Telegram bot webhook status and health.
 *
 * Usage:
 *   php tools/tg_webhook_info.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/intake_service.php';

$cfg = intake_config();

echo "========================================================\n";
echo " Avazonia Telegram Bot - Webhook Inspection Tool\n";
echo "========================================================\n\n";

if ($cfg['token'] === '') {
    echo "[-] Error: TG_BOT_TOKEN is not defined in .env\n";
    exit(1);
}

// 1. Check Bot Identity
$me = tg_api($cfg, 'getMe');
if (!empty($me['ok'])) {
    $bot = $me['result'];
    echo "[+] Bot Identity:\n";
    echo "    ID:        " . ($bot['id'] ?? 'N/A') . "\n";
    echo "    Name:      " . ($bot['first_name'] ?? '') . "\n";
    echo "    Username:  @" . ($bot['username'] ?? 'N/A') . "\n\n";
} else {
    echo "[-] Failed to authenticate bot token with Telegram.\n";
    echo "    Description: " . ($me['description'] ?? 'Network or token error') . "\n\n";
}

// 2. Check Webhook Info
$info = tg_api($cfg, 'getWebhookInfo');
if (!empty($info['ok'])) {
    $res = $info['result'];
    echo "[*] Webhook Status:\n";
    echo "    Registered URL:           " . ($res['url'] !== '' ? $res['url'] : '(none - polling mode)') . "\n";
    echo "    Has Custom Certificate:   " . (!empty($res['has_custom_certificate']) ? 'Yes' : 'No') . "\n";
    echo "    Pending Updates Count:    " . ($res['pending_update_count'] ?? 0) . "\n";
    echo "    Max Connections:          " . ($res['max_connections'] ?? 40) . "\n";
    if (!empty($res['ip_address'])) {
        echo "    Resolved IP Address:      " . $res['ip_address'] . "\n";
    }
    if (!empty($res['last_error_date'])) {
        echo "    [!] Last Error Date:      " . date('Y-m-d H:i:s', $res['last_error_date']) . "\n";
        echo "    [!] Last Error Message:   " . ($res['last_error_message'] ?? 'N/A') . "\n";
    }
    if (!empty($res['last_synchronization_error_date'])) {
        echo "    [!] Last Sync Error:      " . date('Y-m-d H:i:s', $res['last_synchronization_error_date']) . "\n";
    }
    echo "\n";
} else {
    echo "[-] Failed to retrieve webhook info: " . ($info['description'] ?? 'Unknown error') . "\n";
}

// 3. Local Configuration Checks
echo "[*] Local Staging Configuration:\n";
echo "    Allowed Chat IDs:         " . (!empty($cfg['chats']) ? implode(', ', $cfg['chats']) : '(EMPTY - all updates will be dropped!)') . "\n";
echo "    Secret Configured:        " . ($cfg['secret'] !== '' ? 'Yes (' . strlen($cfg['secret']) . ' chars)' : 'NO') . "\n";
echo "    Local Uploads Directory:  " . realpath(__DIR__ . '/../public/uploads/intake') . "\n";
echo "    Active Pending Batches:   " . intake_pending_count() . "\n";
echo "\n";
