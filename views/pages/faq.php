<?php
// views/pages/faq.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<section class="page-hero" style="background: var(--ink); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">HELP CENTER</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.6; letter-spacing: 0.1em; text-transform: uppercase;">Frequently Asked Questions</p>
    </div>
</section>

<section class="faq-content" style="padding: 80px 0;">
    <div class="container" style="max-width: 800px;">
        
        <div class="faq-group" style="margin-bottom: 60px;">
            <h2 style="font-family: var(--f-display); font-size: 24px; font-weight: 800; border-bottom: 2px solid var(--red); padding-bottom: 12px; margin-bottom: 32px;">ORDERING & PAYMENT</h2>
            
            <div class="faq-item" style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--ink);">How do I place an order?</h3>
                <p style="color: var(--mid-gray); line-height: 1.6;">Simply browse our shop, add items to your cart, and proceed to checkout. You will need to provide your delivery details and choose a payment method (Paystack or Bank Transfer).</p>
            </div>

            <div class="faq-item" style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--ink);">What payment methods do you accept?</h3>
                <p style="color: var(--mid-gray); line-height: 1.6;">We accept all major credit/debit cards and Mobile Money via Paystack. We also support direct bank transfers for larger orders.</p>
            </div>
        </div>

        <div class="faq-group" style="margin-bottom: 60px;">
            <h2 style="font-family: var(--f-display); font-size: 24px; font-weight: 800; border-bottom: 2px solid var(--red); padding-bottom: 12px; margin-bottom: 32px;">SHIPPING & DELIVERY</h2>
            
            <div class="faq-item" style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--ink);">How long does delivery take?</h3>
                <p style="color: var(--mid-gray); line-height: 1.6;">Delivery within Accra usually takes 1-2 business days. Nationwide delivery outside Accra takes 3-5 business days depending on your location.</p>
            </div>

            <div class="faq-item" style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--ink);">Do you offer international shipping?</h3>
                <p style="color: var(--mid-gray); line-height: 1.6;">Currently, we only ship within Ghana. We are working on expanding our reach to other West African countries soon.</p>
            </div>
        </div>

        <div class="faq-group" style="margin-bottom: 60px;">
            <h2 style="font-family: var(--f-display); font-size: 24px; font-weight: 800; border-bottom: 2px solid var(--red); padding-bottom: 12px; margin-bottom: 32px;">RETURNS & WARRANTY</h2>
            
            <div class="faq-item" style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--ink);">What is your return policy?</h3>
                <p style="color: var(--mid-gray); line-height: 1.6;">We offer a 7-day return policy for items that are defective or not as described. Please ensure the item is in its original packaging. See our full <a href="<?= APP_URL ?>/returns" style="color: var(--red); font-weight: 700;">Returns Policy</a> for details.</p>
            </div>
        </div>

        <!-- RE-INCLUDE SUPPORT BANNER -->
        <div style="margin-top: 100px; padding-top: 80px; border-top: 1px solid #EEE; text-align: center;">
            <p style="font-family: var(--f-mono); font-size: 12px; font-weight: 700; color: #BBB; text-transform: uppercase;">Still have questions?</p>
            <h2 style="font-family: var(--f-display); font-size: 32px; font-weight: 900; margin-top: 12px;">WE'RE HERE TO HELP</h2>
            <a href="<?= APP_URL ?>/contact" class="btn-primary" style="margin-top: 32px; display: inline-flex; align-items: center; background: var(--red); color: #fff; padding: 18px 40px; border-radius: 100px; font-family: var(--f-display); font-weight: 800; text-transform: uppercase;">Contact Support</a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
