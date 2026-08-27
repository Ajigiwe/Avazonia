<?php
/**
 * DIAGNOSTIC — Upload this to the site root and visit it.
 * It will show PHP version, errors, DB status, and seller data.
 * DELETE AFTER USE.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Avazonia Diagnostic</h2>";

// 1. PHP Version
echo "<h3>PHP Version</h3>";
echo "<pre>" . phpversion() . "</pre>";

// 2. Check key files
echo "<h3>Key Files</h3>";
$files = [
    'core/Csrf.php',
    'core/Router.php',
    'core/Session.php',
    'core/Controller.php',
    'models/Seller.php',
    'models/Order.php',
    'models/Store.php',
    'models/Product.php',
    'controllers/SellerController.php',
    'views/layout/head.php',
    'admin/_csrf_check.php',
];
foreach ($files as $f) {
    $exists = file_exists(__DIR__ . '/' . $f);
    echo ($exists ? '✅' : '❌') . " $f<br>";
}

// 3. DB Connection
echo "<h3>Database</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = db();
    echo "✅ Connected<br>";
    echo "Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";

    // Check sellers table
    $stmt = $db->query("DESCRIBE sellers");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Sellers columns: " . implode(', ', $cols) . "<br><br>";

    // Check if is_active exists
    echo "is_active column: " . (in_array('is_active', $cols) ? '✅ EXISTS' : '❌ MISSING') . "<br>";

    // List sellers
    $sellers = $db->query("SELECT id, user_id, business_name, verification_level, is_verified, is_active FROM sellers ORDER BY id")->fetchAll();
    echo "<h3>Sellers (" . count($sellers) . ")</h3>";
    echo "<pre>";
    foreach ($sellers as $s) {
        echo "ID={$s['id']} User={$s['user_id']} Name={$s['business_name']} Level={$s['verification_level']} Verified={$s['is_verified']} Active={$s['is_active']}\n";
    }
    echo "</pre>";

} catch (Throwable $e) {
    echo "❌ DB Error: " . $e->getMessage() . "<br>";
}

// 4. Test require chain
echo "<h3>Require Chain Test</h3>";
try {
    require_once __DIR__ . '/core/Csrf.php';
    echo "✅ core/Csrf.php loaded<br>";
    echo "Csrf class exists: " . (class_exists('Csrf') ? '✅' : '❌') . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . "<br>";
}

try {
    require_once __DIR__ . '/models/Seller.php';
    $s = new Seller();
    echo "✅ Seller model loaded<br>";
    echo "findActiveByUserId exists: " . (method_exists($s, 'findActiveByUserId') ? '✅' : '❌') . "<br>";
    echo "findByUserId exists: " . (method_exists($s, 'findByUserId') ? '✅' : '❌') . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . "<br>";
}

echo "<hr><p>⚠️ DELETE THIS FILE AFTER USE.</p>";
