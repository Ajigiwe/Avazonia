<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <a href="<?= APP_URL ?>/seller/orders" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-decoration:none;text-transform:uppercase;letter-spacing:0.1em;">&larr; Back to Orders</a>
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:8px 0 0;">Order <?= htmlspecialchars($order['order_ref']) ?></h1>
    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Placed <?= date('d M Y H:i', strtotime($order['created_at'])) ?></div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:24px;">
    <!-- Items -->
    <div>
        <div style="border:2px solid var(--ink);padding:0;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--light-gray);font-family:var(--f-display);font-weight:800;font-size:14px;">Your Items in This Order</div>
            <?php foreach($items as $idx => $item): ?>
            <div style="padding:16px 20px;border-bottom:1px solid var(--light-gray);<?= $idx===count($items)-1?'border-bottom:none;':'' ?>">
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($item['primary_image'] ?? 'public/images/no-image.png') ?>" style="width:64px;height:64px;object-fit:cover;border:1px solid var(--light-gray);">
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:14px;margin-bottom:4px;"><?= htmlspecialchars($item['product_name']) ?></div>
                        <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">Qty: <?= (int)$item['qty'] ?> &times; &#8373;<?= number_format($item['unit_price_ghs'],2) ?> = <strong style="color:var(--ink);">&#8373;<?= number_format($item['unit_price_ghs'] * $item['qty'],2) ?></strong></div>

                        <!-- Seller Status Update -->
                        <div style="margin-top:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;">Fulfillment:</span>
                            <?php
                            $curStatus = $item['seller_order_status'] ?? 'pending';
                            $statuses = ['pending','processing','shipped','delivered','cancelled'];
                            $statusColors = ['pending'=>'#fa8c16','processing'=>'#1677ff','shipped'=>'#722ed1','delivered'=>'#00a854','cancelled'=>'#f5222d'];
                            ?>
                            <form method="POST" action="<?= APP_URL ?>/seller/orders/<?= (int)$order['id'] ?>" style="display:inline;">
                                <input type="hidden" name="item_idx" value="<?= $idx ?>">
                                <select name="seller_status" onchange="this.form.submit()" style="font-family:var(--f-mono);font-size:10px;padding:4px 8px;border:1px solid var(--light-gray);border-radius:4px;background:#fff;cursor:pointer;color:<?= $statusColors[$curStatus] ?? '#333' ?>;">
                                    <?php foreach($statuses as $s): ?>
                                    <option value="<?= $s ?>" <?= $curStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Info Sidebar -->
    <div>
        <div style="border:2px solid var(--ink);padding:20px;">
            <div style="font-family:var(--f-display);font-weight:800;font-size:14px;margin-bottom:16px;">Order Details</div>

            <div style="margin-bottom:16px;">
                <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;margin-bottom:4px;">Customer</div>
                <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($order['customer_name']) ?></div>
                <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);"><?= htmlspecialchars($order['customer_email']) ?></div>
                <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);"><?= htmlspecialchars($order['customer_phone']) ?></div>
            </div>

            <div style="margin-bottom:16px;">
                <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;margin-bottom:4px;">Shipping Address</div>
                <div style="font-size:12px;line-height:1.5;"><?= htmlspecialchars($order['shipping_address'] ?? '-') ?></div>
                <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);"><?= htmlspecialchars($order['shipping_city'] ?? '') ?> <?= htmlspecialchars($order['shipping_region'] ?? '') ?></div>
            </div>

            <div style="border-top:1px solid var(--light-gray);padding-top:16px;margin-top:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:12px;"><span style="color:var(--mid-gray);">Order Status</span>
                    <span style="font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;<?= $order['status']==='paid'?'background:#e6f7ec;color:#00a854;':($order['status']==='processing'?'background:#fff7e6;color:#fa8c16;':'background:#f0f0f0;color:#555;') ?>"><?= $order['status'] ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:12px;"><span style="color:var(--mid-gray);">Payment</span><span style="font-weight:700;"><?= htmlspecialchars($order['payment_method']) ?></span></div>
                <div style="display:flex;justify-content:space-between;font-weight:800;font-size:14px;border-top:1px solid var(--light-gray);padding-top:12px;margin-top:12px;"><span>My Subtotal</span><span>&#8373;<?= number_format(array_sum(array_map(fn($i)=>$i['unit_price_ghs']*$i['qty'], $items)),2) ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
