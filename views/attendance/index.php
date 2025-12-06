<?php
$title = 'Attendance';
$active_page = 'attendance';
require_once __DIR__ . '/../../app/helpers/TimeHelper.php';
require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
$selectedDateForDisplay = isset($_GET['date']) ? (new DateTime($_GET['date']))->format('M d, Y') : (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('M d, Y');
$currentDateIST = $selectedDateForDisplay;

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
                                    <?php if ($user_role === 'owner'): ?>
                                    <button class="ab-btn ab-btn--warning" onclick="markManualAttendance(<?= $_SESSION['user_id'] ?>, '<?= $record['check_in'] ?? '' ?>', '<?= $record['check_out'] ?? '' ?>')" title="Edit Attendance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
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
                            <td>
                                <div class="ab-container">
                                    <?php if ($user_role === 'owner'): ?>
                                    <button class="ab-btn ab-btn--warning" onclick="markManualAttendance(<?= $record['user_id'] ?>, '<?= $record['check_in'] ?? '' ?>', '<?= $record['check_out'] ?? '' ?>')" title="Edit Attendance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <?php if ($user_role === 'owner'): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
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
                                    <button class="ab-btn ab-btn--warning" onclick="markManualAttendance(<?= $record['user_id'] ?>, '<?= $record['check_in'] ?? '' ?>', '<?= $record['check_out'] ?? '' ?>')" title="Edit Attendance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
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
                                    <button class="ab-btn ab-btn--warning" onclick="markManualAttendance(<?= $record['user_id'] ?>, '<?= $record['check_in'] ?? '' ?>', '<?= $record['check_out'] ?? '' ?>')" title="Edit Attendance">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--info" onclick="generateAttendanceReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                        </svg>
                                    </button>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>)" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <polyline points="3,6 5,6 21,6"/>
                                            <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
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

function markManualAttendance(userId, checkIn, checkOut) {
    const hasCheckIn = checkIn && checkIn !== '0000-00-00 00:00:00';
    const hasCheckOut = checkOut && checkOut !== '0000-00-00 00:00:00';
    const defaultEntryType = hasCheckIn && !hasCheckOut ? 'clock_out' : 'clock_in';
    const entryDate = hasCheckIn ? checkIn.split(' ')[0] : new Date().toISOString().split('T')[0];
    const checkInTime = hasCheckIn ? checkIn.split(' ')[1].substring(0, 5) : new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0');
    const checkOutTime = hasCheckOut ? checkOut.split(' ')[1].substring(0, 5) : '17:00';
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${hasCheckIn ? 'Edit Attendance' : 'Manual Attendance Entry'}</h3>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Entry Type:</label>
                    <select id="manual-entry-type" class="form-input" onchange="toggleManualTimeFields()">
                        <option value="clock_in" ${defaultEntryType === 'clock_in' ? 'selected' : ''}>Clock In</option>
                        <option value="clock_out" ${defaultEntryType === 'clock_out' ? 'selected' : ''}>Clock Out</option>
                        <option value="full_day" ${defaultEntryType === 'full_day' ? 'selected' : ''}>Full Day</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" id="manual-entry-date" class="form-input" value="${entryDate}">
                </div>
                <div id="single-time-field" class="form-group">
                    <label>Time:</label>
                    <input type="time" id="manual-entry-time" class="form-input" value="${defaultEntryType === 'clock_out' ? checkOutTime : checkInTime}">
                </div>
                <div id="full-day-fields" style="display: none;">
                    <div class="form-group">
                        <label>Clock In Time:</label>
                        <input type="time" id="manual-clock-in" class="form-input" value="${checkInTime}">
                    </div>
                    <div class="form-group">
                        <label>Clock Out Time:</label>
                        <input type="time" id="manual-clock-out" class="form-input" value="${checkOutTime}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason:</label>
                    <select id="manual-reason" class="form-input">
                        <option value="geo_fencing">Outside geo-fencing range</option>
                        <option value="technical_issue">Technical/App issue</option>
                        <option value="network_problem">Network problem</option>
                        <option value="emergency">Emergency</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes:</label>
                    <textarea id="manual-notes" class="form-input" rows="2" placeholder="Additional details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn--secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                <button class="btn btn--primary" onclick="submitManualAttendance(${userId})">Submit</button>
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
                max-height: 90vh;
                overflow-y: auto;
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
            .form-group {
                margin-bottom: 12px;
            }
            .form-group label {
                display: block;
                margin-bottom: 4px;
                font-weight: 500;
                font-size: 0.875rem;
            }
            .form-input {
                width: 100%;
                padding: 8px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
                font-size: 0.875rem;
            }
            .form-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

function toggleManualTimeFields() {
    const entryType = document.getElementById('manual-entry-type').value;
    const singleTimeField = document.getElementById('single-time-field');
    const fullDayFields = document.getElementById('full-day-fields');
    
    if (entryType === 'full_day') {
        singleTimeField.style.display = 'none';
        fullDayFields.style.display = 'block';
    } else {
        singleTimeField.style.display = 'block';
        fullDayFields.style.display = 'none';
    }
}

function submitManualAttendance(userId) {
    const entryType = document.getElementById('manual-entry-type').value;
    const entryDate = document.getElementById('manual-entry-date').value;
    const reason = document.getElementById('manual-reason').value;
    const notes = document.getElementById('manual-notes').value;
    
    let data = {
        user_id: userId,
        entry_date: entryDate,
        entry_type: entryType,
        reason: reason,
        notes: notes
    };
    
    if (entryType === 'full_day') {
        data.clock_in_time = document.getElementById('manual-clock-in').value;
        data.clock_out_time = document.getElementById('manual-clock-out').value;
    } else {
        data.entry_time = document.getElementById('manual-entry-time').value;
    }
    
    fetch('/ergon/api/manual_attendance_simple.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: Object.keys(data).map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key])).join('&')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Manual attendance entry submitted successfully!');
            document.querySelector('.modal-overlay')?.remove();
            location.reload();
        } else {
            alert('Error: ' + (data.message || data.error || 'Failed to submit'));
            console.log('API Response:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
    });
}

function editAttendanceRecord(attendanceId, userId) {
    alert('Edit functionality for attendance ID: ' + attendanceId);
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
    
    console.log('Generating report for user:', userId, 'from', startDate, 'to', endDate);
    document.querySelector('.modal-overlay')?.remove();
    
    const reportUrl = `/ergon/attendance/report?user_id=${userId}&start_date=${startDate}&end_date=${endDate}`;
    console.log('Report URL:', reportUrl);
    window.open(reportUrl, '_blank');
}

function generateReport(userId) {
    generateAttendanceReport(userId);
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

function makeUserActive(userId) {
    if (confirm('Make this user active?')) {
        fetch('/ergon/users/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&status=active`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User activated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to activate user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function resetUserPassword(userId) {
    if (confirm('Reset password for this user?')) {
        fetch('/ergon/users/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password reset successfully! New password: ' + data.new_password);
            } else {
                alert('Error: ' + (data.error || 'Failed to reset password'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error occurred');
        });
    }
}

function terminateUser(userId) {
    if (confirm('Terminate this user? This action cannot be undone.')) {
        fetch('/ergon/users/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${userId}&status=terminated`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User terminated successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to terminate user'));
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
