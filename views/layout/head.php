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

    <!-- Google Translate Widget (hidden init + floating icon) -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            autoDisplay: false,
            layout: google.translate.TranslateElement.InlineLayout.VERTICAL
        }, 'google_translate_element');
    }
    /* Called when user picks a language from our floating dropdown */
    function gtTranslateTo(lang) {
        if (lang === 'en') {
            // Reset to original English — clear Google Translate
            var iframes = document.querySelectorAll('iframe.goog-te-banner-frame, iframe.skiptranslate');
            iframes.forEach(function(f) { f.srcdoc = ''; f.style.display = 'none'; });
            var body = document.body;
            body.style.top = '0px';
            body.classList.remove('translated-ltr','translated-rtl');
            // Clear stored lang
            document.cookie = 'avazonia_lang=;path=/;max-age=0';
            localStorage.removeItem('avazonia_lang');
            return;
        }
        // Store the choice
        document.cookie = 'avazonia_lang=' + lang + ';path=/;max-age=' + (365*86400) + ';SameSite=Lax';
        localStorage.setItem('avazonia_lang', lang);
        // Translate!
        if (typeof google !== 'undefined' && google.translate && google.translate.TranslateElement) {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                autoDisplay: false,
                layout: google.translate.TranslateElement.InlineLayout.VERTICAL
            }, 'google_translate_element');
            setTimeout(function() {
                var combo = document.querySelector('#google_translate_element select.goog-te-combo');
                if (combo) {
                    combo.value = lang;
                    combo.dispatchEvent(new Event('change'));
                }
            }, 500);
        }
    }
    </script>
    <style>
    /* Hide ALL Google Translate chrome */
    .goog-te-banner-frame, .goog-te-banner-frame + div { display: none !important; }
    body { top: 0 !important; }
    #goog-gt-tt { display: none !important; }
    .skiptranslate { display: none !important; }
    /* The hidden init div — must exist but invisible */
    #google_translate_element { display: none !important; }

    /* ═══ Floating Language Icon ═══ */
    .gt-float {
        position: fixed;
        bottom: 24px;
        left: 24px;
        z-index: 9999;
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
        font-size: 22px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .gt-float-btn:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 24px rgba(0,0,0,0.35);
    }
    .gt-float-btn svg {
        width: 24px;
        height: 24px;
        fill: none;
        stroke: #fff;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .gt-float-menu {
        position: absolute;
        bottom: 62px;
        left: 0;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        min-width: 180px;
        padding: 8px 0;
        display: none;
        overflow: hidden;
    }
    .gt-float-menu.show { display: block; }
    .gt-float-menu-title {
        padding: 8px 16px 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #999;
        font-family: var(--f-mono, monospace);
    }
    .gt-lang-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        cursor: pointer;
        transition: background 0.15s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        font-size: 14px;
        font-family: var(--f-body, sans-serif);
        color: #1a1a1a;
    }
    .gt-lang-option:hover {
        background: #f5f5f5;
    }
    .gt-lang-option.active {
        background: rgba(229,0,26,0.06);
        color: var(--red, #E5001A);
        font-weight: 600;
    }
    .gt-lang-option .gt-flag {
        font-size: 20px;
        line-height: 1;
    }
    .gt-lang-option .gt-name {
        flex: 1;
    }
    .gt-lang-option .gt-check {
        width: 16px;
        height: 16px;
        opacity: 0;
    }
    .gt-lang-option.active .gt-check {
        opacity: 1;
    }
    @media (max-width: 768px) {
        .gt-float {
            bottom: 16px;
            left: 16px;
        }
        .gt-float-btn {
            width: 46px;
            height: 46px;
            font-size: 20px;
        }
        .gt-float-menu {
            min-width: 160px;
        }
    }
    </style>
</head>
<body>
    <!-- Floating Language Icon -->
    <div class="gt-float" id="gt-float">
        <div class="gt-float-menu" id="gt-float-menu">
            <div class="gt-float-menu-title">Language</div>
            <button class="gt-lang-option active" data-lang="en">
                <span class="gt-flag">🇬🇧</span>
                <span class="gt-name">English</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="fr">
                <span class="gt-flag">🇫🇷</span>
                <span class="gt-name">Français</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="zh-CN">
                <span class="gt-flag">🇨🇳</span>
                <span class="gt-name">中文</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="es">
                <span class="gt-flag">🇪🇸</span>
                <span class="gt-name">Español</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="de">
                <span class="gt-flag">🇩🇪</span>
                <span class="gt-name">Deutsch</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="ar">
                <span class="gt-flag">🇸🇦</span>
                <span class="gt-name">العربية</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
            <button class="gt-lang-option" data-lang="pt">
                <span class="gt-flag">🇧🇷</span>
                <span class="gt-name">Português</span>
                <svg class="gt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </button>
        </div>
        <button class="gt-float-btn" id="gt-float-btn" aria-label="Change Language" title="Change Language">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
        </button>
    </div>
    <script>
    (function() {
        var btn = document.getElementById('gt-float-btn');
        var menu = document.getElementById('gt-float-menu');
        var options = document.querySelectorAll('.gt-lang-option');
        var currentLang = localStorage.getItem('avazonia_lang') || 'en';

        // Highlight active language on load
        options.forEach(function(o) {
            if (o.dataset.lang === currentLang) o.classList.add('active');
            else o.classList.remove('active');
        });

        // Toggle menu
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.gt-float')) {
                menu.classList.remove('show');
            }
        });

        // Language selection
        options.forEach(function(opt) {
            opt.addEventListener('click', function() {
                var lang = this.dataset.lang;
                // Update active state
                options.forEach(function(o) { o.classList.remove('active'); });
                this.classList.add('active');
                // Close menu
                menu.classList.remove('show');
                // Translate
                gtTranslateTo(lang);
            });
        });

        // Auto-translate on page load if a language was previously selected
        if (currentLang && currentLang !== 'en') {
            // Wait for Google Translate to loadn            var attempts = 0;
            var waitForGT = setInterval(function() {
                attempts++;
                if (typeof google !== 'undefined' && google.translate && google.translate.TranslateElement) {
                    clearInterval(waitForGT);
                    gtTranslateTo(currentLang);
                }
                if (attempts > 40) clearInterval(waitForGT); // 4 seconds max
            }, 100);
        }
    })();
    </script>
</head>
<body>
