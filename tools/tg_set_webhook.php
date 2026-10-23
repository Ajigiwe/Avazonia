<?php
/**
 * CLI Tool: Set or delete Telegram bot webhook.
 *
 * Usage:
 *   php tools/tg_set_webhook.php            # Register webhook with TG_WEBHOOK_URL and TG_WEBHOOK_SECRET
 *   php tools/tg_set_webhook.php --drop     # Register webhook and drop pending updates
 *   php tools/tg_set_webhook.php --delete   # Delete webhook (switches back to getUpdates mode)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/intake_service.php';

$cfg = intake_config();

echo "========================================================\n";
echo " Avazonia Telegram Bot - Webhook Registration Tool\n";
echo "========================================================\n\n";

if ($cfg['token'] === '') {
    echo "[-] Error: TG_BOT_TOKEN is not defined in .env\n";
    exit(1);
}

$isDelete = in_array('--delete', $argv, true);
$dropPending = in_array('--drop', $argv, true);

if ($isDelete) {
    echo "[*] Deleting webhook from Telegram...\n";
    $res = tg_api($cfg, 'deleteWebhook', ['drop_pending_updates' => $dropPending]);
    if (!empty($res['ok'])) {
        echo "[+] Webhook deleted successfully!\n";
    } else {
        echo "[-] Failed to delete webhook: " . ($res['description'] ?? 'Unknown error') . "\n";
    }
    exit(0);
}

$url = $cfg['webhook_url'];
if ($url === '') {
    // Attempt automatic fallback
    $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
    $url = $appUrl . '/tg_webhook.php';
}

if ($cfg['secret'] === '') {
    echo "[-] Warning: TG_WEBHOOK_SECRET is empty. Telegram requires a secret token for secure verification.\n";
    echo "    Generate one (e.g. " . bin2hex(random_bytes(16)) . ") and put it in .env under TG_WEBHOOK_SECRET\n";
    exit(1);
}

echo "[*] Target URL:    " . $url . "\n";
echo "[*] Secret Token:  " . substr($cfg['secret'], 0, 4) . '...' . substr($cfg['secret'], -4) . "\n";
echo "[*] Drop Pending:  " . ($dropPending ? 'YES' : 'NO') . "\n\n";

if (!preg_match('#^https://#i', $url)) {
    echo "[!] WARNING: Telegram Bot API requires HTTPS with a valid SSL certificate for live webhooks!\n";
    echo "    Current URL is HTTP: $url\n";
    echo "    For local development and testing, use: php tools/tg_simulate_update.php\n";
    echo "    Or create an HTTPS tunnel using ngrok / Cloudflare Tunnel / LocalXpose:\n";
    echo "      ngrok http 80\n";
    echo "    Then set TG_WEBHOOK_URL=https://<your-subdomain>.ngrok-free.app/Avazonia-main/tg_webhook.php\n\n";
}

echo "[*] Sending setWebhook request to Telegram API...\n";
$params = [
    'url'                  => $url,
    'secret_token'         => $cfg['secret'],
    'drop_pending_updates' => $dropPending,
];

$res = tg_api($cfg, 'setWebhook', $params);

if (!empty($res['ok'])) {
    echo "[+] SUCCESS: Webhook registered with Telegram!\n";
    echo "    Description: " . ($res['description'] ?? 'OK') . "\n\n";
    echo "Tip: Run `php tools/tg_webhook_info.php` to verify webhook connectivity.\n";
} else {
    echo "[-] FAILED to register webhook:\n";
    echo "    Error Code:  " . ($res['error_code'] ?? 'N/A') . "\n";
    echo "    Description: " . ($res['description'] ?? 'No response / network error') . "\n";
    exit(1);
}
