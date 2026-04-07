<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>
<?php require_once __DIR__ . '/../../models/Settings.php'; ?>
<?php $settingsModel = new Settings(); ?>

<style>
    .contact-main { padding-top: 120px; padding-bottom: 100px; }
    .contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 80px; margin-top: 80px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    
    @media (max-width: 1024px) {
        .contact-grid { grid-template-columns: 1fr; gap: 60px; margin-top: 40px; }
        .contact-main { padding-top: 80px; padding-bottom: 80px; }
    }
    
    @media (max-width: 768px) {
        .form-row { grid-template-columns: 1fr; }
        .hero-heading { font-size: 42px !important; }
        .contact-main { padding-top: 60px; }
    }
</style>

<main class="contact-main">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <div class="sec-over">Get in Touch</div>
                <h1 class="hero-heading" style="color: var(--ink); font-size: clamp(48px, 10vw, 120px); margin-bottom: 0;">Contact Us.</h1>
            </div>
        </div>

        <div class="reveal rd1">
            <div class="contact-split-hero">
                <!-- LEFT: THE FORM (RED) -->
                <div class="contact-box-red">
                    <div>
                        <div class="help-icon-circle">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                        <h2 class="help-title">Need Any<br>Help?</h2>
                        <p class="help-subtitle" style="margin-top: 12px;">We are here to help you with any question.</p>
                    </div>

                    <?php if (isset($success) && $success): ?>
                        <div style="padding: 40px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px;">
                            <h3 style="font-family: var(--f-display); font-size: 24px; color: #fff; margin-bottom: 8px;">Message Sent.</h3>
                            <p style="color: #fff; opacity: 0.8; font-size: 14px;">Our team will get back to you within 2 business hours.</p>
                        </div>
                    <?php else: ?>
                        <form action="<?= APP_URL ?>/contact" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                            <div>
                                <input type="text" name="name" placeholder="Name *" required class="contact-input-white">
                            </div>
                            <div>
                                <input type="email" name="email" placeholder="E-mail *" required class="contact-input-white">
                            </div>
                            <div>
                                <input type="text" name="subject" placeholder="Subject *" required class="contact-input-white">
                            </div>
                            <div>
                                <textarea name="message" placeholder="Message *" required class="contact-input-white contact-textarea-white"></textarea>
                            </div>
                            <button type="submit" class="contact-btn-dark">Submit</button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- RIGHT: THE INFO (WHITE) -->
                <div class="contact-box-white">
                    <div class="info-row">
                        <div class="info-icon-sq">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                        <div>
                            <div class="info-large-text">+<?= $settingsModel->get('whatsapp_number', '233240000000') ?></div>
                            <a href="https://wa.me/<?= $settingsModel->get('whatsapp_number', '233240000000') ?>" target="_blank" class="info-btn-outline" style="margin-top: 16px;">
                                Online Help
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p style="font-family: var(--f-semi); font-size: 13px; color: var(--mid-gray); margin-bottom: 8px;">Monday to Saturday — 9am - 7pm</p>
                        <a href="<?= APP_URL ?>/faq" style="font-family: var(--f-display); font-weight: 800; font-size: 15px; color: var(--ink); text-decoration: underline;">Frequently Asked Questions</a>
                    </div>

                    <div>
                        <div style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mid-gray); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px;">STORE HUB</div>
                        <div style="max-width: 320px;">
                            <h3 style="font-family: var(--f-display); font-size: 22px; font-weight: 800; color: var(--ink); margin-bottom: 8px;">Avazonia Accra HUB</h3>
                            <div style="display: flex; gap: 12px; color: var(--mid-gray); font-size: 14px; line-height: 1.6;">
                                <div style="width: 8px; height: 8px; background: var(--red); border-radius: 50%; margin-top: 6px; flex-shrink: 0;"></div>
                                <div>Spintex Road, Near Shell Signboard, Greater Accra, Ghana</div>
                            </div>
                            <a href="mailto:<?= $settingsModel->get('support_email', 'hello@avazonia.gh') ?>" style="display: inline-block; margin-top: 16px; border-bottom: 2px solid var(--ink); font-weight: 800; font-size: 14px; color: var(--ink);"><?= $settingsModel->get('support_email', 'hello@avazonia.gh') ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        $mapAddress = $settingsModel->get('store_map_address', 'Spintex Road, Near Shell Signboard, Accra, Ghana');
        if ($mapAddress): 
        ?>
        <div class="reveal rd2" style="margin-top: 100px;">
            <div class="contact-map-wrapper">
                <iframe 
                    class="contact-map-frame"
                    frameborder="0" 
                    scrolling="no" 
                    marginheight="0" 
                    marginwidth="0" 
                    src="https://maps.google.com/maps?q=<?= urlencode($mapAddress) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
                </iframe>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
