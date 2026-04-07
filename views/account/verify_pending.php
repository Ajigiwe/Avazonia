<?php
// views/account/verify_pending.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>
<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 480px; width: 100%; margin: 0 auto; text-align: center;">

      <!-- Icon -->
      <div style="width: 96px; height: 96px; background: #FFF0F2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; border: 2px solid #FECDD3;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#E5001A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
      </div>

      <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 36px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Check Your Inbox</h1>
      <p style="font-family: var(--f-body); font-size: 15px; color: var(--mid-gray); margin-bottom: 40px; line-height: 1.6;">
        We sent a verification link to your email. Click it to activate your account.<br>
        <strong style="color: var(--ink);">It may take a minute or two.</strong>
      </p>

      <div style="background: #F9F9F9; border: 1px solid var(--light-gray); border-radius: 12px; padding: 24px; margin-bottom: 32px; text-align: left;">
        <p style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; letter-spacing: .1em; color: var(--mid-gray); margin-bottom: 12px;">WHAT'S NEXT?</p>
        <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
          <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">1</div>
          <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">Open the email from <strong>Avazonia</strong></p>
        </div>
        <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
          <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">2</div>
          <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">Click <strong>"Verify My Email"</strong></p>
        </div>
        <div style="display: flex; align-items: flex-start; gap: 12px;">
          <div style="width: 24px; height: 24px; background: var(--red); color:#fff; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0;">3</div>
          <p style="font-family: var(--f-body); font-size: 14px; color: #555; margin: 0; line-height: 1.5;">You're in! Start shopping 🛍</p>
        </div>
      </div>

      <a href="<?= APP_URL ?>" class="btn-ink" style="display:inline-block; padding: 14px 32px; font-size: 11px; text-decoration:none; border-radius:12px; margin-bottom: 20px;">Continue Shopping</a>

      <p style="font-family: var(--f-body); font-size: 13px; color: var(--mid-gray);">
        Didn't receive the email?
        <button id="resendBtn" onclick="resendVerification(this)" style="background:none; border:none; color:var(--red); font-weight:700; cursor:pointer; font-size:13px; text-decoration:underline; font-family:inherit;">Resend it</button>
      </p>
      <p id="resendMsg" style="font-family: var(--f-mono); font-size: 11px; color: #16A34A; margin-top:8px; display:none;"></p>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="https://images.pexels.com/photos/3345882/pexels-photo-3345882.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">Architecture-Led Electronics</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">YOUR ACCOUNT<br>IS ALMOST<br>READY.</h2>
    </div>
  </div>
</div>

<script>
async function resendVerification(btn) {
  btn.disabled = true;
  btn.textContent = 'Sending...';
  try {
    const res = await fetch('<?= APP_URL ?>/api/resend-verification', { method: 'POST' });
    const json = await res.json();
    const msg = document.getElementById('resendMsg');
    msg.style.display = 'block';
    msg.textContent = json.message;
    if (!json.success) msg.style.color = '#E5001A';
  } catch(e) {
    btn.disabled = false;
    btn.textContent = 'Resend it';
  }
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
