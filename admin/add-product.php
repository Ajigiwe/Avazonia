<?php
// admin/add-product.php
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

// Fetch categories, brands, and existing tags for the form
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();

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
    $price = $_POST['price'] ?? 0;
    $compare_price = !empty($_POST['compare_price']) ? $_POST['compare_price'] : null;
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_dropshipping = isset($_POST['is_dropshipping']) ? 1 : 0;
    $lead_time = !empty($_POST['lead_time']) ? (int)$_POST['lead_time'] : null;
    $tags = $_POST['tags'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    $meta_keywords = $_POST['meta_keywords'] ?? '';

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

    // Simple slug generation
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!$error && (empty($name) || empty($price))) {
        $error = "Product name and price are required.";
    }

    if (!$error) {
        try {
            $stmt = $db->prepare("INSERT INTO products (name, slug, category_id, brand_id, price_ghs, compare_at_price_ghs, stock_qty, description, tags, meta_title, meta_description, meta_keywords, is_preorder, is_bestseller, is_featured, is_dropshipping, lead_time_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $category_id, $brand_id, $price, $compare_price, $stock, $description, $tags, $meta_title, $meta_description, $meta_keywords, $is_preorder, $is_bestseller, $is_featured, $is_dropshipping, $lead_time]);
            
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
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Product Name</label>
                    <input type="text" name="name" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Current Price (GHS)</label>
                    <input type="number" step="0.01" name="price" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--red); margin-bottom: 8px;">Old Price (Compare at)</label>
                    <input type="number" step="0.01" name="compare_price" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;" placeholder="e.g. 1500.00">
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

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Image URL (Optional)</label>
                <input type="url" name="image_url" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Description</label>
                <textarea name="description" rows="5" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: vertical;"></textarea>
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
</script>
<?php include 'layout/footer.php'; ?>
