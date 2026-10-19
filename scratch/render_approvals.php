<?php
// scratch/render_approvals.php — throwaway: render admin/approvals.php as an admin.
error_reporting(E_ALL);
ini_set('display_errors', '1');
$warnings = [];
set_error_handler(function ($no, $str, $file, $line) use (&$warnings) {
    $warnings[] = "$str @ $file:$line"; return true;
});
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/database.php';
Session::start();
Session::set('user_role', 'admin');
Session::set('user_id', 1);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

// Seed a pending row so the row-level markup (edit button, decision buttons)
// is actually exercised. The local catalogue is otherwise all-active, which
// would silently test nothing. NB: the slug is inlined rather than bound —
// with emulated prepares a '?' inside a quoted literal is still counted as a
// placeholder, which breaks the parameter numbering.
$db = db();
$now = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()';
$seedSlug = 'render-seed-' . substr(md5((string)microtime(true)), 0, 8);
$db->prepare("INSERT INTO products (name, slug, price_ghs, stock_qty, currency, status_market, is_active, created_at)
              VALUES ('Render Seed Product', '$seedSlug', 12.34, 3, 'GHS', 'pending_review', 1, $now)")
   ->execute();
$seedId = (int)$db->lastInsertId();

chdir(__DIR__ . '/../admin');
ob_start();
try { include __DIR__ . '/../admin/approvals.php'; }
catch (Throwable $e) {
    ob_end_clean();
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([$seedId]);
    fwrite(STDERR, "FATAL: {$e->getMessage()}\n  at {$e->getFile()}:{$e->getLine()}\n");
    exit(1);
}
$html = ob_get_clean();
$db->prepare('DELETE FROM products WHERE id = ?')->execute([$seedId]);

$pass = 0; $fail = 0;
function check($label, $cond, $detail = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}

check('renders without PHP warnings', !$warnings, implode(' | ', array_slice($warnings, 0, 3)));
check('drawer markup present', strpos($html, 'id="pev"') !== false);
check('drawer backdrop present', strpos($html, 'id="pev-backdrop"') !== false);
check('save button present', strpos($html, 'id="pev-save"') !== false);
check('save & approve button present', strpos($html, 'id="pev-approve"') !== false);
check('edit buttons replace the old links', strpos($html, 'class="pe-edit-btn"') !== false);
check('no leftover edit-product.php link in queue', !preg_match('/<a href="edit-product\.php\?id=\d+"/', $html));
check('endpoints referenced correctly', strpos($html, "api/product-edit.php") !== false);
check('approve/reject buttons still present', strpos($html, 'value="active"') !== false && strpos($html, 'value="rejected"') !== false);
check('csrf meta available for ajax', true); // emitted by layout/header.php, checked below

// The header emits the CSRF meta tag the drawer reads.
preg_match('/<meta name="csrf-token" content="([^"]*)"/', $html, $m);
check('csrf meta tag emitted with a token', !empty($m[1]));

// The drawer needs the install base to resolve root-relative image paths.
preg_match('/<aside class="pev"[^>]*data-base="([^"]*)"/', $html, $b);
check('drawer emits data-base for image paths', isset($b[1]), 'no data-base attribute found');
check('data-base matches the APP_URL path', ($b[1] ?? null) === rtrim(parse_url(APP_URL, PHP_URL_PATH) ?: '', '/'),
    'got=' . var_export($b[1] ?? null, true) . ' want=' . var_export(rtrim(parse_url(APP_URL, PHP_URL_PATH) ?: '', '/'), true));
check('imgUrl resolver present in drawer script', strpos($html, 'function imgUrl(') !== false);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
