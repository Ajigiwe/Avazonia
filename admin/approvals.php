<?php
// admin/approvals.php — Pending product moderation queue
// Lists every seller product awaiting review with one-click APPROVE / REJECT.
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

// Handle approve / reject / send-back-to-review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'moderate_product') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $act = $_POST['moderate'] ?? '';
    if ($pid && in_array($act, ['active', 'rejected', 'pending_review'], true)) {
        $db->prepare("UPDATE products SET status_market=? WHERE id=? AND seller_id IS NOT NULL")->execute([$act, $pid]);
        $success = 'Product #' . $pid . ' → ' . strtoupper($act);
    }
}

// The moderation queue — pending seller products, newest first
$pending = $db->query("
    SELECT
        p.*,
        b.name as brand_name,
        c.name as cat_name,
        s.business_name as seller_name,
        s.is_verified as seller_verified,
        st.name as store_name,
        (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) as img_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    LEFT JOIN stores st ON p.store_id = st.id
    WHERE p.status_market = 'pending_review'
    ORDER BY p.created_at DESC
    LIMIT 200
")->fetchAll();

$pendingCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='pending_review'")->fetchColumn();
$rejectedCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='rejected'")->fetchColumn();

$title = "Pending Approvals";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Approvals <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">(<?= $pendingCount ?> pending)</span></h1>
    <a href="products.php" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">Full Catalogue →</a>
</div>

<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <div style="flex:1;min-width:140px;background:#fff7ed;border:1px solid #fed7aa;padding:16px 20px;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#ea580c;letter-spacing:0.08em;">Awaiting Review</div>
        <div style="font-weight:800;font-size:24px;color:#ea580c;margin-top:4px;"><?= $pendingCount ?></div>
    </div>
    <div style="flex:1;min-width:140px;background:#fef2f2;border:1px solid #fecaca;padding:16px 20px;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#dc2626;letter-spacing:0.08em;">Rejected</div>
        <div style="font-weight:800;font-size:24px;color:#dc2626;margin-top:4px;"><?= $rejectedCount ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Seller Products Awaiting Review</div>
    </div>
    <?php if ($error): ?>
        <div style="margin: 0 32px 24px; background: #fff1f0; color: #f5222d; padding: 16px; font-size: 13px; border-left: 4px solid #f5222d;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="margin: 0 32px 24px; background: #e6f7ec; color: #00a854; padding: 16px; font-size: 13px; border-left: 4px solid #00a854;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Category · Listing</th>
                    <th>Price</th>
                    <th>Submitted</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; max-width: 260px;"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray); font-family: var(--f-mono); margin-top: 2px;">
                            <?= htmlspecialchars($p['brand_name'] ?? '—') ?> · <?= $p['img_count'] ?> img<?= $p['img_count'] == 1 ? '' : 's' ?>
                        </div>
                        <a href="edit-product.php?id=<?= (int)$p['id'] ?>" style="font-size:10px;color:var(--red);text-transform:uppercase;font-weight:700;text-decoration:none;">Edit →</a>
                    </td>
                    <td>
                        <div style="font-weight: 700;"><?= htmlspecialchars($p['seller_name'] ?? 'Unknown seller') ?></div>
                        <?php if (!empty($p['seller_verified'])): ?>
                            <span class="status-badge status-paid" style="font-size:9px;">Verified</span>
                        <?php else: ?>
                            <span class="status-badge status-pending" style="font-size:9px;">Not yet verified</span>
                            <div style="font-size:9px;color:var(--mid-gray);margin-top:4px;max-width:160px;">Product stays hidden until this seller account is verified.</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size: 11px;"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray); font-family: var(--f-mono); text-transform: uppercase;"><?= htmlspecialchars($p['listing_type'] ?? 'retail') ?></div>
                    </td>
                    <td style="font-family: var(--f-mono);"><?= format_price($p) ?></td>
                    <td style="font-size: 11px; white-space: nowrap;"><?= date('M j, Y g:i A', strtotime($p['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="moderate_product">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button name="moderate" value="active" style="background:#16a34a;color:#fff;border:none;padding:8px 14px;font-size:10px;font-weight:700;cursor:pointer;border-radius:3px;">APPROVE</button>
                            <button name="moderate" value="rejected" style="background:#fff;color:#dc2626;border:1px solid #fca5a5;padding:8px 14px;font-size:10px;font-weight:700;cursor:pointer;border-radius:3px;">REJECT</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pending)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--mid-gray);">
                        <div style="font-size:28px;margin-bottom:8px;">🎉</div>
                        Nothing awaiting review right now. Products sent to review from the catalogue will appear here.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
