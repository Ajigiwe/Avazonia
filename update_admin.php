<?php
/**
 * AVAZONIA PASSWORD RESET UTILITY
 * Safety: Run this script and then delete it immediately.
 */

require_once 'config/app.php';
require_once 'config/database.php';

header('Content-Type: text/plain');

try {
    $db = db();
    $new_password = 'Avazonia@$$1';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@avazonia.com' AND role = 'admin'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Success: Admin password updated successfully.\n";
    } else {
        echo "⚠️ Notice: No admin user with email 'admin@avazonia.com' was updated (perhaps already set or email mismatch).\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
