<?php
// views/pages/track-order.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<section class="page-hero" style="background: var(--red); padding: 100px 0 60px; text-align: center; color: #fff;">
    <div class="container">
        <h1 style="font-family: var(--f-display); font-size: 56px; font-weight: 900; margin-bottom: 16px;">TRACK YOUR ORDER</h1>
        <p style="font-family: var(--f-mono); font-size: 13px; font-weight: 700; opacity: 0.8; letter-spacing: 0.1em; text-transform: uppercase;">Real-Time Status Updates</p>
    </div>
</section>

<section class="page-content" style="padding: 100px 0;">
    <div class="container" style="max-width: 800px; text-align: center;">
        <div id="tracking-search-area">
            <h2 style="font-family: var(--f-display); font-size: 28px; font-weight: 800; margin-bottom: 16px; color: var(--ink);">Where is my package?</h2>
            <p style="color: var(--mid-gray); margin-bottom: 48px;">Enter your Order ID and the Email or Phone number used for the order.</p>

            <form id="track-form" style="display: flex; flex-direction: column; gap: 16px; max-width: 500px; margin: 0 auto;">
                <input type="text" name="order_ref" placeholder="ORDER ID (e.g. #AV-12345)" required style="height: 64px; padding: 0 24px; border: 2px solid #EEE; border-radius: 12px; font-family: var(--f-display); font-size: 16px; font-weight: 700; outline: none; transition: 0.2s;">
                <input type="text" name="identity" placeholder="EMAIL OR PHONE NUMBER" required style="height: 64px; padding: 0 24px; border: 2px solid #EEE; border-radius: 12px; font-family: var(--f-display); font-size: 16px; font-weight: 700; outline: none; transition: 0.2s;">
                <button type="submit" class="btn-primary" style="height: 64px; background: var(--red); color: #fff; border: none; border-radius: 12px; font-family: var(--f-display); font-size: 15px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: transform 0.2s;">Track My Order</button>
            </form>
            <div id="track-error" style="margin-top: 20px; color: var(--red); font-weight: 700; display: none;"></div>
        </div>

        <div id="tracking-result-area" style="display: none;">
            <div class="tracking-result-card">
                <div class="tracking-header-info">
                    <div class="tracking-ref">
                        <h4>Order Reference</h4>
                        <h3 id="res-ref">#AV-00000</h3>
                    </div>
                    <div id="res-status-badge" class="tracking-status-badge">Processing</div>
                </div>

                <div class="tracking-timeline">
                    <div id="timeline-progress-bar" class="timeline-progress" style="width: 0%;"></div>
                    
                    <div class="tracking-step" id="step-1">
                        <div class="step-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></div>
                        <span>Pending</span>
                    </div>
                    <div class="tracking-step" id="step-2">
                        <div class="step-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
                        <span>Processing</span>
                    </div>
                    <div class="tracking-step" id="step-3">
                        <div class="step-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div>
                        <span>Shipped</span>
                    </div>
                    <div class="tracking-step" id="step-4">
                        <div class="step-dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                        <span>Delivered</span>
                    </div>
                </div>

                <div class="tracking-details-grid">
                    <div>
                        <div class="detail-label">Shipped To</div>
                        <div class="detail-val" id="res-city">---</div>
                    </div>
                    <div>
                        <div class="detail-label">Order Date</div>
                        <div class="detail-val" id="res-date">---</div>
                    </div>
                    <div style="grid-column: span 2; border-top: 1px solid #EEE; padding-top: 24px; margin-top: 8px;">
                        <div class="detail-label">Items Summary</div>
                        <div class="detail-val" id="res-items" style="font-size: 14px; opacity: 0.8;">---</div>
                    </div>
                </div>

                <div style="margin-top: 40px; text-align: center;">
                    <button onclick="location.reload()" style="background: none; border: none; color: var(--red); font-weight: 800; cursor: pointer; text-decoration: underline;">Track another order</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('track-form');
    const searchArea = document.getElementById('tracking-search-area');
    const resultArea = document.getElementById('tracking-result-area');
    const errorDiv = document.getElementById('track-error');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const order_ref = formData.get('order_ref');
        const identity = formData.get('identity');
        const btn = form.querySelector('button');

        btn.innerText = 'LOCATING...';
        btn.disabled = true;
        errorDiv.style.display = 'none';

        try {
            const response = await fetch('<?= APP_URL ?>/api/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_ref, identity })
            });

            const data = await response.json();

            if (data.success) {
                const order = data.order;
                
                // Populate data
                document.getElementById('res-ref').innerText = order.ref;
                document.getElementById('res-status-badge').innerText = order.status;
                document.getElementById('res-city').innerText = order.city;
                document.getElementById('res-date').innerText = order.date;
                document.getElementById('res-items').innerText = order.items.map(i => `${i.qty}x ${i.name}`).join(', ');

                // Handle Timeline
                const steps = [1, 2, 3, 4];
                const currentStep = order.step;
                
                steps.forEach(s => {
                    const el = document.getElementById(`step-${s}`);
                    el.classList.remove('active', 'current');
                    if (s <= currentStep) el.classList.add('active');
                    if (s === currentStep) el.classList.add('current');
                });

                // Calculate progress bar width
                const progressWidth = currentStep === 0 ? 0 : ((currentStep - 1) / (steps.length - 1)) * 100;
                document.getElementById('timeline-progress-bar').style.width = `${progressWidth}%`;

                // Show area
                searchArea.style.display = 'none';
                resultArea.style.display = 'block';
            } else {
                errorDiv.innerText = data.message;
                errorDiv.style.display = 'block';
                btn.innerText = 'Track My Order';
                btn.disabled = false;
            }
        } catch (err) {
            errorDiv.innerText = 'Unable to connect to tracking server. Please try again.';
            errorDiv.style.display = 'block';
            btn.innerText = 'Track My Order';
            btn.disabled = false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
