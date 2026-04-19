<?php
// test-email.php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Mailer.php';

header('Content-Type: text/plain');

$to = $_GET['to'] ?? SITE_EMAIL;
echo "Testing email delivery to: $to\n";
echo "Mailer: " . (defined('MAIL_MAILER') ? MAIL_MAILER : 'NOT SET') . "\n";
echo "Brevo Key: " . (defined('BREVO_API_KEY') ? 'SET' : 'MISSING') . "\n";

try {
    $res = Mailer::send($to, 'Test User', 'Avazonia Email Test', '<h1>Test Successful</h1><p>If you see this, Brevo is working.</p>');
    if ($res) {
        echo "✅ SUCCESS: Email sent successfully according to Mailer.\n";
    } else {
        echo "❌ FAIL: Mailer returned false. Check php_errorlog for details.\n";
    }
} catch (Exception $e) {
    echo "🚨 ERROR: " . $e->getMessage() . "\n";
}
