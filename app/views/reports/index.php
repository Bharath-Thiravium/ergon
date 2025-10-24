<?php
$title = 'Reports & Analytics';
$active_page = 'reports';

ob_start();
?>

<div class="header-actions">
    <a href="/ergon/reports/export" class="btn btn--primary">Export Report</a>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">↗ +3%</div>
        </div>
        <div class="kpi-card__value"><?= $data['attendance_summary']['total_present'] ?>%</div>
        <div class="kpi-card__label">Attendance Rate</div>
        <div class="kpi-card__status">Excellent</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ +7%</div>
        </div>
        <div class="kpi-card__value"><?= $data['task_summary']['completion_rate'] ?>%</div>
        <div class="kpi-card__label">Task Completion</div>
        <div class="kpi-card__status">On Track</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏰</div>
            <div class="kpi-card__trend">— 0%</div>
        </div>
        <div class="kpi-card__value"><?= $data['attendance_summary']['average_hours'] ?>h</div>
        <div class="kpi-card__label">Avg Work Hours</div>
        <div class="kpi-card__status">Standard</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">↘ -15%</div>
        </div>
        <div class="kpi-card__value"><?= $data['task_summary']['overdue_tasks'] ?></div>
        <div class="kpi-card__label">Overdue Tasks</div>
        <div class="kpi-card__status kpi-card__status--pending">Critical</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📅 Attendance Summary</h2>
        </div>
        <div class="card__body">
            <div class="form-group">
                <div class="form-label">Present Today</div>
                <div class="kpi-card__value"><?= $data['attendance_summary']['total_present'] ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Absent Today</div>
                <div class="kpi-card__value"><?= $data['attendance_summary']['total_absent'] ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Late Arrivals</div>
                <div class="kpi-card__value"><?= $data['attendance_summary']['late_arrivals'] ?></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📊 Task Analytics</h2>
        </div>
        <div class="card__body">
            <div class="form-group">
                <div class="form-label">Completed</div>
                <div class="kpi-card__value"><?= $data['task_summary']['completed_tasks'] ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Pending</div>
                <div class="kpi-card__value"><?= $data['task_summary']['pending_tasks'] ?></div>
            </div>
            <div class="form-group">
                <div class="form-label">Overdue</div>
                <div class="kpi-card__value"><?= $data['task_summary']['overdue_tasks'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">🏆 Top Performers</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
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
                            <span class="kpi-card__status <?= $user['attendance_rate'] >= 95 ? '' : 'kpi-card__status--pending' ?>">
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