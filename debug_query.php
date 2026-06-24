<?php
require_once 'config/app.php';
require_once 'config/database.php';
require_once 'models/Product.php';

try {
    $productModel = new Product();
    $preorders = $productModel->getPreorderProducts(8);
    echo "Query executed successfully. Result count: " . count($preorders) . "\n";
    print_r($preorders);
} catch (Exception $e) {
    echo "Query failed with exception: " . $e->getMessage() . "\n";
}
