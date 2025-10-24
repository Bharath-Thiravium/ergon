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
        <div class="kpi-card__value"><?= count($data['attendance']) ?></div>
        <div class="kpi-card__label">Total Records</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🟡</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['attendance'], fn($a) => $a['check_out'] === null)) ?></div>
        <div class="kpi-card__label">Active Sessions</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Recent Attendance</h2>
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
                    // Show recent attendance (last 7 days) instead of just today
                    $recentAttendance = array_filter($data['attendance'], fn($a) => strtotime($a['check_in']) > strtotime('-7 days'));
                    if (empty($recentAttendance)): 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center">No recent attendance records</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentAttendance as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['user_name']) ?></td>
                        <td><?= date('M j, H:i', strtotime($record['check_in'])) ?></td>
                        <td><?= $record['check_out'] ? date('M j, H:i', strtotime($record['check_out'])) : '<span class="badge badge--warning">Still In</span>' ?></td>
                        <td><?= htmlspecialchars($record['location_name']) ?></td>
                        <td>
                            <span class="badge badge--<?= $record['check_out'] ? 'success' : 'warning' ?>">
                                <?= $record['check_out'] ? 'Complete' : 'Active' ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($record['check_out']) {
                                $duration = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 60;
                                echo number_format($duration, 0) . ' min';
                            } else {
                                $duration = (time() - strtotime($record['check_in'])) / 60;
                                echo number_format($duration, 0) . ' min (ongoing)';
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