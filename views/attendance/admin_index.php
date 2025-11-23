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
                            In: <?= $admin_attendance['check_in'] ? date('H:i', strtotime($admin_attendance['check_in'])) : '-' ?>
                            <?php if ($admin_attendance['check_out']): ?>
                                | Out: <?= date('H:i', strtotime($admin_attendance['check_out'])) ?>
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

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Employee Attendance Status
        </h2>
        <div class="card__actions">
            <input type="date" id="attendanceDate" value="<?= $filter_date ?? date('Y-m-d') ?>" onchange="filterByDate(this.value)" class="form-control" style="width: auto;">
        </div>
    </div>
    <div class="card__body">
        <?php 
        // Debug output
        echo "<!-- DEBUG: employees count = " . count($employees ?? []) . " -->";
        if (isset($employees) && !empty($employees)) {
            echo "<!-- DEBUG: employees data exists, count = " . count($employees) . " -->";
        } else {
            echo "<!-- DEBUG: employees is empty or not set -->";
        }
        ?>
        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Employees Found</h3>
                <p>No employees are registered in the system. This could mean:</p>
                <ul style="text-align: left; margin: 1rem 0;">
                    <li>No users with role 'user' exist in the database</li>
                    <li>Database connection issues</li>
                    <li>Users table is empty</li>
                </ul>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                    <a href="/ergon/fix_no_employees.php" class="btn btn--primary">🔧 Fix & Create Users</a>
                    <a href="/ergon/debug_attendance_users.php" class="btn btn--secondary">🔍 Debug</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Total Hours</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Debug output
                        if (empty($employees)) {
                            error_log('No employees data passed to view. Current user role: ' . ($_SESSION['role'] ?? 'unknown'));
                        } else {
                            error_log('Displaying ' . count($employees) . ' employees in admin view. Current user role: ' . ($_SESSION['role'] ?? 'unknown'));
                        }
                        
                        foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $employee['status'] === 'Present' ? '#22c55e' : '#ef4444' ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: bold;">
                                        <?= strtoupper(substr($employee['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($employee['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars($employee['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($employee['department'] ?? 'General') ?></td>
                            <td>
                                <?php if ($employee['status'] === 'On Leave'): ?>
                                    <span class="badge badge--warning">
                                        🏖️ On Leave
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge--<?= $employee['status'] === 'Present' ? 'success' : 'danger' ?>">
                                        <?= $employee['status'] === 'Present' ? '✅ Present' : '❌ Absent' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['check_in']): ?>
                                    <span style="color: #059669; font-weight: 500;">
                                        <?= date('H:i', strtotime($employee['check_in'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['check_out']): ?>
                                    <span style="color: #dc2626; font-weight: 500;">
                                        <?= date('H:i', strtotime($employee['check_out'])) ?>
                                    </span>
                                <?php elseif ($employee['check_in']): ?>
                                    <span style="color: #f59e0b; font-weight: 500;">Working...</span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($employee['total_hours'] > 0): ?>
                                    <span style="color: #1f2937; font-weight: 500;">
                                        <?= number_format($employee['total_hours'], 2) ?>h
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">0h</span>
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
    // Use AJAX to refresh the employee table without full page reload
    const currentDate = document.getElementById('attendanceDate').value;
    const url = `/ergon/attendance?ajax=1${currentDate ? '&date=' + currentDate : ''}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.querySelector('tbody');
            const currentTableBody = document.querySelector('.table tbody');
            
            if (newTableBody && currentTableBody) {
                currentTableBody.innerHTML = newTableBody.innerHTML;
                console.log('Attendance table refreshed successfully');
            } else {
                console.log('Table elements not found, doing full reload');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Refresh error:', error);
            // Fallback to full page reload on error
            window.location.reload();
        });
}

function filterByDate(date) {
    // Redirect with date parameter
    window.location.href = '/ergon/attendance?date=' + date;
}

function viewEmployeeDetails(employeeId) {
    alert('Employee details view - Employee ID: ' + employeeId);
    // TODO: Implement employee details modal or page
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
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
    })
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
        if (error.message.includes('404')) {
            alert('Route not found. Please check if the manual attendance route is configured.');
        } else {
            alert('Server error: ' + error.message);
        }
    });
}

// Admin clock in/out functionality
function adminClockAction(type) {
    // Get location if available
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
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
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

// Simple page refresh every 15 seconds for guaranteed updates
setInterval(function() {
    window.location.reload();
}, 15000);
</script>

<style>
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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>