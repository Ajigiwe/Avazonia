<?php
// admin/intake.php — Telegram Bot Intake & Image Staging Review GUI
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../includes/intake_service.php';
require_once __DIR__ . '/../includes/intake_matcher.php';
require_once __DIR__ . '/../core/ProductCsvImporter.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest()) {
        $error = 'Security token expired. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $batchId = (int) ($_POST['batch_id'] ?? 0);
        $batch = intake_batch($batchId);

        if ($action === 'discard_batch') {
            if ($batch === null) {
                $error = 'That batch does not exist.';
            } else {
                $n = intake_discard($batchId);
                $success = sprintf('Batch #%d discarded (%d staged photo(s) deleted from disk).', $batchId, $n);
            }
        } elseif ($action === 'register_webhook') {
            $cfg = intake_config();
            $url = trim((string)($_POST['webhook_url'] ?? $cfg['webhook_url']));
            if ($url === '') {
                $url = rtrim(APP_URL, '/') . '/tg_webhook.php';
            }
            if ($cfg['token'] === '') {
                $error = 'TG_BOT_TOKEN is not defined in .env';
            } elseif ($cfg['secret'] === '') {
                $error = 'TG_WEBHOOK_SECRET is not defined in .env';
            } else {
                $res = tg_api($cfg, 'setWebhook', [
                    'url'                  => $url,
                    'secret_token'         => $cfg['secret'],
                    'drop_pending_updates' => !empty($_POST['drop_pending']),
                ]);
                if (!empty($res['ok'])) {
                    $success = 'Webhook successfully registered with Telegram! Target URL: ' . htmlspecialchars($url);
                } else {
                    $error = 'Failed to set webhook: ' . ($res['description'] ?? 'Unknown Telegram error');
                }
            }
        } elseif ($action === 'delete_webhook') {
            $cfg = intake_config();
            $res = tg_api($cfg, 'deleteWebhook');
            if (!empty($res['ok'])) {
                $success = 'Webhook deleted from Telegram. Bot switched to polling mode.';
            } else {
                $error = 'Failed to delete webhook: ' . ($res['description'] ?? 'Unknown Telegram error');
            }
        } elseif ($action === 'sync_disk_photos') {
            if ($batch === null) {
                $error = 'Batch not found.';
            } else {
                $n = intake_sync_disk_photos($batchId);
                $success = sprintf('Scanned uploads directory: %d photo(s) newly synced into Batch #%d.', $n, $batchId);
            }
        } elseif ($action === 'remove_photo') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if ($batch === null || $itemId <= 0) {
                $error = 'Invalid photo or batch.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => $error]);
                    exit;
                }
            } else {
                $ok = intake_remove_item($batchId, $itemId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => $ok]);
                    exit;
                }
                $success = 'Photo removed successfully.';
            }
        } elseif ($action === 'set_cover_photo') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $itemId = (int) ($_POST['item_id'] ?? 0);
            if ($batch === null || $itemId <= 0) {
                $error = 'Invalid photo or batch.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => $error]);
                    exit;
                }
            } else {
                $ok = intake_set_cover($batchId, $itemId);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => $ok]);
                    exit;
                }
                $success = 'Cover photo updated successfully.';
            }
        } elseif ($action === 'upload_photos') {
            if ($batch === null) {
                $error = 'Batch not found.';
            } elseif (empty($_FILES['photos'])) {
                $error = 'No photos selected for upload.';
            } else {
                $n = intake_add_photos($batchId, $_FILES['photos']);
                $success = sprintf('Uploaded %d new photo(s) to Batch #%d.', $n, $batchId);
            }
        } elseif ($action === 'split_items') {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $itemIds = (array)($_POST['item_ids'] ?? []);
            $label = trim((string)($_POST['new_label'] ?? ''));
            if ($batch === null || empty($itemIds)) {
                $error = 'No photos selected to split or batch not found.';
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => false, 'error' => $error]);
                    exit;
                }
            } else {
                $newBatchId = intake_split_items($batchId, $itemIds, $label);
                if ($newBatchId > 0) {
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => true, 'new_batch_id' => $newBatchId, 'count' => count($itemIds)]);
                        exit;
                    }
                    $success = sprintf('Split %d photo(s) into new Batch #%d.', count($itemIds), $newBatchId);
                } else {
                    $error = 'Could not split photos into a new batch.';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['ok' => false, 'error' => $error]);
                        exit;
                    }
                }
            }
        } elseif ($action === 'publish_vehicle_live') {
            if ($batch === null) {
                $error = 'Batch not found.';
            } else {
                $csvData = (string) ($batch['sheet_data'] ?? '');
                $parsed = bulk_parse_csv_string($csvData);
                $rows = $parsed['rows'] ?? [];
                if (empty($rows)) {
                    $error = 'No vehicle details found in this batch.';
                } else {
                    $rowIndex = (int)($_POST['row_index'] ?? 0);
                    if (!isset($rows[$rowIndex])) {
                        $rowIndex = 0;
                    }
                    $row = $rows[$rowIndex];

                    // 1. Resolve or create Brand
                    $brandName = trim((string)($row['brand'] ?? ''));
                    $brandId = null;
                    if ($brandName !== '') {
                        $stBrand = $db->prepare("SELECT id FROM brands WHERE LOWER(name) = LOWER(?) LIMIT 1");
                        $stBrand->execute([$brandName]);
                        $foundBrand = $stBrand->fetchColumn();
                        if ($foundBrand) {
                            $brandId = (int)$foundBrand;
                        } else {
                            $bSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $brandName), '-'));
                            $db->prepare("INSERT INTO brands (name, slug, is_active, sort_order) VALUES (?, ?, 1, 99)")
                               ->execute([$brandName, $bSlug]);
                            $brandId = (int)$db->lastInsertId();
                        }
                    }

                    // 2. Resolve Category (Automotive / Vehicles -> SUVs or Cars)
                    $subCat = trim((string)($row['sub_category'] ?? ''));
                    $categoryId = null;
                    if ($subCat !== '') {
                        $stCat = $db->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND parent_id IS NOT NULL LIMIT 1");
                        $stCat->execute([$subCat]);
                        $foundCat = $stCat->fetchColumn();
                        if ($foundCat) {
                            $categoryId = (int)$foundCat;
                        }
                    }
                    if (!$categoryId) {
                        $stFallback = $db->query("SELECT id FROM categories WHERE slug IN ('vehicles-suvs', 'vehicles-cars', 'cars') LIMIT 1");
                        $categoryId = (int)($stFallback->fetchColumn() ?: 40);
                    }

                    // 3. Prepare product destination directories
                    $prodUploadsDir = realpath(__DIR__ . '/../public/uploads/products');
                    if (!$prodUploadsDir) {
                        @mkdir(__DIR__ . '/../public/uploads/products', 0775, true);
                        $prodUploadsDir = realpath(__DIR__ . '/../public/uploads/products');
                    }
                    $root = dirname(__DIR__);

                    // 4. Copy staged photos to product images
                    $stagedPhotos = intake_items($batchId);
                    $targetPhotos = [];
                    if (count($rows) > 1 && $rowIndex !== null) {
                        $match = intake_match($rows, $stagedPhotos, ['order_fallback' => true]);
                        $assignedPaths = $match['assignments'][$rowIndex]['paths'] ?? [];
                        if (!empty($assignedPaths)) {
                            foreach ($stagedPhotos as $item) {
                                if (in_array((string)$item['file_path'], $assignedPaths, true)) {
                                    $targetPhotos[] = $item;
                                }
                            }
                        }
                    }
                    if (empty($targetPhotos) || count($rows) === 1) {
                        $targetPhotos = [];
                        foreach ($stagedPhotos as $item) {
                            if (!in_array((string)$item['media_group'], ['sheet', 'video'], true)) {
                                $targetPhotos[] = $item;
                            }
                        }
                    }

                    $finalImages = [];
                    foreach ($targetPhotos as $item) {
                        if (in_array((string)$item['media_group'], ['sheet', 'video'], true)) {
                            continue;
                        }
                        $srcPath = $root . '/' . ltrim((string)$item['file_path'], '/');
                        if (is_file($srcPath)) {
                            $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
                            $newName = 'p_intake_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            $destPath = $prodUploadsDir . DIRECTORY_SEPARATOR . $newName;
                            if (@copy($srcPath, $destPath)) {
                                $finalImages[] = 'public/uploads/products/' . $newName;
                            }
                        }
                    }

                    // Apply anti-theft watermark
                    require_once __DIR__ . '/../core/Watermark.php';
                    Watermark::applyToPaths($finalImages);

                    // 5. Handle video
                    $videoUrl = trim((string)($row['video_url'] ?? ''));
                    if ($videoUrl === '') {
                        $stagedVideo = db_one("SELECT file_path FROM intake_items WHERE batch_id = ? AND media_group = 'video' ORDER BY id ASC LIMIT 1", [$batchId]);
                        if (!empty($stagedVideo['file_path'])) {
                            $videoUrl = (string)$stagedVideo['file_path'];
                        }
                    }

                    // 6. Build product attributes
                    $name = trim((string)($row['name'] ?? 'Vehicle'));
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                    if ($slug === '') $slug = 'vehicle-' . time();
                    $slug .= '-' . bin2hex(random_bytes(3));

                    $priceUsd = (float)($row['price'] ?? 0);
                    $currency = (string)($row['currency'] ?? 'USD');
                    $fobPriceUsd = $priceUsd;
                    $incoterms = 'FOB';
                    $conditionType = (string)($row['condition'] ?? 'used');
                    $locationCountry = (string)($row['location_country'] ?? 'CN');
                    $vehicleOrigin = 'international_export';
                    $isPreorder = 1;
                    $availableInGhana = 0;
                    $description = (string)($row['description'] ?? '');
                    $tags = (string)($row['tags'] ?? '');
                    $stockQty = 1;

                    // Convert features and specs
                    $featRaw = trim((string)($row['features'] ?? ''));
                    $featuresJson = null;
                    if ($featRaw !== '') {
                        $featArr = array_values(array_filter(array_map('trim', preg_split('/[\r\n|;]+/', $featRaw))));
                        if (!empty($featArr)) $featuresJson = json_encode($featArr, JSON_UNESCAPED_UNICODE);
                    }

                    $specsRaw = trim((string)($row['specs'] ?? ''));
                    $specsJson = null;
                    if ($specsRaw !== '') {
                        $specsArr = [];
                        foreach (preg_split('/[\r\n|;]+/', $specsRaw) as $specLine) {
                            if (strpos($specLine, ':') !== false) {
                                list($k, $v) = explode(':', $specLine, 2);
                                if (trim($k) !== '') $specsArr[trim($k)] = trim($v);
                            }
                        }
                        if (!empty($specsArr)) $specsJson = json_encode($specsArr, JSON_UNESCAPED_UNICODE);
                    }

                    try {
                        $productColumns = 'name, slug, category_id, brand_id, listing_type, visibility, condition_type, moq, wholesale_price_ghs, fob_price_usd, incoterms, location_country, vehicle_origin, status_market, price_ghs, price_usd, currency, stock_qty, description, features, specs, tags, is_preorder, is_bestseller, is_featured, is_dropshipping, available_in_ghana, video_url, brand_name';
                        $productValues = [
                            $name, $slug, $categoryId, $brandId, 'export', 'public', $conditionType, 1, null,
                            $fobPriceUsd, $incoterms, $locationCountry, $vehicleOrigin, 'active', 0, $priceUsd,
                            $currency, $stockQty, $description, $featuresJson, $specsJson, $tags,
                            $isPreorder, 0, 1, 0, $availableInGhana, $videoUrl ?: null, $brandName ?: null
                        ];
                        $placeholders = implode(', ', array_fill(0, count($productValues), '?'));
                        $stmt = $db->prepare("INSERT INTO products ($productColumns) VALUES ($placeholders)");
                        $stmt->execute($productValues);
                        $productId = (int)$db->lastInsertId();

                        // Insert images
                        foreach ($finalImages as $index => $img) {
                            $isPrimary = ($index === 0) ? 1 : 0;
                            $stmtImg = $db->prepare("INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)");
                            $stmtImg->execute([$productId, $img, $isPrimary]);
                        }

                        // Flush cache
                        require_once __DIR__ . '/../core/Cache.php';
                        Cache::flushTags(['products']);

                        // Multi-vehicle vs single vehicle batch claim logic
                        if (count($rows) > 1) {
                            // Remove published photo items from the intake batch so they aren't reused by remaining vehicles
                            foreach ($targetPhotos as $tp) {
                                if (!empty($tp['id'])) {
                                    db_exec("DELETE FROM intake_items WHERE id = ? AND batch_id = ?", [(int)$tp['id'], $batchId]);
                                }
                            }

                            array_splice($rows, $rowIndex, 1);
                            $headers = $parsed['headers'] ?? array_keys($rows[0]);
                            $stream = fopen('php://memory', 'r+');
                            fwrite($stream, "\xEF\xBB\xBF");
                            fputcsv($stream, $headers);
                            foreach ($rows as $r) {
                                $rVals = [];
                                foreach ($headers as $h) {
                                    $rVals[] = $r[$h] ?? '';
                                }
                                fputcsv($stream, $rVals);
                            }
                            rewind($stream);
                            $newCsv = stream_get_contents($stream);
                            fclose($stream);
                            intake_set_sheet($batchId, $batch['sheet_name'], $newCsv, count($rows));

                            header('Location: ' . APP_URL . '/admin/edit-product.php?id=' . $productId . '&published_from_intake=1&remaining_batch=' . $batchId);
                            exit;
                        } else {
                            intake_claim($batchId);
                            header('Location: ' . APP_URL . '/admin/edit-product.php?id=' . $productId . '&published_from_intake=1');
                            exit;
                        }
                    } catch (Throwable $e) {
                        $error = 'Failed to publish vehicle: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'send_to_importer') {
            if ($batch === null) {
                $error = 'Batch not found.';
            } else {
                $csvData = (string) ($batch['sheet_data'] ?? '');
                if (trim($csvData) === '') {
                    $error = 'This batch does not have a spreadsheet. Send a .csv or .xlsx to the bot first.';
                } else {
                    $parsed = bulk_parse_csv_string($csvData);
                    $rows = $parsed['rows'] ?? [];

                    if (empty($rows)) {
                        $error = 'The spreadsheet does not contain valid data rows.';
                    } else {
                        $photos = intake_items($batchId);
                        $useOrder = !empty($_POST['use_order']);
                        $match = intake_match($rows, $photos, ['order_fallback' => $useOrder]);
                        $assignments = $match['assignments'] ?? [];
                        $overrides = (array) ($_POST['assign'] ?? []);

                        // Prepare destination directory
                        $prodUploadsDir = realpath(__DIR__ . '/../public/uploads/products');
                        if (!$prodUploadsDir) {
                            @mkdir(__DIR__ . '/../public/uploads/products', 0775, true);
                            $prodUploadsDir = realpath(__DIR__ . '/../public/uploads/products');
                        }

                        $root = dirname(__DIR__);
                        $manualCount = 0;

                        // Process rows and copy/link images
                        foreach ($rows as $i => &$row) {
                            $paths = $assignments[$i]['paths'] ?? [];

                            // Check for manual overrides from the UI
                            if (array_key_exists($i, $overrides)) {
                                $ov = trim((string) $overrides[$i]);
                                if ($ov === 'none') {
                                    $paths = [];
                                } elseif ($ov !== '') {
                                    $paths = [$ov];
                                    $manualCount++;
                                }
                            }

                            // Copy matched intake images to product gallery directory
                            $finalImages = [];
                            foreach ($paths as $p) {
                                $srcPath = $root . '/' . ltrim($p, '/');
                                if (is_file($srcPath)) {
                                    $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
                                    $newName = 'p_intake_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                                    $destPath = $prodUploadsDir . DIRECTORY_SEPARATOR . $newName;
                                    if (@copy($srcPath, $destPath)) {
                                        $finalImages[] = 'public/uploads/products/' . $newName;
                                    } else {
                                        $finalImages[] = ltrim($p, '/');
                                    }
                                } else {
                                    $finalImages[] = ltrim($p, '/');
                                }
                            }

                            // Populate image columns
                            for ($slot = 1; $slot <= 10; $slot++) {
                                if (isset($finalImages[$slot - 1])) {
                                    $row['image_url_' . $slot] = $finalImages[$slot - 1];
                                }
                            }
                            if (!empty($finalImages[0])) {
                                $row['image_url'] = $finalImages[0];
                            }
                        }
                        unset($row);

                        // Build transformed CSV for ProductCsvImporter
                        $headers = array_keys($rows[0]);
                        $tmpCsv = tempnam(sys_get_temp_dir(), 'tg_import_');
                        $fh = fopen($tmpCsv, 'w');
                        fwrite($fh, "\xEF\xBB\xBF");
                        fputcsv($fh, $headers);
                        foreach ($rows as $r) {
                            fputcsv($fh, array_values($r));
                        }
                        fclose($fh);

                        try {
                            $result = ProductCsvImporter::previewSupplier($tmpCsv, $db, $batch['sheet_name']);
                            @unlink($tmpCsv);

                            $key = bin2hex(random_bytes(24));
                            Session::set('product_csv_import', ProductCsvImporter::previewTag([
                                'key' => $key,
                                'preview' => $result['preview'],
                                'lookups' => $result['lookups'],
                            ]));

                            // Mark batch as claimed
                            intake_claim($batchId);

                            header('Location: ' . APP_URL . '/admin/import-products.php?from_intake=' . $batchId);
                            exit;
                        } catch (Throwable $e) {
                            @unlink($tmpCsv);
                            $error = 'Failed to prepare products for importer: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// GET Logic
$reviewId = (int) ($_GET['review'] ?? 0);
$batch = $reviewId > 0 ? intake_batch($reviewId) : null;
$cfg = intake_config();
$isConfigured = intake_is_configured();

$batches = intake_batches(null, 50);
$pendingCount = intake_pending_count();

$title = $batch ? "Review Batch #{$batch['id']}" : "Telegram Intake";
include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="admin-header">
    <div>
        <h1>
            Telegram Intake
            <?php if ($batch): ?>
                <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">/ Batch #<?= (int)$batch['id'] ?></span>
            <?php else: ?>
                <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">(<?= $pendingCount ?> pending)</span>
            <?php endif; ?>
        </h1>
        <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">Stage catalogs and product photography sent via Telegram, match images, and publish to the live store.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <?php if ($batch): ?>
            <a href="intake.php" class="btn-secondary" style="height:40px;padding:0 18px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;text-decoration:none;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;">&larr; All Batches</a>
        <?php else: ?>
            <a href="import-products.php" class="btn-secondary" style="height:40px;padding:0 18px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;text-decoration:none;border-radius:4px;border:1px solid #d1d5db;background:#fff;color:#374151;">Manual Import &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isConfigured): ?>
<div style="margin-bottom:24px;background:#fffbeb;border:1px solid #fef3c7;border-left:4px solid #f59e0b;padding:16px 20px;border-radius:4px;">
    <div style="display:flex;align-items:flex-start;gap:12px;">
        <span style="font-size:20px;">⚠️</span>
        <div>
            <div style="font-weight:700;font-size:14px;color:#92400e;">Telegram Bot Not Fully Configured</div>
            <div style="font-size:13px;color:#b45309;margin-top:4px;line-height:1.5;">
                To receive uploads from Telegram, configure these variables in your <code style="background:#fef3c7;padding:2px 6px;border-radius:3px;">.env</code> file:
                <br>
                <strong>TG_BOT_TOKEN</strong> (from @BotFather) &bull;
                <strong>TG_WEBHOOK_SECRET</strong> (secure random token) &bull;
                <strong>TG_ALLOWED_CHAT_IDS</strong> (your numeric chat ID from @userinfobot)
                <br>
                Then run: <code style="background:#fef3c7;padding:2px 6px;border-radius:3px;">php tools/tg_set_webhook.php</code>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="margin-bottom:24px;background:#fff1f0;color:#f5222d;padding:16px;font-size:13px;border-left:4px solid #f5222d;border-radius:4px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div style="margin-bottom:24px;background:#e6f7ec;color:#00a854;padding:16px;font-size:13px;border-left:4px solid #00a854;border-radius:4px;"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($batch): ?>
    <?php
    $csvData = (string)($batch['sheet_data'] ?? '');
    $parsed = $csvData !== '' ? bulk_parse_csv_string($csvData) : ['headers' => [], 'rows' => []];
    $sheetRows = $parsed['rows'] ?? [];
    $photos = intake_items((int)$batch['id']);
    $photoCount = intake_photo_count((int)$batch['id']);
    if ($photoCount === 0) {
        $autoAdded = intake_sync_disk_photos((int)$batch['id']);
        if ($autoAdded > 0) {
            $photos = intake_items((int)$batch['id']);
            $photoCount = intake_photo_count((int)$batch['id']);
        }
    }
    $videos = db_all("SELECT * FROM intake_items WHERE batch_id = ? AND media_group = 'video'", [(int)$batch['id']]);
    $videoCount = count($videos);
    $vehicleRows = [];
    foreach ($sheetRows as $idx => $r) {
        if (!empty($r['specs']) || !empty($r['features']) || str_starts_with((string)($batch['sheet_name'] ?? ''), 'Vehicle') || str_starts_with((string)($batch['sheet_name'] ?? ''), 'Vehicles') || !empty($r['brand'])) {
            $vehicleRows[$idx] = $r;
        }
    }
    $isVehicleBatch = !empty($vehicleRows);

    $match = intake_match($sheetRows, $photos, ['order_fallback' => true]);
    $assignments = $match['assignments'] ?? [];
    $stats = $match['stats'] ?? [];

    // Check existing products in DB to detect catalog duplicates
    $skus = array_values(array_filter(array_column($sheetRows, 'sku')));
    $existingSkus = [];
    if (!empty($skus)) {
        $inClause = implode(',', array_fill(0, count($skus), '?'));
        $stmt = $db->prepare("SELECT id, sku, name FROM products WHERE sku IN ($inClause)");
        $stmt->execute($skus);
        foreach ($stmt->fetchAll() as $row) {
            $existingSkus[strtolower(trim($row['sku']))] = $row;
        }
    }
    ?>

    <!-- Stat overview cards -->
    <div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;padding:16px 20px;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.03);">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Spreadsheet Rows</div>
            <div style="font-weight:800;font-size:24px;color:#111827;margin-top:4px;"><?= count($sheetRows) ?></div>
        </div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;padding:16px 20px;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.03);">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Staged Photos</div>
            <div style="font-weight:800;font-size:24px;color:#2563eb;margin-top:4px;"><?= $photoCount ?></div>
        </div>
        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e5e7eb;padding:16px 20px;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.03);">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Staged Videos</div>
            <div style="font-weight:800;font-size:24px;color:#7c3aed;margin-top:4px;"><?= $videoCount ?></div>
        </div>
        <div style="flex:1;min-width:140px;background:#f0fdf4;border:1px solid #bbf7d0;padding:16px 20px;border-radius:6px;">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#166534;letter-spacing:0.08em;">SKU Matched</div>
            <div style="font-weight:800;font-size:24px;color:#16a34a;margin-top:4px;"><?= $stats['sku'] ?? 0 ?></div>
        </div>
        <div style="flex:1;min-width:140px;background:#eff6ff;border:1px solid #bfdbfe;padding:16px 20px;border-radius:6px;">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#1e40af;letter-spacing:0.08em;">Title / Caption Matched</div>
            <div style="font-weight:800;font-size:24px;color:#3b82f6;margin-top:4px;"><?= $stats['caption'] ?? 0 ?></div>
        </div>
        <div style="flex:1;min-width:140px;background:#fafafa;border:1px solid #e5e7eb;padding:16px 20px;border-radius:6px;">
            <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#6b7280;letter-spacing:0.08em;">Unmatched Rows</div>
            <div style="font-weight:800;font-size:24px;color:#6b7280;margin-top:4px;"><?= $stats['unmatched'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Staged Media & Photo Gallery Panel -->
    <div class="panel" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="padding:16px 20px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-weight:700;font-size:14px;color:#111827;">📸 Staged Gallery Photos (<span id="gallery-count"><?= $photoCount ?></span>)</span>
                <?php if ($videoCount > 0): ?>
                    <span style="background:#fae8ff;color:#86198f;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">🎥 <?= $videoCount ?> Video(s)</span>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <!-- Split selected photos button -->
                <button type="button" id="split-selected-btn" onclick="splitSelectedPhotos(<?= (int)$batch['id'] ?>)"
                        disabled
                        style="background:#6366f1;color:#fff;border:none;padding:5px 12px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;opacity:0.5;transition:all 0.15s;display:inline-flex;align-items:center;gap:4px;">
                    ✂️ Split to New Batch
                </button>
                <button type="button" onclick="toggleSelectAllPhotos()"
                        style="background:#fff;border:1px solid #d1d5db;padding:4px 10px;font-size:11px;font-weight:600;color:#374151;border-radius:4px;cursor:pointer;">
                    Select All
                </button>
                <button type="button" onclick="document.getElementById('intake-photo-upload-input').click()"
                        style="background:#10b981;color:#fff;border:none;padding:5px 12px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;box-shadow:0 1px 2px rgba(16,185,129,0.2);">
                    <span>➕ Add Photos</span>
                </button>
                <form method="post" style="margin:0;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="sync_disk_photos">
                    <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
                    <button type="submit" style="background:#fff;border:1px solid #d1d5db;padding:4px 10px;font-size:11px;font-weight:700;color:#374151;border-radius:4px;cursor:pointer;">🔄 Sync Disk</button>
                </form>
            </div>
        </div>

        <div style="padding:16px;">
            <!-- Hidden photo upload form -->
            <form id="intake-photo-upload-form" method="post" enctype="multipart/form-data" style="display:none;">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="upload_photos">
                <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
                <input type="file" id="intake-photo-upload-input" name="photos[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,image/avif" onchange="this.form.submit()">
            </form>

            <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));gap:20px;">
                <!-- Video Walkthrough Player -->
                <?php if (!empty($videos)): ?>
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px;">
                        <div style="font-weight:700;font-size:12px;color:#111827;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                            🎥 Video Walkthrough (<?= count($videos) ?>)
                        </div>
                        <?php foreach ($videos as $vItem): ?>
                            <video src="<?= htmlspecialchars(intake_media_url($vItem['file_path'])) ?>"
                                   controls preload="metadata"
                                   style="width:100%;max-height:240px;border-radius:6px;background:#000;margin-bottom:8px;"></video>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Staged Photo Gallery Grid -->
                <div style="flex:1;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(95px, 1fr));gap:8px;max-height:360px;overflow-y:auto;padding:4px;">
                        <?php $pIdx = 0; ?>
                        <?php foreach ($photos as $ph): ?>
                            <?php if (in_array((string)$ph['media_group'], ['sheet', 'video'], true)) continue; ?>
                            <div class="staged-photo-card" id="photo-card-<?= (int)$ph['id'] ?>"
                                 style="position:relative;height:90px;border-radius:6px;overflow:hidden;border:1px solid #e5e7eb;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,0.04);transition:all 0.15s;"
                                 onmouseover="this.querySelector('.photo-overlay').style.opacity='1';this.style.borderColor='#9ca3af';"
                                 onmouseout="this.querySelector('.photo-overlay').style.opacity='0';this.style.borderColor='#e5e7eb';">
                                
                                <!-- Multi-select checkbox -->
                                <input type="checkbox" class="photo-select-cb" value="<?= (int)$ph['id'] ?>"
                                       onchange="updateSplitButton()"
                                       style="position:absolute;top:4px;right:4px;width:16px;height:16px;cursor:pointer;z-index:4;accent-color:#2563eb;"
                                       onclick="event.stopPropagation()">

                                <img src="<?= htmlspecialchars(intake_media_url($ph['file_path'])) ?>"
                                     style="width:100%;height:100%;object-fit:cover;" alt="" loading="lazy">
                                
                                <?php if ($pIdx === 0): ?>
                                    <span style="position:absolute;top:4px;left:4px;background:#10b981;color:#fff;font-size:8px;font-weight:800;padding:2px 5px;border-radius:3px;z-index:2;letter-spacing:0.04em;box-shadow:0 1px 2px rgba(0,0,0,0.3);">COVER</span>
                                <?php endif; ?>

                                <!-- Hover actions overlay -->
                                <div class="photo-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;justify-content:space-between;padding:4px;opacity:0;transition:opacity 0.15s;z-index:3;">
                                    <div style="display:flex;justify-content:flex-start;">
                                        <button type="button"
                                                onclick="removeGalleryPhoto(<?= (int)$batch['id'] ?>, <?= (int)$ph['id'] ?>)"
                                                title="Remove Photo"
                                                style="background:#ef4444;color:#fff;border:none;width:20px;height:20px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;line-height:1;box-shadow:0 1px 3px rgba(0,0,0,0.3);">
                                            ✕
                                        </button>
                                    </div>
                                    <?php if ($pIdx !== 0): ?>
                                        <div>
                                            <button type="button"
                                                    onclick="setCoverPhoto(<?= (int)$batch['id'] ?>, <?= (int)$ph['id'] ?>)"
                                                    title="Set as Primary Cover"
                                                    style="width:100%;background:rgba(255,255,255,0.92);color:#111827;border:none;padding:3px 2px;font-size:9px;font-weight:700;border-radius:3px;cursor:pointer;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,0.2);">
                                                ★ Make Cover
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $pIdx++; ?>
                        <?php endforeach; ?>

                        <!-- Add Photo Tile at the end -->
                        <div onclick="document.getElementById('intake-photo-upload-input').click()"
                             title="Add Photos from Computer"
                             style="height:90px;border-radius:6px;border:2px dashed #d1d5db;background:#f3f4f6;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;color:#6b7280;font-size:11px;font-weight:600;gap:2px;transition:all 0.15s;"
                             onmouseover="this.style.borderColor='#10b981';this.style.color='#059669';this.style.background='#ecfdf5';"
                             onmouseout="this.style.borderColor='#d1d5db';this.style.color='#6b7280';this.style.background='#f3f4f6';">
                            <span style="font-size:18px;">➕</span>
                            <span>Add Photo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isVehicleBatch): ?>
        <?php if (count($vehicleRows) > 1): ?>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-left:4px solid #3b82f6;padding:14px 18px;border-radius:6px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="font-size:13px;color:#1e40af;font-weight:600;">
                    🚗 Multi-Vehicle Batch: This batch contains <strong><?= count($vehicleRows) ?> vehicles</strong>. Each vehicle can be reviewed and published live independently below.
                </div>
                <button type="button" onclick="const f = document.getElementById('intakeReviewForm'); f.style.display = f.style.display === 'none' ? 'block' : 'none';"
                        style="background:#fff;border:1px solid #bfdbfe;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:700;color:#1e40af;cursor:pointer;">
                    Toggle Catalog Table
                </button>
            </div>
        <?php endif; ?>

        <?php foreach ($vehicleRows as $vIdx => $vRow): ?>
            <?php
            $vAssignedPaths = $assignments[$vIdx]['paths'] ?? [];
            if (count($vehicleRows) === 1 || empty($vAssignedPaths)) {
                $vAssignedPaths = [];
                foreach ($photos as $ph) {
                    if (!in_array((string)$ph['media_group'], ['sheet', 'video'], true) && !empty($ph['file_path'])) {
                        $vAssignedPaths[] = (string)$ph['file_path'];
                    }
                }
            }
            ?>
            <!-- Vehicle Drop Staging Card -->
            <div class="panel" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="padding:18px 24px;background:linear-gradient(to right, #111827, #1f2937);color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <span style="font-family:var(--f-mono);font-size:10px;text-transform:uppercase;letter-spacing:0.08em;background:rgba(255,255,255,0.15);padding:3px 8px;border-radius:4px;">
                                🚗 Vehicle <?= count($vehicleRows) > 1 ? '#' . ($vIdx + 1) : 'Drop Staging' ?>
                            </span>
                            <span style="font-family:var(--f-mono);font-size:10px;text-transform:uppercase;letter-spacing:0.08em;background:#059669;color:#fff;padding:3px 8px;border-radius:4px;">
                                China Export (Pre-Order)
                            </span>
                        </div>
                        <h2 style="margin:0;font-size:18px;font-weight:700;line-height:1.3;color:#fff;">
                            <?= htmlspecialchars($vRow['name'] ?? 'Vehicle Listing') ?>
                        </h2>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:0.06em;">FOB Price</div>
                        <div style="font-size:24px;font-weight:800;color:#10b981;font-family:var(--f-mono);">
                            $<?= number_format((float)($vRow['price'] ?? 0)) ?> <span style="font-size:13px;font-weight:600;color:#d1fae5;"><?= htmlspecialchars($vRow['currency'] ?? 'USD') ?></span>
                        </div>
                    </div>
                </div>

                <div style="padding:24px;">
                    <!-- Badges Row -->
                    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
                        <?php if (!empty($vRow['brand'])): ?>
                            <span style="background:#f3f4f6;color:#1f2937;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;">
                                🏷️ Brand: <?= htmlspecialchars($vRow['brand']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($vRow['sub_category'])): ?>
                            <span style="background:#eff6ff;color:#1e40af;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;">
                                📂 Category: <?= htmlspecialchars($vRow['sub_category']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($vRow['condition'])): ?>
                            <span style="background:#fef3c7;color:#92400e;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;text-transform:capitalize;">
                                ⚙️ Condition: <?= htmlspecialchars($vRow['condition']) ?>
                            </span>
                        <?php endif; ?>
                        <span style="background:#ede9fe;color:#5b21b6;padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;">
                            📸 <?= count($vAssignedPaths) ?> Matched Photos
                        </span>
                    </div>

                    <!-- Matched Photos Thumbnails (if multi-vehicle or available) -->
                    <?php if (!empty($vAssignedPaths)): ?>
                        <div style="margin-bottom:20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:12px 16px;">
                            <div style="font-weight:700;font-size:12px;color:#374151;margin-bottom:8px;">
                                📸 Photos Attached to this Vehicle (<?= count($vAssignedPaths) ?>)
                            </div>
                            <div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;">
                                <?php foreach ($vAssignedPaths as $vap): ?>
                                    <div style="width:68px;height:68px;flex-shrink:0;border-radius:4px;overflow:hidden;border:1px solid #e5e7eb;background:#fff;">
                                        <img src="<?= htmlspecialchars(intake_media_url($vap)) ?>" style="width:100%;height:100%;object-fit:cover;" alt="" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Specifications & Features Grid -->
                    <div style="display:grid;grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));gap:24px;margin-bottom:24px;">
                        <!-- Vehicle Specifications -->
                        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;background:#fff;">
                            <div style="font-weight:700;font-size:13px;color:#111827;margin-bottom:12px;border-bottom:1px solid #f3f4f6;padding-bottom:6px;">
                                ⚙️ Vehicle Specifications
                            </div>
                            <?php
                            $specItems = [];
                            if (!empty($vRow['specs'])) {
                                foreach (explode('|', (string)$vRow['specs']) as $sp) {
                                    if (strpos($sp, ':') !== false) {
                                        list($sk, $sv) = explode(':', $sp, 2);
                                        $specItems[trim($sk)] = trim($sv);
                                    }
                                }
                            }
                            ?>
                            <?php if (empty($specItems)): ?>
                                <div style="color:#9ca3af;font-size:12px;">No specifications extracted.</div>
                            <?php else: ?>
                                <table style="width:100%;font-size:12px;border-collapse:collapse;">
                                    <?php foreach ($specItems as $k => $v): ?>
                                        <tr style="border-bottom:1px solid #f9fafb;">
                                            <td style="padding:6px 0;color:#6b7280;font-weight:500;width:45%;"><?= htmlspecialchars($k) ?></td>
                                            <td style="padding:6px 0;color:#111827;font-weight:600;"><?= htmlspecialchars($v) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>
                        </div>

                        <!-- Key Features -->
                        <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;background:#fff;">
                            <div style="font-weight:700;font-size:13px;color:#111827;margin-bottom:12px;border-bottom:1px solid #f3f4f6;padding-bottom:6px;">
                                ✨ Key Features
                            </div>
                            <?php
                            $featItems = !empty($vRow['features']) ? array_filter(array_map('trim', explode('|', (string)$vRow['features']))) : [];
                            ?>
                            <?php if (empty($featItems)): ?>
                                <div style="color:#9ca3af;font-size:12px;">No key features extracted.</div>
                            <?php else: ?>
                                <ul style="margin:0;padding-left:18px;font-size:12px;color:#374151;line-height:1.7;">
                                    <?php foreach ($featItems as $fi): ?>
                                        <li><?= htmlspecialchars($fi) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 1-Click Publishing Action Bar -->
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
                        <div>
                            <div style="font-weight:700;font-size:14px;color:#111827;">Ready to publish this vehicle?</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                                This will create the vehicle product live, attach its <?= count($vAssignedPaths) ?> photos, apply watermarks, and link video walkthroughs.
                                <?php if (count($vehicleRows) > 1): ?>
                                    <br><strong style="color:#2563eb;">Remaining <?= count($vehicleRows) - 1 ?> vehicle(s) in this batch will be preserved.</strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;align-items:center;">
                            <!-- 1-Click Live Publish Form -->
                            <form method="POST" style="margin:0;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="publish_vehicle_live">
                                <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
                                <input type="hidden" name="row_index" value="<?= $vIdx ?>">
                                <button type="submit" class="btn-primary"
                                        style="height:44px;padding:0 24px;font-size:13px;font-weight:700;border-radius:6px;background:#059669;color:#fff;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
                                    🚀 Publish <?= htmlspecialchars($vRow['name'] ?? 'Vehicle') ?> Live
                                </button>
                            </form>

                            <!-- Traditional Send to Importer -->
                            <form method="POST" style="margin:0;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="send_to_importer">
                                <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
                                <button type="submit" class="btn-secondary"
                                        style="height:44px;padding:0 18px;font-size:12px;font-weight:600;border-radius:6px;background:#fff;border:1px solid #d1d5db;color:#374151;cursor:pointer;">
                                    Send to Importer / Waiting Room &rarr;
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Standard Review Form (for multi-product spreadsheets or advanced image remapping) -->
    <form method="POST" id="intakeReviewForm" style="<?= ($isVehicleBatch ? 'display:none;' : '') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="send_to_importer">
        <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">

        <div class="panel" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;margin-bottom:24px;overflow:hidden;">
            <div class="panel-header" style="padding:16px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#f9fafb;">
                <div>
                    <div class="panel-title" style="font-weight:700;font-size:15px;color:#111827;">Product Matching Review</div>
                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">Review photo assignments below. Click any thumbnail to pick a different image or detach.</div>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <label style="font-size:12px;color:#4b5563;display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" name="use_order" value="1" checked style="accent-color:var(--red,#E60000);">
                        Use sequential send-order fallback for unmatched photos
                    </label>
                    <button type="submit" class="btn-red" style="height:38px;padding:0 20px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;">
                        Send to Product Importer &rarr;
                    </button>
                </div>
            </div>

            <div class="table-container" style="overflow-x:auto;">
                <table class="admin-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb;text-align:left;border-bottom:1px solid #e5e7eb;">
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;width:50px;">#</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;width:120px;">Photos</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Product Name</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">SKU</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Category</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Price</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Match Confidence</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sheetRows)): ?>
                            <tr>
                                <td colspan="8" style="padding:40px;text-align:center;color:#6b7280;">
                                    No rows found in this batch's spreadsheet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sheetRows as $i => $row): ?>
                                <?php
                                $sku = trim($row['sku'] ?? '');
                                $isExisting = ($sku !== '' && isset($existingSkus[strtolower($sku)]));
                                $matchInfo = $assignments[$i] ?? null;
                                $method = $matchInfo['method'] ?? 'none';
                                $matchedPaths = $matchInfo['paths'] ?? [];
                                $heroPath = $matchedPaths[0] ?? null;
                                ?>
                                <tr style="border-bottom:1px solid #f3f4f6;<?= $isExisting ? 'background:#fffbeb;' : '' ?>">
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:11px;color:#9ca3af;"><?= $i + 1 ?></td>
                                    
                                    <!-- Photo Thumbnail preview -->
                                    <td style="padding:12px 16px;">
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <div id="thumb_box_<?= $i ?>"
                                                 onclick="openPicker(<?= $i ?>)"
                                                 style="width:52px;height:52px;border-radius:4px;border:1px solid #e5e7eb;background:#f9fafb;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer;position:relative;">
                                                <?php if ($heroPath): ?>
                                                    <img src="<?= htmlspecialchars(intake_media_url($heroPath)) ?>"
                                                         alt="" style="width:100%;height:100%;object-fit:cover;">
                                                    <?php if (count($matchedPaths) > 1): ?>
                                                        <span style="position:absolute;bottom:2px;right:2px;background:rgba(0,0,0,0.7);color:#fff;font-size:9px;padding:1px 4px;border-radius:3px;font-family:var(--f-mono);">+<?= count($matchedPaths) - 1 ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:#9ca3af;font-size:18px;">📷</span>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Hidden input holding manual override -->
                                            <input type="hidden" name="assign[<?= $i ?>]" id="input_assign_<?= $i ?>" value="<?= htmlspecialchars($heroPath ?? '') ?>">
                                        </div>
                                    </td>

                                    <!-- Product Name -->
                                    <td style="padding:12px 16px;">
                                        <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($row['name'] ?? $row['title'] ?? 'Unnamed Product') ?></div>
                                        <?php if ($isExisting): ?>
                                            <span style="display:inline-block;margin-top:3px;font-size:10px;font-weight:700;color:#b45309;background:#fef3c7;padding:2px 6px;border-radius:3px;">
                                                ⚠ Existing Product in Store
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- SKU -->
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:12px;color:#4b5563;">
                                        <?= htmlspecialchars($sku ?: '—') ?>
                                    </td>

                                    <!-- Category -->
                                    <td style="padding:12px 16px;font-size:12px;color:#4b5563;">
                                        <?= htmlspecialchars($row['category'] ?? $row['sub_category'] ?? '—') ?>
                                    </td>

                                    <!-- Price -->
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:12px;font-weight:600;color:#111827;">
                                        ₵<?= htmlspecialchars($row['price'] ?? '0.00') ?>
                                    </td>

                                    <!-- Match Confidence Badge -->
                                    <td style="padding:12px 16px;" id="badge_col_<?= $i ?>">
                                        <?php if ($method === 'sku'): ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;">
                                                ✓ SKU MATCH
                                            </span>
                                        <?php elseif ($method === 'caption'): ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#dbeafe;color:#1e40af;font-weight:700;">
                                                ★ CAPTION MATCH
                                            </span>
                                        <?php elseif ($method === 'order'): ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#fef9c3;color:#854d0e;font-weight:700;">
                                                ⏱ BY SEND ORDER
                                            </span>
                                        <?php else: ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#6b7280;">
                                                NO PHOTO
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td style="padding:12px 16px;text-align:right;">
                                        <button type="button" onclick="openPicker(<?= $i ?>)"
                                                style="background:none;border:1px solid #d1d5db;padding:4px 10px;font-size:11px;border-radius:3px;cursor:pointer;color:#374151;">
                                            Change...
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Action Bar -->
            <div style="padding:16px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#f9fafb;">
                <div style="font-size:12px;color:#6b7280;">
                    Total: <?= count($sheetRows) ?> rows in catalog &bull; <?= $photoCount ?> photos staged in Batch #<?= (int)$batch['id'] ?>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn-red" style="height:42px;padding:0 24px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;">
                        Send to Product Importer &rarr;
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Discard Batch form -->
    <div style="text-align:right;margin-bottom:40px;">
        <form method="POST" onsubmit="return confirm('Are you sure you want to discard batch #<?= (int)$batch['id'] ?>? All uploaded photos for this batch will be permanently deleted from disk.');" style="display:inline;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="discard_batch">
            <input type="hidden" name="batch_id" value="<?= (int)$batch['id'] ?>">
            <button type="submit" style="background:none;border:none;color:#dc2626;font-size:12px;cursor:pointer;text-decoration:underline;">
                Discard batch and purge staged files
            </button>
        </form>
    </div>

    <!-- Image Picker Modal -->
    <div id="imagePickerModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:8px;max-width:800px;width:100%;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
            <div style="padding:16px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Select Product Image</h3>
                <button type="button" onclick="closePicker()" style="background:none;border:none;font-size:22px;color:#6b7280;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:20px;overflow-y:auto;flex:1;">
                <div style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="font-size:12px;color:#6b7280;">Click a photo to assign it to this product row:</span>
                    <button type="button" onclick="assignPhoto('none')" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;padding:4px 10px;font-size:11px;font-weight:700;border-radius:3px;cursor:pointer;">Detach Photo (None)</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:12px;">
                    <?php foreach ($photos as $ph): ?>
                        <?php if ((string)$ph['media_group'] === 'sheet') continue; ?>
                        <div onclick="assignPhoto('<?= htmlspecialchars(ltrim($ph['file_path'], '/')) ?>')"
                             style="border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;cursor:pointer;transition:transform 0.15s, border-color 0.15s;background:#f9fafb;"
                             onmouseover="this.style.borderColor='var(--red,#E60000)';this.style.transform='scale(1.02)';"
                             onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='scale(1)';">
                            <div style="width:100%;height:110px;overflow:hidden;">
                                <img src="<?= htmlspecialchars(intake_media_url($ph['file_path'])) ?>"
                                     alt="" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                            <?php if (!empty($ph['caption'])): ?>
                                <div style="padding:6px;font-size:10px;color:#4b5563;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($ph['caption']) ?>">
                                    <?= htmlspecialchars($ph['caption']) ?>
                                </div>
                            <?php else: ?>
                                <div style="padding:6px;font-size:9px;color:#9ca3af;font-family:var(--f-mono);">
                                    #<?= $ph['id'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="padding:12px 24px;border-top:1px solid #e5e7eb;text-align:right;background:#f9fafb;">
                <button type="button" onclick="closePicker()" class="btn-secondary" style="height:36px;padding:0 18px;font-size:12px;border-radius:4px;cursor:pointer;">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    let currentActiveRow = null;
    const INTAKE_BASE_URL = '<?= htmlspecialchars(intake_media_url('')) ?>';

    function openPicker(rowIndex) {
        currentActiveRow = rowIndex;
        document.getElementById('imagePickerModal').style.display = 'flex';
    }

    function closePicker() {
        document.getElementById('imagePickerModal').style.display = 'none';
        currentActiveRow = null;
    }

    function assignPhoto(path) {
        if (currentActiveRow === null) return;
        const rowIndex = currentActiveRow;
        const input = document.getElementById('input_assign_' + rowIndex);
        const thumbBox = document.getElementById('thumb_box_' + rowIndex);
        const badgeCol = document.getElementById('badge_col_' + rowIndex);

        input.value = path;

        if (path === 'none' || path === '') {
            thumbBox.innerHTML = '<span style="color:#9ca3af;font-size:18px;">📷</span>';
            badgeCol.innerHTML = '<span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#6b7280;">NO PHOTO</span>';
        } else {
            const clean = path.replace(/^\/+/, '');
            const fullUrl = (clean.startsWith('http://') || clean.startsWith('https://')) ? clean : (INTAKE_BASE_URL + '/' + clean);
            thumbBox.innerHTML = '<img src="' + fullUrl + '" style="width:100%;height:100%;object-fit:cover;">';
            badgeCol.innerHTML = '<span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#ede9fe;color:#6d28d9;font-weight:700;">★ MANUAL PICK</span>';
        }

        closePicker();
    }

    function removeGalleryPhoto(batchId, itemId) {
        if (!confirm('Remove this photo from the staged batch?')) return;
        const card = document.getElementById('photo-card-' + itemId);
        if (card) {
            card.style.opacity = '0.3';
            card.style.pointerEvents = 'none';
        }
        const form = new FormData();
        form.append('action', 'remove_photo');
        form.append('batch_id', batchId);
        form.append('item_id', itemId);
        form.append('_csrf_token', '<?= Csrf::token() ?>');

        fetch('intake.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        }).then(r => r.json()).then(res => {
            if (res.ok) {
                if (card) {
                    card.remove();
                    const cnt = document.getElementById('gallery-count');
                    if (cnt) {
                        const cur = parseInt(cnt.innerText, 10);
                        if (!isNaN(cur) && cur > 0) cnt.innerText = (cur - 1);
                    }
                }
            } else {
                alert(res.error || 'Failed to remove photo.');
                if (card) {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                }
            }
        }).catch(err => {
            window.location.reload();
        });
    }

    function setCoverPhoto(batchId, itemId) {
        const form = new FormData();
        form.append('action', 'set_cover_photo');
        form.append('batch_id', batchId);
        form.append('item_id', itemId);
        form.append('_csrf_token', '<?= Csrf::token() ?>');

        fetch('intake.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        }).then(r => r.json()).then(res => {
            if (res.ok) {
                window.location.reload();
            } else {
                alert(res.error || 'Failed to set cover photo.');
            }
        }).catch(err => {
            window.location.reload();
        });
    }

    function toggleSelectAllPhotos() {
        const cbs = document.querySelectorAll('.photo-select-cb');
        const anyUnchecked = Array.from(cbs).some(cb => !cb.checked);
        cbs.forEach(cb => cb.checked = anyUnchecked);
        updateSplitButton();
    }

    function updateSplitButton() {
        const count = document.querySelectorAll('.photo-select-cb:checked').length;
        const btn = document.getElementById('split-selected-btn');
        if (btn) {
            btn.disabled = count === 0;
            btn.style.opacity = count === 0 ? '0.5' : '1';
            btn.innerText = count > 0 ? '✂️ Split ' + count + ' to New Batch' : '✂️ Split to New Batch';
        }
    }

    function splitSelectedPhotos(batchId) {
        const checked = Array.from(document.querySelectorAll('.photo-select-cb:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('Please select at least one photo using the checkboxes.');
            return;
        }
        const label = prompt('Enter a label/title for the new batch:', 'Split Vehicle Photos (' + checked.length + ' photos)');
        if (label === null) return;

        const btn = document.getElementById('split-selected-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Splitting...';
        }

        const form = new FormData();
        form.append('action', 'split_items');
        form.append('batch_id', batchId);
        form.append('new_label', label);
        form.append('_csrf_token', '<?= Csrf::token() ?>');
        checked.forEach(id => form.append('item_ids[]', id));

        fetch('intake.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        }).then(r => r.json()).then(res => {
            if (res.ok) {
                alert('Successfully moved ' + res.count + ' photo(s) to new Batch #' + res.new_batch_id + '!');
                window.location.href = 'intake.php?review=' + res.new_batch_id;
            } else {
                alert(res.error || 'Failed to split photos.');
                if (btn) btn.disabled = false;
            }
        }).catch(err => {
            window.location.reload();
        });
    }
    </script>

<?php else: ?>
    <!-- Batches List View -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;">
        <div class="panel" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;">
            <div class="panel-header" style="padding:16px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                <div class="panel-title" style="font-weight:700;font-size:15px;color:#111827;">Staged Intake Batches</div>
                <span style="font-family:var(--f-mono);font-size:11px;color:#6b7280;"><?= count($batches) ?> total</span>
            </div>

            <div class="table-container" style="overflow-x:auto;">
                <table class="admin-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb;text-align:left;border-bottom:1px solid #e5e7eb;">
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Batch</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Status</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Product / Listing</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Media</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Chat ID</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;">Received</th>
                            <th style="padding:12px 16px;font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:#6b7280;text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="7" style="padding:48px 24px;text-align:center;color:#6b7280;">
                                    <div style="font-size:28px;margin-bottom:8px;">📥</div>
                                    <div style="font-weight:600;font-size:14px;color:#111827;">No Telegram batches received yet</div>
                                    <div style="font-size:12px;color:#9ca3af;margin-top:4px;">Send a product spreadsheet (.csv/.xlsx) and photos to your bot on Telegram.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:12px;font-weight:700;color:#111827;">
                                        #<?= (int)$b['id'] ?>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#fef3c7;color:#92400e;font-weight:700;">PENDING</span>
                                        <?php elseif ($b['status'] === 'claimed'): ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#dcfce7;color:#166534;font-weight:700;">CLAIMED</span>
                                        <?php else: ?>
                                            <span style="font-family:var(--f-mono);font-size:9px;letter-spacing:0.04em;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#6b7280;">DISCARDED</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;">
                                        <?php if ($b['sheet_name'] !== ''): ?>
                                            <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($b['sheet_name']) ?></div>
                                            <div style="font-size:11px;color:#6b7280;"><?= (int)$b['row_count'] ?> vehicle/product(s)</div>
                                        <?php elseif (!empty($b['label'])): ?>
                                            <div style="font-weight:600;color:#111827;"><?= htmlspecialchars($b['label']) ?></div>
                                            <div style="font-size:11px;color:#6b7280;">Staged drop</div>
                                        <?php else: ?>
                                            <span style="color:#9ca3af;font-style:italic;">No listing details yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:12px;color:#2563eb;font-weight:600;">
                                        📸 <?= (int)$b['photo_count'] ?>
                                        <?php if ((int)$b['video_count'] > 0): ?>
                                            <span style="color:#7c3aed;margin-left:6px;">🎥 <?= (int)$b['video_count'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 16px;font-family:var(--f-mono);font-size:11px;color:#6b7280;">
                                        <?= (int)$b['chat_id'] ?>
                                    </td>
                                    <td style="padding:12px 16px;font-size:12px;color:#6b7280;">
                                        <?= date('M j, Y H:i', strtotime($b['created_at'])) ?>
                                    </td>
                                    <td style="padding:12px 16px;text-align:right;">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <a href="intake.php?review=<?= (int)$b['id'] ?>" class="btn-primary" style="padding:6px 14px;font-size:11px;font-weight:700;border-radius:4px;text-decoration:none;display:inline-block;background:#059669;color:#fff;">
                                                Review &amp; Publish &rarr;
                                            </a>
                                        <?php else: ?>
                                            <a href="intake.php?review=<?= (int)$b['id'] ?>" style="color:#6b7280;font-size:12px;text-decoration:underline;">
                                                View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- How It Works Sidebar Card -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:20px;">
                <h3 style="margin:0 0 12px;font-size:14px;font-weight:700;color:#111827;">How Telegram Intake Works</h3>
                <ol style="margin:0;padding-left:18px;font-size:13px;color:#4b5563;line-height:1.7;">
                    <li><strong>Send Spreadsheet:</strong> Send a wholesale <code style="font-family:var(--f-mono);background:#f3f4f6;padding:1px 4px;">.csv</code> or <code style="font-family:var(--f-mono);background:#f3f4f6;padding:1px 4px;">.xlsx</code> catalog file to your Telegram bot.</li>
                    <li><strong>Upload Photos:</strong> Forward product photography as albums or single shots. Captions with the SKU or Product Title will auto-match!</li>
                    <li><strong>Review &amp; Match:</strong> Click <em>Review &amp; Match</em> here to inspect the 4-tier match confidence badges.</li>
                    <li><strong>Publish:</strong> Click <em>Send to Product Importer</em> to review categories and commit live to your store!</li>
                </ol>
            </div>

            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:20px;">
                <h3 style="margin:0 0 12px;font-size:14px;font-weight:700;color:#111827;">Bot Chat Commands</h3>
                <table style="width:100%;font-size:12px;color:#4b5563;border-collapse:collapse;">
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:6px 0;font-family:var(--f-mono);font-weight:700;color:#111827;">/status</td>
                        <td style="padding:6px 0;">Summary of active staged batch</td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:6px 0;font-family:var(--f-mono);font-weight:700;color:#111827;">/undo</td>
                        <td style="padding:6px 0;">Undo most recent photo or sheet</td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:6px 0;font-family:var(--f-mono);font-weight:700;color:#111827;">/clearsheet</td>
                        <td style="padding:6px 0;">Remove sheet, keep staged photos</td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:6px 0;font-family:var(--f-mono);font-weight:700;color:#111827;">/clearphotos</td>
                        <td style="padding:6px 0;">Remove photos, keep sheet</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;font-family:var(--f-mono);font-weight:700;color:#dc2626;">/clear</td>
                        <td style="padding:6px 0;">Discard entire batch &amp; files</td>
                    </tr>
                </table>
            </div>

            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:20px;">
                <h3 style="margin:0 0 10px;font-size:14px;font-weight:700;color:#111827;">CLI Diagnostics</h3>
                <p style="font-size:12px;color:#6b7280;margin:0 0 10px;line-height:1.5;">
                    Test and maintain your webhook from the command line:
                </p>
                <div style="font-family:var(--f-mono);font-size:11px;background:#18181b;color:#f4f4f5;padding:10px 12px;border-radius:4px;overflow-x:auto;">
                    php tools/tg_webhook_info.php<br>
                    php tools/tg_set_webhook.php<br>
                    php tools/tg_simulate_update.php<br>
                    php tools/tg_poll.php
                </div>
            </div>

            <!-- Webhook Registration Card -->
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:20px;">
                <h3 style="margin:0 0 10px;font-size:14px;font-weight:700;color:#111827;">Live Webhook Control</h3>
                <p style="font-size:12px;color:#6b7280;margin:0 0 12px;line-height:1.5;">
                    Register your public HTTPS endpoint with Telegram with 1-click:
                </p>
                <form method="POST" style="margin:0;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="register_webhook">
                    <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">Webhook Endpoint URL:</label>
                    <input type="url" name="webhook_url" value="<?= htmlspecialchars($cfg['webhook_url'] ?: rtrim(APP_URL, '/') . '/tg_webhook.php') ?>"
                           style="width:100%;box-sizing:border-box;font-family:var(--f-mono);font-size:11px;padding:8px 10px;border:1px solid #d1d5db;border-radius:4px;margin-bottom:10px;" required>
                    <div style="display:flex;gap:8px;flex-direction:column;">
                        <button type="submit" class="btn-red" style="width:100%;height:36px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;">
                            Set Webhook with Telegram
                        </button>
                    </div>
                </form>
                <form method="POST" style="margin-top:8px;" onsubmit="return confirm('Remove webhook and switch bot back to polling mode?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="delete_webhook">
                    <button type="submit" style="width:100%;background:none;border:1px solid #e5e7eb;padding:6px 0;font-size:11px;color:#6b7280;border-radius:4px;cursor:pointer;">
                        Delete Webhook (Polling Mode)
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'layout/footer.php'; ?>
