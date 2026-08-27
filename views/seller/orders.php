<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">Orders</h1>
    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Orders containing your products</div>
</div>

<div class="seller-stats-bar">
    <div class="seller-stat-card"><div class="stat-label">Total Orders</div><div class="stat-value"><?= (int)($stats['total_orders']??0) ?></div></div>
    <div class="seller-stat-card"><div class="stat-label">Gross Sales</div><div class="stat-value">&#8373;<?= number_format($stats['gross_sales']??0,2) ?></div></div>
    <div class="seller-stat-card"><div class="stat-label">Pending Payout</div><div class="stat-value">&#8373;<?= number_format($stats['pending_payout']??0,2) ?></div></div>
</div>

<div style="border:2px solid var(--ink);overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;min-width:700px;">
        <thead>
            <tr style="background:var(--off);">
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Order</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Customer</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Date</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">My Items</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">My Subtotal</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Status</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $o): ?>
            <tr style="border-bottom:1px solid var(--light-gray);">
                <td style="padding:14px 20px;">
                    <div style="font-weight:700;font-size:13px;font-family:var(--f-mono);"><?= htmlspecialchars($o['order_ref']) ?></div>
                </td>
                <td style="padding:14px 20px;">
                    <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($o['customer_name']) ?></div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);"><?= htmlspecialchars($o['customer_email']) ?></div>
                </td>
                <td style="padding:14px 20px;font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td style="padding:14px 20px;text-align:right;font-weight:700;font-size:13px;"><?= (int)$o['seller_item_count'] ?></td>
                <td style="padding:14px 20px;text-align:right;font-weight:800;font-size:13px;">&#8373;<?= number_format($o['seller_subtotal'],2) ?></td>
                <td style="padding:14px 20px;">
                    <span style="font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;<?= $o['status']==='paid'?'background:#e6f7ec;color:#00a854;':($o['status']==='processing'?'background:#fff7e6;color:#fa8c16;':($o['status']==='shipped'?'background:#e6f4ff;color:#1677ff;':($o['status']==='delivered'?'background:#e6f7ec;color:#00a854;':'background:#f0f0f0;color:#555;'))) ?>"><?= $o['status'] ?></span>
                </td>
                <td style="padding:14px 20px;text-align:right;">
                    <a href="<?= APP_URL ?>/seller/orders/<?= (int)$o['id'] ?>" style="font-family:var(--f-mono);font-size:10px;color:var(--red);text-decoration:none;font-weight:700;">View &rarr;</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($orders)): ?>
            <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--mid-gray);font-size:12px;">No orders yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
