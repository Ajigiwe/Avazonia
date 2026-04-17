<?php
// emails/order_placed.php
// Template vars: $toEmail, $toName, $order (array), $items (array)
require_once __DIR__ . '/layout.php';

$currencySymbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₵';
ob_start(); ?>
  <div class="email-hero">
    <h1>Order Confirmed! 🎉</h1>
    <p>Your order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong> has been received and is being processed.</p>
  </div>
  <div class="email-body">
    <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
    <p>Thanks for shopping with Avazonia! We've received your order and will notify you as soon as it's processed.</p>
  
    <h2>📦 Order Summary</h2>
    <table class="order-table">
      <thead>
        <tr>
          <th>Item</th>
          <th style="text-align:right;">Qty</th>
          <th style="text-align:right;">Price</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
            <?php if (!empty($item['variant_label'])): ?>
            <br><span style="font-size:12px;color:#888;"><?= htmlspecialchars($item['variant_label']) ?></span>
            <?php endif; ?>
          </td>
          <td style="text-align:right;"><?= (int)$item['qty'] ?></td>
          <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($item['unit_price_ghs'] * $item['qty'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:600; font-size:13px; color:#888; padding-top:16px;">Subtotal</td>
          <td style="text-align:right; padding-top:16px;"><?= $currencySymbol ?><?= number_format($order['subtotal_ghs'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:600; font-size:13px; color:#888;">Shipping</td>
          <td style="text-align:right;"><?= $currencySymbol ?><?= number_format($order['shipping_ghs'], 2) ?></td>
        </tr>
        <tr>
          <td colspan="2" style="text-align:right; font-weight:700; font-size:15px;">Total</td>
          <td style="text-align:right; font-weight:700; font-size:15px; color: <?= defined('PRIMARY_COLOR') ? PRIMARY_COLOR : '#E5001A' ?>;"><?= $currencySymbol ?><?= number_format($order['total_ghs'], 2) ?></td>

      </tr>
    </tfoot>
  </table>

  <h2>🚚 Delivery Details</h2>
  <div class="info-block">
    <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
    <?= htmlspecialchars($order['shipping_address'] ?? '') ?><?php if (!empty($order['shipping_city'])): ?>, <?= htmlspecialchars($order['shipping_city']) ?><?php endif; ?><br>
    <?php if (!empty($order['shipping_region'])): ?><?= htmlspecialchars($order['shipping_region']) ?><br><?php endif; ?>
    📞 <?= htmlspecialchars($order['customer_phone']) ?>
  </div>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= defined('APP_URL') ? APP_URL : '#' ?>/account" class="btn-primary">View My Orders →</a>
  </div>

  <p style="font-size:13px; color:#999;">Order placed on <?= date('D, d M Y · H:i', strtotime($order['created_at'])) ?></p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Your Avazonia order #' . $order['order_ref'] . ' is confirmed!');
