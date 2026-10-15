<?php
// core/ProductCsvImporter.php

class ProductCsvImporter {
    public const REQUIRED_HEADERS = ['name', 'price'];
    public const MAX_IMAGES = 10;
    public const IMAGE_HEADERS = [
        'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5',
        'image_url_6', 'image_url_7', 'image_url_8', 'image_url_9', 'image_url_10',
    ];
    public const HEADERS = [
        'name', 'sku', 'price', 'currency', 'stock', 'category', 'sub_category', 'brand', 'description', 'tags',
        'listing_type', 'condition', 'visibility', 'moq', 'wholesale_price', 'location_country', 'features', 'specs',
        'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5',
        'image_url_6', 'image_url_7', 'image_url_8', 'image_url_9', 'image_url_10',
    ];

    public static function sendTemplate(PDO $db, string $format = 'excel'): void {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        if ($format === 'csv') {
            self::sendCsvTemplate();
        } else {
            self::sendExcelTemplate($db);
        }
    }

    public static function sendCsvTemplate(): void {
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="avazonia-products-template-v3.csv"');
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // Keep the CSV UTF-8 clean in Excel and Sheets.
        fputcsv($out, self::HEADERS, ',', '"', '', "\r\n");
        fputcsv($out, [
            'Example Product Name', 'PROD-SKU-001', '99.99', 'GHS', '10', 'Electronics', 'Audio & Headphones', 'Apple',
            'Add a clear product description.', 'example, new', 'retail', 'new', 'public', '', '', 'GH',
            'Fast Charging | Waterproof | 2-Year Warranty', 'Battery: 5000mAh | RAM: 12GB | Screen: 6.7" AMOLED',
            'https://example.com/images/product-main.jpg', '', '', '', '', '', '', '', '', '',
        ], ',', '"', '', "\r\n");
        fclose($out);
        exit;
    }

    public static function sendExcelTemplate(PDO $db): void {
        $lookups = self::getLookupOptions($db);
        $mainCats = $lookups['main_categories'];
        $subCats = $lookups['sub_categories'];
        $allCats = array_unique(array_merge($mainCats, $subCats));
        sort($allCats);

        $brands = $lookups['brands'];
        $currencies = ['GHS', 'USD'];
        $listingTypes = ['retail', 'wholesale', 'rfq', 'export'];
        $conditions = ['new', 'used'];
        $visibilities = ['public', 'b2b_only', 'retail_only'];

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            self::sendCsvTemplate();
            return;
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $catEndRow = count($allCats) + 1;
        $subEndRow = count($subCats) + 1;
        $brandEndRow = count($brands) + 1;

        $countries = ['GH', 'CN', 'OTHER'];
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Products" sheetId="1" r:id="rId1"/>
    <sheet name="Lookups" sheetId="2" r:id="rId2"/>
  </sheets>
  <definedNames>
    <definedName name="CategoriesList">Lookups!$A$2:$A$' . $catEndRow . '</definedName>
    <definedName name="SubcategoriesList">Lookups!$B$2:$B$' . $subEndRow . '</definedName>
    <definedName name="BrandsList">Lookups!$C$2:$C$' . $brandEndRow . '</definedName>
    <definedName name="CurrenciesList">Lookups!$D$2:$D$3</definedName>
    <definedName name="ListingTypesList">Lookups!$E$2:$E$5</definedName>
    <definedName name="ConditionsList">Lookups!$F$2:$F$3</definedName>
    <definedName name="VisibilitiesList">Lookups!$G$2:$G$4</definedName>
    <definedName name="CountriesList">Lookups!$H$2:$H$4</definedName>
  </definedNames>
</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Segoe UI"/></font></fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="1"><border/></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>');

        // Sheet 2: Lookups
        $maxLookupRows = max(count($allCats), count($subCats), count($brands), 5);
        $s2Rows = [];
        $s2Rows[] = '<row r="1">
          <c r="A1" t="inlineStr"><is><t>Categories</t></is></c>
          <c r="B1" t="inlineStr"><is><t>Subcategories</t></is></c>
          <c r="C1" t="inlineStr"><is><t>Brands</t></is></c>
          <c r="D1" t="inlineStr"><is><t>Currencies</t></is></c>
          <c r="E1" t="inlineStr"><is><t>ListingTypes</t></is></c>
          <c r="F1" t="inlineStr"><is><t>Conditions</t></is></c>
          <c r="G1" t="inlineStr"><is><t>Visibilities</t></is></c>
          <c r="H1" t="inlineStr"><is><t>Countries</t></is></c>
        </row>';

        for ($i = 0; $i < $maxLookupRows; $i++) {
            $rNum = $i + 2;
            $catVal = isset($allCats[$i]) ? htmlspecialchars($allCats[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $subVal = isset($subCats[$i]) ? htmlspecialchars($subCats[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $brandVal = isset($brands[$i]) ? htmlspecialchars($brands[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $currVal = isset($currencies[$i]) ? htmlspecialchars($currencies[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $listVal = isset($listingTypes[$i]) ? htmlspecialchars($listingTypes[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $condVal = isset($conditions[$i]) ? htmlspecialchars($conditions[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $visVal = isset($visibilities[$i]) ? htmlspecialchars($visibilities[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';
            $cntVal = isset($countries[$i]) ? htmlspecialchars($countries[$i], ENT_QUOTES | ENT_XML1, 'UTF-8') : '';

            $cells = '';
            if ($catVal !== '') $cells .= '<c r="A' . $rNum . '" t="inlineStr"><is><t>' . $catVal . '</t></is></c>';
            if ($subVal !== '') $cells .= '<c r="B' . $rNum . '" t="inlineStr"><is><t>' . $subVal . '</t></is></c>';
            if ($brandVal !== '') $cells .= '<c r="C' . $rNum . '" t="inlineStr"><is><t>' . $brandVal . '</t></is></c>';
            if ($currVal !== '') $cells .= '<c r="D' . $rNum . '" t="inlineStr"><is><t>' . $currVal . '</t></is></c>';
            if ($listVal !== '') $cells .= '<c r="E' . $rNum . '" t="inlineStr"><is><t>' . $listVal . '</t></is></c>';
            if ($condVal !== '') $cells .= '<c r="F' . $rNum . '" t="inlineStr"><is><t>' . $condVal . '</t></is></c>';
            if ($visVal !== '') $cells .= '<c r="G' . $rNum . '" t="inlineStr"><is><t>' . $visVal . '</t></is></c>';
            if ($cntVal !== '') $cells .= '<c r="H' . $rNum . '" t="inlineStr"><is><t>' . $cntVal . '</t></is></c>';

            if ($cells !== '') {
                $s2Rows[] = '<row r="' . $rNum . '">' . $cells . '</row>';
            }
        }

        $sheet2Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>' . implode('', $s2Rows) . '</sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);

        // Sheet 1: Products
        $sheet1Xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <dimension ref="A1:AB2"/>
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>name</t></is></c>
      <c r="B1" t="inlineStr"><is><t>sku</t></is></c>
      <c r="C1" t="inlineStr"><is><t>price</t></is></c>
      <c r="D1" t="inlineStr"><is><t>currency</t></is></c>
      <c r="E1" t="inlineStr"><is><t>stock</t></is></c>
      <c r="F1" t="inlineStr"><is><t>category</t></is></c>
      <c r="G1" t="inlineStr"><is><t>sub_category</t></is></c>
      <c r="H1" t="inlineStr"><is><t>brand</t></is></c>
      <c r="I1" t="inlineStr"><is><t>description</t></is></c>
      <c r="J1" t="inlineStr"><is><t>tags</t></is></c>
      <c r="K1" t="inlineStr"><is><t>listing_type</t></is></c>
      <c r="L1" t="inlineStr"><is><t>condition</t></is></c>
      <c r="M1" t="inlineStr"><is><t>visibility</t></is></c>
      <c r="N1" t="inlineStr"><is><t>moq</t></is></c>
      <c r="O1" t="inlineStr"><is><t>wholesale_price</t></is></c>
      <c r="P1" t="inlineStr"><is><t>location_country</t></is></c>
      <c r="Q1" t="inlineStr"><is><t>features</t></is></c>
      <c r="R1" t="inlineStr"><is><t>specs</t></is></c>
      <c r="S1" t="inlineStr"><is><t>image_url_1</t></is></c>
      <c r="T1" t="inlineStr"><is><t>image_url_2</t></is></c>
      <c r="U1" t="inlineStr"><is><t>image_url_3</t></is></c>
      <c r="V1" t="inlineStr"><is><t>image_url_4</t></is></c>
      <c r="W1" t="inlineStr"><is><t>image_url_5</t></is></c>
      <c r="X1" t="inlineStr"><is><t>image_url_6</t></is></c>
      <c r="Y1" t="inlineStr"><is><t>image_url_7</t></is></c>
      <c r="Z1" t="inlineStr"><is><t>image_url_8</t></is></c>
      <c r="AA1" t="inlineStr"><is><t>image_url_9</t></is></c>
      <c r="AB1" t="inlineStr"><is><t>image_url_10</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Example Product Name</t></is></c>
      <c r="B2" t="inlineStr"><is><t>PROD-SKU-001</t></is></c>
      <c r="C2"><v>99.99</v></c>
      <c r="D2" t="inlineStr"><is><t>GHS</t></is></c>
      <c r="E2"><v>10</v></c>
      <c r="F2" t="inlineStr"><is><t>' . htmlspecialchars($allCats[0] ?? 'Electronics', ENT_QUOTES | ENT_XML1, 'UTF-8') . '</t></is></c>
      <c r="G2" t="inlineStr"><is><t>' . htmlspecialchars($subCats[0] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8') . '</t></is></c>
      <c r="H2" t="inlineStr"><is><t>' . htmlspecialchars($brands[0] ?? 'Generic', ENT_QUOTES | ENT_XML1, 'UTF-8') . '</t></is></c>
      <c r="I2" t="inlineStr"><is><t>Add a clear product description.</t></is></c>
      <c r="J2" t="inlineStr"><is><t>example, new</t></is></c>
      <c r="K2" t="inlineStr"><is><t>retail</t></is></c>
      <c r="L2" t="inlineStr"><is><t>new</t></is></c>
      <c r="M2" t="inlineStr"><is><t>public</t></is></c>
      <c r="N2"><v></v></c>
      <c r="O2"><v></v></c>
      <c r="P2" t="inlineStr"><is><t>GH</t></is></c>
      <c r="Q2" t="inlineStr"><is><t>Fast Charging | Waterproof | 2-Year Warranty</t></is></c>
      <c r="R2" t="inlineStr"><is><t>Battery: 5000mAh | RAM: 12GB | Screen: 6.7 inch AMOLED</t></is></c>
      <c r="S2" t="inlineStr"><is><t>https://example.com/images/product-main.jpg</t></is></c>
    </row>
  </sheetData>
  <dataValidations count="8">
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="D2:D1000">
      <formula1>CurrenciesList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="F2:F1000">
      <formula1>CategoriesList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="G2:G1000">
      <formula1>SubcategoriesList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="H2:H1000">
      <formula1>BrandsList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="K2:K1000">
      <formula1>ListingTypesList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="L2:L1000">
      <formula1>ConditionsList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="M2:M1000">
      <formula1>VisibilitiesList</formula1>
    </dataValidation>
    <dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="P2:P1000">
      <formula1>CountriesList</formula1>
    </dataValidation>
  </dataValidations>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
        $zip->close();

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="avazonia-products-template-v3.xlsx"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($tmpFile));
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0');
        readfile($tmpFile);
        @unlink($tmpFile);
        exit;
    }

    public static function getLookupOptions(PDO $db): array {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $categoryColumns = $db->query('PRAGMA table_info(categories)')->fetchAll(PDO::FETCH_COLUMN, 1);
            $categoryHasActive = in_array('is_active', $categoryColumns, true);
        } else {
            $categoryHasActive = true;
        }

        $cats = $db->query('SELECT id, name, parent_id FROM categories' . ($categoryHasActive ? ' WHERE is_active = 1' : '') . ' ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $mainCats = [];
        $subCats = [];
        $catMap = [];
        foreach ($cats as $c) {
            $catMap[strtolower(trim($c['name']))] = (int)$c['id'];
            if (empty($c['parent_id'])) {
                $mainCats[] = $c['name'];
            } else {
                $subCats[] = $c['name'];
            }
        }

        $brands = $db->query('SELECT id, name FROM brands ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
        $brandList = [];
        $brandMap = [];
        foreach ($brands as $b) {
            $brandList[] = $b['name'];
            $brandMap[strtolower(trim($b['name']))] = (int)$b['id'];
        }

        return [
            'main_categories' => array_values(array_unique($mainCats)),
            'sub_categories' => array_values(array_unique($subCats)),
            'category_map' => $catMap,
            'brands' => array_values(array_unique($brandList)),
            'brand_map' => $brandMap,
            'currencies' => ['GHS', 'USD'],
            'listing_types' => ['retail', 'wholesale', 'rfq', 'export'],
            'conditions' => ['new', 'used'],
            'visibilities' => ['public', 'b2b_only', 'retail_only'],
        ];
    }

    /** Parse raw rows from either .csv or .xlsx */
    public static function parseFileRows(string $filePath, string $originalFilename = ''): array {
        $ext = strtolower(pathinfo($originalFilename !== '' ? $originalFilename : $filePath, PATHINFO_EXTENSION));
        if ($ext === 'xlsx' || self::isZipFile($filePath)) {
            return self::parseXlsx($filePath);
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) throw new RuntimeException('The file could not be read.');
        $rows = [];
        while (($cells = fgetcsv($handle)) !== false) {
            $rows[] = $cells;
        }
        fclose($handle);
        return $rows;
    }

    private static function isZipFile(string $filePath): bool {
        $f = @fopen($filePath, 'rb');
        if (!$f) return false;
        $header = fread($f, 4);
        fclose($f);
        return $header === "PK\x03\x04";
    }

    private static function parseXlsx(string $filePath): array {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Could not open uploaded XLSX file.");
        }

        $sharedStrings = [];
        $ssContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssContent !== false) {
            $xml = @simplexml_load_string($ssContent);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) $text .= (string)$r->t;
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetContent === false) {
            $zip->close();
            throw new RuntimeException("The uploaded XLSX spreadsheet contains no readable worksheet data.");
        }

        $xml = @simplexml_load_string($sheetContent);
        $zip->close();

        if (!$xml || !isset($xml->sheetData)) {
            throw new RuntimeException("The XLSX worksheet contains invalid XML data.");
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $rAttr = (string)$c['r'];
                preg_match('/^([A-Z]+)(\d+)$/', $rAttr, $matches);
                $colStr = $matches[1] ?? 'A';

                $colIndex = 0;
                $len = strlen($colStr);
                for ($i = 0; $i < $len; $i++) {
                    $colIndex = $colIndex * 26 + (ord($colStr[$i]) - 64);
                }
                $colIndex--;

                $type = (string)$c['t'];
                $val = '';
                if ($type === 's') {
                    $idx = (int)$c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)$c->is->t;
                } else {
                    $val = (string)$c->v;
                }
                $rowData[$colIndex] = trim($val);
            }

            if (!empty($rowData)) {
                $maxIndex = max(array_keys($rowData));
                $fullRow = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $fullRow[$i] = $rowData[$i] ?? '';
                }
                $rows[] = $fullRow;
            }
        }
        return $rows;
    }

    /** Check an image reference is either an http(s) image URL or a local uploads path. */
    public static function isValidImageUrl(string $u): bool {
        $u = trim($u);
        if ($u === '') return false;
        // Local uploads paths (as produced by the file-picker endpoints and the HOCO converter).
        if (preg_match('#^/?public/uploads/products/[A-Za-z0-9._/-]+$#', $u)) {
            return (bool)preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $u);
        }
        if (preg_match('#^uploads/products/[A-Za-z0-9._/-]+$#', $u)) {
            return (bool)preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $u);
        }
        if (!filter_var($u, FILTER_VALIDATE_URL)) return false;
        $scheme = strtolower((string)parse_url($u, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) return false;
        $path = (string)parse_url($u, PHP_URL_PATH);
        if ($path === '') return false;
        return (bool)preg_match('/\.(jpe?g|png|webp|gif)(\?.*)?$/i', $path . (strpos($u, '?') !== false ? '?' . parse_url($u, PHP_URL_QUERY) : ''));
    }

    /** Normalize uploads paths to the no-leading-slash form used by single-product flows. */
    public static function normalizeImageUrl(string $u): string {
        $u = trim($u);
        if (str_starts_with($u, '/public/uploads/')) return ltrim($u, '/');
        return $u;
    }

    /** Render helper: stored paths are relative (public/...), so prefix for <img src>. */
    public static function imageSrc(string $u): string {
        $u = trim($u);
        if ($u === '' || preg_match('#^https?://#i', $u)) return $u;
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        return $base . '/' . ltrim($u, '/');
    }

    /** Validate uploaded CSV/XLSX file and return preview array along with lookup options. */
    public static function preview(string $filePath, PDO $db, string $originalFilename = ''): array {
        $rawRows = self::parseFileRows($filePath, $originalFilename);
        if (!$rawRows) {
            throw new RuntimeException('The file is empty or contains no data rows.');
        }

        $headers = array_shift($rawRows);
        if (!$headers) {
            throw new RuntimeException('The file is missing a header row.');
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string)$header));
            if ($key !== '') {
                // Support both category and sub_category aliases
                if ($key === 'sub_category' || $key === 'subcategory') $key = 'sub_category';
                if ($key === 'origin_country' || $key === 'country' || $key === 'origin' || $key === 'location' || $key === 'location_country') $key = 'location_country';
                if ($key === 'key_features' || $key === 'feature_list' || $key === 'feature') $key = 'features';
                if ($key === 'specifications' || $key === 'spec_list' || $key === 'spec') $key = 'specs';
                // Image column aliases: single-image names map to slot 1, multi names map to shared 'images' bucket
                if (in_array($key, ['image', 'image_url', 'picture', 'photo', 'photo_url', 'image_link'], true)) $key = 'image_url_1';
                if (in_array($key, ['images', 'image_urls', 'photos', 'gallery', 'pictures'], true)) $key = 'images';
                if (preg_match('/^image[_-]?url[_-]?(\d{1,2})$/', $key, $m)) {
                    $slot = (int)$m[1];
                    if ($slot >= 1 && $slot <= self::MAX_IMAGES) $key = 'image_url_' . $slot;
                }
                $headerMap[$key] = $index;
            }
        }

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerMap)));
        if ($missing) {
            throw new RuntimeException('Missing required columns: ' . implode(', ', $missing) . '. Download the template to get the correct headers.');
        }

        $lookups = self::getLookupOptions($db);
        $categories = $lookups['category_map'];
        $brands = $lookups['brand_map'];

        $preview = [];
        $line = 1;
        foreach ($rawRows as $cells) {
            $line++;
            if (count($preview) >= 5000) {
                throw new RuntimeException('File can contain at most 5,000 product rows.');
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

            // Category & Subcategory matching
            $catInput = $row['sub_category'] !== '' ? $row['sub_category'] : $row['category'];
            $categoryId = null;
            if ($catInput !== '') {
                $categoryId = $categories[strtolower($catInput)] ?? null;
                if ($categoryId === null) $errors[] = 'Category/Subcategory "' . htmlspecialchars($catInput) . '" does not match an active catalogue category.';
            }

            $brandId = null;
            if ($row['brand'] !== '') {
                $brandId = $brands[strtolower($row['brand'])] ?? null;
                if ($brandId === null) $errors[] = 'Brand "' . htmlspecialchars($row['brand']) . '" does not match a catalogue brand.';
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

            $countryRaw = strtolower(trim((string)($row['location_country'] ?? '')));
            $locationCountry = 'GH';
            if (str_contains($countryRaw, 'china') || $countryRaw === 'cn') {
                $locationCountry = 'CN';
            } elseif (str_contains($countryRaw, 'ghana') || $countryRaw === 'gh') {
                $locationCountry = 'GH';
            } elseif ($countryRaw !== '') {
                $locationCountry = strtoupper(substr($countryRaw, 0, 5));
            }

            $featRaw = trim((string)($row['features'] ?? ''));
            $featuresJson = null;
            if ($featRaw !== '') {
                $featArr = array_values(array_filter(array_map('trim', preg_split('/[\r\n|;]+/', $featRaw))));
                if (!empty($featArr)) $featuresJson = json_encode($featArr);
            }

            $specsRaw = trim((string)($row['specs'] ?? ''));
            $specsJson = null;
            if ($specsRaw !== '') {
                $specsArr = [];
                foreach (preg_split('/[\r\n|;]+/', $specsRaw) as $specLine) {
                    if (strpos($specLine, ':') !== false) {
                        list($k, $v) = explode(':', $specLine, 2);
                        if (trim($k) !== '') {
                            $specsArr[trim($k)] = trim($v);
                        }
                    }
                }
                if (!empty($specsArr)) $specsJson = json_encode($specsArr);
            }

            // Collect up to MAX_IMAGES image URLs from image_url_1..N plus the shared 'images' bucket.
            $rawImageUrls = [];
            foreach (self::IMAGE_HEADERS as $imgKey) {
                $u = trim((string)($row[$imgKey] ?? ''));
                if ($u !== '') $rawImageUrls[] = $u;
            }
            if (isset($headerMap['images'])) {
                $bucket = trim((string)($cells[$headerMap['images']] ?? ''));
                if ($bucket !== '') {
                    foreach (preg_split('/[\r\n|,;]+/', $bucket) as $part) {
                        $part = trim($part);
                        if ($part !== '') $rawImageUrls[] = $part;
                    }
                }
            }
            $imageUrls = [];
            $seenLower = [];
            foreach ($rawImageUrls as $idx => $u) {
                if (count($imageUrls) >= self::MAX_IMAGES) break;
                $label = 'Image ' . ($idx + 1);
                if (strlen($u) > 500) { $errors[] = $label . ' URL is too long (max 500 characters).'; continue; }
                if (!self::isValidImageUrl($u)) { $errors[] = $label . ' is not a valid http(s) image URL (JPG, PNG, WebP, GIF) or uploads path.'; continue; }
                $norm = self::normalizeImageUrl($u);
                $lower = strtolower($norm);
                if (isset($seenLower[$lower])) continue;
                $seenLower[$lower] = true;
                $imageUrls[] = $norm;
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
                'location_country' => $locationCountry,
                'available_in_ghana' => ($locationCountry === 'GH' ? 1 : 0),
                'features' => $featuresJson,
                'specs' => $specsJson,
                'image_urls' => $imageUrls,
            ];
            $preview[] = ['line' => $line, 'row' => $row, 'values' => $values, 'errors' => $errors];
        }

        if (!$preview) throw new RuntimeException('The uploaded file contains no product rows.');

        return [
            'preview' => $preview,
            'lookups' => $lookups,
        ];
    }

    /**
     * Supplier-aware preview: auto-detects HOCO quotations (embedded images,
     * tier pricing, color continuation rows) and groups them 1 product + variants.
     * Falls back to the standard v3 preview for template files.
     */
    public static function previewSupplier(string $filePath, PDO $db, string $originalFilename = ''): array {
        $rawRows = self::parseFileRows($filePath, $originalFilename);
        if (!$rawRows) throw new RuntimeException('The file is empty or contains no data rows.');
        $headers = $rawRows[0] ?? [];
        require_once __DIR__ . '/Supplier/HocoParser.php';
        if (HocoParser::isHocoSheet(is_array($headers) ? $headers : [])) {
            require_once __DIR__ . '/Supplier/XlsxImageExtractor.php';
            $destDir = __DIR__ . '/../public/uploads/products';
            $imagesByRow = XlsxImageExtractor::extract($filePath, $destDir, 'hoco');
            try { require_once __DIR__ . '/Watermark.php'; Watermark::applyToPaths($imagesByRow ? array_merge(...array_values($imagesByRow)) : []); } catch (Throwable $e) {}
            $groups = HocoParser::parseGroups($rawRows);
            if (!$groups) throw new RuntimeException('No HOCO product groups found. The sheet layout may have changed.');
            $result = HocoParser::toPreviewItems($groups, $imagesByRow, $db);
            if (empty($result['preview'])) throw new RuntimeException('The HOCO file contains no importable product rows.');
            return $result;
        }
        return self::preview($filePath, $db, $originalFilename);
    }

    /** Ensure a brand exists (used for HOCO auto-create); returns brand id or null. */
    public static function ensureBrand(PDO $db, string $name): ?int {
        $name = trim($name);
        if ($name === '') return null;
        $stmt = $db->prepare('SELECT id FROM brands WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if ($slug === '') $slug = 'brand-' . bin2hex(random_bytes(3));
        try {
            $db->prepare('INSERT INTO brands (name, slug, is_active) VALUES (?, ?, 1)')->execute([$name, $slug]);
            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            // Slug collision — retry with suffix.
            try {
                $db->prepare('INSERT INTO brands (name, slug, is_active) VALUES (?, ?, 1)')->execute([$name, $slug . '-' . bin2hex(random_bytes(3))]);
                return (int)$db->lastInsertId();
            } catch (Throwable $e2) { return null; }
        }
    }

    /** Persist 1-product+N-variants color options into the variants table. */
    private static function linkVariants(PDO $db, int $productId, array $item, bool $isUpdate): void {
        $variants = array_values(array_filter(
            (array)($item['values']['variants'] ?? []),
            static fn($v) => is_array($v) && (trim((string)($v['color'] ?? '')) !== '' || trim((string)($v['sku'] ?? '')) !== '')
        ));
        if (!$variants) return;
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $cols = $db->query('PRAGMA table_info(variants)')->fetchAll(PDO::FETCH_COLUMN, 1);
            } else {
                $cols = $db->query('SHOW COLUMNS FROM variants')->fetchAll(PDO::FETCH_COLUMN, 0);
            }
        } catch (Throwable $e) { return; }
        if (!in_array('product_id', (array)$cols, true)) return;
        $hasColor = in_array('color', (array)$cols, true);
        $hasSku = in_array('sku', (array)$cols, true);
        $hasStock = in_array('stock_qty', (array)$cols, true);

        $haveSkus = [];
        if ($isUpdate && $hasSku) {
            try {
                $st = $db->prepare('SELECT sku FROM variants WHERE product_id = ?');
                $st->execute([$productId]);
                foreach ($st->fetchAll(PDO::FETCH_COLUMN, 0) as $s) {
                    if ($s !== null && $s !== '') $haveSkus[strtolower(trim((string)$s))] = true;
                }
            } catch (Throwable $e) {}
        }
        foreach ($variants as $v) {
            $color = mb_substr(trim((string)($v['color'] ?? '')), 0, 80);
            $sku = mb_substr(trim((string)($v['sku'] ?? '')), 0, 80);
            $stock = isset($v['stock_qty']) && is_numeric($v['stock_qty']) ? max(0, (int)$v['stock_qty']) : (int)($item['values']['stock_qty'] ?? 0);
            if ($sku !== '' && isset($haveSkus[strtolower($sku)])) continue;
            // Guarantee global UNIQUE(sku): suffix on collision.
            if ($sku !== '' && $hasSku) {
                $base = $sku;
                $n = 1;
                while (true) {
                    try {
                        $chk = $db->prepare('SELECT id FROM variants WHERE sku = ? LIMIT 1');
                        $chk->execute([$sku]);
                        if (!$chk->fetchColumn()) break;
                        // On upsert, allow reusing a SKU that already belongs to this product.
                        if ($isUpdate && isset($haveSkus[strtolower($sku)])) break 2;
                    } catch (Throwable $e) { break; }
                    $n++;
                    $sku = mb_substr($base . '-' . $n, 0, 80);
                    if ($n > 50) break;
                }
            }
            $insCols = ['product_id'];
            $insVals = [$productId];
            if ($hasColor) { $insCols[] = 'color'; $insVals[] = ($color !== '' ? $color : null); }
            if ($hasSku) { $insCols[] = 'sku'; $insVals[] = ($sku !== '' ? $sku : null); }
            if ($hasStock) { $insCols[] = 'stock_qty'; $insVals[] = $stock; }
            try {
                $db->prepare('INSERT INTO variants (' . implode(',', $insCols) . ') VALUES (' . implode(',', array_fill(0, count($insCols), '?')) . ')')->execute($insVals);
                if ($sku !== '') $haveSkus[strtolower($sku)] = true;
            } catch (Throwable $e) {
                // One bad variant must not fail the product: retry without SKU on UNIQUE clash.
                if ($hasSku && $sku !== '') {
                    try {
                        $insCols2 = ['product_id'];
                        $insVals2 = [$productId];
                        if ($hasColor) { $insCols2[] = 'color'; $insVals2[] = ($color !== '' ? $color : null); }
                        if ($hasStock) { $insCols2[] = 'stock_qty'; $insVals2[] = $stock; }
                        $db->prepare('INSERT INTO variants (' . implode(',', $insCols2) . ') VALUES (' . implode(',', array_fill(0, count($insCols2), '?')) . ')')->execute($insVals2);
                    } catch (Throwable $e2) {}
                }
            }
        }
    }

    /** Process import in chunks or full batch, supporting insert vs upsert mode and dynamic cell updates. */
    public static function import(PDO $db, array $preview, ?int $sellerId, ?int $storeId, string $status = 'pending_review', string $mode = 'insert', int $offset = 0, int $limit = 0, array $overrides = []): array {
        $columns = self::productColumns($db);
        $required = ['name', 'slug', 'price_ghs', 'currency', 'stock_qty', 'is_active'];
        if (count(array_intersect($required, $columns)) !== count($required)) {
            throw new RuntimeException('The products table is missing required columns. Please run the marketplace migration.');
        }
        if ($sellerId !== null && !in_array('seller_id', $columns, true)) {
            throw new RuntimeException('Seller product imports require the marketplace migration to add seller ownership fields.');
        }

        // Apply overrides if passed from the browser preview grid
        if ($overrides) {
            $lookups = self::getLookupOptions($db);
            $catMap = $lookups['category_map'];
            $brandMap = $lookups['brand_map'];

            foreach ($preview as &$item) {
                $line = (string)$item['line'];
                if (isset($overrides[$line])) {
                    $ov = $overrides[$line];
                    if (isset($ov['category']) && trim($ov['category']) !== '') {
                        $catKey = strtolower(trim($ov['category']));
                        $item['values']['category_id'] = $catMap[$catKey] ?? $item['values']['category_id'];
                        $item['row']['category'] = $ov['category'];
                    }
                    if (isset($ov['brand']) && trim($ov['brand']) !== '') {
                        $brandKey = strtolower(trim($ov['brand']));
                        $item['values']['brand_id'] = $brandMap[$brandKey] ?? $item['values']['brand_id'];
                        $item['row']['brand'] = $ov['brand'];
                    }
                    if (isset($ov['currency'])) {
                        $item['values']['currency'] = $ov['currency'];
                        if ($ov['currency'] === 'GHS') {
                            $item['values']['price_ghs'] = (float)($ov['price'] ?? $item['values']['price_ghs']);
                            $item['values']['price_usd'] = null;
                        } else {
                            $item['values']['price_usd'] = (float)($ov['price'] ?? $item['values']['price_ghs']);
                            $item['values']['price_ghs'] = 0;
                        }
                    }
                    if (isset($ov['video_url'])) {
                        $item['values']['video_url'] = trim($ov['video_url']);
                    }
                    // Clear category/brand errors if newly selected
                    $item['errors'] = array_values(array_filter($item['errors'], static function($err) {
                        return !str_contains($err, 'Category') && !str_contains($err, 'Brand');
                    }));
                }
            }
            unset($item);
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
                // HOCO flow: auto-create the brand so preview stays importable.
                if (empty($values['brand_id']) && trim((string)($item['row']['brand'] ?? '')) !== '') {
                    $made = self::ensureBrand($db, trim((string)$item['row']['brand']));
                    if ($made) $values['brand_id'] = $made;
                }
                $existingId = null;

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
                    $hasVideoCol = in_array('video_url', $columns, true);
                    $nowFn = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') ? "datetime('now')" : 'NOW()';
                    $updateSql = 'UPDATE products SET
                        price_ghs = ?, price_usd = ?, currency = ?, stock_qty = ?,
                        category_id = ?, brand_id = ?, description = ?, tags = ?,
                        listing_type = ?, condition_type = ?, visibility = ?, moq = ?,
                        wholesale_price_ghs = ?' . ($hasVideoCol ? ', video_url = ?' : '') . ', updated_at = ' . $nowFn;
                    $params = [
                        $values['price_ghs'], $values['price_usd'], $values['currency'], $values['stock_qty'],
                        $values['category_id'], $values['brand_id'], $values['description'], $values['tags'],
                        $values['listing_type'], $values['condition_type'], $values['visibility'], $values['moq'],
                        $values['wholesale_price_ghs'],
                    ];
                    if ($hasVideoCol) $params[] = $values['video_url'] ?? null;
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

                    // Link spreadsheet image URLs + file-picker uploads to the updated product (append new only)
                    self::linkImages($db, (int)$existingId, $item, $overrides, true);
                    self::linkVariants($db, (int)$existingId, ['values' => $values] + $item, true);
                } else {
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

                    // Link spreadsheet image URLs + file-picker uploads to the new product
                    self::linkImages($db, $id, $item, $overrides, false);
                    self::linkVariants($db, $id, ['values' => $values] + $item, false);
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                $outcomes[$item['line']] = ['success' => false, 'id' => null, 'action' => 'failed', 'error' => 'Database rejected row: ' . $e->getMessage()];
            }
        }
        Cache::flushTags(['products']);
        return $outcomes;
    }

    /** Merge spreadsheet image URLs with file-picker uploads and persist to product_images. */
    private static function linkImages(PDO $db, int $productId, array $item, array $overrides, bool $isUpdate): void {
        $line = (string)$item['line'];
        $final = [];
        foreach ((array)($item['values']['image_urls'] ?? []) as $u) {
            $u = self::normalizeImageUrl(trim((string)$u));
            if ($u !== '' && self::isValidImageUrl($u)) $final[] = $u;
        }
        // Grid removals (user removed a spreadsheet URL thumb in the preview UI).
        $removed = [];
        if (!empty($overrides[$line]['remove_image_urls']) && is_array($overrides[$line]['remove_image_urls'])) {
            foreach ($overrides[$line]['remove_image_urls'] as $r) {
                $removed[strtolower(self::normalizeImageUrl(trim((string)$r)))] = true;
            }
            if ($removed) $final = array_values(array_filter($final, static fn($u) => !isset($removed[strtolower($u)])));
        }
        // File-picker uploads appended after spreadsheet URLs.
        if (!empty($overrides[$line]['images']) && is_array($overrides[$line]['images'])) {
            foreach ($overrides[$line]['images'] as $imgPath) {
                $imgPath = self::normalizeImageUrl(trim((string)$imgPath));
                if ($imgPath !== '' && self::isValidImageUrl($imgPath)) $final[] = $imgPath;
            }
        }
        // Dedupe (case-insensitive) and cap.
        $seen = [];
        $deduped = [];
        foreach ($final as $u) {
            $k = strtolower($u);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $deduped[] = $u;
            if (count($deduped) >= self::MAX_IMAGES) break;
        }
        if (!$deduped) return;

        if ($isUpdate) {
            $existing = $db->prepare('SELECT url FROM product_images WHERE product_id = ?');
            $existing->execute([$productId]);
            $have = [];
            foreach ($existing->fetchAll(PDO::FETCH_COLUMN, 0) as $eu) {
                $have[strtolower(self::normalizeImageUrl((string)$eu))] = true;
            }
            $deduped = array_values(array_filter($deduped, static fn($u) => !isset($have[strtolower($u)])));
            if (!$deduped) return;
            $hasPrimary = (bool)$db->query('SELECT id FROM product_images WHERE product_id=' . $productId . ' AND is_primary=1 LIMIT 1')->fetchColumn();
        } else {
            $hasPrimary = false;
        }

        $hasSort = true;
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $cols = $db->query('PRAGMA table_info(product_images)')->fetchAll(PDO::FETCH_COLUMN, 1);
            } else {
                $cols = $db->query('SHOW COLUMNS FROM product_images')->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            $hasSort = in_array('sort_order', (array)$cols, true);
        } catch (Throwable $e) { $hasSort = false; }

        foreach ($deduped as $idx => $url) {
            $isPrimary = (!$hasPrimary && $idx === 0) ? 1 : 0;
            if ($hasSort) {
                $db->prepare('INSERT INTO product_images (product_id, url, is_primary, sort_order) VALUES (?, ?, ?, ?)')->execute([$productId, $url, $isPrimary, $idx]);
            } else {
                $db->prepare('INSERT INTO product_images (product_id, url, is_primary) VALUES (?, ?, ?)')->execute([$productId, $url, $isPrimary]);
            }
        }
    }

    private static function productColumns(PDO $db): array {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            return $db->query('PRAGMA table_info(products)')->fetchAll(PDO::FETCH_COLUMN, 1);
        }
        return $db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

