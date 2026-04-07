<?php
// views/account/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
require_once __DIR__ . '/../../config/paystack.php';

$user_name = Session::get('user_name') ?: 'Member';
?>

<section class="account-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">
        
        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?= APP_URL ?>" style="color: inherit; text-decoration: none;">Avazonia</a>
                <span>/</span>
                <span style="color: var(--ink);">Account</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px;">
                <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">Welcome, <?= explode(' ', $user_name)[0] ?></h1>
                <div style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?= date('l, d M Y') ?></div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">
            
            <!-- Sidebar -->
            <aside>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px; position: sticky; top: 120px;">
                    <nav style="display: flex; flex-direction: column; gap: 4px;">
                        <a href="<?= APP_URL ?>/account" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--off); border-radius: 8px; text-decoration: none; color: var(--red); font-weight: 700; font-size: 13px;">
                            <span style="font-size: 16px;">📊</span> Dashboard
                        </a>
                        <a href="<?= APP_URL ?>/account/settings" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
                            <span style="font-size: 16px;">⚙️</span> Profile Settings
                        </a>
                        <div style="margin: 12px 0; border-top: 1px solid #eee;"></div>
                        <a href="<?= APP_URL ?>/logout" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #f5222d; font-size: 13px;">
                            <span style="font-size: 16px;">👋</span> Logout
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="order-history">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
                    <h3 style="font-family: var(--f-display); font-weight: 900; font-size: 18px; text-transform: uppercase;">Recent Orders</h3>
                    <div style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray);"><?= count($orders) ?> Total</div>
                </div>

                <?php if (empty($orders)): ?>
                    <div style="padding: 80px 40px; text-align: center; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                        <span style="font-size: 40px; display: block; margin-bottom: 16px;">📦</span>
                        <p style="font-weight: 700; font-size: 16px; color: var(--ink); margin-bottom: 8px;">No orders found yet.</p>
                        <a href="<?= APP_URL ?>/shop" style="color: var(--red); font-size: 12px; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 0.05em;">Start Shopping →</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($orders as $o): ?>
                        <div class="order-card" style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr auto; align-items: center; gap: 24px; transition: 0.2s;">
                            <div>
                                <div style="font-family: var(--f-mono); font-weight: 700; font-size: 11px; color: var(--red);"><?= $o['order_ref'] ?></div>
                                <div style="font-size: 12px; color: var(--mid-gray); margin-top: 4px;"><?= date('d M Y', strtotime($o['created_at'])) ?></div>
                            </div>
                            
                            <div>
                                <div style="font-weight: 800; font-size: 16px; color: var(--ink);">₵<?= number_format($o['total_ghs'] ?? $o['total'], 2) ?></div>
                                <a href="<?= APP_URL ?>/order/invoice/<?= $o['order_ref'] ?>" target="_blank" style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); text-decoration: underline; opacity: 0.6; text-transform: uppercase;">Invoice</a>
                            </div>

                            <div>
                                <?php
                                $statusColors = [
                                    'pending' => ['bg' => '#fff7e6', 'text' => '#fa8c16'],
                                    'approved' => ['bg' => '#e6f7ff', 'text' => '#1890ff'],
                                    'processing' => ['bg' => '#f9f0ff', 'text' => '#722ed1'],
                                    'arrived' => ['bg' => '#fffbe6', 'text' => '#d4b106'],
                                    'paid-full' => ['bg' => '#f6ffed', 'text' => '#52c41a'],
                                    'delivered' => ['bg' => '#f6ffed', 'text' => '#52c41a'],
                                    'cancelled' => ['bg' => '#fff1f0', 'text' => '#f5222d']
                                ];
                                $s = $statusColors[$o['status']] ?? ['bg' => '#f5f5f5', 'text' => '#a1a1a1'];
                                ?>
                                <span style="display: inline-block; font-size: 9px; text-transform: uppercase; padding: 4px 10px; background: <?= $s['bg'] ?>; color: <?= $s['text'] ?>; border-radius: 4px; font-weight: 800; letter-spacing: 0.05em;">
                                    <?= $o['status'] ?>
                                </span>
                            </div>

                            <div>
                                <?php if($o['is_preorder'] && $o['status'] == 'arrived' && $o['balance_amount_ghs'] > 0): ?>
                                    <button onclick="payBalance(<?= $o['id'] ?>, this)" style="padding: 8px 16px; background: var(--red); color: #fff; font-size: 10px; font-weight: 700; border-radius: 6px; border: none; cursor: pointer;">
                                        PAY BALANCE: ₵<?= number_format($o['balance_amount_ghs'], 0) ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .order-card:hover { border-color: var(--ink) !important; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    aside a:hover { background: var(--off); color: var(--ink) !important; opacity: 1 !important; }

    @media (max-width: 768px) {
        .account-page { padding: 40px 0 60px !important; }
        .account-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        aside { display: none; }
        
        .order-card {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
            text-align: left;
        }

        .order-card > div { display: flex; flex-direction: column; gap: 4px; }
        
        h1 { font-size: 24px !important; }
        .order-history h3 { font-size: 16px !important; }
    }
</style>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
async function payBalance(orderId, btn) {
    const oldText = btn.innerHTML;
    btn.innerHTML = 'PREPARING...';
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('order_id', orderId);

        const res = await fetch('<?= APP_URL ?>/checkout/init-balance', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (!data.success) {
            alert(data.message);
            btn.innerHTML = oldText;
            btn.disabled = false;
            return;
        }

        const handler = PaystackPop.setup({
            key: '<?= PAYSTACK_PUBLIC_KEY ?>',
            email: data.email,
            amount: data.amount * 100,
            currency: 'GHS',
            ref: 'BAL-' + data.order_ref + '-' + Math.floor((Math.random() * 1000000000) + 1),
            metadata: {
                custom_fields: [
                    { display_name: "Order ID", variable_name: "order_id", value: orderId },
                    { display_name: "Payment Type", variable_name: "payment_type", value: "balance" }
                ]
            },
            callback: function(response) {
                verifyBalance(orderId, response.reference);
            },
            onClose: function() {
                btn.innerHTML = oldText;
                btn.disabled = false;
            }
        });
        handler.openIframe();
    } catch (err) {
        console.error(err);
        btn.innerHTML = oldText;
        btn.disabled = false;
    }
}

async function verifyBalance(orderId, reference) {
    const res = await fetch('<?= APP_URL ?>/api/paystack-verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reference: reference, order_id: orderId, payment_type: 'balance' })
    });
    const result = await res.json();
    if (result.success) location.reload();
    else alert('Verification failed: ' + result.error);
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
