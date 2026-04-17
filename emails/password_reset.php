<?php
// emails/password_reset.php
// Template vars: $toEmail, $toName, $resetUrl
require_once __DIR__ . '/layout.php';

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #0A0A0A 0%, #333 100%);">
  <h1>Reset Your Password 🔐</h1>
  <p>We received a request to reset your Avazonia account password.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>Someone (hopefully you!) requested a password reset for the account associated with <strong><?= htmlspecialchars($toEmail) ?></strong>.</p>
  <p>Click the button below to choose a new password:</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= htmlspecialchars($resetUrl) ?>" class="btn-secondary">🔑 Reset My Password</a>
  </div>

  <div class="notice">
    ⏱ This link expires in <strong>1 hour</strong>. After that, you'll need to request a new reset link.
  </div>

  <hr class="divider">

  <p>If you didn't request a password reset, no action is needed — your account is safe.</p>

  <p style="font-size: 13px; color: #999; margin-top: 24px;">Or paste this URL in your browser:<br>
    <a href="<?= htmlspecialchars($resetUrl) ?>" style="color: <?= defined('PRIMARY_COLOR') ? PRIMARY_COLOR : '#E5001A' ?>; word-break: break-all;"><?= htmlspecialchars($resetUrl) ?></a>
  </p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Reset your Avazonia password — link expires in 1 hour');
