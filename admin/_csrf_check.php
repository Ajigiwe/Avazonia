<?php
// admin/_csrf_check.php
// Include this at the top of admin files that handle POST requests.
// Validates CSRF token for admin forms and AJAX calls.
// Must be included AFTER Session::start() and the auth check.

require_once __DIR__ . '/../core/Csrf.php';

// Admin forms contain session-bound tokens and must never be served from a
// browser, service-worker, or intermediary cache with an old session token.
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Only validate on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest()) {
        // When PHP receives a multipart request larger than post_max_size, it
        // discards both $_POST and $_FILES. Report that accurately instead of
        // misleading the administrator with a CSRF-expired message.
        $message = 'CSRF token invalid. Please refresh and try again.';
        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxSize = (string)ini_get('post_max_size');
        $unit = strtoupper(substr($postMaxSize, -1));
        $multiplier = $unit === 'G' ? 1073741824 : ($unit === 'M' ? 1048576 : ($unit === 'K' ? 1024 : 1));
        $postMaxBytes = (int)($unit !== '0' && $unit !== '' && !ctype_digit($postMaxSize)
            ? (float)$postMaxSize * $multiplier
            : (float)$postMaxSize);
        $isOversizedUpload = $contentLength > 0 && empty($_POST) && empty($_FILES) && $postMaxBytes > 0 && $contentLength > $postMaxBytes;
        $responseCode = 403;
        if ($isOversizedUpload) {
            $message = 'This upload is too large for the server. Please use smaller files or upload fewer images at a time.';
            $responseCode = 413;
        }

        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($acceptsJson || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code($responseCode);
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            http_response_code($responseCode);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><meta name="viewport" content="width=device-width, initial-scale=1"><title>Security check failed</title><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        exit;
    }
}
