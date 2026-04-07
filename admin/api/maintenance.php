<?php
// admin/api/maintenance.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../models/Maintenance.php';
require_once __DIR__ . '/../../models/Logger.php';

Session::start();

// 1. Security Check: Admin Access
if (Session::get('user_role') !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access Attempt']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$action = $input['action'] ?? '';
$filename = $input['filename'] ?? '';
$password = $input['password'] ?? '';

try {
    $maintenance = new Maintenance();

    // Verification wrapper for high-risk actions
    $verifyPassword = function($pass) {
        $db = db();
        $adminId = Session::get('user_id');
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();
        return $user && password_verify($pass, $user['password_hash']);
    };

    switch ($action) {
        case 'backup':
            $newFile = $maintenance->createBackup();
            Logger::log('BACKUP_CREATE', "Admin manual backup generated: $newFile");
            echo json_encode(['success' => true, 'message' => 'System Backup Completed.']);
            break;

        case 'restore':
            if (!$verifyPassword($password)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Security Credentials.']);
                break;
            }
            $maintenance->restoreFromBackup($filename);
            Logger::log('BACKUP_RESTORE', "System restored from backup: $filename");
            echo json_encode(['success' => true, 'message' => 'System Restoration Successful.']);
            break;

        case 'wipe':
            if (!$verifyPassword($password)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Security Credentials.']);
                break;
            }
            $maintenance->wipeData();
            Logger::log('SYSTEM_WIPE', "Administrative Wipe Performed. Auto-backup generated.");
            echo json_encode(['success' => true, 'message' => 'System Data Successfully Reset.']);
            break;

        case 'delete':
            $maintenance->deleteBackup($filename);
            echo json_encode(['success' => true, 'message' => 'Backup file removed.']);
            break;

        case 'list':
            $list = $maintenance->getBackups();
            echo json_encode(['success' => true, 'backups' => $list]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid Action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Maintenance Engine Error: ' . $e->getMessage()]);
}
