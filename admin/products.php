<?php
// admin/products.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Product.php';

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

// Pagination
$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
if ($page > $totalPages) $page = $totalPages;

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
        $db->prepare("UPDATE products SET status_market=? WHERE id=?")->execute([$act,$pid]);
        $success="Product #$pid → ".strtoupper($act);
    }
}

$products = $db->query("
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
    GROUP BY p.id
    ORDER BY p.status_market='pending_review' DESC, p.created_at DESC
    LIMIT $perPage OFFSET $offset
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
                    <th>Brand & Category · Seller</th>
                    <th>Price</th>
                    <th>Stock</th>
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
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span class="status-badge <?= $p['is_active'] ? 'status-paid' : 'status-cancelled' ?>"><?= $p['is_active'] ? 'Active' : 'Hidden' ?></span>
                            <span class="status-badge <?= ($p['status_market']??'active')==='pending_review'?'status-processing':(($p['status_market']??'active')==='rejected'?'status-cancelled':'status-paid') ?>" style="font-size:9px;"><?= strtoupper($p['status_market']??'active') ?></span>
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
                            <form method="POST" onsubmit="return confirm(<?= json_encode($confirmMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);" style="margin: 0;">
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
                <tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--mid-gray);">No products found. Start by adding one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:center;align-items:center;gap:8px;padding:24px 32px;border-top:1px solid var(--light-gray);">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;border:1px solid var(--light-gray);text-decoration:none;color:var(--ink);border-radius:4px;transition:all 0.2s;">&laquo; Prev</a>
    <?php endif; ?>
    <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">&hellip;</span>';
        for ($i = $start; $i <= $end; $i++):
    ?>
        <a href="?page=<?= $i ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;text-decoration:none;border-radius:4px;transition:all 0.2s;<?= $i === $page ? 'background:var(--ink);color:#fff;' : 'border:1px solid var(--light-gray);color:var(--ink);' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($end < $totalPages) echo '<span style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">&hellip;</span>'; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>" style="padding:8px 14px;font-family:var(--f-mono);font-size:11px;border:1px solid var(--light-gray);text-decoration:none;color:var(--ink);border-radius:4px;transition:all 0.2s;">Next &raquo;</a>
    <?php endif; ?>
    <span style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-left:12px;">Page <?= $page ?> of <?= $totalPages ?> (<?= $totalProducts ?> products)</span>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
