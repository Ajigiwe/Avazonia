<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">Finances</h1>
        <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Earnings &amp; commission breakdown</div>
    </div>
    <a href="<?= APP_URL ?>/seller/finances/csv" style="border:2px solid var(--ink);color:var(--ink);padding:10px 20px;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">Export CSV</a>
</div>

<div class="seller-stats-bar">
    <div class="seller-stat-card">
        <div class="stat-label">Gross Sales</div>
        <div class="stat-value">&#8373;<?= number_format($earnings['gross_sales'] ?? 0, 2) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Platform Commission (<?= (int)($stats['commission_pct']??5) ?>%)</div>
        <div class="stat-value" style="color:var(--red);">&#8373;<?= number_format($stats['commission']??0, 2) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Net Earnings</div>
        <div class="stat-value" style="color:#00a854;">&#8373;<?= number_format($stats['net_earnings']??0, 2) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Pending Payout</div>
        <div class="stat-value">&#8373;<?= number_format($earnings['pending_payout'] ?? 0, 2) ?></div>
        <div class="stat-sub">Released on delivery</div>
    </div>
</div>

<!-- How It Works -->
<div style="background:var(--off);padding:20px 24px;border-radius:0;margin-bottom:24px;border:1px solid var(--light-gray);">
    <div style="font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;margin-bottom:8px;">How Payouts Work</div>
    <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);line-height:1.6;">
        1. Buyer pays &rarr; Order status = <strong>paid</strong> &rarr; your items show <strong>pending</strong>.<br>
        2. You ship &rarr; mark items <strong>shipped</strong>.<br>
        3. Buyer confirms delivery &rarr; order <strong>delivered</strong> &rarr; items <strong>delivered</strong> &rarr; your earnings are released.<br>
        4. Platform commission (<?= (int)($stats['commission_pct']??5) ?>%) is deducted. Net amount = your payout.
    </div>
</div>

<!-- Earnings History Table -->
<div style="border:2px solid var(--ink);overflow-x:auto;">
    <div style="padding:16px 20px;border-bottom:1px solid var(--light-gray);font-family:var(--f-display);font-weight:800;font-size:14px;">Earnings History</div>
    <table style="width:100%;border-collapse:collapse;min-width:800px;">
        <thead>
            <tr style="background:var(--off);">
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Order</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Date</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Product</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Qty</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Unit Price</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Total</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Seller Status</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Order Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $commissionPct = $stats['commission_pct'] ?? 5;
            foreach($history as $h):
                $lineTotal = (float)$h['line_total'];
                $commission = round($lineTotal * ($commissionPct / 100), 2);
                $net = round($lineTotal - $commission, 2);
                $ss = $h['seller_order_status'] ?? 'pending';
                $ssColors = ['pending'=>'#fa8c16','processing'=>'#1677ff','shipped'=>'#722ed1','delivered'=>'#00a854','cancelled'=>'#f5222d'];
            ?>
            <tr style="border-bottom:1px solid var(--light-gray);">
                <td style="padding:12px 20px;font-family:var(--f-mono);font-size:12px;font-weight:700;"><?= htmlspecialchars($h['order_ref']) ?></td>
                <td style="padding:12px 20px;font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);"><?= date('d M Y', strtotime($h['order_date'])) ?></td>
                <td style="padding:12px 20px;font-size:12px;font-weight:600;"><?= htmlspecialchars($h['product_name']) ?></td>
                <td style="padding:12px 20px;text-align:right;font-size:12px;"><?= (int)$h['qty'] ?></td>
                <td style="padding:12px 20px;text-align:right;font-family:var(--f-mono);font-size:12px;">&#8373;<?= number_format($h['unit_price_ghs'],2) ?></td>
                <td style="padding:12px 20px;text-align:right;font-weight:800;font-size:13px;">&#8373;<?= number_format($lineTotal,2) ?></td>
                <td style="padding:12px 20px;"><span style="font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;background:<?= $ssColors[$ss] ?? '#f0f0f0' ?>22;color:<?= $ssColors[$ss] ?? '#555' ?>;"><?= ucfirst($ss) ?></span></td>
                <td style="padding:12px 20px;"><span style="font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;background:#f0f0f0;color:#555;"><?= $h['order_status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($history)): ?>
            <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--mid-gray);font-size:12px;">No earnings history yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
