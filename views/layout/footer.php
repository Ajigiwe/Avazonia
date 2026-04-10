<?php
// views/layout/footer.php
global $dbSettings;
?>

</div> <!-- End #page-wrapper -->

<footer class="footer">
    <div class="footer-inner-container">
        <div class="footer-top">
            <!-- Identity Pillar -->
            <div class="reveal">
                <a href="<?= APP_URL ?>" class="footer-logo-link">
                    <svg viewBox="0 0 680 160" class="logo-svg" role="img" xmlns="http://www.w3.org/2000/svg">
                      <defs>
                        <clipPath id="aclip-footer">
                          <polygon points="40,10 130,10 130,150 40,150"/>
                        </clipPath>
                      </defs>
                      <text x="38" y="138"
                        font-family="'Barlow Condensed', 'Arial Narrow', Arial, sans-serif"
                        font-weight="800"
                        font-size="130"
                        fill="#8B0000"
                        letter-spacing="2"
                        font-style="italic">AVAZONIA</text>
                      <line x1="96" y1="12" x2="68" y2="138"
                        stroke="white" stroke-width="9"
                        clip-path="url(#aclip-footer)"/>
                    </svg>
                </a>
                <p class="footer-newsletter-disclaimer" style="margin-bottom: 2px; font-size: 12px; opacity: 0.8;"><?= htmlspecialchars($dbSettings['footer_address'] ?? 'Q4 Gibbefish Street Beach Road Takoradi, Ghana') ?></p>
                <a href="https://maps.google.com" target="_blank" class="footer-directions-link" style="margin-bottom: 12px;">Get Directions</a>
                
                <ul class="footer-contact-list" style="margin: 0;">
                    <li class="footer-contact-item" style="margin-bottom: 2px; font-size: 12px;">Email: <?= SITE_EMAIL ?></li>
                    <li class="footer-contact-item" style="margin-bottom: 0; font-size: 12px;">Phone: +<?= WHATSAPP_NUMBER ?></li>
                </ul>

                <div class="footer-socials" style="display: flex; gap: 8px; margin-top: 16px;">
                    <?php if (!empty($dbSettings['instagram_link'])): ?>
                        <a href="<?= htmlspecialchars($dbSettings['instagram_link']) ?>" class="fsoc-round" target="_blank" title="Instagram">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($dbSettings['facebook_link'])): ?>
                        <a href="<?= htmlspecialchars($dbSettings['facebook_link']) ?>" class="fsoc-round" target="_blank" title="Facebook">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($dbSettings['tiktok_link'])): ?>
                        <a href="<?= htmlspecialchars($dbSettings['tiktok_link']) ?>" class="fsoc-round" target="_blank" title="TikTok">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.06-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.03 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96a6.66 6.66 0 0 1 4.44-1.56c.05 1.63.07 3.26.06 4.9-.3-.04-.61-.04-.9-.01-.72.07-1.41.33-1.97.77-.51.41-.86.98-1 1.62-.17.76-.1 1.72.16 2.45.38.9 1.19 1.56 2.14 1.78.36.08.74.1 1.12.08 1.05-.01 2.05-.51 2.67-1.35.3-.41.48-.9.51-1.4.07-2.31.04-4.62.04-6.93V0h-4.01z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php 
                    $waLink = $dbSettings['whatsapp_link'] ?? '';
                    if (empty($waLink) && !empty(WHATSAPP_NUMBER)) {
                        $waLink = "https://wa.me/" . WHATSAPP_NUMBER;
                    }
                    if (!empty($waLink)): 
                    ?>
                        <a href="<?= htmlspecialchars($waLink) ?>" class="fsoc-round" target="_blank" title="WhatsApp">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-2.014-.001-3.996-.51-5.746-1.474l-6.247 1.638zm6.314-3.666l.453.268c1.611.956 3.468 1.462 5.362 1.462l.006.001c5.859 0 10.627-4.767 10.63-10.627 0-2.84-.1.104-5.511-2.115-7.724-2.012-2.215-5.239-3.321-7.455-3.322-5.861 0-10.631 4.771-10.633 10.633 0 2.102.616 4.14 1.782 5.892l.294.444-1.001 3.653 3.738-.981zm12.384-1.21c-.328-.164-1.94-.956-2.241-1.066-.301-.11-.52-.164-.738.164-.219.328-.847 1.066-1.039 1.284-.192.219-.383.246-.711.082-.328-.164-1.386-.511-2.641-1.63-.977-.872-1.637-1.947-1.829-2.275-.192-.328-.02-.506.143-.669.148-.146.328-.383.492-.574.164-.192.219-.328.328-.547.11-.219.055-.41-.027-.574-.082-.164-.738-1.776-1.012-2.433-.267-.64-.54-.553-.738-.563-.192-.01-.41-.01-.629-.01s-.574.082-.875.41c-.301.328-1.148 1.121-1.148 2.732s1.176 3.169 1.34 3.388c.164.219 2.312 3.53 5.598 4.956.781.339 1.391.541 1.866.692.783.248 1.497.213 2.06.129.628-.094 1.94-.793 2.215-1.558.274-.766.274-1.42.192-1.558-.082-.137-.301-.192-.629-.356z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($dbSettings['telegram_link'])): ?>
                        <a href="<?= htmlspecialchars($dbSettings['telegram_link']) ?>" class="fsoc-round" target="_blank" title="Telegram">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.14-.26.26-.53.26l.204-2.925 5.328-4.814c.232-.206-.05-.32-.36-.11l-6.58 4.142-2.837-.887c-.615-.192-.627-.615.128-.9l11.08-4.271c.513-.192.962.115.787.892z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($dbSettings['youtube_link'])): ?>
                        <a href="<?= htmlspecialchars($dbSettings['youtube_link']) ?>" class="fsoc-round" target="_blank" title="YouTube">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505a3.017 3.017 0 0 0-2.122 2.136C0 8.055 0 12 0 12s0 3.945.501 5.814a3.017 3.017 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.945 24 12 24 12s0-3.945-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Company Pillar -->
            <div class="reveal rd1">
                <h4 class="footer-col-label">Company</h4>
                <a href="<?= APP_URL ?>/about" class="footer-col-link">About Us</a>
                <a href="<?= APP_URL ?>/shop" class="footer-col-link">Shop</a>
                <a href="<?= APP_URL ?>/contact" class="footer-col-link">Contact Us</a>
                <a href="<?= APP_URL ?>/track-order" class="footer-col-link">Track Your Order</a>
                <a href="<?= APP_URL ?>/login" class="footer-col-link">Login / Register</a>
            </div>

            <!-- Support Pillar -->
            <div class="reveal rd2">
                <h4 class="footer-col-label">Support</h4>
                <a href="<?= APP_URL ?>/terms" class="footer-col-link">Terms & Conditions</a>
                <a href="<?= APP_URL ?>/privacy" class="footer-col-link">Privacy Policy</a>
                <a href="<?= APP_URL ?>/payment-policy" class="footer-col-link">Payment Policy</a>
                <a href="<?= APP_URL ?>/shipping" class="footer-col-link">Shipping & Delivery Policy</a>
            </div>

            <!-- Direct Support Pillar -->
            <div class="reveal rd3 footer-contact-column">
                <h4 class="footer-col-label">Direct Support</h4>
                <p class="footer-newsletter-disclaimer" style="margin-bottom: 16px;">Have any questions or concerns? Link with us directly via email.</p>
                <a href="mailto:<?= SITE_EMAIL ?>" class="footer-support-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 10px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    Email Our Team
                </a>
                <p class="footer-newsletter-disclaimer" style="margin-top: 20px; font-size: 10px; opacity: 0.6;">We typically respond within 24 hours.</p>
            </div>
        </div>

        <div class="footer-bottom">
        <div class="footer-copy">
            <?= FOOTER_NOTICE ?> • built by D.V INSTALLATIONS LTD
        </div>
        <div class="footer-legal">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Cookies</a>
        </div>
    </div>
</div>
</footer>


<a href="https://wa.me/<?= WHATSAPP_NUMBER ?>" class="wa-btn" style="position: fixed; bottom: 30px; right: 30px; background: #25D366; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; z-index: 99; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 1 1-7.6-13.5 8.38 8.38 0 0 1 3.8.9L21 3z"></path></svg>
</a>
<script>
async function toggleWishlist(pid, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Find all heart buttons for this product across the page
    const btns = document.querySelectorAll('.wish-btn-' + pid + ', #wish-toggle-btn');
    btns.forEach(b => {
        b.classList.add('pulse-heart');
        setTimeout(() => b.classList.remove('pulse-heart'), 400);
    });

    const formData = new FormData();
    formData.append('product_id', pid);

    try {
        const res = await fetch('<?= APP_URL ?>/api/wishlist-toggle', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }

        if (data.success) {
            btns.forEach(b => {
                const svg = b.querySelector('svg');
                if (data.status === 'added') {
                    b.classList.add('active');
                    if (svg) {
                        svg.setAttribute('fill', 'var(--red)');
                        svg.setAttribute('stroke', 'var(--red)');
                    }
                } else {
                    b.classList.remove('active');
                    if (svg) {
                        svg.setAttribute('fill', 'none');
                        svg.setAttribute('stroke', 'var(--ink)');
                    }
                }
            });
        }
    } catch (err) {
        console.error('Wishlist sync failure');
    }
}

async function quickAddToCart(pid, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    
    // Loading State
    btn.innerHTML = '<span style="font-size: 10px;">⌛</span>';
    btn.style.pointerEvents = 'none';

    const formData = new FormData();
    formData.append('product_id', pid);
    formData.append('qty', 1);

    try {
        const res = await fetch('<?= APP_URL ?>/api/cart-add', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            btn.innerHTML = '<span style="font-size: 14px;">✅</span>';
            btn.style.background = 'var(--ink)';
            btn.style.color = '#fff';
            
            // Re-sync cart count in nav if it exists
            const cartCountElems = document.querySelectorAll('.cart-count');
            cartCountElems.forEach(el => {
                el.innerText = data.cart_count;
                el.style.display = 'flex';
                el.classList.add('pulse-pop');
                setTimeout(() => el.classList.remove('pulse-pop'), 400);
            });

            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.style.background = '';
                btn.style.color = '';
                btn.style.pointerEvents = '';
            }, 2000);
        }
    } catch (err) {
        btn.innerHTML = '❌';
        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.pointerEvents = '';
        }, 2000);
    }
}
</script>
</body>
</html>
