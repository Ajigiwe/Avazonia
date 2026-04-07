<?php
// views/shop/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<?php require_once __DIR__ . '/../layout/hero.php'; ?>

<section class="shop-content" style="padding: 120px 0 80px;">
    <div class="container">
        <div class="sec-head reveal">
            <div>
                <div class="sec-over">THE DROP</div>
                <h2 class="hero-heading" style="color: var(--ink); font-size: 64px; margin-bottom: 0; line-height: 0.85;">
                    <?= $currentCat ? strtoupper($currentCat) : 'ALL PRODUCTS' ?>
                </h2>
            </div>
            <div style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; letter-spacing: 0.1em;">
                Showing <?= count($products) ?> items
            </div>
        </div>

        <!-- Product Grid -->
        <div id="product-grid" class="products-grid">
            <?php require 'grid.php'; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
