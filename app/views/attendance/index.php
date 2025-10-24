<?php
$title = 'Attendance Management';
$active_page = 'attendance';
ob_start();
?>

<div class="header-actions">
    <button class="btn btn--primary" onclick="refreshData()">🔄 Refresh</button>
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
        <div class="kpi-card__value"><?= count(array_filter($data['attendance'] ?? [], fn($a) => empty($a['check_out']))) ?></div>
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
                    $recentAttendance = array_filter($data['attendance'] ?? [], fn($a) => !empty($a['check_in']) && strtotime($a['check_in']) > strtotime('-7 days'));
                    if (empty($recentAttendance)): 
                    ?>
                    <tr>
                        <td colspan="6" class="text-center">No recent attendance records</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentAttendance as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></td>
                        <td><?= !empty($record['check_in']) ? date('M j, H:i', strtotime($record['check_in'])) : 'N/A' ?></td>
                        <td><?= !empty($record['check_out']) ? date('M j, H:i', strtotime($record['check_out'])) : '<span class="kpi-card__status kpi-card__status--pending">Still In</span>' ?></td>
                        <td><?= htmlspecialchars($record['location_name'] ?? 'Unknown') ?></td>
                        <td>
                            <span class="kpi-card__status <?= !empty($record['check_out']) ? '' : 'kpi-card__status--pending' ?>">
                                <?= !empty($record['check_out']) ? 'Complete' : 'Active' ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if (!empty($record['check_out']) && !empty($record['check_in'])) {
                                $duration = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 60;
                                echo number_format($duration, 0) . ' min';
                            } elseif (!empty($record['check_in'])) {
                                $duration = (time() - strtotime($record['check_in'])) / 60;
                                echo number_format($duration, 0) . ' min (ongoing)';
                            } else {
                                echo 'N/A';
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