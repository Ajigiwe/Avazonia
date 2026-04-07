<?php
// models/Maintenance.php

class Maintenance {
    private $db;
    private $backupDir;

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = db();
        $this->backupDir = __DIR__ . '/../backups/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Creates a full SQL dump of the database.
     */
    public function createBackup($isAuto = false) {
        $tables = [];
        $result = $this->db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Avazonia System Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Structure
            $res = $this->db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $res['Create Table'] . ";\n\n";

            // Data
            $res = $this->db->query("SELECT * FROM `$table` ");
            while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_keys($row);
                $values = array_values($row);
                $escapedValues = array_map(function($v) {
                    if ($v === null) return "NULL";
                    return $this->db->quote($v);
                }, $values);
                
                $sql .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $prefix = $isAuto ? 'auto_' : 'manual_';
        $filename = $prefix . 'backup_' . date('Y-m-d_His') . '.sql';
        file_put_contents($this->backupDir . $filename, $sql);
        
        return $filename;
    }

    /**
     * Restore database from a specific SQL file.
     */
    public function restoreFromBackup($filename) {
        $path = $this->backupDir . basename($filename);
        if (!file_exists($path)) throw new Exception("Backup file not found.");

        $sql = file_get_contents($path);
        
        // Execute multi-query
        // We use exec for the whole block; some environments limit this, 
        // but for standard Avazonia schema, it should be fine.
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->db->exec($sql);
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        return true;
    }

    /**
     * Safe Wipe: Auto-backups then truncates transactional and catalog tables.
     */
    public function wipeData() {
        // Step 1: Auto Backup
        $this->createBackup(true);

        // Step 2: Clear Tables
        $tablesToWipe = [
            'order_items', 'orders', 'reviews', 'promo_codes',
            'product_images', 'variants', 'products', 'categories', 'brands'
        ];

        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        foreach ($tablesToWipe as $table) {
            // Check if table exists before truncating to avoid errors during partial setup
            $this->db->exec("DELETE FROM `$table`;");
            $this->db->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1;");
        }
        
        // Also wipe customers (non-admins)
        $this->db->exec("DELETE FROM `users` WHERE `role` != 'admin';");
        $this->db->exec("ALTER TABLE `users` AUTO_INCREMENT = 1;");
        
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        
        return true;
    }

    public function getBackups() {
        $files = glob($this->backupDir . '*.sql');
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $backups = [];
        foreach ($files as $f) {
            $backups[] = [
                'filename' => basename($f),
                'size' => filesize($f),
                'date' => date('Y-m-d H:i:s', filemtime($f)),
                'type' => strpos(basename($f), 'auto_') === 0 ? 'Automatic' : 'Manual'
            ];
        }
        return $backups;
    }

    public function deleteBackup($filename) {
        $path = $this->backupDir . basename($filename);
        if (file_exists($path)) {
            unlink($path);
            return true;
        }
        return false;
    }
}
