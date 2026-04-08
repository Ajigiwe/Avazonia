<?php
/**
 * AVAZONIA SECURE DEPLOYMENT UTILITY
 * Usage: Visit this file in your browser to force-sync with GitHub.
 * SAFETY: DELETE THIS FILE AFTER USE.
 */

header('Content-Type: text/plain');

echo "🚀 Avazonia Deployment Utility Started...\n";
echo "------------------------------------------\n";

function run_git_cmd($cmd) {
    echo "Executing: $cmd\n";
    $output = shell_exec($cmd . " 2>&1");
    echo $output . "\n";
    return $output;
}

// 1. Fetch the latest changes from GitHub
run_git_cmd("git fetch --all");

// 2. FORCE the server to match the GitHub 'main' branch exactly
// This will discard any local conflicts in app.php or database.php
run_git_cmd("git reset --hard origin/main");

// 3. Optional: Verify current status
run_git_cmd("git status");

echo "------------------------------------------\n";
echo "✅ Deployment Sync Complete! Please check your site now.\n";
echo "⚠️ IMPORTANT: Delete this deploy.php file from your server immediately for security.\n";
