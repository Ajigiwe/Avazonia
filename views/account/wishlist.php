<?php
// views/account/wishlist.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$user_name = Session::get('user_name') ?: 'Member';
?>

<section class="wishlist-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">
        
        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?= APP_URL ?>" style="color: inherit; text-decoration: none;">Avazonia</a>
                <span>/</span>
                <a href="<?= APP_URL ?>/account" style="color: inherit; text-decoration: none;">Account</a>
                <span>/</span>
                <span style="color: var(--ink);">Wishlist</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px; flex-wrap: wrap; gap: 12px;">
                <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">My Favorites</h1>
                <div style="font-family: var(--f-mono); font-size: 11px; font-weight: 700; color: var(--mid-gray);"><?= count($items) ?> Items</div>
            </div>
        </nav>

        <div class="account-grid">
            
            <!-- Sidebar -->
            <aside class="account-sidebar">
                <div style="position: sticky; top: 120px;">
                    <nav style="display: flex; flex-direction: column; gap: 4px;">
                        <a href="<?= APP_URL ?>/account" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
                            <span style="font-size: 16px;">📊</span> Dashboard
                        </a>
                        <a href="<?= APP_URL ?>/orders" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
                            <span style="font-size: 16px;">📦</span> My Orders
                        </a>
                        <a href="<?= APP_URL ?>/wishlist" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--off); border-radius: 8px; text-decoration: none; color: var(--red); font-weight: 700; font-size: 13px;">
                            <span style="font-size: 16px;">💖</span> Wishlist
                        </a>
                        <a href="<?= APP_URL ?>/account/settings" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px; transition: 0.2s;">
                            <span style="font-size: 16px;">⚙️</span> Profile Settings
                        </a>
                        <div style="margin: 12px 0; border-top: 1px solid #eee;"></div>
                        <a href="<?= APP_URL ?>/logout" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #f5222d; font-size: 13px;">
                            <span style="font-size: 16px;">👋</span> Logout
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Wishlist Content -->
            <div class="wishlist-content">
                <?php if (empty($items)): ?>
                    <div style="padding: 80px 40px; text-align: center; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                        <span style="font-size: 40px; display: block; margin-bottom: 16px;">🖤</span>
                        <p style="font-weight: 700; font-size: 16px; color: var(--ink); margin-bottom: 8px;">Your wishlist is empty.</p>
                        <p style="font-size: 13px; color: var(--mid-gray); margin-bottom: 24px;">Save items you love and they'll appear here.</p>
                        <a href="<?= APP_URL ?>/shop" style="display: inline-block; padding: 12px 32px; background: var(--ink); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em;">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="wishlist-list" style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($items as $item): ?>
                            <div class="wish-card" id="wish-<?= $item['product_id'] ?>">
                                <a href="<?= APP_URL ?>/product/<?= $item['slug'] ?>" style="width: 80px; height: 80px; background: #f9f9f9; border-radius: 8px; overflow: hidden; display: block;">
                                    <?php 
                                    $wishImg = $item['primary_image'];
                                    if (!filter_var($wishImg, FILTER_VALIDATE_URL)) {
                                        $wishImg = APP_PATH . '/' . ltrim($wishImg, '/');
                                    }
                                    ?>
                                    <img src="<?= $wishImg ?>" alt="<?= $item['name'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </a>
                                
                                <div>
                                    <a href="<?= APP_URL ?>/product/<?= $item['slug'] ?>" style="text-decoration: none; color: var(--ink); font-weight: 800; font-size: 16px; display: block; margin-bottom: 4px;"><?= $item['name'] ?></a>
                                    <div style="display: flex; align-items: baseline; gap: 8px;">
                                        <span style="font-weight: 900; color: var(--red); font-size: 18px;">₵<?= number_format($item['price_ghs'], 2) ?></span>
                                        <?php if ($item['compare_at_price_ghs']): ?>
                                            <span style="text-decoration: line-through; color: var(--mid-gray); font-size: 12px;">₵<?= number_format($item['compare_at_price_ghs'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['is_preorder']): ?>
                                        <div style="display: inline-block; margin-top: 8px; padding: 2px 8px; background: #fff1f0; color: #f5222d; font-size: 10px; font-weight: 800; border-radius: 4px; border: 1px solid rgba(245,34,45,0.1); text-transform: uppercase;">Pre-order</div>
                                    <?php endif; ?>
                                </div>

                                <div class="wish-actions">
                                    <form class="ajax-cart-form" action="<?= APP_URL ?>/api/cart-add" method="POST">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" style="padding: 10px 20px; background: var(--ink); color: #fff; border: none; border-radius: 100px; cursor: pointer; font-weight: 700; font-size: 12px; text-transform: uppercase;">Add to Cart</button>
                                    </form>
                                    <button onclick="toggleWishlist(<?= $item['product_id'] ?>, true)" style="background: none; border: none; color: #ff4d4f; cursor: pointer; padding: 8px;" title="Remove">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 6L5 18M5 6l14 14"></path></svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .wish-card:hover { border-color: var(--ink) !important; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    aside a:hover { background: var(--off); color: var(--ink) !important; opacity: 1 !important; }
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
