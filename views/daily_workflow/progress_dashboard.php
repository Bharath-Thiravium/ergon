<div class="page-header">
    <div class="page-title">
        <h1><span>📊</span> Progress Dashboard</h1>
        <p>Real-time team productivity and task completion tracking</p>
    </div>
    <div class="page-actions">
        <input type="date" class="form-control" value="<?= $data['selectedDate'] ?>" onchange="window.location.href='?date='+this.value" style="width: auto;">
        <button class="btn btn--secondary" onclick="exportReport()">
            <span>📄</span> Export Report
        </button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['teamProgress'], function($t) { return $t['morning_submitted']; })) ?></div>
        <div class="kpi-card__label">Plans Submitted</div>
        <div class="kpi-card__status">Morning</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🌆</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($data['teamProgress'], function($t) { return $t['evening_updated']; })) ?></div>
        <div class="kpi-card__label">Updates Submitted</div>
        <div class="kpi-card__status">Evening</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">✅</div>
        </div>
        <div class="kpi-card__value"><?= array_sum(array_column($data['teamProgress'], 'total_completed_tasks')) ?></div>
        <div class="kpi-card__label">Tasks Completed</div>
        <div class="kpi-card__status">Today</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
        </div>
        <div class="kpi-card__value"><?= count($data['delayedTasks']) ?></div>
        <div class="kpi-card__label">Delayed Tasks</div>
        <div class="kpi-card__status">Action Needed</div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>👥</span> Team Progress Overview
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Morning Plan</th>
                        <th>Evening Update</th>
                        <th>Tasks</th>
                        <th>Hours</th>
                        <th>Productivity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['teamProgress'] as $progress): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><?= strtoupper(substr($progress['name'], 0, 1)) ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($progress['name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($progress['morning_submitted']): ?>
                                <span class="badge badge--success">
                                    ✅ <?= date('g:i A', strtotime($progress['morning_submitted_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge--danger">❌ Not Submitted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($progress['evening_updated']): ?>
                                <span class="badge badge--success">
                                    ✅ <?= date('g:i A', strtotime($progress['evening_updated_at'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge--warning">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span class="badge badge--secondary"><?= $progress['total_tasks'] ?> planned</span>
                                <span class="badge badge--success"><?= $progress['completed_tasks'] ?> done</span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;">
                                <div>Planned: <?= $progress['total_planned_hours'] ?>h</div>
                                <div>Actual: <?= $progress['total_actual_hours'] ?>h</div>
                            </div>
                        </td>
                        <td>
                            <?php if ($progress['productivity_score']): ?>
                                <div class="progress-circle-small">
                                    <div class="progress-value"><?= round($progress['productivity_score']) ?>%</div>
                                    <div class="progress-bar" style="--progress: <?= $progress['productivity_score'] ?>%"></div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No data</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn--sm btn--secondary" onclick="viewUserDetails(<?= $progress['id'] ?>)">
                                    👁️ View
                                </button>
                                <?php if (!$progress['morning_submitted']): ?>
                                    <button class="btn btn--sm btn--warning" onclick="remindUser(<?= $progress['id'] ?>)">
                                        🔔 Remind
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn--sm btn--danger" onclick="deleteUserWorkflow(<?= $progress['id'] ?>, '<?= htmlspecialchars($progress['name']) ?>')">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($data['delayedTasks'])): ?>
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>⚠️</span> Delayed & Blocked Tasks
        </h2>
    </div>
    <div class="card__body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Employee</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['delayedTasks'] as $task): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <?php if ($task['description']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($task['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($task['user_name']) ?></td>
                        <td>
                            <span class="badge badge--<?= $task['status'] === 'blocked' ? 'danger' : 'warning' ?>">
                                <?= $task['status'] === 'blocked' ? '🚫 Blocked' : '⏳ Pending' ?>
                            </span>
                        </td>
                        <td>
                            <div class="progress-bar-small">
                                <div class="progress-fill" style="width: <?= $task['progress'] ?>%"></div>
                                <span class="progress-text"><?= $task['progress'] ?>%</span>
                            </div>
                        </td>
                        <td><?= date('M j', strtotime($task['plan_date'])) ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn--sm btn--primary" onclick="followUpTask(<?= $task['id'] ?>)">
                                    📞 Follow Up
                                </button>
                                <button class="btn btn--sm btn--secondary" onclick="viewTaskDetails(<?= $task['id'] ?>)">
                                    👁️ Details
                                </button>
                                <button class="btn btn--sm btn--danger" onclick="deleteDelayedTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title']) ?>')">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.progress-circle-small {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: conic-gradient(var(--success) var(--progress, 0%), var(--border-color) 0%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-circle-small::before {
    content: '';
    position: absolute;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: var(--bg-primary);
}

.progress-value {
    position: relative;
    z-index: 1;
    font-size: 0.75rem;
    font-weight: bold;
    color: var(--text-primary);
}

.progress-bar-small {
    position: relative;
    width: 80px;
    height: 20px;
    background: var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: var(--success);
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: bold;
    color: var(--text-inverse);
}
</style>

<script>
function viewUserDetails(userId) {
    window.open(`/ergon/users/view/${userId}?date=<?= $data['selectedDate'] ?>`, '_blank');
}

function remindUser(userId) {
    if (confirm('Send reminder to submit morning plan?')) {
        fetch('/ergon/api/send-reminder', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: userId, type: 'morning_plan'})
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert('Failed to send reminder.');
            }
        });
    }
}

function followUpTask(taskId) {
    if (confirm('Send follow-up notification for this task?')) {
        fetch('/ergon/api/follow-up-task', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({task_id: taskId})
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Follow-up sent successfully!');
            } else {
                alert('Failed to send follow-up.');
            }
        });
    }
}

function viewTaskDetails(taskId) {
    window.open(`/ergon/tasks/view/${taskId}`, '_blank');
}

function exportReport() {
    window.open(`/ergon/reports/daily-progress?date=<?= $data['selectedDate'] ?>&format=pdf`, '_blank');
}

function deleteUserWorkflow(userId, userName) {
    if (confirm(`Are you sure you want to delete workflow data for "${userName}"? This will remove all their tasks for the selected date.`)) {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('date', '<?= $data['selectedDate'] ?>');
        
        fetch('/ergon/daily-workflow/delete-user-workflow', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete workflow: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete workflow');
        });
    }
}

function deleteDelayedTask(taskId, taskTitle) {
    if (confirm(`Are you sure you want to delete task "${taskTitle}"?`)) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        
        fetch('/ergon/daily-workflow/delete-task', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete task: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete task');
        });
    }
}

// Auto-refresh every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>