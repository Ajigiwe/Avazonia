<?php
// Bypass the redirect — don't set $_GET before Translator loads
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Session.php';
Session::start();
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/Translator.php';

// Force language via session (no URL param, no redirect)
$lang = $_GET['lang'] ?? 'zh';
$_SESSION['lang'] = $lang;
setcookie('avazonia_lang', $lang, time() + 30*24*3600, '/');

// Reset singleton
$ref = new ReflectionClass('Translator');
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, null);

// Now create fresh instance (no ?lang= in URL, so no redirect)
$t = Translator::getInstance();

header('Content-Type: text/plain; charset=utf-8');
echo "=== Avazonia Language Diagnostic ===\n\n";
echo "Active lang: " . $t->getLang() . "\n";
echo "Flag: " . $t->getFlag($t->getLang()) . "\n\n";

// Test key translations
$testKeys = [
    'nav.home', 'nav.shop', 'nav.cart', 'nav.login',
    'footer.shop', 'footer.contact_us', 'footer.tagline',
    'product.add_to_bag', 'product.wishlist',
    'cart.title', 'cart.checkout',
    'checkout.place_order', 'checkout.contact',
    'shop.title', 'shop.all_products',
    'cat.all', 'cat.phones',
    'misc.view_all',
];

echo "--- Sample Translations (zh) ---\n";
foreach ($testKeys as $key) {
    $val = $t->get($key, '???');
    $isTranslated = ($val !== $key && $val !== '???');
    $mark = $isTranslated ? '✓' : '✗';
    echo "$mark $key = $val\n";
}

echo "\n--- t() function test ---\n";
echo "t('nav.home') = " . t('nav.home', 'Home') . "\n";
echo "t('nav.shop') = " . t('nav.shop', 'Shop') . "\n";

echo "\n--- Session/Cookie ---\n";
echo "Session lang: " . ($_SESSION['lang'] ?? 'NOT SET') . "\n";

echo "\n⚠️ DELETE THIS FILE AFTER USE.";
