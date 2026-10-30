<?php
/**
 * CLI Tool: Offline simulation of Telegram Bot updates.
 *
 * Test sheet ingestion, photo matching, and chat commands locally
 * without needing an active Telegram Bot token or public HTTPS webhook tunnel.
 *
 * Usage:
 *   php tools/tg_simulate_update.php --type=command --text="/start"
 *   php tools/tg_simulate_update.php --type=command --text="/status"
 *   php tools/tg_simulate_update.php --type=sheet --file="path/to/catalog.csv"
 *   php tools/tg_simulate_update.php --type=photo --file="path/to/photo.jpg" --caption="Dress SKU-1002"
 *   php tools/tg_simulate_update.php --type=command --text="/undo"
 *   php tools/tg_simulate_update.php --type=command --text="/clear"
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/intake_service.php';
require_once __DIR__ . '/../includes/intake_matcher.php';

// Parse CLI options
$options = getopt('', ['type:', 'text:', 'file:', 'caption:', 'album:', 'chat_id:']);

$type = $options['type'] ?? 'command';
$cfg  = intake_config();

$chatId = isset($options['chat_id']) ? (int) $options['chat_id'] : ($cfg['chats'][0] ?? 999999);
if (!in_array($chatId, $cfg['chats'], true)) {
    // Ensure simulator chat ID is allowed for local simulation
    $cfg['chats'][] = $chatId;
}

echo "========================================================\n";
echo " Avazonia Telegram Intake - Local Simulation\n";
echo "========================================================\n";
echo "Simulating update from Chat ID: $chatId\n";
echo "Type: $type\n\n";

$update = [];
$localFiles = [];
$msgId = (int) (time() + rand(100, 999));

if ($type === 'command') {
    $text = (string) ($options['text'] ?? '/status');
    $update = [
        'update_id' => rand(100000, 999999),
        'message' => [
            'message_id' => $msgId,
            'date'       => time(),
            'chat'       => ['id' => $chatId, 'type' => 'private'],
            'from'       => ['id' => $chatId, 'first_name' => 'StoreManager'],
            'text'       => $text,
        ],
    ];
} elseif ($type === 'sheet') {
    $filePath = (string) ($options['file'] ?? '');
    if ($filePath === '' || !file_exists($filePath)) {
        echo "[-] Error: File not found: $filePath\n";
        echo "    Provide a valid file path via --file=\"filename.csv\"\n";
        exit(1);
    }
    $bytes = file_get_contents($filePath);
    $fileName = basename($filePath);
    $fileId = 'sim_doc_' . bin2hex(random_bytes(4));
    $localFiles[$fileId] = $bytes;

    $update = [
        'update_id' => rand(100000, 999999),
        'message' => [
            'message_id' => $msgId,
            'date'       => time(),
            'chat'       => ['id' => $chatId, 'type' => 'private'],
            'from'       => ['id' => $chatId, 'first_name' => 'StoreManager'],
            'document'   => [
                'file_id'   => $fileId,
                'file_name' => $fileName,
                'file_size' => strlen($bytes),
                'mime_type' => str_ends_with($fileName, '.xlsx')
                    ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    : 'text/csv',
            ],
        ],
    ];
} elseif ($type === 'photo') {
    $filePath = (string) ($options['file'] ?? '');
    if ($filePath === '' || !file_exists($filePath)) {
        echo "[-] Error: Image file not found: $filePath\n";
        echo "    Provide a valid image path via --file=\"item.jpg\"\n";
        exit(1);
    }
    $bytes = file_get_contents($filePath);
    $caption = (string) ($options['caption'] ?? '');
    $albumId = (string) ($options['album'] ?? '');
    $fileId = 'sim_photo_' . bin2hex(random_bytes(4));
    $localFiles[$fileId] = $bytes;

    $msg = [
        'message_id' => $msgId,
        'date'       => time(),
        'chat'       => ['id' => $chatId, 'type' => 'private'],
        'from'       => ['id' => $chatId, 'first_name' => 'StoreManager'],
        'photo'      => [
            ['file_id' => $fileId . '_small', 'width' => 100, 'height' => 100],
            ['file_id' => $fileId, 'width' => 800, 'height' => 800],
        ],
    ];
    if ($caption !== '') {
        $msg['caption'] = $caption;
    }
    if ($albumId !== '') {
        $msg['media_group_id'] = $albumId;
    }

    $update = [
        'update_id' => rand(100000, 999999),
        'message'   => $msg,
    ];
} elseif ($type === 'video') {
    $filePath = (string) ($options['file'] ?? '');
    if ($filePath === '' || !file_exists($filePath)) {
        echo "[-] Error: Video file not found: $filePath\n";
        echo "    Provide a valid video path via --file=\"walkthrough.mp4\"\n";
        exit(1);
    }
    $bytes = file_get_contents($filePath);
    $caption = (string) ($options['caption'] ?? '');
    $fileId = 'sim_video_' . bin2hex(random_bytes(4));
    $localFiles[$fileId] = $bytes;

    $msg = [
        'message_id' => $msgId,
        'date'       => time(),
        'chat'       => ['id' => $chatId, 'type' => 'private'],
        'from'       => ['id' => $chatId, 'first_name' => 'StoreManager'],
        'video'      => [
            'file_id'   => $fileId,
            'duration'  => 30,
            'width'     => 1080,
            'height'    => 1920,
            'file_name' => basename($filePath),
            'mime_type' => 'video/mp4',
            'file_size' => strlen($bytes),
        ],
    ];
    if ($caption !== '') {
        $msg['caption'] = $caption;
    }

    $update = [
        'update_id' => rand(100000, 999999),
        'message'   => $msg,
    ];
} elseif ($type === 'text') {
    $text = (string) ($options['text'] ?? '');
    if ($text === '' && !empty($options['file']) && file_exists($options['file'])) {
        $text = file_get_contents($options['file']);
    }
    if ($text === '') {
        echo "[-] Error: Text is empty. Provide text via --text=\"...\" or --file=\"listing.txt\"\n";
        exit(1);
    }

    $update = [
        'update_id' => rand(100000, 999999),
        'message' => [
            'message_id' => $msgId,
            'date'       => time(),
            'chat'       => ['id' => $chatId, 'type' => 'private'],
            'from'       => ['id' => $chatId, 'first_name' => 'StoreManager'],
            'text'       => $text,
        ],
    ];
} else {
    echo "[-] Unknown update type: $type (expected command, text, sheet, photo, or video)\n";
    exit(1);
}

// Dispatch through intake service
$reply = intake_dispatch($cfg, $update, $localFiles);

echo "[+] Bot Response:\n";
echo "--------------------------------------------------------\n";
echo ($reply !== '' ? $reply : "(Silent OK / photo staged)") . "\n";
echo "--------------------------------------------------------\n\n";

// Display current active batch status
$batch = intake_active_batch($chatId);
if ($batch) {
    echo "[*] Active Batch #{$batch['id']}:\n";
    echo "    Sheet:  " . ($batch['sheet_name'] ?: 'None') . " (" . $batch['row_count'] . " rows)\n";
    echo "    Photos: " . intake_photo_count((int) $batch['id']) . " staged\n";
    echo "    Status: {$batch['status']}\n";
    echo "    Admin:  http://localhost/Avazonia-main/admin/intake.php?review={$batch['id']}\n";
} else {
    echo "[*] No pending batch currently active for this chat.\n";
}
