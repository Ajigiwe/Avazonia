<?php
// core/Csrf.php
// CSRF protection — generates, stores, and validates per-session tokens.

class Csrf {

    private const TOKEN_KEY = '_csrf_token';

    /**
     * Ensure a CSRF token exists in the session, generate if missing.
     */
    public static function ensure(): string {
        Session::start();
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Get the current CSRF token (creates one if needed).
     */
    public static function token(): string {
        return self::ensure();
    }

    /**
     * Validate a submitted token against the session token.
     * Clears the old token on success (one-time use) to prevent replay.
     */
    public static function validate(?string $submitted): bool {
        if ($submitted === '' || $submitted === null) {
            return false;
        }
        Session::start();
        $sessionToken = $_SESSION[self::TOKEN_KEY] ?? '';
        if (empty($sessionToken)) {
            return false;
        }
        return hash_equals($sessionToken, $submitted);
    }

    /**
     * Output a hidden input field for HTML forms.
     * Usage: <?= Csrf::field() ?>
     */
    public static function field(): string {
        $token = self::ensure();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get the token value for use in JS (e.g., meta tag content or AJAX header).
     */
    public static function headerValue(): string {
        return self::ensure();
    }

    /**
     * Get the HTTP header name used for AJAX CSRF tokens.
     */
    public static function headerName(): string {
        return 'X-CSRF-Token';
    }

    /**
     * Validate a request: check header first, then POST field, then JSON body field.
     * Returns true if valid, false otherwise.
     */
    public static function validateRequest(): bool {
        // 1. Check X-CSRF-Token header (AJAX / fetch calls)
        $headerName = self::headerName();
        if (!empty($_SERVER['HTTP_' . str_replace('-', '_', strtoupper($headerName))])) {
            $token = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($headerName))];
            return self::validate($token);
        }

        // 2. Check _csrf_token in POST data (HTML forms)
        if (!empty($_POST[self::TOKEN_KEY])) {
            return self::validate($_POST[self::TOKEN_KEY]);
        }

        // 3. Check _csrf_token in JSON body (API endpoints reading php://input)
        $rawInput = file_get_contents('php://input');
        if ($rawInput) {
            $json = json_decode($rawInput, true);
            if (is_array($json) && !empty($json[self::TOKEN_KEY])) {
                return self::validate($json[self::TOKEN_KEY]);
            }
        }

        return false;
    }

    /**
     * Validate request or send JSON 403 error and exit.
     * Use in API endpoints: Csrf::validateRequestOrDie();
     */
    public static function validateRequestOrDie(): void {
        if (!self::validateRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF token invalid or missing. Please refresh the page and try again.']);
            exit;
        }
    }
}
