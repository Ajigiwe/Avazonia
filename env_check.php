<?php
// env_check.php — Temporary diagnostic. DELETE AFTER USE.
header('Content-Type: text/plain');

echo "=== AVAZONIA ENV DIAGNOSTIC ===\n\n";

// 1. Check paths
$paths = [
    'config/../.env' => __DIR__ . '/config/../.env',
    'DOCUMENT_ROOT/.env' => $_SERVER['DOCUMENT_ROOT'] . '/.env',
    'getcwd()/.env' => getcwd() . '/.env',
    '../.env (parent)' => __DIR__ . '/../.env',
    './.env (root)' => __DIR__ . '/.env',
];

echo "--- PATH SEARCH ---\n";
foreach ($paths as $label => $path) {
    $realpath = realpath($path);
    $exists = file_exists($path) ? 'YES' : 'NO';
    $readable = is_readable($path) ? 'YES' : 'NO';
    echo "$label\n";
    echo "  Raw:      $path\n";
    echo "  Real:     " . ($realpath ?: 'N/A') . "\n";
    echo "  Exists:   $exists\n";
    echo "  Readable: $readable\n\n";
}

echo "--- SERVER INFO ---\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "__DIR__:       " . __DIR__ . "\n";
echo "getcwd():      " . getcwd() . "\n";
echo "PHP User:      " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n\n";

// 2. Try loading
echo "--- ENV LOADING TEST ---\n";
$found = null;
foreach ($paths as $label => $path) {
    if (file_exists($path) && is_readable($path)) {
        $found = $path;
        echo "WINNER: $label => $path\n";
        break;
    }
}

if ($found) {
    $lines = file($found, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            $count++;
            // Only show key names (NOT values) for security
            echo "  Loaded: $key = " . str_repeat('*', min(strlen($val), 8)) . " (" . strlen($val) . " chars)\n";
        }
    }
    echo "\nTotal keys loaded: $count\n";
} else {
    echo "CRITICAL: No .env file found in ANY location!\n";
}
