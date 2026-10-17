<?php
// admin/products.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../core/ProductFilter.php';

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

// ── Filter engine ──────────────────────────────────────────────────────────
// The whole list state (filters, sort, page) lives in the query string, so a
// filtered view can be bookmarked, shared, paginated, and survives the POST
// round-trips the page does for delete / moderate / bulk actions. ProductFilter
// validates every incoming value and compiles them into a parameterised WHERE.
$filter = new ProductFilter($_GET);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $productModel = new Product();
    $result = $productModel->deleteById($productId);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'moderate_product') {
    $pid=(int)($_POST['product_id']??0); $act=$_POST['moderate']??'';
    if ($pid && in_array($act,['active','rejected','draft'])) {
        if ($act === 'active') {
            // Approving a product an admin pulled must also put it back live, otherwise
            // it stays hidden (is_active=0) even though its status is now 'active'.
            // Only rows with pulled_at set get is_active restored, so a product the
            // admin deliberately hid via the edit form stays hidden after approval.
            $db->prepare("UPDATE products SET status_market=?, is_active = CASE WHEN pulled_at IS NOT NULL THEN 1 ELSE is_active END, pulled_at = NULL WHERE id=?")->execute([$act,$pid]);
        } else {
            $db->prepare("UPDATE products SET status_market=? WHERE id=?")->execute([$act,$pid]);
        }
        Cache::flushTags(['products']);
        $success="Product #$pid → ".strtoupper($act);
    }
}

// ── Bulk catalogue actions: pull products off the storefront, or put them back ──
// Pull sets is_active=0 (the one flag every storefront query checks) plus
// status_market='pending_review' so the items land in the Approvals queue and
// cannot go live again until approved.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['bulk_pull','bulk_restore'], true)) {
    require_once __DIR__ . '/../models/Logger.php';
    $bulkAction = $_POST['action'];
    $ids = [];
    foreach (explode(',', (string)($_POST['product_ids'] ?? '')) as $r) {
        $r = trim($r);
        if ($r !== '' && ctype_digit($r)) $ids[] = (int)$r;
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        $error = 'Select at least one product first.';
    } else {
        if (count($ids) > 1000) $ids = array_slice($ids, 0, 1000); // bound the request
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $now = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') ? "datetime('now')" : 'NOW()';
        try {
            if ($bulkAction === 'bulk_pull') {
                // COALESCE keeps the original pull time if the product is already pulled.
                $stmt = $db->prepare("UPDATE products SET is_active=0, status_market='pending_review', pulled_at = COALESCE(pulled_at, $now) WHERE id IN ($ph)");
            } else {
                $stmt = $db->prepare("UPDATE products SET is_active=1, status_market='active', pulled_at = NULL WHERE id IN ($ph)");
            }
            $stmt->execute($ids);
            $changed = (int)$stmt->rowCount();
            Cache::flushTags(['products']);
            Logger::log(
                $bulkAction === 'bulk_pull' ? 'PRODUCT_PULL' : 'PRODUCT_RESTORE',
                $changed . ' product(s) ' . ($bulkAction === 'bulk_pull' ? 'pulled from the storefront' : 'restored to live'),
                ['product_ids' => $ids]
            );
            $success = $bulkAction === 'bulk_pull'
                ? $changed . ' product' . ($changed === 1 ? '' : 's') . ' pulled from the site and queued for approval. They stay offline until approved again.'
                : $changed . ' product' . ($changed === 1 ? '' : 's') . ' put back live.';
        } catch (Throwable $e) {
            $error = 'Bulk update failed: ' . $e->getMessage();
        }
    }
}

// ── Count + fetch, driven by the filter engine ─────────────────────────────
// The count runs against the same WHERE clause as the page query so the
// pagination math always matches what is actually listed.
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalProducts = $filter->rowCount($db);
// Unfiltered total, so the header can read "12 of 480" while a filter is on.
$catalogueTotal = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Name maps for the filter dropdowns and for readable chips like "Brand: Nike".
$brandRows = $db->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
$categoryRows = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$sellerRows = $db->query("SELECT id, business_name FROM sellers ORDER BY business_name")->fetchAll();
$filter->setLabels([
    'brand'    => array_column($brandRows, 'name', 'id'),
    'category' => array_column($categoryRows, 'name', 'id'),
    'seller'   => array_column($sellerRows, 'business_name', 'id'),
]);

$productsQuery = "
    SELECT
        p.*,
        b.name as brand_name,
        c.name as cat_name,
        s.business_name as seller_name,
        st.name as store_name,
        COUNT(oi.id) as order_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id=s.id
    LEFT JOIN stores st ON p.store_id=st.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    {$filter->whereSql()}
    GROUP BY p.id
    ORDER BY {$filter->orderBy()}
    LIMIT $perPage OFFSET $offset
";
$productsStmt = $db->prepare($productsQuery);
$productsStmt->execute($filter->params());
$products = $productsStmt->fetchAll();

$title = "Manage Products";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Products <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">(<?= $totalProducts ?><?= $filter->isActive() ? ' of ' . $catalogueTotal : '' ?>)</span></h1>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="import-products.php" class="admin-btn admin-btn-secondary" style="height:44px;">Import CSV</a>
    <a href="add-product.php" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center;">+ Add New Product</a>
    </div>
</div>

<!-- ── Filter engine ─────────────────────────────────────────────────── -->
<style>
    .pf-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px 14px; padding: 20px 24px; border-bottom: 1px solid var(--light-gray); background: var(--off, #fafafa); }
    .pf-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    .pf-field > span { font-family: var(--f-semi); font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); }
    .pf-bar input[type=text], .pf-bar input[type=number], .pf-bar input[type=date], .pf-bar select {
        height: 38px; padding: 0 10px; border: 1px solid var(--light-gray); border-radius: 4px;
        background: #fff; color: var(--ink); font-family: var(--f-mono); font-size: 11px; width: 100%; box-sizing: border-box;
    }
    .pf-bar input:focus, .pf-bar select:focus { outline: none; border-color: var(--red); }
    .pf-range { display: flex; align-items: center; gap: 6px; }
    .pf-range span { color: var(--mid-gray); font-family: var(--f-mono); font-size: 11px; }
    .pf-actions { display: flex; align-items: flex-end; gap: 8px; }
    .pf-apply { height: 38px; padding: 0 18px; border: none; border-radius: 4px; background: var(--red); color: #fff; font-family: var(--f-semi); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; }
    .pf-apply:hover { background: var(--red-deep, #b8001f); }
    .pf-clear { height: 38px; padding: 0 14px; border: 1px solid var(--light-gray); border-radius: 4px; background: #fff; color: var(--ink); font-family: var(--f-semi); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .pf-clear:hover { border-color: var(--ink); }
    .pf-presets { display: flex; gap: 6px; flex-wrap: wrap; }
    .pf-preset { padding: 4px 10px; border: 1px solid var(--light-gray); border-radius: 99px; background: #fff; color: var(--mid-gray); font-family: var(--f-mono); font-size: 10px; text-decoration: none; white-space: nowrap; }
    .pf-preset:hover { border-color: var(--ink); color: var(--ink); }
    .pf-preset.on { background: var(--ink); border-color: var(--ink); color: #fff; }
    .pf-chips { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding: 14px 24px; border-bottom: 1px solid var(--light-gray); }
    .pf-chip { display: inline-flex; align-items: center; gap: 7px; padding: 5px 10px; border: 1px solid var(--light-gray); border-radius: 99px; background: #fff; color: var(--ink); font-family: var(--f-mono); font-size: 10px; text-decoration: none; }
    .pf-chip:hover { border-color: var(--red); color: var(--red); }
    .pf-chip b { font-weight: 700; }
    .pf-chip i { font-style: normal; color: var(--red); font-weight: 800; }
</style>

<div class="panel" style="margin-bottom: 24px;">
    <form method="GET" action="products.php" class="pf-bar">
        <div class="pf-field" style="grid-column: span 2;">
            <span>Name / Brand / SKU</span>
            <input type="text" name="q" value="<?= htmlspecialchars($filter->value('q'), ENT_QUOTES, 'UTF-8') ?>" placeholder="SEARCH THE CATALOGUE…" autocomplete="off">
        </div>

        <div class="pf-field">
            <span>Uploaded</span>
            <select name="when">
                <option value="">Any time</option>
                <?php foreach (ProductFilter::presets() as $preset): ?>
                <option value="<?= $preset['key'] ?>" <?= $filter->activePreset() === $preset['key'] ? 'selected' : '' ?>><?= $preset['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>From → To</span>
            <div class="pf-range">
                <input type="date" name="from" value="<?= htmlspecialchars($filter->value('from'), ENT_QUOTES, 'UTF-8') ?>">
                <span>→</span>
                <input type="date" name="to" value="<?= htmlspecialchars($filter->value('to'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="pf-field">
            <span>Category</span>
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categoryRows as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $filter->value('category') === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Brand</span>
            <select name="brand">
                <option value="">All brands</option>
                <?php foreach ($brandRows as $b): ?>
                <option value="<?= (int)$b['id'] ?>" <?= $filter->value('brand') === (int)$b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Seller</span>
            <select name="seller">
                <option value="">All sellers</option>
                <?php foreach ($sellerRows as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= $filter->value('seller') === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['business_name'] ?? 'Seller #' . $s['id'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Status</span>
            <select name="status">
                <option value="">Any status</option>
                <?php foreach (ProductFilter::SPECS['status']['options'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filter->value('status') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Visibility</span>
            <select name="vis">
                <option value="">Live &amp; hidden</option>
                <?php foreach (ProductFilter::SPECS['vis']['options'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filter->value('vis') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Listing type</span>
            <select name="listing">
                <option value="">Any type</option>
                <?php foreach (ProductFilter::SPECS['listing']['options'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filter->value('listing') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Stock</span>
            <select name="stock">
                <option value="">Any stock level</option>
                <?php foreach (ProductFilter::SPECS['stock']['options'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $filter->value('stock') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-field">
            <span>Price (<?= CURRENCY_SYMBOL ?>)</span>
            <div class="pf-range">
                <input type="number" name="price_min" min="0" step="0.01" value="<?= htmlspecialchars($filter->value('price_min'), ENT_QUOTES, 'UTF-8') ?>" placeholder="MIN">
                <span>→</span>
                <input type="number" name="price_max" min="0" step="0.01" value="<?= htmlspecialchars($filter->value('price_max'), ENT_QUOTES, 'UTF-8') ?>" placeholder="MAX">
            </div>
        </div>

        <div class="pf-field">
            <span>Sort by</span>
            <select name="sort">
                <?php foreach (ProductFilter::SORT_LABELS as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($_GET['sort'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pf-actions">
            <button type="submit" class="pf-apply">Apply</button>
            <?php if ($filter->isActive() || isset($_GET['sort'])): ?>
            <a href="<?= htmlspecialchars($filter->clearUrl(), ENT_QUOTES, 'UTF-8') ?>" class="pf-clear">Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($chips = $filter->chips()): ?>
    <div class="pf-chips">
        <span style="font-family:var(--f-semi);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);">Active:</span>
        <?php foreach ($chips as $chip): ?>
        <a class="pf-chip" href="<?= htmlspecialchars($chip['url'], ENT_QUOTES, 'UTF-8') ?>" title="Remove this filter">
            <b><?= htmlspecialchars($chip['label'], ENT_QUOTES, 'UTF-8') ?></b><i>✕</i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="pf-chips" style="<?= $filter->isActive() ? '' : 'border-bottom:none;padding-top:0;' ?>">
        <span style="font-family:var(--f-semi);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);">Quick upload range:</span>
        <div class="pf-presets">
            <a class="pf-preset <?= $filter->activePreset() === '' ? 'on' : '' ?>" href="<?= htmlspecialchars($filter->url(['when' => null, 'from' => null, 'to' => null]), ENT_QUOTES, 'UTF-8') ?>">All time</a>
            <?php foreach (ProductFilter::presets() as $preset): $on = $filter->activePreset() === $preset['key']; ?>
            <a class="pf-preset <?= $on ? 'on' : '' ?>" href="<?= htmlspecialchars($filter->url(['when' => $preset['key'], 'from' => null, 'to' => null]), ENT_QUOTES, 'UTF-8') ?>"><?= $preset['label'] ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Catalogue Inventory</div>
        <form method="POST" id="bulk-form" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:0;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="bulk-action" value="">
            <input type="hidden" name="product_ids" id="bulk-ids" value="">
            <span id="bulk-count" style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">0 selected</span>
            <button type="submit" onclick="document.getElementById('bulk-action').value='bulk_pull';" data-confirm="Pull the selected products off the storefront? They will be hidden immediately and stay offline until you approve them again from the Approvals queue." data-confirm-title="Pull From Site" style="height:38px;padding:0 16px;font-size:10px;font-weight:800;letter-spacing:0.06em;background:var(--red);color:#fff;border:none;border-radius:3px;cursor:pointer;">Pull From Site</button>
            <button type="submit" onclick="document.getElementById('bulk-action').value='bulk_restore';" data-confirm="Put the selected products back on the storefront as live and active?" data-confirm-title="Restore To Live" style="height:38px;padding:0 16px;font-size:10px;font-weight:800;letter-spacing:0.06em;background:#16a34a;color:#fff;border:none;border-radius:3px;cursor:pointer;">Restore To Live</button>
        </form>
    </div>
    <?php if ($error): ?>
        <div style="margin: 0 32px 24px; background: #fff1f0; color: #f5222d; padding: 16px; font-size: 13px; border-left: 4px solid #f5222d;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="margin: 0 32px 24px; background: #e6f7ec; color: #00a854; padding: 16px; font-size: 13px; border-left: 4px solid #00a854;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:34px;"><input type="checkbox" id="bulk-select-all" title="Select all on this page" style="width:14px;height:14px;accent-color:var(--red);cursor:pointer;"></th>
                    <th>Product</th>
                    <th>Brand & Category · Seller</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Uploaded</th>
                    <th>Status</th>
                    <th>Moderation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <?php
                    $hasOrders = (int)($p['order_count'] ?? 0) > 0;
                    $confirmMessage = $hasOrders
                        ? "Delete " . $p['name'] . "? This product has order history. The delete will still go through, and a backup will be created automatically first."
                        : "Delete " . $p['name'] . "? This cannot be undone.";
                ?>
                <tr>
                    <td><input type="checkbox" class="bulk-check" value="<?= $p['id'] ?>" style="width:14px;height:14px;accent-color:var(--red);cursor:pointer;"></td>
                    <td style="font-weight: 600;"><?= $p['name'] ?></td>
                    <td>
                        <div style="font-size: 11px; font-weight: 700; color: var(--red); text-transform: uppercase;"><?= $p['brand_name'] ?></div>
                        <div style="font-size: 11px; color: var(--mid-gray);"><?= $p['cat_name'] ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray); font-family: var(--f-mono);"><?= htmlspecialchars($p['seller_name'] ?? 'Avazonia Official') ?> · <?= htmlspecialchars($p['store_name'] ?? '-') ?> · <span style="text-transform:uppercase;"><?= htmlspecialchars($p['listing_type'] ?? 'retail') ?><?= !empty($p['moq'])?' MOQ '.$p['moq']:'' ?></span></div>
                    </td>
                    <td style="font-family: var(--f-mono);"><?= format_price($p) ?></td>
                    <td>
                        <span style="<?= $p['stock_qty'] < 5 ? 'color: var(--red); font-weight: 700;' : '' ?>">
                            <?= $p['stock_qty'] ?> units
                        </span>
                    </td>
                    <td style="font-family: var(--f-mono); font-size: 11px; white-space: nowrap; color: var(--mid-gray);" title="Uploaded <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($p['created_at'])), ENT_QUOTES, 'UTF-8') ?>">
                        <?= date('M j, Y', strtotime($p['created_at'])) ?>
                        <div style="font-size: 10px; opacity: .7;"><?= date('g:i A', strtotime($p['created_at'])) ?></div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span class="status-badge <?= $p['is_active'] ? 'status-paid' : 'status-cancelled' ?>"><?= $p['is_active'] ? 'Active' : 'Hidden' ?></span>
                            <span class="status-badge <?= ($p['status_market']??'active')==='pending_review'?'status-processing':(($p['status_market']??'active')==='rejected'?'status-cancelled':'status-paid') ?>" style="font-size:9px;"><?= strtoupper($p['status_market']??'active') ?></span>
                            <?php if (!empty($p['pulled_at'])): ?>
                                <span class="status-badge status-cancelled" title="Pulled from the storefront on <?= date('M j, Y g:i A', strtotime($p['pulled_at'])) ?>">Pulled</span>
                            <?php endif; ?>
                            <?php if ($p['is_preorder']): ?>
                                <span style="font-size: 9px; background: #000; color: #fff; padding: 2px 6px; border-radius: 2px; text-transform: uppercase;">Pre-Order</span>
                            <?php endif; ?>
                            <?php if ($p['is_dropshipping']): ?>
                                <span style="font-size: 9px; background: var(--red); color: #fff; padding: 2px 6px; border-radius: 2px; text-transform: uppercase;">Global</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if (($p['status_market']??'active')==='pending_review'): ?>
                        <form method="POST" style="display:flex;gap:6px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="moderate_product"><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button name="moderate" value="active" style="background:#16a34a;color:#fff;border:none;padding:6px 10px;font-size:9px;font-weight:700;cursor:pointer;">APPROVE</button>
                            <button name="moderate" value="rejected" style="background:var(--red);color:#fff;border:none;padding:6px 10px;font-size:9px;font-weight:700;cursor:pointer;">REJECT</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:flex;gap:6px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="moderate_product"><input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button name="moderate" value="pending_review" style="background:var(--ink);color:#fff;border:none;padding:6px 10px;font-size:9px;cursor:pointer;">→ Review</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px; min-width: 92px;">
                            <a href="edit-product.php?id=<?= $p['id'] ?>" class="nav-link" style="font-size: 10px; color: var(--ink); text-decoration: none; font-weight: 700; text-transform: uppercase; line-height: 1;">Edit</a>
                            <form method="POST" data-confirm="<?= htmlspecialchars($confirmMessage, ENT_QUOTES, 'UTF-8') ?>" data-confirm-title="Delete Product" style="margin: 0;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" style="display: inline-flex; align-items: center; background: none; border: none; padding: 0; color: var(--red); font-size: 10px; font-family: var(--f-semi); font-weight: 700; text-transform: uppercase; cursor: pointer; line-height: 1; white-space: nowrap;">Delete</button>
                            </form>
                            <?php if ($hasOrders): ?>
                                <span style="font-size: 9px; color: var(--mid-gray); text-transform: uppercase; line-height: 1.2;">Backup first</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--mid-gray);">
                    <?php if ($filter->isActive()): ?>
                        No products match these filters. <a href="<?= htmlspecialchars($filter->clearUrl(), ENT_QUOTES, 'UTF-8') ?>" style="color:var(--red);font-weight:700;">Clear all filters</a>
                    <?php else: ?>
                        No products found. Start by adding one!
                    <?php endif; ?>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:center;align-items:center;gap:8px;padding:24px 32px;border-top:1px solid var(--light-gray);flex-wrap:wrap;">
    <?php if ($page > 1): ?>
        <a href="<?= htmlspecialchars($filter->url(['page' => $page-1]), ENT_QUOTES, 'UTF-8') ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;border:1px solid var(--light-gray);text-decoration:none;color:var(--ink);border-radius:4px;transition:all 0.2s;">&laquo; Prev</a>
    <?php endif; ?>
    <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">&hellip;</span>';
        for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="<?= htmlspecialchars($filter->url(['page' => $i]), ENT_QUOTES, 'UTF-8') ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;text-decoration:none;border-radius:4px;transition:all 0.2s;<?= $i === $page ? 'background:var(--ink);color:#fff;' : 'border:1px solid var(--light-gray);color:var(--ink);' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">&hellip;</span>'; ?>
    <?php if ($page < $totalPages): ?>
        <a href="<?= htmlspecialchars($filter->url(['page' => $page+1]), ENT_QUOTES, 'UTF-8') ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;border:1px solid var(--light-gray);text-decoration:none;color:var(--ink);border-radius:4px;transition:all 0.2s;">Next &raquo;</a>
    <?php endif; ?>
    <span style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-left:12px;">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalProducts ?> product<?= $totalProducts === 1 ? '' : 's' ?><?= $filter->isActive() ? ' of ' . $catalogueTotal : '' ?>)</span>
</div>
<?php endif; ?>

<script>
// ── Bulk select / pull-from-site controls ───────────────────────────
(function () {
    var form = document.getElementById('bulk-form');
    if (!form) return;
    var selectAll = document.getElementById('bulk-select-all');
    var countEl = document.getElementById('bulk-count');
    var idsInput = document.getElementById('bulk-ids');

    function checks() { return Array.prototype.slice.call(document.querySelectorAll('.bulk-check')); }

    function refresh() {
        var n = checks().filter(function (c) { return c.checked; }).length;
        countEl.textContent = n + ' selected';
        countEl.style.color = n ? 'var(--ink)' : 'var(--mid-gray)';
        // Reflect a partial selection on the header checkbox.
        var all = checks();
        selectAll.checked = all.length > 0 && n === all.length;
        selectAll.indeterminate = n > 0 && n < all.length;
    }

    selectAll.addEventListener('change', function () {
        checks().forEach(function (c) { c.checked = selectAll.checked; });
        refresh();
    });
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('bulk-check')) refresh();
    });

    // Guard against submitting with nothing selected, and hand the server the ids.
    form.addEventListener('submit', function (e) {
        var ids = checks().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        if (!ids.length) {
            e.preventDefault();
            e.stopImmediatePropagation();
            window.AdminUI && window.AdminUI.alert('Select at least one product first.', { title: 'Nothing Selected', tone: 'info' });
            return false;
        }
        idsInput.value = ids.join(',');
        return true;
    });

    refresh();
})();
</script>

<?php include 'layout/footer.php'; ?>
