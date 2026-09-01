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
            --red-deep: <?= PRIMARY_COLOR ?>; /* Simple fallback for deep red */
            --nav-offset: 72px;
        }
        /* Global nav offset — prevents fixed nav from covering page content on every page */
        #page-wrapper { padding-top: var(--nav-offset); }
        @media (max-width: 768px) { :root { --nav-offset: 64px; } }
        @media (min-width: 1024px) {
            .nav-cat-rail { display: flex; }
        }
        .page-fade { transition: opacity 0.4s ease, transform 0.4s ease; }
        .page-fade.is-loading { opacity: 0; transform: translateY(10px); }
    #lang-dropdown.show { display: block !important; }
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

    <!-- Google Translate -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'en,fr,zh-CN,ar,es,ha,de,pt',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
    // Translate page directly — no cookie tricks, no reloads
    function gtTranslate(lang) {
        if (lang === 'en') {
            // Reset to English: reload without cookie
            document.cookie = 'avazonia_lang=;path=/;max-age=0';
            location.reload();
            return;
        }
        document.cookie = 'avazonia_lang=' + lang + ';path=/;max-age=' + (365*86400) + ';SameSite=Lax';
        if (typeof google !== 'undefined' && google.translate) {
            google.translate.TranslateElement.TranslatePage('en', lang);
        }
    }
    // Auto-translate on page load if cookie is set
    document.addEventListener('DOMContentLoaded', function() {
        var m = document.cookie.match(/avazonia_lang=([^;]+)/);
        if (m && m[1] && m[1] !== 'en') {
            // Wait for Google Translate to load
            var attempts = 0;
            var check = setInterval(function() {
                attempts++;
                if (typeof google !== 'undefined' && google.translate) {
                    clearInterval(check);
                    google.translate.TranslateElement.TranslatePage('en', m[1]);
                }
                if (attempts > 50) clearInterval(check); // 5 seconds max
            }, 100);
        }
    });
    </script>
    <style>
    .goog-te-banner-frame { display: none !important; }
    .goog-te-menu-frame { max-height: 400px !important; overflow: auto !important; }
    body { top: 0 !important; }
    #goog-gt-tt { display: none !important; }
    .goog-te-spinner-pos { display: none !important; }
    #google_translate_element { display: none; }
    /* Custom lang dropdown styles */
    .gt-dropdown { position: relative; display: inline-block; }
    .gt-dropdown-btn {
        background: none; border: none; cursor: pointer;
        font-size: 16px; line-height: 1; padding: 6px;
        border-radius: 6px; transition: background 0.15s;
    }
    .gt-dropdown-btn:hover { background: var(--off, #f5f5f5); }
    .gt-dropdown-menu {
        display: none; position: absolute; top: 100%; right: 0;
        margin-top: 8px; background: #fff; border: 1px solid var(--light-gray, #e5e7eb);
        border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        min-width: 160px; z-index: 10000; overflow: hidden;
    }
    .gt-dropdown-menu.show { display: block; }
    .gt-dropdown-menu a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; text-decoration: none; color: var(--ink, #111);
        font-size: 12px; font-family: var(--f-body, sans-serif);
        transition: background 0.12s; cursor: pointer;
    }
    .gt-dropdown-menu a:hover { background: var(--off, #f5f5f5); }
    .gt-dropdown-menu a .gt-flag { font-size: 16px; width: 22px; text-align: center; }
    .gt-dropdown-menu a .gt-check { margin-left: auto; opacity: 0; font-size: 12px; color: var(--red, #E5001A); font-weight: 700; }
    .gt-dropdown-menu a.active .gt-check { opacity: 1; }
    .gt-dropdown-menu .gt-divider { height: 1px; background: var(--light-gray, #e5e7eb); margin: 4px 0; }
    </style>
</head>
<body>
