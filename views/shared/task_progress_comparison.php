<?php
require_once __DIR__ . '/../../app/config/database.php';

$db = Database::connect();

// Get current user's task progress vs team average
$stmt = $db->query("
    SELECT 
        u.name,
        u.id,
        COUNT(dp.id) as total_tasks,
        SUM(CASE WHEN dp.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        AVG(CASE WHEN dp.status = 'completed' THEN dp.actual_hours ELSE NULL END) as avg_completion_time,
        AVG(dws.productivity_score) as avg_productivity
    FROM users u
    LEFT JOIN daily_plans dp ON u.id = dp.user_id AND dp.plan_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    WHERE u.role = 'user' AND u.status = 'active'
    GROUP BY u.id, u.name
    ORDER BY completed_tasks DESC, avg_productivity DESC
");
$userProgress = $stmt->fetchAll();

// Calculate team averages
$teamAvg = [
    'tasks' => array_sum(array_column($userProgress, 'total_tasks')) / count($userProgress),
    'completion_rate' => array_sum(array_map(function($u) { 
        return $u['total_tasks'] > 0 ? ($u['completed_tasks'] / $u['total_tasks']) * 100 : 0; 
    }, $userProgress)) / count($userProgress),
    'productivity' => array_sum(array_column($userProgress, 'avg_productivity')) / count($userProgress)
];

// Find current user's position
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUserData = array_filter($userProgress, fn($u) => $u['id'] == $currentUserId);
$currentUserData = reset($currentUserData);
?>

<div class="task-progress-comparison">
    <div class="comparison-header">
        <h3>📊 Your Performance vs Team</h3>
        <div class="time-period">Last 7 Days</div>
    </div>

    <div class="comparison-metrics">
        <?php if ($currentUserData): ?>
        <div class="metric-card">
            <div class="metric-title">Tasks Completed</div>
            <div class="metric-comparison">
                <div class="your-score">
                    <span class="value"><?= $currentUserData['completed_tasks'] ?></span>
                    <span class="label">You</span>
                </div>
                <div class="vs-indicator">vs</div>
                <div class="team-average">
                    <span class="value"><?= round($teamAvg['tasks'], 1) ?></span>
                    <span class="label">Team Avg</span>
                </div>
            </div>
            <div class="performance-indicator">
                <?php 
                $yourRate = $currentUserData['total_tasks'] > 0 ? ($currentUserData['completed_tasks'] / $currentUserData['total_tasks']) * 100 : 0;
                $diff = $yourRate - $teamAvg['completion_rate'];
                ?>
                <span class="indicator <?= $diff >= 0 ? 'positive' : 'negative' ?>">
                    <?= $diff >= 0 ? '↗' : '↘' ?> <?= abs(round($diff, 1)) ?>% <?= $diff >= 0 ? 'above' : 'below' ?> team
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-title">Productivity Score</div>
            <div class="metric-comparison">
                <div class="your-score">
                    <span class="value"><?= round($currentUserData['avg_productivity'] ?? 0, 1) ?>%</span>
                    <span class="label">You</span>
                </div>
                <div class="vs-indicator">vs</div>
                <div class="team-average">
                    <span class="value"><?= round($teamAvg['productivity'], 1) ?>%</span>
                    <span class="label">Team Avg</span>
                </div>
            </div>
            <div class="performance-indicator">
                <?php 
                $prodDiff = ($currentUserData['avg_productivity'] ?? 0) - $teamAvg['productivity'];
                ?>
                <span class="indicator <?= $prodDiff >= 0 ? 'positive' : 'negative' ?>">
                    <?= $prodDiff >= 0 ? '↗' : '↘' ?> <?= abs(round($prodDiff, 1)) ?>% <?= $prodDiff >= 0 ? 'above' : 'below' ?> team
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="team-ranking">
        <h4>🏆 Team Rankings</h4>
        <div class="ranking-list">
            <?php $position = 1; foreach ($userProgress as $user): ?>
            <div class="rank-item <?= $user['id'] == $currentUserId ? 'current-user' : '' ?>">
                <div class="rank-position">
                    <?= match($position) {
                        1 => '🥇',
                        2 => '🥈',
                        3 => '🥉',
                        default => "#$position"
                    } ?>
                </div>
                <div class="rank-info">
                    <div class="rank-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="rank-stats">
                        <?= $user['completed_tasks'] ?>/<?= $user['total_tasks'] ?> tasks • 
                        <?= round($user['avg_productivity'] ?? 0, 1) ?>% productivity
                    </div>
                </div>
                <?php if ($user['id'] == $currentUserId): ?>
                <div class="you-badge">YOU</div>
                <?php endif; ?>
            </div>
            <?php $position++; endforeach; ?>
        </div>
    </div>
</div>

<style>
.task-progress-comparison {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    padding: var(--space-5);
    margin: var(--space-5) 0;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
}

.comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-5);
    padding-bottom: var(--space-3);
    border-bottom: 2px solid var(--border-color);
}

.comparison-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: var(--font-size-lg);
    font-weight: 600;
}

.time-period {
    background: var(--bg-secondary);
    padding: var(--space-1) var(--space-3);
    border-radius: 12px;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    font-weight: 500;
}

.comparison-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.metric-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-4);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.metric-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.metric-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-3);
    font-size: var(--font-size-sm);
}

.metric-comparison {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.your-score, .team-average {
    text-align: center;
}

.your-score .value {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--primary);
    display: block;
}

.team-average .value {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--text-secondary);
    display: block;
}

.label {
    font-size: var(--font-size-sm);
    color: var(--text-muted);
    font-weight: 500;
}

.vs-indicator {
    font-size: var(--font-size-base);
    color: var(--text-muted);
    font-weight: 600;
}

.performance-indicator {
    text-align: center;
    padding: 8px;
    border-radius: 6px;
    font-size: 0.85em;
    font-weight: bold;
}

.indicator.positive {
    color: var(--success);
    background: rgba(5, 150, 105, 0.1);
    border: 1px solid rgba(5, 150, 105, 0.2);
}

.indicator.negative {
    color: var(--error);
    background: rgba(220, 38, 38, 0.1);
    border: 1px solid rgba(220, 38, 38, 0.2);
}

.team-ranking h4 {
    margin: 0 0 15px 0;
    color: #495057;
}

.ranking-list {
    max-height: 300px;
    overflow-y: auto;
}

.rank-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 5px;
    background: white;
    border: 1px solid #e9ecef;
}

.rank-item.current-user {
    background: #e3f2fd;
    border-color: #2196f3;
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.1);
}

.rank-position {
    font-size: 1.1em;
    min-width: 30px;
    text-align: center;
}

.rank-info {
    flex: 1;
}

.rank-name {
    font-weight: bold;
    color: #495057;
    margin-bottom: 2px;
}

.rank-stats {
    font-size: 0.85em;
    color: #6c757d;
}

.you-badge {
    background: #007bff;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7em;
    font-weight: bold;
}

@media (max-width: 768px) {
    .comparison-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .comparison-metrics {
        grid-template-columns: 1fr;
    }
    
    .metric-comparison {
        flex-direction: column;
        gap: 10px;
    }
    
    .vs-indicator {
        order: 2;
    }
}
</style>