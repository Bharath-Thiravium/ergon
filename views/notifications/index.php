<?php
$active_page = 'notifications';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <button class="btn btn--back" onclick="goBack()" title="Go Back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" width="16" height="16">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back
        </button>
        <h1><span>🔔</span> Notifications</h1>
        <p>Stay updated with your latest notifications</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="markAllAsRead()" id="markAllBtn">Mark All Read</button>
        <button class="btn btn--primary" onclick="markSelectedAsRead()" id="markSelectedBtn" disabled>Mark Selected Read</button>
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
                            $referenceType = $notification['module_name'] ?? $notification['reference_type'] ?? '';
                            $moduleIcon = [
                                'task' => '✅', 'tasks' => '✅',
                                'leave' => '📅', 'leaves' => '📅', 
                                'expense' => '💰', 'expenses' => '💰',
                                'advance' => '💳', 'advances' => '💳',
                                'system' => '⚙️'
                            ][$referenceType] ?? '🔔';
                            
                            // Generate URL based on reference type and ID (same logic as tasks)
                            $actionUrl = $notification['action_url'] ?? null;
                            $referenceId = $notification['reference_id'] ?? null;
                            
                            // Auto-populate missing reference_id for existing notifications
                            if (!$referenceId && $referenceType && in_array($referenceType, ['expense', 'leave', 'advance'])) {
                                try {
                                    require_once __DIR__ . '/../../app/config/database.php';
                                    $db = Database::connect();
                                    $table = $referenceType === 'advance' ? 'advances' : $referenceType . 's';
                                    
                                    // Try to match by user and time proximity (within 1 hour)
                                    $stmt = $db->prepare("SELECT id FROM {$table} WHERE user_id = ? AND ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) <= 60 ORDER BY ABS(TIMESTAMPDIFF(MINUTE, created_at, ?)) ASC LIMIT 1");
                                    $stmt->execute([$notification['sender_id'], $notification['created_at'], $notification['created_at']]);
                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    // Fallback: get latest record for this user
                                    if (!$result) {
                                        $stmt = $db->prepare("SELECT id FROM {$table} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                                        $stmt->execute([$notification['sender_id']]);
                                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                    }
                                    
                                    if ($result) {
                                        $referenceId = $result['id'];
                                        $updateStmt = $db->prepare("UPDATE notifications SET reference_id = ? WHERE id = ?");
                                        $updateStmt->execute([$referenceId, $notification['id']]);
                                    }
                                } catch (Exception $e) {
                                    // Ignore errors, continue with null reference_id
                                }
                            }
                            
                            $viewUrl = '/ergon/dashboard'; // Default fallback
                            
                            // Debug: Check what we have
                            // echo "<!-- Debug: ID={$referenceId}, Type={$referenceType}, ActionURL={$actionUrl} -->"
                            
                            if ($actionUrl) {
                                $viewUrl = $actionUrl;
                            } elseif ($referenceType && !empty($referenceId) && is_numeric($referenceId) && $referenceId > 0) {
                                switch ($referenceType) {
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
                                        $pluralType = $referenceType . 's';
                                        $viewUrl = "/ergon/{$pluralType}/view/{$referenceId}";
                                }
                            } elseif ($referenceType) {
                                $moduleUrls = [
                                    'leave' => '/ergon/leaves',
                                    'expense' => '/ergon/expenses', 
                                    'advance' => '/ergon/advances',
                                    'task' => '/ergon/tasks'
                                ];
                                $viewUrl = $moduleUrls[$referenceType] ?? "/ergon/{$referenceType}";
                            }
                        ?>
                        <tr class="<?= $isUnread ? 'notification--unread' : '' ?>" data-notification-id="<?= (int)$notification['id'] ?>">
                            <td>
                                <input type="checkbox" class="notification-checkbox" name="notification_<?= (int)$notification['id'] ?>" value="<?= (int)$notification['id'] ?>" onchange="updateMarkSelectedButton()" style="margin-right: 8px; vertical-align: top;">
                                <div class="notification-content" style="display: inline-block; width: calc(100% - 30px);">
                                    <div class="notification-title">
                                        <strong><?= htmlspecialchars($notification['title'] ?? ucfirst($referenceType ?: 'General')) ?></strong>
                                        <?php if ($isUnread): ?>
                                        <span class="badge badge--warning" style="margin-left: 8px;">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notification-message" style="margin-top: 4px; color: #666;">
                                        <?= htmlspecialchars($notification['message'] ?? 'No message') ?>
                                    </div>
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
                                    <button class="ab-btn ab-btn--success" onclick="markAsRead(<?= (int)$notification['id'] ?>)" data-tooltip="Mark as read">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="20,6 9,17 4,12"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($viewUrl && $viewUrl !== '/ergon/dashboard'): ?>
                                    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="ab-btn ab-btn--view" data-tooltip="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
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

<script src="/ergon/assets/js/notifications-enhanced.js" defer></script>
<script>
function goBack() {
    window.history.back();
}
window.goBack = goBack;
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>