<?php
/**
 * User Dashboard - Complete User Interface
 * ERGON - Employee Tracker & Task Manager
 */

$pageTitle = 'My Dashboard';
include __DIR__ . '/../layouts/dashboard.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?>!</h1>
            <p class="text-muted mb-0">Here's what's happening with your work today</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus"></i> Quick Actions
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/ergon/user/submit-leave"><i class="fas fa-calendar-alt"></i> Request Leave</a></li>
                <li><a class="dropdown-item" href="/ergon/user/submit-expense"><i class="fas fa-receipt"></i> Submit Expense</a></li>
                <li><a class="dropdown-item" href="/ergon/user/submit-advance"><i class="fas fa-money-bill"></i> Request Advance</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/ergon/user/my-tasks"><i class="fas fa-tasks"></i> View All Tasks</a></li>
            </ul>
        </div>
    </div>

    <!-- Attendance Status Alert -->
    <?php if ($attendance_status['status'] === 'not_clocked_in'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-clock"></i> You haven't clocked in today. 
        <button class="btn btn-sm btn-warning ms-2" onclick="clockIn()">Clock In Now</button>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($attendance_status['status'] === 'clocked_in'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> You clocked in at <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?>. 
        <button class="btn btn-sm btn-success ms-2" onclick="clockOut()">Clock Out</button>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($attendance_status['status'] === 'clocked_out'): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i> You completed your day from <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?> to <?= date('h:i A', strtotime($attendance_status['clock_out'])) ?>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['my_tasks']['total'] ?? 0 ?></div>
                            <div class="text-xs text-muted">
                                <?= $stats['my_tasks']['pending'] ?? 0 ?> pending, 
                                <?= $stats['my_tasks']['in_progress'] ?? 0 ?> in progress
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['attendance_this_month'] ?? 0 ?></div>
                            <div class="text-xs text-muted">Days this month</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending Requests</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pending_requests'] ?? 0 ?></div>
                            <div class="text-xs text-muted">Awaiting approval</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Leave Balance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['leave_balance'] ?? 0 ?></div>
                            <div class="text-xs text-muted">Days remaining</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-umbrella-beach fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Today's Tasks -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Tasks & Priorities</h6>
                    <a href="/ergon/user/my-tasks" class="btn btn-sm btn-primary">View All Tasks</a>
                </div>
                <div class="card-body">
                    <?php if (empty($today_tasks)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">No tasks for today. Great job!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
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
                                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                                            <?php if (!empty($task['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 50)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $task['priority'] === 'high' ? 'danger' : ($task['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d', strtotime($task['due_date'])) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" style="width: <?= $task['progress'] ?? 0 ?>%">
                                                    <?= $task['progress'] ?? 0 ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="updateTaskProgress(<?= $task['id'] ?>)">
                                                Update
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
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4 mb-4">
            <!-- Quick Clock In/Out -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attendance</h6>
                </div>
                <div class="card-body text-center">
                    <?php if ($attendance_status['can_clock_in']): ?>
                        <button class="btn btn-success btn-lg" onclick="clockIn()">
                            <i class="fas fa-play"></i> Clock In
                        </button>
                        <p class="text-muted mt-2">Start your workday</p>
                    <?php elseif ($attendance_status['can_clock_out']): ?>
                        <p class="text-success mb-2">
                            <i class="fas fa-check"></i> Clocked in at <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?>
                        </p>
                        <button class="btn btn-warning btn-lg" onclick="clockOut()">
                            <i class="fas fa-stop"></i> Clock Out
                        </button>
                    <?php else: ?>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p>Work completed for today!</p>
                            <small class="text-muted">
                                <?= date('h:i A', strtotime($attendance_status['clock_in'])) ?> - 
                                <?= date('h:i A', strtotime($attendance_status['clock_out'])) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center">No new notifications</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1 small"><?= htmlspecialchars($notification['message']) ?></p>
                                        <small class="text-muted"><?= date('M d, h:i A', strtotime($notification['created_at'])) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $notification['type'] === 'success' ? 'success' : 'info' ?>">
                                        <?= ucfirst($notification['type']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/ergon/user/my-tasks" class="list-group-item list-group-item-action">
                            <i class="fas fa-tasks text-primary"></i> My Tasks
                        </a>
                        <a href="/ergon/user/my-requests" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt text-success"></i> My Requests
                        </a>
                        <a href="/ergon/user/my-attendance" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-check text-info"></i> My Attendance
                        </a>
                        <a href="/ergon/profile" class="list-group-item list-group-item-action">
                            <i class="fas fa-user text-warning"></i> My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <?php if (!empty($recent_activities)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach (array_slice($recent_activities, 0, 5) as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title"><?= htmlspecialchars($activity['action']) ?></h6>
                                <p class="timeline-text"><?= htmlspecialchars($activity['description']) ?></p>
                                <small class="text-muted"><?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Task Progress Modal -->
<div class="modal fade" id="taskProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Task Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="taskProgressForm">
                    <input type="hidden" id="taskId" name="task_id">
                    
                    <div class="mb-3">
                        <label for="progress" class="form-label">Progress (%)</label>
                        <input type="range" class="form-range" id="progress" name="progress" min="0" max="100" value="0" oninput="updateProgressValue(this.value)">
                        <div class="d-flex justify-content-between">
                            <span>0%</span>
                            <span id="progressValue">0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Keep current status</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskComments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="taskComments" name="comments" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitTaskProgress()">Update Task</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #007bff;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -31px;
    top: 15px;
    width: 2px;
    height: calc(100% + 10px);
    background-color: #e3e6f0;
}

.timeline-item:last-child:before {
    display: none;
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
    document.getElementById('taskId').value = taskId;
    const modal = new bootstrap.Modal(document.getElementById('taskProgressModal'));
    modal.show();
}

function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
}

function submitTaskProgress() {
    const formData = new FormData(document.getElementById('taskProgressForm'));
    
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
</script>