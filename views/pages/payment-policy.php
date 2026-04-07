<?php
// views/pages/payment-policy.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">PAYMENT POLICY</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Secure Transactions</p>
    </div>
</section>

<section class="page-content" style="padding: 80px 0;">
    <div class="container" style="max-width: 800px; color: var(--mid-gray); line-height: 1.8;">
        <h2 style="color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px;">Accepted Methods</h2>
        <p style="margin-bottom: 32px;">We accept payments via <strong>Paystack</strong> (Cards & Mobile Money) and direct <strong>Bank Transfers</strong>. For pre-orders, a partial deposit may be required as specified on the product page.</p>

        <h2 style="color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px;">Currency</h2>
        <p style="margin-bottom: 32px;">All transactions are processed in Ghana Cedis (GHS).</p>

        <h2 style="color: var(--ink); font-family: var(--f-display); font-size: 24px; font-weight: 800; margin-bottom: 24px;">Security</h2>
        <p style="margin-bottom: 32px;">Your payment details are processed through encrypted, secure gateways. Avazonia does not store your full card details on our servers.</p>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
