<?php
// admin/report.php
// Generates a self-contained, offline traffic report (GA4 + first-party).
// Read-only: no orders, no accounts, no writes. ?download=1 sends it as a file.
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

// ── GA4: 28-day overview + 12 calendar months ────────────────────────────
$ga4Monthly = null;
$ga4Overview = null;
$ga4Error = '';
try {
    $client = Ga4Client::fromSettings();
    if ($client->isConfigured()) {
        $ga4Monthly = $client->fetchMonthly($months);
        if (empty($ga4Monthly['ok'])) {
            $ga4Error = (string)($ga4Monthly['error'] ?? 'Unknown analytics error.');
            $ga4Monthly = null;
        }
        $ga4Overview = $client->fetchOverview(28);
        if (empty($ga4Overview['ok'])) {
            if ($ga4Error === '') $ga4Error = (string)($ga4Overview['error'] ?? 'Unknown analytics error.');
            $ga4Overview = null;
        }
    } else {
        $ga4Error = $client->configError();
    }
} catch (Throwable $e) {
    $ga4Error = $e->getMessage();
}

// ── First-party tracker ──────────────────────────────────────────────────
$monthly = $summary = $daily7 = $pages7 = $refs7 = $uaRows = [];
try {
    $pv = new PageView();
    $monthly   = $pv->monthlySeries($months);
    $summary   = $pv->summary();
    $daily7    = $pv->dailySeries(7);
    $pages7    = $pv->topPages(7, 5);
    $refs7     = $pv->topReferrers(7, 5);
    $uaRows    = $pv->topUserAgents(30, 6);
} catch (Throwable $e) {
    error_log('[report] first-party query failed: ' . $e->getMessage());
}

$pvByMonth = [];
foreach ($monthly as $row) $pvByMonth[$row['month']] = $row;
$gByMonth = [];
if (is_array($ga4Monthly)) {
    foreach ($ga4Monthly['months'] as $row) $gByMonth[$row['month']] = $row;
}

$monthKeys = [];
for ($i = 0; $i < $months; $i++) {
    $monthKeys[] = date('Y-m', strtotime(date('Y-m-01') . ' -' . $i . ' months'));
}

$ga4SessionsTotal = 0;
$ga4ViewsTotal = 0;
foreach ($monthKeys as $m) {
    if (isset($gByMonth[$m])) {
        $ga4SessionsTotal += $gByMonth[$m]['sessions'];
        $ga4ViewsTotal    += $gByMonth[$m]['pageviews'];
    }
}

$h = fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$num = fn($v): string => number_format((float)$v);
$pct = function (float $cur, float $prev): array {
    if ($prev <= 0) return [null, ''];
    $p = (($cur - $prev) / $prev) * 100;
    return [round($p, 1), $p >= 0 ? 'up' : 'down'];
};
$mn = fn(string $m): string => date('M Y', strtotime($m . '-01'));

$generatedAt = date('j F Y, H:i');
$stamp = date('Y-m-d');
$filename = 'avazonia-traffic-report-' . $stamp . '.html';

if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
} else {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
}

$totalVisitors = $ga4Overview['totals']['visitors'] ?? null;
$sessions      = $ga4Overview['totals']['sessions'] ?? null;
$views         = $ga4Overview['totals']['pageviews'] ?? null;
$newVisitors   = $ga4Overview['totals']['new_visitors'] ?? null;
$engaged       = $ga4Overview['totals']['engaged'] ?? null;
$avgDuration   = $ga4Overview['totals']['avg_duration'] ?? null;

[$vDelta, $vDir] = $totalVisitors !== null ? $pct((float)$totalVisitors, (float)($ga4Overview['prev']['visitors'] ?? 0)) : [null, ''];
[$sDelta, $sDir] = $sessions !== null ? $pct((float)$sessions, (float)($ga4Overview['prev']['sessions'] ?? 0)) : [null, ''];
[$pDelta, $pDir] = $views !== null ? $pct((float)$views, (float)($ga4Overview['prev']['pageviews'] ?? 0)) : [null, ''];

$maxDay = 1;
foreach (($ga4Overview['daily'] ?? []) as $d) $maxDay = max($maxDay, (int)$d['pageviews']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Avazonia — Traffic Report, <?= $h($generatedAt) ?></title>
<style>
  :root{--ink:#111214;--mid:#6b7280;--line:#e6e7e9;--off:#f7f7f8;--red:#e8002d;--red-deep:#b8001f;--green:#0f9d58}
  *{box-sizing:border-box}
  body{margin:0;background:#f1f2f3;color:var(--ink);font:14px/1.55 -apple-system,"Segoe UI",Roboto,Arial,sans-serif}
  .toolbar{max-width:900px;margin:18px auto -10px;display:flex;justify-content:space-between;align-items:center;gap:12px}
  .toolbar a{font:700 11px/1 -apple-system,"Segoe UI",Arial,sans-serif;text-transform:uppercase;letter-spacing:.08em;text-decoration:none;padding:10px 16px;border-radius:8px}
  .toolbar .back{color:var(--mid);background:#fff;border:1px solid var(--line)}
  .toolbar .dl{color:#fff;background:var(--red)}
  .page{max-width:900px;margin:18px auto 28px;background:#fff;padding:44px 52px 52px;box-shadow:0 1px 3px rgba(0,0,0,.09)}
  h1{font-size:30px;line-height:1.05;letter-spacing:-.02em;margin:0 0 8px;text-transform:uppercase}
  h2{font-size:14px;text-transform:uppercase;letter-spacing:.07em;margin:38px 0 12px;padding-bottom:7px;border-bottom:2px solid var(--ink)}
  h3{font-size:12px;text-transform:uppercase;letter-spacing:.07em;color:var(--mid);margin:22px 0 8px}
  p{margin:8px 0}
  .kicker{display:inline-block;background:var(--red);color:#fff;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;padding:5px 11px;border-radius:999px}
  .meta{color:var(--mid);font-size:12px;margin-top:12px}.meta strong{color:var(--ink)}
  .cards{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:22px}
  .card{border:1px solid var(--line);border-radius:10px;padding:15px 16px}
  .card .label{font-size:9px;letter-spacing:.09em;text-transform:uppercase;color:var(--mid);font-weight:700}
  .card .value{font-size:25px;font-weight:800;letter-spacing:-.02em;margin-top:3px}
  .card .sub{font-size:11px;color:var(--mid);margin-top:2px}
  .up{color:var(--green);font-weight:700}.down{color:var(--red);font-weight:700}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  th,td{padding:9px 10px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}
  th{font-size:9.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mid);background:var(--off)}
  td.num,th.num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
  tr.total td{font-weight:800;background:#fafafa;border-top:2px solid var(--ink)}
  .muted{color:var(--mid)}
  .note{border:1px dashed var(--line);border-radius:9px;padding:13px 15px;font-size:12px;color:var(--mid);line-height:1.6;margin:16px 0}
  .note strong{color:var(--ink)}
  .alert{border:1px solid rgba(232,0,45,.25);background:#fff6f7;border-radius:9px;padding:13px 15px;font-size:12.5px;margin:16px 0}
  .bars{display:flex;align-items:flex-end;gap:3px;height:120px;border-bottom:1px solid var(--line);padding-top:8px}
  .bars .b{flex:1;background:linear-gradient(180deg,var(--red),var(--red-deep));border-radius:2px 2px 0 0;min-height:2px}
  .bars .b.partial{background:var(--mid)}
  .axis{display:flex;justify-content:space-between;font-size:10px;color:var(--mid);margin-top:6px}
  .hbars{display:grid;gap:8px;margin-top:6px}
  .hrow{display:grid;grid-template-columns:140px 1fr 58px;align-items:center;gap:10px;font-size:11.5px}
  .track{height:10px;background:#eee;border-radius:99px;overflow:hidden}
  .fill{height:100%;background:linear-gradient(90deg,var(--red),var(--red-deep));border-radius:99px}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:30px}
  ul{margin:8px 0 0;padding-left:19px}li{margin:7px 0}
  .tag{display:inline-block;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:2px 6px;border-radius:4px;background:var(--off);color:var(--mid)}
  .chip{display:inline-block;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 7px;border-radius:999px;background:#eef0f2;color:var(--mid)}
  .chip-bot{background:#fff1f0;color:var(--red)}
  footer{margin-top:40px;padding-top:13px;border-top:1px solid var(--line);font-size:11px;color:var(--mid)}
  @media (max-width:760px){.page{margin:0;padding:26px 20px}.cards{grid-template-columns:repeat(2,1fr)}.two{grid-template-columns:1fr;gap:22px}}
  @media print{body{background:#fff}.toolbar{display:none}.page{box-shadow:none;margin:0;padding:0;max-width:none}h2{page-break-after:avoid}table,.bars,.cards{page-break-inside:avoid}}
</style>
</head>
<body>

<?php if (!isset($_GET['download'])): ?>
<div class="toolbar">
    <a class="back" href="index.php">&larr; Dashboard</a>
    <a class="dl" href="?download=1">Download HTML</a>
</div>
<?php endif; ?>

<div class="page">
  <span class="kicker">Traffic report · generated by admin</span>
  <h1>Visitors &amp; Visits</h1>
  <div class="meta">
    Generated <strong><?= $h($generatedAt) ?></strong> from the live admin ·
    sources: Google Analytics 4 and the first-party server log · no order or account data.
  </div>

  <?php if (is_array($ga4Overview)): ?>
  <div class="cards">
    <div class="card">
      <div class="label">Visitors · 28 days</div>
      <div class="value"><?= $num($totalVisitors) ?></div>
      <div class="sub"><?php if ($vDelta !== null): ?><span class="<?= $h($vDir) ?>"><?= $vDelta >= 0 ? '▲' : '▼' ?> <?= $h(abs($vDelta)) ?>%</span> vs prev 28d<?php else: ?>&nbsp;<?php endif; ?></div>
    </div>
    <div class="card">
      <div class="label">Sessions · 28 days</div>
      <div class="value"><?= $num($sessions) ?></div>
      <div class="sub"><?php if ($sDelta !== null): ?><span class="<?= $h($sDir) ?>"><?= $sDelta >= 0 ? '▲' : '▼' ?> <?= $h(abs($sDelta)) ?>%</span> vs prev 28d<?php else: ?>&nbsp;<?php endif; ?></div>
    </div>
    <div class="card">
      <div class="label">Page views · 28 days</div>
      <div class="value"><?= $num($views) ?></div>
      <div class="sub"><?php if ($pDelta !== null): ?><span class="<?= $h($pDir) ?>"><?= $pDelta >= 0 ? '▲' : '▼' ?> <?= $h(abs($pDelta)) ?>%</span> vs prev 28d<?php else: ?>&nbsp;<?php endif; ?></div>
    </div>
    <div class="card">
      <div class="label">Engagement</div>
      <div class="value"><?= $sessions > 0 ? $h(round(((int)$engaged / (int)$sessions) * 100)) . '%' : '—' ?></div>
      <div class="sub"><?= $num($engaged) ?> engaged · <?= $h(Ga4Client::formatDuration((int)$avgDuration)) ?> avg</div>
    </div>
  </div>
  <?php else: ?>
  <div class="alert"><strong>Google Analytics unavailable.</strong> <?= $h($ga4Error !== '' ? $ga4Error : 'Google Analytics is not configured.') ?> The first-party sections below are unaffected.</div>
  <?php endif; ?>

  <h2>1. Daily traffic — last 28 days</h2>
  <?php if (is_array($ga4Overview) && !empty($ga4Overview['daily'])): ?>
  <div class="bars">
    <?php foreach ($ga4Overview['daily'] as $i => $d):
        $height = max(2, (int)round(($d['pageviews'] / $maxDay) * 100));
        $isToday = ($d['date'] === date('Y-m-d'));
    ?>
    <div class="b<?= $isToday ? ' partial' : '' ?>" style="height:<?= $height ?>%" title="<?= $h($d['date']) ?> · <?= $num($d['pageviews']) ?> views"></div>
    <?php endforeach; ?>
  </div>
  <div class="axis">
    <span><?= $h(date('j M', strtotime($ga4Overview['daily'][0]['date'] ?? 'now'))) ?></span>
    <span><?= $h(date('j M')) ?></span>
  </div>
  <?php else: ?>
  <p class="muted">No GA4 daily data available.</p>
  <?php endif; ?>

  <h2>2. Month by month</h2>
  <table>
    <thead>
      <tr><th>Month</th><th class="num">GA4 visitors</th><th class="num">GA4 sessions</th><th class="num">GA4 views</th><th class="num">1st-party visitors</th><th class="num">1st-party views</th></tr>
    </thead>
    <tbody>
      <?php foreach ($monthKeys as $m):
          $g = $gByMonth[$m] ?? null;
          $p = $pvByMonth[$m] ?? null;
          $current = ($m === date('Y-m'));
      ?>
      <tr>
        <td><?= $h($mn($m)) ?><?= $current ? ' <span class="tag">MTD</span>' : '' ?></td>
        <td class="num <?= $g ? '' : 'muted' ?>"><?= $g ? $num($g['visitors']) : '—' ?></td>
        <td class="num <?= $g ? '' : 'muted' ?>"><?= $g ? $num($g['sessions']) : '—' ?></td>
        <td class="num <?= $g ? '' : 'muted' ?>"><?= $g ? $num($g['pageviews']) : '—' ?></td>
        <td class="num <?= $p ? '' : 'muted' ?>"><?= $p ? $num($p['visitors']) : '—' ?></td>
        <td class="num <?= $p ? '' : 'muted' ?>"><?= $p ? $num($p['views']) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="total">
        <td>12-month total</td>
        <td class="num">—</td>
        <td class="num"><?= is_array($ga4Monthly) ? $num($ga4SessionsTotal) : '—' ?></td>
        <td class="num"><?= is_array($ga4Monthly) ? $num($ga4ViewsTotal) : '—' ?></td>
        <td class="num">—</td>
        <td class="num">—</td>
      </tr>
    </tbody>
  </table>
  <p class="muted" style="font-size:11.5px">Visitor counts are unique per month and not additive, so the total row omits them. First-party rows exclude User-Agents classified as automation.</p>

  <h2>3. Where visitors go</h2>
  <div class="two">
    <div>
      <h3>Top pages · 28 days (GA4)</h3>
      <?php if (is_array($ga4Overview) && !empty($ga4Overview['pages'])): ?>
      <table>
        <thead><tr><th>Page</th><th class="num">Views</th><th class="num">Visitors</th></tr></thead>
        <tbody>
          <?php foreach ($ga4Overview['pages'] as $pg): ?>
          <tr><td><?= $h($pg['path']) ?></td><td class="num"><?= $num($pg['views']) ?></td><td class="num"><?= $num($pg['visitors']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <p class="muted">No page data.</p>
      <?php endif; ?>
    </div>
    <div>
      <h3>Sessions by source · 28 days</h3>
      <?php if (is_array($ga4Overview) && !empty($ga4Overview['sources'])):
          $maxSrc = max(1, max(array_column($ga4Overview['sources'], 'sessions')));
      ?>
      <div class="hbars">
        <?php foreach ($ga4Overview['sources'] as $src): ?>
        <div class="hrow">
          <span><?= $h($src['source']) ?></span>
          <div class="track"><div class="fill" style="width:<?= (int)round(($src['sessions'] / $maxSrc) * 100) ?>%"></div></div>
          <span class="num"><?= $num($src['sessions']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p class="muted">No source data.</p>
      <?php endif; ?>
    </div>
  </div>

  <h2>4. First-party log — last 7 days</h2>
  <?php if (($summary['views_7d'] ?? 0) > 0): ?>
  <div class="cards" style="margin-top:0">
    <div class="card"><div class="label">Visitors today</div><div class="value"><?= $num($summary['today_visitors'] ?? 0) ?></div><div class="sub">partial day</div></div>
    <div class="card"><div class="label">Views today</div><div class="value"><?= $num($summary['today_views'] ?? 0) ?></div><div class="sub">partial day</div></div>
    <div class="card"><div class="label">Visitors 7d</div><div class="value"><?= $num($summary['visitors_7d'] ?? 0) ?></div><div class="sub">views <?= $num($summary['views_7d'] ?? 0) ?></div></div>
    <div class="card"><div class="label">Views / visitor</div><div class="value"><?= ($summary['visitors_7d'] ?? 0) > 0 ? $h(round(($summary['views_7d'] ?? 0) / $summary['visitors_7d'], 1)) : '0' ?></div><div class="sub">7-day average</div></div>
  </div>
  <?php if (!empty($daily7)): ?>
  <table style="margin-top:16px">
    <thead><tr><th>Day</th><th class="num">Views</th><th class="num">Visitors</th></tr></thead>
    <tbody>
      <?php foreach ($daily7 as $d): ?>
      <tr><td><?= $h(date('j M', strtotime($d['day']))) ?></td><td class="num"><?= $num($d['views']) ?></td><td class="num"><?= $num($d['visitors']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <?php else: ?>
  <p class="muted">No first-party traffic logged in the last 7 days.</p>
  <?php endif; ?>

  <?php if (!empty($uaRows)): ?>
  <h3>User-agent audit · 30 days</h3>
  <table>
    <thead><tr><th>User agent</th><th class="num">Views</th><th class="num">Visitors</th><th>Class</th></tr></thead>
    <tbody>
      <?php foreach ($uaRows as $u): ?>
      <tr>
        <td style="max-width:520px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:ui-monospace,Consolas,monospace;font-size:11px;" title="<?= $h($u['user_agent']) ?>"><?= $h($u['user_agent'] !== '' ? $u['user_agent'] : '(none)') ?></td>
        <td class="num"><?= $num($u['views']) ?></td>
        <td class="num"><?= $num($u['visitors']) ?></td>
        <td><?= $u['likely_bot'] ? '<span class="chip chip-bot">automated</span>' : '<span class="chip">browser</span>' ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <h2>5. Recommendations for visitors</h2>
  <p class="muted" style="font-size:12px">Generated from this period's traffic; each item maps to a number above.</p>
  <ul>
    <li><b>Make /shop self-explanatory above the fold.</b> When most visitors land in the catalogue, visible category chips, price bands and condition filters are the difference between a browse and a bounce.</li>
    <li><b>Put trust next to the price.</b> Verification badge, seller rating, return policy and a WhatsApp contact on every product page answer “can I trust this?” before the visitor leaves.</li>
    <li><b>Keep the first screen fast and single-purpose.</b> With most visitors new and sessions around a minute, one clear action (browse, search, chat) beats a wall of promotions.</li>
    <li><b>Add a “similar items / recently viewed” rail.</b> Low pages-per-session means visitors rarely compare; a related-items row turns one-page visits into comparisons.</li>
    <li><b>Stay readable to AI and search referrals.</b> Keep price, availability, year, mileage and location in the page markup so an assistant or search result can link to the matching listing.</li>
    <li><b>Measure mobile speed before adding anything.</b> Fast first paint on a phone underpins every recommendation above; treat mobile LCP as a visitor-facing metric.</li>
  </ul>

  <footer>
    Traffic only — order and account data are deliberately excluded. GA4 figures may be cached for up to 15 minutes;
    today's first-party figures are partial. Report generated by <code>admin/report.php</code>
    on <?= $h(date('j M Y, H:i')) ?>.
  </footer>
</div>
</body>
</html>
