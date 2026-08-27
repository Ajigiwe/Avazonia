<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <a href="<?= APP_URL ?>/seller/products" style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-decoration:none;text-transform:uppercase;letter-spacing:0.1em;">&larr; Back to Products</a>
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:8px 0 0;">Edit Product</h1>
</div>

<?php if(!empty($error)): ?><div style="background:#fef3c7;border:1.5px solid #f59e0b;padding:12px 14px;font-family:var(--f-mono);font-size:11px;color:#92400e;margin-bottom:14px;">&#9888; <?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/seller/products/edit/<?= (int)$product['id'] ?>" style="max-width:700px;">
    <?= Csrf::field() ?>
    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Product Name <span style="color:var(--red);">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Price (GHS) <span style="color:var(--red);">*</span></label>
            <input type="number" step="0.01" name="price_ghs" value="<?= (float)$product['price_ghs'] ?>" required style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
        </div>
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Stock Qty</label>
            <input type="number" name="stock_qty" value="<?= (int)($product['stock_qty']??0) ?>" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Category</label>
            <select name="category_id" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
                <option value="0">Select category</option>
                <?php foreach($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= ((int)($product['category_id']??0)===(int)$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Listing Type</label>
            <select name="listing_type" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
                <?php foreach(['retail','wholesale','rfq','export'] as $lt): ?>
                <option value="<?= $lt ?>" <?= ($product['listing_type']??'retail')===$lt?'selected':'' ?>><?= ucfirst($lt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Condition</label>
            <select name="condition_type" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
                <?php foreach(['new','refurbished','used'] as $ct): ?>
                <option value="<?= $ct ?>" <?= ($product['condition_type']??'new')===$ct?'selected':'' ?>><?= ucfirst($ct) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Visibility</label>
            <select name="visibility" style="width:100%;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
                <?php foreach(['public','retail_only','b2b_only'] as $v): ?>
                <option value="<?= $v ?>" <?= ($product['visibility']??'public')===$v?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$v)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">MOQ (Minimum Order Qty)</label>
        <input type="number" name="moq" value="<?= (int)($product['moq']??0) ?>" style="width:200px;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="margin-bottom:20px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Wholesale Price (GHS)</label>
        <input type="number" step="0.01" name="wholesale_price_ghs" value="<?= (float)($product['wholesale_price_ghs']??0) ?>" style="width:200px;height:44px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:0 14px;font-size:13px;color:var(--ink);box-sizing:border-box;">
    </div>

    <div style="margin-bottom:24px;">
        <label style="display:block;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--mid-gray);margin-bottom:8px;">Description</label>
        <textarea name="description" style="width:100%;min-height:120px;background:var(--off);border:1px solid var(--light-gray);border-radius:8px;padding:12px 14px;font-size:13px;color:var(--ink);box-sizing:border-box;font-family:var(--f-body);"><?= htmlspecialchars($product['description']??'') ?></textarea>
    </div>

    <div style="display:flex;gap:12px;">
        <button type="submit" style="background:var(--red);color:#fff;padding:14px 32px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;letter-spacing:0.05em;">Save Changes</button>
        <a href="<?= APP_URL ?>/seller/products" style="border:2px solid var(--ink);color:var(--ink);padding:14px 32px;font-family:var(--f-semi);font-size:11px;font-weight:800;text-transform:uppercase;text-decoration:none;letter-spacing:0.05em;">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
