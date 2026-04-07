<?php
// admin/api/user-update.php
require_once '../../config/app.php';
require_once '../../config/database.php';
require_once '../../core/Session.php';

Session::start();

// 🟢 AUTH CHECK
if (Session::get('user_role') !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$db = db();

// 🟢 PROCESS REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $value  = $_POST['value'] ?? '';

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        exit;
    }

    try {
        if ($action === 'toggle_status') {
            $newStatus = ($value === 'active' ? 1 : 0);
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            echo json_encode(['success' => true, 'new_status' => $value]);
        } 
        elseif ($action === 'update_role') {
            if (!in_array($value, ['admin', 'user'])) {
                throw new Exception("Invalid role.");
            }
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$value, $userId]);
            echo json_encode(['success' => true, 'new_role' => $value]);
        }
        elseif ($action === 'send_password_reset') {
            $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                
                // Save token
                $stmt = $db->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$user['email']]);
                $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['email'], $token, $expiresAt]);

                // Send Email
                require_once '../../core/Mailer.php';
                $resetUrl = APP_URL . '/reset-password?token=' . $token;
                Mailer::sendTemplate(
                    $user['email'], $user['full_name'],
                    'Reset your Avazonia password',
                    'password_reset',
                    ['toEmail' => $user['email'], 'toName' => $user['full_name'], 'resetUrl' => $resetUrl]
                );
                echo json_encode(['success' => true, 'message' => 'Reset email sent.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
            }
        }
        elseif ($action === 'manual_reset_password') {
            $hashed = password_hash($value, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $userId])) {
                echo json_encode(['success' => true, 'message' => 'Password manually updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
            }
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
