<?php
/**
 * DIAGNOSTIC v2 — Tests the exact seller dashboard code path.
 * Upload, visit, screenshot the output, then DELETE.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Avazonia Seller Dashboard Diagnostic</h2>";

// 1. PHP version
echo "<h3>PHP " . phpversion() . "</h3>";

// 2. Session + Auth
session_start();
$userId = $_SESSION['user_id'] ?? null;
echo "<h3>Session</h3>";
echo "user_id: " . ($userId ?? 'NOT SET (not logged in)') . "<br>";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";

// 3. Load app config
try {
    require_once __DIR__ . '/config/app.php';
    echo "✅ config/app.php loaded<br>";
    echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'NOT DEFINED') . "<br>";
} catch (Throwable $e) {
    echo "❌ config/app.php: " . $e->getMessage() . "<br>";
}

// 4. DB
try {
    require_once __DIR__ . '/config/database.php';
    $db = db();
    echo "<h3>Database</h3>";
    echo "Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";

    // Check sellers table
    $sellers = $db->query("SELECT id, user_id, business_name, verification_level, is_verified, is_active FROM sellers")->fetchAll();
    echo "Sellers count: " . count($sellers) . "<br>";
    foreach ($sellers as $s) {
        $active = $s['is_active'] ?? 'N/A';
        echo "  ID={$s['id']} user={$s['user_id']} name={$s['business_name']} level={$s['verification_level']} verified={$s['is_verified']} active={$active}<br>";
    }

    // Check order_items columns
    $cols = $db->query("PRAGMA table_info(order_items)")->fetchAll(PDO::FETCH_COLUMN, 1);
    echo "order_items columns: " . implode(', ', $cols) . "<br>";
    echo "seller_id in order_items: " . (in_array('seller_id', $cols) ? '✅' : '❌ MISSING') . "<br>";
    echo "store_id in order_items: " . (in_array('store_id', $cols) ? '✅' : '❌ MISSING') . "<br>";
    echo "seller_order_status in order_items: " . (in_array('seller_order_status', $cols) ? '✅' : '❌ MISSING') . "<br>";

} catch (Throwable $e) {
    echo "❌ DB: " . $e->getMessage() . "<br>";
}

// 5. Test require chain
echo "<h3>Require Chain</h3>";
$requires = [
    'core/Controller.php',
    'core/Csrf.php',
    'core/Session.php',
    'models/Seller.php',
    'models/Store.php',
    'models/Product.php',
    'models/Order.php',
    'models/Rfq.php',
    'models/Settings.php',
    'models/Category.php',
];
foreach ($requires as $f) {
    try {
        require_once __DIR__ . '/' . $f;
        echo "✅ $f<br>";
    } catch (Throwable $e) {
        echo "❌ $f: " . $e->getMessage() . "<br>";
    }
}

// 6. Test SellerController load
echo "<h3>SellerController</h3>";
try {
    require_once __DIR__ . '/controllers/SellerController.php';
    echo "✅ SellerController loaded<br>";
} catch (Throwable $e) {
    echo "❌ " . $e->getMessage() . "<br>";
}

// 7. Simulate requireSeller()
echo "<h3>requireSeller() Simulation</h3>";
if (!$userId) {
    echo "⚠️ NOT LOGGED IN — can't test seller lookup<br>";
} else {
    try {
        $s = new Seller();
        $seller = $s->findByUserId((int)$userId);
        if (!$seller) {
            echo "❌ No seller record for user_id=$userId<br>";
        } else {
            echo "✅ Seller found: {$seller['business_name']}<br>";
            echo "is_active: " . ($seller['is_active'] ?? 'N/A') . "<br>";
            echo "is_verified: {$seller['is_verified']}<br>";
            echo "verification_level: {$seller['verification_level']}<br>";
        }
    } catch (Throwable $e) {
        echo "❌ " . $e->getMessage() . "<br>";
    }
}

// 8. Simulate dashboard data loading
echo "<h3>Dashboard Data Loading</h3>";
if ($userId) {
    try {
        $s = new Seller();
        $seller = $s->findByUserId((int)$userId);
        if ($seller) {
            $sid = (int)$seller['id'];

            echo "Testing Store::findBySellerId...<br>";
            try {
                $store = (new Store())->findBySellerId($sid);
                echo "  ✅ Store: " . ($store ? $store['name'] : 'none') . "<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }

            echo "Testing Product::getBySeller...<br>";
            try {
                $products = (new Product())->getBySeller($sid, 8, 0);
                echo "  ✅ Products: " . count($products) . "<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }

            echo "Testing Rfq::getBySeller...<br>";
            try {
                $rfqs = (new Rfq())->getBySeller($sid, 5);
                echo "  ✅ RFQs: " . count($rfqs) . "<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }

            echo "Testing Order::getSellerOrders...<br>";
            try {
                $orders = (new Order())->getSellerOrders($sid, 5);
                echo "  ✅ Orders: " . count($orders) . "<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }

            echo "Testing Order::countSellerOrders...<br>";
            try {
                $count = (new Order())->countSellerOrders($sid);
                echo "  ✅ Count: $count<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }

            echo "Testing Order::getSellerEarnings...<br>";
            try {
                $earnings = (new Order())->getSellerEarnings($sid);
                echo "  ✅ Earnings: gross={$earnings['gross_sales']} pending={$earnings['pending_payout']}<br>";
            } catch (Throwable $e) {
                echo "  ❌ " . $e->getMessage() . "<br>";
            }
        }
    } catch (Throwable $e) {
        echo "❌ " . $e->getMessage() . "<br>";
    }
}

echo "<hr><p>⚠️ DELETE THIS FILE AFTER USE.</p>";
