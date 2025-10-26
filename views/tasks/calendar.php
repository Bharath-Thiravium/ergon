<?php
$title = 'Task Calendar';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <h1>📅 Enhanced Calendar & Daily Planner</h1>
    <div class="header-actions">
        <a href="/ergon/planner/calendar" class="btn btn--primary">📋 Daily Planner Calendar</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="/ergon/tasks/create" class="btn btn--secondary">Create Task</a>
        <?php endif; ?>
        <a href="/ergon/tasks" class="btn btn--secondary">Task List</a>
    </div>
</div>

<div class="alert alert--info">
    <strong>🚀 New Feature Available!</strong> 
    We've upgraded the calendar with daily planner functionality and department-specific forms. 
    <a href="/ergon/planner/calendar" class="btn btn--sm btn--primary" style="margin-left: 10px;">Try New Calendar</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Today</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['tasks'], fn($t) => date('Y-m-d', strtotime($t['deadline'])) === date('Y-m-d'))) ?></div>
        <div class="kpi-card__label">Due Today</div>
        <div class="kpi-card__status kpi-card__status--urgent">Urgent</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— Week</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['tasks'], fn($t) => strtotime($t['deadline']) <= strtotime('+7 days'))) ?></div>
        <div class="kpi-card__label">This Week</div>
        <div class="kpi-card__status kpi-card__status--pending">Upcoming</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ Total</div>
        </div>
        <div class="kpi-card__value"><?= count($data['tasks']) ?></div>
        <div class="kpi-card__label">Scheduled Tasks</div>
        <div class="kpi-card__status kpi-card__status--info">All</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Upcoming Deadlines</h2>
    </div>
    <div class="card__body">
        <?php foreach ($data['tasks'] as $task): ?>
        <div class="timeline-item">
            <div class="timeline-date"><?= date('M d', strtotime($task['deadline'])) ?></div>
            <div class="timeline-content">
                <h4><?= htmlspecialchars($task['title']) ?> 
                    <span class="badge badge--<?= $task['priority'] === 'high' ? 'error' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>">
                        <?= ucfirst($task['priority']) ?>
                    </span>
                </h4>
                <p>Assigned to: <strong><?= htmlspecialchars($task['assigned_to_name']) ?></strong> • Progress: <strong><?= $task['progress'] ?>%</strong></p>
                <p>Due: <?= date('M d, Y H:i', strtotime($task['deadline'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
