<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<style>
.import-page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}
.import-page-eyebrow {
  font-family: var(--f-mono);
  font-size: 11px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--mid-gray);
  margin-bottom: 6px;
}
.import-page-title {
  font-family: var(--f-display);
  font-weight: 900;
  font-size: clamp(24px, 4vw, 36px);
  margin: 0;
  letter-spacing: -0.02em;
  color: var(--ink);
}
.import-back-link {
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 600;
  color: var(--ink);
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: var(--paper);
  border: 1px solid var(--light-gray);
  border-radius: 6px;
  transition: all 0.2s var(--ease);
}
.import-back-link:hover {
  border-color: var(--ink);
  background: var(--off);
  transform: translateX(-2px);
}

.import-steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}
.import-step-card {
  background: var(--paper);
  border: 1px solid var(--light-gray);
  border-radius: 12px;
  padding: 28px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.03);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.2s var(--ease), box-shadow 0.2s var(--ease);
}
.import-step-card:hover {
  box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}
.step-number-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-family: var(--f-mono);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--red);
  background: rgba(232,0,45,0.08);
  padding: 6px 12px;
  border-radius: 20px;
  margin-bottom: 16px;
  width: fit-content;
}
.step-card-title {
  font-family: var(--f-display);
  font-weight: 800;
  font-size: 18px;
  color: var(--ink);
  margin: 0 0 10px;
}
.step-card-desc {
  font-size: 14px;
  line-height: 1.55;
  color: var(--mid-gray);
  margin-bottom: 24px;
}

/* Drag and Drop Zone */
.dropzone-box {
  border: 2px dashed #D0D5DD;
  border-radius: 12px;
  background: #FAFAFC;
  padding: 36px 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s var(--ease);
  position: relative;
}
.dropzone-box:hover, .dropzone-box.dragover {
  border-color: var(--red);
  background: #FFF5F6;
  box-shadow: 0 6px 20px rgba(232,0,45,0.08);
}
.dropzone-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(232,0,45,0.08);
  color: var(--red);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  transition: transform 0.2s var(--ease);
}
.dropzone-box:hover .dropzone-icon-wrap {
  transform: translateY(-4px) scale(1.05);
}
.dropzone-main-text {
  font-family: var(--f-display);
  font-weight: 700;
  font-size: 15px;
  color: var(--ink);
  margin-bottom: 4px;
}
.dropzone-sub-text {
  font-size: 13px;
  color: var(--mid-gray);
  margin-bottom: 8px;
}
.dropzone-browse-btn {
  color: var(--red);
  font-weight: 700;
  text-decoration: underline;
}

.file-selected-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 8px;
  padding: 12px 16px;
  margin-top: 14px;
}
.file-selected-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.file-selected-icon {
  font-size: 20px;
}
.file-selected-name {
  font-weight: 700;
  font-size: 14px;
  color: #166534;
}
.file-selected-size {
  font-size: 12px;
  color: #15803D;
}

/* Mode Selection Cards */
.mode-selection-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 14px;
  margin: 16px 0 24px;
}
.mode-card-option {
  border: 2px solid var(--light-gray);
  border-radius: 10px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s var(--ease);
  background: var(--paper);
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.mode-card-option:hover {
  border-color: var(--ink);
}
.mode-card-option.active {
  border-color: var(--red);
  background: #FFF5F6;
  box-shadow: 0 4px 14px rgba(232,0,45,0.06);
}
.mode-card-option input[type="radio"] {
  accent-color: var(--red);
  margin-top: 3px;
  width: 18px;
  height: 18px;
}
.mode-card-title {
  font-family: var(--f-display);
  font-weight: 800;
  font-size: 14px;
  color: var(--ink);
  margin-bottom: 4px;
}
.mode-card-desc {
  font-size: 12px;
  line-height: 1.45;
  color: var(--mid-gray);
}

.seller-btn-primary {
  background: var(--red);
  color: #fff;
  font-family: var(--f-display);
  font-weight: 800;
  font-size: 14px;
  padding: 14px 28px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s var(--ease);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.seller-btn-primary:hover:not(:disabled) {
  background: var(--red-deep);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(232,0,45,0.25);
}
.seller-btn-secondary {
  background: var(--paper);
  color: var(--ink);
  border: 1.5px solid var(--light-gray);
  font-family: var(--f-display);
  font-weight: 700;
  font-size: 14px;
  padding: 14px 24px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s var(--ease);
}
.seller-btn-secondary:hover {
  border-color: var(--ink);
  background: var(--off);
}
</style>

<div class="import-page-header">
  <div>
    <div class="import-page-eyebrow">Catalog Management</div>
    <h1 class="import-page-title">Import Products from CSV</h1>
  </div>
  <a href="<?= APP_URL ?>/seller/products" class="import-back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
    Back to Products
  </a>
</div>

<?php if(!empty($error)): ?>
<div style="background:#FFF1F0;border:1px solid #FFA39E;border-radius:8px;padding:16px 20px;margin-bottom:24px;color:#CF1322;display:flex;align-items:center;gap:12px;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <span style="font-weight:600;font-size:14px;"><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<!-- Step 1 & Step 2 Setup Cards -->
<div class="import-steps-grid">
  <!-- Step 1: Template -->
  <div class="import-step-card">
    <div>
      <div class="step-number-badge">Step 1 · Template Setup</div>
      <h3 class="step-card-title">Download CSV Template</h3>
      <p class="step-card-desc">Download our pre-formatted UTF-8 template containing standard columns: <code>name</code>, <code>sku</code>, <code>price</code>, <code>stock</code>, <code>category</code>, <code>brand</code>, <code>description</code>, etc.</p>
    </div>
    <a href="<?= APP_URL ?>/seller/products/import/template" class="seller-btn-secondary" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Download Template CSV
    </a>
  </div>

  <!-- Step 2: Upload Zone -->
  <div class="import-step-card">
    <form method="post" enctype="multipart/form-data" id="previewUploadForm" style="display:flex;flex-direction:column;height:100%;justify-content:space-between;">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="preview">
      
      <div>
        <div class="step-number-badge">Step 2 · Upload &amp; Preview</div>
        <h3 class="step-card-title">Upload Catalogue File</h3>
        
        <div class="dropzone-box" id="dropzoneBox" onclick="document.getElementById('csvFileInput').click();">
          <input type="file" name="csv_file" id="csvFileInput" accept=".csv,text/csv" required style="display:none;" onchange="handleFileSelected(this)">
          <div id="dropzonePrompt">
            <div class="dropzone-icon-wrap">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <div class="dropzone-main-text">Drag &amp; drop your CSV file here</div>
            <div class="dropzone-sub-text">or <span class="dropzone-browse-btn">browse from computer</span></div>
          </div>
          <div id="fileSelectedArea" style="display:none;">
            <div class="file-selected-card">
              <div class="file-selected-info">
                <span class="file-selected-icon">📊</span>
                <div style="text-align:left;">
                  <div class="file-selected-name" id="fileNameDisp">file.csv</div>
                  <div class="file-selected-size" id="fileSizeDisp">0 KB</div>
                </div>
              </div>
              <span style="font-size:12px;color:var(--red);font-weight:700;">Change</span>
            </div>
          </div>
        </div>
      </div>

      <button type="submit" class="seller-btn-primary" style="margin-top:20px;width:100%;justify-content:center;">
        Upload &amp; Generate Preview →
      </button>
    </form>
  </div>
</div>

<!-- Step 3: Interactive Preview & Mode Selection Grid -->
<?php if ($preview !== null): $validCount=count(array_filter($preview,static fn($r)=>empty($r['errors']))); ?>
<div class="seller-panel" style="max-width:1100px;margin-bottom:32px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.05);overflow:hidden;background:var(--paper);border:1px solid var(--light-gray);">
  <div style="padding:22px 28px;border-bottom:1px solid var(--light-gray);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--off);">
    <div>
      <div style="font-family:var(--f-mono);font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:var(--mid-gray);">Step 3 · Verification &amp; Execution</div>
      <div style="font-family:var(--f-display);font-weight:900;font-size:20px;color:var(--ink);margin-top:2px;">
        CSV Data Preview
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <span style="background:var(--paper);border:1px solid var(--light-gray);padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;color:var(--ink);"><?= count($preview) ?> Total Rows</span>
      <span style="background:#E6F7ED;border:1px solid #B7EB8F;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;color:#276749;"><?= $validCount ?> Ready</span>
      <?php if(count($preview) - $validCount > 0): ?>
      <span style="background:#FFF1F0;border:1px solid #FFA39E;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;color:#CF1322;"><?= count($preview) - $validCount ?> Need Fix</span>
      <?php endif; ?>
    </div>
  </div>

  <div style="padding:28px;">
    <?php if (!$outcomes && $validCount > 0): ?>
    <div id="asyncProgressContainer" style="display:none;margin-bottom:24px;background:#FAFAFC;border:1px solid var(--light-gray);padding:20px;border-radius:10px;">
      <div style="font-family:var(--f-display);font-weight:800;font-size:16px;margin-bottom:10px;color:var(--ink);" id="asyncProgressTitle">Importing Product Chunks...</div>
      <div style="background:#E8E5DF;height:12px;border-radius:6px;overflow:hidden;">
        <div id="asyncProgressBar" style="width:0%;height:100%;background:var(--red);transition:width 0.25s var(--ease);"></div>
      </div>
      <div style="font-size:13px;color:var(--mid-gray);margin-top:8px;font-weight:600;" id="asyncProgressDetail">0 of <?= $validCount ?> items processed (0%)</div>
    </div>

    <form id="importForm" method="post">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="import_chunk">
      <input type="hidden" name="import_key" value="<?= htmlspecialchars(Session::get('seller_product_csv_import')['key'] ?? '') ?>">
      
      <div style="margin-bottom:20px;">
        <label style="font-family:var(--f-display);font-weight:800;font-size:15px;color:var(--ink);display:block;margin-bottom:8px;">Choose Import Mode:</label>
        <div class="mode-selection-grid">
          <label class="mode-card-option active" id="modeCardInsert" onclick="selectModeCard('insert')">
            <input type="radio" name="mode" value="insert" checked onclick="selectModeCard('insert')">
            <div>
              <div class="mode-card-title">Insert Only (Create New)</div>
              <div class="mode-card-desc">Always creates new product catalog entries for each valid row in the CSV.</div>
            </div>
          </label>

          <label class="mode-card-option" id="modeCardUpsert" onclick="selectModeCard('upsert')">
            <input type="radio" name="mode" value="upsert" onclick="selectModeCard('upsert')">
            <div>
              <div class="mode-card-title">Smart Upsert (Update &amp; Create)</div>
              <div class="mode-card-desc">Updates existing products if <strong>SKU</strong> or <strong>Name</strong> matches. Otherwise creates new product.</div>
            </div>
          </label>
        </div>
      </div>

      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
        <button id="startImportBtn" type="submit" class="seller-btn-primary" <?= $validCount?'':'disabled' ?>>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Execute Batch Import (<?= $validCount ?> Valid Rows)
        </button>
        <button type="submit" name="action" value="cancel" class="seller-btn-secondary">Cancel</button>
      </div>
    </form>
    <?php elseif (!$outcomes): ?>
    <div style="padding:16px;background:#FFF1F0;border:1px solid #FFA39E;border-radius:8px;color:#CF1322;font-weight:600;">No rows are ready to import. Please correct the CSV errors and upload again.</div>
    <?php else: $succeeded=count(array_filter($outcomes,static fn($o)=>$o['success'])); $failed=count($outcomes)-$succeeded; ?>
    <div id="finalSummaryBox" style="padding:16px 20px;background:#E6F7ED;border:1px solid #B7EB8F;border-radius:8px;margin-bottom:24px;color:#276749;font-weight:700;font-size:15px;display:flex;align-items:center;gap:12px;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <span>Import Complete! Successfully processed <?= $succeeded ?> items<?php if($failed): ?>, <?= $failed ?> failed during execution<?php endif; ?>.</span>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div style="overflow-x:auto;border:1px solid var(--light-gray);border-radius:8px;">
      <table class="admin-table" style="width:100%;border-collapse:collapse;text-align:left;">
        <thead>
          <tr style="background:var(--off);border-bottom:1px solid var(--light-gray);">
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">Row</th>
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">Product Name</th>
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">SKU</th>
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">Price</th>
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">Category / Brand</th>
            <th style="padding:14px 16px;font-family:var(--f-mono);font-size:11px;text-transform:uppercase;">Validation Status / Result</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($preview as $item): $result=$outcomes[$item['line']]??null; $messages=$item['errors']; if($result && !$result['success']) $messages[]=$result['error']; ?>
          <tr id="row-<?= (int)$item['line'] ?>" style="border-bottom:1px solid var(--light-gray);">
            <td style="padding:14px 16px;font-weight:700;color:var(--mid-gray);"><?= (int)$item['line'] ?></td>
            <td style="padding:14px 16px;font-weight:700;color:var(--ink);"><?= htmlspecialchars($item['row']['name']) ?></td>
            <td style="padding:14px 16px;font-family:var(--f-mono);font-size:12px;color:var(--mid-gray);"><?= htmlspecialchars($item['row']['sku'] ?? '—') ?></td>
            <td style="padding:14px 16px;font-weight:700;"><?= htmlspecialchars($item['row']['currency'].' '.$item['row']['price']) ?></td>
            <td style="padding:14px 16px;color:var(--mid-gray);font-size:13px;"><?= htmlspecialchars($item['row']['category'].' / '.$item['row']['brand']) ?></td>
            <td class="result-cell" style="padding:14px 16px;">
              <?php if($result && $result['success']): ?>
                <span style="background:#E6F7ED;color:#276749;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:800;"><?= $result['action'] === 'updated' ? 'Updated (#'.(int)$result['id'].')' : 'Imported (#'.(int)$result['id'].')' ?></span>
              <?php elseif($messages): ?>
                <span style="background:#FFF1F0;color:#CF1322;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:700;"><?= htmlspecialchars(implode(' ',$messages)) ?></span>
              <?php else: ?>
                <span style="background:var(--off);color:var(--mid-gray);padding:4px 10px;border-radius:12px;font-size:12px;font-weight:700;">Ready to import</span>
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

<script>
function handleFileSelected(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    document.getElementById('dropzonePrompt').style.display = 'none';
    document.getElementById('fileSelectedArea').style.display = 'block';
    document.getElementById('fileNameDisp').textContent = file.name;
    document.getElementById('fileSizeDisp').textContent = (file.size / 1024).toFixed(1) + ' KB';
  }
}

function selectModeCard(mode) {
  document.getElementById('modeCardInsert').classList.toggle('active', mode === 'insert');
  document.getElementById('modeCardUpsert').classList.toggle('active', mode === 'upsert');
}

// Drag and drop handlers
const dropzone = document.getElementById('dropzoneBox');
if (dropzone) {
  ['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); }, false);
  });
  ['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); }, false);
  });
  dropzone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length) {
      const fileInput = document.getElementById('csvFileInput');
      fileInput.files = files;
      handleFileSelected(fileInput);
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('importForm');
  if (!form) return;

  const totalValid = <?= (int)($validCount ?? 0) ?>;
  const CHUNK_SIZE = 50;

  form.addEventListener('submit', async function(e) {
    const submitter = e.submitter;
    if (submitter && submitter.value === 'cancel') return;
    e.preventDefault();

    const startBtn = document.getElementById('startImportBtn');
    startBtn.disabled = true;
    startBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Processing Batch...';

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
                    resCell.innerHTML = '<span style="background:#E0F2FE;color:#0369A1;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:800;">Updated (#' + outcome.id + ')</span>';
                  } else {
                    createdTotal++;
                    resCell.innerHTML = '<span style="background:#E6F7ED;color:#276749;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:800;">Imported (#' + outcome.id + ')</span>';
                  }
                } else {
                  failedTotal++;
                  resCell.innerHTML = '<span style="background:#FFF1F0;color:#CF1322;padding:4px 10px;border-radius:12px;font-size:12px;font-weight:700;">' + outcome.error + '</span>';
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
    document.getElementById('asyncProgressTitle').textContent = '🎉 Batch Import Complete!';
    progressBar.style.background = '#00a854';
    progressDetail.innerHTML = '<strong>Successfully processed ' + processedTotal + ' items:</strong> ' + createdTotal + ' created, ' + updatedTotal + ' updated' + (failedTotal ? ', ' + failedTotal + ' failed' : '') + '.';
  });
});
</script>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>


