<?php
/**
 * Avazonia - One-time seeder for Jiji-style subcategories
 * Usage:
 *   Browser: https://www.avazonia.com/seed_subcategories.php?secret=avazonia_seed_2026
 *   CLI: php seed_subcategories.php
 *
 * Security: requires ?secret=avazonia_seed_2026 OR admin login.
 * Delete this file after use.
 */
header('Content-Type: text/plain; charset=utf-8');

$SECRET = 'avazonia_seed_2026';
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
        echo "Example: https://www.avazonia.com/seed_subcategories.php?secret=$SECRET\n";
        exit;
    }
}

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = db();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "DB driver: $driver\n";
    echo "DB: ".($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'unknown')." @ ".($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost')."\n\n";

    // Define subcategories: parent_slug => [ [name, slug, icon], ... ]
    $map = [
        'smartphones' => [
            ['Android Phones', 'android-phones', '📱'],
            ['iPhones', 'iphones', '🍎'],
            ['Feature Phones', 'feature-phones', '📞'],
            ['Phone Accessories', 'phone-accessories', '🔌'],
        ],
        'laptops' => [
            ['Gaming Laptops', 'gaming-laptops', '🎮'],
            ['Business Laptops', 'business-laptops', '💼'],
            ['Laptop Accessories', 'laptop-accessories', '⌨️'],
        ],
        'audio' => [
            ['Headphones', 'headphones', '🎧'],
            ['Speakers', 'speakers', '🔊'],
            ['Earbuds', 'earbuds', '🎵'],
        ],
        'audio-devices' => [ // alternate slug variant
            ['Headphones', 'headphones', '🎧'],
            ['Speakers', 'speakers', '🔊'],
            ['Earbuds', 'earbuds', '🎵'],
        ],
        'wearables' => [
            ['Smartwatches', 'smartwatches', '⌚'],
            ['Fitness Trackers', 'fitness-trackers', '🏃'],
        ],
        'mobile-accessories' => [
            ['Chargers', 'chargers', '🔋'],
            ['Cases & Covers', 'cases-covers', '📱'],
            ['Cables', 'cables', '🔌'],
        ],
        'smart-home-devices' => [
            ['Smart Lighting', 'smart-lighting', '💡'],
            ['Security Cameras', 'security-cameras', '📷'],
        ],
    ];

    $created = 0;
    $skipped = 0;
    $missingParent = 0;

    foreach ($map as $parentSlug => $children) {
        // Find parent
        $stmt = $db->prepare("SELECT id, name FROM categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$parentSlug]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$parent) {
            // Don't spam for alternate slug that may not exist
            if ($parentSlug === 'audio-devices') continue;
            echo "SKIP parent '$parentSlug' not found\n";
            $missingParent++;
            continue;
        }
        $parentId = (int)$parent['id'];
        echo "Parent '{$parent['name']}' ($parentSlug) id=$parentId\n";

        foreach ($children as $idx => $child) {
            [$name, $slug, $icon] = $child;
            // Check exists by slug
            $chk = $db->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
            $chk->execute([$slug]);
            if ($chk->fetch()) {
                echo "  - $name ($slug) already exists, skip\n";
                $skipped++;
                continue;
            }
            $stmt = $db->prepare("INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$parentId, $name, $slug, $icon, $idx]);
            $newId = $db->lastInsertId();
            echo "  + $name ($slug) created id=$newId under $parentId\n";
            $created++;
        }
    }

    echo "\nDone. Created: $created, Skipped (exists): $skipped, Missing parents: $missingParent\n";

    // Verify
    $total = $db->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL")->fetchColumn();
    echo "Total subcategories now: $total\n";

    $stmt = $db->prepare("SELECT slug FROM categories WHERE slug = ?");
    $stmt->execute(['android-phones']);
    if ($stmt->fetch()) {
        echo "Check: android-phones exists - OK\n";
    }

    // Optional: show Smartphones children with counts
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = 'smartphones' LIMIT 1");
    $stmt->execute();
    $sp = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sp) {
        $stmt2 = $db->prepare("SELECT c.name, c.slug, (SELECT COUNT(*) FROM products p WHERE p.is_active=1 AND (p.category_id=c.id OR p.category_id IN (SELECT id FROM categories WHERE parent_id=c.id))) AS cnt FROM categories c WHERE c.parent_id = ? ORDER BY c.sort_order");
        $stmt2->execute([$sp['id']]);
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "\nSmartphones subcategories with counts:\n";
        foreach ($rows as $r) {
            echo "  - {$r['name']} ({$r['slug']}): {$r['cnt']} ads\n";
        }
    }

    echo "\nNext: Visit https://www.avazonia.com/shop?cat=smartphones (hard refresh) should show Jiji-style list.\n";
    echo "Delete this file after use: seed_subcategories.php\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
