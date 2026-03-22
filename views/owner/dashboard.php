<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'company_owner'])) {
    header("Location: /ergon/login");
    exit;
}

require_once __DIR__ . '/../../app/helpers/ModuleManager.php';

$systemAdminDisabled = false;
$reportsDisabled = false;
try {
    $systemAdminDisabled = ModuleManager::isModuleDisabled('system_admin');
    $reportsDisabled = ModuleManager::isModuleDisabled('reports');
} catch (Exception $e) {
    // Silently fail
}

$title = 'Executive Dashboard';
$active_page = 'dashboard';
?>

<div class="header-actions">
    <a href="/ergon/system-admin" class="btn btn--primary <?= $systemAdminDisabled ? 'btn--disabled' : '' ?>" <?= $systemAdminDisabled ? 'onclick="return false;" style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>🔧 System Admins<?= $systemAdminDisabled ? ' 🔒' : '' ?></a>
    <a href="/ergon/users" class="btn btn--secondary">👥 User Admins</a>
    <a href="/ergon/owner/approvals" class="btn btn--secondary">Review Approvals</a>
    <a href="/ergon/reports" class="btn btn--secondary <?= $reportsDisabled ? 'btn--disabled' : '' ?>" <?= $reportsDisabled ? 'onclick="return false;" style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>View Reports<?= $reportsDisabled ? ' 🔒' : '' ?></a>
    <?php if (!$systemAdminDisabled): ?>
    <a href="/ergon/settings" class="btn btn--secondary">System Settings</a>
    <?php else: ?>
    <span class="btn btn--secondary btn--disabled" style="opacity: 0.5; cursor: not-allowed;">System Settings 🔒</span>
    <?php endif; ?>
</div>

<style>
/* Hide header actions for company_owner users */
body[data-user-role="company_owner"] .header-actions {
    display: none !important;
}
</style>

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
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">💳</div>
            <div class="kpi-card__trend">↗ +2%</div>
        </div>
        <div class="kpi-card__value"><?php
            try {
                if (!isset($db)) {
                    require_once __DIR__ . '/../../app/config/database.php';
                    $db = Database::connect();
                }
                $stmt = $db->query("SELECT COUNT(*) FROM advances WHERE status = 'pending'");
                echo $stmt->fetchColumn();
            } catch (Exception $e) {
                echo '0';
            }
        ?></div>
        <div class="kpi-card__label">Pending Advances</div>
        <div class="kpi-card__status kpi-card__status--pending">Awaiting Approval</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🎯 Project Progress Overview</h2>
            <div class="card-actions">
                <a href="/ergon/dashboard/project-overview" class="btn btn--primary btn--sm">View Details</a>
            </div>
        </div>
        <div class="card__body">
            <div class="overview-summary">
                <div class="summary-stat">
                    <?php
                try {
                    require_once __DIR__ . '/../../app/config/database.php';
                    $db = Database::connect();
                    $stmt = $db->query("SELECT COUNT(*) FROM projects WHERE status = 'active'");
                    $activeProjects = $stmt->fetchColumn();
                    if ($activeProjects == 0) {
                        $stmt = $db->query("SELECT COUNT(DISTINCT project_name) FROM tasks WHERE project_name IS NOT NULL AND project_name != ''");
                        $activeProjects = $stmt->fetchColumn();
                    }
                } catch (Exception $e) {
                    $activeProjects = 0;
                }
                ?>
                <span class="summary-number">📁 <?= $activeProjects ?></span>
                    <span class="summary-label">Active Projects</span>
                </div>
                <div class="summary-stat">
                    <?php
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'");
                    $completedTasks = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $completedTasks = 0;
                }
                ?>
                <span class="summary-number">✅ <?= $completedTasks ?></span>
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
                <a href="/ergon/dashboard/delayed-tasks-overview" class="btn btn--primary btn--sm">View Details</a>
            </div>
        </div>
        <div class="card__body">
            <div class="overview-summary">
                <div class="summary-stat">
                    <?php
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date < CURDATE() OR deadline < CURDATE()) AND status NOT IN ('completed', 'cancelled')");
                    $overdueTasks = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $overdueTasks = 0;
                }
                ?>
                <span class="summary-number">🚨 <?= $overdueTasks ?></span>
                    <span class="summary-label">Overdue Tasks</span>
                </div>
                <div class="summary-stat">
                    <?php
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) OR deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND status NOT IN ('completed', 'cancelled')");
                    $dueThisWeek = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $dueThisWeek = 0;
                }
                ?>
                <span class="summary-number">⏰ <?= $dueThisWeek ?></span>
                    <span class="summary-label">Due This Week</span>
                </div>
                <div class="summary-stat">
                    <?php
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM tasks WHERE (DATE(due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) OR DATE(deadline) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)) AND status NOT IN ('completed', 'cancelled')");
                    $dueTomorrow = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $dueTomorrow = 0;
                }
                ?>
                <span class="summary-number">📅 <?= $dueTomorrow ?></span>
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
            <div class="approval-summary">
                <div class="approval-item">
                    <div class="approval-label">Leave Requests</div>
                    <div class="approval-value"><?= htmlspecialchars($data['stats']['leave_requests'] ?? '3', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="approval-item">
                    <div class="approval-label">Expense Claims</div>
                    <div class="approval-value"><?= htmlspecialchars($data['stats']['expense_claims'] ?? '5', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="approval-item">
                    <div class="approval-label">Advance Requests</div>
                    <div class="approval-value"><?= htmlspecialchars($data['stats']['advance_requests'] ?? '2', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
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



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
