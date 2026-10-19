<?php
// scratch/test_product_editor.php — throwaway harness for core/ProductModerationEditor.php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/ProductModerationEditor.php';
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₵');

$db = db();
$pass = 0; $fail = 0;
function check($label, $cond, $detail = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}
function row($db, $id) {
    $s = $db->prepare('SELECT * FROM products WHERE id = ?'); $s->execute([$id]); return $s->fetch();
}
function freshProduct(array $overrides = []) {
    // Build a throwaway product row in a known state, so each test is isolated.
    // NB: db() falls back between MariaDB and SQLite depending on what is
    // running, so the timestamp literal must be driver-aware.
    $db = db();
    $now = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()';
    $slug = 'pe-test-' . bin2hex(random_bytes(4));
    $db->prepare("INSERT INTO products (name, slug, price_ghs, stock_qty, currency, status_market, is_active, created_at)
                  VALUES (?, ?, ?, ?, 'GHS', 'pending_review', 1, $now)")
       ->execute(['PE Test ' . $slug, $slug, 10.00, 5]);
    $id = (int)$db->lastInsertId();
    foreach ($overrides as $col => $val) {
        $db->prepare("UPDATE products SET {$col} = ? WHERE id = ?")->execute([$val, $id]);
    }
    return $id;
}
function cleanup($db, $id) {
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    $db->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
}

echo "── load / options ──\n";
$id = freshProduct();
check('load returns a product', ProductModerationEditor::load($db, $id) !== null);
check('load returns images array', isset(ProductModerationEditor::load($db, $id)['images']));
check('load returns null for missing id', ProductModerationEditor::load($db, 999999999) === null);
$opts = ProductModerationEditor::options($db);
check('options exposes categories', isset($opts['categories']));
check('options exposes brands', isset($opts['brands']));
cleanup($db, $id);

echo "\n── required fields ──\n";
$id = freshProduct();
$r = ProductModerationEditor::save($db, $id, ['name' => '']);
check('empty name rejected', !$r['success'] && isset($r['errors']['name']), json_encode($r['errors'] ?? []));
$r = ProductModerationEditor::save($db, $id, ['stock_qty' => '']);
check('empty stock rejected', !$r['success'] && isset($r['errors']['stock_qty']));
check('stock unchanged after failed save', (int)row($db, $id)['stock_qty'] === 5);

echo "\n── successful save ──\n";
$r = ProductModerationEditor::save($db, $id, ['name' => 'Renamed thing', 'stock_qty' => '42', 'price_ghs' => '19.99']);
check('save succeeds', $r['success'], json_encode($r));
$row = row($db, $id);
check('name written', $row['name'] === 'Renamed thing');
check('stock written as int', (int)$row['stock_qty'] === 42);
check('price rounded to 2dp', (float)$row['price_ghs'] === 19.99);
check('changed list names fields', in_array('name', $r['changed']) && in_array('stock_qty', $r['changed']));

echo "\n── partial semantics (the core guarantee) ──\n";
$before = row($db, $id);
$r = ProductModerationEditor::save($db, $id, ['name' => 'Only the name changes']);
$after = row($db, $id);
check('unrelated columns untouched', $after['price_ghs'] == $before['price_ghs'] && $after['stock_qty'] == $before['stock_qty']);
check('status_market never writable', ProductModerationEditor::save($db, $id, ['status_market' => 'active'])['success'] === true
    && row($db, $id)['status_market'] === 'pending_review', 'status=' . row($db, $id)['status_market']);
check('is_active never writable', ProductModerationEditor::save($db, $id, ['is_active' => '1'])['success'] === true
    && row($db, $id)['is_active'] == 1);
$r = ProductModerationEditor::save($db, $id, ['status_market' => 'active', 'pulled_at' => '2020-01-01', 'slug' => 'hacked', 'sku' => 'HAX']);
$row = row($db, $id);
check('slug/sku/pulled_at ignored', $row['slug'] !== 'hacked' && $row['sku'] !== 'HAX' && $row['pulled_at'] === null);

echo "\n── no-op detection ──\n";
$r = ProductModerationEditor::save($db, $id, ['name' => row($db, $id)['name']]);
check('unchanged save reports no changes', $r['success'] && $r['changed'] === [], json_encode($r));

echo "\n── validation: numbers ──\n";
$r = ProductModerationEditor::save($db, $id, ['stock_qty' => '12abc']);
check('non-numeric stock rejected', !$r['success'] && isset($r['errors']['stock_qty']));
$r = ProductModerationEditor::save($db, $id, ['stock_qty' => '-3']);
check('negative stock rejected', !$r['success'] && isset($r['errors']['stock_qty']));
$r = ProductModerationEditor::save($db, $id, ['price_ghs' => 'abc']);
check('non-numeric price rejected', !$r['success'] && isset($r['errors']['price_ghs']));
$r = ProductModerationEditor::save($db, $id, ['price_ghs' => '-5']);
check('negative price rejected', !$r['success'] && isset($r['errors']['price_ghs']));

echo "\n── validation: compare-at must exceed price ──\n";
$r = ProductModerationEditor::save($db, $id, ['price_ghs' => '50', 'compare_at_price_ghs' => '40']);
check('compare-at below price rejected', !$r['success'] && isset($r['errors']['compare_at_price_ghs']), json_encode($r['errors'] ?? []));
check('nothing written on that failure', (float)row($db, $id)['price_ghs'] !== 50.0);
$r = ProductModerationEditor::save($db, $id, ['price_ghs' => '50', 'compare_at_price_ghs' => '80']);
check('compare-at above price accepted', $r['success'], json_encode($r['errors'] ?? []));
$r = ProductModerationEditor::save($db, $id, ['compare_at_price_ghs' => '']);
check('cleared compare-at becomes null', $r['success'] && row($db, $id)['compare_at_price_ghs'] === null);

echo "\n── validation: currency-conditional price ──\n";
$r = ProductModerationEditor::save($db, $id, ['currency' => 'USD', 'price_usd' => '']);
check('USD product requires a USD price', !$r['success'] && isset($r['errors']['price_usd']), json_encode($r['errors'] ?? []));
$r = ProductModerationEditor::save($db, $id, ['currency' => 'USD', 'price_usd' => '7.50']);
check('USD price accepted', $r['success'], json_encode($r['errors'] ?? []));
check('currency persisted', row($db, $id)['currency'] === 'USD');
$r = ProductModerationEditor::save($db, $id, ['currency' => 'EUR']);
check('non-whitelisted currency rejected', !$r['success'] && isset($r['errors']['currency']));

echo "\n── validation: enums ──\n";
$r = ProductModerationEditor::save($db, $id, ['listing_type' => 'dropship']);
check('bogus listing_type rejected', !$r['success'] && isset($r['errors']['listing_type']));
$r = ProductModerationEditor::save($db, $id, ['listing_type' => 'wholesale']);
check('valid listing_type accepted', $r['success']);
$r = ProductModerationEditor::save($db, $id, ['visibility' => 'nope']);
check('bogus visibility rejected', !$r['success'] && isset($r['errors']['visibility']));

echo "\n── validation: id existence ──\n";
$r = ProductModerationEditor::save($db, $id, ['category_id' => '999999999']);
check('non-existent category rejected', !$r['success'] && isset($r['errors']['category_id']), json_encode($r['errors'] ?? []));
$catId = (int)$db->query('SELECT id FROM categories WHERE parent_id IS NOT NULL LIMIT 1')->fetchColumn();
if ($catId) {
    $r = ProductModerationEditor::save($db, $id, ['category_id' => (string)$catId]);
    check('existing category accepted', $r['success'], json_encode($r['errors'] ?? []));
} else {
    echo "  skip no leaf categories in fixture\n";
}

echo "\n── brand sentinel ──\n";
$beforeBrands = (int)$db->query('SELECT COUNT(*) FROM brands')->fetchColumn();
$r = ProductModerationEditor::save($db, $id, ['brand_id' => '_new', 'brand_name' => '']);
check('new brand without a name rejected', !$r['success'] && isset($r['errors']['brand_id']));
$r = ProductModerationEditor::save($db, $id, ['brand_id' => '_new', 'brand_name' => 'PE Test Brand ' . substr(md5((string)microtime(true)), 0, 6)]);
check('new brand created', $r['success'], json_encode($r['errors'] ?? []));
$afterBrands = (int)$db->query('SELECT COUNT(*) FROM brands')->fetchColumn();
check('brand row actually inserted', $afterBrands === $beforeBrands + 1);
check('product points at the new brand', (int)row($db, $id)['brand_id'] > 0);
$newBrandId = (int)row($db, $id)['brand_id'];
$r = ProductModerationEditor::save($db, $id, ['brand_id' => '_new', 'brand_name' => $db->query("SELECT name FROM brands WHERE id=$newBrandId")->fetchColumn()]);
check('re-creating an existing brand reuses it', $r['success'] && (int)$db->query('SELECT COUNT(*) FROM brands')->fetchColumn() === $afterBrands);

echo "\n── images ──\n";
$db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, 'a.jpg', 1)")->execute([$id]);
$db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, 'b.jpg', 0)")->execute([$id]);
$imgs = ProductModerationEditor::load($db, $id)['images'];
$primaryId = null; $otherId = null;
foreach ($imgs as $im) { if ($im['is_primary']) $primaryId = (int)$im['id']; else $otherId = (int)$im['id']; }
check('two images loaded', count($imgs) === 2);
$r = ProductModerationEditor::setPrimary($db, $id, $otherId);
check('set primary succeeds', $r['success']);
$imgs = ProductModerationEditor::load($db, $id)['images'];
$nowPrimary = null; $countPrimary = 0;
foreach ($imgs as $im) if ($im['is_primary']) { $nowPrimary = (int)$im['id']; $countPrimary++; }
check('exactly one primary after switch', $countPrimary === 1);
check('the right image is primary', $nowPrimary === $otherId);
$r = ProductModerationEditor::setPrimary($db, 999999999, $otherId);
check('set primary on missing product fails', !$r['success']);
$r = ProductModerationEditor::deleteImage($db, $id, $otherId);
check('delete image succeeds', $r['success']);
$imgs = ProductModerationEditor::load($db, $id)['images'];
$fallback = null; foreach ($imgs as $im) if ($im['is_primary']) $fallback = (int)$im['id'];
check('fallback promoted after deleting primary', $fallback === $primaryId, 'primary=' . var_export($fallback, true));
$r = ProductModerationEditor::deleteImage($db, $id, 999999999);
check('deleting a missing image fails', !$r['success']);

echo "\n── uploads ──\n";
$id2 = freshProduct();
// Build a real image file on disk to stand in for an upload. The engine
// accepts any readable temp file when is_uploaded_file() is false, which is
// exactly the CLI-harness path; in a live request it still enforces real uploads.
$tmpPng = sys_get_temp_dir() . '/pe_up_' . bin2hex(random_bytes(3)) . '.png';
$im = imagecreatetruecolor(4, 4);
imagepng($im, $tmpPng);
imagedestroy($im);
$tmpJunk = sys_get_temp_dir() . '/pe_junk_' . bin2hex(random_bytes(3)) . '.jpg';
file_put_contents($tmpJunk, 'this is definitely not an image');
$tmpPdf = sys_get_temp_dir() . '/pe_doc_' . bin2hex(random_bytes(3)) . '.pdf';
file_put_contents($tmpPdf, '%PDF-1.4 not an image');

function packFiles(array $entries): array {
    // Shape $_FILES['images'] exactly as PHP would for a multi-file upload.
    $f = ['name' => [], 'tmp_name' => [], 'error' => [], 'type' => [], 'size' => []];
    foreach ($entries as $e) {
        $f['name'][] = $e['name']; $f['tmp_name'][] = $e['tmp'];
        $f['error'][] = $e['error'] ?? UPLOAD_ERR_OK; $f['type'][] = 'image/png'; $f['size'][] = 100;
    }
    return $f;
}

check('upload rejects an unknown product', !ProductModerationEditor::uploadImages($db, 999999999, packFiles([['name'=>'a.png','tmp'=>$tmpPng]]))['success']);
check('upload rejects an empty file list', !ProductModerationEditor::uploadImages($db, $id2, ['name'=>[], 'tmp_name'=>[], 'error'=>[]])['success']);

$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'a.png', 'tmp' => $tmpPng]]));
check('valid image uploads', $r['success'], json_encode($r));
check('upload reports one added', ($r['added'] ?? 0) === 1);
check('upload returns refreshed images', count($r['images'] ?? []) === 1);
$stored = ProductModerationEditor::load($db, $id2)['images'][0]['url'] ?? '';
check('stored path is project-root relative', strpos($stored, 'public/uploads/products/') === 0, $stored);
check('stored path has no leading slash', $stored !== '' && $stored[0] !== '/', $stored);
$absStored = __DIR__ . '/../' . $stored;
check('file really written to disk', is_file($absStored), $absStored);

// First-ever image must be promoted to primary automatically.
check('first upload becomes primary', (int)(ProductModerationEditor::load($db, $id2)['images'][0]['is_primary'] ?? 0) === 1);

// A second upload onto a product that already has a primary must NOT steal it.
$tmpPng2 = sys_get_temp_dir() . '/pe_up2_' . bin2hex(random_bytes(3)) . '.png';
$im = imagecreatetruecolor(4, 4); imagepng($im, $tmpPng2); imagedestroy($im);
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'b.png', 'tmp' => $tmpPng2]]));
$imgs = ProductModerationEditor::load($db, $id2)['images'];
$primaryCount = 0; $primaryUrl = '';
foreach ($imgs as $im2) if ($im2['is_primary']) { $primaryCount++; $primaryUrl = $im2['url']; }
check('second upload does not steal primary', $primaryCount === 1, 'count=' . $primaryCount);
check('the original image stays primary', $primaryUrl === $stored, "primary=$primaryUrl stored=$stored");

// Rejected content: right extension, wrong bytes.
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'fake.jpg', 'tmp' => $tmpJunk]]));
check('fake jpg with bad content rejected', !$r['success'], json_encode($r));
check('bad-content rejection names the file', strpos(json_encode($r['errors'] ?? []), 'fake.jpg') !== false);
check('no image row added for the fake', count(ProductModerationEditor::load($db, $id2)['images']) === 2);

// Rejected extension.
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'doc.pdf', 'tmp' => $tmpPdf]]));
check('non-image extension rejected', !$r['success']);

// PHP upload error codes surface as readable messages.
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'big.png', 'tmp' => $tmpPng, 'error' => UPLOAD_ERR_INI_SIZE]]));
check('upload error code handled', !$r['success'] && strpos(json_encode($r['errors']), 'exceeds') !== false, json_encode($r['errors'] ?? []));
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name' => 'partial.png', 'tmp' => $tmpPng, 'error' => UPLOAD_ERR_PARTIAL]]));
check('partial upload handled', !$r['success'] && strpos(json_encode($r['errors']), 'partially') !== false);

// Partial success: one good file, one bad — good one lands, error reported.
$tmpPng3 = sys_get_temp_dir() . '/pe_up3_' . bin2hex(random_bytes(3)) . '.png';
$im = imagecreatetruecolor(4, 4); imagepng($im, $tmpPng3); imagedestroy($im);
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([
    ['name' => 'good.png', 'tmp' => $tmpPng3],
    ['name' => 'bad.jpg', 'tmp' => $tmpJunk],
]));
check('partial upload still succeeds', $r['success'], json_encode($r));
check('partial upload reports the skipped file', count($r['errors'] ?? []) === 1);
check('partial upload added the good file', ($r['added'] ?? 0) === 1);

// Multi-file in one call.
$pa = sys_get_temp_dir() . '/pe_m1_' . bin2hex(random_bytes(3)) . '.png';
$pb = sys_get_temp_dir() . '/pe_m2_' . bin2hex(random_bytes(3)) . '.png';
$im = imagecreatetruecolor(4, 4); imagepng($im, $pa); imagepng($im, $pb); imagedestroy($im);
$r = ProductModerationEditor::uploadImages($db, $id2, packFiles([['name'=>'m1.png','tmp'=>$pa], ['name'=>'m2.png','tmp'=>$pb]]));
check('multi-file upload adds both', $r['success'] && ($r['added'] ?? 0) === 2, json_encode($r));
check('filenames do not collide', count(array_unique(array_map(function($x){return $x['url'];}, $r['images'] ?? []))) === count($r['images'] ?? []));

// Tidy up the files this test wrote to the uploads dir.
foreach (ProductModerationEditor::load($db, $id2)['images'] as $im3) {
    @unlink(__DIR__ . '/../' . $im3['url']);
}
@unlink($tmpPng); @unlink($tmpPng2); @unlink($tmpPng3); @unlink($pa); @unlink($pb);
@unlink($tmpJunk); @unlink($tmpPdf);
cleanup($db, $id2);

echo "\n── missing product ──\n";
$r = ProductModerationEditor::save($db, 999999999, ['name' => 'x']);
check('save on missing product fails', !$r['success']);

cleanup($db, $id);
echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
