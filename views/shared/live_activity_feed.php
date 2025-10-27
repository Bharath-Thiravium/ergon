<?php
require_once __DIR__ . '/../../app/config/database.php';

$db = Database::connect();

// Get recent team activities
$stmt = $db->query("
    (SELECT 'task_completed' as activity_type, u.name, dp.title as activity_data, dp.updated_at as activity_time
     FROM daily_plans dp 
     JOIN users u ON dp.user_id = u.id 
     WHERE dp.status = 'completed' AND dp.updated_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
     ORDER BY dp.updated_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'badge_earned' as activity_type, u.name, bd.name as activity_data, ub.awarded_on as activity_time
     FROM user_badges ub
     JOIN users u ON ub.user_id = u.id
     JOIN badge_definitions bd ON ub.badge_id = bd.id
     WHERE ub.awarded_on >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
     ORDER BY ub.awarded_on DESC LIMIT 5)
    UNION ALL
    (SELECT 'points_earned' as activity_type, u.name, CONCAT(up.points, ' points - ', up.reason) as activity_data, up.created_at as activity_time
     FROM user_points up
     JOIN users u ON up.user_id = u.id
     WHERE up.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
     ORDER BY up.created_at DESC LIMIT 5)
    ORDER BY activity_time DESC
    LIMIT 10
");
$activities = $stmt->fetchAll();
?>

<div class="live-activity-feed">
    <div class="feed-header">
        <h3>⚡ Live Team Activity</h3>
        <div class="live-indicator">
            <span class="pulse-dot"></span>
            LIVE
        </div>
    </div>

    <div class="activity-stream">
        <?php if (empty($activities)): ?>
        <div class="no-activity">
            <span class="icon">💤</span>
            <p>No recent activity</p>
            <small>Complete tasks to see live updates!</small>
        </div>
        <?php else: ?>
        <?php foreach ($activities as $activity): ?>
        <div class="activity-item">
            <div class="activity-icon">
                <?= match($activity['activity_type']) {
                    'task_completed' => '✅',
                    'badge_earned' => '🏆',
                    'points_earned' => '💎',
                    default => '📝'
                } ?>
            </div>
            <div class="activity-content">
                <div class="activity-text">
                    <strong><?= htmlspecialchars($activity['name']) ?></strong>
                    <?= match($activity['activity_type']) {
                        'task_completed' => 'completed task: ' . htmlspecialchars($activity['activity_data']),
                        'badge_earned' => 'earned badge: ' . htmlspecialchars($activity['activity_data']),
                        'points_earned' => 'earned ' . htmlspecialchars($activity['activity_data']),
                        default => htmlspecialchars($activity['activity_data'])
                    } ?>
                </div>
                <div class="activity-time">
                    <?php
                    $timeAgo = time() - strtotime($activity['activity_time']);
                    if ($timeAgo < 60) {
                        echo 'Just now';
                    } elseif ($timeAgo < 3600) {
                        echo floor($timeAgo / 60) . 'm ago';
                    } else {
                        echo floor($timeAgo / 3600) . 'h ago';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="feed-footer">
        <button class="refresh-feed" onclick="refreshActivityFeed()">
            🔄 Refresh
        </button>
    </div>
</div>

<style>
.live-activity-feed {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    margin: var(--space-5) 0;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.feed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4) var(--space-5);
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-color);
}

.feed-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: var(--font-size-lg);
    font-weight: 600;
}

.live-indicator {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--font-size-xs);
    font-weight: 600;
    color: var(--success);
}

.pulse-dot {
    width: 8px;
    height: 8px;
    background: var(--success);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
}

.activity-stream {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-5);
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
}

.activity-item:hover {
    background: var(--bg-secondary);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.2em;
    min-width: 24px;
    text-align: center;
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: var(--font-size-sm);
    color: var(--text-primary);
    line-height: 1.4;
    margin-bottom: var(--space-1);
}

.activity-time {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
}

.no-activity {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.no-activity .icon {
    font-size: 2em;
    display: block;
    margin-bottom: 10px;
}

.feed-footer {
    padding: 10px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

.refresh-feed {
    background: var(--primary);
    color: var(--text-inverse);
    border: none;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--border-radius);
    font-size: var(--font-size-sm);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.refresh-feed:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

/* Scrollbar styling */
.activity-stream::-webkit-scrollbar {
    width: 4px;
}

.activity-stream::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.activity-stream::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.activity-stream::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
function refreshActivityFeed() {
    // Simple page refresh for now - can be enhanced with AJAX
    location.reload();
}

// Auto-refresh every 30 seconds
setInterval(function() {
    // In a real implementation, this would use AJAX to update just the feed
    // For now, we'll just indicate that auto-refresh is available
    console.log('Activity feed auto-refresh available');
}, 30000);
</script>