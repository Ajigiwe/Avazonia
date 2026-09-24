<?php
// admin/api/upload-import-video.php
// AJAX endpoint: Upload product video during import preview
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

$file = $_FILES['video'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No valid video file uploaded']);
    exit;
}

$allowed = ['video/mp4', 'video/webm', 'video/quicktime'];
$mime = '';
if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
} elseif (function_exists('mime_content_type')) {
    $mime = mime_content_type($file['tmp_name']);
} else {
    $mime = $file['type'];
}

if (!in_array($mime, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid video type. Use MP4, WebM or MOV.']);
    exit;
}

$ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'][$mime] ?? 'mp4';
$uploadDir = realpath(__DIR__ . '/../../public/uploads/videos');
if (!$uploadDir) {
    @mkdir(__DIR__ . '/../../public/uploads/videos', 0755, true);
    $uploadDir = realpath(__DIR__ . '/../../public/uploads/videos');
}

if (!$uploadDir) {
    echo json_encode(['success' => false, 'error' => 'Server configuration error: Upload directory unavailable']);
    exit;
}

$filename = 'v_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $line . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (!@move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file on server']);
    exit;
}

$url = '/public/uploads/videos/' . $filename;

// Unlike images, we don't need to store it in session array because JS sets the URL into the input field directly.
// The JS then sends the video_url as part of the overrides.

echo json_encode([
    'success' => true,
    'url' => APP_URL . $url,
    'path' => $url,
    'line' => $line
]);
