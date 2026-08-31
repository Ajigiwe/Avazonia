<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Session.php';
Session::start();
if (Session::get('user_role') !== 'admin') { header('Location: '.APP_URL.'/login'); exit; }
require_once __DIR__ . '/_csrf_check.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../config/database.php';
$db=db();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $s=new Seller();
    if (isset($_POST['toggle_active_id'])) {
        $seller=$s->findById((int)$_POST['toggle_active_id']);
        if ($seller) {
            if (!empty($seller['is_active'])) { $s->suspend((int)$seller['id']); }
            else { $s->reactivate((int)$seller['id']); }
        }
        header('Location: sellers.php?success=1'); exit;
    }
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

$allSellersStmt=$db->prepare("SELECT s.*, u.full_name, u.email FROM sellers s LEFT JOIN users u ON s.user_id=u.id ORDER BY s.is_active DESC, s.is_verified DESC, s.created_at DESC LIMIT 200");
$allSellersStmt->execute();
$sellers=$allSellersStmt->fetchAll();

// Stats
$totalSellers=count($sellers);
$activeSellers=0; $suspendedSellers=0; $pendingVerification=0; $verifiedSellers=0;
foreach($sellers as $sv) {
    if(!empty($sv['is_active'])) $activeSellers++; else $suspendedSellers++;
    if($sv['is_verified']) $verifiedSellers++; else $pendingVerification++;
}

$title = "Manage Sellers";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Sellers <span style="font-family:var(--f-mono);font-size:14px;font-weight:400;color:var(--mid-gray);">(<?= $totalSellers ?>)</span></h1>
</div>

<?php if(isset($_GET['success'])): ?>
    <div style="background:#f6ffed;border:1px solid #b7eb8f;color:#52c41a;padding:12px 20px;border-radius:8px;margin-bottom:24px;font-family:var(--f-semi);font-size:13px;">
        Seller updated successfully.
    </div>
<?php endif; ?>

<!-- Summary Stats -->
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
    <div style="flex:1;min-width:120px;background:var(--paper);border:1px solid var(--light-gray);padding:16px 20px;text-align:center;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.08em;">Total</div>
        <div style="font-weight:800;font-size:24px;margin-top:4px;"><?= $totalSellers ?></div>
    </div>
    <div style="flex:1;min-width:120px;background:#f0fdf4;border:1px solid #bbf7d0;padding:16px 20px;text-align:center;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#16a34a;letter-spacing:0.08em;">Active</div>
        <div style="font-weight:800;font-size:24px;color:#16a34a;margin-top:4px;"><?= $activeSellers ?></div>
    </div>
    <div style="flex:1;min-width:120px;background:#fef2f2;border:1px solid #fecaca;padding:16px 20px;text-align:center;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#dc2626;letter-spacing:0.08em;">Suspended</div>
        <div style="font-weight:800;font-size:24px;color:#dc2626;margin-top:4px;"><?= $suspendedSellers ?></div>
    </div>
    <div style="flex:1;min-width:120px;background:#fff7ed;border:1px solid #fed7aa;padding:16px 20px;text-align:center;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#ea580c;letter-spacing:0.08em;">Pending Review</div>
        <div style="font-weight:800;font-size:24px;color:#ea580c;margin-top:4px;"><?= $pendingVerification ?></div>
    </div>
    <div style="flex:1;min-width:120px;background:#eff6ff;border:1px solid #bfdbfe;padding:16px 20px;text-align:center;">
        <div style="font-family:var(--f-mono);font-size:9px;text-transform:uppercase;color:#2563eb;letter-spacing:0.08em;">Verified</div>
        <div style="font-weight:800;font-size:24px;color:#2563eb;margin-top:4px;"><?= $verifiedSellers ?></div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Seller Accounts</div>
    </div>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th>Documents</th>
                    <th>Store</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sellers as $s): ?>
                <?php
                    $store=$db->query("SELECT slug,name FROM stores WHERE seller_id=".(int)$s['id']." LIMIT 1")->fetch();
                    $docs=json_decode($s['docs']??'null',true);
                    $lvl=$s['verification_level']??'unverified';
                ?>
                <tr style="<?= empty($s['is_active'])?'background:#fef2f2;':'' ?>">
                    <!-- Seller -->
                    <td>
                        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($s['business_name'] ?: $s['full_name']) ?></div>
                        <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-top:2px;"><?= htmlspecialchars($s['email']) ?></div>
                        <div style="font-family:var(--f-mono);font-size:9px;color:#9ca3af;margin-top:1px;">User #<?= (int)$s['user_id'] ?></div>
                    </td>
                    <!-- Type -->
                    <td>
                        <span style="font-family:var(--f-mono);font-size:9px;padding:3px 8px;border-radius:99px;background:var(--off);border:1px solid var(--light-gray);text-transform:uppercase;"><?= htmlspecialchars(str_replace('_',' ',$s['seller_type'])) ?></span>
                    </td>
                    <!-- Location -->
                    <td>
                        <div style="font-size:12px;font-weight:600;"><?= htmlspecialchars($s['country_code']) ?></div>
                        <?php if(!empty($s['city'])): ?>
                        <div style="font-size:11px;color:var(--mid-gray);"><?= htmlspecialchars($s['city']) ?></div>
                        <?php endif; ?>
                    </td>
                    <!-- Status -->
                    <td>
                        <?php if(!empty($s['is_active'])): ?>
                            <span class="status-badge status-paid">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-cancelled">Suspended</span>
                        <?php endif; ?>
                    </td>
                    <!-- Verification -->
                    <td>
                        <?php
                        $levelLabels=[
                            'unverified'=>['color'=>'#6b7280','bg'=>'#f3f4f6','label'=>'Unverified'],
                            'phone_verified'=>['color'=>'#0369a1','bg'=>'#e0f2fe','label'=>'Phone'],
                            'business_verified'=>['color'=>'#1d4ed8','bg'=>'#dbeafe','label'=>'Business'],
                            'company_verified'=>['color'=>'#b45309','bg'=>'#fef3c7','label'=>'Company'],
                            'avazonia_verified'=>['color'=>'#15803d','bg'=>'#dcfce7','label'=>'Avazonia'],
                        ];
                        $lStyle=$levelLabels[$lvl]??$levelLabels['unverified'];
                        ?>
                        <span style="display:inline-block;font-family:var(--f-mono);font-size:9px;padding:3px 10px;border-radius:99px;background:<?= $lStyle['bg'] ?>;color:<?= $lStyle['color'] ?>;font-weight:700;letter-spacing:0.03em;"><?= $lStyle['label'] ?></span>
                    </td>
                    <!-- Documents -->
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <?php if(!empty($docs['ghana_card'])): ?>
                                <a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['ghana_card']) ?>" target="_blank" title="View Ghana Card" style="display:flex;align-items:center;gap:4px;text-decoration:none;">
                                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($docs['ghana_card']) ?>" style="width:28px;height:20px;object-fit:cover;border:1px solid var(--light-gray);border-radius:2px;" alt="Ghana Card">
                                    <span style="font-size:9px;color:var(--ink);font-weight:600;">Card</span>
                                </a>
                            <?php endif; ?>
                            <?php if(!empty($docs['face_id'])): ?>
                                <a href="<?= APP_URL ?>/<?= htmlspecialchars($docs['face_id']) ?>" target="_blank" title="View Face ID" style="display:flex;align-items:center;gap:4px;text-decoration:none;">
                                    <img src="<?= APP_URL ?>/<?= htmlspecialchars($docs['face_id']) ?>" style="width:20px;height:20px;object-fit:cover;border-radius:50%;border:1px solid var(--light-gray);" alt="Face ID">
                                    <span style="font-size:9px;color:var(--ink);font-weight:600;">Face</span>
                                </a>
                            <?php endif; ?>
                            <?php if(empty($docs['ghana_card']) && empty($docs['face_id'])): ?>
                                <span style="font-size:10px;color:#9ca3af;font-style:italic;">No docs</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <!-- Store -->
                    <td>
                        <?php if($store): ?>
                            <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug']) ?>" target="_blank" style="font-size:12px;font-weight:600;color:var(--ink);text-decoration:none;"><?= htmlspecialchars($store['name']) ?></a>
                        <?php else: ?>
                            <span style="font-size:11px;color:#9ca3af;">—</span>
                        <?php endif; ?>
                    </td>
                    <!-- Actions -->
                    <td>
                        <div style="display:flex;flex-direction:column;gap:6px;min-width:140px;">
                            <form method="POST" style="display:flex;gap:4px;align-items:center;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="verify_id" value="<?= (int)$s['id'] ?>">
                                <select name="verification_level" style="height:26px;font-size:10px;font-family:var(--f-mono);border:1px solid var(--light-gray);padding:0 6px;border-radius:3px;flex:1;">
                                    <option value="unverified" <?= $lvl==='unverified'?'selected':'' ?>>Unverified</option>
                                    <option value="phone_verified" <?= $lvl==='phone_verified'?'selected':'' ?>>Phone</option>
                                    <option value="business_verified" <?= $lvl==='business_verified'?'selected':'' ?>>Business</option>
                                    <option value="company_verified" <?= $lvl==='company_verified'?'selected':'' ?>>Company</option>
                                    <option value="avazonia_verified" <?= $lvl==='avazonia_verified'?'selected':'' ?>>Avazonia</option>
                                </select>
                                <button type="submit" style="background:var(--ink);color:#fff;border:none;padding:4px 10px;cursor:pointer;font-size:9px;font-weight:700;text-transform:uppercase;border-radius:3px;">Save</button>
                            </form>
                            <form method="POST">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="toggle_active_id" value="<?= (int)$s['id'] ?>">
                                <?php if(!empty($s['is_active'])): ?>
                                    <button type="submit" onclick="return confirm('Suspend this seller? Their products will be hidden from the marketplace.')" style="background:#fff;color:#dc2626;border:1px solid #fca5a5;padding:4px 10px;cursor:pointer;font-size:9px;font-weight:700;text-transform:uppercase;width:100%;border-radius:3px;transition:all 0.2s;">Suspend</button>
                                <?php else: ?>
                                    <button type="submit" onclick="return confirm('Reactivate this seller?')" style="background:#fff;color:#16a34a;border:1px solid #86efac;padding:4px 10px;cursor:pointer;font-size:9px;font-weight:700;text-transform:uppercase;width:100%;border-radius:3px;transition:all 0.2s;">Reactivate</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($sellers)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--mid-gray);">No sellers yet. Registrations with seller_type will appear here.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
