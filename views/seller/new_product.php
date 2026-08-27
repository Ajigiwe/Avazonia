<?php require_once __DIR__.'/../layout/head.php'; require_once __DIR__.'/../layout/nav.php'; ?>
<div style="max-width:720px;margin:0 auto;padding:24px 16px;">
  <h1 style="font-family:var(--f-display);font-weight:900;">List a Product — <?= htmlspecialchars($seller['business_name']) ?></h1>
  <p style="color:var(--mid-gray);">Free listing · <?= htmlspecialchars($seller['seller_type']) ?> · <?= htmlspecialchars($seller['verification_level']) ?></p>
  <?php if(!empty($error)): ?><div style="background:#fee;padding:10px;border:1px solid #f99;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" style="display:flex;flex-direction:column;gap:12px;margin-top:16px;">
    <?= Csrf::field() ?>
    <input type="text" name="name" placeholder="Product name" required style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <input type="number" step="0.01" name="price_ghs" placeholder="Price GHS" required style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
      <input type="number" name="stock_qty" placeholder="Stock qty" value="10" style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
    </div>
    <select name="category_id" style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
      <option value="">Select Category</option>
      <?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <select name="listing_type" style="height:44px;border:1px solid var(--light-gray);"><option value="retail">Retail</option><option value="wholesale">Wholesale (MOQ)</option><option value="rfq">RFQ</option><option value="export">Export</option></select>
      <select name="condition_type" style="height:44px;border:1px solid var(--light-gray);"><option value="new">New</option><option value="used">Used</option></select>
    </div>
    <input type="number" name="moq" placeholder="MOQ if wholesale" style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
    <textarea name="description" rows="4" placeholder="Description" style="border:1px solid var(--light-gray);padding:12px;"></textarea>
    <input type="url" name="image_url" placeholder="Image URL (https://...)" style="height:44px;border:1px solid var(--light-gray);padding:0 12px;">
    <button type="submit" style="height:48px;background:var(--ink);color:#fff;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;">Submit for Review</button>
    <p style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">Products are pending review before appearing public (free at launch).</p>
  </form>
</div>
<?php require_once __DIR__.'/../layout/footer.php'; ?>
