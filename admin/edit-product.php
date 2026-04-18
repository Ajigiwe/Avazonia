<?php
// admin/edit-product.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$error = '';
$success = '';

$productId = $_GET['id'] ?? 0;
$product = $db->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$productId]);
$product = $product->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$images = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
$images->execute([$productId]);
$existing_images = $images->fetchAll();

$variantsStmt = $db->prepare("SELECT * FROM variants WHERE product_id = ? ORDER BY id ASC");
$variantsStmt->execute([$productId]);
$existing_variants = $variantsStmt->fetchAll();

// Fetch categories, brands, and existing tags for the form
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();

// Get unique tags from existing products
$tagsResult = $db->query("SELECT tags FROM products WHERE tags IS NOT NULL AND tags != ''")->fetchAll();
$allTags = [];
foreach ($tagsResult as $row) {
    if (!$row['tags']) continue;
    $rowTags = explode(',', $row['tags']);
    foreach ($rowTags as $t) {
        $trimmed = trim($t);
        if ($trimmed && !in_array($trimmed, $allTags)) $allTags[] = $trimmed;
    }
}
sort($allTags);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_product';

    if ($action === 'add_variant') {
        $color = $_POST['var_color'] ?? null;
        $colorHex = $_POST['var_color_hex'] ?? null;
        $size = $_POST['var_size'] ?? null;
        $sku = !empty($_POST['var_sku']) ? $_POST['var_sku'] : null;
        $stockQty = (int)($_POST['var_stock'] ?? 0);
        $priceOverride = !empty($_POST['var_price']) ? (float)$_POST['var_price'] : null;
        $imageUrl = $_POST['var_image_url'] ?? null;

        try {
            $stmt = $db->prepare("INSERT INTO variants (product_id, color, color_hex, size, sku, stock_qty, price_override_ghs, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$productId, $color, $colorHex, $size, $sku, $stockQty, $priceOverride, $imageUrl]);
            $success = "Variant added successfully!";
            header("Refresh: 1; URL=edit-product.php?id=$productId");
        } catch (PDOException $e) {
            $error = "Err adding variant: " . $e->getMessage();
        }
    } elseif ($action === 'del_variant') {
        $vId = (int)$_POST['variant_id'];
        $db->prepare("DELETE FROM variants WHERE id = ? AND product_id = ?")->execute([$vId, $productId]);
        $success = "Variant removed.";
        header("Refresh: 1; URL=edit-product.php?id=$productId");
    } else {
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $brand_id = $_POST['brand_id'] ?? null;
        $price = $_POST['price'] ?? 0;
        $compare_price = !empty($_POST['compare_price']) ? $_POST['compare_price'] : null;
        $stock = $_POST['stock'] ?? 0;
        $description = $_POST['description'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $video_url_manual = $_POST['video_url_manual'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
        $is_dropshipping = isset($_POST['is_dropshipping']) ? 1 : 0;
        $lead_time = !empty($_POST['lead_time']) ? (int)$_POST['lead_time'] : null;
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

    // Delete selected images
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $imgId) {
            $stmt = $db->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
            $stmt->execute([$imgId, $productId]);
        }
    }

    // File upload handling
    $uploaded_images = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $uploadDir = '../public/uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
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
                    $error = "Invalid file type. Only JPG, PNG, and WEBP allowed.";
                }
            }
        }
    }

    // Single video upload handling
    $uploaded_video = $video_url_manual;
    $video_updated = false;
    if (isset($_FILES['product_video']) && $_FILES['product_video']['error'] === UPLOAD_ERR_OK) {
        $videoDir = '../public/uploads/videos/';
        if (!is_dir($videoDir)) mkdir($videoDir, 0777, true);
        
        $allowedVideos = ['mp4', 'webm'];
        $fileExt = strtolower(pathinfo($_FILES['product_video']['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowedVideos)) {
            $fileName = 'v_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $targetPath = $videoDir . $fileName;
            if (move_uploaded_file($_FILES['product_video']['tmp_name'], $targetPath)) {
                $uploaded_video = 'public/uploads/videos/' . $fileName;
                $video_updated = true;
            }
        } else {
            $error = "Invalid video type. Only MP4 and WEBM allowed.";
        }
    }
    if (!empty($video_url_manual)) {
        $video_updated = true;
    }

    if (!$error && (empty($name) || empty($price))) {
        $error = "Product name and price are required.";
    }

    if (!$error) {
        try {
            $stmt = $db->prepare("UPDATE products SET name = ?, category_id = ?, brand_id = ?, price_ghs = ?, compare_at_price_ghs = ?, stock_qty = ?, description = ?, features = ?, specs = ?, tags = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, is_active = ?, is_bestseller = ?, is_featured = ?, is_preorder = ?, is_dropshipping = ?, lead_time_days = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $brand_id, $price, $compare_price, $stock, $description, $features_json, $specs_json, $tags, $meta_title, $meta_description, $meta_keywords, $is_active, $is_bestseller, $is_featured, $is_preorder, $is_dropshipping, $lead_time, $productId]);
            
            if ($video_updated) {
                $stmt = $db->prepare("UPDATE products SET video_url = ? WHERE id = ?");
                $stmt->execute([$uploaded_video, $productId]);
            }
            
            foreach ($uploaded_images as $img) {
                $stmt = $db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, 0)");
                $stmt->execute([$productId, $img]);
            }
            
            if (!empty($image_url)) {
                $stmt = $db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, 0)");
                $stmt->execute([$productId, $image_url]);
            }
            
            // Ensure there is exactly 1 primary image
            $checkPrimary = $db->prepare("SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1");
            $checkPrimary->execute([$productId]);
            if (!$checkPrimary->fetch()) {
                $firstImg = $db->prepare("SELECT id FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                $firstImg->execute([$productId]);
                if ($firstrow = $firstImg->fetch()) {
                    $db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?")->execute([$firstrow['id']]);
                }
            }
            
            $success = "Product updated successfully!";
            header('Refresh: 2; URL=products.php');
        } catch (PDOException $e) {
            $error = "Err: " . $e->getMessage();
        }
    }
    }
}

$title = "Edit Product - " . $product['name'];
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Edit Product</h1>
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
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Product Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Current Price (GHS)</label>
                    <input type="number" step="0.01" name="price" value="<?= $product['price_ghs'] ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--red); margin-bottom: 8px;">Old Price (Compare at)</label>
                    <input type="number" step="0.01" name="compare_price" value="<?= $product['compare_at_price_ghs'] ?>" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;" placeholder="e.g. 1500.00">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Category</label>
                    <select name="category_id" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Brand</label>
                    <select name="brand_id" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                        <option value="">Select Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= $product['brand_id'] == $brand['id'] ? 'selected' : '' ?>><?= $brand['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Stock Quantity</label>
                    <input type="number" name="stock" value="<?= $product['stock_qty'] ?>" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
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
                    <?php if(!empty($product['video_url'])): ?>
                        <div style="font-size: 10px; color: var(--red); margin-top: 4px;">Current Video: <?= htmlspecialchars(basename($product['video_url'])) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Video URL</label>
                    <input type="url" name="video_url_manual" placeholder="https://..." value="<?= filter_var($product['video_url'], FILTER_VALIDATE_URL) ? htmlspecialchars($product['video_url']) : '' ?>" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Image URL (Optional)</label>
                <input type="url" name="image_url" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
            </div>

            <?php if (!empty($existing_images)): ?>
            <div style="background: var(--off); padding: 24px; border-radius: 4px; border: 1px solid var(--light-gray);">
                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700;">Manage Existing Images</label>
                <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                    <?php foreach ($existing_images as $img): ?>
                        <div style="position: relative; width: 100px; height: 100px; border: 1px solid var(--light-gray); background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 4px;">
                            <?php 
                            $imgUrl = $img['url'];
                            if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $imgUrl = APP_URL . '/' . ltrim($imgUrl, '/');
                            }
                            ?>
                            <img src="<?= $imgUrl ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php if ($img['is_primary']): ?>
                                <span style="position: absolute; top: 4px; left: 4px; background: var(--red); color: #fff; font-size: 8px; padding: 2px 4px; border-radius: 2px;">Primary</span>
                            <?php endif; ?>
                            <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: #fff; font-size: 9px; text-align: center; padding: 4px; cursor: pointer;">
                                <input type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>"> Delete
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: var(--off); padding: 24px; border-radius: 4px; border: 1px solid var(--light-gray);">
                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700;">Manage Variants</label>
                
                <?php if (!empty($existing_variants)): ?>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px;">
                        <?php foreach ($existing_variants as $v): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 12px; border: 1px solid var(--light-gray); border-radius: 4px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($v['color_hex']): ?>
                                        <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $v['color_hex'] ?>; border: 1px solid var(--light-gray);"></div>
                                    <?php endif; ?>
                                    <div style="font-size: 13px; font-weight: 700;">
                                        <?= $v['color'] ?: 'No Color' ?> / <?= $v['size'] ?: 'No Size' ?>
                                    </div>
                                    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">
                                        SKU: <?= $v['sku'] ?: 'N/A' ?> | Stock: <?= $v['stock_qty'] ?> | Price: <?= $v['price_override_ghs'] ? '₵'.$v['price_override_ghs'] : 'Base' ?>
                                    </div>
                                </div>
                                <button type="button" onclick="if(confirm('Delete variant?')){ const f = document.createElement('form'); f.method='POST'; const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='del_variant'; const i = document.createElement('input'); i.type='hidden'; i.name='variant_id'; i.value='<?= $v['id'] ?>'; f.appendChild(a); f.appendChild(i); document.body.appendChild(f); f.submit(); }" style="background: none; border: none; color: var(--red); font-size: 11px; font-family: var(--f-semi); cursor: pointer; text-transform: uppercase;">Delete</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="background: #fff; padding: 20px; border: 1px solid var(--light-gray); border-radius: 4px;">
                    <h4 style="font-size: 11px; text-transform: uppercase; margin: 0 0 16px 0;">Add New Variant</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">Color Name</label>
                            <input type="text" name="var_color" style="width: 100%; padding: 8px; border: 1px solid var(--light-gray);" placeholder="e.g. Midnight Black">
                        </div>
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">Color HEX</label>
                            <input type="color" name="var_color_hex" style="width: 100%; height: 33px; padding: 2px; border: 1px solid var(--light-gray); cursor: pointer;" value="#000000">
                        </div>
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">Size / Config</label>
                            <input type="text" name="var_size" style="width: 100%; padding: 8px; border: 1px solid var(--light-gray);" placeholder="e.g. 128GB or XL">
                        </div>
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">Stock Qty</label>
                            <input type="number" name="var_stock" style="width: 100%; padding: 8px; border: 1px solid var(--light-gray);" value="0">
                        </div>
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">Price Override (Optional)</label>
                            <input type="number" step="0.01" name="var_price" style="width: 100%; padding: 8px; border: 1px solid var(--light-gray);" placeholder="e.g. 599.99">
                        </div>
                        <div>
                            <label style="display: block; font-size: 10px; color: var(--mid-gray); margin-bottom: 4px;">New Image URL (Optional)</label>
                            <input type="url" name="var_image_url" style="width: 100%; padding: 8px; border: 1px solid var(--light-gray);" placeholder="https://...">
                        </div>
                    </div>
                    <button type="submit" name="action" value="add_variant" class="btn-dark" style="font-size: 10px; padding: 8px 16px; height: auto;">+ Add Variant Config</button>
                    <input type="hidden" name="action" value="update_product" id="main_action_bound">
                    <script>
                        // Prevent main action from overriding button action natively in some browsers
                        document.querySelectorAll('button[name="action"]').forEach(b => {
                            b.addEventListener('click', () => document.getElementById('main_action_bound').disabled = true);
                        });
                    </script>
                </div>
            </div>

            <div style="display: flex; gap: 32px; align-items: center; background: #fff; padding: 20px; border: 1px solid var(--light-gray); border-radius: 4px; border-left: 4px solid var(--red);">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <input type="checkbox" name="is_active" id="is_active" <?= $product['is_active'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--red);">
                    <label for="is_active" style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; cursor: pointer; font-weight: 700;">Active in Shop</label>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <input type="checkbox" name="is_bestseller" id="is_bestseller" <?= ($product['is_bestseller'] ?? 0) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--red);">
                    <label for="is_bestseller" style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; cursor: pointer; color: var(--red); font-weight: 800;">★ Bestseller Slider</label>
                </div>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <input type="checkbox" name="is_featured" id="is_featured" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--red);">
                    <label for="is_featured" style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; cursor: pointer; color: var(--ink); font-weight: 800;">🔥 Featured Section</label>
                </div>
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Description (Overview)</label>
                <textarea name="description" rows="8" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical;"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <?php
            // Prepare Features & Specs for textarea
            $features_text = "";
            if (!empty($product['features'])) {
                $f_arr = json_decode($product['features'], true);
                if (is_array($f_arr)) $features_text = implode("\n", $f_arr);
            }
            
            $specs_text = "";
            if (!empty($product['specs'])) {
                $s_arr = json_decode($product['specs'], true);
                if (is_array($s_arr)) {
                    foreach ($s_arr as $k => $v) $specs_text .= "$k: $v\n";
                }
            }
            ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Key Features (One per line)</label>
                    <textarea name="features" rows="6" placeholder="Fast Charging&#10;Waterproof" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical; font-size: 13px;"><?= htmlspecialchars($features_text) ?></textarea>
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Technical Specs (Key: Value)</label>
                    <textarea name="specs" rows="6" placeholder="Weight: 200g&#10;Battery: 5000mAh" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical; font-size: 13px;"><?= htmlspecialchars($specs_text) ?></textarea>
                </div>
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Tags (comma-separated)</label>
                <input type="text" name="tags" id="tags-input" value="<?= htmlspecialchars($product['tags'] ?? '') ?>" placeholder="e.g. Premium, New Arrival, Limited" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                <?php if (!empty($allTags)): ?>
                    <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 9px; color: var(--mid-gray); text-transform: uppercase; width: 100%; margin-bottom: 4px;">Popular Tags:</span>
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
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>" placeholder="Search result title..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Meta Description (SEO & Shares)</label>
                        <textarea name="meta_description" maxlength="160" rows="3" placeholder="Brief summary (max 160 characters)..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: none;"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="<?= htmlspecialchars($product['meta_keywords'] ?? '') ?>" placeholder="gadgets, tech, avazonia" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                </div>
            </div>

            <div style="background: var(--off); padding: 24px; border-radius: 4px; border: 1px solid var(--light-gray);">
                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700;">Special Opportunities</label>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_preorder" value="1" <?= $product['is_preorder'] ? 'checked' : '' ?>>
                        <span>Pre-order Item</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer;">
                        <input type="checkbox" name="is_dropshipping" value="1" <?= $product['is_dropshipping'] ? 'checked' : '' ?>>
                        <span>Global Direct (Drop Shipping)</span>
                    </label>
                </div>

                <div style="margin-top: 20px;">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Lead Time (Days - for Global Direct)</label>
                    <input type="number" name="lead_time" value="<?= $product['lead_time_days'] ?>" placeholder="e.g. 14" style="width: 120px; padding: 10px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
            </div>

            <button type="submit" class="btn-red" style="height: 52px; justify-content: center; font-size: 11px; letter-spacing: 0.1em; margin-top: 12px;">Update Product</button>
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
</script>
<?php include 'layout/footer.php'; ?>
