<?php
// views/layout/nav.php
$uri = $_SERVER['REQUEST_URI'];
$basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
$relativePath = $uri;
if ($basePath && strpos($uri, $basePath) === 0) {
    $relativePath = substr($uri, strlen($basePath));
}
$is_home = ($relativePath === '/' || $relativePath === '' || $relativePath === '/index.php');
$is_auth = (strpos($uri, '/login') !== false || strpos($uri, '/register') !== false);

// Fetch categories for the nav
if (!isset($navCategories)) {
    require_once __DIR__ . '/../../models/Category.php';
    $catModel = new Category();
    $navCategories = array_slice($catModel->getAll(), 0, 10);
}

function getCatIcon($slug) {
    switch ($slug) {
        case 'smartphones': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>';
        case 'mobile-accessories': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 13h10s2.5 0 2.5-3.5S12 6 12 6H2v7z"></path><path d="M7 16v3"></path><path d="M12 16v3"></path><line x1="2" y1="6" x2="2" y2="13"></line></svg>';
        case 'wearables': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="7"></circle><polyline points="12 9 12 12 13.5 13.5"></polyline><path d="M16.51 17.35l-.35 3.83a2 2 0 0 1-2 1.82H9.84a2 2 0 0 1-2-1.82l-.35-3.83m.01-10.7l.35-3.83A2 2 0 0 1 9.84 1H14.16a2 2 0 0 1 2 1.82l.35 3.83"></path></svg>';
        case 'audio-devices': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"></path><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path></svg>';
        case 'computers-accessories': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="20" x2="22" y2="20"></line></svg>';
        case 'smart-home-devices': return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
        default: return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>';
    }
}
?>

<nav class="nav" id="main-nav">
    <div class="container-fluid nav-inner">
        <!-- Row 1: Actions & Brand -->
        <div class="nav-top">
            <!-- Left: Toggle (Mobile Only) / Brand (Desktop) -->
            <button type="button" class="nav-icon-btn hamburger-square mobile-only" id="nav-toggle" aria-label="Open Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>

            <a href="<?= APP_URL ?>" class="nav-brand">
                <img src="<?= APP_URL ?>/public/assets/img/logo.png" alt="AVAZONIA" class="logo-img">
            </a>

            

            <!-- Search (Now in Top Row) -->
            <form action="<?= APP_URL ?>/shop" method="GET" class="nav-search-pill" id="nav-search-form">
                <input type="text" name="q" id="nav-search-input" placeholder="Search for products..." required autocomplete="off" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                <button type="submit" class="search-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <div id="search-suggestions" class="search-suggestions" style="display: none;"></div>
            </form>

            <!-- Right: Icons -->
            <div class="nav-right-icons">
                <!-- Wishlist (Desktop Only) -->
                 <!-- Categories Dropdown (Desktop Only) -->
            <div class="nav-cat-trigger desktop-only" id="cat-trigger">
                <div class="hamburger-mini">
                    <span></span><span></span><span></span>
                </div>
                <span class="nav-cat-label">Categories</span>
                
                <div class="cat-dropdown">
                    <?php foreach ($navCategories as $cat): ?>
                        <a href="<?= APP_URL ?>/shop?cat=<?= $cat['slug'] ?>" class="cat-drop-item">
                            <span class="cat-drop-icon"><?= getCatIcon($cat['slug']) ?></span>
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="<?= APP_URL ?>/shop" class="cat-drop-item" style="border-top: 1px solid rgba(0,0,0,0.05); margin-top: 8px;">
                        <span class="cat-drop-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                        </span>
                        Browse All
                    </a>
                </div>
            </div>
                <a href="<?= APP_URL ?>/wishlist" class="nav-icon-btn desktop-only" aria-label="Wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </a>

                <div class="nav-account-trigger" id="acc-trigger">
                    <button class="nav-icon-btn" aria-label="Account Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </button>
                    
                    <div class="acc-dropdown">
                        <?php if (Session::get('user_id')): ?>
                            <a href="<?= APP_URL ?>/wishlist" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.84-8.84 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                Wishlist
                            </a>
                            <a href="<?= APP_URL ?>/account" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                My Account
                            </a>
                            <a href="<?= APP_URL ?>/orders" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8h-2V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v3H3a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1zM7 5h10v3H7V5zm12 15H5V10h14v10z"></path></svg>
                                My Orders
                            </a>
                            <a href="<?= APP_URL ?>/logout" class="acc-link logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                Logout 
                            </a>
                        <?php else: ?>
                            <a href="<?= APP_URL ?>/login" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                                Login
                            </a>
                            <a href="<?= APP_URL ?>/register" class="acc-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                                Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <a href="<?= APP_URL ?>/cart" class="nav-icon-btn nav-cart" aria-label="Cart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span class="cart-badge"><?= array_sum(array_column(Session::get('cart', []), 'qty')) ?></span>
                </a>
            </div>
        </div>

    </div>
</nav>

<div class="menu-overlay" id="menu-overlay"></div>

<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu-header">
        <button class="menu-close" id="menu-close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <nav style="display: flex; flex-direction: column; gap: 4px; margin-top: 20px;">
        <a href="<?= APP_URL ?>/deals" class="mobile-link" style="color: var(--red); font-weight: 800; border-left: 3px solid var(--red); padding-left: 12px; margin-left: -12px;">Flash Deals</a>
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        
        <a href="<?= APP_URL ?>" class="mobile-link">Store Home</a>
        <a href="<?= APP_URL ?>/shop" class="mobile-link">Shop All</a>
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        
        <?php foreach ($navCategories as $cat): ?>
            <a href="<?= APP_URL ?>/shop?cat=<?= $cat['slug'] ?>" class="mobile-link"><?= htmlspecialchars($cat['name']) ?></a>
        <?php endforeach; ?>
        
        <div style="height: 1px; background: rgba(0,0,0,0.05); margin: 10px 0;"></div>
        <?php if (Session::get('user_id')): ?>
            <a href="<?= APP_URL ?>/account/settings" class="mobile-link">Profile Settings</a>
            <a href="<?= APP_URL ?>/logout" class="mobile-link" style="opacity: 0.5;">Logout ↗</a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/login" class="mobile-link">Login</a>
            <a href="<?= APP_URL ?>/register" class="mobile-link">Sign Up</a>
        <?php endif; ?>
    </nav>
</div>

<script>
    (function() {
        const mainNav = document.getElementById('main-nav');
        
        function handleScroll() {
            if (!mainNav) return;
            const scrollThreshold = 50;
            const isScrolled = window.scrollY > scrollThreshold;
            
            // Robust path detection
            const basePath = '<?= APP_PATH ?>'.replace(/\/$/, '');
            let currentPath = window.location.pathname;
            if (basePath && currentPath.indexOf(basePath) === 0) {
                currentPath = currentPath.substring(basePath.length);
            }
            const cleanPath = currentPath.replace(/\/$/, '') || '/';
            const isHome = cleanPath === '/' || cleanPath === '/index.php' || cleanPath === '';

            if (isScrolled) {
                mainNav.classList.add('nav-scrolled');
                mainNav.classList.remove('nav-home');
            } else {
                if (isHome) {
                    mainNav.classList.remove('nav-scrolled');
                    mainNav.classList.add('nav-home');
                } else {
                    mainNav.classList.add('nav-scrolled');
                    mainNav.classList.remove('nav-home');
                }
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();

        // Menu Toggles (Delegated)
        document.addEventListener('click', (e) => {
            // Hamburger
            if (e.target.closest('#nav-toggle')) {
                toggleMenu(true);
            }
            // Close Menu
            if (e.target.closest('#menu-close') || e.target.closest('#menu-overlay')) {
                toggleMenu(false);
            }
            // Mobile Search Toggle
            if (e.target.closest('#mobile-search-toggle')) {
                const searchForm = document.getElementById('nav-search-form');
                const searchInput = document.getElementById('nav-search-input');
                if (searchForm) {
                    searchForm.classList.add('is-active');
                    if (searchInput) setTimeout(() => searchInput.focus(), 100);
                }
            }
            // Mobile Search Close
            if (e.target.closest('#mobile-search-close')) {
                const searchForm = document.getElementById('nav-search-form');
                if (searchForm) searchForm.classList.remove('is-active');
                const suggestions = document.getElementById('search-suggestions');
                if (suggestions) suggestions.style.display = 'none';
            }
        });

        window.toggleMenu = function(show) {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('menu-overlay');
            if (menu) menu.classList.toggle('active', show);
            if (overlay) overlay.classList.toggle('active', show);
            document.body.style.overflow = show ? 'hidden' : '';
        }

        // Dropdowns (Delegated)
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('.nav-cat-trigger, .nav-account-trigger');
            const dropdowns = document.querySelectorAll('.nav-cat-trigger, .nav-account-trigger');
            
            if (trigger) {
                if (e.target.closest('a')) return;
                e.preventDefault();
                e.stopPropagation();
                const isActive = trigger.classList.contains('active');
                dropdowns.forEach(d => d.classList.remove('active'));
                if (!isActive) trigger.classList.add('active');
            } else if (!e.target.closest('.cat-dropdown') && !e.target.closest('.acc-dropdown')) {
                dropdowns.forEach(d => d.classList.remove('active'));
            }
        });

        // Global SPA Engine
        document.addEventListener('click', async (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || link.target === '_blank') return;
            
            // External Check
            const isLogout = href.includes('/logout');
            if (isExternal || isCheckout || isLogout) return;

            e.preventDefault();
            const url = new URL(link.href);
            const wrapper = document.getElementById('page-wrapper');
            if (!wrapper) { window.location.href = href; return; }
            
            wrapper.classList.add('is-loading');
            
            try {
                const fetchUrl = new URL(url);
                fetchUrl.searchParams.set('_t', Date.now());
                const response = await fetch(fetchUrl);
                const html = await response.text();
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newWrapper = doc.getElementById('page-wrapper');
                if (!newWrapper) throw new Error('No wrapper');
                
                const newContent = newWrapper.innerHTML;
                const newTitle = doc.title;
                
                // Only replace if content is actually different to avoid flicker
                if (wrapper.innerHTML !== newContent) {
                    setTimeout(() => {
                        wrapper.innerHTML = newContent;
                        document.title = newTitle;
                        window.scrollTo(0, 0);
                        wrapper.classList.remove('is-loading');
                        history.pushState(null, '', url);
                        
                        // Re-initialize scripts
                        if (window.reinitScripts) window.reinitScripts();
                        
                        toggleMenu(false);
                        handleScroll();
                    }, 400);
                } else {
                    wrapper.classList.remove('is-loading');
                    history.pushState(null, '', url);
                    toggleMenu(false);
                }
            } catch (err) {
                window.location.href = href;
            }
        });

        // AJAX Cart & Search Handler (Delegated)
        document.addEventListener('submit', async (e) => {
            const form = e.target;
            if (form.classList.contains('ajax-cart-form')) {
                e.preventDefault();
                const btn = form.querySelector('[type="submit"]');
                if (btn) btn.disabled = true;
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        const badge = document.querySelector('.cart-badge');
                        if (badge) {
                            badge.innerText = data.cart_count || '0';
                            badge.style.transform = 'scale(1.2)';
                            setTimeout(() => badge.style.transform = '', 200);
                        }
                        showToast(data.message || 'Added to cart');
                    } else if (data.redirect) window.location.href = data.redirect;
                } catch (err) { form.submit(); } 
                finally { if (btn) btn.disabled = false; }
            }
            
            if (form.classList.contains('nav-search')) {
                e.preventDefault();
                const q = form.querySelector('input[name="q"]').value;
                const catId = form.querySelector('select[name="cat_id"]').value;
                const url = new URL(form.action);
                url.searchParams.set('q', q);
                if (catId) url.searchParams.set('cat_id', catId);
                
                const wrapper = document.getElementById('page-wrapper');
                if (!wrapper) { window.location.href = url.href; return; }
                
                wrapper.classList.add('is-loading');
                try {
                    const response = await fetch(url + (url.search ? '&' : '?') + '_t=' + Date.now());
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newWrapper = doc.getElementById('page-wrapper');
                    if (newWrapper) {
                        setTimeout(() => {
                            wrapper.innerHTML = newWrapper.innerHTML;
                            document.title = doc.title;
                            window.scrollTo(0, 0);
                            wrapper.classList.remove('is-loading');
                            history.pushState(null, '', url);
                            if (window.reinitScripts) window.reinitScripts();
                            handleScroll();
                        }, 400);
                    } else window.location.href = url.href;
                } catch (err) { window.location.href = url.href; }
            }
        });

        window.toggleWishlist = async function(productId, isRemoveOnly = false) {
            const fd = new FormData();
            fd.append('product_id', productId);
            try {
                const res = await fetch('<?= APP_URL ?>/api/wishlist-toggle', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    if (isRemoveOnly && data.status === 'removed') {
                        const el = document.getElementById('wish-' + productId);
                        if (el) { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }
                    }
                    showToast(data.message);
                    document.querySelectorAll('.wish-btn-' + productId).forEach(b => b.classList.toggle('active', data.status === 'added'));
                } else if (data.redirect) window.location.href = data.redirect;
            } catch (err) { console.error(err); }
        }

        function showToast(msg) {
            let t = document.getElementById('toast');
            if (!t) { t = document.createElement('div'); t.id = 'toast'; document.body.appendChild(t); }
            t.innerText = msg; t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // Suggestions Logic (Delegated)
        let searchTimeout;
        document.addEventListener('input', (e) => {
            if (e.target.id === 'nav-search-input') {
                const box = document.getElementById('search-suggestions');
                if (!box) return;
                clearTimeout(searchTimeout);
                const q = e.target.value.trim();
                if (q.length < 2) { box.style.display = 'none'; return; }
                searchTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch(`<?= APP_URL ?>/api/search-suggestions?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        if (data && data.length > 0) {
                            box.innerHTML = data.map(i => `<a href="${i.url}" class="suggestion-item"><img src="${i.image}" alt=""><span>${i.name}</span></a>`).join('');
                            box.style.display = 'block';
                        } else { box.innerHTML = '<div class="suggestion-empty">No products found</div>'; box.style.display = 'block'; }
                    } catch (err) { console.error(err); }
                }, 300);
            }
        });

        document.addEventListener('click', (e) => {
            const box = document.getElementById('search-suggestions');
            const input = document.getElementById('nav-search-input');
            if (box && input && !input.contains(e.target) && !box.contains(e.target)) box.style.display = 'none';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const box = document.getElementById('search-suggestions');
                if (box) box.style.display = 'none';
                const form = document.getElementById('nav-search-form');
                if (form) form.classList.remove('is-active');
            }
        });
        // UI Initialization Functions
        window.initSlider = function() {
            const slides = document.querySelectorAll('.hero-slide');
            const dots = document.querySelectorAll('.dot');
            if (!slides.length) return;
            
            let currentSlide = 0;
            let slideInterval;

            const showSlide = (n) => {
                slides.forEach(s => s.classList.remove('active'));
                dots.forEach(d => d.classList.remove('active'));
                if (slides[n]) slides[n].classList.add('active');
                if (dots[n]) dots[n].classList.add('active');
                currentSlide = n;
            };

            const nextSlide = () => {
                let next = (currentSlide + 1) % slides.length;
                showSlide(next);
            };

            const startAutoPlay = () => {
                clearInterval(slideInterval);
                slideInterval = setInterval(nextSlide, 5000);
            };

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    clearInterval(slideInterval);
                    showSlide(index);
                    startAutoPlay();
                });
            });

            showSlide(0);
            startAutoPlay();
        };

        window.initScrollReveal = function() {
            const reveals = document.querySelectorAll('.reveal');
            const observerOptions = {
                root: null,
                threshold: 0.1,
                rootMargin: "0px 0px -50px 0px"
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                    }
                });
            }, observerOptions);

            reveals.forEach(el => observer.observe(el));
        };

        window.scrollSlider = function(id, direction) {
            const slider = document.getElementById(id);
            if (!slider) return;
            const firstCard = slider.querySelector('.card');
            if (!firstCard) return;
            const scrollAmount = (firstCard.offsetWidth + 12) * direction; // card width + gap
            slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        };

        window.initBestsellersAutoplay = function() {
            const slider = document.getElementById('bestsellers-slider');
            if (!slider) return;

            let autoplayInterval;
            const startAutoplay = () => {
                clearInterval(autoplayInterval);
                autoplayInterval = setInterval(() => {
                    // Check if we reached the end
                    const maxScrollLeft = slider.scrollWidth - slider.clientWidth;
                    if (slider.scrollLeft >= maxScrollLeft - 10) {
                        slider.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        window.scrollSlider('bestsellers-slider', 1);
                    }
                }, 5000); // Scroll every 5 seconds
            };

            // Pause autoplay on mouse enter and resume on leave
            slider.addEventListener('mouseenter', () => clearInterval(autoplayInterval));
            slider.addEventListener('mouseleave', startAutoplay);
            slider.addEventListener('touchstart', () => clearInterval(autoplayInterval), { passive: true });
            
            startAutoplay();
        };

        window.reinitScripts = function() {
            window.initSlider();
            window.initScrollReveal();
            window.initBestsellersAutoplay();
            // Handle any other page-specific setup here
        };

        // Initial launch
        document.addEventListener('DOMContentLoaded', window.reinitScripts);
    })();
</script>

<div id="page-wrapper" class="page-fade">
