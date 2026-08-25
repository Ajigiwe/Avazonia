<?php
// views/shop/category.php — Subcategory list (Jiji-style)
// Expects: $category, $children (with product_count), $breadcrumbs, $totalInSubtree, $categories
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$catName = $category['name'] ?? 'Category';
$catSlug = $category['slug'] ?? '';
$parentCrumb = count($breadcrumbs) > 1 ? $breadcrumbs[count($breadcrumbs)-2] : null;
?>
<style>
/* Fix: main nav is fixed and was covering category header. Hide main nav on this page for Jiji-like full-width header. */
.cat-list-page { background: #f5f5f5; min-height: 60vh; padding: 72px 0 40px; }
.cat-list-header-bar {
  background: <?= PRIMARY_COLOR ?>;
  padding: 12px 16px;
  position: sticky;
  top: 56px;
  z-index: 5;
}
@media (max-width: 768px) {
  /* On mobile, hide main AVAZONIA nav for category drill-down (matches Jiji 2nd image — only green/red bar shown) */
  .nav, #main-nav { display: none !important; }
  body { padding-bottom: 74px !important; }
  .cat-list-page { padding-top: 0 !important; padding-bottom: 90px; }
  .cat-list-header-bar { top: 0 !important; position: sticky; border-radius: 0 !important; z-index: 100; }
  #page-wrapper { padding-top: 0 !important; }
}
@media (max-width: 768px) and (display-mode: standalone) {
  .cat-list-header-bar { padding-top: calc(12px + env(safe-area-inset-top, 0px)); }
}
.cat-list-search-wrap {
  max-width: 640px;
  margin: 0 auto;
  position: relative;
}
.cat-list-search-wrap .search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}
.cat-list-search-input {
  width: 100%;
  padding: 12px 14px 12px 42px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-family: var(--f-body, sans-serif);
  outline: none;
  box-shadow: 0 1px 4px rgba(0,0,0,0.12);
}
.cat-list-container {
  max-width: 640px;
  margin: 16px auto;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.cat-list-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-bottom: 1px solid #f0f0f0;
  text-decoration: none;
  color: inherit;
  transition: background 0.15s;
}
.cat-list-row:last-child { border-bottom: none; }
.cat-list-row:hover { background: #fafafa; }
.cat-list-row:active { background: #f0f0f0; }
.cat-list-thumb {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  background: #f8f8f8;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid #eee;
}
.cat-list-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.cat-list-thumb .fallback-icon {
  font-size: 20px;
}
.cat-list-info { flex: 1; min-width: 0; }
.cat-list-name {
  font-size: 14px;
  font-weight: 600;
  color: #222;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cat-list-count {
  font-size: 12px;
  color: #888;
  margin-top: 2px;
}
.cat-list-chevron {
  color: #ccc;
  flex-shrink: 0;
}
.cat-list-back {
  max-width: 640px;
  margin: 12px auto 0;
  padding: 0 16px;
}
.cat-list-back a {
  font-size: 13px;
  color: #666;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.cat-list-back a:hover { color: var(--ink); }
.cat-list-viewall {
  display: block;
  text-align: center;
  padding: 16px;
  font-size: 13px;
  font-weight: 700;
  color: <?= PRIMARY_COLOR ?>;
  text-decoration: none;
  border-top: 1px solid #f0f0f0;
  background: #fff;
}
.cat-list-viewall:hover { background: #fafafa; }
.cat-list-empty {
  padding: 40px 20px;
  text-align: center;
  color: #888;
  font-size: 14px;
}
@media (min-width: 768px) {
  .cat-list-page { padding: 80px 0 60px; }
  .cat-list-header-bar { border-radius: 8px 8px 0 0; max-width: 640px; margin: 0 auto; top: 64px; }
}
</style>

<div class="cat-list-page">
  <!-- Green/red search bar (Jiji-style) -->
  <div class="cat-list-header-bar">
    <div class="cat-list-search-wrap">
      <span class="search-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </span>
      <input type="text" id="cat-search" class="cat-list-search-input" placeholder="Find category..." autocomplete="off">
    </div>
  </div>

  <!-- Back link -->
  <div class="cat-list-back">
    <?php if ($parentCrumb): ?>
      <a href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($parentCrumb['slug']) ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Back to <?= htmlspecialchars($parentCrumb['name']) ?>
      </a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Home
      </a>
    <?php endif; ?>
    <span style="float:right; font-size:12px; color:#999; line-height: 20px;"><?= htmlspecialchars($catName) ?></span>
  </div>

  <div class="cat-list-container" id="cat-list">
    <?php if (!empty($children)): ?>
      <?php foreach ($children as $sub):
        $thumb = $sub['image_url'] ?? '';
        $hasImg = !empty($thumb);
        if ($hasImg && !filter_var($thumb, FILTER_VALIDATE_URL)) {
          $thumb = APP_URL . '/' . ltrim($thumb, '/');
        }
        $count = (int)($sub['product_count'] ?? 0);
        $countLabel = $count === 1 ? '1 ad' : number_format($count) . ' ads';
      ?>
        <a href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($sub['slug']) ?>" class="cat-list-row" data-name="<?= htmlspecialchars(strtolower($sub['name'])) ?>">
          <div class="cat-list-thumb">
            <?php if ($hasImg): ?>
              <img src="<?= htmlspecialchars($thumb) ?>" alt="<?= htmlspecialchars($sub['name']) ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
              <span class="fallback-icon" style="display:none;"><?= htmlspecialchars($sub['icon'] ?? '📦') ?></span>
            <?php else: ?>
              <span class="fallback-icon"><?= htmlspecialchars($sub['icon'] ?? '📦') ?></span>
            <?php endif; ?>
          </div>
          <div class="cat-list-info">
            <div class="cat-list-name"><?= htmlspecialchars($sub['name']) ?></div>
            <div class="cat-list-count"><?= $countLabel ?></div>
          </div>
          <span class="cat-list-chevron">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          </span>
        </a>
      <?php endforeach; ?>

      <!-- View all products in parent -->
      <a href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($catSlug) ?>&view=all" class="cat-list-viewall">
        View all <?= number_format($totalInSubtree) ?> ads in <?= htmlspecialchars($catName) ?> →
      </a>
    <?php else: ?>
      <div class="cat-list-empty">
        No subcategories yet.<br>
        <a href="<?= APP_URL ?>/shop?cat=<?= htmlspecialchars($catSlug) ?>&view=all" style="color:<?= PRIMARY_COLOR ?>; font-weight:700; text-decoration:none;">View <?= htmlspecialchars($catName) ?> products →</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
  const input = document.getElementById('cat-search');
  const rows = document.querySelectorAll('#cat-list .cat-list-row');
  if (!input || !rows.length) return;
  input.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    rows.forEach(function(row) {
      const name = row.getAttribute('data-name') || '';
      row.style.display = (!q || name.indexOf(q) !== -1) ? 'flex' : 'none';
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
