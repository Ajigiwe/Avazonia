<?php
require_once 'config/database.php';
try {
    $db = db();
    $res = $db->query("SELECT id, name, price_ghs FROM products WHERE name LIKE '%Samsung%'")->fetchAll();
    print_r($res);
    $res2 = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetch();
    print_r($res2);
} catch (Exception $e) {
    echo $e->getMessage();
}
