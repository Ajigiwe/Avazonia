<?php
// core/ProductCsvImporter.php

class ProductCsvImporter {
    public const REQUIRED_HEADERS = ['name', 'price'];
    public const HEADERS = [
        'name', 'sku', 'price', 'currency', 'stock', 'category', 'brand', 'description', 'tags',
        'listing_type', 'condition', 'visibility', 'moq', 'wholesale_price',
    ];

    public static function sendTemplate(): void {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="avazonia-products-template.csv"');
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // Keep the CSV UTF-8 clean in Excel and Sheets.
        fputcsv($out, self::HEADERS, ',', '"', '', "\r\n");
        fputcsv($out, [
            'Example Product Name', 'PROD-SKU-001', '99.99', 'GHS', '10', '', '',
            'Add a clear product description.', 'example, new', 'retail', 'new', 'public', '', '',
        ], ',', '"', '', "\r\n");
        fclose($out);
        exit;
    }

    /** Validate the uploaded CSV and return one preview record per data row. */
    public static function preview(string $filePath, PDO $db): array {
        $handle = fopen($filePath, 'r');
        if (!$handle) throw new RuntimeException('The CSV file could not be read.');

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new RuntimeException('The CSV file is empty.');
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string)$header));
            if ($key !== '') $headerMap[$key] = $index;
        }
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerMap)));
        if ($missing) {
            fclose($handle);
            throw new RuntimeException('Missing required CSV columns: ' . implode(', ', $missing) . '. Download the template to get the correct headers.');
        }

        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $categoryColumns = $db->query('PRAGMA table_info(categories)')->fetchAll(PDO::FETCH_COLUMN, 1);
            $categoryHasActive = in_array('is_active', $categoryColumns, true);
        } else {
            $categoryHasActive = true;
        }
        $categorySql = 'SELECT id, name FROM categories WHERE parent_id IS NOT NULL' . ($categoryHasActive ? ' AND is_active = 1' : '');
        $categories = [];
        foreach ($db->query($categorySql) as $row) {
            $categories[strtolower(trim($row['name']))] = (int)$row['id'];
        }
        $brands = [];
        foreach ($db->query('SELECT id, name FROM brands') as $row) {
            $brands[strtolower(trim($row['name']))] = (int)$row['id'];
        }

        $preview = [];
        $line = 1;
        while (($cells = fgetcsv($handle)) !== false) {
            $line++;
            if (count($preview) >= 5000) {
                fclose($handle);
                throw new RuntimeException('A CSV can contain at most 5,000 product rows.');
            }
            if ($cells === [null] || (count($cells) === 1 && trim((string)$cells[0]) === '')) continue;

            $row = [];
            foreach (self::HEADERS as $key) {
                $row[$key] = isset($headerMap[$key]) ? trim((string)($cells[$headerMap[$key]] ?? '')) : '';
            }
            $errors = [];
            if ($row['name'] === '') $errors[] = 'Product name is required.';
            if (strlen($row['name']) > 200) $errors[] = 'Product name must be 200 characters or fewer.';
            if ($row['sku'] !== '' && strlen($row['sku']) > 80) $errors[] = 'SKU must be 80 characters or fewer.';

            $currency = strtoupper($row['currency'] !== '' ? $row['currency'] : 'GHS');
            if (!in_array($currency, ['GHS', 'USD'], true)) $errors[] = 'Currency must be GHS or USD.';
            if ($row['price'] === '' || !is_numeric($row['price']) || (float)$row['price'] <= 0) $errors[] = 'Price must be a number greater than zero.';

            $stock = filter_var($row['stock'] !== '' ? $row['stock'] : '0', FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) $errors[] = 'Stock must be a whole number greater than or equal to zero.';

            $categoryId = null;
            if ($row['category'] !== '') {
                $categoryId = $categories[strtolower($row['category'])] ?? null;
                if ($categoryId === null) $errors[] = 'Category does not match an active catalogue subcategory.';
            }
            $brandId = null;
            if ($row['brand'] !== '') {
                $brandId = $brands[strtolower($row['brand'])] ?? null;
                if ($brandId === null) $errors[] = 'Brand does not match a catalogue brand.';
            }

            $listing = strtolower($row['listing_type'] !== '' ? $row['listing_type'] : 'retail');
            if (!in_array($listing, ['retail', 'wholesale', 'rfq', 'export'], true)) $errors[] = 'Listing type must be retail, wholesale, rfq, or export.';
            $condition = strtolower($row['condition'] !== '' ? $row['condition'] : 'new');
            if (!in_array($condition, ['new', 'used'], true)) $errors[] = 'Condition must be new or used.';
            $visibility = strtolower($row['visibility'] !== '' ? $row['visibility'] : 'public');
            if (!in_array($visibility, ['public', 'b2b_only', 'retail_only'], true)) $errors[] = 'Visibility must be public, b2b_only, or retail_only.';

            $moq = null;
            if ($row['moq'] !== '') {
                $moq = filter_var($row['moq'], FILTER_VALIDATE_INT);
                if ($moq === false || $moq < 1) $errors[] = 'MOQ must be a positive whole number when supplied.';
            }
            $wholesalePrice = null;
            if ($row['wholesale_price'] !== '') {
                if (!is_numeric($row['wholesale_price']) || (float)$row['wholesale_price'] <= 0) $errors[] = 'Wholesale price must be greater than zero when supplied.';
                else $wholesalePrice = (float)$row['wholesale_price'];
            }

            $values = [
                'name' => $row['name'],
                'sku' => $row['sku'] !== '' ? $row['sku'] : null,
                'price_ghs' => $currency === 'GHS' ? (float)$row['price'] : 0,
                'price_usd' => $currency === 'USD' ? (float)$row['price'] : null,
                'currency' => $currency,
                'stock_qty' => $stock === false ? 0 : $stock,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'tags' => $row['tags'] !== '' ? $row['tags'] : null,
                'listing_type' => $listing,
                'condition_type' => $condition,
                'visibility' => $visibility,
                'moq' => $moq,
                'wholesale_price_ghs' => $wholesalePrice,
            ];
            $preview[] = ['line' => $line, 'row' => $row, 'values' => $values, 'errors' => $errors];
        }
        fclose($handle);
        if (!$preview) throw new RuntimeException('The CSV contains no product rows.');
        return $preview;
    }

    /** Process import in chunks or full batch, supporting insert vs upsert mode. */
    public static function import(PDO $db, array $preview, ?int $sellerId, ?int $storeId, string $status = 'active', string $mode = 'insert', int $offset = 0, int $limit = 0): array {
        $columns = self::productColumns($db);
        $required = ['name', 'slug', 'price_ghs', 'currency', 'stock_qty', 'is_active'];
        if (count(array_intersect($required, $columns)) !== count($required)) {
            throw new RuntimeException('The products table is missing required columns. Please run the marketplace migration.');
        }
        if ($sellerId !== null && !in_array('seller_id', $columns, true)) {
            throw new RuntimeException('Seller product imports require the marketplace migration to add seller ownership fields.');
        }

        $validItems = array_values(array_filter($preview, static fn($r) => empty($r['errors'])));
        if ($limit > 0) {
            $itemsToProcess = array_slice($validItems, $offset, $limit);
        } else {
            $itemsToProcess = $validItems;
        }

        $outcomes = [];
        foreach ($itemsToProcess as $item) {
            if (!empty($item['errors'])) continue;
            try {
                $values = $item['values'];
                $existingId = null;

                // Check for existing product in upsert mode
                if ($mode === 'upsert') {
                    if (!empty($values['sku'])) {
                        if ($sellerId !== null) {
                            $stmt = $db->prepare('SELECT id FROM products WHERE sku = ? AND seller_id = ? LIMIT 1');
                            $stmt->execute([$values['sku'], $sellerId]);
                        } else {
                            $stmt = $db->prepare('SELECT id FROM products WHERE sku = ? LIMIT 1');
                            $stmt->execute([$values['sku']]);
                        }
                        $existingId = $stmt->fetchColumn() ?: null;
                    }
                    if (!$existingId && !empty($values['name'])) {
                        if ($sellerId !== null) {
                            $stmt = $db->prepare('SELECT id FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND seller_id = ? LIMIT 1');
                            $stmt->execute([$values['name'], $sellerId]);
                        } else {
                            $stmt = $db->prepare('SELECT id FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
                            $stmt->execute([$values['name']]);
                        }
                        $existingId = $stmt->fetchColumn() ?: null;
                    }
                }

                $db->beginTransaction();

                if ($existingId && $mode === 'upsert') {
                    // Update existing product
                    $updateSql = 'UPDATE products SET
                        price_ghs = ?, price_usd = ?, currency = ?, stock_qty = ?,
                        category_id = ?, brand_id = ?, description = ?, tags = ?,
                        listing_type = ?, condition_type = ?, visibility = ?, moq = ?,
                        wholesale_price_ghs = ?, updated_at = NOW()';
                    $params = [
                        $values['price_ghs'], $values['price_usd'], $values['currency'], $values['stock_qty'],
                        $values['category_id'], $values['brand_id'], $values['description'], $values['tags'],
                        $values['listing_type'], $values['condition_type'], $values['visibility'], $values['moq'],
                        $values['wholesale_price_ghs']
                    ];
                    if (!empty($values['sku']) && in_array('sku', $columns, true)) {
                        $updateSql .= ', sku = ?';
                        $params[] = $values['sku'];
                    }
                    $updateSql .= ' WHERE id = ?';
                    $params[] = (int)$existingId;
                    $stmt = $db->prepare($updateSql);
                    $stmt->execute($params);
                    $db->commit();
                    $outcomes[$item['line']] = ['success' => true, 'id' => (int)$existingId, 'action' => 'updated', 'error' => ''];
                } else {
                    // Insert new product
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $values['name']), '-'));
                    if ($slug === '') $slug = 'product';
                    $slug .= '-' . bin2hex(random_bytes(5));
                    $data = $values + [
                        'slug' => $slug,
                        'seller_id' => $sellerId,
                        'store_id' => $storeId,
                        'status_market' => $status,
                        'is_active' => 1,
                        'is_bestseller' => 0,
                        'is_featured' => 0,
                        'is_preorder' => 0,
                        'is_dropshipping' => 0,
                        'available_in_ghana' => 0,
                        'oem_odm' => 0,
                        'location_country' => 'GH',
                    ];
                    $insertColumns = array_values(array_intersect(array_keys($data), $columns));
                    $sql = 'INSERT INTO products (' . implode(',', $insertColumns) . ') VALUES (' . implode(',', array_fill(0, count($insertColumns), '?')) . ')';
                    $stmt = $db->prepare($sql);
                    $stmt->execute(array_map(static fn($column) => $data[$column], $insertColumns));
                    $id = (int)$db->lastInsertId();
                    $db->commit();
                    $outcomes[$item['line']] = ['success' => true, 'id' => $id, 'action' => 'created', 'error' => ''];
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                $outcomes[$item['line']] = ['success' => false, 'id' => null, 'action' => 'failed', 'error' => 'Database rejected row: ' . $e->getMessage()];
            }
        }
        Cache::flushTags(['products']);
        return $outcomes;
    }

    private static function productColumns(PDO $db): array {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            return $db->query('PRAGMA table_info(products)')->fetchAll(PDO::FETCH_COLUMN, 1);
        }
        return $db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}
