<?php
// models/Order.php
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Logger.php';

class Order extends Model {
    public function __construct() {
        parent::__construct();
        // $this->ensureSchema(); // Can be called here or manually
    }

    /**
     * Ensure database schema matches code expectations
     */
    public function ensureSchema() {
        $db = $this->db;
        
        // 1. Update ENUM for orders status
        // Note: Using a safe approach that works in most MySQL versions
        $db->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded','approved','arrived','paid-full') DEFAULT 'pending'");

        // 2. Add missing columns to orders table
        $columns = [
            'is_preorder' => "TINYINT(1) DEFAULT 0 AFTER notes",
            'deposit_amount_ghs' => "DECIMAL(10,2) DEFAULT 0.00 AFTER is_preorder",
            'balance_amount_ghs' => "DECIMAL(10,2) DEFAULT 0.00 AFTER deposit_amount_ghs"
        ];
        
        foreach ($columns as $col => $def) {
            try {
                $db->exec("ALTER TABLE orders ADD COLUMN $col $def");
            } catch (Exception $e) {
                // Column likely exists
            }
        }

        // 3. Add missing columns to order_items table
        $itemColumns = [
            'is_preorder' => "TINYINT(1) DEFAULT 0",
            'deposit_paid_ghs' => "DECIMAL(10,2) DEFAULT 0.00"
        ];
        foreach ($itemColumns as $col => $def) {
            try {
                $db->exec("ALTER TABLE order_items ADD COLUMN $col $def");
            } catch (Exception $e) {
                // Column likely exists
            }
        }
    }

    public function create($data, $items) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, is_preorder, order_ref, subtotal_ghs, shipping_ghs, total_ghs, deposit_amount_ghs, balance_amount_ghs, customer_name, customer_email, customer_phone, shipping_address, shipping_city, shipping_region, delivery_zone_id, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['user_id'] ?? null,
                $data['is_preorder'] ?? 0,
                $data['order_ref'],
                $data['subtotal'],
                $data['shipping'],
                $data['total'],
                $data['deposit_amount'] ?? 0,
                $data['balance_amount'] ?? 0,
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['region'],
                $data['delivery_zone_id'],
                $data['payment_method'] ?? 'paystack',
                $data['payment_status'] ?? 'unpaid'
            ]);
            $orderId = $this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, product_name, qty, unit_price_ghs, is_preorder, deposit_paid_ghs) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['qty'],
                    $item['price_ghs'],
                    $item['is_preorder'] ?? 0,
                    $item['deposit_paid'] ?? 0
                ]);
            }

            $this->db->commit();
            
            // LOG THE PURCHASE
            Logger::log('PURCHASE', "New order placed: {$data['order_ref']} by {$data['name']}", [
                'ref' => $data['order_ref'],
                'total' => $data['total'],
                'items_count' => count($items)
            ], $orderId, 'order');

            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus($orderId, $status, $reference = null) {
        $db = $this->db;

        // Fetch order before update for email notifications
        $order = $this->findById($orderId);
        
        // --- AUTO-REFUND LOGIC ---
        if ($status === 'cancelled') {
            if ($order && in_array($order['status'], ['paid', 'processing', 'shipped', 'arrived', 'paid-full'])) {
                if (!empty($order['paystack_reference'])) {
                    require_once __DIR__ . '/../config/paystack.php';
                    
                    // Determine how much to refund
                    $refundAmount = null; // Default to Full Refund (per Paystack docs)
                    
                    // If it was a preorder and ONLY the deposit was taken, specify the amount
                    if ($order['is_preorder'] && $order['status'] !== 'paid-full') {
                        $refundAmount = (float)$order['deposit_amount_ghs'];
                    }

                    $refundRes = paystack_refund($order['paystack_reference'], $refundAmount);
                    
                    if (isset($refundRes['status']) && $refundRes['status'] === true) {
                        $status = 'refunded'; // Upgrade status to refunded
                    } else {
                        // Log error for admin audit
                        error_log("Paystack Refund Failed for Order ID: " . $orderId . " | Error: " . ($refundRes['message'] ?? 'Unknown'));
                    }
                }
            }
        }
        // -------------------------

        $sql = "UPDATE orders SET status = ?, updated_at = NOW()";
        $params = [$status];
        if ($reference) {
            $sql .= ", paystack_reference = ?";
            $params[] = $reference;
        }
        $sql .= " WHERE id = ?";
        $params[] = $orderId;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // LOG THE STATUS CHANGE
        Logger::log('STATUS_CHANGE', "Order REF: {$order['order_ref']} status updated to " . strtoupper($status), [
            'ref' => $order['order_ref'],
            'old_status' => $order['status'],
            'new_status' => $status
        ], $orderId, 'order');

        // --- EMAIL NOTIFICATIONS on status change ---
        if ($order && !empty($order['customer_email'])) {
            try {
                require_once __DIR__ . '/../core/Mailer.php';
                $emailMap = [
                    'shipped'  => ['order_shipped', 'Your order #%s has shipped! 🚚'],
                    'cancelled' => ['order_cancelled', 'Your Avazonia order #%s has been cancelled'],
                    'refunded'  => ['order_refunded', 'Refund initiated for order #%s 💰'],
                ];

                if (isset($emailMap[$status])) {
                    [$template, $subjectTpl] = $emailMap[$status];
                    Mailer::sendTemplate(
                        $order['customer_email'], 
                        $order['customer_name'], 
                        sprintf($subjectTpl, $order['order_ref']), 
                        $template, 
                        [
                            'toEmail' => $order['customer_email'],
                            'toName' => $order['customer_name'],
                            'order' => array_merge($order, ['status' => $status])
                        ]
                    );
                }
            } catch (\Exception $e) {
                error_log('[Mailer] Status email failed for order #' . $orderId . ': ' . $e->getMessage());
            }
        }
        // -------------------------------------------

        return $status;
    }

    public function findByReference($reference) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE paystack_reference = ?");
        $stmt->execute([$reference]);
        return $stmt->fetch();
    }

    public function findByUserId($userId) {
        $stmt = $this->db->prepare("SELECT orders.*, (SELECT SUM(qty) FROM order_items WHERE order_items.order_id = orders.id) as item_count 
                                  FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById($orderId) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    public function findItemsByOrderId($orderId) {
        $stmt = $this->db->prepare("SELECT oi.*, pi.url as primary_image 
                                  FROM order_items oi 
                                  LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
                                  WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /** Alias for findItemsByOrderId — used by paystack-verify and email triggers */
    public function getItems($orderId): array {
        return $this->findItemsByOrderId($orderId);
    }

    public function payBalance($orderId, $reference) {
        $stmt = $this->db->prepare("UPDATE orders SET balance_amount_ghs = 0, status = 'paid-full', paystack_reference = ? WHERE id = ?");
        return $stmt->execute([$reference, $orderId]);
    }

    public function findByOrderRef($ref) {
        // Handle both with and without '#'
        $ref = ltrim($ref, '#');
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_ref = ? OR order_ref = ?");
        $stmt->execute([$ref, "#$ref"]);
        return $stmt->fetch();
    }
}
