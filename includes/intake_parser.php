<?php
/**
 * Telegram intake - structured vehicle & product text parser.
 *
 * Automatically parses mobile vehicle / product listings into structured catalog
 * entries, extracting specifications, key features, pricing, condition, export terms,
 * and narrative descriptions.
 */

declare(strict_types=1);

/**
 * Checks whether an incoming text message or photo caption is a structured product/vehicle listing.
 */
if (!function_exists('intake_is_listing_text')) {
    function intake_is_listing_text(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if (mb_strlen($t) < 40) {
            return false;
        }

        $indicators = [
            'vehicle specification',
            'vehicle specifications',
            'specifications',
            'key features',
            'brand:',
            'model:',
            'fob price',
            'engine:',
            'transmission:',
            'mileage:',
            'seating capacity',
        ];

        $hits = 0;
        foreach ($indicators as $ind) {
            if (str_contains($t, $ind)) {
                $hits++;
            }
        }

        // If 2 or more strong indicators match, treat as listing text
        return $hits >= 2;
    }
}

if (!function_exists('intake_parse_listing_text')) {
    function intake_parse_listing_text(string $raw): array
    {
    $lines = array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", $raw)));
    while (!empty($lines) && $lines[0] === '') {
        array_shift($lines);
    }

    if (empty($lines)) {
        return [];
    }

    $title = $lines[0];
    $section = 'header';
    $specs = [];
    $features = [];
    $overviewLines = [];
    $conditionLines = [];
    $shippingLines = [];
    $footerLines = [];

    for ($i = 1; $i < count($lines); $i++) {
        $line = $lines[$i];
        if ($line === '') {
            continue;
        }

        $lower = mb_strtolower($line);
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

        switch ($section) {
            case 'header':
                $overviewLines[] = $line;
                break;
            case 'specs':
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $k = trim(trim($k), "* \t-");
                    $v = trim(trim($v), "* \t");
                    if ($k !== '') {
                        $specs[$k] = $v;
                    }
                } elseif (!empty($cleanLine)) {
                    $specs[$cleanLine] = '';
                }
                break;
            case 'features':
                $f = trim((string)preg_replace('/^(\s*[-•*]\s*|\s*\d+[\.\)]\s+)/u', '', $line));
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

    // Brand, Model, Year
    $brand = $specs['Brand'] ?? $specs['brand'] ?? $specs['Make'] ?? $specs['make'] ?? '';
    $model = $specs['Model'] ?? $specs['model'] ?? '';
    $year  = $specs['Year'] ?? $specs['year'] ?? '';

    // If Brand or Year missing from specs, attempt regex on title
    if ($year === '' && preg_match('/\b(20[12]\d)\b/', $title, $ym)) {
        $year = $ym[1];
    }
    if ($brand === '') {
        $knownBrands = ['Jetour', 'Toyota', 'Honda', 'Mercedes-Benz', 'Mercedes', 'BMW', 'Audi', 'Hyundai', 'Kia', 'Nissan', 'Geely', 'BYD', 'Changan', 'Haval', 'Chery', 'GAC', 'Great Wall', 'Volkswagen', 'VW', 'Ford', 'Lexus', 'Mazda', 'Tesla', 'Peugeot', 'Land Rover'];
        foreach ($knownBrands as $kb) {
            if (stripos($title, $kb) !== false) {
                $brand = $kb;
                break;
            }
        }
    }

    // Price extraction
    $priceRaw = $specs['Price'] ?? $specs['price'] ?? $specs['FOB Price'] ?? '';
    if ($priceRaw === '') {
        foreach ($shippingLines as $sl) {
            if (stripos($sl, 'price') !== false) {
                $priceRaw = $sl;
                break;
            }
        }
    }

    $price = 0.0;
    $currency = 'USD';
    if (preg_match('/(?:US\$|USD|\$)\s*([\d,]+(?:\.\d+)?)/i', $priceRaw, $m)) {
        $price = (float)str_replace(',', '', $m[1]);
        $currency = 'USD';
    } elseif (preg_match('/(?:GH[₵S]|GHS|₵)\s*([\d,]+(?:\.\d+)?)/i', $priceRaw, $m)) {
        $price = (float)str_replace(',', '', $m[1]);
        $currency = 'GHS';
    } elseif (preg_match('/([\d,]+(?:\.\d+)?)/', $priceRaw, $m)) {
        $price = (float)str_replace(',', '', $m[1]);
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

if (!function_exists('intake_listing_to_csv')) {
    /**
     * Format parsed listing into a standard CSV string matching ProductCsvImporter::HEADERS
     * plus extra attributes (video_url, etc.)
     */
    function intake_listing_to_csv(array $parsed, string $videoUrl = ''): string
    {
    $headers = [
        'name', 'sku', 'price', 'currency', 'stock', 'category', 'sub_category', 'brand',
        'description', 'tags', 'listing_type', 'condition', 'visibility', 'moq',
        'wholesale_price', 'location_country', 'features', 'specs', 'video_url',
        'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5',
        'image_url_6', 'image_url_7', 'image_url_8', 'image_url_9', 'image_url_10'
    ];

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
        '', '', '', '', '', '', '', '', '', '' // image placeholders
    ];

    $stream = fopen('php://memory', 'r+');
    fwrite($stream, "\xEF\xBB\xBF");
    fputcsv($stream, $headers);
    fputcsv($stream, $row);
    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    return (string)$csv;
}
}
