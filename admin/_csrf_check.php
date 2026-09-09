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
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($acceptsJson || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF token invalid. Please refresh and try again.']);
        } else {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><meta name="viewport" content="width=device-width, initial-scale=1"><title>Security check failed</title><p>For your security, this form expired. Please go back, refresh the page, and submit it again.</p>';
        }
        exit;
    }
}
