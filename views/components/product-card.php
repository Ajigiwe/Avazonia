<?php
/**
 * views/components/product-card.php
 * REUSABLE MASTER COMPONENT
 * Expects: $p (Product array), $wishlistIds (Array of IDs in wishlist)
 */

$avg_rating = round($p['avg_rating'] ?? 0);
$category = htmlspecialchars($p['category_name'] ?? 'Gadget');
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

$contactWhatsApp = preg_replace('/[^0-9]/', '', (string)($p['seller_whatsapp'] ?? ''));
$contactPhone   = preg_replace('/[^0-9]/', '', (string)($p['seller_phone'] ?? ''));
if ($contactWhatsApp === '' && $contactPhone === '') {
    // Avazonia Official products (no seller row) use the site contact number
    if (!function_exists('_avzOfficialContact')) {
        function _avzOfficialContact() {
            static $official = null;
            if ($official === null) {
                try {
                    require_once __DIR__ . '/../../models/Settings.php';
                    $official = preg_replace('/[^0-9]/', '', (string)(new Settings())->get('whatsapp_number', WHATSAPP_NUMBER));
                } catch (Throwable $e) {
                    $official = preg_replace('/[^0-9]/', '', WHATSAPP_NUMBER);
                }
            }
            return $official;
        }
    }
    $contactWhatsApp = _avzOfficialContact();
    $contactPhone    = $contactWhatsApp;
    $isOfficialStore = true;
}
$contactWeChat = trim((string)($p['seller_wechat'] ?? ''));
if ($contactPhone === '' && $contactWhatsApp !== '') $contactPhone = $contactWhatsApp; // WhatsApp numbers are callable
$contactMessage = rawurlencode("Hi, I'm interested in {$p['name']} on Avazonia: " . APP_URL . '/product/' . $p['slug']);
$contactType = $contactWhatsApp !== '' ? 'whatsapp' : ($contactWeChat !== '' ? 'wechat' : '');

if (empty($processedCardImages)) $processedCardImages[] = $imgUrl;
?>

<div class="card">
    <!-- Action Arrow (Top Right) -->
    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-action-arrow" aria-label="View Product">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
    </a>

    <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="card-link-block">
        <div class="card-img-wrap" onmouseenter="const v = this.querySelector('video'); if(v){v.style.opacity=1; v.play();}" onmouseleave="const v = this.querySelector('video'); if(v){v.style.opacity=0; v.pause();}">
            <?php if ($p['stock_qty'] <= 0 && empty($p['is_preorder']) && empty($p['is_dropshipping'])): ?>
                <span class="card-tag outofstock">OUT OF STOCK</span>
            <?php elseif (!empty($p['is_preorder'])): ?>
                <span class="card-tag preorder">PRE-ORDER</span>
            <?php elseif ($p['stock_qty'] > 0 && $p['stock_qty'] <= 5 && empty($p['is_preorder']) && empty($p['is_dropshipping'])): ?>
                <span class="card-tag lowstock">ONLY <?= (int)$p['stock_qty'] ?> LEFT</span>
            <?php elseif ($p['compare_at_price_ghs'] > $p['price_ghs']): ?>
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

                <?php if ($contactType !== '' || $contactPhone !== ''): ?>
                    <div class="card-contact-official"><?= $isOfficialStore ? 'Official Store' : 'Contact Seller' ?></div>
                    <?php if ($contactWhatsApp !== ''): ?>
                    <button type="button" class="card-contact-btn whatsapp" data-contact-url="<?= htmlspecialchars('https://wa.me/' . $contactWhatsApp . '?text=' . $contactMessage, ENT_QUOTES) ?>" aria-label="Enquire on WhatsApp" title="Enquire on WhatsApp" onclick="event.preventDefault(); event.stopPropagation(); window.open(this.dataset.contactUrl, '_blank', 'noopener');">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.03 2C6.5 2 2 6.48 2 12c0 1.76.46 3.42 1.32 4.87L2 22l5.27-1.38A9.96 9.96 0 0 0 12.03 22C17.55 22 22 17.52 22 12S17.55 2 12.03 2Zm0 18.2c-1.53 0-3.02-.41-4.34-1.18l-.31-.18-3.13.82.84-3.05-.2-.31A8.2 8.2 0 1 1 12.03 20.2Zm4.5-6.15c-.25-.13-1.48-.73-1.71-.81-.23-.08-.4-.13-.57.13-.17.25-.65.81-.79.98-.15.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.49-1.41-1.74-.15-.25-.02-.39.11-.51.12-.12.25-.29.38-.44.13-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.57-1.37-.78-1.88-.2-.49-.41-.42-.57-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1s.9 2.43 1.02 2.6c.13.17 1.76 2.68 4.27 3.76.6.26 1.07.42 1.43.53.6.19 1.15.16 1.58.1.48-.07 1.48-.61 1.69-1.2.21-.59.21-1.1.15-1.2-.06-.11-.23-.17-.48-.3Z"/></svg>
                    </button>
                    <?php if ($contactPhone !== ''): ?>
                    <button type="button" class="card-contact-btn call" data-contact-url="tel:<?= htmlspecialchars('+' . $contactPhone, ENT_QUOTES) ?>" aria-label="Call seller" title="Call seller" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.dataset.contactUrl;">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.85 21 3 13.15 3 3.5a1 1 0 0 1 1-1H7.5a1 1 0 0 1 1 1c0 1.24.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2Z"/></svg>
                    </button>
                    <?php endif; ?>
                    <?php if ($contactWeChat !== ''): ?>
                    <button type="button" class="card-contact-btn wechat" data-contact-url="<?= htmlspecialchars('weixin://dl/chat?' . rawurlencode($contactWeChat), ENT_QUOTES) ?>" aria-label="Enquire on WeChat" title="WeChat: <?= htmlspecialchars($contactWeChat) ?>" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.dataset.contactUrl;">
                        <svg viewBox="0 0 36 24" fill="currentColor" aria-hidden="true"><path d="M11 2C5.48 2 1 5.58 1 10c0 2.35 1.2 4.48 3.2 5.92L3 20l4.35-2.18c1.1.38 2.3.58 3.65.58 5.52 0 10-3.58 10-8.4S16.52 2 11 2Zm0 14.4c-1.2 0-2.3-.2-3.25-.58l-.65-.25-1.45.73.42-1.42-.55-.42C4.25 13.55 3 11.88 3 10c0-3.3 3.6-6 8-6s8 2.7 8 6-3.6 6.4-8 6.4Z"/><path d="M25 7.5c5.52 0 10 3.05 10 7.2 0 1.84-.97 3.5-2.58 4.8l.55 2.5-3.3-1.35c-1.34.48-2.9.75-4.67.75-2.65 0-5.02-.67-6.78-1.8 1.9-.1 3.7-.62 5.2-1.55 1.65-1.02 2.62-2.45 2.62-4.05 0-2.3-1.55-4.3-4-5.55.9-.58 1.9-.95 2.96-.95Z"/></svg>
                    </button>
                    <?php endif; ?>
                <?php elseif ($contactWeChat !== ''): ?>
                    <div class="card-contact-official">Contact Seller</div>
                    <button type="button" class="card-contact-btn wechat" data-contact-url="<?= htmlspecialchars('weixin://dl/chat?' . rawurlencode($contactWeChat), ENT_QUOTES) ?>" aria-label="Enquire on WeChat" title="WeChat: <?= htmlspecialchars($contactWeChat) ?>" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.dataset.contactUrl;">
                        <svg viewBox="0 0 36 24" fill="currentColor" aria-hidden="true"><path d="M11 2C5.48 2 1 5.58 1 10c0 2.35 1.2 4.48 3.2 5.92L3 20l4.35-2.18c1.1.38 2.3.58 3.65.58 5.52 0 10-3.58 10-8.4S16.52 2 11 2Zm0 14.4c-1.2 0-2.3-.2-3.25-.58l-.65-.25-1.45.73.42-1.42-.55-.42C4.25 13.55 3 11.88 3 10c0-3.3 3.6-6 8-6s8 2.7 8 6-3.6 6.4-8 6.4Z"/><path d="M25 7.5c5.52 0 10 3.05 10 7.2 0 1.84-.97 3.5-2.58 4.8l.55 2.5-3.3-1.35c-1.34.48-2.9.75-4.67.75-2.65 0-5.02-.67-6.78-1.8 1.9-.1 3.7-.62 5.2-1.55 1.65-1.02 2.62-2.45 2.62-4.05 0-2.3-1.55-4.3-4-5.55.9-.58 1.9-.95 2.96-.95Z"/></svg>
                    </button>
                <?php elseif ($contactPhone !== ''): ?>
                    <div class="card-contact-official">Contact Seller</div>
                    <button type="button" class="card-contact-btn call" data-contact-url="tel:<?= htmlspecialchars('+' . $contactPhone, ENT_QUOTES) ?>" aria-label="Call seller" title="Call seller" onclick="event.preventDefault(); event.stopPropagation(); window.location.href=this.dataset.contactUrl;">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.85 21 3 13.15 3 3.5a1 1 0 0 1 1-1H7.5a1 1 0 0 1 1 1c0 1.24.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2Z"/></svg>
                    </button>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($p['stock_qty'] <= 0 && empty($p['is_preorder']) && empty($p['is_dropshipping'])): ?>
                    <button type="button" 
                            class="card-cart-btn disabled"
                            disabled
                            aria-label="Out of Stock">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                            <path d="M3 6h18"></path>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </button>
                <?php elseif (!empty($p['is_preorder'])): ?>
                    <button type="button" 
                            class="card-cart-btn preorder"
                            onclick="window.location.href='<?= APP_URL ?>/product/<?= $p['slug'] ?>'; event.preventDefault();"
                            aria-label="Pre-Order">
                        PRE
                    </button>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </div>

        <!-- Trio images for mobile list view (3 side-by-side) -->
        <?php $trioImages = array_slice($processedCardImages, 0, 3); ?>
        <div class="card-trio trio-count-<?= count($trioImages) ?>" data-images='<?= htmlspecialchars(json_encode($processedCardImages), ENT_QUOTES) ?>'>
            <?php foreach ($trioImages as $trioIdx => $src): ?>
            <div class="trio-slot">
                <img src="<?= $src ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            </div>
            <?php endforeach; ?>
            <div class="trio-price-overlay"><?= format_price($p) ?></div>
        </div>

        <div class="card-body">
            <div class="card-cat"><?= strtoupper($category) ?></div>
            <div class="card-name"><?= htmlspecialchars($p['name']) ?></div>

            <div class="card-rating <?= ($avg_rating <= 0) ? 'faded' : '' ?>">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?= ($i <= ($avg_rating ?: 5)) ? 'filled' : '' ?>">★</span>
                <?php endfor; ?>
            </div>
            
            <div class="card-price-area">
                <div class="card-price"><?= format_price($p) ?></div>
                <?php if (($p['currency'] ?? 'GHS') === 'USD' ? ($p['compare_at_price_usd'] ?? 0) > ($p['price_usd'] ?? 0) : ($p['compare_at_price_ghs'] ?? 0) > ($p['price_ghs'] ?? 0)): ?>
                    <div class="card-price-old"><?= format_compare_price($p) ?></div>
                <?php endif; ?>
            </div>
            <?php $showMkt = !empty($p['listing_type']) && $p['listing_type']!=='retail'; $showSeller = !empty($p['store_name']) || !empty($p['seller_name']); ?>
            <?php if ($showMkt || $showSeller): ?>
            <div class="card-seller-meta">
              <?php if ($showMkt): ?><?= listing_type_badge($p) ?><?php endif; ?>
              <?php if ($showSeller && !empty($p['store_slug'])): ?><a href="<?= APP_URL ?>/store/<?= htmlspecialchars($p['store_slug']) ?>" class="card-seller-link">Sold by: <?= htmlspecialchars($p['store_name'] ?: $p['seller_name']) ?> →</a><?php elseif ($showSeller): ?><span class="card-seller-name">Sold by: <?= htmlspecialchars($p['store_name'] ?: $p['seller_name']) ?></span><?php endif; ?>
              <?php if (!empty($p['verification_level']) && $p['verification_level']!=='unverified'): ?><span class="card-seller-verified"><?php if (($p['verification_level'] ?? '') === 'business_verified'): ?>✓ Verified Business<?php elseif (($p['verification_level'] ?? '') === 'company_verified'): ?>✓ Verified Supplier<?php elseif (($p['verification_level'] ?? '') === 'avazonia_verified'): ?>★ Avazonia Verified<?php else: ?>✓ Verified<?php endif; ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </a>
</div>
