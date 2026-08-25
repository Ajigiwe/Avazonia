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
                <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                    <?= $currentCat ? strtoupper($currentCat) : 'ALL PRODUCTS' ?>
                </h2>
            </div>
            <div style="display:flex; align-items:center; gap:16px;">
                <div class="view-toggle" role="group" aria-label="View toggle">
                    <button id="view-grid" class="view-btn active" aria-pressed="true" onclick="setProductView('grid')" title="Grid view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </button>
                    <button id="view-list" class="view-btn" aria-pressed="false" onclick="setProductView('list')" title="List view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </button>
                </div>
                <div style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; letter-spacing: 0.1em;">
                    Showing <?= $pagination['total'] ?> items
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
            <?php require __DIR__ . '/grid.php'; ?>
        </div>

        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="shop-pagination">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $baseQuery = http_build_query($queryParams);
            $baseUrl = APP_URL . '/shop' . ($baseQuery ? '?' . $baseQuery : '');
            $sep = $baseQuery ? '&' : '?';
            ?>
            <?php if ($pagination['hasPrev']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] - 1) ?>" class="page-btn">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $pagination['page'] - 2);
            $end = min($pagination['totalPages'], $pagination['page'] + 2);
            if ($start > 1) echo '<span class="page-dots">...</span>';
            for ($i = $start; $i <= $end; $i++):
                $isActive = $i === $pagination['page'];
            ?>
                <a href="<?= $baseUrl . $sep . 'page=' . $i ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; 
            if ($end < $pagination['totalPages']) echo '<span class="page-dots">...</span>';
            ?>

            <?php if ($pagination['hasNext']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] + 1) ?>" class="page-btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
