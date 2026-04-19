<?php
/**
 * views/components/product-card.php
 * REUSABLE MASTER COMPONENT
 * Expects: $p (Product array), $wishlistIds (Array of IDs in wishlist)
 */

$avg_rating = round($p['avg_rating'] ?? 0);
$brand = htmlspecialchars($p['brand_name'] ?? 'Gadget');
$imgUrl = $p['primary_image'] ?: 'https://via.placeholder.com/400x400';
if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
    $imgUrl = APP_PATH . '/' . ltrim($imgUrl, '/');
}

global $dbSettings;
$sliderEnabled = !isset($dbSettings['product_card_slider_enabled']) || $dbSettings['product_card_slider_enabled'] == '1';

$processedCardImages = [];
if ($sliderEnabled) {
    $db = db();
    $stmt = $db->prepare("SELECT url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 5");
    $stmt->execute([$p['id']]);
    $cardImagesRaw = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($cardImagesRaw as $url) {
        $processedCardImages[] = filter_var($url, FILTER_VALIDATE_URL) ? $url : APP_PATH . '/' . ltrim($url, '/');
    }
}

if (empty($processedCardImages)) $processedCardImages[] = $imgUrl;
?>

<div class="card">
    <!-- Action Arrow (Top Right) -->
    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-action-arrow" aria-label="View Product">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </a>

    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-link-block">
        <div class="card-img-wrap" onmouseenter="const v = this.querySelector('video'); if(v){v.style.opacity=1; v.play();}" onmouseleave="const v = this.querySelector('video'); if(v){v.style.opacity=0; v.pause();}">
            <?php if ($p['compare_at_price_ghs'] > $p['price_ghs']): ?>
                <span class="card-tag discount">HOT</span>
            <?php elseif (!empty($p['is_new_arrival'])): ?>
                <span class="card-tag new">NEW</span>
            <?php endif; ?>
            
            <div class="card-img <?= $sliderEnabled && count($processedCardImages) > 1 ? 'card-auto-slider' : '' ?>" style="position: relative;">
                <?php foreach ($processedCardImages as $idx => $src): ?>
                    <img src="<?= $src ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" class="slide-img" style="<?= $idx === 0 ? 'transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1); opacity: 1; transform: scale(1) translateY(0);' : 'position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; opacity:0; transform: scale(1.05) translateY(8px); transition: all 0.8s cubic-bezier(0.25, 1, 0.5, 1);' ?>">
                <?php endforeach; ?>
                <?php if (!empty($p['video_url'])): 
                    $vidUrl = filter_var($p['video_url'], FILTER_VALIDATE_URL) ? $p['video_url'] : APP_PATH . '/' . ltrim($p['video_url'], '/');
                ?>
                    <video src="<?= $vidUrl ?>" muted loop playsinline style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; opacity:0; transition: opacity 0.3s; z-index: 2; pointer-events:none;"></video>
                <?php endif; ?>
            </div>

            <div class="card-actions">
                <!-- Add to Wishlist -->
                <button type="button" 
                        class="card-wish-btn wish-btn-<?= $p['id'] ?> <?= in_array($p['id'], $wishlistIds ?? []) ? 'active' : '' ?>" 
                        onclick="toggleWishlist(<?= $p['id'] ?>, event)"
                        aria-label="Add to Wishlist">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= in_array($p['id'], $wishlistIds ?? []) ? 'var(--red)' : 'none' ?>" stroke="<?= in_array($p['id'], $wishlistIds ?? []) ? 'var(--red)' : 'var(--ink)' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </button>

                <!-- Share Product -->
                <button type="button" 
                        class="card-share-btn" 
                        onclick="openShareModal('<?= APP_URL ?>/product/<?= $p['slug'] ?>', '<?= addslashes($p['name']) ?>', event)"
                        aria-label="Share Product">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"></circle>
                        <circle cx="6" cy="12" r="3"></circle>
                        <circle cx="18" cy="19" r="3"></circle>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                    </svg>
                </button>

                <!-- Quick Add to Bag -->
                <button type="button" 
                        class="card-cart-btn"
                        onclick="quickAddToCart(<?= $p['id'] ?>, event)"
                        aria-label="Add to Bag">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                        <path d="M3 6h18"></path>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="card-cat"><?= $brand ?></div>
            <div class="card-name"><?= htmlspecialchars($p['name']) ?></div>

            <div class="card-rating <?= ($avg_rating <= 0) ? 'faded' : '' ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= ($i <= ($avg_rating ?: 5)) ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
            </div>
            
            <div class="card-price-area">
                <div class="card-price">₵<?= number_format($p['price_ghs'], 0) ?></div>
                <?php if($p['compare_at_price_ghs'] > $p['price_ghs']): ?>
                    <div class="card-price-old">₵<?= number_format($p['compare_at_price_ghs'], 0) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </a>
</div>
