<?php
// config/app.php

// 🟢 LIGHTWEIGHT .ENV LOADER
// Load .env file from the root directory and set environment variables
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
        }
    }
}

// 🟢 DYNAMIC SETTINGS LOADER
// Load settings from database to override .env defaults
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

define('APP_NAME', $dbSettings['store_name'] ?? (getenv('APP_NAME') ?: 'Avazonia'));
$rawUrl = getenv('APP_URL') ?: 'http://localhost/avazonia';
$finalUrl = (strpos($rawUrl, 'http') === 0) ? $rawUrl : 'https://' . rtrim($rawUrl, '/');
define('APP_URL', $finalUrl);
define('APP_PATH', parse_url(APP_URL, PHP_URL_PATH) ?: ''); // Subdirectory path for assets
define('CURRENCY_SYMBOL', '₵');
define('WHATSAPP_NUMBER', $dbSettings['whatsapp_number'] ?? (getenv('WHATSAPP_NUMBER') ?: '233240000000'));
define('SITE_EMAIL', $dbSettings['support_email'] ?? (getenv('SITE_EMAIL') ?: 'hello@avazonia.com.gh'));

// Design Tokens (Derived from DB)
define('PRIMARY_COLOR', $dbSettings['primary_brand_color'] ?? '#E5001A');
define('GRID_DENSITY', (int)($dbSettings['grid_density'] ?? 5));
define('ANNOUNCEMENT_BAR', $dbSettings['announcement_text'] ?? (getenv('ANNOUNCEMENT_TEXT') ?: ''));
define('FOOTER_NOTICE', $dbSettings['footer_notice'] ?? ('© ' . date('Y') . ' AVAZONIA GH — CRAFTED IN TAKORADI'));

// Shipping Tiers
define('SHIPPING_ACCRA', $dbSettings['shipping_accra'] ?? '30.00');
define('SHIPPING_KUMASI', $dbSettings['shipping_kumasi'] ?? '35.00');
define('SHIPPING_OTHERS', $dbSettings['shipping_others'] ?? '50.00');
define('SHIPPING_PICKUP', $dbSettings['shipping_pickup'] ?? 'FREE');
define('SHIPPING_FREE_THRESHOLD', (float)($dbSettings['shipping_free_threshold'] ?? 200.00));

// Mail Settings (Static .env Configuration)
define('MAIL_HOST',       getenv('MAIL_HOST')       ?: 'smtp.gmail.com');
define('MAIL_PORT',       (int)(getenv('MAIL_PORT') ?: 587));
define('MAIL_USERNAME',   getenv('MAIL_USERNAME')   ?: '');
define('MAIL_PASSWORD',   getenv('MAIL_PASSWORD')   ?: '');
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: SITE_EMAIL);
define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: APP_NAME);
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
define('MAIL_DEBUG',      (int)(getenv('MAIL_DEBUG') ?: 0));
?>
