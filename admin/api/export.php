<?php
// admin/api/export.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    die('Unauthorized');
}

$type = $_GET['type'] ?? 'logs';
$db = db();

// Set Headers for CSV Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=avazonia_' . $type . '_export_' . date('Y-m-d_H-i') . '.csv');

// Create file pointer
$output = fopen('php://output', 'w');

// Logic based on type
switch ($type) {
    case 'orders':
        fputcsv($output, ['ID', 'Ref', 'Customer', 'Email', 'Phone', 'Total (GHS)', 'Status', 'Payment', 'Date']);
        $stmt = $db->query("SELECT id, order_ref, customer_name, customer_email, customer_phone, total_ghs, status, payment_status, created_at FROM orders ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    case 'products':
        fputcsv($output, ['ID', 'Name', 'SKU', 'Price (GHS)', 'Stock', 'Status', 'Is New', 'Is Preorder']);
        $stmt = $db->query("SELECT id, name, slug as sku, price_ghs, stock_qty, is_active, is_new_arrival, is_preorder FROM products");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    case 'users':
        fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Status', 'Verified', 'Created At']);
        $stmt = $db->query("SELECT id, full_name, email, role, is_active, email_verified, created_at FROM users");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;

    case 'logs':
    default:
        fputcsv($output, ['ID', 'Action', 'Entity', 'Entity ID', 'Description', 'IP Address', 'Date']);
        $stmt = $db->query("SELECT id, action, entity_type, entity_id, description, ip_address, created_at FROM system_logs ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
        break;
}

fclose($output);
exit;
