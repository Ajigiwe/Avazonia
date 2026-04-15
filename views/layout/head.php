<?php
// views/layout/head.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            --nav-offset: 0px;
        }
        @media (min-width: 1024px) {
            .nav-cat-rail { display: flex; }
        }
        .page-fade { transition: opacity 0.4s ease, transform 0.4s ease; }
        .page-fade.is-loading { opacity: 0; transform: translateY(10px); }
    </style>
    <link rel="icon" type="image/jpeg" href="<?= APP_URL ?>/public/assets/img/logo.jpg">

    <!-- PWA Support -->
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="<?= PRIMARY_COLOR ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= APP_NAME ?>">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/public/assets/img/logo.png">
</head>
<body>
