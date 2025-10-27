<?php
$content = ob_start();
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏆</div>
        </div>
        <div class="kpi-card__value"><?= number_format($team_stats['total_points']) ?></div>
        <div class="kpi-card__label">Team Points</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= $team_stats['total_tasks'] ?></div>
        <div class="kpi-card__label">Tasks Completed</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏅</div>
        </div>
        <div class="kpi-card__value"><?= $team_stats['total_badges'] ?></div>
        <div class="kpi-card__label">Total Badges</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
        </div>
        <div class="kpi-card__value"><?= count($user_stats) ?></div>
        <div class="kpi-card__label">Team Members</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🏆 Top Performers</h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Points</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($leaderboard, 0, 10) as $index => $leader): ?>
                            <tr>
                                <td>
                                    <?php if ($index === 0): ?>
                                        <span class="badge badge--warning">🥇 1st</span>
                                    <?php elseif ($index === 1): ?>
                                        <span class="badge badge--success">🥈 2nd</span>
                                    <?php elseif ($index === 2): ?>
                                        <span class="badge">🥉 3rd</span>
                                    <?php else: ?>
                                        #<?= $index + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($leader['name']) ?></td>
                                <td><strong><?= number_format($leader['total_points']) ?></strong></td>
                                <td>
                                    <?php if ($index < 3): ?>
                                        <span class="badge badge--success">Top Performer</span>
                                    <?php else: ?>
                                        <span class="badge">Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📊 Team Statistics</h2>
        </div>
        <div class="card__body">
            <div class="stat-item">
                <div class="stat-value"><?= number_format(array_sum(array_column($user_stats, 'total_points')) / max(1, count($user_stats))) ?></div>
                <div class="stat-label">Average Points per Member</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= number_format(array_sum(array_column($user_stats, 'completed_tasks')) / max(1, count($user_stats))) ?></div>
                <div class="stat-label">Average Tasks per Member</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= count(array_filter($user_stats, function($u) { return $u['total_points'] > 0; })) ?></div>
                <div class="stat-label">Active Contributors</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">👥 Team Member Performance</h2>
    </div>
    <div class="card__body">
        <div class="user-grid">
            <?php foreach ($user_stats as $user): ?>
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['name'], 0, 2)) ?>
                        </div>
                        <div class="user-card__badges">
                            <?php if ($user['total_points'] > 0): ?>
                                <span class="badge badge--success"><?= number_format($user['total_points']) ?> pts</span>
                            <?php endif; ?>
                            <?php if (count($user['badges']) > 0): ?>
                                <span class="badge badge--warning"><?= count($user['badges']) ?> badges</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-card__name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-card__email"><?= htmlspecialchars($user['department'] ?? 'General') ?></div>
                    <div class="user-card__role"><?= ucfirst($user['role']) ?></div>
                    
                    <?php if (!empty($user['tasks'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>Recent Tasks:</strong>
                            <?php foreach (array_slice($user['tasks'], 0, 2) as $task): ?>
                                <div style="font-size: 0.8rem; margin: 2px 0;">
                                    <?php 
                                    $status_icon = '📋';
                                    if (isset($task['status'])) {
                                        switch($task['status']) {
                                            case 'completed': $status_icon = '✅'; break;
                                            case 'in_progress': $status_icon = '🔄'; break;
                                            case 'pending': $status_icon = '⏳'; break;
                                        }
                                    }
                                    ?>
                                    <?= $status_icon ?> <?= htmlspecialchars(substr($task['title'] ?? 'Task', 0, 25)) ?><?= strlen($task['title'] ?? '') > 25 ? '...' : '' ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-card__actions">
                        <span class="btn btn--sm">Tasks: <?= $user['completed_tasks'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>