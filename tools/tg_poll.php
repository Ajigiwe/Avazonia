<?php
/**
 * CLI Tool: Long-polling worker for Telegram Bot.
 *
 * Allows receiving real-time Telegram updates directly on local development
 * machines without needing a public HTTPS domain, ngrok tunnel, or port forwarding.
 *
 * Usage:
 *   php tools/tg_poll.php
 *
 * Press Ctrl+C to stop.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/intake_service.php';
require_once __DIR__ . '/../includes/intake_xlsx.php';
require_once __DIR__ . '/../includes/intake_matcher.php';

$cfg = intake_config();

echo "========================================================\n";
echo " Avazonia Telegram Bot - Local Polling Worker\n";
echo "========================================================\n\n";

if ($cfg['token'] === '') {
    echo "[-] Error: TG_BOT_TOKEN is not defined in .env\n";
    exit(1);
}

// 1. Verify Bot Identity
$me = tg_api($cfg, 'getMe');
if (empty($me['ok'])) {
    echo "[-] Error: Failed to authenticate with Telegram Bot API.\n";
    echo "    Description: " . ($me['description'] ?? 'Network error') . "\n";
    exit(1);
}

$bot = $me['result'];
echo "[+] Bot authenticated: @" . ($bot['username'] ?? 'unknown') . " (" . ($bot['first_name'] ?? '') . ")\n";
echo "[+] Allowed Chat IDs:  " . (!empty($cfg['chats']) ? implode(', ', $cfg['chats']) : '(None configured!)') . "\n";

// 2. Ensure Webhook is cleared for polling mode
$wh = tg_api($cfg, 'getWebhookInfo');
if (!empty($wh['result']['url'])) {
    echo "[*] Clearing existing webhook (" . $wh['result']['url'] . ") to enable polling mode...\n";
    tg_api($cfg, 'deleteWebhook');
    echo "[+] Webhook cleared. Polling mode active.\n";
}

echo "\n[*] Worker started! Listening for incoming messages from Telegram...\n";
echo "    Open Telegram and message @" . ($bot['username'] ?? 'your bot') . " to test.\n";
echo "    Press Ctrl+C to exit.\n\n";

$offset = 0;

while (true) {
    $params = [
        'offset'  => $offset,
        'timeout' => 20,
    ];

    $res = tg_api($cfg, 'getUpdates', $params);

    if (is_array($res) && !empty($res['ok']) && is_array($res['result'])) {
        foreach ($res['result'] as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            $offset = $updateId + 1;

            $msg = $update['message'] ?? null;
            if (!$msg) continue;

            $chatId = (int) ($msg['chat']['id'] ?? 0);
            $fromName = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
            $fromUser = $msg['from']['username'] ?? 'no_username';

            echo sprintf("[%s] Update #%d from %s (@%s, ID: %d)\n",
                date('H:i:s'),
                $updateId,
                $fromName ?: 'Unknown',
                $fromUser,
                $chatId
            );

            // Process update through intake engine
            $reply = intake_dispatch($cfg, $update);

            if ($reply !== '') {
                echo "    -> Bot reply: " . str_replace("\n", " ", substr($reply, 0, 80)) . "...\n";
                tg_send_text($cfg, $chatId, $reply);
            } else {
                echo "    -> Processed (photo staged or dropped)\n";
            }
        }
    }

    // Small sleep between poll attempts
    usleep(500000);
}
