<?php
$content = ob_start();
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏆</div>
        </div>
        <div class="kpi-card__value"><?= number_format($user_stats['total_points']) ?></div>
        <div class="kpi-card__label">Total Points</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
        </div>
        <div class="kpi-card__value">#<?= $user_stats['rank'] ?></div>
        <div class="kpi-card__label">My Rank</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏅</div>
        </div>
        <div class="kpi-card__value"><?= count($user_stats['badges']) ?></div>
        <div class="kpi-card__label">Badges Earned</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🏅 My Badges</h2>
        </div>
        <div class="card__body">
            <?php if (!empty($user_stats['badges'])): ?>
                <div class="user-grid">
                    <?php foreach ($user_stats['badges'] as $badge): ?>
                        <div class="user-card">
                            <div class="user-card__header">
                                <div style="font-size: 2rem;"><?= htmlspecialchars($badge['icon'] ?? '🏅') ?></div>
                            </div>
                            <div class="user-card__name"><?= htmlspecialchars($badge['name']) ?></div>
                            <div class="user-card__email"><?= date('M j, Y', strtotime($badge['awarded_on'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🎯</div>
                    <h3>No Badges Yet</h3>
                    <p>Complete tasks to earn your first badge!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🏆 Team Leaderboard</h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaderboard as $index => $leader): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $rankBadge = match($index) {
                                        0 => '<span class="badge badge--warning">🥇 1st</span>',
                                        1 => '<span class="badge badge--info">🥈 2nd</span>',
                                        2 => '<span class="badge badge--success">🥉 3rd</span>',
                                        default => '<span class="badge badge--pending">#' . ($index + 1) . '</span>'
                                    };
                                    echo $rankBadge;
                                    ?>
                                </td>
                                <td>
                                    <div class="cell-meta">
                                        <div class="cell-primary"><?= htmlspecialchars($leader['name']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-meta">
                                        <div class="cell-primary"><?= number_format($leader['total_points']) ?></div>
                                        <div class="cell-secondary">points</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">👥 Team Overview</h2>
    </div>
    <div class="card__body">
        <div class="user-grid">
            <?php foreach ($all_users as $user): ?>
                <?php 
                $userPoints = 0;
                $leaderData = array_filter($leaderboard, fn($leader) => $leader['name'] === $user['name']);
                if (!empty($leaderData)) {
                    $userPoints = reset($leaderData)['total_points'];
                }
                ?>
                <div class="user-card">
                    <div class="user-card__header">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user['name'], 0, 2)) ?>
                        </div>
                    </div>
                    <div class="user-card__name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-card__role"><?= ucfirst($user['role']) ?></div>
                    <div class="user-card__email"><?= number_format($userPoints) ?> points</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>