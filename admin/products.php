<?php
// admin/products.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';
require_once '../models/Product.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$error = '';
$success = '';

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

$products = $db->query("
    SELECT
        p.*,
        b.name as brand_name,
        c.name as cat_name,
        COUNT(oi.id) as order_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
")->fetchAll();

$title = "Manage Products";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Products</h1>
    <a href="add-product.php" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center;">+ Add New Product</a>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Catalogue Inventory</div>
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
                    <th>Product</th>
                    <th>Brand & Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <?php
                    $hasOrders = (int)($p['order_count'] ?? 0) > 0;
                    $actionLabel = $hasOrders ? 'Archive' : 'Delete';
                    $confirmMessage = $hasOrders
                        ? "Archive " . $p['name'] . "? It will be hidden from the shop but preserved for past orders."
                        : "Delete " . $p['name'] . "? This cannot be undone.";
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= $p['name'] ?></td>
                    <td>
                        <div style="font-size: 11px; font-weight: 700; color: var(--red); text-transform: uppercase;"><?= $p['brand_name'] ?></div>
                        <div style="font-size: 11px; color: var(--mid-gray);"><?= $p['cat_name'] ?></div>
                    </td>
                    <td style="font-family: var(--f-mono);">₵<?= number_format($p['price_ghs'], 2) ?></td>
                    <td>
                        <span style="<?= $p['stock_qty'] < 5 ? 'color: var(--red); font-weight: 700;' : '' ?>">
                            <?= $p['stock_qty'] ?> units
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span class="status-badge <?= $p['is_active'] ? 'status-paid' : 'status-cancelled' ?>"><?= $p['is_active'] ? 'Active' : 'Hidden' ?></span>
                            <?php if ($p['is_preorder']): ?>
                                <span style="font-size: 9px; background: #000; color: #fff; padding: 2px 6px; border-radius: 2px; text-transform: uppercase;">Pre-Order</span>
                            <?php endif; ?>
                            <?php if ($p['is_dropshipping']): ?>
                                <span style="font-size: 9px; background: var(--red); color: #fff; padding: 2px 6px; border-radius: 2px; text-transform: uppercase;">Global</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <a href="edit-product.php?id=<?= $p['id'] ?>" class="nav-link" style="font-size: 10px; color: var(--ink); text-decoration: none; font-weight: 700; text-transform: uppercase;">Edit</a>
                            <form method="POST" onsubmit="return confirm(<?= json_encode($confirmMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);" style="margin: 0;">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" style="background: none; border: none; padding: 0; color: var(--red); font-size: 10px; font-family: var(--f-semi); font-weight: 700; text-transform: uppercase; cursor: pointer;"><?= $actionLabel ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--mid-gray);">No products found. Start by adding one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
