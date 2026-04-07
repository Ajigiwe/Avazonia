<?php include __DIR__ . '/../layout/head.php'; ?>
<?php include __DIR__ . '/../layout/nav.php'; ?>

<style>
    :root {
        --deals-bg: #FFFFFF;
        --deals-card: #FFFFFF;
        --deals-border: #E8E8E8;
        --deals-text: var(--ink);
        --deals-muted: #999999;
        --deals-accent: #f8f8f8;
    }
    
    @keyframes pulse {
        0% { opacity: 0.4; transform: scale(0.95); }
        50% { opacity: 1; transform: scale(1.05); }
        100% { opacity: 0.4; transform: scale(0.95); }
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .deals-page {
        background: var(--deals-bg);
        background-image: 
            radial-gradient(at 0% 0%, rgba(229, 0, 26, 0.03) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(0, 136, 255, 0.03) 0px, transparent 50%);
        color: var(--deals-text);
        min-height: 100vh;
        padding-top: 180px;
        padding-bottom: 120px;
        overflow-x: hidden;
    }

    .deals-hero {
        text-align: left;
        margin-bottom: 120px;
        max-width: 900px;
        animation: slideIn 0.8s var(--ease) auto forwards;
    }
    
    .hero-pre {
        font-family: var(--f-mono);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .4em;
        color: var(--red);
        font-weight: 800;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .hero-pre::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(to right, var(--red), transparent);
        opacity: 0.2;
    }

    .deals-hero h1 {
        font-family: var(--f-display);
        font-size: clamp(56px, 10vw, 110px);
        font-weight: 800;
        line-height: .9;
        text-transform: uppercase;
        letter-spacing: -.04em;
        margin: 0;
        color: var(--ink);
    }
    
    .hero-outline {
        -webkit-text-stroke: 1px var(--ink);
        color: transparent;
    }

    .deals-section { 
        margin-bottom: 160px;
        opacity: 0;
        transform: translateY(40px);
        transition: all 0.8s var(--ease);
    }
    .deals-section.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .deals-sec-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 64px;
        position: relative;
    }
    
    .deals-sec-title {
        font-family: var(--f-display);
        font-size: 40px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -.02em;
        line-height: 1;
    }
    
    .live-dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--red);
        margin-right: 12px;
        animation: pulse 1.5s infinite;
    }

    .deals-sec-tag {
        font-family: var(--f-mono);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .15em;
        color: var(--mid-gray);
        background: var(--deals-accent);
        padding: 8px 16px;
        border-radius: 100px;
    }

    .deals-grid {
        display: grid;
        grid-template-columns: repeat(<?= GRID_DENSITY ?>, 1fr);
        gap: 40px;
    }
    @media (max-width: 1024px) { .deals-grid { grid-template-columns: repeat(2, 1fr); } }

    .deal-card {
        text-decoration: none; color: inherit; display: block;
        transition: all .4s var(--ease);
        position: relative;
    }
    
    .deal-img-wrap {
        width: 100%; aspect-ratio: 1;
        background: var(--deals-accent);
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        margin-bottom: 28px;
        border: 1px solid transparent;
        transition: all .4s var(--ease);
    }
    
    .deal-card:hover .deal-img-wrap {
        background: #fff;
        border-color: var(--deals-border);
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        transform: translateY(-8px);
    }

    .deal-img {
        width: 100%; height: 100%;
        object-fit: contain;
        padding: 48px;
        transition: transform .8s var(--ease);
    }
    .deal-card:hover .deal-img { transform: scale(1.08); }
    
    .deal-badge {
        position: absolute; top: 24px; left: 24px;
        background: var(--ink);
        color: #fff;
        font-family: var(--f-mono);
        font-size: 9px; font-weight: 700;
        padding: 6px 14px;
        text-transform: uppercase;
        letter-spacing: .15em;
        z-index: 2;
        border-radius: 2px;
    }
    .deal-badge.sale { background: var(--red); }
    .deal-badge.global { background: #fff; color: var(--ink); border: 1px solid var(--deals-border); }

    .deal-cat {
        font-family: var(--f-semi); font-size: 9px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .15em;
        color: var(--mid-gray); margin-bottom: 12px;
    }
    .deal-title {
        font-family: var(--f-display); font-size: 18px; font-weight: 700;
        color: var(--ink); margin-bottom: 16px;
        line-height: 1.25; height: 44px; overflow: hidden;
    }
    .deal-price-row {
        display: flex; align-items: baseline; gap: 12px;
    }
    .deal-price {
        font-family: var(--f-display); font-size: 20px; font-weight: 800;
        color: var(--ink);
    }
    .deal-price-old {
        font-family: var(--f-mono); font-size: 13px;
        text-decoration: line-through; color: var(--mid-gray);
    }
    .save-tag {
        margin-left: auto;
        font-family: var(--f-mono); font-size: 9px; font-weight: 700;
        color: var(--red);
        text-transform: uppercase;
    }
</style>

<main class="deals-page">
    <div class="container">
        
        <div class="deals-hero">
            <div class="hero-pre">Exclusive Opportunity Hub</div>
            <h1>Flash<br><span class="hero-outline">Deals</span> & Drops</h1>
        </div>

        <?php if (!empty($preorders)): ?>
        <!-- ── PRE-ORDERS ────────────────────────────────────── -->
        <section class="deals-section">
            <div class="deals-sec-header">
                <h2 class="deals-sec-title">Upcoming Tech</h2>
                <span class="deals-sec-tag">Secure your unit / Presale</span>
            </div>
            <div class="deals-grid">
                <?php foreach ($preorders as $p): ?>
                <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="deal-card">
                    <div class="deal-img-wrap">
                        <span class="deal-badge preorder">Pre-order</span>
                        <img src="<?= $p['primary_image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="deal-img">
                        <button class="card-wishlist wish-btn-<?= $p['id'] ?> <?= in_array($p['id'], $wishlistIds ?? []) ? 'active' : '' ?>" 
                                aria-label="Add to wishlist" 
                                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?= $p['id'] ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                    </div>
                    <div class="deal-body">
                        <div class="deal-cat"><?= htmlspecialchars($p['brand_name'] ?? 'Gadget') ?></div>
                        
                        <h3 class="deal-title"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="deal-price-row">
                            <span class="deal-price"><?= number_format($p['price_ghs'], 2) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($deals)): ?>
        <!-- ── FLASH DEALS ───────────────────────────────────── -->
        <section class="deals-section">
            <div class="deals-sec-header">
                <h2 class="deals-sec-title"><span class="live-dot"></span>Limited Discounts</h2>
                <span class="deals-sec-tag">Up to 40% OFF</span>
            </div>
            <div class="deals-grid">
                <?php foreach ($deals as $p): ?>
                <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="deal-card">
                    <div class="deal-img-wrap">
                        <?php 
                        $pct = round((($p['compare_at_price_ghs'] - $p['price_ghs']) / $p['compare_at_price_ghs']) * 100);
                        ?>
                        <span class="deal-badge">-<?= $pct ?>%</span>
                        <img src="<?= $p['primary_image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="deal-img">
                        <button class="card-wishlist wish-btn-<?= $p['id'] ?> <?= in_array($p['id'], $wishlistIds ?? []) ? 'active' : '' ?>" 
                                aria-label="Add to wishlist" 
                                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?= $p['id'] ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                    </div>
                    <div class="deal-body">
                        <div class="deal-cat"><?= htmlspecialchars($p['brand_name'] ?? 'Deal') ?></div>
                        <?php if (!empty($p['tags'])): ?>
                            <div class="card-tags-mini" style="display: flex; gap: 4px; margin: 4px 0 8px; overflow: hidden; white-space: nowrap;">
                                <?php 
                                $ptags = explode(',', $p['tags']);
                                foreach (array_slice($ptags, 0, 2) as $tag): 
                                    if (trim($tag)):
                                ?>
                                    <span style="font-family: var(--f-mono); font-size: 8px; text-transform: uppercase; color: var(--mid-gray); background: var(--off); padding: 2px 6px; border-radius: 2px; border: 1px solid var(--light-gray);"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="deal-title"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="deal-price-row">
                            <span class="deal-price">₵<?= number_format($p['price_ghs'], 2) ?></span>
                            <span class="deal-price-old">₵<?= number_format($p['compare_at_price_ghs'], 2) ?></span>
                            <span class="save-tag">SAVE ₵<?= number_format($p['compare_at_price_ghs'] - $p['price_ghs'], 0) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($dropshipping)): ?>
        <!-- ── GLOBAL DIRECT ─────────────────────────────────── -->
        <section class="deals-section">
            <div class="deals-sec-header">
                <h2 class="deals-sec-title">Global Direct</h2>
                <span class="deals-sec-tag">Shipped from International Hubs</span>
            </div>
            <div class="deals-grid">
                <?php foreach ($dropshipping as $p): ?>
                <a href="<?= APP_URL ?>/product/<?= $p['slug'] ?>" class="deal-card">
                    <div class="deal-img-wrap">
                        <span class="deal-badge global">Global Direct</span>
                        <img src="<?= $p['primary_image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="deal-img">
                        <button class="card-wishlist wish-btn-<?= $p['id'] ?> <?= in_array($p['id'], $wishlistIds ?? []) ? 'active' : '' ?>" 
                                aria-label="Add to wishlist" 
                                onclick="event.preventDefault(); event.stopPropagation(); toggleWishlist(<?= $p['id'] ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                    </div>
                    <div class="deal-body">
                        <div class="deal-cat">7-14 Days Transit</div>
                        <?php if (!empty($p['tags'])): ?>
                            <div class="card-tags-mini" style="display: flex; gap: 4px; margin: 4px 0 8px; overflow: hidden; white-space: nowrap;">
                                <?php 
                                $ptags = explode(',', $p['tags']);
                                foreach (array_slice($ptags, 0, 2) as $tag): 
                                    if (trim($tag)):
                                ?>
                                    <span style="font-family: var(--f-mono); font-size: 8px; text-transform: uppercase; color: var(--mid-gray); background: var(--off); padding: 2px 6px; border-radius: 2px; border: 1px solid var(--light-gray);"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="deal-title"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="deal-price-row">
                            <span class="deal-price"><?= number_format($p['price_ghs'], 2) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<script>
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.deals-section').forEach(section => {
        observer.observe(section);
    });
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
