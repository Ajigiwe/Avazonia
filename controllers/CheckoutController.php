<?php
// controllers/CheckoutController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/paystack.php';

class CheckoutController extends Controller {
    public function index() {
        $cart = Session::get('cart', []);
        if (empty($cart)) {
            $this->redirect(APP_URL . '/cart');
        }

        $subtotal = 0;
        $total_to_pay_now = 0;
        $has_preorder = false;
        
        $settings = new Settings();
        $deposit_pct = (float)$settings->get('preorder_deposit_pct', 5);
        $productModel = new Product();

        foreach ($cart as $key => $item) {
            $item_total = $item['price_ghs'] * $item['qty'];
            $subtotal += $item_total;
            
            // Check if is_preorder is in session, if not, check DB as fallback
            $is_pre = $item['is_preorder'] ?? null;
            if ($is_pre === null) {
                $p = $productModel->findById($item['product_id']);
                $is_pre = (int)($p['is_preorder'] ?? 0);
                // Sync back to session for future calls in this request
                $cart[$key]['is_preorder'] = $is_pre;
            }

            if ($is_pre) {
                $has_preorder = true;
                $total_to_pay_now += $item_total * ($deposit_pct / 100);
            } else {
                $total_to_pay_now += $item_total;
            }
        }
        Session::set('cart', $cart); // Update session with synced flags

        $shipping = (float)SHIPPING_ACCRA; // Default for Accra
        
        // Handle pre-selected zone from cart
        $zone_id = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 1;
        if ($zone_id === 2) {
            $shipping = (float)(defined('SHIPPING_KUMASI') ? SHIPPING_KUMASI : 25);
        } elseif ($zone_id === 3) {
            $shipping = (float)(defined('SHIPPING_OTHERS') ? SHIPPING_OTHERS : 60);
        } elseif ($zone_id === 4) {
            $shipping = 0.00; // Store Pickup
        }

        if ($subtotal >= SHIPPING_FREE_THRESHOLD) {
            $shipping = 0.00;
        }
        $total_to_pay_now += $shipping;
        $total_full = $subtotal + $shipping;

        $this->view('checkout/index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total_full,
            'pay_now' => $total_to_pay_now,
            'has_preorder' => $has_preorder,
            'deposit_pct' => $deposit_pct,
            'paystackKey' => PAYSTACK_PUBLIC_KEY,
            'zone_id' => $zone_id
        ]);
    }

    public function complete() {
        $data = json_decode(file_get_contents('php://input'), true);
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return $this->json(['success' => false, 'message' => 'Cart is empty.']);
        }

        // Calculate totals on backend
        $subtotal = 0;
        $total_to_pay_now = 0;
        $has_preorder = false;
        
        $settings = new Settings();
        $deposit_pct = (float)$settings->get('preorder_deposit_pct', 5);

        $processed_items = [];
        $productModel = new Product();
        foreach ($cart as $key => $item) {
            $item_total = $item['price_ghs'] * $item['qty'];
            $subtotal += $item_total;
            
            $is_pre = $item['is_preorder'] ?? null;
            if ($is_pre === null) {
                $p = $productModel->findById($item['product_id']);
                $is_pre = (int)($p['is_preorder'] ?? 0);
            }

            $processed_item = $item;
            if ($is_pre) {
                $has_preorder = true;
                $deposit = $item_total * ($deposit_pct / 100);
                $total_to_pay_now += $deposit;
                $processed_item['deposit_paid'] = $deposit;
                $processed_item['is_preorder'] = 1;
            } else {
                $total_to_pay_now += $item_total;
                $processed_item['deposit_paid'] = 0;
                $processed_item['is_preorder'] = 0;
            }
            $processed_items[$key] = $processed_item;
        }

        $shipping = (float)($data['shipping_cost'] ?? SHIPPING_ACCRA); 
        if ($subtotal >= SHIPPING_FREE_THRESHOLD) {
            $shipping = 0.00;
        }
        $total_to_pay_now += $shipping;
        $total_full = $subtotal + $shipping;
        $balance = $total_full - $total_to_pay_now;

        $orderRef = 'NX-' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);

        $payment_method = $data['payment_method'] ?? 'paystack';
        
        // Final Totals & Deposit logic
        $deposit_amt = $payment_method === 'pod' ? 0 : $total_to_pay_now;
        $balance_amt = $payment_method === 'pod' ? $total_full : $balance;
        $order_status = $payment_method === 'pod' ? 'pending' : 'pending'; // Both start as pending, but POD cart is cleared now

        // Prep data for model
        $orderData = [
            'user_id' => Session::get('user_id'),
            'is_preorder' => $has_preorder ? 1 : 0,
            'order_ref' => $orderRef,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total_full,
            'deposit_amount' => $deposit_amt,
            'balance_amount' => $balance_amt,
            'name' => $data['name'] ?? 'Guest',
            'email' => $data['email'] ?? 'guest@example.com',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'region' => $data['region'] ?? '',
            'delivery_zone_id' => (int)($data['delivery_zone_id'] ?? 1),
            'payment_method' => $payment_method,
            'payment_status' => $payment_method === 'pod' ? 'unpaid' : 'pending'
        ];

        try {
            $orderModel = new Order();
            $orderId = $orderModel->create($orderData, $processed_items);
            
            // Clear cart immediately IF it's a Pay on Delivery order
            if ($payment_method === 'pod') {
                Session::set('cart', []);
            }
            
            return $this->json([
                'success' => true, 
                'order_id' => $orderId,
                'order_ref' => $orderRef,
                'amount_ghs' => (float)$deposit_amt,
                'payment_method' => $payment_method,
                'redirect_to_confirm' => ($payment_method === 'pod'),
                'message' => 'Order created.'
            ]);
        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function initBalancePayment() {
        if (!Session::get('user_id')) {
            return $this->json(['success' => false, 'message' => 'Unauthorized.']);
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if (!$orderId) {
            return $this->json(['success' => false, 'message' => 'Order ID required.']);
        }

        $orderModel = new Order();
        $order = $orderModel->findById($orderId);

        if (!$order || $order['user_id'] != Session::get('user_id')) {
            return $this->json(['success' => false, 'message' => 'Order not found or access denied.']);
        }

        if ($order['balance_amount_ghs'] <= 0) {
            return $this->json(['success' => false, 'message' => 'No balance due on this order.']);
        }

        return $this->json([
            'success' => true,
            'amount' => (float)$order['balance_amount_ghs'],
            'email' => $order['customer_email'],
            'order_ref' => $order['order_ref'],
            'payment_type' => 'balance'
        ]);
    }
}
