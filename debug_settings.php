<?php
require_once 'config/database.php';
try {
    $db = db();
    $res = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    file_put_contents('settings_debug.txt', print_r($res, true));
    echo "Done";
} catch (Exception $e) {
    echo $e->getMessage();
}
