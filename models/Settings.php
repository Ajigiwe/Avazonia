<?php
// models/Settings.php
require_once __DIR__ . '/../core/Model.php';

class Settings extends Model {
    /**
     * Get a setting value by key
     */
    public function get($key, $default = null) {
        $stmt = $this->db->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $res = $stmt->fetch();
        return $res ? $res['value'] : $default;
    }

    /**
     * Get all settings as an associative array
     */
    public function all() {
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Set a setting value
     */
    public function set($key, $value) {
        $stmt = $this->db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        return $stmt->execute([$key, $value, $value]);
    }

    /**
     * Ensure the settings table exists
     */
    public function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            `key`   VARCHAR(100) PRIMARY KEY,
            `value` TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }
}
