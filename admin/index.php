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

// 6. RECENT ACTIVITY
$recentOrders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

$title = "Dashboard Insights — Avazonia";
include 'layout/header.php';
?>

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
    
    <div style="display: flex; flex-direction: column; gap: 40px;">
        <!-- CATEGORY SALES (CSS CHART) -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Sales by Category</div>
            </div>
            <div style="padding: 40px;">
                <div class="chart-container">
                    <?php 
                    $maxRev = !empty($catSales) ? $catSales[0]['revenue'] : 1;
                    foreach ($catSales as $cs): 
                        $pct = ($cs['revenue'] / $maxRev) * 100;
                    ?>
                    <div class="bar-row">
                        <div style="font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;"><?= $cs['name'] ?></div>
                        <div class="bar-bg" style="background: #f0f0f0; height: 16px; border-radius: 0;">
                            <div class="bar-fill" style="width: <?= $pct ?>%; background: var(--ink); border-radius: 0;"></div>
                        </div>
                        <div style="font-family: var(--f-mono); font-size: 12px; font-weight: 700; text-align: right;">₵<?= number_format($cs['revenue'], 0) ?></div>
                    </div>
                    <?php endforeach; ?>
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

    <!-- RIGHT SIDEBAR (LEADERBOARD) -->
    <div style="display: flex; flex-direction: column; gap: 40px;">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">Product Leaderboard</div>
            </div>
            <div style="padding: 32px;">
                <?php foreach ($topProducts as $idx => $tp): ?>
                <div class="leaderboard-item" style="position: relative; overflow: hidden;">
                    <div style="display: flex; gap: 20px; align-items: center; position: relative; z-index: 2;">
                        <span style="font-family: var(--f-display); font-size: 48px; font-weight: 900; color: var(--ink); opacity: 0.05; position: absolute; left: -10px; top: 50%; transform: translateY(-50%); pointer-events: none;">0<?= $idx + 1 ?></span>
                        <div style="padding-left: 20px;">
                            <div style="font-size: 13px; font-weight: 900; line-height: 1.2; margin-bottom: 4px; text-transform: uppercase; letter-spacing: -0.01em;"><?= $tp['name'] ?></div>
                            <div style="font-family: var(--f-mono); font-size: 10px; font-weight: 700; color: var(--mid-gray); text-transform: uppercase;"><?= $tp['total_sold'] ?> Units Sold</div>
                        </div>
                    </div>
                    <div style="text-align: right; position: relative; z-index: 2;">
                        <div style="font-family: var(--f-display); font-size: 14px; font-weight: 900;">₵<?= number_format($tp['price_ghs'], 0) ?></div>
                        <div style="font-family: var(--f-mono); font-size: 10px; color: <?= $tp['stock_qty'] < 5 ? 'var(--red)' : '#00a854' ?>; font-weight: 700; text-transform: uppercase;">Stock: <?= $tp['stock_qty'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
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

<?php include 'layout/footer.php'; ?>
