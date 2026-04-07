<?php
// emails/layout.php
// Shared HTML email shell — include this in all templates via ob_start wrapping
// Usage: wrap your email body between $content_start and $content_end

/**
 * Helper: render the full layout around a content block.
 * Call this at bottom of each template file.
 */
function email_layout(string $content, string $preheader = ''): string {
    $appName  = defined('APP_NAME')  ? APP_NAME  : 'Avazonia';
    $appUrl   = defined('APP_URL')   ? APP_URL   : '#';
    $siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';
    $year = date('Y');

    ob_start(); ?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= htmlspecialchars($appName) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #F4F4F5; font-family: 'Inter', Arial, sans-serif; color: #111; }
    .email-wrap { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .email-header { background: #0A0A0A; padding: 32px 40px; }
    .email-header a { color: #fff; text-decoration: none; font-size: 22px; font-weight: 900; letter-spacing: -0.04em; text-transform: uppercase; }
    .email-header a span { color: #E5001A; }
    .email-hero { background: linear-gradient(135deg, #E5001A 0%, #FF4D6D 100%); padding: 48px 40px; color: #fff; }
    .email-hero h1 { font-size: 28px; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 8px; }
    .email-hero p { font-size: 15px; opacity: 0.9; line-height: 1.5; }
    .email-body { padding: 40px; }
    .email-body p { font-size: 15px; line-height: 1.7; color: #444; margin-bottom: 16px; }
    .email-body h2 { font-size: 18px; font-weight: 700; color: #111; margin: 24px 0 12px; }
    .btn-primary { display: inline-block; background: #E5001A; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 14px; letter-spacing: 0.03em; margin: 24px 0; }
    .btn-secondary { display: inline-block; background: #0A0A0A; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 14px; letter-spacing: 0.03em; margin: 8px 0; }
    .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .order-table th { background: #F4F4F5; padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #888; }
    .order-table td { padding: 14px 16px; border-bottom: 1px solid #F0F0F0; font-size: 14px; color: #333; }
    .order-table tr:last-child td { border-bottom: none; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
    .status-paid { background: #DCFCE7; color: #16A34A; }
    .status-shipped { background: #DBEAFE; color: #1D4ED8; }
    .status-cancelled { background: #FEE2E2; color: #DC2626; }
    .status-refunded { background: #FEF3C7; color: #D97706; }
    .divider { border: none; border-top: 1px solid #F0F0F0; margin: 24px 0; }
    .info-block { background: #F9F9F9; border-left: 3px solid #E5001A; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; color: #444; line-height: 1.7; }
    .notice { background: #FFFBEB; border: 1px solid #FDE68A; padding: 16px 20px; border-radius: 8px; margin: 20px 0; font-size: 13px; color: #92400E; }
    .email-footer { background: #F4F4F5; padding: 32px 40px; text-align: center; }
    .email-footer p { font-size: 12px; color: #999; line-height: 1.6; }
    .email-footer a { color: #E5001A; text-decoration: none; }
    @media (max-width: 600px) {
      .email-wrap { margin: 0; border-radius: 0; }
      .email-hero, .email-body, .email-footer, .email-header { padding: 28px 24px; }
      .email-hero h1 { font-size: 22px; }
    }
  </style>
</head>
<body>
  <?php if ($preheader): ?>
  <span style="display:none;max-height:0;overflow:hidden;"><?= htmlspecialchars($preheader) ?></span>
  <?php endif; ?>
  <div class="email-wrap">
    <!-- Header -->
    <div class="email-header">
      <a href="<?= $appUrl ?>"><?= htmlspecialchars($appName) ?><span>.</span></a>
    </div>

    <?= $content ?>

    <!-- Footer -->
    <div class="email-footer">
      <p>
        © <?= $year ?> <?= htmlspecialchars($appName) ?> — Crafted in Takoradi, Ghana<br>
        <a href="<?= $appUrl ?>">Shop</a> &nbsp;·&nbsp;
        <a href="<?= $appUrl ?>/account">My Account</a> &nbsp;·&nbsp;
        <a href="mailto:<?= htmlspecialchars($siteEmail) ?>">Support</a>
      </p>
      <p style="margin-top:12px;">This email was sent to <strong><?= htmlspecialchars($toEmail ?? '') ?></strong>.
      If you didn't request this, you can safely ignore it.</p>
    </div>
  </div>
</body>
</html>
<?php
    return ob_get_clean();
}
