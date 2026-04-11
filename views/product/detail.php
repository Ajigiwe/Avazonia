<?php
// views/product/detail.php

// Prepare SEO metadata
$meta_title = !empty($product['meta_title']) ? $product['meta_title'] : ($product['name'] . ' — Avazonia');
$meta_description = !empty($product['meta_description']) ? $product['meta_description'] : substr(strip_tags($product['description']), 0, 155);
$og_image = $product['primary_image'] ? (filter_var($product['primary_image'], FILTER_VALIDATE_URL) ? $product['primary_image'] : APP_URL . '/' . ltrim($product['primary_image'], '/')) : null;

require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

// Wishlist Status Check
require_once __DIR__ . '/../../models/Wishlist.php';
$wishlistModel = new Wishlist();
$isInWishlist = false;
if (Session::get('user_id')) {
    $wishlistIds = $wishlistModel->getProductIds(Session::get('user_id'));
    $isInWishlist = in_array($product['id'], $wishlistIds);
}
?>

<!-- JSON-LD Structured Data for Google -->
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "<?= htmlspecialchars($product['name']) ?>",
  "image": "<?= $og_image ?>",
  "description": "<?= htmlspecialchars(strip_tags($product['description'])) ?>",
  "brand": {
    "@type": "Brand",
    "name": "<?= htmlspecialchars($product['brand_name'] ?? 'Gadget') ?>"
  },
  "sku": "AVZ-<?= $product['id'] ?>",
  "offers": {
    "@type": "Offer",
    "url": "<?= APP_URL . $_SERVER['REQUEST_URI'] ?>",
    "priceCurrency": "GHS",
    "price": "<?= $product['price_ghs'] ?>",
    "availability": "https://schema.org/<?= ($product['stock_qty'] > 0 || $product['is_preorder'] || $product['is_dropshipping']) ? 'InStock' : 'OutOfStock' ?>",
    "itemCondition": "https://schema.org/NewCondition"
  }
  <?php if ($review_count > 0): ?>,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= $avg_rating ?>",
    "reviewCount": "<?= $review_count ?>"
  }
  <?php endif; ?>
}
</script>

<section class="product-page" style="padding: 170px 0 100px;">
    <div class="container product-detail-layout">
        <!-- Image Gallery -->
        <div class="product-gallery" style="display: flex; flex-direction: column; gap: 16px;">
            <div class="zoom-container" id="zoom-container" style="aspect-ratio: 1; background: var(--off); border: 1px solid var(--light-gray); display: flex; align-items: center; justify-content: center;">
                <?php 
                $primaryImgUrl = $product['primary_image'] ?: 'https://via.placeholder.com/800x800';
                if (!filter_var($primaryImgUrl, FILTER_VALIDATE_URL)) {
                    $primaryImgUrl = APP_PATH . '/' . ltrim($primaryImgUrl, '/');
                }
                ?>
                <img id="main-product-image" src="<?= $primaryImgUrl ?>" alt="<?= htmlspecialchars($product['alt_text'] ?? $product['name']) ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 40px; transition: transform 0.1s ease-out, opacity 0.2s ease;">
            </div>
            
            <?php if (!empty($images) && count($images) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <?php foreach ($images as $index => $imgData): 
                    $thumbUrl = $imgData['url'];
                    if (!filter_var($thumbUrl, FILTER_VALIDATE_URL)) {
                        $thumbUrl = APP_PATH . '/' . ltrim($thumbUrl, '/');
                    }
                ?>
                    <div class="thumbnail-item" onclick="const mainImg = document.getElementById('main-product-image'); mainImg.style.opacity='0.5'; setTimeout(()=>{mainImg.src='<?= $thumbUrl ?>'; mainImg.style.opacity='1';},100); document.querySelectorAll('.thumbnail-item').forEach(t=>t.style.borderColor='var(--light-gray)'); this.style.borderColor='var(--red)';" style="aspect-ratio: 1; background: var(--off); border: 1.5px solid <?= $index === 0 ? 'var(--red)' : 'var(--light-gray)' ?>; cursor: pointer; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 8px;">
                        <img src="<?= $thumbUrl ?>" alt="<?= htmlspecialchars($imgData['alt_text'] ?? 'Product Thumbnail') ?>" style="width: 100%; height: 100%; object-fit: contain; pointer-events: none;">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <div class="sec-eyebrow">
                <span class="eyebrow-text"><?= $product['brand_name'] ?? 'Gadget' ?></span>
                <span class="eyebrow-line"></span>
            </div>


            <?php if ($product['is_preorder']): ?>
                <div style="display: inline-block; background: #0088FF; color: #fff; font-family: var(--f-display); font-size: 10px; font-weight: 800; padding: 6px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 20px;">Pre-order Item</div>
            <?php elseif ($product['is_dropshipping']): ?>
                <div style="display: inline-block; background: #FF8800; color: #fff; font-family: var(--f-display); font-size: 10px; font-weight: 800; padding: 6px 16px; border-radius: 100px; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 20px;">Global Direct</div>
            <?php endif; ?>

            <h1 style="font-family: var(--f-display); font-weight: 700; font-size: clamp(24px, 4vw, 38px); text-transform: uppercase; margin-bottom: 16px; line-height: 1.1; letter-spacing: -0.02em;"><?= $product['name'] ?></h1>
            
            <?php if ($review_count > 0): ?>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 32px;">
                    <div class="card-rating" style="font-size: 14px; gap: 4px;">
                        <?php 
                        $rating = round($avg_rating ?? 5); 
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <a href="#reviews" style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-decoration: none; text-transform: uppercase; letter-spacing: .05em;">(<?= $review_count ?>) Reviews</a>
                </div>
            <?php endif; ?>

            <div style="font-family: var(--f-display); margin-bottom: 32px; display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                <div id="live-price-display" style="font-weight: 800; font-size: clamp(28px, 5vw, 44px); color: var(--ink); line-height: 1;">₵<?= number_format($product['price_ghs'], 2) ?></div>
                <?php if ($product['compare_at_price_ghs'] > $product['price_ghs']): ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-family: var(--f-display); font-size: 18px; color: var(--mid-gray); text-decoration: line-through; font-weight: 500; opacity: 0.6;">₵<?= number_format($product['compare_at_price_ghs'], 2) ?></span>
                        <span style="background: #FFF5E6; color: #FF8C00; font-size: 14px; font-weight: 800; padding: 6px 14px; border-radius: 8px;">-<?= round((($product['compare_at_price_ghs'] - $product['price_ghs']) / $product['compare_at_price_ghs']) * 100) ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['tags'])): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px; align-items: center;">
                    <span style="font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; color: var(--mid-gray); margin-right: 4px;">In This Drop:</span>
                    <?php foreach (explode(',', $product['tags']) as $tag): ?>
                        <?php if (trim($tag)): ?>
                            <span style="font-family: var(--f-mono); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--ink); background: var(--off); padding: 4px 10px; border-radius: 4px; border: 1px solid var(--light-gray);"><?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p style="font-family: var(--f-body); font-size: 15px; color: var(--mid-gray); line-height: 1.7; margin-bottom: 40px;">
                <?= nl2br($product['description'] ?: 'No description available.') ?>
            </p>

            <?php if (!empty($variants)): ?>
            <div style="margin-bottom: 32px;">
                <label style="display: block; font-family: var(--f-mono); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 12px; font-weight: 600;">Select Variant</label>
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <?php foreach ($variants as $idx => $v): ?>
                        <div class="variant-pill" 
                             data-id="<?= $v['id'] ?>"
                             data-price="<?= $v['price_override_ghs'] ? number_format($v['price_override_ghs'], 2) : number_format($product['price_ghs'], 2) ?>"
                             data-image="<?= $v['image_url'] ?: '' ?>"
                             onclick="selectVariant(this)"
                             style="cursor: pointer; display: flex; align-items: center; gap: 8px; border: 2px solid <?= $idx === 0 ? 'var(--ink)' : 'var(--light-gray)' ?>; border-radius: 20px; padding: 6px 16px; font-size: 12px; font-weight: 700;">
                            <?php if ($v['color_hex']): ?>
                                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 50%; background: <?= $v['color_hex'] ?>; border: 1px solid rgba(0,0,0,0.1);"></span>
                            <?php endif; ?>
                            <span><?= trim($v['color'] . ' ' . $v['size']) ?: 'Standard' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
                window.selectVariant = function(pill) {
                    const priceDisplay = document.getElementById('live-price-display');
                    const formVarInput = document.getElementById('form-variant-id');
                    const mainImg = document.getElementById('main-product-image');
                    
                    document.querySelectorAll('.variant-pill').forEach(p => p.style.borderColor = 'var(--light-gray)');
                    pill.style.borderColor = 'var(--ink)';
                    
                    if (formVarInput) formVarInput.value = pill.getAttribute('data-id');
                    
                    if (pill.getAttribute('data-price')) {
                        priceDisplay.innerText = '₵' + pill.getAttribute('data-price');
                    }
                    
                    const newImage = pill.getAttribute('data-image');
                    if (newImage && mainImg) {
                        mainImg.style.opacity = '0.5';
                        setTimeout(() => {
                            mainImg.src = newImage.startsWith('http') ? newImage : '<?= APP_PATH ?>/' + newImage;
                            mainImg.style.opacity = '1';
                        }, 100);
                    }
                };
            </script>
            <?php endif; ?>

            <form class="ajax-cart-form" action="<?= APP_URL ?>/api/cart-add" method="POST" style="display: flex; gap: 16px; align-items: center;">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="variant_id" id="form-variant-id" value="<?= !empty($variants) ? $variants[0]['id'] : '' ?>">
                
                <!-- Compact Quantity Selector -->
                <div style="display: flex; align-items: center; border: 1px solid var(--light-gray); height: 42px; border-radius: 6px; overflow: hidden; background: #fff;">
                    <button type="button" onclick="changeQty(-1)" style="width: 36px; height: 100%; border: none; background: none; cursor: pointer; font-size: 16px; color: var(--mid-gray); display: flex; align-items: center; justify-content: center;">-</button>
                    <input type="number" name="qty" id="product-qty" value="1" min="1" max="<?= $product['stock_qty'] ?: 99 ?>" 
                           style="width: 40px; height: 100%; border: none; text-align: center; font-family: var(--f-display); font-weight: 700; font-size: 13px; -moz-appearance: textfield; background: #fff;">
                    <button type="button" onclick="changeQty(1)" style="width: 36px; height: 100%; border: none; background: none; cursor: pointer; font-size: 16px; color: var(--mid-gray); display: flex; align-items: center; justify-content: center;">+</button>
                </div>

                <style>
                    #product-qty::-webkit-outer-spin-button, #product-qty::-webkit-inner-spin-button { 
                        -webkit-appearance: none; margin: 0; 
                    }
                </style>
                <button type="submit" class="btn-ink" style="height: 42px; font-size: 11px; padding: 0 22px; border-radius: 6px; display: flex; align-items: center; gap: 8px; width: fit-content;">
                    <?= $product['is_preorder'] ? 'PRE-ORDER' : 'ADD TO BAG' ?> 
                    <span style="font-size: 1.1em;">→</span>
                </button>

                <!-- Heart Wishlist Toggle -->
                <button type="button" id="wish-toggle-btn" 
                        onclick="toggleWishlist(<?= $product['id'] ?>)"
                        class="wish-btn <?= $isInWishlist ? 'active' : '' ?>"
                        style="height: 42px; width: 42px; padding: 0; border: 1px solid var(--light-gray); background: #fff; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1); flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $isInWishlist ? 'var(--red)' : 'none' ?>" stroke="<?= $isInWishlist ? 'var(--red)' : 'var(--ink)' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transition: all 0.3s; pointer-events: none;">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                <style>
                    .wish-btn:hover { border-color: var(--red); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(229,0,26,0.1); }
                    .wish-btn.active { border-color: var(--red); background: #FFF5F6; }
                    .wish-btn.pulse-heart svg { transform: scale(1.3); }
                </style>
            </form>


            <!-- Premium Trust & Help Section -->
            <div class="product-trust-group">
                <!-- Payment Support -->
                <div class="payment-trust-box">
                    <span class="payment-label">Supported payment types:</span>
                    <div class="payment-icons-row">
                        <img src="<?= APP_URL ?>/public/assets/img/paystack1.png" alt="Powered by Paystack" class="paystack-banner">
                    </div>
                </div>

                <!-- Shipping & Social -->
                <div class="trust-meta-row">
                    <div class="shipping-promise">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--mid-gray);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M9 16l2 2 4-4"></path></svg>
                        <span>Order now and your order ships by <span class="ship-date-highlight"><?= date('D, M d', strtotime('+3 days')) ?></span></span>
                    </div>

                    <div class="social-sharing-circles">
                        <?php if (!empty($dbSettings['facebook_link'])): ?>
                            <a href="<?= htmlspecialchars($dbSettings['facebook_link']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($dbSettings['youtube_link'])): ?>
                            <a href="<?= htmlspecialchars($dbSettings['youtube_link']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.42a2.78 2.78 0 0 0-1.94 2C1 8.11 1 12 1 12s0 3.89.46 5.58a2.78 2.78 0 0 0 1.94 2C5.12 20 12 20 12 20s6.88 0 8.6-.42a2.78 2.78 0 0 0 1.94-2C23 15.89 23 12 23 12s0-3.89-.46-5.58zM9.75 15.02V8.98L15 12l-5.25 3.02z"></path></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($dbSettings['instagram_link'])): ?>
                            <a href="<?= htmlspecialchars($dbSettings['instagram_link']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a>
                        <?php endif; ?>
                        <?php if (!empty($dbSettings['tiktok_link'])): ?>
                            <a href="<?= htmlspecialchars($dbSettings['tiktok_link']) ?>" class="soc-circle" target="_blank"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12a4 4 0 1 0 4 4V0h4a8.13 8.13 0 0 1-5 2V8a4 4 0 0 0-3 4z"></path></svg></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Help Pills -->
                <div class="help-pills-row">
                    <a href="<?= APP_URL ?>/contact" class="help-pill-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Need Help? Chat with an Expert
                    </a>
                    <a href="tel:+<?= WHATSAPP_NUMBER ?>" class="help-pill-btn call-pill">
                        <span class="call-number">+<?= WHATSAPP_NUMBER ?></span>
                        <span class="call-action">Call Us</span>
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="trust-badges-large">
                    <div class="tbadge-item">
                        <div class="tbadge-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12A10 10 0 1 1 12 2a10 10 0 0 1 10 10z"></path><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="tbadge-content">
                            <h4>Online Support 24/7</h4>
                        </div>
                    </div>
                    <div class="tbadge-item">
                        <div class="tbadge-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                        <div class="tbadge-content">
                            <h4>Secure Payment</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Lightbox Overlay -->
    <div id="mobile-lightbox" class="lightbox-overlay">
        <button class="lightbox-close" id="close-lightbox">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <img id="lightbox-img" class="lightbox-img" src="" alt="Expanded View">
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('zoom-container');
        const img = document.getElementById('main-product-image');
        const lightbox = document.getElementById('mobile-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const closeLightbox = document.getElementById('close-lightbox');

        if (!container || !img) return;

        // --- CLICK TO EXPAND (UNIVERSAL) ---
        container.addEventListener('click', function() {
            lightboxImg.src = img.src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        if (closeLightbox) {
            closeLightbox.addEventListener('click', function() {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</section>

<!-- ── REVIEWS SECTION ────────────────────────────────── -->
<section id="reviews" class="reviews-sec" style="padding: 100px 0 60px; border-top: 1px solid var(--light-gray);">
    <div class="container">
        <div style="margin-bottom: 64px;">
            <div class="sec-eyebrow">
                <span class="eyebrow-text">Feedback</span>
                <span class="eyebrow-line"></span>
            </div>
            <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 32px; text-transform: uppercase; margin-bottom: 24px; line-height: 1;">Customer Reviews</h2>
            
            <div style="display: flex; align-items: center; gap: 20px; margin-top: 24px;">
                <div style="font-family: var(--f-display); font-size: 40px; font-weight: 900; line-height: 1; color: var(--ink);"><?= number_format($avg_rating, 1) ?></div>
                <div>
                    <div class="card-rating" style="font-size: 20px; gap: 6px;">
                        <?php 
                        $rating = round($avg_rating ?? 5); 
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <span class="star <?= $i <= $rating ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .12em; color: var(--mid-gray); margin-top: 6px;">Based on <?= $review_count ?> reviews</div>
                </div>
            </div>
        </div>

        <div class="product-detail-layout" style="align-items: flex-start; gap: 48px; max-width: 900px;">
            <!-- Left: Form -->
            <div>
                <!-- Submit Review Form -->
                <div style="background: var(--off); padding: 32px; border-radius: 2px;">
                    <h3 style="font-family: var(--f-display); font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .15em; margin-bottom: 32px; color: var(--ink);">Write a review</h3>
                    <form action="<?= APP_URL ?>/api/review-add" method="POST" style="display: flex; flex-direction: column; gap: 28px;">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="slug" value="<?= $product['slug'] ?>">
                        
                        <div class="form-group">
                            <label style="display: block; font-family: var(--f-semi); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--mid-gray); margin-bottom: 12px;">Full Name</label>
                            <input type="text" name="name" required value="<?= Session::get('user_name', '') ?>" placeholder="Your Name" style="width: 100%; height: 52px; background: #fff; border: 1px solid var(--light-gray); padding: 0 20px; font-family: var(--f-body); font-size: 14px; outline: none; transition: border-color .3s;">
                        </div>

                        <div class="form-group">
                            <label style="display: block; font-family: var(--f-semi); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--mid-gray); margin-bottom: 12px;">Your Rating</label>
                            <input type="hidden" name="rating" id="review-rating-val" value="5">
                            <div class="star-rating-input" style="display: flex; gap: 8px; font-size: 28px; line-height: 1; cursor: pointer; color: var(--light-gray);">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span class="star-pick active" data-value="<?= $i ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <script>
                            document.querySelectorAll('.star-pick').forEach(star => {
                                star.addEventListener('click', function() {
                                    const val = this.getAttribute('data-value');
                                    document.getElementById('review-rating-val').value = val;
                                    
                                    document.querySelectorAll('.star-pick').forEach(s => {
                                        if (s.getAttribute('data-value') <= val) {
                                            s.style.color = 'var(--red)';
                                        } else {
                                            s.style.color = 'var(--light-gray)';
                                        }
                                    });
                                });
                            });
                        </script>

                        <div class="form-group">
                            <label style="display: block; font-family: var(--f-semi); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--mid-gray); margin-bottom: 12px;">Your Experience</label>
                            <textarea name="comment" required placeholder="What do you think of this product?" style="width: 100%; height: 140px; background: #fff; border: 1px solid var(--light-gray); padding: 20px; font-family: var(--f-body); font-size: 14px; outline: none; resize: none; line-height: 1.6;"></textarea>
                        </div>

                        <button type="submit" class="btn-red" style="width: 100%; height: 56px; font-size: 12px; margin-top: 8px;">Post Review <span style="margin-left: 12px;">→</span></button>
                    </form>
                </div>
            </div>

            <!-- Right: Review List -->
            <div class="review-list" style="padding-top: 32px;">
                <?php if (empty($reviews)): ?>
                    <div style="padding: 64px 32px; text-align: center; background: var(--off); border: 1px solid var(--light-gray); border-radius: 12px;">
                        <div style="font-size: 24px; margin-bottom: 20px; opacity: 0.3;">★</div>
                        <p style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: .2em; line-height: 1.8;">
                            The first review is yet to be critiqued.<br>
                            <span style="color: var(--ink); font-weight: 700;">Share your experience above.</span>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="review-item" style="padding: 24px 0; border-bottom: 1px solid var(--light-gray);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <div>
                                    <div style="font-family: var(--f-display); font-weight: 800; font-size: 16px; text-transform: uppercase; color: var(--ink); line-height: 1; letter-spacing: .02em;"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
                                    <div style="font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); margin-top: 8px; text-transform: uppercase; letter-spacing: .1em;"><?= date('M d, Y', strtotime($rev['created_at'])) ?> — Verified Tech</div>
                                </div>
                                <div style="color: var(--red); font-size: 10px; display: flex; gap: 4px;">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <span style="color: <?= $i <= $rev['rating'] ? 'var(--red)' : 'var(--light-gray)' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p style="font-family: var(--f-body); font-size: 14px; line-height: 1.7; color: var(--ink); font-weight: 400; opacity: .8;"><?= nl2br(htmlspecialchars($rev['body'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ── RELATED PRODUCTS ──────────────────────────────── -->
<?php if (!empty($related)): ?>
<section class="related-products" style="padding: 120px 0; background: var(--white); border-top: 1px solid var(--light-gray);">
    <div class="container">
        <div class="sec-eyebrow">
            <span class="eyebrow-text">You May Also Like</span>
            <span class="eyebrow-line"></span>
        </div>
        <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 48px; text-transform: uppercase; margin-bottom: 64px;">Related<br>Drops</h2>
        
        <div class="product-grid">
            <?php foreach ($related as $p): ?>
                <?php require __DIR__ . '/../components/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
