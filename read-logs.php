<?php
// read-logs.php
header('Content-Type: text/plain');
$logs = [
    'root' => __DIR__ . '/error_log',
    'admin' => __DIR__ . '/admin/error_log',
    'api' => __DIR__ . '/api/error_log'
];

foreach ($logs as $name => $path) {
    echo "--- $name ($path) ---\n";
    if (file_exists($path)) {
        echo file_get_contents($path);
    } else {
        echo "File not found.\n";
    }
    echo "\n\n";
}
