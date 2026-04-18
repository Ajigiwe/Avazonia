<?php
// admin/settings.php
require_once '../config/app.php';
require_once '../core/Session.php';
require_once '../models/Settings.php';

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

$title = "Persistence Configuration — Avazonia";
include 'layout/header.php';
?>

<style>
    .settings-layout { display: grid; grid-template-columns: 240px 1fr; gap: 48px; align-items: start; min-height: 70vh; }
    
    /* Settings Sidebar Nav */
    .settings-nav { 
        display: flex; flex-direction: column; gap: 8px; position: sticky; top: 120px;
        background: #000; padding: 24px 16px; border-radius: 16px; border: 1px solid #222;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .settings-tab-btn {
        background: transparent; border: none; padding: 14px 20px; text-align: left;
        font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; letter-spacing: 0.12em;
        color: #777; cursor: pointer; border-radius: 8px; transition: all 0.3s;
        display: flex; align-items: center; gap: 12px; font-weight: 700;
    }
    .settings-tab-btn:hover { background: #111; color: #fff; }
    .settings-tab-btn.active { 
        background: #fff; color: #000; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
    }
    .settings-tab-btn span { font-size: 14px; opacity: 0.8; }

    /* Content Area */
    .settings-content-area { max-width: 800px; }
    .settings-section { display: none; animation: fadeIn 0.4s ease; }
    .settings-section.active { display: block; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .section-header { margin-bottom: 32px; padding-bottom: 16px; border-bottom: 1px solid var(--light-gray); }
    .section-header h2 { font-family: var(--f-display); font-weight: 800; font-size: 24px; letter-spacing: -0.01em; margin: 0; }
    .section-header p { font-size: 13px; color: var(--mid-gray); margin-top: 4px; }

    /* Modern Inputs */
    .field-group { margin-bottom: 32px; }
    .field-label { display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 10px; letter-spacing: 0.05em; }
    .field-input { 
        width: 100%; padding: 14px 20px; border: 1px solid var(--light-gray); border-radius: 8px;
        font-size: 14px; transition: all 0.3s; background: #fff; box-sizing: border-box;
    }
    .field-input:focus { outline: none; border-color: var(--red); box-shadow: 0 0 0 4px rgba(229,0,26,0.05); }
    
    .field-sub { font-size: 11px; color: var(--mid-gray); margin-top: 8px; display: block; }

    /* Grid layout for fields */
    .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

    /* Sync Bar */
    .sync-bar {
        position: fixed; bottom: 32px; left: calc(var(--sidebar-w) + 240px + 80px); right: 40px;
        background: var(--ink); color: #fff; padding: 16px 32px; border-radius: 99px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2); z-index: 1000; transition: all 0.4s;
    }

    @media (max-width: 1200px) {
        .settings-layout { grid-template-columns: 1fr; }
        .settings-nav { position: relative; top: 0; flex-direction: row; overflow-x: auto; padding-bottom: 8px; }
        .settings-tab-btn { white-space: nowrap; }
        .sync-bar { left: 40px; }
    }

    @media (max-width: 900px) {
        .sync-bar { left: 16px; right: 16px; bottom: 16px; border-radius: 12px; flex-direction: column; gap: 16px; text-align: center; }
        .sync-bar button { width: 100%; }
    }

    .swatch { width: 32px; height: 32px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
    .swatch:hover { transform: scale(1.1); }
    .swatch.active { border-color: var(--ink); }
</style>

<div class="admin-header">
    <h1>Settings</h1>
    <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; letter-spacing: 0.1em;">Configuration • Sync Engine High</div>
</div>

<div class="settings-layout">
    <!-- NAVIGATION -->
    <aside class="settings-nav">
        <div style="flex: 1;">
            <button class="settings-tab-btn active" onclick="showTab('general')"><span>🏠</span> General</button>
            <button class="settings-tab-btn" onclick="showTab('hero')"><span>🖼️</span> Hero Banners</button>
            <button class="settings-tab-btn" onclick="showTab('storefront')"><span>🎨</span> Storefront</button>
            <button class="settings-tab-btn" onclick="showTab('payments')"><span>💳</span> Payments</button>
            <button class="settings-tab-btn" onclick="showTab('logistics')"><span>🚚</span> Logistics</button>
            <button class="settings-tab-btn" onclick="showTab('social')"><span>🌐</span> Social & SEO</button>
            <button class="settings-tab-btn" onclick="showTab('legal')"><span>📜</span> Policies</button>
        </div>

        <div style="margin-top: 40px; padding: 24px; background: #111; border-radius: 12px; border: 1px solid #222;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: #00A854; animation: pulse 2s infinite;"></div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #888; font-weight: 700;">Persistence Ready</div>
            </div>
            <button type="button" id="saveBtn" style="width: 100%; padding: 14px; background: #fff; color: #000; border: none; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase; cursor: pointer; letter-spacing: 0.05em; transition: all 0.2s;">Sync Changes</button>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="settings-content-area">
        
        <!-- 00: HERO BANNERS (DYNAMIC) -->
        <section id="tab-hero" class="settings-section">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h2>Hero Banners</h2>
                    <p>Manage the high-fidelity imagery and marketing slides across your site.</p>
                </div>
                <a href="add-slide.php" class="btn-red" style="height: 48px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none;">+ Add Slide</a>
            </div>

            <?php
            try {
                require_once __DIR__ . '/../models/Slider.php';
                $sliderModel = new Slider();
                $allSlides = $sliderModel->getAllRecords();
            } catch (Exception $e) {
                echo "<div style='color: var(--red); font-size: 11px; padding: 12px; border: 1px solid var(--red); opacity: 0.6;'>Intelligence Engine Failure: " . $e->getMessage() . "</div>";
                $allSlides = [];
            }
            ?>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($allSlides as $s): ?>
                    <div style="background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 24px;">
                        <img src="<?= APP_URL ?>/<?= $s['image_url'] ?>" style="width: 120px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid var(--light-gray);">
                        <div style="flex: 1;">
                            <div style="font-family: var(--f-semi); font-size: 14px; margin-bottom: 4px;"><?= $s['heading'] ?></div>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <span style="font-family: var(--f-mono); font-size: 10px; background: var(--off); padding: 2px 8px; border-radius: 4px; color: var(--mid-gray);">
                                    <?= $s['page_path'] === '*' ? 'GLOBAL' : $s['page_path'] ?>
                                </span>
                                <span class="status-badge <?= $s['is_active'] ? 'status-paid' : 'status-cancelled' ?>" style="font-size: 9px; padding: 2px 8px;">
                                    <?= $s['is_active'] ? 'Active' : 'Draft' ?>
                                </span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 12px;">
                            <a href="edit-slide.php?id=<?= $s['id'] ?>" class="btn-ink" style="height: 40px; padding: 0 16px; font-size: 9px; text-decoration: none; display: flex; align-items: center; justify-content: center;">Edit</a>
                            <a href="sliders.php?delete=<?= $s['id'] ?>" class="btn-red" style="height: 40px; padding: 0 16px; font-size: 9px; background: #fff; color: var(--red); border: 1px solid var(--red); text-decoration: none; display: flex; align-items: center; justify-content: center;" onclick="return confirm('Archive this slide?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($allSlides)): ?>
                    <div style="text-align: center; padding: 60px; background: var(--off); border-radius: 12px; border: 2px dashed var(--light-gray);">
                        <div style="font-size: 24px; margin-bottom: 12px;">🖼️</div>
                        <div style="font-family: var(--f-semi); font-size: 14px; color: var(--mid-gray);">No Hero Banners Found</div>
                        <p style="font-size: 12px; color: var(--mid-gray); margin-top: 8px;">Your platform will look quite empty without high-fidelity visuals.</p>
                        <a href="add-slide.php" class="nav-link" style="font-size: 11px; margin-top: 16px; display: inline-block;">+ Start Creating Slides</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 01: GENERAL -->
        <section id="tab-general" class="settings-section active">
            <div class="section-header">
                <h2>General Configuration</h2>
                <p>Manage your core store identity, branding, and contact details.</p>
            </div>
            
            <div class="field-group">
                <label class="field-label">Store Public Name</label>
                <input type="text" id="set-store_name" value="<?= getSet('store_name', 'Avazonia') ?>" class="field-input">
                <span class="field-sub">Displayed in browser titles, emails, and invoices.</span>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Support Email</label>
                    <input type="email" id="set-support_email" value="<?= getSet('support_email', 'hello@avazonia.com.gh') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label">WhatsApp Number</label>
                    <input type="text" id="set-whatsapp_number" value="<?= getSet('whatsapp_number', '233240000000') ?>" class="field-input">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Primary Brand Color</label>
                <div style="display: flex; gap: 16px; align-items: center;">
                    <input type="color" id="colorPicker" value="<?= getSet('primary_brand_color', '#E5001A') ?>" style="width: 44px; height: 44px; border: none; background: none; cursor: pointer;">
                    <input type="text" id="set-primary_brand_color" value="<?= getSet('primary_brand_color', '#E5001A') ?>" class="field-input" style="flex: 1; font-family: var(--f-mono);">
                </div>
                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <?php foreach (['#E5001A', '#007AFF', '#00A854', '#FF9500', '#5856D6', '#000000'] as $p): ?>
                        <div class="swatch" data-color="<?= $p ?>" style="background: <?= $p ?>;"></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Store Location (Google Maps)</label>
                <input type="text" id="set-store_map_address" value="<?= getSet('store_map_address') ?>" class="field-input" placeholder="Spintex Road, Accra...">
            </div>

            <!-- SUPPORT BANNER CONFIG -->
            <div style="background: var(--off); padding: 32px; border-radius: 12px; margin-top: 40px;">
                <h3 style="font-size: 13px; text-transform: uppercase; margin-bottom: 24px;">Support Banner Content</h3>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Title</label>
                        <input type="text" id="set-support_title" value="<?= getSet('support_title', 'Need Any Help?') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Subtitle</label>
                        <input type="text" id="set-support_subtitle" value="<?= getSet('support_subtitle', 'We are here to help you with any question.') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Support Phone</label>
                        <input type="text" id="set-support_phone" value="<?= getSet('support_phone', '+233 201500300') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Business Hours</label>
                        <input type="text" id="set-support_hours" value="<?= getSet('support_hours', 'Monday to Saturday - 9am - 6pm') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Footer Address</label>
                        <input type="text" id="set-footer_address" value="<?= getSet('footer_address', 'Q4 Gibbefish Street Beach Road Takoradi, Ghana') ?>" class="field-input">
                    </div>
                </div>
            </div>
        </section>

        <!-- 02: STOREFRONT -->
        <section id="tab-storefront" class="settings-section">
            <div class="section-header">
                <h2>Storefront Experience</h2>
                <p>Customize the visual layout and promotional elements of your shop.</p>
            </div>

            <div class="field-group">
                <label class="field-label">Global Announcement Bar</label>
                <input type="text" id="set-announcement_text" value="<?= getSet('announcement_text') ?>" class="field-input" placeholder="FREE SHIPPING ON ALL S25 ORDERS!">
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Product Grid Density</label>
                    <select id="set-grid_density" class="field-input">
                        <option value="4" <?= getSet('grid_density') == '4' ? 'selected' : '' ?>>Roomy (4 Columns)</option>
                        <option value="6" <?= getSet('grid_density') == '6' ? 'selected' : '' ?>>Standard (6 Columns)</option>
                        <option value="8" <?= getSet('grid_density') == '8' ? 'selected' : '' ?>>Dense (8 Columns)</option>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Hide Low-Stock Threshold</label>
                    <input type="number" id="set-min_stock_threshold" value="<?= getSet('min_stock_threshold', '1') ?>" class="field-input">
                </div>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Footer Copyright Notice</label>
                    <input type="text" id="set-footer_notice" value="<?= getSet('footer_notice', '© 2026 AVAZONIA GH') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label">Product Card Auto Slider</label>
                    <select id="set-product_card_slider_enabled" class="field-input">
                        <option value="1" <?= getSet('product_card_slider_enabled', '1') == '1' ? 'selected' : '' ?>>Enabled</option>
                        <option value="0" <?= getSet('product_card_slider_enabled') == '0' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </div>
            </div>

            <!-- POPUP MINI SECTION -->
            <div style="background: var(--off); padding: 32px; border-radius: 12px; margin-top: 24px;">
                <h3 style="font-size: 13px; text-transform: uppercase; margin-bottom: 24px;">Marketing Popup</h3>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Enabled</label>
                        <select id="set-home_popup_enabled" class="field-input">
                            <option value="1" <?= getSet('home_popup_enabled') == '1' ? 'selected' : '' ?>>YES</option>
                            <option value="0" <?= getSet('home_popup_enabled') == '0' ? 'selected' : '' ?>>NO</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Popup Type</label>
                        <select id="set-home_popup_type" class="field-input">
                            <option value="promo" <?= getSet('home_popup_type') == 'promo' ? 'selected' : '' ?>>Promotional Image</option>
                            <option value="newsletter" <?= getSet('home_popup_type') == 'newsletter' ? 'selected' : '' ?>>Newsletter Subscription</option>
                            <option value="discount" <?= getSet('home_popup_type') == 'discount' ? 'selected' : '' ?>>Direct Discount Code</option>
                        </select>
                    </div>
                </div>
                <div class="field-grid">
                    <div class="field-group">
                        <label class="field-label">Frequency (Visits)</label>
                        <input type="number" id="set-home_popup_frequency" value="<?= getSet('home_popup_frequency', '3') ?>" class="field-input">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Redirect Link (Promo Only)</label>
                        <input type="text" id="set-home_popup_link" value="<?= getSet('home_popup_link') ?>" class="field-input">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label">Main Headline</label>
                    <input type="text" id="set-home_popup_title" value="<?= getSet('home_popup_title') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label">Description / Body Text</label>
                    <textarea id="set-home_popup_desc" class="field-input" style="height: 100px;"><?= getSet('home_popup_desc') ?></textarea>
                </div>
                <div class="field-group">
                    <label class="field-label">Popup Media / Image URL</label>
                    <input type="text" id="set-home_popup_image" value="<?= getSet('home_popup_image') ?>" class="field-input">
                </div>
            </div>
        </section>

        <!-- 03: PAYMENTS -->
        <section id="tab-payments" class="settings-section">
            <div class="section-header">
                <h2>Payment Integration</h2>
                <p>Configure Paystack gateway API keys and financial settings.</p>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Public Key (Test/Live)</label>
                    <input type="text" id="set-paystack_public_key" value="<?= getSet('paystack_public_key') ?>" class="field-input" style="font-family: var(--f-mono);">
                </div>
                <div class="field-group">
                    <label class="field-label">Secret Key</label>
                    <input type="password" id="set-paystack_secret_key" value="<?= getSet('paystack_secret_key') ?>" class="field-input">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Store Currency Symbol</label>
                <input type="text" id="set-currency_symbol" value="<?= getSet('currency_symbol', '₵') ?>" class="field-input" style="font-family: var(--f-semi);">
            </div>
        </section>
        
        <!-- 04: SOCIAL & SEO -->
        <section id="tab-social" class="settings-section">
            <div class="section-header">
                <h2>Social Presence & SEO</h2>
                <p>Manage your social media links and search engine metadata.</p>
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Instagram Profile Link</label>
                    <input type="text" id="set-instagram_link" value="<?= getSet('instagram_link') ?>" class="field-input" placeholder="https://instagram.com/...">
                </div>
                <div class="field-group">
                    <label class="field-label">Facebook Page Link</label>
                    <input type="text" id="set-facebook_link" value="<?= getSet('facebook_link') ?>" class="field-input" placeholder="https://facebook.com/...">
                </div>
                <div class="field-group">
                    <label class="field-label">Youtube Channel</label>
                    <input type="text" id="set-youtube_link" value="<?= getSet('youtube_link') ?>" class="field-input" placeholder="https://youtube.com/...">
                </div>
                <div class="field-group">
                    <label class="field-label">TikTok Profile Link</label>
                    <input type="text" id="set-tiktok_link" value="<?= getSet('tiktok_link') ?>" class="field-input" placeholder="https://tiktok.com/@...">
                </div>
                <div class="field-group">
                    <label class="field-label">Telegram Channel / User</label>
                    <input type="text" id="set-telegram_link" value="<?= getSet('telegram_link') ?>" class="field-input" placeholder="https://t.me/...">
                </div>
                <div class="field-group">
                    <label class="field-label">WhatsApp Direct Link (Optional)</label>
                    <input type="text" id="set-whatsapp_link" value="<?= getSet('whatsapp_link') ?>" class="field-input" placeholder="https://wa.me/...">
                </div>
                <div class="field-group">
                    <label class="field-label">Twitter / X Profile</label>
                    <input type="text" id="set-twitter_link" value="<?= getSet('twitter_link') ?>" class="field-input">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Default Meta Description (SEO)</label>
                <textarea id="set-meta_description" class="field-input" style="height: 100px;"><?= getSet('meta_description') ?></textarea>
                <span class="field-sub">Summarize your shop for search engine results. Used when no product-specific metadata exists.</span>
            </div>
            <div class="field-group">
                <label class="field-label">Default SEO Keywords</label>
                <input type="text" id="set-meta_keywords" value="<?= getSet('meta_keywords') ?>" class="field-input" placeholder="gadgets, phones, accra, ghana...">
            </div>
        </section>

        <!-- 03: LOGISTICS -->
        <section id="tab-logistics" class="settings-section">
            <div class="section-header">
                <h2>Logistics & Finance</h2>
                <p>Configure shipping rates, deposit requirements, and free delivery thresholds.</p>
            </div>

            <div class="field-group">
                <label class="field-label">Pre-Order Deposit Requirement (%)</label>
                <input type="number" id="set-preorder_deposit_pct" value="<?= getSet('preorder_deposit_pct', '5') ?>" class="field-input">
            </div>

            <div class="field-grid">
                <div class="field-group">
                    <label class="field-label">Greater Accra Rate (GHS)</label>
                    <input type="number" id="set-shipping_accra" value="<?= getSet('shipping_accra', '30.00') ?>" class="field-input">
                </div>
                <div class="field-group">
                    <label class="field-label">Other Regions Rate (GHS)</label>
                    <input type="number" id="set-shipping_others" value="<?= getSet('shipping_others', '50.00') ?>" class="field-input">
                </div>
            </div>

            <div class="field-group">
                <label class="field-label">Free Shipping Threshold</label>
                <input type="number" id="set-shipping_free_threshold" value="<?= getSet('shipping_free_threshold', '200') ?>" class="field-input">
            </div>
        </section>

        <!-- 04: LEGAL -->
        <section id="tab-legal" class="settings-section">
            <div class="section-header">
                <h2>Legal & Policies</h2>
                <p>Public-facing policy text for returns, shipping, and user terms.</p>
            </div>

            <div class="field-group">
                <label class="field-label">Returns, Refunds & Exchange Policy</label>
                <textarea id="set-returns_policy" class="field-input" style="height: 250px;"><?= getSet('returns_policy') ?></textarea>
            </div>

            <div class="field-group">
                <label class="field-label">Shipping & Delivery Policy</label>
                <textarea id="set-shipping_policy" class="field-input" style="height: 250px;"><?= getSet('shipping_policy') ?></textarea>
            </div>
        </section>
    </main>
</div>
<script>
    function showTab(tabId) {
        document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.settings-tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById('tab-' + tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const saveBtn = document.getElementById('saveBtn');
        const colorInput = document.getElementById('set-primary_brand_color');
        const colorPicker = document.getElementById('colorPicker');

        if (colorPicker) colorPicker.addEventListener('input', (e) => colorInput.value = e.target.value.toUpperCase());
        if (colorInput) colorInput.addEventListener('input', (e) => colorPicker.value = e.target.value);
        
        document.querySelectorAll('.swatch').forEach(s => {
            s.addEventListener('click', () => {
                const c = s.dataset.color;
                if (colorInput) colorInput.value = c;
                if (colorPicker) colorPicker.value = c;
            });
        });

        if (saveBtn) saveBtn.addEventListener('click', async () => {
            const originalText = saveBtn.innerText;
            saveBtn.innerText = 'SYNCING...';
            saveBtn.disabled = true;

            const data = {};
            document.querySelectorAll('input[id^="set-"], select[id^="set-"], textarea[id^="set-"]').forEach(el => {
                data[el.id.replace('set-', '')] = el.value;
            });

            try {
                const res = await fetch('api/save-settings.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    saveBtn.innerText = 'SUCCESSFUL';
                    saveBtn.style.background = '#00A854';
                    saveBtn.style.color = '#fff';
                    setTimeout(() => {
                        saveBtn.innerText = originalText;
                        saveBtn.style.background = '#fff';
                        saveBtn.style.color = '#000';
                        saveBtn.disabled = false;
                    }, 2000);
                }
            } catch (err) {
                alert('Connection failure');
                saveBtn.disabled = false;
            }
        });
    });
</script>

<?php include 'layout/footer.php'; ?>
