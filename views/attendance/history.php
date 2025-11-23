<?php
$title = 'Employee Attendance History';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>📊</span> Attendance History - <?= htmlspecialchars($employee['name'] ?? 'Employee') ?></h1>
        <p>Complete attendance records for the selected employee</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/attendance" class="btn btn--secondary">
            <span>←</span> Back to Attendance
        </a>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📅</span> Attendance Records
        </h2>
        <div class="card__actions">
            <select id="periodFilter" onchange="filterPeriod(this.value)" class="form-input">
                <option value="30" <?= ($period ?? 30) == 30 ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="60" <?= ($period ?? 30) == 60 ? 'selected' : '' ?>>Last 60 Days</option>
                <option value="90" <?= ($period ?? 30) == 90 ? 'selected' : '' ?>>Last 90 Days</option>
                <option value="365" <?= ($period ?? 30) == 365 ? 'selected' : '' ?>>Last Year</option>
            </select>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($attendance_history)): ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3>No Attendance Records</h3>
                <p>No attendance records found for this employee in the selected period.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_history as $record): ?>
                        <tr>
                            <td>
                                <strong><?= date('M d, Y', strtotime($record['check_in'])) ?></strong>
                                <br><small class="text-muted"><?= date('l', strtotime($record['check_in'])) ?></small>
                            </td>
                            <td>
                                <?php 
                                require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
                                if ($record['check_in']): ?>
                                    <span class="badge badge--success">
                                        <?= TimezoneHelper::displayTime($record['check_in']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['check_out']): ?>
                                    <span class="badge badge--danger">
                                        <?= TimezoneHelper::displayTime($record['check_out']) ?>
                                    </span>
                                <?php elseif ($record['check_in']): ?>
                                    <span class="badge badge--warning">Working</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $totalHours = 0;
                                if ($record['check_in'] && $record['check_out']) {
                                    $totalHours = (strtotime($record['check_out']) - strtotime($record['check_in'])) / 3600;
                                }
                                ?>
                                <?php if ($totalHours > 0): ?>
                                    <strong><?= number_format($totalHours, 2) ?>h</strong>
                                <?php else: ?>
                                    <span class="text-muted">0h</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status = $record['status'] ?? 'present';
                                $statusClass = match($status) {
                                    'present' => 'success',
                                    'late' => 'warning', 
                                    'absent' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge badge--<?= $statusClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= htmlspecialchars($record['location_name'] ?? 'Office') ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Stats -->
            <div class="mt-4">
                <div class="dashboard-grid">
                    <div class="kpi-card">
                        <div class="kpi-card__header">
                            <div class="kpi-card__icon">📅</div>
                        </div>
                        <div class="kpi-card__value"><?= count($attendance_history) ?></div>
                        <div class="kpi-card__label">Total Days</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card__header">
                            <div class="kpi-card__icon">✅</div>
                        </div>
                        <div class="kpi-card__value"><?= count(array_filter($attendance_history, fn($r) => $r['check_in'])) ?></div>
                        <div class="kpi-card__label">Present Days</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card__header">
                            <div class="kpi-card__icon">🕐</div>
                        </div>
                        <div class="kpi-card__value">
                            <?php 
                            $totalHours = 0;
                            foreach ($attendance_history as $record) {
                                if ($record['check_in'] && $record['check_out']) {
                                    $totalHours += (strtotime($record['check_out']) - strtotime($record['check_in'])) / 3600;
                                }
                            }
                            echo number_format($totalHours, 1) . 'h';
                            ?>
                        </div>
                        <div class="kpi-card__label">Total Hours</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterPeriod(period) {
    const employeeId = <?= $employee_id ?? 0 ?>;
    window.location.href = `/ergon/attendance/history/${employeeId}?period=${period}`;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>