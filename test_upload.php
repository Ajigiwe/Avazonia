<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['line'] = 1;
file_put_contents('test.jpg', 'mock content');
$_FILES['image'] = ['name' => 'test.jpg', 'type' => 'image/jpeg', 'tmp_name' => __DIR__.'/test.jpg', 'error' => 0, 'size' => 100];
require 'admin/api/upload-import-image.php';
