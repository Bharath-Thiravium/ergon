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

<?php if (($user_role ?? '') === 'admin' && $admin_attendance): ?>
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card__header">
        <h2 class="card__title">
            <span>💼</span> My Attendance (Admin)
        </h2>
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
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($admin_attendance['user_name'] ?? 'Unknown') ?></strong>
                            <br><small class="text-muted">Role: Admin</small>
                        </td>
                        <td>
                            <div class="assignment-info">
                                <div class="assigned-user"><?= date('M d, Y', strtotime($selected_date ?? 'now')) ?></div>
                                <div class="priority-badge">
                                    <?php 
                                    $statusClass = match($admin_attendance['status'] ?? 'Absent') {
                                        'Present' => 'success',
                                        'On Leave' => 'warning',
                                        'Absent' => 'danger',
                                        default => 'danger'
                                    };
                                    ?>
                                    <span class="badge badge--<?= $statusClass ?>"><?= $admin_attendance['status'] ?? 'Absent' ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-info">
                                    <span class="progress-percentage"><?= $admin_attendance['working_hours'] ?? '0h 0m' ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="cell-meta">
                                <div class="cell-primary">In: <?= $admin_attendance['check_in_time'] ?? '00:00' ?></div>
                                <div class="cell-secondary">Out: <?= $admin_attendance['check_out_time'] ?? '00:00' ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="ab-container">
                                <a class="ab-btn ab-btn--view" href="/ergon/attendance/clock" title="Clock In/Out">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

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
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: Admin</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'Absent') {
                                            'Present' => 'success',
                                            'On Leave' => 'warning',
                                            'Absent' => 'danger',
                                            default => 'danger'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= $record['status'] ?? 'Absent' ?></span>
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
                                    <a class="ab-btn ab-btn--view" data-action="view" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                    <?php if ($user_role === 'owner'): ?>
                                    <a class="ab-btn ab-btn--edit" href="/ergon/attendance/edit?id=<?= $record['attendance_id'] ?? 0 ?>&user_id=<?= $record['user_id'] ?>" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </a>
                                    <button class="ab-btn ab-btn--delete" data-action="delete" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" data-name="<?= htmlspecialchars($record['user_name'] ?? 'Record', ENT_QUOTES) ?>" title="Delete Record">
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
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: Employee</small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'Absent') {
                                            'Present' => 'success',
                                            'On Leave' => 'warning',
                                            'Absent' => 'danger',
                                            default => 'danger'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= $record['status'] ?? 'Absent' ?></span>
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
                                    <a class="ab-btn ab-btn--view" data-action="view" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                    <a class="ab-btn ab-btn--edit" href="/ergon/attendance/edit?id=<?= $record['attendance_id'] ?? 0 ?>&user_id=<?= $record['user_id'] ?>" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </a>
                                    <button class="ab-btn ab-btn--delete" data-action="delete" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" data-name="<?= htmlspecialchars($record['user_name'] ?? 'Record', ENT_QUOTES) ?>" title="Delete Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
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
                                <strong><?= htmlspecialchars($record['user_name'] ?? 'Unknown') ?></strong>
                                <br><small class="text-muted">Role: <?= ucfirst($record['user_role'] ?? 'Employee') === 'User' ? 'Employee' : ucfirst($record['user_role'] ?? 'Employee') ?></small>
                            </td>
                            <td>
                                <div class="assignment-info">
                                    <div class="assigned-user"><?= date('M d, Y') ?></div>
                                    <div class="priority-badge">
                                        <?php 
                                        $statusClass = match($record['status'] ?? 'Absent') {
                                            'Present' => 'success',
                                            'On Leave' => 'warning',
                                            'Absent' => 'danger',
                                            default => 'danger'
                                        };
                                        ?>
                                        <span class="badge badge--<?= $statusClass ?>"><?= $record['status'] ?? 'Absent' ?></span>
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
                                    <a class="ab-btn ab-btn--view" data-action="view" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" title="View Details">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14,2 14,8 20,8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                        </svg>
                                    </a>
                                    <?php if (!(($user_role ?? '') === 'admin' && ($record['user_role'] ?? '') === 'admin')): ?>
                                    <a class="ab-btn ab-btn--edit" href="/ergon/attendance/edit?id=<?= $record['attendance_id'] ?? 0 ?>&user_id=<?= $record['user_id'] ?>" title="Edit Record">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                            <path d="M15 5l4 4"/>
                                        </svg>
                                    </a>
                                    <button class="ab-btn ab-btn--delete" data-action="delete" data-module="attendance" data-id="<?= $record['attendance_id'] ?? 0 ?>" data-name="<?= htmlspecialchars($record['user_name'] ?? 'Record', ENT_QUOTES) ?>" title="Delete Record">
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

<script src="/ergon/assets/js/table-utils.js"></script>

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

function checkOut(recordId) {
    if (confirm('Are you sure you want to check out?')) {
        fetch('/ergon/attendance/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ record_id: recordId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to check out'));
            }
        })
        .catch(error => {
            console.error('Check out error:', error);
            alert('An error occurred while checking out.');
        });
    }
}

// Standard action button handlers
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ab-btn');
    if (!btn) return;
    
    const action = btn.dataset.action;
    const module = btn.dataset.module;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    
    if (action === 'view' && module && id) {
        window.location.href = `/ergon/${module}/view/${id}`;
    } else if (action === 'edit' && module && id) {
        window.location.href = `/ergon/${module}/edit/${id}`;
    } else if (action === 'delete' && module && id && name) {
        deleteRecord(module, id, name);
    }
});
</script>



<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>