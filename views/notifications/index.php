<?php
$title = 'Notifications';
$active_page = 'notifications';
ob_start();
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔔</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count($notifications ?? []) ?></div>
        <div class="kpi-card__label">Total Notifications</div>
        <div class="kpi-card__status">Received</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔴</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($notifications ?? [], fn($n) => !($n['is_read'] ?? false))) ?></div>
        <div class="kpi-card__label">Unread</div>
        <div class="kpi-card__status kpi-card__status--pending">Pending</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($notifications ?? [], fn($n) => ($n['is_read'] ?? false))) ?></div>
        <div class="kpi-card__label">Read</div>
        <div class="kpi-card__status">Processed</div>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <?php if (empty($notifications ?? [])): ?>
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>No Notifications</h3>
                <p>You're all caught up! No new notifications.</p>
            </div>
        <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= ($notification['is_read'] ?? false) ? 'notification-item--read' : 'notification-item--unread' ?>" data-notification-id="<?= $notification['id'] ?? 0 ?>">
                    <div class="notification-header">
                        <h4 class="notification-title"><?= htmlspecialchars($notification['title'] ?? 'Notification') ?></h4>
                        <span class="notification-time"><?= date('M d, H:i', strtotime($notification['created_at'] ?? 'now')) ?></span>
                    </div>
                    <p class="notification-message"><?= htmlspecialchars($notification['message'] ?? 'No message') ?></p>
                    <?php if (!($notification['is_read'] ?? false)): ?>
                    <div class="notification-actions">
                        <button class="btn btn--sm btn--primary" onclick="markAsRead(<?= $notification['id'] ?? 0 ?>)">
                            Mark as Read
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function markAsRead(id) {
    fetch('/ergon/api/notifications/mark-as-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to mark as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

function markAllAsRead() {
    fetch('/ergon/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to mark all as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>