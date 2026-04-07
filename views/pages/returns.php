<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<?php
// Default content if none set in admin
$defaultContent = "Returns, Refunds & Exchange Policy – Avazonia
At Avazonia, we are committed to customer satisfaction. Subject to our Terms and Conditions, we offer returns, exchanges, or refunds for eligible items within 7 days of purchase. Requests made after this period will not be accepted.

Eligibility for Return, Refund, or Exchange
🔹 Wrong Item Delivered
The product must remain sealed and unopened
Item must have no dents, damage, or liquid exposure
Proof of purchase (receipt) is required

🔹 Manufacturing Defects
Defective items reported within 7 days will be replaced with the same product (subject to availability)
All returned items will undergo inspection and verification
Defects reported after 7 days will be referred to the manufacturer’s service center under warranty

🔹 Incomplete Package
Missing items or accessories must be reported within 7 days for quick resolution

Refund / Chargeback Policy
🔹 Undelivered Orders
Refund requests for undelivered orders will be reviewed and approved after verification
Approved refunds will be processed within 30 days

🔹 Payment Reversals
Chargebacks for card or bank payments must be initiated through your bank
Refunds will be processed using an appropriate payment method as determined by Avazonia

Need Help?
Our support team is always available to assist you with any questions regarding our policies.
Contact Avazonia Support for assistance.";

$displayContent = $content ?: $defaultContent;
?>

<main style="padding-top: 120px; padding-bottom: 120px; background: #fff;">
    <div class="container" style="max-width: 800px;">
        <div class="sec-head reveal" style="margin-bottom: 60px;">
            <div class="sec-over" style="margin-bottom: 12px; font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.2em; color: var(--red); text-transform: uppercase;">Customer Support</div>
            <h1 class="hero-heading" style="color: var(--ink); font-size: clamp(48px, 6vw, 72px); line-height: 0.85; margin: 0; font-family: var(--f-display); font-weight: 900; letter-spacing: -0.04em;">RETURNS &<br>EXCHANGES.</h1>
        </div>

        <div class="reveal rd1" style="background: var(--off); padding: 60px; border-radius: 4px; border: 1px solid var(--light-gray);">
            <div class="policy-body" style="font-family: var(--f-body); font-size: 16px; line-height: 1.8; color: var(--ink); white-space: pre-wrap;">
                <?= htmlspecialchars($displayContent) ?>
            </div>
            
            <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid var(--light-gray); text-align: center;">
                <p style="font-family: var(--f-semi); font-size: 14px; color: var(--mid-gray); margin-bottom: 24px;">Need immediate assistance with a return?</p>
                <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" class="btn-red" style="height: 56px; padding: 0 48px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0; font-weight: 900; letter-spacing: 0.05em;">CONTACT SUPPORT</a>
            </div>
        </div>
        
        <div class="reveal rd2" style="margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
            <div style="border: 1px solid var(--light-gray); padding: 32px; border-radius: 4px;">
                <h4 style="font-family: var(--f-display); font-size: 14px; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 16px;">Fast Resolution</h4>
                <p style="font-size: 14px; color: var(--mid-gray); line-height: 1.6;">DOA items are swapped immediately upon verification. No wait times.</p>
            </div>
            <div style="border: 1px solid var(--light-gray); padding: 32px; border-radius: 4px;">
                <h4 style="font-family: var(--f-display); font-size: 14px; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 16px;">Secure Refunds</h4>
                <p style="font-size: 14px; color: var(--mid-gray); line-height: 1.6;">Approved refunds are processed to your original MoMo wallet or Bank account.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

