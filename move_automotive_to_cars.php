<?php
/**
 * Avazonia — Move products from the top-level 'automotive' category into its
 * 'cars' subcategory.
 *
 * The final 26-category structure puts passenger vehicles under
 * Automotive > Cars. The seed remapped the legacy 'automobile' products onto
 * the 'automotive' umbrella; this script drops them down one level into 'cars'
 * so they show up on the Automotive > Cars page (leaf categories are the ones
 * that display products normally).
 *
 * Scope: ONLY products whose category_id is the top-level 'automotive'
 *        category. Nothing else is touched.
 *
 * Usage:
 *   Browser: https://www.avazonia.com/move_automotive_to_cars.php?secret=avazonia_final_structure_2026
 *   CLI:     php move_automotive_to_cars.php   (local SQLite dev)
 *
 * Security: requires ?secret= OR admin login. DELETE THIS FILE AFTER USE.
 */
header('Content-Type: text/plain; charset=utf-8');

$SECRET = 'avazonia_final_structure_2026';
$isCli = php_sapi_name() === 'cli';
$providedSecret = $_GET['secret'] ?? $_GET['key'] ?? '';

if (!$isCli) {
    require_once __DIR__ . '/core/Session.php';
    Session::start();
    $isAdmin = Session::get('user_role') === 'admin';
    $hasSecret = hash_equals($SECRET, $providedSecret);
    if (!$isAdmin && !$hasSecret) {
        http_response_code(403);
        echo "403 Forbidden\n";
        echo "Usage: ?secret=$SECRET (or login as admin)\n";
        exit;
    }
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = db();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "DB driver: $driver\n";
    echo "DB: " . ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'unknown') . " @ " . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost') . "\n\n";

    // 0. Resolve categories — refuse to run unless the tree looks right
    $auto = $db->query("SELECT id, name FROM categories WHERE slug = 'automotive' AND parent_id IS NULL")->fetch(PDO::FETCH_ASSOC);
    $car  = $db->query("SELECT id, name, parent_id FROM categories WHERE slug = 'cars'")->fetch(PDO::FETCH_ASSOC);
    if (!$auto) {
        echo "ERROR: top-level category 'automotive' not found — aborting, no changes made.\n";
        exit(1);
    }
    if (!$car || (int)$car['parent_id'] !== (int)$auto['id']) {
        echo "ERROR: subcategory 'cars' under '{$auto['name']}' not found — aborting, no changes made.\n";
        exit(1);
    }
    $fromId = (int)$auto['id'];
    $toId   = (int)$car['id'];
    echo "From: {$auto['name']} (top-level, id=$fromId)\n";
    echo "To:   {$car['name']} (subcategory of {$auto['name']}, id=$toId)\n\n";

    // 1. Count what would move
    $inAuto = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id = $fromId")->fetchColumn();
    $inCars = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id = $toId")->fetchColumn();
    echo "Products directly in 'automotive': $inAuto\n";
    echo "Products already in 'cars':        $inCars\n";
    if ($inAuto === 0) {
        echo "\nNothing to move — aborting, no changes made.\n";
        exit(0);
    }

    // 2. Backup affected rows (restorable)
    $db->exec("DROP TABLE IF EXISTS zz_automotive_products_backup");
    $db->exec("CREATE TABLE zz_automotive_products_backup AS SELECT id AS product_id, category_id FROM products WHERE category_id = $fromId");
    echo "\n1) Backup: $inAuto affected product rows -> zz_automotive_products_backup\n";

    // 3. Move
    $db->exec("UPDATE products SET category_id = $toId WHERE category_id = $fromId");
    echo "2) Moved: $inAuto products (automotive -> cars)\n";

    // 4. Verify
    $leftInAuto = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id = $fromId")->fetchColumn();
    $nowInCars  = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id = $toId")->fetchColumn();
    $total      = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $uncat      = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL")->fetchColumn();
    echo "\n3) Verification\n";
    echo "   Products left in 'automotive' (direct): $leftInAuto (expect 0)\n";
    echo "   Products now in 'cars':                 $nowInCars (expect " . ($inCars + $inAuto) . ")\n";
    echo "   Total products:                         $total (unchanged)\n";
    echo "   Products uncategorized:                 $uncat (unchanged)\n";

    echo "\n   Moved products:\n";
    $stmt = $db->query("SELECT id, name FROM products WHERE category_id = $toId ORDER BY id");
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "     #{$r['id']} " . mb_substr($r['name'], 0, 80) . "\n";
    }

    echo "\nDone. Verify at https://www.avazonia.com/shop?cat=automotive and ?cat=cars (hard refresh).\n";
    echo "DELETE THIS FILE AFTER USE: move_automotive_to_cars.php\n";
} catch (Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
