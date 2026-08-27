<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
if (Session::get('user_role') !== 'admin') { header('Location: '.APP_URL.'/login'); exit; }
// CSRF Check for POST requests
require_once __DIR__ . '/_csrf_check.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../config/database.php';
$db=db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $s=new Seller();
    // Suspend / Reactivate action
    if (isset($_POST['toggle_active_id'])) {
        $seller=$s->findById((int)$_POST['toggle_active_id']);
        if ($seller) {
            if (!empty($seller['is_active'])) {
                $s->suspend((int)$seller['id']);
            } else {
                $s->reactivate((int)$seller['id']);
            }
        }
        header('Location: sellers.php?success=1'); exit;
    }
    // Verification level update
    if (isset($_POST['verify_id'])) {
        $seller=$s->findById((int)$_POST['verify_id']);
        if ($seller) {
            $level=$_POST['verification_level'] ?? 'business_verified';
            $verified = ($level==='avazonia_verified' || $level==='company_verified' || $level==='business_verified') ? 1 : (int)($_POST['is_verified'] ?? 0);
            $s->updateVerification((int)$seller['id'],$level,$verified);
            header('Location: sellers.php?success=1'); exit;
        }
    }
}
$allSellersStmt=$db->prepare("SELECT s.*, u.full_name, u.email FROM sellers s LEFT JOIN users u ON s.user_id=u.id ORDER BY s.is_active DESC, s.is_verified DESC, s.created_at DESC LIMIT 100");
$allSellersStmt->execute();
$sellers=$allSellersStmt->fetchAll();
?>
<?php include __DIR__.'/layout/header.php'; ?>
<div style="padding:24px;max-width:1200px;">
  <h1 style="font-weight:900;">Marketplace — Sellers</h1>
  <p style="color:var(--mid-gray);">C2C/B2C/B2B sellers · verification per §13</p>
  <?php if(isset($_GET['success'])): ?><div style="background:#dcfce7;padding:10px;margin:12px 0;border:1px solid #16a34a;">Updated.</div><?php endif; ?>

  <?php
  // Summary stats
  $totalSellers=count($sellers);
  $activeSellers=0; $suspendedSellers=0; $pendingVerification=0; $verifiedSellers=0;
  foreach($sellers as $sv) {
      if(!empty($sv['is_active'])) $activeSellers++; else $suspendedSellers++;
      if($sv['is_verified']) $verifiedSellers++; else $pendingVerification++;
  }
  ?>
  <div style="display:flex;gap:16px;margin:16px 0;flex-wrap:wrap;">
    <div style="background:var(--paper);border:1px solid var(--light-gray);padding:12px 18px;min-width:120px;text-align:center;">
      <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:var(--mid-gray);">Total</div>
      <div style="font-weight:800;font-size:20px;"><?= $totalSellers ?></div>
    </div>
    <div style="background:#e6f7ec;border:1px solid #16a34a;padding:12px 18px;min-width:120px;text-align:center;">
      <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#16a34a;">Active</div>
      <div style="font-weight:800;font-size:20px;color:#16a34a;"><?= $activeSellers ?></div>
    </div>
    <div style="background:#fff1f0;border:1px solid #f5222d;padding:12px 18px;min-width:120px;text-align:center;">
      <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#f5222d;">Suspended</div>
      <div style="font-weight:800;font-size:20px;color:#f5222d;"><?= $suspendedSellers ?></div>
    </div>
    <div style="background:#fff7e6;border:1px solid #fa8c16;padding:12px 18px;min-width:120px;text-align:center;">
      <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#fa8c16;">Pending Review</div>
      <div style="font-weight:800;font-size:20px;color:#fa8c16;"><?= $pendingVerification ?></div>
    </div>
    <div style="background:#f0f5ff;border:1px solid #2f54eb;padding:12px 18px;min-width:120px;text-align:center;">
      <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#2f54eb;">Verified</div>
      <div style="font-weight:800;font-size:20px;color:#2f54eb;"><?= $verifiedSellers ?></div>
    </div>
  </div>

  <table style="width:100%;border-collapse:collapse;margin-top:16px;">
    <thead><tr style="background:var(--ink);color:#fff;font-family:var(--f-mono);font-size:11px;"><th style="padding:10px;text-align:left;">Business / User</th><th>Type</th><th>Country</th><th>Status</th><th>Verification</th><th>Documents</th><th>Store</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($sellers as $s): ?>
        <?php $store=$db->query("SELECT slug,name FROM stores WHERE seller_id=".(int)$s['id']." LIMIT 1")->fetch(); ?>
        <?php $docs=json_decode($s['docs']??'null',true); ?>
        <tr style="border-bottom:1px solid #eee;<?= empty($s['is_active'])?'background:#fff1f0;':'' ?>">
          <td style="padding:10px;"><strong><?= htmlspecialchars($s['business_name'] ?: $s['full_name']) ?></strong><br><span style="font-size:11px;color:#666;"><?= htmlspecialchars($s['email']) ?> · User #<?= (int)$s['user_id'] ?></span></td>
          <td style="padding:10px;font-family:var(--f-mono);font-size:11px;"><?= htmlspecialchars($s['seller_type']) ?></td>
          <td style="padding:10px;"><?= htmlspecialchars($s['country_code']) ?> <?= htmlspecialchars($s['city']??'') ?></td>
          <td style="padding:10px;">
            <?php if(!empty($s['is_active'])): ?>
              <span style="display:inline-block;font-family:var(--f-mono);font-size:10px;padding:3px 10px;border-radius:99px;background:#e6f7ec;color:#16a34a;font-weight:700;">Active</span>
            <?php else: ?>
              <span style="display:inline-block;font-family:var(--f-mono);font-size:10px;padding:3px 10px;border-radius:99px;background:#fff1f0;color:#f5222d;font-weight:700;">Suspended</span>
            <?php endif; ?>
          </td>
          <td style="padding:10px;">
            <?php
            $levelLabels=[
              'unverified'=>['color'=>'#9ca3af','bg'=>'#f3f4f6'],
              'phone_verified'=>['color'=>'#0ea5e9','bg'=>'#f0f9ff'],
              'business_verified'=>['color'=>'#2563eb','bg'=>'#eff6ff'],
              'company_verified'=>['color'=>'#f59e0b','bg'=>'#fffbeb'],
              'avazonia_verified'=>['color'=>'#16a34a','bg'=>'#f0fdf4'],
            ];
            $lvl=$s['verification_level']??'unverified';
            $lStyle=$levelLabels[$lvl]??$levelLabels['unverified'];
            ?>
            <span style="display:inline-block;font-family:var(--f-mono);font-size:10px;padding:3px 10px;border-radius:99px;background:<?= $lStyle['bg'] ?>;color:<?= $lStyle['color'] ?>;font-weight:700;"><?= htmlspecialchars(str_replace('_',' ',ucfirst($lvl))) ?></span>
          </td>
          <td style="padding:10px;">
            <?php if(!empty($docs['ghana_card'])): ?>
              <div style="margin-bottom:4px;">
                <a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['ghana_card']) ?>" target="_blank" style="font-size:10px;color:var(--red);display:flex;align-items:center;gap:4px;">
                  <img src="<?= APP_URL ?>/<?= htmlspecialchars($docs['ghana_card']) ?>" style="width:32px;height:22px;object-fit:cover;border:1px solid var(--light-gray);" alt="Ghana Card">
                  🪪 Card
                </a>
              </div>
            <?php endif; ?>
            <?php if(!empty($docs['face_id'])): ?>
              <div>
                <a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['face_id']) ?>" target="_blank" style="font-size:10px;color:var(--red);display:flex;align-items:center;gap:4px;">
                  <img src="<?= APP_URL ?>/<?= htmlspecialchars($docs['face_id']) ?>" style="width:22px;height:22px;object-fit:cover;border-radius:50%;border:1px solid var(--light-gray);" alt="Face ID">
                  🧑 Face
                </a>
              </div>
            <?php endif; ?>
            <?php if(empty($docs['ghana_card']) && empty($docs['face_id'])): ?>
              <span style="font-size:10px;color:#999;">No docs</span>
            <?php endif; ?>
          </td>
          <td style="padding:10px;"><?php if($store): ?><a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug']) ?>" target="_blank"><?= htmlspecialchars($store['name']) ?></a><?php else: ?>—<?php endif; ?></td>
          <td style="padding:10px;">
            <div style="display:flex;flex-direction:column;gap:6px;">
              <form method="POST" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="verify_id" value="<?= (int)$s['id'] ?>">
                <select name="verification_level" style="height:28px;font-size:11px;">
                  <option value="unverified" <?= $lvl==='unverified'?'selected':'' ?>>Unverified</option>
                  <option value="phone_verified" <?= $lvl==='phone_verified'?'selected':'' ?>>Phone Verified</option>
                  <option value="business_verified" <?= $lvl==='business_verified'?'selected':'' ?>>Business Verified</option>
                  <option value="company_verified" <?= $lvl==='company_verified'?'selected':'' ?>>Company Verified</option>
                  <option value="avazonia_verified" <?= $lvl==='avazonia_verified'?'selected':'' ?>>Avazonia Verified</option>
                </select>
                <button type="submit" style="background:var(--ink);color:#fff;border:none;padding:4px 10px;cursor:pointer;font-size:10px;">Save</button>
              </form>
              <form method="POST">
                <input type="hidden" name="toggle_active_id" value="<?= (int)$s['id'] ?>">
                <?php if(!empty($s['is_active'])): ?>
                  <button type="submit" onclick="return confirm('Suspend this seller? Their products will be hidden from the marketplace.')" style="background:#fff;color:#f5222d;border:1px solid #f5222d;padding:4px 10px;cursor:pointer;font-size:10px;width:100%;">Suspend</button>
                <?php else: ?>
                  <button type="submit" onclick="return confirm('Reactivate this seller?')" style="background:#fff;color:#16a34a;border:1px solid #16a34a;padding:4px 10px;cursor:pointer;font-size:10px;width:100%;">Reactivate</button>
                <?php endif; ?>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($sellers)): ?><tr><td colspan="8" style="padding:20px;text-align:center;color:#999;">No sellers yet. New registrations with seller_type will appear here.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__.'/layout/footer.php'; ?>
