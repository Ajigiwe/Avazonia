<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
if (Session::get('user_role') !== 'admin') { header('Location: '.APP_URL.'/login'); exit; }
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../config/database.php';
$db=db();
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['verify_id'])) {
    $s=new Seller(); $seller=$s->findById((int)$_POST['verify_id']);
    if ($seller) {
        $level=$_POST['verification_level'] ?? 'business_verified';
        $verified = ($level==='avazonia_verified' || $level==='company_verified' || $level==='business_verified') ? 1 : (int)($_POST['is_verified'] ?? 0);
        $s->updateVerification((int)$seller['id'],$level,$verified);
        header('Location: sellers.php?success=1'); exit;
    }
}
$sellers=(new Seller())->getAll(100,0);
?>
<?php include __DIR__.'/layout/header.php'; ?>
<div style="padding:24px;max-width:1200px;">
  <h1 style="font-weight:900;">Marketplace — Sellers</h1>
  <p style="color:var(--mid-gray);">C2C/B2C/B2B sellers · verification per §13</p>
  <?php if(isset($_GET['success'])): ?><div style="background:#dcfce7;padding:10px;margin:12px 0;border:1px solid #16a34a;">Updated.</div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse;margin-top:16px;">
    <thead><tr style="background:var(--ink);color:#fff;font-family:var(--f-mono);font-size:11px;"><th style="padding:10px;text-align:left;">Business / User</th><th>Type</th><th>Country</th><th>Verification</th><th>Store</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach($sellers as $s): ?>
        <?php $store=$db->query("SELECT slug,name FROM stores WHERE seller_id=".(int)$s['id']." LIMIT 1")->fetch(); ?>
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:10px;"><strong><?= htmlspecialchars($s['business_name'] ?: $s['full_name']) ?></strong><br><span style="font-size:11px;color:#666;"><?= htmlspecialchars($s['email']) ?> · User #<?= (int)$s['user_id'] ?></span></td>
          <td style="padding:10px;font-family:var(--f-mono);font-size:11px;"><?= htmlspecialchars($s['seller_type']) ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($s['country_code']) ?> <?= htmlspecialchars($s['city']??'') ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($s['verification_level']) ?> <?= $s['is_verified']?'✓':'' ?><?php if(!empty($s['docs'])): $docs=json_decode($s['docs'],true); if(!empty($docs['ghana_card'])): ?><br><a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['ghana_card']) ?>" target="_blank" style="font-size:10px;color:var(--red);">🪪 Ghana Card</a><?php endif; if(!empty($docs['face_id'])): ?> · <a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['face_id']) ?>" target="_blank" style="font-size:10px;color:var(--red);">🧑 Face</a><?php endif; if(!empty($docs[0])): ?><br><a href="<?= APP_URL ?>/<?= htmlspecialchars($docs[0]) ?>" target="_blank" style="font-size:10px;color:var(--red);">📄 Doc</a><?php endif; endif; ?></td>
          <td style="padding:10px;"><?php if($store): ?><a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug']) ?>" target="_blank"><?= htmlspecialchars($store['name']) ?></a><?php else: ?>—<?php endif; ?></td>
          <td style="padding:10px;">
            <form method="POST" style="display:flex;gap:6px;">
              <input type="hidden" name="verify_id" value="<?= (int)$s['id'] ?>">
              <select name="verification_level" style="height:32px;">
                <option value="unverified" <?= $s['verification_level']=='unverified'?'selected':'' ?>>Unverified</option>
                <option value="phone_verified" <?= $s['verification_level']=='phone_verified'?'selected':'' ?>>Phone Verified</option>
                <option value="business_verified" <?= $s['verification_level']=='business_verified'?'selected':'' ?>>Business Verified</option>
                <option value="company_verified" <?= $s['verification_level']=='company_verified'?'selected':'' ?>>Company Verified</option>
                <option value="avazonia_verified" <?= $s['verification_level']=='avazonia_verified'?'selected':'' ?>>Avazonia Verified</option>
              </select>
              <button type="submit" style="background:var(--ink);color:#fff;border:none;padding:6px 12px;cursor:pointer;">Save</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($sellers)): ?><tr><td colspan="6" style="padding:20px;text-align:center;color:#999;">No sellers yet. New registrations with seller_type will appear here.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/layout/footer.php'; ?>
