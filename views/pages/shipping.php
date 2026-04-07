<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<main style="padding-top: 72px; padding-bottom: 100px;">
    <!-- Cinematic Header -->
    <section class="shipping-hero reveal" style="position: relative; height: 50vh; overflow: hidden; background: var(--ink);">
        <img src="https://images.unsplash.com/photo-1566576721346-d4a3b4eaad5b?q=80&w=2670&auto=format&fit=crop" alt="Shipping & Logistics" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.3;">
        <div class="container" style="position: absolute; bottom: 0; left: 0; right: 0; padding-bottom: 60px; z-index: 5;">
            <div class="sec-over" style="color: #fff; margin-bottom: 12px; font-family: var(--f-mono); font-size: 10px; letter-spacing: 0.2em;">LOGISTICS</div>
            <h1 class="hero-heading" style="color: #fff; line-height: 0.9; margin: 0; font-size: clamp(40px, 8vw, 100px); font-family: var(--f-display); font-weight: 900; text-transform: uppercase;">SHIPPING<br>INFORMATION.</h1>
        </div>
    </section>

    <div class="container" style="margin-top: 80px; max-width: 900px;">
        <div class="reveal rd1">
            <h2 style="font-family: var(--f-display); font-size: 32px; font-weight: 700; line-height: 1.2; margin-bottom: 48px; color: var(--ink);">Delivery & Shipping Information – Avazonia</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
                <!-- Column 1 -->
                <div>
                    <div style="margin-bottom: 40px;">
                        <h3 style="font-family: var(--f-display); font-size: 18px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--red); margin-bottom: 16px;">Delivery Time</h3>
                        <p style="font-size: 15px; line-height: 1.8; color: var(--ink); margin-bottom: 8px;"><strong>Accra:</strong> 1–3 working days</p>
                        <p style="font-size: 15px; line-height: 1.8; color: var(--ink);"><strong>Outside Accra:</strong> 3–7 working days</p>
                    </div>

                    <div style="margin-bottom: 40px;">
                        <h3 style="font-family: var(--f-display); font-size: 18px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink); margin-bottom: 16px;">Order Processing</h3>
                        <p style="font-size: 15px; line-height: 1.8; color: var(--ink);">Orders are processed and fulfilled from Monday to Friday.</p>
                    </div>

                    <div>
                        <h3 style="font-family: var(--f-display); font-size: 18px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink); margin-bottom: 16px;">Shipping Cost</h3>
                        <div style="background: rgba(229,0,26,0.05); border: 1px solid var(--red); padding: 24px; border-radius: 4px; margin-bottom: 24px;">
                            <p style="font-size: 16px; font-weight: 800; color: var(--red); margin-bottom: 4px;">FREE delivery on all orders above GHS 200</p>
                        </div>
                        <p style="font-size: 14px; color: var(--mid-gray); margin-bottom: 12px; font-weight: 600;">For orders below GHS 200, delivery fees apply:</p>
                        <ul style="list-style: none; padding: 0; font-size: 15px; line-height: 2;">
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-gray);"><span>Greater Accra</span> <span>GHS 30</span></li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-gray);"><span>Ashanti Region</span> <span>GHS 35</span></li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-gray);"><span>Brong Ahafo Region</span> <span>GHS 35</span></li>
                            <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--light-gray);"><span>Other Regions</span> <span>GHS 50</span></li>
                        </ul>
                    </div>
                </div>

                <!-- Column 2 -->
                <div style="background: var(--off); padding: 40px; border-radius: 4px;">
                    <h3 style="font-family: var(--f-display); font-size: 18px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink); margin-bottom: 24px;">Important Information</h3>
                    <ul style="padding-left: 18px; font-size: 15px; line-height: 1.8; color: var(--ink);">
                        <li style="margin-bottom: 16px;">You will receive your tracking number via email once your order is shipped.</li>
                        <li style="margin-bottom: 16px;">All orders are delivered through trusted courier services.</li>
                        <li style="margin-bottom: 16px;">Delivery is strictly door-to-door (no P.O. Box addresses allowed).</li>
                    </ul>
                    
                    <div style="margin-top: 40px; padding-top: 40px; border-top: 1px solid var(--light-gray); font-size: 14px; font-style: italic; color: var(--mid-gray);">
                        At Avazonia, we are committed to ensuring fast, reliable, and convenient delivery to your doorstep.
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

