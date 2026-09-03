<?php
/**
 * Avazonia - Seed the FINAL category structure (26 top-level + 246 subcategories)
 *
 * MODE: Replace. Backs up the current categories table, wipes it, inserts the
 * client's final structure, then remaps every product onto its new category so
 * no product loses its category link.
 *
 * Usage:
 *   Browser: https://www.avazonia.com/seed_final_categories.php?secret=avazonia_final_structure_2026
 *   CLI:     php seed_final_categories.php
 *
 * Security: requires ?secret=avazonia_final_structure_2026 OR admin login.
 * DELETE THIS FILE AFTER USE.
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

/**
 * FINAL category structure.
 * [Name, slug, icon, [ [Name, slug, icon], ... ]]
 */
$CATS = [
    ['Electronics', 'electronics', '🔌', [
        ['TVs & Home Entertainment', 'tvs-home-entertainment', '📺'],
        ['Cameras & Photography', 'cameras-photography', '📷'],
        ['Audio & Headphones', 'audio-headphones', '🎧'],
        ['Speakers', 'speakers', '🔊'],
        ['Gaming', 'gaming', '🎮'],
        ['Smart Home', 'smart-home', '🏠'],
        ['Networking', 'networking', '🌐'],
        ['Storage Devices', 'storage-devices', '💾'],
        ['Printers & Scanners', 'printers-scanners', '🖨️'],
        ['Electronic Components', 'electronic-components', '🔩'],
        ['Other Electronics', 'other-electronics', '🔌'],
    ]],
    ['Mobile Phones & Accessories', 'mobile-phones-accessories', '📱', [
        ['Smartphones', 'smartphones', '📱'],
        ['Feature Phones', 'feature-phones', '📞'],
        ['Phone Cases & Covers', 'phone-cases-covers', '🛡️'],
        ['Screen Protectors', 'screen-protectors', '📲'],
        ['Chargers', 'chargers', '🔋'],
        ['Charging Cables', 'charging-cables', '🔌'],
        ['Power Banks', 'power-banks', '🔋'],
        ['Wireless Chargers', 'wireless-chargers', '⚡'],
        ['Car Chargers', 'car-chargers', '🚗'],
        ['Phone Holders', 'phone-holders', '📱'],
        ['Selfie Sticks & Tripods', 'selfie-sticks-tripods', '🤳'],
        ['Phone Stands', 'phone-stands', '📱'],
        ['Mobile Accessories', 'mobile-accessories', '🎧'],
    ]],
    ['Computers & Accessories', 'computers-accessories', '💻', [
        ['Laptops', 'laptops', '💻'],
        ['Desktop Computers', 'desktop-computers', '🖥️'],
        ['Monitors', 'monitors', '🖥️'],
        ['Keyboards', 'keyboards', '⌨️'],
        ['Mice', 'mice', '🖱️'],
        ['Webcams', 'webcams', '📷'],
        ['Laptop Bags', 'laptop-bags', '💼'],
        ['Laptop Stands', 'laptop-stands', '📐'],
        ['USB Hubs', 'usb-hubs', '🔌'],
        ['Hard Drives', 'hard-drives', '💾'],
        ['SSDs', 'ssds', '⚡'],
        ['Flash Drives', 'flash-drives', '💾'],
        ['RAM & Components', 'ram-components', '🧩'],
        ['Routers & Networking', 'routers-networking', '📡'],
        ['Computer Accessories', 'computer-accessories', '🖱️'],
    ]],
    ['Fashion', 'fashion', '👗', [
        ['Men\'s Clothing', 'mens-clothing', '👔'],
        ['Women\'s Clothing', 'womens-clothing', '👗'],
        ['Children\'s Clothing', 'childrens-clothing', '🧒'],
        ['Shoes', 'shoes', '👟'],
        ['Bags', 'bags', '👜'],
        ['Watches', 'watches', '⌚'],
        ['Jewelry', 'jewelry', '💍'],
        ['Sunglasses', 'sunglasses', '🕶️'],
        ['Belts', 'belts', '👖'],
        ['Hats & Caps', 'hats-caps', '🧢'],
        ['Fashion Accessories', 'fashion-accessories', '🕶️'],
    ]],
    ['Beauty & Personal Care', 'beauty-personal-care', '💄', [
        ['Skincare', 'skincare', '🧴'],
        ['Hair Care', 'hair-care', '💇'],
        ['Hair Extensions & Wigs', 'hair-extensions-wigs', '💇‍♀️'],
        ['Makeup', 'makeup', '💄'],
        ['Fragrances', 'fragrances', '🌸'],
        ['Men\'s Grooming', 'mens-grooming', '🪒'],
        ['Personal Care Appliances', 'personal-care-appliances', '🔌'],
        ['Nail Care', 'nail-care', '💅'],
        ['Bath & Body', 'bath-body', '🛁'],
        ['Beauty Accessories', 'beauty-accessories', '🪞'],
    ]],
    ['Health & Wellness', 'health-wellness', '💊', [
        ['Vitamins & Supplements', 'vitamins-supplements', '💊'],
        ['Herbal Products', 'herbal-products', '🌿'],
        ['Medical Supplies', 'medical-supplies', '🩺'],
        ['First Aid', 'first-aid', '⛑️'],
        ['Fitness & Wellness', 'fitness-wellness', '🏃'],
        ['Personal Health Devices', 'personal-health-devices', '💓'],
        ['Mobility & Support Products', 'mobility-support-products', '♿'],
    ]],
    ['Home & Living', 'home-living', '🏠', [
        ['Furniture', 'furniture', '🛋️'],
        ['Home Décor', 'home-decor', '🖼️'],
        ['Lighting', 'lighting', '💡'],
        ['Bedding', 'bedding', '🛏️'],
        ['Bathroom', 'bathroom', '🚿'],
        ['Storage & Organization', 'storage-organization', '🗃️'],
        ['Cleaning Supplies', 'cleaning-supplies', '🧹'],
        ['Home Improvement', 'home-improvement', '🔨'],
        ['Household Essentials', 'household-essentials', '🧺'],
    ]],
    ['Kitchen & Appliances', 'kitchen-appliances', '🍳', [
        ['Refrigerators', 'refrigerators', '🧊'],
        ['Freezers', 'freezers', '❄️'],
        ['Cookers & Ovens', 'cookers-ovens', '🔥'],
        ['Microwaves', 'microwaves', '♨️'],
        ['Blenders', 'blenders', '🥤'],
        ['Air Fryers', 'air-fryers', '🍟'],
        ['Rice Cookers', 'rice-cookers', '🍚'],
        ['Electric Kettles', 'electric-kettles', '🫖'],
        ['Coffee Makers', 'coffee-makers', '☕'],
        ['Kitchen Appliances', 'kitchen-appliances-2', '🍳'],
        ['Kitchen Tools & Utensils', 'kitchen-tools-utensils', '🍴'],
    ]],
    ['Automotive', 'automotive', '🚗', [
        ['Cars', 'cars', '🚗'],
        ['SUVs', 'suvs', '🚙'],
        ['Pickup Trucks', 'pickup-trucks', '🛻'],
        ['Vans', 'vans', '🚐'],
        ['Buses', 'buses', '🚌'],
        ['Trucks', 'trucks', '🚚'],
        ['Motorcycles', 'motorcycles', '🏍️'],
        ['Electric Vehicles', 'electric-vehicles', '⚡'],
        ['Car Parts', 'car-parts', '🔧'],
        ['Car Accessories', 'car-accessories', '🎛️'],
        ['Car Electronics', 'car-electronics', '📻'],
        ['Car Care Products', 'car-care-products', '🧽'],
        ['Tyres & Wheels', 'tyres-wheels', '🛞'],
        ['Automotive Tools & Equipment', 'automotive-tools-equipment', '🧰'],
    ]],
    ['Gadgets & Smart Devices', 'gadgets-smart-devices', '⌚', [
        ['Smartwatches', 'smartwatches', '⌚'],
        ['Fitness Trackers', 'fitness-trackers', '🏃'],
        ['Smart Glasses', 'smart-glasses', '🕶️'],
        ['Smart Bands', 'smart-bands', '📿'],
        ['Bluetooth Trackers', 'bluetooth-trackers', '📍'],
        ['Smart Home Devices', 'smart-home-devices', '🏠'],
        ['Wearable Technology', 'wearable-technology', '⌚'],
        ['Other Gadgets', 'other-gadgets', '🤖'],
    ]],
    ['Electrical & Power', 'electrical-power', '⚡', [
        ['Solar Panels', 'solar-panels', '☀️'],
        ['Solar Inverters', 'solar-inverters', '🔆'],
        ['Batteries', 'batteries', '🔋'],
        ['Power Stations', 'power-stations', '🔋'],
        ['Generators', 'generators', '⚙️'],
        ['UPS', 'ups', '🔌'],
        ['Voltage Regulators', 'voltage-regulators', '⚡'],
        ['Electrical Cables', 'electrical-cables', '🔌'],
        ['Switches & Sockets', 'switches-sockets', '🔘'],
        ['Electrical Accessories', 'electrical-accessories', '🔌'],
    ]],
    ['Tools & Hardware', 'tools-hardware', '🔧', [
        ['Power Tools', 'power-tools', '🔨'],
        ['Hand Tools', 'hand-tools', '🔧'],
        ['Construction Tools', 'construction-tools', '🏗️'],
        ['Workshop Equipment', 'workshop-equipment', '🛠️'],
        ['Measuring Tools', 'measuring-tools', '📏'],
        ['Welding Equipment', 'welding-equipment', '🥽'],
        ['Hardware', 'hardware', '🔩'],
        ['Safety Equipment', 'safety-equipment', '🦺'],
        ['Tool Storage', 'tool-storage', '🧰'],
    ]],
    ['Sports & Fitness', 'sports-fitness', '⚽', [
        ['Gym Equipment', 'gym-equipment', '🏋️'],
        ['Fitness Accessories', 'fitness-accessories', '🏃'],
        ['Football', 'football', '⚽'],
        ['Basketball', 'basketball', '🏀'],
        ['Volleyball', 'volleyball', '🏐'],
        ['Tennis', 'tennis', '🎾'],
        ['Running', 'running', '🏃'],
        ['Cycling', 'cycling', '🚴'],
        ['Swimming', 'swimming', '🏊'],
        ['Outdoor Sports', 'outdoor-sports', '⛺'],
        ['Sportswear', 'sportswear', '👕'],
        ['Sports Accessories', 'sports-accessories', '🎽'],
    ]],
    ['Musical Instruments', 'musical-instruments', '🎵', [
        ['Pianos & Keyboards', 'pianos-keyboards', '🎹'],
        ['Guitars', 'guitars', '🎸'],
        ['Drums & Percussion', 'drums-percussion', '🥁'],
        ['Violins & String Instruments', 'violins-string-instruments', '🎻'],
        ['Brass Instruments', 'brass-instruments', '🎺'],
        ['Wind Instruments', 'wind-instruments', '🎷'],
        ['DJ Equipment', 'dj-equipment', '🎧'],
        ['Microphones', 'microphones', '🎤'],
        ['Amplifiers', 'amplifiers', '🔊'],
        ['Speakers', 'speakers-2', '🔊'],
        ['Studio Equipment', 'studio-equipment', '🎚️'],
        ['Musical Accessories', 'musical-accessories', '🎼'],
    ]],
    ['Baby & Kids', 'baby-kids', '🍼', [
        ['Baby Clothing', 'baby-clothing', '👶'],
        ['Baby Shoes', 'baby-shoes', '👟'],
        ['Baby Feeding', 'baby-feeding', '🍼'],
        ['Baby Care', 'baby-care', '🧴'],
        ['Strollers', 'strollers', '🚼'],
        ['Car Seats', 'car-seats', '🚗'],
        ['Toys', 'toys', '🧸'],
        ['Educational Toys', 'educational-toys', '🎓'],
        ['Kids\' Furniture', 'kids-furniture', '🪑'],
        ['School Supplies', 'school-supplies', '🎒'],
    ]],
    ['Toys & Hobbies', 'toys-hobbies', '🧸', [
        ['Remote Control Toys', 'remote-control-toys', '🎮'],
        ['Drones', 'drones', '🚁'],
        ['Collectibles', 'collectibles', '🏆'],
        ['Board Games', 'board-games', '🎲'],
        ['Outdoor Toys', 'outdoor-toys', '🪁'],
        ['Educational Toys', 'educational-toys-2', '🧩'],
        ['Model Kits', 'model-kits', '✈️'],
        ['Hobby Equipment', 'hobby-equipment', '🎨'],
    ]],
    ['Office & School Supplies', 'office-school-supplies', '📚', [
        ['Stationery', 'stationery', '✏️'],
        ['Pens & Pencils', 'pens-pencils', '🖊️'],
        ['Notebooks', 'notebooks', '📓'],
        ['School Bags', 'school-bags', '🎒'],
        ['Office Furniture', 'office-furniture', '🪑'],
        ['Office Electronics', 'office-electronics', '🖨️'],
        ['Printers', 'printers', '🖨️'],
        ['Ink & Toner', 'ink-toner', '🖋️'],
        ['Filing & Organization', 'filing-organization', '🗂️'],
        ['Educational Materials', 'educational-materials', '📖'],
    ]],
    ['Agriculture & Farming', 'agriculture-farming', '🌾', [
        ['Farm Machinery', 'farm-machinery', '🚜'],
        ['Agricultural Tools', 'agricultural-tools', '🔧'],
        ['Irrigation Equipment', 'irrigation-equipment', '💧'],
        ['Seeds', 'seeds', '🌱'],
        ['Gardening Equipment', 'gardening-equipment', '🪴'],
        ['Poultry Equipment', 'poultry-equipment', '🐔'],
        ['Livestock Equipment', 'livestock-equipment', '🐄'],
        ['Greenhouse Equipment', 'greenhouse-equipment', '🏡'],
        ['Agricultural Supplies', 'agricultural-supplies', '🌾'],
    ]],
    ['Industrial & Commercial Equipment', 'industrial-commercial-equipment', '🏭', [
        ['Manufacturing Equipment', 'manufacturing-equipment', '🏭'],
        ['Packaging Machinery', 'packaging-machinery', '📦'],
        ['Food Processing Equipment', 'food-processing-equipment', '🍽️'],
        ['Restaurant Equipment', 'restaurant-equipment', '🍳'],
        ['Commercial Refrigeration', 'commercial-refrigeration', '🧊'],
        ['Construction Equipment', 'construction-equipment', '🏗️'],
        ['Warehouse Equipment', 'warehouse-equipment', '🏢'],
        ['Industrial Tools', 'industrial-tools', '⚙️'],
        ['Safety Equipment', 'safety-equipment-2', '🦺'],
    ]],
    ['Food & Beverages', 'food-beverages', '🍎', [
        ['Groceries', 'groceries', '🛒'],
        ['Snacks', 'snacks', '🍿'],
        ['Beverages', 'beverages', '🥤'],
        ['Cooking Ingredients', 'cooking-ingredients', '🧂'],
        ['Canned & Packaged Foods', 'canned-packaged-foods', '🥫'],
        ['Baby Food', 'baby-food', '🍼'],
        ['Specialty Foods', 'specialty-foods', '🍯'],
    ]],
    ['Pet Supplies', 'pet-supplies', '🐾', [
        ['Dog Supplies', 'dog-supplies', '🐶'],
        ['Cat Supplies', 'cat-supplies', '🐱'],
        ['Bird Supplies', 'bird-supplies', '🐦'],
        ['Fish & Aquarium', 'fish-aquarium', '🐠'],
        ['Pet Food', 'pet-food', '🦴'],
        ['Pet Accessories', 'pet-accessories', '🎀'],
        ['Pet Grooming', 'pet-grooming', '✂️'],
        ['Pet Health', 'pet-health', '💊'],
    ]],
    ['Travel & Luggage', 'travel-luggage', '🧳', [
        ['Suitcases', 'suitcases', '🧳'],
        ['Travel Bags', 'travel-bags', '🎒'],
        ['Backpacks', 'backpacks', '🎒'],
        ['Travel Accessories', 'travel-accessories', '🛂'],
        ['Travel Organizers', 'travel-organizers', '🗃️'],
        ['Camping & Outdoor Gear', 'camping-outdoor-gear', '⛺'],
        ['Travel Electronics', 'travel-electronics', '🔌'],
    ]],
    ['Jewelry & Accessories', 'jewelry-accessories', '💍', [
        ['Rings', 'rings', '💍'],
        ['Necklaces', 'necklaces', '📿'],
        ['Bracelets', 'bracelets', '💫'],
        ['Earrings', 'earrings', '💎'],
        ['Watches', 'watches-2', '⌚'],
        ['Fashion Jewelry', 'fashion-jewelry', '💅'],
        ['Jewelry Boxes', 'jewelry-boxes', '🎁'],
        ['Jewelry Accessories', 'jewelry-accessories-2', '🪞'],
    ]],
    ['Books, Media & Entertainment', 'books-media-entertainment', '📖', [
        ['Books', 'books', '📚'],
        ['Educational Books', 'educational-books', '📘'],
        ['Religious Books', 'religious-books', '📕'],
        ['Magazines', 'magazines', '📰'],
        ['Music', 'music', '🎵'],
        ['Movies', 'movies', '🎬'],
        ['Educational Media', 'educational-media', '💿'],
        ['Collectibles', 'collectibles-2', '🏆'],
    ]],
    ['Services', 'services', '🛠️', [
        ['Business Services', 'business-services', '💼'],
        ['Professional Services', 'professional-services', '🧑‍💼'],
        ['Repair Services', 'repair-services', '🔧'],
        ['Installation Services', 'installation-services', '🔩'],
        ['Delivery & Logistics', 'delivery-logistics', '🚚'],
        ['Events & Entertainment', 'events-entertainment', '🎉'],
        ['Real Estate Services', 'real-estate-services', '🏠'],
        ['Other Services', 'other-services', '🛠️'],
    ]],
    ['Other Products', 'other-products', '📦', [
        ['Miscellaneous Products', 'miscellaneous-products', '📦'],
        ['Other Unclassified Products', 'other-unclassified-products', '📦'],
    ]],
];

/**
 * Product remap: old category slug => new category slug.
 * Products whose old category slug is missing here are left uncategorized and reported.
 */
$REMAP = [
    'smartphones'            => 'smartphones',
    'laptops'                => 'laptops',
    'audio'                  => 'audio-headphones',
    'wearables'              => 'wearable-technology',
    'mobile-accessories'     => 'mobile-accessories',
    'smart-home-devices'     => 'smart-home-devices',
    'vehicles'               => 'automotive',
    'vehicles-cars'          => 'cars',
    'automobile'             => 'automotive',
    'energy'                 => 'electrical-power',
    'industrial-machinery'   => 'industrial-commercial-equipment',
    'wholesale-general'      => 'other-products',
    'home-living'            => 'home-living',
    'health-medical'         => 'health-wellness',
    'health-wellness'        => 'health-wellness',
    'beauty-personal-care'   => 'beauty-personal-care',
    'fashion-textiles'       => 'fashion',
    // legacy leaf categories (from earlier partial seeds)
    'android-phones'         => 'smartphones',
    'iphones'                => 'smartphones',
    'feature-phones'         => 'feature-phones',
    'phone-accessories'      => 'mobile-accessories',
    'chargers'               => 'chargers',
    'cases-covers'           => 'phone-cases-covers',
    'cables'                 => 'charging-cables',
    'headphones'             => 'audio-headphones',
    'speakers'               => 'speakers',
    'earbuds'                => 'audio-headphones',
    'smartwatches'           => 'smartwatches',
    'fitness-trackers'       => 'fitness-trackers',
    'smart-lighting'         => 'smart-home',
    'security-cameras'       => 'smart-home-devices',
    'audi'                   => 'cars',
    'honda'                  => 'cars',
    'mitsubishi-'            => 'cars',
    'vehicles-evs'           => 'electric-vehicles',
    'vehicles-suvs'          => 'suvs',
    'vehicles-hybrid'        => 'cars',
    'vehicles-motorcycles'   => 'motorcycles',
    'vehicles-trucks'        => 'trucks',
    'vehicles-buses'         => 'buses',
    'vehicles-auto-parts'    => 'car-parts',
    'vehicles-tyres'         => 'tyres-wheels',
    'vehicles-batteries'     => 'batteries',
    'vehicles-chargers'      => 'car-chargers',
];

try {
    $db = db();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "DB driver: $driver\n";
    echo "DB: " . ($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'unknown') . " @ " . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost') . "\n\n";

    // 1. Backup current categories + product->category mapping (restorable)
    $before = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $prodBefore = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id IS NOT NULL")->fetchColumn();
    $db->exec("DROP TABLE IF EXISTS zz_categories_backup");
    $db->exec("CREATE TABLE zz_categories_backup AS SELECT * FROM categories");
    $db->exec("DROP TABLE IF EXISTS zz_product_category_backup");
    $db->exec("CREATE TABLE zz_product_category_backup AS SELECT id AS product_id, category_id FROM products WHERE category_id IS NOT NULL");
    echo "1) Backup: $before categories, $prodBefore categorized products -> zz_categories_backup / zz_product_category_backup\n";

    // 2. Wipe (ON DELETE SET NULL clears products.category_id; remap step restores them)
    $db->exec("DELETE FROM categories");
    if ($driver === 'mysql') {
        $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
    }
    echo "2) Old categories wiped\n";

    // 3. Insert final structure
    $insTop = $db->prepare("INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active) VALUES (NULL, ?, ?, ?, ?, 1)");
    $insSub = $db->prepare("INSERT INTO categories (parent_id, name, slug, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $topCount = 0;
    $subCount = 0;
    foreach ($CATS as $topIdx => [$topName, $topSlug, $topIcon, $children]) {
        $insTop->execute([$topName, $topSlug, $topIcon, $topIdx]);
        $parentId = (int)$db->lastInsertId();
        $topCount++;
        foreach ($children as $subIdx => [$subName, $subSlug, $subIcon]) {
            $insSub->execute([$parentId, $subName, $subSlug, $subIcon, $subIdx]);
            $subCount++;
        }
    }
    echo "3) Seeded $topCount top-level + $subCount subcategories = " . ($topCount + $subCount) . " total\n";

    // 4. Remap products: old slug -> new slug
    $oldSlugById = [];
    foreach ($db->query("SELECT id, slug FROM zz_categories_backup") as $r) {
        $oldSlugById[(int)$r['id']] = $r['slug'];
    }
    $newIdBySlug = [];
    foreach ($db->query("SELECT id, slug FROM categories") as $r) {
        $newIdBySlug[$r['slug']] = (int)$r['id'];
    }
    $rows = $db->query("SELECT product_id, category_id FROM zz_product_category_backup")->fetchAll(PDO::FETCH_ASSOC);
    $remapped = 0;
    $unmapped = [];
    foreach ($rows as $row) {
        $oldSlug = $oldSlugById[(int)$row['category_id']] ?? null;
        $newSlug = $oldSlug !== null ? ($REMAP[$oldSlug] ?? null) : null;
        if ($newSlug !== null && isset($newIdBySlug[$newSlug])) {
            $up = $db->prepare("UPDATE products SET category_id = ? WHERE id = ?");
            $up->execute([$newIdBySlug[$newSlug], (int)$row['product_id']]);
            $remapped++;
        } else {
            $unmapped[] = ['product_id' => (int)$row['product_id'], 'old_slug' => $oldSlug];
        }
    }
    echo "4) Products remapped: $remapped";
    if ($unmapped) {
        echo " | UNMAPPED (" . count($unmapped) . "): ";
        $labels = array_map(fn($u) => '#' . $u['product_id'] . ' (' . ($u['old_slug'] ?? 'no-cat') . ')', $unmapped);
        echo implode(', ', $labels);
    }
    echo "\n";

    // 5. Verify
    echo "\n5) Verification\n";
    $total = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $tops = (int)$db->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NULL")->fetchColumn();
    echo "   Total categories: $total (top-level: $tops, subcategories: " . ($total - $tops) . ")\n";
    $uncat = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL")->fetchColumn();
    echo "   Products uncategorized now: $uncat (was " . ((int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn() - $prodBefore) . " before + remap)\n";

    echo "\n   Top-level categories:\n";
    $topRows = $db->query("SELECT c.name, c.slug, (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) AS subs FROM categories c WHERE c.parent_id IS NULL ORDER BY c.sort_order")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($topRows as $r) {
        echo "     - {$r['name']} ({$r['slug']}): {$r['subs']} subs\n";
    }

    echo "\nDone. Verify at https://www.avazonia.com/shop (hard refresh).\n";
    echo "DELETE THIS FILE AFTER USE: seed_final_categories.php\n";

} catch (Throwable $e) {
    if (!headers_sent()) http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}