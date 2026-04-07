<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<main style="padding-top: 120px; padding-bottom: 120px;">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <div class="sec-over" style="margin-bottom: 12px; font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.2em; color: var(--red); text-transform: uppercase;">Protection</div>
                <h1 class="hero-heading" style="color: var(--ink); font-size: clamp(40px, 8vw, 72px); line-height: 0.85; margin: 0; font-family: var(--f-display); font-weight: 900; letter-spacing: -0.04em;">WARRANTY<br>POLICY.</h1>
            </div>
        </div>

        <style>
            .warranty-grid {
                display: grid; 
                grid-template-columns: 1.2fr 1fr; 
                gap: 80px; 
                margin-top: 80px;
            }
            @media (max-width: 1024px) {
                .warranty-grid {
                    grid-template-columns: 1fr;
                    gap: 64px;
                    margin-top: 60px;
                }
                .hero-heading { font-size: 48px !important; }
            }
        </style>

        <div class="reveal rd1 warranty-grid">
            <div>
                <p style="font-size: 20px; line-height: 1.6; color: var(--ink); margin-bottom: 40px; font-weight: 400;">
                    At AVAZONIA, we stand behind the quality of every gadget we sell. Our warranty is designed to give you total peace of mind.
                </p>
                <div style="padding: 40px; background: var(--ink); color: #fff; border-radius: 4px; box-shadow: 0 20px 60px rgba(0,0,0,0.1);">
                    <p style="font-family: var(--f-semi); font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: .15em; color: var(--red); margin-bottom: 16px;">Fast Tracking:</p>
                    <p style="font-size: 16px; opacity: 0.9; line-height: 1.7; font-weight: 300;">WhatsApp us with your order number and a video of the issue. We aim to respond and provide a resolution path in under 2 hours.</p>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 56px;">
                <div>
                  <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">12 MONTHS</div>
                  <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">Coverage</h3>
                  <p style="color: var(--mid-gray); font-size: 15px; line-height: 1.7;">Covers all manufacturing defects on electronics, including smartphones, audio gear, and laptops.</p>
                </div>
                <div>
                  <div style="font-family: var(--f-display); font-size: 14px; color: var(--red); margin-bottom: 8px;">ZERO DEBATE</div>
                  <h3 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1;">No Stories</h3>
                  <p style="color: var(--mid-gray); font-size: 15px; line-height: 1.7;">If it's a factory fault, we replace or repair it. We don't hide behind fine print. We know tech fails, we fix it.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
