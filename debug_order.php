<?php
require_once 'config/database.php';
require_once 'models/Order.php';
try {
    $orderModel = new Order();
    $res = $orderModel->db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetch();
    file_put_contents('order_debug.txt', print_r($res, true));
    echo "Done";
} catch (Exception $e) {
    echo $e->getMessage();
}
