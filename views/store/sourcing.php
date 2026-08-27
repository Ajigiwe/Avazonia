<?php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>
<div style="max-width:1200px;margin:0 auto;padding:24px 16px;">
  <div style="background:var(--ink);color:#fff;padding:28px;border:2px solid var(--ink);">
    <div style="font-family:var(--f-mono);font-size:10px;letter-spacing:.1em;opacity:.7;">AVAZONIA B2B · SOURCE FROM FACTORIES & WHOLESALERS</div>
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:36px;margin:6px 0;">SOURCE. WHOLESALE. IMPORT.</h1>
    <p style="max-width:720px;opacity:.85;">Find manufacturers, wholesalers and international suppliers. MOQ, FOB/EXW, OEM/ODM, export from China to Africa — all inside Avazonia. Free to enquire.</p>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/shop?listing_type=wholesale" style="background:var(--red);color:#fff;padding:12px 18px;font-weight:800;text-transform:uppercase;text-decoration:none;">Browse Wholesale</a>
      <a href="<?= APP_URL ?>/shop?listing_type=export" style="background:#fff;color:var(--ink);padding:12px 18px;font-weight:800;text-transform:uppercase;text-decoration:none;">Export Vehicles</a>
      <a href="<?= APP_URL ?>/seller/apply" style="border:2px solid #fff;color:#fff;padding:10px 18px;font-weight:800;text-transform:uppercase;text-decoration:none;">Become a Supplier</a>
    </div>
  </div>

  <?php if (!empty($wholesaleDeals)): ?>
  <h2 style="font-family:var(--f-display);font-weight:800;margin:24px 0 12px;">Wholesale Deals</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
    <?php foreach($wholesaleDeals as $p){ $product=$p; include __DIR__.'/../components/product-card.php'; } ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($intlStores)): ?>
  <h2 style="font-family:var(--f-display);font-weight:800;margin:24px 0 12px;">International Suppliers</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
    <?php foreach($intlStores as $st): ?>
      <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($st['slug']) ?>" style="border:2px solid var(--ink);padding:16px;display:flex;gap:12px;text-decoration:none;color:var(--ink);background:#fff;">
        <div style="width:48px;height:48px;background:var(--off);border:1px solid var(--light-gray);display:flex;align-items:center;justify-content:center;">🌍</div>
        <div><div style="font-weight:800;"><?= htmlspecialchars($st['name']) ?></div><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);"><?= htmlspecialchars($st['country_code']) ?> · <?= htmlspecialchars($st['seller_type']) ?></div><?= verification_badge($st) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($exportCars)): ?>
  <h2 style="font-family:var(--f-display);font-weight:800;margin:24px 0 12px;">International Vehicle Sourcing (FOB China)</h2>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
    <?php foreach($exportCars as $p){ $product=$p; include __DIR__.'/../components/product-card.php'; } ?>
  </div>
  <?php endif; ?>

  <div style="margin-top:24px;padding:18px;border:2px dashed var(--ink);background:var(--off);">
    <strong>How it works:</strong> 🇨🇳 Chinese Manufacturer → Avazonia B2B → 🇬🇭 Ghanaian Importer → Ghanaian Consumer. Suppliers sell to businesses, not directly to consumers.
  </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
