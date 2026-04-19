<?php
// admin/api/notifications.php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../core/Session.php';
require_once '../../models/Notification.php';

Session::start();

// Security: Only admins can access
if (Session::get('user_role') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$notificationModel = new Notification();

if ($action === 'list') {
    $unread = $notificationModel->getUnread();
    echo json_encode(['success' => true, 'notifications' => $unread]);
} elseif ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $notificationModel->markAsRead($id);
        echo json_encode(['success' => true]);
    } else {
        $notificationModel->markAllAsRead();
        echo json_encode(['success' => true]);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
