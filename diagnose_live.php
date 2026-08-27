<?php
/**
 * DIAGNOSTIC v3 — Must be visited WHILE LOGGED IN.
 * Captures the exact error in the seller dashboard flow.
 * DELETE AFTER USE.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Capture fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h3 style='color:red'>FATAL ERROR</h3>";
        echo "<pre>" . $error['message'] . "\nFile: {$error['file']}\nLine: {$error['line']}</pre>";
    }
});

// Start session the same way index.php does
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Session.php';
Session::start();

echo "<h2>Seller Dashboard Diagnostic v3</h2>";

// Session info
$userId = Session::get('user_id');
echo "<h3>Session</h3>";
echo "user_id: " . ($userId ?? 'NOT SET') . "<br>";
echo "user_role: " . (Session::get('user_role') ?? 'NOT SET') . "<br>";

if (!$userId) {
    echo "<h3 style='color:red'>NOT LOGGED IN — <a href='" . APP_URL . "/login'>Login first</a>, then visit this page again.</h3>";
    exit;
}

// Test 1: Seller lookup
echo "<h3>1. Seller Lookup</h3>";
try {
    require_once __DIR__ . '/models/Seller.php';
    $s = new Seller();
    $seller = $s->findByUserId((int)$userId);
    if (!$seller) {
        echo "❌ No seller record for user_id=$userId<br>";
    } else {
        echo "✅ Seller: ID={$seller['id']} name={$seller['business_name']} active={$seller['is_active']} verified={$seller['is_verified']}<br>";
    }
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 2: Store lookup
echo "<h3>2. Store Lookup</h3>";
try {
    require_once __DIR__ . '/models/Store.php';
    $store = (new Store())->findBySellerId((int)$seller['id']);
    echo "✅ Store: " . ($store ? $store['name'] : 'none (null)') . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 3: Products
echo "<h3>3. Products</h3>";
try {
    require_once __DIR__ . '/models/Product.php';
    $products = (new Product())->getBySeller((int)$seller['id'], 8, 0);
    echo "✅ Products: " . count($products) . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 4: RFQs
echo "<h3>4. RFQs</h3>";
try {
    require_once __DIR__ . '/models/Rfq.php';
    $rfqs = (new Rfq())->getBySeller((int)$seller['id'], 5);
    echo "✅ RFQs: " . count($rfqs) . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 5: Orders
echo "<h3>5. Orders</h3>";
try {
    require_once __DIR__ . '/models/Order.php';
    $orders = (new Order())->getSellerOrders((int)$seller['id'], 5);
    echo "✅ Orders: " . count($orders) . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 6: Seller Earnings
echo "<h3>6. Seller Earnings</h3>";
try {
    $earnings = (new Order())->getSellerEarnings((int)$seller['id']);
    echo "✅ Gross: " . $earnings['gross_sales'] . " Pending: " . $earnings['pending_payout'] . "<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 7: Seller Orders count
echo "<h3>7. Seller Orders Count</h3>";
try {
    $count = (new Order())->countSellerOrders((int)$seller['id']);
    echo "✅ Count: $count<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 8: Product counts
echo "<h3>8. Product Counts</h3>";
try {
    $pm = new Product();
    echo "total: " . $pm->countBySeller((int)$seller['id']) . "<br>";
    echo "active: " . $pm->countActiveBySeller((int)$seller['id']) . "<br>";
    echo "pending: " . $pm->countPendingBySeller((int)$seller['id']) . "<br>";
    echo "✅ All product counts OK<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 9: Settings + commission
echo "<h3>9. Settings</h3>";
try {
    require_once __DIR__ . '/models/Settings.php';
    $settings = new Settings();
    $commissionPct = (float)$settings->get('commission_pct', 5);
    echo "✅ commission_pct: $commissionPct<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 10: Csrf
echo "<h3>10. CSRF</h3>";
try {
    $token = \Csrf::ensure();
    echo "✅ Token: " . substr($token, 0, 10) . "...<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

// Test 11: Try rendering the view
echo "<h3>11. View Render Test</h3>";
try {
    // Simulate the view data
    $stats = [
        'total_products' => 0, 'active_products' => 0, 'pending_products' => 0,
        'total_orders' => 0, 'gross_sales' => 0, 'commission' => 0,
        'net_earnings' => 0, 'pending_payout' => 0, 'commission_pct' => 5,
    ];
    $products = [];
    $rfqs = [];
    $orders = [];
    $page = 'overview';
    ob_start();
    extract(compact('seller', 'store', 'products', 'rfqs', 'orders', 'stats', 'page'));
    require_once __DIR__ . '/views/layout/head.php';
    echo "✅ head.php rendered OK<br>";
    ob_end_clean();
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ " . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}<br>";
}

echo "<hr><p>⚠️ DELETE THIS FILE AFTER USE.</p>";
