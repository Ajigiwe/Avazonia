<?php
/**
 * api/paystack-webhook.php
 * Automated Paystack Webhook Handler for Avazonia
 * 
 * This script listens for real-time status updates from Paystack (e.g., refund success/failure)
 * and automatically updates the order status in the database.
 */

require_once '../config/app.php';
require_once '../config/paystack.php';
require_once '../models/Order.php';

// 1. SECURITY: Verify X-Paystack-Signature
$input = file_get_contents('php://input');

if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY)) {
    http_response_code(401);
    die('Unauthorized Signature');
}

// 2. PARSE EVENT
http_response_code(200); // Acknowledge early
$event = json_decode($input, true);

if (!$event) die();

$eventName = $event['event'] ?? '';
$data = $event['data'] ?? [];
$reference = $data['transaction_reference'] ?? ($data['reference'] ?? '');

// LOG EVENT FOR AUDIT
$log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => $eventName,
    'reference' => $reference,
    'status' => $data['status'] ?? 'unknown'
];
file_put_contents(__DIR__ . '/../paystack_webhooks.log', json_encode($log) . "\n", FILE_APPEND);

// 3. HANDLE REFUND EVENTS
$orderModel = new Order();

switch ($eventName) {
    case 'refund.processed':
        // Refund was successful!
        $order = $orderModel->findByReference($reference);
        if ($order) {
            $orderModel->updateStatus($order['id'], 'refunded');
        }
        break;

    case 'refund.failed':
        // Refund failed (e.g. insufficient dashboard balance)
        // We log it, and maybe keep status as cancelled
        error_log("Paystack Webhook: Refund Failed for Reference " . $reference);
        break;
}

exit;
