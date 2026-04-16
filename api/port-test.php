<?php
// api/port-test.php
header('Content-Type: text/plain');

$targets = [
    'smtp.gmail.com' => [465, 587, 25],
    'localhost' => [25, 465, 587],
    'mail.avazonia.com' => [25, 465, 587]
];

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

echo "\nENVIRONMENT CHECK\n";
echo "-----------------\n";
require_once __DIR__ . '/../config/app.php';
echo "MAIL_MAILER: " . (defined('MAIL_MAILER') ? MAIL_MAILER : 'UNDEFINED') . "\n";
echo "MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : 'UNDEFINED') . "\n";
?>
