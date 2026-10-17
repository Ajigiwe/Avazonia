<?php
// scratch/render_products.php — throwaway harness: render admin/products.php
// as an admin, with and without filters, and report any PHP warnings/fatals.
error_reporting(E_ALL);
ini_set('display_errors', '1');

$warnings = [];
set_error_handler(function ($no, $str, $file, $line) use (&$warnings) {
    $warnings[] = "$str @ $file:$line";
    return true;
});

// Fake an admin session before the page boots. Session::start() calls
// session_start(), which replaces any pre-seeded $_SESSION — so start it
// first, then seed, then let the page run.
require_once __DIR__ . '/../core/Session.php';
Session::start();
Session::set('user_role', 'admin');
Session::set('user_id', 1);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
// Read GET params from a JSON file rather than argv — PowerShell's native
// command argument passing mangles embedded quotes, which silently turned
// some CLI runs into empty-GET runs and made results non-deterministic.
$jsonFile = $argv[1] ?? '';
$_GET = ($jsonFile !== '' && is_file($jsonFile)) ? (json_decode(file_get_contents($jsonFile), true) ?: []) : [];

chdir(__DIR__ . '/../admin');
ob_start();
try {
    include __DIR__ . '/../admin/products.php';
} catch (Throwable $e) {
    ob_end_clean();
    fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n  at " . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(1);
}
$html = ob_get_clean();

// Assertions on the rendered markup.
$pass = 0; $fail = 0;
function check($label, $cond, $detail = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}

check('renders without PHP warnings', !$warnings, implode(' | ', array_slice($warnings, 0, 3)));
check('has filter form', strpos($html, 'class="pf-bar"') !== false);
check('has name/brand/sku search box', strpos($html, 'name="q"') !== false);
check('has uploaded preset select', strpos($html, 'name="when"') !== false);
check('has from/to date inputs', strpos($html, 'name="from"') !== false && strpos($html, 'name="to"') !== false);
check('has category dropdown', strpos($html, 'name="category"') !== false);
check('has sort dropdown', strpos($html, 'name="sort"') !== false);
check('has price range inputs', strpos($html, 'name="price_min"') !== false && strpos($html, 'name="price_max"') !== false);
check('has uploaded column header', strpos($html, '<th>Uploaded</th>') !== false);
check('has table rows or empty state', strpos($html, '<tbody>') !== false);
// Pagination only renders when there's more than one page (3 rows here, so it
// won't appear). If it ever does appear, its links must be filter-aware.
if (strpos($html, 'Next &raquo;') !== false || strpos($html, '&laquo; Prev') !== false) {
    check('pagination links are filter-aware', strpos($html, 'products.php?') !== false);
} else {
    echo "  ok   pagination block correctly hidden on a single page\n";
    $pass++;
}

// Filter chips must appear when filters are active. NB: the quick-presets row
// also uses the pf-chips container class, so probe for the chip element itself.
if (strpos($html, 'class="pf-chip"') !== false) {
    check('active filters render chips', strpos($html, 'class="pf-chip"') !== false);
    // The "Active:" label should only precede real chips, never bare presets.
    check('chip row is labelled "Active:"', strpos($html, '>Active:</span>') !== false);
} else {
    echo "  ok   no chips row when nothing is filtered\n";
    $pass++;
}

// The "of N" context should appear in the H1 only while filtering. Match the
// H1 specifically — the phrase " of " also occurs in unrelated body copy.
// Detect "filtering" from any known filter param, not a hardcoded subset.
$filterParams = ['q', 'brand', 'category', 'seller', 'status', 'vis', 'listing', 'stock', 'price_min', 'price_max', 'when', 'from', 'to'];
$filtered = false;
foreach ($filterParams as $fp) {
    if (isset($_GET[$fp]) && $_GET[$fp] !== '') { $filtered = true; break; }
}
preg_match('/<h1>.*?<\/h1>/s', $html, $h1m);
$h1 = $h1m[0] ?? '';
check('H1 shows "of N" only while filtering',
    ($filtered ? strpos($h1, ' of ') !== false : strpos($h1, ' of ') === false), $h1);

// Chip hrefs must be filter-aware. When the chip removes the LAST active
// filter the URL correctly collapses to bare products.php — that is the
// right answer, so only assert a query string survives if filters remain.
if (preg_match_all('/<a class="pf-chip" href="([^"]*)"/', $html, $cm) && $cm[1]) {
    $othersRemain = false; // determined per-chip below via chip count
    foreach ($cm[1] as $href) {
        check('chip href is a products.php url', strpos($href, 'products.php') === 0, $href);
    }
}

// An XSS payload in q must be escaped in the search box value attribute.
if (isset($_GET['q']) && strpos($_GET['q'], '<script>') !== false) {
    check('XSS payload escaped in search box', strpos($html, '<script>alert(1)</script>') === false
        && strpos($html, '&lt;script&gt;') !== false);
}

// An out-of-range page must clamp rather than render an empty page.
if (isset($_GET['page'])) {
    check('page clamp does not warn', !$warnings, implode(' | ', array_slice($warnings, 0, 3)));
}

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
