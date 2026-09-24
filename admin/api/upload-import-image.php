<?php
// admin/api/upload-import-image.php
// AJAX endpoint: Upload product images during import preview
// Stores images temporarily; after import they get linked to the created product.
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Session.php';

Session::start();
header('Content-Type: application/json');

if (Session::get('user_role') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST only']);
    exit;
}

$line = (int)($_POST['line'] ?? 0);
if (!$line) {
    echo json_encode(['success' => false, 'error' => 'Missing line number']);
    exit;
}

$file = $_FILES['image'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No valid image file uploaded']);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid image type. Use JPEG, PNG, WebP or GIF.']);
    exit;
}

$ext = ['image/jpeg' => 'jpeg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? 'jpeg';
$uploadDir = realpath(__DIR__ . '/../../public/uploads/products');
if (!$uploadDir) {
    @mkdir(__DIR__ . '/../../public/uploads/products', 0755, true);
    $uploadDir = realpath(__DIR__ . '/../../public/uploads/products');
}

$filename = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $line . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

$url = '/public/uploads/products/' . $filename;

// Store in session for later linking after import
$importImages = Session::get('import_images') ?? [];
if (!isset($importImages[$line])) {
    $importImages[$line] = [];
}
$importImages[$line][] = $url;
Session::set('import_images', $importImages);

echo json_encode([
    'success' => true,
    'url' => APP_URL . $url,
    'path' => $url,
    'line' => $line,
    'count' => count($importImages[$line])
]);
