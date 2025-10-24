<?php
$title = 'Executive Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/admin/management" class="btn btn--primary">👥 Manage Admins</a>
    <a href="/ergon/owner/approvals" class="btn btn--secondary">Review Approvals</a>
    <a href="/ergon/reports" class="btn btn--secondary">View Reports</a>
    <a href="/ergon/settings" class="btn btn--secondary">System Settings</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_users'] ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status kpi-card__status--active">Online</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +18%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_tasks'] ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status kpi-card__status--active">In Progress</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -3%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?></div>
        <div class="kpi-card__label">Pending Expenses</div>
        <div class="kpi-card__status kpi-card__status--review">Under Review</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Approval Summary</h2>
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
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Recent Activities</h2>
        </div>
        <div class="card__body">
            <?php foreach ($data['recent_activities'] as $activity): ?>
            <div class="timeline-item">
                <div class="timeline-date"><?= date('M d', strtotime($activity['created_at'])) ?></div>
                <div class="timeline-content">
                    <h4><?= htmlspecialchars($activity['action']) ?></h4>
                    <p><?= htmlspecialchars($activity['description']) ?></p>
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