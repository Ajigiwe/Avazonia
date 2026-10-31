<?php
/**
 * Telegram product intake - ingestion service.
 *
 * tg_webhook.php validates the request and hands the raw update to
 * intake_dispatch(); everything below stages the spreadsheet and photos from
 * an approved Telegram chat into intake_batches / intake_items.
 *
 * Staging vs Publishing Isolation:
 * The bot never writes directly to products or product_images. Everything stays
 * staged until reviewed and committed through the CSRF-protected admin panel
 * (admin/intake.php -> Product Importer / Waiting Room).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/intake_xlsx.php';
require_once __DIR__ . '/intake_parser.php';

const INTAKE_MAX_PHOTOS      = 400;
const INTAKE_MAX_IMAGE_BYTES = 8 * 1024 * 1024;   // 8 MB per photo
const INTAKE_MAX_VIDEO_BYTES = 25 * 1024 * 1024;  // 25 MB per video (Telegram API max ~20MB for bots)
const INTAKE_MAX_SHEET_BYTES = 2 * 1024 * 1024;   // 2 MB per spreadsheet

/* ------------------------------------------------------------- DB helpers ---- */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('db_all')) {
    function db_all(string $sql, array $params = []): array {
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}

if (!function_exists('db_one')) {
    function db_one(string $sql, array $params = []): ?array {
        $st = db()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }
}

if (!function_exists('db_val')) {
    function db_val(string $sql, array $params = [], mixed $default = null): mixed {
        $st = db()->prepare($sql);
        $st->execute($params);
        $v = $st->fetchColumn();
        return $v === false ? $default : $v;
    }
}

if (!function_exists('db_exec')) {
    function db_exec(string $sql, array $params = []): int {
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }
}

/* ------------------------------------------------------------- tables ---- */

function intake_tables(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    $driver = 'mysql';
    try {
        $driver = db()->getAttribute(PDO::ATTR_DRIVER_NAME);
    } catch (Throwable $e) {}

    if ($driver === 'sqlite') {
        db_exec("CREATE TABLE IF NOT EXISTS intake_batches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id BIGINT NOT NULL,
            label VARCHAR(160) NOT NULL DEFAULT '',
            sheet_name VARCHAR(190) NOT NULL DEFAULT '',
            sheet_data TEXT NULL,
            row_count INT NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        db_exec("CREATE TABLE IF NOT EXISTS intake_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            batch_id INTEGER NOT NULL,
            message_id BIGINT NOT NULL,
            media_group VARCHAR(64) NOT NULL DEFAULT '',
            file_path VARCHAR(255) NOT NULL,
            caption VARCHAR(512) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (batch_id) REFERENCES intake_batches(id) ON DELETE CASCADE
        )");
    } else {
        db_exec("CREATE TABLE IF NOT EXISTS `intake_batches` (
            `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `chat_id`    BIGINT NOT NULL,
            `label`      VARCHAR(160) NOT NULL DEFAULT '',
            `sheet_name` VARCHAR(190) NOT NULL DEFAULT '',
            `sheet_data` MEDIUMTEXT NULL,
            `row_count`  INT UNSIGNED NOT NULL DEFAULT 0,
            `status`     ENUM('pending','claimed','discarded') NOT NULL DEFAULT 'pending',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_intake_status` (`status`,`chat_id`,`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        db_exec("CREATE TABLE IF NOT EXISTS `intake_items` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `batch_id`    BIGINT UNSIGNED NOT NULL,
            `message_id`  BIGINT UNSIGNED NOT NULL,
            `media_group` VARCHAR(64) NOT NULL DEFAULT '',
            `file_path`   VARCHAR(255) NOT NULL,
            `caption`     VARCHAR(512) NOT NULL DEFAULT '',
            `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_intake_batch_message` (`batch_id`,`message_id`),
            KEY `idx_intake_batch_order` (`batch_id`,`message_id`),
            CONSTRAINT `fk_intake_batch` FOREIGN KEY (`batch_id`)
                REFERENCES `intake_batches` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $col = db_one("SHOW COLUMNS FROM `intake_items` LIKE 'media_group'");
            if (!$col) {
                db_exec("ALTER TABLE `intake_items` ADD COLUMN `media_group` VARCHAR(64) NOT NULL DEFAULT '' AFTER `message_id`");
            }
        } catch (Throwable $e) {
            // Table may not exist yet or user lacks alter rights
        }
    }
}

/* ----------------------------------------------------------- configure --- */

function intake_config(): array
{
    $rawChats = (string) (env('TG_ALLOWED_CHAT_IDS') ?? '');
    $chats = [];
    foreach (explode(',', $rawChats) as $one) {
        $one = trim($one);
        if ($one !== '' && preg_match('/^-?\d+$/', $one) === 1) {
            $chats[] = (int) $one;
        }
    }
    return [
        'token'       => trim((string) (env('TG_BOT_TOKEN') ?? '')),
        'secret'      => trim((string) (env('TG_WEBHOOK_SECRET') ?? '')),
        'webhook_url' => trim((string) (env('TG_WEBHOOK_URL') ?? '')),
        'chats'       => $chats,
    ];
}

function intake_is_configured(): bool
{
    $cfg = intake_config();
    return $cfg['token'] !== '' && $cfg['secret'] !== '' && $cfg['chats'] !== [];
}

/* --------------------------------------------------------- telegram api -- */

function tg_api(array $cfg, string $method, array $params = []): ?array
{
    if ($cfg['token'] === '') {
        return null;
    }
    $url     = 'https://api.telegram.org/bot' . $cfg['token'] . '/' . $method;
    $payload = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return null;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $raw === '') {
            error_log('tg_api (curl): ' . $method . ' failed: ' . $err);
            return null;
        }
    } else {
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content'       => $payload,
            'timeout'       => 20,
            'ignore_errors' => true,
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            error_log('tg_api: ' . $method . ' request failed');
            return null;
        }
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function tg_send_text(array $cfg, int $chatId, string $text): bool
{
    $res = tg_api($cfg, 'sendMessage', [
        'chat_id'                  => $chatId,
        'text'                     => $text,
        'disable_web_page_preview' => true,
    ]);
    return is_array($res) && !empty($res['ok']);
}

function tg_download(string $url, int $maxBytes): ?string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $buf = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($buf === false || $code !== 200 || strlen($buf) > $maxBytes) {
            error_log('tg_download (curl) failed: ' . ($err ?: "HTTP $code or size exceeded"));
            return null;
        }
        return $buf === '' ? null : $buf;
    }

    $in = @fopen($url, 'rb', false, stream_context_create(['http' => ['timeout' => 45]]));
    if ($in === false) {
        return null;
    }
    $buf = '';
    while (!feof($in)) {
        $chunk = fread($in, 65536);
        if ($chunk === false) {
            break;
        }
        $buf .= $chunk;
        if (strlen($buf) > $maxBytes) {
            fclose($in);
            return null;
        }
    }
    fclose($in);
    return $buf === '' ? null : $buf;
}

/* -------------------------------------------------------------- batches -- */

function intake_active_batch_id(int $chatId): ?int
{
    intake_tables();
    $row = db_one(
        "SELECT id FROM intake_batches WHERE chat_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1",
        [$chatId]
    );
    return $row === null ? null : (int) $row['id'];
}

function intake_active_batch(int $chatId): ?array
{
    intake_tables();
    return db_one(
        "SELECT * FROM intake_batches WHERE chat_id = ? AND status = 'pending' ORDER BY updated_at DESC, id DESC LIMIT 1",
        [$chatId]
    );
}

function intake_new_batch(int $chatId, string $label): int
{
    intake_tables();
    db_exec(
        "INSERT INTO intake_batches (chat_id, label, created_at, updated_at) VALUES (?, ?, NOW(), NOW())",
        [$chatId, mb_substr(trim($label), 0, 160)]
    );
    return (int) db_val('SELECT LAST_INSERT_ID()', [], 0);
}

function intake_batch(int $batchId): ?array
{
    intake_tables();
    return db_one('SELECT * FROM intake_batches WHERE id = ?', [$batchId]);
}

function intake_batches(?string $status = null, int $limit = 50): array
{
    intake_tables();
    $limit = max(1, min(200, $limit));
    $sql   = 'SELECT b.*, 
                COUNT(DISTINCT CASE WHEN i.media_group NOT IN ("sheet", "video") THEN i.id END) AS photo_count,
                COUNT(DISTINCT CASE WHEN i.media_group = "video" THEN i.id END) AS video_count
                FROM intake_batches b
                LEFT JOIN intake_items i ON i.batch_id = b.id';
    $params = [];
    if ($status !== null) {
        $sql .= ' WHERE b.status = ?';
        $params[] = $status;
    }
    $sql .= ' GROUP BY b.id ORDER BY b.id DESC LIMIT ' . $limit;
    return db_all($sql, $params);
}

function intake_pending_count(): int
{
    try {
        return (int) db_val("SELECT COUNT(*) FROM intake_batches WHERE status = 'pending'", [], 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function intake_items(int $batchId): array
{
    intake_tables();
    return db_all('SELECT * FROM intake_items WHERE batch_id = ? ORDER BY message_id ASC, id ASC', [$batchId]);
}

function intake_photo_count(int $batchId): int
{
    intake_tables();
    return (int) db_val(
        "SELECT COUNT(*) FROM intake_items WHERE batch_id = ? AND media_group NOT IN ('sheet', 'video')",
        [$batchId],
        0
    );
}

function intake_video_count(int $batchId): int
{
    intake_tables();
    return (int) db_val(
        "SELECT COUNT(*) FROM intake_items WHERE batch_id = ? AND media_group = 'video'",
        [$batchId],
        0
    );
}

function intake_sheet_name(int $batchId): string
{
    intake_tables();
    return (string) db_val('SELECT sheet_name FROM intake_batches WHERE id = ?', [$batchId], '');
}

function intake_set_sheet(int $batchId, string $name, string $csv, int $rows): void
{
    intake_tables();
    db_exec(
        'UPDATE intake_batches SET sheet_name = ?, sheet_data = ?, row_count = ?, updated_at = NOW() WHERE id = ?',
        [mb_substr($name, 0, 190), $csv, $rows, $batchId]
    );
}

function intake_claim(int $batchId): void
{
    intake_tables();
    db_exec("UPDATE intake_batches SET status = 'claimed', updated_at = NOW() WHERE id = ?", [$batchId]);
}

function intake_discard(int $batchId): int
{
    intake_tables();
    $removed = 0;
    $root    = dirname(__DIR__);
    foreach (intake_items($batchId) as $item) {
        $rel = (string) $item['file_path'];
        if ($rel !== '' && is_file($root . '/' . ltrim($rel, '/'))) {
            if (@unlink($root . '/' . ltrim($rel, '/'))) {
                $removed++;
            }
        }
    }
    db_exec('DELETE FROM intake_items WHERE batch_id = ?', [$batchId]);
    db_exec("UPDATE intake_batches SET status = 'discarded', updated_at = NOW() WHERE id = ?", [$batchId]);
    return $removed;
}

function intake_clear_sheet(int $batchId): int
{
    intake_tables();
    $batch = db_one('SELECT * FROM intake_batches WHERE id = ?', [$batchId]);
    if (!$batch) {
        return 0;
    }
    db_exec(
        "UPDATE intake_batches SET sheet_name = '', sheet_data = '', row_count = 0, updated_at = NOW() WHERE id = ?",
        [$batchId]
    );
    db_exec("DELETE FROM intake_items WHERE batch_id = ? AND media_group = 'sheet'", [$batchId]);
    return (int) $batch['row_count'];
}

function intake_clear_photos(int $batchId): int
{
    intake_tables();
    $removed = 0;
    $root    = dirname(__DIR__);
    foreach (intake_items($batchId) as $item) {
        $rel = (string) $item['file_path'];
        if ($rel !== '' && !in_array((string)$item['media_group'], ['sheet', 'video'], true) && is_file($root . '/' . ltrim($rel, '/'))) {
            if (@unlink($root . '/' . ltrim($rel, '/'))) {
                $removed++;
            }
        }
    }
    db_exec("DELETE FROM intake_items WHERE batch_id = ? AND media_group NOT IN ('sheet', 'video')", [$batchId]);
    return $removed;
}

function intake_clear_videos(int $batchId): int
{
    intake_tables();
    $removed = 0;
    $root    = dirname(__DIR__);
    foreach (intake_items($batchId) as $item) {
        $rel = (string) $item['file_path'];
        if ($rel !== '' && (string)$item['media_group'] === 'video' && is_file($root . '/' . ltrim($rel, '/'))) {
            if (@unlink($root . '/' . ltrim($rel, '/'))) {
                $removed++;
            }
        }
    }
    db_exec("DELETE FROM intake_items WHERE batch_id = ? AND media_group = 'video'", [$batchId]);
    return $removed;
}

function intake_sync_disk_photos(int $batchId): int
{
    intake_tables();
    $dir = dirname(__DIR__) . '/public/uploads/intake';
    if (!is_dir($dir)) {
        return 0;
    }
    $existing = db_all("SELECT file_path FROM intake_items WHERE batch_id = ?", [$batchId]);
    $known = [];
    foreach ($existing as $r) {
        $known[ltrim((string)$r['file_path'], '/')] = true;
    }

    $files = scandir($dir);
    if ($files === false) {
        return 0;
    }

    $added = 0;
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitignore') {
            continue;
        }
        $relPath = 'public/uploads/intake/' . $file;
        if (isset($known[$relPath])) {
            continue;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
            continue;
        }

        $msgId = (int) round(microtime(true) * 1000) + $added;
        try {
            db_exec(
                "INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
                 VALUES (?, ?, '', ?, '', NOW())",
                [$batchId, $msgId, $relPath]
            );
            $known[$relPath] = true;
            $added++;
        } catch (Throwable $e) {
            // ignore duplicates
        }
    }
    return $added;
}

function intake_media_url(?string $path): string
{
    $p = trim((string)$path);
    if ($p === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $p) || str_starts_with($p, 'data:')) {
        return $p;
    }
    $basePath = parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_PATH) ?: '';
    $basePath = rtrim($basePath, '/');
    return ($basePath !== '' ? $basePath : '') . '/' . ltrim($p, '/');
}

function intake_remove_item(int $batchId, int $itemId): bool
{
    intake_tables();
    $item = db_one("SELECT * FROM intake_items WHERE id = ? AND batch_id = ?", [$itemId, $batchId]);
    if (!$item) {
        return false;
    }
    $root = dirname(__DIR__);
    $rel = (string) $item['file_path'];
    if ($rel !== '' && is_file($root . '/' . ltrim($rel, '/'))) {
        @unlink($root . '/' . ltrim($rel, '/'));
    }
    db_exec("DELETE FROM intake_items WHERE id = ? AND batch_id = ?", [$itemId, $batchId]);
    return true;
}

function intake_set_cover(int $batchId, int $itemId): bool
{
    intake_tables();
    $item = db_one("SELECT * FROM intake_items WHERE id = ? AND batch_id = ?", [$itemId, $batchId]);
    if (!$item) {
        return false;
    }
    $minMsg = (int) db_val(
        "SELECT MIN(message_id) FROM intake_items WHERE batch_id = ? AND media_group NOT IN ('sheet', 'video')",
        [$batchId],
        100
    );
    $newMsgId = $minMsg > 1 ? $minMsg - 1 : 1;
    if ($newMsgId === (int)$item['message_id']) {
        db_exec(
            "UPDATE intake_items SET message_id = message_id + 10 WHERE batch_id = ? AND id != ?",
            [$batchId, $itemId]
        );
        $newMsgId = 1;
    }
    db_exec("UPDATE intake_items SET message_id = ? WHERE id = ? AND batch_id = ?", [$newMsgId, $itemId, $batchId]);
    return true;
}

function intake_split_items(int $srcBatchId, array $itemIds, string $newLabel = ''): int
{
    intake_tables();
    $src = intake_batch($srcBatchId);
    if (!$src || empty($itemIds)) {
        return 0;
    }
    $label = trim($newLabel);
    if ($label === '') {
        $label = 'Split from Batch #' . $srcBatchId;
    }
    $newBatchId = intake_new_batch((int)$src['chat_id'], $label);

    $valid = [];
    foreach ($itemIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $valid[] = $id;
        }
    }
    if (empty($valid)) {
        return 0;
    }

    $inClause = implode(',', $valid);
    db_exec("UPDATE intake_items SET batch_id = ? WHERE id IN ($inClause) AND batch_id = ?", [$newBatchId, $srcBatchId]);
    db_exec("UPDATE intake_batches SET updated_at = NOW() WHERE id IN (?, ?)", [$srcBatchId, $newBatchId]);
    return $newBatchId;
}

function intake_add_photos(int $batchId, array $files): int
{
    intake_tables();
    if (empty($files['name'])) {
        return 0;
    }
    $names = (array) $files['name'];
    $tmpNames = (array) $files['tmp_name'];
    $errors = (array) $files['error'];
    $sizes = (array) $files['size'];

    $added = 0;
    $count = count($names);
    for ($i = 0; $i < $count; $i++) {
        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = (string) ($tmpNames[$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }
        $sz = (int) ($sizes[$i] ?? 0);
        if ($sz <= 0 || $sz > INTAKE_MAX_IMAGE_BYTES) {
            continue;
        }
        $bytes = @file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            continue;
        }
        $path = intake_save_image($bytes);
        if ($path === null) {
            continue;
        }
        $caption = mb_substr(pathinfo((string)$names[$i], PATHINFO_FILENAME), 0, 512);
        $maxMsg = (int) db_val("SELECT MAX(message_id) FROM intake_items WHERE batch_id = ?", [$batchId], 1000);
        $newMsgId = max($maxMsg + 1, (int) round(microtime(true) * 1000) + $i);
        try {
            db_exec(
                "INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
                 VALUES (?, ?, 'manual', ?, ?, NOW())",
                [$batchId, $newMsgId, $path, $caption]
            );
            $added++;
        } catch (Throwable $e) {
            // ignore duplicates
        }
    }
    return $added;
}

function intake_undo_last(int $chatId): array
{
    intake_tables();
    $batch = intake_active_batch($chatId);
    if (!$batch) {
        return ['ok' => false, 'error' => 'No active batch.'];
    }
    $lastItem = db_one(
        'SELECT * FROM intake_items WHERE batch_id = ? ORDER BY message_id DESC, id DESC LIMIT 1',
        [$batch['id']]
    );
    if (!$lastItem) {
        return ['ok' => false, 'error' => 'Nothing staged to undo.'];
    }
    $root = dirname(__DIR__);
    $rel  = (string) $lastItem['file_path'];
    if ($rel !== '' && is_file($root . '/' . ltrim($rel, '/'))) {
        @unlink($root . '/' . ltrim($rel, '/'));
    }
    if ((string) $lastItem['media_group'] === 'sheet') {
        db_exec("UPDATE intake_batches SET sheet_name = '', sheet_data = '', row_count = 0, updated_at = NOW() WHERE id = ?", [$batch['id']]);
    }
    db_exec('DELETE FROM intake_items WHERE id = ?', [$lastItem['id']]);
    return ['ok' => true, 'removed' => 1];
}

function intake_message_seen(int $chatId, int $messageId): bool
{
    intake_tables();
    $n = (int) db_val(
        'SELECT COUNT(*) FROM intake_items i
           JOIN intake_batches b ON b.id = i.batch_id
          WHERE b.chat_id = ? AND i.message_id = ?',
        [$chatId, $messageId],
        0
    );
    return $n > 0;
}

/* --------------------------------------------------------------- ingest -- */

function intake_save_image(string $bytes): ?string
{
    if ($bytes === '' || strlen($bytes) > INTAKE_MAX_IMAGE_BYTES) {
        return null;
    }
    static $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'image/avif' => 'avif',
        'image/bmp'  => 'bmp',
    ];

    $mime = null;
    if (class_exists('finfo')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = (string) $finfo->buffer($bytes);
            if (isset($allowed[$detected])) {
                $mime = $detected;
            }
        } catch (Throwable $e) {
            $mime = null;
        }
    }

    if ($mime === null) {
        $sizeInfo = @getimagesizefromstring($bytes);
        if (is_array($sizeInfo) && isset($sizeInfo['mime']) && isset($allowed[$sizeInfo['mime']])) {
            $mime = (string) $sizeInfo['mime'];
        }
    }

    // Binary magic bytes fallback
    if ($mime === null) {
        $header = substr($bytes, 0, 16);
        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            $mime = 'image/jpeg';
        } elseif (str_starts_with($header, "\x89PNG\r\n\x1a\n")) {
            $mime = 'image/png';
        } elseif (str_starts_with($header, "RIFF") && strpos($header, "WEBP") !== false) {
            $mime = 'image/webp';
        } elseif (str_starts_with($header, "GIF87a") || str_starts_with($header, "GIF89a")) {
            $mime = 'image/gif';
        }
    }

    if ($mime === null || !isset($allowed[$mime])) {
        return null;
    }

    $dir = dirname(__DIR__) . '/public/uploads/intake';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        error_log("intake_save_image: Failed to create or access directory $dir");
        return null;
    }
    $name = gmdate('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (@file_put_contents($dir . '/' . $name, $bytes) === false) {
        error_log("intake_save_image: Failed to write image file $dir/$name");
        return null;
    }
    @chmod($dir . '/' . $name, 0644);
    return 'public/uploads/intake/' . $name;
}

function intake_photo_bytes(array $cfg, array $msg): ?string
{
    $sizes = $msg['photo'] ?? null;
    if (!is_array($sizes) || $sizes === []) {
        return null;
    }
    $biggest = null;
    foreach ($sizes as $one) {
        if (is_array($one) && isset($one['file_id'])) {
            $biggest = $one;
        }
    }
    if ($biggest === null || $cfg['token'] === '') {
        return null;
    }
    $info     = tg_api($cfg, 'getFile', ['file_id' => (string) $biggest['file_id']]);
    $filePath = (string) ($info['result']['file_path'] ?? '');
    if ($filePath === '') {
        return null;
    }
    return tg_download(
        'https://api.telegram.org/file/bot' . $cfg['token'] . '/' . $filePath,
        INTAKE_MAX_IMAGE_BYTES
    );
}

function intake_ingest_photo(array $cfg, int $chatId, array $msg, ?string $bytes = null): array
{
    intake_tables();

    $messageId = (int) ($msg['message_id'] ?? 0);
    if ($messageId > 0 && intake_message_seen($chatId, $messageId)) {
        return ['ok' => true, 'dup' => true, 'path' => null];
    }

    $batchId = intake_active_batch_id($chatId);
    if ($batchId === null) {
        $batchId = intake_new_batch($chatId, 'Photos from Telegram');
    }
    if (intake_photo_count($batchId) >= INTAKE_MAX_PHOTOS) {
        return ['ok' => false, 'reply' => sprintf(
            'This batch already holds %d photos. Open the admin panel to review and publish it, then use /clear to start fresh.',
            INTAKE_MAX_PHOTOS
        )];
    }

    if ($bytes === null) {
        $bytes = intake_photo_bytes($cfg, $msg);
    }
    if ($bytes === null) {
        return ['ok' => false, 'reply' => 'Could not download photo. Please try sending again.'];
    }
    $path = intake_save_image($bytes);
    if ($path === null) {
        return ['ok' => false, 'reply' => 'File is not a valid supported image (JPG, PNG, WebP up to 8 MB).'];
    }

    try {
        db_exec(
            'INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [
                $batchId,
                $messageId > 0 ? $messageId : (int) round(microtime(true) * 1000),
                mb_substr((string) ($msg['media_group_id'] ?? ''), 0, 64),
                $path,
                mb_substr(trim((string) ($msg['caption'] ?? '')), 0, 512),
            ]
        );
    } catch (PDOException $e) {
        @unlink(dirname(__DIR__) . '/' . $path);
        return ['ok' => true, 'dup' => true, 'path' => null];
    } catch (Throwable $e) {
        @unlink(dirname(__DIR__) . '/' . $path);
        error_log('intake: error saving photo item - ' . $e->getMessage());
        return ['ok' => false, 'reply' => 'Could not record photo on the server.'];
    }

    return ['ok' => true, 'path' => $path, 'batch_id' => $batchId];
}

function intake_save_video(string $bytes, string $ext = 'mp4'): ?string
{
    if ($bytes === '' || strlen($bytes) > INTAKE_MAX_VIDEO_BYTES) {
        return null;
    }
    static $allowed = [
        'video/mp4'                => 'mp4',
        'video/webm'               => 'webm',
        'video/quicktime'          => 'mov',
        'video/x-msvideo'          => 'avi',
        'video/x-matroska'         => 'mkv',
        'application/octet-stream' => 'mp4',
    ];

    $mime = null;
    if (class_exists('finfo')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = (string) $finfo->buffer($bytes);
            if (isset($allowed[$detected])) {
                $mime = $detected;
            }
        } catch (Throwable $e) {
            $mime = null;
        }
    }

    $validExts = ['mp4', 'webm', 'mov', 'avi', 'mkv'];
    $cleanExt = strtolower(trim($ext, '. '));
    if (!in_array($cleanExt, $validExts, true)) {
        $cleanExt = 'mp4';
    }

    $chosenExt = $allowed[$mime] ?? $cleanExt;

    $dir = dirname(__DIR__) . '/public/uploads/videos';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        error_log("intake_save_video: Failed to create or access directory $dir");
        return null;
    }
    $name = 'v_intake_' . gmdate('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $chosenExt;
    if (@file_put_contents($dir . '/' . $name, $bytes) === false) {
        error_log("intake_save_video: Failed to write video file $dir/$name");
        return null;
    }
    @chmod($dir . '/' . $name, 0644);
    return 'public/uploads/videos/' . $name;
}

function intake_video_bytes(array $cfg, array $msg): ?string
{
    $vid = $msg['video'] ?? $msg['document'] ?? null;
    if (!is_array($vid) || empty($vid['file_id']) || $cfg['token'] === '') {
        return null;
    }
    $fileSize = (int) ($vid['file_size'] ?? 0);
    if ($fileSize > INTAKE_MAX_VIDEO_BYTES) {
        return null;
    }
    $info = tg_api($cfg, 'getFile', ['file_id' => (string) $vid['file_id']]);
    $filePath = (string) ($info['result']['file_path'] ?? '');
    if ($filePath === '') {
        return null;
    }
    return tg_download(
        'https://api.telegram.org/file/bot' . $cfg['token'] . '/' . $filePath,
        INTAKE_MAX_VIDEO_BYTES
    );
}

function intake_ingest_video(array $cfg, int $chatId, array $msg, ?string $bytes = null): array
{
    intake_tables();

    $messageId = (int) ($msg['message_id'] ?? 0);
    if ($messageId > 0 && intake_message_seen($chatId, $messageId)) {
        return ['ok' => true, 'dup' => true, 'path' => null];
    }

    $batchId = intake_active_batch_id($chatId);
    if ($batchId === null) {
        $batchId = intake_new_batch($chatId, 'Vehicle Video from Telegram');
    }

    if ($bytes === null) {
        $bytes = intake_video_bytes($cfg, $msg);
    }
    if ($bytes === null) {
        return [
            'ok' => false,
            'reply' => 'Could not download video. Note: Telegram Bot API limit is 20 MB per video. Send a compressed clip or link.'
        ];
    }

    $ext = 'mp4';
    if (!empty($msg['document']['file_name'])) {
        $ext = strtolower(pathinfo($msg['document']['file_name'], PATHINFO_EXTENSION));
    }
    $path = intake_save_video($bytes, $ext);
    if ($path === null) {
        return ['ok' => false, 'reply' => 'File is not a valid supported video (MP4, WebM, MOV up to 25 MB).'];
    }

    try {
        db_exec(
            'INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
             VALUES (?, ?, "video", ?, ?, NOW())',
            [
                $batchId,
                $messageId > 0 ? $messageId : (int) round(microtime(true) * 1000),
                $path,
                mb_substr(trim((string) ($msg['caption'] ?? '')), 0, 512),
            ]
        );
    } catch (Throwable $e) {
        @unlink(dirname(__DIR__) . '/' . $path);
        error_log('intake: error saving video item - ' . $e->getMessage());
        return ['ok' => false, 'reply' => 'Could not record video on the server.'];
    }

    // If active batch has a staged vehicle listing CSV, link this video to it
    $batch = intake_batch($batchId);
    if ($batch && !empty($batch['sheet_data'])) {
        $csv = (string) $batch['sheet_data'];
        $parsed = bulk_parse_csv_string($csv);
        if (!empty($parsed['rows'])) {
            if (empty($parsed['rows'][0]['video_url'])) {
                $parsed['rows'][0]['video_url'] = $path;
                $stream = fopen('php://memory', 'r+');
                fputcsv($stream, $parsed['headers']);
                foreach ($parsed['rows'] as $r) {
                    $rowVals = [];
                    foreach ($parsed['headers'] as $h) {
                        $rowVals[] = $r[$h] ?? '';
                    }
                    fputcsv($stream, $rowVals);
                }
                rewind($stream);
                $newCsv = stream_get_contents($stream);
                fclose($stream);
                intake_set_sheet($batchId, $batch['sheet_name'], $newCsv, count($parsed['rows']));
            }
        }
    }

    return [
        'ok' => true,
        'path' => $path,
        'batch_id' => $batchId,
        'video_count' => intake_video_count($batchId),
        'photo_count' => intake_photo_count($batchId),
    ];
}

function intake_ingest_listing_text(array $cfg, int $chatId, string $text, int $messageId = 0): array
{
    require_once __DIR__ . '/intake_parser.php';
    intake_tables();

    $chunks = intake_split_multiple_listings($text);
    $parsedList = [];
    foreach ($chunks as $chunk) {
        $p = intake_parse_listing_text($chunk);
        if (!empty($p) && !empty($p['name'])) {
            $parsedList[] = $p;
        }
    }

    if (empty($parsedList)) {
        return ['ok' => false, 'error' => 'Could not parse listing details.'];
    }

    $activeBatch = intake_active_batch($chatId);
    $batchId = null;

    if ($activeBatch !== null) {
        $hasListing = (!empty($activeBatch['sheet_data']) && (int)$activeBatch['row_count'] > 0);
        $hasMedia   = (intake_photo_count((int)$activeBatch['id']) > 0 || intake_video_count((int)$activeBatch['id']) > 0);
        $firstName  = $parsedList[0]['name'] ?? '';
        $isDifferent = (mb_strtolower(trim((string)$activeBatch['label'])) !== mb_strtolower(trim($firstName)));

        // If active batch already has a vehicle listing AND already has media or is a distinct vehicle:
        // Do NOT overwrite it! Automatically start a fresh batch for this new vehicle!
        if ($hasListing && ($hasMedia || $isDifferent)) {
            $batchLabel = count($parsedList) > 1
                ? sprintf('%s (+%d more)', $parsedList[0]['name'], count($parsedList) - 1)
                : $parsedList[0]['name'];
            $batchId = intake_new_batch($chatId, $batchLabel);
        } else {
            $batchId = (int)$activeBatch['id'];
            $batchLabel = count($parsedList) > 1
                ? sprintf('%s (+%d more)', $parsedList[0]['name'], count($parsedList) - 1)
                : $parsedList[0]['name'];
            db_exec('UPDATE intake_batches SET label = ?, updated_at = NOW() WHERE id = ?', [mb_substr($batchLabel, 0, 160), $batchId]);
        }
    } else {
        $batchLabel = count($parsedList) > 1
            ? sprintf('%s (+%d more)', $parsedList[0]['name'], count($parsedList) - 1)
            : $parsedList[0]['name'];
        $batchId = intake_new_batch($chatId, $batchLabel);
    }

    // Check if there are already staged videos in this batch
    $existingVideos = db_all(
        "SELECT file_path FROM intake_items WHERE batch_id = ? AND media_group = 'video' ORDER BY id ASC",
        [$batchId]
    );
    $videoUrl = !empty($existingVideos[0]['file_path']) ? (string)$existingVideos[0]['file_path'] : '';

    $csv = intake_listings_to_csv($parsedList, $videoUrl);
    $sheetName = count($parsedList) === 1
        ? 'Vehicle: ' . ($parsedList[0]['brand'] ? $parsedList[0]['brand'] . ' ' . $parsedList[0]['model'] : $parsedList[0]['name'])
        : sprintf('Vehicles Drop (%d vehicles)', count($parsedList));
    intake_set_sheet($batchId, $sheetName, $csv, count($parsedList));

    // Marker item in intake_items for chronological undo
    db_exec(
        "INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
         VALUES (?, ?, 'sheet', '', ?, NOW())",
        [$batchId, $messageId > 0 ? $messageId : (int)round(microtime(true) * 1000), mb_substr($parsedList[0]['name'], 0, 512)]
    );

    return [
        'ok'          => true,
        'batch_id'    => $batchId,
        'parsed'      => $parsedList[0],
        'parsed_list' => $parsedList,
        'photo_count' => intake_photo_count($batchId),
        'video_count' => intake_video_count($batchId)
    ];
}

function intake_normalize_csv(string $bytes): string
{
    if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
        $bytes = substr($bytes, 3);
    }
    if (!mb_check_encoding($bytes, 'UTF-8')) {
        $bytes = mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
    }
    return $bytes;
}

/** Robust CSV string parser with automatic delimiter detection and header normalization. */
function bulk_parse_csv_string(string $content): array
{
    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $content = substr($content, 3);
    }
    $content = trim($content);
    if ($content === '') {
        return ['headers' => [], 'rows' => [], 'error' => 'The uploaded CSV file is empty.'];
    }

    $firstLine = strtok($content, "\r\n") ?: '';
    $delimiters = [',', ';', "\t", '|'];
    $chosenDelimiter = ',';
    $maxCount = 0;
    foreach ($delimiters as $delim) {
        $count = substr_count($firstLine, $delim);
        if ($count > $maxCount) {
            $maxCount = $count;
            $chosenDelimiter = $delim;
        }
    }

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    $rawHeader = fgetcsv($stream, 0, $chosenDelimiter);
    if ($rawHeader === false || $rawHeader === [null] || $rawHeader === []) {
        fclose($stream);
        return ['headers' => [], 'rows' => [], 'error' => 'Could not read header row from CSV.'];
    }

    $normalizedHeaders = [];
    foreach ($rawHeader as $idx => $h) {
        $clean = strtolower(trim((string) $h));
        $clean = preg_replace('/[\s\-_]+/', '_', $clean) ?? $clean;
        $clean = trim($clean, '_');
        
        $clean = match ($clean) {
            'product_name', 'item_name', 'title' => 'name',
            'product_title', 'product' => 'name',
            'dept', 'division' => 'category',
            'cat', 'category_name' => 'category',
            'sub_category', 'subcategory' => 'sub_category',
            'brand_name', 'manufacturer' => 'brand',
            'sale_price', 'regular_price', 'unit_price', 'cost' => 'price',
            'compare_price', 'msrp', 'original_price' => 'compare_at_price',
            'qty', 'quantity', 'inventory', 'stock_quantity' => 'stock',
            'item_code', 'code', 'product_code' => 'sku',
            'image', 'thumbnail', 'photo', 'main_image' => 'image_url',
            'images', 'gallery', 'additional_images' => 'gallery_images',
            'desc', 'full_desc', 'full_description', 'product_desc', 'details', 'notes' => 'description',
            default => $clean,
        };
        $normalizedHeaders[$idx] = $clean;
    }

    $rows = [];
    $lineNum = 1;
    while (($row = fgetcsv($stream, 0, $chosenDelimiter)) !== false) {
        $lineNum++;
        if ($row === [null] || empty(array_filter($row, static fn($v) => trim((string) $v) !== ''))) {
            continue;
        }

        $assoc = [];
        foreach ($normalizedHeaders as $idx => $colName) {
            if ($colName === '') {
                continue;
            }
            $assoc[$colName] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
        }
        $assoc['_row_num'] = (string) $lineNum;
        $rows[] = $assoc;
    }
    fclose($stream);

    return [
        'headers' => array_values(array_filter($normalizedHeaders)),
        'rows'    => $rows,
    ];
}

function intake_ingest_sheet(array $cfg, int $chatId, array $doc, ?string $bytes = null): array
{
    intake_tables();

    $name = mb_substr(trim((string) ($doc['file_name'] ?? 'sheet')), 0, 190) ?: 'sheet';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'xlsx'], true)) {
        return ['ok' => false, 'error' => 'Send catalog as .csv or .xlsx (Excel / Google Sheets).'];
    }
    if ((int) ($doc['file_size'] ?? 0) > INTAKE_MAX_SHEET_BYTES) {
        return ['ok' => false, 'error' => 'File exceeds 2 MB limit for product spreadsheets.'];
    }

    if ($bytes === null) {
        if ($cfg['token'] === '') {
            return ['ok' => false, 'error' => 'Bot token not configured.'];
        }
        $info     = tg_api($cfg, 'getFile', ['file_id' => (string) ($doc['file_id'] ?? '')]);
        $filePath = (string) ($info['result']['file_path'] ?? '');
        if ($filePath === '') {
            return ['ok' => false, 'error' => 'Could not download file. Please re-send.'];
        }
        $bytes = tg_download(
            'https://api.telegram.org/file/bot' . $cfg['token'] . '/' . $filePath,
            INTAKE_MAX_SHEET_BYTES
        );
        if ($bytes === null) {
            return ['ok' => false, 'error' => 'Could not download file data.'];
        }
    }

    if ($ext === 'csv') {
        $csv = intake_normalize_csv($bytes);
    } else {
        $tmp = tempnam(sys_get_temp_dir(), 'tgx');
        if ($tmp === false || file_put_contents($tmp, $bytes) === false) {
            return ['ok' => false, 'error' => 'Could not read .xlsx spreadsheet.'];
        }
        try {
            $csv = intake_xlsx_to_csv($tmp);
        } catch (Throwable $e) {
            @unlink($tmp);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        @unlink($tmp);
    }

    $parsed = bulk_parse_csv_string($csv);
    if (isset($parsed['error']) || $parsed['rows'] === []) {
        return ['ok' => false, 'error' => $parsed['error']
            ?? 'No product rows found. First line must contain header columns (e.g. name, price, sku).'];
    }

    $batchId = intake_active_batch_id($chatId);
    if ($batchId === null || intake_sheet_name($batchId) !== '') {
        $batchId = intake_new_batch($chatId, $name);
    }
    intake_set_sheet($batchId, $name, $csv, count($parsed['rows']));

    // Sheet marker item for chronological undo
    db_exec(
        "INSERT INTO intake_items (batch_id, message_id, media_group, file_path, caption, created_at)
         VALUES (?, ?, 'sheet', '', ?, NOW())",
        [$batchId, (int) ($doc['message_id'] ?? 0), $name]
    );

    return [
        'ok'             => true,
        'rows'           => count($parsed['rows']),
        'name'           => $name,
        'batch_id'       => $batchId,
        'photos_pending' => intake_photo_count($batchId),
    ];
}

/* ------------------------------------------------------------ dispatch --- */

function intake_dispatch(array $cfg, array $update, array $localFiles = []): string
{
    $msg = $update['message'] ?? null;
    if (!is_array($msg)) {
        return '';
    }

    $chatId = (int) ($msg['chat']['id'] ?? 0);
    if ($chatId === 0) {
        return '';
    }

    // Authorization check
    if ($cfg['chats'] !== [] && !in_array($chatId, $cfg['chats'], true)) {
        error_log(sprintf('intake: unauthorized chat_id %d dropped', $chatId));
        return '';
    }

    $text = trim((string) ($msg['text'] ?? ''));

    // 1. Chat commands
    if ($text !== '' && $text[0] === '/') {
        return intake_command_reply($cfg, $chatId, $text);
    }

    // 2. Structured Vehicle or Product Listing Post (Plain text)
    if ($text !== '' && intake_is_listing_text($text)) {
        $res = intake_ingest_listing_text($cfg, $chatId, $text, (int) ($msg['message_id'] ?? 0));
        if (!$res['ok']) {
            return (string) ($res['error'] ?? 'Could not parse listing details.');
        }
        $p = $res['parsed'];
        $priceFmt = $p['price'] > 0
            ? sprintf('%s %s%s', $p['currency'], ($p['currency'] === 'USD' ? '$' : '₵'), number_format($p['price']))
            : 'Price on request';
        if ($p['incoterms']) {
            $priceFmt .= " ({$p['incoterms']})";
        }

        $specsSummary = [];
        if (!empty($p['specs']['Engine'])) $specsSummary[] = $p['specs']['Engine'];
        if (!empty($p['specs']['Transmission'])) $specsSummary[] = $p['specs']['Transmission'];
        if (!empty($p['specs']['Mileage'])) $specsSummary[] = $p['specs']['Mileage'];
        if (!empty($p['specs']['Fuel Type'])) $specsSummary[] = $p['specs']['Fuel Type'];
        if (!empty($p['specs']['Frame Number'])) $specsSummary[] = 'Frame: ' . $p['specs']['Frame Number'];
        $specsLine = !empty($specsSummary) ? implode(' | ', $specsSummary) : ($p['year'] ? 'Year: ' . $p['year'] : '');

        $adminUrl = defined('APP_URL') ? APP_URL . '/admin/intake.php?review=' . $res['batch_id'] : 'Shop Admin';

        if (count($res['parsed_list'] ?? []) > 1) {
            $lines = [
                "🚗 Staged " . count($res['parsed_list']) . " Vehicle Listings in Batch #{$res['batch_id']}!",
                "━━━━━━━━━━━━━━━━━━━━"
            ];
            foreach ($res['parsed_list'] as $idx => $pItem) {
                $pFmt = $pItem['price'] > 0 ? '$' . number_format($pItem['price']) . ' FOB' : 'Price on request';
                $lines[] = sprintf("%d. %s (%s)", $idx + 1, $pItem['name'], $pFmt);
            }
            $lines[] = sprintf("\n📸 %d photo(s) & 🎥 %d video(s) currently staged in Batch #%d.", $res['photo_count'], $res['video_count'], $res['batch_id']);
            $lines[] = "👉 Send vehicle photos & videos now! Captions will auto-match to each vehicle.";
            $lines[] = "\n🔗 Review & Publish in Admin:\n" . $adminUrl;
            return implode("\n", $lines);
        }

        return "🚗 Staged Vehicle Listing!\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "📋 " . $p['name'] . "\n"
            . "🏷️ Brand: " . ($p['brand'] ?: 'Generic') . " | Model: " . ($p['model'] ?: 'Standard') . ($p['year'] ? " ({$p['year']})" : '') . "\n"
            . "💰 Price: " . $priceFmt . "\n"
            . ($specsLine ? "⚙️ Specs: " . $specsLine . "\n" : "")
            . "📦 Origin: China Export (Pre-Order)\n\n"
            . sprintf("📸 %d photo(s) & 🎥 %d video(s) currently in Batch #%d.\n", $res['photo_count'], $res['video_count'], $res['batch_id'])
            . "👉 Send vehicle photos & 1-2 videos now! They will auto-link to this vehicle.\n\n"
            . "🔗 Review & Publish in Admin:\n" . $adminUrl;
    }

    // 3. Document attachments (Excel spreadsheet, or uncompressed photos/videos sent as files)
    if (isset($msg['document'])) {
        $doc = $msg['document'];
        $fileName = (string)($doc['file_name'] ?? '');
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mime = strtolower((string)($doc['mime_type'] ?? ''));

        // Check if image document
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'], true) || str_starts_with($mime, 'image/')) {
            $local = $localFiles[(string) ($doc['file_id'] ?? '')] ?? null;
            // Map document structure to photo structure
            $pseudoMsg = $msg;
            $pseudoMsg['photo'] = [$doc];
            $res = intake_ingest_photo($cfg, $chatId, $pseudoMsg, $local);
            if (!$res['ok']) {
                return (string) ($res['reply'] ?? 'Photo could not be saved.');
            }
            return '';
        }

        // Check if video document
        if (in_array($ext, ['mp4', 'mov', 'webm', 'avi', 'mkv'], true) || str_starts_with($mime, 'video/')) {
            $local = $localFiles[(string) ($doc['file_id'] ?? '')] ?? null;
            $res = intake_ingest_video($cfg, $chatId, $msg, $local);
            if (!$res['ok']) {
                return (string) ($res['reply'] ?? 'Video could not be saved.');
            }
            return sprintf("🎥 Video walkthrough attached! (%d video(s), %d photo(s) in batch)", $res['video_count'], $res['photo_count']);
        }

        // Standard spreadsheet (.csv or .xlsx)
        $local = $localFiles[(string) ($doc['file_id'] ?? '')] ?? null;
        $res   = intake_ingest_sheet($cfg, $chatId, $doc, $local);
        if (!$res['ok']) {
            return (string) ($res['error'] ?? 'Spreadsheet could not be processed.');
        }
        $tail = $res['photos_pending'] > 0
            ? sprintf(' %d photo(s) are already staged in this batch.', $res['photos_pending'])
            : ' Now upload product photos — caption each with product name or SKU.';
        return sprintf("Spreadsheet attached: %d rows from %s.%s", $res['rows'], $res['name'], $tail);
    }

    // 4. Video attachments
    if (isset($msg['video'])) {
        $caption = trim((string)($msg['caption'] ?? ''));
        $listingParsed = null;
        if ($caption !== '' && intake_is_listing_text($caption)) {
            $resListing = intake_ingest_listing_text($cfg, $chatId, $caption, (int)($msg['message_id'] ?? 0));
            if ($resListing['ok']) {
                $listingParsed = $resListing['parsed'];
            }
        }

        $local = $localFiles[(string) ($msg['video']['file_id'] ?? '')] ?? null;
        $res = intake_ingest_video($cfg, $chatId, $msg, $local);
        if (!$res['ok']) {
            return (string) ($res['reply'] ?? 'Video could not be saved.');
        }

        if ($listingParsed) {
            $adminUrl = defined('APP_URL') ? APP_URL . '/admin/intake.php?review=' . $res['batch_id'] : 'Shop Admin';
            return sprintf(
                "🚗 Vehicle Listing & Video Staged!\n📋 %s\n🎥 1 video attached (%d photos staged).\n\nReview & Publish in Admin:\n%s",
                $listingParsed['name'],
                $res['photo_count'],
                $adminUrl
            );
        }

        return sprintf("🎥 Video walkthrough attached! (%d video(s), %d photo(s) in batch)", $res['video_count'], $res['photo_count']);
    }

    // 5. Photo attachments (Singles or Albums)
    if (isset($msg['photo'])) {
        $caption = trim((string)($msg['caption'] ?? ''));
        $listingParsed = null;
        if ($caption !== '' && intake_is_listing_text($caption)) {
            $resListing = intake_ingest_listing_text($cfg, $chatId, $caption, (int)($msg['message_id'] ?? 0));
            if ($resListing['ok']) {
                $listingParsed = $resListing['parsed'];
            }
        }

        $sizes = is_array($msg['photo']) ? $msg['photo'] : [];
        $last  = null;
        foreach ($sizes as $one) {
            if (is_array($one)) {
                $last = $one;
            }
        }
        $local = $localFiles[(string) ($last['file_id'] ?? '')] ?? null;
        $res   = intake_ingest_photo($cfg, $chatId, $msg, $local);
        if (!$res['ok']) {
            return (string) ($res['reply'] ?? 'Photo could not be saved.');
        }

        if ($listingParsed) {
            $adminUrl = defined('APP_URL') ? APP_URL . '/admin/intake.php?review=' . $res['batch_id'] : 'Shop Admin';
            return sprintf(
                "🚗 Vehicle Listing & Photo Staged!\n📋 %s\n📸 Staged in Batch #%d.\nSend remaining photos and 1-2 videos!\n\nReview & Publish in Admin:\n%s",
                $listingParsed['name'],
                $res['batch_id'],
                $adminUrl
            );
        }

        return ''; // Silent on photo uploads to avoid flood on bulk forwards
    }

    return "Avazonia Product & Vehicle Intake Bot:\n\n"
        . "1. Forward a vehicle drop with title, specs, and description\n"
        . "2. Send bulk product photos\n"
        . "3. Send 1-2 video walkthroughs\n"
        . "4. Or send a .csv / .xlsx catalog spreadsheet\n\n"
        . "Commands: /status  /undo  /clearsheet  /clearphotos  /clearvideos  /clear  /help";
}

function intake_command_reply(array $cfg, int $chatId, string $text): string
{
    $parts = preg_split('/\s+/', trim($text)) ?: [''];
    $cmd   = strtolower(explode('@', (string) ($parts[0] ?? ''), 2)[0]);

    switch ($cmd) {
        case '/start':
        case '/help':
            return "Avazonia Telegram Intake Bot\n\n"
                . "Multi-Product & Vehicle Drops:\n"
                . "1. Forward vehicle listing text (with Brand, Specs, Features, Price)\n"
                . "2. Send bulk photos & 1-2 video walkthroughs\n"
                . "3. Forward next vehicle or type /new to start next product!\n"
                . "4. Log in to Avazonia Admin -> Telegram Intake to review & 1-click publish live!\n\n"
                . "Available commands:\n"
                . "/status      - View currently active batch\n"
                . "/new         - Finish current vehicle & start fresh batch\n"
                . "/batches     - List all pending batches\n"
                . "/batch <id>  - Switch active batch (e.g. /batch 3)\n"
                . "/undo        - Remove the last message you sent\n"
                . "/clearsheet  - Remove spreadsheet / vehicle specs, keep photos\n"
                . "/clearphotos - Remove staged photos\n"
                . "/clearvideos - Remove staged videos\n"
                . "/clear       - Discard entire active batch";

        case '/new':
        case '/next':
        case '/done':
            $current = intake_active_batch($chatId);
            $summary = "";
            if ($current) {
                $pCount = intake_photo_count((int)$current['id']);
                $vCount = intake_video_count((int)$current['id']);
                $summary = sprintf("✅ Batch #%d (%s) saved with %d photos & %d videos!\n\n", (int)$current['id'], $current['label'], $pCount, $vCount);
            }
            $newId = intake_new_batch($chatId, 'New Vehicle Staging');
            $adminUrl = defined('APP_URL') ? APP_URL . '/admin/intake.php?review=' . $newId : 'Admin';
            return $summary . "🆕 Fresh Batch #{$newId} is now active!\n"
                . "👉 Forward the listing details, photos, and video for your next vehicle.\n\n"
                . "Review in Admin:\n" . $adminUrl;

        case '/batches':
        case '/list':
            $pendingBatches = db_all("SELECT * FROM intake_batches WHERE chat_id = ? AND status = 'pending' ORDER BY updated_at DESC, id DESC LIMIT 10", [$chatId]);
            if (empty($pendingBatches)) {
                return "No pending batches right now. Forward a vehicle drop or type /new to start staging!";
            }
            $lines = ["📋 Pending Staged Batches:"];
            foreach ($pendingBatches as $b) {
                $p = intake_photo_count((int)$b['id']);
                $v = intake_video_count((int)$b['id']);
                $lines[] = sprintf("• Batch #%d: %s (%d photos, %d videos)", (int)$b['id'], $b['label'] ?: 'Untitled', $p, $v);
            }
            $lines[] = "\nTo switch active batch, type: /batch <id> (e.g. /batch 3)";
            return implode("\n", $lines);

        case '/batch':
        case '/switch':
            $targetId = (int)($parts[1] ?? 0);
            if ($targetId <= 0) {
                return "Please specify a batch ID. Example: /batch 3";
            }
            $target = db_one("SELECT * FROM intake_batches WHERE id = ? AND chat_id = ?", [$targetId, $chatId]);
            if (!$target) {
                return "Batch #{$targetId} not found.";
            }
            db_exec("UPDATE intake_batches SET updated_at = NOW() WHERE id = ?", [$targetId]);
            $p = intake_photo_count($targetId);
            $v = intake_video_count($targetId);
            return sprintf("🔄 Switched active batch to #%d (%s)!\nPhotos: %d | Videos: %d\nSubsequent uploads will attach here.", $targetId, $target['label'], $p, $v);

        case '/status':
            $batch = intake_active_batch($chatId);
            if ($batch === null) {
                return 'No pending batch right now. Send a vehicle post, product sheet, or photos to start staging!';
            }
            $sheet = $batch['sheet_name'] !== ''
                ? sprintf('%s (%d item)', $batch['sheet_name'], (int) $batch['row_count'])
                : 'None yet';
            $photos = intake_photo_count((int) $batch['id']);
            $videos = intake_video_count((int) $batch['id']);
            $adminUrl = defined('APP_URL') ? APP_URL . '/admin/intake.php?review=' . $batch['id'] : 'Shop Admin';
            return sprintf(
                "Staged Batch #%d\nListing: %s\nPhotos: %d staged\nVideos: %d staged\n\nReview & Publish in Admin:\n%s",
                (int) $batch['id'],
                $sheet,
                $photos,
                $videos,
                $adminUrl
            );

        case '/clearsheet':
            $batch = intake_active_batch($chatId);
            if ($batch === null) {
                return 'No active batch.';
            }
            $rows = intake_clear_sheet((int) $batch['id']);
            return sprintf('Listing details cleared (%d row(s)). %d photos and %d videos kept.', $rows, intake_photo_count((int) $batch['id']), intake_video_count((int) $batch['id']));

        case '/clearphotos':
            $batch = intake_active_batch($chatId);
            if ($batch === null) {
                return 'No active batch.';
            }
            $removed = intake_clear_photos((int) $batch['id']);
            $sheet = $batch['sheet_name'] !== '' ? ' (listing intact)' : '';
            return sprintf('Cleared %d photo(s)%s.', $removed, $sheet);

        case '/clearvideos':
            $batch = intake_active_batch($chatId);
            if ($batch === null) {
                return 'No active batch.';
            }
            $removed = intake_clear_videos((int) $batch['id']);
            return sprintf('Cleared %d video(s).', $removed);

        case '/undo':
            $res = intake_undo_last($chatId);
            if (!$res['ok']) {
                return (string)$res['error'];
            }
            $batch = intake_active_batch($chatId);
            $sheet = $batch && $batch['sheet_name'] !== '' ? ' (listing intact)' : '';
            $left  = $batch ? intake_photo_count((int) $batch['id']) : 0;
            $vLeft = $batch ? intake_video_count((int) $batch['id']) : 0;
            return sprintf('Undone 1 item. Remaining: %d photo(s), %d video(s)%s.', $left, $vLeft, $sheet);

        case '/clear':
            $batch = intake_active_batch($chatId);
            if ($batch === null) {
                return 'Nothing staged to clear.';
            }
            $photos = intake_photo_count((int) $batch['id']);
            $videos = intake_video_count((int) $batch['id']);
            intake_discard((int) $batch['id']);
            return sprintf('Batch #%d discarded (%d photos & %d videos cleaned up).', (int) $batch['id'], $photos, $videos);

        default:
            return "Unknown command. Try /status, /clear, or /help.";
    }
}
