<?php
// admin/index.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$db = db();

// 1. REVENUE & GROWTH (DYNAMIC)
$curMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') AND status NOT IN ('cancelled', 'failed')")->fetchColumn() ?: 0;
$lastMonthRev = $db->query("SELECT SUM(total_ghs) FROM orders WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') AND status NOT IN ('cancelled', 'failed')")->fetchColumn() ?: 1; // Prevent div by 0

$revGrowth = (($curMonthRev - $lastMonthRev) / $lastMonthRev) * 100;

// 2. ORDER TRENDS
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$curMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
$lastMonthOrders = $db->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn() ?: 1;
$orderGrowth = (($curMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100;

// 3. AOV (AVERAGE ORDER VALUE)
$aov = $totalOrders > 0 ? ($db->query("SELECT SUM(total_ghs) FROM orders WHERE status NOT IN ('cancelled', 'failed')")->fetchColumn() / $totalOrders) : 0;

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
$revenueTrends = $db->query("
    SELECT DATE_FORMAT(created_at, '%b %d') as date_label, SUM(total_ghs) as total
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    AND status NOT IN ('cancelled', 'failed')
    GROUP BY DATE(created_at)
    ORDER BY created_at ASC
")->fetchAll();

// 7. RECENT ACTIVITY
$recentOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

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
        border: 2px solid var(--ink); padding: 32px; position: relative; overflow: hidden;
        display: flex; flex-direction: column; gap: 8px;
    }
    .stat-card-bold .label { font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; }
    .stat-card-bold .value { font-family: var(--f-display); font-size: 32px; font-weight: 800; letter-spacing: -0.02em; }
    .trend-indicator { font-family: var(--f-mono); font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 4px; }
    .trend-up { color: #00a854; }
    .trend-down { color: var(--red); }
    
    .chart-container { display: flex; flex-direction: column; gap: 24px; margin-top: 24px; }
    .bar-row { display: grid; grid-template-columns: 140px 1fr 80px; align-items: center; gap: 16px; }
    .bar-bg { height: 12px; background: #eee; border-radius: 2px; overflow: hidden; }
    .bar-fill { height: 100%; background: var(--ink); border-radius: 2px; }
    
    .leaderboard-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--light-gray); }
    .leaderboard-item:last-child { border: none; }
    
    .dashboard-layout { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 40px; align-items: start; }
    @media (max-width: 1024px) { .dashboard-layout { grid-template-columns: 1fr; } }
</style>

<div class="admin-header" style="margin-bottom: 48px;">
    <div style="display: flex; flex-direction: column; gap: 8px;">
        <h1 class="insights-title">Performance<br>Insights</h1>
        <div style="font-family: var(--f-mono); font-size: 11px; color: var(--mid-gray); margin-top: 12px;">Unified Intelligence Engine • Active Tracking</div>
    </div>
</div>

<style>
    .insights-title { font-size: clamp(38px, 8vw, 64px); line-height: 0.9; margin: 0; letter-spacing: -0.04em; }
    @media (max-width: 600px) { .insights-title { font-size: 38px; } }
</style>

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

<div class="dashboard-layout">
    
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

    <!-- RIGHT SIDEBAR (LEADERBOARD CHART) -->
    <div style="display: flex; flex-direction: column; gap: 40px;">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Product Leaderboard</div>
            </div>
            <div style="padding: 32px; min-height: 400px;">
                <canvas id="productLeaderboardChart"></canvas>
                
                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($topProducts as $idx => $tp): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 11px; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                            <span style="font-weight: 700; color: var(--mid-gray);">#<?= $idx+1 ?> <?= htmlspecialchars($tp['name']) ?></span>
                            <span style="font-family: var(--f-mono);"><?= $tp['total_sold'] ?> sold</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><div class="panel-title">Strategic Actions</div></div>
            <div style="padding: 32px; display: flex; flex-direction: column; gap: 16px;">
                <a href="add-product.php" class="btn-ink" style="width: 100%; justify-content: center; height: 50px; font-weight: 900; border-radius: 0;">DEPLOY NEW DROP</a>
                <a href="products.php" class="btn-ink" style="width: 100%; justify-content: center; height: 50px; font-weight: 900; border-radius: 0; background: transparent; color: var(--ink); border: 2px solid var(--ink);">INVENTORY CONTROL</a>
            </div>
        </div>
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

    // 3. PRODUCT LEADERBOARD CHART
    new Chart(document.getElementById('productLeaderboardChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($p) => strlen($p['name']) > 15 ? substr($p['name'], 0, 15) . '...' : $p['name'], $topProducts)) ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?= json_encode(array_column($topProducts, 'total_sold')) ?>,
                backgroundColor: '#000',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { display: false }, ticks: { font: { family: fontStack, size: 10 } } },
                y: { grid: { display: false }, ticks: { font: { family: fontStack, size: 10, weight: '700' } } }
            }
        }
    });
});
</script>

</div>

<?php include 'layout/footer.php'; ?>
