<?php
// git-check.php
header('Content-Type: text/plain');
echo "Git Remote:\n";
echo shell_exec("git remote -v") . "\n";
echo "Git Branch:\n";
echo shell_exec("git branch -a") . "\n";
echo "Git Log (last 5):\n";
echo shell_exec("git log -n 5 --oneline") . "\n";
echo "Git Fetching...\n";
echo shell_exec("git fetch --all") . "\n";
echo "Origin/Main Log:\n";
echo shell_exec("git log origin/main -n 5 --oneline") . "\n";
