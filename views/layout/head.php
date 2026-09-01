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
        .skiptranslate,
        #goog-gt-tt { display: none !important; }
        body { top: 0 !important; }
        /* Hide the hidden init div */
        #google_translate_element { display: inline-block; vertical-align: middle; }
        /* Hide the Google branding text next to combo */
        #google_translate_element .goog-te-gadget span:not(.goog-te-combo) { display: none !important; }
        /* Style the combo to match Avazonia */
        #google_translate_element .goog-te-gadget { font-size: 0 !important; line-height: 1; margin: 0; padding: 0; }
        #google_translate_element .goog-te-gadget .goog-te-combo {
            height: 36px; padding: 4px 28px 4px 10px;
            border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 13px; font-family: var(--f-body, sans-serif);
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23666'/%3E%3C/svg%3E") no-repeat right 8px center;
            -webkit-appearance: none; -moz-appearance: none; appearance: none;
            cursor: pointer; outline: none; color: #1a1a1a;
        }
        #google_translate_element .goog-te-gadget .goog-te-combo:hover { border-color: var(--red, #E5001A); }
        .gt-widget-wrap { display: inline-block; vertical-align: middle; flex-shrink: 0; }
        @media (min-width: 769px) { .gt-float { display: none; } }
        @media (max-width: 768px) { .gt-widget-wrap { display: none; } }

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
        .gt-float-menu {
            position: absolute;
            bottom: 62px;
            left: 0;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            min-width: 200px;
            padding: 8px 0;
            display: none;
            overflow: hidden;
        }
        .gt-float-menu.open { display: block; }
        .gt-float-menu-title {
            padding: 8px 16px 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            font-family: var(--f-mono, monospace);
        }
        .gt-opt {
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
        .gt-opt:hover { background: #f5f5f5; }
        .gt-opt.on { background: rgba(229,0,26,0.06); color: var(--red, #E5001A); font-weight: 600; }
        .gt-opt .gt-fl { font-size: 20px; line-height: 1; }
        .gt-opt .gt-nm { flex: 1; }
        .gt-more {
            padding: 8px 16px;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #f0f0f0;
            margin-top: 4px;
            text-align: center;
        }
        .gt-more a { color: var(--red, #E5001A); text-decoration: none; font-weight: 600; }
        .gt-more a:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .gt-float { bottom: 72px; left: 16px; }
            .gt-float-btn { width: 46px; height: 46px; }
            .gt-float-menu { min-width: 180px; }
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
            pageLanguage: 'en',
            autoDisplay: false
        }, 'google_translate_element');
    }
    </script>
</head>
<body>


<!-- Floating Language Icon -->
<div class="gt-float" id="gt-float">
    <div class="gt-float-menu" id="gt-float-menu">
        <div class="gt-float-menu-title">Translate to</div>
        <button class="gt-opt" onclick="gtPick('')"><span class="gt-fl">🇬🇧</span><span class="gt-nm">English</span></button>
        <button class="gt-opt" onclick="gtPick('fr')"><span class="gt-fl">🇫🇷</span><span class="gt-nm">Français</span></button>
        <button class="gt-opt" onclick="gtPick('zh-CN')"><span class="gt-fl">🇨🇳</span><span class="gt-nm">中文 (Chinese)</span></button>
        <button class="gt-opt" onclick="gtPick('es')"><span class="gt-fl">🇪🇸</span><span class="gt-nm">Español</span></button>
        <button class="gt-opt" onclick="gtPick('de')"><span class="gt-fl">🇩🇪</span><span class="gt-nm">Deutsch</span></button>
        <button class="gt-opt" onclick="gtPick('ar')"><span class="gt-fl">🇸🇦</span><span class="gt-nm">العربية (Arabic)</span></button>
        <button class="gt-opt" onclick="gtPick('pt')"><span class="gt-fl">🇧🇷</span><span class="gt-nm">Português</span></button>
        <button class="gt-opt" onclick="gtPick('ja')"><span class="gt-fl">🇯🇵</span><span class="gt-nm">日本語 (Japanese)</span></button>
        <button class="gt-opt" onclick="gtPick('ko')"><span class="gt-fl">🇰🇷</span><span class="gt-nm">한국어 (Korean)</span></button>
        <button class="gt-opt" onclick="gtPick('hi')"><span class="gt-fl">🇮🇳</span><span class="gt-nm">हिन्दी (Hindi)</span></button>
        <button class="gt-opt" onclick="gtPick('sw')"><span class="gt-fl">🇰🇪</span><span class="gt-nm">Kiswahili</span></button>
        <button class="gt-opt" onclick="gtPick('ha')"><span class="gt-fl">🇳🇬</span><span class="gt-nm">Hausa</span></button>
        <button class="gt-more">All 100+ languages <a href="#" onclick="gtPick('all');return false;">See all →</a></button>
    </div>
    <button class="gt-float-btn" id="gt-float-btn" aria-label="Change Language" title="Change Language">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
    </button>
</div>
<script>
/* Toggle menu */
document.getElementById('gt-float-btn').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('gt-float-menu').classList.toggle('open');
});

/* Close on outside click */
document.addEventListener('click', function(e) {
    var m = document.getElementById('gt-float-menu');
    if (m && !e.target.closest('.gt-float')) m.classList.remove('open');
});

/* Pick language — set GOOGTRANS cookie and reload so Google Translate picks it up */
function gtPick(lang) {
    document.getElementById('gt-float-menu').classList.remove('open');
    if (lang === 'all') {
        /* "See all" shows Google's native combo */
        var combo = document.querySelector('#google_translate_element select.goog-te-combo');
        if (combo) {
            /* Position it near the floating icon */
            combo.style.position = 'fixed';
            combo.style.bottom = '150px';
            combo.style.left = '20px';
            combo.style.zIndex = '100000';
            combo.style.display = 'block';
            combo.style.height = '40px';
            combo.style.width = '200px';
            combo.style.fontSize = '14px';
            combo.focus();
        }
        return;
    }
    if (lang === '') {
        /* English — clear Google Translate */
        document.cookie = 'GOOGTRANS=;path=/;max-age=0';
        location.reload();
        return;
    }
    /* Set GOOGTRANS cookie and reload — Google Translate reads it on init */
    document.cookie = 'GOOGTRANS=/en/' + lang + ';path=/;max-age=' + (365*86400) + ';SameSite=Lax';
    location.reload();
}

/* Highlight active language on load */
(function() {
    var match = document.cookie.match(/GOOGTRANS=\/en\/([a-z-]+)/);
    if (match) {
        var lang = match[1];
        var opts = document.querySelectorAll('.gt-opt');
        opts.forEach(function(o) {
            if (o.getAttribute('onclick') && o.getAttribute('onclick').indexOf("'" + lang + "'") !== -1) {
                o.classList.add('on');
            }
        });
    }
})();
</script>
