<?php
/**
 * Telegram intake - structured vehicle & product text parser.
 *
 * Automatically parses mobile vehicle / product listings into structured catalog
 * entries, extracting specifications, key features, pricing, condition, export terms,
 * and narrative descriptions.
 */

declare(strict_types=1);

// Polyfills for environments missing the mbstring extension (e.g. shared hosting)
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string {
        return strtolower($string);
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string {
        return strtoupper($string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int {
        return strlen($string);
    }
}
if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trim_marker = '', ?string $encoding = null): string {
        $sub = substr($string, $start, $width);
        if (strlen($string) > $width && $trim_marker !== '') {
            $sub = substr($sub, 0, max(0, $width - strlen($trim_marker))) . $trim_marker;
        }
        return $sub;
    }
}
if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding(mixed $value = null, ?string $encoding = null): bool {
        return true;
    }
}
if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding(mixed $string, string $to_encoding, mixed $from_encoding = null): mixed {
        if (!is_string($string)) return $string;
        if (function_exists('iconv')) {
            $from = is_string($from_encoding) ? $from_encoding : 'Windows-1252';
            $converted = @iconv($from, $to_encoding . '//IGNORE', $string);
            if ($converted !== false) return $converted;
        }
        return $string;
    }
}

/**
 * Checks whether an incoming text message or photo caption is a structured product/vehicle listing.
 */
if (!function_exists('intake_is_listing_text')) {
    function intake_is_listing_text(string $text): bool
    {
        $normalized = str_replace(['：', "\r\n", "\r"], [':', "\n", "\n"], trim($text));
        $t = mb_strtolower($normalized);
        if (mb_strlen($t) < 25) {
            return false;
        }

        $indicators = [
            'vehicle specification',
            'vehicle specifications',
            'specifications',
            'specs',
            'key features',
            'features',
            'brand:',
            'make:',
            'model:',
            'year:',
            'model year:',
            'fob price',
            'fob:',
            'price:',
            'export price',
            'engine:',
            'transmission:',
            'mileage:',
            'seating capacity',
            'fuel type:',
            'frame number:',
            'vin:',
            'condition:',
            'why choose avazonia',
            'pre-order',
            'preorder',
            'fob',
        ];

        $hits = 0;
        foreach ($indicators as $ind) {
            if (str_contains($t, $ind)) {
                $hits++;
            }
        }

        if ($hits >= 2) {
            return true;
        }

        // Check key-value lines (e.g. Make: EXEED, Model: Yaoguang, Price: 15393)
        $kvLines = preg_match_all('/^[a-z\s]{2,25}\s*:\s*.+/im', $t);
        return $kvLines >= 2;
    }
}

if (!function_exists('intake_parse_listing_text')) {
    function intake_parse_listing_text(string $raw): array
    {
        // Normalize newlines and full-width colons
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($raw));
        $normalized = str_replace('：', ':', $normalized);
        $lines = array_map('trim', explode("\n", $normalized));

        while (!empty($lines) && $lines[0] === '') {
            array_shift($lines);
        }

        if (empty($lines)) {
            return [];
        }

        $section = 'header';
        $specs = [];
        $features = [];
        $overviewLines = [];
        $conditionLines = [];
        $shippingLines = [];
        $footerLines = [];
        $titleCandidates = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $cleanLine = trim($line, "#* \t-");
            $lowerClean = mb_strtolower($cleanLine);

            // Section header detection
            if (str_starts_with($lowerClean, 'vehicle specification') || str_starts_with($lowerClean, 'specifications') || str_starts_with($lowerClean, 'specs')) {
                $section = 'specs';
                continue;
            }
            if (str_starts_with($lowerClean, 'key feature') || str_starts_with($lowerClean, 'features')) {
                $section = 'features';
                continue;
            }
            if ($lowerClean === 'condition' || str_starts_with($lowerClean, 'vehicle condition')) {
                $section = 'condition';
                continue;
            }
            if (str_starts_with($lowerClean, 'price & shipping') || str_starts_with($lowerClean, 'pricing & shipping') || str_starts_with($lowerClean, 'price and shipping')) {
                $section = 'shipping';
                continue;
            }
            if (str_starts_with($lowerClean, 'why choose') || str_starts_with($lowerClean, 'interested in') || str_starts_with($lowerClean, 'contact') || str_starts_with($lowerClean, '📞') || str_starts_with($lowerClean, '🌐')) {
                $section = 'footer';
                $footerLines[] = $line;
                continue;
            }

            // Potential title candidate (before specs/footer, substantive text, no key-value colon)
            if ($section === 'header' && !str_contains($cleanLine, ':') && !str_starts_with($lowerClean, 'http') && mb_strlen($cleanLine) > 5) {
                $titleCandidates[] = $cleanLine;
            }

            // If in header section but encountered a spec key like "Brand: Jetour", auto-switch to specs!
            if ($section === 'header' && preg_match('/^(?:brand|make|model|year|model year|transmission|engine|mileage|seating capacity|fuel type|frame number)\s*:/i', $cleanLine)) {
                $section = 'specs';
            }

            switch ($section) {
                case 'header':
                    $overviewLines[] = $line;
                    break;
                case 'specs':
                    if (str_contains($line, ':')) {
                        [$k, $v] = explode(':', $line, 2);
                        $k = trim(trim($k), "* \t-•·");
                        $v = trim(trim($v), "* \t");
                        if ($k !== '') {
                            $specs[$k] = $v;
                        }
                    } elseif (!empty($cleanLine)) {
                        $specs[$cleanLine] = '';
                    }
                    break;
                case 'features':
                    $f = trim((string)preg_replace('/^(\s*[-•*·]\s*|\s*\d+[\.\)]\s+)/u', '', $line));
                    if ($f !== '') {
                        $features[] = $f;
                    }
                    break;
                case 'condition':
                    $conditionLines[] = $line;
                    break;
                case 'shipping':
                    $shippingLines[] = $line;
                    break;
                case 'footer':
                    $footerLines[] = $line;
                    break;
            }
        }

        // Brand, Model, Year extraction with rich aliases
        $brand = $specs['Brand'] ?? $specs['brand'] ?? $specs['Make'] ?? $specs['make'] ?? $specs['Manufacturer'] ?? '';
        $model = $specs['Model'] ?? $specs['model'] ?? $specs['Model Name'] ?? '';
        $year  = $specs['Year'] ?? $specs['year'] ?? $specs['Model Year'] ?? $specs['model year'] ?? $specs['Manufacture Year'] ?? $specs['Year of Manufacture'] ?? '';

        // Pick best title
        $title = $titleCandidates[0] ?? trim($lines[0], "#* \t-");
        foreach ($titleCandidates as $c) {
            if (preg_match('/\b(20[12]\d)\b/', $c)) {
                $title = $c;
                break;
            }
        }

        // If Brand or Year missing from specs, attempt regex on title
        if ($year === '' && preg_match('/\b(20[12]\d)\b/', $title, $ym)) {
            $year = $ym[1];
        }
        if ($brand === '') {
            $knownBrands = ['Jetour', 'Toyota', 'Honda', 'Mercedes-Benz', 'Mercedes', 'BMW', 'Audi', 'Hyundai', 'Kia', 'Nissan', 'Geely', 'BYD', 'Changan', 'Haval', 'Chery', 'GAC', 'Great Wall', 'Volkswagen', 'VW', 'Ford', 'Lexus', 'Mazda', 'Tesla', 'Peugeot', 'Land Rover', 'EXEED', 'Zeekr', 'Li Auto', 'NIO', 'Voyah', 'Hongqi', 'Tank', 'Dongfeng', 'FAW', 'MG'];
            foreach ($knownBrands as $kb) {
                if (stripos($title, $kb) !== false) {
                    $brand = $kb;
                    break;
                }
            }
        }

        // If title is empty or looks like a spec key (e.g. "Fuel Type: Petrol" or "Frame Number: 015775") or section header:
        if (empty($title) || str_contains($title, ':') || preg_match('/^(?:vehicle specifications|specifications|key features|price|condition)$/i', $title)) {
            $synthesized = trim(implode(' ', array_filter([$year, $brand, $model])));
            if (!empty($synthesized)) {
                $title = $synthesized;
            } else {
                $title = 'Vehicle Listing';
            }
        }

        // Price extraction
        $price = 0.0;
        $currency = 'USD';

        $priceRaw = $specs['Price'] ?? $specs['price'] ?? $specs['FOB Price'] ?? $specs['fob price'] ?? $specs['Price (FOB)'] ?? $specs['FOB'] ?? '';
        if ($priceRaw === '') {
            foreach ($shippingLines as $sl) {
                if (stripos($sl, 'price') !== false || stripos($sl, 'fob') !== false) {
                    $priceRaw = $sl;
                    break;
                }
            }
        }

        if ($priceRaw !== '') {
            if (preg_match('/(?:us\$|usd|\$)\s*([\d,]+(?:\.\d+)?)/iu', $priceRaw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            } elseif (preg_match('/(?:gh[¢s\x{20B5}]|ghs|[¢\x{20B5}])\s*([\d,]+(?:\.\d+)?)/iu', $priceRaw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'GHS';
            } elseif (preg_match('/([\d,]+(?:\.\d+)?)\s*(?:usd|us\$|\$|\(fob\)|fob)/iu', $priceRaw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            } elseif (preg_match('/([\d,]+(?:\.\d+)?)/u', $priceRaw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
            }
        }

        // Fallback: scan entire raw text for price / fob
        if ($price <= 0.0) {
            if (preg_match('/(?:fob\s*price|export\s*price|fob|price)\s*[:：]?\s*(?:us\$|usd|\$)\s*([\d,]+(?:\.\d+)?)/iu', $raw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            } elseif (preg_match('/(?:fob\s*price|export\s*price|fob|price)\s*[:：]?\s*(?:gh[¢s\x{20B5}]|ghs|[¢\x{20B5}])\s*([\d,]+(?:\.\d+)?)/iu', $raw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'GHS';
            } elseif (preg_match('/(?:fob\s*price|export\s*price|fob|price)\s*[:：]?\s*([\d,]+(?:\.\d+)?)\s*(?:usd|us\$|\$|\(fob\)|fob)/iu', $raw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            } elseif (preg_match('/(?:fob\s*price|export\s*price|price)\s*[:：]?\s*([\d,]+(?:\.\d+)?)/iu', $raw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            } elseif (preg_match('/(?:us\$|usd|\$)\s*([\d,]+(?:\.\d+)?)/iu', $raw, $m)) {
                $price = (float)str_replace(',', '', $m[1]);
                $currency = 'USD';
            }
        }

        // Incoterms
        $incoterms = 'FOB';
        $searchScope = $priceRaw . ' ' . implode(' ', $shippingLines);
        if (preg_match('/\b(CIF|EXW|FOB)\b/i', $searchScope, $im)) {
            $incoterms = strtoupper($im[1]);
        }

        // Condition
        $condStr = mb_strtolower(($specs['Condition'] ?? '') . ' ' . implode(' ', $conditionLines));
        $conditionType = (str_contains($condStr, 'used') || str_contains($condStr, 'mileage')) ? 'used' : 'new';

        // Preorder & Location
        $shippingStr = mb_strtolower(implode(' ', $shippingLines) . ' ' . implode(' ', $footerLines));
        $isPreorder = (str_contains($shippingStr, 'pre-order') || str_contains($shippingStr, 'preorder') || str_contains($shippingStr, 'not available for immediate delivery')) ? 1 : 0;
        $availableInGhana = ($isPreorder || str_contains($shippingStr, 'not available for immediate delivery in ghana')) ? 0 : 1;
        $locationCountry = (str_contains($shippingStr, 'china') || str_contains($shippingStr, 'fob')) ? 'CN' : 'GH';
        $vehicleOrigin = ($locationCountry === 'CN' || $isPreorder) ? 'international_export' : 'local';

        // Subcategory mapping
        $titleLower = mb_strtolower($title);
        $subCategory = 'Cars';
        if (str_contains($titleLower, 'suv')) {
            $subCategory = 'SUVs';
        } elseif (str_contains($titleLower, 'ev') || str_contains($titleLower, 'electric')) {
            $subCategory = 'EVs';
        } elseif (str_contains($titleLower, 'hybrid')) {
            $subCategory = 'Hybrid Vehicles';
        } elseif (str_contains($titleLower, 'truck')) {
            $subCategory = 'Trucks';
        } elseif (str_contains($titleLower, 'bus')) {
            $subCategory = 'Buses';
        }

        // Auto-generate SKU
        $skuBrand = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $brand ?: 'VEH'), 0, 4));
        $skuModel = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $model ?: 'MOD'), 0, 4));
        $skuYear = $year ?: date('Y');
        $skuRand = strtoupper(bin2hex(random_bytes(2)));
        $sku = "{$skuBrand}-{$skuModel}-{$skuYear}-{$skuRand}";

        // Clean description body
        $descParts = [];
        if (!empty($overviewLines)) {
            $descParts[] = implode("\n", $overviewLines);
        }
        if (!empty($conditionLines)) {
            $descParts[] = "Condition:\n" . implode("\n", $conditionLines);
        }
        if (!empty($shippingLines)) {
            $descParts[] = "Price & Shipping:\n" . implode("\n", $shippingLines);
        }
        if (!empty($footerLines)) {
            $descParts[] = implode("\n", $footerLines);
        }
        $description = implode("\n\n", $descParts);

        return [
            'name'               => $title,
            'brand'              => $brand,
            'model'              => $model,
            'year'               => $year,
            'sku'                => $sku,
            'price'              => $price,
            'currency'           => $currency,
            'fob_price'          => $price,
            'incoterms'          => $incoterms,
            'category'           => 'Vehicles',
            'sub_category'       => $subCategory,
            'condition_type'     => $conditionType,
            'is_preorder'        => $isPreorder,
            'available_in_ghana' => $availableInGhana,
            'vehicle_origin'     => $vehicleOrigin,
            'location_country'   => $locationCountry,
            'specs'              => $specs,
            'specs_json'         => json_encode($specs, JSON_UNESCAPED_UNICODE),
            'features'           => $features,
            'features_json'      => json_encode($features, JSON_UNESCAPED_UNICODE),
            'description'        => $description,
        ];
    }
}

if (!function_exists('intake_split_multiple_listings')) {
    /**
     * Splits a text message containing multiple vehicle/product listings into individual chunks.
     * Returns an array with 1 or more text listings. Never splits a single vehicle listing.
     */
    function intake_split_multiple_listings(string $text): array
    {
        $t = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($t === '') {
            return [];
        }

        $t = str_replace('：', ':', $t);

        // Check frequency of distinct Brand/Make lines and Vehicle Specifications headers
        $brandCount = preg_match_all('/^\s*(?:Brand|Make|品牌)\s*:/mi', $t);
        $specHeaderCount = preg_match_all('/^\s*(?:#*\s*Vehicle\s+Specifications?|#*\s*Specifications?)\b/mi', $t);

        // If there is at most 1 Brand line and at most 1 Specifications header, it is ALWAYS a single vehicle listing!
        if ($brandCount <= 1 && $specHeaderCount <= 1) {
            return [$t];
        }

        // 1. Try splitting on explicit divider lines (e.g. ---, ===, ***, ___)
        $dividerParts = preg_split('/(?:\n\s*[-=_*]{3,}\s*\n)/u', $t);
        if (is_array($dividerParts) && count($dividerParts) >= 2) {
            $validDividerChunks = [];
            foreach ($dividerParts as $dp) {
                $dp = trim($dp);
                if ($dp !== '' && (preg_match('/^\s*(?:Brand|Make|品牌)\s*:/mi', $dp) || preg_match('/^\s*(?:#*\s*Vehicle\s+Specifications?)\b/mi', $dp)) && preg_match('/(?:price|fob)\s*[:：]?\s*(?:us\$|usd|\$)?\s*[\d,]+/i', $dp)) {
                    $validDividerChunks[] = $dp;
                }
            }
            if (count($validDividerChunks) >= 2 && count($validDividerChunks) === count($dividerParts)) {
                return $validDividerChunks;
            }
        }

        // 2. Line-by-line boundary scanner
        $lines = explode("\n", $t);
        $chunks = [];
        $currentChunk = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $cleanTrimmed = trim($trimmed, "#* \t-");
            $lowerClean = mb_strtolower($cleanTrimmed);

            $isNewListingStart = false;

            if (!empty($currentChunk)) {
                $joined = implode("\n", $currentChunk);
                $hasBrand = preg_match('/^\s*(?:Brand|Make|品牌)\s*:/mi', $joined);
                $hasPrice = preg_match('/(?:price|fob)\s*[:：]?\s*(?:us\$|usd|\$)?\s*[\d,]+/i', $joined);

                // Only consider boundary if the current chunk ALREADY has both a Brand and a Price
                if ($hasBrand && $hasPrice) {
                    // Trigger 1: A line starting with 4-digit year + vehicle name
                    if (preg_match('/^(?:20[12]\d)\s+[A-Za-z0-9]/', $trimmed)) {
                        $isNewListingStart = true;
                    }
                    // Trigger 2: A new "Vehicle Specifications" header
                    elseif (str_starts_with($lowerClean, 'vehicle specification') || $lowerClean === 'specifications') {
                        $isNewListingStart = true;
                    }
                    // Trigger 3: A new Brand/Make line if currentChunk already had one
                    elseif (preg_match('/^(?:brand|make|品牌)\s*:/i', $cleanTrimmed)) {
                        $isNewListingStart = true;
                    }
                }
            }

            if ($isNewListingStart) {
                $chunks[] = implode("\n", $currentChunk);
                $currentChunk = [];
            }
            $currentChunk[] = $line;
        }

        if (!empty($currentChunk)) {
            $chunks[] = implode("\n", $currentChunk);
        }

        // Verify all chunks are genuine standalone listings with their own Brand/Make AND Price
        if (count($chunks) >= 2) {
            $allValid = true;
            foreach ($chunks as $c) {
                $hasBrand = preg_match('/^\s*(?:Brand|Make|品牌)\s*:/mi', $c);
                $hasPrice = preg_match('/(?:price|fob)\s*[:：]?\s*(?:us\$|usd|\$)?\s*[\d,]+/i', $c);
                if (!$hasBrand || !$hasPrice) {
                    $allValid = false;
                    break;
                }
            }
            if ($allValid) {
                return $chunks;
            }
        }

        return [$t];
    }
}

if (!function_exists('intake_listings_to_csv')) {
    /**
     * Format multiple parsed listings into a standard CSV string matching ProductCsvImporter::HEADERS
     */
    function intake_listings_to_csv(array $parsedList, string $videoUrl = ''): string
    {
        $headers = [
            'name', 'sku', 'price', 'currency', 'stock', 'category', 'sub_category', 'brand',
            'description', 'tags', 'listing_type', 'condition', 'visibility', 'moq',
            'wholesale_price', 'location_country', 'features', 'specs', 'video_url',
            'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5',
            'image_url_6', 'image_url_7', 'image_url_8', 'image_url_9', 'image_url_10'
        ];

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers);

        foreach ($parsedList as $parsed) {
            $pipeFeatures = implode(' | ', $parsed['features'] ?? []);

            $specPairs = [];
            foreach (($parsed['specs'] ?? []) as $k => $v) {
                $specPairs[] = $v !== '' ? "{$k}: {$v}" : $k;
            }
            $pipeSpecs = implode(' | ', $specPairs);

            $tags = implode(', ', array_filter([
                $parsed['brand'] ?? '',
                $parsed['model'] ?? '',
                $parsed['year'] ?? '',
                $parsed['sub_category'] ?? '',
                'china export',
                'preorder'
            ]));

            $row = [
                $parsed['name'] ?? '',
                $parsed['sku'] ?? '',
                (string)($parsed['price'] ?? '0.00'),
                $parsed['currency'] ?? 'USD',
                '1', // stock
                $parsed['category'] ?? 'Vehicles',
                $parsed['sub_category'] ?? 'Cars',
                $parsed['brand'] ?? '',
                $parsed['description'] ?? '',
                $tags,
                'export', // listing_type
                $parsed['condition_type'] ?? 'used',
                'public', // visibility
                '1', // moq
                '', // wholesale_price
                $parsed['location_country'] ?? 'CN',
                $pipeFeatures,
                $pipeSpecs,
                $videoUrl,
                '', '', '', '', '', '', '', '', '', ''
            ];
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (string)$csv;
    }
}

if (!function_exists('intake_listing_to_csv')) {
    /**
     * Format single parsed listing into CSV string
     */
    function intake_listing_to_csv(array $parsed, string $videoUrl = ''): string
    {
        return intake_listings_to_csv([$parsed], $videoUrl);
    }
}
