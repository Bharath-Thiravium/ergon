<div class="notifications-header">
    <div class="notifications-stats">
        <div class="stat-card stat-card--total">
            <div class="stat-icon">📬</div>
            <div class="stat-content">
                <div class="stat-number"><?= count($notifications ?? []) ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        <div class="stat-card stat-card--unread">
            <div class="stat-icon">🔴</div>
            <div class="stat-content">
                <div class="stat-number"><?= count(array_filter($notifications ?? [], fn($n) => !($n['is_read'] ?? false))) ?></div>
                <div class="stat-label">Unread</div>
            </div>
        </div>
        <div class="stat-card stat-card--read">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?= count(array_filter($notifications ?? [], fn($n) => ($n['is_read'] ?? false))) ?></div>
                <div class="stat-label">Read</div>
            </div>
        </div>
    </div>
    <div class="notifications-actions">
        <button class="btn btn--secondary" onclick="filterNotifications('all')">All</button>
        <button class="btn btn--primary" onclick="filterNotifications('unread')">Unread Only</button>
        <button class="btn btn--success" onclick="markAllAsRead()">Mark All Read</button>
    </div>
</div>

<div class="notifications-container">
    <?php if (empty($notifications ?? [])): ?>
        <div class="empty-state">
            <div class="empty-icon">🔔</div>
            <h3>No Notifications</h3>
            <p>You're all caught up! No new notifications.</p>
        </div>
    <?php else: ?>
        <div class="notifications-table-wrapper">
            <table class="notifications-table">
                <thead>
                    <tr>
                        <th class="col-status"></th>
                        <th class="col-type">Type</th>
                        <th class="col-message">Message</th>
                        <th class="col-sender">From</th>
                        <th class="col-time">Time</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): 
                        $isUnread = !($notification['is_read'] ?? false);
                        $moduleIcon = ['task' => '✅', 'leave' => '📅', 'expense' => '💰', 'advance' => '💳'][$notification['module_name']] ?? '🔔';
                        $viewUrl = $notification['link'] ?? "/ergon/{$notification['module_name']}";
                    ?>
                    <tr class="notification-row <?= $isUnread ? 'notification-row--unread' : 'notification-row--read' ?>" data-notification-id="<?= $notification['id'] ?>">
                        <td class="col-status">
                            <?php if ($isUnread): ?>
                                <div class="status-indicator status-indicator--unread" title="Unread"></div>
                            <?php else: ?>
                                <div class="status-indicator status-indicator--read" title="Read"></div>
                            <?php endif; ?>
                        </td>
                        <td class="col-type">
                            <div class="notification-type">
                                <span class="type-icon"><?= $moduleIcon ?></span>
                                <span class="type-text"><?= ucfirst($notification['module_name'] ?? 'General') ?></span>
                            </div>
                        </td>
                        <td class="col-message">
                            <div class="message-content">
                                <div class="message-text"><?= htmlspecialchars($notification['message'] ?? 'No message') ?></div>
                                <div class="message-meta">
                                    <span class="action-type"><?= ucfirst($notification['action_type'] ?? '') ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="col-sender">
                            <span class="sender-name"><?= htmlspecialchars($notification['sender_name'] ?? 'System') ?></span>
                        </td>
                        <td class="col-time">
                            <div class="time-info">
                                <div class="time-relative"><?= timeAgo($notification['created_at']) ?></div>
                                <div class="time-exact"><?= date('M j, H:i', strtotime($notification['created_at'])) ?></div>
                            </div>
                        </td>
                        <td class="col-actions">
                            <div class="action-buttons">
                                <?php if ($isUnread): ?>
                                    <button class="btn btn--xs btn--primary" onclick="markAsRead(<?= $notification['id'] ?>)" title="Mark as read">
                                        <i class="bi bi-check"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="<?= $viewUrl ?>" class="btn btn--xs btn--secondary" title="View details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
?>

<script>
function markAsRead(id) {
    fetch('/ergon/api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark-read&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`[data-notification-id="${id}"]`);
            if (row) {
                row.classList.remove('notification-row--unread');
                row.classList.add('notification-row--read');
                const statusIndicator = row.querySelector('.status-indicator');
                if (statusIndicator) {
                    statusIndicator.className = 'status-indicator status-indicator--read';
                    statusIndicator.title = 'Read';
                }
                const markButton = row.querySelector('.btn--primary');
                if (markButton) markButton.remove();
            }
            updateNotificationCounts();
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
    fetch('/ergon/api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=mark-all-read'
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

function filterNotifications(type) {
    const rows = document.querySelectorAll('.notification-row');
    rows.forEach(row => {
        if (type === 'all') {
            row.style.display = '';
        } else if (type === 'unread') {
            row.style.display = row.classList.contains('notification-row--unread') ? '' : 'none';
        }
    });
}

function toggleTypeGroup(groupId) {
    const content = document.getElementById(groupId);
    const button = content.previousElementSibling.querySelector('button i');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        button.className = 'bi bi-chevron-up';
    } else {
        content.style.display = 'none';
        button.className = 'bi bi-chevron-down';
    }
}

function updateNotificationCounts() {
    const unreadCount = document.querySelectorAll('.notification-row--unread').length;
    const readCount = document.querySelectorAll('.notification-row--read').length;
    
    const unreadStat = document.querySelector('.stat-card--unread .stat-number');
    const readStat = document.querySelector('.stat-card--read .stat-number');
    
    if (unreadStat) unreadStat.textContent = unreadCount;
    if (readStat) readStat.textContent = readCount;
}

// Auto-collapse older notification groups
document.addEventListener('DOMContentLoaded', function() {
    const typeGroups = document.querySelectorAll('.notification-type-content');
    typeGroups.forEach((group, index) => {
        if (index > 2) { // Collapse groups after the first 3
            group.style.display = 'none';
            const button = group.previousElementSibling.querySelector('button i');
            if (button) button.className = 'bi bi-chevron-down';
        }
    });
});
</script>

<style>
.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.notifications-stats {
    display: flex;
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 6px;
    min-width: 120px;
}

.stat-card--unread { background: #fef2f2; }
.stat-card--read { background: #f0fdf4; }

.stat-icon {
    font-size: 1.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.notifications-actions {
    display: flex;
    gap: 0.5rem;
}

.notifications-container {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.notifications-table {
    width: 100%;
    border-collapse: collapse;
}

.notifications-table th {
    background: #f8fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.notifications-table td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.notification-row--unread {
    background: #fffbeb;
    border-left: 3px solid #f59e0b;
}

.notification-row--read {
    background: #fff;
    opacity: 0.8;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator--unread { background: #f59e0b; }
.status-indicator--read { background: #10b981; }

.notification-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.type-icon {
    font-size: 1.25rem;
}

.type-text {
    font-weight: 500;
    color: #374151;
}

.message-content {
    max-width: 400px;
}

.message-text {
    color: #1f2937;
    line-height: 1.5;
    margin-bottom: 0.25rem;
}

.message-meta {
    font-size: 0.75rem;
    color: #6b7280;
}

.action-type {
    background: #e5e7eb;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    text-transform: capitalize;
}

.sender-name {
    font-weight: 500;
    color: #374151;
}

.time-info {
    text-align: right;
}

.time-relative {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.time-exact {
    font-size: 0.75rem;
    color: #6b7280;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.col-status { width: 20px; }
.col-type { width: 120px; }
.col-message { width: auto; }
.col-sender { width: 150px; }
.col-time { width: 120px; }
.col-actions { width: 80px; }

@media (max-width: 768px) {
    .notifications-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .notifications-stats {
        width: 100%;
        justify-content: space-between;
    }
    
    .stat-card {
        flex: 1;
        min-width: auto;
    }
    
    .notifications-table-wrapper {
        overflow-x: auto;
    }
    
    .message-content {
        max-width: 200px;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>