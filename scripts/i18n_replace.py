#!/usr/bin/env python3
"""Batch-replace hardcoded English strings with t() calls."""
import pathlib, re

ROOT = pathlib.Path(__file__).resolve().parent.parent

# ── NAV.PHP ──
p = ROOT / 'views/layout/nav.php'
c = p.read_text(encoding='utf-8')

nav_replacements = [
    ('>Wishlist<', "><?= t('nav.wishlist', 'Wishlist') ?><"),
    ('>My Account<', "><?= t('account.my_account', 'My Account') ?><"),
    ('>My Orders<', "><?= t('account.orders', 'My Orders') ?><"),
    ('>Become a Seller<', "><?= t('seller.become', 'Become a Seller') ?><"),
    ('>Login<', "><?= t('nav.login', 'Login') ?><"),
    ('>Register<', "><?= t('nav.register', 'Register') ?><"),
]
for old, new in nav_replacements:
    c = c.replace(old, new)
p.write_text(c, encoding='utf-8')
print('nav.php done')

# ── FOOTER.PHP ──
p = ROOT / 'views/layout/footer.php'
c = p.read_text(encoding='utf-8')

footer_replacements = [
    ('>Contact Us<', "><?= t('footer.contact_us', 'Contact Us') ?><"),
    ('>Help Center<', "><?= t('footer.help_center', 'Help Center') ?><"),
    ('>Returns & Refunds<', "><?= t('footer.returns', 'Returns & Refunds') ?><"),
    ('>Shipping Info<', "><?= t('footer.shipping_info', 'Shipping Info') ?><"),
    ('>Login / Register<', "><?= t('nav.login', 'Login') ?> / <?= t('nav.register', 'Register') ?><"),
    ('>Privacy Policy<', "><?= t('footer.privacy', 'Privacy Policy') ?><"),
    ('>Terms of Service<', "><?= t('footer.terms', 'Terms of Service') ?><"),
    ('>Stay updated<', "><?= t('footer.newsletter', 'Stay updated') ?><"),
]
for old, new in footer_replacements:
    c = c.replace(old, new)
p.write_text(c, encoding='utf-8')
print('footer.php done')

# ── PRODUCT CARD ──
p = ROOT / 'views/components/product-card.php'
c = p.read_text(encoding='utf-8')

card_replacements = [
    ('>Add to Bag<', "><?= t('product.add_to_bag', 'Add to Bag') ?><"),
    ("'Add to Cart'", "'<?= t(\"product.add_to_cart\", \"Add to Cart\") ?>'"),
    ('>Wishlist<', "><?= t('product.wishlist', 'Wishlist') ?><"),
    ('>Share<', "><?= t('product.share', 'Share') ?><"),
    ('>View Product<', "><?= t('product.view_product', 'View Product') ?><"),
]
for old, new in card_replacements:
    c = c.replace(old, new)
p.write_text(c, encoding='utf-8')
print('product-card.php done')

# ── CART ──
p = ROOT / 'views/cart/index.php'
c = p.read_text(encoding='utf-8')

cart_replacements = [
    ("'Your Cart '", "t('cart.title', 'Your Cart') . ' '"),
    ('>Proceed to Checkout<', "><?= t('cart.checkout', 'Proceed to Checkout') ?><"),
    ("'Subtotal'", "t('cart.subtotal', 'Subtotal')"),
    ("'Shipping'", "t('cart.shipping', 'Shipping')"),
    ("'Total'", "t('cart.total', 'Total')"),
    ("'Remove'", "t('cart.remove', 'Remove')"),
    ("'FREE'", "t('cart.free', 'FREE')"),
]
for old, new in cart_replacements:
    c = c.replace(old, new)
p.write_text(c, encoding='utf-8')
print('cart/index.php done')

# ── CHECKOUT ──
p = ROOT / 'views/checkout/index.php'
c = p.read_text(encoding='utf-8')

checkout_replacements = [
    ("'Contact Info'", "t('checkout.contact', 'Contact Info')"),
    ("'Full Name'", "t('checkout.name', 'Full Name')"),
    ("'Email Address'", "t('checkout.email', 'Email Address')"),
    ("'Phone Number'", "t('checkout.phone', 'Phone Number')"),
    ("'Shipping Address'", "t('checkout.shipping_addr', 'Shipping Address')"),
    ("'City'", "t('checkout.city', 'City')"),
    ("'Place Order'", "t('checkout.place_order', 'Place Order')"),
    ("'Pay Now'", "t('checkout.pay_now', 'Pay Now')"),
    ("'Pay on Delivery'", "t('checkout.pay_delivery', 'Pay on Delivery')"),
]
for old, new in checkout_replacements:
    c = c.replace(old, new)
p.write_text(c, encoding='utf-8')
print('checkout/index.php done')

print('\nAll done!')
