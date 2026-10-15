<?php
// core/Supplier/HocoParser.php
//
// Parses the HOCO supplier quotation layout into Avazonia v3 preview items
// with 1 product + N color variants per group.
//
// Supplier layout (sheet 'popular products'):
//   name | Picture(embedded) | Package | sku(+newline+color) | Color(tier marker
//   like "<200pcs" OR real color) | price | Msrp | stock | Carton | Volume |
//   Description | Link for clear photos
//
// Row patterns:
//   - Section header: name only, no sku/price/desc (e.g. "EW series ...").
//   - Master row: has name (+ first color/SKU/price/stock/desc).
//   - Continuation row: no name, but has SKU and/or tier price — either a
//     color variant (has barcode) or a pure quantity-break tier (no barcode).

class HocoParser {
    /** Section header -> Avazonia sub-category name. */
    public const SECTION_MAP = [
        'EA series' => 'Earbuds',
        'EQ series' => 'Earbuds',
        'EW series' => 'Earbuds',
        'ES series' => 'Earbuds',
        'E series (single ear' => 'Earbuds',
        'E series (car' => 'Audio',
        'W series' => 'Headphones',
        'Type-C wired earphones' => 'Earbuds',
        '3.5mm wired earphones' => 'Earbuds',
        'HC series' => 'Speakers',
        'BS series' => 'Speakers',
        'UPA series' => 'Audio',
        'LS series' => 'Audio',
        'Apple anti-lost' => 'Phone Accessories',
        'Google anti-lost' => 'Phone Accessories',
        'keyboard/mouse' => 'Laptop Accessories',
        'keyboard' => 'Laptop Accessories',
        'mouse' => 'Laptop Accessories',
        'Multi-charging cables' => 'Cables',
        'Silicone cable' => 'Cables',
        'X series' => 'Cables',
        'HB series' => 'Laptop Accessories',
        'UA series' => 'Cables',
        'Chinese CCC' => 'Chargers',
        'power bank' => 'Chargers',
        'JA series' => 'Chargers',
        'EU plug' => 'Chargers',
        'US plug' => 'Chargers',
        'AC series' => 'Chargers',
        'wireless charging' => 'Chargers',
        'Car holder' => 'Phone Accessories',
        'case' => 'Cases & Covers',
        'tempered glass' => 'Cases & Covers',
        'Other' => '',
    ];

    public static function isHocoSheet(array $headers): bool {
        $keys = array_map(static fn($h) => strtolower(trim((string)$h)), $headers);
        $joined = implode('|', $keys);
        if (str_contains($joined, 'link for clear photos')) return true;
        if (str_contains($joined, 'msrp') && str_contains($joined, 'picture')) return true;
        return false;
    }

    public static function mapSection(string $section): string {
        foreach (self::SECTION_MAP as $needle => $cat) {
            if ($needle !== '' && stripos($section, $needle) !== false) return $cat;
        }
        return '';
    }

    /** Sub-category -> parent fallback when live lacks the sub-category. */
    public const PARENT_FALLBACK = [
        'Earbuds' => 'Audio',
        'Headphones' => 'Audio',
        'Speakers' => 'Audio',
        'Audio' => 'Audio',
        'Cables' => 'Accessories',
        'Chargers' => 'Accessories',
        'Cases & Covers' => 'Accessories',
        'Laptop Accessories' => 'Laptops',
        'Phone Accessories' => 'Smartphones',
    ];

    /**
     * Resolve a desired category name against the live catalogue map.
     * Returns [usedName, categoryId, fellBack]. Falls back to the parent
     * vertical (Audio, Accessories, ...) so imports stay valid on servers
     * where the subcategory seed has not been run.
     */
    public static function resolveCategory(string $desired, array $catMap): array {
        $candidates = [];
        if ($desired !== '') $candidates[] = $desired;
        if (isset(self::PARENT_FALLBACK[$desired])) $candidates[] = self::PARENT_FALLBACK[$desired];
        foreach ($candidates as $i => $name) {
            $id = $catMap[strtolower($name)] ?? null;
            if ($id !== null) return [$name, $id, $i > 0];
        }
        return [$desired, null, false];
    }
    public static function guessCategory(string $name): string {
        $n = strtolower($name);
        if (preg_match('/mouse|keyboard|mouse pad|mousepad|hub|docking|adapter.*usb|otg/', $n)) return 'Laptop Accessories';
        if (preg_match('/tw?s\b|earbud|earphone|headset|owo|ows|air conduction|neckband|single ear/', $n)) return 'Earbuds';
        if (preg_match('/headphone/', $n)) return 'Headphones';
        if (preg_match('/speaker/', $n)) return 'Speakers';
        if (preg_match('/cable|charging.*cable|data cable|type-c.*cable|lightning/', $n)) return 'Cables';
        if (preg_match('/charger|power bank|gan|plug|wall charg|car charg|wireless charg/', $n)) return 'Chargers';
        if (preg_match('/finger|game.*pad|gaming/', $n)) return 'Phone Accessories';
        if (preg_match('/anti-lost|tracker|tag/', $n)) return 'Phone Accessories';
        if (preg_match('/holder|stand|mount/', $n)) return 'Phone Accessories';
        if (preg_match('/case|cover|tempered glass|screen protector/', $n)) return 'Cases & Covers';
        return '';
    }

    private static function clean($v): string {
        if ($v === null) return '';
        if (is_float($v) && (int)$v == $v) return (string)(int)$v;
        return trim((string)$v);
    }

    private static function numOrNull($v): ?float {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            $f = (float)$v;
            return $f > 0 ? $f : null;
        }
        // Supplier cells like "＄1.27\nNo extra discount" (full-width Yuan/mark + note).
        if (is_string($v) && preg_match('/\d+(?:\.\d+)?/', $v, $m)) {
            $f = (float)$m[0];
            return $f > 0 ? $f : null;
        }
        return null;
    }

    private static function intOrZero($v): int {
        if ($v === null || $v === '') return 0;
        if (!is_numeric($v)) return 0;
        return max(0, (int)floatval($v));
    }

    /** Tier markers live in the Color column: "<200pcs", "200-499pcs", ... */
    public static function isTierMarker(string $colorCell): bool {
        if ($colorCell === '') return false;
        if (preg_match('/pcs/i', $colorCell)) return true;
        if (preg_match('/^[<>\x{FF1C}\x{FF1E}\x{2265}\x{2264}]/u', $colorCell)) return true;
        if (preg_match('/^\d+\s*[-+]/', $colorCell)) return true;
        return false;
    }

    /** Split a sku cell that may embed color on following lines. */
    public static function splitSkuCell(string $skuCell): array {
        $lines = preg_split('/[\r\n]+/', $skuCell);
        $lines = array_values(array_filter(array_map('trim', $lines), static fn($s) => $s !== ''));
        if (!$lines) return ['', ''];
        $barcode = '';
        $rest = [];
        foreach ($lines as $ln) {
            $digits = preg_replace('/[^0-9]/', '', $ln);
            if ($barcode === '' && preg_match('/^\d{8,20}$/', $digits)) {
                $barcode = $digits;
            } else {
                // Skip pure-digit leftovers; keep human color text.
                if ($ln !== $digits) $rest[] = $ln;
                elseif (!preg_match('/^\d{8,20}$/', $ln)) $rest[] = $ln;
            }
        }
        if ($barcode === '') {
            // Fallback: first token if it looks like a barcode.
            $first = preg_replace('/[^0-9]/', '', $lines[0]);
            if (preg_match('/^\d{8,20}$/', $first)) {
                $barcode = $first;
                $rest = array_slice($lines, 1);
            }
        }
        return [$barcode, implode(' / ', $rest)];
    }

    public static function cleanName(string $raw): string {
        $t = preg_replace('/\s+/', ' ', trim($raw));
        return mb_substr($t, 0, 200);
    }

    /**
     * @param array $rows Full value matrix INCLUDING header at index 0.
     * @return array groups, each with master + variants + tiers + lines + section
     */
    public static function parseGroups(array $rows): array {
        $groups = [];
        $current = null;
        $section = '';
        foreach ($rows as $idx => $r) {
            if ($idx === 0) continue; // header
            $excelLine = $idx + 1;
            $name = self::clean($r[0] ?? '');
            $skuCell = self::clean($r[3] ?? '');
            $colorCell = self::clean($r[4] ?? '');
            $price = self::numOrNull($r[5] ?? null);
            $desc = self::clean($r[10] ?? '');

            if ($name === '' && $skuCell === '' && $price === null && $desc === '') continue;

            // Section header: name only.
            if ($name !== '' && $skuCell === '' && $price === null && $desc === '') {
                $section = preg_replace('/\s+/', ' ', $name);
                // Flush open group — a header always starts a new context.
                if ($current !== null) { $groups[] = $current; $current = null; }
                continue;
            }

            if ($name !== '') {
                if ($current !== null) $groups[] = $current;
                [$barcode, $colorFromSku] = self::splitSkuCell($skuCell);
                $trueColor = self::isTierMarker($colorCell) || $colorCell === '' ? $colorFromSku : $colorCell;
                $current = [
                    'line' => $excelLine,
                    'lines' => [$excelLine],
                    'section' => $section,
                    'name' => $name,
                    'sku' => $barcode,
                    'color' => $trueColor,
                    'price' => $price,
                    'msrp' => self::numOrNull($r[6] ?? null),
                    'stock' => self::intOrZero($r[7] ?? null),
                    'carton' => self::clean($r[8] ?? ''),
                    'volume' => self::clean($r[9] ?? ''),
                    'desc' => $desc,
                    'link' => self::clean($r[11] ?? ''),
                    'tiers' => [],
                    'variants' => [],
                ];
                if ($price !== null) $current['tiers'][] = ['label' => $colorCell, 'price' => $price];
                continue;
            }

            // Continuation row (no name): belongs to the open group.
            if ($current === null) continue;
            $current['lines'][] = $excelLine;
            [$barcode, $colorFromSku] = self::splitSkuCell($skuCell);
            $trueColor = self::isTierMarker($colorCell) || $colorCell === '' ? $colorFromSku : $colorCell;
            if ($barcode !== '') {
                $current['variants'][] = [
                    'line' => $excelLine,
                    'sku' => $barcode,
                    'color' => $trueColor,
                    'price' => $price,
                    'stock' => self::intOrZero($r[7] ?? null),
                ];
            }
            if ($price !== null) $current['tiers'][] = ['label' => $colorCell, 'price' => $price];
            // A continuation may carry the only stock value (rare); keep max.
            $st = self::intOrZero($r[7] ?? null);
            if ($st > $current['stock']) $current['stock'] = $st;
        }
        if ($current !== null) $groups[] = $current;
        return $groups;
    }

    /**
     * Convert groups to v3 preview items (same shape as ProductCsvImporter::preview).
     *
     * @param array $groups from parseGroups()
     * @param array $imagesByRow excel row => [relative paths]
     */
    public static function toPreviewItems(array $groups, array $imagesByRow, PDO $db): array {
        require_once __DIR__ . '/../ProductCsvImporter.php';
        $lookups = ProductCsvImporter::getLookupOptions($db);
        $catMap = $lookups['category_map'];
        $brandMap = $lookups['brand_map'];

        $preview = [];
        $seenParentSku = [];
        foreach ($groups as $g) {
            $prices = array_values(array_filter(
                array_merge([$g['price']], array_column($g['tiers'], 'price')),
                static fn($p) => $p !== null && $p > 0
            ));
            $price = $prices ? max($prices) : null; // tier rows ascend/descend; retail = highest break
            // For cable-style groups (single price shared) max == the price. For
            // headset tier groups (<200pcs highest) max == retail cost. Correct.
            $low = $prices ? min($prices) : null;

            $colors = [];
            if ($g['color'] !== '') $colors[] = $g['color'];
            foreach ($g['variants'] as $v) {
                if ($v['color'] !== '' && !in_array($v['color'], $colors, true)) $colors[] = $v['color'];
            }

            $wantedCat = self::mapSection($g['section']);
            if ($wantedCat === '') $wantedCat = self::guessCategory($g['name']);
            [$catName, $categoryId, $catFallback] = self::resolveCategory($wantedCat, $catMap);

            // Parent SKU: master barcode, suffixed on collision (supplier reuses
            // barcodes across packaged/unpackaged rows).
            $parentSku = $g['sku'];
            if ($parentSku !== '' && isset($seenParentSku[$parentSku])) {
                $seenParentSku[$parentSku]++;
                $parentSku .= '-' . $seenParentSku[$parentSku];
            } elseif ($parentSku !== '') {
                $seenParentSku[$parentSku] = 1;
            }

            $desc = $g['desc'];
            $meta = [];
            if ($colors) $meta[] = 'Colors: ' . implode(', ', $colors);
            if ($g['msrp'] !== null) $meta[] = 'MSRP (USD): ' . rtrim(rtrim(sprintf('%.4f', $g['msrp']), '0'), '.');
            if ($g['section'] !== '') $meta[] = 'Series: ' . $g['section'];
            if ($g['link'] !== '') $meta[] = 'Supplier gallery: ' . $g['link'];
            if ($meta) $desc = ($desc !== '' ? $desc . "\n" : '') . implode("\n", $meta);

            $specsBits = [];
            if ($g['carton'] !== '') $specsBits[] = 'Carton: ' . $g['carton'];
            if ($g['volume'] !== '') $specsBits[] = 'Volume (cbm): ' . $g['volume'];
            if ($low !== null && $price !== null && abs($low - $price) > 0.0001) {
                $specsBits[] = 'Wholesale from (USD): ' . rtrim(rtrim(sprintf('%.4f', $low), '0'), '.');
            }

            $imgUrls = [];
            foreach ($g['lines'] as $ln) {
                foreach ((array)($imagesByRow[$ln] ?? []) as $p) $imgUrls[] = $p;
                if (count($imgUrls) >= ProductCsvImporter::MAX_IMAGES) break;
            }
            $imgUrls = array_values(array_unique($imgUrls));
            $imgUrls = array_slice($imgUrls, 0, ProductCsvImporter::MAX_IMAGES);

            // Variants: master color first, then continuations. Inherit stock
            // when the continuation carries none (cable white rows).
            $variants = [];
            $seenSku = [];
            $pushVariant = static function (string $color, string $sku, int $stock) use (&$variants, &$seenSku, $g) {
                if ($color === '' && $sku === '') return;
                $key = strtolower($sku !== '' ? $sku : $color);
                if (isset($seenSku[$key])) return;
                $seenSku[$key] = true;
                $variants[] = [
                    'color' => mb_substr($color, 0, 80),
                    'sku' => mb_substr($sku, 0, 80),
                    'stock_qty' => $stock > 0 ? $stock : $g['stock'],
                    'image_url' => null,
                ];
            };
            $pushVariant($g['color'], $g['sku'], $g['stock']);
            foreach ($g['variants'] as $v) {
                $pushVariant($v['color'], $v['sku'], $v['stock']);
            }

            $row = [
                'name' => self::cleanName($g['name']),
                'sku' => $parentSku,
                'price' => $price !== null ? rtrim(rtrim(sprintf('%.4f', $price), '0'), '.') : '',
                'currency' => 'USD',
                'stock' => (string)$g['stock'],
                'category' => $catName,
                'sub_category' => $catName,
                'brand' => 'HOCO',
                'description' => $desc,
                'tags' => implode(', ', $colors),
                'listing_type' => 'retail',
                'condition' => 'new',
                'visibility' => 'public',
                'moq' => count($g['tiers']) > 1 ? '200' : '',
                'wholesale_price' => ($low !== null && $price !== null && abs($low - $price) > 0.0001)
                    ? rtrim(rtrim(sprintf('%.4f', $low), '0'), '.') : '',
                'location_country' => 'CN',
                'features' => '',
                'specs' => implode("\n", $specsBits),
            ];
            foreach (range(1, ProductCsvImporter::MAX_IMAGES) as $k) {
                $row['image_url_' . $k] = $imgUrls[$k - 1] ?? '';
            }

            $errors = [];
            if ($row['name'] === '') $errors[] = 'Product name is required.';
            if ($price === null) $errors[] = 'Price must be a number greater than zero.';
            if ($categoryId === null) {
                $errors[] = $catName !== ''
                    ? 'Category/Subcategory "' . $catName . '" does not match an active catalogue category.'
                    : 'Category could not be mapped from series "' . mb_substr($g['section'], 0, 60) . '" — pick one in the grid.';
            }
            // Brand HOCO is auto-created on import; do not block preview when missing.
            $brandId = $brandMap['hoco'] ?? null;

            // Variant SKU pre-check: flag (not block) collisions already in DB.
            // Import-time linkVariants() will suffix instead of failing.

            $values = [
                'name' => $row['name'],
                'sku' => $parentSku !== '' ? $parentSku : null,
                'price_ghs' => 0,
                'price_usd' => $price,
                'currency' => 'USD',
                'stock_qty' => $g['stock'],
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'description' => $desc !== '' ? $desc : null,
                'tags' => $row['tags'] !== '' ? $row['tags'] : null,
                'listing_type' => 'retail',
                'condition_type' => 'new',
                'visibility' => 'public',
                'moq' => $row['moq'] !== '' ? (int)$row['moq'] : null,
                'wholesale_price_ghs' => null,
                'location_country' => 'CN',
                'available_in_ghana' => 0,
                'features' => null,
                'specs' => $row['specs'] !== '' ? self::specsToJson($row['specs']) : null,
                'image_urls' => $imgUrls,
                'variants' => $variants,
                'hoco_section' => $g['section'],
                'hoco_lines' => $g['lines'],
            ];

            $preview[] = ['line' => $g['line'], 'row' => $row, 'values' => $values, 'errors' => $errors];
        }

        return ['preview' => $preview, 'lookups' => $lookups];
    }

    private static function specsToJson(string $specsRaw): ?string {
        $out = [];
        foreach (preg_split('/[\r\n|;]+/', $specsRaw) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                if (trim($k) !== '') $out[trim($k)] = trim($v);
            }
        }
        return $out ? json_encode($out) : null;
    }
}
