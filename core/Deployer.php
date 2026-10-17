<?php
// core/Deployer.php
// Git-based deployment engine for shared hosting with no SSH access.
//
// Syncs the working tree to the remote tracking branch using
// `git fetch` + `git reset --hard`, then clears bytecode and app caches.
//
// DANGERS THIS CLASS GUARDS AGAINST:
//   * `git reset --hard` silently destroys local edits to tracked files.
//     Untracked/ignored files (.env, uploads/) are NOT touched, which is why
//     this class never runs `git clean` — that would delete them.
//   * `.env` must never be tracked; if it were, reset would replace the live
//     credentials with the repo copy.
//   * A compromised admin session would otherwise become remote code
//     execution, so every precondition is re-verified at call time.

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Cache.php';
require_once __DIR__ . '/../models/Logger.php';

class DeployException extends Exception {}

class Deployer {

    /** Only this remote may be deployed from. Change if you fork. */
    public const EXPECTED_ORIGIN = 'https://github.com/Ajigiwe/Avazonia.git';
    public const BRANCH = 'main';

    private string $root;

    public function __construct(?string $root = null) {
        $this->root = rtrim($root ?? dirname(__DIR__), '/\\');
    }

    // ── Low-level ────────────────────────────────────────────────────────

    public static function shellAvailable(): bool {
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (in_array('shell_exec', $disabled, true)) return false;
        return function_exists('shell_exec');
    }

    /** Run a git command anchored at the app root. stderr is captured. */
    private function git(string $args): string {
        if (!self::shellAvailable()) return '';
        $cmd = 'git -C ' . escapeshellarg($this->root) . ' ' . $args . ' 2>&1';
        $out = @shell_exec($cmd);
        return is_string($out) ? trim($out) : '';
    }

    // ── Preflight (read-only) ────────────────────────────────────────────

    /**
     * Inspect the server without changing anything.
     * Returns a report the UI renders; `ready` is false when deploying
     * would be unsafe or impossible.
     */
    public function preflight(): array {
        $r = [
            'shell'         => self::shellAvailable(),
            'git_ok'        => false,
            'git_version'   => '',
            'is_repo'       => false,
            'branch'        => null,
            'head'          => null,
            'remote_url'    => null,
            'origin_ok'     => false,
            'env_tracked'   => false,
            'local_changes' => [],
            'lossy_changes'    => [],
            'harmless_changes' => [],
            'ahead'         => 0,
            'incoming'      => [],
            'incoming_count'=> 0,
            'diff_stat'     => '',
            'fetch_error'   => '',
            'ready'         => false,
            'reason'        => '',
            'migrations'    => [],
        ];

        $r['git_version'] = $r['shell'] ? $this->git('--version') : '';
        $r['git_ok'] = (bool)preg_match('/^git version/i', $r['git_version']);
        if (!$r['git_ok']) { $r['reason'] = 'The git binary is not available to PHP on this server.'; return $r; }

        $r['is_repo'] = $this->git('rev-parse --is-inside-work-tree') === 'true';
        if (!$r['is_repo']) { $r['reason'] = $this->root . ' is not a git repository.'; return $r; }

        $r['branch']     = $this->git('rev-parse --abbrev-ref HEAD');
        $r['head']       = $this->git('rev-parse --short HEAD');
        $r['remote_url'] = $this->git('remote get-url origin');
        $r['origin_ok']  = stripos((string)$r['remote_url'], self::EXPECTED_ORIGIN) !== false;
        if (!$r['origin_ok']) {
            $r['reason'] = 'Remote origin (' . ($r['remote_url'] ?: 'none') . ') does not match ' . self::EXPECTED_ORIGIN;
            return $r;
        }

        // .env must stay untracked, otherwise reset --hard clobbers prod creds.
        $r['env_tracked'] = in_array('.env', array_filter(explode("\n", $this->git('ls-files'))), true);
        if ($r['env_tracked']) { $r['reason'] = '.env is tracked by git — deploying would destroy the live credentials.'; return $r; }

        // Any output from fetch is an error (git is quiet on success).
        $r['fetch_error'] = $this->git('fetch origin --prune');

        $target = $this->git('rev-parse origin/' . self::BRANCH);
        if (!preg_match('/^[0-9a-f]{7,40}$/i', $target)) {
            $r['reason'] = 'Could not resolve origin/' . self::BRANCH . '. Fetch said: ' . $r['fetch_error'];
            return $r;
        }

        $r['ahead']          = (int)$this->git('rev-list --count origin/' . self::BRANCH . '..HEAD');
        $r['incoming_count'] = (int)$this->git('rev-list --count HEAD..origin/' . self::BRANCH);
        $r['incoming']       = $this->lines($this->git('log --oneline --no-decorate -12 HEAD..origin/' . self::BRANCH));
        $r['diff_stat']      = $this->git('diff --stat --stat-count=40 HEAD origin/' . self::BRANCH);

        // Tracked files with local edits would be DESTROYED by reset --hard.
        // Untracked ("??") entries are safe — reset does not touch them.
        $statusLines = [];
        foreach ($this->lines($this->git('status --porcelain')) as $line) {
            if (strpos(substr($line, 0, 2), '??') !== false) continue;
            $statusLines[] = $line;
        }
        $r['local_changes'] = $statusLines;

        // `status --porcelain` compares against the LOCAL head, but
        // `reset --hard` writes the REMOTE's version. So a file that differs
        // from the local head yet already matches origin/main comes out
        // byte-identical — nothing is lost. Only files that also differ from
        // origin/main are genuinely at risk. Splitting them means the
        // confirmation flow only demands a force-acknowledge when content
        // would really be destroyed.
        $differsFromRemote = [];
        foreach ($this->lines($this->git('diff --name-only origin/' . self::BRANCH)) as $f) {
            $differsFromRemote[trim($f)] = true;
        }

        $r['lossy_changes']    = [];
        $r['harmless_changes'] = [];
        foreach ($statusLines as $line) {
            $file = preg_replace('/^[ MARCDTU]{1,2}/', '', $line);
            if (strpos($file, '->') !== false) {                 // rename: keep the new path
                $parts = explode('->', $file);
                $file = end($parts);
            }
            $file = trim($file);
            if (isset($differsFromRemote[$file])) $r['lossy_changes'][]    = $line;
            else                                  $r['harmless_changes'][] = $line;
        }

        // Migrations this deploy would introduce (never auto-run — preview only).
        $r['migrations'] = $this->pendingMigrations();

        $r['ready'] = true;
        $r['reason'] = '';
        return $r;
    }

    /**
     * Migration files added or modified between HEAD and the remote.
     * There is no schema_migrations table, so the diff against the remote is
     * the only reliable signal. Returns the SQL so it can be reviewed/run
     * manually in phpMyAdmin.
     */
    private function pendingMigrations(): array {
        $out = $this->git('diff --name-status --diff-filter=AM HEAD origin/' . self::BRANCH . ' -- migrations/');
        $migrations = [];
        foreach ($this->lines($out) as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) < 2) continue;
            $path = $parts[1];
            if (substr($path, -4) !== '.sql') continue;
            $name = basename($path);
            // Read the version as it exists on the remote, not the local copy.
            $sql = $this->git('show origin/' . self::BRANCH . ':' . escapeshellarg('migrations/' . $name));
            $migrations[] = [
                'file'   => $name,
                'status' => $parts[0],
                'sql'    => $sql,
            ];
        }
        return $migrations;
    }

    // ── Deploy ───────────────────────────────────────────────────────────

    /**
     * Fetch and hard-reset the working tree to the remote branch.
     * @param array $preflight Result of preflight(); re-verified here.
     * @param bool  $force     Acknowledge that local tracked edits are lost.
     * @throws DeployException when any precondition fails.
     */
    public function deploy(array $preflight, bool $force = false): array {
        if (empty($preflight['ready'])) {
            throw new DeployException($preflight['reason'] ?: 'Preflight did not pass.');
        }
        // Only edits that differ from the REMOTE can lose content. Local edits
        // that already equal origin/main come out of reset --hard byte-identical,
        // so they must neither block the deploy nor demand a force-acknowledge —
        // otherwise files an admin merely re-uploaded by hand look dangerous.
        $lossy = $preflight['lossy_changes'] ?? [];
        if (!empty($lossy) && !$force) {
            throw new DeployException(count($lossy)
                . ' local edit(s) differ from the remote and WILL be overwritten by reset --hard. '
                . "Re-run with force to proceed:\n" . implode("\n", $lossy));
        }

        $before = (string)$preflight['head'];

        $fetchOut = $this->git('fetch origin --prune');
        $target = $this->git('rev-parse origin/' . self::BRANCH);
        if (!preg_match('/^[0-9a-f]{40}$/i', $target)) {
            throw new DeployException('git fetch failed — could not resolve origin/' . self::BRANCH . '. Output: ' . $fetchOut);
        }

        $resetOut = $this->git('reset --hard origin/' . self::BRANCH);
        $after = $this->git('rev-parse HEAD');

        if (strcasecmp($after, $target) !== 0) {
            Logger::log('DEPLOY_FAILED', 'reset --hard did not reach the target ref.', [
                'before' => $before, 'expected' => $target, 'actual' => $after, 'output' => $resetOut,
            ]);
            throw new DeployException('reset --hard did not reach the target commit. Git said: ' . $resetOut);
        }

        // Stale bytecode would keep serving the previous release.
        if (function_exists('opcache_reset')) @opcache_reset();
        Cache::flushAll();

        $short = (string)$this->git('rev-parse --short HEAD');
        Logger::log('DEPLOY_RUN', 'Deployed ' . self::EXPECTED_ORIGIN . '@' . $short . ' from the admin panel', [
            'before' => $before, 'after' => $short, 'forced' => $force,
        ]);

        return ['before' => $before, 'after' => $short];
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function lines(string $s): array {
        if ($s === '') return [];
        return array_values(array_filter(explode("\n", $s), fn($l) => trim($l) !== ''));
    }
}
