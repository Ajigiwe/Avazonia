<?php
// admin/purge_spam.php — purge May 10 random-name spam customers on live
// Access: https://www.avazonia.com/admin/purge_spam.php?secret=avazonia_purge_2026  (shows count)
//        &confirm=1 to actually delete (with backup)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
$secret = $_GET['secret'] ?? '';
if ($secret !== 'avazonia_purge_2026' && Session::get('user_role') !== 'admin') {
    header('Content-Type: text/plain'); http_response_code(403);
    echo "Forbidden: login as admin or ?secret=avazonia_purge_2026\n"; exit;
}
header('Content-Type: text/plain');
$db = db();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver: $driver\n";
$confirm = isset($_GET['confirm']) && $_GET['confirm']=='1';

// Criteria: May 10 2026 batch, customer, random lowercase name 8-15 chars, 0 orders, not seller, not verified
// Use REGEXP for MySQL, for SQLite use GLOB fallback
$isMysql = ($driver !== 'sqlite');
$whereRandom = $isMysql ? "full_name REGEXP '^[a-z]{8,15}$'" : "full_name GLOB '[a-z]*' AND length(full_name) BETWEEN 8 AND 15 AND instr(full_name,' ')=0";

$sqlCount = "SELECT COUNT(*) FROM users WHERE role='customer' AND is_active=1 AND created_at >= '2026-05-10 00:00:00' AND created_at < '2026-05-11 00:00:00' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)";
try {
    $count = (int)$db->query($sqlCount)->fetchColumn();
    echo "Matched spam candidates: $count\n";
} catch(Throwable $e) { echo "Count error: ".$e->getMessage()."\n"; $count=0; }

if (!$confirm) {
    echo "Dry run only. Add &confirm=1 to delete with backup.\n";
    // Show sample 10
    try {
        $sampleSql = "SELECT id, full_name, email, created_at FROM users WHERE role='customer' AND is_active=1 AND created_at >= '2026-05-10 00:00:00' AND created_at < '2026-05-11 00:00:00' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL) LIMIT 10";
        foreach($db->query($sampleSql)->fetchAll() as $r) { echo " - #{$r['id']} {$r['full_name']} {$r['email']} {$r['created_at']}\n"; }
    } catch(Throwable $e) { echo "Sample error: ".$e->getMessage()."\n"; }
    echo "To execute: ?secret=avazonia_purge_2026&confirm=1\n";
    exit;
}

// Backup
$backupDir = __DIR__ . '/../backups/spam_purge';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
$backupFile = $backupDir . '/spam_2026-05-10_' . date('Ymd_His') . '.json';
try {
    $rows = $db->query("SELECT * FROM users WHERE role='customer' AND is_active=1 AND created_at >= '2026-05-10 00:00:00' AND created_at < '2026-05-11 00:00:00' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)")->fetchAll();
    file_put_contents($backupFile, json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    echo "Backup written: $backupFile (".count($rows)." rows)\n";
} catch(Throwable $e) { echo "Backup failed: ".$e->getMessage()."\n"; }

// Also backup related cleanups (wishlist, reviews, password_resets, etc. — should be 0 but delete anyway)
$deletedUsers = 0;
try {
    $ids = $db->query("SELECT id FROM users WHERE role='customer' AND is_active=1 AND created_at >= '2026-05-10 00:00:00' AND created_at < '2026-05-11 00:00:00' AND $whereRandom AND id NOT IN (SELECT user_id FROM orders WHERE user_id IS NOT NULL) AND id NOT IN (SELECT user_id FROM sellers WHERE user_id IS NOT NULL)")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($ids)) { echo "No ids to delete.\n"; exit; }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    // Clean related tables first
    foreach (['wishlist','reviews','password_resets','system_logs'] as $tbl) {
        try { $exists = $db->query("SELECT 1 FROM $tbl LIMIT 1"); $exists->fetch(); } catch(Throwable $e) { continue; }
        try {
            if ($tbl==='password_resets') {
                // password_resets uses email, not user_id — skip unless we join
                $emails = $db->query("SELECT email FROM users WHERE id IN ($placeholders)")->fetchAll(PDO::FETCH_COLUMN);
                // prepare for email based delete
                if (!empty($emails)) {
                    $ph2 = implode(',', array_fill(0, count($emails), '?'));
                    $stmt = $db->prepare("DELETE FROM $tbl WHERE email IN ($ph2)");
                    $stmt->execute($emails);
                    echo "Cleaned $tbl: ".$stmt->rowCount()."\n";
                }
            } elseif ($tbl==='system_logs') {
                $stmt = $db->prepare("DELETE FROM $tbl WHERE user_id IN ($placeholders)");
                $stmt->execute($ids);
                echo "Cleaned $tbl: ".$stmt->rowCount()."\n";
            } else {
                $col = ($tbl==='wishlist' || $tbl==='reviews') ? 'user_id' : 'user_id';
                $stmt = $db->prepare("DELETE FROM $tbl WHERE $col IN ($placeholders)");
                $stmt->execute($ids);
                echo "Cleaned $tbl: ".$stmt->rowCount()."\n";
            }
        } catch(Throwable $e) { echo "Clean $tbl error: ".$e->getMessage()."\n"; }
    }
    // Finally delete users
    $stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $deletedUsers = $stmt->rowCount();
    echo "Deleted users: $deletedUsers\n";
} catch(Throwable $e) { echo "Delete error: ".$e->getMessage()."\n"; }

// Final counts
foreach (['users','sellers','stores','products','orders'] as $t) {
    try { $c=$db->query("SELECT COUNT(*) FROM $t")->fetchColumn(); echo "$t: $c\n"; } catch(Throwable $e) { echo "$t: ERROR ".$e->getMessage()."\n"; }
}
echo "Purge complete. Verify in /admin/users.php. Delete this file after.\n";
