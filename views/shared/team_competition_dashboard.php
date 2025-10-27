<?php
require_once __DIR__ . '/../../app/models/Gamification.php';
require_once __DIR__ . '/../../app/config/database.php';

$db = Database::connect();
$gamification = new Gamification();

// Get team leaderboard
$leaderboard = $gamification->getLeaderboard(10);

// Get today's task completion stats
$stmt = $db->query("
    SELECT u.name, u.total_points,
           COUNT(dp.id) as today_tasks,
           SUM(CASE WHEN dp.status = 'completed' THEN 1 ELSE 0 END) as completed_today,
           AVG(dws.productivity_score) as avg_productivity,
           (SELECT COUNT(*) FROM user_badges WHERE user_id = u.id) as badge_count
    FROM users u
    LEFT JOIN daily_plans dp ON u.id = dp.user_id AND dp.plan_date = CURDATE()
    LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    WHERE u.role = 'user' AND u.status = 'active'
    GROUP BY u.id, u.name, u.total_points
    ORDER BY u.total_points DESC
");
$teamStats = $stmt->fetchAll();

// Get recent achievements
$stmt = $db->query("
    SELECT u.name, bd.icon, bd.name as badge_name, ub.awarded_on
    FROM user_badges ub
    JOIN users u ON ub.user_id = u.id
    JOIN badge_definitions bd ON ub.badge_id = bd.id
    WHERE ub.awarded_on >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY ub.awarded_on DESC
    LIMIT 5
");
$recentAchievements = $stmt->fetchAll();
?>

<div class="team-competition-dashboard">
    <div class="competition-header">
        <h2>🏆 Team Competition Dashboard</h2>
        <div class="competition-stats">
            <span class="stat">👥 <?= count($teamStats) ?> Active Players</span>
            <span class="stat">🎖️ <?= array_sum(array_column($teamStats, 'badge_count')) ?> Total Badges</span>
            <span class="stat">💎 <?= array_sum(array_column($teamStats, 'total_points')) ?> Total Points</span>
        </div>
    </div>

    <div class="competition-grid">
        <!-- Leaderboard -->
        <div class="competition-card">
            <h3>🥇 Top Performers</h3>
            <div class="leaderboard">
                <?php $rank = 1; foreach ($teamStats as $player): ?>
                <div class="player-rank <?= $rank <= 3 ? 'top-three' : '' ?>">
                    <div class="rank-badge">
                        <?= match($rank) {
                            1 => '🥇',
                            2 => '🥈', 
                            3 => '🥉',
                            default => "#$rank"
                        } ?>
                    </div>
                    <div class="player-info">
                        <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                        <div class="player-stats">
                            <span class="points"><?= $player['total_points'] ?> pts</span>
                            <span class="badges"><?= $player['badge_count'] ?> 🏆</span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min(100, ($player['avg_productivity'] ?? 0)) ?>%"></div>
                        <span class="progress-text"><?= round($player['avg_productivity'] ?? 0, 1) ?>%</span>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
            </div>
        </div>

        <!-- Today's Battle -->
        <div class="competition-card">
            <h3>⚡ Today's Battle</h3>
            <div class="daily-competition">
                <?php foreach ($teamStats as $player): ?>
                <div class="daily-player">
                    <div class="player-avatar"><?= strtoupper(substr($player['name'], 0, 1)) ?></div>
                    <div class="daily-stats">
                        <div class="player-name"><?= htmlspecialchars($player['name']) ?></div>
                        <div class="task-progress">
                            <span class="completed"><?= $player['completed_today'] ?></span>
                            <span class="separator">/</span>
                            <span class="total"><?= $player['today_tasks'] ?></span>
                            <span class="label">tasks</span>
                        </div>
                    </div>
                    <div class="completion-circle">
                        <?php 
                        $completionRate = $player['today_tasks'] > 0 ? 
                            round(($player['completed_today'] / $player['today_tasks']) * 100) : 0;
                        ?>
                        <svg width="40" height="40">
                            <circle cx="20" cy="20" r="18" fill="none" stroke="#e0e0e0" stroke-width="3"/>
                            <circle cx="20" cy="20" r="18" fill="none" stroke="#4caf50" stroke-width="3"
                                    stroke-dasharray="<?= 2 * pi() * 18 ?>" 
                                    stroke-dashoffset="<?= 2 * pi() * 18 * (1 - $completionRate/100) ?>"
                                    transform="rotate(-90 20 20)"/>
                        </svg>
                        <span class="circle-text"><?= $completionRate ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Achievements -->
        <div class="competition-card">
            <h3>🎉 Recent Achievements</h3>
            <div class="achievements-feed">
                <?php if (empty($recentAchievements)): ?>
                    <div class="no-achievements">
                        <span class="icon">🏆</span>
                        <p>No recent achievements</p>
                        <small>Complete tasks to earn badges!</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentAchievements as $achievement): ?>
                    <div class="achievement-item">
                        <div class="achievement-badge"><?= $achievement['icon'] ?></div>
                        <div class="achievement-info">
                            <div class="achievement-text">
                                <strong><?= htmlspecialchars($achievement['name']) ?></strong> 
                                earned <em><?= htmlspecialchars($achievement['badge_name']) ?></em>
                            </div>
                            <div class="achievement-time"><?= date('M d, H:i', strtotime($achievement['awarded_on'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Weekly Challenge -->
        <div class="competition-card">
            <h3>🎯 Weekly Challenge</h3>
            <div class="weekly-challenge">
                <div class="challenge-title">Most Productive Week</div>
                <div class="challenge-description">Complete the most tasks with highest productivity score</div>
                <div class="challenge-progress">
                    <?php 
                    $stmt = $db->query("
                        SELECT u.name, 
                               COUNT(dp.id) as week_tasks,
                               SUM(CASE WHEN dp.status = 'completed' THEN 1 ELSE 0 END) as completed_week,
                               AVG(dws.productivity_score) as week_productivity
                        FROM users u
                        LEFT JOIN daily_plans dp ON u.id = dp.user_id AND dp.plan_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        LEFT JOIN daily_workflow_status dws ON u.id = dws.user_id AND dws.workflow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        WHERE u.role = 'user' AND u.status = 'active'
                        GROUP BY u.id, u.name
                        ORDER BY completed_week DESC, week_productivity DESC
                        LIMIT 3
                    ");
                    $weeklyLeaders = $stmt->fetchAll();
                    ?>
                    
                    <?php $pos = 1; foreach ($weeklyLeaders as $leader): ?>
                    <div class="challenge-leader">
                        <span class="position"><?= $pos ?></span>
                        <span class="name"><?= htmlspecialchars($leader['name']) ?></span>
                        <span class="score"><?= $leader['completed_week'] ?> tasks</span>
                        <span class="productivity"><?= round($leader['week_productivity'] ?? 0, 1) ?>%</span>
                    </div>
                    <?php $pos++; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.team-competition-dashboard {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: var(--space-6);
    color: var(--text-primary);
    margin: var(--space-6) 0;
    box-shadow: var(--shadow);
}

.competition-header {
    text-align: center;
    margin-bottom: var(--space-6);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--border-color);
}

.competition-header h2 {
    margin: 0 0 var(--space-3) 0;
    font-size: var(--font-size-2xl);
    font-weight: 600;
    color: var(--text-primary);
}

.competition-stats {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.stat {
    background: var(--bg-secondary);
    padding: var(--space-2) var(--space-4);
    border-radius: 20px;
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    font-weight: 500;
}

.competition-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-4);
}

.competition-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-4);
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.competition-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.competition-card h3 {
    margin: 0 0 var(--space-4) 0;
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border-color);
    padding-bottom: var(--space-2);
}

.player-rank {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
}

.player-rank:hover {
    background: var(--bg-tertiary);
    border-radius: var(--border-radius);
    padding: var(--space-3);
    margin: 0 calc(-1 * var(--space-3));
}

.player-rank.top-three {
    background: var(--bg-tertiary);
    border: 1px solid var(--primary);
    border-radius: var(--border-radius);
    padding: var(--space-3);
    margin-bottom: var(--space-2);
}

.rank-badge {
    font-size: var(--font-size-lg);
    min-width: 30px;
    text-align: center;
    font-weight: 600;
}

.player-info {
    flex: 1;
}

.player-name {
    font-weight: 600;
    margin-bottom: var(--space-1);
    color: var(--text-primary);
    font-size: var(--font-size-base);
}

.player-stats {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.points {
    margin-right: var(--space-3);
    font-weight: 500;
}

.progress-bar {
    position: relative;
    width: 60px;
    height: 20px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success), #10b981);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: var(--font-size-xs);
    font-weight: 600;
    color: var(--text-primary);
}

.daily-player {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.player-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.daily-stats {
    flex: 1;
}

.task-progress {
    font-size: 0.9em;
    opacity: 0.9;
}

.completed {
    font-weight: bold;
    color: #4caf50;
}

.completion-circle {
    position: relative;
    width: 40px;
    height: 40px;
}

.circle-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.7em;
    font-weight: bold;
}

.achievement-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.achievement-badge {
    font-size: 1.5em;
}

.achievement-info {
    flex: 1;
}

.achievement-text {
    font-size: 0.9em;
    margin-bottom: 2px;
}

.achievement-time {
    font-size: 0.75em;
    opacity: 0.7;
}

.no-achievements {
    text-align: center;
    padding: 20px;
    opacity: 0.7;
}

.no-achievements .icon {
    font-size: 2em;
    display: block;
    margin-bottom: 10px;
}

.weekly-challenge {
    text-align: center;
}

.challenge-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.challenge-description {
    font-size: 0.85em;
    opacity: 0.8;
    margin-bottom: 15px;
}

.challenge-leader {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    font-size: 0.9em;
}

.position {
    width: 20px;
    font-weight: bold;
}

.name {
    flex: 1;
    text-align: left;
}

.score, .productivity {
    font-weight: bold;
}

@media (max-width: 768px) {
    .competition-grid {
        grid-template-columns: 1fr;
    }
    
    .competition-stats {
        flex-direction: column;
        align-items: center;
    }
}
</style>