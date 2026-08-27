<?php
// views/seller/sidebar.php
// Expected: $seller, $page, $stats
$page = $page ?? 'overview';
$basePath = APP_URL . '/seller';
?>
<style>
/* ── Hide main site nav on seller pages ── */
body.seller-dash #main-nav,
body.seller-dash .nav-search-pill,
body.seller-dash .mobile-menu,
body.seller-dash .menu-overlay,
body.seller-dash .nav-cat-trigger,
body.seller-dash .nav-account-trigger,
body.seller-dash .nav-cart,
body.seller-dash .nav-right-icons .desktop-only,
body.seller-dash #page-wrapper { padding-top: 0 !important; }

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
.seller-sidebar-footer a { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.4); font-size: 10px; text-decoration: none; font-family: var(--f-mono); text-transform: uppercase; letter-spacing: 0.08em; transition: color 0.2s; }
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
    .mobile-seller-nav {
        display: flex !important; overflow-x: auto; gap: 8px;
        padding: 12px 16px; background: var(--ink);
        -webkit-overflow-scrolling: touch; scrollbar-width: none;
    }
    .mobile-seller-nav::-webkit-scrollbar { display: none; }
    .mobile-seller-nav a {
        white-space: nowrap; padding: 8px 14px;
        color: rgba(255,255,255,0.6); font-family: var(--f-semi); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.08em; text-decoration: none;
        border-radius: 99px; transition: all 0.2s;
        border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;
    }
    .mobile-seller-nav a.active { background: var(--red); color: #fff; border-color: var(--red); }
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
.mobile-seller-nav { display: none; }
</style>

<script>document.body.classList.add('seller-dash');</script>

<!-- Mobile nav -->
<div class="mobile-seller-nav">
    <a href="<?= $basePath ?>/dashboard" class="<?= $page==='overview'?'active':'' ?>">&#9632; Overview</a>
    <a href="<?= $basePath ?>/products" class="<?= $page==='products'?'active':'' ?>">&#9733; Products</a>
    <a href="<?= $basePath ?>/orders" class="<?= $page==='orders'?'active':'' ?>">&#9776; Orders</a>
    <a href="<?= $basePath ?>/rfqs" class="<?= $page==='rfqs'?'active':'' ?>">&#9993; RFQs</a>
    <a href="<?= $basePath ?>/finances" class="<?= $page==='finances'?'active':'' ?>">&#9830; Finances</a>
    <a href="<?= $basePath ?>/settings" class="<?= $page==='settings'?'active':'' ?>">&#9881; Settings</a>
</div>

<div class="seller-layout">
<aside class="seller-sidebar">
    <div class="seller-sidebar-brand">
        <div class="store-name"><?= htmlspecialchars($seller['business_name']) ?></div>
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
        <a href="<?= APP_URL ?>/" style="color:rgba(255,255,255,0.6);">&larr; Back to Store</a>
        <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?>">View Public Store &rarr;</a>
        <a href="<?= APP_URL ?>/account" style="margin-top:4px;">My Account</a>
        <a href="<?= APP_URL ?>/logout" style="margin-top:4px;color:var(--red);">Logout</a>
    </div>
</aside>
<main class="seller-content">
