<?php
// api/paystack-verify.php
require_once '../config/app.php';
require_once '../config/paystack.php';
require_once '../models/Order.php';
require_once '../core/Session.php';
require_once '../core/Mailer.php';

Session::start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';
$orderId = (int)($input['order_id'] ?? 0);

if (!$reference || !$orderId) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit;
}

$verification = paystack_verify($reference);

if (($verification['data']['status'] ?? '') === 'success') {
    $orderModel = new Order();
    $paymentType = $input['payment_type'] ?? 'deposit';

    if ($paymentType === 'balance') {
        $orderModel->payBalance($orderId, $reference);
    } else {
        $orderModel->updateStatus($orderId, 'paid', $reference);
        // Clear the cart session for new orders
        Session::set('cart', []);
    }

    // Send order paid confirmation email
    try {
        $order = $orderModel->findById($orderId);
        if ($order && !empty($order['customer_email'])) {
            $items = $orderModel->getItems($orderId);
            // Send order paid email
            Mailer::sendTemplate(
                $order['customer_email'],
                $order['customer_name'],
                'Payment Confirmed — Order #' . $order['order_ref'],
                'order_paid',
                ['toEmail' => $order['customer_email'], 'toName' => $order['customer_name'], 'order' => $order]
            );
            // Also send full order confirmation if it's a new order (not balance pay)
            if ($paymentType !== 'balance') {
                Mailer::sendTemplate(
                    $order['customer_email'],
                    $order['customer_name'],
                    'Your Avazonia Order #' . $order['order_ref'] . ' is Confirmed!',
                    'order_placed',
                    [
                        'toEmail' => $order['customer_email'],
                        'toName'  => $order['customer_name'],
                        'order'   => $order,
                        'items'   => $items
                    ]
                );

                // 3. To Admin
                Mailer::sendTemplate(
                    SITE_EMAIL,
                    'Avazonia Admin',
                    'NEW PAID ORDER — #' . $order['order_ref'],
                    'order_placed',
                    [
                        'toEmail' => SITE_EMAIL,
                        'toName'  => 'Admin',
                        'order'   => $order,
                        'items'   => $items
                    ]
                );

                // 4. To Admin Dashboard Alert
                try {
                    require_once __DIR__ . '/../models/Notification.php';
                    Notification::create('new_order', "NEW PAID ORDER: #{$order['order_ref']}", ['order_id' => $orderId, 'order_ref' => $order['order_ref']]);
                } catch (\Exception $e) {
                    error_log('[Notification] Failed to create order notification: ' . $e->getMessage());
                }
            }
        }
    } catch (\Exception $e) {
        error_log('[Mailer] Order paid email failed for order #' . $orderId . ': ' . $e->getMessage());
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Payment not verified']);
}

