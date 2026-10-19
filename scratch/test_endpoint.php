<?php
// scratch/test_endpoint.php — throwaway: real HTTP round-trip against
// admin/api/product-edit.php via PHP's built-in server, exercising CSRF,
// auth, validation errors, a successful save, and image ops.
//
// Usage: php scratch/test_endpoint.php   (starts/stops its own server)
$base = 'http://127.0.0.1:8399';
$root = realpath(__DIR__ . '/..');

// ── Boot a server in a session that is already an admin ──────────────────
$bootstrap = __DIR__ . '/_ep_bootstrap.php';
file_put_contents($bootstrap, '<?php
require_once __DIR__ . "/../core/Session.php";
Session::start();
Session::set("user_role", "admin");
Session::set("user_id", 1);
');

$cmd = sprintf('"%s" -S 127.0.0.1:8399 -t "%s"', PHP_BINARY, escapeshellarg($root));
$proc = proc_open($cmd, [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root);
if (!$proc) { fwrite(STDERR, "could not start server\n"); exit(1); }
sleep(1);

$pass = 0; $fail = 0;
function check($label, $cond, $detail = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok   $label\n"; }
    else { $fail++; echo "  FAIL $label" . ($detail !== '' ? " — $detail" : '') . "\n"; }
}

// Fetch a CSRF token by loading the bootstrap page (it shares the session).
$cookieJar = tempnam(sys_get_temp_dir(), 'epc');
$ch = curl_init("$base/scratch/_ep_bootstrap.php");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar]);
curl_exec($ch);
curl_close($ch);

// Re-run to capture the token the session now holds.
$tokenScript = __DIR__ . '/_ep_token.php';
file_put_contents($tokenScript, '<?php
require_once __DIR__ . "/../core/Session.php";
require_once __DIR__ . "/../core/Csrf.php";
Session::start();
echo Csrf::token();
');
$ch = curl_init("$base/scratch/_ep_token.php");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar]);
$token = curl_exec($ch);
curl_close($ch);
check('obtained a csrf token', strlen($token) > 10, "token=" . var_export($token, true));

// ── Seed a pending product ───────────────────────────────────────────────
require_once $root . '/config/app.php';
require_once $root . '/config/database.php';
$db = db();
$now = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()';
$slug = 'ep-test-' . substr(md5((string)microtime(true)), 0, 8);
$db->prepare("INSERT INTO products (name, slug, price_ghs, stock_qty, currency, status_market, is_active, created_at)
              VALUES ('EP Seed', '$slug', 10.00, 5, 'GHS', 'pending_review', 1, $now)")->execute();
$pid = (int)$db->lastInsertId();
$db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, 'ep.jpg', 1)")->execute([$pid]);
$imgId = (int)$db->lastInsertId();

function post($base, $cookieJar, $fields, $withCsrf = true) {
    global $token;
    $ch = curl_init("$base/admin/api/product-edit.php");
    $f = $fields;
    if ($withCsrf) $f['_csrf_token'] = $token;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($f),
        CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_HEADER => true,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $split = strpos($raw, "\r\n\r\n");
    return ['code' => $code, 'body' => substr($raw, $split + 4), 'json' => json_decode(substr($raw, $split + 4), true)];
}

// 1. Missing CSRF is rejected.
$r = post($base, $cookieJar, ['action' => 'load', 'product_id' => $pid], false);
check('request without csrf is rejected', $r['code'] === 403, 'code=' . $r['code']);

// 2. Bad product id.
$r = post($base, $cookieJar, ['action' => 'load', 'product_id' => 0]);
check('zero product id rejected', $r['code'] === 400);

// 3. Missing product.
$r = post($base, $cookieJar, ['action' => 'load', 'product_id' => 999999999]);
check('missing product 404s', $r['code'] === 404, 'code=' . $r['code']);

// 4. Load succeeds.
$r = post($base, $cookieJar, ['action' => 'load', 'product_id' => $pid]);
check('load returns success', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
check('load returns the product row', ($r['json']['product']['name'] ?? '') === 'EP Seed');
check('load returns images', count($r['json']['images'] ?? []) === 1);
check('load returns dropdown options', isset($r['json']['options']['categories']));

// 4b. Real multipart upload — the only path that exercises move_uploaded_file().
// curl's CURLFile makes PHP build a true multipart/form-data body with an
// uploaded temp file, so is_uploaded_file() returns true server-side, exactly
// as it would from the browser's drop zone.
function postMultipart($base, $cookieJar, array $fields, array $files) {
    global $token;
    $ch = curl_init("$base/admin/api/product-edit.php");
    $fields['_csrf_token'] = $token;
    $post = [];
    foreach ($fields as $k => $v) $post[$k] = $v;
    foreach ($files as $k => $list) {
        foreach ($list as $i => $f) $post[$k . '[' . $i . ']'] = new CURLFile($f['path'], $f['type'], $f['name']);
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_HEADER => true,
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $split = strpos($raw, "\r\n\r\n");
    return ['code' => $code, 'body' => substr($raw, $split + 4), 'json' => json_decode(substr($raw, $split + 4), true)];
}

// A real 1x1 PNG, written to disk so CURLFile can send it.
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$upPng = sys_get_temp_dir() . '/ep_up_' . substr(md5((string)microtime(true)), 0, 8) . '.png';
file_put_contents($upPng, $png);
// A file with a .jpg extension that is not an image at all.
$upJunk = sys_get_temp_dir() . '/ep_junk_' . substr(md5((string)microtime(true)), 0, 8) . '.jpg';
file_put_contents($upJunk, 'not an image');

$r = postMultipart($base, $cookieJar, ['action' => 'upload_images', 'product_id' => $pid], [
    'images' => [['path' => $upPng, 'type' => 'image/png', 'name' => 'ep-upload.png']],
]);
check('multipart upload succeeds', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
check('upload reports one added', ($r['json']['added'] ?? 0) === 1);
check('upload returns the image list', count($r['json']['images'] ?? []) >= 1, json_encode($r['json'] ?? []));
$uploadedUrl = $r['json']['images'][count($r['json']['images']) - 1]['url'] ?? '';
check('uploaded file written under uploads dir', strpos($uploadedUrl, 'public/uploads/products/') === 0, $uploadedUrl);
check('uploaded file exists on disk', is_file($root . '/' . $uploadedUrl), $root . '/' . $uploadedUrl);
check('the pre-seeded image is still primary',
    (int)($r['json']['images'][0]['is_primary'] ?? 0) === 1,
    json_encode($r['json']['images'] ?? []));

// The junk file must be refused by the content check, not just the extension.
$r = postMultipart($base, $cookieJar, ['action' => 'upload_images', 'product_id' => $pid], [
    'images' => [['path' => $upJunk, 'type' => 'image/jpeg', 'name' => 'ep-fake.jpg']],
]);
check('non-image content rejected over http', $r['code'] === 422, 'code=' . $r['code'] . ' body=' . $r['body']);
check('rejection names the offending file', strpos(json_encode($r['json']['errors'] ?? []), 'ep-fake.jpg') !== false);

// Upload with no CSRF is refused before any file is touched.
$ch = curl_init("$base/admin/api/product-edit.php");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['action' => 'upload_images', 'product_id' => $pid,
        'images[0]' => new CURLFile($upPng, 'image/png', 'ep-upload.png')],
    CURLOPT_COOKIEJAR => $cookieJar, CURLOPT_COOKIEFILE => $cookieJar, CURLOPT_HEADER => true,
]);
$raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
check('upload without csrf is rejected', $code === 403, 'code=' . $code);
@unlink($upPng); @unlink($upJunk);

// 5. Validation error surfaces as 422 with a field-keyed bag.
$r = post($base, $cookieJar, ['action' => 'save', 'product_id' => $pid, 'name' => '', 'stock_qty' => '5']);
check('invalid save is 422', $r['code'] === 422, 'code=' . $r['code']);
check('error bag keyed by field', isset($r['json']['errors']['name']), json_encode($r['json']));

// 6. Successful save returns the refreshed row.
$r = post($base, $cookieJar, ['action' => 'save', 'product_id' => $pid, 'name' => 'EP Renamed', 'stock_qty' => '9', 'price_ghs' => '11.50']);
check('valid save returns 200', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
check('save reports the new name back', ($r['json']['product']['name'] ?? '') === 'EP Renamed');
check('save reports changed fields', in_array('name', $r['json']['changed'] ?? []));
$row = $db->prepare('SELECT * FROM products WHERE id = ?'); $row->execute([$pid]); $row = $row->fetch();
check('db row really updated', $row['name'] === 'EP Renamed' && (int)$row['stock_qty'] === 9);

// 7. Save must not touch moderation state.
$r = post($base, $cookieJar, ['action' => 'save', 'product_id' => $pid, 'status_market' => 'active', 'name' => 'EP Renamed 2']);
$row = $db->prepare('SELECT * FROM products WHERE id = ?'); $row->execute([$pid]); $row = $row->fetch();
check('save cannot approve the product', $row['status_market'] === 'pending_review', 'status=' . $row['status_market']);

// 8. Image ops.
$r = post($base, $cookieJar, ['action' => 'primary_image', 'product_id' => $pid, 'image_id' => $imgId]);
check('set primary succeeds', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
$r = post($base, $cookieJar, ['action' => 'del_image', 'product_id' => $pid, 'image_id' => $imgId]);
check('delete image succeeds', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
// NB: the upload test above added a second image, so deleting the seeded
// primary leaves one — and promoteFallback must have promoted it.
$remaining = $r['json']['images'] ?? [];
check('only the uploaded image remains', count($remaining) === 1, json_encode($remaining));
check('surviving image promoted to primary', (int)($remaining[0]['is_primary'] ?? 0) === 1, json_encode($remaining));
$leftoverUrl = $remaining[0]['url'] ?? '';

// 9. Moderation from the drawer, then verify the pulled-restore semantics.
$db->prepare("UPDATE products SET is_active=0, status_market='pending_review', pulled_at=$now WHERE id=?")->execute([$pid]);
$r = post($base, $cookieJar, ['action' => 'moderate_product', 'product_id' => $pid, 'moderate' => 'active']);
check('approve from drawer succeeds', $r['code'] === 200 && !empty($r['json']['success']), $r['body']);
$row = $db->prepare('SELECT * FROM products WHERE id = ?'); $row->execute([$pid]); $row = $row->fetch();
check('approving a pulled product restores is_active', (int)$row['is_active'] === 1, 'is_active=' . $row['is_active']);
check('pulled_at cleared on approve', $row['pulled_at'] === null);

// 10. Invalid moderation value.
$r = post($base, $cookieJar, ['action' => 'moderate_product', 'product_id' => $pid, 'moderate' => 'DROP TABLE']);
check('bogus moderation value rejected', $r['code'] === 400);

// 11. Invalid action.
$r = post($base, $cookieJar, ['action' => 'nuke_everything', 'product_id' => $pid]);
check('unknown action rejected', $r['code'] === 400);

// ── Cleanup ──────────────────────────────────────────────────────────────
// Remove the file the upload test wrote to the real uploads directory, so the
// harness leaves no debris in public/uploads/products/.
if (!empty($leftoverUrl)) @unlink($root . '/' . $leftoverUrl);
$db->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
$db->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$pid]);
@unlink($bootstrap); @unlink($tokenScript); @unlink($cookieJar);
proc_terminate($proc); proc_close($proc);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
