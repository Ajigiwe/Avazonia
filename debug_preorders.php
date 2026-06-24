<?php
require_once 'config/app.php';
require_once 'config/database.php';
try {
    $db = db();
    $stmt = $db->query("SELECT id, name, is_preorder, is_active, stock_qty FROM products");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
// Do not delete immediately so we can fetch it first
