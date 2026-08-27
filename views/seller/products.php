<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
    <div>
        <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">Products</h1>
        <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Manage your product listings</div>
    </div>
    <a href="<?= APP_URL ?>/seller/new-product" style="background:var(--red);color:#fff;padding:12px 24px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">+ List Product</a>
</div>

<?php if(!empty($success)): ?><div style="background:#e6f7ec;border:1.5px solid #00a854;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#00a854;margin-bottom:14px;">&#10003; <?= $success==='deleted'?'Product removed from listings.':'Product saved successfully.' ?></div><?php endif; ?>
<?php if (empty($seller['is_verified'])): ?>
<div style="background:#fff7e6;border:1.5px solid #f59e0b;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#92400e;margin-bottom:14px;">&#9888; Verification required to list products.</div>
<?php endif; ?>

<div class="seller-stats-bar">
    <div class="seller-stat-card"><div class="stat-label">Total</div><div class="stat-value"><?= (int)($stats['total_products']??0) ?></div></div>
    <div class="seller-stat-card"><div class="stat-label">Active</div><div class="stat-value" style="color:#00a854;"><?= (int)($stats['active_products']??0) ?></div></div>
    <div class="seller-stat-card"><div class="stat-label">Pending Review</div><div class="stat-value" style="color:#fa8c16;"><?= (int)($stats['pending_products']??0) ?></div></div>
</div>

<div style="border:2px solid var(--ink);overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;min-width:700px;">
        <thead>
            <tr style="background:var(--off);">
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Product</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Price</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Stock</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Type</th>
                <th style="padding:14px 20px;text-align:left;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Status</th>
                <th style="padding:14px 20px;text-align:right;font-family:var(--f-semi);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($products as $p): ?>
            <tr style="border-bottom:1px solid var(--light-gray);">
                <td style="padding:14px 20px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="<?= APP_URL ?>/<?= htmlspecialchars($p['primary_image'] ?? 'public/images/no-image.png') ?>" style="width:44px;height:44px;object-fit:cover;border:1px solid var(--light-gray);">
                        <div>
                            <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);"><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></div>
                        </div>
                    </div>
                </td>
                <td style="padding:14px 20px;font-weight:800;font-size:13px;">&#8373;<?= number_format($p['price_ghs'],2) ?></td>
                <td style="padding:14px 20px;font-size:13px;<?= ($p['stock_qty']??0)<5?'color:#f5222d;font-weight:700;':'' ?>"><?= (int)($p['stock_qty']??0) ?></td>
                <td style="padding:14px 20px;"><span style="font-family:var(--f-mono);font-size:9px;padding:3px 8px;border-radius:99px;background:var(--off);border:1px solid var(--light-gray);"><?= htmlspecialchars($p['listing_type']??'retail') ?></span></td>
                <td style="padding:14px 20px;">
                    <?php $sm=$p['status_market']??'active'; ?>
                    <span style="font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;<?= $sm==='active'?'background:#e6f7ec;color:#00a854;':($sm==='pending_review'?'background:#fff7e6;color:#fa8c16;':'background:#fff1f0;color:#f5222d;') ?>"><?= $sm ?></span>
                </td>
                <td style="padding:14px 20px;text-align:right;">
                    <a href="<?= APP_URL ?>/product/<?= htmlspecialchars($p['slug']) ?>" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-decoration:none;margin-right:12px;">View</a>
                    <?php if(!empty($seller['is_verified'])): ?>
                    <a href="<?= APP_URL ?>/seller/products/edit/<?= (int)$p['id'] ?>" style="font-family:var(--f-mono);font-size:10px;color:var(--ink);text-decoration:none;font-weight:700;margin-right:12px;">Edit</a>
                    <a href="<?= APP_URL ?>/seller/products/delete/<?= (int)$p['id'] ?>" style="font-family:var(--f-mono);font-size:10px;color:#f5222d;text-decoration:none;" onclick="return confirm('Remove this product from listings?')">Remove</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($products)): ?>
            <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--mid-gray);font-size:12px;">No products yet. <a href="<?= APP_URL ?>/seller/new-product" style="color:var(--red);">List your first product</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
