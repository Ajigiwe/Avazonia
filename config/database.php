<?php
// config/database.php
function db(): PDO {
    static $pdo;
    static $driver;
    if (!$pdo) {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $db   = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'avazonia';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $charset = 'utf8mb4';
        $sqliteFile = __DIR__ . '/../storage/database.sqlite';

        // Explicit SQLite mode (set DB_DRIVER=sqlite in .env to force)
        $requestedDriver = strtolower(trim($_ENV['DB_DRIVER'] ?? $_SERVER['DB_DRIVER'] ?? getenv('DB_DRIVER') ?: ''));

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_TIMEOUT            => 2,
        ];

        // If SQLite explicitly requested, go straight there
        if ($requestedDriver === 'sqlite' || $requestedDriver === 'sqlite3') {
            $pdo = db_sqlite($sqliteFile);
            return $pdo;
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $driver = 'mysql';
        } catch (\PDOException $e) {
            // Local DX: if host=db but we're on host (no Docker DNS), retry on 127.0.0.1
            $isDockerHost = ($host === 'db');
            $isDnsError = stripos($e->getMessage(), 'getaddrinfo') !== false || stripos($e->getMessage(), 'Unknown host') !== false || stripos($e->getMessage(), 'php_network_getaddresses') !== false;
            if ($isDockerHost && $isDnsError) {
                $fallbackDsn = "mysql:host=127.0.0.1;dbname=$db;charset=$charset;port=$port";
                try {
                    $pdo = new PDO($fallbackDsn, $user, $pass, $options);
                    $driver = 'mysql';
                    return $pdo;
                } catch (\PDOException $e2) {
                    // fall through to SQLite fallback below
                    $e = $e2;
                }
            }
            // No MySQL available — fall back to SQLite file (zero-config local dev)
            // This lets `php -S` work without installing MariaDB.
            if (extension_loaded('pdo_sqlite')) {
                error_log("[database] MySQL unavailable ({$e->getMessage()}) — falling back to SQLite ($sqliteFile)");
                $pdo = db_sqlite($sqliteFile);
                return $pdo;
            }
            throw new \PDOException(
                $e->getMessage() . " | Hint: MySQL not running. Install Docker Desktop + `docker compose up -d db`, or start XAMPP MySQL, or set DB_DRIVER=sqlite in .env for file-based dev.",
                (int)$e->getCode()
            );
        }
    }
    return $pdo;
}

function db_driver(): string {
    // Call db() first to ensure driver is set
    try { db(); } catch (Throwable $e) {}
    global $pdo;
    // Peek at actual driver via PDO attribute
    try {
        $pdo = db();
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    } catch (Throwable $e) {
        return 'mysql';
    }
}

function db_sqlite(string $file): PDO {
    static $sqlite;
    if ($sqlite) return $sqlite;
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $needsInit = !file_exists($file) || filesize($file) < 100;
    $sqlite = new PDO("sqlite:$file", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    $sqlite->exec("PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;");
    if ($needsInit) {
        $schema = __DIR__ . '/../docker/mysql/init/01-schema.sqlite.sql';
        if (file_exists($schema)) {
            $sql = file_get_contents($schema);
            $sqlite->exec($sql);
            $seed = __DIR__ . '/../docker/mysql/init/02-seed.sql';
            if (file_exists($seed)) {
                // 02-seed.sql is MySQL syntax — run a SQLite-compatible subset
                // We execute it statement-by-statement, skipping MySQL-only directives
                $seedSql = file_get_contents($seed);
                // Strip MySQL-specific lines (LOCK TABLES, etc. not in our 02-seed anyway — it's simple INSERTs)
                // Our 02-seed.sql is already compatible (simple INSERT IGNORE)
                // Convert INSERT IGNORE -> INSERT OR IGNORE for SQLite
                $seedSql = str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $seedSql);
                try { $sqlite->exec($seedSql); } catch (Throwable $e) { error_log("[database] SQLite seed warning: " . $e->getMessage()); }
            }
        }
    }
    return $sqlite;
}
