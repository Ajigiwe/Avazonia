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
    } elseif ($action === 'import') {
        $saved = Session::get('product_csv_import');
        if (!$saved || !hash_equals((string)$saved['key'], (string)($_POST['import_key'] ?? ''))) {
            $error = 'This preview has expired. Upload the CSV again.';
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
                try {
                    $outcomes = ProductCsvImporter::import($db, $saved['preview'], $sellerId, $storeId, 'active');
                    $preview = $saved['preview'];
                    Session::remove('product_csv_import');
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
    } elseif ($action === 'cancel') {
        Session::remove('product_csv_import');
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
    <p>Use the template headers exactly. Category and brand values must match active catalogue names (case-insensitive). Leave either blank when not applicable. Images and ZIP files are not imported; add images from each product editor.</p>
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
    <form method="post">
      <?= Csrf::field() ?><input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('product_csv_import')['key'] ?? '') ?>">
      <label style="display:block;margin-bottom:16px;">Assign imported products to seller
        <select name="seller_id"><option value="">Avazonia Official (default)</option><?php foreach($sellers as $seller): ?><option value="<?= (int)$seller['id'] ?>"><?= htmlspecialchars($seller['business_name']) ?></option><?php endforeach; ?></select>
      </label>
      <button class="admin-btn admin-btn-primary" type="submit" name="action" value="import" <?= $validCount ? '' : 'disabled' ?>>Import <?= $validCount ?> valid rows</button>
      <button class="admin-btn admin-btn-secondary" name="action" value="cancel" type="submit">Cancel</button>
    </form>
    <?php elseif (!$outcomes): ?><p>No rows are ready to import. Correct the CSV errors and upload it again.</p>
    <?php else: $successCount = count(array_filter($outcomes, static fn($o) => $o['success'])); ?>
    <p>Import complete: <?= $successCount ?> imported, <?= count($preview)-$successCount-count(array_filter($preview, static fn($r) => !empty($r['errors']))) ?> failed during import, <?= count(array_filter($preview, static fn($r) => !empty($r['errors']))) ?> skipped for validation errors.</p>
    <?php endif; ?>
    <div style="overflow:auto;margin-top:16px;"><table class="admin-table"><thead><tr><th>CSV row</th><th>Name</th><th>Price</th><th>Category / Brand</th><th>Result</th></tr></thead><tbody>
    <?php foreach($preview as $item): $result = $outcomes[$item['line']] ?? null; $messages = $item['errors']; if ($result && !$result['success']) $messages[] = $result['error']; ?>
      <tr><td><?= (int)$item['line'] ?></td><td><?= htmlspecialchars($item['row']['name']) ?></td><td><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td><td><?= htmlspecialchars($item['row']['category'].' / '.$item['row']['brand']) ?></td><td><?php if ($result && $result['success']): ?>Imported (#<?= (int)$result['id'] ?>)<?php elseif ($messages): ?><?= htmlspecialchars(implode(' ', $messages)) ?><?php else: ?>Ready to import<?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>
<?php endif; ?>
<?php include 'layout/footer.php'; ?>
