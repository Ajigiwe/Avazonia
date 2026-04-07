<?php
// admin/orders.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

$title = "Manage Orders";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Orders</h1>
    <a href="api/export.php?type=orders" class="btn-ink" style="height: 44px; padding: 0 24px; font-size: 11px; font-weight: 800; border-radius: 0; display: flex; align-items: center; gap: 8px;">
        📥 EXPORT TO CSV
    </a>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Order History</div>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td style="font-family: var(--f-mono); font-size: 11px;"><?= $o['order_ref'] ?></td>
                    <td>
                        <div style="font-weight: 600;"><?= $o['customer_name'] ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray);"><?= $o['customer_email'] ?></div>
                    </td>
                    <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td style="font-weight: 700;">
                        <div>₵<?= number_format($o['total_ghs'], 2) ?></div>
                        <?php if ($o['is_preorder']): ?>
                            <div style="font-size: 10px; color: var(--red); font-weight: 400; font-family: var(--f-mono);">DEP: ₵<?= number_format($o['deposit_amount_ghs'], 2) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $o['status'] ?>"><?= $o['status'] ?></span>
                        <?php if ($o['is_preorder']): ?>
                            <div style="font-size: 8px; color: var(--red); font-weight: 900; font-family: var(--f-mono); margin-top: 4px; letter-spacing: 0.1em;">PRE-ORDER</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size: 10px; font-weight: 700; color: <?= $o['payment_method'] === 'pod' ? 'var(--red)' : 'var(--mid-gray)' ?>;">
                            <?= $o['payment_method'] === 'pod' ? '🚚 POD' : '💳 ONLINE' ?>
                        </div>
                    </td>
                    <td>
                        <a href="view-order.php?id=<?= $o['id'] ?>" class="btn-red" style="padding: 0 16px; font-size: 9px; height: 32px; border-radius: 99px; display: inline-flex; align-items: center; text-decoration: none;">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--mid-gray);">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
