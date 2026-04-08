<?php
// config/app.php

// 🟢 LIGHTWEIGHT .ENV LOADER
$envPath = __DIR__ . '/../.env';
$debugMsg = "Env path: $envPath\n";

if (file_exists($envPath)) {
    $debugMsg .= "File exists. Reading...\n";
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            putenv("$key=$val");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
} else {
    $debugMsg .= "CRITICAL: .env file NOT FOUND at $envPath\n";
}

// 🔴 EMERGENCY FALLBACK FOR PRODUCTION (Corrects the 'root' error)
if (empty($_ENV['DB_USER']) || $_ENV['DB_USER'] === 'root') {
    $debugMsg .= "Using fallback credentials for production.\n";
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'avazonia_avazonia';
    $_ENV['DB_USER'] = 'avazonia_admin';
    $_ENV['DB_PASS'] = 'Avazonia123@';
}

// Log status for troubleshooting
file_put_contents(__DIR__ . '/../env_debug.log', date('Y-m-d H:i:s') . ": " . $debugMsg, FILE_APPEND);

// 🟢 DYNAMIC SETTINGS LOADER
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/Settings.php';

try {
    $sModel = new Settings();
    // $sModel->ensureTable(); // Avoid recursive call or heavy check here
    $dbSettings = $sModel->all();
} catch (Exception $e) {
    $dbSettings = [];
}

if (!defined('APP_NAME')) define('APP_NAME', $dbSettings['store_name'] ?? (getenv('APP_NAME') ?: 'Avazonia'));
$rawUrl = getenv('APP_URL') ?: 'http://localhost/avazonia';
$finalUrl = (strpos($rawUrl, 'http') === 0) ? $rawUrl : 'https://' . rtrim($rawUrl, '/');
if (!defined('APP_URL')) define('APP_URL', $finalUrl);
if (!defined('APP_PATH')) define('APP_PATH', parse_url(APP_URL, PHP_URL_PATH) ?: ''); // Subdirectory path for assets
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₵');
if (!defined('WHATSAPP_NUMBER')) define('WHATSAPP_NUMBER', $dbSettings['whatsapp_number'] ?? (getenv('WHATSAPP_NUMBER') ?: '233240000000'));
if (!defined('SITE_EMAIL')) define('SITE_EMAIL', $dbSettings['support_email'] ?? (getenv('SITE_EMAIL') ?: 'hello@avazonia.com.gh'));

// Design Tokens (Derived from DB)
if (!defined('PRIMARY_COLOR')) define('PRIMARY_COLOR', $dbSettings['primary_brand_color'] ?? '#E5001A');
if (!defined('GRID_DENSITY')) define('GRID_DENSITY', (int)($dbSettings['grid_density'] ?? 5));
if (!defined('ANNOUNCEMENT_BAR')) define('ANNOUNCEMENT_BAR', $dbSettings['announcement_text'] ?? (getenv('ANNOUNCEMENT_TEXT') ?: ''));
if (!defined('FOOTER_NOTICE')) define('FOOTER_NOTICE', $dbSettings['footer_notice'] ?? ('© ' . date('Y') . ' AVAZONIA GH — CRAFTED IN TAKORADI'));

// Shipping Tiers
if (!defined('SHIPPING_ACCRA')) define('SHIPPING_ACCRA', $dbSettings['shipping_accra'] ?? '30.00');
if (!defined('SHIPPING_KUMASI')) define('SHIPPING_KUMASI', $dbSettings['shipping_kumasi'] ?? '35.00');
if (!defined('SHIPPING_OTHERS')) define('SHIPPING_OTHERS', $dbSettings['shipping_others'] ?? '50.00');
if (!defined('SHIPPING_PICKUP')) define('SHIPPING_PICKUP', $dbSettings['shipping_pickup'] ?? 'FREE');
if (!defined('SHIPPING_FREE_THRESHOLD')) define('SHIPPING_FREE_THRESHOLD', (float)($dbSettings['shipping_free_threshold'] ?? 200.00));

// Mail Settings (Static .env Configuration)
if (!defined('MAIL_HOST'))       define('MAIL_HOST',       getenv('MAIL_HOST')       ?: 'smtp.gmail.com');
if (!defined('MAIL_PORT'))       define('MAIL_PORT',       (int)(getenv('MAIL_PORT') ?: 587));
if (!defined('MAIL_USERNAME'))   define('MAIL_USERNAME',   getenv('MAIL_USERNAME')   ?: '');
if (!defined('MAIL_PASSWORD'))   define('MAIL_PASSWORD',   getenv('MAIL_PASSWORD')   ?: '');
if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: SITE_EMAIL);
if (!defined('MAIL_FROM_NAME'))  define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: APP_NAME);
if (!defined('MAIL_ENCRYPTION')) define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
if (!defined('MAIL_DEBUG'))      define('MAIL_DEBUG',      (int)(getenv('MAIL_DEBUG') ?: 0));
?>
