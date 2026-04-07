<?php
// views/account/forgot_password.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>
<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 400px; width: 100%; margin: 0 auto;">

      <a href="<?= APP_URL ?>/login" style="display:inline-flex;align-items:center;gap:8px;font-family:var(--f-semi);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--mid-gray);text-decoration:none;margin-bottom:40px;">
        ← Back to Login
      </a>

      <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 38px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">Forgot Password</h1>
      <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Enter the email on your account and we'll send you a reset link.</p>

      <?php if (isset($success)): ?>
        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; color: #16A34A; padding: 20px 24px; border-radius: 12px; margin-bottom: 32px; font-family: var(--f-body); font-size: 14px; line-height: 1.6;">
          <strong>📬 Email Sent!</strong><br>
          <?= htmlspecialchars($success) ?>
        </div>
        <div style="text-align:center; margin-top:12px;">
          <a href="<?= APP_URL ?>/login" class="btn-ink" style="display:inline-block;padding:14px 32px;font-size:11px;text-decoration:none;border-radius:12px;">Back to Login</a>
        </div>
      <?php else: ?>

        <?php if (isset($error)): ?>
          <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
            [ERROR] <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form action="<?= APP_URL ?>/forgot-password" method="POST" style="display:flex;flex-direction:column;gap:24px;">
          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Email Address</label>
            <input type="email" name="email" placeholder="USER@DOMAIN.COM" required
              style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
          </div>
          <button type="submit" class="btn-red" style="width:100%;height:48px;font-size:11px;margin-top:8px;">Send Reset Link →</button>
        </form>

      <?php endif; ?>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="https://images.pexels.com/photos/325153/pexels-photo-325153.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">Account Recovery</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">WE'VE GOT<br>YOUR BACK.</h2>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
