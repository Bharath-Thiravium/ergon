<?php
ob_start();
$title = 'Notifications';
$active_page = 'notifications';
?>

<div class="notifications-container">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🔔</span> Notification Center
            </h2>
            <div class="card__actions">
                <button class="btn btn--secondary btn--sm" onclick="filterNotifications('all')">All</button>
                <button class="btn btn--primary btn--sm" onclick="filterNotifications('unread')">Unread</button>
                <button class="btn btn--success btn--sm" onclick="markSelectedAsRead()" id="markSelectedBtn" disabled>Mark Selected Read</button>
            </div>
        </div>
        <div class="card__body">
            <div class="notification-stats">
                <div class="stat-item">
                    <span class="stat-icon">📬</span>
                    <div class="stat-content">
                        <div class="stat-number"><?= count($notifications ?? []) ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <div class="stat-item stat-item--unread">
                    <span class="stat-icon">🔴</span>
                    <div class="stat-content">
                        <div class="stat-number"><?= count(array_filter($notifications ?? [], fn($n) => !($n['is_read'] ?? false))) ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                </div>
                <div class="stat-item stat-item--read">
                    <span class="stat-icon">✅</span>
                    <div class="stat-content">
                        <div class="stat-number"><?= count(array_filter($notifications ?? [], fn($n) => ($n['is_read'] ?? false))) ?></div>
                        <div class="stat-label">Read</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>📋</span> Notifications
            </h2>
            <div class="card__actions">
                <button class="btn btn--sm btn--secondary" onclick="markSelectedAsRead()" id="markSelectedBtn" disabled>Mark Selected Read</button>
            </div>
        </div>
        <div class="card__body">
            <?php if (empty($notifications ?? [])): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔔</div>
                    <h3>No Notifications</h3>
                    <p>You're all caught up! No new notifications.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" name="select_all" onchange="toggleSelectAll()">
                                </th>
                                <th>Notification</th>
                                <th>From</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): 
                                $isUnread = !($notification['is_read'] ?? false);
                                $moduleIcon = ['tasks' => '✅', 'leaves' => '📅', 'expenses' => '💰', 'advances' => '💳', 'system' => '⚙️'][$notification['module_name'] ?? 'system'] ?? '🔔';
                                // Generate proper URL based on module and reference
                                $viewUrl = '/ergon/dashboard';
                                if ($notification['reference_id'] && $notification['module_name']) {
                                    switch ($notification['module_name']) {
                                        case 'tasks':
                                            $viewUrl = "/ergon/tasks/view/{$notification['reference_id']}";
                                            break;
                                        case 'leaves':
                                            $viewUrl = "/ergon/leaves/view/{$notification['reference_id']}";
                                            break;
                                        case 'expenses':
                                            $viewUrl = "/ergon/expenses/view/{$notification['reference_id']}";
                                            break;
                                        case 'advances':
                                            $viewUrl = "/ergon/advances/view/{$notification['reference_id']}";
                                            break;
                                        default:
                                            $viewUrl = "/ergon/{$notification['module_name']}";
                                    }
                                } elseif ($notification['module_name']) {
                                    $viewUrl = "/ergon/{$notification['module_name']}";
                                }
                            ?>
                            <tr class="<?= $isUnread ? 'notification--unread' : '' ?>" data-notification-id="<?= $notification['id'] ?>">
                                <td>
                                    <input type="checkbox" class="notification-checkbox" name="notification_<?= $notification['id'] ?>" value="<?= $notification['id'] ?>" onchange="updateMarkSelectedButton()">
                                </td>
                                <td>
                                    <div class="notification-content">
                                        <div class="notification-header">
                                            <span class="notification-icon"><?= $moduleIcon ?></span>
                                            <strong><?= ucfirst($notification['module_name'] ?? 'General') ?></strong>
                                            <?php if ($isUnread): ?>
                                            <span class="badge badge--warning">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-message">
                                            <?= htmlspecialchars($notification['message'] ?? 'No message') ?>
                                        </div>
                                        <?php if ($notification['action_type'] ?? ''): ?>
                                        <small class="text-muted"><?= ucfirst($notification['action_type']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?= strtoupper(substr($notification['sender_name'] ?? 'S', 0, 1)) ?></div>
                                        <div>
                                            <strong><?= htmlspecialchars($notification['sender_name'] ?? 'System') ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-meta">
                                        <div class="cell-primary"><?= timeAgo($notification['created_at']) ?></div>
                                        <div class="cell-secondary"><?= date('M j, H:i', strtotime($notification['created_at'])) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="ab-container">
                                        <?php if ($isUnread): ?>
                                        <button class="ab-btn ab-btn--success" onclick="markAsRead(<?= $notification['id'] ?>)" data-tooltip="Mark as read">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <a href="<?= $viewUrl ?>" class="ab-btn ab-btn--view" data-tooltip="View Details">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
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
    </div>
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

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateMarkSelectedButton();
}

function updateMarkSelectedButton() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    const button = document.getElementById('markSelectedBtn');
    
    button.disabled = checkboxes.length === 0;
    button.textContent = checkboxes.length > 0 ? `Mark ${checkboxes.length} Read` : 'Mark Selected Read';
}

function markSelectedAsRead() {
    const checkboxes = document.querySelectorAll('.notification-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    fetch('/ergon/api/notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark-selected-read&ids=${ids.join(',')}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to mark notifications as read');
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

// Contextual action functions
function snoozeReminder(notificationId, minutes) {
    fetch('/ergon/api/notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=snooze&id=${notificationId}&minutes=${minutes}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (row) row.style.opacity = '0.5';
            showToast(`Reminder snoozed for ${minutes} minutes`, 'success');
        }
    });
}

function quickApprove(referenceId, module) {
    if (confirm('Approve this request?')) {
        fetch(`/ergon/api/${module}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=quick-approve&id=${referenceId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Request approved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        });
    }
}

function quickReject(referenceId, module) {
    const reason = prompt('Rejection reason (optional):');
    if (reason !== null) {
        fetch(`/ergon/api/${module}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=quick-reject&id=${referenceId}&reason=${encodeURIComponent(reason)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Request rejected', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        });
    }
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
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



<?php
$content = ob_get_clean();
?>

<link rel="stylesheet" href="/ergon/assets/css/notifications.css">

<?php
include __DIR__ . '/../layouts/dashboard.php';
?>