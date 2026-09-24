<?php
require 'config/database.php';
$db = db();
$stmt = $db->query('SHOW COLUMNS FROM products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $db->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
