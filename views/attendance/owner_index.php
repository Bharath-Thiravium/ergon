<?php
$title = 'Owner - Employee Attendance Management';
$active_page = 'attendance';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👑</span> HR & Finance - Employee Attendance Management</h1>
        <p>Complete attendance overview for all staff members (Admins & Employees)</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="refreshAttendance()">
            <span>🔄</span> Refresh
        </button>
        <button class="btn btn--info" onclick="exportAttendance()">
            <span>📊</span> Export Report
        </button>
        <span class="badge badge--info">Today: <?= date('M d, Y') ?></span>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">Total Staff</div>
        </div>
        <div class="kpi-card__value"><?= count($employees) ?></div>
        <div class="kpi-card__label">All Users</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👔</div>
            <div class="kpi-card__trend">Management</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($employees, fn($e) => $e['role'] === 'admin')) ?></div>
        <div class="kpi-card__label">Admins</div>
        <div class="kpi-card__status">Staff</div>
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
    

</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>📊</span> Complete Staff Attendance Report
        </h2>
        <div class="card__actions">
            <input type="date" id="attendanceDate" value="<?= $filter_date ?? date('Y-m-d') ?>" onchange="filterByDate(this.value)" class="form-control" style="width: auto;">
        </div>
    </div>
    <div class="card__body">
        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Staff Members Found</h3>
                <p>No staff members are registered in the system. This could mean:</p>
                <ul style="text-align: left; margin: 1rem 0;">
                    <li>No users exist in the database</li>
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
                            <th>Staff Member</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
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
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $employee['role'] === 'admin' ? '#8b5cf6' : ($employee['status'] === 'Present' ? '#22c55e' : '#ef4444') ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: bold;">
                                        <?= $employee['role'] === 'admin' ? '👔' : strtoupper(substr($employee['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($employee['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars($employee['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge--<?= $employee['role'] === 'admin' ? 'info' : 'secondary' ?>">
                                    <?= $employee['role'] === 'admin' ? '👔 Admin' : '👤 Employee' ?>
                                </span>
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
                                <div style="display: flex; gap: 0.25rem;">
                                    <button class="btn btn--sm btn--secondary" onclick="viewStaffDetails(<?= $employee['id'] ?>)" title="View Details">
                                        <span>👁️</span>
                                    </button>
                                    <?php if ($employee['status'] === 'Absent' || !$employee['check_in']): ?>
                                        <button class="btn btn--sm btn--success" onclick="clockInUser(<?= $employee['id'] ?>)" title="Clock In" style="background-color: #22c55e !important; color: white !important; border: 2px solid #16a34a !important;">
                                            <span>⏰</span>
                                        </button>
                                    <?php elseif ($employee['check_in'] && !$employee['check_out']): ?>
                                        <button class="btn btn--sm btn--warning" onclick="clockOutUser(<?= $employee['id'] ?>)" title="Clock Out" style="background-color: #f97316 !important; color: white !important; border: 2px solid #ea580c !important;">
                                            <span>⏰</span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn--sm btn--warning" onclick="markManualAttendance(<?= $employee['id'] ?>)" title="Manual Entry">
                                        <span>✏️</span>
                                    </button>
                                    <button class="btn btn--sm btn--info" onclick="viewAttendanceHistory(<?= $employee['id'] ?>)" title="History">
                                        <span>📊</span>
                                    </button>
                                    <button class="btn btn--sm btn--primary" onclick="generateUserReport(<?= $employee['id'] ?>)" title="Generate Report">
                                        <span>📄</span>
                                    </button>
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
    window.location.reload();
}

function filterByDate(date) {
    window.location.href = '/ergon/attendance?date=' + date;
}

function exportAttendance() {
    const date = document.getElementById('attendanceDate').value;
    window.open('/ergon/reports/attendance-export?date=' + date, '_blank');
}

function viewStaffDetails(staffId) {
    window.open('/ergon/users/view/' + staffId, '_blank');
}

function viewAttendanceHistory(staffId) {
    window.location.href = '/ergon/attendance/history/' + staffId;
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
        alert('Server error: ' + error.message);
    });
}

function clockInUser(userId) {
    if (confirm('Clock in this user?')) {
        const time = prompt('Enter clock-in time (HH:MM format):', new Date().toTimeString().slice(0,5));
        if (!time) return;
        
        const date = document.getElementById('attendanceDate').value;
        
        fetch('/ergon/api/simple_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                action: 'clock_in', 
                user_id: userId, 
                date: date, 
                time: time 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked in successfully!');
                refreshAttendance();
            } else {
                alert('Error: ' + (data.message || 'Clock in failed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
        });
    }
}

function clockOutUser(userId) {
    if (confirm('Clock out this user?')) {
        const time = prompt('Enter clock-out time (HH:MM format):', new Date().toTimeString().slice(0,5));
        if (!time) return;
        
        const date = document.getElementById('attendanceDate').value;
        
        fetch('/ergon/api/simple_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                action: 'clock_out', 
                user_id: userId, 
                date: date, 
                time: time 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User clocked out successfully!');
                refreshAttendance();
            } else {
                alert('Error: ' + (data.message || 'Clock out failed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred.');
        });
    }
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

// Auto-refresh every 60 seconds
setInterval(refreshAttendance, 60000);
</script>

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

<style>
.badge--info {
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.badge--secondary {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
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

/* Button Styling for Owner Panel */
.btn--sm.btn--warning {
    background-color: #f97316 !important;
    color: #ffffff !important;
    border: 2px solid #ea580c !important;
    font-weight: 700 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.btn--sm.btn--warning:hover {
    background-color: #ea580c !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4) !important;
}

.btn--sm.btn--success {
    background-color: #22c55e !important;
    color: #ffffff !important;
    border: 2px solid #16a34a !important;
    font-weight: 700 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.btn--sm.btn--success:hover {
    background-color: #16a34a !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4) !important;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>