<?php require_once __DIR__ . '/../layout/head.php'; require_once __DIR__ . '/../layout/nav.php'; ?>
<div style="max-width:1100px;margin:0 auto;padding:32px 16px;">
    <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
        <h1 style="font-family:var(--f-display);font-weight:900;font-size:clamp(22px,4vw,32px);margin:0;">My Enquiries</h1>
        <a href="<?= APP_URL ?>/sourcing" style="font-family:var(--f-mono);font-size:11px;color:var(--red);text-decoration:none;text-transform:uppercase;letter-spacing:0.08em;">+ New Sourcing Enquiry</a>
    </div>

    <?php if (empty($rfqs)): ?>
    <div style="border:2px dashed var(--light-gray);padding:60px 24px;text-align:center;">
        <div style="font-size:24px;margin-bottom:12px;">&#9993;</div>
        <div style="font-family:var(--f-semi);font-size:14px;color:var(--mid-gray);">No enquiries yet</div>
        <div style="font-size:12px;color:var(--mid-gray);margin-top:8px;">Request a quote from any wholesale or international supplier listing and it will appear here.</div>
        <a href="<?= APP_URL ?>/sourcing" style="display:inline-block;margin-top:16px;background:var(--ink);color:#fff;padding:10px 18px;font-weight:800;font-size:11px;text-transform:uppercase;text-decoration:none;letter-spacing:0.08em;">Browse B2B Sourcing</a>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($rfqs as $r): ?>
        <div style="border:2px solid var(--ink);padding:20px;background:#fff;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                <div style="flex:1;min-width:220px;">
                    <div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);margin-bottom:6px;">
                        #<?= (int)$r['id'] ?> &middot; <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
                        <?php if (!empty($r['product_name'])): ?> &middot; <?= htmlspecialchars($r['product_name']) ?><?php endif; ?>
                    </div>
                    <div style="font-family:var(--f-mono);font-size:11px;color:var(--mid-gray);margin-bottom:8px;">
                        Supplier: <strong style="color:var(--ink);"><?= htmlspecialchars($r['business_name'] ?? '—') ?></strong>
                        &middot; Qty: <?= (int)$r['qty'] ?>
                        <?php if (!empty($r['destination'])): ?>&rarr; <?= htmlspecialchars($r['destination']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($r['message'])): ?>
                    <div style="font-size:13px;line-height:1.6;color:var(--ink);"><?= nl2br(htmlspecialchars($r['message'])) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php $statusColors = ['pending'=>['var(--off)','var(--mid-gray)','var(--light-gray)'],'quoted'=>['#e6f4ea','#00a854','#00a854'],'accepted'=>['#e6f4ea','#00a854','#00a854'],'rejected'=>['#fdecea','#f5222d','#f5222d'],'closed'=>['var(--off)','var(--mid-gray)','var(--light-gray)']]; ?>
                    <?php $sc = $statusColors[$r['status']] ?? $statusColors['pending']; ?>
                    <span style="font-family:var(--f-mono);font-size:10px;padding:8px 16px;background:<?= $sc[0] ?>;color:<?= $sc[1] ?>;border:1px solid <?= $sc[2] ?>;text-transform:uppercase;letter-spacing:0.08em;"><?= htmlspecialchars($r['status']) ?></span>
                </div>
            </div>

            <?php if ($r['status'] === 'quoted'): ?>                <div style="margin-top:14px;border:1px solid #b7e4c7;background:#f6fef9;padding:14px;">
                <div style="font-family:var(--f-mono);font-size:10px;color:#00a854;text-transform:uppercase;letter-spacing:0.1em;margin-bottom:8px;">&#10003; Supplier Quote</div>
                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <?php if (!empty($r['quote_unit_price'])): ?>
                    <div><div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;">Unit Price</div><div style="font-family:var(--f-display);font-weight:900;font-size:20px;">GHS <?= number_format((float)$r['quote_unit_price'], 2) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($r['quote_qty'])): ?>
                    <div><div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;">Quoted Qty</div><div style="font-family:var(--f-display);font-weight:900;font-size:20px;"><?= (int)$r['quote_qty'] ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($r['quote_lead_time_days'])): ?>
                    <div><div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;">Lead Time</div><div style="font-family:var(--f-display);font-weight:900;font-size:20px;"><?= (int)$r['quote_lead_time_days'] ?> days</div></div>
                    <?php endif; ?>
                    <div><div style="font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);text-transform:uppercase;">Total</div><div style="font-family:var(--f-display);font-weight:900;font-size:20px;">GHS <?= number_format((float)$r['quote_unit_price'] * (float)($r['quote_qty'] ?: $r['qty']), 2) ?></div></div>
                </div>
                <?php if (!empty($r['seller_reply'])): ?>
                <div style="margin-top:10px;font-size:12px;line-height:1.6;color:var(--ink);border-top:1px solid #b7e4c7;padding-top:10px;"><strong>Supplier note:</strong> <?= nl2br(htmlspecialchars($r['seller_reply'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($r['quote_at'])): ?>
                <div style="margin-top:8px;font-family:var(--f-mono);font-size:9px;color:var(--mid-gray);">Quoted on <?= date('d M Y', strtotime($r['quote_at'])) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
