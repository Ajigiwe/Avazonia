<?php
// api/check-config.php
header('Content-Type: text/plain');
require_once __DIR__ . '/../config/app.php';

echo "RUNTIME CONFIGURATION CHECK\n";
echo "---------------------------\n";
echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'UNDEFINED') . "\n";
echo "SITE_EMAIL: " . (defined('SITE_EMAIL') ? SITE_EMAIL : 'UNDEFINED') . "\n";
echo "MAIL_FROM_EMAIL: " . (defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'UNDEFINED') . "\n";
echo "MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? MAIL_USERNAME : 'UNDEFINED') . "\n";
echo "MAIL_MAILER: " . (defined('MAIL_MAILER') ? MAIL_MAILER : 'UNDEFINED') . "\n";

echo "\nDATABASE SETTINGS (from 'settings' table):\n";
try {
    $sModel = new Settings();
    $dbSettings = $sModel->all();
    foreach ($dbSettings as $key => $val) {
        if (strpos($key, 'email') !== false || strpos($key, 'name') !== false) {
            echo "$key: $val\n";
        }
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

echo "\nENV CHECK (getenv):\n";
echo "SITE_EMAIL: " . getenv('SITE_EMAIL') . "\n";
echo "MAIL_FROM_EMAIL: " . getenv('MAIL_FROM_EMAIL') . "\n";
?>
