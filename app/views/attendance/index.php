<?php
$title = 'Attendance Management';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <h1>📍 Attendance Management</h1>
    <div class="header-actions">
        <button class="btn btn--primary" onclick="refreshData()">🔄 Refresh</button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['attendance'], fn($a) => $a['status'] === 'present' && date('Y-m-d', strtotime($a['check_in'])) === date('Y-m-d'))) ?></div>
        <div class="kpi-card__label">Present Today</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🟡</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['attendance'], fn($a) => $a['check_out'] === null && date('Y-m-d', strtotime($a['check_in'])) === date('Y-m-d'))) ?></div>
        <div class="kpi-card__label">Still Clocked In</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Today's Attendance</h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $todayAttendance = array_filter($data['attendance'], fn($a) => date('Y-m-d', strtotime($a['check_in'])) === date('Y-m-d'));
                    if (empty($todayAttendance)): 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center">No attendance records for today</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($todayAttendance as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['user_name']) ?></td>
                        <td><?= date('H:i', strtotime($record['check_in'])) ?></td>
                        <td><?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '<span class="badge badge--warning">Still In</span>' ?></td>
                        <td><?= htmlspecialchars($record['location_name']) ?></td>
                        <td>
                            <span class="badge badge--<?= $record['status'] === 'present' ? 'success' : 'warning' ?>">
                                <?= ucfirst($record['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($record['check_out']) {
                                $duration = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 3600;
                                echo number_format($duration, 1) . 'h';
                            } else {
                                $duration = (time() - strtotime($record['check_in'])) / 3600;
                                echo number_format($duration, 1) . 'h (ongoing)';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function refreshData() {
    location.reload();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>