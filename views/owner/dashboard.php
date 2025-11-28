<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    header("Location: /ergon/login");
    exit;
}

$title = 'Executive Dashboard';
$active_page = 'dashboard';

ob_start();
?>

<div class="header-actions">
    <a href="/ergon/system-admin" class="btn btn--primary">🔧 System Admins</a>
    <a href="/ergon/users" class="btn btn--secondary">👥 User Admins</a>
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
            <h2 class="card__title">🎯 Project Progress Overview</h2>
            <div class="card-actions">
                <a href="/ergon/daily-planner/project-overview" class="btn btn--primary btn--sm">View Details</a>
            </div>
        </div>
        <div class="card__body">
            <div class="overview-summary">
                <div class="summary-stat">
                    <span class="summary-number">📁 <?= htmlspecialchars($data['stats']['active_projects'] ?? '0', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="summary-label">Active Projects</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">✅ <?= htmlspecialchars($data['stats']['completed_tasks'] ?? '0', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="summary-label">Completed Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">📊 <?= htmlspecialchars($data['stats']['avg_progress'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</span>
                    <span class="summary-label">Avg Progress</span>
                </div>
            </div>
            <div class="overview-stats">
                <div class="stat-row">
                    <div class="stat-item-inline">
                        <div class="stat-icon">📈</div>
                        <div>
                            <div class="stat-value-sm"><?= htmlspecialchars($data['stats']['in_progress'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="stat-label-sm">In Progress</div>
                        </div>
                    </div>
                    <div class="stat-item-inline">
                        <div class="stat-icon">⏳</div>
                        <div>
                            <div class="stat-value-sm"><?= htmlspecialchars($data['stats']['pending'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="stat-label-sm">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overview-progress">
                <div class="progress-header">
                    <span class="progress-label">Overall Completion</span>
                    <span class="progress-value"><?= htmlspecialchars($data['stats']['completion_rate'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= htmlspecialchars($data['stats']['completion_rate'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%"></div>
                </div>
                <div class="progress-footer">
                    <span class="progress-trend">↗ +12% this month</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">⚠️ Delayed Tasks Overview</h2>
            <div class="card-actions">
                <a href="/ergon/daily-planner/delayed-tasks-overview" class="btn btn--primary btn--sm">View Details</a>
            </div>
        </div>
        <div class="card__body">
            <div class="overview-summary">
                <div class="summary-stat">
                    <span class="summary-number">🚨 <?= htmlspecialchars($data['stats']['overdue_tasks'] ?? '0', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="summary-label">Overdue Tasks</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">⏰ <?= htmlspecialchars($data['stats']['due_this_week'] ?? '0', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="summary-label">Due This Week</span>
                </div>
                <div class="summary-stat">
                    <span class="summary-number">📅 <?= htmlspecialchars($data['stats']['due_tomorrow'] ?? '0', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="summary-label">Due Tomorrow</span>
                </div>
            </div>
            <div class="overview-stats">
                <div class="stat-row">
                    <div class="stat-item-inline">
                        <div class="stat-icon">🔄</div>
                        <div>
                            <div class="stat-value-sm"><?= htmlspecialchars($data['stats']['rescheduled'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="stat-label-sm">Rescheduled</div>
                        </div>
                    </div>
                    <div class="stat-item-inline">
                        <div class="stat-icon">⚠️</div>
                        <div>
                            <div class="stat-value-sm"><?= htmlspecialchars($data['stats']['critical'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="stat-label-sm">Critical</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overview-progress">
                <div class="progress-header">
                    <span class="progress-label">On-Time Rate</span>
                    <span class="progress-value"><?= htmlspecialchars($data['stats']['ontime_rate'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= htmlspecialchars($data['stats']['ontime_rate'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%"></div>
                </div>
                <div class="progress-footer">
                    <span class="progress-trend">↘ -5% from last month</span>
                </div>
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
            <div class="form-group">
                <div class="form-label">Leave Requests</div>
                <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['leave_requests'] ?? '3', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Expense Claims</div>
                <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['expense_claims'] ?? '5', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Advance Requests</div>
                <div class="kpi-card__value"><?= htmlspecialchars($data['stats']['advance_requests'] ?? '2', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
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

<!-- Debug Info -->
<div style="background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;font-family:monospace;font-size:12px;">
    <strong>Debug Info:</strong><br>
    Active Projects: <?= $data['stats']['active_projects'] ?? 'N/A' ?><br>
    Completed Tasks: <?= $data['stats']['completed_tasks'] ?? 'N/A' ?><br>
    In Progress: <?= $data['stats']['in_progress'] ?? 'N/A' ?><br>
    Pending: <?= $data['stats']['pending'] ?? 'N/A' ?><br>
    <?php if (isset($data['stats']['debug_info'])): ?>
        <br><strong>Raw Query Results:</strong><br>
        <?php foreach ($data['stats']['debug_info'] as $key => $value): ?>
            <?= $key ?>: <?= $value ?><br>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/dashboard_styles.php';
include __DIR__ . '/../layouts/dashboard.php';
?>