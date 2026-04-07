<?php
// api/subscribe.php

require_once '../config/database.php';
require_once '../models/Subscriber.php';

use Models\Subscriber;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get raw JSON body or form data
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? $_POST['email'] ?? '';

// Sanitize email
$email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    echo json_encode(['status' => false, 'message' => 'Email is required.']);
    exit;
}

$subscriberModel = new Subscriber();
$result = $subscriberModel->subscribe($email);

echo json_encode($result);
exit;
