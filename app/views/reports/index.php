<?php
$title = 'Reports & Analytics';
$active_page = 'reports';

ob_start();
?>

<div class="header-actions" style="margin-bottom: var(--space-6);">
    <a href="/ergon/reports/export" class="btn btn--primary">Export Report</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +3%</div>
        </div>
        <div class="kpi-card__value"><?= $data['attendance_summary']['total_present'] ?>%</div>
        <div class="kpi-card__label">Attendance Rate</div>
        <div class="kpi-card__status kpi-card__status--active">Excellent</div>
    </div>
    
    <div class="kpi-card kpi-card--primary">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend kpi-card__trend--up">↗ +7%</div>
        </div>
        <div class="kpi-card__value"><?= $data['task_summary']['completion_rate'] ?>%</div>
        <div class="kpi-card__label">Task Completion</div>
        <div class="kpi-card__status kpi-card__status--active">On Track</div>
    </div>
    
    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend kpi-card__trend--neutral">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['attendance_summary']['average_hours'] ?>h</div>
        <div class="kpi-card__label">Avg Work Hours</div>
        <div class="kpi-card__status kpi-card__status--info">Standard</div>
    </div>
    
    <div class="kpi-card kpi-card--error">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['task_summary']['overdue_tasks'] ?></div>
        <div class="kpi-card__label">Overdue Tasks</div>
        <div class="kpi-card__status kpi-card__status--urgent">Critical</div>
    </div>
</div>

<div class="reports-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Attendance Summary</h2>
        </div>
        <div class="card__body">
            <div class="report-stats">
                <div class="stat-item">
                    <span class="stat-label">Present Today</span>
                    <span class="stat-value"><?= $data['attendance_summary']['total_present'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Absent Today</span>
                    <span class="stat-value"><?= $data['attendance_summary']['total_absent'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Late Arrivals</span>
                    <span class="stat-value"><?= $data['attendance_summary']['late_arrivals'] ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Task Analytics</h2>
        </div>
        <div class="card__body">
            <div class="report-stats">
                <div class="stat-item">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?= $data['task_summary']['completed_tasks'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= $data['task_summary']['pending_tasks'] ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Overdue</span>
                    <span class="stat-value"><?= $data['task_summary']['overdue_tasks'] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Top Performers</h2>
    </div>
    <div class="card__body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Tasks Completed</th>
                        <th>Attendance Rate</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['user_performance'] as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= $user['tasks_completed'] ?></td>
                        <td><?= $user['attendance_rate'] ?>%</td>
                        <td>
                            <span class="badge badge--<?= $user['attendance_rate'] >= 95 ? 'success' : ($user['attendance_rate'] >= 85 ? 'warning' : 'error') ?>">
                                <?= $user['attendance_rate'] >= 95 ? 'Excellent' : ($user['attendance_rate'] >= 85 ? 'Good' : 'Needs Improvement') ?>
                            </span>
                        </td>
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