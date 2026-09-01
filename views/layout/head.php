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

        /* ═══════════════════════════════════════
           Google Translate — hide all native chrome
           ═══════════════════════════════════════ */
        .goog-te-banner-frame,
        .goog-te-banner-frame + div,
        .skiptranslate,
        #goog-gt-tt,
        #google_translate_element { display: none !important; }
        body { top: 0 !important; }

        /* ═══════════════════════════════════════
           Floating Language Icon
           ═══════════════════════════════════════ */
        .gt-float {
            position: fixed;
            bottom: 90px;
            left: 20px;
            z-index: 99999;
            font-family: var(--f-body, -apple-system, sans-serif);
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
        .gt-float-menu.open { display: block; }
        .gt-float-menu-title {
            padding: 8px 16px 4px;
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
        .gt-opt.on {
            background: rgba(229,0,26,0.06);
            color: var(--red, #E5001A);
            font-weight: 600;
        }
        .gt-opt .gt-fl { font-size: 20px; line-height: 1; }
        .gt-opt .gt-nm { flex: 1; }
        @media (max-width: 768px) {
            .gt-float { bottom: 72px; left: 16px; }
            .gt-float-btn { width: 46px; height: 46px; }
            .gt-float-menu { min-width: 160px; }
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
</head>
<body>

<!-- Floating Language Icon -->
<div class="gt-float" id="gt-float">
    <div class="gt-float-menu" id="gt-float-menu">
        <div class="gt-float-menu-title">Language</div>
        <button class="gt-opt" id="gt-opt-en" onclick="gtPick('en')"><span class="gt-fl">🇬🇧</span><span class="gt-nm">English</span></button>
        <button class="gt-opt" id="gt-opt-fr" onclick="gtPick('fr')"><span class="gt-fl">🇫🇷</span><span class="gt-nm">Français</span></button>
        <button class="gt-opt" id="gt-opt-zh" onclick="gtPick('zh-CN')"><span class="gt-fl">🇨🇳</span><span class="gt-nm">中文</span></button>
        <button class="gt-opt" id="gt-opt-es" onclick="gtPick('es')"><span class="gt-fl">🇪🇸</span><span class="gt-nm">Español</span></button>
        <button class="gt-opt" id="gt-opt-de" onclick="gtPick('de')"><span class="gt-fl">🇩🇪</span><span class="gt-nm">Deutsch</span></button>
        <button class="gt-opt" id="gt-opt-ar" onclick="gtPick('ar')"><span class="gt-fl">🇸🇦</span><span class="gt-nm">العربية</span></button>
        <button class="gt-opt" id="gt-opt-pt" onclick="gtPick('pt')"><span class="gt-fl">🇧🇷</span><span class="gt-nm">Português</span></button>
    </div>
    <button class="gt-float-btn" onclick="document.getElementById('gt-float-menu').classList.toggle('open')" aria-label="Change Language" title="Change Language">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
    </button>
</div>
<script>
/* Toggle menu open/close */
function gtToggle() {
    var m = document.getElementById('gt-float-menu');
    if (m) m.classList.toggle('open');
}
/* Pick language — set GOOGTRANS cookie and reload */
function gtPick(lang) {
    document.getElementById('gt-float-menu').classList.remove('open');
    if (lang === 'en') {
        document.cookie = 'GOOGTRANS=;path=/;max-age=0';
    } else {
        document.cookie = 'GOOGTRANS=/en/' + lang + ';path=/;max-age=' + (365*86400) + ';SameSite=Lax';
    }
    localStorage.setItem('avazonia_lang', lang);
    location.reload();
}
/* Close menu on outside click */
document.addEventListener('click', function(e) {
    var m = document.getElementById('gt-float-menu');
    var b = document.getElementById('gt-float-btn');
    if (m && e.target !== b && !b.contains(e.target)) {
        m.classList.remove('open');
    }
});
/* Highlight active language on load */
(function() {
    var lang = localStorage.getItem('avazonia_lang') || 'en';
    var id = 'gt-opt-' + lang.replace(/-CN/i, '').toLowerCase();
    var el = document.getElementById(id);
    if (el) el.classList.add('on');
})();
</script>
