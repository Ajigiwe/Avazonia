<?php
// api/newsletter-subscribe.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$email = trim($input['email'] ?? $_POST['email'] ?? $_GET['email'] ?? $_REQUEST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false, 
        'message' => 'PLEASE ENTER A VALID EMAIL.',
        'debug_raw' => $rawInput,
        'debug_parsed' => $input,
        'debug_post' => $_POST,
        'debug_method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

try {
    $db = db();
    
    // Check if already subscribed
    $stmt = $db->prepare("SELECT id FROM newsletter_subscriptions WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'YOU ARE ALREADY ON THE LIST!']);
        exit;
    }

    // Insert new subscription
    $stmt = $db->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
    $stmt->execute([$email]);

    // --- SEND EMAIL NOTIFICATIONS ---
    try {
        require_once __DIR__ . '/../core/Mailer.php';

        // 1. Welcome email to the subscriber
        Mailer::sendTemplate($email, 'Subscriber', 'Welcome to Avazonia! 🎉', 'newsletter_welcome', ['toEmail' => $email]);

        // 2. Notification email to the site owner
        $adminEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';
        if (!empty($adminEmail)) {
            Mailer::sendTemplate($adminEmail, defined('APP_NAME') ? APP_NAME . ' Admin' : 'Admin', 'New Subscriber: ' . $email, 'newsletter_admin_notify', ['subscriberEmail' => $email]);
        }
    } catch (\Exception $mailErr) {
        error_log('[Newsletter Mail Fail] ' . $mailErr->getMessage());
    }
    // ---------------------------------

    echo json_encode(['success' => true, 'message' => 'SUCCESS! WELCOME TO AVAZONIA.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SERVER ERROR.']);
}



