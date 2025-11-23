<?php
$title = 'Attendance';
$active_page = 'attendance';
require_once __DIR__ . '/../../app/helpers/TimeHelper.php';
require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
$currentDateIST = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('M d, Y');
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <?php if ($user_role === 'user'): ?>
        <h1><span>📍</span> My Attendance</h1>
        <p>View your attendance records and working hours</p>
        <?php else: ?>
        <h1><span>📍</span> Attendance Management</h1>
        <p>Track employee attendance and working hours</p>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
        <input type="date" id="dateFilter" value="<?= $selected_date ?? TimezoneHelper::getCurrentDate() ?>" onchange="filterByDate(this.value)" class="form-input" style="margin-right: 1rem;">
        <select id="filterSelect" onchange="filterAttendance(this.value)" class="form-input">
            <option value="today" <?= ($current_filter ?? 'today') === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= ($current_filter ?? '') === 'week' ? 'selected' : '' ?>>One Week</option>
            <option value="two_weeks" <?= ($current_filter ?? '') === 'two_weeks' ? 'selected' : '' ?>>Two Weeks</option>
            <option value="month" <?= ($current_filter ?? '') === 'month' ? 'selected' : '' ?>>One Month</option>
        </select>
        <?php endif; ?>
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
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="col-title">Employee</th>
                        <th class="col-assignment">Date & Status</th>
                        <th class="col-progress">Working Hours</th>
                        <th class="col-date">Check Times</th>
                        <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                        <th class="col-actions">Actions</th>
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
                                <p>No attendance records found.</p>
                            </div>
                        </td>
                    </tr>
                    <?php elseif ($is_grouped ?? false): ?>
                        <!-- Admin Users Section -->
                        <?php if (!empty($attendance['admin'])): ?>
                        <tr class="group-header">
                            <td colspan="<?= in_array($user_role ?? '', ['owner', 'admin']) ? '5' : '4' ?>" style="background: #f8fafc; font-weight: 600; color: #374151; padding: 0.75rem 1rem; border-top: 2px solid #e5e7eb;">
                                <span>👔</span> Admin Users
                            </td>
                        </tr>
                        <?php foreach ($attendance['admin'] as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: Admin</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= $currentDateIST ?></div>
                                    <div class="priority-badge">
                                        <span class="badge badge--<?= $record['status'] === 'Present' ? 'success' : 'danger' ?>"><?= $record['status'] ?? 'Absent' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <span class="progress-percentage"><?= $record['working_hours'] ?? '0h 0m' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary">In: <?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_in']) : 'Not clocked in' ?></div>
                                    <div class="cell-secondary">Out: <?= ($record['check_out'] && $record['check_out'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_out']) : 'Not clocked out' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['attendance_id'] ?? 0 ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if (($selected_date ?? TimezoneHelper::getCurrentDate()) === TimezoneHelper::getCurrentDate()): ?>
                                        <?php if (empty($record['check_in'])): ?>
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" title="Clock In Admin">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (empty($record['check_out'])): ?>
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= $record['user_id'] ?>)" title="Clock Out Admin">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M16 12l-4-4-4 4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>, <?= $record['user_id'] ?>)" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--info" onclick="generateReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <?php if (($record['attendance_id'] ?? 0) && in_array($user_role, ['owner', 'admin'])): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Employee Users Section -->
                        <?php if (!empty($attendance['user'])): ?>
                        <tr class="group-header">
                            <td colspan="<?= in_array($user_role ?? '', ['owner', 'admin']) ? '5' : '4' ?>" style="background: #f8fafc; font-weight: 600; color: #374151; padding: 0.75rem 1rem; border-top: 2px solid #e5e7eb;">
                                <span>👥</span> Employee Users
                            </td>
                        </tr>
                        <?php foreach ($attendance['user'] as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: Employee</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= $currentDateIST ?></div>
                                    <div class="priority-badge">
                                        <span class="badge badge--<?= $record['status'] === 'Present' ? 'success' : 'danger' ?>"><?= $record['status'] ?? 'Absent' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <span class="progress-percentage"><?= $record['working_hours'] ?? '0h 0m' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary">In: <?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_in']) : 'Not clocked in' ?></div>
                                    <div class="cell-secondary">Out: <?= ($record['check_out'] && $record['check_out'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_out']) : 'Not clocked out' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['attendance_id'] ?? 0 ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if (($selected_date ?? TimezoneHelper::getCurrentDate()) === TimezoneHelper::getCurrentDate()): ?>
                                        <?php if (empty($record['check_in'])): ?>
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" title="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (empty($record['check_out'])): ?>
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= $record['user_id'] ?>)" title="Clock Out User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M16 12l-4-4-4 4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>, <?= $record['user_id'] ?>)" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--info" onclick="generateReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <?php if (($record['attendance_id'] ?? 0) && in_array($user_role, ['owner', 'admin'])): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: <?= ($record['role'] ?? 'user') === 'user' ? 'Employee' : ucfirst($record['role'] ?? 'user') ?></small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= $currentDateIST ?></div>
                                    <div class="priority-badge">
                                        <span class="badge badge--<?= $record['status'] === 'Present' ? 'success' : 'danger' ?>"><?= $record['status'] ?? 'Present' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <span class="progress-percentage"><?= $record['working_hours'] ?? '0h 0m' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary">In: <?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_in']) : 'Not clocked in' ?></div>
                                    <div class="cell-secondary">Out: <?= ($record['check_out'] && $record['check_out'] !== '0000-00-00 00:00:00') ? TimeHelper::formatToIST($record['check_out']) : 'Not clocked out' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['attendance_id'] ?? 0 ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if (($selected_date ?? TimezoneHelper::getCurrentDate()) === TimezoneHelper::getCurrentDate()): ?>
                                        <?php if (!isset($record['check_in']) || empty($record['check_in'])): ?>
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" title="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (isset($record['check_in']) && !empty($record['check_in']) && (!isset($record['check_out']) || empty($record['check_out']))): ?>
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= $record['user_id'] ?>)" title="Clock Out User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M16 12l-4-4-4 4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>, <?= $record['user_id'] ?>)" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--info" onclick="generateReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <?php if (($record['attendance_id'] ?? 0) && in_array($user_role, ['owner', 'admin'])): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
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

<script>
function filterAttendance(filter) {
    const currentDate = document.getElementById('dateFilter')?.value || '';
    let url = '/ergon/attendance?filter=' + filter;
    if (currentDate) {
        url += '&date=' + currentDate;
    }
    window.location.href = url;
}

function filterByDate(selectedDate) {
    const currentFilter = document.getElementById('filterSelect')?.value || 'today';
    window.location.href = '/ergon/attendance?date=' + selectedDate + '&filter=' + currentFilter;
}

function viewAttendanceDetails(attendanceId) {
    alert('View details for attendance ID: ' + attendanceId);
}

function clockInUser(userId) {
    if (confirm('Clock in this user?')) {
        fetch('/ergon/attendance/manual', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&check_in=1&date=${new Date().toISOString().split('T')[0]}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked in successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to clock in'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function clockOutUser(userId) {
    if (confirm('Clock out this user?')) {
        fetch('/ergon/attendance/manual', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&check_out=1&date=${new Date().toISOString().split('T')[0]}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked out successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to clock out'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function editAttendanceRecord(attendanceId, userId) {
    alert('Edit functionality for attendance ID: ' + attendanceId);
}

function generateReport(userId) {
    window.open('/ergon/reports/user/' + userId, '_blank');
}

function deleteAttendanceRecord(attendanceId) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        fetch('/ergon/attendance/delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `attendance_id=${attendanceId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Record deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to delete'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}
</script>

<script src="/ergon/assets/js/attendance-auto-refresh.js?v=<?= time() ?>"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>