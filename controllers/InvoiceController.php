<?php
// controllers/InvoiceController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../core/Session.php';

class InvoiceController extends Controller {
    public function show($orderRef) {
        $orderModel = new Order();
        $db = db();
        
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_ref = ?");
        $stmt->execute([$orderRef]);
        $order = $stmt->fetch();

        if (!$order) {
            die("Order not found.");
        }

        // Permission check
        $userId = Session::get('user_id');
        $userRole = Session::get('user_role');

        if ($userRole !== 'admin' && $order['user_id'] != $userId) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $items = $orderModel->findItemsByOrderId($order['id']);
        
        $this->view('order/invoice', [
            'order' => $order,
            'items' => $items
        ]);
    }
}
