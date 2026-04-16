<?php
// emails/newsletter_admin_notify.php
// Template vars: $subscriberEmail
require_once __DIR__ . '/layout.php';

$appName = defined('APP_NAME') ? APP_NAME : 'Avazonia';
$appUrl  = defined('APP_URL')  ? APP_URL  : '#';
$toEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #16A34A 0%, #22D3EE 100%);">
  <h1>New Subscriber! 📬</h1>
  <p>Someone just joined the <?= htmlspecialchars($appName) ?> mailing list.</p>
</div>
<div class="email-body">
  <p>Hey Admin,</p>
  <p>A new visitor has subscribed to your newsletter. Here are the details:</p>

  <div class="info-block">
    📧 <strong>Email:</strong> <?= htmlspecialchars($subscriberEmail) ?><br>
    📅 <strong>Date:</strong> <?= date('D, d M Y · H:i T') ?><br>
    🌐 <strong>Source:</strong> Website newsletter popup
  </div>

  <p>Your mailing list is growing! You can view and manage all subscribers from your admin dashboard.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/admin/newsletter.php" class="btn-primary">View Subscribers →</a>
  </div>

  <p style="font-size: 13px; color: #999;">This is an automated notification from <?= htmlspecialchars($appName) ?>.</p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, "New newsletter subscriber: {$subscriberEmail}");
