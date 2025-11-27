<?php
$title = 'Notifications';
$active_page = 'notifications';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🔔</span> Notifications</h1>
        <p>Stay updated with your latest notifications</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="markAllAsRead()" id="markAllBtn">Mark All Read</button>
        <button class="btn btn--primary" onclick="markSelectedAsRead()" id="markSelectedBtn" disabled>Mark Selected Read</button>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Your Notifications</h2>
        <div class="card__actions">
            <button class="btn btn--secondary" onclick="markAllAsRead()" id="markAllBtn">Mark All Read</button>
            <button class="btn btn--primary" onclick="markSelectedAsRead()" id="markSelectedBtn" disabled>Mark Selected Read</button>
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
                            <th style="width: 50%; min-width: 300px;">
                                <input type="checkbox" id="selectAll" name="select_all" onchange="toggleSelectAll()" style="margin-right: 8px;">
                                Notification
                            </th>
                            <th style="width: 150px; min-width: 120px;">From</th>
                            <th style="width: 120px; min-width: 100px;">Time</th>
                            <th style="width: 120px; min-width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): 
                            $isUnread = !($notification['is_read'] ?? false);
                            $moduleName = $notification['module_name'] ?? '';
                            $moduleIcon = [
                                'task' => '✅', 'tasks' => '✅',
                                'leave' => '📅', 'leaves' => '📅', 
                                'expense' => '💰', 'expenses' => '💰',
                                'advance' => '💳', 'advances' => '💳',
                                'system' => '⚙️'
                            ][$moduleName] ?? '🔔';
                            // Generate proper URL based on module and reference
                            $viewUrl = '/ergon/dashboard';
                            $referenceId = $notification['reference_id'] ?? null;
                            
                            if ($referenceId && $moduleName) {
                                switch ($moduleName) {
                                    case 'task':
                                    case 'tasks':
                                        $viewUrl = "/ergon/tasks/view/{$referenceId}";
                                        break;
                                    case 'leave':
                                    case 'leaves':
                                        $viewUrl = "/ergon/leaves/view/{$referenceId}";
                                        break;
                                    case 'expense':
                                    case 'expenses':
                                        $viewUrl = "/ergon/expenses/view/{$referenceId}";
                                        break;
                                    case 'advance':
                                    case 'advances':
                                        $viewUrl = "/ergon/advances/view/{$referenceId}";
                                        break;
                                    default:
                                        $viewUrl = "/ergon/{$moduleName}";
                                }
                            } elseif ($moduleName) {
                                $viewUrl = "/ergon/{$moduleName}";
                            }
                        ?>
                        <tr class="<?= $isUnread ? 'notification--unread' : '' ?>" data-notification-id="<?= $notification['id'] ?>">
                            <td>
                                <div class="notification-content">
                                    <div class="notification-header">
                                        <input type="checkbox" class="notification-checkbox" name="notification_<?= $notification['id'] ?>" value="<?= $notification['id'] ?>" onchange="updateMarkSelectedButton()" style="margin-right: 8px;">
                                        <strong><?= ucfirst($moduleName ?: 'General') ?></strong>
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

<style>
.table {
    table-layout: fixed;
    width: 100%;
}
.table th:first-child,
.table td:first-child {
    width: 50%;
    min-width: 300px;
}
.table th:nth-child(2),
.table td:nth-child(2) {
    width: 150px;
    min-width: 120px;
}
.table th:nth-child(3),
.table td:nth-child(3) {
    width: 120px;
    min-width: 100px;
}
.table th:nth-child(4),
.table td:nth-child(4) {
    width: 120px;
    min-width: 100px;
}
.notification-icon {
    display: inline-block;
    margin-right: 8px;
    font-size: 16px;
    vertical-align: middle;
}
.notification-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
</style>

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
                row.classList.remove('notification--unread');
                row.classList.add('notification--read');
                const badge = row.querySelector('.badge--warning');
                if (badge) badge.remove();
                const button = row.querySelector('.ab-btn--success');
                if (button) button.remove();
            }
        }
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateMarkSelectedButton();
}

function updateMarkSelectedButton() {
    const selected = document.querySelectorAll('.notification-checkbox:checked');
    document.getElementById('markSelectedBtn').disabled = selected.length === 0;
}

function markSelectedAsRead() {
    const selected = document.querySelectorAll('.notification-checkbox:checked');
    selected.forEach(cb => markAsRead(cb.value));
}

function markAllAsRead() {
    const unreadRows = document.querySelectorAll('.notification--unread');
    unreadRows.forEach(row => {
        const id = row.dataset.notificationId;
        if (id) markAsRead(id);
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>