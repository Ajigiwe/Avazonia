<?php
// config/database.php
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $db   = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'avazonia';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?: '';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    return $pdo;
}
