<?php
// admin/newsletter.php
require_once '../config/app.php';
require_once '../core/Session.php';
require_once '../models/Newsletter.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$newsletterModel = new Newsletter();
$subscribers = $newsletterModel->getAll();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=avazonia_subscribers_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email Address', 'Subscription Date']);
    foreach ($subscribers as $sub) {
        fputcsv($output, [$sub['id'], $sub['email'], $sub['created_at']]);
    }
    fclose($output);
    exit;
}

$title = "Newsletter Subscribers — Avazonia";
include 'layout/header.php';
?>

<div class="admin-header">
    <div class="header-left">
        <h1>Newsletter Subscribers</h1>
        <p><?= count($subscribers) ?> users have joined your mailing list.</p>
    </div>
    <div class="header-right" style="display: flex; gap: 12px;">
        <a href="?export=csv" class="btn-red" style="height: 48px; padding: 0 24px; text-decoration: none; display: flex; align-items: center; justify-content: center; background: #00A854; border-color: #00A854;">Export to CSV</a>
        <button id="copy-all-emails" class="btn-ink" style="height: 48px; padding: 0 24px;">Copy All Emails</button>
    </div>
</div>

<div class="admin-card">
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email Address</th>
                    <th>Subscribed On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);">#<?= $sub['id'] ?></td>
                        <td>
                            <div style="font-family: var(--f-semi); color: var(--ink);"><?= htmlspecialchars($sub['email']) ?></div>
                        </td>
                        <td style="color: var(--mid-gray);">
                            <?= date('M j, Y • H:i', strtotime($sub['created_at'])) ?>
                        </td>
                        <td>
                             <button class="btn-ink copy-single" data-email="<?= htmlspecialchars($sub['email']) ?>" style="height: 32px; padding: 0 12px; font-size: 9px;">Copy</button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 100px 0;">
                            <div style="font-size: 40px; margin-bottom: 20px;">📧</div>
                            <div style="font-family: var(--f-semi); color: var(--mid-gray);">No subscribers found yet.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Copy Single
    document.querySelectorAll('.copy-single').forEach(btn => {
        btn.onclick = () => {
            const email = btn.dataset.email;
            navigator.clipboard.writeText(email);
            const originalText = btn.innerText;
            btn.innerText = 'Copied!';
            setTimeout(() => btn.innerText = originalText, 2000);
        };
    });

    // Copy All
    const allBtn = document.getElementById('copy-all-emails');
    if (allBtn) {
        allBtn.onclick = () => {
            const emails = <?= json_encode(array_column($subscribers, 'email')) ?>;
            if (emails.length === 0) return;
            navigator.clipboard.writeText(emails.join(', '));
            const originalText = allBtn.innerText;
            allBtn.innerText = 'All Emails Copied!';
            allBtn.style.background = '#00A854';
            allBtn.style.color = '#fff';
            setTimeout(() => {
                allBtn.innerText = originalText;
                allBtn.style.background = '';
                allBtn.style.color = '';
            }, 2000);
        };
    }
});
</script>

<?php include 'layout/footer.php'; ?>
