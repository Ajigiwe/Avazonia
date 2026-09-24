<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>
<div style="margin-bottom:28px;">
  <a href="<?= APP_URL ?>/seller/products" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-decoration:none;text-transform:uppercase;">&larr; Back to Products</a>
  <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:8px 0 0;">Import Products from CSV</h1>
</div>
<?php if(!empty($error)): ?><div style="background:#fff1f0;border:1px solid #f5222d;padding:12px 14px;margin-bottom:16px;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="seller-panel" style="max-width:1100px;margin-bottom:24px;">
  <div style="padding:20px 24px;border-bottom:1px solid var(--light-gray);font-weight:800;">Template &amp; Upload</div>
  <div style="padding:24px;">
    <p>Download the CSV template, fill in the product fields, then upload it for a preview. Category names must match an active catalogue subcategory and brand names must match a catalogue brand (case-insensitive). Leave either blank if not applicable. Images and ZIP files are not imported; add images from each product editor.</p>
    <a href="<?= APP_URL ?>/seller/products/import/template" class="seller-btn-secondary" style="padding:12px 20px;margin-bottom:18px;">Download CSV Template</a>
    <form method="post" enctype="multipart/form-data" style="display:flex;align-items:end;gap:12px;flex-wrap:wrap;">
      <?= Csrf::field() ?><input type="hidden" name="action" value="preview">
      <label style="display:block;">CSV file<br><input type="file" name="csv_file" accept=".csv,text/csv" required></label>
      <button type="submit" class="seller-btn-primary">Upload &amp; Preview</button>
    </form>
  </div>
</div>
<?php if ($preview !== null): $validCount=count(array_filter($preview,static fn($r)=>empty($r['errors']))); ?>
<div class="seller-panel" style="max-width:1100px;margin-bottom:24px;">
  <div style="padding:20px 24px;border-bottom:1px solid var(--light-gray);font-weight:800;">Preview · <?= count($preview) ?> rows · <?= $validCount ?> ready · <?= count($preview)-$validCount ?> need correction</div>
  <div style="padding:24px;">
    <?php if (!$outcomes && $validCount > 0): ?>
    <p>Products will be assigned to <strong><?= htmlspecialchars($store['name'] ?? $seller['business_name']) ?></strong>. Only valid rows will be imported.</p>
    <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;">
      <?= Csrf::field() ?><input type="hidden" name="action" value="import"><input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('seller_product_csv_import')['key'] ?? '') ?>">
      <button type="submit" class="seller-btn-primary" <?= $validCount?'':'disabled' ?>>Import <?= $validCount ?> valid rows</button>
      <button type="submit" name="action" value="cancel" class="seller-btn-secondary">Cancel</button>
    </form>
    <?php elseif (!$outcomes): ?><p>No rows are ready to import. Correct the CSV errors and upload it again.</p>
    <?php else: $succeeded=count(array_filter($outcomes,static fn($o)=>$o['success'])); $failed=count($outcomes)-$succeeded; ?>
    <div style="padding:12px;background:#e6f7ec;margin-bottom:16px;">Import complete: <?= $succeeded ?> imported<?php if($failed): ?>, <?= $failed ?> failed during import<?php endif; ?>, <?= count($preview)-count(array_filter($preview,static fn($r)=>empty($r['errors']))) ?> skipped for validation errors.</div>
    <?php endif; ?>
    <div style="overflow:auto;margin-top:16px;"><table class="admin-table" style="width:100%;border-collapse:collapse;"><thead><tr><th>CSV row</th><th>Name</th><th>Price</th><th>Category / Brand</th><th>Validation / Result</th></tr></thead><tbody>
    <?php foreach($preview as $item): $result=$outcomes[$item['line']]??null; $messages=$item['errors']; if($result && !$result['success']) $messages[]=$result['error']; ?>
      <tr><td><?= (int)$item['line'] ?></td><td><?= htmlspecialchars($item['row']['name']) ?></td><td><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td><td><?= htmlspecialchars($item['row']['category'].' / '.$item['row']['brand']) ?></td><td><?php if($result && $result['success']): ?>Imported<?php elseif($messages): ?><?= htmlspecialchars(implode(' ',$messages)) ?><?php else: ?>Ready to import<?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
