<?php
/**
 * AVAZONIA DEPLOYMENT UTILITY  —  TEMPORARY BRIDGE
 * =================================================
 * Syncs the live server's working tree to the GitHub `main` branch.
 *
 * ⚠️  TEMPORARY. This exists only because the host has no SSH access.
 *     Delete this file (and re-block it in .htaccess) once the permanent
 *     deploy engine is in place.
 *
 * The ORIGINAL version of this file was unauthenticated: anyone on the
 * internet who guessed the URL could run `git reset --hard origin/main`
 * on production. This version requires:
 *   1. An authenticated admin session
 *   2. A valid CSRF token
 *   3. Re-entry of the admin password (step-up auth)
 *   4. Typing the confirmation phrase
 *
 * It additionally REFUSES to run when doing so would destroy data:
 *   - refuses if `.env` is tracked by git (would clobber prod credentials)
 *   - refuses if the remote origin is not the expected GitHub repo
 *   - refuses if tracked files carry local edits, unless explicitly forced
 *     (git reset --hard destroys local edits to tracked files; it does NOT
 *      touch untracked/ignored files, so .env and uploads are safe)
 *
 * It NEVER runs `git clean` — that would delete untracked files including .env.
 */

/**
 * ⚠️  Order matters. Load Session.php and start the session BEFORE anything
 * that could emit output. `config/app.php` ends with `?>` plus a trailing
 * newline, which PHP sends as output; on a host with output_buffering off
 * that makes headers_sent() true, and Session::start() then silently skips
 * session_start(). Result: an empty session and a spurious 403 even though
 * the administrator IS logged in.
 */
require_once __DIR__ . '/core/Session.php';
Session::start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/Cache.php';
require_once __DIR__ . '/models/Logger.php';
require_once __DIR__ . '/models/User.php';

// ─── CONFIGURATION ────────────────────────────────────────────────────────
const DEPLOY_ORIGIN   = 'https://github.com/Ajigiwe/Avazonia.git';
const DEPLOY_BRANCH   = 'main';
const DEPLOY_LOCK_AFTER = 5;   // failed password attempts before lockout
const DEPLOY_LOCK_MINS  = 15;

// ─── HELPERS ──────────────────────────────────────────────────────────────

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function shell_available(): bool {
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (in_array('shell_exec', $disabled, true)) return false;
    return function_exists('shell_exec');
}

/**
 * Run a git command anchored at the app root.
 * Always redirects stderr so failures are visible rather than silent.
 */
function git(string $args): string {
    if (!shell_available()) return '';
    $cmd = 'git -C ' . escapeshellarg(__DIR__) . ' ' . $args . ' 2>&1';
    $out = @shell_exec($cmd);
    return is_string($out) ? trim($out) : '';
}

function deploy_block(string $reason): void {
    Logger::log('DEPLOY_BLOCKED', 'Deployment refused: ' . $reason);
    http_response_code(403);
    echo '<h1>Deployment blocked</h1><p>' . e($reason) . '</p>';
    exit;
}

// ─── 1. AUTHENTICATED ADMIN SESSION REQUIRED ──────────────────────────────

header('Content-Type: text/html; charset=utf-8');

if (Session::get('user_role') !== 'admin') {
    http_response_code(403);

    // Diagnostic: an empty session can mean "never logged in", OR a session
    // that failed to start (output sent first / cookie not round-tripping).
    // Report which, so this screen is not a dead end.
    $sid = (string)session_id();
    $cookieName = session_name();
    $cookieSent = isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '';
    $cookieMatches = ($sid !== '' && $cookieSent && hash_equals($sid, (string)$_COOKIE[$cookieName]));

    $appHost = defined('APP_URL') ? strtolower((string)parse_url(APP_URL, PHP_URL_HOST)) : '';
    $reqHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    $hostMatch = ($appHost !== '' && $reqHost !== '' && strcasecmp($appHost, $reqHost) === 0);

    $statusMap = [
        PHP_SESSION_DISABLED => 'disabled',
        PHP_SESSION_NONE     => 'not started',
        PHP_SESSION_ACTIVE   => 'active',
    ];
    $status = $statusMap[session_status()] ?? (string)session_status();
    $diag = [
        'session status'     => $status . ($status === 'active' ? ' ✓' : '  ← session did NOT start'),
        'session id'         => $sid !== '' ? substr($sid, 0, 8) . '…' : '(empty)',
        "cookie $cookieName" => $cookieSent ? 'received ✓' : 'NOT SENT by browser',
        'cookie matches id'  => $cookieMatches ? 'yes ✓'
                              : 'NO — browser sent a different session id (its own empty session)',
        'session keys'       => $_SESSION ? implode(', ', array_keys($_SESSION)) : '(none)',
        'user_role'          => var_export(Session::get('user_role'), true),
        'headers_sent'       => headers_sent($hFile, $hLine) ? "yes ({$hFile}:{$hLine})" : 'no',
        'APP_URL'            => defined('APP_URL') ? APP_URL : '(undefined)',
        'requested host'     => $reqHost !== '' ? $reqHost : '(none)',
        'host match'         => $hostMatch ? $appHost . ' ✓'
                              : 'MISMATCH: APP_URL is ' . ($appHost ?: '(unset)') . ' but you are on '
                                . ($reqHost ?: '(unset)') . ' — session cookies do not cross hosts',
    ];

    // The fix, offered as a link the moment the hosts disagree.
    $suggested = '';
    if (!$hostMatch && $appHost !== '') {
        $scheme = defined('APP_URL') ? ((string)parse_url(APP_URL, PHP_URL_SCHEME) ?: 'https') : 'https';
        $path   = (string)($_SERVER['REQUEST_URI'] ?? '/deploy.php');
        $suggested = '<p style="font-family:system-ui,sans-serif;font-size:15px;background:#fff5f5;'
                   . 'border-left:4px solid #e53935;padding:14px 18px"><strong>Open this instead:</strong> '
                   . '<a href="' . e($scheme . '://' . $appHost . $path) . '">'
                   . e($scheme . '://' . $appHost . $path) . '</a></p>';
    }

    $rows = '';
    foreach ($diag as $k => $v) {
        $bad = (strpos((string)$v, '←') !== false
                || strpos((string)$v, 'NO —') === 0
                || strpos((string)$v, 'MISMATCH') === 0
                || $v === 'NOT SENT by browser'
                || $v === '(none)' || $v === '(empty)');
        $style = 'padding:5px 24px 5px 0;border-bottom:1px solid #eee;'
               . ($bad ? 'color:#e53935;font-weight:700' : 'color:#00a854');
        $rows .= '<tr><td style="' . $style . '">' . e($k) . '</td>'
               . '<td style="padding:5px 0;border-bottom:1px solid #eee;'
               . ($bad ? 'color:#e53935;font-weight:700' : 'color:#00a854') . '">'
               . e($v) . '</td></tr>';
    }

    exit('<h1 style="font-family:system-ui,sans-serif">403 — Forbidden</h1>'
       . $suggested
       . '<p style="font-family:system-ui,sans-serif;font-size:14px">'
       . 'An authenticated administrator session is required. '
       . 'If you are already logged into the <a href="' . e(APP_URL) . '/login">admin panel</a> in this browser, '
       . 'reload this page. If you are not, log in there first, then come back.</p>'
       . '<p style="font-family:system-ui,sans-serif;font-size:13px;font-weight:700">'
       . 'If any row below is red, the session is not reaching this script — send me this output.</p>'
       . '<table style="border-collapse:collapse;font:13px/1.6 ui-monospace,monospace">'
       . '<tr><th align="left" style="padding:6px 24px 6px 0;border-bottom:1px solid #ccc">check</th>'
       . '<th align="left" style="padding:6px 0;border-bottom:1px solid #ccc">result</th></tr>'
       . $rows . '</table>');
}

// ─── 2. PREFLIGHT (read-only) ─────────────────────────────────────────────

$pf = [];
$pf['shell']       = shell_available();
$pf['git_version'] = $pf['shell'] ? git('--version') : '';
$pf['git_ok']      = (bool)preg_match('/^git version/i', $pf['git_version']);
$pf['is_repo']     = $pf['git_ok'] && git('rev-parse --is-inside-work-tree') === 'true';

if ($pf['git_ok'] && $pf['is_repo']) {
    $pf['branch']     = git('rev-parse --abbrev-ref HEAD');
    $pf['head']       = git('rev-parse --short HEAD');
    $pf['remote_url'] = git('remote get-url origin');
    $pf['origin_ok']  = stripos($pf['remote_url'], DEPLOY_ORIGIN) !== false;

    // `.env` must never be tracked — if it were, reset --hard would replace
    // the live credentials with the (nonexistent) repo copy.
    $trackedFiles = array_filter(explode("\n", git('ls-files')));
    $pf['env_tracked'] = in_array('.env', $trackedFiles, true);

    // Fetch so we can diff against the remote. Any output here is an error.
    $pf['fetch_output'] = git('fetch origin --prune');

    $pf['origin_sha'] = git('rev-parse --short origin/' . DEPLOY_BRANCH);
    $pf['is_sha']     = (bool)preg_match('/^[0-9a-f]{7,40}$/i', $pf['origin_sha']);

    if ($pf['is_sha']) {
        $pf['incoming_count'] = git('rev-list --count HEAD..origin/' . DEPLOY_BRANCH);
        $pf['incoming_log']   = git('log --oneline --no-decorate -12 HEAD..origin/' . DEPLOY_BRANCH);
        $pf['diff_stat']      = git('diff --stat --stat-count=40 HEAD origin/' . DEPLOY_BRANCH);
        $pf['behind_count']   = git('rev-list --count origin/' . DEPLOY_BRANCH . '..HEAD');
    }

    // Local edits to TRACKED files would be destroyed by reset --hard.
    // Untracked entries are untouched by reset, so they are skipped.
    $pf['local_changes'] = [];
    foreach (array_filter(explode("\n", git('status --porcelain'))) as $line) {
        $status = substr($line, 0, 2);
        if (strpos($status, '??') !== false) continue;
        $pf['local_changes'][] = $line;
    }

    // `status --porcelain` compares against the LOCAL head, but reset --hard
    // writes the REMOTE's version. A file that differs from the local head yet
    // already matches origin/main comes out byte-identical — nothing is lost.
    // Only files that ALSO differ from origin/main are genuinely at risk, and
    // only those should demand a force-acknowledge. This matters when an admin
    // re-uploads a file by hand that is already committed to the remote.
    $differsFromRemote = [];
    if ($pf['is_sha']) {
        foreach (array_filter(explode("\n", git('diff --name-only origin/' . DEPLOY_BRANCH))) as $f) {
            $differsFromRemote[trim($f)] = true;
        }
    }
    $pf['lossy_changes'] = [];
    $pf['harmless_changes'] = [];
    foreach ($pf['local_changes'] as $line) {
        $file = preg_replace('/^[ MARCDTU]{1,2}/', '', $line);
        if (strpos($file, '->') !== false) {                 // rename: keep the new path
            $parts = explode('->', $file);
            $file = end($parts);
        }
        $file = trim($file);
        if (isset($differsFromRemote[$file])) $pf['lossy_changes'][]    = $line;
        else                                  $pf['harmless_changes'][] = $line;
    }
} else {
    $pf['fetch_output'] = '';
    $pf['local_changes'] = [];
    $pf['lossy_changes'] = [];
    $pf['harmless_changes'] = [];
    $pf['origin_ok'] = $pf['env_tracked'] = $pf['is_sha'] = false;
}

// ─── 3. DEPLOY ACTION ─────────────────────────────────────────────────────

$deployResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deploy') {

    if (!Csrf::validateRequest()) {
        deploy_block('CSRF token invalid or missing. Reload the page and retry.');
    }

    // ── Step-up auth: re-enter the admin password ──
    $lockedUntil = (int)(Session::get('deploy_locked_until') ?? 0);
    if ($lockedUntil > time()) {
        $mins = (int)ceil(($lockedUntil - time()) / 60);
        deploy_block("Too many failed password attempts. Locked for {$mins} more minute(s).");
    }

    $password = (string)($_POST['password'] ?? '');
    $userModel = new User();
    $admin = $userModel->findById((int)Session::get('user_id'));

    if ($password === '' || !$admin || !password_verify($password, $admin['password_hash'])) {
        $fails = (int)(Session::get('deploy_fail_count') ?? 0) + 1;
        Session::set('deploy_fail_count', $fails);
        if ($fails >= DEPLOY_LOCK_AFTER) {
            Session::set('deploy_locked_until', time() + DEPLOY_LOCK_MINS * 60);
            Session::set('deploy_fail_count', 0);
            deploy_block('Too many failed password attempts. Locked for ' . DEPLOY_LOCK_MINS . ' minutes.');
        }
        deploy_block('Incorrect admin password (' . (DEPLOY_LOCK_AFTER - $fails) . ' attempt(s) left).');
    }
    Session::set('deploy_fail_count', 0);

    // ── Typed confirmation phrase ──
    if (trim((string)($_POST['confirm_phrase'] ?? '')) !== 'RESET') {
        deploy_block("Confirmation phrase must be exactly RESET.");
    }

    // ── Refuse destructive preconditions ──
    if (!shell_available()) deploy_block('shell_exec() is disabled on this server — git cannot be run.');
    if (!$pf['git_ok'])      deploy_block('The git binary is not available on this server.');
    if (!$pf['is_repo'])     deploy_block(__DIR__ . ' is not a git repository.');
    if (!$pf['origin_ok'])   deploy_block('Remote origin (' . ($pf['remote_url'] ?? 'none') . ') does not match ' . DEPLOY_ORIGIN);
    if ($pf['env_tracked'])  deploy_block('.env is tracked by git — reset --hard would destroy the live credentials.');
    if (!empty($pf['lossy_changes']) && empty($_POST['force'])) {
        deploy_block(count($pf['lossy_changes']) . ' local edit(s) differ from the remote and would be overwritten '
                   . 'by reset --hard. These cannot be recovered. Tick "I understand" to force.');
    }

    // ── Execute ──
    $before = $pf['head'] ?? '?';
    $fetchOut = git('fetch origin --prune');
    $target = git('rev-parse origin/' . DEPLOY_BRANCH);

    if (!preg_match('/^[0-9a-f]{40}$/i', $target)) {
        deploy_block('git fetch failed — could not resolve origin/' . DEPLOY_BRANCH . '. Output: ' . $fetchOut);
    }

    $resetOut = git('reset --hard origin/' . DEPLOY_BRANCH);
    $after = git('rev-parse HEAD');
    $afterShort = git('rev-parse --short HEAD');

    if (strcasecmp($after, $target) !== 0) {
        Logger::log('DEPLOY_FAILED', "reset --hard did not reach the target ref.", [
            'before' => $before, 'expected' => $target, 'actual' => $after, 'output' => $resetOut
        ]);
        $deployResult = [
            'success' => false,
            'message' => 'reset --hard did not reach the target commit. Git said: ' . $resetOut,
        ];
    } else {
        if (function_exists('opcache_reset')) @opcache_reset();
        Cache::flushAll();

        Logger::log('DEPLOY_RUN', 'Deployed ' . DEPLOY_ORIGIN . '@' . $afterShort . ' via deploy.php', [
            'before' => $before, 'after' => $afterShort, 'forced' => !empty($_POST['force']),
        ]);

        $deployResult = [
            'success' => true,
            'message' => 'Deployed ' . $afterShort . ' (' . $before . ' → ' . $afterShort . '). '
                       . 'OPcache cleared and application cache flushed.',
        ];
    }
}

$canDeploy = $pf['git_ok'] && $pf['is_repo'] && $pf['origin_ok']
          && empty($pf['env_tracked']) && !empty($pf['is_sha']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Avazonia — Deployment Utility</title>
<style>
    :root { --ink:#0d0d0d; --line:#e3e3e3; --muted:#6b6b6b; --red:#e53935; --green:#00a854; --amber:#f5a623; }
    * { box-sizing:border-box; }
    body { margin:0; background:#f4f4f2; color:var(--ink); font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
    .wrap { max-width:820px; margin:0 auto; padding:40px 20px 80px; }
    h1 { font-size:22px; margin:0 0 4px; }
    .sub { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.1em; margin-bottom:24px; }
    .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:24px; margin-bottom:20px; }
    .label { font-size:10px; text-transform:uppercase; letter-spacing:.12em; color:var(--muted); font-weight:700; margin-bottom:14px; display:block; }
    .warn { border-left:4px solid var(--amber); background:#fffaf0; padding:14px 16px; border-radius:0 8px 8px 0; font-size:13px; margin-bottom:20px; }
    .danger { border-left:4px solid var(--red); background:#fff5f5; padding:14px 16px; border-radius:0 8px 8px 0; font-size:13px; margin-bottom:20px; }
    .ok { border-left:4px solid var(--green); background:#f3fbf6; padding:14px 16px; border-radius:0 8px 8px 0; font-size:13px; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    td { padding:8px 0; border-bottom:1px solid var(--line); vertical-align:top; }
    td:first-child { color:var(--muted); width:180px; }
    tr:last-child td { border-bottom:none; }
    code, pre { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:12px; }
    pre { background:#0d0d0d; color:#e8e8e8; padding:14px; border-radius:8px; overflow:auto; margin:12px 0; white-space:pre-wrap; }
    .pill { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
    .pill-g { background:#e5f7ec; color:var(--green); } .pill-r { background:#fdecea; color:var(--red); } .pill-a { background:#fff4e0; color:#b5730a; }
    input[type=password] { width:100%; padding:12px 14px; border:1.5px solid var(--line); border-radius:8px; font-size:14px; margin-bottom:14px; }
    input[type=password]:focus { outline:none; border-color:var(--ink); }
    button { width:100%; padding:14px; border:none; border-radius:8px; background:var(--red); color:#fff; font-weight:800; font-size:13px; text-transform:uppercase; letter-spacing:.08em; cursor:pointer; }
    button:disabled { background:#ccc; cursor:not-allowed; }
    label.check { display:flex; gap:8px; align-items:flex-start; font-size:12px; margin-bottom:14px; cursor:pointer; }
    .muted { color:var(--muted); font-size:12px; }
    a { color:#007AFF; }
</style>
</head>
<body>
<div class="wrap">

    <h1>Deployment Utility</h1>
    <div class="sub">Temporary bridge · delete when finished</div>

    <?php if ($deployResult !== null): ?>
        <div class="<?= $deployResult['success'] ? 'ok' : 'danger' ?>">
            <strong><?= $deployResult['success'] ? 'Deployed.' : 'Deploy failed.' ?></strong>
            <?= e($deployResult['message']) ?>
        </div>
    <?php endif; ?>

    <div class="warn">
        <strong>⚠️ This file is temporary.</strong> It exists only because the host has no SSH access.
        Delete <code>deploy.php</code> from the server and re-add it to the <code>.htaccess</code>
        block list as soon as the permanent deploy engine is in place.
    </div>

    <!-- ── CAPABILITY ── -->
    <div class="card">
        <span class="label">Server capability</span>
        <table>
            <tr><td>shell_exec()</td><td><?= $pf['shell'] ? '<span class="pill pill-g">available</span>' : '<span class="pill pill-r">disabled</span>' ?></td></tr>
            <tr><td>git binary</td><td><?= $pf['git_ok'] ? '<span class="pill pill-g">' . e($pf['git_version']) . '</span>' : '<span class="pill pill-r">not found</span>' ?></td></tr>
            <tr><td>git repository</td><td><?= $pf['is_repo'] ? '<span class="pill pill-g">yes</span> <code>' . e(__DIR__) . '</code>' : '<span class="pill pill-r">no</span>' ?></td></tr>
            <tr><td>remote origin</td><td><?= $pf['origin_ok'] ? '<span class="pill pill-g">matches</span> ' : '<span class="pill pill-r">MISMATCH</span> ' ?><code><?= e($pf['remote_url'] ?? '—') ?></code></td></tr>
            <tr><td>.env tracked by git</td><td><?= $pf['env_tracked'] ? '<span class="pill pill-r">YES — deploying blocked</span>' : '<span class="pill pill-g">no — safe</span>' ?></td></tr>
        </table>
    </div>

    <?php if ($pf['git_ok'] && $pf['is_repo']): ?>

    <!-- ── STATE ── -->
    <div class="card">
        <span class="label">Current state</span>
        <table>
            <tr><td>Server is on</td><td><code><?= e($pf['branch']) ?></code> @ <code><?= e($pf['head']) ?></code></td></tr>
            <tr><td>Remote has</td><td><code><?= e($pf['origin_sha'] ?? '—') ?></code></td></tr>
            <tr><td>Incoming commits</td><td><?= e($pf['incoming_count'] ?? '?') ?></td></tr>
        </table>
        <?php if (($pf['behind_count'] ?? '0') !== '0' && $pf['behind_count'] !== 0): ?>
            <div class="warn" style="margin:14px 0 0">
                The server is <strong><?= e($pf['behind_count']) ?> commit(s) AHEAD</strong> of the remote.
                Deploying will <strong>discard those commits</strong> permanently.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── INCOMING ── -->
    <?php if (!empty($pf['incoming_log'])): ?>
    <div class="card">
        <span class="label">Incoming commits</span>
        <pre><?= e($pf['incoming_log']) ?></pre>
        <span class="label">Files that will change</span>
        <pre><?= e($pf['diff_stat']) ?></pre>
    </div>
    <?php endif; ?>

    <!-- ── LOCAL EDITS ── -->
    <?php if (!empty($pf['lossy_changes'])): ?>
    <div class="danger">
        <strong>⚠️ <?= count($pf['lossy_changes']) ?> local edit(s) differ from the remote.</strong><br>
        <code>git reset --hard</code> will <strong>overwrite these with the remote version</strong>.
        Your edits cannot be recovered afterwards.
        <pre style="margin-top:10px"><?= e(implode("\n", $pf['lossy_changes'])) ?></pre>
    </div>
    <?php endif; ?>

    <?php if (!empty($pf['harmless_changes'])): ?>
    <div class="ok">
        <strong><?= count($pf['harmless_changes']) ?> file(s) differ from your local HEAD but already match
        <code>origin/main</code>.</strong> You likely re-uploaded these by hand — the reset writes the same
        bytes, so nothing is lost and no confirmation is required.
        <pre style="margin-top:10px"><?= e(implode("\n", $pf['harmless_changes'])) ?></pre>
    </div>
    <?php endif; ?>

    <?php if (empty($pf['lossy_changes']) && !empty($pf['fetch_output'])): ?>
    <div class="danger">
        <strong>git fetch reported an error:</strong>
        <pre><?= e($pf['fetch_output']) ?></pre>
    </div>
    <?php elseif (empty($pf['lossy_changes']) && empty($pf['harmless_changes'])): ?>
        <div class="ok">No local edits at risk — <code>.env</code> and uploaded images are untracked and will survive the deploy untouched.</div>
    <?php endif; ?>

    <!-- ── DEPLOY ── -->
    <div class="card" style="border-color:var(--red)">
        <span class="label" style="color:var(--red)">Execute deployment</span>

        <?php if (!$canDeploy): ?>
            <div class="danger">Deploy is disabled — see the capability table above for the failing precondition.</div>
        <?php else: ?>
            <form method="post" id="deploy-form" onsubmit="return confirmDeploy()">
                <input type="hidden" name="action" value="deploy">
                <?= Csrf::field() ?>

                <p class="muted" style="margin-top:0">
                    This runs <code>git fetch</code> + <code>git reset --hard origin/<?= e(DEPLOY_BRANCH) ?></code>,
                    then clears OPcache and the application cache. It never runs <code>git clean</code>,
                    so untracked files (including <code>.env</code>) are never deleted.
                </p>

                <?php if (!empty($pf['lossy_changes'])): ?>
                <label class="check">
                    <input type="checkbox" name="force" value="1">
                    <span>I understand my <strong><?= count($pf['lossy_changes']) ?> local edit(s) will be overwritten</strong> with the remote version and cannot be recovered.</span>
                </label>
                <?php endif; ?>

                <input type="password" name="password" id="pw" placeholder="Re-enter admin password" autocomplete="off" required>
                <input type="password" name="confirm_phrase" id="phrase" placeholder='Type "RESET" to confirm' autocomplete="off" required>

                <button type="submit" id="btn">Deploy origin/<?= e(DEPLOY_BRANCH) ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php else: ?>
        <div class="danger">
            <strong>Cannot deploy.</strong>
            <?php if (!$pf['shell']): ?>
                <code>shell_exec()</code> is disabled on this server. Use cPanel File Manager or FTP instead.
            <?php elseif (!$pf['git_ok']): ?>
                The <code>git</code> binary is not available. Use cPanel File Manager or FTP instead.
            <?php else: ?>
                This directory is not a git repository.
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<?php if ($canDeploy): ?>
<script>
function confirmDeploy() {
    var pw = document.getElementById('pw').value;
    var ph = document.getElementById('phrase').value.trim();
    if (!pw) { alert('Admin password required.'); return false; }
    if (ph !== 'RESET') { alert('Type RESET exactly to confirm.'); return false; }
    if (!confirm('Deploy origin/<?= e(DEPLOY_BRANCH) ?> to this server now?\n\nThis overwrites tracked files and cannot be undone from here.')) return false;
    var b = document.getElementById('btn');
    b.disabled = true; b.textContent = 'DEPLOYING...';
    return true;
}
</script>
<?php endif; ?>

</body>
</html>
