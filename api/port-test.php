<?php
// api/port-test.php
ini_set('display_errors', 1);
ini_set('display_log', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

$targets = [
    'smtp.gmail.com' => [465, 587, 25, 2525],
    'localhost' => [25, 465, 587],
    'mail.avazonia.com' => [25, 465, 587]
];

require_once __DIR__ . '/../config/app.php';

echo "PORT CONNECTIVITY TEST\n";
echo "----------------------\n";

foreach ($targets as $host => $ports) {
    foreach ($ports as $port) {
        echo "Testing $host:$port... ";
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if (is_resource($connection)) {
            echo "OPEN! ✅\n";
            fclose($connection);
        } else {
            echo "CLOSED ($errstr) ❌\n";
        }
    }
}

echo "\n--- TEST 4: Local SMTP Relay (Port 25, No Auth) ---\n";
require_once __DIR__ . '/../core/Mailer.php';
test_local_relay(MAIL_USERNAME);

function test_local_relay($to) {
    echo "Attempting send via localhost:25 (no auth)...\n";
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'localhost';
        $mail->Port = 25;
        $mail->SMTPAuth = false;
        $mail->setFrom('info@avazonia.com', 'Avazonia Local');
        $mail->addAddress($to);
        $mail->Subject = "PROD DEBUG - LOCAL RELAY";
        $mail->Body    = "Testing local relay (localhost:25) with no auth.";
        
        if($mail->send()) {
            echo "SUCCESS via local relay! ✅ (Check your spam folder)\n";
        }
    } catch (Exception $e) {
        echo "FAILED via local relay: " . $e->getMessage() . " ❌\n";
    }
}

echo "\nENVIRONMENT CHECK\n";
echo "-----------------\n";
echo "MAIL_MAILER: " . (defined('MAIL_MAILER') ? MAIL_MAILER : 'UNDEFINED') . "\n";
echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : 'UNDEFINED') . "\n";
?>

