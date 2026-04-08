<?php
// views/checkout/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$cart = Session::get('cart', []);
$totalItems = array_sum(array_column($cart, 'qty'));
$user_name = Session::get('user_name', '');
$user_email = Session::get('user_email', '');

// Access variables from controller
// $subtotal, $shipping, $total, $pay_now, $has_preorder, $deposit_pct are passed from CheckoutController@index
?>

<style>
body { background: #f8f8f8; color: #111; font-family: var(--f-body); }
.co-page { padding-top: 68px; min-height: 100svh; }

/* PROGRESS */
.pb-bar { background: #fff; border-bottom: 1px solid #eee; height: 48px; position: sticky; top: 68px; z-index: 90; }
.pb-inner { display: flex; align-items: stretch; height: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.pb-step { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: var(--f-mono); font-size: 10px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #ccc; border-bottom: 2px solid transparent; }
.pb-step.on { color: #111; border-bottom-color: var(--red); }
.pb-step.done { color: #16a34a; }
.pb-n { width: 22px; height: 22px; border: 1.5px solid currentColor; display: flex; align-items: center; justify-content: center; border-radius: 0; font-size: 10px; }
.pb-step.on .pb-n { background: var(--red); border-color: var(--red); color: #fff; }

/* LAYOUT */
.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.co-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 32px; padding: 40px 0 80px; align-items: start; }

/* CARDS */
.co-card { background: #fff; border: 1px solid #eee; border-radius: 4px; margin-bottom: 24px; overflow: hidden; }
.co-card-head { padding: 18px 24px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 12px; }
.co-card-n { width: 24px; height: 24px; background: var(--red); display: flex; align-items: center; justify-content: center; color: #fff; font-family: var(--f-mono); font-size: 11px; font-weight: 700; border-radius: 2px; }
.co-card-t { font-family: var(--f-display); font-weight: 700; font-size: 18px; letter-spacing: -.01em; }
.co-card-body { padding: 32px; }

/* FORMS */
.co-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.co-row.full { grid-template-columns: 1fr; }
.fg { display: flex; flex-direction: column; gap: 8px; }
.fl { font-family: var(--f-mono); font-size: 8px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #999; }
.fi { width: 100%; height: 48px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 0 16px; font-family: var(--f-body); font-size: 14px; outline: none; transition: border .2s; }
.fi:focus { border-color: #111; background: #fff; }
.fs { width: 100%; height: 48px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 0 16px; font-family: var(--f-body); font-size: 14px; outline: none; appearance: none; cursor: pointer; }

/* PHONE COMPONENT */
.phone-box { display: flex; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px; overflow: hidden; }
.phone-pfx { padding: 0 16px; background: #f0f0f0; border-right: 1px solid #eee; display: flex; align-items: center; font-family: var(--f-mono); font-size: 10px; font-weight: 600; color: #999; white-space: nowrap; }
.phone-box .fi { border: none; background: none; flex: 1; border-radius: 0; }

/* PAYMENT GRID */
.pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px; }
.pay-item { padding: 24px 12px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; text-align: center; transition: all .2s; background: #fff; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.pay-item:hover { border-color: #999; }
.pay-item.on { border: 1.5px solid var(--red); background: rgba(229,0,26,.02); }
.pay-icon { font-size: 20px; filter: grayscale(1); opacity: .5; }
.pay-item.on .pay-icon { filter: none; opacity: 1; }
.pay-lbl { font-family: var(--f-mono); font-size: 8px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #999; }
.pay-item.on .pay-lbl { color: var(--red); }

/* MOMO BOX */
.momo-box { background: #fcfcfc; border: 1px solid #eee; border-radius: 4px; padding: 32px; margin-bottom: 24px; }
.prov-row { display: flex; background: #fff; padding: 4px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 24px; }
.prov-btn { flex: 1; height: 38px; border: none; background: none; font-family: var(--f-mono); font-size: 10px; font-weight: 700; border-radius: 3px; cursor: pointer; color: #aaa; text-transform: uppercase; letter-spacing: .05em; transition: all .1s; }
.prov-btn.on { background: #111; color: #fff; }

/* SIDEBAR SUMMARY */
.co-side { position: sticky; top: 140px; }
.co-sum { background: #fff; border: 1px solid #eee; border-radius: 4px; padding: 28px; }
.sum-t { font-family: var(--f-display); font-weight: 700; font-size: 20px; color: #111; margin-bottom: 12px; }
.sum-line-h { width: 100%; height: 2px; background: #111; margin-bottom: 24px; }
.itm-row { display: flex; gap: 16px; margin-bottom: 16px; align-items: flex-start; }
.itm-img { width: 44px; height: 44px; border: 1px solid #eee; border-radius: 4px; overflow: hidden; flex-shrink: 0; }
.itm-img img { width: 100%; height: 100%; object-fit: contain; padding: 4px; }
.itm-n { font-family: var(--f-display); font-weight: 700; font-size: 14px; line-height: 1; color: #111; }
.itm-m { font-family: var(--f-mono); font-size: 9px; color: #aaa; margin-top: 4px; text-transform: uppercase; }
.itm-p { font-family: var(--f-display); font-weight: 700; font-size: 15px; margin-left: auto; }

.co-line { display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; }
.co-lbl { color: #999; }
.co-val { font-weight: 700; color: #111; }

.co-total-row { border-top: 2px solid #111; margin-top: 12px; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
.co-total-l { font-family: var(--f-display); font-weight: 800; font-size: 16px; text-transform: uppercase; }
.co-total-v { font-family: var(--f-display); font-weight: 900; font-size: 38px; letter-spacing: -.03em; }

.co-pay-btn { width: 100%; height: 56px; background: var(--red); border: none; color: #fff; font-family: var(--f-display); font-size: 13px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 24px; border-radius: 4px; }
.co-pay-btn:hover { background: #c70016; }
.co-sec-note { text-align: center; color: #ccc; font-family: var(--f-mono); font-size: 8px; letter-spacing: .1em; margin-top: 16px; text-transform: uppercase; }

@media (max-width: 900px) {
    .co-layout { grid-template-columns: 1fr; }
    .co-side { position: static; }
}
@media (max-width: 640px) {
    .pb-step-name { display: none; }
    .co-row { grid-template-columns: 1fr; }
}
</style>

<div class="co-page" id="coView">
    <div class="pb-bar">
        <div class="pb-inner">
            <div class="pb-step done"><div class="pb-n">✓</div> <span class="pb-step-name">Cart</span></div>
            <div class="pb-step on"><div class="pb-n">2</div> <span class="pb-step-name">Checkout</span></div>
            <div class="pb-step"><div class="pb-n">3</div> <span class="pb-step-name">Confirmation</span></div>
        </div>
    </div>

    <div class="container">
        <!-- STACKED FORM ERROR BANNER -->
        <div id="co-error-banner" style="display:none; background:#fbe9e7; border-top:4px solid #d32f2f; padding:20px 24px; margin-bottom:32px; font-family:var(--f-body); font-size:15px; align-items:center; gap:16px;">
            <div style="width:24px; height:24px; background:#d32f2f; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:900; flex-shrink:0;">!</div>
            <div style="color:#d32f2f; font-weight:600;">You must complete all required fields</div>
        </div>

        <style>
        .fg { position: relative; }
        .err-icon { position: absolute; right: 12px; top: 40px; width: 20px; height: 20px; background: #d32f2f; border-radius: 50%; display: none; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 800; pointer-events: none; }
        .err-txt { color: #d32f2f; font-size: 11px; margin-top: 4px; display: none; font-family: var(--f-body); }
        .fi.err { border-bottom: 2px solid #d32f2f !important; }
        </style>

        <div class="co-layout">
            <!-- MAIN CONTENT -->
            <div>
                <!-- STEP 1: CONTACT -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">1</div>
                        <div class="co-card-t">Contact Info</div>
                    </div>
                    <div class="co-card-body">
                        <div class="co-row">
                            <div class="fg">
                                <label class="fl">Full Name</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-name" placeholder="Kwame Mensah" value="<?= $user_name ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                            <div class="fg">
                                <label class="fl">Email Address</label>
                                <div style="position:relative;">
                                    <input class="fi" type="email" id="co-email" placeholder="kwame@example.com" value="<?= $user_email ?>">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Phone Number</label>
                                <div style="position:relative;">
                                    <div class="phone-box">
                                        <div class="phone-pfx">GH +233</div>
                                        <input class="fi" type="tel" id="co-phone" placeholder="24 000 0000">
                                    </div>
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: DELIVERY -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">2</div>
                        <div class="co-card-t">Delivery Details</div>
                    </div>
                    <div class="co-card-body">
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Delivery Zone</label>
                                <div style="position:relative;">
                                    <select class="fs" id="co-zone" onchange="updateShip(this)">
                                        <option value="<?= defined('SHIPPING_ACCRA') ? SHIPPING_ACCRA : 15 ?>" data-id="1">📍 Accra & Greater Accra — ₵<?= defined('SHIPPING_ACCRA') ? SHIPPING_ACCRA : 15 ?> (1–2 days)</option>
                                        <option value="<?= defined('SHIPPING_KUMASI') ? SHIPPING_KUMASI : 25 ?>" data-id="2">📍 Kumasi / Takoradi — ₵<?= defined('SHIPPING_KUMASI') ? SHIPPING_KUMASI : 25 ?> (2–3 days)</option>
                                        <option value="<?= defined('SHIPPING_OTHERS') ? SHIPPING_OTHERS : 60 ?>" data-id="3">📍 All Other Regions — ₵<?= defined('SHIPPING_OTHERS') ? SHIPPING_OTHERS : 60 ?> (3–5 days)</option>
                                        <option value="0" data-id="4">🏪 Store Pickup — <?= defined('SHIPPING_PICKUP') ? SHIPPING_PICKUP : 'Free' ?></option>
                                    </select>
                                    <div class="err-icon" style="top:50%; transform:translateY(-50%); right:32px;">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row full">
                            <div class="fg">
                                <label class="fl">Street Address / Digital Address</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-address" placeholder="No. 24 Liberation Road / GA-182-1234">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                        <div class="co-row">
                            <div class="fg">
                                <label class="fl">City</label>
                                <div style="position:relative;">
                                    <input class="fi" type="text" id="co-city" placeholder="Accra">
                                    <div class="err-icon">!</div>
                                </div>
                                <div class="err-txt">This is a required field</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: PAYMENT -->
                <div class="co-card">
                    <div class="co-card-head">
                        <div class="co-card-n">3</div>
                        <div class="co-card-t">Payment Method</div>
                    </div>
                    <div class="co-card-body">
                        <!-- Payment Selection Grid -->
                        <div class="pay-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                            <div class="pay-item on" onclick="selectPayMethod('paystack')" id="pay-paystack">
                                <span style="font-size: 24px; margin-bottom: 8px; display: block;">💳</span>
                                <div class="pay-lbl" style="font-size: 11px; font-weight: 800;">Online / MoMo</div>
                                <div style="font-size: 9px; color: #999; margin-top: 4px;">Fastest Processing</div>
                            </div>
                            
                            <div class="pay-item <?= $has_preorder ? 'disabled' : '' ?>" 
                                 onclick="<?= $has_preorder ? 'return false;' : "selectPayMethod('pod')" ?>" 
                                 id="pay-pod" 
                                 style="<?= $has_preorder ? 'opacity: 0.5; cursor: not-allowed; border-color: #eee; background: #fafafa;' : '' ?>">
                                <span style="font-size: 24px; margin-bottom: 8px; display: block;">🚚</span>
                                <div class="pay-lbl" style="font-size: 11px; font-weight: 800;">Pay on Delivery</div>
                                <div style="font-size: 9px; color: #999; margin-top: 4px;"><?= $has_preorder ? 'Unavailable for Pre-orders' : 'Cash or MoMo at doorstep' ?></div>
                            </div>
                        </div>

                        <input type="hidden" id="co-payment-method" value="paystack">

                        <div id="paystack-info" class="paystack-note" style="background: #f9f9f9; border: 1px solid #eee; padding: 24px; border-radius: 4px; display: flex; gap: 16px; align-items: flex-start;">
                            <span style="font-size:24px;">🛡️</span>
                            <div style="font-size: 13px; line-height: 1.5; color: #666;">
                                <strong style="color: #111; display: block; margin-bottom: 4px;">Secure Online Checkout</strong>
                                You will be redirected to **Paystack** to complete your purchase securely.
                            </div>
                        </div>

                        <div id="pod-info" style="display: none; background: #fff8e1; border: 1px solid #ffe082; padding: 24px; border-radius: 4px; display: none; gap: 16px; align-items: flex-start;">
                            <span style="font-size:24px;">📦</span>
                            <div style="font-size: 13px; line-height: 1.5; color: #856404;">
                                <strong style="color: #111; display: block; margin-bottom: 4px;">Payment at Doorstep</strong>
                                You will pay the total amount to the delivery agent upon receipt of your package.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR SUMMARY -->
            <div class="co-side">
                <div class="co-sum">
                    <div class="sum-t">Your Order</div>
                    <div class="sum-line-h"></div>
                    
                    <div class="co-items" style="margin-bottom:24px; border-bottom:1px solid #f0f0f0; padding-bottom:24px;">
                        <?php foreach ($cart as $item): ?>
                            <div class="itm-row">
                                <div class="itm-img"><img src="<?= $item['image'] ?: 'https://via.placeholder.com/400x400' ?>" alt=""></div>
                                <div style="flex:1;">
                                    <div class="itm-n"><?= $item['name'] ?></div>
                                    <div class="itm-m">
                                        QTY: <?= $item['qty'] ?> · <?= strtoupper($item['variant_name'] ?? 'Universal') ?>
                                        <?php if($item['is_preorder'] ?? 0): ?>
                                            <span style="color:var(--red); display:block; margin-top:4px;">[ PRE-ORDER ITEM ]</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="itm-p">₵<?= number_format($item['price_ghs'] * $item['qty'], 0) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="co-line"><span class="co-lbl">Subtotal</span><span class="co-val">₵<?= number_format($subtotal, 2) ?></span></div>
                    <div class="co-line"><span class="co-lbl">Delivery</span><span class="co-val" id="disp-ship">₵<?= number_format($shipping, 2) ?></span></div>

                    <div class="co-total-row">
                        <div class="co-total-l">Order Total</div>
                        <div class="co-total-v" id="disp-main-total">₵<?= number_format($total, 0) ?></div>
                    </div>

                    <div style="margin-top:24px; padding:20px; background:var(--off); border-radius:4px; border:1px solid var(--light-gray);">
                        <div style="font-family:var(--f-mono); font-size:9px; color:var(--mid-gray); text-transform:uppercase; margin-bottom:8px;">Due Now (Deposit + Shipping)</div>
                        <div style="font-family:var(--f-display); font-size:32px; font-weight:900; color:var(--red);" id="disp-total">₵<?= number_format($pay_now, 0) ?></div>
                        
                        <?php if($has_preorder): ?>
                            <div style="font-size:11px; color:var(--mid-gray); margin-top:8px;"> Includes <?= (int)$deposit_pct ?>% deposit for pre-order items.</div>
                        <?php endif; ?>
                    </div>

                    <button class="co-pay-btn" onclick="initPaystack(event)">
                        🛍️ PAY ₵<?= number_format($pay_now, 0) ?> NOW →
                    </button>

                    <div class="co-sec-note">🔒 SECURED BY PAYSTACK · PCI-DSS</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CONFIRMATION VIEW -->
<div class="conf-page" id="confView" style="display:none; padding:100px 24px; text-align:center; background:#fff;">
    <div style="width:80px; height:80px; background:rgba(22,163,74,.1); border:2px solid #16a34a; border-radius:100px; display:flex; align-items:center; justify-content:center; margin:0 auto 32px; font-size:32px; color:#16a34a;">✓</div>
    <h1 style="font-family:var(--f-display); font-size:64px; font-weight:700; letter-spacing:-.04em; line-height:.9; margin-bottom:12px;">Order<br><span style="color:#16a34a;">Confirmed!</span></h1>
    <p style="font-family:var(--f-mono); font-size:10px; color:#aaa; letter-spacing:.1em; text-transform:uppercase; margin-bottom:48px;">Your gadgets are being prepared for delivery</p>
    
    <div style="background:#f9f9f9; border:1px solid #eee; padding:32px; max-width:400px; margin:0 auto 32px; text-align:center;">
        <div style="font-family:var(--f-mono); font-size:9px; color:#999; margin-bottom:8px;">ORDER REFERENCE</div>
        <div style="font-family:var(--f-display); font-size:32px; font-weight:700;" id="conf-ref">#NX-82319</div>
    </div>
    
    <a href="<?= APP_URL ?>/shop" style="display:inline-flex; height:48px; background:#111; color:#fff; padding:0 32px; align-items:center; text-decoration:none; font-family:var(--f-display); font-weight:700; font-size:14px; letter-spacing:.02em;">Continue Shopping →</a>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>

function selectPayMethod(m) {
    document.getElementById('co-payment-method').value = m;
    
    // UI state toggle
    document.getElementById('pay-paystack').classList.toggle('on', m === 'paystack');
    document.getElementById('pay-pod').classList.toggle('on', m === 'pod');
    
    // Info block toggle
    document.getElementById('paystack-info').style.display = (m === 'paystack' ? 'flex' : 'none');
    document.getElementById('pod-info').style.display = (m === 'pod' ? 'flex' : 'none');
    
    // Button label update
    const btns = document.querySelectorAll('.co-pay-btn');
    btns.forEach(b => {
        if (m === 'pod') {
            b.innerHTML = '📦 PLACE ORDER (PAY ON DELIVERY) →';
        } else {
            updateShip(document.getElementById('co-zone')); // Restore dynamic price
        }
    });
}

function updateShip(el) {
    if (!el) return;
    let shipVal = parseFloat(el.value);
    const subtotal = <?= $subtotal ?>;
    const threshold = <?= defined('SHIPPING_FREE_THRESHOLD') ? SHIPPING_FREE_THRESHOLD : 200 ?>;
    
    if (subtotal >= threshold) {
        shipVal = 0;
    }

    const payNowBase = <?= $pay_now - $shipping ?>; // Original pay_now minus default shipping
    const totalBase = <?= $total - $shipping ?>; // Original total minus default shipping
    
    const newPayNow = payNowBase + shipVal;
    const newTotal = totalBase + shipVal;
    
    document.getElementById('disp-ship').innerText = shipVal > 0 ? '₵' + shipVal.toFixed(2) : 'FREE';
    document.getElementById('disp-total').innerText = '₵' + Math.round(newPayNow).toLocaleString();
    if(document.getElementById('disp-main-total')) {
        document.getElementById('disp-main-total').innerText = '₵' + Math.round(newTotal).toLocaleString();
    }
    
    const m = document.getElementById('co-payment-method').value;
    const btns = document.querySelectorAll('.co-pay-btn');
    btns.forEach(b => {
        if (m === 'pod') {
            b.innerHTML = '📦 PLACE ORDER (PAY ON DELIVERY) →';
        } else {
            b.innerHTML = '🛍️ PAY ₵' + Math.round(newPayNow).toLocaleString() + ' NOW →';
        }
    });
}

function validateForm() {
    const fields = ['co-name', 'co-email', 'co-phone', 'co-address', 'co-city'];
    let anyMissing = false;
    
    fields.forEach(fid => {
        const el = document.getElementById(fid);
        const fg = el.closest('.fg');
        const icon = fg.querySelector('.err-icon');
        const txt = fg.querySelector('.err-txt');

        if (!el || !el.value.trim()) {
            el.classList.add('err');
            if(icon) icon.style.display = 'flex';
            if(txt)  txt.style.display = 'block';
            anyMissing = true;
        } else {
            el.classList.remove('err');
            if(icon) icon.style.display = 'none';
            if(txt)  txt.style.display = 'none';
        }
    });

    const banner = document.getElementById('co-error-banner');
    if (anyMissing) {
        banner.style.display = 'flex';
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    banner.style.display = 'none';
    return true;
}

function initPaystack(evt) {
    if (!validateForm()) return;

    const paymentMethod = document.getElementById('co-payment-method').value;

    // GUARD: Ensure Paystack script is loaded (only for online payment)
    if (paymentMethod === 'paystack' && typeof PaystackPop === 'undefined') {
        alert('Payment gateway is still loading. Please wait a moment and try again.');
        return;
    }

    const btns = document.querySelectorAll('.co-pay-btn');
    const oldTexts = [];
    btns.forEach((btn, i) => {
        oldTexts[i] = btn.innerHTML;
        btn.innerHTML = '<span class="loading-dots">Processing Order...</span>';
        btn.disabled = true;
    });
    
    // UI RECOVERY HELPER
    const restoreButtons = () => {
        btns.forEach((btn, i) => {
            btn.innerHTML = oldTexts[i];
            btn.disabled = false;
        });
    };
    
    // Phase 1: Create the order
    const select = document.getElementById('co-zone');
    const zoneId = select.options[select.selectedIndex].getAttribute('data-id');
    
    const formData = {
        name: document.getElementById('co-name').value,
        email: document.getElementById('co-email').value,
        phone: document.getElementById('co-phone').value,
        delivery_zone_id: zoneId,
        shipping_cost: parseFloat(select.value),
        address: document.getElementById('co-address').value,
        city: document.getElementById('co-city').value,
        payment_method: paymentMethod,
        region: ''
    };

    fetch('<?= APP_URL ?>/checkout/complete', {
        method: 'POST',
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            restoreButtons();
            alert(data.message);
            return;
        }

        // REDIRECT FOR POD
        if (data.redirect_to_confirm) {
            showConfirm(data.order_ref);
            return;
        }

        // Phase 2: Open Paystack Popup
        const amountGhc = data.amount_ghs;
        
        let handler = PaystackPop.setup({
            key: '<?= PAYSTACK_PUBLIC_KEY ?>',
            email: formData.email,
            amount: Math.round(amountGhc * 100),
            currency: 'GHS',
            ref: data.order_ref,
            callback: function(response) {
                // Phase 3: Verify Payment on Server
                btns.forEach(btn => btn.innerHTML = 'Verifying Transaction...');
                
                fetch('<?= APP_URL ?>/api/paystack-verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reference: response.reference,
                        order_id: data.order_id
                    })
                })
                .then(vRes => vRes.json())
                .then(vData => {
                    if (vData.success) {
                        showConfirm(data.order_ref);
                    } else {
                        alert('Payment verification failed. Please contact support with Ref: ' + data.order_ref);
                        restoreButtons();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Verification error. Please contact support with Ref: ' + data.order_ref);
                    restoreButtons();
                });
            },
            onClose: function() {
                restoreButtons();
            }
        });
        handler.openIframe();
    })
    .catch(err => {
        console.error(err);
        restoreButtons();
        alert('Connectivity error. Please try again.');
    });
}

function showConfirm(ref) {
    document.getElementById('coView').style.display = 'none';
    document.getElementById('confView').style.display = 'block';
    document.getElementById('conf-ref').innerText = '#' + ref;
    window.scrollTo({top:0, behavior:'smooth'});
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
