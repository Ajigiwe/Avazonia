<?php
// admin/migrate_marketplace.php — one-time marketplace migration for live MySQL
// Access: https://www.avazonia.com/admin/migrate_marketplace.php (must be logged in as admin)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
$secret = $_GET['secret'] ?? '';
if ($secret !== 'avazonia_migrate_2026' && Session::get('user_role') !== 'admin') {
    header('Content-Type: text/plain');
    http_response_code(403);
    echo "Forbidden: login as admin at /login or use ?secret=avazonia_migrate_2026\n";
    exit;
}
header('Content-Type: text/plain');
$db = db();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Driver: $driver\n";
echo "Starting marketplace migration 010_marketplace.sql\n";

function run_sql_file_live(PDO $db, string $path): void {
    if (!file_exists($path)) { echo "SKIP $path\n"; return; }
    $sql = file_get_contents($path);
    try { $db->exec($sql); echo "OK $path (bulk)\n"; return; } catch (PDOException $e) { echo "Bulk failed: ".$e->getMessage()."\nTrying statement-by-statement...\n"; }
    $stmts = array_filter(array_map('trim', explode(';', $sql)));
    $ok=0; $fail=0;
    foreach ($stmts as $stmt) {
        if ($stmt==='' || str_starts_with($stmt,'--') || str_starts_with($stmt,'/*') || str_starts_with($stmt,'/*!')) continue;
        if (preg_match('/^(SET|LOCK|UNLOCK)/i', $stmt)) { try { $db->exec($stmt); $ok++; } catch(Throwable $e) { } continue; }
        try { $db->exec($stmt); $ok++; } catch (PDOException $e2) {
            $msg=$e2->getMessage();
            // Ignore duplicate column / duplicate key / already exists
            if (stripos($msg,'Duplicate column')!==false || stripos($msg,'already exists')!==false || stripos($msg,'Duplicate entry')!==false || stripos($msg,'Duplicate key')!==false) { $ok++; continue; }
            $fail++; if ($fail<5) echo " fail: ".substr($msg,0,180)."\n";
        }
    }
    echo "Done: $ok ok, $fail failed (ignored if duplicate)\n";
}

run_sql_file_live($db, __DIR__ . '/../migrations/010_marketplace.sql');

// Backfill Avazonia Official seller/store for existing products
try {
    $hasSeller = $db->query("SELECT id FROM sellers LIMIT 1")->fetch();
    if (!$hasSeller) {
        $adminId = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        if ($adminId) {
            $db->exec("INSERT IGNORE INTO sellers (user_id, seller_type, business_name, slug, country_code, verification_level, is_verified) VALUES ($adminId, 'business_retailer', 'Avazonia Official', 'avazonia-official', 'GH', 'avazonia_verified', 1)");
            $sid = $db->query("SELECT id FROM sellers WHERE user_id=$adminId LIMIT 1")->fetchColumn();
            if ($sid) {
                $db->exec("INSERT IGNORE INTO stores (seller_id, slug, name, country_code, is_featured) VALUES ($sid, 'avazonia-official', 'Avazonia Official', 'GH', 1)");
                $storeId = $db->query("SELECT id FROM stores WHERE seller_id=$sid LIMIT 1")->fetchColumn();
                if ($storeId) {
                    $updated = $db->exec("UPDATE products SET seller_id=$sid, store_id=$storeId WHERE seller_id IS NULL");
                    echo "Backfill: seller $sid store $storeId updated $updated products\n";
                }
            }
        }
    } else {
        echo "Sellers already exist, skipping backfill\n";
        // Ensure existing products without seller get assigned
        $adminId = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
        $sid = $db->query("SELECT id FROM sellers WHERE user_id=$adminId LIMIT 1")->fetchColumn();
        $storeId = $db->query("SELECT id FROM stores WHERE seller_id=$sid LIMIT 1")->fetchColumn();
        $updated = $db->exec("UPDATE products SET seller_id=$sid, store_id=$storeId WHERE seller_id IS NULL");
        echo "Backfill remaining products: $updated\n";
    }
} catch(Throwable $e) { echo "Backfill error: ".$e->getMessage()."\n"; }

// Show counts
foreach (['users','sellers','stores','products','categories','rfqs'] as $t) {
    try { $c=$db->query("SELECT COUNT(*) FROM $t")->fetchColumn(); echo "$t: $c\n"; } catch(Throwable $e) { echo "$t: ERROR ".$e->getMessage()."\n"; }
}
echo "Marketplace migration complete. Delete this file.\n";
