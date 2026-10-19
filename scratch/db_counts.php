<?php
// scratch/db_counts.php — throwaway: report status_market distribution.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
$d = db();
echo 'driver=' . $d->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
foreach (['pending_review', 'active', 'rejected', 'draft'] as $s) {
    echo $s . '=' . $d->query("SELECT COUNT(*) FROM products WHERE status_market='$s'")->fetchColumn() . PHP_EOL;
}
echo 'total=' . $d->query('SELECT COUNT(*) FROM products')->fetchColumn() . PHP_EOL;
