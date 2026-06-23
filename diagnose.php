<?php
/**
 * AVAZONIA DEEP DIAGNOSTICS & SYSTEM MONITOR
 * Security: Requires ?secret=avazonia_debug query parameter.
 * IMPORTANT: Delete this file after diagnosing server issues.
 */

// 1. Security Check
$secret = 'avazonia_debug';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    header('HTTP/1.1 403 Forbidden');
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Access Denied - Avazonia Diagnostics</title>
        <style>
            body { background: #0f172a; color: #f1f5f9; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .card { background: #1e293b; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; max-width: 400px; border: 1px solid #334155; }
            h1 { color: #f43f5e; margin-top: 0; }
            p { color: #94a3b8; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>403 Forbidden</h1>
            <p>Access to the diagnostics utility is restricted. Please provide the correct authorization token.</p>
        </div>
    </body>
    </html>';
    exit;
}

// Disable output buffering to send results incrementally if slow
if (ob_get_level()) ob_end_clean();

// Capture any startup errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// 2. Perform Checks
$results = [];

// Check PHP & Server
$results['system'] = [
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'load_avg' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'Unavailable',
    'disk_free' => function_exists('disk_free_space') ? formatBytes(disk_free_space('.')) : 'Unavailable',
    'disk_total' => function_exists('disk_total_space') ? formatBytes(disk_total_space('.')) : 'Unavailable',
];

// Check Core Limits
$results['limits'] = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . 's',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors') ? 'ON' : 'OFF',
    'log_errors' => ini_get('log_errors') ? 'ON' : 'OFF',
    'error_log' => ini_get('error_log') ?: 'Default Web Server Log',
];

// Check Environment File (.env)
$envPath = __DIR__ . '/.env';
$results['env'] = [
    'exists' => file_exists($envPath),
    'readable' => is_readable($envPath),
    'size' => file_exists($envPath) ? formatBytes(filesize($envPath)) : 'N/A',
];

// Check Required Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'gd', 'zip', 'session'];
$results['extensions'] = [];
foreach ($required_extensions as $ext) {
    $results['extensions'][$ext] = extension_loaded($ext);
}

// Check Directory Permissions
$critical_dirs = [
    '.' => 'Root Directory',
    'public/uploads' => 'Uploads Directory',
    'backups' => 'Backups Directory',
    'config' => 'Config Directory'
];
$results['dirs'] = [];
foreach ($critical_dirs as $path => $name) {
    $fullPath = __DIR__ . '/' . $path;
    $exists = file_exists($fullPath);
    $results['dirs'][$name] = [
        'exists' => $exists,
        'readable' => $exists && is_readable($fullPath),
        'writable' => $exists && is_writable($fullPath),
    ];
}

// Database Connection Diagnostics
$dbStatus = 'Unattempted';
$dbLatency = 0;
$dbError = null;
$dbTableCheck = [];
$dbFallbackTest = null;
$dbPortOpen = false;

$startTime = microtime(true);
try {
    // Attempt loading app environment and initiating DB config
    if (file_exists(__DIR__ . '/config/database.php')) {
        // Load app config temporarily
        @include_once __DIR__ . '/config/app.php';
        if (function_exists('db')) {
            $db = db();
            $endTime = microtime(true);
            $dbLatency = round(($endTime - $startTime) * 1000, 2); // ms
            $dbStatus = 'Success';
            
            // Query tables
            $tables = ['settings', 'users', 'products', 'orders'];
            foreach ($tables as $t) {
                try {
                    $q = $db->query("SELECT COUNT(*) FROM `$t`");
                    $count = $q->fetchColumn();
                    $dbTableCheck[$t] = "OK ($count records)";
                } catch (Exception $ex) {
                    $dbTableCheck[$t] = "Failed: " . $ex->getMessage();
                }
            }
        } else {
            $dbStatus = 'Failed: db() function not found';
        }
    } else {
        $dbStatus = 'Failed: config/database.php not found';
    }
} catch (Exception $e) {
    $dbStatus = 'Failed';
    $dbError = $e->getMessage();
    
    // Diagnostic Fallbacks:
    // 1. Check if port 3306 is listening on 127.0.0.1
    $connection = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 2);
    if (is_resource($connection)) {
        $dbPortOpen = true;
        fclose($connection);
    }
    
    // 2. Try connecting using TCP/IP explicitly (127.0.0.1) instead of localhost
    if (strpos($dbError, '2002') !== false || strpos($dbError, 'socket') !== false) {
        $host = '127.0.0.1';
        $db   = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? getenv('DB_NAME') ?: 'avazonia_avazonia';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? getenv('DB_USER') ?: 'avazonia_admin';
        $pass = $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? getenv('DB_PASS') ?: 'Avazonia123@';
        $port = $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        try {
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4;port=$port";
            $test_pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 2, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbFallbackTest = 'Success using 127.0.0.1 (TCP/IP connection works!)';
        } catch (Exception $fallback_ex) {
            $dbFallbackTest = 'Failed: ' . $fallback_ex->getMessage();
        }
    }
}

// Read Log Snippets
$logs = [];
$logFiles = [
    'env_debug.log' => __DIR__ . '/env_debug.log',
    'php_error_log' => ini_get('error_log')
];
foreach ($logFiles as $name => $path) {
    if ($path && file_exists($path) && is_readable($path) && filesize($path) > 0) {
        $lines = file($path);
        $last_lines = array_slice($lines, -25);
        $logs[$name] = implode('', $last_lines);
    } else {
        $logs[$name] = "No log found or log file is unreadable/empty at path: " . ($path ?: 'N/A');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avazonia Deep Diagnostics Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: #151d30;
            --border-color: #24324f;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --accent-primary: #3b82f6;
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 2rem 1rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease-out;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .badge-security {
            background: rgba(239, 68, 68, 0.1);
            color: var(--accent-danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 0.75rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-primary);
            opacity: 0.7;
        }

        .card.success::before { background: var(--accent-success); }
        .card.warning::before { background: var(--accent-warning); }
        .card.danger::before { background: var(--accent-danger); }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title span.icon {
            font-size: 1.5rem;
        }

        .diagnose-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .diagnose-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
        }

        .diagnose-list li:last-child {
            border-bottom: none;
        }

        .diagnose-list li .label {
            color: var(--text-muted);
        }

        .diagnose-list li .value {
            font-weight: 500;
            font-family: 'JetBrains Mono', monospace;
        }

        .status-pill {
            padding: 0.125rem 0.5rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pill.success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-success);
        }

        .status-pill.warning {
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent-warning);
        }

        .status-pill.danger {
            background: rgba(239, 68, 68, 0.15);
            color: var(--accent-danger);
        }

        .extensions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .extension-badge {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.85rem;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .extension-badge.loaded {
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.03);
        }

        .extension-badge.missing {
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.03);
        }

        .log-section {
            margin-top: 2rem;
        }

        .log-card {
            background: #060911;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .log-header {
            background: #111827;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .log-body {
            padding: 1.25rem;
            margin: 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            white-space: pre-wrap;
            max-height: 350px;
            color: #d1d5db;
            line-height: 1.6;
        }

        .refresh-btn {
            background: var(--accent-primary);
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .refresh-btn:hover {
            background: #2563eb;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>Avazonia Diagnostics</h1>
        <p>Real-time environment validation and crash diagnostics</p>
        <span class="badge-security">🔒 Secure Session Active</span>
    </header>

    <div class="grid">
        <!-- System Info Card -->
        <div class="card">
            <h2 class="card-title">System Information <span class="icon">🖥️</span></h2>
            <ul class="diagnose-list">
                <li><span class="label">OS / Platform</span><span class="value"><?php echo htmlspecialchars($results['system']['os']); ?></span></li>
                <li><span class="label">PHP Version</span><span class="value"><?php echo htmlspecialchars($results['system']['php_version']); ?></span></li>
                <li><span class="label">Server Software</span><span class="value" style="font-size:0.85rem;"><?php echo htmlspecialchars($results['system']['server_software']); ?></span></li>
                <li><span class="label">CPU Load Avg</span><span class="value"><?php echo htmlspecialchars($results['system']['load_avg']); ?></span></li>
                <li><span class="label">Disk Free Space</span><span class="value"><?php echo htmlspecialchars($results['system']['disk_free']); ?> / <?php echo htmlspecialchars($results['system']['disk_total']); ?></span></li>
            </ul>
        </div>

        <!-- Limits Card -->
        <div class="card <?php echo ((int)$results['limits']['memory_limit'] < 128) ? 'warning' : 'success'; ?>">
            <h2 class="card-title">PHP Config & Resource Limits <span class="icon">⚙️</span></h2>
            <ul class="diagnose-list">
                <li>
                    <span class="label">Memory Limit</span>
                    <span class="value">
                        <?php echo htmlspecialchars($results['limits']['memory_limit']); ?>
                        <?php if ((int)$results['limits']['memory_limit'] < 128): ?>
                            <span class="status-pill warning">Low</span>
                        <?php else: ?>
                            <span class="status-pill success">OK</span>
                        <?php endif; ?>
                    </span>
                </li>
                <li><span class="label">Max Execution Time</span><span class="value"><?php echo htmlspecialchars($results['limits']['max_execution_time']); ?></span></li>
                <li><span class="label">Max Upload / Post</span><span class="value"><?php echo htmlspecialchars($results['limits']['upload_max_filesize']); ?> / <?php echo htmlspecialchars($results['limits']['post_max_size']); ?></span></li>
                <li><span class="label">Display Errors</span><span class="value"><?php echo htmlspecialchars($results['limits']['display_errors']); ?></span></li>
                <li><span class="label">Log Errors</span><span class="value"><?php echo htmlspecialchars($results['limits']['log_errors']); ?></span></li>
            </ul>
        </div>

        <!-- Database Status -->
        <div class="card <?php echo ($dbStatus === 'Success') ? 'success' : 'danger'; ?>">
            <h2 class="card-title">Database Status <span class="icon">🗄️</span></h2>
            <ul class="diagnose-list">
                <li>
                    <span class="label">DB Connection</span>
                    <span class="value">
                        <?php if ($dbStatus === 'Success'): ?>
                            <span class="status-pill success">Connected</span>
                        <?php else: ?>
                            <span class="status-pill danger">Failed</span>
                        <?php endif; ?>
                    </span>
                </li>
                <li><span class="label">Response Latency</span><span class="value"><?php echo $dbLatency; ?> ms</span></li>
                <?php if ($dbError): ?>
                    <li style="flex-direction:column; align-items:flex-start; gap:0.5rem;">
                        <span class="label" style="color:var(--accent-danger);">Connection Error:</span>
                        <span class="value" style="word-break:break-all; font-size:0.8rem; color:var(--accent-danger);"><?php echo htmlspecialchars($dbError); ?></span>
                    </li>
                <?php endif; ?>
                <?php if (isset($dbPortOpen) && !$dbPortOpen && $dbStatus !== 'Success'): ?>
                    <li>
                        <span class="label">Port 3306 (127.0.0.1)</span>
                        <span class="value status-pill danger">Closed / Offline</span>
                    </li>
                <?php elseif (isset($dbPortOpen) && $dbPortOpen && $dbStatus !== 'Success'): ?>
                    <li>
                        <span class="label">Port 3306 (127.0.0.1)</span>
                        <span class="value status-pill success">Open / Listening</span>
                    </li>
                <?php endif; ?>
                <?php if (isset($dbFallbackTest) && $dbFallbackTest && $dbStatus !== 'Success'): ?>
                    <li style="flex-direction:column; align-items:flex-start; gap:0.5rem;">
                        <span class="label">TCP Fallback (127.0.0.1):</span>
                        <span class="value" style="font-size:0.85rem; color: <?php echo (strpos($dbFallbackTest, 'Success') === 0) ? 'var(--accent-success)' : 'var(--accent-danger)'; ?>;"><?php echo htmlspecialchars($dbFallbackTest); ?></span>
                    </li>
                <?php endif; ?>
                <?php foreach ($dbTableCheck as $tbl => $chk): ?>
                    <li>
                        <span class="label">Table `<?php echo htmlspecialchars($tbl); ?>`</span>
                        <span class="value <?php echo (strpos($chk, 'OK') === 0) ? '' : 'status-pill danger'; ?>" style="font-size:0.85rem;">
                            <?php echo htmlspecialchars($chk); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="grid">
        <!-- Environment Card -->
        <div class="card <?php echo ($results['env']['exists'] && $results['env']['readable']) ? 'success' : 'danger'; ?>">
            <h2 class="card-title">Environment (.env) Check <span class="icon">🔑</span></h2>
            <ul class="diagnose-list">
                <li>
                    <span class="label">File Exists</span>
                    <span class="value"><?php echo $results['env']['exists'] ? '<span class="status-pill success">Yes</span>' : '<span class="status-pill danger">No</span>'; ?></span>
                </li>
                <li>
                    <span class="label">Readable</span>
                    <span class="value"><?php echo $results['env']['readable'] ? '<span class="status-pill success">Yes</span>' : '<span class="status-pill danger">No</span>'; ?></span>
                </li>
                <li><span class="label">File Size</span><span class="value"><?php echo htmlspecialchars($results['env']['size']); ?></span></li>
            </ul>
        </div>

        <!-- Directory Permissions -->
        <div class="card">
            <h2 class="card-title">Directory Health <span class="icon">📁</span></h2>
            <ul class="diagnose-list">
                <?php foreach ($results['dirs'] as $name => $perms): ?>
                    <li>
                        <span class="label"><?php echo htmlspecialchars($name); ?></span>
                        <span class="value">
                            <?php if (!$perms['exists']): ?>
                                <span class="status-pill danger">Missing</span>
                            <?php else: ?>
                                <span class="status-pill <?php echo $perms['readable'] ? 'success' : 'danger'; ?>">R</span>
                                <span class="status-pill <?php echo $perms['writable'] ? 'success' : 'danger'; ?>">W</span>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- PHP Extensions -->
    <div class="card" style="margin-bottom: 2rem; width:100%; box-sizing:border-box;">
        <h2 class="card-title">Required PHP Extensions <span class="icon">🔌</span></h2>
        <div class="extensions-grid">
            <?php foreach ($results['extensions'] as $ext => $loaded): ?>
                <div class="extension-badge <?php echo $loaded ? 'loaded' : 'missing'; ?>">
                    <span><?php echo htmlspecialchars($ext); ?></span>
                    <span class="status-pill <?php echo $loaded ? 'success' : 'danger'; ?>" style="font-size:0.75rem;">
                        <?php echo $loaded ? 'Loaded' : 'Missing'; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Logs Section -->
    <div class="log-section">
        <h2>System & Environment Logs</h2>

        <div class="log-card">
            <div class="log-header">
                <span>Recent environment initialization log (env_debug.log)</span>
                <span class="status-pill success">File-bound</span>
            </div>
            <pre class="log-body"><?php echo htmlspecialchars($logs['env_debug.log']); ?></pre>
        </div>

        <div class="log-card">
            <div class="log-header">
                <span>Server PHP Error Log</span>
                <span class="status-pill warning">Runtime</span>
            </div>
            <pre class="log-body"><?php echo htmlspecialchars($logs['php_error_log']); ?></pre>
        </div>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <button class="refresh-btn" onclick="window.location.reload()">🔄 Refresh Metrics</button>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:1rem;">⚠️ Remember to delete this diagnostics script (`diagnose.php`) after resolving live server issues.</p>
    </div>
</div>

</body>
</html>
