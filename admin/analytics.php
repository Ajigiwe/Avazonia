<?php
// admin/analytics.php
// Read-only monthly traffic report: GA4 sessions/views grouped by calendar
// month, alongside the first-party page_views log. No writes, no forms.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

require_once __DIR__ . '/../core/Ga4Client.php';
require_once __DIR__ . '/../models/PageView.php';

$months = 12;

// 1. GA4 — calendar-month totals. Fails soft: the page still renders the
//    first-party table when Google is unconfigured or unreachable.
$ga4 = null;
$ga4Error = '';
$ga4Configured = false;
try {
    $client = Ga4Client::fromSettings();
    $ga4Configured = $client->isConfigured();
    if ($ga4Configured) {
        $ga4 = $client->fetchMonthly($months);
        if (empty($ga4['ok'])) {
            $ga4Error = (string)($ga4['error'] ?? 'Unknown analytics error.');
            $ga4 = null;
        }
    } else {
        $ga4Error = $client->configError();
    }
} catch (Throwable $e) {
    $ga4Error = $e->getMessage();
}

// 2. First-party tracker — same calendar months, distinct visitors per month.
$pvRows = [];
$uaRows = [];
$pvError = '';
try {
    $pvModel = new PageView();
    $pvRows = $pvModel->monthlySeries($months);
    $uaRows = $pvModel->topUserAgents(30, 8);
} catch (Throwable $e) {
    $pvError = $e->getMessage();
}

$ga4ByMonth = [];
if (is_array($ga4)) {
    foreach ($ga4['months'] as $row) $ga4ByMonth[$row['month']] = $row;
}
$pvByMonth = [];
foreach ($pvRows as $row) $pvByMonth[$row['month']] = $row;

// 3. Every calendar month in the window, newest first, so gaps stay visible.
$monthKeys = [];
for ($i = 0; $i < $months; $i++) {
    // Anchor to the 1st so short months never skip or repeat a label.
    $monthKeys[] = date('Y-m', strtotime(date('Y-m-01') . ' -' . $i . ' months'));
}

$ga4ViewsTotal = 0;
$ga4SessionsTotal = 0;
$pvViewsTotal = 0;
$peak = null;
foreach ($monthKeys as $m) {
    if (isset($ga4ByMonth[$m])) {
        $ga4ViewsTotal    += $ga4ByMonth[$m]['pageviews'];
        $ga4SessionsTotal += $ga4ByMonth[$m]['sessions'];
        if ($peak === null || $ga4ByMonth[$m]['pageviews'] > $peak['views']) {
            $peak = ['month' => $m, 'views' => $ga4ByMonth[$m]['pageviews']];
        }
    }
    if (isset($pvByMonth[$m])) {
        $pvViewsTotal += $pvByMonth[$m]['views'];
        if ($peak === null && $pvByMonth[$m]['views'] > 0) {
            $peak = ['month' => $m, 'views' => $pvByMonth[$m]['views']];
        }
    }
}

$title = "Monthly Analytics — Avazonia";
include 'layout/header.php';
?>

<style>
    .ma-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
    @media (max-width: 1100px) { .ma-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .ma-grid { grid-template-columns: 1fr; } }
    .ma-card { background: #fff; border: 1px solid var(--light-gray); border-radius: 16px; padding: 22px 24px; box-shadow: 0 1px 3px rgba(13,13,13,.04); }
    .ma-card .label { font-family: var(--f-mono); font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--mid-gray); }
    .ma-card .value { font-family: var(--f-display); font-weight: 800; font-size: 28px; letter-spacing: -.02em; margin-top: 6px; }
    .ma-card .sub { font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); margin-top: 4px; }
    .ma-table-wrap { overflow-x: auto; }
    .ma-table { width: 100%; border-collapse: collapse; min-width: 720px; }
    .ma-table th { padding: 14px 20px; background: var(--off); text-align: left; font-family: var(--f-semi); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: .08em; }
    .ma-table th.num, .ma-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .ma-table td { padding: 14px 20px; border-bottom: 1px solid var(--light-gray); font-size: 13px; }
    .ma-table tr:last-child td { border-bottom: none; }
    .ma-table tr.ma-total td { background: #fafafa; font-weight: 800; border-top: 2px solid var(--ink); }
    .ma-table td.ma-month { font-family: var(--f-mono); font-size: 12px; font-weight: 700; white-space: nowrap; }
    .ma-table td.ma-empty { color: #b0b0b0; }
    .ma-split { font-family: var(--f-mono); font-size: 9px; color: var(--mid-gray); display: block; letter-spacing: .04em; }
    .ma-note { border: 1px dashed var(--light-gray); border-radius: 12px; padding: 16px 20px; font-size: 12px; color: var(--mid-gray); line-height: 1.6; margin-top: 18px; }
    .ma-chip { display: inline-block; font-family: var(--f-mono); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; padding: 3px 8px; border-radius: 999px; background: #eef0f2; color: var(--mid-gray); white-space: nowrap; }
    .ma-chip-bot { background: #fff1f0; color: var(--red); }
    .ma-ua { max-width: 520px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--f-mono); font-size: 11px; }
    .ma-note strong { color: var(--ink); }
    .ma-alert { border: 1px solid rgba(232,0,45,.25); background: #fff6f7; border-radius: 12px; padding: 16px 20px; font-size: 12.5px; margin-bottom: 24px; }
    .ma-alert strong { font-family: var(--f-mono); font-size: 10px; letter-spacing: .08em; color: var(--red); text-transform: uppercase; display: block; margin-bottom: 4px; }
</style>

<div class="admin-hero" style="margin-bottom: 32px;">
    <div>
        <div class="hero-kicker">&#9632; Read-only report</div>
        <div class="hero-title">Monthly Analytics</div>
        <div class="hero-sub">GA4 + first-party traffic, grouped by calendar month &middot; last <?= (int)$months ?> months</div>
    </div>
</div>

<div class="ma-grid">
    <div class="ma-card">
        <div class="label">GA4 page views</div>
        <div class="value"><?= is_array($ga4) ? number_format($ga4ViewsTotal) : '—' ?></div>
        <div class="sub"><?= (int)$months ?>-month total</div>
    </div>
    <div class="ma-card">
        <div class="label">GA4 sessions</div>
        <div class="value"><?= is_array($ga4) ? number_format($ga4SessionsTotal) : '—' ?></div>
        <div class="sub"><?= (int)$months ?>-month total</div>
    </div>
    <div class="ma-card">
        <div class="label">First-party views</div>
        <div class="value"><?= number_format($pvViewsTotal) ?></div>
        <div class="sub">server log, bots excluded</div>
    </div>
    <div class="ma-card">
        <div class="label">Peak month</div>
        <div class="value"><?= $peak ? htmlspecialchars(date('M Y', strtotime($peak['month'] . '-01'))) : '—' ?></div>
        <div class="sub"><?= $peak ? number_format($peak['views']) . ' views' : 'no data yet' ?></div>
    </div>
</div>

<?php if (!is_array($ga4)): ?>
<div class="ma-alert">
    <strong>Google Analytics unavailable</strong>
    <?= htmlspecialchars($ga4Error !== '' ? $ga4Error : 'Google Analytics is not configured.') ?>
    <?php if (!$ga4Configured): ?>
    <div style="margin-top:6px;">Connect a GA4 property in <a href="settings.php#social" style="color:var(--red);font-weight:700;">Settings &rarr; Social &amp; SEO</a> to populate the GA4 columns. The first-party columns below are unaffected.</div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($pvError !== ''): ?>
<div class="ma-alert">
    <strong>First-party tracker unavailable</strong>
    <?= htmlspecialchars($pvError) ?>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Calendar-month traffic</div>
        <span style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">
            <?= is_array($ga4) ? 'GA4 ' . htmlspecialchars($ga4['start']) . ' → ' . htmlspecialchars($ga4['end']) : 'GA4 not connected' ?>
        </span>
    </div>
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="num">GA4 visitors</th>
                    <th class="num">GA4 sessions</th>
                    <th class="num">GA4 views</th>
                    <th class="num">1st-party visitors</th>
                    <th class="num">1st-party views</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthKeys as $m):
                    $g = $ga4ByMonth[$m] ?? null;
                    $p = $pvByMonth[$m] ?? null;
                    $isCurrent = ($m === date('Y-m'));
                ?>
                <tr>
                    <td class="ma-month">
                        <?= htmlspecialchars(date('M Y', strtotime($m . '-01'))) ?>
                        <?php if ($isCurrent): ?><span class="ma-split">month to date</span><?php endif; ?>
                    </td>
                    <td class="num <?= $g ? '' : 'ma-empty' ?>"><?= $g ? number_format($g['visitors']) : '—' ?></td>
                    <td class="num <?= $g ? '' : 'ma-empty' ?>"><?= $g ? number_format($g['sessions']) : '—' ?></td>
                    <td class="num <?= $g ? '' : 'ma-empty' ?>"><?= $g ? number_format($g['pageviews']) : '—' ?></td>
                    <td class="num <?= $p ? '' : 'ma-empty' ?>"><?= $p ? number_format($p['visitors']) : '—' ?></td>
                    <td class="num <?= $p ? '' : 'ma-empty' ?>"><?= $p ? number_format($p['views']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="ma-total">
                    <td>Total</td>
                    <td class="num">—</td>
                    <td class="num"><?= is_array($ga4) ? number_format($ga4SessionsTotal) : '—' ?></td>
                    <td class="num"><?= is_array($ga4) ? number_format($ga4ViewsTotal) : '—' ?></td>
                    <td class="num">—</td>
                    <td class="num"><?= number_format($pvViewsTotal) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="panel" style="margin-top: 24px;">
    <div class="panel-header">
        <div class="panel-title">User-agent audit (30 days)</div>
        <span style="font-family:var(--f-mono);font-size:10px;color:var(--mid-gray);">raw log &middot; automation included</span>
    </div>
    <div class="ma-table-wrap">
        <table class="ma-table" style="min-width: 640px;">
            <thead>
                <tr><th>User agent</th><th class="num">Views</th><th class="num">Visitors</th><th>Class</th></tr>
            </thead>
            <tbody>
                <?php if (empty($uaRows)): ?>
                <tr><td colspan="4" class="ma-empty">No page views logged yet.</td></tr>
                <?php else: foreach ($uaRows as $u): ?>
                <tr>
                    <td class="ma-ua" title="<?= htmlspecialchars($u['user_agent']) ?>"><?= htmlspecialchars($u['user_agent'] !== '' ? $u['user_agent'] : '(no user agent)') ?></td>
                    <td class="num"><?= number_format($u['views']) ?></td>
                    <td class="num"><?= number_format($u['visitors']) ?></td>
                    <td><?= $u['likely_bot'] ? '<span class="ma-chip ma-chip-bot">automated</span>' : '<span class="ma-chip">browser</span>' ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ma-note">
    <strong>How these numbers are counted.</strong>
    GA4 rows are calendar months in the property time zone; the current month is month-to-date.
    First-party rows come from the server-side <code>page_views</code> log (bots and signed-in staff excluded) and are
    distinct visitor cookies within each month. Visitor columns are deliberately not totalled: users can appear in several
    months, so a sum would not be a unique-user count. GA4 data is cached for 15 minutes; first-party rows are live
    and retained for 180 days. Rows whose User-Agent is obvious automation are rejected on write and filtered out of
    every first-party figure above; the audit table shows them anyway so the gap stays visible.
</div>

<?php include 'layout/footer.php'; ?>
