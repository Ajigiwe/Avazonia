<?php
// views/layout/head.php
if (!class_exists('Csrf')) require_once __DIR__ . '/../../core/Csrf.php';
if (!class_exists('Translator')) require_once __DIR__ . '/../../core/Translator.php';
$_t = Translator::getInstance();
?>
<!DOCTYPE html>
<html lang="<?= $_t->getLang() ?>">
<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-G3GWGCPMPP"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-G3GWGCPMPP');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::ensure(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $meta_title ?? ($title ?? APP_NAME) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? 'Avazonia - Premium Tech & Gadgets in Ghana. Discover the latest electronics with nationwide delivery.') ?>">
    <?php if (!empty($meta_keywords)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
    <?php endif; ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:title" content="<?= $meta_title ?? ($title ?? APP_NAME) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_description ?? 'Avazonia - Premium Tech & Gadgets in Ghana.') ?>">
    <meta property="og:image" content="<?= $og_image ?? (APP_URL . '/public/images/og-default.jpg') ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= APP_URL . $_SERVER['REQUEST_URI'] ?>">
    <meta property="twitter:title" content="<?= $meta_title ?? ($title ?? APP_NAME) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($meta_description ?? 'Avazonia - Premium Tech & Gadgets in Ghana.') ?>">
    <meta property="twitter:image" content="<?= $og_image ?? (APP_URL . '/public/images/og-default.jpg') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&family=Outfit:wght@300;400;500;600;700;800;900&family=Barlow+Condensed:ital,wght@1,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/css/styles.css?v=<?= time() ?>">
    <style>
        :root {
            --red: <?= PRIMARY_COLOR ?>;
            --red-deep: <?= PRIMARY_COLOR ?>;
            --nav-offset: 72px;
        }
        #page-wrapper { padding-top: var(--nav-offset); }
        @media (max-width: 768px) { :root { --nav-offset: 64px; } }
        @media (min-width: 1024px) { .nav-cat-rail { display: flex; } }
        .page-fade { transition: opacity 0.4s ease, transform 0.4s ease; }
        .page-fade.is-loading { opacity: 0; transform: translateY(10px); }

        /* ═══ Google Translate — hide all native chrome ═══ */
        .goog-te-banner-frame,
        .goog-te-banner-frame + div,
        #goog-gt-tt { display: none !important; }
        body { top: 0 !important; }
        /* Hide any Google Translate banner/notification bar */
        body > div.skiptranslate:not(#google_translate_element) { display: none !important; position: absolute !important; }
        #google_translate_element .goog-te-gadget { line-height: 1; margin: 0; padding: 0; }
        #google_translate_element .goog-te-gadget span:not(.goog-te-combo) { display: none !important; }
        #google_translate_element .goog-te-gadget .goog-te-combo {
            height: 36px; padding: 4px 28px 4px 10px;
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 12px; font-family: var(--f-body, sans-serif);
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23666'/%3E%3C/svg%3E") no-repeat right 8px center;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            cursor: pointer; outline: none; color: #1a1a1a;
        }
        /* Desktop: sit the combo in the nav bar (top-right) — body-level, so position:fixed works */
        @media (min-width: 769px) {
            #google_translate_element {
                position: fixed !important; top: 16px !important; right: 240px !important;
                z-index: 2001 !important; display: inline-flex !important; align-items: center;
            }
            .gt-float { display: none !important; }
        }
        /* Mobile: combo hidden until the floating globe is tapped, then shown as a floating card */
        @media (max-width: 768px) {
            #google_translate_element {
                display: none !important;
                position: fixed !important;
                bottom: 150px !important; left: 20px !important; top: auto !important; right: auto !important;
                background: #fff !important; padding: 8px 10px !important;
                border-radius: 12px !important; box-shadow: 0 8px 32px rgba(0,0,0,0.18) !important; z-index: 100000 !important;
            }
            #google_translate_element.open { display: block !important; }
            #google_translate_element .goog-te-gadget .goog-te-combo { height: 34px !important; font-size: 13px !important; }
            .gt-float { display: block !important; }
        }

        /* ═══ Floating Language Icon ═══ */
        .gt-float {
            position: fixed;
            bottom: 90px;
            left: 20px;
            z-index: 99999;
        }
        .gt-float-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #1a1a1a;
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .gt-float-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 6px 24px rgba(0,0,0,0.35);
        }
        .gt-float-btn svg {
            width: 24px; height: 24px; fill: none; stroke: #fff;
            stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
        }
    </style>
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/public/assets/img/logo2-rounded.png?v=2">

    <!-- PWA Support -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.webmanifest?v=2">
    <meta name="theme-color" content="<?= PRIMARY_COLOR ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/public/assets/img/logo2-rounded.png?v=2">

    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= APP_URL ?>/sw.js')
                .then(reg => console.log('[PWA] SW Registered', reg.scope))
                .catch(err => console.error('[PWA] SW Failed', err));
        });
    }
    </script>

    <!-- Google Translate — loaded hidden, reads GOOGTRANS cookie on init -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en'
        }, 'google_translate_element');
    }
    /* Hide the Google Translate notification banner */
    function hideGTBanner() {
        document.querySelectorAll('body > div').forEach(function(d) {
            if (d.id === 'google_translate_element' || d.id === 'gt-float' || d.id === 'page-wrapper' || d.classList.length > 0) return;
            if (d.style && (d.style.position === 'fixed' || d.innerHTML.indexOf('Show original') !== -1 || d.innerHTML.indexOf('translated') !== -1)) {
                d.style.display = 'none';
            }
        });
    }
    new MutationObserver(hideGTBanner).observe(document.body, { childList: true, subtree: true });
    setTimeout(hideGTBanner, 2000);
    </script>
</head>
<body>

<!-- Google Translate widget (single instance; direct child of body so it escapes the nav's backdrop-filter containing block) -->
<div id="google_translate_element"></div>

<!-- Floating Language Icon (mobile) — toggles the native full-language combo -->
<div class="gt-float" id="gt-float">
    <button class="gt-float-btn" aria-label="Change Language" title="Change Language" onclick="gtMobileToggle(event)">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><line x1="2" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" fill="none" stroke="currentColor" stroke-width="2"/></svg>
    </button>
</div>
<script>
function gtMobileToggle(e) {
    if (e) e.stopPropagation();
    var el = document.getElementById('google_translate_element');
    if (!el) return;
    el.classList.toggle('open');
    if (el.classList.contains('open')) {
        var combo = el.querySelector('select.goog-te-combo');
        if (combo) setTimeout(function(){ combo.focus(); }, 50);
    }
}
// Close the mobile combo when tapping outside
function gtCloseIfOutside(e) {
    var el = document.getElementById('google_translate_element');
    if (!el || !el.classList.contains('open')) return;
    if (!el.contains(e.target) && !document.querySelector('.gt-float-btn').contains(e.target)) {
        el.classList.remove('open');
    }
}
document.addEventListener('click', gtCloseIfOutside);
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { var el = document.getElementById('google_translate_element'); if (el) el.classList.remove('open'); } });
</script>
