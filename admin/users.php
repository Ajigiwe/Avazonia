<?php
// admin/users.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();

// 🟢 FILTERS & SEARCH
$search = $_GET['q'] ?? '';
$where = $search ? "WHERE u.email LIKE ? OR u.full_name LIKE ?" : "";
$params = $search ? ["%$search%", "%$search%"] : [];

// 🟢 FETCH USERS WITH SPEND & ORDERS
$query = "
    SELECT u.*, 
           COUNT(o.id) as total_orders, 
           IFNULL(SUM(o.total_ghs), 0) as total_spend
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$title = "Account Management — Avazonia";
include 'layout/header.php';
?>

<style>
    .status-active { background: #e6f7ec; color: #00a854; }
    .status-suspended { background: #fff1f0; color: #f5222d; }
    .role-admin { background: #f0f5ff; color: #1d39c4; border: 1px solid #adc6ff; }
    .role-user { background: #fafafa; color: #8c8c8c; border: 1px solid #d9d9d9; }
    
    .search-bar { width: 100%; max-width: 400px; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px; font-family: var(--f-mono); font-size: 11px; margin-bottom: 24px; }
    .action-btn { 
        padding: 6px 12px; font-size: 9px; font-weight: 800; text-transform: uppercase; 
        cursor: pointer; border-radius: 4px; border: 1px solid var(--light-gray); 
        background: #fff; transition: all 0.2s;
    }
    .action-btn:hover { background: var(--off); border-color: var(--ink); }
</style>

<div class="admin-header">
    <div>
        <h1 style="font-size: 32px; letter-spacing: -0.02em; margin-bottom: 8px;">Account<br>Management</h1>
        <p style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);">Managing <?= count($users) ?> registered users in database.</p>
    </div>
    
    <form action="" method="GET">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="SEARCH BY NAME OR EMAIL..." class="search-bar">
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">User Registry</div>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User Details</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Stats</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="font-weight: 800; font-size: 14px;"><?= htmlspecialchars($u['full_name']) ?></div>
                        <div style="font-family: var(--f-mono); font-size: 10px; opacity: 0.6;"><?= htmlspecialchars($u['email']) ?></div>
                    </td>
                    <td>
                        <span class="status-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                    </td>
                    <td>
                        <span class="status-badge <?= $u['is_active'] ? 'status-active' : 'status-suspended' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Suspended' ?>
                        </span>
                        <?php if ($u['email_verified']): ?>
                            <span style="font-size: 14px; margin-left: 4px; color: #00a854;">✔</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 800; color: var(--ink);">₵<?= number_format($u['total_spend'], 2) ?></div>
                        <div style="font-size: 10px; color: var(--mid-gray);"><?= $u['total_orders'] ?> Orders</div>
                    </td>
                    <td style="font-size: 11px; opacity: 0.7;">
                        <?= date('M d, Y', strtotime($u['created_at'])) ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="action-btn" onclick="updateUser(<?= $u['id'] ?>, 'toggle_status', '<?= $u['is_active'] ? 'suspended' : 'active' ?>')">
                                <?= $u['is_active'] ? 'Suspend' : 'Activate' ?>
                            </button>
                            
                            <?php if ($u['id'] != Session::get('user_id')): // Prevent self-demote ?>
                                <button class="action-btn" onclick="updateUser(<?= $u['id'] ?>, 'update_role', '<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>')">
                                    <?= $u['role'] === 'admin' ? 'Demote' : 'Promote' ?>
                                </button>
                            <?php endif; ?>
    
                            <button class="action-btn" style="color: var(--red); border-color: rgba(229,0,26,0.2);" onclick="updateUser(<?= $u['id'] ?>, 'send_password_reset', '')">
                                Reset Link
                            </button>
                            <button class="action-btn" onclick="manualReset(<?= $u['id'] ?>)">
                                Set Manually
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function manualReset(userId) {
    const newPass = prompt("Enter new password (min 6 chars):");
    if (!newPass || newPass.length < 6) {
        if (newPass) alert("Password too short.");
        return;
    }
    updateUser(userId, 'manual_reset_password', newPass);
}

async function updateUser(userId, action, value) {
    if (!confirm(`Are you sure you want to perform this action (${action}: ${value})?`)) return;

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    formData.append('value', value);

    try {
        const response = await fetch('api/user-update.php', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        
        if (res.success) {
            window.location.reload();
        } else {
            alert('Error: ' + res.message);
        }
    } catch (err) {
        console.error(err);
        alert('An unexpected error occurred.');
    }
}
</script>

<?php include 'layout/footer.php'; ?>
