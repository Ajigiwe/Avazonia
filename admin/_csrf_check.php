<?php
// admin/_csrf_check.php
// Include this at the top of admin files that handle POST requests.
// Validates CSRF token for admin forms and AJAX calls.
// Must be included AFTER Session::start() and the auth check.

require_once __DIR__ . '/../core/Csrf.php';

// Only validate on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest()) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token invalid. Please refresh and try again.']);
        exit;
    }
}
