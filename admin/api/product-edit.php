<?php
// admin/api/product-edit.php
//
// JSON backend for the approvals slide-over drawer (admin/approvals.php).
//
// Actions:
//   load  — POST product_id            → product row + images + dropdown options
//   save  — POST product_id + fields   → validate + partial UPDATE
//   del_image / primary_image          → image row operations
//
// Deliberately NOT handled here: approve / reject. Moderation state is written
// only by the approvals decision buttons — see ProductModerationEditor rule 1.
// A save can never flip status_market, is_active, or pulled_at, because those
// columns are not in the editor's field whitelist.

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/ProductModerationEditor.php';

Session::start();

function fail(string $message, int $code = 400, array $extra = []): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit;
}

function ok(array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// Auth check — same guard shape as admin/api/user-update.php.
if (Session::get('user_role') !== 'admin') {
    fail('Unauthorized access.', 403);
}

// CSRF — header (X-CSRF-Token) or form field, via the shared validator.
require_once __DIR__ . '/../_csrf_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('POST required.', 405);
}

$db = db();
$action = (string)($_POST['action'] ?? 'load');
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    fail('Invalid product id.');
}

switch ($action) {

    // ── Populate the drawer ───────────────────────────────────────────────
    case 'load':
        $data = ProductModerationEditor::load($db, $productId);
        if (!$data) fail('Product not found.', 404);

        // Dropdown options are static per install — the client caches them
        // across rows so repeated opens cost one small query only the first time.
        ok([
            'product' => $data['product'],
            'images'  => $data['images'],
            'options' => ProductModerationEditor::options($db),
        ]);
        break;

    // ── Save the drawer ───────────────────────────────────────────────────
    case 'save':
        $result = ProductModerationEditor::save($db, $productId, $_POST);
        if (!$result['success']) {
            // Field errors ride along so the drawer can highlight inputs.
            fail($result['message'], 422, [
                'errors' => $result['errors'] ?? [],
            ]);
        }
        // Return the refreshed row so the queue can update in place without
        // a reload — the whole point of staying inside the drawer.
        $fresh = ProductModerationEditor::load($db, $productId);
        ok([
            'message' => $result['message'],
            'changed' => $result['changed'] ?? [],
            'product' => $fresh['product'] ?? null,
            'images'  => $fresh['images'] ?? [],
        ]);
        break;

    // ── Approve from inside the drawer ─────────────────────────────────
    // Same semantics as the APPROVE button in admin/approvals.php: approving a
    // pulled listing must also restore is_active, otherwise it stays hidden
    // even though status_market is now 'active'. Only pulled rows get
    // is_active restored, so a product the admin deliberately hid stays hidden.
    case 'moderate_product':
        $act = (string)($_POST['moderate'] ?? '');
        if (!in_array($act, ['active', 'rejected', 'pending_review'], true)) {
            fail('Invalid moderation action.');
        }
        if ($act === 'active') {
            $db->prepare("UPDATE products SET status_market=?, is_active = CASE WHEN pulled_at IS NOT NULL THEN 1 ELSE is_active END, pulled_at = NULL WHERE id=?")
               ->execute([$act, $productId]);
        } else {
            $db->prepare('UPDATE products SET status_market=? WHERE id=?')->execute([$act, $productId]);
        }
        require_once __DIR__ . '/../../core/Cache.php';
        Cache::flushTags(['products']);
        ok(['message' => 'Product #' . $productId . ' → ' . strtoupper($act)]);
        break;

    // ── Image uploads ─────────────────────────────────────────────────────
    // Multipart, so $_FILES is read directly rather than via the FormData
    // helper the other actions use. CSRF still applies — _csrf_check.php
    // validates the token from the POST field alongside the file upload.
    case 'upload_images':
        if (empty($_FILES['images'])) {
            fail('No files were selected.');
        }
        $r = ProductModerationEditor::uploadImages($db, $productId, $_FILES['images']);
        if (!$r['success']) {
            fail($r['message'], 422, ['errors' => $r['errors'] ?? []]);
        }
        // Partial success (some files skipped) still returns 200 with the
        // skipped-file list, so the drawer can show what did and didn't land.
        ok([
            'message' => $r['message'],
            'added' => $r['added'] ?? 0,
            'images' => $r['images'] ?? [],
            'errors' => $r['errors'] ?? [],
        ]);
        break;

    // ── Image row operations ──────────────────────────────────────────────
    case 'del_image':
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId <= 0) fail('Invalid image id.');
        $r = ProductModerationEditor::deleteImage($db, $productId, $imageId);
        if (!$r['success']) fail($r['message'], 404);
        $fresh = ProductModerationEditor::load($db, $productId);
        ok(['message' => $r['message'], 'images' => $fresh['images'] ?? []]);
        break;

    case 'primary_image':
        $imageId = (int)($_POST['image_id'] ?? 0);
        if ($imageId <= 0) fail('Invalid image id.');
        $r = ProductModerationEditor::setPrimary($db, $productId, $imageId);
        if (!$r['success']) fail($r['message'], 404);
        $fresh = ProductModerationEditor::load($db, $productId);
        ok(['message' => $r['message'], 'images' => $fresh['images'] ?? []]);
        break;

    default:
        fail('Invalid action.');
}
