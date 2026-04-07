<?php
// emails/order_paid.php
// Template vars: $toEmail, $toName, $order (array)
require_once __DIR__ . '/layout.php';

$currencySymbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₵';
ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #16A34A 0%, #22C55E 100%);">
  <h1>Payment Received ✅</h1>
  <p>We've successfully received your payment for order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong>.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>Great news — your payment of <strong><?= $currencySymbol ?><?= number_format($order['total_ghs'], 2) ?></strong> has been confirmed. Your order is now being prepared.</p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order['order_ref']) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Amount Paid</td>
        <td style="font-weight:700; text-align:right; color:#16A34A;"><?= $currencySymbol ?><?= number_format($order['total_ghs'], 2) ?></td>
      </tr>
      <tr>
        <td style="color:#888;">Status</td>
        <td style="text-align:right;"><span class="status-badge status-paid">Paid</span></td>
      </tr>
    </table>
  </div>

  <p>We'll send you another email as soon as your order ships. Expected delivery: <strong>1–3 business days</strong> (Accra) or <strong>3–5 days</strong> (other regions).</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= defined('APP_URL') ? APP_URL : '#' ?>/account" class="btn-primary">Track My Order →</a>
  </div>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Payment confirmed for order #' . $order['order_ref']);
