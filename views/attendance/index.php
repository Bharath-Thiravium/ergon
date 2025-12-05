<?php
$title = 'Attendance';
$active_page = 'attendance';
require_once __DIR__ . '/../../app/helpers/TimeHelper.php';
require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
$selectedDateForDisplay = isset($_GET['date']) ? (new DateTime($_GET['date']))->format('M d, Y') : (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('M d, Y');
$currentDateIST = $selectedDateForDisplay;

// Helper function to safely format time
function safeFormatTime($datetime) {
    if (!$datetime || $datetime === '0000-00-00 00:00:00') {
        return 'Not set';
    }
    try {
        return TimeHelper::formatToIST($datetime);
    } catch (Exception $e) {
        return date('h:i:s A', strtotime($datetime));
    }
}

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
        <input type="date" id="dateFilter" name="date_filter" value="<?= $selected_date ?? TimezoneHelper::getCurrentDate() ?>" onchange="filterByDate(this.value)" class="form-input" style="margin-right: 1rem;">
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

<?php if ($user_role === 'admin'): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>👤</span> My Attendance Records
        </h2>
        <p class="card__subtitle">Personal attendance details for logged-in admin</p>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table" data-table-utils="initialized">
                <thead>
                    <tr>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Admin Name</span>
                </div>
            </th>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Date &amp; Status</span>
                </div>
            </th>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Working Hours</span>
                </div>
            </th>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Check Times</span>
                </div>
            </th>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Actions</span>
                </div>
            </th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $adminPersonalAttendance = [];
                    if (isset($attendance['admin'])) {
                        $adminPersonalAttendance = array_filter($attendance['admin'], function($record) {
                            return $record['user_id'] == $_SESSION['user_id'];
                        });
                    } elseif (isset($attendance) && is_array($attendance)) {
                        $adminPersonalAttendance = array_filter($attendance, function($record) {
                            return $record['user_id'] == $_SESSION['user_id'];
                        });
                    }
                    ?>
                    <?php if (empty($adminPersonalAttendance)): ?>
                                            <tr>
                        <td colspan="5" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">📍</div>
                                <h3>No Personal Records</h3>
                                <p>No attendance records found for your account.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($adminPersonalAttendance as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong>
                                <br><small class="text-muted">Role: Admin</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? date('M d, Y', strtotime($record['check_in'])) : $currentDateIST ?></div>
                                    <div class="priority-badge">
                                        <span class="badge badge--<?= ($record['status'] ?? 'Present') === 'Present' ? 'success' : 'danger' ?>"><?= $record['status'] ?? 'Present' ?></span>
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
                                    <div class="cell-primary">In: <?= safeFormatTime($record['check_in'] ?? null) ?></div>
                                    <div class="cell-secondary">Out: <?= safeFormatTime($record['check_out'] ?? null) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $_SESSION['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                                    </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title"><?= $user_role === 'admin' ? 'Team Attendance Records' : 'Attendance Records' ?></h2>
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
                        <?php foreach ($attendance['admin'] as $record): ?>
                        <?php if ($record['user_id'] != $_SESSION['user_id']): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: Admin</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? date('M d, Y', strtotime($record['check_in'])) : $currentDateIST ?></div>
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
                                    <div class="cell-primary">In: <?= safeFormatTime($record['check_in'] ?? null) ?></div>
                                    <div class="cell-secondary">Out: <?= safeFormatTime($record['check_out'] ?? null) ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endif; ?>
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
                                    <div class="assigned-user"><?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? date('M d, Y', strtotime($record['check_in'])) : $currentDateIST ?></div>
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
                                    <div class="cell-primary">In: <?= safeFormatTime($record['check_in'] ?? null) ?></div>
                                    <div class="cell-secondary">Out: <?= safeFormatTime($record['check_out'] ?? null) ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
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
                                    <div class="assigned-user"><?= ($record['check_in'] && $record['check_in'] !== '0000-00-00 00:00:00') ? date('M d, Y', strtotime($record['check_in'])) : $currentDateIST ?></div>
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
                                    <div class="cell-primary">In: <?= safeFormatTime($record['check_in'] ?? null) ?></div>
                                    <div class="cell-secondary">Out: <?= safeFormatTime($record['check_out'] ?? null) ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
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

function generateAttendanceReport(userId) {
    const defaultStartDate = new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0];
    const defaultEndDate = new Date().toISOString().split('T')[0];
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate Attendance Report</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <label>Start Date:</label>
                <input type="date" id="report-start-date" name="start_date" value="${defaultStartDate}" class="form-input">
                <label>End Date:</label>
                <input type="date" id="report-end-date" name="end_date" value="${defaultEndDate}" class="form-input">
            </div>
            <div class="modal-footer">
                <button class="btn btn--secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                <button class="btn btn--primary" onclick="downloadAttendanceReport(${userId})">Generate Report</button>
            </div>
        </div>
    `;
    
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10001;
            }
            .modal-content {
                background: white;
                border-radius: 8px;
                width: 400px;
                max-width: 90vw;
            }
            .modal-header {
                padding: 16px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-body {
                padding: 16px;
            }
            .modal-body label {
                display: block;
                margin-bottom: 4px;
                font-weight: 500;
            }
            .modal-body .form-input {
                width: 100%;
                margin-bottom: 12px;
                padding: 8px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
            }
            .modal-footer {
                padding: 16px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
}

function downloadAttendanceReport(userId) {
    const startDate = document.getElementById('report-start-date').value;
    const endDate = document.getElementById('report-end-date').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be later than end date.');
        return;
    }
    
    document.querySelector('.modal-overlay')?.remove();
    const reportUrl = `/ergon/attendance/report?user_id=${userId}&start_date=${startDate}&end_date=${endDate}`;
    window.open(reportUrl, '_blank');
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

<link rel="stylesheet" href="/ergon/assets/css/enhanced-table-utils.css?v=<?= time() ?>">
<script src="/ergon/assets/js/table-utils.js?v=<?= time() ?>"></script>
<script src="/ergon/assets/js/attendance-auto-refresh.js?v=<?= time() ?>"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
