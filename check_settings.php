<?php
require_once 'config/database.php';
try {
    $db = db();
    $res = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
