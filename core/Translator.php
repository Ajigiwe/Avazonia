<?php
// core/Translator.php — Lightweight i18n for Avazonia
// Usage: echo t('nav.home', 'Home');

class Translator {
    private static ?Translator $instance = null;
    private string $lang = 'en';
    private array $strings = [];
    private array $supported = ['en', 'fr', 'zh'];
    private array $labels = [
        'en' => ['label' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
        'fr' => ['label' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
        'zh' => ['label' => '中文', 'flag' => '🇨🇳', 'dir' => 'ltr'],
    ];

    private function __construct() {
        $this->lang = $this->detectLang();
        $this->load($this->lang);
    }

    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detectLang(): string {
        // Ensure session is available (Translator loads before controllers call Session::start())
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // 1. URL param ?lang=fr (highest priority)
        if (!empty($_GET['lang']) && in_array($_GET['lang'], $this->supported)) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
            // Update cookie for persistence (30 days)
            if (!headers_sent()) {
                setcookie('avazonia_lang', $lang, time() + 30*24*3600, '/');
            }
            // Redirect to clean URL (remove ?lang= param)
            $url = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            if ($url !== false && $url !== '' && !headers_sent()) {
                header("Location: $url", true, 302);
                exit;
            }
            return $lang;
        }
        // 2. Session
        if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $this->supported)) {
            return $_SESSION['lang'];
        }
        // 3. Cookie
        if (!empty($_COOKIE['avazonia_lang']) && in_array($_COOKIE['avazonia_lang'], $this->supported)) {
            return $_COOKIE['avazonia_lang'];
        }
        // 4. Browser Accept-Language header
        $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        foreach ($this->supported as $s) {
            if (stripos($accept, $s) !== false) {
                return $s;
            }
        }
        // 5. Default
        return 'en';
    }

    private function load(string $lang): void {
        $file = __DIR__ . '/../lang/' . $lang . '.php';
        if (file_exists($file)) {
            $this->strings = require $file;
        }
        // Always load English as fallback
        if ($lang !== 'en') {
            $fallback = __DIR__ . '/../lang/en.php';
            if (file_exists($fallback)) {
                $this->strings = array_merge(require $fallback, $this->strings);
            }
        }
    }

    public function get(string $key, string $fallback = ''): string {
        return $this->strings[$key] ?? $fallback ?: $key;
    }

    public function getLang(): string {
        return $this->lang;
    }

    public function getSupported(): array {
        return $this->supported;
    }

    public function getLabel(string $lang): string {
        return $this->labels[$lang]['label'] ?? strtoupper($lang);
    }

    public function getFlag(string $lang): string {
        return $this->labels[$lang]['flag'] ?? '🌐';
    }

    public function getDir(string $lang = ''): string {
        return $this->labels[$lang ?: $this->lang]['dir'] ?? 'ltr';
    }

    public function getOthers(): array {
        $others = [];
        foreach ($this->supported as $s) {
            if ($s !== $this->lang) {
                $others[$s] = $this->labels[$s];
            }
        }
        return $others;
    }
}

// Global helper function
function t(string $key, string $fallback = ''): string {
    return Translator::getInstance()->get($key, $fallback);
}
