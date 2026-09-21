</main>

<!-- Notification Container -->
<div id="notification-toast-container"></div>

<!-- ── Shared Admin Dialog System (replaces native alert/confirm/prompt) ── -->
<style>
    .adlg-backdrop {
        position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center;
        padding: 20px; background: rgba(13,13,13,0.55); backdrop-filter: blur(6px);
        opacity: 0; transition: opacity 0.25s ease;
    }
    .adlg-backdrop.active { opacity: 1; }
    .adlg-box {
        background: #fff; width: 100%; max-width: 420px; border-radius: 16px; overflow: hidden;
        border: 1px solid var(--light-gray); box-shadow: 0 30px 80px rgba(13,13,13,0.35);
        transform: translateY(16px) scale(0.97); transition: transform 0.25s cubic-bezier(0.19, 1, 0.22, 1);
        font-family: var(--f-body, 'Inter', sans-serif);
    }
    .adlg-backdrop.active .adlg-box { transform: translateY(0) scale(1); }
    .adlg-accent { height: 4px; width: 100%; background: var(--red); }
    .adlg-accent.is-info { background: #1d39c4; }
    .adlg-accent.is-warn { background: #fa8c16; }
    .adlg-accent.is-success { background: #00a854; }
    .adlg-body { padding: 28px 28px 8px; text-align: left; }
    .adlg-title { font-family: var(--f-display, 'Outfit', sans-serif); font-weight: 800; font-size: 18px; text-transform: uppercase; letter-spacing: -0.01em; color: var(--ink); margin-bottom: 10px; }
    .adlg-msg { font-size: 13.5px; line-height: 1.55; color: #555; word-wrap: break-word; }
    .adlg-input {
        width: 100%; box-sizing: border-box; margin-top: 14px; height: 46px; padding: 0 14px;
        border: 1.5px solid var(--light-gray); border-radius: 10px; font-size: 14px; color: var(--ink);
        outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .adlg-input:focus { border-color: var(--ink); box-shadow: 0 0 0 3px rgba(13,13,13,0.06); }
    .adlg-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 20px 24px 24px; }
    .adlg-btn {
        min-width: 96px; height: 42px; padding: 0 20px; border-radius: 10px; cursor: pointer;
        font-family: var(--f-semi, 'Inter', sans-serif); font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em; transition: all 0.15s; border: none;
    }
    .adlg-btn:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
    .adlg-btn-secondary { background: var(--off, #f5f5f5); color: var(--ink); border: 1.5px solid var(--light-gray); }
    .adlg-btn-secondary:hover { border-color: var(--ink); }
    .adlg-btn-danger { background: linear-gradient(135deg, var(--red) 0%, var(--red-deep, #b8001f) 100%); color: #fff; box-shadow: 0 4px 14px rgba(232,0,45,0.25); }
    .adlg-btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(232,0,45,0.35); }
    .adlg-btn-primary { background: var(--ink); color: #fff; }
    .adlg-btn-primary:hover { opacity: 0.9; }
    .adlg-btn[disabled] { opacity: 0.6; cursor: wait; transform: none !important; }

    /* Styled toast feedback (success / error) */
    .adlg-toast {
        position: fixed; top: 24px; right: 24px; z-index: 10001; display: flex; flex-direction: column; gap: 10px;
        pointer-events: none;
    }
    .adlg-toast-item {
        pointer-events: auto; display: flex; align-items: flex-start; gap: 12px; min-width: 300px; max-width: 380px;
        background: var(--ink, #0d0d0d); color: #fff; padding: 14px 16px; border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25); border-left: 4px solid var(--red);
        font-size: 13px; line-height: 1.45; word-wrap: break-word;
        animation: adlg-toast-in 0.4s cubic-bezier(0.19, 1, 0.22, 1) forwards;
        transition: opacity 0.4s, transform 0.4s;
    }
    .adlg-toast-item.is-success { border-left-color: #00a854; }
    .adlg-toast-item.is-info { border-left-color: #1d39c4; }
    .adlg-toast-item.is-leaving { opacity: 0; transform: translateX(110%); }
    .adlg-toast-icon { flex-shrink: 0; margin-top: 1px; }
    .adlg-toast-close { cursor: pointer; opacity: 0.55; margin-left: auto; font-size: 16px; line-height: 1; background: none; border: none; color: #fff; padding: 0; }
    .adlg-toast-close:hover { opacity: 1; }
    @keyframes adlg-toast-in { from { transform: translateX(110%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
<script>
(function() {
    'use strict';
    if (window.AdminUI) return; // idempotent

    const ICONS = {
        danger: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff4d6a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#00c853" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#adc6ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    };

    let activeBox = null; // { backdrop, resolve, onCancel }

    function closeDialog(result) {
        if (!activeBox) return;
        const { backdrop, resolve } = activeBox;
        activeBox = null;
        document.removeEventListener('keydown', onKeydown, true);
        backdrop.classList.remove('active');
        setTimeout(() => backdrop.remove(), 250);
        resolve(result);
    }

    function onKeydown(e) {
        if (!activeBox) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); closeDialog(null); }
        if (e.key === 'Enter' && activeBox && !e.defaultPrevented) {
            // Enter confirms dialog + prompt input
            const btn = activeBox.backdrop.querySelector('[data-adlg-primary]');
            if (btn && document.activeElement !== btn) { e.preventDefault(); btn.click(); }
        }
    }

    function openDialog(opts) {
        if (activeBox) closeDialog(false); // one at a time; queue-free by design
        const {
            title = 'Are you sure?',
            message = '',
            tone = 'danger',            // danger | warn | info | success
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            showCancel = true,
            input = null                // { value, placeholder, type, validate } => prompt mode
        } = opts;

        return new Promise((resolve) => {
            const backdrop = document.createElement('div');
            backdrop.className = 'adlg-backdrop';
            const toneClass = tone === 'success' ? 'is-success' : tone === 'warn' ? 'is-warn' : tone === 'info' ? 'is-info' : '';
            const confirmClass = tone === 'danger' || tone === 'warn' ? 'adlg-btn-danger' : 'adlg-btn-primary';

            backdrop.innerHTML = `
                <div class="adlg-box" role="${showCancel ? 'dialog' : 'alertdialog'}" aria-modal="true" aria-label="${title.replace(/"/g, '&quot;')}">
                    <div class="adlg-accent ${toneClass}"></div>
                    <div class="adlg-body">
                        <div class="adlg-title">${title}</div>
                        <div class="adlg-msg">${message}</div>
                        ${input ? `<input class="adlg-input" type="${input.type || 'text'}" placeholder="${(input.placeholder || '').replace(/"/g, '&quot;')}">` : ''}
                    </div>
                    <div class="adlg-actions">
                        ${showCancel ? '<button type="button" class="adlg-btn adlg-btn-secondary" data-adlg-cancel>' + cancelText + '</button>' : ''}
                        <button type="button" class="adlg-btn ${confirmClass}" data-adlg-primary>${confirmText}</button>
                    </div>
                </div>`;
            document.body.appendChild(backdrop);

            const inputEl = backdrop.querySelector('.adlg-input');
            const confirmBtn = backdrop.querySelector('[data-adlg-primary]');
            requestAnimationFrame(() => {
                backdrop.classList.add('active');
                (inputEl || confirmBtn).focus();
                if (inputEl && inputEl.value) inputEl.select();
            });

            activeBox = { backdrop, resolve, onCancel: null };

            if (input) {
                inputEl.addEventListener('input', () => {
                    const ok = !input.validate || input.validate(inputEl.value);
                    inputEl.style.borderColor = inputEl.value && !ok ? 'var(--red)' : '';
                });
                inputEl.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); }
                });
            }
            const cancelBtn = backdrop.querySelector('[data-adlg-cancel]');
            if (cancelBtn) cancelBtn.addEventListener('click', () => closeDialog(null));
            backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop) closeDialog(null); });
            confirmBtn.addEventListener('click', () => {
                const value = inputEl ? inputEl.value : true;
                if (input && input.validate && !input.validate(value)) {
                    inputEl.style.borderColor = 'var(--red)';
                    inputEl.focus();
                    return;
                }
                confirmBtn.disabled = true;
                closeDialog(value);
            });
            document.addEventListener('keydown', onKeydown, true);
        });
    }

    const toastHost = document.createElement('div');
    toastHost.className = 'adlg-toast';
    document.addEventListener('DOMContentLoaded', () => { if (!toastHost.isConnected) document.body.appendChild(toastHost); });
    if (document.readyState !== 'loading' && !toastHost.isConnected) document.body.appendChild(toastHost);

    function toast(message, type = 'error', duration = 5000) {
        const item = document.createElement('div');
        item.className = 'adlg-toast-item' + (type === 'success' ? ' is-success' : type === 'info' ? ' is-info' : '');
        const icon = type === 'success' ? ICONS.success : type === 'info' ? ICONS.info : ICONS.danger;
        item.innerHTML = `
            <span class="adlg-toast-icon">${icon}</span>
            <span style="flex:1;">${message}</span>
            <button type="button" class="adlg-toast-close" aria-label="Dismiss">&times;</button>`;
        toastHost.appendChild(item);
        const kill = () => { item.classList.add('is-leaving'); setTimeout(() => item.remove(), 450); };
        item.querySelector('.adlg-toast-close').addEventListener('click', kill);
        setTimeout(kill, duration);
    }

    window.AdminUI = {
        confirm: (message, opts = {}) => openDialog({
            title: opts.title || 'Please Confirm',
            message: message,
            tone: opts.tone || 'danger',
            confirmText: opts.confirmText || 'Confirm',
            cancelText: opts.cancelText || 'Cancel'
        }).then((r) => r === true),
        alert: (message, opts = {}) => openDialog({
            title: opts.title || (opts.tone === 'success' ? 'Success' : 'Notice'),
            message: message,
            tone: opts.tone || 'info',
            confirmText: 'OK',
            showCancel: false
        }),
        prompt: (message, opts = {}) => openDialog({
            title: opts.title || 'Input Required',
            message: message,
            tone: opts.tone || 'info',
            confirmText: opts.confirmText || 'OK',
            input: {
                value: opts.value || '',
                placeholder: opts.placeholder || '',
                type: opts.type || 'text',
                validate: opts.validate
            }
        }).then((r) => (r === null ? null : r)),
        success: (message) => toast(message, 'success'),
        error: (message) => toast(message, 'error'),
        info: (message) => toast(message, 'info'),
        toast: toast
    };

    // ── Delegated data-confirm handling (forms + links) ──
    document.addEventListener('click', async (e) => {
        const el = e.target.closest('a[data-confirm], button[data-confirm]');
        if (!el) return;
        if (el.dataset.confirmed === '1') { delete el.dataset.confirmed; return; } // synthetic pass-through
        e.preventDefault();
        e.stopPropagation();
        const ok = await window.AdminUI.confirm(el.getAttribute('data-confirm'), {
            title: el.getAttribute('data-confirm-title') || 'Please Confirm'
        });
        if (!ok) return;
        el.dataset.confirmed = '1'; // guard so the synthetic re-click passes through
        if (el.tagName === 'A') {
            if (el.target === '_blank') window.open(el.href, '_blank');
            else window.location.href = el.href;
        } else {
            el.click();
        }
    }, true);

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('form[data-confirm]');
        if (!form || form.dataset.confirmed === '1') { if (form) delete form.dataset.confirmed; return; } // synthetic pass-through
        e.preventDefault();
        e.stopPropagation();
        const ok = await window.AdminUI.confirm(form.getAttribute('data-confirm'), {
            title: form.getAttribute('data-confirm-title') || 'Please Confirm'
        });
        if (ok) {
            form.dataset.confirmed = '1'; // one-shot pass-through, cleared by the handler above
            // Re-submit natively so the original submit button (name/value) is included.
            const submitter = (e.submitter instanceof HTMLElement) ? e.submitter : form.querySelector('button[type=submit], input[type=submit]');
            if (typeof form.requestSubmit === 'function' && submitter) form.requestSubmit(submitter); else form.submit();
        }
    }, true);
})();
</script>
<!-- ── End Shared Admin Dialog System ── -->

<script>
// ── CSRF Token Interceptor (Admin) ─────────────────────────────────
(function() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    if (!csrfToken) return;

    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        options = options || {};
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            if (!options.headers) {
                options.headers = {};
            }
            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-CSRF-Token')) {
                    options.headers.set('X-CSRF-Token', csrfToken);
                }
            } else {
                if (!options.headers['X-CSRF-Token']) {
                    options.headers['X-CSRF-Token'] = csrfToken;
                }
            }
            if (options.body instanceof FormData && !options.body.has('_csrf_token')) {
                options.body.append('_csrf_token', csrfToken);
            }
        }
        return originalFetch.call(this, url, options);
    };
})();
// ── End CSRF Interceptor ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    let seenNotifications = new Set();
    const pollInterval = 30000; // 30 seconds

    function fetchNotifications() {
        fetch('<?= APP_URL ?>/admin/api/notifications.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        if (!seenNotifications.has(notif.id)) {
                            showToast(notif);
                            seenNotifications.add(notif.id);
                        }
                    });
                }
            })
            .catch(err => console.error('Notification poll error:', err));
    }

    function showToast(notif) {
        const container = document.getElementById('notification-toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast-alert';
        
        let link = 'orders.php';
        if (notif.data) {
            try {
                const meta = JSON.parse(notif.data);
                if (meta.order_id) link = 'view-order.php?id=' + meta.order_id;
            } catch(e) {}
        }

        toast.innerHTML = `
            <div style="flex: 1;">
                <div style="font-weight: 800; margin-bottom: 4px;">ORDER ALERT</div>
                <div style="opacity: 0.8; font-size: 11px;">${notif.message}</div>
            </div>
            <a href="${link}" class="view-link">View</a>
            <div class="close-toast" onclick="this.parentElement.remove()">×</div>
        `;

        container.appendChild(toast);

        // Play sound if possible (optional)
        // const audio = new Audio('<?= APP_URL ?>/assets/sounds/notification.mp3');
        // audio.play().catch(e => {});

        // Auto remove after 10 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.5s';
                setTimeout(() => toast.remove(), 500);
            }
        }, 10000);
        
        // Mark as read immediately when shown or when clicked
        markAsRead(notif.id);
    }

    function markAsRead(id) {
        const formData = new FormData();
        formData.append('id', id);
        fetch('<?= APP_URL ?>/admin/api/notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        });
    }

    // Initial check and set interval
    fetchNotifications();
    setInterval(fetchNotifications, pollInterval);
});
</script>

</body>
</html>
