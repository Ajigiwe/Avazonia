<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$db = db();
$subs = $db->query("SELECT email, created_at FROM newsletter_subscriptions ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

echo "Latest 10 subscribers:\n";
foreach ($subs as $s) {
    echo "- {$s['email']} (Joined: {$s['created_at']})\n";
}
