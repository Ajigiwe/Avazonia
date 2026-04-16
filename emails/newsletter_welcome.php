<?php
// emails/newsletter_welcome.php
// Template vars: $toEmail
require_once __DIR__ . '/layout.php';

$appName = defined('APP_NAME') ? APP_NAME : 'Avazonia';
$appUrl  = defined('APP_URL')  ? APP_URL  : '#';

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #0A0A0A 0%, #1a1a2e 100%);">
  <h1>Welcome to the Family! 🎉</h1>
  <p>You're officially on the <?= htmlspecialchars($appName) ?> insider list.</p>
</div>
<div class="email-body">
  <p>Hi there,</p>
  <p>Thanks for subscribing to the <strong><?= htmlspecialchars($appName) ?></strong> newsletter! You'll now be the first to know about:</p>

  <div class="info-block">
    🔥 <strong>Exclusive Drops</strong> — New arrivals before anyone else<br>
    🏷️ <strong>Members-Only Deals</strong> — Special discounts just for subscribers<br>
    📦 <strong>Restock Alerts</strong> — Never miss your favorite items<br>
    🎁 <strong>Seasonal Promos</strong> — Holiday sales, flash deals & more
  </div>

  <p>We keep it short, relevant, and spam-free. Expect updates only when it matters.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/shop" class="btn-primary">Start Shopping →</a>
  </div>

  <p style="font-size: 13px; color: #999;">You subscribed with <strong><?= htmlspecialchars($toEmail) ?></strong> on <?= date('D, d M Y · H:i') ?>.</p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, "Welcome to {$appName} — you're in!");
