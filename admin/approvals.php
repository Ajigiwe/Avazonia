<?php
// admin/approvals.php — Pending product moderation queue
// Lists every seller product awaiting review with one-click APPROVE / REJECT.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

// CSRF Check for POST requests
require_once __DIR__ . '/_csrf_check.php';

$db = db();
$error = '';
$success = '';

// Handle approve / reject / send-back-to-review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'moderate_product') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $act = $_POST['moderate'] ?? '';
    if ($pid && in_array($act, ['active', 'rejected', 'pending_review'], true)) {
        if ($act === 'active') {
            // Approving a product an admin pulled must also put it back live, otherwise
            // it stays hidden (is_active=0) even though its status is now 'active'.
            // Only rows with pulled_at set get is_active restored, so a product the
            // admin deliberately hid via the edit form stays hidden after approval.
            $db->prepare("UPDATE products SET status_market=?, is_active = CASE WHEN pulled_at IS NOT NULL THEN 1 ELSE is_active END, pulled_at = NULL WHERE id=?")->execute([$act, $pid]);
        } else {
            $db->prepare("UPDATE products SET status_market=? WHERE id=?")->execute([$act, $pid]);
        }
        require_once __DIR__ . '/../core/Cache.php';
        Cache::flushTags(['products']);
        $success = 'Product #' . $pid . ' → ' . strtoupper($act);
    }
}

// Bulk: approve every pending product at once
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'moderate_all') {
    $n = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='pending_review'")->fetchColumn();
    if ($n > 0) {
        $db->exec("UPDATE products SET status_market='active', is_active = CASE WHEN pulled_at IS NOT NULL THEN 1 ELSE is_active END, pulled_at = NULL WHERE status_market='pending_review'");
        require_once __DIR__ . '/../core/Cache.php';
        Cache::flushTags(['products']);
        $success = $n . ' product' . ($n === 1 ? '' : 's') . ' approved and live.';
    } else {
        $success = 'Nothing awaiting review — queue is already empty.';
    }
}

// The moderation queue — pending seller products, newest first
$pending = $db->query("
    SELECT
        p.*,
        b.name as brand_name,
        c.name as cat_name,
        s.business_name as seller_name,
        s.is_verified as seller_verified,
        st.name as store_name,
        (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) as img_count
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    LEFT JOIN stores st ON p.store_id = st.id
    WHERE p.status_market = 'pending_review'
    ORDER BY p.created_at DESC
    LIMIT 200
")->fetchAll();

$pendingCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='pending_review'")->fetchColumn();
$rejectedCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='rejected'")->fetchColumn();

$title = "Pending Approvals";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Approvals <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">(<?= $pendingCount ?> pending)</span></h1>
    <div style="display:flex;gap:10px;align-items:center;">
        <?php if ($pendingCount > 0): ?>
        <form method="POST" onsubmit="return confirm('Approve all <?= $pendingCount ?> pending products and make them live?');" style="margin:0;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="moderate_all">
            <button type="submit" style="height:44px;padding:0 24px;font-size:10px;font-weight:800;letter-spacing:0.06em;background:#16a34a;color:#fff;border:none;border-radius:3px;cursor:pointer;">Approve all (<?= $pendingCount ?>) ✓</button>
        </form>
        <?php endif; ?>
        <a href="products.php" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">Full Catalogue →</a>
    </div>
</div>

<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <div style="flex:1;min-width:140px;background:#fff7ed;border:1px solid #fed7aa;padding:16px 20px;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#ea580c;letter-spacing:0.08em;">Awaiting Review</div>
        <div style="font-weight:800;font-size:24px;color:#ea580c;margin-top:4px;"><?= $pendingCount ?></div>
    </div>
    <div style="flex:1;min-width:140px;background:#fef2f2;border:1px solid #fecaca;padding:16px 20px;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#dc2626;letter-spacing:0.08em;">Rejected</div>
        <div style="font-weight:800;font-size:24px;color:#dc2626;margin-top:4px;"><?= $rejectedCount ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Seller Products Awaiting Review</div>
    </div>
    <?php if ($error): ?>
        <div style="margin: 0 32px 24px; background: #fff1f0; color: #f5222d; padding: 16px; font-size: 13px; border-left: 4px solid #f5222d;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="margin: 0 32px 24px; background: #e6f7ec; color: #00a854; padding: 16px; font-size: 13px; border-left: 4px solid #00a854;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Category · Listing</th>
                    <th>Price</th>
                    <th>Submitted</th>
                    <th>Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; max-width: 260px;"><?= htmlspecialchars($p['name']) ?></div>
                        <?php if (!empty($p['pulled_at'])): ?>
                            <div style="font-size:9px;font-weight:800;letter-spacing:0.06em;text-transform:uppercase;color:#fff;background:var(--red);display:inline-block;padding:3px 7px;border-radius:2px;margin-top:4px;" title="Pulled from the storefront by an admin on <?= date('M j, Y g:i A', strtotime($p['pulled_at'])) ?>">Pulled from site</div>
                            <div style="font-size:9px;color:var(--mid-gray);margin-top:3px;">Approving puts this listing back live.</div>
                        <?php else: ?>
                            <div style="font-size:9px;color:var(--mid-gray);margin-top:3px;">Seller submission</div>
                        <?php endif; ?>
                        <div style="font-size: 10px; color: var(--mid-gray); font-family: var(--f-mono); margin-top: 2px;">
                            <?= htmlspecialchars($p['brand_name'] ?? '—') ?> · <?= $p['img_count'] ?> img<?= $p['img_count'] == 1 ? '' : 's' ?>
                        </div>
                        <button type="button" class="pe-edit-btn" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" style="font-size:10px;color:var(--red);text-transform:uppercase;font-weight:700;background:none;border:none;padding:0;cursor:pointer;font-family:var(--f-semi);">Edit →</button>
                    </td>
                    <td>
                        <div style="font-weight: 700;"><?= htmlspecialchars($p['seller_name'] ?? 'Unknown seller') ?></div>
                        <?php if (!empty($p['seller_verified'])): ?>
                            <span class="status-badge status-paid" style="font-size:9px;">Verified</span>
                        <?php else: ?>
                            <span class="status-badge status-pending" style="font-size:9px;">Not yet verified</span>
                            <div style="font-size:9px;color:var(--mid-gray);margin-top:4px;max-width:160px;">Product stays hidden until this seller account is verified.</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-size: 11px;"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray); font-family: var(--f-mono); text-transform: uppercase;"><?= htmlspecialchars($p['listing_type'] ?? 'retail') ?></div>
                    </td>
                    <td style="font-family: var(--f-mono);"><?= format_price($p) ?></td>
                    <td style="font-size: 11px; white-space: nowrap;"><?= date('M j, Y g:i A', strtotime($p['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="moderate_product">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button name="moderate" value="active" style="background:#16a34a;color:#fff;border:none;padding:8px 14px;font-size:10px;font-weight:700;cursor:pointer;border-radius:3px;">APPROVE</button>
                            <button name="moderate" value="rejected" style="background:#fff;color:#dc2626;border:1px solid #fca5a5;padding:8px 14px;font-size:10px;font-weight:700;cursor:pointer;border-radius:3px;">REJECT</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pending)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:48px;color:var(--mid-gray);">
                        <div style="font-size:28px;margin-bottom:8px;">🎉</div>
                        Nothing awaiting review right now. Products sent to review from the catalogue will appear here.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Product edit engine: slide-over drawer ──────────────────────────── -->
<style>
    .pev-backdrop { position: fixed; inset: 0; background: rgba(13,13,13,.45); opacity: 0; visibility: hidden; transition: opacity .3s, visibility .3s; z-index: 4000; backdrop-filter: blur(2px); }
    .pev-backdrop.open { opacity: 1; visibility: visible; }
    .pev {
        position: fixed; top: 0; right: 0; bottom: 0; width: min(560px, 100vw);
        background: #fff; z-index: 4001; display: flex; flex-direction: column;
        transform: translateX(100%); transition: transform .34s cubic-bezier(.19,1,.22,1);
        box-shadow: -20px 0 60px rgba(0,0,0,.18);
    }
    .pev.open { transform: translateX(0); }
    .pev-head { padding: 20px 24px; border-bottom: 1px solid var(--light-gray); display: flex; align-items: flex-start; gap: 12px; }
    .pev-head-main { flex: 1; min-width: 0; }
    .pev-kicker { font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); }
    .pev-title { font-family: var(--f-display); font-weight: 800; font-size: 17px; text-transform: uppercase; margin-top: 4px; line-height: 1.2; overflow-wrap: anywhere; }
    .pev-close { background: none; border: 1px solid var(--light-gray); border-radius: 6px; width: 32px; height: 32px; cursor: pointer; color: var(--ink); font-size: 16px; line-height: 1; flex-shrink: 0; }
    .pev-close:hover { border-color: var(--ink); }
    .pev-body { flex: 1; overflow-y: auto; padding: 20px 24px 32px; }
    .pev-foot { padding: 16px 24px; border-top: 1px solid var(--light-gray); display: flex; gap: 10px; align-items: center; background: var(--off, #fafafa); }
    .pev-sec { margin-bottom: 24px; }
    .pev-sec-title { font-family: var(--f-semi); font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--light-gray); }
    .pev-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pev-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
    .pev-field.full { grid-column: 1 / -1; }
    .pev-field > label { font-family: var(--f-semi); font-size: 10px; font-weight: 700; color: var(--ink); }
    .pev-field > label .req { color: var(--red); }
    .pev-field input[type=text], .pev-field input[type=number], .pev-field select, .pev-field textarea {
        border: 1px solid var(--light-gray); border-radius: 5px; padding: 9px 10px;
        font-family: var(--f-mono); font-size: 12px; color: var(--ink); background: #fff; width: 100%; box-sizing: border-box;
    }
    .pev-field textarea { resize: vertical; min-height: 84px; line-height: 1.5; }
    .pev-field input:focus, .pev-field select:focus, .pev-field textarea:focus { outline: none; border-color: var(--red); }
    .pev-field .err { font-size: 10px; color: var(--red); font-weight: 600; }
    .pev-field.invalid input, .pev-field.invalid select, .pev-field.invalid textarea { border-color: var(--red); background: #fff8f8; }
    .pev-hint { font-size: 10px; color: var(--mid-gray); }
    .pev-flags { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .pev-flag { display: flex; align-items: center; gap: 8px; padding: 9px 10px; border: 1px solid var(--light-gray); border-radius: 5px; cursor: pointer; font-size: 12px; }
    .pev-flag:hover { border-color: var(--ink); }
    .pev-flag input { accent-color: var(--red); width: 15px; height: 15px; }
    .pev-imgs { display: grid; grid-template-columns: repeat(auto-fill, minmax(88px, 1fr)); gap: 10px; }
    .pev-img { position: relative; border: 1px solid var(--light-gray); border-radius: 6px; overflow: hidden; background: #fff; }
    .pev-img img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
    .pev-img.primary { border-color: var(--red); box-shadow: 0 0 0 2px rgba(232,0,45,.18); }
    .pev-img-badge { position: absolute; top: 4px; left: 4px; background: var(--red); color: #fff; font-size: 8px; font-weight: 800; letter-spacing: .06em; padding: 2px 5px; border-radius: 2px; text-transform: uppercase; }
    .pev-img-acts { display: flex; border-top: 1px solid var(--light-gray); }
    .pev-img-acts button { flex: 1; background: #fff; border: none; padding: 5px 0; font-size: 9px; font-weight: 800; text-transform: uppercase; cursor: pointer; color: var(--mid-gray); font-family: var(--f-semi); }
    .pev-img-acts button:hover { color: var(--ink); background: var(--off); }
    .pev-img-acts button.del:hover { color: var(--red); }
    .pev-empty { font-size: 11px; color: var(--mid-gray); padding: 16px; border: 1px dashed var(--light-gray); border-radius: 6px; text-align: center; }
    .pev-drop { position: relative; margin-top: 12px; border: 1.5px dashed var(--light-gray); border-radius: 8px; background: var(--off, #fafafa); transition: border-color .15s, background .15s; }
    .pev-drop:hover, .pev-drop.dragover { border-color: var(--red); background: #fff5f5; }
    .pev-drop input[type=file] { position: absolute; inset: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
    .pev-drop-inner { padding: 20px; text-align: center; pointer-events: none; }
    .pev-drop-icon { color: var(--mid-gray); margin-bottom: 6px; }
    .pev-drop.dragover .pev-drop-icon { color: var(--red); }
    .pev-drop-text { font-size: 12px; color: var(--ink); font-weight: 600; }
    .pev-drop-text span { color: var(--red); text-decoration: underline; }
    .pev-drop-hint { font-size: 10px; color: var(--mid-gray); margin-top: 4px; font-family: var(--f-mono); }
    .pev-drop-bar { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: var(--light-gray); overflow: hidden; border-radius: 0 0 7px 7px; }
    .pev-drop-bar span { display: block; height: 100%; width: 0; background: var(--red); transition: width .2s; }
    .pev-drop.busy { pointer-events: none; opacity: .7; }
    .pev-drop-err { margin-top: 8px; font-size: 11px; color: var(--red); font-weight: 600; }
    .pev-save { height: 40px; padding: 0 20px; border: none; border-radius: 4px; background: var(--red); color: #fff; font-family: var(--f-semi); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; }
    .pev-save:hover { background: var(--red-deep, #b8001f); }
    .pev-save:disabled { opacity: .6; cursor: default; }
    .pev-approve { height: 40px; padding: 0 18px; border: none; border-radius: 4px; background: #16a34a; color: #fff; font-family: var(--f-semi); font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; }
    .pev-approve:hover { background: #15803d; }
    .pev-dirty { font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); margin-left: auto; }
    .pev-dirty.on { color: #ea580c; font-weight: 700; }
    .pev-loading { padding: 40px; text-align: center; color: var(--mid-gray); font-family: var(--f-mono); font-size: 11px; }
    .pev-backdrop-confirm { position: fixed; inset: 0; z-index: 4002; }
</style>

<div class="pev-backdrop" id="pev-backdrop"></div>
<aside class="pev" id="pev" role="dialog" aria-modal="true" aria-labelledby="pev-title" aria-hidden="true">
    <div class="pev-head">
        <div class="pev-head-main">
            <div class="pev-kicker">Edit product · <span id="pev-id">—</span></div>
            <div class="pev-title" id="pev-title">Loading…</div>
        </div>
        <button type="button" class="pev-close" id="pev-close" aria-label="Close editor">&times;</button>
    </div>
    <div class="pev-body" id="pev-body"><div class="pev-loading">Loading product…</div></div>
    <div class="pev-foot">
        <button type="button" class="pev-save" id="pev-save">Save changes</button>
        <button type="button" class="pev-approve" id="pev-approve">Save &amp; Approve</button>
        <span class="pev-dirty" id="pev-dirty"></span>
    </div>
</aside>

<script>
// ── Product edit engine (client half) ───────────────────────────────────
// Opens a slide-over drawer, loads one product from admin/api/product-edit.php,
// and saves it back over fetch. Staying on the page is the point: the queue
// keeps its scroll position and its 293-row context.
(function () {
    'use strict';

    const ENDPOINT = 'api/product-edit.php';
    const backdrop = document.getElementById('pev-backdrop');
    const drawer = document.getElementById('pev');
    const bodyEl = document.getElementById('pev-body');
    const titleEl = document.getElementById('pev-title');
    const idEl = document.getElementById('pev-id');
    const saveBtn = document.getElementById('pev-save');
    const approveBtn = document.getElementById('pev-approve');
    const dirtyEl = document.getElementById('pev-dirty');

    let currentId = null;
    let optionsCache = null;   // categories + brands are static per install
    let dirty = false;
    let closing = false;

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF = csrfMeta ? csrfMeta.getAttribute('content') : '';

    // ── CSRF-aware POST ────────────────────────────────────────────────
    async function post(fields) {
        const fd = new FormData();
        fd.append('_csrf_token', CSRF);
        Object.keys(fields).forEach(k => fd.append(k, fields[k]));
        const res = await fetch(ENDPOINT, { method: 'POST', body: fd });
        let data = null;
        try { data = await res.json(); } catch (e) { /* non-JSON error page */ }
        return { ok: res.ok, status: res.status, data: data || {} };
    }

    // ── Drawer shell ───────────────────────────────────────────────────
    function openDrawer() {
        backdrop.classList.add('open');
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    async function closeDrawer(force) {
        if (dirty && !force) {
            const ok = await window.AdminUI.confirm(
                'You have unsaved edits on this product. Discard them and close the editor?',
                { title: 'Unsaved changes', confirmText: 'Discard edits', tone: 'warn' }
            );
            if (!ok) return;
        }
        closing = true;
        backdrop.classList.remove('open');
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        currentId = null; dirty = false;
        updateDirty();
        setTimeout(() => { closing = false; bodyEl.innerHTML = ''; }, 340);
    }

    function updateDirty() {
        dirtyEl.textContent = dirty ? '● Unsaved changes' : '';
        dirtyEl.classList.toggle('on', dirty);
    }

    // ── Field rendering (driven by the engine's own field list) ────────
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    function field(opts) {
        // opts: {key, label, type, value, required, hint, options, full}
        const id = 'pevf-' + opts.key;
        const req = opts.required ? ' <span class="req">*</span>' : '';
        let control = '';
        const v = esc(opts.value);
        switch (opts.type) {
            case 'textarea':
                control = `<textarea id="${id}" name="${opts.key}" rows="4">${v}</textarea>`;
                break;
            case 'select':
                control = `<select id="${id}" name="${opts.key}">` + (opts.options || []).map(o =>
                    `<option value="${esc(o.value)}"${String(o.value) === String(opts.value) ? ' selected' : ''}>${esc(o.label)}</option>`
                ).join('') + '</select>';
                break;
            case 'number':
                control = `<input type="number" id="${id}" name="${opts.key}" value="${v}" step="${opts.step || '1'}" min="${opts.min != null ? opts.min : ''}">`;
                break;
            default:
                control = `<input type="text" id="${id}" name="${opts.key}" value="${v}">`;
        }
        return `<div class="pev-field${opts.full ? ' full' : ''}" data-field="${opts.key}">
            <label for="${id}">${esc(opts.label)}${req}</label>
            ${control}
            ${opts.hint ? `<span class="pev-hint">${esc(opts.hint)}</span>` : ''}
            <span class="err" data-err="${opts.key}"></span>
        </div>`;
    }

    function render(product, images, options) {
        const cur = product.currency === 'USD' ? 'USD' : 'GHS';
        const cats = (options.categories || []).map(c => ({ value: c.id, label: c.name }));
        const brands = (options.brands || []).map(b => ({ value: b.id, label: b.name }));
        const isGHS = cur === 'GHS';

        idEl.textContent = '#' + product.id;
        titleEl.textContent = product.name || 'Untitled product';

        bodyEl.innerHTML = `
            <form id="pev-form" autocomplete="off">
                <div class="pev-sec">
                    <div class="pev-sec-title">Listing</div>
                    <div class="pev-grid">
                        ${field({ key: 'name', label: 'Product name', type: 'text', value: product.name, required: true, full: true })}
                        ${field({ key: 'category_id', label: 'Category', type: 'select', value: product.category_id, required: true, options: cats })}
                        ${field({ key: 'brand_id', label: 'Brand', type: 'select', value: product.brand_id, options: [{ value: '', label: '— No brand —' }].concat(brands, [{ value: '_new', label: '+ Create new brand…' }]) })}
                        ${field({ key: 'brand_name', label: 'New brand name', type: 'text', value: '', hint: 'Only used when "Create new brand" is selected.', full: true })}
                        ${field({ key: 'listing_type', label: 'Listing type', type: 'select', value: product.listing_type || 'retail', options: [{ value: 'retail', label: 'Retail' }, { value: 'wholesale', label: 'Wholesale' }, { value: 'rfq', label: 'RFQ' }, { value: 'export', label: 'Export' }] })}
                        ${field({ key: 'visibility', label: 'Visibility', type: 'select', value: product.visibility || 'public', options: [{ value: 'public', label: 'Public' }, { value: 'b2b_only', label: 'B2B only' }, { value: 'retail_only', label: 'Retail only' }] })}
                        ${field({ key: 'condition_type', label: 'Condition', type: 'select', value: product.condition_type || 'new', options: [{ value: 'new', label: 'New' }, { value: 'used', label: 'Used' }] })}
                        ${field({ key: 'currency', label: 'Currency', type: 'select', value: cur, options: [{ value: 'GHS', label: 'GHS' }, { value: 'USD', label: 'USD' }] })}
                    </div>
                </div>

                <div class="pev-sec">
                    <div class="pev-sec-title">Pricing &amp; stock</div>
                    <div class="pev-grid">
                        ${field({ key: 'price_ghs', label: 'Price (GHS)', type: 'number', value: product.price_ghs, step: '0.01', min: 0 })}
                        ${field({ key: 'compare_at_price_ghs', label: 'Compare-at (GHS)', type: 'number', value: product.compare_at_price_ghs, step: '0.01', min: 0 })}
                        ${field({ key: 'price_usd', label: 'Price (USD)', type: 'number', value: product.price_usd, step: '0.01', min: 0 })}
                        ${field({ key: 'compare_at_price_usd', label: 'Compare-at (USD)', type: 'number', value: product.compare_at_price_usd, step: '0.01', min: 0 })}
                        ${field({ key: 'stock_qty', label: 'Stock units', type: 'number', value: product.stock_qty, min: 0, required: true })}
                        ${field({ key: 'wholesale_price_ghs', label: 'Wholesale price (GHS)', type: 'number', value: product.wholesale_price_ghs, step: '0.01', min: 0 })}
                        ${field({ key: 'moq', label: 'MOQ', type: 'number', value: product.moq, min: 1 })}
                    </div>
                </div>

                <div class="pev-sec">
                    <div class="pev-sec-title">Description</div>
                    ${field({ key: 'description', label: 'Description', type: 'textarea', value: product.description, full: true })}
                </div>

                <div class="pev-sec">
                    <div class="pev-sec-title">Flags</div>
                    <div class="pev-flags">
                        ${['is_featured', 'is_bestseller', 'is_preorder', 'is_dropshipping', 'available_in_ghana'].map(k => {
                            const labels = { is_featured: 'Featured', is_bestseller: 'Bestseller', is_preorder: 'Pre-order', is_dropshipping: 'Global / dropship', available_in_ghana: 'Available in Ghana' };
                            return `<label class="pev-flag"><input type="checkbox" name="${k}" value="1"${parseInt(product[k]) ? ' checked' : ''}> ${labels[k]}</label>`;
                        }).join('')}
                    </div>
                </div>

                <div class="pev-sec">
                    <div class="pev-sec-title">Images</div>
                    <div class="pev-imgs" id="pev-imgs">${renderImages(images)}</div>
                    <div class="pev-drop" id="pev-drop">
                        <input type="file" id="pev-file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="pev-drop-inner">
                            <div class="pev-drop-icon" aria-hidden="true">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            </div>
                            <div class="pev-drop-text">Drop images here or <span>browse</span></div>
                            <div class="pev-drop-hint">JPG, PNG or WEBP · watermarked automatically</div>
                        </div>
                        <div class="pev-drop-bar" id="pev-drop-bar" hidden><span></span></div>
                    </div>
                </div>
            </form>`;

        wireForm();
        wireImages();
        wireUpload();
    }

    function renderImages(images) {
        if (!images || !images.length) return '<div class="pev-empty">No images on this listing yet.</div>';
        return images.map(img => `
            <div class="pev-img${parseInt(img.is_primary) ? ' primary' : ''}" data-img="${img.id}">
                <img src="${esc(img.url)}" alt="" loading="lazy">
                ${parseInt(img.is_primary) ? '<span class="pev-img-badge">Primary</span>' : ''}
                <div class="pev-img-acts">
                    <button type="button" data-img-primary="${img.id}" title="Make this the primary image"${parseInt(img.is_primary) ? ' disabled style="opacity:.4;"' : ''}>Primary</button>
                    <button type="button" class="del" data-img-del="${img.id}" title="Remove this image">Remove</button>
                </div>
            </div>`).join('');
    }

    function wireForm() {
        const form = document.getElementById('pev-form');
        if (!form) return;
        form.addEventListener('input', () => { dirty = true; updateDirty(); });
        form.addEventListener('change', e => {
            dirty = true; updateDirty();
            // Brand picker reveals the free-text name field on demand.
            if (e.target && e.target.name === 'brand_id') {
                const nf = form.querySelector('[data-field="brand_name"]');
                if (nf) nf.style.display = e.target.value === '_new' ? '' : 'none';
            }
            // Currency toggles which price pair is the required one.
            if (e.target && e.target.name === 'currency') {
                const usd = e.target.value === 'USD';
                const ghsLbl = form.querySelector('[data-field="price_ghs"] label');
                const usdLbl = form.querySelector('[data-field="price_usd"] label');
                if (ghsLbl && usdLbl) {
                    ghsLbl.innerHTML = 'Price (GHS)' + (usd ? '' : ' <span class="req">*</span>');
                    usdLbl.innerHTML = 'Price (USD)' + (usd ? ' <span class="req">*</span>' : '');
                }
            }
        });
        // Hide the new-brand box until it is actually selected.
        const nf = form.querySelector('[data-field="brand_name"]');
        const bs = form.querySelector('[name="brand_id"]');
        if (nf && bs) nf.style.display = bs.value === '_new' ? '' : 'none';
    }

    function wireImages() {
        bodyEl.querySelectorAll('[data-img-primary]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const r = await post({ action: 'primary_image', product_id: currentId, image_id: btn.getAttribute('data-img-primary') });
                if (r.ok && r.data.images) document.getElementById('pev-imgs').innerHTML = renderImages(r.data.images);
                else window.AdminUI.alert(r.data.message || 'Could not update the primary image.', { title: 'Image error' });
            });
        });
        bodyEl.querySelectorAll('[data-img-del]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const ok = await window.AdminUI.confirm('Remove this image from the listing? The file itself is kept on the server.', { title: 'Remove image', confirmText: 'Remove' });
                if (!ok) return;
                const r = await post({ action: 'del_image', product_id: currentId, image_id: btn.getAttribute('data-img-del') });
                if (r.ok && r.data.images) {
                    document.getElementById('pev-imgs').innerHTML = renderImages(r.data.images);
                    dirty = true; updateDirty();
                } else {
                    window.AdminUI.alert(r.data.message || 'Could not remove the image.', { title: 'Image error' });
                }
            });
        });
    }

    // ── Uploads ────────────────────────────────────────────────────────
    // Uploading commits immediately on the server (the file is stored, the
    // product_images rows are inserted, the watermark is stamped), so — like
    // delete and set-primary — it is not part of the dirty-save model. There
    // is nothing to "save" afterwards.
    //
    // XHR rather than fetch: it is still the only way to get upload progress
    // events, which matters on a 293-item queue over a slow connection.
    function wireUpload() {
        const drop = document.getElementById('pev-drop');
        const input = document.getElementById('pev-file');
        const bar = document.getElementById('pev-drop-bar');
        const barFill = bar ? bar.querySelector('span') : null;
        if (!drop || !input) return;

        const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];
        const MAX_BYTES = 8 * 1024 * 1024; // matches a sane server upload cap

        function setBusy(on) {
            drop.classList.toggle('busy', on);
            if (bar) bar.hidden = !on;
            if (barFill && !on) barFill.style.width = '0%';
        }

        function showDropError(msg) {
            let box = drop.parentNode.querySelector('.pev-drop-err');
            if (!box) {
                box = document.createElement('div');
                box.className = 'pev-drop-err';
                drop.parentNode.appendChild(box);
            }
            box.textContent = msg;
            setTimeout(() => { if (box.parentNode) box.remove(); }, 6000);
        }

        function upload(files) {
            const list = Array.prototype.slice.call(files || []);
            if (!list.length) return;

            // Client-side gate for the obvious rejects, so the user is not
            // made to wait for a round-trip to be told "no PDFs".
            const usable = [];
            const rejected = [];
            list.forEach(f => {
                if (ACCEPT.indexOf(f.type) === -1) rejected.push('"' + f.name + '" is not a JPG, PNG or WEBP.');
                else if (f.size > MAX_BYTES) rejected.push('"' + f.name + '" is larger than 8 MB.');
                else usable.push(f);
            });
            if (rejected.length) showDropError(rejected.join(' '));
            if (!usable.length) return;

            const fd = new FormData();
            fd.append('_csrf_token', CSRF);
            fd.append('action', 'upload_images');
            fd.append('product_id', currentId);
            usable.forEach(f => fd.append('images[]', f));

            const xhr = new XMLHttpRequest();
            xhr.open('POST', ENDPOINT);
            setBusy(true);

            xhr.upload.addEventListener('progress', e => {
                if (e.lengthComputable && barFill) {
                    barFill.style.width = Math.round((e.loaded / e.total) * 100) + '%';
                }
            });

            xhr.addEventListener('load', () => {
                setBusy(false);
                input.value = ''; // allow re-selecting the same file
                let data = {};
                try { data = JSON.parse(xhr.responseText); } catch (e) { /* non-JSON error page */ }

                if (xhr.status >= 200 && xhr.status < 300) {
                    if (data.images) document.getElementById('pev-imgs').innerHTML = renderImages(data.images);
                    // Re-bind the primary/remove buttons on the fresh markup.
                    wireImages();
                    window.AdminUI.success(data.message || 'Uploaded.');
                    // Skipped files ride along on a partial success.
                    if (data.errors && data.errors.length) showDropError(data.errors.join(' '));
                } else {
                    showDropError(data.message || 'Upload failed.');
                }
            });

            xhr.addEventListener('error', () => {
                setBusy(false);
                input.value = '';
                showDropError('Network error — the upload did not complete.');
            });

            xhr.send(fd);
        }

        input.addEventListener('change', () => { upload(input.files); input.value = ''; });

        // Drag and drop, with the dragover class driving the visual state.
        ['dragenter', 'dragover'].forEach(ev => {
            drop.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
                drop.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(ev => {
            drop.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
                drop.classList.remove('dragover');
            });
        });
        drop.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files) upload(e.dataTransfer.files);
        });
    }

    function clearErrors() {
        bodyEl.querySelectorAll('.pev-field.invalid').forEach(f => f.classList.remove('invalid'));
        bodyEl.querySelectorAll('.err').forEach(e => { e.textContent = ''; });
    }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors || {}).forEach(key => {
            const wrap = bodyEl.querySelector('[data-field="' + key + '"]');
            const err = bodyEl.querySelector('[data-err="' + key + '"]');
            if (err) err.textContent = errors[key];
            if (wrap) {
                wrap.classList.add('invalid');
                const input = wrap.querySelector('input,select,textarea');
                if (input) input.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    }

    // ── Save ───────────────────────────────────────────────────────────
    async function save() {
        const form = document.getElementById('pev-form');
        if (!form || !currentId) return null;
        saveBtn.disabled = true; approveBtn.disabled = true;
        try {
            const fd = new FormData(form);
            fd.append('_csrf_token', CSRF);
            fd.append('action', 'save');
            fd.append('product_id', currentId);
            // Unchecked checkboxes post nothing; the engine treats absence as
            // "leave unchanged", so normalise them to explicit zeros here.
            ['is_featured', 'is_bestseller', 'is_preorder', 'is_dropshipping', 'available_in_ghana'].forEach(k => {
                if (!fd.has(k)) fd.append(k, '0');
            });
            const res = await fetch(ENDPOINT, { method: 'POST', body: fd });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                if (data.errors && Object.keys(data.errors).length) showErrors(data.errors);
                else window.AdminUI.alert(data.message || 'Save failed.', { title: 'Could not save' });
                return null;
            }
            clearErrors();
            if (data.message === 'No changes to save.') { dirty = false; updateDirty(); return data; }
            dirty = false; updateDirty();
            if (data.product) titleEl.textContent = data.product.name || titleEl.textContent;
            window.AdminUI.success(data.message || 'Saved.');
            return data;
        } finally {
            saveBtn.disabled = false; approveBtn.disabled = false;
        }
    }

    saveBtn.addEventListener('click', () => { save(); });
    approveBtn.addEventListener('click', async () => {
        const saved = await save();
        if (!saved) return; // validation failed — stay in the drawer
        const r = await post({ action: 'moderate_product', product_id: currentId, moderate: 'active' });
        if (r.ok) {
            window.AdminUI.success('Saved and approved.');
            await closeDrawer(true);
            setTimeout(() => window.location.reload(), 600);
        } else {
            window.AdminUI.alert(r.data.message || 'Saved, but approval failed. Use the APPROVE button in the queue.', { title: 'Approval failed' });
        }
    });

    // ── Open ───────────────────────────────────────────────────────────
    async function open(productId, name) {
        currentId = productId;
        dirty = false; updateDirty();
        titleEl.textContent = name || 'Loading…';
        idEl.textContent = '#' + productId;
        bodyEl.innerHTML = '<div class="pev-loading">Loading product…</div>';
        openDrawer();

        const r = await post({ action: 'load', product_id: productId });
        if (!r.ok || !r.data.product) {
            bodyEl.innerHTML = '<div class="pev-loading">' + esc(r.data.message || 'Could not load this product.') + '</div>';
            return;
        }
        optionsCache = r.data.options || { categories: [], brands: [] };
        render(r.data.product, r.data.images, optionsCache);
    }

    document.addEventListener('click', e => {
        const btn = e.target.closest('.pe-edit-btn');
        if (!btn) return;
        open(parseInt(btn.getAttribute('data-product-id'), 10), btn.getAttribute('data-product-name'));
    });

    document.getElementById('pev-close').addEventListener('click', () => closeDrawer(false));
    backdrop.addEventListener('click', () => closeDrawer(false));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && drawer.classList.contains('open') && !closing) closeDrawer(false);
        // Ctrl/Cmd+S saves without leaving the drawer — handy in a long queue.
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's' && drawer.classList.contains('open')) {
            e.preventDefault(); save();
        }
    });
    // Guard against losing edits to a stray navigation.
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // Stop stray drops elsewhere on the page from navigating away mid-edit.
    // NB: registered once at init, not inside wireUpload() — render() runs on
    // every drawer open, so attaching here would stack an unremovable listener
    // per product viewed across a long queue.
    ['dragover', 'drop'].forEach(ev => {
        document.addEventListener(ev, e => {
            if (drawer.classList.contains('open')) e.preventDefault();
        });
    });
})();
</script>

<?php include 'layout/footer.php'; ?>
