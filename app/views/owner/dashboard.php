<?php
$title = 'Executive Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<div class="header-actions">
    <a href="/ergon/system-admin" class="btn btn--primary">🔧 System Admins</a>
    <a href="/ergon/admin/management" class="btn btn--secondary">👥 User Admins</a>
    <a href="/ergon/owner/approvals" class="btn btn--secondary">Review Approvals</a>
    <a href="/ergon/reports" class="btn btn--secondary">View Reports</a>
    <a href="/ergon/settings" class="btn btn--secondary">System Settings</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['total_users'] ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status">Online</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend">↗ +18%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['active_tasks'] ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status">In Progress</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_leaves'] ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -3%</div>
        </div>
        <div class="kpi-card__value"><?= $data['stats']['pending_expenses'] ?></div>
        <div class="kpi-card__label">Pending Expenses</div>
        <div class="kpi-card__status">Under Review</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📊 Approval Summary</h2>
        </div>
        <div class="card__body">
            <?php foreach ($data['pending_approvals'] as $approval): ?>
            <div class="form-group">
                <div class="form-label"><?= $approval['type'] ?> Requests</div>
                <div class="kpi-card__value"><?= $approval['count'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">⚡ Recent Activities</h2>
        </div>
        <div class="card__body card__body--scrollable">
            <?php if (empty($data['recent_activities'])): ?>
            <div class="form-group">
                <div class="form-label">📝 System Initialized</div>
                <p>ERGON system is ready for use</p>
            </div>
            <?php else: ?>
            <?php foreach ($data['recent_activities'] as $activity): ?>
            <div class="form-group">
                <div class="form-label">📋 <?= htmlspecialchars($activity['action']) ?></div>
                <p><?= htmlspecialchars($activity['description']) ?></p>
                <small><?= date('M d, H:i', strtotime($activity['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>