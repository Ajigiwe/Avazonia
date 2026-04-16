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

echo "\n--- TEST 1: Port 465 (SSL) ---\n";
test_send(MAIL_HOST, 465, 'ssl', MAIL_USERNAME, MAIL_PASSWORD);

echo "\n--- TEST 2: Port 587 (TLS) ---\n";
test_send(MAIL_HOST, 587, 'tls', MAIL_USERNAME, MAIL_PASSWORD);

function test_send($host, $port, $enc, $user, $pass) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = $enc;
        $mail->Port       = $port;
        $mail->SMTPDebug  = 2; 
        $mail->Debugoutput = function($str, $level) { echo "DEBUG: $str\n"; };

        $mail->setFrom($user, 'Debug Test');
        $mail->addAddress($user); 
        $mail->Subject = "PROD DEBUG - PORT $port";
        $mail->Body    = "Testing connection on port $port with $enc";

        echo "Attempting send on port $port ($enc)...\n";
        $mail->send();
        echo "SUCCESS on port $port!\n";
    } catch (Exception $e) {
        echo "FAILED on port $port: " . $e->getMessage() . "\n";
    }
}

