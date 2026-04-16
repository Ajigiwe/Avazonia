<?php
// TEMPORARY — Delete after testing
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$email = $_GET['email'] ?? '';
if (empty($email)) {
    echo json_encode(['error' => 'Provide ?email=xxx']);
    exit;
}

$db = db();
$stmt = $db->prepare("DELETE FROM newsletter_subscriptions WHERE email = ?");
$stmt->execute([$email]);

echo json_encode([
    'deleted' => $stmt->rowCount(),
    'email' => $email,
    'message' => $stmt->rowCount() > 0 ? 'Removed! You can re-subscribe now.' : 'Email not found in subscriptions.'
]);
