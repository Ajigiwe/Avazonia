<?php
// bin/setup.php — idempotent local setup: creates tables, seeds, checks health.
// Usage: php bin/setup.php         (from project root)
//        php bin/setup.php --fresh (drop & recreate — destructive)
// Supports MySQL/MariaDB and SQLite fallback (storage/database.sqlite).

$action = $argv[1] ?? '';
$fresh = ($action === '--fresh' || $action === '--reset');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function run_sql_file(PDO $db, string $path): void {
    if (!file_exists($path)) {
        echo "  SKIP  $path (not found)\n";
        return;
    }
    $sql = file_get_contents($path);
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    // SQLite: convert MySQL INSERT IGNORE and strip MySQL directives
    if ($driver === 'sqlite') {
        $sql = str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $sql);
        // Remove MySQL ENGINE/CHARSET clauses that SQLite exec may choke on if accidentally used
        // 01-schema.sqlite.sql is already clean; this is for reusing MySQL files where possible
    }
    try {
        $db->exec($sql);
        echo "  OK    $path\n";
    } catch (PDOException $e) {
        echo "  WARN  $path failed bulk exec: " . $e->getMessage() . "\n";
        echo "        Trying statement-by-statement...\n";
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        $ok = 0; $fail = 0;
        foreach ($stmts as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--') || str_starts_with($stmt, '/*') || str_starts_with($stmt, '/*!')) continue;
            // Skip MySQL-only directives for SQLite
            if ($driver === 'sqlite' && preg_match('/^(SET|LOCK|UNLOCK|ALTER\s+TABLE.*DISABLE|ENABLE)/i', $stmt)) continue;
            try { $db->exec($stmt); $ok++; } catch (PDOException $e2) { $fail++; if ($fail < 3) echo "          fail: " . substr($e2->getMessage(),0,120) . "\n"; }
        }
        echo "        -> $ok ok, $fail failed (ignored)\n";
    }
}

$driverHint = $_ENV['DB_DRIVER'] ?? getenv('DB_DRIVER') ?: '(auto)';
echo "Avazonia local setup\n";
echo "====================\n";
echo "DB: " . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost') . "/" . ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'avazonia') . "  driver=$driverHint\n";
echo "APP_URL: " . (defined('APP_URL') ? APP_URL : 'unknown') . "\n\n";

// Connectivity check — db() will auto-fallback to SQLite if MySQL unavailable
try {
    $db = db();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $db->query("SELECT 1");
    echo "[1/4] DB connection: OK ($driver)\n";
    if ($driver === 'sqlite') {
        $file = __DIR__ . '/../storage/database.sqlite';
        echo "      SQLite file: $file (" . (file_exists($file) ? round(filesize($file)/1024) . " KB" : "new") . ")\n";
        echo "      Tip: set DB_DRIVER=sqlite in .env to force SQLite, or install MySQL for production parity.\n";
    }
} catch (Throwable $e) {
    echo "[1/4] DB connection: FAILED\n";
    echo "      " . $e->getMessage() . "\n";
    echo "\nFix: ensure DB is running and .env DB_* matches.\n";
    echo "  Docker: docker compose up -d db  (then retry)\n";
    echo "  Native MySQL: start XAMPP/MAMP MySQL or `net start mysql`\n";
    echo "  SQLite fallback: set DB_DRIVER=sqlite in .env (or just ensure pdo_sqlite is enabled — auto-fallback should have picked it)\n";
    exit(1);
}

if ($fresh) {
    echo "\n[2/4] --fresh: dropping existing tables...\n";
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        $db->exec("PRAGMA foreign_keys=OFF");
        foreach ($tables as $t) {
            $db->exec("DROP TABLE IF EXISTS \"$t\"");
            echo "  dropped $t\n";
        }
        $db->exec("PRAGMA foreign_keys=ON");
        // Remove file content fully — re-init will recreate
        // Keep file but empty is fine; we just dropped tables
    } else {
        $db->exec("SET FOREIGN_KEY_CHECKS=0");
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $t) {
            $db->exec("DROP TABLE IF EXISTS `$t`");
            echo "  dropped $t\n";
        }
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    }
}

echo "\n[2/4] Applying schema...\n";
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
    run_sql_file($db, __DIR__ . '/../docker/mysql/init/01-schema.sqlite.sql');
} else {
    run_sql_file($db, __DIR__ . '/../docker/mysql/init/01-schema.sql');
    run_sql_file($db, __DIR__ . '/../sliders_schema.sql');
    run_sql_file($db, __DIR__ . '/../db_email_migration.sql');
    run_sql_file($db, __DIR__ . '/../migrations/add_currency.sql');
    run_sql_file($db, __DIR__ . '/../migrations/010_marketplace.sql');
    run_sql_file($db, __DIR__ . '/../migrations/011_rfq_quotes.sql');
    // Seed Avazonia Owned seller for existing products
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
                    if ($storeId) $db->exec("UPDATE products SET seller_id=$sid, store_id=$storeId WHERE seller_id IS NULL");
                }
            }
        }
    } catch (Throwable $e) { echo "  marketplace backfill warning: ".$e->getMessage()."\n"; }
}

echo "\n[3/4] Seeding...\n";
run_sql_file($db, __DIR__ . '/../docker/mysql/init/02-seed.sql');

// ── Marketplace patch for existing SQLite DBs (add missing columns/tables) ──
try {
    if ($driver === 'sqlite') {
        // Ensure marketplace columns exist (idempotent)
        $ensureCol = function($table, $col, $def) use ($db) {
            $cols = $db->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array($col, $cols)) {
                $db->exec("ALTER TABLE $table ADD COLUMN $col $def");
                echo "  patched $table.$col\n";
            }
        };
        $ensureCol('users','seller_type',"TEXT");
        $ensureCol('users','buyer_type',"TEXT DEFAULT 'individual'");
        $ensureCol('users','verification_level',"TEXT DEFAULT 'unverified'");
        $ensureCol('users','country_code',"TEXT DEFAULT 'GH'");
        $ensureCol('users','company_name',"TEXT");
        $ensureCol('users','is_business',"INTEGER DEFAULT 0");
        $ensureCol('products','seller_id',"INTEGER");
        $ensureCol('products','store_id',"INTEGER");
        $ensureCol('products','listing_type',"TEXT DEFAULT 'retail'");
        $ensureCol('products','visibility',"TEXT DEFAULT 'public'");
        $ensureCol('products','condition_type',"TEXT DEFAULT 'new'");
        $ensureCol('products','moq',"INTEGER");
        $ensureCol('products','wholesale_price_ghs',"REAL");
        $ensureCol('products','fob_price_usd',"REAL");
        $ensureCol('products','incoterms',"TEXT");
        $ensureCol('products','production_capacity',"TEXT");
        $ensureCol('products','oem_odm',"INTEGER DEFAULT 0");
        $ensureCol('products','export_markets',"TEXT");
        $ensureCol('products','certifications',"TEXT");
        $ensureCol('products','location_country',"TEXT DEFAULT 'GH'");
        $ensureCol('products','vehicle_origin',"TEXT");
        $ensureCol('products','status_market',"TEXT DEFAULT 'active'");
        $ensureCol('order_items','seller_id',"INTEGER");
        $ensureCol('order_items','store_id',"INTEGER");
        // Categories expansion
        $db->exec("INSERT OR IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order, is_active) VALUES
        (30, NULL, 'Vehicles', 'vehicles', '🚗', 6, 1),
        (31, NULL, 'Fashion & Textiles', 'fashion-textiles', '👕', 7, 1),
        (32, NULL, 'Beauty & Personal Care', 'beauty-personal-care', '💄', 8, 1),
        (33, NULL, 'Health & Medical', 'health-medical', '🏥', 9, 1),
        (34, NULL, 'Home & Living', 'home-living', '🏠', 10, 1),
        (35, NULL, 'Energy', 'energy', '⚡', 11, 1),
        (36, NULL, 'Industrial & Machinery', 'industrial-machinery', '🏭', 12, 1),
        (37, NULL, 'Wholesale & General Merchandise', 'wholesale-general', '📦', 13, 1)");
        $db->exec("INSERT OR IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order, is_active) VALUES
        (40, 30, 'Cars', 'vehicles-cars', '🚗', 0, 1),
        (41, 30, 'SUVs', 'vehicles-suvs', '🚙', 1, 1),
        (42, 30, 'EVs', 'vehicles-evs', '🔋', 2, 1),
        (43, 30, 'Hybrid Vehicles', 'vehicles-hybrid', '⚡', 3, 1),
        (44, 30, 'Motorcycles', 'vehicles-motorcycles', '🏍️', 4, 1),
        (45, 30, 'Trucks', 'vehicles-trucks', '🚚', 5, 1),
        (46, 30, 'Buses', 'vehicles-buses', '🚌', 6, 1),
        (47, 30, 'Auto Parts', 'vehicles-auto-parts', '🔧', 7, 1),
        (48, 30, 'Tyres', 'vehicles-tyres', '🛞', 8, 1),
        (49, 30, 'Batteries', 'vehicles-batteries', '🔋', 9, 1),
        (50, 30, 'Chargers & Accessories', 'vehicles-chargers', '🔌', 10, 1)");
        // Backfill Avazonia Official
        $hasSeller = $db->query("SELECT id FROM sellers LIMIT 1")->fetch();
        if (!$hasSeller) {
            $adminId = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
            if ($adminId) {
                $db->exec("INSERT OR IGNORE INTO sellers (user_id, seller_type, business_name, slug, country_code, verification_level, is_verified) VALUES ($adminId, 'business_retailer', 'Avazonia Official', 'avazonia-official', 'GH', 'avazonia_verified', 1)");
                $sid = $db->query("SELECT id FROM sellers WHERE user_id=$adminId LIMIT 1")->fetchColumn();
                if ($sid) {
                    $db->exec("INSERT OR IGNORE INTO stores (seller_id, slug, name, country_code, is_featured) VALUES ($sid, 'avazonia-official', 'Avazonia Official', 'GH', 1)");
                    $storeId = $db->query("SELECT id FROM stores WHERE seller_id=$sid LIMIT 1")->fetchColumn();
                    if ($storeId) $db->exec("UPDATE products SET seller_id=$sid, store_id=$storeId WHERE seller_id IS NULL");
                }
            }
        }
    }
} catch (Throwable $e) { echo "  marketplace sqlite patch warning: ".$e->getMessage()."\n"; }

// Ensure runtime tables exist (idempotent) — driver-aware check
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
foreach (['notifications', 'newsletter_subscriptions', 'system_logs', 'settings'] as $t) {
    try {
        if ($driver === 'sqlite') {
            $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'")->fetch();
        } else {
            $exists = $db->query("SHOW TABLES LIKE '$t'")->fetch();
        }
        echo "  " . ($exists ? "OK" : "MISSING") . "  $t\n";
    } catch (Throwable $e) { echo "  MISSING  $t (" . $e->getMessage() . ")\n"; }
}

echo "\n[4/4] Health checks...\n";
$checks = [
    'users' => "SELECT COUNT(*) FROM users",
    'categories' => "SELECT COUNT(*) FROM categories",
    'brands' => "SELECT COUNT(*) FROM brands",
    'products' => "SELECT COUNT(*) FROM products",
    'settings' => "SELECT COUNT(*) FROM settings",
    'sliders' => "SELECT COUNT(*) FROM sliders",
];
foreach ($checks as $label => $sql) {
    try {
        $n = $db->query($sql)->fetchColumn();
        echo "  $label: $n rows\n";
    } catch (Throwable $e) {
        echo "  $label: ERROR — " . $e->getMessage() . "\n";
    }
}

try {
    $admin = $db->query("SELECT email, role FROM users WHERE role='admin' LIMIT 1")->fetch();
    if ($admin) {
        echo "\nAdmin login: {$admin['email']} / Admin123!\n";
        echo "Customer:   customer@avazonia.local / Admin123!\n";
    } else {
        echo "\nWARNING: no admin user found.\n";
    }
} catch (Throwable $e) { echo "\nWARNING: users table check failed: " . $e->getMessage() . "\n"; }

// Upload dirs
$dirs = ['public/uploads/products','public/uploads/categories','public/uploads/sliders','public/uploads/videos','backups','storage'];
echo "\nUpload dirs:\n";
foreach ($dirs as $d) {
    $full = __DIR__ . '/../' . $d;
    if (!is_dir($full)) { mkdir($full, 0775, true); echo "  created $d\n"; }
    else echo "  OK      $d\n";
}

echo "\nDone. Next:\n";
if ($driver === 'sqlite') {
    echo "  SQLite mode: php -S localhost:8000 -t . router.php  -> http://localhost:8000\n";
    echo "  For MySQL parity later: install Docker Desktop + `docker compose up -d db` then `php bin/setup.php --fresh`\n";
} else {
    echo "  Docker:  docker compose up --build   -> http://localhost:8080\n";
    echo "  Native:  php -S localhost:8000 -t . router.php  -> http://localhost:8000\n";
}
