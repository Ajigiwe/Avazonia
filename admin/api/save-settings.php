<?php
// admin/api/save-settings.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../models/Settings.php';
require_once __DIR__ . '/../../models/Logger.php';

Session::start();

// Security Check
if (Session::get('user_role') !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $settingsModel = new Settings();
    $settingsModel->ensureTable(); // Migration check

    // We iterate through the keys and save them
    // Define allowed keys for security
    $allowedKeys = [
        'store_name', 'support_email', 'whatsapp_number', 
        'announcement_text', 'primary_brand_color', 'grid_density',
        'default_shipping_fee', 'footer_notice',
        'shipping_accra', 'shipping_kumasi', 'shipping_others', 'shipping_pickup', 'shipping_free_threshold',
        'preorder_deposit_pct', 'returns_policy', 'shipping_policy', 'min_stock_threshold',
        'home_popup_enabled', 'home_popup_type', 'home_popup_title', 'home_popup_image', 
        'home_popup_desc', 'home_popup_discount', 'home_popup_link', 'home_popup_btn_text', 'home_popup_frequency',
        'instagram_link', 'facebook_link', 'twitter_link', 'youtube_link', 'tiktok_link', 'telegram_link', 'whatsapp_link',
        'meta_description', 'meta_keywords', 'store_map_address',
        'paystack_public_key', 'paystack_secret_key', 'currency_symbol',
        'support_title', 'support_subtitle', 'support_phone', 'support_hours',
        'footer_address'
    ];

    foreach ($input as $key => $value) {
        if (in_array($key, $allowedKeys)) {
            $settingsModel->set($key, $value);
        }
    }

    $changedCount = count($input);
    Logger::log('SETTING_UPDATE', "Administrative update to $changedCount system configuration keys.", ['keys' => array_keys($input)]);

    echo json_encode(['success' => true, 'message' => 'Settings saved successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
