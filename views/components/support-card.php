<?php
// views/components/support-card.php
global $dbSettings;
?>
<div class="support-banner-container">
    <div class="support-banner-inner container">
        <div class="support-banner-left">
            <div class="support-banner-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div class="support-banner-text">
                <h3><?= htmlspecialchars($dbSettings['support_title'] ?? 'Need Any Help?') ?></h3>
                <p><?= htmlspecialchars($dbSettings['support_subtitle'] ?? 'We are here to help you with any question.') ?></p>
            </div>
        </div>
        
        <div class="support-banner-center">
            <div class="support-banner-phone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
                <span><?= htmlspecialchars($dbSettings['support_phone'] ?? '+233 201500300') ?></span>
            </div>
            <div class="support-banner-hours">
                <?= htmlspecialchars($dbSettings['support_hours'] ?? 'Monday to Saturday - 9am - 6pm') ?>
            </div>
        </div>
    </div>
</div>
