<?php require_once __DIR__.'/../layout/head.php'; require_once __DIR__.'/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<style>
.form-label { display: block; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; font-weight: 600; letter-spacing: 0.06em; }
.form-input { width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 13px; background: #fff; transition: border-color 0.2s; }
.form-input:focus { outline: none; border-color: var(--ink); }
.form-select { width: 100%; padding: 12px; border: 1px solid var(--light-gray); font-family: inherit; font-size: 13px; background: #fff; appearance: auto; }
.form-section { background: var(--off); padding: 24px; border: 1px solid var(--light-gray); }
.form-section-title { display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--ink); margin-bottom: 20px; font-weight: 700; letter-spacing: 0.06em; }
</style>

<div style="margin-bottom:32px;">
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">List a Product</h1>
        <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Free listing &middot; <?= htmlspecialchars($seller['seller_type']) ?> &middot; <?= htmlspecialchars($seller['verification_level']) ?></div>
    </div>

    <div style="border:2px solid var(--ink);max-width:860px;">
        <div style="padding:16px 24px;border-bottom:1px solid var(--light-gray);background:var(--ink);color:#fff;">
            <span style="font-family:var(--f-display);font-weight:800;font-size:14px;">Product Details</span>
        </div>
        <div style="padding:32px 40px;">

            <?php if(!empty($error)): ?>
                <div style="background:#fff1f0;color:#f5222d;padding:14px 16px;margin-bottom:24px;font-size:13px;border-left:4px solid #f5222d;font-family:var(--f-semi);">&#9888; <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:24px;">
                <?= Csrf::field() ?>

                <!-- Row: Name + Currency -->
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" required class="form-input" placeholder="e.g. Samsung Galaxy S24 Ultra">
                    </div>
                    <div>
                        <label class="form-label">Currency</label>
                        <select name="currency" id="currency-select" onchange="toggleCurrency()" class="form-select">
                            <option value="GHS">GHS (₵) — Ghana Cedis</option>
                        </select>
                    </div>
                </div>

                <!-- Row: Price + Compare Price -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Current Price (GHS ₵)</label>
                        <input type="number" step="0.01" name="price" required class="form-input" placeholder="e.g. 2500.00">
                    </div>
                    <div>
                        <label class="form-label" style="color:var(--red);">Old Price / Compare at (GHS ₵)</label>
                        <input type="number" step="0.01" name="compare_price" class="form-input" placeholder="e.g. 3000.00">
                    </div>
                </div>

                <!-- Row: Category + Brand -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Brand</label>
                        <select name="brand_id" id="brand-select" class="form-select" onchange="toggleCustomBrand()">
                            <option value="">Select Brand</option>
                            <?php if(!empty($brands)): foreach($brands as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; endif; ?>
                            <option value="_new">✍️ Other (type below)</option>
                        </select>
                        <input type="text" name="custom_brand_name" id="custom-brand-input" class="form-input" style="display:none;margin-top:8px;" placeholder="Type brand name...">
                    </div>
                </div>

                <!-- Row: Stock + Upload Images -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_qty" value="10" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Upload Images (Multiple)</label>
                        <input type="file" name="images[]" multiple accept="image/*" class="form-input" style="padding:9px;font-size:11px;">
                    </div>
                </div>

                <!-- Row: Video Upload + Video URL -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Upload Video (MP4/WEBM)</label>
                        <input type="file" name="product_video" accept="video/mp4,video/webm" class="form-input" style="padding:9px;font-size:11px;">
                    </div>
                    <div>
                        <label class="form-label">Or Video URL</label>
                        <input type="url" name="video_url" placeholder="https://..." class="form-input">
                    </div>
                </div>

                <!-- Row: Image URL -->
                <div>
                    <label class="form-label">Or Image URL (Optional)</label>
                    <input type="url" name="image_url" placeholder="https://..." class="form-input">
                </div>

                <!-- Description -->
                <div>
                    <label class="form-label">Description (Overview)</label>
                    <textarea name="description" rows="5" class="form-input" style="resize:vertical;"></textarea>
                </div>

                <!-- Row: Features + Specs -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                    <div>
                        <label class="form-label">Key Features (One per line)</label>
                        <textarea name="features" rows="6" class="form-input" style="resize:vertical;font-size:13px;" placeholder="Fast Charging&#10;Waterproof&#10;2-Year Warranty"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Technical Specs (Key: Value)</label>
                        <textarea name="specs" rows="6" class="form-input" style="resize:vertical;font-size:13px;" placeholder="Weight: 200g&#10;Battery: 5000mAh&#10;Material: Silicone"></textarea>
                    </div>
                </div>

                <!-- Tags -->
                <div>
                    <label class="form-label">Tags (comma-separated)</label>
                    <input type="text" name="tags" class="form-input" placeholder="e.g. Premium, New Arrival, Limited">
                </div>

                <!-- Marketplace Section -->
                <div class="form-section">
                    <span class="form-section-title">🏪 Marketplace — Listing &amp; Visibility</span>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label class="form-label">Listing Type</label>
                            <select name="listing_type" id="listing_type" onchange="toggleMarketplace()" class="form-select">
                                <option value="retail">Retail — single price</option>
                                <option value="wholesale">Wholesale — MOQ / bulk</option>
                                <option value="rfq">Request for Quote — price on enquiry</option>
                                <option value="export">International Export — FOB/CIF</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Condition</label>
                            <select name="condition_type" class="form-select">
                                <option value="new">New</option>
                                <option value="used">Used (C2C)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Wholesale fields -->
                    <div id="wholesale-fields" style="display:none;margin-top:20px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                            <div>
                                <label class="form-label">MOQ (Min Order Qty)</label>
                                <input type="number" name="moq" placeholder="e.g. 20" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Wholesale Price (GHS)</label>
                                <input type="number" step="0.01" name="wholesale_price_ghs" placeholder="e.g. 120.00" class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Export fields -->
                    <div id="export-fields" style="display:none;margin-top:20px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                            <div>
                                <label class="form-label">FOB Price (USD)</label>
                                <input type="number" step="0.01" name="fob_price_usd" placeholder="e.g. 1500" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Incoterms</label>
                                <select name="incoterms" class="form-select">
                                    <option value="">—</option>
                                    <option value="EXW">EXW</option>
                                    <option value="FOB">FOB</option>
                                    <option value="CIF">CIF</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Production Capacity</label>
                                <input type="text" name="production_capacity" placeholder="e.g. 500 units/month" class="form-input">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <div>
                            <label class="form-label">Visibility</label>
                            <select name="visibility" class="form-select">
                                <option value="public">Public — everyone</option>
                                <option value="b2b_only">B2B Only — business buyers</option>
                                <option value="retail_only">Retail Only — consumers</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">OEM/ODM</label>
                            <label style="display:flex;align-items:center;gap:8px;padding:12px;font-size:13px;cursor:pointer;">
                                <input type="checkbox" name="oem_odm" value="1"> OEM/ODM Available
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" style="height:52px;background:var(--red);color:#fff;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.1em;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;">Submit for Review</button>
                <p style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-align:center;">Products are pending review before appearing public (free listing at launch).</p>
            </form>
        </div>
    </div>

<?php include __DIR__ . '/sidebar_footer.php'; ?>

<script>
function toggleCurrency() {
    var sel = document.getElementById('currency-select').value;
    document.getElementById('price-ghs-fields').style.display = sel === 'GHS' ? '' : 'none';
    document.getElementById('price-usd-fields').style.display = sel === 'USD' ? '' : 'none';
}
function toggleMarketplace() {
    var v = document.getElementById('listing_type').value;
    document.getElementById('wholesale-fields').style.display = (v === 'wholesale' || v === 'export') ? '' : 'none';
    document.getElementById('export-fields').style.display = (v === 'export') ? '' : 'none';
}
function toggleCustomBrand() {
    var sel = document.getElementById('brand-select').value;
    var inp = document.getElementById('custom-brand-input');
    inp.style.display = sel === '_new' ? 'block' : 'none';
    inp.required = (sel === '_new');
}
</script>

<?php require_once __DIR__.'/../layout/footer.php'; ?>
