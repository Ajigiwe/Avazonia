<?php
// api/test-mailersend.php
require_once __DIR__ . '/../config/app.php';

echo "MAILERSEND API DIAGNOSTIC\n";
echo "-------------------------\n";

$apiKey = defined('MAILERSEND_API_KEY') ? MAILERSEND_API_KEY : '';
if (empty($apiKey)) {
    die("ERROR: MAILERSEND_API_KEY is missing from .env or config.\n");
}

$fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : SITE_EMAIL;
$fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME;
$toEmail   = $fromEmail; // Send to self for testing

echo "From: $fromName <$fromEmail>\n";
echo "To: $toEmail\n";
echo "API Key Length: " . strlen($apiKey) . "\n\n";

$payload = [
    'from' => ['email' => $fromEmail, 'name' => $fromName],
    'to'   => [['email' => $toEmail, 'name' => 'Admin Test']],
    'subject' => 'MailerSend API Test',
    'html' => '<b>This is a test from Avazonia</b>',
    'text' => 'This is a test from Avazonia'
];

$ch = curl_init('https://api.mailersend.com/v1/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Authorization: Bearer ' . $apiKey
]);

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response Body:\n";
echo $response . "\n";
?>
