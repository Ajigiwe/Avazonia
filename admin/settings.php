<?php
// admin/settings.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Category.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$settingsModel = new Settings();
$dbSettings = $settingsModel->all();

function getSet($key, $default = '') {
    global $dbSettings;
    return isset($dbSettings[$key]) ? htmlspecialchars($dbSettings[$key]) : $default;
}

// Render a YES/NO toggle select. $help explains what the setting does.
function toggleSelect(string $key, string $label, string $labelOn, string $labelOff, string $default = '0', string $help = ''): void {
    ?>
    <div class="field-group">
        <label class="field-label" for="set-<?= $key ?>"><?= htmlspecialchars($label) ?></label>
        <select id="set-<?= $key ?>" class="field-input">
            <option value="1" <?= getSet($key, $default) == '1' ? 'selected' : '' ?>><?= htmlspecialchars($labelOn) ?></option>
            <option value="0" <?= getSet($key, $default) == '0' ? 'selected' : '' ?>><?= htmlspecialchars($labelOff) ?></option>
        </select>
        <?php if ($help): ?><span class="field-sub"><?= $help ?></span><?php endif; ?>
    </div>
    <?php
}

$title = "Settings — Avazonia";
include 'layout/header.php';
?>

<style>
    .settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 40px; align-items: start; min-height: 70vh; }

    /* Settings Sidebar Nav */
    .settings-nav {
        display: flex; flex-direction: column; gap: 6px; position: sticky; top: 40px;
        background: #000; padding: 20px 14px; border-radius: 16px; border: 1px solid #222;
    }
    .settings-tab-btn {
        background: transparent; border: none; padding: 12px 16px; text-align: left;
        font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em;
        color: #777; cursor: pointer; border-radius: 8px; transition: all 0.2s;
        display: flex; align-items: center; gap: 12px; font-weight: 700;
    }
    .settings-tab-btn:hover { background: #111; color: #fff; }
    .settings-tab-btn.active { background: #fff; color: #000; }
    .settings-tab-btn .tab-dot { display: none; width: 6px; height: 6px; border-radius: 50%; background: var(--red); margin-left: auto; }
    .settings-tab-btn.has-unsaved .tab-dot { display: inline-block; }

    /* Content Area */
    .settings-content-area { max-width: 860px; min-width: 0; }
    .settings-section { display: none; animation: fadeIn 0.25s ease; }
    .settings-section.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .section-header { margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1px solid var(--light-gray); }
    .section-header h2 { font-family: var(--f-display); font-weight: 800; font-size: 24px; letter-spacing: -0.01em; margin: 0; }
    .section-header p { font-size: 13px; color: var(--mid-gray); margin-top: 4px; }

    /* Inputs */
    .field-group { margin-bottom: 22px; }
    .field-label { display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 8px; letter-spacing: 0.05em; }
    .field-input {
        width: 100%; padding: 12px 16px; border: 1px solid var(--light-gray); border-radius: 8px;
        font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; background: #fff; box-sizing: border-box;
    }
    .field-input:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
    .field-sub { font-size: 11px; color: var(--mid-gray); margin-top: 8px; display: block; }
    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 700px) { .field-grid { grid-template-columns: 1fr; } }

    /* Grouped setting cards */
    .setting-card { background: #fff; border: 1px solid var(--light-gray); border-radius: 14px; padding: 28px; margin-bottom: 24px; }
    .setting-card > h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
    .setting-card > .card-desc { font-size: 12px; color: var(--mid-gray); margin: 0 0 20px; }
    .setting-card .field-group:last-child { margin-bottom: 0; }
    .card-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--off); display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }

    /* Sync Bar */
    .sync-bar {
        position: fixed; bottom: 24px; left: calc(var(--sidebar-w) + 40px + 240px + 40px); right: 40px;
        background: var(--ink); color: #fff; padding: 14px 28px; border-radius: 14px;
        display: flex; justify-content: space-between; align-items: center; gap: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25); z-index: 1000; transition: all 0.3s;
        transform: translateY(120px); pointer-events: none; opacity: 0;
    }
    .sync-bar.visible { transform: translateY(0); pointer-events: auto; opacity: 1; }
    .sync-bar .sync-status { font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #9a9a9a; }
    .sync-bar .sync-actions { display: flex; gap: 12px; }
    .sync-bar button { height: 42px; padding: 0 24px; border: none; border-radius: 8px; font-family: var(--f-semi); font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; }
    #discardBtn { background: transparent; color: #bbb; border: 1px solid #333 !important; }
    #discardBtn:hover { color: #fff; border-color: #555 !important; }
    #saveBtn { background: #fff; color: #000; }
    #saveBtn:disabled { opacity: 0.6; cursor: wait; }
    #saveBtn.save-ok { background: #00A854; color: #fff; }
    #saveBtn.save-err { background: var(--red); color: #fff; }

    @media (max-width: 1200px) {
        .settings-layout { grid-template-columns: 1fr; }
        .settings-nav { position: relative; top: 0; flex-direction: row; overflow-x: auto; }
        .settings-tab-btn { white-space: nowrap; }
        .sync-bar { left: 40px; }
    }
    @media (max-width: 900px) {
        .sync-bar { left: 16px; right: 16px; bottom: 16px; flex-direction: column; text-align: center; }
        .sync-bar .sync-actions { width: 100%; }
        .sync-bar .sync-actions button { flex: 1; }
    }

    .swatch { width: 30px; height: 30px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: transform 0.15s; }
    .swatch:hover { transform: scale(1.12); }
    .swatch.active { border-color: var(--ink); }

    /* Slide rows */
    .slide-row { background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 20px; }
    @media (max-width: 700px) { .slide-row { flex-direction: column; align-items: stretch; } }
</style>

<div class="admin-header">
    <h1>Settings</h1>
    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: 0.1em;">Configuration</div>
</div>

<div class="settings-layout">
    <!-- NAVIGATION -->
    <aside class="settings-nav">
        <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
            <button class="settings-tab-btn active" data-tab="general">🏠 General<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="storefront">🎨 Storefront<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="payments">💳 Payments<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="logistics">🚚 Logistics<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="social">🌐 Social &amp; SEO<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="policies">📜 Policies<span class="tab-dot"></span></button>
            <button class="settings-tab-btn" data-tab="hero">🖼️ Hero Banners<span class="tab-dot"></span></button>
        </div>

        <div style="margin-top: 24px; padding: 18px; background: #111; border-radius: 12px; border: 1px solid #222;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                <div id="navStatusDot" style="width: 8px; height: 8px; border-radius: 50%; background: #00A854;"></div>
                <div id="navStatusLabel" style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.1em; color: #888; font-weight: 700;">All changes saved</div>
            </div>
            <button type="button" id="navSaveBtn" style="width: 100%; padding: 12px; background: #fff; color: #000; border: none; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase; cursor: pointer; letter-spacing: 0.05em;">Save Changes</button>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="settings-content-area">

        <!-- ═══════════ GENERAL ═══════════ -->
        <section id="tab-general" class="settings-section active">
            <div class="section-header">
                <h2>General</h2>
                <p>Store identity, contact details, branding, and seller onboarding rules.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🏪</span> Store Identity</h3>
                <p class="card-desc">Shown in browser titles, emails, invoices, and the storefront.</p>
                <div class="field-group">
                    <label class="field-label" for="set-store_name">Store Public Name</label>
                    <input type="text" id="set-store_name" value="<?= getSet('store_name', 'Avazonia') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label" for="set-primary_brand_color">Primary Brand Color</label>
                    <div style="display: flex; gap: 14px; align-items: center;">
                        <input type="color" id="colorPicker" value="<?= getSet('primary_brand_color', '#E5001A') ?>" style="width: 42px; height: 42px; border: none; background: none; cursor: pointer; padding: 0;">
                        <input type="text" id="set-primary_brand_color" value="<?= getSet('primary_brand_color', '#E5001A') ?>" class="field-input" style="flex: 1; font-family: var(--f-mono); max-width: 200px;">
                        <div style="display: flex; gap: 8px;">
                            <?php foreach (['#E5001A', '#007AFF', '#00A854', '#FF9500', '#5856D6', '#000000'] as $p): ?>
                                <div class="swatch" data-color="<?= $p ?>" style="background: <?= $p ?>;"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-store_map_address">Store Location (Google Maps)</label>
                    <input type="text" id="set-store_map_address" value="<?= getSet('store_map_address') ?>" class="field-input" placeholder="Spintex Road, Accra...">
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📞</span> Contact &amp; Support</h3>
                <p class="card-desc">Displayed in the support banner and used for customer contact.</p>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-support_email">Support Email</label>
                        <input type="email" id="set-support_email" value="<?= getSet('support_email', 'hello@avazonia.com.gh') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-whatsapp_number">WhatsApp Number</label>
                        <input type="text" id="set-whatsapp_number" value="<?= getSet('whatsapp_number', '233240000000') ?>" class="field-input" placeholder="233240000000">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-support_phone">Support Phone</label>
                        <input type="text" id="set-support_phone" value="<?= getSet('support_phone', '+233 201500300') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-support_hours">Business Hours</label>
                        <input type="text" id="set-support_hours" value="<?= getSet('support_hours', 'Monday to Saturday - 9am - 6pm') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-support_title">Support Banner Title</label>
                        <input type="text" id="set-support_title" value="<?= getSet('support_title', 'Need Any Help?') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-support_subtitle">Support Banner Subtitle</label>
                        <input type="text" id="set-support_subtitle" value="<?= getSet('support_subtitle', 'We are here to help you with any question.') ?>" class="field-input">
                    </div>
                    <div class="field-group" style="margin-bottom:0;">
                        <label class="field-label" for="set-footer_address">Footer Address</label>
                        <input type="text" id="set-footer_address" value="<?= getSet('footer_address', 'Q4 Gibbefish Street Beach Road Takoradi, Ghana') ?>" class="field-input">
                    </div>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🛡️</span> Seller Verification</h3>
                <p class="card-desc">Controls whether new sellers must pass document verification before their products appear.</p>
                <div class="field-group" style="margin-bottom:0;">
                    <div style="display:flex;align-items:center;gap:16px;background:var(--off);padding:16px 20px;border-radius:8px;">
                        <label style="position:relative;display:inline-block;width:56px;height:30px;flex-shrink:0;">
                            <input type="checkbox" id="set-seller_verification_required" class="field-input" value="<?= getSet('seller_verification_required', '1') == '1' ? '1' : '0' ?>" <?= getSet('seller_verification_required', '1') == '1' ? 'checked' : '' ?> style="opacity:0;width:0;height:0;padding:0;border:none;">
                            <span class="verif-slider" style="position:absolute;cursor:pointer;inset:0;border-radius:999px;background:<?= getSet('seller_verification_required', '1') == '1' ? 'var(--red)' : '#8a8a8a' ?>;transition:.3s;"></span>
                            <span class="verif-knob" style="position:absolute;height:22px;width:22px;left:4px;bottom:4px;background:#fff;border-radius:50%;transition:.3s;<?= getSet('seller_verification_required', '1') == '1' ? 'transform:translateX(26px);' : '' ?>"></span>
                        </label>
                        <div>
                            <div id="verif-state-label" style="font-family:var(--f-semi);font-size:13px;font-weight:800;">
                                <?= getSet('seller_verification_required', '1') == '1' ? 'ON — new sellers must pass Ghana Card + Face ID' : 'OFF — anyone can start selling instantly' ?>
                            </div>
                            <div class="field-sub" style="margin-top:4px;">When OFF, seller applications skip document capture and their products go straight to the marketplace. Existing verified sellers keep their badges.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🖼️</span> Product Media</h3>
                <p class="card-desc">Newly uploaded product images are stamped with the shop logo to deter image theft. Existing uploads are never modified.</p>
                <div class="field-grid">
                    <?php toggleSelect('product_watermark_enabled', 'Watermark Product Images', 'YES — stamp logo on new uploads', 'NO — keep images clean', '1'); ?>
                </div>
            </div>
        </section>

        <!-- ═══════════ STOREFRONT ═══════════ -->
        <section id="tab-storefront" class="settings-section">
            <div class="section-header">
                <h2>Storefront</h2>
                <p>Visual layout, announcements, homepage sections, and the marketing popup.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📢</span> Announcement &amp; Texts</h3>
                <p class="card-desc">Global messaging shown across the storefront.</p>
                <div class="field-group">
                    <label class="field-label" for="set-announcement_text">Global Announcement Bar</label>
                    <input type="text" id="set-announcement_text" value="<?= getSet('announcement_text') ?>" class="field-input" placeholder="FREE SHIPPING ON ALL S25 ORDERS!">
                </div>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-home_deals_title">Deals Section Title</label>
                        <input type="text" id="set-home_deals_title" value="<?= getSet('home_deals_title', 'FLASH DEALS & DROPS') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-home_deals_eyebrow">Deals Eyebrow Text</label>
                        <input type="text" id="set-home_deals_eyebrow" value="<?= getSet('home_deals_eyebrow', 'EXCLUSIVE OPPORTUNITY HUB') ?>" class="field-input">
                    </div>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-footer_notice">Footer Copyright Notice</label>
                    <input type="text" id="set-footer_notice" value="<?= getSet('footer_notice', '© 2026 AVAZONIA GH') ?>" class="field-input">
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🧊</span> Product Grid &amp; Cards</h3>
                <p class="card-desc">How products are laid out and presented on listing pages.</p>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-grid_density">Product Grid Density</label>
                        <select id="set-grid_density" class="field-input">
                            <option value="4" <?= getSet('grid_density') == '4' ? 'selected' : '' ?>>Roomy (4 Columns)</option>
                            <option value="6" <?= getSet('grid_density') == '6' ? 'selected' : '' ?>>Standard (6 Columns)</option>
                            <option value="8" <?= getSet('grid_density') == '8' ? 'selected' : '' ?>>Dense (8 Columns)</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-min_stock_threshold">Hide Low-Stock Below (units)</label>
                        <input type="number" id="set-min_stock_threshold" value="<?= getSet('min_stock_threshold', '1') ?>" class="field-input">
                    </div>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <?php toggleSelect('product_card_slider_enabled', 'Card Image Slideshow', 'Auto-cycle images on product cards', 'Show first image only (no auto-cycle)', '1'); ?>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📱</span> Mobile Category Grid</h3>
                <p class="card-desc">Pick which categories appear in the homepage tile grid on mobile. The first selected becomes the hero tile.</p>
                <input type="hidden" id="set-home_mobile_category_grid" class="field-input" value="<?= getSet('home_mobile_category_grid') ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;" id="category-grid-checkboxes">
                    <?php
                    $catModel = new Category();
                    $allCats = $catModel->getAll();
                    $selectedIds = array_filter(array_map('intval', explode(',', getSet('home_mobile_category_grid'))));
                    foreach ($allCats as $cat):
                        $isChecked = in_array((int)$cat['id'], $selectedIds);
                    ?>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--off); border-radius: 8px; cursor: pointer; font-size: 12px;">
                        <input type="checkbox" value="<?= $cat['id'] ?>" <?= $isChecked ? 'checked' : '' ?>
                            onchange="updateCategoryGrid()" style="accent-color: var(--red);">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">✨</span> Marketing Popup</h3>
                <p class="card-desc">The promo popup shown to homepage visitors.</p>
                <div class="field-grid">
                    <?php toggleSelect('home_popup_enabled', 'Popup Status', 'Enabled', 'Disabled', '0'); ?>
                    <div class="field-group">
                        <label class="field-label" for="set-home_popup_type">Popup Type</label>
                        <select id="set-home_popup_type" class="field-input">
                            <option value="promo" <?= getSet('home_popup_type') == 'promo' ? 'selected' : '' ?>>Promotional Image</option>
                            <option value="newsletter" <?= getSet('home_popup_type') == 'newsletter' ? 'selected' : '' ?>>Newsletter Subscription</option>
                            <option value="discount" <?= getSet('home_popup_type') == 'discount' ? 'selected' : '' ?>>Direct Discount Code</option>
                        </select>
                    </div>
                </div>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-home_popup_frequency">Show Every N Visits</label>
                        <input type="number" id="set-home_popup_frequency" value="<?= getSet('home_popup_frequency', '3') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-home_popup_link">Redirect Link (Promo Only)</label>
                        <input type="text" id="set-home_popup_link" value="<?= getSet('home_popup_link') ?>" class="field-input">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="set-home_popup_title">Main Headline</label>
                    <input type="text" id="set-home_popup_title" value="<?= getSet('home_popup_title') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label" for="set-home_popup_desc">Description / Body Text</label>
                    <textarea id="set-home_popup_desc" class="field-input" style="height: 90px;"><?= getSet('home_popup_desc') ?></textarea>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-home_popup_image">Popup Media / Image URL</label>
                    <input type="text" id="set-home_popup_image" value="<?= getSet('home_popup_image') ?>" class="field-input">
                </div>
            </div>
        </section>

        <!-- ═══════════ PAYMENTS ═══════════ -->
        <section id="tab-payments" class="settings-section">
            <div class="section-header">
                <h2>Payments</h2>
                <p>Paystack gateway credentials and currency.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">💳</span> Paystack API Keys</h3>
                <p class="card-desc">Paste the keys from your Paystack dashboard. The secret key is stored write-only — leave blank to keep the current one.</p>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-paystack_public_key">Public Key</label>
                        <input type="text" id="set-paystack_public_key" value="<?= getSet('paystack_public_key') ?>" class="field-input" style="font-family: var(--f-mono);" placeholder="pk_live_...">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-paystack_secret_key">Secret Key</label>
                        <input type="password" id="set-paystack_secret_key" value="" class="field-input" placeholder="<?= getSet('paystack_secret_key') !== '' ? '•••••••••••••••• (saved — leave blank to keep)' : 'sk_live_...' ?>">
                        <span class="field-sub">Saved securely. Shown masked above; leave blank to keep the existing key.</span>
                    </div>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">₵</span> Currency</h3>
                <div class="field-grid">
                    <div class="field-group" style="margin-bottom:0;">
                        <label class="field-label" for="set-currency_symbol">Store Currency Symbol</label>
                        <input type="text" id="set-currency_symbol" value="<?= getSet('currency_symbol', '₵') ?>" class="field-input" style="max-width: 140px;">
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ LOGISTICS ═══════════ -->
        <section id="tab-logistics" class="settings-section">
            <div class="section-header">
                <h2>Logistics &amp; Finance</h2>
                <p>Shipping rates, deposits, exchange rate, and seller commission.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🚚</span> Shipping Rates (GHS)</h3>
                <p class="card-desc">Applied at checkout based on the delivery region.</p>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-shipping_accra">Greater Accra Rate</label>
                        <input type="number" step="0.01" id="set-shipping_accra" value="<?= getSet('shipping_accra', '30.00') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-shipping_others">Other Regions Rate</label>
                        <input type="number" step="0.01" id="set-shipping_others" value="<?= getSet('shipping_others', '50.00') ?>" class="field-input">
                    </div>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-shipping_free_threshold">Free Shipping Threshold</label>
                    <input type="number" step="0.01" id="set-shipping_free_threshold" value="<?= getSet('shipping_free_threshold', '200') ?>" class="field-input" style="max-width: 220px;">
                    <span class="field-sub">Orders above this amount ship free.</span>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📦</span> Pre-Orders</h3>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-preorder_deposit_pct">Pre-Order Deposit Requirement (%)</label>
                    <input type="number" id="set-preorder_deposit_pct" value="<?= getSet('preorder_deposit_pct', '5') ?>" class="field-input" style="max-width: 220px;">
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">💱</span> Finance</h3>
                <p class="card-desc">Currency conversion and seller payout deductions.</p>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-usd_to_ghs_rate">USD → GHS Exchange Rate</label>
                        <input type="number" step="0.01" id="set-usd_to_ghs_rate" value="<?= getSet('usd_to_ghs_rate', '11.35') ?>" class="field-input">
                        <span class="field-sub">Converts USD product prices to GHS at checkout. Update as the rate changes.</span>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-commission_pct">Seller Commission (%)</label>
                        <input type="number" step="0.1" min="0" max="50" id="set-commission_pct" value="<?= getSet('commission_pct', '5') ?>" class="field-input">
                        <span class="field-sub">Deducted from each seller sale when calculating payouts. Applies platform-wide.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ SOCIAL & SEO ═══════════ -->
        <section id="tab-social" class="settings-section">
            <div class="section-header">
                <h2>Social &amp; SEO</h2>
                <p>Social profiles, community popups, vendor recruiting, and search metadata.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🔗</span> Social Profiles</h3>
                <p class="card-desc">Linked from the site footer.</p>
                <div class="field-grid">
                    <div class="field-group"><label class="field-label" for="set-instagram_link">Instagram</label><input type="text" id="set-instagram_link" value="<?= getSet('instagram_link') ?>" class="field-input" placeholder="https://instagram.com/..."></div>
                    <div class="field-group"><label class="field-label" for="set-facebook_link">Facebook</label><input type="text" id="set-facebook_link" value="<?= getSet('facebook_link') ?>" class="field-input" placeholder="https://facebook.com/..."></div>
                    <div class="field-group"><label class="field-label" for="set-youtube_link">YouTube</label><input type="text" id="set-youtube_link" value="<?= getSet('youtube_link') ?>" class="field-input" placeholder="https://youtube.com/..."></div>
                    <div class="field-group"><label class="field-label" for="set-tiktok_link">TikTok</label><input type="text" id="set-tiktok_link" value="<?= getSet('tiktok_link') ?>" class="field-input" placeholder="https://tiktok.com/@..."></div>
                    <div class="field-group"><label class="field-label" for="set-telegram_link">Telegram</label><input type="text" id="set-telegram_link" value="<?= getSet('telegram_link') ?>" class="field-input" placeholder="https://t.me/..."></div>
                    <div class="field-group"><label class="field-label" for="set-whatsapp_link">WhatsApp Direct Link</label><input type="text" id="set-whatsapp_link" value="<?= getSet('whatsapp_link') ?>" class="field-input" placeholder="https://wa.me/..."></div>
                    <div class="field-group" style="margin-bottom:0;"><label class="field-label" for="set-twitter_link">Twitter / X</label><input type="text" id="set-twitter_link" value="<?= getSet('twitter_link') ?>" class="field-input"></div>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">💬</span> Community Popups</h3>
                <p class="card-desc">Closeable Telegram/WhatsApp invitations in the corner of public pages. A popup only appears when its link is filled in.</p>
                <div class="field-group">
                    <?php toggleSelect('community_popup_enabled', 'Community Popups', 'Enabled', 'Disabled', '1'); ?>
                </div>
                <div class="field-grid">
                    <div class="field-group"><label class="field-label" for="set-community_telegram_link">Telegram Community Link</label><input type="url" id="set-community_telegram_link" value="<?= getSet('community_telegram_link') ?>" class="field-input" placeholder="https://t.me/your-community"></div>
                    <div class="field-group"><label class="field-label" for="set-community_whatsapp_link">WhatsApp Community Link</label><input type="url" id="set-community_whatsapp_link" value="<?= getSet('community_whatsapp_link') ?>" class="field-input" placeholder="https://chat.whatsapp.com/..."></div>
                    <div class="field-group"><label class="field-label" for="set-community_telegram_title">Telegram Popup Title</label><input type="text" id="set-community_telegram_title" value="<?= getSet('community_telegram_title', 'Join our Telegram community') ?>" class="field-input"></div>
                    <div class="field-group"><label class="field-label" for="set-community_whatsapp_title">WhatsApp Popup Title</label><input type="text" id="set-community_whatsapp_title" value="<?= getSet('community_whatsapp_title', 'Join our WhatsApp community') ?>" class="field-input"></div>
                    <div class="field-group"><label class="field-label" for="set-community_telegram_text">Telegram Popup Message</label><input type="text" id="set-community_telegram_text" value="<?= getSet('community_telegram_text', 'Get updates, new drops and offers.') ?>" class="field-input"></div>
                    <div class="field-group" style="margin-bottom:0;"><label class="field-label" for="set-community_whatsapp_text">WhatsApp Popup Message</label><input type="text" id="set-community_whatsapp_text" value="<?= getSet('community_whatsapp_text', 'Connect with the Avazonia community.') ?>" class="field-input"></div>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🤝</span> Vendor Recruiting</h3>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-vendor_invite_message">Vendor Invite Message</label>
                    <textarea id="set-vendor_invite_message" class="field-input" style="height: 100px;"><?= getSet('vendor_invite_message', "Hi! I'd love to have you selling on Avazonia — Ghana's home for hot drops and trusted vendors. Setting up your store is free and takes less than two minutes. Start here: " . APP_URL . "/sell") ?></textarea>
                    <span class="field-sub">Shown in the Invite Vendors card on the admin dashboard. Include the link <strong><?= APP_URL ?>/sell</strong> so vendors land on the signup flow.</span>
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">🔍</span> Search Engine Metadata</h3>
                <p class="card-desc">Used when a page has no product-specific metadata.</p>
                <div class="field-group">
                    <label class="field-label" for="set-meta_description">Default Meta Description</label>
                    <textarea id="set-meta_description" class="field-input" style="height: 90px;"><?= getSet('meta_description') ?></textarea>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-meta_keywords">Default SEO Keywords</label>
                    <input type="text" id="set-meta_keywords" value="<?= getSet('meta_keywords') ?>" class="field-input" placeholder="gadgets, phones, accra, ghana...">
                </div>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📊</span> Google Analytics (Traffic Widget)</h3>
                <p class="card-desc">Connect the existing GA4 property (<?= htmlspecialchars(APP_URL) ?> tracks with ID <strong>G-G3GWGCPMPP</strong>) so the admin dashboard shows visitors, sessions, and top pages without leaving the site.</p>
                <div class="field-group">
                    <?php toggleSelect('page_view_tracking_enabled', 'Built-in Visit Tracker', 'On — log page views server-side (no Google scripts)', 'Off — stop logging visits', '1', 'A first-party page_views log that works even when visitors block Google. Feeds the dashboard\'s Built-in Traffic card and keeps 180 days of history. Turn Off to pause logging; existing history stays.'); ?>
                </div>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label" for="set-ga4_property_id">GA4 Property ID</label>
                        <input type="text" id="set-ga4_property_id" value="<?= getSet('ga4_property_id') ?>" class="field-input" style="font-family: var(--f-mono);" placeholder="e.g. 480123456">
                        <span class="field-sub">The numeric property ID (not the G- measurement ID) — GA4 Admin → Property Settings.</span>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="set-ga4_service_json">Service Account JSON Key</label>
                        <textarea id="set-ga4_service_json" class="field-input" style="height: 110px; font-family: var(--f-mono); font-size: 11px;" placeholder="<?= getSet('ga4_service_json') !== '' ? '•••••• saved — paste a new key file to replace it' : 'Paste the full contents of the downloaded JSON key file' ?>"></textarea>
                        <span class="field-sub">In Google Cloud Console: create a service account → download its JSON key → paste it here. Then in GA4 Admin → Property Access Management, add the service account's email as <strong>Viewer</strong>. Stored write-only; leave blank to keep the saved key.</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ POLICIES ═══════════ -->
        <section id="tab-policies" class="settings-section">
            <div class="section-header">
                <h2>Policies</h2>
                <p>Public-facing policy text shown on checkout and policy pages.</p>
            </div>

            <div class="setting-card">
                <h3><span class="card-icon">📜</span> Policy Documents</h3>
                <div class="field-group">
                    <label class="field-label" for="set-returns_policy">Returns, Refunds &amp; Exchange Policy</label>
                    <textarea id="set-returns_policy" class="field-input" style="height: 220px;"><?= getSet('returns_policy') ?></textarea>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label class="field-label" for="set-shipping_policy">Shipping &amp; Delivery Policy</label>
                    <textarea id="set-shipping_policy" class="field-input" style="height: 220px;"><?= getSet('shipping_policy') ?></textarea>
                </div>
            </div>
        </section>

        <!-- ═══════════ HERO BANNERS ═══════════ -->
        <section id="tab-hero" class="settings-section">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: start; gap: 20px; flex-wrap: wrap;">
                <div>
                    <h2>Hero Banners</h2>
                    <p>Marketing slides across the site. Managed on their own pages.</p>
                </div>
                <a href="add-slide.php" class="btn-red" style="height: 44px; padding: 0 22px; font-size: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">+ Add Slide</a>
            </div>

            <?php
            try {
                require_once __DIR__ . '/../models/Slider.php';
                $sliderModel = new Slider();
                $allSlides = $sliderModel->getAllRecords();
            } catch (Exception $e) {
                echo "<div style='color: var(--red); font-size: 11px; padding: 12px; border: 1px solid var(--red); opacity: 0.7;'>Could not load slides: " . htmlspecialchars($e->getMessage()) . "</div>";
                $allSlides = [];
            }
            ?>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($allSlides as $s): ?>
                    <div class="slide-row">
                        <img src="<?= APP_URL ?>/<?= $s['image_url'] ?>" style="width: 120px; height: 68px; object-fit: cover; border-radius: 8px; border: 1px solid var(--light-gray);">
                        <div style="flex: 1;">
                            <div style="font-family: var(--f-semi); font-size: 14px; margin-bottom: 4px;"><?= htmlspecialchars($s['heading']) ?></div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span style="font-family: var(--f-mono); font-size: 10px; background: var(--off); padding: 2px 8px; border-radius: 4px; color: var(--mid-gray);">
                                    <?= $s['page_path'] === '*' ? 'GLOBAL' : htmlspecialchars($s['page_path']) ?>
                                </span>
                                <span class="status-badge <?= $s['is_active'] ? 'status-paid' : 'status-cancelled' ?>" style="font-size: 9px; padding: 2px 8px;">
                                    <?= $s['is_active'] ? 'Active' : 'Draft' ?>
                                </span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="edit-slide.php?id=<?= $s['id'] ?>" class="btn-ink" style="height: 38px; padding: 0 16px; font-size: 9px; text-decoration: none; display: flex; align-items: center; justify-content: center;">Edit</a>
                            <a href="sliders.php?delete=<?= $s['id'] ?>" class="btn-red" style="height: 38px; padding: 0 16px; font-size: 9px; background: #fff; color: var(--red); border: 1px solid var(--red); text-decoration: none; display: flex; align-items: center; justify-content: center;" data-confirm="Archive this slide? It will disappear from the homepage carousel." data-confirm-title="Archive Slide">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($allSlides)): ?>
                    <div style="text-align: center; padding: 56px; background: var(--off); border-radius: 12px; border: 2px dashed var(--light-gray);">
                        <div style="font-size: 24px; margin-bottom: 10px;">🖼️</div>
                        <div style="font-family: var(--f-semi); font-size: 14px; color: var(--mid-gray);">No Hero Banners Found</div>
                        <p style="font-size: 12px; color: var(--mid-gray); margin-top: 8px;">Add your first marketing slide to dress up the storefront.</p>
                        <a href="add-slide.php" class="nav-link" style="font-size: 11px; margin-top: 14px; display: inline-block;">+ Start Creating Slides</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<!-- Sticky save bar -->
<div class="sync-bar" id="syncBar">
    <div class="sync-status" id="syncStatus">Unsaved changes</div>
    <div class="sync-actions">
        <button type="button" id="discardBtn">Discard</button>
        <button type="button" id="saveBtn">Save Changes</button>
    </div>
</div>

<script>
    // ── Tab switching ────────────────────────────────────────────────
    const settingsTabs = document.querySelectorAll('.settings-tab-btn');
    settingsTabs.forEach(btn => btn.addEventListener('click', () => showTab(btn.dataset.tab, btn)));

    function showTab(tabId, btn) {
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
        const sec = document.getElementById('tab-' + tabId);
        if (sec) sec.classList.add('active');
        const target = btn || document.querySelector('.settings-tab-btn[data-tab="' + tabId + '"]');
        if (target) target.classList.add('active');
        try { history.replaceState(null, '', '#' + tabId); } catch (e) {}
    }

    // Restore tab from URL hash on load
    document.addEventListener('DOMContentLoaded', () => {
        const hash = (location.hash || '').replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) showTab(hash);
    });

    // ── Category grid checkboxes ─────────────────────────────────────
    function updateCategoryGrid() {
        const hidden = document.getElementById('set-home_mobile_category_grid');
        const checked = document.querySelectorAll('#category-grid-checkboxes input[type="checkbox"]:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        hidden.value = ids.join(',');
        const changed = hidden.value !== (originalValues.get(hidden.id) ?? '');
        hidden.classList.toggle('is-dirty', changed);
        if (changed) markDirty();
    }

    function restoreCategoryCheckboxes() {
        const ids = (originalValues.get('set-home_mobile_category_grid') ?? '').split(',').map(s => s.trim()).filter(Boolean);
        document.querySelectorAll('#category-grid-checkboxes input[type="checkbox"]').forEach(cb => {
            cb.checked = ids.includes(cb.value);
        });
    }

    // ── Dirty state + save bar ───────────────────────────────────────
    let isDirty = false;
    const syncBar = document.getElementById('syncBar');
    const syncStatus = document.getElementById('syncStatus');
    const navStatusDot = document.getElementById('navStatusDot');
    const navStatusLabel = document.getElementById('navStatusLabel');

    function setDirtyUI() {
        syncBar.classList.toggle('visible', isDirty);
        navStatusDot.style.background = isDirty ? '#FF9500' : '#00A854';
        navStatusLabel.textContent = isDirty ? 'Unsaved changes' : 'All changes saved';
        settingsTabs.forEach(b => {
            const sec = document.getElementById('tab-' + b.dataset.tab);
            if (!sec) return;
            b.classList.toggle('has-unsaved', sec.querySelectorAll('.field-input.is-dirty').length > 0);
        });
    }
    function markDirty() {
        isDirty = true;
        syncStatus.textContent = 'Unsaved changes';
        setDirtyUI();
    }

    // Track which fields the user actually changed (highlight + tab dots)
    const originalValues = new Map();
    document.querySelectorAll('.field-input').forEach(el => originalValues.set(el.id, el.value));
    document.querySelectorAll('.field-input').forEach(el => {
        el.addEventListener('input', () => {
            const changed = el.value !== (originalValues.get(el.id) ?? '');
            el.classList.toggle('is-dirty', changed);
            markDirty();
        });
        el.addEventListener('change', () => {
            const changed = el.value !== (originalValues.get(el.id) ?? '');
            el.classList.toggle('is-dirty', changed);
            markDirty();
        });
    });

    // Verification toggle painting + dirty marking
    (function(){
        const cb = document.getElementById('set-seller_verification_required');
        const stateLabel = document.getElementById('verif-state-label');
        const card = cb.closest('.setting-card');
        const track = card.querySelector('.verif-slider');
        const knob = card.querySelector('.verif-knob');
        function paint(){
            track.style.background = cb.checked ? 'var(--red)' : '#8a8a8a';
            knob.style.transform = cb.checked ? 'translateX(26px)' : 'translateX(0)';
            stateLabel.textContent = cb.checked
                ? 'ON — new sellers must pass Ghana Card + Face ID'
                : 'OFF — anyone can start selling instantly';
            cb.value = cb.checked ? '1' : '0';
            const changed = cb.value !== (originalValues.get(cb.id) ?? '');
            cb.classList.toggle('is-dirty', changed);
            if (changed) markDirty();
        }
        cb.addEventListener('change', paint);
    })();

    // Color picker sync
    (function(){
        const colorInput = document.getElementById('set-primary_brand_color');
        const colorPicker = document.getElementById('colorPicker');
        if (colorPicker) colorPicker.addEventListener('input', (e) => { colorInput.value = e.target.value.toUpperCase(); colorInput.dispatchEvent(new Event('input')); });
        if (colorInput) colorInput.addEventListener('input', (e) => { colorPicker.value = e.target.value; });
        document.querySelectorAll('.swatch').forEach(s => {
            s.addEventListener('click', () => {
                if (colorInput) { colorInput.value = s.dataset.color; colorInput.dispatchEvent(new Event('input')); }
                if (colorPicker) colorPicker.value = s.dataset.color;
            });
        });
    })();

    // ── Save (only changed fields) + Discard ─────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const saveBtn = document.getElementById('saveBtn');
        const navSaveBtn = document.getElementById('navSaveBtn');
        const discardBtn = document.getElementById('discardBtn');

        async function saveChanges(btn) {
            const originalText = btn.innerText;
            btn.innerText = 'SAVING...';
            btn.disabled = true;

            const data = {};
            document.querySelectorAll('.field-input.is-dirty').forEach(el => {
                if (!el.id || !el.id.startsWith('set-')) return;
                data[el.id.replace('set-', '')] = el.value;
            });

            if (Object.keys(data).length === 0) {
                btn.innerText = 'NOTHING TO SAVE';
                setTimeout(() => { btn.innerText = originalText; btn.disabled = false; }, 1500);
                return;
            }

            try {
                const res = await fetch('api/save-settings.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    // Persist new baselines (secret key stays write-only: clear it)
                    Object.keys(data).forEach(k => {
                        const el = document.getElementById('set-' + k);
                        if (!el) return;
                        if (k === 'paystack_secret_key' || k === 'ga4_service_json') { el.value = ''; }
                        originalValues.set(el.id, el.value);
                        el.classList.remove('is-dirty');
                    });
                    isDirty = false;
                    syncStatus.textContent = 'All changes saved';
                    setDirtyUI();
                    btn.innerText = 'SAVED ✓';
                    btn.classList.add('save-ok');
                    setTimeout(() => { btn.innerText = originalText; btn.classList.remove('save-ok'); btn.disabled = false; }, 1800);
                } else {
                    throw new Error(result.message || 'Save rejected');
                }
            } catch (err) {
                btn.innerText = 'FAILED — RETRY?';
                btn.classList.add('save-err');
                syncStatus.textContent = 'Save failed: ' + (err.message || 'connection error');
                setTimeout(() => { btn.innerText = originalText; btn.classList.remove('save-err'); btn.disabled = false; }, 2500);
            }
        }

        if (saveBtn) saveBtn.addEventListener('click', () => saveChanges(saveBtn));
        if (navSaveBtn) navSaveBtn.addEventListener('click', () => saveChanges(navSaveBtn));

        if (discardBtn) discardBtn.addEventListener('click', () => {
            document.querySelectorAll('.field-input.is-dirty').forEach(el => {
                el.value = originalValues.get(el.id) ?? '';
                el.classList.remove('is-dirty');
                if (el.id === 'set-seller_verification_required') el.dispatchEvent(new Event('change'));
            });
            restoreCategoryCheckboxes();
            isDirty = false;
            syncStatus.textContent = 'Changes discarded';
            setDirtyUI();
            setTimeout(() => { syncStatus.textContent = 'All changes saved'; }, 1800);
        });

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) { e.preventDefault(); e.returnValue = ''; }
        });
    });
</script>

<?php include 'layout/footer.php'; ?>
