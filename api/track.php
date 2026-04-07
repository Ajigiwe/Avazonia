<?php
// api/track.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/Order.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$orderRef = $data['order_ref'] ?? '';
$identity = strtolower(trim($data['identity'] ?? ''));

if (empty($orderRef) || empty($identity)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both Order ID and Email/Phone.']);
    exit;
}

try {
    $orderModel = new Order();
    $order = $orderModel->findByOrderRef($orderRef);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found. Please check your Order ID.']);
        exit;
    }

    // IDENTITY VERIFICATION (Email or Phone)
    $dbEmail = strtolower(trim($order['customer_email']));
    $dbPhone = trim($order['customer_phone']);

    if ($identity !== $dbEmail && $identity !== $dbPhone) {
        echo json_encode(['success' => false, 'message' => 'Identity verification failed. Information does not match this Order ID.']);
        exit;
    }

    // FORMAT STATUS FOR TIMELINE
    $statusMap = [
        'unpaid'     => ['step' => 1, 'label' => 'Payment Pending'],
        'paid'       => ['step' => 2, 'label' => 'Payment Received'],
        'processing' => ['step' => 2, 'label' => 'Processing'],
        'shipped'    => ['step' => 3, 'label' => 'Dispatched'],
        'arrived'    => ['step' => 4, 'label' => 'Delivered'],
        'cancelled'  => ['step' => 0, 'label' => 'Cancelled'],
        'refunded'   => ['step' => 0, 'label' => 'Refunded']
    ];

    $currentStatus = $statusMap[$order['status']] ?? ['step' => 1, 'label' => strtoupper($order['status'])];

    // FETCH ITEMS
    $items = $orderModel->findItemsByOrderId($order['id']);

    echo json_encode([
        'success' => true,
        'order' => [
            'ref' => $order['order_ref'],
            'status' => $currentStatus['label'],
            'step' => $currentStatus['step'],
            'total' => $order['total_ghs'],
            'date' => date('M j, Y', strtotime($order['created_at'])),
            'city' => $order['shipping_city'],
            'items' => array_map(function($item) {
                return [
                    'name' => $item['product_name'],
                    'qty' => $item['qty']
                ];
            }, $items)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SYSTEM ERROR: ' . $e->getMessage()]);
}
