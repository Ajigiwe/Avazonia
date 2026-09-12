<?php
// wm_test.php — TEMPORARY smoke test for the watermark engine. Delete after use.
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Watermark.php';
header('Content-Type: text/plain');

$secret = 'avazonia_debug';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) { http_response_code(403); die('forbidden'); }

echo "gd_loaded=" . (function_exists('imagecreatetruecolor') ? 'yes' : 'no') . "\n";
echo "jpeg=" . (function_exists('imagecreatefromjpeg') ? 'y' : 'n');
echo " png=" . (function_exists('imagecreatefrompng') ? 'y' : 'n');
echo " webp=" . (function_exists('imagecreatefromwebp') ? 'y' : 'n') . "\n";
echo "setting=" . (Watermark::enabled() ? 'on' : 'off') . "\n";

// Build a 640x480 test JPEG inside the products upload dir
$testRel = 'public/uploads/products/_wm_test_' . bin2hex(random_bytes(3)) . '.jpg';
$abs = __DIR__ . '/' . $testRel;
$im = imagecreatetruecolor(640, 480);
imagefill($im, 0, 0, imagecolorallocate($im, 200, 205, 210));
imagejpeg($im, $abs, 90); imagedestroy($im);

$ok = Watermark::applyToFile($testRel);
echo "stamp=" . ($ok ? 'ok' : 'FAILED') . "\n";

// Confirm the file actually changed (size grew after logo overlay)
clearstatcache();
echo "size_after=" . filesize($abs) . "\n";
@unlink($abs);
echo "cleanup=" . (!file_exists($abs) ? 'done' : 'FAILED') . "\n";
