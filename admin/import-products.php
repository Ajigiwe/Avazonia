<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../core/ProductCsvImporter.php';
Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}
require_once __DIR__ . '/_csrf_check.php';

$db = db();
$error = '';
$preview = null;
$outcomes = [];
$sellers = $db->query("SELECT id, business_name FROM sellers WHERE is_active = 1 ORDER BY business_name")->fetchAll();

if (isset($_GET['template'])) ProductCsvImporter::sendTemplate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $action = $_POST['action'] ?? '';
    if ($action === 'preview') {
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) !== 'csv') {
            $error = 'Choose a readable .csv file to preview.';
        } else {
            try {
                $preview = ProductCsvImporter::preview($file['tmp_name'], $db);
                $key = bin2hex(random_bytes(24));
                Session::set('product_csv_import', ['key' => $key, 'preview' => $preview]);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'import' || $action === 'import_chunk') {
        $saved = Session::get('product_csv_import');
        if (!$saved || !hash_equals((string)$saved['key'], (string)($_POST['import_key'] ?? ''))) {
            $error = 'This preview has expired. Upload the CSV again.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        } else {
            $sellerId = (int)($_POST['seller_id'] ?? 0) ?: null;
            $storeId = null;
            if ($sellerId) {
                $check = $db->prepare('SELECT id FROM sellers WHERE id = ? AND is_active = 1');
                $check->execute([$sellerId]);
                if (!$check->fetchColumn()) $error = 'Choose an active seller or Avazonia Official.';
                else {
                    $store = $db->prepare('SELECT id FROM stores WHERE seller_id = ? LIMIT 1');
                    $store->execute([$sellerId]);
                    $storeId = $store->fetchColumn() ?: null;
                }
            }
            if (!$error) {
                $preview = $saved['preview'];
                $mode = in_array($_POST['mode'] ?? 'insert', ['insert', 'upsert'], true) ? $_POST['mode'] : 'insert';
                $offset = max(0, (int)($_POST['offset'] ?? 0));
                $limit = max(0, (int)($_POST['limit'] ?? 0));
                try {
                    $outcomes = ProductCsvImporter::import($db, $preview, $sellerId, $storeId, 'active', $mode, $offset, $limit);
                    $validCount = count(array_filter($preview, static fn($r) => empty($r['errors'])));
                    $processedCount = $offset + count($outcomes);
                    $isDone = ($limit === 0 || $processedCount >= $validCount);

                    if ($isDone && !$isAjax) {
                        Session::remove('product_csv_import');
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'offset' => $offset,
                            'limit' => $limit,
                            'processed' => count($outcomes),
                            'total_valid' => $validCount,
                            'done' => $isDone,
                            'outcomes' => $outcomes
                        ]);
                        exit;
                    }
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $error]);
                        exit;
                    }
                }
            } else if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }
    } elseif ($action === 'cancel') {
        Session::remove('product_csv_import');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'cancelled' => true]);
            exit;
        }
    }
}
if (!$preview && Session::get('product_csv_import')) $preview = Session::get('product_csv_import')['preview'];

$title = 'Import Products';
include 'layout/header.php';
?>
<div class="admin-header"><h1>Import Products from CSV</h1><a href="products.php" class="nav-link">← Back to Products</a></div>
<div class="panel" style="max-width:1100px;">
  <div class="panel-header"><div class="panel-title">Template &amp; Upload</div><a class="admin-btn admin-btn-secondary" href="?template=1">Download CSV Template</a></div>
  <div style="padding:24px;">
    <p>Use the template headers exactly. Category and brand values must match active catalogue names (case-insensitive). Optional <strong>sku</strong> column is supported for exact matching.</p>
    <?php if ($error): ?><div style="background:#fff1f0;color:#b91c1c;padding:12px;margin-bottom:16px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
      <?= Csrf::field() ?><input type="hidden" name="action" value="preview">
      <label>CSV file <input type="file" name="csv_file" accept=".csv,text/csv" required></label>
      <button class="admin-btn admin-btn-primary" type="submit">Upload &amp; Preview</button>
    </form>
  </div>
</div>
<?php if ($preview !== null): $validCount = count(array_filter($preview, static fn($r) => empty($r['errors']))); ?>
<div class="panel" style="max-width:1100px;">
  <div class="panel-header"><div class="panel-title">Preview · <?= count($preview) ?> rows · <?= $validCount ?> ready · <?= count($preview)-$validCount ?> need correction</div></div>
  <div style="padding:24px;">
    <?php if (!$outcomes && $validCount > 0): ?>
    
    <div id="asyncProgressContainer" style="display:none;margin-bottom:20px;background:#f9f9f9;border:1px solid #e0e0e0;padding:16px;border-radius:6px;">
      <div style="font-weight:700;margin-bottom:8px;" id="asyncProgressTitle">Processing Batch Import...</div>
      <div style="background:#e0e0e0;height:12px;border-radius:6px;overflow:hidden;">
        <div id="asyncProgressBar" style="width:0%;height:100%;background:#e8002d;transition:width 0.2s;"></div>
      </div>
      <div style="font-size:12px;color:#55514e;margin-top:6px;" id="asyncProgressDetail">0 of <?= $validCount ?> items processed (0%)</div>
    </div>

    <form id="adminImportForm" method="post" style="display:flex;flex-direction:column;gap:16px;margin-bottom:16px;">
      <?= Csrf::field() ?>
      <input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('product_csv_import')['key'] ?? '') ?>">
      
      <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <label style="flex:1;min-width:260px;">Assign imported products to seller
          <select name="seller_id" style="width:100%;margin-top:4px;padding:8px;"><option value="">Avazonia Official (default)</option><?php foreach($sellers as $seller): ?><option value="<?= (int)$seller['id'] ?>"><?= htmlspecialchars($seller['business_name']) ?></option><?php endforeach; ?></select>
        </label>
        
        <div style="flex:1;min-width:260px;">
          <div style="font-weight:600;margin-bottom:4px;">Import Mode:</div>
          <label style="display:inline-flex;align-items:center;gap:6px;margin-right:16px;cursor:pointer;">
            <input type="radio" name="mode" value="insert" checked>
            <span><strong>Insert Only</strong></span>
          </label>
          <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
            <input type="radio" name="mode" value="upsert">
            <span><strong>Upsert</strong> (Update matching SKU/Name)</span>
          </label>
        </div>
      </div>

      <div style="display:flex;gap:10px;">
        <button id="adminStartImportBtn" class="admin-btn admin-btn-primary" type="submit" name="action" value="import" <?= $validCount ? '' : 'disabled' ?>>Start Batch Import (<?= $validCount ?> valid rows)</button>
        <button class="admin-btn admin-btn-secondary" name="action" value="cancel" type="submit">Cancel</button>
      </div>
    </form>
    <?php elseif (!$outcomes): ?><p>No rows are ready to import. Correct the CSV errors and upload it again.</p>
    <?php else: $successCount = count(array_filter($outcomes, static fn($o) => $o['success'])); ?>
    <p style="padding:12px;background:#e6f7ec;margin-bottom:16px;">Import complete: <?= $successCount ?> processed, <?= count($preview)-$successCount-count(array_filter($preview, static fn($r) => !empty($r['errors']))) ?> failed during import, <?= count(array_filter($preview, static fn($r) => !empty($r['errors']))) ?> skipped for validation errors.</p>
    <?php endif; ?>
    <div style="overflow:auto;margin-top:16px;"><table class="admin-table"><thead><tr><th>CSV row</th><th>Name</th><th>SKU</th><th>Price</th><th>Category / Brand</th><th>Result</th></tr></thead><tbody>
    <?php foreach($preview as $item): $result = $outcomes[$item['line']] ?? null; $messages = $item['errors']; if ($result && !$result['success']) $messages[] = $result['error']; ?>
      <tr id="admin-row-<?= (int)$item['line'] ?>">
        <td><?= (int)$item['line'] ?></td>
        <td><?= htmlspecialchars($item['row']['name']) ?></td>
        <td><?= htmlspecialchars($item['row']['sku'] ?? '—') ?></td>
        <td><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td>
        <td><?= htmlspecialchars($item['row']['category'].' / '.$item['row']['brand']) ?></td>
        <td class="admin-result-cell"><?php if ($result && $result['success']): ?><span style="color:#00a854;font-weight:700;"><?= $result['action'] === 'updated' ? 'Updated (#'.(int)$result['id'].')' : 'Imported (#'.(int)$result['id'].')' ?></span><?php elseif ($messages): ?><span style="color:#d32f2f;"><?= htmlspecialchars(implode(' ', $messages)) ?></span><?php else: ?><span style="color:#55514e;">Ready to import</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('adminImportForm');
  if (!form) return;

  const totalValid = <?= (int)$validCount ?>;
  const CHUNK_SIZE = 50;

  form.addEventListener('submit', async function(e) {
    const submitter = e.submitter;
    if (submitter && submitter.value === 'cancel') return;
    e.preventDefault();

    const startBtn = document.getElementById('adminStartImportBtn');
    startBtn.disabled = true;
    startBtn.textContent = 'Importing...';

    const mode = form.querySelector('input[name="mode"]:checked').value;
    const sellerId = form.querySelector('select[name="seller_id"]').value;
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
      formData.append('seller_id', sellerId);
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

        if (data.outcomes) {
          for (const line in data.outcomes) {
            const outcome = data.outcomes[line];
            const rowElem = document.getElementById('admin-row-' + line);
            if (rowElem) {
              const resCell = rowElem.querySelector('.admin-result-cell');
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
<?php include 'layout/footer.php'; ?>

