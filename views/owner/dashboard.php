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
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['total_users'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status">Online</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend">↗ +18%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['active_tasks'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status">In Progress</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['pending_leaves'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -3%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['pending_expenses'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Pending Expenses</div>
        <div class="kpi-card__status">Under Review</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                🎯 Project Progress Overview
            </h2>
        </div>
        <div class="card__body">
            <div class="project-summary">
                <div class="summary-stat">
                    <span class="summary-number">12</span>
                    <span class="summary-label">Active Projects</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">35</span>
                    <span class="summary-label">Completed Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">85%</span>
                    <span class="summary-label">Avg Progress</span>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn btn--sm btn--primary" onclick="viewProjectDetails()">
                    View Details
                </button>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                ⚠️ Delayed Tasks Overview
            </h2>
        </div>
        <div class="card__body">
            <div class="project-summary">
                <div class="summary-stat">
                    <span class="summary-number">5</span>
                    <span class="summary-label">Overdue Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">8</span>
                    <span class="summary-label">Due This Week</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">92%</span>
                    <span class="summary-label">On-Time Rate</span>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn btn--sm btn--primary" onclick="viewDelayedTasks()">
                    View Details
                </button>
            </div>
        </div>
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
                <div class="form-label"><?= htmlspecialchars($approval['type'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="kpi-card__value"><?= htmlspecialchars($approval['count'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">⚡ Recent Activities</h2>
        </div>
        <div class="card__body card__body--scrollable">
            <?php foreach ($data['recent_activities'] as $activity): ?>
            <div class="form-group">
                <div class="form-label">📋 <?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></div>
                <p><?= htmlspecialchars($activity['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <small><?= date('M d, H:i', strtotime($activity['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function viewProjectDetails() {
    window.location.href = '/ergon/daily-planner/project-overview';
}

function viewDelayedTasks() {
    window.location.href = '/ergon/daily-planner/delayed-tasks-overview';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
