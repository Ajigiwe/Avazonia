<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Session.php';
Session::start();
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/Translator.php';

$lang = $_GET['lang'] ?? 'en';

// Force set the language
$_SESSION['lang'] = $lang;
setcookie('avazonia_lang', $lang, time() + 30*24*3600, '/');

// Get a fresh translator instance
$oldInstance = Translator::getInstance();

// Reflect to reset singleton so we get fresh instance
$ref = new ReflectionClass('Translator');
$prop = $ref->getProperty('instance');
$prop->setAccessible(true);
$prop->setValue(null, null);

$_GET['lang'] = $lang;
$t = Translator::getInstance();

header('Content-Type: text/plain; charset=utf-8');
echo "=== Avazonia Language Diagnostic ===\n\n";
echo "Requested lang: $lang\n";
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

echo "--- Sample Translations ---\n";
foreach ($testKeys as $key) {
    $val = $t->get($key, '???');
    $isTranslated = ($val !== $key && $val !== '???');
    $mark = $isTranslated ? '✓' : '✗';
    echo "$mark $key = $val\n";
}

// Check if t() function works
echo "\n--- t() function test ---\n";
echo "t('nav.home') = " . t('nav.home', 'Home') . "\n";
echo "t('nav.shop') = " . t('nav.shop', 'Shop') . "\n";

// Check session and cookie
echo "\n--- Session/Cookie ---\n";
echo "Session lang: " . ($_SESSION['lang'] ?? 'NOT SET') . "\n";
echo "Cookie lang: " . ($_COOKIE['avazonia_lang'] ?? 'NOT SET') . "\n";

echo "\n⚠️ DELETE THIS FILE AFTER USE.";
