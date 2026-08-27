<?php
// views/account/register.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<div class="auth-split reverse">
    <!-- Form Side -->
    <div class="auth-form-side">
        <div style="max-width: 400px; width: 100%; margin: 0 auto;">
            
            <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 40px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Join the Drop</h1>
            <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Create your account to access exclusive architectural tech.</p>

            <?php if (isset($error)): ?>
                <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
                    [ERROR] <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="<?= APP_URL ?>/register" method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Full Name</label>
                    <input type="text" name="full_name" placeholder="VADER WEST" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Email Address</label>
                    <input type="email" name="email" placeholder="USER@DOMAIN.COM" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>
                
                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Phone (WhatsApp)</label>
                    <input type="tel" name="phone" placeholder="+233 24 000 0000" style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">I want to</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                        <label style="border:1px solid var(--light-gray);border-radius:10px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:8px;"><input type="radio" name="buyer_type" value="individual" checked onchange="document.getElementById('company-row').style.display='none'"> <span style="font-family:var(--f-mono);font-size:11px;">Buy for myself</span></label>
                        <label style="border:1px solid var(--light-gray);border-radius:10px;padding:12px;cursor:pointer;display:flex;align-items:center;gap:8px;"><input type="radio" name="buyer_type" value="business" onchange="document.getElementById('company-row').style.display='block'"> <span style="font-family:var(--f-mono);font-size:11px;">Buy for business (bulk)</span></label>
                    </div>
                    <div id="company-row" style="display:none;"><input type="text" name="company_name" placeholder="Company / Business Name (optional)" style="width:100%;height:42px;background:#fff;border:1px solid var(--light-gray);border-radius:10px;padding:0 14px;font-family:var(--f-mono);font-size:11px;"></div>
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Also sell on Avazonia? (optional)</label>
                    <select name="seller_type" style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 14px;font-family:var(--f-mono);font-size:11px;color:var(--ink);outline:none;">
                        <option value="">No, I just want to buy</option>
                        <option value="individual">Individual Seller (C2C - used items)</option>
                        <option value="business_retailer">Business / Retailer (B2C)</option>
                        <option value="wholesaler">Wholesaler / Distributor (B2B)</option>
                        <option value="manufacturer">Manufacturer (B2B)</option>
                        <option value="international_supplier">International Supplier (B2B Export)</option>
                    </select>
                    <p style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);margin-top:6px;">Free to list. You can also apply later from your account. Sellers are verified with badges.</p>
                </div>

                <div class="form-group">
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 8px;">Password</label>
                    <div class="password-wrapper" style="position: relative;">
                        <input type="password" name="password" id="password-input" placeholder="••••••••" required style="width: 100%; height: 48px; background: #fff; border: 1px solid var(--light-gray); border-radius: 12px; padding: 0 48px 0 16px; font-family: var(--f-mono); font-size: 12px; color: var(--ink); outline: none;">
                        <button type="button" id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #BBB; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;">
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <script>
                document.getElementById('toggle-password').addEventListener('click', function() {
                    const input = document.getElementById('password-input');
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('svg').style.color = type === 'text' ? 'var(--red)' : '#BBB';
                });
                </script>

                <!-- Honeypot (bots fill this) -->
                <div style="position:absolute;left:-5000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                    <label>Leave this blank</label>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
                </div>
                <input type="hidden" name="form_time" value="<?= Session::get('register_form_time', time()) ?>">
                <!-- Math Captcha -->
                <div class="form-group" style="background:var(--off);border:1px solid var(--light-gray);border-radius:12px;padding:14px 16px;">
                    <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Human check: What is <?= (int)($captcha_a ?? Session::get('register_captcha_a',3)) ?> + <?= (int)($captcha_b ?? Session::get('register_captcha_b',4)) ?> ? <span style="color:var(--red);">*</span></label>
                    <input type="number" name="captcha_answer" placeholder="Answer" required style="width:100%;height:42px;background:#fff;border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-family:var(--f-mono);font-size:13px;color:var(--ink);outline:none;">
                    <div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);margin-top:6px;">Prevents bots. Takes 2 seconds.</div>
                </div>

                <button type="submit" class="btn-red" style="width: 100%; height: 48px; font-size: 11px; margin-top: 16px;">Create Account →</button>
                
                <div style="margin-top: 32px; text-align: center;">
                    <p style="font-family: var(--f-body); font-size: 13px; color: var(--mid-gray);">
                        Already have an account? <a href="<?= APP_URL ?>/login" style="color: var(--red); font-weight: 700; margin-left:8px; border-bottom: 1px solid var(--red); text-decoration: none;">Login here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Graphic Side -->
    <div class="auth-graphic-side">
        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 40%, rgba(0,0,0,0.8)); z-index: 1;"></div>
        <img src="https://images.pexels.com/photos/325153/pexels-photo-325153.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2" alt="Avazonia Brand Photography" style="width: 100%; height: 100%; object-fit: cover;">
        <div style="position: absolute; bottom: 80px; left: 80px; right: 80px; color: #fff; z-index: 2;">
            <p style="font-family: var(--f-display); font-weight: 900; font-size: 12px; text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 24px; opacity: 0.8;">Architecture-Led Electronics</p>
            <h2 style="font-family: var(--f-display); font-weight: 900; font-size: 48px; text-transform: uppercase; line-height: 1; letter-spacing: -0.04em;">REDEFINING THE<br>DIGITAL WARDROBE</h2>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
