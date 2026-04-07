<?php
// models/Logger.php
require_once __DIR__ . '/../core/Model.php';

class Logger extends Model {
    /**
     * Record a system event
     * @param string $action The type of action (e.g. 'PURCHASE', 'SETTING_UPDATE')
     * @param string $description Human readable description
     * @param array $metadata Optional technical data to store as JSON
     * @param int|null $entity_id The ID of the affected item (order_id, product_id)
     * @param string|null $entity_type The type of the affected item ('order', 'product')
     */
    public static function log($action, $description, $metadata = null, $entity_id = null, $entity_type = null) {
        $db = db();
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $meta_json = $metadata ? json_encode($metadata) : null;

        $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id, description, metadata, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $user_id,
            strtoupper($action),
            $entity_type,
            $entity_id,
            $description,
            $meta_json,
            $ip_address
        ]);
    }

    /**
     * Get recent logs
     */
    public function getRecent($limit = 100, $offset = 0, $action = null) {
        $sql = "SELECT l.*, u.full_name as user_name 
                FROM system_logs l 
                LEFT JOIN users u ON l.user_id = u.id";
        
        $params = [];
        if ($action) {
            $sql .= " WHERE l.action = ?";
            $params[] = strtoupper($action);
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ensure the system_logs table exists
     */
    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS system_logs (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id     INT UNSIGNED NULL,
            action      VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NULL,
            entity_id   INT UNSIGNED NULL,
            description TEXT NOT NULL,
            metadata    LONGTEXT NULL,
            ip_address  VARCHAR(45) NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }
}
