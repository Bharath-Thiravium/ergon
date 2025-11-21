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
                    <?php else: ?>
                        <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: <?= ucfirst($record['user_role'] ?? 'Employee') === 'User' ? 'Employee' : ucfirst($record['user_role'] ?? 'Employee') ?></small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= date('M d, Y', strtotime($record['check_in'] ?? 'now')) ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'present') {
                                            'present' => 'success',
                                            'On Leave' => 'warning',
                                            'Absent' => 'danger',
                                            default => 'success'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= ucfirst($record['status'] ?? 'Present') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-info">
                                        <?php
                                        $workingHours = '0h 0m';
                                        if ($record['check_in'] && $record['check_out']) {
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
                                    <div class="cell-primary">In: <?= $record['check_in'] ? date('H:i', strtotime($record['check_in'])) : '00:00' ?></div>
                                    <div class="cell-secondary">Out: <?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '00:00' ?></div>
                                </div>
                            </td>
                            <?php if (in_array($user_role ?? '', ['owner', 'admin'])): ?>
                            <td>
                                <div class="ab-container">
                                    <?php 
                                    $isToday = date('Y-m-d') === date('Y-m-d', strtotime($record['check_in'] ?? 'now'));
                                    $hasCheckedOut = !empty($record['check_out']);
                                    ?>
                                    
                                    <!-- View Details -->
                                    <button class="ab-btn ab-btn--view" onclick="viewAttendanceDetails(<?= $record['id'] ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    
                                    <?php if ($isToday): ?>
                                        <?php if (empty($record['check_in'])): ?>
                                        <!-- Clock In -->
                                        <button class="ab-btn ab-btn--success" onclick="clockInUser(<?= $record['user_id'] ?>)" title="Clock In User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12,6 12,12 16,14"/>
                                            </svg>
                                        </button>
                                        <?php elseif (!$hasCheckedOut): ?>
                                        <!-- Clock Out -->
                                        <button class="ab-btn ab-btn--warning" onclick="clockOutUser(<?= $record['user_id'] ?>)" title="Clock Out User">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M16 12l-4-4-4 4"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Edit Record -->
                                    <button class="ab-btn ab-btn--edit" onclick="editAttendanceRecord(<?= $record['id'] ?>, <?= $record['user_id'] ?>)" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </button>
                                    
                                    <!-- Generate Report -->
                                    <button class="ab-btn ab-btn--info" onclick="generateUserReport(<?= $record['user_id'] ?>)" title="Generate Report">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10,9 9,9 8,9"/>
                                        </svg>
                                    </button>
                                    
                                    <?php if ($user_role === 'owner'): ?>
                                    <!-- Delete Record (Owner Only) -->
                                    <button class="ab-btn ab-btn--delete" onclick="deleteAttendanceRecord(<?= $record['id'] ?>, '<?= htmlspecialchars($record['user_name'] ?? 'Record', ENT_QUOTES) ?>')" title="Delete Record">
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
    fetch('/ergon/api/attendance_admin.php', {
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
            alert(`Attendance Details:\n\nEmployee: ${record.user_name}\nEmail: ${record.email}\nDate: ${record.date}\nStatus: ${record.status || 'Present'}\nCheck In: ${record.check_in || 'Not checked in'}\nCheck Out: ${record.check_out || 'Not checked out'}\nWorking Hours: ${record.working_hours_calculated || 'N/A'}`);
        } else {
            alert('Error: ' + (data.message || 'Failed to get attendance details'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching attendance details.');
    });
}

function clockInUser(userId) {
    if (confirm('Clock in this user?')) {
        fetch('/ergon/api/attendance_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'clock_in', user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked in successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to clock in user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clocking in user.');
        });
    }
}

function clockOutUser(userId) {
    if (confirm('Clock out this user?')) {
        fetch('/ergon/api/attendance_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'clock_out', user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked out successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to clock out user'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clocking out user.');
        });
    }
}

function editAttendanceRecord(attendanceId, userId) {
    alert('Edit functionality not yet implemented. Please use manual attendance entry for corrections.');
}

function generateUserReport(userId) {
    alert('Report generation not yet implemented.');
}

function deleteAttendanceRecord(attendanceId, userName) {
    if (confirm(`Are you sure you want to delete the attendance record for ${userName}?\n\nThis action cannot be undone.`)) {
        fetch('/ergon/api/attendance_admin.php', {
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
                location.reload();
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>