<?php
// models/Subscriber.php

namespace Models;

use Core\Database;

class Subscriber {
    private $db;

    public function __construct() {
        $this->db = db();
    }

    /**
     * Subscribe a new email to the newsletter.
     * 
     * @param string $email
     * @return array [status => boolean, message => string]
     */
    public function subscribe($email) {
        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => 'Invalid email address.'];
        }

        try {
            // Check if already subscribed
            $stmt = $this->db->prepare("SELECT id FROM subscribers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['status' => false, 'message' => 'You are already subscribed!'];
            }

            // Insert new subscriber
            $stmt = $this->db->prepare("INSERT INTO subscribers (email) VALUES (?)");
            $stmt->execute([$email]);

            return ['status' => true, 'message' => 'Successfully subscribed!'];
        } catch (\PDOException $e) {
            return ['status' => false, 'message' => 'An error occurred. Please try again later.'];
        }
    }
}
