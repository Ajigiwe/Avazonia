<?php
// views/checkout/success.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$ref = $_GET['ref'] ?? 'NX-000000';
?>

<div class="success-page" style="padding: 120px 24px; text-align: center; background: #fff; min-height: 80vh; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="max-width: 600px;">
        <div style="width: 80px; height: 80px; background: rgba(22,163,74,.1); border: 2px solid #16a34a; border-radius: 100px; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; font-size: 32px; color: #16a34a; animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            ✓
        </div>
        
        <h1 style="font-family: var(--f-display); font-size: clamp(40px, 8vw, 64px); font-weight: 700; letter-spacing: -0.04em; line-height: 0.9; margin-bottom: 12px;">
            Order<br><span style="color: #16a34a;">Confirmed!</span>
        </h1>
        
        <p style="font-family: var(--f-mono); font-size: 10px; color: #aaa; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 48px;">
            Your gadgets are being prepared for delivery
        </p>
        
        <div style="background: #f9f9f9; border: 1px solid #eee; padding: 32px; border-radius: 12px; margin-bottom: 48px; position: relative; overflow: hidden;">
            <div style="font-family: var(--f-mono); font-size: 9px; color: #999; margin-bottom: 8px; text-transform: uppercase;">Order Reference</div>
            <div style="font-family: var(--f-display); font-size: 32px; font-weight: 800; color: var(--ink);">#<?= htmlspecialchars($ref) ?></div>
            
            <div style="position: absolute; top: -10px; right: -10px; font-size: 80px; opacity: 0.03; font-weight: 900; pointer-events: none;">SUCCESS</div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 16px; align-items: center;">
            <a href="<?= APP_URL ?>/shop" class="btn-ink" style="width: 100%; max-width: 300px; height: 56px; display: flex; align-items: center; justify-content: center; font-family: var(--f-display); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                Continue Shopping →
            </a>
            
            <a href="<?= APP_URL ?>/account" style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); text-decoration: underline; text-underline-offset: 4px; text-transform: uppercase; letter-spacing: 0.05em;">
                View Order Status in Account
            </a>
        </div>
    </div>
</div>

<style>
@keyframes scaleIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.success-page .btn-ink {
    background: var(--ink);
    color: #fff;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.success-page .btn-ink:hover {
    background: var(--red);
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(229, 0, 26, 0.15);
}
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
