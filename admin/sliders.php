<?php
// admin/sliders.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';
require_once '../models/Slider.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$sliderModel = new Slider();
$slides = $sliderModel->getAllRecords();

// Handle Delete Action
if (isset($_GET['delete'])) {
    $sliderModel->delete($_GET['delete']);
    header('Location: sliders.php?msg=deleted');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $slide = $sliderModel->findById($_GET['toggle']);
    if ($slide) {
        $sliderModel->update($slide['id'], ['is_active' => $slide['is_active'] ? 0 : 1]);
    }
    header('Location: sliders.php');
    exit;
}

$title = "Hero Slider Manager";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Hero Sliders</h1>
    <a href="add-slide.php" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center;">+ Create New Slide</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div style="background: #e6f7ec; color: #00a854; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #00a854;">
        Action completed successfully.
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Active & Scheduled Slides</div>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Slide Content</th>
                    <th>Target Page</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $s): ?>
                <tr>
                    <td style="width: 120px;">
                        <img src="<?= APP_URL ?>/<?= $s['image_url'] ?>" style="width: 100px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid var(--light-gray);">
                    </td>
                    <td>
                        <div style="font-weight: 700; font-size: 14px; margin-bottom: 4px;"><?= $s['heading'] ?></div>
                        <div style="font-size: 11px; color: var(--mid-gray); max-width: 300px; line-height: 1.4;"><?= $s['subheading'] ?></div>
                    </td>
                    <td>
                        <span style="font-family: var(--f-mono); font-size: 11px; background: var(--off); padding: 4px 8px; border-radius: 4px; color: var(--ink);">
                            <?= $s['page_path'] === '*' ? 'Global' : $s['page_path'] ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--mid-gray);">
                            <?= str_replace('-', ' ', $s['template_type']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle=<?= $s['id'] ?>" class="status-badge <?= $s['is_active'] ? 'status-paid' : 'status-cancelled' ?>" style="text-decoration: none; cursor: pointer;">
                            <?= $s['is_active'] ? 'Active' : 'Draft' ?>
                        </a>
                    </td>
                    <td>
                        <div style="display: flex; gap: 16px;">
                            <a href="edit-slide.php?id=<?= $s['id'] ?>" class="nav-link" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">Edit</a>
                            <a href="?delete=<?= $s['id'] ?>" class="nav-link" style="font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--red);" onclick="return confirm('Archive this slide?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($slides)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--mid-gray);">No hero slides found. Your homepage will look pretty empty!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
