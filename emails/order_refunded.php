<?php
// emails/order_refunded.php
// Template vars: $toEmail, $toName, $order (array)
require_once __DIR__ . '/layout.php';

$currencySymbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₵';
ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #D97706 0%, #FBBF24 100%);">
  <h1>Refund Initiated 💰</h1>
  <p>A refund has been processed for order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong>.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>We've initiated a refund for your cancelled order. Here are the details:</p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order['order_ref']) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Refund Amount</td>
        <td style="font-weight:700; text-align:right; color:#D97706;"><?= $currencySymbol ?><?= number_format($order['total_ghs'], 2) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Status</td>
        <td style="text-align:right;"><span class="status-badge status-refunded">Refunded</span></td>
      </tr>
      <tr>
        <td style="color:#888;">Payment Method</td>
        <td style="text-align:right;"><?= ucfirst(str_replace('_', ' ', $order['paystack_channel'] ?? 'Original method')) ?></td>
      </tr>
    </table>
  </div>

  <div class="notice">
    ⏱ Refunds typically arrive within <strong>3–5 business days</strong>, depending on your bank or mobile money provider (MTN, Telecel, AT).
  </div>

  <p>If you have not received your refund after 5 business days, please contact us via WhatsApp or email and we'll investigate immediately.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="https://wa.me/<?= defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '' ?>" class="btn-primary">📱 Need Help?</a>
  </div>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Refund initiated for order #' . $order['order_ref']);
