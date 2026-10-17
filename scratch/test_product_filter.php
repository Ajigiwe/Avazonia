<?php
// scratch/test_product_filter.php — throwaway harness for core/ProductFilter.php
error_reporting(E_ALL);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/ProductFilter.php';

if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₵');
$db = db();
$pass = 0; $fail = 0;

function check($label, $cond, $detail = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}

// Run a filter through the full page query shape and return [count, rows].
function run($db, array $get) {
    $f = new ProductFilter($get);
    $count = $f->rowCount($db);
    $sql = "SELECT p.id, p.name, p.price_ghs, p.created_at, p.stock_qty, p.status_market, p.is_active
            FROM products p {$f->whereSql()} GROUP BY p.id ORDER BY {$f->orderBy()} LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute($f->params());
    return [$f, $count, $stmt->fetchAll()];
}

$base = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
echo "products in db: $base\n";

// 1. No filters → everything, no WHERE.
[$f, $c, $rows] = run($db, []);
check('empty GET returns full catalogue', $c === $base, "got $c want $base");
check('empty GET emits no WHERE', $f->whereSql() === '', $f->whereSql());
check('empty GET is not active', !$f->isActive());

// 2. Name search.
[$f, $c, $rows] = run($db, ['q' => 'a']);
check('q=a narrows or equals list', $c <= $base && $c > 0, "count=$c");
check('q=a is active', $f->isActive());
$allLike = $db->query("SELECT COUNT(*) FROM products WHERE name LIKE '%a%' OR brand_name LIKE '%a%' OR sku LIKE '%a%'")->fetchColumn();
check('q=a count matches hand-written LIKE', $c === (int)$allLike, "engine=$c hand=$allLike");

// 3. LIKE wildcards in the term must be literal, not operators.
[$f, $c, $rows] = run($db, ['q' => '%']);
check('q=% treated literally (no match on empty sku rows only)', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE name LIKE '%%!%%' ESCAPE '!' OR brand_name LIKE '%%!%%' ESCAPE '!' OR sku LIKE '%%!%%' ESCAPE '!'")->fetchColumn(), "engine=$c");
check('q=% does not match every row', $c < $base, "count=$c base=$base");

// 4. SQL injection attempt in q is inert.
[$f, $c, $rows] = run($db, ['q' => "'; DROP TABLE products; --"]);
check('injection in q is harmless', $c >= 0 && $db->query("SELECT COUNT(*) FROM products")->fetchColumn() == $base);

// 5. Enum validation: bogus status is dropped, real one applies.
[$f, $c, $rows] = run($db, ['status' => 'not_a_status']);
check('bogus status ignored', $f->whereSql() === '', $f->whereSql());
[$f, $c, $rows] = run($db, ['status' => 'pending_review']);
check('status=pending_review filters', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='pending_review'")->fetchColumn(), "count=$c");

// 6. vis enum uses the map fragment (tinyint flag).
[$f, $c, $rows] = run($db, ['vis' => 'hidden']);
check('vis=hidden matches is_active=0', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=0")->fetchColumn(), "count=$c");
[$f, $c, $rows] = run($db, ['vis' => 'active']);
check('vis=active matches is_active=1', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(), "count=$c");

// 7. stock enum map fragments.
[$f, $c, $rows] = run($db, ['stock' => 'out']);
check('stock=out matches 0 units', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE stock_qty=0")->fetchColumn(), "count=$c");
[$f, $c, $rows] = run($db, ['stock' => 'low']);
check('stock=low matches 1-4 units', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE stock_qty>0 AND stock_qty<5")->fetchColumn(), "count=$c");

// 8. Time uploaded: preset and explicit range agree.
[$f, $c, $rows] = run($db, ['when' => 'today']);
$todayStart = date('Y-m-d') . ' 00:00:00';
check('when=today matches >= midnight', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE created_at >= '$todayStart'")->fetchColumn(), "count=$c");
[$f, $c, $rows] = run($db, ['from' => '2020-01-01', 'to' => '2020-12-31']);
$hand = (int)$db->query("SELECT COUNT(*) FROM products WHERE created_at >= '2020-01-01 00:00:00' AND created_at <= '2020-12-31 23:59:59'")->fetchColumn();
check('from+to range matches hand-written bounds', $c === $hand, "engine=$c hand=$hand");

// 9. Invalid dates rejected (incl. impossible calendar dates).
[$f, $c, $rows] = run($db, ['from' => '2020-13-45']);
check('malformed date dropped', $f->whereSql() === '', $f->whereSql());
[$f, $c, $rows] = run($db, ['from' => "2020-01-01' OR '1'='1"]);
check('injection in date dropped', $f->whereSql() === '', $f->whereSql());

// 10. int filters reject junk and non-positive ids.
[$f, $c, $rows] = run($db, ['brand' => 'abc']);
check('non-numeric brand dropped', $f->whereSql() === '');
[$f, $c, $rows] = run($db, ['brand' => '-5']);
check('negative brand dropped', $f->whereSql() === '');
$someBrand = (int)$db->query("SELECT brand_id FROM products WHERE brand_id IS NOT NULL LIMIT 1")->fetchColumn();
[$f, $c, $rows] = run($db, ['brand' => (string)$someBrand]);
check('valid brand filters to that brand', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE brand_id=$someBrand")->fetchColumn(), "count=$c");

// 11. float range.
[$f, $c, $rows] = run($db, ['price_min' => '100', 'price_max' => '500']);
check('price range filters', $c === (int)$db->query("SELECT COUNT(*) FROM products WHERE price_ghs >= 100 AND price_ghs <= 500")->fetchColumn(), "count=$c");
[$f, $c, $rows] = run($db, ['price_min' => 'abc']);
check('non-numeric price dropped', $f->whereSql() === '');

// 12. Combined filters AND together.
[$f, $c, $rows] = run($db, ['status' => 'active', 'vis' => 'active', 'q' => 'a']);
$hand = (int)$db->query("SELECT COUNT(*) FROM products WHERE status_market='active' AND is_active=1 AND (name LIKE '%a%' OR brand_name LIKE '%a%' OR sku LIKE '%a%')")->fetchColumn();
check('combined filters AND together', $c === $hand, "engine=$c hand=$hand");

// 13. Sort whitelist: valid key changes order, bogus key falls back to default.
[$f, $c, $rows] = run($db, ['sort' => 'price_desc']);
$prices = array_map(function ($r) { return (float)$r['price_ghs']; }, $rows);
$sorted = $prices; rsort($sorted);
check('sort=price_desc returns descending prices', count($prices) < 50 || $prices === $sorted);
[$f, $c, $rows] = run($db, ['sort' => 'created_at DESC; DROP TABLE products']);
check('bogus sort falls back to default', $f->orderBy() === ProductFilter::SORTS['newest'], $f->orderBy());

// 14. Chips + URL round-trip.
$f = new ProductFilter(['q' => 'hilux', 'status' => 'pending_review', 'when' => '7d', 'page' => '3']);
$f->setLabels(['brand' => [7 => 'Nike']]);
$chips = $f->chips();
check('chips emitted per active filter', count($chips) === 3, 'got ' . count($chips));
$labels = array_column($chips, 'label');
check('q chip is quoted', in_array('Search: “hilux”', $labels), implode(' | ', $labels));
check('status chip uses option label', in_array('Status: Pending review', $labels), implode(' | ', $labels));
check('when chip uses preset label', in_array('Uploaded: Last 7 days', $labels), implode(' | ', $labels));
$firstUrl = $chips[0]['url'];
check('chip url carries remaining filters', strpos($firstUrl, 'status=pending_review') !== false && strpos($firstUrl, 'when=7d') !== false, $firstUrl);
check('chip url drops only its own filter', strpos($firstUrl, 'q=') === false, $firstUrl);
check('chip url never carries page', strpos($firstUrl, 'page=') === false, $firstUrl);
check('clear url is bare', $f->clearUrl() === 'products.php');

// 15. Label map surfaces in chips.
$f = new ProductFilter(['brand' => '7']);
$f->setLabels(['brand' => [7 => 'Nike']]);
check('id chip shows the name', $f->chips()[0]['label'] === 'Brand: Nike', $f->chips()[0]['label']);
$f2 = new ProductFilter(['brand' => '7']);
check('id chip falls back to #id', $f2->chips()[0]['label'] === 'Brand: #7', $f2->chips()[0]['label']);

// 16. Pagination URL building.
$f = new ProductFilter(['q' => 'a', 'status' => 'active']);
$p2 = $f->url(['page' => 2]);
check('url() keeps filters and adds page', strpos($p2, 'q=a') !== false && strpos($p2, 'status=active') !== false && strpos($p2, 'page=2') !== false, $p2);
$p1 = $f->url(['page' => 1]);
check('url(page=1) drops page', strpos($p1, 'page=') === false, $p1);

// 17. Array-valued query params are ignored, not crashed on.
[$f, $c, $rows] = run($db, ['q' => ['array', 'attack'], 'status' => ['x']]);
check('array query params ignored', $f->whereSql() === '', $f->whereSql());

// 18. value() re-populates the form with validated values only.
$f = new ProductFilter(['q' => ' hilux ', 'brand' => '7', 'junk' => 'x']);
check('value() trims q', $f->value('q') === 'hilux');
check('value() returns int for brand', $f->value('brand') === 7);
check('value() default for unknown', $f->value('nope', 'dflt') === 'dflt');

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
