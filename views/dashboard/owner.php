<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: /ergon/public/login");
    exit;
}

$title = 'Executive Dashboard';
$active_page = 'dashboard';

ob_start();
?>

<div class="header-actions">
    <a href="/ergon/public/system-admin" class="btn btn--primary">🔧 System Admins</a>
    <a href="/ergon/public/admin/management" class="btn btn--secondary">👥 User Admins</a>
    <a href="/ergon/public/owner/approvals" class="btn btn--secondary">Review Approvals</a>
    <a href="/ergon/public/reports" class="btn btn--secondary">View Reports</a>
    <a href="/ergon/public/settings" class="btn btn--secondary">System Settings</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['total_users'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Users</div>
        <div class="kpi-card__status">Online</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📋</div>
            <div class="kpi-card__trend">↗ +18%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['active_tasks'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status">In Progress</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['pending_leaves'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Pending Leaves</div>
        <div class="kpi-card__status kpi-card__status--pending">Needs Review</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💰</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -3%</div>
        </div>
        <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['pending_expenses'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
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
            <?php if (!empty($data['pending_approvals'])): ?>
                <?php foreach ($data['pending_approvals'] as $approval): ?>
                <div class="form-group">
                    <div class="form-label"><?= htmlspecialchars($approval['type'], ENT_QUOTES, 'UTF-8') ?> Requests</div>
                    <div class="kpi-card__value"><?= htmlspecialchars($approval['count'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="form-group">
                    <div class="form-label">📝 No Pending Approvals</div>
                    <p>All requests have been processed</p>
                </div>
            <?php endif; ?>
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
                <div class="form-label">📋 <?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?></div>
                <p><?= htmlspecialchars($activity['description'], ENT_QUOTES, 'UTF-8') ?></p>
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
