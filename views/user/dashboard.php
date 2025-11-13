<?php
$title = 'My Dashboard';
$active_page = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>🏠</span> Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</h1>
        <p>Here's what's happening with your work today</p>
    </div>
    <div class="page-actions">
        <a href="/ergon/leaves/create" class="btn btn--primary">
            <span>📅</span> Request Leave
        </a>
        <a href="/ergon/expenses/create" class="btn btn--secondary">
            <span>💰</span> Submit Expense
        </a>
    </div>
</div>

<?php if (isset($attendance_status) && $attendance_status['status'] === 'not_clocked_in'): ?>
<div class="alert alert--warning">
    ⏰ You haven't clocked in today. 
    <button class="btn btn--warning" onclick="clockIn()">Clock In Now</button>
</div>
<?php elseif (isset($attendance_status) && $attendance_status['status'] === 'clocked_in'): ?>
<div class="alert alert--success">
    ✅ You clocked in at <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?>. 
    <button class="btn btn--success" onclick="clockOut()">Clock Out</button>
</div>
<?php elseif (isset($attendance_status) && $attendance_status['status'] === 'clocked_out'): ?>
<div class="alert alert--info">
    ℹ️ You completed your day from <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?> to <?= date('h:i A', strtotime($attendance_status['clock_out'])) ?>.
</div>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
            <div class="kpi-card__trend">↗ Active</div>
        </div>
        <div class="kpi-card__value"><?= $stats['my_tasks']['total'] ?? 0 ?></div>
        <div class="kpi-card__label">My Tasks</div>
        <div class="kpi-card__status"><?= $stats['my_tasks']['pending'] ?? 0 ?> pending, <?= $stats['my_tasks']['in_progress'] ?? 0 ?> in progress</div>
    </div>

    <div class="kpi-card kpi-card--success">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">This Month</div>
        </div>
        <div class="kpi-card__value"><?= $stats['attendance_this_month'] ?? 0 ?></div>
        <div class="kpi-card__label">Attendance</div>
        <div class="kpi-card__status">Days Present</div>
    </div>

    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⏳</div>
            <div class="kpi-card__trend">Pending</div>
        </div>
        <div class="kpi-card__value"><?= $stats['pending_requests'] ?? 0 ?></div>
        <div class="kpi-card__label">Requests</div>
        <div class="kpi-card__status">Awaiting Approval</div>
    </div>

    <div class="kpi-card kpi-card--info">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏖️</div>
            <div class="kpi-card__trend">Available</div>
        </div>
        <div class="kpi-card__value"><?= $stats['leave_balance'] ?? 0 ?></div>
        <div class="kpi-card__label">Leave Balance</div>
        <div class="kpi-card__status">Days Remaining</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card" style="grid-column: span 2;">
        <div class="card__header">
            <h2 class="card__title">
                <span>✅</span> Today's Tasks & Priorities
            </h2>
            <div class="card__actions">
                <a href="/ergon/tasks" class="btn btn--primary">View All Tasks</a>
            </div>
        </div>
        <div class="card__body">
            <?php if (empty($today_tasks ?? [])): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>No tasks for today</h3>
                    <p>Great job! You're all caught up.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_tasks as $task): ?>
                            <tr>
                                <td>
                                    <div class="cell-meta">
                                        <div class="cell-primary"><?= htmlspecialchars($task['title']) ?></div>
                                        <?php if (!empty($task['description'])): ?>
                                        <div class="cell-secondary"><?= htmlspecialchars(substr($task['description'], 0, 50)) ?>...</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge--<?= $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                        <?= ucfirst($task['priority']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d', strtotime($task['due_date'])) ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $task['progress'] ?? 0 ?>%"></div>
                                        <span class="progress-text"><?= $task['progress'] ?? 0 ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-icon btn-icon--edit" onclick="updateTaskProgress(<?= $task['id'] ?>)" title="Update Progress">
                                        ✏️
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🕰️</span> Attendance
            </h2>
        </div>
        <div class="card__body" style="text-align: center;">
            <?php if (isset($attendance_status) && $attendance_status['can_clock_in']): ?>
                <button class="btn btn--success" onclick="clockIn()" style="padding: 1rem 2rem; font-size: 1.2rem;">
                    ▶️ Clock In
                </button>
                <p style="margin-top: 1rem; color: #666;">Start your workday</p>
            <?php elseif (isset($attendance_status) && $attendance_status['can_clock_out']): ?>
                <p style="color: #28a745; margin-bottom: 1rem;">
                    ✅ Clocked in at <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?>
                </p>
                <button class="btn btn--warning" onclick="clockOut()" style="padding: 1rem 2rem; font-size: 1.2rem;">
                    ⏹️ Clock Out
                </button>
            <?php else: ?>
                <div style="color: #28a745;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">✅</div>
                    <p>Work completed for today!</p>
                    <?php if (isset($attendance_status['clock_in']) && isset($attendance_status['clock_out'])): ?>
                    <small style="color: #666;">
                        <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?> - 
                        <?= date('h:i A', strtotime($attendance_status['clock_out'])) ?>
                    </small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">
                <span>🔗</span> Quick Links
            </h2>
        </div>
        <div class="card__body">
            <div class="quick-links">
                <a href="/ergon/tasks" class="quick-link">
                    <span>✅</span> My Tasks
                </a>
                <a href="/ergon/leaves" class="quick-link">
                    <span>📅</span> My Leaves
                </a>
                <a href="/ergon/expenses" class="quick-link">
                    <span>💰</span> My Expenses
                </a>
                <a href="/ergon/profile" class="quick-link">
                    <span>👤</span> My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.progress-bar {
    position: relative;
    background: #e9ecef;
    border-radius: 4px;
    height: 20px;
    overflow: hidden;
}

.progress-fill {
    background: #28a745;
    height: 100%;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
    font-weight: bold;
    color: #333;
}

.quick-links {
    display: grid;
    gap: 0.5rem;
}

.quick-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--text-primary);
    transition: background-color 0.2s;
}

.quick-link:hover {
    background: var(--bg-hover);
}
</style>

<script>
function clockIn() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const formData = new FormData();
            formData.append('latitude', position.coords.latitude);
            formData.append('longitude', position.coords.longitude);
            
            fetch('/ergon/user/clock-in', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while clocking in');
            });
        }, function(error) {
            alert('Location access is required for attendance. Please enable location services.');
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function clockOut() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const formData = new FormData();
            formData.append('latitude', position.coords.latitude);
            formData.append('longitude', position.coords.longitude);
            
            fetch('/ergon/user/clock-out', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Clocked out successfully! Work hours: ' + data.work_hours.toFixed(2) + ' hours');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while clocking out');
            });
        }, function(error) {
            alert('Location access is required for attendance. Please enable location services.');
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

function updateTaskProgress(taskId) {
    // Simple progress update - could be enhanced with a modal
    const progress = prompt('Enter progress percentage (0-100):');
    if (progress !== null && progress >= 0 && progress <= 100) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('progress', progress);
        
        fetch('/ergon/user/update-task-progress', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>