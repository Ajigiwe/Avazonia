<?php
// admin/maintenance.php
require_once '../config/app.php';
require_once '../core/Session.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$title = "System Maintenance — Avazonia";
include 'layout/header.php';
?>

<style>
    .maintenance-grid { display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: start; }
    
    .maintenance-card { background: #fff; border: 2px solid var(--ink); padding: 32px; border-radius: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
    .card-label { font-family: 'Outfit', sans-serif; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--mid-gray); margin-bottom: 24px; font-weight: 800; display: block; }
    
    .backup-item { display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid var(--light-gray); }
    .backup-item:last-child { border-bottom: none; }
    .backup-info { flex: 1; }
    .backup-name { font-family: var(--f-semi); font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .backup-info { flex: 1; }
    .backup-name { font-family: var(--f-semi); font-size: 14px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
    .backup-meta { font-size: 11px; color: var(--mid-gray); }
    
    .type-badge { font-size: 9px; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; font-weight: 800; border: 1px solid; }
    .type-auto { border-color: #007AFF; color: #007AFF; }
    .type-manual { border-color: #00A854; color: #00A854; }

    .action-group { display: flex; flex-direction: column; gap: 16px; }
    .wide-btn { width: 100%; justify-content: center; }
    
    /* Modal Styling */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-content { background: #fff; width: 100%; max-width: 440px; padding: 40px; border: 3px solid var(--ink); transform: translateY(20px); transition: transform 0.3s ease; }
    .modal-overlay.active .modal-content { transform: translateY(0); }

    .modal-title { font-family: var(--f-display); font-weight: 900; font-size: 20px; color: var(--ink); text-transform: uppercase; margin-bottom: 12px; }
    .modal-desc { font-size: 13px; color: var(--mid-gray); margin-bottom: 24px; line-height: 1.5; }
    
    .pass-input { width: 100%; padding: 14px 20px; border: 2px solid var(--ink); border-radius: 4px; font-family: var(--f-semi); font-size: 14px; margin-bottom: 16px; }
    .pass-input:focus { outline: none; background: #fff; border-color: var(--red); }

    .modal-btn-row { display: flex; gap: 12px; }
    
    @media (max-width: 1024px) {
        .maintenance-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="admin-header">
    <div>
        <h1>System Maintenance</h1>
        <div style="font-family: var(--f-mono); font-size: 10px; color: var(--mid-gray); text-transform: uppercase; margin-top: 4px; letter-spacing: 0.1em;">Infrastructure Control Panel</div>
    </div>
</div>

<div class="maintenance-grid">
    <!-- BACKUP LIST -->
    <div class="maintenance-card">
        <span class="card-label">Stored System Backups</span>
        <div id="backup-list">
            <div style="padding: 40px; text-align: center; color: var(--mid-gray); font-size: 13px;">⌛ Scanning backup repository...</div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="action-group">
        <div class="maintenance-card">
            <span class="card-label">Safe Actions</span>
            <button id="btn-backup-now" class="btn-ink wide-btn" style="height: 56px;">Generate Manual Backup</button>
            <p style="font-size: 11px; color: var(--mid-gray); margin-top: 16px; line-height: 1.4;">Creates a full SQL snapshot of your products, settings, and orders.</p>
        </div>

        <div class="maintenance-card" style="border-color: var(--red);">
            <span class="card-label" style="color: var(--red);">Danger Zone</span>
            <button id="btn-wipe-data" class="btn-red wide-btn" style="height: 56px; background: #fff; color: var(--red); border: 2px solid var(--red);">Perform Secure Wipe</button>
            <p style="font-size: 11px; color: var(--red); margin-top: 16px; line-height: 1.4; font-weight: 500;">Clears all products, orders, and categories. An auto-backup is generated before wiping.</p>
        </div>
    </div>
</div>

<!-- CONFIRMATION MODAL -->
<div id="security-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-title" id="modal-title">Confirm Security</div>
        <div class="modal-desc" id="modal-desc">This action involves sensitive system data. Please confirm your administrator password to proceed.</div>
        
        <input type="password" id="confirm-password" class="pass-input" placeholder="Enter Admin Password">
        
        <div class="modal-btn-row">
            <button id="modal-cancel" class="btn-ink" style="background: var(--off); color: var(--ink); border: none; flex: 1; height: 48px;">Cancel</button>
            <button id="modal-confirm" class="btn-red" style="flex: 2; height: 48px;">Confirm & Run</button>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('security-modal');
    const passwordInput = document.getElementById('confirm-password');
    let currentAction = null;
    let currentFile = null;

    async function loadBackups() {
        const container = document.getElementById('backup-list');
        try {
            const res = await fetch('api/maintenance.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'list' })
            });
            const data = await res.json();
            
            if (data.success) {
                if (data.backups.length === 0) {
                    container.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--mid-gray); font-size: 13px;">No backups found in repository.</div>';
                    return;
                }

                let html = '';
                data.backups.forEach(b => {
                    const sizeMB = (b.size / 1024 / 1024).toFixed(2);
                    html += `
                        <div class="backup-item">
                            <div class="backup-info">
                                <div class="backup-name">
                                    ${b.filename}
                                    <span class="type-badge ${b.type === 'Automatic' ? 'type-auto' : 'type-manual'}">${b.type}</span>
                                </div>
                                <div class="backup-meta">${b.date} • ${sizeMB} MB</div>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <button onclick="triggerRestore('${b.filename}')" class="nav-link" style="font-size: 10px; color: #007AFF;">RESTORE</button>
                                <button onclick="deleteBackup('${b.filename}')" class="nav-link" style="font-size: 10px; color: var(--red);">DELETE</button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }
        } catch (err) {
            container.innerHTML = 'Error loading repository.';
        }
    }

    function openModal(action, filename = null) {
        currentAction = action;
        currentFile = filename;
        
        if (action === 'wipe') {
            document.getElementById('modal-title').innerText = 'Nuclear System Wipe';
            document.getElementById('modal-desc').innerText = 'WARNING: This will clear all products, orders, and customers. A system backup will be generated automatically.';
        } else {
            document.getElementById('modal-title').innerText = 'Confirm Restoration';
            document.getElementById('modal-desc').innerText = `Target File: ${filename}. Current data will be replaced entirely.`;
        }
        
        modal.classList.add('active');
        passwordInput.focus();
    }

    function closeModal() {
        modal.classList.remove('active');
        passwordInput.value = '';
        currentAction = null;
    }

    async function executeMaintenance() {
        const password = passwordInput.value;
        if (!password) return alert('Password required');

        const confirmBtn = document.getElementById('modal-confirm');
        const originalText = confirmBtn.innerText;
        confirmBtn.innerText = 'PROCESSING...';
        confirmBtn.disabled = true;

        try {
            const res = await fetch('api/maintenance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: currentAction,
                    filename: currentFile,
                    password: password
                })
            });
            const data = await res.json();
            
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
                confirmBtn.innerText = originalText;
                confirmBtn.disabled = false;
            }
        } catch (err) {
            alert('Connection failure');
            confirmBtn.disabled = false;
        }
    }

    async function handleSimpleAction(action) {
        const btn = document.getElementById('btn-backup-now');
        const originalText = btn.innerText;
        btn.innerText = 'GENERATING...';
        btn.disabled = true;

        try {
            const res = await fetch('api/maintenance.php', {
                method: 'POST',
                body: JSON.stringify({ action: action })
            });
            const data = await res.json();
            if (data.success) {
                loadBackups();
                btn.innerText = 'COMPLETED';
                setTimeout(() => { btn.innerText = originalText; btn.disabled = false; }, 2000);
            }
        } catch (err) {
            alert('Failed to trigger background engine.');
            btn.disabled = false;
        }
    }

    async function deleteBackup(filename) {
        if (!confirm('Confirm permanent deletion of this snapshot?')) return;
        
        const res = await fetch('api/maintenance.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete', filename: filename })
        });
        const data = await res.json();
        if (data.success) loadBackups();
    }

    function triggerRestore(filename) {
        openModal('restore', filename);
    }

    document.getElementById('btn-backup-now').addEventListener('click', () => handleSimpleAction('backup'));
    document.getElementById('btn-wipe-data').addEventListener('click', () => openModal('wipe'));
    document.getElementById('modal-cancel').addEventListener('click', closeModal);
    document.getElementById('modal-confirm').addEventListener('click', executeMaintenance);

    // Close on escape
    window.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeModal(); });

    loadBackups();
</script>

<?php include 'layout/footer.php'; ?>
