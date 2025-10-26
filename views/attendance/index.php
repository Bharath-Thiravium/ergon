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
            <div class="kpi-card__trend">↗ +8%</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($attendance ?? [], fn($a) => ($a['status'] ?? 'present') === 'present')) ?></div>
        <div class="kpi-card__label">Present Today</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🕰️</div>
            <div class="kpi-card__trend">↗ +3%</div>
        </div>
        <div class="kpi-card__value"><?= number_format(array_sum(array_map(function($a) { return $a['check_out'] ? (strtotime($a['check_out']) - strtotime($a['check_in'])) / 3600 : 0; }, $attendance ?? [])), 1) ?>h</div>
        <div class="kpi-card__label">Total Hours</div>
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
                    <?php foreach ($attendance ?? [] as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></td>
                        <td><?= date('M d, Y', strtotime($record['check_in'])) ?></td>
                        <td><?= date('H:i', strtotime($record['check_in'])) ?></td>
                        <td><?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '-' ?></td>
                        <td><?= $record['check_out'] ? round((strtotime($record['check_out']) - strtotime($record['check_in'])) / 3600, 1) . 'h' : '-' ?></td>
                        <td><span class="badge badge--success"><?= ucfirst($record['status'] ?? 'present') ?></span></td>
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
