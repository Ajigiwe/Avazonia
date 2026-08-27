<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<?php include __DIR__ . '/sidebar.php'; ?>

<div style="margin-bottom:32px;">
    <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">RFQs / Enquiries</h1>
    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);text-transform:uppercase;letter-spacing:0.1em;margin-top:6px;">Request for Quotations from buyers</div>
</div>

<div class="seller-stats-bar">
    <div class="seller-stat-card"><div class="stat-label">Total RFQs</div><div class="stat-value"><?= count($rfqs) ?></div></div>
    <div class="seller-stat-card"><div class="stat-label">Pending</div><div class="stat-value"><?= count(array_filter($rfqs, fn($r)=>$r['status']==='pending')) ?></div></div>
</div>

<?php if(!empty($rfqs)): ?>
<div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach($rfqs as $r): ?>
    <div style="border:2px solid var(--ink);padding:20px;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div style="flex:1;min-width:200px;">
                <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-bottom:6px;"><?= date('d M Y H:i', strtotime($r['created_at'])) ?> &middot; <span style="font-weight:700;text-transform:uppercase;"><?= htmlspecialchars($r['status']) ?></span></div>
                <div style="font-weight:700;font-size:14px;margin-bottom:4px;"><?= htmlspecialchars($r['product_name'] ?? 'General Enquiry') ?></div>
                <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);margin-bottom:8px;">Qty: <?= (int)$r['qty'] ?> &rarr; <?= htmlspecialchars($r['destination'] ?? '-') ?></div>
                <?php if(!empty($r['specs'])): ?><div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);margin-bottom:8px;">Specs: <?= htmlspecialchars($r['specs']) ?></div><?php endif; ?>
                <div style="font-size:13px;line-height:1.6;margin-bottom:8px;"><?= nl2br(htmlspecialchars($r['message'] ?? '')) ?></div>
                <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">Buyer: <strong style="color:var(--ink);"><?= htmlspecialchars($r['buyer_name']) ?></strong> (<?= htmlspecialchars($r['buyer_email']) ?>)</div>
            </div>
            <div style="display:flex;gap:8px;">
                <?php if($r['status']==='pending'): ?>
                <form method="POST" action="<?= APP_URL ?>/seller/rfqs/respond/<?= (int)$r['id'] ?>" style="display:inline;">
                    <input type="hidden" name="rfq_action" value="quote">
                    <button type="submit" style="background:var(--ink);color:#fff;padding:8px 16px;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;">Reply</button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/seller/rfqs/respond/<?= (int)$r['id'] ?>" style="display:inline;">
                    <input type="hidden" name="rfq_action" value="accept">
                    <button type="submit" style="background:#00a854;color:#fff;padding:8px 16px;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;border:none;cursor:pointer;">Accept</button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/seller/rfqs/respond/<?= (int)$r['id'] ?>" style="display:inline;">
                    <input type="hidden" name="rfq_action" value="reject">
                    <button type="submit" style="background:#fff;color:#f5222d;border:1px solid #f5222d;padding:8px 16px;font-family:var(--f-semi);font-size:10px;font-weight:800;text-transform:uppercase;cursor:pointer;" onclick="return confirm('Reject this enquiry?')">Reject</button>
                </form>
                <?php else: ?>
                <span style="font-family:var(--f-mono);font-size:10px;padding:8px 16px;background:var(--off);border:1px solid var(--light-gray);text-transform:uppercase;"><?= htmlspecialchars($r['status']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="border:2px dashed var(--light-gray);padding:60px;text-align:center;">
    <div style="font-size:24px;margin-bottom:12px;">&#9993;</div>
    <div style="font-family:var(--f-semi);font-size:14px;color:var(--mid-gray);">No RFQs yet</div>
    <div style="font-size:12px;color:var(--mid-gray);margin-top:8px;">When buyers send enquiries about your products, they'll appear here.</div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/sidebar_footer.php'; ?>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
