<?php
// admin/deploy.php
// Deployment engine — pulls the live working tree to GitHub `main`.
// Shared hosting has no SSH, so this is how releases reach production.
// Session first — see deploy.php for why ordering matters.
require_once __DIR__ . '/../core/Session.php';
Session::start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Deployer.php';

if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$title = "Deployment Engine — Avazonia";
include 'layout/header.php';
?>

<style>
    .deploy-grid { display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: start; }
    @media (max-width: 1024px) { .deploy-grid { grid-template-columns: 1fr; } }

    .card { background: #fff; border: 1px solid var(--light-gray); padding: 28px; border-radius: 16px; box-shadow: 0 1px 3px rgba(13,13,13,0.04); margin-bottom: 20px; }
    .card-label { font-family: 'Outfit', sans-serif; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid-gray); margin-bottom: 20px; font-weight: 800; display: block; }

    .kv { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kv td { padding: 10px 0; border-bottom: 1px solid var(--light-gray); vertical-align: top; }
    .kv tr:last-child td { border-bottom: none; }
    .kv td:first-child { color: var(--mid-gray); width: 190px; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
    .kv code { font-family: var(--f-mono); font-size: 11px; background: var(--off); padding: 2px 6px; border-radius: 4px; word-break: break-all; }

    .pill { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .pill-g { background: #e5f7ec; color: #00a854; }
    .pill-r { background: #fdecea; color: var(--red); }
    .pill-a { background: #fff4e0; color: #b5730a; }

    .note { padding: 14px 16px; border-radius: 0 8px 8px 0; font-size: 12px; line-height: 1.5; margin-bottom: 16px; border-left: 4px solid; }
    .note-ok { border-color: #00a854; background: #f3fbf6; }
    .note-warn { border-color: #f5a623; background: #fffaf0; }
    .note-danger { border-color: var(--red); background: #fff5f5; }

    pre.sql { background: var(--ink); color: #e8e8e8; padding: 14px; border-radius: 8px; overflow: auto; font-family: var(--f-mono); font-size: 11px; line-height: 1.5; margin: 10px 0 0; max-height: 320px; }
    .commits { list-style: none; padding: 0; margin: 0; font-family: var(--f-mono); font-size: 11px; }
    .commits li { padding: 7px 0; border-bottom: 1px solid var(--light-gray); color: var(--ink); }
    .commits li:last-child { border-bottom: none; }
    .commits .sha { color: var(--red); font-weight: 700; }

    details.mig { border: 1px solid var(--light-gray); border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; }
    details.mig summary { cursor: pointer; font-family: var(--f-semi); font-size: 12px; font-weight: 700; }

    /* Modal — matches the maintenance.php security modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; opacity: 0; transition: opacity .3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-content { background: #fff; width: 100%; max-width: 480px; padding: 40px; border: 3px solid var(--ink); transform: translateY(20px); transition: transform .3s ease; }
    .modal-overlay.active .modal-content { transform: translateY(0); }
    .modal-title { font-family: var(--f-display); font-weight: 900; font-size: 20px; color: var(--ink); text-transform: uppercase; margin-bottom: 12px; }
    .modal-desc { font-size: 13px; color: var(--mid-gray); margin-bottom: 24px; line-height: 1.5; }
    .pass-input { width: 100%; padding: 14px 20px; border: 1.5px solid var(--light-gray); border-radius: 12px; font-family: var(--f-semi); font-size: 14px; margin-bottom: 12px; outline: none; }
    .pass-input:focus { border-color: var(--red); }
    .modal-btn-row { display: flex; gap: 12px; margin-top: 8px; }
    label.force-check { display: flex; gap: 10px; align-items: flex-start; font-size: 12px; margin-bottom: 14px; cursor: pointer; line-height: 1.5; }

    .wide-btn { width: 100%; justify-content: center; height: 56px; }
    .skeleton { padding: 40px; text-align: center; color: var(--mid-gray); font-size: 13px; }
</style>

<div class="admin-header">
    <div>
        <h1>Deployment Engine</h1>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; margin-top: 4px; letter-spacing: 0.1em;">Git Sync · <?= htmlspecialchars(Deployer::BRANCH) ?></div>
    </div>
</div>

<div class="deploy-grid">
    <div>
        <div class="card">
            <span class="card-label">Incoming Changes</span>
            <div id="incoming-box"><div class="skeleton">⌛ Inspecting repository…</div></div>
        </div>

        <div class="card" id="migrations-card" style="display:none">
            <span class="card-label" style="color:#b5730a">⚠ Pending Database Migrations</span>
            <div id="migrations-box"></div>
        </div>

        <div class="card" id="local-card" style="display:none">
            <span class="card-label" style="color:var(--red)">Local Edits At Risk</span>
            <div id="local-box"></div>
        </div>
    </div>

    <div>
        <div class="card">
            <span class="card-label">Server Capability</span>
            <table class="kv" id="cap-table"><tr><td colspan="2" class="skeleton">Scanning…</td></tr></table>
        </div>

        <div class="card">
            <span class="card-label">Repository State</span>
            <table class="kv" id="state-table"><tr><td colspan="2" class="skeleton">Scanning…</td></tr></table>
        </div>

        <div class="card" style="border-color: var(--red)">
            <span class="card-label" style="color:var(--red)">Deploy</span>
            <div id="deploy-area"><p style="font-size:12px;color:var(--mid-gray);margin:0">Run inspection to enable.</p></div>
        </div>
    </div>
</div>

<!-- SECURITY MODAL -->
<div id="deploy-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-title">Confirm Deployment</div>
        <div class="modal-desc" id="modal-desc">
            This overwrites tracked files on the live server with <code>origin/main</code>,
            then clears OPcache. It cannot be undone from here.
        </div>

        <label id="force-wrap" style="display:none">
            <input type="checkbox" id="force-check">
            <span style="font-size:12px;line-height:1.5">I understand my local edits will be <strong>permanently destroyed</strong> and cannot be recovered.</span>
        </label>

        <input type="password" id="m-password" class="pass-input" placeholder="Re-enter Admin Password" autocomplete="off">
        <input type="password" id="m-phrase" class="pass-input" placeholder='Type "RESET" to confirm' autocomplete="off">

        <div class="modal-btn-row">
            <button id="m-cancel" class="admin-btn admin-btn-secondary" style="flex:1;height:48px">Cancel</button>
            <button id="m-confirm" class="admin-btn admin-btn-primary" style="flex:2;height:48px">Deploy Now</button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal    = document.getElementById('deploy-modal');
    const BRANCH   = '<?= Deployer::BRANCH ?>';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let report = null;

    async function api(payload) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const res = await fetch('api/deploy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': meta ? meta.content : ''
            },
            body: JSON.stringify(payload)
        });
        return res.json();
    }

    function pill(ok, okText, badText) {
        return ok ? `<span class="pill pill-g">${esc(okText)}</span>` : `<span class="pill pill-r">${esc(badText)}</span>`;
    }

    function render(r) {
        document.getElementById('cap-table').innerHTML = `
            <tr><td>shell_exec()</td><td>${pill(r.shell, 'available', 'disabled')}</td></tr>
            <tr><td>git binary</td><td>${r.git_ok ? pill(true, esc(r.git_version), '') : pill(false, '', 'not found')}</td></tr>
            <tr><td>Repository</td><td>${pill(r.is_repo, 'yes', 'no')}</td></tr>
            <tr><td>Remote origin</td><td>${pill(r.origin_ok, 'matches', 'MISMATCH')}<br><code>${esc(r.remote_url || '—')}</code></td></tr>
            <tr><td>.env tracked</td><td>${pill(!r.env_tracked, 'no — safe', 'YES — blocked')}</td></tr>`;

        document.getElementById('state-table').innerHTML = `
            <tr><td>On</td><td><code>${esc(r.branch || '—')}</code> @ <code>${esc(r.head || '—')}</code></td></tr>
            <tr><td>Incoming</td><td><strong>${esc(r.incoming_count)}</strong> commit(s)</td></tr>
            <tr><td>Ahead</td><td>${r.ahead > 0
                ? `<span class="pill pill-r">${esc(r.ahead)} ahead</span> — deploy discards these`
                : '<span class="pill pill-g">in sync</span>'}</td></tr>`;

        // Incoming commits + diff
        const inc = document.getElementById('incoming-box');
        if (!r.ready) {
            inc.innerHTML = `<div class="note note-danger"><strong>Cannot deploy.</strong> ${esc(r.reason)}</div>`;
        } else if (r.incoming_count === 0) {
            inc.innerHTML = `<div class="note note-ok">The server already matches <code>origin/main</code>. Nothing to deploy.</div>`;
        } else {
            inc.innerHTML = `<ul class="commits">${r.incoming.map(c => {
                const m = c.match(/^([0-9a-f]{7,40})\s+(.*)$/);
                return `<li><span class="sha">${esc(m ? m[1] : c.slice(0,7))}</span> ${esc(m ? m[2] : c.slice(7))}</li>`;
            }).join('')}</ul>`
            + (r.diff_stat ? `<pre class="sql" style="margin-top:14px">${esc(r.diff_stat)}</pre>` : '');
        }

        // Local edits
        const lcard = document.getElementById('local-card');
        if (r.local_changes && r.local_changes.length) {
            lcard.style.display = '';
            document.getElementById('local-box').innerHTML =
                `<div class="note note-danger"><strong>${r.local_changes.length} tracked file(s) have local edits.</strong>
                 <code>git reset --hard</code> will <strong>permanently destroy</strong> them. They are not in the remote
                 and cannot be recovered afterwards.</div>
                 <pre class="sql">${esc(r.local_changes.join('\n'))}</pre>`;
        } else {
            lcard.style.display = 'none';
        }

        // Migrations
        const mcard = document.getElementById('migrations-card');
        if (r.migrations && r.migrations.length) {
            mcard.style.display = '';
            document.getElementById('migrations-box').innerHTML =
                `<div class="note note-warn">This release changes the database schema. The engine <strong>never runs SQL
                 automatically</strong> — review each statement and run it in phpMyAdmin <em>after</em> deploying.</div>`
                + r.migrations.map(m => `<details class="mig"><summary>${esc(m.file)}
                     <span class="pill pill-a" style="margin-left:6px">${esc(m.status)}</span></summary>
                     <pre class="sql">${esc(m.sql)}</pre></details>`).join('');
        } else {
            mcard.style.display = 'none';
        }

        // Deploy action
        const area = document.getElementById('deploy-area');
        if (!r.ready) {
            area.innerHTML = `<div class="note note-danger" style="margin:0">Deploy is disabled — fix the failing check above.</div>`;
        } else if (r.incoming_count === 0 && r.ahead === 0) {
            area.innerHTML = `<div class="note note-ok" style="margin:0">Already up to date.</div>`;
        } else {
            area.innerHTML = `<button id="btn-deploy" class="admin-btn admin-btn-primary wide-btn">Deploy origin/` + esc(BRANCH) + `</button>`
                + `<p style="font-size:11px;color:var(--mid-gray);margin:14px 0 0;line-height:1.5">
                     Runs <code>git fetch</code> + <code>git reset --hard</code>, then clears OPcache and the app cache.
                     Never runs <code>git clean</code>, so <code>.env</code> and uploads are untouched.</p>`;
            document.getElementById('btn-deploy').addEventListener('click', openModal);
        }
    }

    async function load() {
        try {
            const data = await api({ action: 'preflight' });
            if (data.success) { report = data.report; render(report); }
            else document.getElementById('incoming-box').innerHTML =
                `<div class="note note-danger">${esc(data.message)}</div>`;
        } catch (e) {
            document.getElementById('incoming-box').innerHTML =
                `<div class="note note-danger">Could not reach the deployment engine.</div>`;
        }
    }

    function openModal() {
        const needsForce = report && report.local_changes && report.local_changes.length > 0;
        document.getElementById('force-wrap').style.display = needsForce ? 'flex' : 'none';
        document.getElementById('force-check').checked = false;
        document.getElementById('modal-desc').innerHTML = needsForce
            ? `This will <strong>destroy ${report.local_changes.length} local edit(s)</strong> and overwrite tracked files with <code>origin/main</code>.`
            : `This overwrites tracked files on the live server with <code>origin/main</code>, then clears OPcache.`;
        modal.classList.add('active');
        document.getElementById('m-password').focus();
    }

    function closeModal() {
        modal.classList.remove('active');
        document.getElementById('m-password').value = '';
        document.getElementById('m-phrase').value = '';
    }

    async function execute() {
        const password = document.getElementById('m-password').value;
        const phrase   = document.getElementById('m-phrase').value.trim();
        const needsForce = report && report.local_changes && report.local_changes.length > 0;

        if (!password) { await AdminUI.alert('Password required to confirm this action.', { title: 'Password Required' }); return; }
        if (phrase !== 'RESET') { await AdminUI.alert('Type RESET exactly to confirm.', { title: 'Confirmation Required' }); return; }
        if (needsForce && !document.getElementById('force-check').checked) {
            await AdminUI.alert('Confirm you understand the local edits will be destroyed.', { title: 'Confirmation Required' }); return;
        }

        const btn = document.getElementById('m-confirm');
        const original = btn.textContent;
        btn.textContent = 'DEPLOYING…';
        btn.disabled = true;

        try {
            const data = await api({
                action: 'deploy',
                password: password,
                confirm_phrase: phrase,
                force: needsForce && document.getElementById('force-check').checked
            });
            btn.textContent = original;
            btn.disabled = false;

            if (data.success) {
                closeModal();
                await AdminUI.alert(data.message, { title: 'Deployed', tone: 'success', confirmText: 'Continue' });
                load();
            } else {
                await AdminUI.alert(data.message, { title: 'Deployment Blocked', tone: 'danger' });
            }
        } catch (e) {
            btn.textContent = original;
            btn.disabled = false;
            await AdminUI.alert('Connection failure — the deploy may still be running. Reload to check.', { title: 'Connection Error' });
        }
    }

    document.getElementById('m-cancel').addEventListener('click', closeModal);
    document.getElementById('m-confirm').addEventListener('click', execute);
    window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    load();
})();
</script>

<?php include 'layout/footer.php'; ?>
