<?php
// router.php — PHP built-in server router for `php -S localhost:8000 -t . router.php`
// Serves existing files directly; everything else goes to index.php (front controller).

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = __DIR__ . $uri;

// Serve existing files/dirs (including /public/*, /admin/*.php, /api/*.php) directly
if ($uri !== '/' && file_exists($path)) {
    // Let PHP's built-in server serve the file (honor mime type, etc.)
    if (is_file($path)) {
        return false;
    }
    // Directories (e.g. /public/) — try index
    if (is_dir($path) && file_exists($path . '/index.php')) {
        require $path . '/index.php';
        return true;
    }
}

// Fallback to MVC router
require __DIR__ . '/index.php';
