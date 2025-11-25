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
                <?php 
                // Group notifications by type and date for better organization
                $groupedNotifications = [];
                foreach ($notifications as $notification) {
                    $date = date('Y-m-d', strtotime($notification['created_at'] ?? 'now'));
                    $type = $notification['module_name'] ?? 'general';
                    $groupedNotifications[$date][$type][] = $notification;
                }
                
                foreach ($groupedNotifications as $date => $typeGroups): 
                    $dateLabel = $date === date('Y-m-d') ? 'Today' : ($date === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('M d, Y', strtotime($date)));
                ?>
                <div class="notification-date-group">
                    <h3 class="notification-date-header"><?= $dateLabel ?></h3>
                    <?php foreach ($typeGroups as $type => $typeNotifications): ?>
                        <?php if (count($typeNotifications) > 1): ?>
                        <div class="notification-type-group">
                            <h4 class="notification-type-header">
                                <?= ucfirst($type) ?> (<?= count($typeNotifications) ?>)
                                <button class="btn btn--xs btn--secondary" onclick="toggleTypeGroup('<?= $type ?>-<?= $date ?>')">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </h4>
                            <div class="notification-type-content" id="<?= $type ?>-<?= $date ?>">
                        <?php endif; ?>
                        
                        <?php foreach ($typeNotifications as $notification): 
                            $isFromSelf = ($notification['sender_id'] ?? 0) == ($_SESSION['user_id'] ?? 0);
                            $priority = $notification['action_type'] === 'reminder' ? 'high' : 'normal';
                        ?>
                        <div class="notification-item notification-item--<?= $priority ?> <?= ($notification['is_read'] ?? false) ? 'notification-item--read' : 'notification-item--unread' ?> <?= $isFromSelf ? 'notification-item--self' : '' ?>" data-notification-id="<?= $notification['id'] ?? 0 ?>">
                            <div class="notification-header">
                                <div class="notification-meta">
                                    <span class="notification-type-badge notification-type-badge--<?= $type ?>">
                                        <?php 
                                        $icons = ['task' => '✅', 'leave' => '📅', 'expense' => '💰', 'advance' => '💳', 'reminder' => '⏰'];
                                        echo $icons[$type] ?? '🔔';
                                        ?>
                                        <?= ucfirst($notification['action_type'] ?? 'notification') ?>
                                    </span>
                                    <?php if ($notification['action_type'] === 'reminder'): ?>
                                    <span class="notification-priority-badge">⚡ Priority</span>
                                    <?php endif; ?>
                                </div>
                                <span class="notification-time"><?= date('H:i', strtotime($notification['created_at'] ?? 'now')) ?></span>
                            </div>
                            <p class="notification-message"><?= htmlspecialchars($notification['message'] ?? 'No message') ?></p>
                            <?php if (!($notification['is_read'] ?? false)): ?>
                            <div class="notification-actions">
                                <button class="btn btn--sm btn--primary" onclick="markAsRead(<?= $notification['id'] ?? 0 ?>)">
                                    Mark as Read
                                </button>
                                <?php if ($notification['module_name'] && $notification['reference_id']): ?>
                                <a href="/ergon/<?= $notification['module_name'] ?>/view/<?= $notification['reference_id'] ?>" class="btn btn--sm btn--secondary">
                                    View Details
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($typeNotifications) > 1): ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
            // Update UI without reload
            const notificationItem = document.querySelector(`[data-notification-id="${id}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('notification-item--unread');
                notificationItem.classList.add('notification-item--read');
                const actions = notificationItem.querySelector('.notification-actions');
                if (actions) actions.remove();
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
    const unreadCount = document.querySelectorAll('.notification-item--unread').length;
    const readCount = document.querySelectorAll('.notification-item--read').length;
    
    // Update KPI cards if they exist
    const unreadCard = document.querySelector('.kpi-card--warning .kpi-card__value');
    const readCard = document.querySelector('.kpi-card:not(.kpi-card--warning) .kpi-card__value');
    
    if (unreadCard) unreadCard.textContent = unreadCount;
    if (readCard) readCard.textContent = readCount;
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
.notification-date-group {
    margin-bottom: 2rem;
}

.notification-date-header {
    font-size: 1.2rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.notification-type-group {
    margin-bottom: 1.5rem;
}

.notification-type-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 1rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.notification-item--high {
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
}

.notification-item--self {
    opacity: 0.7;
    background: #f9fafb;
}

.notification-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.notification-type-badge--task { background: #dbeafe; color: #1e40af; }
.notification-type-badge--leave { background: #fef3c7; color: #92400e; }
.notification-type-badge--expense { background: #d1fae5; color: #065f46; }
.notification-type-badge--reminder { background: #fecaca; color: #991b1b; }

.notification-priority-badge {
    background: #fef2f2;
    color: #dc2626;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>