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
        <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
        <input type="date" id="dateFilter" value="<?= $selected_date ?? date('Y-m-d') ?>" onchange="filterByDate(this.value)" class="form-input">
        <a href="/ergon/views/attendance/manual_entry_simple.php" class="btn btn--secondary">
            <span>✏️</span> Manual Entry
        </a>
        <?php endif; ?>
        <select id="filterSelect" onchange="filterAttendance(this.value)" class="form-input">
            <option value="today" <?= ($current_filter ?? 'today') === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= ($current_filter ?? '') === 'week' ? 'selected' : '' ?>>This Week</option>
            <option value="month" <?= ($current_filter ?? '') === 'month' ? 'selected' : '' ?>>This Month</option>
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
        <div class="kpi-card__value"><?php 
            if ($is_grouped ?? false) {
                echo count($attendance['admin'] ?? []) + count($attendance['user'] ?? []);
            } else {
                echo count($attendance ?? []);
            }
        ?></div>
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
        <h2 class="card__title">Attendance Records</h2>
        <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
        <div class="card__actions">
            <button class="btn btn--sm btn--secondary" onclick="exportAttendance()">
                <span>📊</span> Export
            </button>
            <button class="btn btn--sm btn--info" onclick="generateReport()">
                <span>📋</span> Report
            </button>
        </div>
        <?php endif; ?>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date & Status</th>
                        <th>Working Hours</th>
                        <th>Check Times</th>
                        <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance ?? [])): ?>
                    <tr>
                        <td colspan="<?= in_array($user_role ?? '', ['owner', 'admin']) ? '5' : '4' ?>" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">📍</div>
                                <h3>No Attendance Records</h3>
                                <p>No attendance records found for the selected period.</p>
                                <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                                <a href="/ergon/views/attendance/manual_entry_simple.php" class="btn btn--primary">
                                    Add Manual Entry
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $records = $is_grouped ? array_merge($attendance['admin'] ?? [], $attendance['user'] ?? []) : $attendance;
                        foreach ($records as $record): 
                        ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                    <small class="text-muted"><?= ucfirst($record['user_role'] ?? 'Employee') ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="date-status">
                                    <div class="date"><?= date('M d, Y', strtotime($record['date'] ?? 'now')) ?></div>
                                    <span class="badge badge--<?= match($record['status'] ?? 'Absent') {
                                        'Present' => 'success',
                                        'On Leave' => 'warning', 
                                        'Absent' => 'danger',
                                        default => 'danger'
                                    } ?>"><?= $record['status'] ?? 'Absent' ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="working-hours">
                                    <span class="hours"><?= $record['working_hours'] ?? '0h 0m' ?></span>
                                    <?php if (($record['overtime_hours'] ?? 0) > 0): ?>
                                    <small class="overtime">+<?= $record['overtime_hours'] ?>h OT</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="check-times">
                                    <div class="check-in">In: <?= $record['check_in_time'] ?? '--:--' ?></div>
                                    <div class="check-out">Out: <?= $record['check_out_time'] ?? '--:--' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <!-- View Details -->
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['attendance_id'] ?? 0 ?>, '<?= htmlspecialchars($record['user_name'] ?? '', ENT_QUOTES) ?>')" data-tooltip="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>

                                    <!-- Clock In/Out (if today and not completed) -->
                                    <?php if (date('Y-m-d', strtotime($record['date'] ?? 'now')) === date('Y-m-d')): ?>
                                        <?php if (empty($record['check_in_time'])): ?>
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" data-tooltip="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (empty($record['check_out_time'])): ?>
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= $record['user_id'] ?>)" data-tooltip="Clock Out User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M12 6v6l4 2"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Edit Record -->
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendance(<?= $record['attendance_id'] ?? 0 ?>, <?= $record['user_id'] ?>)" data-tooltip="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>

                                    <!-- Generate Report -->
                                    <button class="ab-btn ab-btn--progress" onclick="generateUserReport(<?= $record['user_id'] ?>, '<?= htmlspecialchars($record['user_name'] ?? '', ENT_QUOTES) ?>')" data-tooltip="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </button>

                                    <!-- Delete (Owner only) -->
                                    <?php if ($user_role === 'owner'): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendance(<?= $record['attendance_id'] ?? 0 ?>, '<?= htmlspecialchars($record['user_name'] ?? '', ENT_QUOTES) ?>')" data-tooltip="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.working-hours {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.overtime {
    color: #f59e0b;
    font-weight: 600;
}

.check-times {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-family: monospace;
}

.check-in { color: #10b981; }
.check-out { color: #ef4444; }

.card__actions {
    display: flex;
    gap: 0.5rem;
}

.empty-state {
    padding: 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}
</style>

<script>
// Attendance-specific action functions
function viewAttendanceDetails(id, userName) {
    // Open modal or navigate to details page
    window.open(`/ergon/attendance/details/${id}`, '_blank');
}

function clockInUser(userId) {
    if (confirm('Clock in this user?')) {
        fetch('/ergon/api/attendance_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clock_in', user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User clocked in successfully', 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        });
    }
}

function clockOutUser(userId) {
    if (confirm('Clock out this user?')) {
        fetch('/ergon/api/attendance_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clock_out', user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User clocked out successfully', 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        });
    }
}

function editAttendance(id, userId) {
    window.location.href = `/ergon/attendance/edit?id=${id}&user_id=${userId}`;
}

function generateUserReport(userId, userName) {
    const dateFrom = prompt('Report from date (YYYY-MM-DD):', '<?= date('Y-m-01') ?>');
    const dateTo = prompt('Report to date (YYYY-MM-DD):', '<?= date('Y-m-d') ?>');
    
    if (dateFrom && dateTo) {
        window.open(`/ergon/attendance/report?user_id=${userId}&from=${dateFrom}&to=${dateTo}`, '_blank');
    }
}

function deleteAttendance(id, userName) {
    if (confirm(`Delete attendance record for ${userName}?`)) {
        fetch('/ergon/api/attendance_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Record deleted successfully', 'success');
                location.reload();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        });
    }
}

function exportAttendance() {
    const format = prompt('Export format (csv/excel):', 'csv');
    if (format) {
        window.location.href = `/ergon/attendance/export?format=${format}&date=<?= $selected_date ?? date('Y-m-d') ?>`;
    }
}

function generateReport() {
    window.open('/ergon/attendance/report', '_blank');
}

function filterAttendance(filter) {
    const currentDate = document.getElementById('dateFilter')?.value || '';
    let url = '/ergon/attendance?filter=' + filter;
    if (currentDate) url += '&date=' + currentDate;
    window.location.href = url;
}

function filterByDate(selectedDate) {
    const currentFilter = document.getElementById('filterSelect')?.value || 'today';
    window.location.href = '/ergon/attendance?date=' + selectedDate + '&filter=' + currentFilter;
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : '#ef4444';
    notification.innerHTML = `
        <div style="position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:1rem;border-radius:6px;z-index:9999;">
            ${message}
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => document.body.removeChild(notification), 3000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>