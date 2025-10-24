<?php
$title = 'Daily Task Manager Dashboard';
$active_page = 'daily-planner-dashboard';
ob_start();
?>

<div class="page-header">
    <h1>📊 Daily Task Manager Dashboard</h1>
    <div class="header-actions">
        <select class="form-control" onchange="window.location.href='?department='+this.value">
            <option value="">All Departments</option>
            <option value="IT" <?= $selectedDepartment === 'IT' ? 'selected' : '' ?>>IT</option>
            <option value="Civil" <?= $selectedDepartment === 'Civil' ? 'selected' : '' ?>>Civil</option>
            <option value="Accounts" <?= $selectedDepartment === 'Accounts' ? 'selected' : '' ?>>Accounts</option>
            <option value="Sales" <?= $selectedDepartment === 'Sales' ? 'selected' : '' ?>>Sales</option>
            <option value="Marketing" <?= $selectedDepartment === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
            <option value="HR" <?= $selectedDepartment === 'HR' ? 'selected' : '' ?>>HR</option>
            <option value="Admin" <?= $selectedDepartment === 'Admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </div>
</div>

<!-- Project Progress Overview -->
<div class="dashboard-grid">
    <div class="card" style="grid-column: span 2;">
        <div class="card__header">
            <h2 class="card__title">🎯 Project Progress Overview</h2>
        </div>
        <div class="card__body">
            <?php foreach ($projectProgress as $project): ?>
            <div class="stat-item">
                <div>
                    <div class="stat-label"><?= htmlspecialchars($project['name']) ?></div>
                    <small class="form-help"><?= $project['completed_tasks'] ?>/<?= $project['total_tasks'] ?> tasks • <?= $project['department'] ?></small>
                </div>
                <div>
                    <span class="badge <?= $project['completion_percentage'] >= 100 ? 'badge--success' : ($project['completion_percentage'] >= 50 ? 'badge--warning' : 'badge--error') ?>">
                        <?= $project['completion_percentage'] ?>%
                    </span>
                    <div class="progress" style="width: 100px; margin-top: 4px;">
                        <div class="progress__bar" style="width: <?= $project['completion_percentage'] ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📊</div>
            <div class="kpi-card__trend kpi-card__trend--up">Active</div>
        </div>
        <div class="kpi-card__value"><?= count($projectProgress) ?></div>
        <div class="kpi-card__label">Active Projects</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">Delayed</div>
        </div>
        <div class="kpi-card__value"><?= count($delayedTasks) ?></div>
        <div class="kpi-card__label">Delayed Tasks</div>
    </div>
    
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend kpi-card__trend--up">Active</div>
        </div>
        <div class="kpi-card__value"><?= count($teamActivity) ?></div>
        <div class="kpi-card__label">Active Users</div>
    </div>
</div>

<!-- Team Activity Today -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">👥 Team Activity - <?= date('d M Y', strtotime($today)) ?></h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Department</th>
                                                    <th>Tasks Updated</th>
                                                    <th>Avg Progress</th>
                                                    <th>Hours Logged</th>
                                                    <th>Performance</th>
                                                </tr>
                                            </thead>
                    <tbody>
                        <?php foreach ($teamActivity as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['name']) ?></td>
                            <td><span class="badge badge--info"><?= htmlspecialchars($activity['department']) ?></span></td>
                            <td>
                                <span class="badge <?= $activity['tasks_updated'] > 0 ? 'badge--success' : 'badge--error' ?>">
                                    <?= $activity['tasks_updated'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($activity['avg_progress']): ?>
                                    <div class="progress" style="width: 80px;">
                                        <div class="progress__bar" style="width: <?= $activity['avg_progress'] ?>%"></div>
                                    </div>
                                    <small><?= round($activity['avg_progress']) ?>%</small>
                                <?php else: ?>
                                    <span class="form-help">No updates</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $activity['total_hours'] ?? 0 ?>h</td>
                            <td>
                                <?php 
                                $performance = 'Poor';
                                $badgeClass = 'badge--error';
                                if ($activity['tasks_updated'] > 0 && $activity['avg_progress'] > 50) {
                                    $performance = 'Excellent';
                                    $badgeClass = 'badge--success';
                                } elseif ($activity['tasks_updated'] > 0) {
                                    $performance = 'Good';
                                    $badgeClass = 'badge--warning';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $performance ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delayed Tasks Alert -->
<?php if (!empty($delayedTasks)): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">⚠️ Delayed Tasks Alert</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Project</th>
                                                    <th>Task</th>
                                                    <th>Category</th>
                                                    <th>Progress</th>
                                                    <th>Days Since Update</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                    <tbody>
                        <?php foreach ($delayedTasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['project_name']) ?></td>
                            <td><?= htmlspecialchars($task['task_name']) ?></td>
                            <td><span class="badge badge--info"><?= htmlspecialchars($task['category_name']) ?></span></td>
                            <td>
                                <div class="progress" style="width: 60px;">
                                    <div class="progress__bar" style="width: <?= $task['completion_percentage'] ?>%"></div>
                                </div>
                                <small><?= $task['completion_percentage'] ?>%</small>
                            </td>
                            <td>
                                <span class="badge badge--error">
                                    <?= $task['days_since_update'] ?? 'Never' ?> days
                                </span>
                            </td>
                            <td>
                                <button class="btn btn--sm btn--primary" onclick="followUpTask(<?= $task['id'] ?>)">
                                    Follow Up
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function followUpTask(taskId) {
    // Simple follow-up action - could be enhanced with notifications
    if (confirm('Send follow-up reminder for this task?')) {
        alert('Follow-up reminder sent! (Feature to be implemented)');
    }
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>