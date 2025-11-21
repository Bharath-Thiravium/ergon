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
        <input type="date" id="dateFilter" value="<?= $selected_date ?? date('Y-m-d') ?>" onchange="filterByDate(this.value)" class="form-input" style="margin-right: 1rem;">
        <?php endif; ?>
        <select id="filterSelect" onchange="filterAttendance(this.value)" class="form-input">
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
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
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
                                    <div class="cell-primary">In: <?= $record['check_in_time'] ?? '00:00' ?></div>
                                    <div class="cell-secondary">Out: <?= $record['check_out_time'] ?? '00:00' ?></div>
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
                                    
                                    <?php 
                                    // Only owners can manually clock in/out admins
                                    if ($user_role === 'owner' && $selected_date === date('Y-m-d')): 
                                    ?>
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
                                    <?php if ($user_role === 'owner'): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>, '<?= htmlspecialchars($record['name'] ?? 'Record', ENT_QUOTES) ?>')" title="Delete Record">
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
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
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
                                    <div class="cell-primary">In: <?= $record['check_in_time'] ?? '00:00' ?></div>
                                    <div class="cell-secondary">Out: <?= $record['check_out_time'] ?? '00:00' ?></div>
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
                                    <?php 
                                    // Admins can only clock in/out regular users, owners can clock in/out anyone
                                    $canClockInOut = ($user_role === 'owner') || ($user_role === 'admin' && $record['role'] === 'user');
                                    if ($canClockInOut && $selected_date === date('Y-m-d')): 
                                    ?>
                                        <?php if (empty($record['check_in']) || $record['check_in'] === null): ?>
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" title="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (empty($record['check_out']) || $record['check_out'] === null): ?>
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
                                    
                                    <button class="ab-btn ab-btn--info" onclick="generateUserReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </button>
                                    
                                    <?php if (in_array($user_role, ['owner', 'admin'])): ?>
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['attendance_id'] ?? 0 ?>, '<?= htmlspecialchars($record['name'] ?? 'Record', ENT_QUOTES) ?>')" title="Delete Record">
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
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: <?= ($record['role'] ?? 'user') === 'user' ? 'Employee' : ucfirst($record['role'] ?? 'user') ?></small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'Present') {
                                            'Present' => 'success',
                                            'present' => 'success', 
                                            'On Leave' => 'warning',
                                            'Absent' => 'danger',
                                            default => 'success'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= $record['status'] ?? 'Present' ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <?php
                                        $workingHours = '0h 0m';
                                        if (isset($record['total_hours']) && $record['total_hours'] > 0) {
                                            $workingHours = number_format($record['total_hours'], 2) . 'h';
                                        } elseif (isset($record['check_in']) && isset($record['check_out']) && $record['check_in'] && $record['check_out']) {
                                            $clockIn = new DateTime($record['check_in']);
                                            $clockOut = new DateTime($record['check_out']);
                                            $diff = $clockIn->diff($clockOut);
                                            $workingHours = $diff->format('%H:%I');
                                        }
                                        ?>
                                        <span class="progress-percentage"><?= $workingHours ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-meta">
                                    <div class="cell-primary">In: <?= isset($record['check_in']) && $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '00:00' ?></div>
                                    <div class="cell-secondary">Out: <?= isset($record['check_out']) && $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '00:00' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <?php 
                                    $isToday = date('Y-m-d') === date('Y-m-d');
                                    $hasCheckedOut = isset($record['check_out']) && !empty($record['check_out']);
                                    ?>
                                    
                                    <!-- View Details -->
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['attendance_id'] ?? 0 ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <?php 
                                    $canClockInOut = ($user_role === 'owner') || ($user_role === 'admin' && ($record['role'] ?? 'user') === 'user');
                                    if ($isToday && $canClockInOut): 
                                    ?>
                                        <?php if (!isset($record['check_in']) || empty($record['check_in']) || $record['check_in'] === null): ?>
                                        <!-- Clock In -->
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= intval($record['user_id'] ?? 0) ?>)" title="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (!$hasCheckedOut): ?>
                                        <!-- Clock Out -->
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= intval($record['user_id'] ?? 0) ?>)" title="Clock Out User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M16 12l-4-4-4 4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Edit Record -->
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendanceRecord(<?= intval($record['attendance_id'] ?? 0) ?>, <?= intval($record['user_id'] ?? 0) ?>)" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Generate Report -->
                                    <button class="ab-btn ab-btn--info" onclick="generateUserReport(<?= intval($record['user_id'] ?? 0) ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </button>
                                    
                                    <?php if (in_array($user_role, ['owner', 'admin'])): ?>
                                    <!-- Delete Record -->
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= intval($record['attendance_id'] ?? 0) ?>, '<?= htmlspecialchars($record['name'] ?? 'Record', ENT_QUOTES) ?>')" title="Delete Record">
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
    if (!attendanceId || attendanceId == 0) {
        alert('No attendance record found');
        return;
    }
    
    fetch('/ergon/api/simple_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'get_details', id: attendanceId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const record = data.record;
            showViewDialog(record);
        } else {
            alert('Error: ' + (data.message || 'Failed to get attendance details'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching attendance details.');
    });
}

function showViewDialog(record) {
    document.getElementById('viewEmployee').textContent = record.user_name || 'Unknown';
    document.getElementById('viewEmail').textContent = record.email || 'N/A';
    document.getElementById('viewDate').textContent = record.date || 'N/A';
    document.getElementById('viewStatus').textContent = record.status || 'Present';
    document.getElementById('viewCheckIn').textContent = record.check_in || 'Not checked in';
    document.getElementById('viewCheckOut').textContent = record.check_out || 'Not checked out';
    document.getElementById('viewWorkingHours').textContent = record.working_hours_calculated || 'N/A';
    document.getElementById('viewDialog').style.display = 'flex';
}

function closeViewDialog() {
    document.getElementById('viewDialog').style.display = 'none';
}

function clockInUser(userId) {
    showAttendanceModal('Clock In User', 'clock_in', userId);
}

function clockOutUser(userId) {
    showAttendanceModal('Clock Out User', 'clock_out', userId);
}

function showAttendanceModal(title, action, userId) {
    document.getElementById('attendanceModalTitle').textContent = title;
    document.getElementById('attendanceAction').value = action;
    document.getElementById('attendanceUserId').value = userId;
    document.getElementById('attendanceDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('attendanceTime').value = action === 'clock_in' ? '09:00' : '18:00';
    document.getElementById('attendanceDialog').style.display = 'flex';
}

function closeAttendanceDialog() {
    document.getElementById('attendanceDialog').style.display = 'none';
}

function submitAttendance() {
    const action = document.getElementById('attendanceAction').value;
    const userId = document.getElementById('attendanceUserId').value;
    const date = document.getElementById('attendanceDate').value;
    const time = document.getElementById('attendanceTime').value;
    
    if (!date || !time) {
        alert('Please select both date and time');
        return;
    }
    
    // Check for future date/time
    const selectedDateTime = new Date(date + 'T' + time);
    const now = new Date();
    
    if (selectedDateTime > now) {
        alert('Warning: Cannot set attendance for future date/time. Please select a past or current date/time.');
        return;
    }
    
    fetch('/ergon/api/simple_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: action, user_id: userId, date: date, time: time })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Success: ' + data.message);
            closeAttendanceDialog();
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
    });
}

function editAttendanceRecord(attendanceId, userId) {
    if (!attendanceId || attendanceId == 0) {
        alert('No attendance record found to edit');
        return;
    }
    
    // Fetch current record details
    fetch('/ergon/api/simple_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ action: 'get_details', id: attendanceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const record = data.record;
            showEditModal(attendanceId, userId, record);
        } else {
            alert('Error: ' + (data.message || 'Failed to get attendance details'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching attendance details.');
    });
}

function showEditModal(attendanceId, userId, record) {
    document.getElementById('editAttendanceId').value = attendanceId;
    document.getElementById('editUserId').value = userId;
    document.getElementById('editDate').value = record.date || '';
    document.getElementById('editCheckIn').value = record.check_in ? record.check_in.substring(11, 16) : '';
    document.getElementById('editCheckOut').value = record.check_out ? record.check_out.substring(11, 16) : '';
    document.getElementById('editDialog').style.display = 'flex';
}

function closeEditDialog() {
    document.getElementById('editDialog').style.display = 'none';
}

function submitEdit() {
    const attendanceId = document.getElementById('editAttendanceId').value;
    const userId = document.getElementById('editUserId').value;
    const date = document.getElementById('editDate').value;
    const checkIn = document.getElementById('editCheckIn').value;
    const checkOut = document.getElementById('editCheckOut').value;
    
    if (!date) {
        alert('Please select a date');
        return;
    }
    
    // Check for future date/time
    const now = new Date();
    const selectedDate = new Date(date);
    
    if (selectedDate > now) {
        alert('Warning: Cannot set attendance for future date. Please select a past or current date.');
        return;
    }
    
    if (checkIn) {
        const checkInDateTime = new Date(date + 'T' + checkIn);
        if (checkInDateTime > now) {
            alert('Warning: Check-in time cannot be in the future.');
            return;
        }
    }
    
    if (checkOut) {
        const checkOutDateTime = new Date(date + 'T' + checkOut);
        if (checkOutDateTime > now) {
            alert('Warning: Check-out time cannot be in the future.');
            return;
        }
    }
    
    fetch('/ergon/api/simple_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
            action: 'edit', 
            id: attendanceId,
            user_id: userId,
            date: date, 
            check_in: checkIn,
            check_out: checkOut
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Success: ' + data.message);
            closeEditDialog();
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
    });
}

function generateUserReport(userId) {
    if (!userId) {
        alert('Invalid user ID');
        return;
    }
    
    document.getElementById('reportUserId').value = userId;
    document.getElementById('reportFromDate').value = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0];
    document.getElementById('reportToDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('reportDialog').style.display = 'flex';
}

function closeReportDialog() {
    document.getElementById('reportDialog').style.display = 'none';
}

function submitReport() {
    const userId = document.getElementById('reportUserId').value;
    const fromDate = document.getElementById('reportFromDate').value;
    const toDate = document.getElementById('reportToDate').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(fromDate) > new Date(toDate)) {
        alert('Start date cannot be after end date');
        return;
    }
    
    closeReportDialog();
    window.open(`/ergon/attendance/export?user_id=${userId}&from=${fromDate}&to=${toDate}`, '_blank');
}

function deleteAttendanceRecord(attendanceId, userName) {
    if (confirm(`Are you sure you want to delete the attendance record for ${userName}?\n\nThis action cannot be undone.`)) {
        fetch('/ergon/api/simple_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'delete', id: attendanceId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Attendance record deleted successfully!');
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete attendance record'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting attendance record.');
        });
    }
}
</script>

<!-- Fast Attendance Modal -->
<div id="attendanceDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4 id="attendanceModalTitle">Attendance</h4>
        <form onsubmit="event.preventDefault(); submitAttendance();">
            <input type="hidden" id="attendanceAction">
            <input type="hidden" id="attendanceUserId">
            
            <div class="form-group">
                <label>Date</label>
                <input type="date" id="attendanceDate" class="form-control" max="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Time</label>
                <input type="time" id="attendanceTime" class="form-control" required>
            </div>
        </form>
        <div class="dialog-buttons">
            <button onclick="closeAttendanceDialog()">Cancel</button>
            <button onclick="submitAttendance()">Submit</button>
        </div>
    </div>
</div>

<!-- View Attendance Details Modal -->
<div id="viewDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4>Attendance Details</h4>
        <div class="details-grid">
            <div class="detail-item">
                <label>Employee:</label>
                <span id="viewEmployee"></span>
            </div>
            <div class="detail-item">
                <label>Email:</label>
                <span id="viewEmail"></span>
            </div>
            <div class="detail-item">
                <label>Date:</label>
                <span id="viewDate"></span>
            </div>
            <div class="detail-item">
                <label>Status:</label>
                <span id="viewStatus"></span>
            </div>
            <div class="detail-item">
                <label>Check In:</label>
                <span id="viewCheckIn"></span>
            </div>
            <div class="detail-item">
                <label>Check Out:</label>
                <span id="viewCheckOut"></span>
            </div>
            <div class="detail-item">
                <label>Working Hours:</label>
                <span id="viewWorkingHours"></span>
            </div>
        </div>
        <div class="dialog-buttons">
            <button onclick="closeViewDialog()">Close</button>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div id="editDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4>Edit Attendance Record</h4>
        <form onsubmit="event.preventDefault(); submitEdit();">
            <input type="hidden" id="editAttendanceId">
            <input type="hidden" id="editUserId">
            
            <div class="form-group">
                <label>Date</label>
                <input type="date" id="editDate" class="form-control" max="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Check In Time</label>
                <input type="time" id="editCheckIn" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Check Out Time</label>
                <input type="time" id="editCheckOut" class="form-control">
            </div>
        </form>
        <div class="dialog-buttons">
            <button onclick="closeEditDialog()">Cancel</button>
            <button onclick="submitEdit()">Update</button>
        </div>
    </div>
</div>

<!-- Generate Report Modal -->
<div id="reportDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4>Generate Attendance Report</h4>
        <form onsubmit="event.preventDefault(); submitReport();">
            <input type="hidden" id="reportUserId">
            
            <div class="form-group">
                <label>From Date</label>
                <input type="date" id="reportFromDate" class="form-control" max="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>To Date</label>
                <input type="date" id="reportToDate" class="form-control" max="<?= date('Y-m-d') ?>" required>
            </div>
        </form>
        <div class="dialog-buttons">
            <button onclick="closeReportDialog()">Cancel</button>
            <button onclick="submitReport()">Generate Report</button>
        </div>
    </div>
</div>

<style>
.dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dialog-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 300px;
    max-width: 400px;
}

.dialog-content h4 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 0.875rem;
    box-sizing: border-box;
}

.dialog-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.dialog-buttons button {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
}

.dialog-buttons button:first-child {
    background: #f3f4f6;
    color: #374151;
}

.dialog-buttons button:last-child {
    background: #3b82f6;
    color: white;
}

.dialog-buttons button:hover {
    opacity: 0.9;
}

.details-grid {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.detail-item label {
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.detail-item span {
    color: #1f2937;
    font-weight: 500;
}
</style>

<script src="/ergon/assets/js/attendance-auto-refresh.js?v=<?= time() ?>"></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>