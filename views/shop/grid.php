<?php
// views/shop/grid.php
?>
<?php foreach ($products as $p): ?>
    <?php require __DIR__ . '/../components/product-card.php'; ?>
<?php endforeach; ?>

<?php if (empty($products)): ?>
    <p style="grid-column: span 3; color: var(--mid-gray); font-family: var(--f-body); font-size: 14px; padding: 40px 0; text-align: center;">No products found in this category.</p>
<?php endif; ?>
