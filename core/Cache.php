<?php
// core/Cache.php
// Minimal file-based cache for expensive, read-heavy queries (e.g. homepage rails).
// No Composer, no external deps — plain PHP + the writable storage/ directory.
//
// Usage:
//   $rows = Cache::remember('home.category_drops', 60, fn() => $buildRails(), ['products']);
//   Cache::flushTags(['products']);   // call after any product write
//
// Notes:
// - Swallows all I/O errors (cache is best-effort; DB is the source of truth).
// - Disabled entirely when CACHE_ENABLED=0 (or file storage is unwritable).
// - SQLite and MySQL share the same storage/ tree, so keys are driver-agnostic.

class Cache
{
    /** @var string|null Resolved cache directory (null = not probed yet). */
    private static ?string $dir = null;

    /** @var bool|null Whether caching is usable (writable + enabled). */
    private static ?bool $usable = null;

    /** Per-request memoization so one page render hits each key at most once. */
    private static array $memory = [];

    /**
     * Get a cached value, or compute + store it via $callback.
     *
     * @param string   $key      Stable cache key (e.g. 'home.category_drops').
     * @param int      $ttl      Seconds to keep the entry (default 60).
     * @param callable $callback Zero-arg callable returning the fresh value.
     * @param string[] $tags     Tag names for bulk invalidation (e.g. ['products']).
     * @return mixed The cached or freshly computed value.
     */
    public static function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        if (array_key_exists($key, self::$memory)) {
            return self::$memory[$key];
        }
        $fresh = null;
        if (!self::usable()) {
            return self::$memory[$key] = $callback();
        }
        $path = self::path($key);
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if ($raw !== false) {
                $entry = @unserialize($raw, ['allowed_classes' => false]);
                if (is_array($entry)
                    && isset($entry['expires_at'], $entry['value'])
                    && $entry['expires_at'] > time()) {
                    return self::$memory[$key] = $entry['value'];
                }
            }
        }
        $fresh = $callback();
        self::store($key, $fresh, $ttl, $tags);
        return self::$memory[$key] = $fresh;
    }

    /**
     * Store a value directly.
     */
    public static function put(string $key, $value, int $ttl, array $tags = []): void
    {
        if (!self::usable()) return;
        self::store($key, $value, $ttl, $tags);
    }

    /**
     * Delete every cache entry carrying any of the given tags.
     * Entries record their tags on write; the flush scans the (small) cache dir.
     */
    public static function flushTags(array $tags): void
    {
        if (!self::usable()) return;
        $tagSet = array_flip($tags);
        foreach (glob(self::dir() . '/*.cache') ?: [] as $path) {
            $raw = @file_get_contents($path);
            if ($raw === false) continue;
            $entry = @unserialize($raw, ['allowed_classes' => false]);
            if (!is_array($entry) || empty($entry['tags']) || !is_array($entry['tags'])) continue;
            foreach ($entry['tags'] as $entryTag) {
                if (isset($tagSet[$entryTag])) {
                    @unlink($path);
                    break;
                }
            }
        }
        // Writes and reads can share a request (admin POST pages); a flush must
        // also drop memoized values or the page keeps serving pre-flush data.
        self::$memory = [];
    }

    /**
     * Drop every cache file (admin nuke / setup scripts).
     */
    public static function flushAll(): void
    {
        if (self::$usable === false) return;
        foreach (glob(self::dir() . '/*.cache') ?: [] as $path) {
            @unlink($path);
        }
        self::$memory = [];
    }

    // ── internals ────────────────────────────────────────────────

    private static function store(string $key, $value, int $ttl, array $tags): void
    {
        $entry = [
            'value'      => $value,
            'tags'       => $tags,
            'expires_at' => time() + max(1, $ttl),
        ];
        @file_put_contents(
            self::path($key),
            serialize($entry),
            LOCK_EX
        );
    }

    private static function path(string $key): string
    {
        return self::dir() . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) . '.cache';
    }

    private static function dir(): string
    {
        if (self::$dir === null) {
            self::$dir = dirname(__DIR__) . '/storage/cache';
            if (!is_dir(self::$dir)) {
                @mkdir(self::$dir, 0775, true);
            }
        }
        return self::$dir;
    }

    private static function usable(): bool
    {
        if (self::$usable !== null) {
            return self::$usable;
        }
        self::$usable = false;
        // Opt-out switch for troubleshooting.
        $envOff = strtolower(trim(
            ($_ENV['CACHE_ENABLED'] ?? $_SERVER['CACHE_ENABLED'] ?? getenv('CACHE_ENABLED') ?: '1')
        ));
        if (in_array($envOff, ['0', 'off', 'false'], true)) {
            return self::$usable;
        }
        $dir = self::dir();
        self::$usable = is_dir($dir) && is_writable($dir);
        return self::$usable;
    }
}
