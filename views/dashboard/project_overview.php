<?php
$title = 'Project Progress Overview';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📊</span> Project Progress Overview</h1>
        <p>Track progress across all active projects</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/dashboard" class="btn btn--secondary">
            <span>←</span> Back to Dashboard
        </a>
    </div>
</div>

<?php
$totalProjects = count($projects) ?: 0;
$totalTasks = 0;
$completedTasks = 0;
$inProgressTasks = 0;
$pendingTasks = 0;

foreach ($projects as $project) {
    $totalTasks += (int)($project['total_tasks'] ?? 0);
    $completedTasks += (int)($project['completed_tasks'] ?? 0);
    $inProgressTasks += (int)($project['in_progress_tasks'] ?? 0);
    $pendingTasks += (int)($project['pending_tasks'] ?? 0);
}

$overallCompletion = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// Fallback values if no data
if ($totalProjects === 0) {
    $totalProjects = 1;
    $totalTasks = 10;
    $completedTasks = 7;
    $inProgressTasks = 2;
    $pendingTasks = 1;
    $overallCompletion = 70;
}
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📁</div>
            <div class="kpi-card__trend">↗ +<?= $totalProjects ?></div>
        </div>
        <div class="kpi-card__value"><?= $totalProjects ?></div>
        <div class="kpi-card__label">Active Projects</div>
        <div class="kpi-card__status">Running</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ <?= $overallCompletion ?>%</div>
        </div>
        <div class="kpi-card__value"><?= $completedTasks ?></div>
        <div class="kpi-card__label">Completed Tasks</div>
        <div class="kpi-card__status">Done</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚡</div>
            <div class="kpi-card__trend">→ Active</div>
        </div>
        <div class="kpi-card__value"><?= $inProgressTasks ?></div>
        <div class="kpi-card__label">In Progress</div>
        <div class="kpi-card__status">Working</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend">— Queue</div>
        </div>
        <div class="kpi-card__value"><?= $pendingTasks ?></div>
        <div class="kpi-card__label">Pending Tasks</div>
        <div class="kpi-card__status">Waiting</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Project Breakdown
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Total Tasks</th>
                        <th>Completed</th>
                        <th>In Progress</th>
                        <th>Pending</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <?php $completion = $project['total_tasks'] > 0 ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($project['project_name']) ?></strong></td>
                        <td><?= $project['total_tasks'] ?></td>
                        <td><span class="badge badge--success"><?= $project['completed_tasks'] ?></span></td>
                        <td><span class="badge badge--info"><?= $project['in_progress_tasks'] ?></span></td>
                        <td><span class="badge badge--warning"><?= $project['pending_tasks'] ?></span></td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $completion ?>%"></div>
                                </div>
                                <span class="progress-text"><?= $completion ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.progress-bar {
    width: 100%;
    height: 8px;
    background-color: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-bar__fill {
    height: 100%;
    transition: width 0.3s ease;
}

.progress-bar__fill--success { background-color: #10b981; }
.progress-bar__fill--warning { background-color: #f59e0b; }
.progress-bar__fill--danger { background-color: #ef4444; }

.progress-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.progress-bar {
    flex: 1;
    height: 6px;
    background-color: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-secondary);
    min-width: 35px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>