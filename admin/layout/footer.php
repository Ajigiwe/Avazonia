</main>

<!-- Notification Container -->
<div id="notification-toast-container"></div>

<script>
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
