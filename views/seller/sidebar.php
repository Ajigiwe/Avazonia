<?php
// views/seller/sidebar.php
// Expected: $seller, $page, $stats
$page = $page ?? 'overview';
$basePath = APP_URL . '/seller';
?>
<style>
/* ── Override nav offset since site nav is hidden ── */
#page-wrapper { padding-top: 0 !important; }
.wa-btn { z-index: 9999 !important; }

/* ── Seller Layout ── */
.seller-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
.seller-sidebar {
    background: var(--ink); color: #fff; padding: 0;
    display: flex; flex-direction: column;
    position: sticky; top: 0; height: 100vh; overflow-y: auto;
    z-index: 900;
}
.seller-sidebar-brand { padding: 24px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
.seller-sidebar-brand .store-name { font-family: var(--f-display); font-weight: 900; font-size: 14px; line-height: 1.2; }
.seller-sidebar-brand .seller-type { font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(255,255,255,0.5); margin-top: 4px; }
.seller-nav { padding: 12px 10px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.seller-nav a {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    color: rgba(255,255,255,0.5); font-family: var(--f-semi); font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.08em; text-decoration: none;
    border-radius: 6px; transition: all 0.2s; font-weight: 600;
}
.seller-nav a:hover { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.9); }
.seller-nav a.active { background: var(--red); color: #fff; font-weight: 700; box-shadow: 0 4px 16px rgba(232,0,45,0.3); }
.seller-nav a .nav-icon { font-size: 16px; width: 20px; text-align: center; }
.seller-nav a .badge { margin-left: auto; background: rgba(255,255,255,0.15); padding: 2px 8px; border-radius: 99px; font-size: 9px; font-family: var(--f-mono); }
.seller-nav a.active .badge { background: rgba(255,255,255,0.25); }
.seller-sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.08); }
.seller-sidebar-footer a { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.4); font-size: 10px; text-decoration: none; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; transition: color 0.2s; padding: 4px 0; }
.seller-sidebar-footer a:hover { color: #fff; }
.seller-content { padding: 32px 40px; background: #fff; min-height: 100vh; overflow-x: hidden; }
.seller-stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 32px; }
.seller-stat-card { border: 2px solid var(--ink); padding: 20px; }
.seller-stat-card .stat-label { font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mid-gray); margin-bottom: 6px; }
.seller-stat-card .stat-value { font-family: var(--f-display); font-weight: 900; font-size: 28px; color: var(--ink); line-height: 1; }
.seller-stat-card .stat-sub { font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); margin-top: 6px; }
.seller-dash-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.seller-mobile-cards { display: none; }
.seller-table-wrap { border: 2px solid var(--ink); overflow-x: auto; }
.seller-table-wrap table { width: 100%; border-collapse: collapse; min-width: 700px; }

/* ── Mobile ── */
@media (max-width: 900px) {
    .seller-layout { grid-template-columns: 1fr; }
    .seller-sidebar { display: none; }
    .seller-content { padding: 16px; }
    .seller-stats-bar { grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 20px; }
    .seller-stat-card { padding: 14px; }
    .seller-stat-card .stat-value { font-size: 22px; }
    .mobile-seller-top { display: flex !important; align-items: center; gap: 12px; padding: 12px 16px; background: var(--ink); }
    .seller-hamburger { background: none; border: none; color: #fff; cursor: pointer; padding: 4px; display: flex; align-items: center; }
    .seller-mobile-brand { color: #fff; font-family: var(--f-display); font-weight: 900; font-size: 14px; text-decoration: none; flex: 1; }
    .seller-mobile-back { color: rgba(255,255,255,0.5); font-family: var(--f-mono); font-size: 10px; text-decoration: none; text-transform: uppercase; letter-spacing: 0.08em; }
    .seller-mobile-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; opacity: 0; visibility: hidden; transition: all 0.3s; }
    .seller-mobile-overlay.active { opacity: 1; visibility: visible; }
    .seller-mobile-drawer { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: var(--ink); z-index: 1000; transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.19,1,0.22,1); display: flex; flex-direction: column; overflow-y: auto; }
    .seller-mobile-drawer.active { transform: translateX(0); }
    .drawer-brand { padding: 24px 20px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); }
    .drawer-brand .store-name { font-family: var(--f-display); font-weight: 900; font-size: 14px; color: #fff; line-height: 1.2; }
    .drawer-brand .seller-type { font-family: var(--f-mono); font-size: 9px; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(255,255,255,0.5); margin-top: 4px; }
    .drawer-nav { padding: 12px 10px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
    .drawer-nav a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: rgba(255,255,255,0.5); font-family: var(--f-semi); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; text-decoration: none; border-radius: 6px; transition: all 0.2s; font-weight: 600; }
    .drawer-nav a:hover { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.9); }
    .drawer-nav a.active { background: var(--red); color: #fff; font-weight: 700; box-shadow: 0 4px 16px rgba(232,0,45,0.3); }
    .drawer-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.08); }
    .drawer-footer a { display: block; color: rgba(255,255,255,0.4); font-size: 10px; text-decoration: none; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; padding: 6px 0; }
    .drawer-footer a:hover { color: #fff; }
    .seller-table-wrap { display: none; }
    .seller-mobile-cards { display: flex; flex-direction: column; gap: 12px; }
    .seller-mobile-card { border: 2px solid var(--ink); padding: 16px; display: flex; gap: 14px; align-items: center; }
    .seller-mobile-card img { width: 56px; height: 56px; object-fit: cover; border: 1px solid var(--light-gray); flex-shrink: 0; }
    .seller-mobile-card .card-info { flex: 1; min-width: 0; }
    .seller-mobile-card .card-name { font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .seller-mobile-card .card-meta { font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); margin-top: 2px; display: flex; gap: 12px; flex-wrap: wrap; }
    .seller-mobile-card .card-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .seller-mobile-card .card-actions a { font-family: var(--f-mono); font-size: 10px; text-decoration: none; padding: 6px 12px; border-radius: 4px; }
    .seller-mobile-card .card-actions .btn-edit { background: var(--ink); color: #fff; }
    .seller-mobile-card .card-actions .btn-view { border: 1px solid var(--light-gray); color: var(--mid-gray); }
    .seller-mobile-card .card-actions .btn-remove { color: #f5222d; border: 1px solid #f5222d; }
    .seller-dash-grid { grid-template-columns: 1fr; gap: 16px; }
}
/* Desktop: hide mobile-only chrome (must be media-scoped or it overrides the drawer on mobile) */
@media (min-width: 901px) {
    .mobile-seller-top { display: none; }
    .seller-mobile-drawer { display: none; }
    .seller-mobile-overlay { display: none; }
}
</style>

<!-- Mobile top bar: hamburger + brand -->
<div class="mobile-seller-top">
    <button type="button" class="seller-hamburger" id="seller-menu-toggle" aria-label="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <a href="<?= $basePath ?>/dashboard" class="seller-mobile-brand">Seller Dashboard</a>
    <a href="<?= APP_URL ?>" class="seller-mobile-back">&larr; Store</a>
</div>
<div class="seller-mobile-overlay" id="seller-overlay"></div>
<div class="seller-mobile-drawer" id="seller-drawer">
    <div class="drawer-brand">
        <div class="store-name"><?= htmlspecialchars($seller['business_name']) ?></div>
        <div class="seller-type"><?= htmlspecialchars($seller['seller_type']) ?></div>
        <div style="margin-top:8px;"><?= verification_badge($seller) ?></div>
    </div>
    <nav class="drawer-nav">
        <a href="<?= $basePath ?>/dashboard" class="<?= $page==='overview'?'active':'' ?>">&#9632; Overview</a>
        <a href="<?= $basePath ?>/products" class="<?= $page==='products'?'active':'' ?>">&#9733; Products</a>
        <a href="<?= $basePath ?>/orders" class="<?= $page==='orders'?'active':'' ?>">&#9776; Orders</a>
        <a href="<?= $basePath ?>/rfqs" class="<?= $page==='rfqs'?'active':'' ?>">&#9993; RFQs</a>
        <a href="<?= $basePath ?>/finances" class="<?= $page==='finances'?'active':'' ?>">&#9830; Finances</a>
        <a href="<?= $basePath ?>/settings" class="<?= $page==='settings'?'active':'' ?>">&#9881; Store Settings</a>
    </nav>
    <div class="drawer-footer">
        <a href="<?= APP_URL ?>/">&larr; Back to Store</a>
        <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?>">Public Store</a>
        <a href="<?= APP_URL ?>/account">My Account</a>
        <a href="<?= APP_URL ?>/logout" style="color:var(--red);">Logout</a>
    </div>
</div>

<div class="seller-layout">
<aside class="seller-sidebar">
    <div class="seller-sidebar-brand">
        <a href="<?= APP_URL ?>/" style="text-decoration:none;color:#fff;">
            <div class="store-name"><?= htmlspecialchars($seller['business_name']) ?></div>
        </a>
        <div class="seller-type"><?= htmlspecialchars($seller['seller_type']) ?> · <?= htmlspecialchars($seller['country_code']) ?></div>
        <div style="margin-top:8px;"><?= verification_badge($seller) ?></div>
    </div>
    <nav class="seller-nav">
        <a href="<?= $basePath ?>/dashboard" class="<?= $page==='overview'?'active':'' ?>">
            <span class="nav-icon">&#9632;</span> Overview
        </a>
        <a href="<?= $basePath ?>/products" class="<?= $page==='products'?'active':'' ?>">
            <span class="nav-icon">&#9733;</span> Products
            <?php if(($stats['pending_products']??0)>0): ?><span class="badge"><?= (int)$stats['pending_products'] ?></span><?php endif; ?>
        </a>
        <a href="<?= $basePath ?>/orders" class="<?= $page==='orders'?'active':'' ?>">
            <span class="nav-icon">&#9776;</span> Orders
            <?php if(($stats['total_orders']??0)>0): ?><span class="badge"><?= (int)$stats['total_orders'] ?></span><?php endif; ?>
        </a>
        <a href="<?= $basePath ?>/rfqs" class="<?= $page==='rfqs'?'active':'' ?>">
            <span class="nav-icon">&#9993;</span> RFQs
        </a>
        <a href="<?= $basePath ?>/finances" class="<?= $page==='finances'?'active':'' ?>">
            <span class="nav-icon">&#9830;</span> Finances
        </a>
        <a href="<?= $basePath ?>/settings" class="<?= $page==='settings'?'active':'' ?>">
            <span class="nav-icon">&#9881;</span> Store Settings
        </a>
    </nav>
    <div class="seller-sidebar-footer">
        <a href="<?= APP_URL ?>/">&larr; Back to Store</a>
        <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?>">View Public Store &rarr;</a>
        <a href="<?= APP_URL ?>/account">My Account</a>
        <a href="<?= APP_URL ?>/logout" style="color:var(--red);">Logout</a>
    </div>
</aside>
<main class="seller-content">

<script>
(function(){
    const toggle = document.getElementById('seller-menu-toggle');
    const drawer = document.getElementById('seller-drawer');
    const overlay = document.getElementById('seller-overlay');
    if (!toggle || !drawer || !overlay) return;
    const open = () => { drawer.classList.add('active'); overlay.classList.add('active'); document.body.style.overflow = 'hidden'; };
    const close = () => { drawer.classList.remove('active'); overlay.classList.remove('active'); document.body.style.overflow = '';
    };
    toggle.addEventListener('click', open);
    overlay.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>
