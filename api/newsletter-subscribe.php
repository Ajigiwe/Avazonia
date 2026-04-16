<?php
// api/newsletter-subscribe.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'PLEASE ENTER A VALID EMAIL.']);
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
        Mailer::sendTemplate(
            $email,
            'Subscriber',
            'Welcome to Avazonia! 🎉',
            'newsletter_welcome',
            ['toEmail' => $email]
        );

        // 2. Notification email to the site owner
        $adminEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';
        if (!empty($adminEmail)) {
            Mailer::sendTemplate(
                $adminEmail,
                defined('APP_NAME') ? APP_NAME . ' Admin' : 'Admin',
                'New Newsletter Subscriber: ' . $email,
                'newsletter_admin_notify',
                ['subscriberEmail' => $email]
            );
        }
    } catch (\Exception $mailErr) {
        // Log but don't break the subscription flow
        error_log('[Mailer] Newsletter email failed for ' . $email . ': ' . $mailErr->getMessage());
    }
    // ---------------------------------

    echo json_encode(['success' => true, 'message' => 'SUCCESS! WELCOME TO AVAZONIA.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SERVER ERROR. PLEASE TRY AGAIN.']);
}
