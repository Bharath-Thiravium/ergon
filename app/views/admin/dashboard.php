<?php
$title = 'Admin Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <div class="header-actions">
        <a href="/ergon/tasks/create" class="btn btn--primary">Assign Task</a>
        <a href="/ergon/leaves" class="btn btn--secondary">Review Leaves</a>
        <a href="/ergon/expenses" class="btn btn--secondary">Review Expenses</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_users'] ?></div>
        <div class="kpi-card__label">Team Members</div>
        <div class="kpi-card__status kpi-card__status--active">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_tasks'] ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status kpi-card__status--active">Assigned</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?></div>
        <div class="kpi-card__label">Leave Requests</div>
        <div class="kpi-card__status kpi-card__status--pending">Pending</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +25%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['overdue_tasks'] ?></div>
        <div class="kpi-card__label">Overdue Tasks</div>
        <div class="kpi-card__status kpi-card__status--urgent">Urgent</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -12%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?></div>
        <div class="kpi-card__label">Expense Claims</div>
        <div class="kpi-card__status kpi-card__status--review">Review</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Recent Tasks</h2>
        </div>
        <div class="card__body">
            <?php if (empty($data['recent_tasks'])): ?>
            <p>No recent tasks.</p>
            <?php else: ?>
            <?php foreach ($data['recent_tasks'] as $task): ?>
            <div class="timeline-item">
                <div class="timeline-date"><?= date('M d', strtotime($task['created_at'])) ?></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($task['title']) ?></h4>
                    <p>Assigned to: <?= htmlspecialchars($task['assigned_to_name']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Pending Approvals</h2>
        </div>
        <div class="card__body">
            <div class="report-stats">
                <?php foreach ($data['pending_approvals'] as $approval): ?>
                <div class="stat-item">
                    <span class="stat-label"><?= $approval['type'] ?> Requests</span>
                    <span class="stat-value"><?= $approval['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>