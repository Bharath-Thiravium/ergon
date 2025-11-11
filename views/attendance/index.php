<?php
$title = 'Attendance';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📍</span> Attendance Management</h1>
        <p>Track employee attendance and working hours</p>
    </div>
    <div class="page-actions">
        <select id="filterSelect" onchange="filterAttendance(this.value)" class="form-control" style="width: auto; margin-right: 1rem;">
            <option value="today" <?= ($current_filter ?? 'today') === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= ($current_filter ?? '') === 'week' ? 'selected' : '' ?>>One Week</option>
            <option value="two_weeks" <?= ($current_filter ?? '') === 'two_weeks' ? 'selected' : '' ?>>Two Weeks</option>
            <option value="month" <?= ($current_filter ?? '') === 'month' ? 'selected' : '' ?>>One Month</option>
        </select>
        <a href="/ergon/attendance/clock" class="btn btn--primary">
            <span>🕰️</span> Clock In/Out
        </a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📍</div>
            <div class="kpi-card__trend">↗ +5%</div>
        </div>
        <div class="kpi-card__value"><?= count($attendance ?? []) ?></div>
        <div class="kpi-card__label">Total Records</div>
        <div class="kpi-card__status">Tracked</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">Present</div>
        </div>
        <div class="kpi-card__value"><?= $stats['present_days'] ?? 0 ?></div>
        <div class="kpi-card__label">Days Present</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🕰️</div>
            <div class="kpi-card__trend">Total</div>
        </div>
        <div class="kpi-card__value"><?= ($stats['total_hours'] ?? 0) ?>h <?= (int)round($stats['total_minutes'] ?? 0) ?>m</div>
        <div class="kpi-card__label">Working Hours</div>
        <div class="kpi-card__status">Logged</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📍</span> Attendance Records
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-clock fa-2x mb-2"></i><br>
                            No attendance records found. <a href="/ergon/attendance/clock">Clock in</a> to start tracking.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></td>
                            <td><?= $record['check_in'] ? date('M d, Y', strtotime($record['check_in'])) : '-' ?></td>
                            <td><?= $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '-' ?></td>
                            <td><?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-' ?></td>
                            <td>
                                <?php if ($record['check_out']): ?>
                                    <?php 
                                    $totalMins = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 60;
                                    $hrs = (int)floor($totalMins / 60);
                                    $mins = (int)round((int)$totalMins % 60);
                                    ?>
                                    <?= $hrs ?>h <?= $mins ?>m
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge--success"><?= ucfirst($record['status'] ?? 'present') ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterAttendance(filter) {
    window.location.href = '/ergon/attendance?filter=' + filter;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
