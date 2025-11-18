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
                                    <?php if ($employee['status'] === 'Absent'): ?>
                                        <button class="btn btn--sm btn--warning" onclick="markManualAttendance(<?= $employee['id'] ?>)" title="Manual Entry">
                                            <span>✏️</span>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn--sm btn--info" onclick="viewAttendanceHistory(<?= $employee['id'] ?>)" title="History">
                                        <span>📊</span>
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
    alert('Attendance history view - Staff ID: ' + staffId);
    // TODO: Implement attendance history modal or page
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

// Auto-refresh every 60 seconds
setInterval(refreshAttendance, 60000);
</script>

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
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>