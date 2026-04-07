<?php
// emails/order_cancelled.php
// Template vars: $toEmail, $toName, $order (array)
require_once __DIR__ . '/layout.php';

$currencySymbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₵';
ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #374151 0%, #6B7280 100%);">
  <h1>Order Cancelled 😔</h1>
  <p>Order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong> has been cancelled.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>Your order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong> has been cancelled. We're sorry this didn't work out.</p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order['order_ref']) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Status</td>
        <td style="text-align:right;"><span class="status-badge status-cancelled">Cancelled</span></td>
      </tr>
      <tr>
        <td style="color:#888;">Order Total</td>
        <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($order['total_ghs'], 2) ?></td>
      </tr>
    </table>
  </div>

  <p>If a payment was made, our team will process any applicable refund within <strong>3–5 business days</strong>. You'll receive a separate email confirming the refund.</p>
  <p>If you have questions or believe this was an error, please contact us immediately.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="https://wa.me/<?= defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '' ?>" class="btn-secondary">📱 Contact Support</a>
  </div>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Your Avazonia order #' . $order['order_ref'] . ' has been cancelled');
