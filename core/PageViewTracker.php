<?php
// core/PageViewTracker.php
// Server-side page-view tracking: one row per human page view, no JavaScript
// and no third-party requests. Two first-party cookies identify the visitor
// (365d) and the session (30-minute sliding window). Works even when visitors
// block Google scripts; powers the admin dashboard traffic widget.

require_once __DIR__ . '/Cache.php';

class PageViewTracker
{
    public const SETTING_KEY     = 'page_view_tracking_enabled';
    public const COOKIE_VISITOR  = 'avz_vid';
    public const COOKIE_SESSION  = 'avz_sid';

    private const VISITOR_TTL_DAYS   = 365;
    private const SESSION_TTL_MIN    = 30;
    private const RETENTION_DAYS     = 180;
    private const PRUNE_CHANCE_PCT   = 1; // ~1% of writes also prune old rows

    /** Test/CLI seam: when true, CLI processes are treated as web requests. */
    public static bool $allowCli = false;

    /** Is tracking turned on? (Settings toggle; fails open.) */
    public static function enabled(): bool
    {
        try {
            global $dbSettings;
            $val = $dbSettings[self::SETTING_KEY] ?? null;
            if ($val === null) {
                if (!class_exists('Settings')) require_once __DIR__ . '/../models/Settings.php';
                $val = (new Settings())->get(self::SETTING_KEY, '1');
            }
            return !in_array(strtolower(trim((string)$val)), ['0', 'off', 'false', ''], true);
        } catch (\Throwable $e) {
            return true; // a broken settings lookup must never take the site down
        }
    }

    /**
     * Assign/refresh the first-party visitor + session cookies.
     * MUST run before any output (cookies are headers). Stashes the ids in
     * $GLOBALS for track() because headers are unavailable afterwards.
     */
    public static function boot(): void
    {
        if (PHP_SAPI === 'cli' && !self::$allowCli) return;

        $now    = time();
        $secure = (($_SERVER['REQUEST_SCHEME'] ?? '') === 'https')
            || strtolower((string)($_SERVER['HTTPS'] ?? '')) === 'on'
            || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $base = ['path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax'];

        $vid = $_COOKIE[self::COOKIE_VISITOR] ?? '';
        $newVisitor = !self::validId($vid);
        if ($newVisitor) $vid = bin2hex(random_bytes(16));
        setcookie(self::COOKIE_VISITOR, $vid, $base + ['expires' => $now + self::VISITOR_TTL_DAYS * 86400]);

        $sid = $_COOKIE[self::COOKIE_SESSION] ?? '';
        $newSession = !self::validId($sid);
        if ($newSession) $sid = bin2hex(random_bytes(16));
        setcookie(self::COOKIE_SESSION, $sid, $base + ['expires' => $now + self::SESSION_TTL_MIN * 60]);

        $GLOBALS['avz_pv'] = ['vid' => $vid, 'sid' => $sid, 'new_visitor' => $newVisitor];
    }

    /**
     * Log the current request as a page view. Silently no-ops when disabled,
     * for bots, for admin staff, and on any database error — analytics must
     * never break a page render.
     */
    public static function track(?string $uri = null): void
    {
        try {
            if (!self::enabled() || (PHP_SAPI === 'cli' && !self::$allowCli)) return;
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

            $uri = $uri ?? (string)($_SERVER['REQUEST_URI'] ?? '/');
            if (self::isExcludedPath($uri)) return;

            $ctx = $GLOBALS['avz_pv'] ?? null;
            if (!is_array($ctx)) return; // boot() never ran — nothing to attribute

            // Admin staff browsing the storefront are not traffic.
            if (($_SESSION['user_role'] ?? '') === 'admin') return;

            // Skip bots, automation clients and non-document requests.
            if (!self::shouldTrack($_SERVER)) return;
            $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

            $parts = parse_url($uri);
            $path  = substr((string)($parts['path'] ?? '/'), 0, 255);
            if ($path === '') $path = '/';
            $qs = isset($parts['query']) ? substr($parts['query'], 0, 255) : null;

            [$refSource, $refPath] = self::classifyReferrer((string)($_SERVER['HTTP_REFERER'] ?? ''));

            $db = db();
            $stmt = $db->prepare(
                'INSERT INTO page_views
                    (path, query_string, visitor_id, session_id, referrer_host, referrer_path, referrer, user_agent, is_new_visitor, viewed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $path,
                $qs,
                $ctx['vid'],
                $ctx['sid'],
                $refSource !== null ? mb_substr($refSource, 0, 190) : null,
                $refPath,
                $refSource,
                mb_substr($ua, 0, 255),
                !empty($ctx['new_visitor']) ? 1 : 0,
                date('Y-m-d H:i:s'),
            ]);

            // Opportunistic retention pruning.
            if (random_int(1, 100) <= self::PRUNE_CHANCE_PCT) {
                $cutoff = date('Y-m-d H:i:s', time() - self::RETENTION_DAYS * 86400);
                $db->prepare('DELETE FROM page_views WHERE viewed_at < ?')->execute([$cutoff]);
            }
        } catch (\Throwable $e) {
            // Analytics is best-effort by design.
        }
    }

    /** Fetch everything the dashboard widget needs in one call. */
    public static function widgetData(): array
    {
        require_once __DIR__ . '/../models/PageView.php';
        $model = new PageView();
        return [
            'summary' => $model->summary(),
            'daily'   => $model->dailySeries(7),
            'pages'   => $model->topPages(7, 5),
            'refs'    => $model->topReferrers(7, 5),
        ];
    }

    private static function validId(?string $id): bool
    {
        return is_string($id) && (bool)preg_match('/^[0-9a-f]{32}$/', $id);
    }

    /**
     * Split the Referer header into (source, path). Same-site navigations are
     * not traffic sources — they keep their path for "previous page" context
     * but return a null source so they never appear in Top Referrers.
     *
     * @return array{0:?string,1:?string}
     */
    private static function classifyReferrer(string $ref): array
    {
        $ref = trim($ref);
        if ($ref === '' || !filter_var($ref, FILTER_VALIDATE_URL)) return [null, null];

        $rp = parse_url($ref);
        $host = strtolower((string)($rp['host'] ?? ''));
        if ($host === '') return [null, null];
        $host = preg_replace('/^www\./', '', $host);

        $site = strtolower((string)(parse_url(APP_URL, PHP_URL_HOST) ?: ''));
        $site = preg_replace('/^www\./', '', $site);

        $path = isset($rp['path']) && $rp['path'] !== '/' ? substr($rp['path'], 0, 255) : null;

        if ($site !== '' && $host === $site) return [null, $path]; // internal navigation
        return [$host, $path];
    }

    private static function isExcludedPath(string $uri): bool
    {
        $path = strtolower((string)(parse_url($uri, PHP_URL_PATH) ?: ''));
        if ($path === '' || $path === '/') $path = '/';
        $excluded = ['/health', '/api/', '/cart/update', '/cart/remove', '/checkout/complete', '/checkout/init-balance'];
        foreach ($excluded as $p) {
            if ($path === $p || strpos($path, $p) === 0) return true;
        }
        // Never log direct asset/asset-ish requests (defensive; the router
        // should never route them anyway).
        if (preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|map|xml|txt)$/', $path)) return true;
        return false;
    }

    /**
     * Decide whether this request is a real browser page view.
     * Three independent gates: an automation-looking UA, a UA that is not a
     * browser engine, and request headers that are not a document navigation.
     * Kept separate from track() so it can be tested without touching the DB.
     *
     * @param array<string,mixed> $server Typically $_SERVER.
     */
    public static function shouldTrack(array $server): bool
    {
        $ua = trim((string)($server['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || self::isBot($ua)) return false;

        // Real navigations carry a browser engine token; script clients
        // (PowerShell, HttpWebRequest, HTTP libraries) do not.
        if (!preg_match('/(Chrome|CriOS|Firefox|FxiOS|Safari|Edg|OPR|Version)\//i', $ua)) return false;

        $purpose = strtolower((string)($server['HTTP_SEC_PURPOSE'] ?? ($server['HTTP_SEC_FETCH_PURPOSE'] ?? ($server['HTTP_X_PURPOSE'] ?? ''))));
        if ($purpose !== '' && (strpos($purpose, 'prefetch') !== false || strpos($purpose, 'prerender') !== false)) return false;

        $mode = strtolower((string)($server['HTTP_SEC_FETCH_MODE'] ?? ''));
        if ($mode !== '' && $mode !== 'navigate') return false;

        // Browsers ask for HTML on a navigation; background fetches ask for */*.
        $accept = strtolower((string)($server['HTTP_ACCEPT'] ?? ''));
        if ($accept === '' || strpos($accept, 'text/html') === false) return false;

        return true;
    }

    /** Does the User-Agent look automated? Broad on purpose: UA is only one gate. */
    public static function isBot(string $ua): bool
    {
        $uaLower = strtolower($ua);
        $needles = [
            'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'python-', 'python/',
            'java/', 'okhttp', 'go-http', 'libwww', 'httpclient', 'scrap', 'headless',
            'phantomjs', 'monitor', 'uptime', 'pingdom', 'gtmetrix', 'lighthouse',
            'semrush', 'ahrefs', 'mj12', 'dotbot', 'megaindex', 'petalbot',
            'facebookexternalhit', 'whatsapp', 'telegrambot', 'twitterbot',
            'embedly', 'quora link preview', 'outbrain', 'vkshare', 'yandex',
            'baidu', 'sogou', 'duckduck', 'applebot', 'google', 'bing',
            // Script clients and automation runtimes seen hitting the site.
            'powershell', 'winhttp', 'postman', 'insomnia', 'httpie', 'axios',
            'node-fetch', 'undici', 'aiohttp', 'httpx', 'guzzle', 'restsharp',
            'playwright', 'puppeteer', 'selenium', 'scrapy', 'zgrab', 'nikto',
            'nmap', 'sqlmap', 'pagespeed', 'statuscake', 'site24x7', 'freshping',
            'uptimerobot', 'screaming frog', 'gptbot', 'chatgpt-user', 'claude-web',
            'anthropic', 'perplexity', 'ccbot', 'bytespider', 'amazonbot',
            'meta-externalagent', 'linkedinbot', 'slackbot', 'discordbot',
            'prefetch', 'preview',
        ];
        foreach ($needles as $n) {
            if (strpos($uaLower, $n) !== false) return true;
        }
        return false;
    }
}
