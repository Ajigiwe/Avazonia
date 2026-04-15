<?php
// models/Newsletter.php
require_once __DIR__ . '/../core/Model.php';

class Newsletter extends Model {
    /**
     * Subscribe a new email
     */
    public function subscribe($email) {
        $this->ensureTable();
        
        // Check if already subscribed
        $stmt = $this->db->prepare("SELECT id FROM newsletter_subscriptions WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => true, 'message' => 'You are already subscribed!'];
        }

        $stmt = $this->db->prepare("INSERT INTO newsletter_subscriptions (email) VALUES (?)");
        if ($stmt->execute([$email])) {
            return ['success' => true, 'message' => 'Subscription successful!'];
        }
        return ['success' => false, 'message' => 'Database error. Please try again.'];
    }

    /**
     * Get all subscribers
     */
    public function getAll() {
        $this->ensureTable();
        return $this->db->query("SELECT * FROM newsletter_subscriptions ORDER BY created_at DESC")->fetchAll();
    }

    /**
     * Ensure table exists
     */
    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }
}
