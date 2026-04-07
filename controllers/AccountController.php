<?php
// controllers/AccountController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Mailer.php';

class AccountController extends Controller {

    // LOGIN
    public function login() {
        Session::start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            if ($user && password_verify($password, $user['password_hash'])) {
                Session::set('user_id',        $user['id']);
                Session::set('user_name',      $user['full_name']);
                Session::set('user_role',      $user['role']);
                Session::set('email_verified', (bool)$user['email_verified']);
                $userModel->updateLastLogin($user['id']);
                if ($user['role'] === 'admin') {
                    $this->redirect(APP_URL . '/admin');
                } else {
                    $this->redirect(APP_URL);
                }
            } else {
                $error = "Invalid email or password.";
                $this->view('account/login', ['error' => $error]);
                return;
            }
        }
        $error   = $_GET['error']   ?? null;
        $success = $_GET['success'] ?? null;
        $this->view('account/login', ['error' => $error, 'success' => $success]);
    }

    // REGISTER
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            if (strlen($password) < 6) {
                $this->view('account/register', ['error' => 'Password must be at least 6 characters.']);
                return;
            }
            $userModel = new User();
            if ($userModel->findByEmail($email)) {
                $this->view('account/register', ['error' => 'Email already registered.']);
                return;
            }
            $token = bin2hex(random_bytes(32));
            if ($userModel->createWithVerification(
                ['email' => $email, 'password' => $password, 'full_name' => $fullName],
                $token
            )) {
                try {
                    $verifyUrl = APP_URL . '/verify-email?token=' . $token;
                    Mailer::sendTemplate(
                        $email, $fullName,
                        'Verify your Avazonia account',
                        'verification',
                        ['toEmail' => $email, 'toName' => $fullName, 'verifyUrl' => $verifyUrl]
                    );
                } catch (\Exception $e) {
                    error_log('[Mailer] Verification email failed for ' . $email . ': ' . $e->getMessage());
                }
                $user = $userModel->findByEmail($email);
                Session::set('user_id',        $user['id']);
                Session::set('user_name',      $user['full_name']);
                Session::set('user_role',      $user['role']);
                Session::set('email_verified', false);
                $this->redirect(APP_URL . '/verify-pending');
            } else {
                $this->view('account/register', ['error' => 'Registration failed. Please try again.']);
            }
        } else {
            $this->view('account/register');
        }
    }

    // LOGOUT
    public function logout() {
        Session::destroy();
        $this->redirect(APP_URL);
    }

    // ACCOUNT INDEX
    public function index() {
        if (!Session::get('user_id')) {
            $this->redirect(APP_URL . '/login');
        }
        $orderModel = new Order();
        $orders = $orderModel->findByUserId(Session::get('user_id'));
        $this->view('account/index', [
            'userName'      => Session::get('user_name'),
            'emailVerified' => Session::get('email_verified', false),
            'orders'        => $orders
        ]);
    }

    // ORDER DETAILS
    public function orderDetails($orderId) {
        if (!Session::get('user_id')) {
            $this->redirect(APP_URL . '/login');
        }
        
        $orderModel = new Order();
        $order = $orderModel->findById($orderId);
        
        // Security: Ensure order exists and belongs to the user
        if (!$order || $order['user_id'] != Session::get('user_id')) {
            $this->redirect(APP_URL . '/orders');
        }
        
        $items = $orderModel->findItemsByOrderId($orderId);
        
        $this->view('account/order_details', [
            'order' => $order,
            'items' => $items
        ]);
    }

    // ACCOUNT SETTINGS
    public function settings() {
        if (!Session::get('user_id')) $this->redirect(APP_URL . '/login');
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([Session::get('user_id')]);
        $user = $stmt->fetch();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = $_POST['full_name'] ?? $user['full_name'];
            $phone    = $_POST['phone']     ?? $user['phone'];
            $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$fullName, $phone, $user['id']])) {
                Session::set('user_name', $fullName);
                $success = "Profile updated successfully.";
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([Session::get('user_id')]);
                $user = $stmt->fetch();
                $this->view('account/settings', ['user' => $user, 'success' => $success]);
                return;
            }
        }
        $this->view('account/settings', ['user' => $user]);
    }

    // VERIFY PENDING PAGE
    public function verifyPending() {
        if (!Session::get('user_id')) $this->redirect(APP_URL . '/login');
        $this->view('account/verify_pending', ['userName' => Session::get('user_name')]);
    }

    // VERIFY EMAIL (token link)
    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            $this->redirect(APP_URL . '/login?error=' . urlencode('Invalid verification link.'));
            return;
        }
        $userModel = new User();
        $user = $userModel->findByVerificationToken($token);
        if (!$user) {
            $this->redirect(APP_URL . '/login?error=' . urlencode('This verification link is invalid or has already been used.'));
            return;
        }
        $userModel->verify($token);
        if (Session::get('user_id') == $user['id']) {
            Session::set('email_verified', true);
        }
        $this->redirect(APP_URL . '/login?success=' . urlencode('Email verified! You can now sign in.'));
    }

    // RESEND VERIFICATION EMAIL
    public function resendVerification() {
        if (!Session::get('user_id')) {
            return $this->json(['success' => false, 'message' => 'Not logged in.']);
        }
        $userModel = new User();
        $user = $userModel->findById(Session::get('user_id'));
        if (!$user || $user['email_verified']) {
            return $this->json(['success' => false, 'message' => 'Already verified.']);
        }
        $token = bin2hex(random_bytes(32));
        $userModel->setVerificationToken($user['id'], $token);
        try {
            $verifyUrl = APP_URL . '/verify-email?token=' . $token;
            Mailer::sendTemplate(
                $user['email'], $user['full_name'],
                'Verify your Avazonia account',
                'verification',
                ['toEmail' => $user['email'], 'toName' => $user['full_name'], 'verifyUrl' => $verifyUrl]
            );
            return $this->json(['success' => true, 'message' => 'Verification email sent!']);
        } catch (\Exception $e) {
            error_log('[Mailer] Resend verification failed: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'Failed to send email. Please try again.']);
        }
    }

    // FORGOT PASSWORD
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $userModel = new User();
            $user = $userModel->findByEmail($email);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                $resetModel = new PasswordReset();
                $resetModel->create($email, $token, $expiresAt);
                try {
                    $resetUrl = APP_URL . '/reset-password?token=' . $token;
                    Mailer::sendTemplate(
                        $email, $user['full_name'],
                        'Reset your Avazonia password',
                        'password_reset',
                        ['toEmail' => $email, 'toName' => $user['full_name'], 'resetUrl' => $resetUrl]
                    );
                } catch (\Exception $e) {
                    error_log('[Mailer] Password reset email failed for ' . $email . ': ' . $e->getMessage());
                }
            }
            $this->view('account/forgot_password', [
                'success' => "If that email is registered, you'll receive a reset link shortly."
            ]);
            return;
        }
        $this->view('account/forgot_password');
    }

    // RESET PASSWORD
    public function resetPassword() {
        $token = $_GET['token'] ?? ($_POST['token'] ?? '');
        if (!$token) {
            $this->redirect(APP_URL . '/forgot-password');
            return;
        }
        $resetModel = new PasswordReset();
        $resetRow   = $resetModel->findValidToken($token);
        if (!$resetRow) {
            $this->view('account/reset_password', [
                'error'   => 'This reset link is invalid or has expired. Please request a new one.',
                'token'   => '',
                'expired' => true
            ]);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password  = $_POST['password']  ?? '';
            $password2 = $_POST['password2'] ?? '';
            if (strlen($password) < 6) {
                $this->view('account/reset_password', ['error' => 'Password must be at least 6 characters.', 'token' => $token]);
                return;
            }
            if ($password !== $password2) {
                $this->view('account/reset_password', ['error' => 'Passwords do not match.', 'token' => $token]);
                return;
            }
            $userModel = new User();
            $user = $userModel->findByEmail($resetRow['email']);
            if ($user) {
                $userModel->updatePassword($user['id'], $password);
                $resetModel->markUsed($token);
                $this->redirect(APP_URL . '/login?success=' . urlencode('Password updated! Please sign in with your new password.'));
                return;
            }
        }
        $this->view('account/reset_password', ['token' => $token]);
    }
}