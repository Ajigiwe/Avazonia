<?php
$files = ['error_log', 'admin/error_log', 'api/error_log', 'models/error_log'];
foreach ($files as $f) {
    if (file_exists($f)) {
        echo "--- $f ---\n";
        echo file_get_contents($f);
        echo "\n";
    }
}
