<?php
// views/store/show.php — Storefront: African business + International supplier
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
$isIntl = ($store['seller_type'] ?? '') === 'international_supplier';
?>
<div style="max-width:1200px;margin:0 auto;padding:24px 16px;">
  <!-- Header -->
  <div style="background:<?= $isIntl ? '#0D0D0D' : '#fff' ?>;color:<?= $isIntl ? '#fff' : 'var(--ink)' ?>;border:2px solid var(--ink);padding:24px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
    <div style="width:72px;height:72px;background:#fff;border:2px solid var(--ink);display:flex;align-items:center;justify-content:center;font-size:28px;"><?= $isIntl ? '🌍' : '🏪' ?></div>
    <div style="flex:1;min-width:240px;">
      <div style="font-family:var(--f-mono);font-size:10px;letter-spacing:.08em;opacity:.7;"><?= $isIntl ? 'International Supplier · B2B / Wholesale / Export' : 'African Business · B2C & B2B' ?></div>
      <h1 style="font-family:var(--f-display);font-weight:900;font-size:28px;margin:4px 0;"><?= htmlspecialchars($store['name']) ?></h1>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
        <?= verification_badge($store) ?>
        <span style="font-family:var(--f-mono);font-size:10px;border:1px solid currentColor;padding:4px 8px;border-radius:999px;"><?= htmlspecialchars($store['country_code'] ?? 'GH') ?> <?= htmlspecialchars($store['city'] ?? '') ?></span>
        <?php if (!empty($store['seller_type'])): ?><span style="font-family:var(--f-mono);font-size:10px;padding:4px 8px;background:<?= $isIntl ? '#E8002D' : '#F4F1EC' ?>;color:<?= $isIntl ? '#fff' : 'var(--ink)' ?>;border-radius:999px;"><?= htmlspecialchars($store['seller_type']) ?></span><?php endif; ?>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <button onclick="document.getElementById('rfq-modal').style.display='block'" style="background:var(--red);color:#fff;border:none;padding:12px 20px;font-family:var(--f-semi);font-weight:800;text-transform:uppercase;cursor:pointer;">Contact / Enquiry</button>
      <a href="<?= APP_URL ?>/shop?seller=<?= (int)$store['seller_id'] ?>" style="text-align:center;border:2px solid currentColor;padding:10px 18px;font-family:var(--f-mono);font-size:11px;text-decoration:none;color:inherit;">View All Products</a>
    </div>
  </div>

  <!-- Info blocks for Intl suppliers -->
  <?php if ($isIntl): ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px;">
    <div style="border:1px solid var(--light-gray);padding:14px;"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">BUSINESS TYPE</div><div style="font-weight:700;margin-top:4px;">Manufacturer / Exporter</div></div>
    <div style="border:1px solid var(--light-gray);padding:14px;"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">MOQ</div><div style="font-weight:700;margin-top:4px;">Ask for quote</div></div>
    <div style="border:1px solid var(--light-gray);padding:14px;"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">OEM/ODM</div><div style="font-weight:700;margin-top:4px;">Available</div></div>
    <div style="border:1px solid var(--light-gray);padding:14px;"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">EXPORT MARKETS</div><div style="font-weight:700;margin-top:4px;">Africa · Global</div></div>
  </div>
  <?php endif; ?>

  <!-- Tabs -->
  <?php $tab=$_GET['tab']??'products'; ?>
  <div style="display:flex;gap:8px;margin-top:18px;border-bottom:2px solid var(--ink);padding-bottom:0;">
    <a href="?tab=products" style="padding:10px 16px;font-family:var(--f-mono);font-size:11px;font-weight:700;text-decoration:none;background:<?= $tab==='products'?'var(--ink)':'#fff' ?>;color:<?= $tab==='products'?'#fff':'var(--ink)' ?>;border:1px solid var(--ink);border-bottom:none;">Products — <?= (int)($pagination['total'] ?? count($products)) ?></a>
    <a href="?tab=about" style="padding:10px 16px;font-family:var(--f-mono);font-size:11px;font-weight:700;text-decoration:none;background:<?= $tab==='about'?'var(--ink)':'#fff' ?>;color:<?= $tab==='about'?'#fff':'var(--ink)' ?>;border:1px solid var(--ink);border-bottom:none;">About</a>
    <a href="?tab=info" style="padding:10px 16px;font-family:var(--f-mono);font-size:11px;font-weight:700;text-decoration:none;background:<?= $tab==='info'?'var(--ink)':'#fff' ?>;color:<?= $tab==='info'?'#fff':'var(--ink)' ?>;border:1px solid var(--ink);border-bottom:none;">Info · MOQ / Certs</a>
  </div>

  <?php if($tab==='about'): ?>
  <div style="padding:20px;border:1px solid var(--light-gray);border-top:none;">
    <h3 style="font-weight:800;">About <?= htmlspecialchars($store['name']) ?></h3>
    <p style="color:var(--mid-gray);line-height:1.6;"><?= nl2br(htmlspecialchars($store['description'] ?? ($isIntl ? 'Verified international supplier providing factory-direct products to African businesses. Contact for MOQ, FOB pricing, OEM/ODM.' : 'African business serving consumers and businesses on Avazonia.'))) ?></p>
    <div style="margin-top:12px;font-family:var(--f-mono);font-size:11px;">Country: <?= htmlspecialchars($store['country_code']) ?> · City: <?= htmlspecialchars($store['city'] ?? '-') ?> · Seller Type: <?= htmlspecialchars($store['seller_type']) ?></div>
  </div>
  <?php elseif($tab==='info'): ?>
  <div style="padding:20px;border:1px solid var(--light-gray);border-top:none;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
    <div><strong>Business Type</strong><br><?= htmlspecialchars($store['seller_type']) ?></div>
    <div><strong>Verification</strong><br><?= verification_badge($store) ?></div>
    <div><strong>Country</strong><br><?= htmlspecialchars($store['country_code']) ?></div>
    <div><strong>City</strong><br><?= htmlspecialchars($store['city'] ?? '-') ?></div>
    <div style="grid-column:1 / -1;"><strong>Note:</strong> For B2B export listings see product MOQ / FOB / Incoterms on each product card. Contact via Enquiry for RFQ.</div>
  </div>
  <?php else: ?>
  <div style="margin-top:18px;">
    <h2 style="font-family:var(--f-display);font-weight:800;font-size:20px;">Products — <?= (int)($pagination['total'] ?? count($products)) ?> items</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-top:16px;">
      <?php foreach ($products as $p): ?>
        <?php include __DIR__ . '/../components/product-card.php'; ?>
      <?php endforeach; ?>
      <?php if (empty($products)): ?><p style="color:var(--mid-gray);">No products yet.</p><?php endif; ?>
    </div>
    <?php if (($pagination['totalPages'] ?? 1) > 1): ?>
      <div style="display:flex;gap:8px;margin-top:18px;">
        <?php for($i=1;$i<= $pagination['totalPages'];$i++): ?>
          <a href="?tab=products&page=<?= $i ?>" style="padding:8px 12px;border:1px solid var(--ink);background:<?= $i==$pagination['page']?'var(--ink)':'#fff' ?>;color:<?= $i==$pagination['page']?'#fff':'var(--ink)' ?>;text-decoration:none;font-family:var(--f-mono);font-size:12px;"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- RFQ Modal -->
<div id="rfq-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;padding:20px;overflow:auto;">
  <div style="max-width:520px;margin:40px auto;background:#fff;border:2px solid var(--ink);padding:20px;position:relative;">
    <button onclick="this.closest('#rfq-modal').style.display='none'" style="position:absolute;right:12px;top:12px;background:none;border:none;font-size:20px;cursor:pointer;">×</button>
    <h3 style="font-family:var(--f-display);font-weight:800;">Send Enquiry to <?= htmlspecialchars($store['name']) ?></h3>
    <p style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);">For wholesale, B2B, import. Seller will reply via email/phone.</p>
    <form id="rfq-form" style="display:flex;flex-direction:column;gap:10px;margin-top:14px;">
      <input type="hidden" name="seller_id" value="<?= (int)$store['seller_id'] ?>">
      <input type="hidden" name="store_id" value="<?= (int)$store['id'] ?>">
      <label style="font-family:var(--f-mono);font-size:10px;">Quantity needed <input type="number" name="qty" value="100" min="1" style="width:100%;height:40px;border:1px solid var(--light-gray);padding:0 10px;"></label>
      <label style="font-family:var(--f-mono);font-size:10px;">Destination <input type="text" name="destination" placeholder="e.g. Tema, Ghana" style="width:100%;height:40px;border:1px solid var(--light-gray);padding:0 10px;"></label>
      <label style="font-family:var(--f-mono);font-size:10px;">Details / Specs <textarea name="specs" rows="3" placeholder="Specs, customization, budget..." style="width:100%;border:1px solid var(--light-gray);padding:10px;"></textarea></label>
      <label style="font-family:var(--f-mono);font-size:10px;">Message <textarea name="message" rows="3" placeholder="Hello, I'm interested in..." style="width:100%;border:1px solid var(--light-gray);padding:10px;"></textarea></label>
      <button type="submit" style="background:var(--ink);color:#fff;border:none;height:44px;font-weight:800;text-transform:uppercase;cursor:pointer;">Send Enquiry</button>
      <div id="rfq-msg" style="font-family:var(--f-mono);font-size:11px;"></div>
    </form>
    <script>
    document.getElementById('rfq-form')?.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const fd=new FormData(e.target);
      const r=await fetch('<?= APP_URL ?>/api/rfq',{method:'POST',body:fd});
      const j=await r.json();
      document.getElementById('rfq-msg').textContent=j.message;
      document.getElementById('rfq-msg').style.color=j.success?'#16a34a':'var(--red)';
      if(j.success) e.target.reset();
    });
    </script>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
