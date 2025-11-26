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
                    <table class="table table--striped">
                        <thead>
                            <tr>
                                <th class="col-checkbox">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th class="col-status"></th>
                                <th class="col-type">Type</th>
                                <th class="col-message">Message</th>
                                <th class="col-sender">From</th>
                                <th class="col-time">Time</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php 
                    // Advanced notification processing with priority sorting
                    $priorityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
                    usort($notifications, function($a, $b) use ($priorityOrder) {
                        $aPriority = $priorityOrder[$a['priority'] ?? 'medium'] ?? 3;
                        $bPriority = $priorityOrder[$b['priority'] ?? 'medium'] ?? 3;
                        if ($aPriority === $bPriority) {
                            return strtotime($b['created_at']) - strtotime($a['created_at']);
                        }
                        return $aPriority - $bPriority;
                    });
                    
                    foreach ($notifications as $notification): 
                        $isUnread = !($notification['is_read'] ?? false);
                        $priority = $notification['priority'] ?? 'medium';
                        $escalationLevel = $notification['escalation_level'] ?? 1;
                        $isDigest = $notification['action_type'] === 'digest';
                        $moduleIcon = ['task' => '✅', 'leave' => '📅', 'expense' => '💰', 'advance' => '💳', 'system' => '⚙️'][$notification['module_name']] ?? '🔔';
                        $priorityIcon = ['critical' => '🚨', 'high' => '🔴', 'medium' => '🟡', 'low' => '⚪'][$priority] ?? '🟡';
                        $viewUrl = $notification['link'] ?? "/ergon/{$notification['module_name']}";
                    ?>
                    <tr class="notification-row notification-row--<?= $priority ?> <?= $isUnread ? 'notification-row--unread' : 'notification-row--read' ?> <?= $isDigest ? 'notification-row--digest' : '' ?>" data-notification-id="<?= $notification['id'] ?>">
                        <td class="col-checkbox">
                            <input type="checkbox" class="notification-checkbox" value="<?= $notification['id'] ?>" onchange="updateMarkSelectedButton()">
                        </td>
                        <td class="col-status">
                            <?php if ($isUnread): ?>
                                <div class="status-indicator status-indicator--unread" title="Unread"></div>
                            <?php else: ?>
                                <div class="status-indicator status-indicator--read" title="Read"></div>
                            <?php endif; ?>
                        </td>
                        <td class="col-type">
                            <div class="notification-type">
                                <span class="priority-icon"><?= $priorityIcon ?></span>
                                <span class="type-icon"><?= $moduleIcon ?></span>
                                <div class="type-info">
                                    <span class="type-text"><?= ucfirst($notification['module_name'] ?? 'General') ?></span>
                                    <?php if ($escalationLevel > 1): ?>
                                    <span class="escalation-badge">⚡ L<?= $escalationLevel ?></span>
                                    <?php endif; ?>
                                    <?php if ($isDigest): ?>
                                    <span class="digest-badge">📊 Digest</span>
                                    <?php endif; ?>
                                </div>
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
                                <?php if ($notification['action_type'] === 'reminder' && $notification['reference_id']): ?>
                                    <button class="btn btn--xs btn--warning" onclick="snoozeReminder(<?= $notification['id'] ?>, 30)" title="Snooze 30 minutes">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array($notification['action_type'], ['approval_request']) && $notification['reference_id']): ?>
                                    <button class="btn btn--xs btn--success" onclick="quickApprove(<?= $notification['reference_id'] ?>, '<?= $notification['module_name'] ?>')" title="Quick approve">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button class="btn btn--xs btn--danger" onclick="quickReject(<?= $notification['reference_id'] ?>, '<?= $notification['module_name'] ?>')" title="Quick reject">
                                        <i class="bi bi-x-circle"></i>
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

<style>
.notifications-container {
    max-width: 1200px;
    margin: 0 auto;
}

.notification-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.stat-item--unread {
    background: #fef2f2;
    border-color: #fecaca;
}

.stat-item--read {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.stat-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.stat-content {
    min-width: 0;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.notification-row--unread {
    background: #fffbeb;
    border-left: 3px solid #f59e0b;
}

.notification-row--read {
    background: var(--bg-primary);
    opacity: 0.8;
}

.notification-row--critical {
    background: linear-gradient(90deg, #fef2f2 0%, #ffffff 20%);
    border-left: 4px solid #dc2626;
    animation: pulse-critical 2s infinite;
}

.notification-row--high {
    background: linear-gradient(90deg, #fffbeb 0%, #ffffff 20%);
    border-left: 4px solid #f59e0b;
}

.notification-row--digest {
    background: var(--bg-secondary);
    border: 2px dashed var(--border-color);
}

.notification-type {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.priority-icon {
    font-size: 0.875rem;
    margin-top: 0.125rem;
}

.type-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.type-text {
    font-weight: 500;
    color: var(--text-primary);
}

.escalation-badge {
    background: #dc2626;
    color: white;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 700;
    animation: blink 1s infinite;
}

.digest-badge {
    background: #6366f1;
    color: white;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator--unread { background: #f59e0b; }
.status-indicator--read { background: #10b981; }

.type-icon {
    font-size: 1.25rem;
}

.message-content {
    max-width: 400px;
}

.message-text {
    color: var(--text-primary);
    line-height: 1.5;
    margin-bottom: 0.25rem;
}

.message-meta {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.action-type {
    background: var(--bg-secondary);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    text-transform: capitalize;
    border: 1px solid var(--border-color);
}

.sender-name {
    font-weight: 500;
    color: var(--text-primary);
}

.time-info {
    text-align: right;
}

.time-relative {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.time-exact {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.col-checkbox { width: 40px; text-align: center; }
.col-status { width: 20px; }
.col-type { width: 120px; }
.col-message { width: auto; }
.col-sender { width: 150px; }
.col-time { width: 120px; }
.col-actions { width: 120px; }

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 16px;
    border-radius: 6px;
    color: white;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast--success { background: #10b981; }
.toast--error { background: #ef4444; }
.toast--info { background: #3b82f6; }

@keyframes pulse-critical {
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
    50% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
}

@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.5; }
}

@media (max-width: 768px) {
    .notification-stats {
        grid-template-columns: 1fr;
    }
    
    .message-content {
        max-width: 200px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .col-actions {
        width: 100px;
    }
    
    .col-checkbox {
        width: 30px;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>