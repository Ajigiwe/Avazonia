<?php
// admin/banners.php — Homepage promo banner manager
// Banners live in the settings table (JSON), see models/HomeBanner.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/HomeBanner.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

require_once __DIR__ . '/_csrf_check.php';

$bannerModel = new HomeBanner();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_banner' || $action === 'update_banner') {
        $id      = $_POST['id'] ?? '';
        $title   = trim($_POST['title'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $active  = isset($_POST['is_active']) ? 1 : 0;

        $existing = ($action === 'update_banner') ? ($bannerModel->find($id) ?: []) : [];
        $imageUrl = $existing['image_url'] ?? '';

        // New upload replaces the current image
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../public/uploads/banners/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $fileName = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $imageUrl = 'public/uploads/banners/' . $fileName;
                }
            }
        }
        // External image URL is only used when there is no upload yet
        if (empty($imageUrl)) {
            $imageUrl = trim($_POST['image_url_manual'] ?? '');
        }

        if ($action === 'add_banner') {
            if (empty($imageUrl)) { header('Location: banners.php?msg=need_image'); exit; }
            $bannerModel->add([
                'title'     => $title,
                'image_url' => $imageUrl,
                'link_url'  => $linkUrl,
                'is_active' => $active,
            ]);
        } else {
            if ($id !== '' && $bannerModel->find($id)) {
                $bannerModel->update($id, [
                    'title'     => $title,
                    'image_url' => $imageUrl,
                    'link_url'  => $linkUrl,
                    'is_active' => $active,
                ]);
            }
        }
        header('Location: banners.php?msg=saved'); exit;
    }

    if ($action === 'delete_banner') {
        $bannerModel->delete($_POST['id'] ?? '');
        header('Location: banners.php?msg=deleted'); exit;
    }

    if ($action === 'toggle_banner') {
        $b = $bannerModel->find($_POST['id'] ?? '');
        if ($b) $bannerModel->update($b['id'], ['is_active' => empty($b['is_active']) ? 1 : 0]);
        header('Location: banners.php'); exit;
    }

    if ($action === 'move_banner') {
        $bannerModel->move($_POST['id'] ?? '', (int)($_POST['dir'] ?? 1));
        header('Location: banners.php'); exit;
    }
}

if (isset($_GET['msg'])) $msg = $_GET['msg'];

$banners = $bannerModel->all();
$title = "Home Banners";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Home Banners</h1>
</div>

<?php if ($msg): ?>
    <div style="background: #e6f7ec; color: #00a854; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #00a854;">
        <?php if ($msg === 'need_image'): ?>
            <span style="color: var(--red);">Please upload an image or provide an image URL.</span>
        <?php else: ?>
            Saved successfully.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Add banner -->
<div class="panel" style="max-width: 980px;">
    <div class="panel-header">
        <div class="panel-title">+ Add Homepage Banner</div>
    </div>
    <div style="padding: 32px;">
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="add_banner">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Banner Label <span style="text-transform:none;">(optional — for your reference)</span></label>
                        <input type="text" name="title" placeholder="e.g. Mother's Day Promo" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Click-through Link</label>
                        <input type="text" name="link_url" placeholder="/shop?cat=electronics" value="/shop" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div style="background: var(--off); padding: 16px; border-radius: 4px; border: 1px solid var(--light-gray);">
                        <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: var(--ink); font-weight: 700;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span>Active & visible on homepage</span>
                        </label>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Banner Image <span style="text-transform:none;">(recommended 1200 × 350 px)</span></label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 9px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 11px;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or External Image URL</label>
                        <input type="url" name="image_url_manual" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <p style="font-size: 11px; color: var(--mid-gray); line-height: 1.5;">Banners appear on the homepage between the <strong>New Drops</strong> rail and the category rows. Active banners stack in the order shown below.</p>
                    <button type="submit" class="btn-red" style="height: 48px; justify-content: center; font-size: 11px; letter-spacing: 0.1em; margin-top: auto;">Add Banner</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Existing banners -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Current Banners <span style="font-family:var(--f-mono);font-size:11px;font-weight:400;color:var(--mid-gray);">(<?= count($banners) ?>)</span></div>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Label / Link</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($banners as $i => $b): ?>
                <tr>
                    <td style="width: 220px;">
                        <?php $src = $b['image_url'] ?? ''; ?>
                        <?php if ($src): ?>
                            <img src="<?= (strpos($src, 'http') === 0 || strpos($src, '//') === 0) ? htmlspecialchars($src) : APP_URL . '/' . htmlspecialchars($src) ?>" style="width: 200px; height: 58px; object-fit: cover; border-radius: 4px; border: 1px solid var(--light-gray); display: block;" alt="Banner">
                        <?php else: ?>
                            <span style="font-size: 11px; color: var(--mid-gray); font-style: italic;">No image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 8px; min-width: 240px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="update_banner">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($b['id']) ?>">
                            <input type="text" name="title" value="<?= htmlspecialchars($b['title'] ?? '') ?>" placeholder="Label" style="padding: 8px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 12px;">
                            <input type="text" name="link_url" value="<?= htmlspecialchars($b['link_url'] ?? '/shop') ?>" style="padding: 8px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 12px; color: var(--mid-gray);">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?= !empty($b['is_active']) ? 'checked' : '' ?>> Active
                                </label>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" style="font-size: 10px; max-width: 160px;" title="Replace image">
                                <input type="url" name="image_url_manual" value="<?= (strpos($b['image_url'] ?? '', 'http') === 0 || strpos($b['image_url'] ?? '', '//') === 0) ? htmlspecialchars($b['image_url']) : '' ?>" placeholder="or external URL" style="padding: 6px; border: 1px solid var(--light-gray); font-size: 11px; flex: 1;">
                            </div>
                            <button type="submit" style="align-self: flex-start; background: var(--ink); color: #fff; border: none; padding: 6px 14px; font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer; border-radius: 3px;">Save</button>
                        </form>
                    </td>
                    <td style="white-space: nowrap;">
                        <form method="POST" style="display: flex; gap: 4px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="move_banner">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($b['id']) ?>">
                            <input type="hidden" name="dir" value="-1">
                            <button type="submit" <?= $i === 0 ? 'disabled' : '' ?> style="background: none; border: 1px solid var(--light-gray); padding: 4px 8px; cursor: pointer; font-size: 12px;">↑</button>
                        </form>
                        <form method="POST" style="display: flex; gap: 4px;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="move_banner">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($b['id']) ?>">
                            <input type="hidden" name="dir" value="1">
                            <button type="submit" <?= $i === count($banners) - 1 ? 'disabled' : '' ?> style="background: none; border: 1px solid var(--light-gray); padding: 4px 8px; cursor: pointer; font-size: 12px;">↓</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="toggle_banner">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($b['id']) ?>">
                            <button type="submit" class="status-badge <?= !empty($b['is_active']) ? 'status-paid' : 'status-cancelled' ?>" style="border: none; cursor: pointer; text-decoration: none;">
                                <?= !empty($b['is_active']) ? 'Active' : 'Hidden' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete this banner?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete_banner">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($b['id']) ?>">
                            <button type="submit" style="background: none; border: none; padding: 0; color: var(--red); font-size: 10px; font-weight: 700; text-transform: uppercase; cursor: pointer;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($banners)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 48px; color: var(--mid-gray);">No banners yet — add your first promo above.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
