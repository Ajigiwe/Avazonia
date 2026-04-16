<?php
// views/home/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<?php require_once __DIR__ . '/../layout/hero.php'; ?>

<section class="featured">
    <div class="container">
        <div class="sec-head reveal">
        <div class="sec-title-box">
            <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">EXCLUSIVE OPPORTUNITY HUB</div>
            <h2 class="hero-heading" style="color: var(--ink); font-size: clamp(24px, 4vw, 38px); margin-bottom: 0; line-height: 1;">
                FLASH DEALS & DROPS
            </h2>
        </div>
            <a href="<?= APP_URL ?>/shop" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;">See all products →</a>
        </div>

        <div class="product-grid">
            <?php 
            $featured_drops = array_slice($featured, 0, 5); 
            if (!empty($featured_drops)):
                foreach ($featured_drops as $p): ?>
                <?php 
                // Pass current product to the unified component
                require __DIR__ . '/../components/product-card.php'; 
                ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No featured products found.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- REMOVED MID-SECTION FOR MINIMALIST LOOK -->

<!-- BESTSELLERS ROW -->
<section class="products-sec">
    <div class="container">
        <div class="sec-head reveal">
        <div class="sec-title-box">
            <div class="sec-over">Hand-picked</div>
            <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">Bestsellers</h2>
        </div>
            <div style="display: flex; align-items: center; gap: 24px;">
                <div class="slider-nav">
                    <button class="slider-nav-btn prev" id="slide-prev" aria-label="Previous">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </button>
                    <button class="slider-nav-btn next" id="slide-next" aria-label="Next">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </button>
                </div>
                <a href="<?= APP_URL ?>/shop" class="btn-ghost">Full catalogue <span class="arr">→</span></a>
            </div>
        </div>

        <div class="slider-container" style="position: relative; width: 100%; overflow: hidden;">
            <div class="slider-viewport" id="bestsellers-slider" style="overflow-x: auto !important; scroll-snap-type: x mandatory !important; display: flex !important; -webkit-overflow-scrolling: touch !important; scrollbar-width: none !important;">
                <div class="slider-track" style="display: flex !important; flex-wrap: nowrap !important; gap: 12px !important; padding: 10px 0 !important; width: max-content !important;">
                    <?php 
                    // Using manually selected bestsellers from controller
                    if (empty($bestsellers)) $bestsellers = array_slice($featured, 0, 5); 
                    ?>
                    <?php foreach ($bestsellers as $p): ?>
                        <?php require __DIR__ . '/../components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
 <?php if ($popup['enabled'] == '1'): ?>
<div id="promo-popup" class="promo-overlay" style="display: none;">
    <div class="promo-modal popup-mode-<?= $popup['type'] ?>">
        <button id="close-promo" class="promo-close" aria-label="Close popup">&times;</button>
        
        <div class="promo-content">
            <?php if ($popup['type'] === 'promo'): ?>
                <!-- PROMO IMAGE MODE -->
                <div class="promo-img-side">
                    <img src="<?= APP_URL ?>/<?= $popup['image'] ?>" alt="Promotion">
                </div>
                <div class="promo-text-side">
                    <div class="promo-label">SPECIAL OFFER</div>
                    <h2 class="promo-title"><?= htmlspecialchars($popup['title']) ?></h2>
                    <p class="promo-desc"><?= htmlspecialchars($popup['desc']) ?></p>
                    <a href="<?= APP_URL . $popup['link'] ?>" class="btn-promo"><?= htmlspecialchars($popup['btn_text']) ?></a>
                </div>

            <?php elseif ($popup['type'] === 'newsletter'): ?>
                <!-- NEWSLETTER MODE (REDESIGNED) -->
                <div class="promo-top-img">
                    <?php 
                    $imgUrl = $popup['image'] ?: 'https://images.unsplash.com/photo-1512428559087-560fa5ceab42?q=80&w=2070&auto=format&fit=crop';
                    $finalImg = (strpos($imgUrl, 'http') === 0 || strpos($imgUrl, '//') === 0) ? $imgUrl : APP_URL . '/' . $imgUrl;
                    ?>
                    <img src="<?= $finalImg ?>" alt="Newsletter">
                </div>
                <div class="promo-text-side" style="padding: 32px 40px; text-align: center;">
                    <h2 class="newsletter-title"><?= htmlspecialchars($popup['title']) ?></h2>
                    <p style="font-size: 14px; color: var(--mid-gray); margin-top: -4px;"><?= htmlspecialchars($popup['desc']) ?></p>
                    
                    <form id="newsletter-form" class="newsletter-pill-form">
                        <div class="pill-container">
                            <input type="email" name="email" placeholder="Email Address..." required class="pill-input">
                            <button type="submit" class="pill-submit">Subscribe</button>
                        </div>
                        <div id="newsletter-msg" style="margin-top: 16px; font-family: var(--f-mono); font-size: 11px; font-weight: 800; display: none;"></div>
                    </form>

                    <div class="newsletter-footer">
                        <p>By subscribing, you agree to our <a href="<?= APP_URL ?>/pages/terms">Terms of Use</a> and <a href="<?= APP_URL ?>/pages/privacy">Privacy Policy</a>.</p>
                        
                        <label class="dont-show-container">
                            <input type="checkbox" id="dont-show-check">
                            <span class="checkmark"></span>
                            Don't show this popup anymore.
                        </label>
                    </div>
                </div>

            <?php elseif ($popup['type'] === 'discount'): ?>
                <!-- DISCOUNT MODE -->
                <div class="promo-text-side" style="grid-column: span 2; padding: 80px; text-align: center;">
                    <div class="promo-label" style="color: var(--ink);">LIMITED TIME DROP</div>
                    <h2 class="promo-title" style="font-size: 64px; margin-bottom: 24px; color: var(--red);"><?= htmlspecialchars($popup['title']) ?></h2>
                    <p class="promo-desc" style="max-width: 480px; margin: 0 auto 48px;"><?= htmlspecialchars($popup['desc']) ?></p>
                    
                    <div class="discount-badge-container">
                        <div class="discount-label">USE CODE AT CHECKOUT</div>
                        <div id="copy-discount" class="discount-code-box">
                            <span id="discount-val"><?= htmlspecialchars($popup['discount']) ?></span>
                            <div class="copy-trigger">COPY</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const popup = document.getElementById('promo-popup');
    if (!popup) return;

    const closeBtn = document.getElementById('close-promo');
    const REFRESH_KEY = 'avazonia_popup_visit_count';
    const DISABLE_KEY = 'avazonia_popup_disabled';
    const frequency = <?= $popup['frequency'] ?>;

    // PERMANENT DISABLE CHECK
    if (localStorage.getItem(DISABLE_KEY) === 'true') return;

    // FREQUENCY LOGIC (Visit-based)
    let visitCount = parseInt(localStorage.getItem(REFRESH_KEY) || '0');
    visitCount++;
    localStorage.setItem(REFRESH_KEY, visitCount.toString());

    const shouldShow = () => {
        // Show on 1st visit, then every Nth visit
        if (visitCount === 1) return true;
        return (visitCount - 1) % frequency === 0;
    };

    if (shouldShow()) {
        setTimeout(() => {
            popup.style.display = 'flex';
            document.documentElement.classList.add('is-locked');
        }, 1500);
    }

    const closePopup = () => {
        // Force disable if checkbox is checked
        const dontShowCheck = document.getElementById('dont-show-check');
        if (dontShowCheck && dontShowCheck.checked) {
            localStorage.setItem(DISABLE_KEY, 'true');
        }

        popup.style.opacity = '0';
        popup.style.transition = 'opacity 0.3s ease';
        document.documentElement.classList.remove('is-locked');
        setTimeout(() => {
            popup.style.display = 'none';
        }, 300);
    };

    if (closeBtn) closeBtn.addEventListener('click', closePopup);
    popup.addEventListener('click', (e) => { if (e.target === popup) closePopup(); });

    // NEWSLETTER AJAX
    const nlForm = document.getElementById('newsletter-form');
    if (nlForm) {
        nlForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = nlForm.querySelector('button');
            const msg = document.getElementById('newsletter-msg');
            const email = nlForm.email.value;
            
            btn.innerText = 'WAIT...';
            btn.disabled = true;

            try {
                const response = await fetch('<?= APP_PATH ?>/api/newsletter-subscribe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const result = await response.json();
                msg.style.display = 'block';
                msg.innerText = result.message;
                msg.style.color = result.success ? '#00A854' : 'var(--red)';
                
                if (result.success) {
                    // Force disable if they subscribed
                    localStorage.setItem(DISABLE_KEY, 'true');
                    nlForm.style.display = 'none';
                    setTimeout(closePopup, 1500);
                } else {
                    btn.innerText = 'Subscribe';
                    btn.disabled = false;
                }
            } catch (err) {
                msg.style.display = 'block';
                msg.innerText = 'CONNECTION ERROR';
                btn.disabled = false;
            }
        });
    }

    // DISCOUNT COPY
    const copyBox = document.getElementById('copy-discount');
    if (copyBox) {
        copyBox.addEventListener('click', () => {
            const code = document.getElementById('discount-val').innerText;
            navigator.clipboard.writeText(code);
            const trigger = copyBox.querySelector('.copy-trigger');
            trigger.innerText = 'COPIED!';
            trigger.style.background = '#00A854';
            setTimeout(() => {
                trigger.innerText = 'COPY';
                trigger.style.background = 'var(--ink)';
            }, 2000);
        });
    }

    // ── BESTSELLERS SLIDER ────────────────────────────
    const slider = document.getElementById('bestsellers-slider');
    const nextBtn = document.getElementById('slide-next');
    const prevBtn = document.getElementById('slide-prev');

    if (slider && nextBtn && prevBtn) {
        // High-fidelity scroll logic
        const scrollAmount = 320; // Pro Console snap distance

        nextBtn.addEventListener('click', () => {
            slider.scrollTo({
                left: slider.scrollLeft + scrollAmount,
                behavior: 'smooth'
            });
        });

        prevBtn.addEventListener('click', () => {
            slider.scrollTo({
                left: slider.scrollLeft - scrollAmount,
                behavior: 'smooth'
            });
        });

        // Toggle visibility based on scroll position
        const checkButtons = () => {
             // Logic to fade buttons if at start/end for premium feel
             // slider.scrollLeft <= 0 ? prevBtn.style.opacity = '0.3' : prevBtn.style.opacity = '1';
        };

        slider.addEventListener('scroll', checkButtons);
    }
});
</script>
</script>
<?php endif; ?>

<!-- SUPPORT BANNER section -->
<?php require __DIR__ . '/../components/support-card.php'; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
