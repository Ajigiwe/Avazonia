<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">Seller Dashboard</h1>
    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Overview &middot; <?= date('d M Y') ?></div>
</div>

<?php if (!empty($error)): ?><div style="background:#fef3c7;border:1.5px solid #f59e0b;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#92400e;margin-bottom:14px;">&#9888; <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($_GET['success'])): ?><div style="background:#e6f7ec;border:1.5px solid #00a854;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#00a854;margin-bottom:14px;">&#10003; Changes saved successfully.</div><?php endif; ?>

<?php if (empty($seller['is_verified'])): ?>
<div style="background:var(--off);border:2px dashed var(--ink);padding:16px;margin-bottom:20px;">
    <div style="font-family:var(--f-semi);font-size:12px;font-weight:800;text-transform:uppercase;">Verification Pending</div>
    <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);margin-top:6px;line-height:1.5;">Your Ghana Card + Face ID is under review. You can list products after Admin verifies you.</div>
</div>
<?php endif; ?>

<div class="seller-stats-bar">
    <div class="seller-stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= (int)($stats['total_products']??0) ?></div>
        <div class="stat-sub"><?= (int)($stats['active_products']??0) ?> active &middot; <?= (int)($stats['pending_products']??0) ?> pending</div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= (int)($stats['total_orders']??0) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Gross Sales</div>
        <div class="stat-value">&#8373;<?= number_format($stats['gross_sales']??0, 2) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Commission (<?= (int)($stats['commission_pct']??5) ?>%)</div>
        <div class="stat-value" style="color:var(--red);">&#8373;<?= number_format($stats['commission']??0, 2) ?></div>
        <div class="stat-sub">Net: &#8373;<?= number_format($stats['net_earnings']??0, 2) ?></div>
    </div>
    <div class="seller-stat-card">
        <div class="stat-label">Pending Payout</div>
        <div class="stat-value">&#8373;<?= number_format($stats['pending_payout']??0, 2) ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Recent Products -->
    <div style="border:2px solid var(--ink);padding:0;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--light-gray);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-family:var(--f-display);font-weight:800;font-size:14px;">Recent Products</div>
            <a href="<?= APP_URL ?>/seller/products" style="font-family:var(--f-mono);font-size:10px;color:var(--red);text-decoration:none;">View All &rarr;</a>
        </div>
        <div style="padding:12px 20px;">
            <?php foreach($products as $p): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--light-gray);">
                <img src="<?= APP_URL ?>/<?= htmlspecialchars($p['primary_image'] ?? 'public/images/no-image.png') ?>" style="width:40px;height:40px;object-fit:cover;border:1px solid var(--light-gray);">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['name']) ?></div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">&#8373;<?= number_format($p['price_ghs'],2) ?></div>
                </div>
                <span style="font-family:var(--f-mono);font-size:9px;padding:3px 8px;border-radius:99px;<?= $p['status_market']==='active'?'background:#e6f7ec;color:#00a854;':($p['status_market']==='pending_review'?'background:#fff7e6;color:#fa8c16;':'background:#fff1f0;color:#f5222d;') ?>"><?= $p['status_market'] ?? 'active' ?></span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($products)): ?>
            <div style="padding:20px;text-align:center;color:var(--mid-gray);font-size:12px;">No products yet. <a href="<?= APP_URL ?>/seller/new-product" style="color:var(--red);">List your first product</a></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div style="border:2px solid var(--ink);padding:0;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--light-gray);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-family:var(--f-display);font-weight:800;font-size:14px;">Recent Orders</div>
            <a href="<?= APP_URL ?>/seller/orders" style="font-family:var(--f-mono);font-size:10px;color:var(--red);text-decoration:none;">View All &rarr;</a>
        </div>
        <div style="padding:12px 20px;">
            <?php foreach($orders as $o): ?>
            <a href="<?= APP_URL ?>/seller/orders/<?= (int)$o['id'] ?>" style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--light-gray);text-decoration:none;color:var(--ink);">
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:12px;"><?= htmlspecialchars($o['order_ref']) ?></div>
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);"><?= htmlspecialchars($o['customer_name']) ?> &middot; <?= count(explode(',',$o['seller_item_count']>$o['seller_item_count']?$o['seller_item_count']:'1')) ?> items</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:800;font-size:12px;">&#8373;<?= number_format($o['seller_subtotal'],2) ?></div>
                    <span style="font-family:var(--f-mono);font-size:9px;padding:3px 8px;border-radius:99px;<?= $o['status']==='paid'?'background:#e6f7ec;color:#00a854;':($o['status']==='processing'?'background:#fff7e6;color:#fa8c16;':'background:#f0f0f0;color:#555;') ?>"><?= $o['status'] ?></span>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if(empty($orders)): ?>
            <div style="padding:20px;text-align:center;color:var(--mid-gray);font-size:12px;">No orders yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<?php if(!empty($seller['is_verified'])): ?>
<div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
    <a href="<?= APP_URL ?>/seller/new-product" style="background:var(--red);color:#fff;padding:12px 24px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">+ List New Product</a>
    <a href="<?= APP_URL ?>/seller/finances" style="border:2px solid var(--ink);color:var(--ink);padding:12px 24px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">View Finances</a>
    <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?>" style="border:2px solid var(--ink);color:var(--ink);padding:12px 24px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">Public Store</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
