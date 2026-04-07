<?php
// admin/logs.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';
require_once '../models/Logger.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$logger = new Logger();
$logs = $logger->getRecent(100);

$title = "System Audit Trail — Avazonia";
include 'layout/header.php';
?>

<style>
    .log-action-badge {
        font-family: var(--f-mono); font-size: 9px; font-weight: 800;
        padding: 4px 8px; border-radius: 4px; text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .action-purchase { background: #e6f7ec; color: #00a854; }
    .action-status_change { background: #fff7e6; color: #fa8c16; }
    .action-setting_update { background: #e6f4ff; color: #1890ff; }
    .action-error { background: #fff1f0; color: #f5222d; }
    .action-default { background: #f5f5f5; color: #8c8c8c; }
</style>

<div class="admin-header" style="margin-bottom: 48px;">
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <h1 style="font-size: 64px; line-height: 0.9; margin: 0; letter-spacing: -0.04em;">System<br>Audit Trail</h1>
        <div style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); margin-top: 12px;">Active Intelligence Logging • Real-time Monitoring</div>
    </div>
    <div style="display: flex; gap: 16px;">
        <a href="api/export.php?type=logs" class="btn-ink" style="height: 50px; padding: 0 32px; border-radius: 0; display: flex; align-items: center; gap: 12px; font-weight: 800;">
            📥 EXPORT AUDIT LOG
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Administrative Activity</div>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">Showing last 100 entries</div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Event Type</th>
                <th>Entity</th>
                <th>Description</th>
                <th>User / IP</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): 
                $actionClass = 'action-' . strtolower($log['action']);
            ?>
            <tr>
                <td>
                    <span class="log-action-badge <?= $actionClass ?>"><?= $log['action'] ?></span>
                </td>
                <td>
                    <?php if ($log['entity_type']): ?>
                        <div style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mid-gray); text-transform: uppercase;">
                            <?= $log['entity_type'] ?> #<?= $log['entity_id'] ?>
                        </div>
                    <?php else: ?>
                        <span style="opacity: 0.3;">—</span>
                    <?php endif; ?>
                </td>
                <td style="max-width: 400px; line-height: 1.4;">
                    <div style="font-weight: 700; color: var(--ink);"><?= $log['description'] ?></div>
                    <?php if ($log['metadata']): 
                        $meta = json_decode($log['metadata'], true);
                        if ($meta): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                                <?php foreach ($meta as $mk => $mv): ?>
                                    <?php if ($mk === 'keys') continue; // Hide the massive list of keys for setting updates ?>
                                    <?php if (is_array($mv)): ?>
                                        <?php foreach ($mv as $iv): ?>
                                            <div style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray);">
                                                <span style="font-weight: 800; opacity: 0.6;"><?= strtoupper($mk) ?>:</span> <?= htmlspecialchars($iv) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray);">
                                            <span style="font-weight: 800; opacity: 0.6;"><?= strtoupper($mk) ?>:</span> <?= htmlspecialchars($mv) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); margin-top: 4px; opacity: 0.7;">
                                <?= htmlspecialchars(substr($log['metadata'], 0, 100)) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight: 800;"><?= $log['user_name'] ?: 'SYSTEM' ?></div>
                    <div style="font-family: var(--f-mono); font-size: 10px; opacity: 0.5;"><?= $log['ip_address'] ?></div>
                </td>
                <td style="white-space: nowrap; font-family: var(--f-mono); font-size: 11px;">
                    <?= date('M d, H:i:s', strtotime($log['created_at'])) ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" style="padding: 100px 0; text-align: center; opacity: 0.3; font-family: var(--f-display); font-size: 18px; font-weight: 800;">
                        NO ACTIVITY RECORDED YET
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'layout/footer.php'; ?>
