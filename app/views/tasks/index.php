<?php
$title = 'Task Management';
$active_page = 'tasks';
ob_start();
?>

<div class="page-header">
    <h1>Task Management</h1>
    <div class="header-actions">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="/ergon/tasks/create" class="btn btn--primary">Create Task</a>
        <?php endif; ?>
        <a href="/ergon/tasks/calendar" class="btn btn--secondary">Calendar View</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Total Tasks</div>
        <div class="kpi-card__status kpi-card__status--info">All Time</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['in_progress_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">In Progress</div>
        <div class="kpi-card__status kpi-card__status--active">Working</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['completed_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Completed</div>
        <div class="kpi-card__status kpi-card__status--review">Done</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🚨</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -5%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['overdue_tasks'] ?? 0 ?></div>
        <div class="kpi-card__label">Overdue</div>
        <div class="kpi-card__status kpi-card__status--urgent">Critical</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">All Tasks</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned To</th>
                        <th>Priority</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['tasks'] as $task): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <br><small><?= htmlspecialchars(substr($task['description'], 0, 50)) ?>...</small>
                        </td>
                        <td><?= htmlspecialchars($task['assigned_to_name']) ?></td>
                        <td>
                            <span class="badge badge--<?= $task['priority'] === 'high' ? 'error' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                <?= ucfirst($task['priority']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress">
                                <div class="progress__bar" style="width: <?= $task['progress'] ?>%"></div>
                            </div>
                            <small><?= $task['progress'] ?>%</small>
                        </td>
                        <td>
                            <span class="badge badge--<?= $task['status'] === 'completed' ? 'success' : ($task['status'] === 'in_progress' ? 'info' : 'pending') ?>">
                                <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($task['deadline'])) ?></td>
                        <td><?= date('M d, Y', strtotime($task['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>