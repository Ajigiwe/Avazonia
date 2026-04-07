<?php
// index.php
require_once 'config/app.php';
require_once 'core/Router.php';
require_once 'core/Session.php';

Session::start();

$router = new Router();

// Routes
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/shop', 'ShopController@index');
$router->add('GET', '/deals', 'DealsController@index');
$router->add('GET', '/product/([a-z0-9-]+)', 'ProductController@show');
$router->add('GET', '/cart', 'CartController@index');
$router->add('POST', '/cart/update', 'CartController@update');
$router->add('POST', '/cart/remove', 'CartController@remove');
$router->add('POST', '/api/cart-add', 'CartController@add');
$router->add('GET', '/api/search-suggestions', 'ShopController@suggestions');
$router->add('POST', '/api/review-add', 'ReviewController@add');
$router->add('GET', '/checkout', 'CheckoutController@index');
$router->add('POST', '/checkout/complete', 'CheckoutController@complete');
$router->add('POST', '/checkout/init-balance', 'CheckoutController@initBalancePayment');
$router->add('GET', '/order/invoice/([A-Z0-9-]+)', 'InvoiceController@show');

// Support routes
$router->add('GET', '/about', 'PageController@about');
$router->add('GET', '/shipping', 'PageController@shipping');
$router->add('GET', '/warranty', 'PageController@warranty');
$router->add('GET', '/returns', 'PageController@returns');
$router->add('GET', '/faq', 'PageController@faq');
$router->add('GET', '/terms', 'PageController@terms');
$router->add('GET', '/privacy', 'PageController@privacy');
$router->add('GET', '/payment-policy', 'PageController@paymentPolicy');
$router->add('GET', '/track-order', 'PageController@trackOrder');
$router->add('GET', '/contact', 'PageController@contact');
$router->add('POST', '/contact', 'PageController@contact');

// Auth routes
$router->add('GET', '/login', 'AccountController@login');
$router->add('POST', '/login', 'AccountController@login');
$router->add('GET', '/register', 'AccountController@register');
$router->add('POST', '/register', 'AccountController@register');
$router->add('GET', '/logout', 'AccountController@logout');
$router->add('GET', '/account', 'AccountController@index');
$router->add('GET', '/orders', 'AccountController@index');
$router->add('GET', '/wishlist', 'WishlistController@index');
$router->add('POST', '/api/wishlist-toggle', 'WishlistController@toggle');
$router->add('GET', '/account/order/([0-9]+)', 'AccountController@orderDetails');
$router->add('GET', '/account/settings', 'AccountController@settings');
$router->add('POST', '/account/settings', 'AccountController@settings');

// Email verification & password reset routes
$router->add('GET', '/verify-pending',         'AccountController@verifyPending');
$router->add('GET', '/verify-email',           'AccountController@verifyEmail');
$router->add('POST', '/api/resend-verification','AccountController@resendVerification');
$router->add('GET', '/forgot-password',        'AccountController@forgotPassword');
$router->add('POST', '/forgot-password',       'AccountController@forgotPassword');
$router->add('GET', '/reset-password',         'AccountController@resetPassword');
$router->add('POST', '/reset-password',        'AccountController@resetPassword');

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Strip query string
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

// Handle subfolder if APP_URL has one
$basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
if (!$uri) $uri = '/';

$router->dispatch($uri, $method);
