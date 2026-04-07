<?php
// views/layout/hero.php
require_once __DIR__ . '/../../models/Slider.php';

$sliderModel = new Slider();

// Get current relative path
$uri = $_SERVER['REQUEST_URI'];
$basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
$path = $uri;
if ($basePath && strpos($uri, $basePath) === 0) {
    $path = substr($uri, strlen($basePath));
}
// Strip query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}
if (!$path) $path = '/';

$activeSlides = $sliderModel->getSlidesByPage($path);

// If no slides match, we show nothing (No Hero Fallback)
if (empty($activeSlides)) return;
?>

<section class="hero-slider">
    <?php foreach ($activeSlides as $index => $s): ?>
    <div class="hero-slide <?= $index === 0 ? 'active' : '' ?> template-<?= $s['template_type'] ?>">
        
        <?php if ($s['template_type'] === 'split'): ?>
            <!-- MODERN SPLIT TEMPLATE -->
            <div class="hero-left">
                <div class="sec-eyebrow">
                    <span class="eyebrow-text">Exclusive Drop</span>
                    <span class="eyebrow-line"></span>
                </div>
                <h1 class="hero-heading"><?= $s['heading'] ?></h1>
                <p style="font-size: 18px; color: rgba(255,255,255,0.7); max-width: 480px; margin-bottom: 48px; line-height: 1.5; font-weight: 500;">
                    <?= $s['subheading'] ?>
                </p>
                <div style="display: flex; gap: 20px;">
                    <a href="<?= APP_URL . $s['cta_link'] ?>" class="btn-red"><?= $s['cta_text'] ?> →</a>
                </div>
            </div>
            <div class="hero-right">
                <img src="<?= APP_URL ?>/<?= $s['image_url'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>

        <?php elseif ($s['template_type'] === 'full-width'): ?>
            <!-- FULL IMMERSIVE TEMPLATE -->
            <div class="hero-full-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;">
                <img src="<?= APP_URL ?>/<?= $s['image_url'] ?>" style="width: 100%; height: 100%; object-fit: cover; filter: brightness(0.6);">
            </div>
            <div class="hero-full-content" style="position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: #fff; padding: 0 40px;">
                <div class="sec-eyebrow" style="justify-content: center;">
                    <span class="eyebrow-text" style="color: var(--red);">Premium Highlight</span>
                </div>
                <h1 class="hero-heading" style="font-size: clamp(42px, 10vw, 120px);"><?= $s['heading'] ?></h1>
                <p style="font-size: 20px; color: rgba(255,255,255,0.9); max-width: 700px; margin-bottom: 48px; line-height: 1.6;">
                    <?= $s['subheading'] ?>
                </p>
                <a href="<?= APP_URL . $s['cta_link'] ?>" class="btn-red" style="padding: 0 48px; height: 56px;"><?= $s['cta_text'] ?></a>
            </div>

        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <?php if (count($activeSlides) > 1): ?>
    <div class="slider-dots">
        <?php foreach ($activeSlides as $index => $s): ?>
            <div class="dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<style>
/* Full width specific corrections if needed */
.template-full-width .hero-left, .template-full-width .hero-right { display: none; }
</style>
