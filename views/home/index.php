<?php
// views/home/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<?php
// ── MOBILE CATEGORY LAUNCHER GRID (first thing under the nav on mobile) ──
$launchCats = !empty($mobileCategories) ? $mobileCategories : (!empty($categoryGrid) ? array_slice($categoryGrid, 0, 16) : []);
if (!empty($launchCats)):
    $postUrl = (!empty($is_seller)) ? APP_URL . '/seller/dashboard' : APP_URL . '/seller/apply';
    // Force emoji presentation (color glyphs) for icons that platforms like
    // Windows default to monochrome text style (needs U+FE0F variation selector).
    $emojiFaces = function (string $icon): string {
        return str_replace(['⌚','⚡','⚽','🎵'], ["⌚\u{FE0F}","⚡\u{FE0F}","⚽\u{FE0F}","🎵\u{FE0F}"], $icon);
    };
?>
<section class="category-grid-section">
    <div class="container">
        <div class="category-grid">
            <a href="<?= $postUrl ?>" class="cat-tile mcat-post">
                <span class="mcat-ic mcat-plus" aria-hidden="true">＋</span>
                <span class="cat-name"><?= t('home.post_ad', 'Post ad') ?></span>
            </a>
            <a href="<?= APP_URL ?>/shop" class="cat-tile">
                <span class="mcat-ic" aria-hidden="true">🔥</span>
                <span class="cat-name"><?= t('home.trending', 'Trending') ?></span>
            </a>
            <?php foreach ($launchCats as $cat): ?>
                <a href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($cat['slug']) ?>" class="cat-tile">
                    <span class="mcat-ic" aria-hidden="true"><?= !empty($cat['icon']) ? $emojiFaces($cat['icon']) : '📦' ?></span>
                    <span class="cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="home-hero-band">
<?php require_once __DIR__ . '/../layout/hero.php'; ?>
</div>

<!-- AVAZONIA MARKETPLACE HERO BAND -->
<section class="marketplace-band" style="background:var(--ink);color:#fff;padding:18px 0;">
  <div class="container" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between;">
    <div>
      <div style="font-family:var(--f-mono);font-size:10px;letter-spacing:.12em;opacity:.7;"><?= t('home.hero_sub', "AVAZONIA — AFRICA'S MULTI-VENDOR MARKETPLACE") ?></div>
      <div style="font-family:var(--f-display);font-weight:900;font-size:18px;letter-spacing:-.02em;"><?= t('home.hero_tagline', 'Buy. Sell. Source. Trade.') ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/shop" style="background:var(--red);color:#fff;padding:10px 16px;font-family:var(--f-semi);font-weight:800;font-size:11px;text-transform:uppercase;text-decoration:none;">🛒 Buy</a>
      <a href="<?= $is_seller ? APP_URL.'/seller/dashboard' : APP_URL.'/seller/apply' ?>" style="background:#fff;color:var(--ink);padding:10px 16px;font-family:var(--f-semi);font-weight:800;font-size:11px;text-transform:uppercase;text-decoration:none;">🏪 <?= $is_seller ? t('seller.dashboard', 'Dashboard') : t('home.hero_sell', 'Sell') ?></a>
      <a href="<?= APP_URL ?>/sourcing" style="border:2px solid #fff;color:#fff;padding:8px 14px;font-family:var(--f-semi);font-weight:800;font-size:11px;text-transform:uppercase;text-decoration:none;">🌍 Source</a>
    </div>
  </div>
</section>



<section class="featured">
    <div class="container">
        <div class="sec-head reveal">
        <div class="sec-title-box">
            <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">
                <?= htmlspecialchars($settings['home_deals_eyebrow'] ?? 'EXCLUSIVE OPPORTUNITY HUB') ?>
            </div>
            <h2 class="hero-heading" style="color: var(--ink); font-size: clamp(24px, 4vw, 38px); margin-bottom: 0; line-height: 1;">
                <?= htmlspecialchars($settings['home_deals_title'] ?? 'FLASH DEALS & DROPS') ?>
            </h2>
        </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="view-toggle" role="group" aria-label="View toggle">
                    <button id="view-grid" class="view-btn active" aria-pressed="true" onclick="setProductView('grid')" title="Grid view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </button>
                    <button id="view-list" class="view-btn" aria-pressed="false" onclick="setProductView('list')" title="List view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </button>
                </div>
                <a href="<?= APP_URL ?>/shop" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;">See all products →</a>
            </div>
        </div>

        <div class="product-grid">
            <?php 
            if (!empty($all_products)):
                foreach ($all_products as $p): ?>
                <?php 
                // Pass current product to the unified component
                require __DIR__ . '/../components/product-card.php'; 
                ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>

        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="shop-pagination" style="margin-top: 32px;">
            <?php if ($pagination['hasPrev']): ?>
                <a href="<?= APP_URL ?>/?page=<?= $pagination['page'] - 1 ?>" class="page-btn">&laquo; Prev</a>
            <?php endif; ?>
            <?php
            $start = max(1, $pagination['page'] - 2);
            $end = min($pagination['totalPages'], $pagination['page'] + 2);
            if ($start > 1) echo '<span class="page-dots">...</span>';
            for ($i = $start; $i <= $end; $i++):
                $isActive = $i === $pagination['page'];
            ?>
                <a href="<?= APP_URL ?>/?page=<?= $i ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; 
            if ($end < $pagination['totalPages']) echo '<span class="page-dots">...</span>';
            ?>
            <?php if ($pagination['hasNext']): ?>
                <a href="<?= APP_URL ?>/?page=<?= $pagination['page'] + 1 ?>" class="page-btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- PRE-ORDER SECTION -->
<?php if (!empty($preorders)): ?>
<section class="products-sec" style="background-color: var(--light-bg); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 60px 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="sec-title-box">
                <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">
                    SECURE YOURS NOW
                </div>
                <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                    Pre-Order Droplist
                </h2>
            </div>
            <a href="<?= APP_URL ?>/shop" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;"><?= t('home.see_all_preorders', 'See all pre-orders') ?> →</a>
        </div>

        <div class="product-grid">
            <?php foreach ($preorders as $p): ?>
                <?php require __DIR__ . '/../components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BESTSELLERS ROW -->
<section class="products-sec">
    <div class="container">
        <div class="sec-head reveal">
        <div class="sec-title-box">
            <div class="sec-over"<?= t('home.bestsellers_over', 'Hand-picked') ?></div>
            <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;"<?= t('home.bestsellers_title', 'Bestsellers') ?></h2>
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
                <a href="<?= APP_URL ?>/shop" class="btn-ghost"<?= t('home.full_catalogue', 'Full catalogue') ?> <span class="arr">→</span></a>
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
    </div>
</section>

 <!-- MARKETPLACE: Wholesale + Intl Suppliers + Featured Businesses -->
<?php if (!empty($wholesaleDeals)): ?>
<section class="products-sec" style="background:var(--off);border-top:2px solid var(--ink);border-bottom:1px solid var(--light-gray);padding:40px 0;">
  <div class="container">
    <div class="sec-head reveal">
      <div class="sec-title-box"><div class="sec-over" style="color:var(--red);font-size:10px;font-weight:800;letter-spacing:.15em;margin-bottom:8px;"><?= t('home.wholesale_over', 'B2B · WHOLESALE') ?></div><h2 class="hero-heading" style="color:var(--ink);margin-bottom:0;line-height:.9;">
                <?= t('home.wholesale_title', 'Wholesale Deals') ?>
            </h2></div>
      <a href="<?= APP_URL ?>/sourcing" style="font-family:var(--f-semi);font-size:12px;text-transform:uppercase;color:var(--mid-gray);font-weight:700;text-decoration:none;border-bottom:1px solid var(--light-gray);padding-bottom:4px;"><?= t('home.go_sourcing', 'Go to Sourcing') ?> →</a>
    </div>
    <div class="product-grid">
      <?php foreach($wholesaleDeals as $p): ?><?php require __DIR__ . '/../components/product-card.php'; ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($featuredBusinesses) || !empty($intlSuppliers)): ?>
<section style="padding:28px 0 32px;border-top:1px solid var(--light-gray);background:#fff;">
  <div class="container">
    <?php if (!empty($featuredBusinesses)): ?>
    <div style="margin-bottom:28px;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;"><span style="width:20px;height:2px;background:var(--red);display:inline-block;"></span><span style="font-family:var(--f-mono);font-size:10px;letter-spacing:.12em;color:var(--red);font-weight:800;text-transform:uppercase;"<?= t('home.featured_biz', 'Featured Businesses') ?></span></div>
      <div style="position:relative;">
        <div id="featured-biz-slider" style="overflow-x:auto;scroll-snap-type:x mandatory;display:flex;gap:12px;padding-bottom:4px;scrollbar-width:none;-ms-overflow-style:none;scroll-behavior:smooth;">
          <style>#featured-biz-slider::-webkit-scrollbar{display:none;}</style>
          <?php foreach($featuredBusinesses as $st): ?>
            <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($st['slug']) ?>" style="flex:0 0 300px;scroll-snap-align:start;display:flex;align-items:center;gap:10px;padding:12px 14px;text-decoration:none;color:var(--ink);background:var(--paper);border:1.5px solid var(--ink);border-radius:8px;white-space:nowrap;">
              <span style="width:32px;height:32px;flex-shrink:0;background:var(--off);border:1px solid var(--light-gray);display:flex;align-items:center;justify-content:center;font-size:16px;border-radius:6px;">🏪</span>
              <span style="min-width:0;flex:1;">
                <span style="font-family:var(--f-semi);font-size:12px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(mb_strimwidth($st['name'],0,18,'…')) ?></span>
                <span style="font-family:var(--f-mono);font-size:8px;letter-spacing:.06em;color:var(--mid-gray);text-transform:uppercase;">GH · Business</span>
              </span>
              <span style="display:flex;align-items:center;gap:6px;flex-shrink:0;"><span style="transform:scale(0.85);transform-origin:center;"><?= verification_badge($st) ?></span><span style="color:var(--mid-gray);">→</span></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($intlSuppliers)): ?>
    <div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;"><span style="width:20px;height:2px;background:var(--red);display:inline-block;"></span><span style="font-family:var(--f-mono);font-size:10px;letter-spacing:.12em;color:var(--red);font-weight:800;text-transform:uppercase;"<?= t('home.intl_suppliers', 'International Suppliers') ?></span></div>
      <div style="position:relative;">
        <div id="intl-supplier-slider" style="overflow-x:auto;scroll-snap-type:x mandatory;display:flex;gap:12px;padding-bottom:4px;scrollbar-width:none;-ms-overflow-style:none;scroll-behavior:smooth;">
          <style>#intl-supplier-slider::-webkit-scrollbar{display:none;}</style>
          <?php foreach($intlSuppliers as $st): ?>
            <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($st['slug']) ?>" style="flex:0 0 300px;scroll-snap-align:start;display:flex;align-items:center;gap:10px;padding:12px 14px;text-decoration:none;color:var(--ink);background:var(--paper);border:1.5px solid var(--ink);border-radius:8px;white-space:nowrap;">
              <span style="width:32px;height:32px;flex-shrink:0;background:var(--off);border:1px solid var(--light-gray);display:flex;align-items:center;justify-content:center;font-size:16px;border-radius:6px;">🌍</span>
              <span style="min-width:0;flex:1;">
                <span style="font-family:var(--f-semi);font-size:12px;font-weight:700;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(mb_strimwidth($st['name'],0,18,'…')) ?></span>
                <span style="font-family:var(--f-mono);font-size:8px;letter-spacing:.06em;color:var(--mid-gray);text-transform:uppercase;">CN · Export</span>
              </span>
              <span style="display:flex;align-items:center;gap:6px;flex-shrink:0;"><span style="transform:scale(0.85);transform-origin:center;"><?= verification_badge($st) ?></span><span style="color:var(--mid-gray);">→</span></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($exportCars)): ?>
    <div style="margin-top:18px;">
      <div style="font-family:var(--f-mono);font-size:10px;letter-spacing:.1em;color:var(--mid-gray);"><?= t('home.vehicle_sourcing', 'INTERNATIONAL VEHICLE SOURCING') ?> — FOB / CIF</div>
      <div class="product-grid" style="margin-top:10px;">
        <?php foreach($exportCars as $p): ?><?php require __DIR__ . '/../components/product-card.php'; ?><?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

 <!-- CATEGORY SHOWCASE SECTIONS -->
<?php if (!empty($categoryShowcase)): ?>
    <?php foreach ($categoryShowcase as $showcase): ?>
        <section class="products-sec" style="border-top: 1px solid var(--border-color); padding: 60px 0;">
            <div class="container">
                <div class="sec-head reveal">
                    <div class="sec-title-box">
                        <div class="sec-over" style="color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.15em; margin-bottom: 8px;">
                            <?= t('home.explore_category', 'EXPLORE CATEGORY') ?>
                        </div>
                        <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                            <?= htmlspecialchars(strtoupper($showcase['category']['name'])) ?>
                        </h2>
                    </div>
                    <a href="<?= APP_URL ?>/shop?cat=<?= $showcase['category']['slug'] ?>" style="font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 4px;">Shop All <?= htmlspecialchars($showcase['category']['name']) ?> →</a>
                </div>

                <div class="product-grid">
                    <?php foreach ($showcase['products'] as $p): ?>
                        <?php require __DIR__ . '/../components/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

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
                const apiUrl = window.location.origin + '/api/newsletter-subscribe.php?email=' + encodeURIComponent(email);
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email }),
                    redirect: 'follow'
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
