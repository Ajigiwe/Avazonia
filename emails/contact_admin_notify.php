<?php
// emails/contact_admin_notify.php
// Template vars: $customerName, $customerEmail, $subjectLine, $messageBody
require_once __DIR__ . '/layout.php';

$appName = defined('APP_NAME') ? APP_NAME : 'Avazonia';
$appUrl  = defined('APP_URL')  ? APP_URL  : '#';

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);">
  <h1>New Support Request 📩</h1>
  <p>A customer has submitted a message via the Contact Form.</p>
</div>
<div class="email-body">
  <p>Hey Admin,</p>
  <p>You have received a new message from the <strong><?= htmlspecialchars($appName) ?></strong> contact form. Please review the details below:</p>

  <div class="info-block">
    👤 <strong>Name:</strong> <?= htmlspecialchars($customerName) ?><br>
    📧 <strong>Email:</strong> <?= htmlspecialchars($customerEmail) ?><br>
    📅 <strong>Date:</strong> <?= date('D, d M Y · H:i T') ?>
  </div>

  <h2>📝 Message Details</h2>
  <div style="background: #FAFAFA; border: 1px solid #EAEAEA; padding: 24px; border-radius: 12px; margin: 20px 0;">
    <p style="margin-bottom: 12px;"><strong>Subject:</strong> <?= htmlspecialchars($subjectLine) ?></p>
    <p style="margin-bottom: 0; white-space: pre-wrap; font-size: 15px; color: #333; line-height: 1.6;"><?= htmlspecialchars($messageBody) ?></p>
  </div>

  <div style="text-align: center; margin: 32px 0;">
    <a href="mailto:<?= htmlspecialchars($customerEmail) ?>?subject=Re: <?= urlencode($subjectLine) ?>" class="btn-primary">Reply to Customer →</a>
  </div>

  <p style="font-size: 13px; color: #999;">This is an automated notification from your website's contact form.</p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, "New Contact Form Submission: {$subjectLine}");
