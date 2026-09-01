#!/usr/bin/env php
<?php
/**
 * DeepL Auto-Translation Script for Avazonia
 * 
 * Reads lang/en.php, sends each value to DeepL API, writes lang/fr.php and lang/zh.php
 * 
 * Usage:
 *   php scripts/deepl_translate.php YOUR_DEEPL_API_KEY
 *   php scripts/deepl_translate.php YOUR_DEEPL_API_KEY --lang=fr
 *   php scripts/deepl_translate.php YOUR_DEEPL_API_KEY --lang=zh
 * 
 * Get a free API key at: https://www.deepl.com/pro-api (free tier = 500K chars/month)
 */

// Parse args
$apiKey = $argv[1] ?? '';
$langFilter = null;
foreach ($argv as $arg) {
    if (preg_match('/^--lang=(\w+)$/', $arg, $m)) {
        $langFilter = $m[1];
    }
}

if (empty($apiKey)) {
    echo "Usage: php scripts/deepl_translate.php YOUR_DEEPL_API_KEY [--lang=fr|zh]\n";
    echo "Get a free API key at: https://www.deepl.com/pro-api\n";
    exit(1);
}

$projectRoot = dirname(__DIR__);
$enFile = $projectRoot . '/lang/en.php';

if (!file_exists($enFile)) {
    echo "ERROR: lang/en.php not found at $enFile\n";
    exit(1);
}

$enStrings = require $enFile;
echo "Loaded " . count($enStrings) . " keys from lang/en.php\n";

// Target languages
$targets = [
    'fr' => ['name' => 'French',    'deepl' => 'FR',   'file' => 'fr.php', 'comment' => 'Français'],
    'zh' => ['name' => 'Chinese',   'deepl' => 'ZH',   'file' => 'zh.php', 'comment' => '简体中文'],
];

if ($langFilter && isset($targets[$langFilter])) {
    $targets = [$langFilter => $targets[$langFilter]];
}

foreach ($targets as $code => $info) {
    echo "\n=== Translating to {$info['name']} ({$info['deepl']}) ===\n";
    
    // Load existing translations to preserve manual overrides
    $existingFile = $projectRoot . "/lang/{$info['file']}";
    $existing = file_exists($existingFile) ? require $existingFile : [];
    
    $translated = [];
    $skipped = 0;
    $translated_count = 0;
    $chars_sent = 0;
    
    foreach ($enStrings as $key => $value) {
        // Skip if we already have a manual translation (non-empty)
        if (!empty($existing[$key])) {
            $translated[$key] = $existing[$key];
            $skipped++;
            continue;
        }
        
        // Call DeepL API
        $result = deeplTranslate($apiKey, $value, $info['deepl']);
        if ($result !== null) {
            $translated[$key] = $result;
            $translated_count++;
            $chars_sent += strlen($value);
        } else {
            // Fallback to English
            $translated[$key] = $value;
            echo "  WARNING: Failed to translate '$key', using English fallback\n";
        }
        
        // Rate limit: DeepL free tier allows 1 req/sec
        usleep(1050000); // 1.05 seconds between requests
    }
    
    // Write the file
    $outputFile = $projectRoot . "/lang/{$info['file']}";
    $content = "<?php\n// lang/{$info['file']} — {$info['comment']} (auto-translated via DeepL)\nreturn [\n";
    
    $currentSection = '';
    foreach ($translated as $key => $value) {
        $section = explode('.', $key)[0];
        if ($section !== $currentSection) {
            $currentSection = $section;
            $sectionLabel = ucfirst(str_replace('_', ' ', $section));
            $content .= "\n    // ── {$sectionLabel} ──\n";
        }
        // Escape single quotes in value
        $escapedValue = str_replace("'", "\\'", $value);
        $content .= "    '{$key}' => '{$escapedValue}',\n";
    }
    $content .= "];\n";
    
    file_put_contents($outputFile, $content);
    
    echo "\nResults:\n";
    echo "  Existing (preserved): {$skipped}\n";
    echo "  Newly translated:     {$translated_count}\n";
    echo "  Characters sent:      {$chars_sent}\n";
    echo "  Output: {$outputFile}\n";
}

echo "\nDone!\n";

/**
 * Call DeepL API for a single string
 */
function deeplTranslate(string $apiKey, string $text, string $targetLang): ?string {
    $url = 'https://api-free.deepl.com/v2/translate';
    
    $data = http_build_query([
        'text' => $text,
        'target_lang' => $targetLang,
        'source_lang' => 'EN',
        'tag_handling' => 'html',
        'ignore_tags' => 'span,div',
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            "Authorization: DeepL-Auth-Key {$apiKey}",
            "Content-Type: application/x-www-form-urlencoded",
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  cURL error: {$error}\n";
        return null;
    }
    
    if ($httpCode !== 200) {
        echo "  API error (HTTP {$httpCode}): {$response}\n";
        return null;
    }
    
    $json = json_decode($response, true);
    if (isset($json['translations'][0]['text'])) {
        return $json['translations'][0]['text'];
    }
    
    echo "  Unexpected response: {$response}\n";
    return null;
}
