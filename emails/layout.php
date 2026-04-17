<?php
// emails/layout.php
// Shared HTML email shell — include this in all templates via ob_start wrapping
// Usage: wrap your email body between $content_start and $content_end

/**
 * Helper: render the full layout around a content block.
 * Call this at bottom of each template file.
 */
function email_layout(string $content, string $preheader = ''): string {
    $appName   = defined('APP_NAME')   ? APP_NAME   : 'Avazonia';
    $appUrl    = defined('APP_URL')    ? APP_URL    : '#';
    $siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : '';
    $year      = date('Y');
    
    // Fallback to a sleek dark red if PRIMARY_COLOR isn't defined or is invalid
    $primaryColor = defined('PRIMARY_COLOR') ? PRIMARY_COLOR : '#E5001A';

    ob_start(); ?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= htmlspecialchars($appName) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap');
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #F4F4F5; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111; -webkit-font-smoothing: antialiased; }
    .email-wrap { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,.05); border: 1px solid rgba(0,0,0,0.05); }
    .email-header { background: #0A0A0A; padding: 32px 40px; text-align: center; }
    .email-header a { color: #fff; text-decoration: none; font-size: 24px; font-weight: 900; letter-spacing: -0.04em; text-transform: uppercase; }
    .email-header a span { color: <?= $primaryColor ?>; }
    .email-hero { background: <?= $primaryColor ?>; padding: 48px 40px; color: #fff; text-align: center; }
    .email-hero h1 { font-size: 32px; font-weight: 900; letter-spacing: -0.03em; margin-bottom: 12px; line-height: 1.2; }
    .email-hero p { font-size: 16px; opacity: 0.95; line-height: 1.6; font-weight: 500; }
    .email-body { padding: 48px 40px; }
    .email-body p { font-size: 16px; line-height: 1.6; color: #444; margin-bottom: 20px; }
    .email-body h2 { font-size: 20px; font-weight: 700; color: #111; margin: 32px 0 16px; letter-spacing: -0.02em; }
    .btn-primary { display: inline-block; background: <?= $primaryColor ?>; color: #fff !important; text-decoration: none; padding: 16px 36px; border-radius: 50px; font-weight: 700; font-size: 15px; letter-spacing: 0.03em; margin: 24px 0; text-align: center; }
    .btn-secondary { display: inline-block; background: #0A0A0A; color: #fff !important; text-decoration: none; padding: 16px 36px; border-radius: 50px; font-weight: 700; font-size: 15px; letter-spacing: 0.03em; margin: 8px 0; text-align: center; }
    .order-table { width: 100%; border-collapse: collapse; margin: 24px 0; }
    .order-table th { background: #FAFAFA; padding: 14px 16px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #888; border-bottom: 1px solid #EAEAEA; }
    .order-table td { padding: 16px; border-bottom: 1px solid #F0F0F0; font-size: 15px; color: #333; }
    .order-table tr:last-child td { border-bottom: none; }
    .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
    .status-paid { background: #DCFCE7; color: #16A34A; }
    .status-shipped { background: #DBEAFE; color: #1D4ED8; }
    .status-cancelled { background: #FEE2E2; color: #DC2626; }
    .status-refunded { background: #FEF3C7; color: #D97706; }
    .divider { border: none; border-top: 1px solid #EAEAEA; margin: 32px 0; }
    .info-block { background: #FAFAFA; border-left: 4px solid <?= $primaryColor ?>; padding: 20px 24px; border-radius: 0 12px 12px 0; margin: 24px 0; font-size: 15px; color: #444; line-height: 1.6; }
    .notice { background: #FFFBEB; border: 1px solid #FDE68A; padding: 20px 24px; border-radius: 12px; margin: 24px 0; font-size: 14px; color: #92400E; line-height: 1.6; }
    .email-footer { background: #FAFAFA; border-top: 1px solid #EAEAEA; padding: 32px 40px; text-align: center; }
    .email-footer p { font-size: 13px; color: #888; line-height: 1.6; }
    .email-footer a { color: <?= $primaryColor ?>; text-decoration: none; font-weight: 500; }
    @media (max-width: 600px) {
      .email-wrap { margin: 16px; border-radius: 12px; }
      .email-hero, .email-body, .email-footer, .email-header { padding: 32px 24px; }
      .email-hero h1 { font-size: 26px; }
      .btn-primary, .btn-secondary { display: block; width: 100%; box-sizing: border-box; }
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
