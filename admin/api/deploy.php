<?php
// admin/api/deploy.php
// Deployment engine endpoint. Requires an admin session + CSRF on every call.
// `deploy` additionally requires the admin password (step-up auth) and the
// typed confirmation phrase, because deploying is remote code execution by
// definition — a hijacked admin session must not be enough on its own.
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Deployer.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Logger.php';

Session::start();

// 1. Admin access only.
if (Session::get('user_role') !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access Attempt']);
    exit;
}

// 2. CSRF.
require_once __DIR__ . '/../_csrf_check.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$action = (string)($input['action'] ?? '');

const DEPLOY_LOCK_AFTER = 5;
const DEPLOY_LOCK_MINS  = 15;

/**
 * Re-verify the administrator's password. Throttled per session so a hijacked
 * session cannot brute-force its way to a deploy.
 */
function verifyDeployPassword(string $password): bool {
    Session::start();

    $lockedUntil = (int)(Session::get('deploy_locked_until') ?? 0);
    if ($lockedUntil > time()) {
        $mins = (int)ceil(($lockedUntil - time()) / 60);
        echo json_encode(['success' => false, 'locked' => true,
            'message' => "Too many failed password attempts. Locked for {$mins} more minute(s)."]);
        exit;
    }

    $userModel = new User();
    $admin = $userModel->findById((int)Session::get('user_id'));

    if ($password === '' || !$admin || !password_verify($password, $admin['password_hash'])) {
        $fails = (int)(Session::get('deploy_fail_count') ?? 0) + 1;
        Session::set('deploy_fail_count', $fails);
        if ($fails >= DEPLOY_LOCK_AFTER) {
            Session::set('deploy_locked_until', time() + DEPLOY_LOCK_MINS * 60);
            Session::set('deploy_fail_count', 0);
            Logger::log('DEPLOY_LOCKOUT', 'Deployment locked after ' . DEPLOY_LOCK_AFTER . ' failed password attempts.');
            echo json_encode(['success' => false, 'locked' => true,
                'message' => 'Too many failed password attempts. Locked for ' . DEPLOY_LOCK_MINS . ' minutes.']);
            exit;
        }
        echo json_encode(['success' => false,
            'message' => 'Incorrect admin password. ' . (DEPLOY_LOCK_AFTER - $fails) . ' attempt(s) remaining.']);
        exit;
    }

    Session::set('deploy_fail_count', 0);
    return true;
}

try {
    $deployer = new Deployer();

    switch ($action) {

        // ── Read-only inspection ──
        case 'preflight':
            $report = $deployer->preflight();
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        // ── Execute ──
        case 'deploy':
            verifyDeployPassword((string)($input['password'] ?? ''));

            if (trim((string)($input['confirm_phrase'] ?? '')) !== 'RESET') {
                Logger::log('DEPLOY_BLOCKED', 'Deployment refused: confirmation phrase was not RESET.');
                echo json_encode(['success' => false,
                    'message' => 'Confirmation phrase must be exactly RESET.']);
                exit;
            }

            $force = !empty($input['force']);

            // Re-run preflight so the conditions are verified at execute time,
            // not merely when the page was rendered.
            $report = $deployer->preflight();
            if (empty($report['ready'])) {
                Logger::log('DEPLOY_BLOCKED', 'Deployment refused: ' . $report['reason']);
                echo json_encode(['success' => false, 'report' => $report, 'message' => $report['reason']]);
                exit;
            }

            $result = $deployer->deploy($report, $force);
            echo json_encode(['success' => true,
                'message' => 'Deployed ' . $result['after'] . ' (' . $result['before'] . ' → ' . $result['after'] . '). '
                           . 'OPcache cleared and application cache flushed.',
                'report' => $deployer->preflight()]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid Action']);
            break;
    }
} catch (DeployException $e) {
    Logger::log('DEPLOY_BLOCKED', 'Deployment refused: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Deployment Engine Error: ' . $e->getMessage()]);
}
