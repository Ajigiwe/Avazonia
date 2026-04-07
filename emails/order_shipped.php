<?php
// emails/order_shipped.php
// Template vars: $toEmail, $toName, $order (array)
require_once __DIR__ . '/layout.php';

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, #1D4ED8 0%, #60A5FA 100%);">
  <h1>Your Order is On Its Way! 🚚</h1>
  <p>Order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong> has been shipped and is heading your way.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p>Exciting news — your Avazonia order has been dispatched! Our delivery team is on the way.</p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order['order_ref']) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">Status</td>
        <td style="text-align:right;"><span class="status-badge status-shipped">Shipped</span></td>
      </tr>
      <tr>
        <td style="color:#888;">Delivering To</td>
        <td style="text-align:right;"><?= htmlspecialchars($order['shipping_city'] ?? $order['shipping_region'] ?? 'Your address') ?></td>
      </tr>
    </table>
  </div>

  <div class="notice" style="background:#EFF6FF; border-color:#BFDBFE; color:#1E40AF;">
    📞 Our delivery team may call <strong><?= htmlspecialchars($order['customer_phone']) ?></strong> to coordinate drop-off. Please keep your phone nearby.
  </div>

  <p>If you have any concerns about your delivery, reply to this email or WhatsApp us — we're always here.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= defined('APP_URL') ? APP_URL : '#' ?>/account" class="btn-primary">View Order →</a>
  </div>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Your order #' . $order['order_ref'] . ' has shipped!');
