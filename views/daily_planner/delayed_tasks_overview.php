<?php
$title = 'Delayed Tasks Overview';
$active_page = 'daily-planner';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>⚠️</span> Delayed Tasks Overview</h1>
        <p>Monitor and manage overdue tasks across all projects</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/public/owner/dashboard" class="btn btn--secondary">← Back to Dashboard</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🔴</div>
            <div class="kpi-card__trend">↗ +2</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['delayedTasks'], fn($t) => $t['days_overdue'] > 3)) ?></div>
        <div class="kpi-card__label">Critical Delays</div>
        <div class="kpi-card__status kpi-card__status--pending">Urgent</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend">↗ +1</div>
        </div>
        <div class="kpi-card__value"><?= count($data['delayedTasks']) ?></div>
        <div class="kpi-card__label">Total Delayed</div>
        <div class="kpi-card__status">Overdue</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= round(array_sum(array_column($data['delayedTasks'], 'completion_percentage')) / max(count($data['delayedTasks']), 1)) ?>%</div>
        <div class="kpi-card__label">Avg Progress</div>
        <div class="kpi-card__status">Incomplete</div>
    </div>
</div>

<div class="dashboard-grid">
    <?php foreach ($data['delayedTasks'] as $task): ?>
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                ⚠️ <?= htmlspecialchars($task['task_name']) ?>
            </h2>
        </div>
        <div class="card__body">
            <div class="project-summary">
                <div class="summary-stat">
                    <span class="summary-number"><?= $task['completion_percentage'] ?>%</span>
                    <span class="summary-label">Progress</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= $task['days_overdue'] ?></span>
                    <span class="summary-label">Days Overdue</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number"><?= ucfirst($task['priority']) ?></span>
                    <span class="summary-label">Priority</span>
                </div>
            </div>
            <div class="card-actions">
                <span class="badge badge--warning"><?= htmlspecialchars($task['user_name']) ?></span>
                <button class="btn btn--sm btn--primary" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                    View Details
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($data['delayedTasks'])): ?>
<div class="empty-state">
    <div class="empty-icon">✅</div>
    <h3>No Delayed Tasks</h3>
    <p>All tasks are on track! Great work.</p>
</div>
<?php endif; ?>

<script>
function viewTaskDetails(taskId) {
    if (taskId) {
        alert(`Task details for ID: ${taskId} (Feature to be implemented)`);
    } else {
        alert('Task details not available');
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
