<?php
// force-sync.php
header('Content-Type: text/plain');
echo "🚀 Deep Sync Started...\n";

function run($cmd) {
    echo "Executing: $cmd\n";
    $out = shell_exec($cmd . " 2>&1");
    echo $out . "\n";
}

run("git fetch --all --prune");
run("git reset --hard origin/main");
run("git log -n 1 --oneline");
