<?php
// api/test-brevo.php
require_once __DIR__ . '/../config/app.php';

echo "BREVO API DIAGNOSTIC\n";
echo "-------------------------\n";

$apiKey = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
if (empty($apiKey)) {
    die("ERROR: BREVO_API_KEY is missing from .env or config.\n");
}

$fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : SITE_EMAIL;
$fromName  = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME;
$toEmail   = $fromEmail; // Send to self for testing

echo "From: $fromName <$fromEmail>\n";
echo "To: $toEmail\n";
echo "API Key Length: " . strlen($apiKey) . "\n\n";

$payload = [
    'sender' => ['name' => $fromName, 'email' => $fromEmail],
    'to'     => [['email' => $toEmail, 'name' => 'Admin Test']],
    'subject' => 'Brevo API Test',
    'htmlContent' => '<b>This is a test from Avazonia using Brevo</b>'
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'api-key: ' . $apiKey,
    'content-type: application/json'
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
