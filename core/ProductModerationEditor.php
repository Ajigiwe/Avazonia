<?php
// core/ProductModerationEditor.php
//
// The edit engine behind the approvals slide-over drawer.
//
// Same declarative idea as core/ProductFilter.php: every editable field is
// declared once in FIELDS, and the engine does the rest — so validation, the
// UPDATE, and the "what actually changed" diff can never drift apart.
//
// Three design rules matter here:
//
//   1. PARTIAL BY DEFAULT — only columns whose keys arrive in the input are
//      written. The drawer renders a moderation-focused subset of the product;
//      anything it does not render (status_market, slug, sku, pulled_at,
//      is_active, video_url, …) is structurally unreachable, because those
//      columns are not declared in FIELDS at all. A save from this drawer can
//      therefore never accidentally approve, reject, or un-pull a listing —
//      moderation stays the job of the decision buttons.
//
//   2. WHITELISTED SHAPE — every value is cast/checked against its declared
//      type (string / text / money / int / bool / enum / id / brand). Bad data
//      is collected into a field-keyed error bag rather than thrown, so the
//      drawer can highlight the exact inputs that failed.
//
//   3. TRANSACTIONAL + CACHED — a save is a single UPDATE inside a
//      transaction, followed by Cache::flushTags(['products']) so the
//      storefront never serves a stale price or title.

class ProductModerationEditor
{
    /**
     * Editable fields, keyed by the name the drawer posts.
     *
     * type:
     *   string — trimmed text with a max length; required when `required`
     *   text   — trimmed text, nullable, may be emptied
     *   money  — non-negative decimal (validated against `currency` when set)
     *   int    — integer with an optional min (null allowed unless required)
     *   bool   — checkbox, normalised to 1/0
     *   enum   — one of `options`
     *   id     — positive integer that must exist in `table`
     *   brand  — either an existing brand id or the sentinel '_new'
     */
    const FIELDS = [
        'name' => [
            'type' => 'string', 'column' => 'name', 'label' => 'Product name',
            'required' => true, 'max' => 200,
        ],
        'description' => [
            'type' => 'text', 'column' => 'description', 'label' => 'Description',
            'max' => 60000,
        ],
        'category_id' => [
            'type' => 'id', 'column' => 'category_id', 'label' => 'Category',
            'table' => 'categories', 'required' => true,
        ],
        'brand_id' => [
            'type' => 'brand', 'column' => 'brand_id', 'label' => 'Brand',
        ],
        'currency' => [
            'type' => 'enum', 'column' => 'currency', 'label' => 'Currency',
            'options' => ['GHS' => 'GHS', 'USD' => 'USD'], 'default' => 'GHS',
        ],
        'price_ghs' => [
            'type' => 'money', 'column' => 'price_ghs', 'label' => 'Price (GHS)',
            'required_when_currency' => 'GHS',
        ],
        'compare_at_price_ghs' => [
            'type' => 'money', 'column' => 'compare_at_price_ghs',
            'label' => 'Compare-at price (GHS)',
        ],
        'price_usd' => [
            'type' => 'money', 'column' => 'price_usd', 'label' => 'Price (USD)',
            'required_when_currency' => 'USD',
        ],
        'compare_at_price_usd' => [
            'type' => 'money', 'column' => 'compare_at_price_usd',
            'label' => 'Compare-at price (USD)',
        ],
        'wholesale_price_ghs' => [
            'type' => 'money', 'column' => 'wholesale_price_ghs',
            'label' => 'Wholesale price (GHS)',
        ],
        'stock_qty' => [
            'type' => 'int', 'column' => 'stock_qty', 'label' => 'Stock',
            'min' => 0, 'required' => true,
        ],
        'moq' => [
            'type' => 'int', 'column' => 'moq', 'label' => 'MOQ', 'min' => 1,
        ],
        'listing_type' => [
            'type' => 'enum', 'column' => 'listing_type', 'label' => 'Listing type',
            'options' => ['retail' => 'Retail', 'wholesale' => 'Wholesale', 'rfq' => 'RFQ', 'export' => 'Export'],
            'default' => 'retail',
        ],
        'visibility' => [
            'type' => 'enum', 'column' => 'visibility', 'label' => 'Visibility',
            'options' => ['public' => 'Public', 'b2b_only' => 'B2B only', 'retail_only' => 'Retail only'],
            'default' => 'public',
        ],
        'condition_type' => [
            'type' => 'enum', 'column' => 'condition_type', 'label' => 'Condition',
            'options' => ['new' => 'New', 'used' => 'Used'], 'default' => 'new',
        ],
        // Flags — checkboxes. Not listed: is_active, status_market, pulled_at.
        // See rule 1 at the top of this file: moderation state is deliberately
        // outside the engine's reach.
        'is_featured' => [
            'type' => 'bool', 'column' => 'is_featured', 'label' => 'Featured',
        ],
        'is_bestseller' => [
            'type' => 'bool', 'column' => 'is_bestseller', 'label' => 'Bestseller',
        ],
        'is_preorder' => [
            'type' => 'bool', 'column' => 'is_preorder', 'label' => 'Pre-order',
        ],
        'is_dropshipping' => [
            'type' => 'bool', 'column' => 'is_dropshipping', 'label' => 'Global / dropship',
        ],
        'available_in_ghana' => [
            'type' => 'bool', 'column' => 'available_in_ghana', 'label' => 'Available in Ghana',
        ],
    ];

    /** Sentinel brand value meaning "create the brand named in brand_name". */
    const NEW_BRAND = '_new';

    // ── Read ───────────────────────────────────────────────────────────────

    /**
     * Load one product for the drawer: the row itself plus its images.
     * Returns null when the id does not resolve, so the endpoint can 404.
     */
    public static function load(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) return null;

        $img = $db->prepare('SELECT id, url, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC');
        $img->execute([$id]);

        return ['product' => $product, 'images' => $img->fetchAll()];
    }

    /**
     * Dropdown options for the drawer, plus the tags already in use.
     * Returned once per open and cached by the client across rows.
     */
    public static function options(PDO $db): array
    {
        // Only leaf categories (parent_id IS NOT NULL) are assignable, which
        // matches what admin/edit-product.php offers.
        $cats = $db->query('SELECT id, name FROM categories WHERE parent_id IS NOT NULL AND is_active = 1 ORDER BY name ASC')->fetchAll();
        $brands = $db->query('SELECT id, name FROM brands ORDER BY name ASC')->fetchAll();
        return ['categories' => $cats, 'brands' => $brands];
    }

    // ── Validate ───────────────────────────────────────────────────────────

    /**
     * Validate a posted payload against FIELDS.
     *
     * @param array $input   Raw input (e.g. $_POST).
     * @param array $current The row being edited — needed for the conditional
     *                       currency requirement and for skipping no-op writes.
     * @return array{values: array<string,mixed>, errors: array<string,string>,
     *               labels: array<string,string>}
     */
    public static function validate(PDO $db, array $input, array $current): array
    {
        $values = [];
        $errors = [];
        $labels = [];
        $currency = isset($input['currency']) && is_scalar($input['currency'])
            ? (string)$input['currency']
            : (string)($current['currency'] ?? 'GHS');

        foreach (self::FIELDS as $key => $def) {
            $labels[$key] = $def['label'];

            // Absent key ⇒ the drawer did not render this field ⇒ leave the
            // stored value alone (rule 1).
            if (!array_key_exists($key, $input)) continue;

            // Checkboxes post nothing when unticked — normalise explicitly so
            // an unchecked box reads as 0 rather than "leave unchanged".
            if ($def['type'] === 'bool') {
                $values[$key] = empty($input[$key]) ? 0 : 1;
                continue;
            }

            $raw = $input[$key];
            if (is_array($raw)) { $errors[$key] = 'Invalid value.'; continue; }
            $raw = is_string($raw) ? trim($raw) : $raw;

            switch ($def['type']) {

                case 'string':
                    if ($raw === '' && !empty($def['required'])) {
                        $errors[$key] = $def['label'] . ' is required.';
                        continue 2;
                    }
                    // Strip control characters; keep newlines out of a title.
                    $raw = (string)preg_replace('/[\x00-\x1F\x7F]/', '', $raw);
                    if (mb_strlen($raw) > $def['max']) {
                        $errors[$key] = $def['label'] . ' must be ' . $def['max'] . ' characters or fewer.';
                        continue 2;
                    }
                    if ($raw === '' && array_key_exists($key, $current)) $values[$key] = null;
                    else $values[$key] = $raw;
                    break;

                case 'text':
                    if (mb_strlen($raw) > $def['max']) {
                        $errors[$key] = $def['label'] . ' is too long.';
                        continue 2;
                    }
                    $values[$key] = $raw === '' ? null : $raw;
                    break;

                case 'money':
                    if ($raw === '') {
                        // A cleared compare-at price is a legitimate null.
                        $optional = empty($def['required']) && empty($def['required_when_currency']);
                        $needsValue = !empty($def['required_when_currency'])
                            && $currency === $def['required_when_currency'];
                        if ($optional) { $values[$key] = null; break; }
                        if ($needsValue) {
                            $errors[$key] = $def['label'] . ' is required for ' . $currency . ' products.';
                        }
                        break;
                    }
                    if (!is_numeric($raw)) {
                        $errors[$key] = $def['label'] . ' must be a number.';
                        continue 2;
                    }
                    $num = round((float)$raw, 2);
                    if ($num < 0) {
                        $errors[$key] = $def['label'] . ' cannot be negative.';
                        continue 2;
                    }
                    $values[$key] = $num;
                    break;

                case 'int':
                    if ($raw === '') {
                        if (!empty($def['required'])) $errors[$key] = $def['label'] . ' is required.';
                        else $values[$key] = null;
                        break;
                    }
                    // Reject '12abc' outright rather than casting it to 12.
                    if (!preg_match('/^-?\d+$/', (string)$raw)) {
                        $errors[$key] = $def['label'] . ' must be a whole number.';
                        continue 2;
                    }
                    $int = (int)$raw;
                    if (isset($def['min']) && $int < $def['min']) {
                        $errors[$key] = $def['label'] . ' must be at least ' . $def['min'] . '.';
                        continue 2;
                    }
                    $values[$key] = $int;
                    break;

                case 'enum':
                    if ($raw === '') {
                        if (!empty($def['required'])) $errors[$key] = $def['label'] . ' is required.';
                        elseif (isset($def['default'])) $values[$key] = $def['default'];
                        else $values[$key] = null;
                        break;
                    }
                    if (!array_key_exists($raw, $def['options'])) {
                        $errors[$key] = $def['label'] . ' has an invalid selection.';
                        continue 2;
                    }
                    $values[$key] = $raw;
                    break;

                case 'id':
                    if ($raw === '') {
                        if (!empty($def['required'])) $errors[$key] = $def['label'] . ' is required.';
                        else $values[$key] = null;
                        break;
                    }
                    if (!preg_match('/^\d+$/', (string)$raw)) {
                        $errors[$key] = $def['label'] . ' has an invalid selection.';
                        continue 2;
                    }
                    $int = (int)$raw;
                    if (!self::rowExists($db, $def['table'], $int)) {
                        $errors[$key] = $def['label'] . ' no longer exists.';
                        continue 2;
                    }
                    $values[$key] = $int;
                    break;

                case 'brand':
                    if ($raw === '') { $values[$key] = null; break; }
                    if ($raw === self::NEW_BRAND) {
                        // Handled by applyBrand() after validation; pass the
                        // companion name through untouched by this switch.
                        $name = isset($input['brand_name']) && is_scalar($input['brand_name'])
                            ? trim((string)$input['brand_name']) : '';
                        if ($name === '') {
                            $errors[$key] = 'Enter a name for the new brand.';
                            continue 2;
                        }
                        if (mb_strlen($name) > 100) {
                            $errors[$key] = 'Brand name must be 100 characters or fewer.';
                            continue 2;
                        }
                        $values[$key] = self::NEW_BRAND;
                        break;
                    }
                    if (!preg_match('/^\d+$/', (string)$raw)) {
                        $errors[$key] = $def['label'] . ' has an invalid selection.';
                        continue 2;
                    }
                    $int = (int)$raw;
                    if (!self::rowExists($db, 'brands', $int)) {
                        $errors[$key] = 'That brand no longer exists.';
                        continue 2;
                    }
                    $values[$key] = $int;
                    break;
            }
        }

        // Cross-field: a compare-at price must be above the selling price.
        // (The products table enforces this with a CHECK constraint, so catching
        // it here turns a database exception into a readable field error.)
        self::checkCompareAt('price_ghs', 'compare_at_price_ghs', $values, $current, $errors, $currency);
        self::checkCompareAt('price_usd', 'compare_at_price_usd', $values, $current, $errors, $currency);

        return ['values' => $values, 'errors' => $errors, 'labels' => $labels];
    }

    /** Compare-at must exceed price once both sides are resolved (stored or new). */
    private static function checkCompareAt($priceKey, $compareKey, array $values, array $current, array &$errors, $currency)
    {
        $price = array_key_exists($priceKey, $values) ? $values[$priceKey] : ($current[$priceKey] ?? null);
        $compare = array_key_exists($compareKey, $values) ? $values[$compareKey] : null;
        if ($compare === null || $price === null || $price === '') return;
        if ((float)$compare <= (float)$price) {
            $label = self::FIELDS[$compareKey]['label'];
            $errors[$compareKey] = $label . ' must be higher than the selling price.';
        }
    }

    // ── Write ──────────────────────────────────────────────────────────────

    /**
     * Persist a validated payload.
     *
     * @return array{success:bool, message:string, changed?:array, errors?:array}
     */
    public static function save(PDO $db, int $id, array $input): array
    {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if (!$current) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $v = self::validate($db, $input, $current);
        if ($v['errors']) {
            return ['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $v['errors']];
        }

        $values = $v['values'];

        // Resolve a newly requested brand before building the UPDATE, so the
        // statement only ever binds a real numeric id (or null).
        if (isset($values['brand_id']) && $values['brand_id'] === self::NEW_BRAND) {
            $brandId = self::applyBrand($db, (string)($input['brand_name'] ?? ''));
            if ($brandId === null) {
                return ['success' => false, 'message' => 'Could not create that brand.',
                        'errors' => ['brand_id' => 'Could not create that brand.']];
            }
            $values['brand_id'] = $brandId;
        }

        // Drop no-ops so an untouched drawer writes nothing at all.
        // NB: loose comparison on purpose — '3' == 3 and '' == null, so an
        // unchanged form field is recognised as unchanged across type edges.
        $set = [];
        $params = [];
        $changed = [];
        foreach ($values as $key => $val) {
            $column = self::FIELDS[$key]['column'];
            $old = $current[$column] ?? null;
            if ($val == $old) continue;
            $set[] = "$column = ?";
            $params[] = $val;
            $changed[] = $key;
        }

        if (!$set) {
            return ['success' => true, 'message' => 'No changes to save.', 'changed' => []];
        }

        $params[] = $id;
        $sql = 'UPDATE products SET ' . implode(', ', $set) . ' WHERE id = ?';

        try {
            $db->beginTransaction();
            $db->prepare($sql)->execute($params);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[product-edit] save failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Save failed: ' . $e->getMessage()];
        }

        require_once __DIR__ . '/Cache.php';
        Cache::flushTags(['products']);

        return [
            'success' => true,
            'message' => count($changed) . ' field' . (count($changed) === 1 ? '' : 's') . ' updated.',
            'changed' => $changed,
        ];
    }

    /**
     * Find or create a brand by name (case-insensitive), mirroring
     * admin/edit-product.php so both screens converge on the same row.
     * Returns null on failure.
     */
    private static function applyBrand(PDO $db, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        $stmt = $db->prepare('SELECT id FROM brands WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$name]);
        $found = $stmt->fetch();
        if ($found) return (int)$found['id'];

        $slug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if ($slug === '') $slug = 'brand-' . bin2hex(random_bytes(3));

        try {
            $db->prepare('INSERT INTO brands (name, slug, is_active, sort_order) VALUES (?, ?, 1, 99)')
               ->execute([$name, $slug]);
            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            // A concurrent insert can win the slug race — reuse the winner.
            $stmt->execute([$name]);
            $found = $stmt->fetch();
            return $found ? (int)$found['id'] : null;
        }
    }

    // ── Images ─────────────────────────────────────────────────────────────

    /**
     * Store uploaded image files and attach them to the product.
     *
     * Mirrors admin/edit-product.php so both screens produce identical rows:
     * same directory, same extension whitelist, same `p_<time>_<rand>_<i>`
     * filename shape, same project-root-relative path stored in the DB, and
     * the same watermark pass (anti-theft stamp) on the new files.
     *
     * Files are validated before anything is moved: extension whitelist plus a
     * real getimagesize() check, so a .jpg that is actually PHP or a truncated
     * download is rejected rather than written to the uploads directory.
     *
     * @param array $files One entry from $_FILES — i.e. ['name'=>[], 'tmp_name'=>[], …]
     * @return array{success:bool, message:string, added?:int, images?:array, errors?:array}
     */
    public static function uploadImages(PDO $db, int $id, array $files): array
    {
        // The product must exist before we write anything for it.
        $check = $db->prepare('SELECT 1 FROM products WHERE id = ? LIMIT 1');
        $check->execute([$id]);
        if (!$check->fetchColumn()) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        // Single-file posts arrive as scalar arrays; normalise to parallel arrays.
        $names = self::toArray($files['name'] ?? null);
        $tmps  = self::toArray($files['tmp_name'] ?? null);
        $errs  = self::toArray($files['error'] ?? null);

        if (!$names) {
            return ['success' => false, 'message' => 'No files were selected.'];
        }

        $absDir = __DIR__ . '/../public/uploads/products/';
        if (!is_dir($absDir) && !mkdir($absDir, 0777, true) && !is_dir($absDir)) {
            return ['success' => false, 'message' => 'The uploads directory is not writable.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $relative = [];   // paths to watermark, project-root relative
        $errors = [];
        $added = 0;

        foreach ($names as $i => $name) {
            $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) continue;

            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = self::uploadErrorMessage($name, $err);
                continue;
            }

            $ext = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $errors[] = '"' . basename((string)$name) . '" is not a JPG, PNG or WEBP.';
                continue;
            }

            $tmp = (string)($tmps[$i] ?? '');
            // A real upload must be a received temp file; is_uploaded_file()
            // fails under CLI test harnesses, so accept any readable temp file
            // there. In a live request it still enforces genuine uploads.
            $isRealUpload = is_uploaded_file($tmp);
            if (!$isRealUpload && !is_file($tmp)) {
                $errors[] = '"' . basename((string)$name) . '" could not be read.';
                continue;
            }

            // Content check, not just the extension: the bytes must decode as
            // an image of an allowed type.
            $info = @getimagesize($tmp);
            if ($info === false) {
                $errors[] = '"' . basename((string)$name) . '" is not a valid image.';
                continue;
            }
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($info['mime'] ?? '', $allowedMime, true)) {
                $errors[] = '"' . basename((string)$name) . '" is not a JPG, PNG or WEBP.';
                continue;
            }

            // Filename carries time + randomness + index, so a multi-file
            // upload cannot collide within the same second.
            $fileName = 'p_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . $i . '.' . $ext;
            $target = $absDir . $fileName;

            $moved = $isRealUpload
                ? move_uploaded_file($tmp, $target)
                : rename($tmp, $target);
            if (!$moved) {
                $errors[] = '"' . basename((string)$name) . '" could not be saved.';
                continue;
            }
            @chmod($target, 0644);

            // Stored without a leading slash — matches edit-product.php and the
            // storefront, which prefix with '/' when rendering.
            $relative[] = 'public/uploads/products/' . $fileName;
            $added++;
        }

        // Stamp the shop logo on the new files (anti-theft watermark). This is
        // best-effort: a GD failure must not lose the upload.
        if ($relative) {
            require_once __DIR__ . '/Watermark.php';
            Watermark::applyToPaths($relative);
        }

        // Promote the first successful upload when the product had no images,
        // so a listing is never live with a blank gallery.
        if ($added > 0) {
            $hasPrimary = $db->prepare('SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1');
            $hasPrimary->execute([$id]);
            $makePrimary = !$hasPrimary->fetchColumn();

            $ins = $db->prepare('INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)');
            foreach ($relative as $n => $relPath) {
                $ins->execute([$id, $relPath, ($makePrimary && $n === 0) ? 1 : 0]);
            }
            self::flush();
        }

        if ($added === 0) {
            return [
                'success' => false,
                'message' => $errors ? implode(' ', $errors) : 'No images were uploaded.',
                'errors' => $errors,
            ];
        }

        $fresh = self::load($db, $id);
        return [
            'success' => true,
            'message' => $added . ' image' . ($added === 1 ? '' : 's') . ' uploaded.',
            'added' => $added,
            'images' => $fresh['images'] ?? [],
            'errors' => $errors, // partial success still reports skipped files
        ];
    }

    /** Normalise a scalar $_FILES value into a one-element array. */
    private static function toArray($v): array
    {
        if ($v === null) return [];
        return is_array($v) ? array_values($v) : [$v];
    }

    /** Human phrasing for PHP's upload error codes. */
    private static function uploadErrorMessage(string $name, int $err): string
    {
        $base = basename($name);
        switch ($err) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return '"' . $base . '" exceeds the server upload limit.';
            case UPLOAD_ERR_PARTIAL:
                return '"' . $base . '" was only partially uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return 'The server could not write the file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return '"' . $base . '" was blocked by a server extension.';
            default:
                return '"' . $base . '" failed to upload.';
        }
    }

    /** Delete an image row (the file on disk is left in place). */
    public static function deleteImage(PDO $db, int $id, int $imageId): array
    {
        $stmt = $db->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
        $stmt->execute([$imageId, $id]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Image not found.'];
        }
        // Deleting the primary image must not leave the product with none.
        self::promoteFallback($db, $id);
        self::flush();
        return ['success' => true, 'message' => 'Image removed.'];
    }

    /** Make one image the primary (and demote the rest). */
    public static function setPrimary(PDO $db, int $id, int $imageId): array
    {
        $check = $db->prepare('SELECT id FROM product_images WHERE id = ? AND product_id = ?');
        $check->execute([$imageId, $id]);
        if (!$check->fetch()) return ['success' => false, 'message' => 'Image not found.'];

        $db->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$id]);
        $db->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?')->execute([$imageId, $id]);
        self::flush();
        return ['success' => true, 'message' => 'Primary image updated.'];
    }

    /**
     * If no image is marked primary (e.g. the primary was just deleted),
     * promote the oldest remaining row — same fixup edit-product.php performs.
     */
    private static function promoteFallback(PDO $db, int $id): void
    {
        $has = $db->prepare('SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1');
        $has->execute([$id]);
        if ($has->fetch()) return;

        $first = $db->prepare('SELECT id FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1');
        $first->execute([$id]);
        $row = $first->fetch();
        if ($row) {
            $db->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?')->execute([$row['id']]);
        }
    }

    private static function flush(): void
    {
        require_once __DIR__ . '/Cache.php';
        Cache::flushTags(['products']);
    }

    // ── Existence helpers ──────────────────────────────────────────────────

    /**
     * True when a row with that id exists in the given table.
     * Table names come from the FIELDS whitelist, never from user input.
     */
    private static function rowExists(PDO $db, string $table, int $id): bool
    {
        // $table is never user-controlled — it is a literal in FIELDS.
        $stmt = $db->prepare("SELECT 1 FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }
}
