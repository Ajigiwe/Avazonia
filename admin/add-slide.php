<?php
// admin/add-slide.php
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
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'heading' => $_POST['heading'] ?? '',
        'subheading' => $_POST['subheading'] ?? '',
        'cta_text' => $_POST['cta_text'] ?? 'Shop Now',
        'cta_link' => $_POST['cta_link'] ?? '/shop',
        'page_path' => $_POST['page_path'] ?? '/',
        'template_type' => $_POST['template_type'] ?? 'split',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'order_priority' => (int)($_POST['order_priority'] ?? 0)
    ];

    // Image Upload Handling
    $image_url = $_POST['image_url_manual'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../public/uploads/sliders/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExt, $allowed)) {
            $fileName = 'slide_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image_url = 'public/uploads/sliders/' . $fileName;
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
        }
    }

    if (!$error && empty($image_url)) {
        $error = "Please upload a slide image or provide a URL.";
    }

    if (!$error) {
        $data['image_url'] = $image_url;
        if ($sliderModel->create($data)) {
            $success = "Hero slide created successfully!";
            header('Refresh: 2; URL=sliders.php');
        } else {
            $error = "Failed to save slide to database.";
        }
    }
}

$title = "Create New Slide";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Create Hero Slide</h1>
    <a href="sliders.php" class="nav-link" style="font-size: 10px;">← Back to Manager</a>
</div>

<div class="panel" style="max-width: 900px;">
    <div class="panel-header">
        <div class="panel-title">Visual & Content Designer</div>
    </div>
    <div style="padding: 40px;">
        <?php if ($error): ?>
            <div style="background: #fff1f0; color: #f5222d; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #f5222d;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background: #e6f7ec; color: #00a854; padding: 16px; margin-bottom: 24px; font-size: 13px; border-left: 4px solid #00a854;">
                <?= $success ?> Redirecting...
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <!-- Left Side: Content -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Primary Heading</label>
                    <input type="text" name="heading" placeholder="e.g. PURE SOUND ONYX" required style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>
                
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Subheading / Paragraph</label>
                    <textarea name="subheading" rows="4" placeholder="Brief promotional description..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; resize: none;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Button Text</label>
                        <input type="text" name="cta_text" value="Shop Now" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                    <div>
                        <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Action Link</label>
                        <input type="text" name="cta_link" value="/shop" placeholder="/deals" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    </div>
                </div>

                <div style="background: var(--off); padding: 20px; border-radius: 4px; border: 1px solid var(--light-gray);">
                    <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: var(--ink); font-weight: 700;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Active & Visible</span>
                    </label>
                </div>
            </div>

            <!-- Right Side: Imagery & Targeting -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Target Page Path</label>
                    <input type="text" name="page_path" placeholder="/shop (Leave / for Home)" value="/" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                    <p style="font-size: 10px; color: var(--mid-gray); margin-top: 6px;">Use '*' to show this slide on EVERY page with a hero area.</p>
                </div>

                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Visual Template</label>
                    <select name="template_type" style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                        <option value="split">Modern Split (Left Content / Right Image)</option>
                        <option value="full-width">Full Immersive (Background Image)</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Upload Slide Artwork</label>
                    <input type="file" name="image" accept="image/*" style="width: 100%; padding: 9px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 11px;">
                </div>

                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Or Image URL</label>
                    <input type="url" name="image_url_manual" placeholder="https://..." style="width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray);">Display Priority</label>
                    <input type="number" name="order_priority" value="0" style="width: 80px; padding: 10px; border: 1px solid var(--light-gray);">
                    <p style="font-size: 10px; color: var(--mid-gray);">Lower numbers appear first.</p>
                </div>

                <button type="submit" class="btn-red" style="height: 52px; justify-content: center; font-size: 11px; letter-spacing: 0.1em; margin-top: 12px;">Create Hero Slide</button>
            </div>
        </form>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
