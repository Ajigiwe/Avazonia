<?php
// Test the new sql_statements() splitter against every SQL file in the repo.
$src = file_get_contents(__DIR__ . '/../bin/setup.php');
if (!preg_match('/function sql_statements\(string \$sql\): array \{.*?\n\}/s', $src, $m)) {
    fwrite(STDERR, "could not extract sql_statements()\n");
    exit(1);
}
eval($m[0]);

$files = array_merge(
    glob(__DIR__ . '/../migrations/*.sql'),
    glob(__DIR__ . '/../docker/mysql/init/*.sql'),
    [__DIR__ . '/../sliders_schema.sql', __DIR__ . '/../db_email_migration.sql', __DIR__ . '/../schema.sql']
);

$total = 0; $bad = 0;
foreach ($files as $f) {
    $stmts = sql_statements(file_get_contents($f));
    $count = count($stmts);
    $total += $count;
    // Heuristics for a broken split:
    //  1. statement begins mid-comment (comment tail glued on)
    //  2. statement contains a line starting with `--` before any SQL keyword
    $issues = [];
    foreach ($stmts as $i => $s) {
        if (preg_match('/^\s*(bin\/setup\.php|runtime schema checks|safe to run)/i', $s)) {
            $issues[] = "stmt" . ($i + 1) . " starts with comment tail: " . substr($s, 0, 60);
        }
        if (preg_match('/--\s+[A-Za-z].*\n.*\b(ALTER|CREATE|INSERT|UPDATE|DELETE|SET|DROP)\b/i', $s)
            && !preg_match('/^(SELECT|INSERT)/i', $s)) {
            // comment left inside a statement body
            $issues[] = "stmt" . ($i + 1) . " contains comment line: " . substr($s, 0, 60);
        }
        if (substr_count($s, '/*') !== substr_count($s, '*/')) {
            $issues[] = "stmt" . ($i + 1) . " unbalanced block comment";
        }
        if (substr_count($s, "'") % 2 !== 0 && substr_count($s, "'") > 0) {
            // crude: odd number of single quotes => string not re-joined
            if (strpos($s, '--') === false) $issues[] = "stmt" . ($i + 1) . " odd single-quote count";
        }
    }
    $rel = str_replace('\\', '/', substr($f, strlen(__DIR__ . '/../')));
    if ($issues) {
        $bad++;
        echo "FAIL $rel\n";
        foreach ($issues as $i) echo "     - $i\n";
    } else {
        echo "ok   $rel ($count stmts)\n";
    }
}

echo "\n" . count($files) . " files, $total statements, " . ($bad ? "$bad with issues" : "0 issues") . "\n";

// Targeted regression: the two statements that used to 1064.
$rfq = sql_statements(file_get_contents(__DIR__ . '/../migrations/011_rfq_quotes.sql'));
$hit = false;
foreach ($rfq as $s) {
    if (stripos($s, 'ADD COLUMN quote_unit_price') !== false) {
        $hit = true;
        $okStart = (stripos($s, 'ALTER TABLE rfqs') === 0);
        echo "\n011 quote_unit_price stmt starts with ALTER: " . ($okStart ? "YES" : "NO (" . substr($s,0,50) . ")");
        echo "\nleading comment tail absent: " . (preg_match('/bin\/setup\.php runs/', $s) ? "NO" : "YES") . "\n";
    }
}
if (!$hit) { echo "\n011: quote_unit_price statement MISSING\n"; exit(1); }

$setNames = false;
foreach ($rfq as $s) { if (stripos($s, 'SET NAMES utf8mb4') === 0) $setNames = true; }
echo "011: SET NAMES utf8mb4 preserved (was skipped before): " . ($setNames ? "YES" : "NO") . "\n";
