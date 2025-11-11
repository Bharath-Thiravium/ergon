<?php
$title = 'Notifications';
$active_page = 'notifications';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🔔</span> Notifications</h1>
        <p>Real-time system updates and alerts</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="refreshNotifications()">
            <span>🔄</span> Refresh
        </button>
        <button class="btn btn--primary" onclick="markAllAsRead()">
            <span>✅</span> Mark All Read
        </button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔔</div>
            <div class="kpi-card__trend">↗ Live</div>
        </div>
        <div class="kpi-card__value"><?= count($notifications ?? []) ?></div>
        <div class="kpi-card__label">Total Notifications</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔴</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ New</div>
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
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend">— Today</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($notifications ?? [], fn($n) => date('Y-m-d', strtotime($n['created_at'] ?? 'now')) === date('Y-m-d'))) ?></div>
        <div class="kpi-card__label">Today's Alerts</div>
        <div class="kpi-card__status">Current</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>🔔</span> Live Notifications
        </h2>
        <div class="card__actions">
            <select id="filterType" onchange="filterNotifications()" class="form-control" style="width: auto;">
                <option value="">All Types</option>
                <option value="leave_request">Leave Requests</option>
                <option value="expense_claim">Expense Claims</option>
                <option value="attendance_alert">Attendance Alerts</option>
                <option value="task_overdue">Task Overdue</option>
                <option value="workflow_missing">Workflow Missing</option>
            </select>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($notifications ?? [])): ?>
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>No Notifications</h3>
                <p>All caught up! No new notifications at the moment.</p>
                <button class="btn btn--primary" onclick="refreshNotifications()">
                    <span>🔄</span> Check for Updates
                </button>
            </div>
        <?php else: ?>
            <div class="notification-list" id="notificationList">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= ($notification['is_read'] ?? false) ? 'notification-item--read' : 'notification-item--unread' ?>" 
                     data-notification-id="<?= $notification['id'] ?? 0 ?>" 
                     data-type="<?php
                        $moduleType = $notification['module_name'] ?? $notification['type'] ?? '';
                        // Map module names to filter values
                        switch ($moduleType) {
                            case 'leave': echo 'leave_request'; break;
                            case 'expense': echo 'expense_claim'; break;
                            case 'attendance': echo 'attendance_alert'; break;
                            case 'task': echo 'task_overdue'; break;
                            case 'workflow': echo 'workflow_missing'; break;
                            default: echo htmlspecialchars($moduleType);
                        }
                     ?>">
                    
                    <div class="notification-icon">
                        <?php
                        $type = $notification['type'] ?? $notification['module_name'] ?? '';
                        $icon = '🔔';
                        switch ($type) {
                            case 'leave':
                            case 'leave_request': $icon = '📅'; break;
                            case 'expense':
                            case 'expense_claim': $icon = '💰'; break;
                            case 'attendance':
                            case 'attendance_alert': $icon = '⏰'; break;
                            case 'task':
                            case 'task_overdue': $icon = '⚠️'; break;
                            case 'workflow':
                            case 'workflow_missing': $icon = '📋'; break;
                            default: $icon = '🔔';
                        }
                        echo $icon;
                        ?>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4 class="notification-title"><?= htmlspecialchars($notification['title'] ?? $notification['message'] ?? 'Notification') ?></h4>
                            <div class="notification-meta">
                                <span class="notification-time"><?= date('M d, H:i', strtotime($notification['created_at'] ?? 'now')) ?></span>
                                <?php if ($notification['actor_name'] ?? $notification['sender_name'] ?? false): ?>
                                    <span class="notification-actor">by <?= htmlspecialchars($notification['actor_name'] ?? $notification['sender_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="notification-message"><?= htmlspecialchars($notification['message'] ?? 'No message') ?></p>
                        
                        <div class="notification-actions">
                            <?php if (!($notification['is_read'] ?? false)): ?>
                                <button class="btn btn--sm btn--primary" onclick="markAsRead(<?= $notification['id'] ?? 0 ?>)">
                                    <span>✅</span> Mark Read
                                </button>
                            <?php endif; ?>
                            
                            <?php if (($notification['reference_type'] ?? false) && ($notification['reference_id'] ?? false)): ?>
                                <button class="btn btn--sm btn--secondary" onclick="viewReference('<?= $notification['reference_type'] ?>', <?= $notification['reference_id'] ?>)">
                                    <span>👁️</span> View Details
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!($notification['is_read'] ?? false)): ?>
                        <div class="notification-badge"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 0.75rem;
    position: relative;
    transition: all 0.3s ease;
}

.notification-item--unread {
    background: #f8fafc;
    border-left: 4px solid #3b82f6;
}

.notification-item--read {
    background: #ffffff;
    opacity: 0.8;
}

.notification-icon {
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.notification-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.notification-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.notification-time {
    font-weight: 500;
}

.notification-actor {
    margin-top: 0.25rem;
    font-style: italic;
}

.notification-message {
    margin: 0 0 1rem 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
}

.notification-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
}

.card__actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

@media (max-width: 768px) {
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notification-meta {
        align-items: flex-start;
        margin-top: 0.5rem;
    }
    
    .notification-actions {
        flex-wrap: wrap;
    }
}
</style>

<script>
function markAsRead(id) {
    fetch('/ergon/notifications/mark-as-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-notification-id="${id}"]`);
            if (item) {
                item.classList.remove('notification-item--unread');
                item.classList.add('notification-item--read');
                const badge = item.querySelector('.notification-badge');
                if (badge) badge.remove();
                const button = item.querySelector('.btn--primary');
                if (button) button.remove();
            }
            updateCounts();
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
    fetch('/ergon/notifications/mark-all-read', {
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

function refreshNotifications() {
    location.reload();
}

function filterNotifications() {
    const filterType = document.getElementById('filterType').value;
    const notifications = document.querySelectorAll('.notification-item');
    let visibleCount = 0;
    
    notifications.forEach(item => {
        const type = item.getAttribute('data-type') || '';
        if (!filterType || type === filterType || type.includes(filterType)) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show count of filtered results
    console.log(`Showing ${visibleCount} notifications for filter: ${filterType || 'All'}`);
}

function viewReference(type, id) {
    let url = '';
    switch (type) {
        case 'leave':
            url = `/ergon/leaves/view/${id}`;
            break;
        case 'expense':
            url = `/ergon/expenses/view/${id}`;
            break;
        case 'task':
            url = `/ergon/tasks/view/${id}`;
            break;
        case 'attendance':
            url = `/ergon/attendance`;
            break;
        case 'workflow':
            url = `/ergon/daily-workflow/morning-planner`;
            break;
        default:
            alert('Reference not available');
            return;
    }
    window.open(url, '_blank');
}

function updateCounts() {
    const unreadCount = document.querySelectorAll('.notification-item--unread').length;
    const readCount = document.querySelectorAll('.notification-item--read').length;
    
    // Update KPI cards if they exist
    const kpiCards = document.querySelectorAll('.kpi-card__value');
    if (kpiCards.length >= 3) {
        kpiCards[1].textContent = unreadCount;
        kpiCards[2].textContent = readCount;
    }
}

// Auto-refresh notifications every 30 seconds
setInterval(() => {
    fetch('/ergon/api/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            if (data.count > document.querySelectorAll('.notification-item--unread').length) {
                // New notifications available, show refresh button
                const refreshBtn = document.querySelector('[onclick="refreshNotifications()"]');
                if (refreshBtn) {
                    refreshBtn.style.background = '#ef4444';
                    refreshBtn.innerHTML = '<span>🔄</span> New Updates Available';
                }
            }
        })
        .catch(error => console.log('Auto-refresh error:', error));
}, 30000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>