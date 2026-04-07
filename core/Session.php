<?php
// core/Session.php
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Defensive: ensure No output has been sent before starting
            if (!headers_sent()) {
                session_start();
            }
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
