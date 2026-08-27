<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
if (Session::get('user_role') !== 'admin') { header('Location: '.APP_URL.'/login'); exit; }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Rfq.php';
$db=db();
if (isset($_POST['rfq_id']) && isset($_POST['status'])) {
    (new Rfq())->updateStatus((int)$_POST['rfq_id'], $_POST['status']);
    header('Location: rfqs.php?success=1'); exit;
}
$rfqs=$db->query("SELECT r.*, u.full_name as buyer_name, u.email as buyer_email, p.name as product_name, s.business_name as seller_name FROM rfqs r LEFT JOIN users u ON r.buyer_user_id=u.id LEFT JOIN products p ON r.product_id=p.id LEFT JOIN sellers s ON r.seller_id=s.id ORDER BY r.created_at DESC LIMIT 100")->fetchAll();
?>
<?php include __DIR__.'/layout/header.php'; ?>
<div style="padding:24px;max-width:1200px;">
  <h1 style="font-weight:900;">B2B Enquiries / RFQs</h1>
  <p style="color:var(--mid-gray);">Buyers requesting quotes from suppliers · B2B sourcing</p>
  <?php if(isset($_GET['success'])): ?><div style="background:#dcfce7;padding:10px;margin:12px 0;">Updated.</div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse;margin-top:16px;">
    <thead><tr style="background:var(--ink);color:#fff;font-family:var(--f-mono);font-size:11px;"><th style="padding:10px;text-align:left;">Date</th><th>Buyer</th><th>Product</th><th>Seller</th><th>Qty / Dest</th><th>Message</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($rfqs as $r): ?>
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:10px;font-size:11px;"><?= htmlspecialchars($r['created_at']) ?></td>
          <td style="padding:10px;font-size:11px;"><?= htmlspecialchars($r['buyer_name']) ?><br><?= htmlspecialchars($r['buyer_email']) ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($r['product_name'] ?? 'General') ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($r['seller_name'] ?? '—') ?></td>
          <td style="padding:10px;"><?= (int)$r['qty'] ?> · <?= htmlspecialchars($r['destination']??'-') ?><br><span style="font-size:11px;"><?= htmlspecialchars($r['specs']??'') ?></span></td>
          <td style="padding:10px;font-size:11px;max-width:220px;"><?= nl2br(htmlspecialchars($r['message']??'')) ?></td>
          <td style="padding:10px;">
            <form method="POST" style="display:flex;gap:4px;">
              <input type="hidden" name="rfq_id" value="<?= (int)$r['id'] ?>">
              <select name="status" style="height:30px;"><option <?= $r['status']=='pending'?'selected':'' ?> value="pending">pending</option><option <?= $r['status']=='quoted'?'selected':'' ?> value="quoted">quoted</option><option <?= $r['status']=='closed'?'selected':'' ?> value="closed">closed</option></select>
              <button type="submit" style="background:var(--ink);color:#fff;border:none;padding:6px 10px;cursor:pointer;">OK</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($rfqs)): ?><tr><td colspan="7" style="padding:20px;text-align:center;color:#999;">No enquiries yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/layout/footer.php'; ?>
