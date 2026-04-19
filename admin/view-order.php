<?php
// admin/view-order.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';
require_once '../models/Order.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$orderModel = new Order();
try {
    $orderModel->ensureSchema(); // Auto-patch DB on visit
} catch (Exception $e) {
    error_log("Order Schema Patch failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    $finalStatus = $orderModel->updateStatus($orderId, $newStatus);
    
    $extra = $finalStatus === 'refunded' ? '&refunded=1' : '';
    header("Location: view-order.php?id=" . $orderId . "&status_updated=1" . $extra);
    exit;
}

$order = $orderModel->findById($orderId);
$items = $orderModel->findItemsByOrderId($orderId);

if (!$order) {
    die("Order not found.");
}

$title = "Order Details — " . $order['order_ref'];
include 'layout/header.php';
?>

<div class="admin-header">
    <div style="display: flex; align-items: center; gap: 20px;">
        <a href="orders.php" style="color: var(--mid-gray); text-decoration: none; font-size: 20px;">←</a>
        <h1 style="margin: 0;">Order Details</h1>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <?php if (isset($_GET['status_updated'])): ?>
            <span style="background: rgba(0,168,84,0.1); color: #00a854; padding: 8px 16px; border-radius: 4px; font-size: 11px; font-weight: 700; font-family: var(--f-mono);">
                <?= isset($_GET['refunded']) ? 'REFUND PROCESSED SUCCESS' : 'STATUS UPDATED SYNCED' ?>
            </span>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/order/invoice/<?= $order['order_ref'] ?>" target="_blank" style="padding: 8px 16px; border: 1.5px solid var(--ink); border-radius: 4px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-decoration: none; color: var(--ink);">PRINT INVOICE</a>
        <span class="status-badge status-<?= $order['status'] ?>" style="font-size: 12px; padding: 8px 24px;"><?= strtoupper($order['status']) ?></span>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start;">
    <!-- Order Items -->
    <div>
        <div class="panel" style="border: 1px solid var(--ink); border-radius: 8px; overflow: hidden;">
            <div class="panel-header" style="border-bottom: 1px solid var(--ink); padding: 20px 24px;">
                <div class="panel-title" style="font-family: var(--f-display); font-weight: 900; letter-spacing: -0.02em;">Purchased Items</div>
                <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);"><?= count($items) ?> Items</div>
            </div>
            <table class="admin-table">
                <thead>
                    <tr style="border-bottom: 1px solid var(--light-gray);">
                        <th style="padding: 16px 24px;">Product</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right; padding-right: 24px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr style="border-bottom: 1px solid var(--light-gray);">
                        <td style="padding: 16px 24px;">
                            <div style="font-weight: 700; color: var(--ink); text-transform: uppercase; font-size: 13px;">
                                <?= $item['product_name'] ?>
                                <?php if($item['is_preorder']): ?>
                                    <span style="color: var(--red); font-size: 10px; margin-left: 8px;">[ PRE-ORDER ]</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 10px; color: var(--mid-gray); margin-top: 4px; font-family: var(--f-mono);">PRO-ID-<?= $item['product_id'] ?></div>
                        </td>
                        <td style="text-align: center; font-family: var(--f-mono); font-weight: 700;">×<?= $item['qty'] ?></td>
                        <td style="text-align: right; font-family: var(--f-mono);">₵<?= number_format($item['unit_price_ghs'], 2) ?></td>
                        <td style="text-align: right; font-weight: 800; padding-right: 24px; font-family: var(--f-mono);">₵<?= number_format($item['unit_price_ghs'] * $item['qty'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="padding: 24px; border-top: 1px solid var(--ink); background: var(--off);">
                <div style="max-width: 320px; margin-left: auto; display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--mid-gray); font-family: var(--f-mono);">
                        <span>Subtotal</span>
                        <span>₵<?= number_format($order['subtotal_ghs'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--mid-gray); font-family: var(--f-mono);">
                        <span>Shipping</span>
                        <span>₵<?= number_format($order['shipping_ghs'], 2) ?></span>
                    </div>
                    <?php if ($order['subtotal_ghs'] > ($order['total_ghs'] - $order['shipping_ghs'])): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--red); font-family: var(--f-mono);">
                        <span>Discount</span>
                        <span>−₵<?= number_format($order['subtotal_ghs'] - ($order['total_ghs'] - $order['shipping_ghs']), 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div style="display: flex; justify-content: space-between; font-family: var(--f-display); font-weight: 900; font-size: 28px; padding-top: 16px; border-top: 2px solid var(--ink); margin-top: 8px; letter-spacing: -0.03em;">
                        <span>Total</span>
                        <span>₵<?= number_format($order['total_ghs'], 2) ?></span>
                    </div>

                    <?php if($order['is_preorder']): ?>
                    <div style="margin-top: 20px; padding: 16px; border: 1.5px dashed <?= ($order['balance_amount_ghs'] <= 0) ? '#00a854' : 'var(--red)' ?>; border-radius: 4px; background: <?= ($order['balance_amount_ghs'] <= 0) ? 'rgba(0,168,84,.02)' : 'rgba(229,0,26,.02)' ?>;">
                        <?php if ($order['balance_amount_ghs'] <= 0): ?>
                            <div style="display: flex; justify-content: space-between; font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: #00a854;">
                                <span>TOTAL PAID</span>
                                <span>₵<?= number_format($order['total_ghs'], 2) ?></span>
                            </div>
                            <div style="font-family: var(--f-mono); font-size: 9px; font-weight: 700; color: #00a854; margin-top: 4px; text-transform: uppercase;">[ FULLY PAID ]</div>
                        <?php else: ?>
                            <div style="display: flex; justify-content: space-between; font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--red);">
                                <span>DEPOSIT PAID</span>
                                <span>₵<?= number_format($order['deposit_amount_ghs'], 2) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-family: var(--f-mono); font-size: 11px; font-weight: 700; margin-top: 4px;">
                                <span>BALANCE DUE</span>
                                <span>₵<?= number_format($order['balance_amount_ghs'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Controls -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Manage Order (PRIORITIZED TOP) -->
        <div class="panel" style="border: 2px solid var(--ink); border-radius: 8px; background: var(--paper); overflow: hidden;">
            <div class="panel-header" style="border-bottom: 2px solid var(--ink); padding: 16px 24px; background: var(--ink); color: #fff;">
                <div class="panel-title" style="font-family: var(--f-display); font-weight: 900; letter-spacing: 0.05em; text-transform: uppercase; font-size: 14px;">Manage Order</div>
            </div>
            <div style="padding: 24px;">
                <form method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                    <input type="hidden" name="update_status" value="1">
                    
                    <?php if($order['is_preorder'] && $order['status'] !== 'arrived'): ?>
                        <button name="status" value="arrived" class="btn-red" style="background: #111; color: #fff; border: 2px solid #111; width: 100%; height: 48px; border-radius: 6px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer; margin-bottom: 20px;">
                            Mark as Arrived & Notify
                        </button>
                    <?php endif; ?>
                    
                    <button name="status" value="approved" class="btn-red" style="background: transparent; color: #00a854; border: 2px solid #00a854; width: 100%; height: 48px; border-radius: 6px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer;">Approve Order</button>
                    
                    <button name="status" value="processing" class="btn-red" style="background: transparent; color: #fa8c16; border: 2px solid #fa8c16; width: 100%; height: 48px; border-radius: 6px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer;">Start Processing</button>
                    
                    <button name="status" value="delivered" class="btn-red" style="background: #00a854; color: #fff; border: 2px solid #00a854; width: 100%; height: 48px; border-radius: 6px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer;">Mark as Delivered</button>
                    
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--light-gray);">
                        <button name="status" value="cancelled" class="btn-red" style="background: transparent; color: #f5222d; border: 2px solid #f5222d; width: 100%; height: 48px; border-radius: 6px; font-family: var(--f-mono); font-size: 11px; font-weight: 700; text-transform: uppercase; cursor: pointer;">Cancel Order</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Customer & Shipping -->
        <div class="panel" style="border: 1px solid var(--ink); border-radius: 8px; overflow: hidden;">
            <div class="panel-header" style="border-bottom: 1px solid var(--ink); padding: 16px 24px;">
                <div class="panel-title" style="font-family: var(--f-display); font-weight: 900;">Shipment Details</div>
            </div>
            <div style="padding: 24px;">
                <div style="margin-bottom: 24px;">
                    <div style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.1em;">Customer</div>
                    <div style="font-weight: 800; font-size: 16px; color: var(--ink);"><?= $order['customer_name'] ?></div>
                    <div style="font-size: 12px; color: var(--mid-gray); margin-top: 2px;"><?= $order['customer_email'] ?></div>
                    <div style="font-family: var(--f-mono); font-size: 11px; margin-top: 4px;"><?= $order['customer_phone'] ?: 'N/A' ?></div>
                </div>
                
                <div style="padding-top: 20px; border-top: 1px solid var(--light-gray);">
                    <div style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.1em;">Final Destination</div>
                    <div style="font-family: var(--f-body); font-size: 14px; line-height: 1.6; color: var(--ink); font-weight: 500;">
                        <?= $order['shipping_address'] ?><br>
                        <span style="font-weight: 700; text-transform: uppercase;"><?= $order['shipping_city'] ?></span>, <?= $order['shipping_region'] ?><br>
                        GHANA
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="panel" style="border: 1px solid var(--light-gray); border-radius: 8px; overflow: hidden;">
            <div style="padding: 24px;">
                <div style="display: flex; gap: 16px;">
                    <div style="position: relative;">
                        <div style="width: 10px; height: 10px; border-radius: 2px; background: var(--red);"></div>
                        <div style="position: absolute; top: 14px; left: 4px; bottom: -24px; width: 2px; background: var(--light-gray);"></div>
                    </div>
                    <div>
                        <div style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink);">Transaction Initiated</div>
                        <div style="font-size: 11px; color: var(--mid-gray); margin-top: 2px;"><?= date('M d, Y @ H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'layout/footer.php'; ?>
