<?php
/**
 * AVAZONIA ONE-SHOT MIGRATION RUNNER
 * Usage: Visit this file in your browser to apply pending migrations.
 * Runs each migration file statement-by-statement, ignoring "already exists" errors.
 * SAFETY: DELETE THIS FILE AFTER USE.
 */

header('Content-Type: text/plain');
require_once __DIR__ . '/config/app.php'; // loads .env so DB creds are available
require_once __DIR__ . '/config/database.php';

echo "🚀 Avazonia Migration Runner\n";
echo "------------------------------------------\n";

function run_sql_file_ignore_errors($db, $file) {
    echo "▶ " . basename($file) . "\n";
    $sql = file_get_contents($file);
    if ($sql === false) { echo "  ✗ Cannot read file\n"; return; }
    // Strip comment lines, split on ;
    $lines = array_filter(explode("\n", $sql), fn($l) => strpos(trim($l), '--') !== 0);
    foreach (array_filter(array_map('trim', explode(';', implode("\n", $lines)))) as $stmt) {
        if ($stmt === '') continue;
        try {
            // query() + closeCursor() instead of exec(): drains any result set the
            // statement returns so it can't poison the next statement (error 2014).
            $st = $db->query($stmt);
            if ($st instanceof PDOStatement) $st->closeCursor();
            echo "  ✓ " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 60) . "...\n";
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // Idempotent: duplicate column/table is fine
            if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate column') !== false) {
                echo "  ~ exists: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 50) . "...\n";
            } else {
                echo "  ✗ " . $msg . "\n";
            }
        }
    }
}

$files = [
    __DIR__ . '/migrations/011_rfq_quotes.sql',
    __DIR__ . '/migrations/012_seller_contact_channels.sql',
    __DIR__ . '/migrations/013_community_popups.sql',
    __DIR__ . '/migrations/014_configure_community_links.sql',
    __DIR__ . '/migrations/015_seller_phone_number.sql',
    __DIR__ . '/migrations/016_product_watermark.sql',
    __DIR__ . '/migrations/017_product_ghana.sql',
    __DIR__ . '/migrations/018_regions.sql',
    __DIR__ . '/migrations/019_page_views.sql',
];

// MySQL only: force buffered results. Migrations like 015 execute server-side
// prepared statements whose result sets (e.g. the IF(...)'s "SELECT 1" branch)
// would otherwise stay open and make every later statement fail with error 2014.
try {
    if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        db()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
} catch (Throwable $e) { /* non-fatal */ }

foreach ($files as $f) {
    if (file_exists($f)) run_sql_file_ignore_errors(db(), $f);
    else echo "✗ Missing: $f\n";
}

echo "------------------------------------------\n";
echo "✅ Migrations applied. DELETE THIS FILE from the server now.\n";
