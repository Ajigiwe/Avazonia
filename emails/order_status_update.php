<?php
// emails/order_status_update.php
// Template vars: $toEmail, $toName, $order (array)
require_once __DIR__ . '/layout.php';

$status_labels = [
    'approved' => ['label' => 'Approved', 'color' => '#00a854', 'icon' => '✅', 'msg' => 'Great news! Your order has been approved and confirmed.'],
    'processing' => ['label' => 'Processing', 'color' => '#fa8c16', 'icon' => '⚙️', 'msg' => 'Your order is now being processed and prepared for dispatch.'],
    'arrived' => ['label' => 'Arrived', 'color' => '#111', 'icon' => '📦', 'msg' => 'Your pre-ordered item has arrived at our warehouse and is ready for final delivery.'],
    'delivered' => ['label' => 'Delivered', 'color' => '#00a854', 'icon' => '🏁', 'msg' => 'Your order has been marked as delivered. We hope you enjoy your purchase!'],
];

$cur = $status_labels[$order['status']] ?? ['label' => ucfirst($order['status']), 'color' => '#333', 'icon' => 'ℹ️', 'msg' => 'The status of your order has been updated.'];

ob_start(); ?>
<div class="email-hero" style="background: linear-gradient(135deg, <?= $cur['color'] ?> 0%, #333 100%);">
  <h1>Order Status Update <?= $cur['icon'] ?></h1>
  <p>Order <strong>#<?= htmlspecialchars($order['order_ref']) ?></strong> is now <strong><?= strtoupper($cur['label']) ?></strong>.</p>
</div>
<div class="email-body">
  <p>Hi <strong><?= htmlspecialchars($toName) ?></strong>,</p>
  <p><?= $cur['msg'] ?></p>

  <div class="info-block">
    <table style="width:100%; font-size:14px;">
      <tr>
        <td style="color:#888; padding-bottom:8px;">Order Ref</td>
        <td style="font-weight:700; text-align:right;">#<?= htmlspecialchars($order['order_ref']) ?></td>
      </tr>
      <tr>
        <td style="color:#888; padding-bottom:8px;">New Status</td>
        <td style="text-align:right;">
          <span style="padding:4px 10px; border-radius:4px; background:<?= $cur['color'] ?>; color:#fff; font-size:10px; font-weight:700; text-transform:uppercase;">
            <?= $cur['label'] ?>
          </span>
        </td>
      </tr>
      <tr>
        <td style="color:#888;">Update Time</td>
        <td style="text-align:right;"><?= date('M d, Y H:i') ?></td>
      </tr>
    </table>
  </div>

  <p>You can track your order progress and view more details in your account dashboard.</p>

  <div style="text-align: center; margin: 32px 0;">
    <a href="<?= defined('APP_URL') ? APP_URL : '#' ?>/account" class="btn-primary">View My Orders →</a>
  </div>

  <p>If you have any questions, feel free to reply to this email or reach out via WhatsApp.</p>
</div>
<?php
$content = ob_get_clean();
echo email_layout($content, 'Order Update — #' . $order['order_ref']);
