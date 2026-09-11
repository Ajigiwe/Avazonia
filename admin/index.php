<?php
// admin/index.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
$isSqlite = ($driver === 'sqlite');

// 1. REVENUE & GROWTH (DYNAMIC) — driver-aware
try {
  if ($isSqlite) {
    $curMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= date('now','start of month') AND status NOT IN ('cancelled','failed')")->fetchColumn() ?: 0;
    $lastMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= date('now','start of month','-1 month') AND created_at < date('now','start of month') AND status NOT IN ('cancelled','failed')")->fetchColumn() ?: 1;
  } else {
    $curMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') AND status NOT IN ('cancelled', 'failed')")->fetchColumn() ?: 0;
    $lastMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') AND status NOT IN ('cancelled', 'failed')")->fetchColumn() ?: 1;
  }
} catch(Throwable $e) { $curMonthRev=0; $lastMonthRev=1; }

$revGrowth = (($curMonthRev - $lastMonthRev) / $lastMonthRev) * 100;

// 2. ORDER TRENDS
try {
  if ($isSqlite) {
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $curMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= date('now','start of month')")->fetchColumn();
    $lastMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= date('now','start of month','-1 month') AND created_at < date('now','start of month')")->fetchColumn() ?: 1;
  } else {
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $curMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
    $lastMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn() ?: 1;
  }
} catch(Throwable $e) { $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(); $curMonthOrders=0; $lastMonthOrders=1; }
$orderGrowth = (($curMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100;

// 3. AOV (AVERAGE ORDER VALUE)
$aov = $totalOrders > 0 ? (($db->query("SELECT SUM(total_ghs) FROM orders WHERE status NOT IN ('cancelled', 'failed')")->fetchColumn() ?: 0) / $totalOrders) : 0;

// 4. TOP SELLING PRODUCTS
$topProducts = $db->query("
    SELECT p.name, p.slug, SUM(oi.qty) as total_sold, p.stock_qty, p.price_ghs
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 4
")->fetchAll();

// 5. REVENUE BY CATEGORY (CHART DATA)
$catSales = $db->query("
    SELECT c.name, SUM(oi.unit_price_ghs * oi.qty) as revenue
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN categories c ON p.category_id = c.id 
    GROUP BY c.id 
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

// 6. REVENUE TRENDS (LAST 14 DAYS)
try {
  if ($isSqlite) {
    $revenueTrends = $db->query("
        SELECT strftime('%m-%d', created_at) as date_label, SUM(total_ghs) as total
        FROM orders 
        WHERE created_at >= date('now','-13 days')
        AND status NOT IN ('cancelled','failed')
        GROUP BY date(created_at)
        ORDER BY created_at ASC
    ")->fetchAll();
  } else {
    $revenueTrends = $db->query("
        SELECT DATE_FORMAT(created_at, '%b %d') as date_label, SUM(total_ghs) as total
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        AND status NOT IN ('cancelled', 'failed')
        GROUP BY DATE(created_at)
        ORDER BY created_at ASC
    ")->fetchAll();
  }
} catch(Throwable $e) { $revenueTrends=[]; }

// 7. RECENT ACTIVITY
$recentOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

$marketStats=[];
try {
  $marketStats['sellers']=$db->query("SELECT COUNT(*) FROM sellers")->fetchColumn();
  $marketStats['stores']=$db->query("SELECT COUNT(*) FROM stores")->fetchColumn();
  $marketStats['pending_products']=$db->query("SELECT COUNT(*) FROM products WHERE status_market='pending_review'")->fetchColumn();
  $marketStats['rfqs']=$db->query("SELECT COUNT(*) FROM rfqs")->fetchColumn();
  $marketStats['wholesale']=$db->query("SELECT COUNT(*) FROM products WHERE listing_type='wholesale'")->fetchColumn();
  $marketStats['export']=$db->query("SELECT COUNT(*) FROM products WHERE listing_type='export' OR vehicle_origin='international_export'")->fetchColumn();
  $marketStats['pending_sellers']=$db->query("SELECT COUNT(*) FROM sellers WHERE verification_level='phone_verified' AND is_verified=0")->fetchColumn();
} catch(Throwable $e) {}
$title = "Dashboard Insights — Avazonia";
include 'layout/header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .analytics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 48px; }
    @media (max-width: 1200px) { .analytics-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .analytics-grid { grid-template-columns: 1fr; } }
    
    .stat-card-bold { 
        background: #fff; border: 1px solid var(--light-gray); border-radius: 16px; padding: 32px; position: relative; overflow: hidden;
        display: flex; flex-direction: column; gap: 8px; box-shadow: 0 1px 3px rgba(13,13,13,0.04); transition: box-shadow .3s, transform .3s;
    }
    .stat-card-bold:hover { box-shadow: 0 12px 28px rgba(232,0,45,0.08); transform: translateY(-2px); }
    .stat-card-bold .label { font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; }
    .stat-card-bold .value { font-family: var(--f-display); font-size: 32px; font-weight: 800; letter-spacing: -0.02em; }
    .trend-indicator { font-family: var(--f-mono); font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 4px; }
    .trend-up { color: #00a854; }
    .trend-down { color: var(--red); }
    
    .chart-container { display: flex; flex-direction: column; gap: 24px; margin-top: 24px; }
    .bar-row { display: grid; grid-template-columns: 140px 1fr 80px; align-items: center; gap: 16px; }
    .bar-bg { height: 12px; background: #eee; border-radius: 99px; overflow: hidden; }
    .bar-fill { height: 100%; background: linear-gradient(90deg, var(--red) 0%, var(--red-deep) 100%); border-radius: 99px; }
    
    .leaderboard-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--light-gray); }
    .leaderboard-item:last-child { border: none; }
    
    .dashboard-layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 40px; align-items: start; }
    @media (max-width: 1024px) { .dashboard-layout { grid-template-columns: 1fr; } }
</style>

<div class="admin-hero" style="margin-bottom: 48px;">
    <div>
        <div class="hero-kicker">&#9632; Admin Control</div>
        <div class="hero-title">Performance Insights</div>
        <div class="hero-sub">Unified intelligence engine &middot; Active tracking</div>
    </div>
</div>

<div class="analytics-grid">
    <!-- STAT 01: REVENUE -->
    <div class="stat-card-bold">
        <span class="label">Total Revenue</span>
        <span class="value">₵<?= number_format($curMonthRev, 2) ?></span>
        <div class="trend-indicator <?= $revGrowth >= 0 ? 'trend-up' : 'trend-down' ?>">
            <?= $revGrowth >= 0 ? '▲' : '▼' ?> <?= abs(round($revGrowth, 1)) ?>% 
            <span style="opacity: 0.5; color: var(--ink);">vs last month</span>
        </div>
    </div>
    
    <!-- STAT 02: ORDERS -->
    <div class="stat-card-bold">
        <span class="label">Total Orders</span>
        <span class="value"><?= number_format($totalOrders) ?></span>
        <div class="trend-indicator <?= $orderGrowth >= 0 ? 'trend-up' : 'trend-down' ?>">
            <?= $orderGrowth >= 0 ? '▲' : '▼' ?> <?= abs(round($orderGrowth, 1)) ?>% 
            <span style="opacity: 0.5; color: var(--ink);">MoM Velocity</span>
        </div>
    </div>
    
    <!-- STAT 03: AVERAGE BASKET -->
    <div class="stat-card-bold">
        <span class="label">Avg Order Value (AOV)</span>
        <span class="value">₵<?= number_format($aov, 2) ?></span>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray);">BASKET EFFICIENCY</div>
    </div>
    
    <!-- STAT 04: GROWTH TARGET -->
    <div class="stat-card-bold" style="background: var(--ink); color: #fff; border: none;">
        <span class="label" style="color: rgba(255,255,255,0.6);">Monthly Revenue Goal</span>
        <span class="value" style="font-size: 48px;">84%</span>
        <div style="height: 6px; background: rgba(255,255,255,0.1); margin-top: 12px; border-radius: 0; overflow: hidden;">
            <div style="width: 84%; height: 100%; background: #00a854;"></div>
        </div>
    </div>
</div>

<!-- MARKETPLACE STATS -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:32px;">
  <div class="mini-card"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);letter-spacing:.08em;">SELLERS</div><div style="font-weight:900;font-size:24px;margin-top:6px;"><?= (int)($marketStats['sellers']??0) ?></div><a href="sellers.php" style="font-size:10px;color:var(--red);font-weight:700;text-decoration:none;">Manage →</a></div>
  <div class="mini-card"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);letter-spacing:.08em;">PENDING PRODUCTS</div><div style="font-weight:900;font-size:24px;margin-top:6px;"><?= (int)($marketStats['pending_products']??0) ?></div><a href="approvals.php" style="font-size:10px;color:var(--red);font-weight:700;text-decoration:none;">Approve →</a></div>
  <div class="mini-card"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);letter-spacing:.08em;">RFQs</div><div style="font-weight:900;font-size:24px;margin-top:6px;"><?= (int)($marketStats['rfqs']??0) ?></div><a href="rfqs.php" style="font-size:10px;color:var(--red);font-weight:700;text-decoration:none;">View →</a></div>
  <div class="mini-card"><div style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);letter-spacing:.08em;">WHOLESALE / EXPORT</div><div style="font-weight:900;font-size:24px;margin-top:6px;"><?= (int)($marketStats['wholesale']??0) ?> / <?= (int)($marketStats['export']??0) ?></div><a href="sellers.php" style="font-size:10px;color:var(--red);font-weight:700;text-decoration:none;">Sellers →</a></div>
</div>

<div style="margin-bottom: 40px;">
    <div class="panel">
        <div class="panel-header">
            <div class="panel-title">Revenue Trends (Last 14 Days)</div>
        </div>
        <div style="padding: 32px; height: 350px;">
            <canvas id="revenueTrendChart"></canvas>
        </div>
    </div>
</div>

<div class="dashboard-layout">
    
    <div style="display: flex; flex-direction: column; gap: 40px;">
        <!-- CATEGORY SALES (CHART.JS) -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Sales by Category</div>
            </div>
            <div style="padding: 40px; display: flex; justify-content: center; align-items: center; min-height: 400px;">
                <div style="width: 100%; max-width: 350px;">
                    <canvas id="categorySalesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITY -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Recent Transactions</div>
                <a href="orders.php" class="nav-link" style="font-size: 10px; color: var(--red);">Full Ledger →</a>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr><th>Ref</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td style="font-family: var(--f-mono); font-size: 11px;"><?= $order['order_ref'] ?></td>
                            <td>
                                <div style="font-weight: 700;"><?= $order['customer_name'] ?></div>
                                <div style="font-size: 10px; opacity: 0.5;"><?= $order['customer_email'] ?></div>
                            </td>
                            <td style="font-weight: 800;">₵<?= number_format($order['total_ghs'], 2) ?></td>
                            <td><span class="status-badge status-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        <div class="panel">
            <div class="panel-header"><div class="panel-title">Strategic Actions</div></div>
            <div style="padding: 32px; display: flex; flex-direction: column; gap: 16px;">
                <a href="add-product.php" class="admin-btn admin-btn-primary" style="width: 100%; height: 50px;">Deploy New Drop</a>
                <a href="products.php" class="admin-btn admin-btn-secondary" style="width: 100%; height: 50px;">Inventory Control</a>
            </div>
        </div>

        <div class="panel" id="vendor-invite">
            <div class="panel-header"><div class="panel-title">Invite Vendors</div></div>
            <div style="padding: 24px 32px 32px;">
                <div style="background: linear-gradient(135deg, var(--red) 0%, var(--red-deep) 100%); color: #fff; border-radius: 14px; padding: 20px 24px; margin-bottom: 16px;">
                    <div style="display:inline-block;background:rgba(255,255,255,.2);font-family:var(--f-mono);font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;padding:4px 10px;border-radius:999px;margin-bottom:10px;">&#9733; Vendor Invitation</div>
                    <div style="font-family:var(--f-display);font-weight:900;font-size:16px;text-transform:uppercase;letter-spacing:-0.01em;">Sell on Avazonia</div>
                    <?php
                    $inviteMsg = '';
                    try { $inviteMsg = trim((string)(new Settings())->get('vendor_invite_message', '')); } catch (\Throwable $e) {}
                    if ($inviteMsg === '') $inviteMsg = "Hi! I'd love to have you selling on Avazonia — Ghana's home for hot drops and trusted vendors. Setting up your store is free and takes less than two minutes. Start here: " . APP_URL . "/sell";
                    ?>
                    <div id="invite-msg" style="font-family:var(--f-body);font-size:12.5px;line-height:1.55;color:rgba(255,255,255,.94);margin-top:6px;"><?= htmlspecialchars($inviteMsg) ?></div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" id="copy-invite" class="admin-btn admin-btn-primary" style="height:44px;flex:1;min-width:140px;">&#128203; Copy Invite Message</button>
                    <a href="https://wa.me/?text=" id="wa-share" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary" style="height:44px;flex:1;min-width:140px;text-decoration:none;">Share on WhatsApp</a>
                    <button type="button" id="dl-invite-img" class="admin-btn admin-btn-secondary" style="height:44px;flex:1;min-width:140px;">&#11015;&#65039; Download Invite Image</button>
                </div>
                <div id="copy-feedback" style="font-family:var(--f-mono);font-size:10px;color:#00a854;margin-top:10px;display:none;">&#10003; Copied — paste it into WhatsApp, Instagram or email</div>
                <div id="img-feedback" style="font-family:var(--f-mono);font-size:10px;color:#00a854;margin-top:10px;display:none;">&#10003; Invite image downloaded — attach it when you share the message</div>
            </div>
        </div>

        <script>
        (function(){
            var msg = document.getElementById('invite-msg');
            var btn = document.getElementById('copy-invite');
            var wa = document.getElementById('wa-share');
            var feedback = document.getElementById('copy-feedback');
            var imgFeedback = document.getElementById('img-feedback');
            var text = msg ? msg.textContent.trim() : '';
            if (wa) wa.href = 'https://wa.me/?text=' + encodeURIComponent(text);
            if (btn) btn.addEventListener('click', function(){
                var done = function(){ feedback.style.display='block'; setTimeout(function(){ feedback.style.display='none'; }, 3000); };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done).catch(function(){ fallback(); });
                } else { fallback(); }
                function fallback(){
                    var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
                    ta.select(); try { document.execCommand('copy'); done(); } catch(e){} ta.remove();
                }
            });

            /* ── Branded invite image generator (1080×1080 PNG, drawn client-side) ── */
            function roundRect(x, X, Y, w, h, r){
                x.beginPath(); x.moveTo(X+r, Y);
                x.arcTo(X+w, Y, X+w, Y+h, r); x.arcTo(X+w, Y+h, X, Y+h, r);
                x.arcTo(X, Y+h, X, Y, r); x.arcTo(X, Y, X+w, Y, r); x.closePath();
            }
            function drawInvite(cb){
                var W=1080, H=1080;
                var c=document.createElement('canvas'); c.width=W; c.height=H;
                var x=c.getContext('2d');
                var g=x.createLinearGradient(0,0,W,H); g.addColorStop(0,'#E8002D'); g.addColorStop(1,'#B8001F');
                x.fillStyle=g; x.fillRect(0,0,W,H);
                x.fillStyle='rgba(255,255,255,0.07)';
                x.beginPath(); x.arc(960,130,200,0,7); x.fill();
                x.beginPath(); x.arc(80,980,160,0,7); x.fill();
                var ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
                ready.then(function(){
                    x.textAlign='center';
                    // Invitation pill
                    x.fillStyle='rgba(255,255,255,0.22)'; roundRect(x, W/2-200, 84, 400, 58, 29); x.fill();
                    x.fillStyle='#fff'; x.font='800 23px Inter, Arial, sans-serif';
                    x.fillText('\u2605  VENDOR INVITATION', W/2, 122);
                    // Headline
                    x.font='900 100px Outfit, Arial, sans-serif';
                    x.fillText('SELL ON', W/2, 288);
                    x.fillText('AVAZONIA', W/2, 396);
                    // Subline
                    x.font='500 30px Inter, Arial, sans-serif'; x.fillStyle='rgba(255,255,255,0.94)';
                    x.fillText("Ghana's home for hot drops & trusted vendors", W/2, 462);
                    // Benefits
                    var items=['Free to list — pay only when you sell','Get the \u2713 Verified Vendor badge','Reach buyers across Ghana & beyond'];
                    x.textAlign='left';
                    items.forEach(function(t,i){
                        var y=560+i*76;
                        x.fillStyle='#fff'; x.beginPath(); x.arc(210,y-10,20,0,7); x.fill();
                        x.fillStyle='#E8002D'; x.font='900 24px Inter, Arial'; x.fillText('\u2713',202,y-1);
                        x.fillStyle='#fff'; x.font='600 30px Inter, Arial, sans-serif'; x.fillText(t,252,y);
                    });
                    // Link pill
                    x.fillStyle='#fff'; roundRect(x, W/2-300, 860, 600, 86, 43); x.fill();
                    x.fillStyle='#B8001F'; x.font='800 36px Inter, Arial, sans-serif'; x.textAlign='center';
                    x.fillText('www.avazonia.com/sell', W/2, 915);
                    cb(c);
                });
            }
            function downloadCanvas(c){
                c.toBlob(function(b){
                    var a=document.createElement('a'); a.href=URL.createObjectURL(b);
                    a.download='avazonia-vendor-invite.png'; a.click();
                    setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
                    if (imgFeedback){ imgFeedback.style.display='block'; setTimeout(function(){ imgFeedback.style.display='none'; }, 4000); }
                },'image/png');
            }
            var dlBtn=document.getElementById('dl-invite-img');
            if (dlBtn) dlBtn.addEventListener('click', function(){ drawInvite(downloadCanvas); });
        })();
        </script>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Shared Config
    const fontStack = "'Inter', system-ui, -apple-system, sans-serif";
    
    // 1. REVENUE TREND CHART
    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($revenueTrends, 'date_label')) ?>,
            datasets: [{
                label: 'Revenue (₵)',
                data: <?= json_encode(array_column($revenueTrends, 'total')) ?>,
                borderColor: '#000',
                backgroundColor: 'rgba(0,0,0,0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#000',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { family: fontStack, size: 10 } } },
                x: { grid: { display: false }, ticks: { font: { family: fontStack, size: 10 } } }
            }
        }
    });

    // 2. CATEGORY SALES CHART
    new Chart(document.getElementById('categorySalesChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($catSales, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($catSales, 'revenue')) ?>,
                backgroundColor: ['#000', '#333', '#666', '#999', '#ccc'],
                borderWidth: 0,
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: fontStack, size: 10, weight: '700' }, boxWidth: 12, padding: 20 } }
            },
            cutout: '70%'
        }
    });

});
</script>

</div>

<?php include 'layout/footer.php'; ?>
