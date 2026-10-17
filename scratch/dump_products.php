<?php
// scratch/dump_products.php — throwaway: print chip hrefs + header h1 for eyeballing.
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/../core/Session.php';
Session::start();
Session::set('user_role', 'admin');
Session::set('user_id', 1);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET = json_decode($argv[1] ?? '{}', true) ?: [];
chdir(__DIR__ . '/../admin');
ob_start();
include __DIR__ . '/../admin/products.php';
$html = ob_get_clean();

preg_match('/<h1>.*?<\/h1>/s', $html, $m);
echo "H1: " . trim(preg_replace('/\s+/', ' ', $m[0] ?? '(none)')) . "\n\n";

preg_match_all('/<a class="pf-chip" href="([^"]*)"/', $html, $m);
echo "CHIP HREFS:\n";
foreach ($m[1] as $h) echo "  - $h\n";
if (!$m[1]) echo "  (none)\n";

echo "\nPRESET LINKS:\n";
preg_match_all('/<a class="pf-preset[^"]*" href="([^"]*)"/', $html, $m);
foreach ($m[1] as $h) echo "  - $h\n";
if (!$m[1]) echo "  (none)\n";

echo "\nSELECTED OPTIONS:\n";
preg_match_all('/<option value="([^"]*)" selected>/', $html, $m);
foreach ($m[1] as $h) echo "  - $h\n";
if (!$m[1]) echo "  (none)\n";

echo "\n' of ' occurrences:\n";
preg_match_all('/.{25} of .{25}/', strip_tags($html), $m);
foreach (array_slice($m[0], 0, 6) as $h) echo "  - " . trim(preg_replace('/\s+/', ' ', $h)) . "\n";
