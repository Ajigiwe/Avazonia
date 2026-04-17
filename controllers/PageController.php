<?php
// controllers/PageController.php

class PageController {
    public function about() {
        require_once __DIR__ . '/../views/pages/about.php';
    }

    public function shipping() {
        require_once __DIR__ . '/../models/Settings.php';
        $settings = new Settings();
        $content = $settings->get('shipping_policy');
        require_once __DIR__ . '/../views/pages/shipping.php';
    }

    public function warranty() {
        require_once __DIR__ . '/../views/pages/warranty.php';
    }

    public function returns() {
        require_once __DIR__ . '/../models/Settings.php';
        $settings = new Settings();
        $content = $settings->get('returns_policy');
        require_once __DIR__ . '/../views/pages/returns.php';
    }

    public function faq() {
        require_once __DIR__ . '/../views/pages/faq.php';
    }

    public function terms() {
        require_once __DIR__ . '/../views/pages/terms.php';
    }

    public function privacy() {
        require_once __DIR__ . '/../views/pages/privacy.php';
    }

    public function paymentPolicy() {
        require_once __DIR__ . '/../views/pages/payment-policy.php';
    }

    public function trackOrder() {
        require_once __DIR__ . '/../views/pages/track-order.php';
    }

    public function contact() {
        $success = false;
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $subject && $message) {
                require_once __DIR__ . '/../core/Mailer.php';
                require_once __DIR__ . '/../config/app.php';
                
                $adminEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';
                $appName = defined('APP_NAME') ? APP_NAME : 'Avazonia';
                
                if (!empty($adminEmail)) {
                    try {
                        Mailer::sendTemplate($adminEmail, $appName . ' Admin', 'New Contact Request: ' . $subject, 'contact_admin_notify', [
                            'customerName' => $name,
                            'customerEmail' => $email,
                            'subjectLine' => $subject,
                            'messageBody' => $message
                        ]);
                        $success = true;
                    } catch (Exception $e) {
                        error_log('[Contact Form] Mail error: ' . $e->getMessage());
                        $error = 'Sorry, there was a problem sending your message. Please try again later.';
                    }
                } else {
                     $error = 'System configuration error. Please contact us directly.';
                }
            } else {
                $error = 'Please fill out all required fields with a valid email.';
            }
        }
        require_once __DIR__ . '/../views/pages/contact.php';
    }
}
