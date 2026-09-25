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

if (isset($_GET['template'])) {
    $format = ($_GET['format'] ?? 'excel') === 'csv' ? 'csv' : 'excel';
    ProductCsvImporter::sendTemplate($db, $format);
}

$lookups = ProductCsvImporter::getLookupOptions($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $action = $_POST['action'] ?? '';
    if ($action === 'preview') {
        $file = $_FILES['csv_file'] ?? null;
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !in_array($ext, ['csv', 'xlsx'], true)) {
            $error = 'Choose a readable .csv or .xlsx spreadsheet file to preview.';
        } else {
            try {
                $result = ProductCsvImporter::preview($file['tmp_name'], $db, $file['name'] ?? '');
                $preview = $result['preview'];
                $savedLookups = $result['lookups'];
                $key = bin2hex(random_bytes(24));
                Session::set('product_csv_import', ['key' => $key, 'preview' => $preview, 'lookups' => $savedLookups]);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'import' || $action === 'import_chunk') {
        $saved = Session::get('product_csv_import');
        if (!$saved || !hash_equals((string)$saved['key'], (string)($_POST['import_key'] ?? ''))) {
            $error = 'This preview has expired. Upload the spreadsheet again.';
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
                $overrides = [];
                if (!empty($_POST['overrides'])) {
                    $rawOverrides = is_string($_POST['overrides']) ? json_decode($_POST['overrides'], true) : $_POST['overrides'];
                    if (is_array($rawOverrides)) $overrides = $rawOverrides;
                }
                try {
                    $outcomes = ProductCsvImporter::import($db, $preview, $sellerId, $storeId, 'pending_review', $mode, $offset, $limit, $overrides);
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
<style>
.admin-import-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 28px;
  flex-wrap: wrap;
  gap: 16px;
}
.admin-import-title {
  font-family: var(--f-display, 'Outfit', sans-serif);
  font-weight: 900;
  font-size: clamp(24px, 4vw, 32px);
  margin: 0;
  color: #0D0D0D;
}
.admin-back-btn {
  font-family: var(--f-mono, monospace);
  font-size: 11px;
  font-weight: 700;
  color: #55514E;
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 8px 16px;
  background: #FFF;
  border: 1px solid #E8E5DF;
  border-radius: 6px;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.admin-back-btn:hover {
  border-color: #0D0D0D;
  color: #0D0D0D;
  background: #F4F1EC;
}

.admin-steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}
.admin-step-card {
  background: #FFFFFF;
  border: 1px solid #E8E5DF;
  border-radius: 12px;
  padding: 28px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.03);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.2s ease;
}
.admin-step-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}
.admin-step-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--f-mono, monospace);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #E8002D;
  background: rgba(232,0,45,0.08);
  padding: 5px 12px;
  border-radius: 20px;
  margin-bottom: 14px;
  width: fit-content;
}

.admin-dropzone {
  border: 2px dashed #D0D5DD;
  border-radius: 12px;
  background: #FAFAFC;
  padding: 32px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
}
.admin-dropzone:hover, .admin-dropzone.dragover {
  border-color: #E8002D;
  background: #FFF5F6;
  box-shadow: 0 6px 20px rgba(232,0,45,0.08);
}
.admin-dropzone-icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(232,0,45,0.08);
  color: #E8002D;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
  transition: transform 0.2s ease;
}
.admin-dropzone:hover .admin-dropzone-icon {
  transform: translateY(-3px) scale(1.05);
}

.admin-file-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 8px;
  padding: 12px 16px;
}

.admin-mode-card {
  border: 2px solid #E8E5DF;
  border-radius: 10px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #FFF;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.admin-mode-card:hover {
  border-color: #0D0D0D;
}
.admin-mode-card.active {
  border-color: #E8002D;
  background: #FFF5F6;
}
.admin-mode-card input[type="radio"] {
  accent-color: #E8002D;
  margin-top: 2px;
  width: 18px;
  height: 18px;
}
</style>

<div class="admin-import-header">
  <div>
    <div style="font-family:var(--f-mono, monospace);font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:#55514E;margin-bottom:4px;">Catalogue Management</div>
    <h1 class="admin-import-title">Import Products from CSV</h1>
  </div>
  <a href="products.php" class="admin-back-btn">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to Products
  </a>
</div>

<?php if ($error): ?>
<div style="background:#FFF1F0;border:1px solid #FFA39E;border-radius:8px;padding:16px 20px;margin-bottom:24px;color:#CF1322;display:flex;align-items:center;gap:12px;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <span style="font-weight:600;font-size:14px;"><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<!-- Step 1 & Step 2 Setup Cards -->
<div class="admin-steps-grid">
  <!-- Step 1 Card -->
  <div class="admin-step-card">
    <div>
      <div class="admin-step-badge">Step 1 · Template Setup</div>
      <h3 style="font-family:var(--f-display, sans-serif);font-weight:800;font-size:18px;margin:0 0 10px;color:#0D0D0D;">Download Template Spreadsheet</h3>
      <p style="font-size:14px;line-height:1.55;color:#55514E;margin-bottom:20px;">Download our official pre-formatted template. Choose <strong>Excel (.xlsx)</strong> to get native in-cell dropdown menus for categories, subcategories, and brands, or download plain CSV.</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a class="admin-btn admin-btn-primary" href="?template=1&format=excel" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;padding:12px 20px;background:#107C41;border-color:#107C41;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Download Excel (.xlsx) with Dropdowns
      </a>
      <a class="admin-btn admin-btn-secondary" href="?template=1&format=csv" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;padding:10px 16px;font-size:13px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download Standard CSV Template
      </a>
    </div>
  </div>

  <!-- Step 2 Card -->
  <div class="admin-step-card">
    <form method="post" enctype="multipart/form-data" id="adminPreviewForm" style="display:flex;flex-direction:column;height:100%;justify-content:space-between;">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="preview">

      <div>
        <div class="admin-step-badge">Step 2 · Upload &amp; Preview</div>
        <h3 style="font-family:var(--f-display, sans-serif);font-weight:800;font-size:18px;margin:0 0 10px;color:#0D0D0D;">Upload Catalogue File</h3>

        <div class="admin-dropzone" id="adminDropzone" onclick="document.getElementById('adminCsvFileInput').click();">
          <input type="file" name="csv_file" id="adminCsvFileInput" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required style="display:none;" onchange="handleAdminFileSelected(this)">
          <div id="adminDropzonePrompt">
            <div class="admin-dropzone-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div style="font-weight:700;font-size:15px;color:#0D0D0D;margin-bottom:4px;">Drag &amp; drop Excel (.xlsx) or CSV file</div>
            <div style="font-size:13px;color:#55514E;">or <span style="color:#E8002D;font-weight:700;text-decoration:underline;">browse from computer</span></div>
          </div>
          <div id="adminFileSelectedArea" style="display:none;">
            <div class="admin-file-selected">
              <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:20px;">📊</span>
                <div style="text-align:left;">
                  <div style="font-weight:700;font-size:14px;color:#166534;" id="adminFileNameDisp">file.xlsx</div>
                  <div style="font-size:12px;color:#15803D;" id="adminFileSizeDisp">0 KB</div>
                </div>
              </div>
              <span style="font-size:12px;color:#E8002D;font-weight:700;">Change</span>
            </div>
          </div>
        </div>
      </div>

      <button class="admin-btn admin-btn-primary" type="submit" style="margin-top:20px;width:100%;justify-content:center;padding:14px;">
        Upload &amp; Generate Preview →
      </button>
    </form>
  </div>
</div>
<?php if ($preview !== null):
  $allCats = array_unique(array_merge($lookups['main_categories'] ?? [], $lookups['sub_categories'] ?? []));
  sort($allCats);
  $allBrands = $lookups['brands'] ?? [];
  $validCount = count(array_filter($preview, static fn($r) => empty($r['errors'])));
?>

<datalist id="adminCategoryDatalist">
  <?php foreach($allCats as $catName): ?>
    <option value="<?= htmlspecialchars($catName) ?>">
  <?php endforeach; ?>
</datalist>

<datalist id="adminBrandDatalist">
  <?php foreach($allBrands as $brandName): ?>
    <option value="<?= htmlspecialchars($brandName) ?>">
  <?php endforeach; ?>
</datalist>

<div class="panel" style="max-width:1100px;margin-bottom:32px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.05);overflow:hidden;background:#FFF;border:1px solid #E8E5DF;">
  <div style="padding:22px 28px;border-bottom:1px solid #E8E5DF;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#FAFAFC;">
    <div>
      <div style="font-family:var(--f-mono, monospace);font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#55514E;">Step 3 · Verification &amp; Web Grid Editor</div>
      <div style="font-family:var(--f-display, sans-serif);font-weight:900;font-size:20px;color:#0D0D0D;margin-top:2px;">
        Spreadsheet Data Preview &amp; Search Autocomplete
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <span style="background:#FFF;border:1px solid #E8E5DF;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;color:#0D0D0D;"><?= count($preview) ?> Total Rows</span>
      <span style="background:#E6F7ED;border:1px solid #B7EB8F;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;color:#276749;"><?= $validCount ?> Valid</span>
    </div>
  </div>

  <div style="padding:28px;">
    <?php if (!$outcomes): ?>
    
    <div id="asyncProgressContainer" style="display:none;margin-bottom:24px;background:#FAFAFC;border:1px solid #E8E5DF;padding:20px;border-radius:10px;">
      <div style="font-family:var(--f-display, sans-serif);font-weight:800;font-size:16px;margin-bottom:10px;color:#0D0D0D;" id="asyncProgressTitle">Importing Product Chunks...</div>
      <div style="background:#E8E5DF;height:12px;border-radius:6px;overflow:hidden;">
        <div id="asyncProgressBar" style="width:0%;height:100%;background:#E8002D;transition:width 0.25s ease;"></div>
      </div>
      <div style="font-size:13px;color:#55514E;margin-top:8px;font-weight:600;" id="asyncProgressDetail">0 items processed (0%)</div>
    </div>

    <form id="adminImportForm" method="post" style="display:flex;flex-direction:column;gap:20px;margin-bottom:24px;">
      <?= Csrf::field() ?>
      <input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('product_csv_import')['key'] ?? '') ?>">
      
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:20px;">
        <div style="background:#FAFAFC;border:1px solid #E8E5DF;padding:18px;border-radius:10px;">
          <label style="font-weight:700;font-size:14px;color:#0D0D0D;display:block;margin-bottom:6px;">Assign Products to Seller:</label>
          <select name="seller_id" style="width:100%;padding:10px;border:1px solid #E8E5DF;border-radius:6px;font-size:14px;">
            <option value="">Avazonia Official (default)</option>
            <?php foreach($sellers as $seller): ?>
              <option value="<?= (int)$seller['id'] ?>"><?= htmlspecialchars($seller['business_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="background:#FAFAFC;border:1px solid #E8E5DF;padding:18px;border-radius:10px;">
          <div style="font-weight:700;font-size:14px;color:#0D0D0D;margin-bottom:8px;">Import Mode:</div>
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <label class="admin-mode-card active" id="adminModeCardInsert" onclick="selectAdminModeCard('insert')" style="flex:1;min-width:180px;">
              <input type="radio" name="mode" value="insert" checked onclick="selectAdminModeCard('insert')">
              <div>
                <div style="font-weight:800;font-size:13px;color:#0D0D0D;">Insert Only</div>
                <div style="font-size:11px;color:#55514E;">Always create new catalog entries.</div>
              </div>
            </label>

            <label class="admin-mode-card" id="adminModeCardUpsert" onclick="selectAdminModeCard('upsert')" style="flex:1;min-width:180px;">
              <input type="radio" name="mode" value="upsert" onclick="selectAdminModeCard('upsert')">
              <div>
                <div style="font-weight:800;font-size:13px;color:#0D0D0D;">Smart Upsert</div>
                <div style="font-size:11px;color:#55514E;">Update matching SKU/Name.</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <button id="adminStartImportBtn" class="admin-btn admin-btn-primary" type="submit" name="action" value="import" style="padding:14px 28px;">
          Execute Batch Import
        </button>
        <button class="admin-btn admin-btn-secondary" name="action" value="cancel" type="submit" style="padding:14px 24px;">Cancel</button>
      </div>
    </form>
    <?php else: $successCount = count(array_filter($outcomes, static fn($o) => $o['success'])); ?>
    <div id="finalSummaryBox" style="padding:16px 20px;background:#E6F7ED;border:1px solid #B7EB8F;border-radius:8px;margin-bottom:24px;color:#276749;font-weight:700;font-size:15px;display:flex;align-items:center;gap:12px;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>Import Complete! Successfully processed <?= $successCount ?> items.</span>
    </div>
    <?php endif; ?>

    <div style="overflow-x:auto;border:1px solid #E8E5DF;border-radius:8px;">
      <table class="admin-table" style="width:100%;border-collapse:collapse;text-align:left;">
        <thead>
          <tr style="background:#FAFAFC;border-bottom:1px solid #E8E5DF;">
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:36px;"></th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:40px;">Row</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;">Product Name</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:110px;">SKU</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:100px;">Price</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;min-width:180px;">Category</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;min-width:150px;">Brand</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:70px;">Images</th>
            <th style="padding:14px 12px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;">Result</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($preview as $item):
          $lineNum = (int)$item['line'];
          $result = $outcomes[$lineNum] ?? null; 
          $messages = $item['errors']; 
          if ($result && !$result['success']) $messages[] = $result['error']; 
          $matchedCat = $item['row']['sub_category'] !== '' ? $item['row']['sub_category'] : $item['row']['category'];
          $matchedBrand = $item['row']['brand'];
          $rowDesc = htmlspecialchars($item['row']['description'] ?? '');
          $rowFeatures = htmlspecialchars($item['row']['features'] ?? '');
          $rowSpecs = htmlspecialchars($item['row']['specs'] ?? '');
          $rowListingType = htmlspecialchars($item['values']['listing_type'] ?? 'retail');
          $rowCondition = htmlspecialchars($item['values']['condition_type'] ?? 'new');
          $rowVisibility = htmlspecialchars($item['values']['visibility'] ?? 'public');
          $rowCountry = htmlspecialchars($item['values']['location_country'] ?? 'GH');
          $rowStock = htmlspecialchars($item['values']['stock_qty'] ?? '0');
          $rowMoq = htmlspecialchars($item['values']['moq'] ?? '');
          $rowWholesale = htmlspecialchars($item['values']['wholesale_price_ghs'] ?? '');
          $rowTags = htmlspecialchars($item['row']['tags'] ?? '');
        ?>
          <!-- Main summary row -->
          <tr id="admin-row-<?= $lineNum ?>" class="import-main-row" style="border-bottom:1px solid #F0EDE8;cursor:pointer;" onclick="document.getElementById('expand-icon-<?= $lineNum ?>').click();">
            <td style="padding:10px 12px;text-align:center;">
              <button type="button" id="expand-icon-<?= $lineNum ?>" onclick="const d = document.getElementById('detail-row-<?= $lineNum ?>'); const open = d.style.display !== 'none'; d.style.display = open ? 'none' : 'table-row'; this.style.transform = open ? 'rotate(0deg)' : 'rotate(90deg)'; event.stopPropagation();" style="background:none;border:none;cursor:pointer;display:inline-block;transition:transform .2s;font-size:14px;color:#55514E;padding:4px;outline:none;">▶</button>
            </td>
            <td style="padding:10px 12px;font-weight:700;color:#55514E;font-size:13px;"><?= $lineNum ?></td>
            <td style="padding:10px 12px;font-weight:700;color:#0D0D0D;font-size:13px;"><?= htmlspecialchars($item['row']['name']) ?></td>
            <td style="padding:10px 12px;font-family:var(--f-mono, monospace);font-size:11px;color:#55514E;"><?= htmlspecialchars($item['row']['sku'] ?? '—') ?></td>
            <td style="padding:10px 12px;font-weight:700;font-size:13px;"><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td>
            
            <td style="padding:8px 10px;" onclick="event.stopPropagation()">
              <input type="text" 
                     class="admin-cat-input" 
                     data-line="<?= $lineNum ?>" 
                     list="adminCategoryDatalist" 
                     value="<?= htmlspecialchars($matchedCat) ?>" 
                     placeholder="Search category..." 
                     oninput="onAdminGridInput(this, 'category')"
                     style="width:100%;padding:7px 10px;border-radius:6px;border:1.5px solid <?= empty($item['values']['category_id']) ? '#EAB308' : '#D1D5DB' ?>;font-size:12px;background:<?= empty($item['values']['category_id']) ? '#FEFCE8' : '#FFF' ?>;font-weight:600;color:#0D0D0D;">
            </td>

            <td style="padding:8px 10px;" onclick="event.stopPropagation()">
              <input type="text" 
                     class="admin-brand-input" 
                     data-line="<?= $lineNum ?>" 
                     list="adminBrandDatalist" 
                     value="<?= htmlspecialchars($matchedBrand) ?>" 
                     placeholder="Search brand..." 
                     oninput="onAdminGridInput(this, 'brand')"
                     style="width:100%;padding:7px 10px;border-radius:6px;border:1.5px solid <?= empty($item['values']['brand_id']) && !empty($matchedBrand) ? '#EAB308' : '#D1D5DB' ?>;font-size:12px;background:<?= empty($item['values']['brand_id']) && !empty($matchedBrand) ? '#FEFCE8' : '#FFF' ?>;font-weight:600;color:#0D0D0D;">
            </td>

            <td style="padding:10px 12px;" onclick="event.stopPropagation()">
              <span id="img-count-<?= $lineNum ?>" style="background:#F0EAFF;color:#6D28D9;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:800;">0</span>
            </td>

            <td class="admin-result-cell" style="padding:10px 12px;">
              <?php if ($result && $result['success']): ?>
                <span style="background:#E6F7ED;color:#276749;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:800;"><?= $result['action'] === 'updated' ? 'Updated (#' . (int)$result['id'] . ')' : 'Imported (#' . (int)$result['id'] . ')' ?></span>
              <?php elseif ($messages): ?>
                <span style="background:#FFF1F0;color:#CF1322;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:700;" class="msg-span"><?= htmlspecialchars(implode(' ', $messages)) ?></span>
              <?php else: ?>
                <span style="background:#FAFAFC;color:#55514E;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:700;" class="msg-span">Ready</span>
              <?php endif; ?>
            </td>
          </tr>

          <!-- Expandable detail row -->
          <tr id="detail-row-<?= $lineNum ?>" style="display:none;border-bottom:2px solid #E8E5DF;">
            <td colspan="9" style="padding:0;">
              <div style="background:#FAFAFC;padding:20px 24px;border-top:1px dashed #D0D5DD;">
                <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;">
                  <!-- Left: All fields -->
                  <div>
                    <div style="font-family:var(--f-mono,monospace);font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#E8002D;font-weight:700;margin-bottom:12px;">Full Field Inspection — Row <?= $lineNum ?></div>
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px,1fr));gap:10px;margin-bottom:16px;">
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Listing Type</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowListingType ?></div>
                      </div>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Condition</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowCondition ?></div>
                      </div>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Visibility</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowVisibility ?></div>
                      </div>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Country</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowCountry === 'CN' ? '🇨🇳 China' : ($rowCountry === 'GH' ? '🇬🇭 Ghana' : $rowCountry) ?></div>
                      </div>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Stock</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowStock ?></div>
                      </div>
                      <?php if ($rowMoq): ?>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">MOQ</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowMoq ?></div>
                      </div>
                      <?php endif; ?>
                      <?php if ($rowWholesale): ?>
                      <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:3px;">Wholesale Price</div>
                        <div style="font-weight:700;font-size:13px;color:#0D0D0D;"><?= $rowWholesale ?></div>
                      </div>
                      <?php endif; ?>
                    </div>

                    <?php if ($rowTags): ?>
                    <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;margin-bottom:10px;">
                      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px;">Tags</div>
                      <div style="font-size:13px;color:#0D0D0D;"><?= $rowTags ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($rowDesc): ?>
                    <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;margin-bottom:10px;">
                      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px;">Description</div>
                      <div style="font-size:13px;color:#333;line-height:1.5;max-height:120px;overflow-y:auto;"><?= nl2br($rowDesc) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($rowFeatures): ?>
                    <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;margin-bottom:10px;">
                      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px;">Features</div>
                      <div style="font-size:13px;color:#333;line-height:1.6;">
                        <?php foreach(preg_split('/[|;]+/', $rowFeatures) as $feat):
                          $feat = trim($feat); if(!$feat) continue;
                        ?>
                          <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                            <span style="color:#16a34a;font-size:14px;">✓</span>
                            <span><?= htmlspecialchars($feat) ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($rowSpecs): ?>
                    <div style="background:#FFF;border:1px solid #E8E5DF;border-radius:8px;padding:10px 14px;margin-bottom:10px;">
                      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px;">Specifications</div>
                      <div style="font-size:13px;color:#333;">
                        <?php foreach(preg_split('/[|;]+/', $rowSpecs) as $spec):
                          $spec = trim($spec); if(!$spec) continue;
                          $parts = explode(':', $spec, 2);
                        ?>
                          <div style="display:flex;gap:8px;margin-bottom:3px;padding:3px 0;border-bottom:1px dotted #eee;">
                            <span style="font-weight:700;min-width:100px;color:#555;"><?= htmlspecialchars(trim($parts[0])) ?></span>
                            <span><?= htmlspecialchars(trim($parts[1] ?? '')) ?></span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$rowDesc && !$rowFeatures && !$rowSpecs && !$rowTags): ?>
                    <div style="padding:20px;text-align:center;color:#888;font-size:13px;font-style:italic;">No description, features, specs or tags provided for this product.</div>
                    <?php endif; ?>
                  </div>

                  <!-- Right: Media upload -->
                  <div>
                    <!-- Images -->
                    <div style="font-family:var(--f-mono,monospace);font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#6D28D9;font-weight:700;margin-bottom:10px;">Product Images</div>
                    
                    <div id="img-preview-<?= $lineNum ?>" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;"></div>
                    
                    <div class="import-img-dropzone" 
                         id="img-dropzone-<?= $lineNum ?>"
                         ondragover="event.preventDefault();this.style.borderColor='#6D28D9';this.style.background='#F5F3FF'"
                         ondragleave="this.style.borderColor='#D0D5DD';this.style.background='#FAFAFC'"
                         ondrop="event.preventDefault();this.style.borderColor='#D0D5DD';this.style.background='#FAFAFC';handleImportImageDrop(event,<?= $lineNum ?>)"
                         style="position:relative;border:2px dashed #D0D5DD;border-radius:10px;background:#FAFAFC;padding:16px;text-align:center;cursor:pointer;transition:all .2s;margin-bottom:6px;overflow:hidden;">
                      <input type="file" id="img-input-<?= $lineNum ?>" accept="image/*" multiple style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2;" onchange="handleImportImageSelect(this,<?= $lineNum ?>)">
                      <div style="font-size:20px;margin-bottom:4px;position:relative;z-index:1;">📸</div>
                      <div style="font-size:11px;font-weight:700;color:#555;position:relative;z-index:1;">Drop images or click</div>
                      <div style="font-size:9px;color:#888;margin-top:2px;position:relative;z-index:1;">JPEG, PNG, WebP</div>
                    </div>
                    <div id="img-status-<?= $lineNum ?>" style="font-size:11px;color:#888;margin-bottom:14px;text-align:center;min-height:16px;"></div>

                    <!-- Video -->
                    <div style="font-family:var(--f-mono,monospace);font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#6D28D9;font-weight:700;margin-bottom:6px;">Product Video</div>
                    
                    <input type="text" class="override-input override-video" data-line="<?= $lineNum ?>" id="video-url-<?= $lineNum ?>" placeholder="Or paste YouTube/Vimeo link here" style="width:100%;font-size:11px;padding:6px;border:1px solid #D0D5DD;border-radius:6px;margin-bottom:6px;">

                    <div class="import-img-dropzone" 
                         id="vid-dropzone-<?= $lineNum ?>"
                         ondragover="event.preventDefault();this.style.borderColor='#6D28D9';this.style.background='#F5F3FF'"
                         ondragleave="this.style.borderColor='#D0D5DD';this.style.background='#FAFAFC'"
                         ondrop="event.preventDefault();this.style.borderColor='#D0D5DD';this.style.background='#FAFAFC';handleImportVideoDrop(event,<?= $lineNum ?>)"
                         style="position:relative;border:2px dashed #D0D5DD;border-radius:10px;background:#FAFAFC;padding:12px;text-align:center;cursor:pointer;transition:all .2s;overflow:hidden;">
                      <input type="file" id="vid-input-<?= $lineNum ?>" accept="video/*" style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2;" onchange="handleImportVideoSelect(this,<?= $lineNum ?>)">
                      <div style="font-size:18px;margin-bottom:2px;position:relative;z-index:1;">🎥</div>
                      <div style="font-size:11px;font-weight:700;color:#555;position:relative;z-index:1;">Upload Video</div>
                      <div style="font-size:9px;color:#888;margin-top:2px;position:relative;z-index:1;">MP4, WebM (1 allowed)</div>
                    </div>
                    <div id="vid-status-<?= $lineNum ?>" style="font-size:11px;color:#888;margin-top:6px;text-align:center;min-height:16px;"></div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
.import-main-row:hover { background: #FAFAFC; }
.import-main-row td { vertical-align: middle; }
.import-img-dropzone:hover { border-color: #6D28D9 !important; background: #F5F3FF !important; }
</style>

<script>
window.validAdminCatsLower = <?= json_encode(array_values(array_map('strtolower', $allCats ?? []))) ?>;
window.validAdminBrandsLower = <?= json_encode(array_values(array_map('strtolower', $allBrands ?? []))) ?>;
window.csrfTokenGlobal = '<?= htmlspecialchars(Csrf::getToken()) ?>';

/* Track uploaded images per line */
window.importImagesByLine = window.importImagesByLine || {};

// toggleDetailRow has been moved to inline JS for bulletproof reliability

window.uploadSingleImage = async function(file, lineNum) {
  const fd = new FormData();
  fd.append('image', file);
  fd.append('line', lineNum);
  fd.append('csrf_token', window.csrfTokenGlobal);
  try {
    const res = await fetch('api/upload-import-image.php', { method: 'POST', body: fd });
    let data;
    try {
      const text = await res.text();
      try {
        data = JSON.parse(text);
      } catch(e) {
        console.error('Non-JSON response (Status ' + res.status + '):', text);
        return { ok: false, error: 'HTTP ' + res.status + ' ' + res.statusText + (text ? ': ' + text.substring(0, 40) : ' (empty body)') };
      }
    } catch(e) {
      return { ok: false, error: 'Server error (failed to read response)' };
    }
    
    if (data.success) {
      if (!window.importImagesByLine[lineNum]) window.importImagesByLine[lineNum] = [];
      window.importImagesByLine[lineNum].push(data.path);
      window.renderImportImagePreviews(lineNum);
      return { ok: true };
    } else {
      console.error('Upload error:', data.error);
      return { ok: false, error: data.error || 'Upload failed' };
    }
  } catch(e) {
    console.error('Upload failed:', e);
    return { ok: false, error: 'Network error' };
  }
}

window.handleImportImageFiles = async function(files, lineNum) {
  const statusEl = document.getElementById('img-status-' + lineNum);
  let uploaded = 0;
  let failed = 0;
  let lastError = '';
  
  const imageFiles = files.filter(f => f.type.startsWith('image/'));
  if (imageFiles.length === 0) {
      statusEl.textContent = 'No valid images selected.';
      statusEl.style.color = '#dc2626';
      setTimeout(() => { statusEl.textContent = ''; }, 3000);
      return;
  }
  
  statusEl.style.color = '#6D28D9';
  
  for (let i = 0; i < imageFiles.length; i++) {
    statusEl.textContent = 'Uploading image ' + (i + 1) + ' of ' + imageFiles.length + '...';
    const result = await window.uploadSingleImage(imageFiles[i], lineNum);
    if (result.ok) {
        uploaded++;
    } else {
        failed++;
        lastError = result.error;
    }
  }
  
  if (failed > 0) {
      statusEl.textContent = uploaded + ' uploaded, ' + failed + ' failed (' + lastError + ')';
      statusEl.style.color = '#dc2626';
  } else {
      statusEl.textContent = uploaded + ' image(s) uploaded successfully';
      statusEl.style.color = '#16a34a';
  }
  
  setTimeout(() => { statusEl.textContent = ''; }, 5000);
}

window.handleImportImageSelect = function(input, lineNum) {
  if (input.files && input.files.length) window.handleImportImageFiles(Array.from(input.files), lineNum);
  input.value = ''; // reset for re-upload
}

window.handleImportImageDrop = function(e, lineNum) {
  const files = e.dataTransfer.files;
  if (files.length) window.handleImportImageFiles(Array.from(files), lineNum);
}

// VIDEO UPLOAD
window.handleImportVideoFiles = async function(files, lineNum) {
  const statusEl = document.getElementById('vid-status-' + lineNum);
  const inputEl = document.getElementById('video-url-' + lineNum);
  
  const videoFile = files.find(f => f.type.startsWith('video/'));
  if (!videoFile) {
      statusEl.textContent = 'No valid video selected.';
      statusEl.style.color = '#dc2626';
      setTimeout(() => { statusEl.textContent = ''; }, 3000);
      return;
  }
  
  statusEl.textContent = 'Uploading video...';
  statusEl.style.color = '#6D28D9';
  
  const fd = new FormData();
  fd.append('video', videoFile);
  fd.append('line', lineNum);
  fd.append('csrf_token', window.csrfTokenGlobal);
  
  try {
    const res = await fetch('api/upload-import-video.php', { method: 'POST', body: fd });
    let data;
    try {
      const text = await res.text();
      try {
        data = JSON.parse(text);
      } catch(e) {
        statusEl.textContent = 'HTTP ' + res.status + ' (Server error)';
        statusEl.style.color = '#dc2626';
        return;
      }
    } catch(e) {
      statusEl.textContent = 'Network error reading response';
      statusEl.style.color = '#dc2626';
      return;
    }
    
    if (data.success) {
      inputEl.value = data.path; // Set input field to path
      statusEl.textContent = 'Video uploaded successfully';
      statusEl.style.color = '#16a34a';
    } else {
      statusEl.textContent = data.error || 'Upload failed';
      statusEl.style.color = '#dc2626';
    }
  } catch(e) {
    statusEl.textContent = 'Network error';
    statusEl.style.color = '#dc2626';
  }
  
  setTimeout(() => { statusEl.textContent = ''; }, 5000);
}

window.handleImportVideoSelect = function(input, lineNum) {
  if (input.files && input.files.length) window.handleImportVideoFiles(Array.from(input.files), lineNum);
  input.value = '';
}

window.handleImportVideoDrop = function(e, lineNum) {
  const files = e.dataTransfer.files;
  if (files.length) window.handleImportVideoFiles(Array.from(files), lineNum);
}

window.renderImportImagePreviews = function(lineNum) {
  const container = document.getElementById('img-preview-' + lineNum);
  const countBadge = document.getElementById('img-count-' + lineNum);
  const imgs = window.importImagesByLine[lineNum] || [];
  countBadge.textContent = imgs.length;
  if (imgs.length > 0) {
    countBadge.style.background = '#DCFCE7';
    countBadge.style.color = '#16a34a';
  }
  container.innerHTML = imgs.map((path, i) => 
    '<div style="position:relative;width:64px;height:64px;border-radius:8px;overflow:hidden;border:1px solid #E8E5DF;">' +
    '<img src="' + (path.startsWith('/') ? path : path) + '" style="width:100%;height:100%;object-fit:cover;">' +
    '<button onclick="removeImportImage(' + lineNum + ',' + i + ');event.stopPropagation()" style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:10px;cursor:pointer;line-height:18px;text-align:center;">✕</button>' +
    (i === 0 ? '<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:8px;text-align:center;padding:1px;font-weight:700;">PRIMARY</div>' : '') +
    '</div>'
  ).join('');
}

function removeImportImage(lineNum, index) {
  if (importImagesByLine[lineNum]) {
    importImagesByLine[lineNum].splice(index, 1);
    renderImportImagePreviews(lineNum);
  }
}

function onAdminGridInput(input, type) {
  const val = input.value.trim().toLowerCase();
  const validList = type === 'category' ? validAdminCatsLower : validAdminBrandsLower;
  const isMatch = val === '' || validList.includes(val);

  if (val !== '' && isMatch) {
    input.style.borderColor = '#166534';
    input.style.background = '#F0FDF4';
    const row = input.closest('tr');
    const badge = row.querySelector('.admin-result-cell .msg-span');
    if (badge && !badge.textContent.includes('Imported') && !badge.textContent.includes('Updated')) {
      badge.style.background = '#E6F7ED';
      badge.style.color = '#276749';
      badge.textContent = 'Ready';
    }
  } else if (val !== '' && !isMatch) {
    input.style.borderColor = '#EAB308';
    input.style.background = '#FEFCE8';
  } else {
    input.style.borderColor = '#D1D5DB';
    input.style.background = '#FFFFFF';
  }
}

function handleAdminFileSelected(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('adminDropzonePrompt').style.display = 'none';
    document.getElementById('adminFileSelectedArea').style.display = 'block';
    document.getElementById('adminFileNameDisp').textContent = file.name;
    document.getElementById('adminFileSizeDisp').textContent = (file.size / 1024).toFixed(1) + ' KB';
  }
}

function selectAdminModeCard(mode) {
  document.getElementById('adminModeCardInsert').classList.toggle('active', mode === 'insert');
  document.getElementById('adminModeCardUpsert').classList.toggle('active', mode === 'upsert');
}

const adminDropzone = document.getElementById('adminDropzone');
if (adminDropzone) {
  ['dragenter', 'dragover'].forEach(eventName => {
    adminDropzone.addEventListener(eventName, (e) => { e.preventDefault(); adminDropzone.classList.add('dragover'); }, false);
  });
  ['dragleave', 'drop'].forEach(eventName => {
    adminDropzone.addEventListener(eventName, (e) => { e.preventDefault(); adminDropzone.classList.remove('dragover'); }, false);
  });
  adminDropzone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length) {
      const fileInput = document.getElementById('adminCsvFileInput');
      fileInput.files = files;
      handleAdminFileSelected(fileInput);
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('adminImportForm');
  if (!form) return;

  const CHUNK_SIZE = 50;

  form.addEventListener('submit', async function(e) {
    const submitter = e.submitter;
    if (submitter && submitter.value === 'cancel') return;
    e.preventDefault();

    const startBtn = document.getElementById('adminStartImportBtn');
    startBtn.disabled = true;
    startBtn.innerHTML = 'Processing Batch…';

    const mode = form.querySelector('input[name="mode"]:checked').value;
    const sellerId = form.querySelector('select[name="seller_id"]').value;
    const csrfToken = form.querySelector('input[name="_csrf_token"]').value;
    const importKey = form.querySelector('input[name="import_key"]').value;

    const progressContainer = document.getElementById('asyncProgressContainer');
    const progressBar = document.getElementById('asyncProgressBar');
    const progressDetail = document.getElementById('asyncProgressDetail');
    progressContainer.style.display = 'block';

    const overrides = {};
    document.querySelectorAll('.admin-cat-input, .admin-brand-input').forEach(inp => {
      const line = inp.dataset.line;
      if (!overrides[line]) overrides[line] = {};
      if (inp.classList.contains('admin-cat-input')) overrides[line].category = inp.value;
      if (inp.classList.contains('admin-brand-input')) overrides[line].brand = inp.value;
    });

    document.querySelectorAll('.override-video').forEach(inp => {
      const line = inp.dataset.line;
      if (inp.value.trim() !== '') {
        if (!overrides[line]) overrides[line] = {};
        overrides[line].video_url = inp.value.trim();
      }
    });

    /* Include uploaded images in overrides so the server can link them */
    for (const line in importImagesByLine) {
      if (!overrides[line]) overrides[line] = {};
      overrides[line].images = importImagesByLine[line];
    }

    let offset = 0;
    let processedTotal = 0;
    let updatedTotal = 0;
    let createdTotal = 0;
    let failedTotal = 0;
    const totalValid = document.querySelectorAll('.admin-cat-input').length;
    let retries = 0;

    while (true) {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      formData.append('action', 'import_chunk');
      formData.append('import_key', importKey);
      formData.append('mode', mode);
      formData.append('seller_id', sellerId);
      formData.append('offset', offset);
      formData.append('limit', CHUNK_SIZE);
      formData.append('overrides', JSON.stringify(overrides));
      formData.append('ajax', '1');

      try {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 30000);
        
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData,
          signal: controller.signal
        });
        clearTimeout(timeout);

        if (!response.ok) {
          throw new Error('Server returned status ' + response.status);
        }

        const text = await response.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch(parseErr) {
          console.error('Non-JSON response:', text.substring(0, 500));
          throw new Error('Server returned invalid response. Check error logs.');
        }

        retries = 0; // reset retries on success

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
                    resCell.innerHTML = '<span style="background:#E0F2FE;color:#0369A1;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:800;">Updated (#' + outcome.id + ')</span>';
                  } else {
                    createdTotal++;
                    resCell.innerHTML = '<span style="background:#E6F7ED;color:#276749;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:800;">Imported (#' + outcome.id + ')</span>';
                  }
                } else {
                  failedTotal++;
                  resCell.innerHTML = '<span style="background:#FFF1F0;color:#CF1322;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:700;">' + (outcome.error || 'Failed') + '</span>';
                }
              }
            }
          }
        }

        processedTotal += (data.processed || 0);
        offset += CHUNK_SIZE;

        const percent = Math.min(100, Math.round((processedTotal / (totalValid || 1)) * 100));
        progressBar.style.width = percent + '%';
        progressDetail.textContent = processedTotal + ' of ' + totalValid + ' items processed (' + percent + '%)';

        if (data.done || processedTotal >= totalValid || (data.processed || 0) === 0) break;

      } catch (err) {
        console.error('Chunk error:', err);
        retries++;
        if (retries >= 3) {
          alert('Import failed after 3 retries: ' + err.message);
          startBtn.disabled = false;
          startBtn.textContent = 'Retry Batch Import';
          return;
        }
        progressDetail.textContent = 'Retry ' + retries + '/3 — ' + err.message;
        await new Promise(r => setTimeout(r, 2000));
      }
    }

    form.style.display = 'none';
    document.getElementById('asyncProgressTitle').textContent = '🎉 Batch Import Complete!';
    progressBar.style.background = '#00a854';
    progressDetail.innerHTML = '<strong>Successfully processed ' + processedTotal + ' items:</strong> ' + createdTotal + ' created (pending review), ' + updatedTotal + ' updated' + (failedTotal ? ', ' + failedTotal + ' failed' : '') + '.<br><div style="margin-top:10px;padding:10px 14px;background:#E0F2FE;border:1px solid #BAE6FD;border-radius:8px;color:#0369A1;font-size:12px;">📋 <strong>Next Step:</strong> Uploaded products are placed in <strong>Pending Review</strong>. Go to <a href="approvals.php" style="color:#0284C7;font-weight:700;text-decoration:underline;">Admin Approvals</a> to inspect and publish them live.</div>';
  });
});
</script>

<?php include 'layout/footer.php'; ?>
