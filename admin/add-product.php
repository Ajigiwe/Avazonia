<?php
// admin/add-product.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

// CSRF Check for POST requests
require_once __DIR__ . '/_csrf_check.php';

$db = db();
$error = '';
$success = '';

// Fetch categories, brands, and existing tags for the form
$categories = $db->query("SELECT id, name, parent_id FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();
$sellers = [];
try { $sellers = $db->query("SELECT s.id, s.business_name, s.seller_type, s.verification_level, u.email FROM sellers s LEFT JOIN users u ON s.user_id=u.id ORDER BY s.business_name ASC")->fetchAll(); } catch (Throwable $e) {}

// Get unique tags from existing products
$tagsResult = $db->query("SELECT tags FROM products WHERE tags IS NOT NULL AND tags != ''")->fetchAll();
$allTags = [];
foreach ($tagsResult as $row) {
    $rowTags = explode(',', $row['tags']);
    foreach ($rowTags as $t) {
        $trimmed = trim($t);
        if ($trimmed && !in_array($trimmed, $allTags)) $allTags[] = $trimmed;
    }
}
sort($allTags);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $brand_id = $_POST['brand_id'] ?? null;
    $currency = $_POST['currency'] ?? 'GHS';
    $price = ($currency === 'GHS') ? (float)($_POST['price'] ?? 0) : 0;
    $compare_price = ($currency === 'GHS' && !empty($_POST['compare_price'])) ? (float)$_POST['compare_price'] : null;
    $price_usd = ($currency === 'USD') ? (float)($_POST['price_usd'] ?? 0) : null;
    $compare_price_usd = ($currency === 'USD' && !empty($_POST['compare_price_usd'])) ? (float)$_POST['compare_price_usd'] : null;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $video_url_manual = $_POST['video_url_manual'] ?? '';
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_dropshipping = isset($_POST['is_dropshipping']) ? 1 : 0;
    $lead_time = !empty($_POST['lead_time']) ? (int)$_POST['lead_time'] : null;
    // Marketplace
    $seller_id = !empty($_POST['seller_id']) ? (int)$_POST['seller_id'] : null;
    $store_id = null;
    if ($seller_id) { $r=$db->prepare("SELECT id FROM stores WHERE seller_id=? LIMIT 1"); $r->execute([$seller_id]); $store_id=$r->fetchColumn() ?: null; }
    $listing_type = $_POST['listing_type'] ?? 'retail';
    $allowedListing=['retail','wholesale','rfq','export']; if(!in_array($listing_type,$allowedListing)) $listing_type='retail';
    $visibility = $_POST['visibility'] ?? 'public'; if(!in_array($visibility,['public','b2b_only','retail_only'])) $visibility='public';
    $condition_type = $_POST['condition_type'] ?? 'new'; if(!in_array($condition_type,['new','used'])) $condition_type='new';
    $moq = !empty($_POST['moq']) ? (int)$_POST['moq'] : null;
    $wholesale_price = !empty($_POST['wholesale_price_ghs']) ? (float)$_POST['wholesale_price_ghs'] : null;
    $fob_price = !empty($_POST['fob_price_usd']) ? (float)$_POST['fob_price_usd'] : null;
    $incoterms = $_POST['incoterms'] ?? null; if($incoterms && !in_array($incoterms,['EXW','FOB','CIF'])) $incoterms=null;
    $production_capacity = $_POST['production_capacity'] ?? null;
    $oem_odm = isset($_POST['oem_odm']) ? 1 : 0;
    $vehicle_origin = $_POST['vehicle_origin'] ?? null; if($vehicle_origin && !in_array($vehicle_origin,['local','international_export'])) $vehicle_origin=null;
    $location_country = $_POST['location_country'] ?? 'GH';
    $status_market = 'active';
    $tags = $_POST['tags'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    $meta_keywords = $_POST['meta_keywords'] ?? '';

    // Handle Features (JSON array)
    $features_raw = $_POST['features'] ?? '';
    $features_arr = array_filter(array_map('trim', explode("\n", $features_raw)));
    $features_json = !empty($features_arr) ? json_encode(array_values($features_arr)) : null;

    // Handle Specs (JSON object)
    $specs_raw = $_POST['specs'] ?? '';
    $specs_arr = [];
    foreach (explode("\n", $specs_raw) as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $val) = explode(':', $line, 2);
            $specs_arr[trim($key)] = trim($val);
        }
    }
    $specs_json = !empty($specs_arr) ? json_encode($specs_arr) : null;

    // Multiple file upload handling
    $uploaded_images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $uploadDir = '../public/uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $fileCount = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($fileExt, $allowed)) {
                    $fileName = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetPath)) {
                        $uploaded_images[] = 'public/uploads/products/' . $fileName;
                    }
                } else {
                    $error = "Invalid file type for one or more images. Only JPG, PNG, and WEBP allowed.";
                }
            }
        }
    }

    // Single video upload handling
    $uploaded_video = $video_url_manual;
    if (isset($_FILES['product_video']) && $_FILES['product_video']['error'] === UPLOAD_ERR_OK) {
        $videoDir = '../public/uploads/videos/';
        if (!is_dir($videoDir)) {
            mkdir($videoDir, 0777, true);
        }
        
        $allowedVideos = ['mp4', 'webm'];
        $fileExt = strtolower(pathinfo($_FILES['product_video']['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowedVideos)) {
            $fileName = 'v_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $targetPath = $videoDir . $fileName;
            if (move_uploaded_file($_FILES['product_video']['tmp_name'], $targetPath)) {
                $uploaded_video = 'public/uploads/videos/' . $fileName;
            }
        } else {
            $error = "Invalid video type. Only MP4 and WEBM allowed.";
        }
    }

    // Simple slug generation
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!$error && empty($name)) {
        $error = "Product name is required.";
    }
    if (!$error && $currency === 'GHS' && empty($price)) {
        $error = "Price in GHS is required.";
    }
    if (!$error && $currency === 'USD' && empty($price_usd)) {
        $error = "Price in USD is required.";
    }

    if (!$error) {
        try {
            $stmt = $db->prepare("INSERT INTO products (name, slug, category_id, brand_id, seller_id, store_id, listing_type, visibility, condition_type, moq, wholesale_price_ghs, fob_price_usd, incoterms, production_capacity, oem_odm, location_country, vehicle_origin, status_market, price_ghs, compare_at_price_ghs, price_usd, compare_at_price_usd, currency, stock_qty, description, features, specs, tags, meta_title, meta_description, meta_keywords, is_preorder, is_bestseller, is_featured, is_dropshipping, lead_time_days, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $category_id, $brand_id, $seller_id, $store_id, $listing_type, $visibility, $condition_type, $moq, $wholesale_price, $fob_price, $incoterms, $production_capacity, $oem_odm, $location_country, $vehicle_origin, $status_market, $price, $compare_price, $price_usd, $compare_price_usd, $currency, $stock, $description, $features_json, $specs_json, $tags, $meta_title, $meta_description, $meta_keywords, $is_preorder, $is_bestseller, $is_featured, $is_dropshipping, $lead_time, $uploaded_video]);
            
            $productId = $db->lastInsertId();
            
            foreach ($uploaded_images as $index => $img) {
                $isPrimary = ($index === 0 && empty($image_url)) ? 1 : 0;
                $stmt = $db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $img, $isPrimary]);
            }
            
            if (!empty($image_url)) {
                // If URL is provided, we'll make it primary if no files were uploaded
                $isPrimary = empty($uploaded_images) ? 1 : 0;
                $stmt = $db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)");
                $stmt->execute([$productId, $image_url, $isPrimary]);
            }
            
            $success = "Product added successfully!";
            header('Refresh: 2; URL=products.php');
        } catch (PDOException $e) {
            $error = "Err: " . $e->getMessage();
        }
    }
}

$title = "Add Product";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Add New Product</h1>
    <a href="products.php" class="nav-link" style="font-size: 10px;">← Back to List</a>
</div>

<div class="panel" style="max-width: 800px;">
    <div class="panel-header">
        <div class="panel-title">Product Details</div>
    </div>
    <div style="padding: 40px;">
        <?php if ($error): ?>
            <div style="background: #fff1f0; color: #f5222d; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #f5222d;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background: #e6f7ec; color: #00a854; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #00a854;">
                <?= $success ?> Redirecting...
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 24px;">
            <?= Csrf::field() ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Product Name</label>
                    <input type="text" name="name" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Currency</label>
                    <select name="currency" id="currency-select" onchange="toggleCurrency()" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; background: #fff;">
                        <option value="GHS">GHS (₵) — Ghana Cedis</option>
                        <option value="USD">USD ($) — US Dollars</option>
                    </select>
                </div>
            </div>

            <div id="price-ghs-fields">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Current Price (GHS ₵)</label>
                        <input type="number" step="0.01" name="price" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--red); margin-bottom: 8px;">Old Price / Compare at (GHS ₵)</label>
                        <input type="number" step="0.01" name="compare_price" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;" placeholder="e.g. 1500.00">
                    </div>
                </div>
            </div>

            <div id="price-usd-fields" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Current Price (USD $)</label>
                        <input type="number" step="0.01" name="price_usd" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;" placeholder="e.g. 49.99">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--red); margin-bottom: 8px;">Old Price / Compare at (USD $)</label>
                        <input type="number" step="0.01" name="compare_price_usd" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;" placeholder="e.g. 79.99">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Category</label>
                    <select name="category_id" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Brand</label>
                    <select name="brand_id" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                        <option value="">Select Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>"><?= $brand['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Stock Quantity</label>
                    <input type="number" name="stock" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Upload Images (Multiple)</label>
                    <input type="file" name="images[]" multiple accept="image/*" style="width: 100%; padding: 9px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 11px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Upload Video (MP4/WEBM)</label>
                    <input type="file" name="product_video" accept="video/mp4,video/webm" style="width: 100%; padding: 9px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 11px;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Video URL</label>
                    <input type="url" name="video_url_manual" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Image URL (Optional)</label>
                <input type="url" name="image_url" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Description (Overview)</label>
                <textarea name="description" rows="5" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Key Features (One per line)</label>
                    <textarea name="features" rows="6" placeholder="Fast Charging&#10;Waterproof&#10;2-Year Warranty" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical; font-size: 13px;"></textarea>
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Technical Specs (Key: Value)</label>
                    <textarea name="specs" rows="6" placeholder="Weight: 200g&#10;Battery: 5000mAh&#10;Material: Silicone" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical; font-size: 13px;"></textarea>
                </div>
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Tags (comma-separated)</label>
                <input type="text" name="tags" id="tags-input" placeholder="e.g. Premium, New Arrival, Limited" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                <?php if (!empty($allTags)): ?>
                    <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 9px; color: var(--mid-gray); text-transform: uppercase; width: 100%; margin-bottom: 4px;">Popular/Selected Tags:</span>
                        <?php foreach ($allTags as $tag): ?>
                            <span class="tag-chip" onclick="addTag('<?= addslashes($tag) ?>')" style="font-size: 10px; background: var(--off); padding: 4px 10px; border-radius: 100px; cursor: pointer; border: 1px solid var(--light-gray); transition: all 0.2s;"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="background: var(--off); padding: 24px; border-radius: 4px; border: 1px solid var(--light-gray);">
                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700;">SEO & Social Discovery</label>
                
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Meta Title (Optional)</label>
                        <input type="text" name="meta_title" placeholder="Search result title..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Meta Description (SEO & Shares)</label>
                        <textarea name="meta_description" maxlength="160" rows="3" placeholder="Brief summary (max 160 characters)..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: none;"></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Meta Keywords</label>
                        <input type="text" name="meta_keywords" placeholder="gadgets, tech, avazonia" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                </div>
            </div>

            <div style="background:#fffbeb;padding:20px;border:2px solid #f59e0b;border-radius:4px;">
                <label style="display:block;font-family:var(--f-semi);font-size:11px;text-transform:uppercase;color:var(--ink);margin-bottom:14px;font-weight:800;">🏪 Marketplace — Seller & Listing</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Seller / Store</label>
                        <select name="seller_id" style="width:100%;padding:10px;border:1px solid var(--light-gray);">
                            <option value="">Avazonia Official (default)</option>
                            <?php foreach($sellers as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars(($s['business_name']?:$s['email']).' — '.$s['seller_type'].' ('.$s['verification_level'].')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Listing Type</label>
                        <select name="listing_type" id="listing_type" onchange="toggleMarketplace()" style="width:100%;padding:10px;border:1px solid var(--light-gray);">
                            <option value="retail">Retail — single price</option>
                            <option value="wholesale">Wholesale — MOQ / bulk</option>
                            <option value="rfq">Request for Quote — price on enquiry</option>
                            <option value="export">International Export — FOB/CIF</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Visibility</label>
                        <select name="visibility" style="width:100%;padding:10px;border:1px solid var(--light-gray);"><option value="public">Public — everyone</option><option value="b2b_only">B2B Only — business buyers</option><option value="retail_only">Retail Only — consumers</option></select>
                    </div>
                    <div>
                        <label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Condition</label>
                        <select name="condition_type" style="width:100%;padding:10px;border:1px solid var(--light-gray);"><option value="new">New</option><option value="used">Used (C2C)</option></select>
                    </div>
                </div>
                <div id="wholesale-fields" style="display:none;margin-top:14px;grid-template-columns:1fr 1fr;gap:14px;">
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">MOQ (Min Order Qty)</label><input type="number" name="moq" placeholder="e.g. 20" style="width:100%;padding:10px;border:1px solid var(--light-gray);"></div>
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Wholesale Price (GHS)</label><input type="number" step="0.01" name="wholesale_price_ghs" placeholder="e.g. 120.00" style="width:100%;padding:10px;border:1px solid var(--light-gray);"></div>
                </div>
                <div id="export-fields" style="display:none;margin-top:14px;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">FOB Price (USD)</label><input type="number" step="0.01" name="fob_price_usd" placeholder="e.g. 1500" style="width:100%;padding:10px;border:1px solid var(--light-gray);"></div>
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Incoterms</label><select name="incoterms" style="width:100%;padding:10px;border:1px solid var(--light-gray);"><option value="">—</option><option value="EXW">EXW</option><option value="FOB">FOB</option><option value="CIF">CIF</option></select></div>
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Production Capacity</label><input type="text" name="production_capacity" placeholder="e.g. 500 units/month" style="width:100%;padding:10px;border:1px solid var(--light-gray);"></div>
                </div>
                <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;"><input type="checkbox" name="oem_odm" value="1"> OEM/ODM Available</label>
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Location Country</label><select name="location_country" style="width:100%;padding:10px;border:1px solid var(--light-gray);"><option value="GH">🇬🇭 Ghana — Local</option><option value="CN">🇨🇳 China — Export</option><option value="OTHER">Other</option></select></div>
                    <div><label style="display:block;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);margin-bottom:6px;">Vehicle Origin (if Vehicles)</label><select name="vehicle_origin" style="width:100%;padding:10px;border:1px solid var(--light-gray);"><option value="">— Not a vehicle</option><option value="local">Local — Available in Ghana</option><option value="international_export">International Export — FOB China</option></select></div>
                </div>
                <script>function toggleMarketplace(){var v=document.getElementById('listing_type').value;document.getElementById('wholesale-fields').style.display=(v==='wholesale'||v==='export')?'grid':'none';document.getElementById('export-fields').style.display=(v==='export')?'grid':'none';}</script>
            </div>

            <div style="background: var(--off); padding: 24px; border-radius: 4px; border: 1px solid var(--light-gray);">
                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700;">Special Opportunities</label>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_preorder" value="1">
                        <span>Pre-order Item</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_dropshipping" value="1">
                        <span>Global Direct (Drop Shipping)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: var(--red); font-weight: 800;">
                        <input type="checkbox" name="is_bestseller" value="1">
                        <span>★ Bestseller Slider</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: var(--ink); font-weight: 800;">
                        <input type="checkbox" name="is_featured" value="1">
                        <span>🔥 Featured Section</span>
                    </label>
                </div>

                <div style="margin-top: 20px;">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Lead Time (Days - for Global Direct)</label>
                    <input type="number" name="lead_time" placeholder="e.g. 14" style="width: 120px; padding: 10px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
            </div>

            <button type="submit" class="btn-red" style="height: 52px; justify-content: center; font-size: 11px; letter-spacing: 0.1em; margin-top: 12px;">Publish Product</button>
        </form>
    </div>
</div>

<script>
function addTag(tag) {
    const input = document.getElementById('tags-input');
    let currentTags = input.value.split(',').map(t => t.trim()).filter(t => t !== "");
    if (!currentTags.includes(tag)) {
        currentTags.push(tag);
        input.value = currentTags.join(', ');
    }
}
function toggleCurrency() {
    const sel = document.getElementById('currency-select').value;
    document.getElementById('price-ghs-fields').style.display = sel === 'GHS' ? '' : 'none';
    document.getElementById('price-usd-fields').style.display = sel === 'USD' ? '' : 'none';
    document.querySelector('#price-ghs-fields input[name="price"]').required = sel === 'GHS';
    document.querySelector('#price-usd-fields input[name="price_usd"]').required = sel === 'USD';
}
</script>
<?php include 'layout/footer.php'; ?>
