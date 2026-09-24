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
                    $outcomes = ProductCsvImporter::import($db, $preview, $sellerId, $storeId, 'active', $mode, $offset, $limit, $overrides);
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
<div class="panel" style="max-width:1100px;margin-bottom:32px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.05);overflow:hidden;background:#FFF;border:1px solid #E8E5DF;">
  <div style="padding:22px 28px;border-bottom:1px solid #E8E5DF;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#FAFAFC;">
    <div>
      <div style="font-family:var(--f-mono, monospace);font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#55514E;">Step 3 · Verification &amp; Web Grid Editor</div>
      <div style="font-family:var(--f-display, sans-serif);font-weight:900;font-size:20px;color:#0D0D0D;margin-top:2px;">
        Spreadsheet Data Preview &amp; Field Selector
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
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:50px;">Row</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;">Product Name</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:120px;">SKU</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;width:100px;">Price</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;min-width:200px;">Category Selector</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;min-width:160px;">Brand Selector</th>
            <th style="padding:14px 16px;font-family:var(--f-mono, monospace);font-size:11px;text-transform:uppercase;">Result</th>
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
        ?>
          <tr id="admin-row-<?= $lineNum ?>" style="border-bottom:1px solid #E8E5DF;">
            <td style="padding:12px 16px;font-weight:700;color:#55514E;"><?= $lineNum ?></td>
            <td style="padding:12px 16px;font-weight:700;color:#0D0D0D;"><?= htmlspecialchars($item['row']['name']) ?></td>
            <td style="padding:12px 16px;font-family:var(--f-mono, monospace);font-size:12px;color:#55514E;"><?= htmlspecialchars($item['row']['sku'] ?? '—') ?></td>
            <td style="padding:12px 16px;font-weight:700;"><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td>
            
            <td style="padding:10px 14px;">
              <select class="admin-cat-select" data-line="<?= $lineNum ?>" style="width:100%;padding:6px 10px;border-radius:6px;border:1px solid <?= empty($item['values']['category_id']) ? '#EAB308' : '#D1D5DB' ?>;font-size:13px;background:<?= empty($item['values']['category_id']) ? '#FEFCE8' : '#FFFFFF' ?>;">
                <option value="">-- Select Category --</option>
                <?php foreach($allCats as $catName): ?>
                  <option value="<?= htmlspecialchars($catName) ?>" <?= strcasecmp($matchedCat, $catName) === 0 ? 'selected' : '' ?>>
                    <?= htmlspecialchars($catName) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>

            <td style="padding:10px 14px;">
              <select class="admin-brand-select" data-line="<?= $lineNum ?>" style="width:100%;padding:6px 10px;border-radius:6px;border:1px solid <?= empty($item['values']['brand_id']) && !empty($matchedBrand) ? '#EAB308' : '#D1D5DB' ?>;font-size:13px;background:<?= empty($item['values']['brand_id']) && !empty($matchedBrand) ? '#FEFCE8' : '#FFFFFF' ?>;">
                <option value="">-- Optional Brand --</option>
                <?php foreach($allBrands as $brandName): ?>
                  <option value="<?= htmlspecialchars($brandName) ?>" <?= strcasecmp($matchedBrand, $brandName) === 0 ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brandName) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>

            <td class="admin-result-cell" style="padding:12px 16px;">
              <?php if ($result && $result['success']): ?>
                <span style="background:#E6F7ED;color:#276749;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:800;"><?= $result['action'] === 'updated' ? 'Updated (#' . (int)$result['id'] . ')' : 'Imported (#' . (int)$result['id'] . ')' ?></span>
              <?php elseif ($messages): ?>
                <span style="background:#FFF1F0;color:#CF1322;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:700;"><?= htmlspecialchars(implode(' ', $messages)) ?></span>
              <?php else: ?>
                <span style="background:#FAFAFC;color:#55514E;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:700;">Ready to import</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>


