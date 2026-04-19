<?php
require_once 'config/database.php';
try {
    $db = db();
    $res = $db->query("DESCRIBE orders")->fetchAll();
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
