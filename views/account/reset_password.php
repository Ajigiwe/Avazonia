<?php
// views/account/reset_password.php
// Vars: $token (string), $error (string|null), $expired (bool)
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>
<div class="auth-split">
  <!-- Form Side -->
  <div class="auth-form-side">
    <div style="max-width: 400px; width: 100%; margin: 0 auto;">

      <h1 style="font-family: var(--f-display); font-weight: 900; font-size: 38px; text-transform: uppercase; margin-bottom: 8px; line-height: 1; letter-spacing: -0.04em;">New Password</h1>
      <p style="font-family: var(--f-body); font-size: 14px; color: var(--mid-gray); margin-bottom: 48px;">Choose a strong password for your Avazonia account.</p>

      <?php if (!empty($error)): ?>
        <div style="background: #fffafa; border: 1px solid #feeaea; color: var(--red); padding: 16px; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; letter-spacing: .05em; border-radius: 4px; margin-bottom: 32px;">
          [ERROR] <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($expired) || empty($token)): ?>
        <p style="font-family:var(--f-body);font-size:14px;color:var(--mid-gray);margin-bottom:24px;">Please request a new reset link.</p>
        <a href="<?= APP_URL ?>/forgot-password" class="btn-red" style="display:inline-block;padding:14px 32px;font-size:11px;text-decoration:none;border-radius:12px;">Request New Link →</a>
      <?php else: ?>

        <form action="<?= APP_URL ?>/reset-password?token=<?= urlencode($token) ?>" method="POST" style="display:flex;flex-direction:column;gap:24px;" id="resetForm">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">New Password</label>
            <div style="position:relative;">
              <input type="password" name="password" id="pw1" placeholder="••••••••" required minlength="6"
                style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 48px 0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
              <button type="button" onclick="togglePw('pw1',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--mid-gray);font-size:18px;">👁</button>
            </div>
            <p style="font-family:var(--f-body);font-size:11px;color:var(--mid-gray);margin-top:6px;">Minimum 6 characters</p>
          </div>

          <div class="form-group">
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Confirm Password</label>
            <div style="position:relative;">
              <input type="password" name="password2" id="pw2" placeholder="••••••••" required minlength="6"
                style="width:100%;height:48px;background:#fff;border:1px solid var(--light-gray);border-radius:12px;padding:0 48px 0 16px;font-family:var(--f-mono);font-size:12px;color:var(--ink);outline:none;">
              <button type="button" onclick="togglePw('pw2',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--mid-gray);font-size:18px;">👁</button>
            </div>
          </div>

          <!-- Password strength bar -->
          <div>
            <div style="height:4px;background:var(--light-gray);border-radius:4px;overflow:hidden;">
              <div id="strengthBar" style="height:100%;width:0%;background:var(--red);transition:width .3s,background .3s;border-radius:4px;"></div>
            </div>
            <p id="strengthLabel" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-top:4px;"></p>
          </div>

          <button type="submit" class="btn-red" style="width:100%;height:48px;font-size:11px;margin-top:8px;">Update Password →</button>
        </form>

      <?php endif; ?>
    </div>
  </div>

  <!-- Graphic Side -->
  <div class="auth-graphic-side">
    <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.8));z-index:1;"></div>
    <img src="https://images.pexels.com/photos/3345882/pexels-photo-3345882.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2" alt="" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;bottom:80px;left:80px;right:80px;color:#fff;z-index:2;">
      <p style="font-family:var(--f-display);font-weight:900;font-size:12px;text-transform:uppercase;letter-spacing:0.2em;margin-bottom:24px;opacity:0.8;">Account Security</p>
      <h2 style="font-family:var(--f-display);font-weight:900;font-size:48px;text-transform:uppercase;line-height:1;letter-spacing:-0.04em;">RESET &<br>SECURE<br>YOUR ACCOUNT.</h2>
    </div>
  </div>
</div>

<script>
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  input.type = input.type === 'password' ? 'text' : 'password';
}

// Password strength meter
document.getElementById('pw1')?.addEventListener('input', function() {
  const v = this.value;
  const bar = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (v.length >= 6) score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;
  const levels = [
    { pct:'20%', color:'#DC2626', text:'Very Weak' },
    { pct:'40%', color:'#EA580C', text:'Weak' },
    { pct:'60%', color:'#F59E0B', text:'Fair' },
    { pct:'80%', color:'#16A34A', text:'Good' },
    { pct:'100%', color:'#15803D', text:'Strong' },
  ];
  const lvl = levels[Math.max(0, score - 1)] || levels[0];
  bar.style.width = lvl.pct;
  bar.style.background = lvl.color;
  label.textContent = v.length > 0 ? lvl.text : '';
  label.style.color = lvl.color;
});

// Client-side match check
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
  const p1 = document.getElementById('pw1').value;
  const p2 = document.getElementById('pw2').value;
  if (p1 !== p2) {
    e.preventDefault();
    alert('Passwords do not match.');
  }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
