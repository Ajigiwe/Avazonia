<?php
// views/account/order_details.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$statusColors = [
    'pending'    => ['#9ca3af', 'Pending'],
    'paid'       => ['#0ea5e9', 'Paid'],
    'processing' => ['#f59e0b', 'Processing'],
    'shipped'    => ['#8b5cf6', 'Shipped'],
    'arrived'    => ['#22c55e', 'Arrived'],
    'delivered'  => ['#16a34a', 'Delivered'],
    'cancelled'  => ['#ef4444', 'Cancelled'],
    'refunded'   => ['#7c2d12', 'Refunded'],
    'paid-full'  => ['#10b981', 'Paid Full']
];

$currStatus = $order['status'] ?? 'pending';
$sColor = $statusColors[$currStatus][0] ?? '#111';
$sText  = $statusColors[$currStatus][1] ?? ucfirst($currStatus);
?>

<style>
.order-page { padding-top: 80px; padding-bottom: 80px; background: #fff; min-height: 80vh; }
.order-header { border-bottom: 1px solid var(--light-gray); padding-bottom: 32px; margin-bottom: 40px; }
.order-title { font-family: var(--f-display); font-size: clamp(32px, 8vw, 48px); font-weight: 900; letter-spacing: -.03em; margin-bottom: 16px; word-break: break-word; line-height: 1.1; }
.order-meta { display: flex; flex-wrap: wrap; gap: 20px 40px; }
.meta-item { display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
.meta-label { font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: .1em; }
.meta-val { font-family: var(--f-display); font-weight: 700; font-size: 14px; }

.order-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; }
.order-items { display: flex; flex-direction: column; gap: 24px; }
.item-card { display: flex; gap: 16px; align-items: start; border-bottom: 1px solid #f9f9f9; padding-bottom: 24px; }
.item-img { width: 80px; height: 80px; background: var(--off); border-radius: 4px; overflow: hidden; flex-shrink: 0; }
.item-img img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.item-info { flex: 1; min-width: 0; }
.item-name { font-family: var(--f-display); font-weight: 800; font-size: 15px; margin-bottom: 4px; line-height: 1.3; }
.item-ref { font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-transform: uppercase; margin-bottom: 12px; }
.item-price-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
.item-qty { font-family: var(--f-body); font-size: 12px; color: var(--mid-gray); }
.item-total { font-family: var(--f-display); font-weight: 900; font-size: 15px; }

.order-summary-card { background: var(--off); padding: 32px; border-radius: 8px; position: sticky; top: 120px; }
.sum-title { font-family: var(--f-display); font-weight: 900; font-size: 18px; text-transform: uppercase; margin-bottom: 20px; }
.sum-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.sum-row.total { border-bottom: none; padding-top: 20px; margin-top: 12px; border-top: 2px solid var(--ink); }
.sum-label { font-family: var(--f-body); font-size: 13px; color: var(--mid-gray); }
.sum-val { font-family: var(--f-display); font-weight: 700; font-size: 14px; }
.sum-total-val { font-family: var(--f-display); font-weight: 900; font-size: 24px; }

.shipping-box { margin-top: 32px; padding: 24px; background: #fdfdfd; border-radius: 4px; border: 1px solid #f0f0f0; }

@media (max-width: 900px) {
    .order-page { padding-top: 60px; padding-bottom: 60px; }
    .order-grid { grid-template-columns: 1fr; gap: 48px; }
    .order-summary-card { position: static; padding: 24px; }
    .order-header { margin-bottom: 40px; }
}

@media (max-width: 480px) {
    .item-card { gap: 12px; }
    .item-img { width: 64px; height: 64px; }
    .order-meta { gap: 16px 24px; }
}
.box-title { font-family: var(--f-mono); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--ink); border-bottom: 1px solid var(--light-gray); padding-bottom: 8px; margin-bottom: 12px; }
.box-content { font-family: var(--f-body); font-size: 14px; line-height: 1.6; color: var(--mid-gray); }

@media (max-width: 1024px) {
    .order-grid { grid-template-columns: 1fr; }
    .order-summary-card { position: static; }
}
</style>

<div class="order-page">
    <div class="container">
        <div class="order-header">
            <div style="margin-bottom: 20px;">
                <a href="<?= APP_URL ?>/orders" style="font-family:var(--f-mono); font-size:10px; color:var(--mid-gray); text-transform:uppercase; text-decoration:none;">← Back to Orders</a>
            </div>
            <h1 class="order-title">Order <span style="color:var(--red);">#<?= htmlspecialchars($order['order_ref']) ?></span></h1>
            
            <div class="order-meta">
                <div class="meta-item">
                    <span class="meta-label">Date Placed</span>
                    <span class="meta-val"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Status</span>
                    <span class="meta-val" style="color:<?= $sColor ?>;"><?= $sText ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Customer</span>
                    <span class="meta-val"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
            </div>
        </div>

        <div class="order-grid">
            <div class="order-summary-sidebar">
                <div class="order-summary-card">
                    <h2 class="sum-title">Payment Summary</h2>
                    <div class="sum-row">
                        <span class="sum-label">Subtotal</span>
                        <span class="sum-val">₵<?= number_format($order['subtotal_ghs'], 2) ?></span>
                    </div>
                    <div class="sum-row">
                        <span class="sum-label">Shipping</span>
                        <span class="sum-val">₵<?= number_format($order['shipping_ghs'], 2) ?></span>
                    </div>
                    <?php if ($order['deposit_amount_ghs'] > 0 && $order['status'] !== 'paid-full'): ?>
                        <div class="sum-row" style="color:var(--red); font-weight:700;">
                            <span class="sum-label" style="color:var(--red);">Deposit Paid</span>
                            <span class="sum-val">₵<?= number_format($order['deposit_amount_ghs'], 2) ?></span>
                        </div>
                        <div class="sum-row">
                            <span class="sum-label">Balance Due</span>
                            <span class="sum-val">₵<?= number_format($order['balance_amount_ghs'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="sum-row">
                        <span class="sum-label">Payment Method</span>
                        <span class="sum-val"><?= ($order['payment_method'] === 'pod' ? 'Pay on Delivery' : 'Online / Card') ?></span>
                    </div>
                    <div class="sum-row total">
                        <span class="sum-label" style="color:var(--ink); font-weight:900; font-size:14px;">Total</span>
                        <span class="sum-total-val">₵<?= number_format($order['total_ghs'], 2) ?></span>
                    </div>

                    <?php if ($order['status'] === 'pending'): ?>
                        <div style="margin-top:24px; padding:20px; background: #fff; border-radius:4px; text-align:center;">
                            <p style="font-size:12px; margin-bottom:12px; color:var(--mid-gray);">This order is awaiting payment.</p>
                            <a href="<?= APP_URL ?>/checkout/repay/<?= $order['id'] ?>" class="btn-red" style="width:100%; justify-content:center;">Complete Payment</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="order-items">
                <h2 class="box-title">Order Items</h2>
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-img">
                            <?php 
                            $imgUrl = $item['primary_image'] ?: 'https://via.placeholder.com/400x400';
                            if ($imgUrl && !filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                                $imgUrl = APP_URL . '/' . ltrim($imgUrl, '/');
                            }
                            ?>
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        </div>
                        <div class="item-info">
                            <h3 class="item-name"><?= htmlspecialchars($item['product_name']) ?></h3>
                            <div class="item-ref">QTY: <?= $item['qty'] ?></div>
                            <div class="item-price-row">
                                <span class="item-qty">Unit Price: ₵<?= number_format($item['unit_price_ghs'], 2) ?></span>
                                <span class="item-total">₵<?= number_format($item['unit_price_ghs'] * $item['qty'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="shipping-box">
                    <h2 class="box-title">Shipping & Logistics</h2>
                    <div class="box-content">
                        <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                        <?= htmlspecialchars($order['shipping_address']) ?><br>
                        <?= htmlspecialchars($order['shipping_city']) ?>, <?= htmlspecialchars($order['shipping_region'] ?: 'Ghana') ?><br>
                        GH +233 <?= htmlspecialchars($order['customer_phone']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
