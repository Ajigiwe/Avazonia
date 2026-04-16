<?php
// api/debug-mail.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Mailer.php';

header('Content-Type: text/plain');

echo "AVAZONIA MAIL DEBUG\n";
echo "-------------------\n";
echo "MAIL_HOST: " . MAIL_HOST . "\n";
echo "MAIL_PORT: " . MAIL_PORT . "\n";
echo "MAIL_USERNAME: " . MAIL_USERNAME . "\n";
echo "MAIL_ENCRYPTION: " . MAIL_ENCRYPTION . "\n";
echo "SITE_EMAIL: " . SITE_EMAIL . "\n";
echo "\nTesting template render...\n";

try {
    $html = Mailer::render('newsletter_welcome', ['toEmail' => 'test@test.com']);
    echo "RENDER SUCCESS! (Size: " . strlen($html) . ")\n";
} catch (Exception $e) {
    echo "RENDER FAIL: " . $e->getMessage() . "\n";
}

echo "\nTesting PHPMailer directly with detailed debug...\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->SMTPDebug  = 4; // Max debug output
    $mail->Debugoutput = function($str, $level) { echo "DEBUG: $str\n"; };

    $fromEmail = MAIL_FROM_EMAIL;
    $mail->setFrom($fromEmail, 'Debug Test');
    $mail->addAddress(MAIL_USERNAME); // Send to yourself
    $mail->Subject = 'PROD DEBUG MAIL';
    $mail->Body    = 'This is a test from the production API folder.';

    echo "Attempting send...\n";
    $mail->send();
    echo "\nSEND SUCCESS!\n";
} catch (Exception $e) {
    echo "\nSEND FAILED: " . $e->getMessage() . "\n";
}
