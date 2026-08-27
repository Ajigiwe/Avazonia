<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">Store Settings</h1>
    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Manage your store profile</div>
</div>

<?php if(!empty($success)): ?><div style="background:#e6f7ec;border:1.5px solid #00a854;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#00a854;margin-bottom:14px;">&#10003; Store settings saved successfully.</div><?php endif; ?>
<?php if(!empty($error)): ?><div style="background:#fef3c7;border:1.5px solid #f59e0b;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#92400e;margin-bottom:14px;">&#9888; <?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/seller/settings" enctype="multipart/form-data" style="max-width:700px;">
    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Store Name <span style="color:var(--red);">*</span></label>
        <input type="text" name="store_name" value="<?= htmlspecialchars($store['name'] ?? $seller['business_name']) ?>" required style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Tagline</label>
        <input type="text" name="tagline" value="<?= htmlspecialchars($store['tagline'] ?? '') ?>" placeholder="Your store's tagline or motto" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">City / Location</label>
        <input type="text" name="city" value="<?= htmlspecialchars($store['city'] ?? $seller['city'] ?? '') ?>" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Store Description</label>
        <textarea name="description" placeholder="Tell buyers about your store, what you sell, and your service quality..." style="width:100%;min-height:120px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--ink);box-sizing:border-box;font-family:var(--f-body);"><?= htmlspecialchars($seller['description'] ?? $store['description'] ?? '') ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Store Logo</label>
            <input type="file" name="logo" accept="image/*" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--ink);box-sizing:border-box;">
            <?php if(!empty($store['logo_url'])): ?>
            <div style="margin-top:8px;"><img src="<?= APP_URL ?>/<?= htmlspecialchars($store['logo_url']) ?>" style="height:40px;border:1px solid var(--light-gray);"></div>
            <?php endif; ?>
        </div>
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Store Banner</label>
            <input type="file" name="banner" accept="image/*" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:10px 14px;font-size:12px;color:var(--ink);box-sizing:border-box;">
            <?php if(!empty($store['banner_url'])): ?>
            <div style="margin-top:8px;"><img src="<?= APP_URL ?>/<?= htmlspecialchars($store['banner_url']) ?>" style="height:40px;width:100px;object-fit:cover;border:1px solid var(--light-gray);"></div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-bottom:24px;padding:16px;background:var(--off);border:1px solid var(--light-gray);">
        <div style="font-family:var(--f-mono);font-size:10px;text-transform:uppercase;color:var(--mid-gray);letter-spacing:0.1em;margin-bottom:6px;">Store Info</div>
        <div style="font-family:var(--f-mono);font-size:11px;color:var(--ink);">Slug: <strong><?= htmlspecialchars($store['slug'] ?? 'pending') ?></strong> &middot; Seller ID: <?= (int)$seller['id'] ?></div>
        <div style="font-family:var(--f-mono);font-size:11px;color:var(--ink);margin-top:4px;">Public URL: <a href="<?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?>" style="color:var(--red);text-decoration:none;"><?= APP_URL ?>/store/<?= htmlspecialchars($store['slug'] ?? '') ?></a></div>
    </div>

    <div style="display:flex;gap:12px;">
        <button type="submit" style="background:var(--red);color:#fff;padding:14px 32px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;letter-spacing:0.05em;">Save Settings</button>
        <a href="<?= APP_URL ?>/seller/dashboard" style="border:2px solid var(--ink);color:var(--ink);padding:14px 32px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
