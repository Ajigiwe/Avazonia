<?php
// emails/verification.php
// Template vars: $toEmail, $toName, $verifyUrl
require_once __DIR__ . '/layout.php';

ob_start(); ?>
<div class="email-hero">
  <h1>Welcome to Avazonia 👋</h1>
  <p>You're almost in. Just verify your email to activate your account.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>Thanks for creating an Avazonia account! We're excited to have you join Ghana's premier tech destination.</p>
  <p>To get started, please verify your email address by clicking the button below:</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= htmlspecialchars($verifyUrl) ?>" class="btn-primary">✉ Verify My Email</a>
  </div>

  <div class="notice">
    ⏱ This link expires in <strong>24 hours</strong>. If you didn't create an account, please ignore this email.
  </div>

  <hr class="divider">

  <p style="font-size: 13px; color: #999;">Or paste this URL in your browser:<br>
    <a href="<?= htmlspecialchars($verifyUrl) ?>" style="color: <?= defined('PRIMARY_COLOR') ? PRIMARY_COLOR : '#E5001A' ?>; word-break: break-all;"><?= htmlspecialchars($verifyUrl) ?></a>
  </p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Verify your email to activate your Avazonia account');
