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
    <p>Download the CSV template, fill in product fields, then upload for a preview. Category names must match an active catalogue subcategory and brand names must match a catalogue brand (case-insensitive). Optional <strong>sku</strong> column is supported for exact matching.</p>
    <a href="<?= APP_URL ?>/seller/products/import/template" class="seller-btn-secondary" style="padding:12px 20px;margin-bottom:18px;display:inline-block;text-decoration:none;">Download CSV Template</a>
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
    <p>Products will be assigned to <strong><?= htmlspecialchars($store['name'] ?? $seller['business_name']) ?></strong>.</p>
    
    <div id="asyncProgressContainer" style="display:none;margin-bottom:20px;background:#f9f9f9;border:1px solid var(--light-gray);padding:16px;border-radius:6px;">
      <div style="font-weight:700;margin-bottom:8px;" id="asyncProgressTitle">Processing Batch Import...</div>
      <div style="background:#e0e0e0;height:12px;border-radius:6px;overflow:hidden;">
        <div id="asyncProgressBar" style="width:0%;height:100%;background:var(--red);transition:width 0.2s;"></div>
      </div>
      <div style="font-size:12px;color:var(--mid-gray);margin-top:6px;" id="asyncProgressDetail">0 of <?= $validCount ?> items processed (0%)</div>
    </div>

    <form id="importForm" method="post" style="display:flex;flex-direction:column;gap:16px;margin-bottom:16px;">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="import_chunk">
      <input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('seller_product_csv_import')['key'] ?? '') ?>">
      
      <div style="background:#fff;border:1px solid var(--light-gray);padding:14px 16px;border-radius:6px;">
        <div style="font-weight:700;margin-bottom:8px;">Import Mode:</div>
        <label style="display:inline-flex;align-items:center;gap:8px;margin-right:20px;cursor:pointer;">
          <input type="radio" name="mode" value="insert" checked>
          <span><strong>Insert Only</strong> — Always create new products</span>
        </label>
        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="radio" name="mode" value="upsert">
          <span><strong>Upsert</strong> — Update existing products matching SKU or Name, otherwise create</span>
        </label>
      </div>

      <div style="display:flex;gap:10px;">
        <button id="startImportBtn" type="submit" class="seller-btn-primary" <?= $validCount?'':'disabled' ?>>Start Batch Import (<?= $validCount ?> valid rows)</button>
        <button type="submit" name="action" value="cancel" class="seller-btn-secondary">Cancel</button>
      </div>
    </form>
    <?php elseif (!$outcomes): ?><p>No rows are ready to import. Correct the CSV errors and upload it again.</p>
    <?php else: $succeeded=count(array_filter($outcomes,static fn($o)=>$o['success'])); $failed=count($outcomes)-$succeeded; ?>
    <div id="finalSummaryBox" style="padding:12px;background:#e6f7ec;margin-bottom:16px;">Import complete: <?= $succeeded ?> processed<?php if($failed): ?>, <?= $failed ?> failed during import<?php endif; ?>, <?= count($preview)-count(array_filter($preview,static fn($r)=>empty($r['errors']))) ?> skipped for validation errors.</div>
    <?php endif; ?>
    <div style="overflow:auto;margin-top:16px;"><table class="admin-table" style="width:100%;border-collapse:collapse;"><thead><tr><th>CSV row</th><th>Name</th><th>SKU</th><th>Price</th><th>Category / Brand</th><th>Validation / Result</th></tr></thead><tbody>
    <?php foreach($preview as $item): $result=$outcomes[$item['line']]??null; $messages=$item['errors']; if($result && !$result['success']) $messages[]=$result['error']; ?>
      <tr id="row-<?= (int)$item['line'] ?>">
        <td><?= (int)$item['line'] ?></td>
        <td><?= htmlspecialchars($item['row']['name']) ?></td>
        <td><?= htmlspecialchars($item['row']['sku'] ?? '—') ?></td>
        <td><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td>
        <td><?= htmlspecialchars($item['row']['category'].' / '.$item['row']['brand']) ?></td>
        <td class="result-cell"><?php if($result && $result['success']): ?><span style="color:#00a854;font-weight:700;"><?= $result['action'] === 'updated' ? 'Updated (#'.(int)$result['id'].')' : 'Imported (#'.(int)$result['id'].')' ?></span><?php elseif($messages): ?><span style="color:#d32f2f;"><?= htmlspecialchars(implode(' ',$messages)) ?></span><?php else: ?><span style="color:var(--mid-gray);">Ready to import</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('importForm');
  if (!form) return;

  const totalValid = <?= (int)$validCount ?>;
  const CHUNK_SIZE = 50;

  form.addEventListener('submit', async function(e) {
    const submitter = e.submitter;
    if (submitter && submitter.value === 'cancel') return; // Allow cancel form post
    e.preventDefault();

    const startBtn = document.getElementById('startImportBtn');
    startBtn.disabled = true;
    startBtn.textContent = 'Importing...';

    const mode = form.querySelector('input[name="mode"]:checked').value;
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    const importKey = form.querySelector('input[name="import_key"]').value;

    const progressContainer = document.getElementById('asyncProgressContainer');
    const progressBar = document.getElementById('asyncProgressBar');
    const progressDetail = document.getElementById('asyncProgressDetail');
    progressContainer.style.display = 'block';

    let offset = 0;
    let processedTotal = 0;
    let updatedTotal = 0;
    let createdTotal = 0;
    let failedTotal = 0;

    while (offset < totalValid) {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('action', 'import_chunk');
      formData.append('import_key', importKey);
      formData.append('mode', mode);
      formData.append('offset', offset);
      formData.append('limit', CHUNK_SIZE);
      formData.append('ajax', '1');

      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await response.json();

        if (!data.success) {
          alert('Import error: ' + (data.error || 'Unknown error occurred.'));
          startBtn.disabled = false;
          startBtn.textContent = 'Retry Batch Import';
          return;
        }

        // Update row status cells in UI table
        if (data.outcomes) {
          for (const line in data.outcomes) {
            const outcome = data.outcomes[line];
            const rowElem = document.getElementById('row-' + line);
            if (rowElem) {
              const resCell = rowElem.querySelector('.result-cell');
              if (resCell) {
                if (outcome.success) {
                  if (outcome.action === 'updated') {
                    updatedTotal++;
                    resCell.innerHTML = '<span style="color:#0288d1;font-weight:700;">Updated (#' + outcome.id + ')</span>';
                  } else {
                    createdTotal++;
                    resCell.innerHTML = '<span style="color:#00a854;font-weight:700;">Imported (#' + outcome.id + ')</span>';
                  }
                } else {
                  failedTotal++;
                  resCell.innerHTML = '<span style="color:#d32f2f;">' + outcome.error + '</span>';
                }
              }
            }
          }
        }

        processedTotal += (data.processed || 0);
        offset += CHUNK_SIZE;

        const percent = Math.min(100, Math.round((processedTotal / totalValid) * 100));
        progressBar.style.width = percent + '%';
        progressDetail.textContent = processedTotal + ' of ' + totalValid + ' items processed (' + percent + '%)';

        if (data.done || processedTotal >= totalValid) break;

      } catch (err) {
        console.error(err);
        alert('Network error during import chunk. Please retry.');
        startBtn.disabled = false;
        startBtn.textContent = 'Retry Batch Import';
        return;
      }
    }

    form.style.display = 'none';
    document.getElementById('asyncProgressTitle').textContent = 'Batch Import Complete!';
    progressBar.style.background = '#00a854';
    progressDetail.innerHTML = '<strong>Successfully processed ' + processedTotal + ' items:</strong> ' + createdTotal + ' created, ' + updatedTotal + ' updated' + (failedTotal ? ', ' + failedTotal + ' failed' : '') + '.';
  });
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>

