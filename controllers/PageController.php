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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Mock submission
            $success = true;
        }
        require_once __DIR__ . '/../views/pages/contact.php';
    }
}
