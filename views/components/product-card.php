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
?>

<div class="card">
    <!-- Action Arrow (Top Right) -->
    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-action-arrow" aria-label="View Product">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </a>

    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-link-block">
        <div class="card-img-wrap">
            <?php if ($p['compare_at_price_ghs'] > $p['price_ghs']): ?>
                <span class="card-tag discount">HOT</span>
            <?php elseif (!empty($p['is_new_arrival'])): ?>
                <span class="card-tag new">NEW</span>
            <?php endif; ?>
            
            <div class="card-img">
                <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
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
                <div class="card-price">₵<?= number_format($p['price_ghs'], 0) ?><?php if($p['compare_at_price_ghs'] > $p['price_ghs']): ?> - ₵<?= number_format($p['compare_at_price_ghs'], 0) ?><?php endif; ?></div>
            </div>
        </div>
    </a>
</div>
