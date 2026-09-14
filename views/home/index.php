<?php
// views/home/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<?php
// ── Interstitial promo banners: one admin-managed banner per call, between sections ──
// $homeBanners comes from the controller; $homeBannerIdx advances across calls so
// each section gap gets the next banner instead of stacking them all in one block.
$homeBannerIdx = 0;
function home_next_banner(array $banners, int &$idx): string {
    while ($idx < count($banners)) {
        $banner = $banners[$idx++];
        $bSrc = $banner['image_url'] ?? '';
        if (!$bSrc) continue;
        if (!filter_var($bSrc, FILTER_VALIDATE_URL)) $bSrc = APP_URL . '/' . ltrim($bSrc, '/');
        $bLink = trim($banner['link_url'] ?? '/shop');
        if (preg_match('~^https?://~i', $bLink)) { $bHref = $bLink; $bExternal = true; }
        else { $bHref = APP_URL . ($bLink !== '' && $bLink[0] !== '/' ? '/' : '') . $bLink; $bExternal = false; }
        return '<div class="container home-banner-slot">'
            . '<a class="banner-link" href="' . htmlspecialchars($bHref) . '"' . (!empty($bExternal) ? ' target="_blank" rel="noopener"' : '') . '>'
            . '<img src="' . htmlspecialchars($bSrc) . '" alt="' . htmlspecialchars($banner['title'] ?? 'Promotion') . '" loading="lazy">'
            . '</a></div>';
    }
    return '';
}

// ── "VIEW MORE" footer for product sections ─────────────────
// $homeMore = ['label' => string, 'url' => string]; $homeCount = items shown.
// Desktop: reveals the preloaded hidden cards in place (no reload).
// Mobile: the rail renders as a 2-col grid, so we deep-link instead of expanding.
$homeMore = $homeMore ?? null;
function home_view_more_footer(?array $homeMore, ?int $homeCount, string $pos = 'post'): string {
    if (!$homeMore) return '';
    $desktopCount = $homeCount !== null ? ($homeCount - 10) : null;
    $reveal = $desktopCount !== null && $desktopCount > 0
        ? '<span class="rail-more-expand">View ' . (int)$desktopCount . ' more</span>'
        : '<span class="rail-more-expand">View more</span>';
    return '<div class="rail-more">'
        . '<a class="rail-more-btn js-rail-more" href="' . htmlspecialchars($homeMore['url']) . '" data-pos="' . $pos . '">'
        . $reveal . '<span class="rail-more-arrow">→</span></a>'
        . '</div>';
}
?>

<style>
.rail-more { display: flex; justify-content: center; margin-top: 18px; }
.rail-more-btn {
    display: inline-flex; align-items: center; gap: 10px; text-decoration: none;
    font-family: var(--f-mono); font-size: 11px; font-weight: 800;
    letter-spacing: .12em; text-transform: uppercase;
    color: var(--ink); border: 2px solid var(--ink); padding: 12px 26px;
    background: #fff; transition: all .25s ease;
}
.rail-more-btn:hover { background: var(--ink); color: #fff; }
.rail-more-btn:hover .rail-more-arrow { transform: translateX(4px); }
.rail-more-arrow { display: inline-block; transition: transform .25s ease; }
.rail-more.hidden { display: none; }

/* Desktop expand: hidden ghost cards join the grid when View More is clicked. */
.card.card-ghost.is-hidden-ghost { display: none !important; }
.rail-expanded .slider-track .card.card-ghost.is-hidden-ghost { display: block !important; }

/* Interstitial promo banners between product sections. */
.home-banner-slot { margin: 2px auto 30px; }
.home-banner-slot a.banner-link { display: block; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 24px rgba(0,0,0,0.08); transition: transform .25s ease, box-shadow .25s ease; }
.home-banner-slot a.banner-link:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,0.14); }
.home-banner-slot img { display: block; width: 100%; height: auto; max-height: 320px; object-fit: cover; }
</style>

<script>
(function () {
    function bindRailMore() {
        document.querySelectorAll('.js-rail-more').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function (e) {
                if (window.matchMedia('(max-width: 900px)').matches) return; // mobile: deep-link out
                e.preventDefault();
                var sec = btn.closest('.products-sec');
                var ghost = sec ? sec.querySelectorAll('.slider-track .card-ghost') : [];
                var hidden = sec ? sec.querySelectorAll('.slider-track .card.is-hidden-ghost') : [];
                // Grid sections (pre-orders): flip card display in place.
                for (var i = 0; i < hidden.length; i++) hidden[i].classList.remove('is-hidden-ghost');
                // Rails: flip the whole container into expanded (wrap) mode.
                if (sec) sec.classList.add('rail-expanded');
                if (ghost.length || hidden.length) {
                    btn.closest('.rail-more').classList.add('hidden');
                    // Remove the injected desktop height cap so the wrap grid shows fully.
                    var vp = sec ? sec.querySelector('.slider-viewport') : null;
                    if (vp) vp.style.height = '';
                } else {
                    window.location.href = btn.href; // nothing extra preloaded — deep-link out
                }
        });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindRailMore);
    } else {
        bindRailMore();
    }
})();
</script>


<div class="home-hero-band">
<?php require_once __DIR__ . '/../layout/hero.php'; ?>
</div>

<?php
// ── MOBILE CATEGORY LAUNCHER GRID (shown immediately after the hero on mobile) ──
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



<!-- NEW DROPS — the 10 most recently added items from any category -->
<section class="products-sec" style="border-top: 2px solid var(--ink); padding: 48px 0 40px;">
    <div class="container">
        <style>
            .rail-scroller { position: relative; }
            .rail-scroller .slider-container { position: relative; width: 100%; }
            /* Centered rail headers */
            .rail-head { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 5px; margin-bottom: 16px; }
            .rail-eyebrow { color: var(--red); font-size: 10px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; }
            .rail-head h2 { font-weight: 800; font-size: clamp(20px, 3vw, 30px); margin: 0; line-height: 1.1; text-transform: uppercase; letter-spacing: -0.01em; }
            .rail-head a.rail-link { font-size: 11px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--light-gray); padding-bottom: 3px; margin-top: 3px; }
            .rail-head a.rail-link:hover { color: var(--red); border-color: var(--red); }
            /* Product rails are wrapped grids, not horizontal scrollers:
               5 per row on desktop, stepping down on smaller screens. */
            .rail-scroller .slider-viewport { display: block !important; overflow: visible !important; padding-bottom: 0 !important; margin-bottom: 0 !important; scroll-snap-type: none !important; }
            .rail-scroller .slider-track { display: grid !important; width: 100% !important; padding: 0 !important; grid-template-columns: repeat(5, 1fr) !important; gap: 20px 14px !important; }
            .rail-scroller .slider-track .card { flex: none !important; width: auto !important; min-width: 0 !important; max-width: none !important; }
            @media (max-width: 1200px) {
                .rail-scroller .slider-track { grid-template-columns: repeat(4, 1fr) !important; }
            }
            @media (max-width: 1024px) {
                .rail-scroller .slider-track { grid-template-columns: repeat(3, 1fr) !important; gap: 16px 12px !important; }
            }
            @media (max-width: 640px) {
                .rail-scroller .slider-track { grid-template-columns: repeat(2, 1fr) !important; gap: 14px 10px !important; }
            }
        </style>
        <div class="rail-scroller">
            <div class="rail-head reveal">
                <div class="rail-eyebrow">JUST IN</div>
                <h2>New Drops</h2>
                <a class="rail-link" href="<?= APP_URL ?>/shop">See all products →</a>
                <div class="view-toggle" role="group" aria-label="View toggle">
                    <button id="view-grid" class="view-btn active" data-view-mode="grid" aria-pressed="true" onclick="setProductView('grid')" title="Grid view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </button>
                    <button id="view-list" class="view-btn" data-view-mode="list" aria-pressed="false" onclick="setProductView('list')" title="List view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </button>
                </div>
            </div>
            <div class="slider-container">
                <div class="slider-viewport">
                    <div class="slider-track">
                        <?php if (!empty($newDrops)): 
                            $newDropsShown = array_slice($newDrops, 0, 10);
                            $newDropsExtra = array_slice($newDrops, 10);
                            foreach ($newDropsShown as $p): ?>
                                <?php require __DIR__ . '/../components/product-card.php'; ?>
                            <?php endforeach; ?>
                            <?php foreach ($newDropsExtra as $p): $homeGhost = true; ?>
                                <?php require __DIR__ . '/../components/product-card.php'; ?>
                            <?php endforeach; $homeGhost = false;
                        else: ?>
                            <p style="color: var(--mid-gray); padding: 20px 0;">No products yet — check back soon.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $homeMore = !empty($newDrops) ? ['label' => 'view_all_products', 'url' => APP_URL . '/shop'] : null;
                echo home_view_more_footer($homeMore, count($newDrops));
                ?>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($newDrops)): ?><?= home_next_banner($homeBanners ?? [], $homeBannerIdx) ?><?php endif; ?>

<!-- TOP 10 PER MAJOR CATEGORY (seller listings included) -->
<?php if (!empty($categoryDrops)): ?>
    <?php foreach ($categoryDrops as $drop): ?>
        <section class="products-sec" style="border-top: 1px solid var(--border-color); padding: 40px 0 28px;">
            <div class="container">
                <div class="rail-scroller">
                    <div class="rail-head reveal">
                        <div class="rail-eyebrow">EXPLORE CATEGORY</div>
                        <h2><?= !empty($drop['category']['icon']) ? htmlspecialchars($drop['category']['icon']) . ' ' : '' ?><?= htmlspecialchars($drop['category']['name']) ?></h2>
                        <a class="rail-link" href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($drop['category']['slug']) ?>">Shop all <?= htmlspecialchars($drop['category']['name']) ?> →</a>
                        <div class="view-toggle" role="group" aria-label="View toggle">
                            <button class="view-btn active" data-view-mode="grid" aria-pressed="true" onclick="setProductView('grid')" title="Grid view">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                            </button>
                            <button class="view-btn" data-view-mode="list" aria-pressed="false" onclick="setProductView('list')" title="List view">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>
                    <div class="slider-container">
                        <div class="slider-viewport">
                            <div class="slider-track">
                                <?php foreach ($drop['products'] as $p): ?>
                                    <?php require __DIR__ . '/../components/product-card.php'; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $homeMore = ['label' => 'category_view_more', 'url' => APP_URL . '/shop?cat=' . urlencode($drop['category']['slug'])];
                echo home_view_more_footer($homeMore, count($drop['products']), 'post');
                ?>
            </div>
        </section>
        <?php if (!empty($drop['products'])): ?><?= home_next_banner($homeBanners ?? [], $homeBannerIdx) ?><?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

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
        <?php
        $homeMore = ['label' => 'view_all_preorders', 'url' => APP_URL . '/deals'];
        echo home_view_more_footer($homeMore, count($preorders), 'grid');
        ?>
    </div>
</section>
<?php endif; ?>
<?php if (!empty($preorders)): ?><?= home_next_banner($homeBanners ?? [], $homeBannerIdx) ?><?php endif; ?>


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
    <?php
    $homeMore = ['label' => 'wholesale_view_more', 'url' => APP_URL . '/shop?listing_type=wholesale'];
    echo home_view_more_footer($homeMore, count($wholesaleDeals), 'grid');
    ?>
  </div>
</section>
<?php endif; ?>
<?php if (!empty($wholesaleDeals)): ?><?= home_next_banner($homeBanners ?? [], $homeBannerIdx) ?><?php endif; ?>

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
});
</script>
<?php endif; ?>


<!-- SUPPORT BANNER section -->
<?php require __DIR__ . '/../components/support-card.php'; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
