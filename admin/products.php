<?php
// admin/products.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$products = $db->query("SELECT p.*, b.name as brand_name, c.name as cat_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();

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
                        <a href="edit-product.php?id=<?= $p['id'] ?>" class="nav-link" style="font-size: 10px; color: var(--ink); text-decoration: none; font-weight: 700; text-transform: uppercase;">Edit</a>
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
