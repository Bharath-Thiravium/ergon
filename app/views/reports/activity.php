<?php
$title = 'IT Activity Reports';
$active_page = 'activity';
ob_start();
?>

<div class="page-header">
    <h1>⏱️ IT Department Activity Reports</h1>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💻</div>
        </div>
        <div class="kpi-card__value"><?= count($data['productivity']) ?></div>
        <div class="kpi-card__label">IT Staff Tracked</div>
        <div class="kpi-card__status kpi-card__status--active">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
        </div>
        <div class="kpi-card__value"><?= !empty($data['productivity']) ? round(array_sum(array_column($data['productivity'], 'productivity_score')) / count($data['productivity']), 1) : 0 ?>%</div>
        <div class="kpi-card__label">Avg Productivity</div>
        <div class="kpi-card__status kpi-card__status--info">Score</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Productivity Summary (Last 7 Days)</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['productivity'])): ?>
            <p>No activity data available. IT department users need to be active on the system to generate reports.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Productivity Score</th>
                            <th>Active Days</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['productivity'] as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $user['productivity_score'] ?>%"></div>
                                    <span class="progress-text"><?= $user['productivity_score'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $user['active_days'] ?> days</td>
                            <td>
                                <span class="badge badge--<?= $user['productivity_score'] >= 70 ? 'success' : ($user['productivity_score'] >= 50 ? 'warning' : 'danger') ?>">
                                    <?= $user['productivity_score'] >= 70 ? 'High' : ($user['productivity_score'] >= 50 ? 'Medium' : 'Low') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Daily Activity Log</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['activity'])): ?>
            <p>No detailed activity logs available yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Active Pings</th>
                            <th>Break Sessions</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['activity'] as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['name']) ?></td>
                            <td><?= date('M d, Y', strtotime($activity['activity_date'])) ?></td>
                            <td><?= $activity['active_pings'] ?></td>
                            <td><?= $activity['break_sessions'] ?></td>
                            <td><?= $activity['last_activity'] ? date('H:i', strtotime($activity['last_activity'])) : 'N/A' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.progress-bar {
    position: relative;
    width: 100px;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff4444 0%, #ffaa00 50%, #00aa44 100%);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: bold;
    color: #333;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>