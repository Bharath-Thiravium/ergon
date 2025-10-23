<?php
$title = 'Notifications';
$active_page = 'notifications';
ob_start();
?>

<div class="page-header">
    <h1>🔔 Notifications</h1>
    <div class="header-actions">
        <button class="btn btn--secondary" onclick="markAllAsRead()">Mark All Read</button>
    </div>
</div>

<div class="notifications-container">
    <?php if (empty($data['notifications'])): ?>
    <div class="empty-state">
        <div class="empty-icon">🔔</div>
        <h3>No notifications yet</h3>
        <p>You'll see important updates and messages here.</p>
    </div>
    <?php else: ?>
    <div class="notification-items">
        <?php foreach ($data['notifications'] as $notification): ?>
        <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $notification['id'] ?>">
            <div class="notification-icon notification-icon--<?= $notification['type'] ?>">
                <?php
                $icons = ['info' => 'ℹ️', 'success' => '✅', 'warning' => '⚠️', 'error' => '❌'];
                echo $icons[$notification['type']] ?? 'ℹ️';
                ?>
            </div>
            <div class="notification-content">
                <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                <div class="notification-time"><?= date('M d, Y H:i', strtotime($notification['created_at'])) ?></div>
            </div>
            <?php if (!$notification['is_read']): ?>
            <button class="notification-mark-read" onclick="markAsRead(<?= $notification['id'] ?>)">Mark Read</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.notifications-container { max-width: 800px; margin: 0 auto; }
.empty-state { text-align: center; padding: 60px 20px; color: #666; }
.empty-icon { font-size: 48px; margin-bottom: 20px; }
.notification-items { display: flex; flex-direction: column; gap: 12px; }
.notification-item { display: flex; align-items: flex-start; padding: 16px; background: white; border-radius: 8px; border-left: 4px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.notification-item.unread { border-left-color: #2196f3; background: #f8f9ff; }
.notification-icon { font-size: 20px; margin-right: 12px; margin-top: 2px; }
.notification-content { flex: 1; }
.notification-title { font-weight: 600; color: #333; margin-bottom: 4px; }
.notification-message { color: #666; line-height: 1.4; margin-bottom: 8px; }
.notification-time { font-size: 12px; color: #999; }
.notification-mark-read { background: #2196f3; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; margin-left: 12px; }
</style>

<script>
function markAsRead(id) {
    fetch('/ergon/api/notifications/mark-read', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({notification_id: id})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${id}"]`);
            item.classList.remove('unread');
            item.classList.add('read');
            const btn = item.querySelector('.notification-mark-read');
            if (btn) btn.remove();
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>