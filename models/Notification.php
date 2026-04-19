<?php
// models/Notification.php
require_once __DIR__ . '/../core/Model.php';

class Notification extends Model {
    
    public static function create($type, $message, $data = null) {
        $db = db();
        $data_json = $data ? json_encode($data) : null;

        try {
            $stmt = $db->prepare("INSERT INTO notifications (type, message, data, is_read) VALUES (?, ?, ?, 0)");
            return $stmt->execute([$type, $message, $data_json]);
        } catch (Exception $e) {
            // If table missing, try to create it
            if (strpos($e->getMessage(), 'notifications') !== false) {
                self::ensureTable();
                $stmt = $db->prepare("INSERT INTO notifications (type, message, data, is_read) VALUES (?, ?, ?, 0)");
                return $stmt->execute([$type, $message, $data_json]);
            }
            throw $e;
        }
    }

    public static function ensureTable() {
        $db = db();
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type        VARCHAR(50) NOT NULL,
            message     TEXT NOT NULL,
            data        JSON NULL,
            is_read     TINYINT(1) DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->exec($sql);
    }

    public function getUnread() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'notifications') !== false) {
                self::ensureTable();
                return []; // Return empty for the first time
            }
            throw $e;
        }
    }

    public function markAsRead($id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function markAllAsRead() {
        return $this->db->exec("UPDATE notifications SET is_read = 1");
    }
}
