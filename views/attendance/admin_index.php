<?php
$title = 'Admin - Attendance Management';
$active_page = 'attendance';
require_once __DIR__ . '/../../app/helpers/TimezoneHelper.php';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> Employee Attendance Management</h1>
        <p>Monitor employee attendance status and working hours - Admin View - <?= isset($filter_date) && $filter_date !== date('Y-m-d') ? date('M d, Y', strtotime($filter_date)) : 'Today' ?></p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="refreshAttendance()">
            <span>🔄</span> Refresh
        </button>
        <span class="badge badge--info">Today: <?= date('M d, Y') ?></span>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">Total</div>
        </div>
        <div class="kpi-card__value"><?= count($employees) ?></div>
        <div class="kpi-card__label">Employees Only</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ Present</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($employees, fn($e) => $e['status'] === 'Present')) ?></div>
        <div class="kpi-card__label">Present Today</div>
        <div class="kpi-card__status">Checked In</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">❌</div>
            <div class="kpi-card__trend">↓ Absent</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($employees, fn($e) => $e['status'] === 'Absent')) ?></div>
        <div class="kpi-card__label">Absent Today</div>
        <div class="kpi-card__status">Not Checked</div>
    </div>
</div>

<!-- Admin Personal Attendance Card -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card__header">
        <h2 class="card__title">
            <span>👔</span> My Attendance - <?= $_SESSION['user_name'] ?? 'Admin' ?>
        </h2>
        <div class="card__actions">
            <span class="badge badge--info">Admin Panel</span>
        </div>
    </div>
    <div class="card__body">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; background: #f8fafc; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #8b5cf6; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; font-weight: bold;">
                    👔
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 1.1rem;"><?= $_SESSION['user_name'] ?? 'Admin' ?></div>
                    <div style="color: #6b7280; font-size: 0.875rem;">Administrator</div>
                    <?php if ($admin_attendance): ?>
                        <div style="color: #059669; font-size: 0.875rem; font-weight: 500;">
                            In: <?= $admin_attendance['check_in'] ? TimezoneHelper::displayTime($admin_attendance['check_in']) : '-' ?>
                            <?php if ($admin_attendance['check_out']): ?>
                                | Out: <?= TimezoneHelper::displayTime($admin_attendance['check_out']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php if (!$admin_attendance): ?>
                    <button id="adminClockInBtn" class="btn btn--primary" onclick="adminClockAction('in')">
                        <span>▶️</span> Clock In
                    </button>
                <?php elseif (!$admin_attendance['check_out']): ?>
                    <button id="adminClockOutBtn" class="btn btn--secondary" onclick="adminClockAction('out')" style="background: #dc2626 !important; border-color: #dc2626 !important;">
                        <span>⏹️</span> Clock Out
                    </button>
                <?php else: ?>
                    <span class="badge badge--success">✅ Completed</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Admin's Detailed Attendance Records -->
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
                    <span class="table-header__text">Location</span>
                </div>
            </th>
                        <th class="table-header__cell">
                <div class="table-header__content">
                    <span class="table-header__text">Project</span>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if ($admin_attendance): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong>
                            <br><small class="text-muted">Role: Administrator</small>
                        </td>
                        <td>
                            <div class="assignment-info">
                                <div class="assigned-user"><?= date('M d, Y', strtotime($filter_date ?? date('Y-m-d'))) ?></div>
                                <div class="priority-badge">
                                    <span class="badge badge--<?= $admin_attendance['check_in'] ? 'success' : 'danger' ?>">
                                        <?= $admin_attendance['check_in'] ? '✅ Present' : '❌ Absent' ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="cell-meta">
                                <div class="cell-primary"><?= htmlspecialchars($admin_attendance['location_display'] ?? '---') ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="cell-meta">
                                <div class="cell-primary"><?= htmlspecialchars($admin_attendance['project_name'] ?? '----') ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span class="progress-percentage">
                                        <?php 
                                        if ($admin_attendance['check_in'] && $admin_attendance['check_out']) {
                                            $hours = (strtotime($admin_attendance['check_out']) - strtotime($admin_attendance['check_in'])) / 3600;
                                            echo number_format($hours, 2) . 'h';
                                        } else {
                                            echo '0h';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="cell-meta">
                                <div class="cell-primary">
                                    In: <?= $admin_attendance['check_in'] ? TimezoneHelper::displayTime($admin_attendance['check_in']) : 'Not clocked in' ?>
                                </div>
                                <div class="cell-secondary">
                                    Out: <?= $admin_attendance['check_out'] ? TimezoneHelper::displayTime($admin_attendance['check_out']) : 'Not clocked out' ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="empty-state">
                                <div class="empty-icon">📍</div>
                                <h3>No Personal Records</h3>
                                <p>No attendance records found for your account.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Employee Attendance Status
        </h2>
        <div class="card__actions attendance-toolbar">
            <div class="attendance-toolbar__left">
                <input type="date" id="attendanceDate" value="<?= $filter_date ?? date('Y-m-d') ?>" onchange="filterByDate(this.value)" class="form-control attendance-date-input" style="width: auto;">
            </div>
            <div class="attendance-toolbar__right">
                <button class="btn btn--warning attendance-mark-holiday-btn" onclick="openHolidayModal()" title="Mark a holiday for all employees">
                    <span>🗓️</span> Mark Holiday
                </button>
            </div>
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Employees Found</h3>
                <p>No employees are registered in the system.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Project</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $employee['status'] === 'Present' ? '#22c55e' : '#ef4444' ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: bold;">
                                        <?= strtoupper(substr($employee['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($employee['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;">Role: <?= ucfirst($employee['role'] ?? 'Employee') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($employee['department'] ?? 'General') ?></td>
                            <td>
                                <?php if ($employee['status'] === 'On Leave'): ?>
                                    <span class="badge badge--warning">🏖️ On Leave</span>
                                <?php else: ?>
                                    <span class="badge badge--<?= $employee['status'] === 'Present' ? 'success' : 'danger' ?>">
                                        <?= $employee['status'] === 'Present' ? '✅ Present' : '❌ Absent' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($employee['location_display'] ?? '---') ?></td>
                            <td><?= htmlspecialchars($employee['project_name'] ?? '----') ?></td>
                            <td>
                                <?php if ($employee['check_in']): ?>
                                    <span style="color: #059669; font-weight: 500;">
                                        <?= TimezoneHelper::displayTime($employee['check_in']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['check_out']): ?>
                                    <span style="color: #dc2626; font-weight: 500;">
                                        <?= TimezoneHelper::displayTime($employee['check_out']) ?>
                                    </span>
                                <?php elseif ($employee['check_in']): ?>
                                    <span style="color: #f59e0b; font-weight: 500;">Working...</span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($employee['working_hours']) && $employee['working_hours'] !== '0h 0m'): ?>
                                    <span style="color: #1f2937; font-weight: 500;">
                                        <?= htmlspecialchars($employee['working_hours']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">0h 0m</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="ab-container">
                                    <button class="ab-btn ab-btn--view" onclick="viewEmployeeDetails(<?= $employee['id'] ?>)" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                    <?php if ($employee['status'] === 'Absent'): ?>
                                        <button class="ab-btn ab-btn--edit" onclick="markManualAttendance(<?= $employee['id'] ?>)" title="Manual Entry">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="M15 5l4 4"/>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function refreshAttendance() {
    const currentDate = document.getElementById('attendanceDate').value;
    const url = `/ergon/attendance?ajax=1${currentDate ? '&date=' + currentDate : ''}`;
    
    fetch(url)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.querySelector('tbody');
            const currentTableBody = document.querySelector('.table tbody');
            
            if (newTableBody && currentTableBody) {
                currentTableBody.innerHTML = newTableBody.innerHTML;
            } else {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Refresh error:', error);
            window.location.reload();
        });
}

function filterByDate(date) {
    window.location.href = '/ergon/attendance?date=' + date;
}

function viewEmployeeDetails(employeeId) {
    alert('Employee details view - Employee ID: ' + employeeId);
}

function markManualAttendance(employeeId) {
    const checkIn = prompt('Enter check-in time (HH:MM format, e.g., 09:00):');
    if (!checkIn) return;
    
    const checkOut = prompt('Enter check-out time (HH:MM format, leave empty if still working):');
    const date = document.getElementById('attendanceDate').value;
    
    const formData = new FormData();
    formData.append('user_id', employeeId);
    formData.append('check_in', checkIn);
    formData.append('check_out', checkOut || '');
    formData.append('date', date);
    
    fetch('/ergon/attendance/manual', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Manual attendance recorded successfully!');
            refreshAttendance();
        } else {
            alert('Error: ' + (data.error || 'Failed to record attendance'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Server error occurred');
    });
}

function openHolidayModal() {
    sessionStorage.setItem('holidayMarked', 'true');
    window.location.href = '/ergon/holidays';
}

function ensureHolidayModalRefresh() {
    // Auto-refresh attendance after returning from holiday modal
    if (sessionStorage.getItem('holidayMarked')) {
        sessionStorage.removeItem('holidayMarked');
        refreshAttendance();
    }
}

function adminClockAction(type) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                performAdminClock(type, position.coords.latitude, position.coords.longitude);
            },
            function(error) {
                performAdminClock(type, null, null);
            }
        );
    } else {
        performAdminClock(type, null, null);
    }
}

function performAdminClock(type, latitude, longitude) {
    const formData = new FormData();
    formData.append('type', type);
    if (latitude && longitude) {
        formData.append('latitude', latitude);
        formData.append('longitude', longitude);
    }
    
    fetch('/ergon/attendance/clock', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Admin clocked ${type} successfully!`);
            window.location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to clock ' + type));
        }
    })
    .catch(error => {
        console.error('Clock error:', error);
        alert('Server error occurred');
    });
}
</script>

<style>
/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.table td {
    vertical-align: middle;
}

/* Badge Colors */
.badge--success {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.badge--danger {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.badge--warning {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

/* Attendance Toolbar */
.attendance-toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    justify-content: space-between;
    flex-wrap: wrap;
}

.attendance-toolbar__left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.attendance-toolbar__right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.attendance-date-input {
    padding: 0.625rem 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.95rem;
    height: 40px;
    min-width: 150px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.attendance-date-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Mark Holiday Button */
.attendance-mark-holiday-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: white;
    border: 1px solid #ea580c;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    height: 40px;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
}

.attendance-mark-holiday-btn:hover {
    background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    transform: translateY(-1px);
}

.attendance-mark-holiday-btn:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.2);
}

.attendance-mark-holiday-btn span {
    font-size: 1.1rem;
    line-height: 1;
}

/* Button classes for standard styling */
.btn--warning {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    color: white;
    border: 1px solid #ea580c;
}

.btn--warning:hover {
    background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .attendance-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .attendance-toolbar__left,
    .attendance-toolbar__right {
        width: 100%;
        justify-content: center;
    }
    
    .attendance-date-input {
        width: 100%;
        min-width: auto;
    }
    
    .attendance-mark-holiday-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .attendance-toolbar {
        gap: 0.5rem;
    }
    
    .attendance-date-input,
    .attendance-mark-holiday-btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        height: 36px;
    }
    
    .attendance-mark-holiday-btn span {
        font-size: 1rem;
    }
    
    .attendance-date-input {
        min-width: 120px;
    }
}

@media (max-width: 480px) {
    .attendance-toolbar {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .attendance-toolbar__left,
    .attendance-toolbar__right {
        width: 100%;
    }
    
    .attendance-date-input,
    .attendance-mark-holiday-btn {
        width: 100%;
        font-size: 0.8rem;
        padding: 0.5rem;
        height: 36px;
    }
    
    .attendance-mark-holiday-btn {
        justify-content: center;
    }
}
</style>

<link rel="stylesheet" href="/ergon/assets/css/mark-holiday-button.css?v=<?= time() ?>">
<link rel="stylesheet" href="/ergon/assets/css/enhanced-table-utils.css?v=<?= time() ?>">
<script src="/ergon/assets/js/table-utils.js?v=<?= time() ?>"></script>

<script>
// Auto-refresh after returning from holiday marking
window.addEventListener('pageshow', function(event) {
    if (sessionStorage.getItem('holidayMarked')) {
        sessionStorage.removeItem('holidayMarked');
        setTimeout(function() {
            refreshAttendance();
        }, 500);
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
