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

<div class="dashboard-grid">
    <?php foreach ($projects ?? [] as $project): ?>
    <?php 
    $completionRate = $project['total_tasks'] > 0 ? 
        round(($project['completed_tasks'] / $project['total_tasks']) * 100, 1) : 0;
    $statusClass = $completionRate >= 80 ? 'success' : ($completionRate >= 50 ? 'warning' : 'danger');
    ?>
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📁</div>
            <div class="kpi-card__trend kpi-card__trend--<?= $statusClass ?>">
                <?= $completionRate ?>%
            </div>
        </div>
        <div class="kpi-card__value"><?= $project['total_tasks'] ?></div>
        <div class="kpi-card__label"><?= htmlspecialchars($project['project_name']) ?></div>
        <div class="kpi-card__status">
            <div class="progress-bar">
                <div class="progress-bar__fill progress-bar__fill--<?= $statusClass ?>" 
                     style="width: <?= $completionRate ?>%"></div>
            </div>
            <small><?= $project['completed_tasks'] ?> of <?= $project['total_tasks'] ?> completed</small>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($projects)): ?>
    <div class="empty-state">
        <div class="empty-state__icon">📊</div>
        <h3>No Projects Found</h3>
        <p>No project data available at the moment.</p>
        <a href="/ergon/tasks/create" class="btn btn--primary">Create New Task</a>
    </div>
    <?php endif; ?>
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

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state__icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>