<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<div style="max-width:1200px;margin:0 auto;padding:24px 16px;">
  <h1 style="font-family:var(--f-display);font-weight:900;">Seller Dashboard — <?= htmlspecialchars($seller['business_name']) ?></h1>
  <div style="display:flex;gap:8px;margin:8px 0 16px;"><?= verification_badge($seller) ?> <span style="font-family:var(--f-mono);font-size:11px;"><?= htmlspecialchars($seller['seller_type']) ?> · <?= htmlspecialchars($seller['country_code']) ?></span></div>
  <?php if (!empty($error)): ?><div style="background:#fef3c7;border:1.5px solid #f59e0b;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#92400e;margin-bottom:14px;">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (empty($seller['is_verified'])): ?>
    <div style="background:var(--off);border:2px dashed var(--ink);padding:16px;margin-bottom:16px;">
      <div style="font-family:var(--f-semi);font-size:12px;font-weight:800;text-transform:uppercase;">⏳ Verification Pending</div>
      <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);margin-top:6px;line-height:1.5;">Your Ghana Card + Face ID is under review. Until Admin verifies you (Admin → Sellers → Verify), you <strong>cannot list or sell</strong>. Your products are hidden from buyers. You’ll be notified once verified.</div>
    </div>
  <?php endif; ?>
  <?php if($store): ?>
    <div style="border:2px solid var(--ink);padding:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
      <div><strong>Store:</strong> <?= htmlspecialchars($store['name']) ?> — <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug']) ?>">View Public Store</a></div>
      <div style="display:flex;gap:8px;align-items:center;"><?php if (!empty($seller['is_verified'])): ?><a href="<?= APP_URL ?>/seller/new-product" style="background:var(--red);color:#fff;padding:8px 14px;font-weight:800;text-transform:uppercase;text-decoration:none;">+ List Product</a><?php else: ?><span style="background:#e5e7eb;color:#6b7280;padding:8px 14px;font-family:var(--f-mono);font-size:11px;font-weight:700;border:1px solid var(--light-gray);">+ List Product — Verification Required</span><?php endif; ?><span style="font-family:var(--f-mono);font-size:10px;background:var(--off);padding:6px 10px;">Store ID <?= (int)$store['id'] ?></span></div>
    </div>
  <?php endif; ?>

  <h2 style="margin:18px 0 10px;font-family:var(--f-display);font-weight:800;">Your Products (<?= count($products) ?>)</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
    <?php foreach($products as $p){ $product=$p; include __DIR__.'/../components/product-card.php'; } ?>
    <?php if(empty($products)): ?><p style="color:var(--mid-gray);">No products yet. Add via Admin → Products (assign seller_id).</p><?php endif; ?>
  </div>

  <h2 style="margin:18px 0 10px;font-family:var(--f-display);font-weight:800;">Enquiries / RFQs (<?= count($rfqs) ?>)</h2>
  <div style="border:1px solid var(--light-gray);">
    <?php foreach($rfqs as $r): ?>
      <div style="padding:12px;border-bottom:1px solid var(--light-gray);">
        <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);"><?= htmlspecialchars($r['created_at']) ?> · <?= htmlspecialchars($r['status']) ?></div>
        <div><strong><?= htmlspecialchars($r['product_name'] ?? 'General enquiry') ?></strong> — Qty <?= (int)$r['qty'] ?> to <?= htmlspecialchars($r['destination'] ?? '-') ?></div>
        <div style="font-size:13px;margin-top:4px;"><?= nl2br(htmlspecialchars($r['message'] ?? '')) ?></div>
        <div style="font-family:var(--f-mono);font-size:11px;margin-top:4px;">Buyer: <?= htmlspecialchars($r['buyer_name']) ?> (<?= htmlspecialchars($r['buyer_email']) ?>)</div>
      </div>
    <?php endforeach; ?>
    <?php if(empty($rfqs)): ?><p style="padding:12px;color:var(--mid-gray);">No enquiries yet.</p><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
