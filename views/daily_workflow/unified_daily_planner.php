<?php
$content = ob_start();
?>
<link rel="stylesheet" href="/ergon/assets/css/daily-planner.css">

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-day"></i> Daily Planner</h1>
        <p>Advanced Task Execution Workflow - <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="dateSelector" value="<?= $selected_date ?>" onchange="changeDate(this.value)" class="form-control">
        <a href="/ergon/tasks/create" class="btn btn--secondary">
            <i class="bi bi-plus"></i> Add Task
        </a>
    </div>
</div>

<div class="planner-grid">
    <!-- Task Execution Section -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="bi bi-play-circle"></i> Task Execution</h3>
            <span class="badge badge--info"><?= count($planned_tasks) ?> tasks</span>
        </div>
        <div class="card__body">
            <?php if (empty($planned_tasks)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h4>No tasks planned for today</h4>
                    <p>Start by adding tasks to your daily planner</p>
                    <a href="/ergon/tasks/create" class="btn btn--primary">
                        <i class="bi bi-plus"></i> Plan First Task
                    </a>
                </div>
            <?php else: ?>
                <div class="task-timeline" id="taskTimeline">
                    <?php 
                    usort($planned_tasks, function($a, $b) {
                        $statusOrder = ['in_progress' => 1, 'on_break' => 2, 'assigned' => 3, 'not_started' => 3, 'completed' => 4];
                        return ($statusOrder[$a['status']] ?? 3) - ($statusOrder[$b['status']] ?? 3);
                    });
                    
                    foreach ($planned_tasks as $task): 
                        $status = $task['status'] ?? 'not_started';
                        $taskId = $task['id'];
                        $slaHours = $task['sla_hours'] ?? 1;
                        $slaDuration = $slaHours * 3600;
                        $startTime = $task['start_time'] ?? null;
                        $startTimestamp = $startTime ? strtotime($startTime) : 0;
                        
                        $remainingTime = $slaDuration;
                        if ($startTimestamp > 0 && ($status === 'in_progress' || $status === 'on_break')) {
                            $elapsed = time() - $startTimestamp;
                            $remainingTime = max(0, $slaDuration - $elapsed);
                        }
                        
                        $timeDisplay = sprintf('%02d:%02d:%02d', 
                            floor($remainingTime / 3600), 
                            floor(($remainingTime % 3600) / 60), 
                            $remainingTime % 60
                        );
                        
                        $cssClass = '';
                        if ($status === 'in_progress') $cssClass = 'task-item--active';
                        elseif ($status === 'on_break') $cssClass = 'task-item--break';
                        elseif ($status === 'completed') $cssClass = 'task-item--completed';
                    ?>
                        <div class="task-item <?= $cssClass ?>" 
                             data-task-id="<?= $taskId ?>" 
                             data-sla-duration="<?= $slaDuration ?>" 
                             data-start-time="<?= $startTimestamp ?>" 
                             data-status="<?= $status ?>">
                            <div class="task-time">
                                <div class="sla-time"><?= $slaHours ?>h</div>
                                <div class="sla-label">SLA</div>
                                <?php if ($status === 'in_progress' || $status === 'on_break'): ?>
                                    <div class="countdown-timer" id="countdown-<?= $taskId ?>">
                                        <div class="countdown-display"><?= $timeDisplay ?></div>
                                        <div class="countdown-label">Left</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="task-content">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                                    <div class="task-badges">
                                        <span class="badge badge--<?= $task['priority'] ?? 'medium' ?>"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
                                        <span class="badge badge--<?= $status ?>" id="status-<?= $taskId ?>">
                                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="task-description"><?= htmlspecialchars($task['description'] ?? 'No description') ?></p>
                                
                                <?php if ($status === 'in_progress'): ?>
                                    <div class="task-timer task-timer--active">
                                        <i class="bi bi-stopwatch"></i> Active
                                    </div>
                                <?php elseif ($status === 'on_break'): ?>
                                    <div class="task-timer task-timer--paused">
                                        <i class="bi bi-pause-circle"></i> On Break
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-actions" id="actions-<?= $taskId ?>">
                                    <?php if ($status === 'not_started' || $status === 'assigned'): ?>
                                        <button class="btn btn--sm btn--success" onclick="startTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Start
                                        </button>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <button class="btn btn--sm btn--warning" onclick="pauseTask(<?= $taskId ?>)">
                                            <i class="bi bi-pause"></i> Break
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="completeTask(<?= $taskId ?>)">
                                            <i class="bi bi-check"></i> Complete
                                        </button>
                                    <?php elseif ($status === 'on_break'): ?>
                                        <button class="btn btn--sm btn--success" onclick="resumeTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Resume
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="completeTask(<?= $taskId ?>)">
                                            <i class="bi bi-check"></i> Complete
                                        </button>
                                    <?php elseif ($status === 'completed'): ?>
                                        <span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>
                                    <?php endif; ?>
                                    <a href="/ergon/tasks/view/<?= $taskId ?>" class="btn btn--sm btn--secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced SLA Dashboard -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="bi bi-speedometer2"></i> SLA Dashboard</h3>
        </div>
        <div class="card__body">
            <?php
            $stats = $daily_stats ?? [];
            $totalTasks = $stats['total_tasks'] ?? count($planned_tasks);
            $completedTasks = $stats['completed_tasks'] ?? 0;
            $inProgressTasks = $stats['in_progress_tasks'] ?? 0;
            $postponedTasks = $stats['postponed_tasks'] ?? 0;
            $totalPlannedMinutes = $stats['total_planned_minutes'] ?? 0;
            $totalActiveSeconds = $stats['total_active_seconds'] ?? 0;
            $totalActiveMinutes = round($totalActiveSeconds / 60, 1);
            $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
            $slaAdherence = $totalPlannedMinutes > 0 ? ($totalActiveMinutes / $totalPlannedMinutes) * 100 : 0;
            ?>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value text-success"><?= $completedTasks ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-primary"><?= $inProgressTasks ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-warning"><?= $postponedTasks ?></div>
                    <div class="stat-label">Postponed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $totalTasks ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>
            
            <div class="sla-metrics">
                <div class="metric-row">
                    <span class="metric-label">Completion Rate:</span>
                    <span class="metric-value"><?= round($completionRate, 1) ?>%</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Planned Time:</span>
                    <span class="metric-value"><?= $totalPlannedMinutes ?> min</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Active Time:</span>
                    <span class="metric-value"><?= $totalActiveMinutes ?> min</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">SLA Adherence:</span>
                    <span class="metric-value <?= $slaAdherence > 100 ? 'text-warning' : 'text-success' ?>">
                        <?= round($slaAdherence, 1) ?>%
                    </span>
                </div>
            </div>

            <div class="progress-bars">
                <div class="progress-item">
                    <label>Task Completion</label>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $completionRate ?>%"></div>
                    </div>
                </div>
                <div class="progress-item">
                    <label>Time Utilization</label>
                    <div class="progress-bar">
                        <div class="progress-fill <?= $slaAdherence > 100 ? 'progress-over' : '' ?>" 
                             style="width: <?= min($slaAdherence, 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Task Modal -->
<div id="quickTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Quick Add Task</h3>
            <button class="modal-close" onclick="closeQuickTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quickTaskForm">
                <div class="form-group">
                    <label for="quickTitle">Task Title</label>
                    <input type="text" id="quickTitle" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="quickDescription">Description</label>
                    <textarea id="quickDescription" name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="quickTime">Start Time</label>
                        <input type="time" id="quickTime" name="planned_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="quickDuration">Duration (min)</label>
                        <input type="number" id="quickDuration" name="duration" class="form-control" min="15" step="15" value="60">
                    </div>
                </div>
                <div class="form-group">
                    <label for="quickPriority">Priority</label>
                    <select id="quickPriority" name="priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Add Task</button>
                    <button type="button" class="btn btn--secondary" onclick="closeQuickTaskModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Task Modal -->
<div id="completeTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Complete Task</h3>
            <button class="modal-close" onclick="closeCompleteTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="completeTaskForm">
                <input type="hidden" id="completeTaskId" name="task_id">
                <div class="form-group">
                    <label>Completion Percentage</label>
                    <div class="percentage-options">
                        <button type="button" class="percentage-btn" data-percentage="50">50%</button>
                        <button type="button" class="percentage-btn" data-percentage="60">60%</button>
                        <button type="button" class="percentage-btn" data-percentage="70">70%</button>
                        <button type="button" class="percentage-btn" data-percentage="80">80%</button>
                        <button type="button" class="percentage-btn" data-percentage="90">90%</button>
                        <button type="button" class="percentage-btn active" data-percentage="100">100%</button>
                    </div>
                    <input type="hidden" id="selectedPercentage" name="percentage" value="100">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Complete Task</button>
                    <button type="button" class="btn btn--secondary" onclick="closeCompleteTaskModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Postpone Task Modal -->
<div id="postponeTaskModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Postpone Task</h3>
            <button class="modal-close" onclick="closePostponeTaskModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="postponeTaskForm">
                <input type="hidden" id="postponeTaskId" name="task_id">
                <div class="form-group">
                    <label for="newDate">Reschedule to Date</label>
                    <input type="date" id="newDate" name="new_date" class="form-control" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--warning">Postpone Task</button>
                    <button type="button" class="btn btn--secondary" onclick="closePostponeTaskModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let timers = {};

function changeDate(date) {
    window.location.href = `/ergon/workflow/daily-planner/${date}`;
}

function startTask(taskId) {
    updateStatus(taskId, 'in_progress', '/ergon/workflow/start-task');
}

function pauseTask(taskId) {
    updateStatus(taskId, 'on_break', '/ergon/workflow/pause-task');
}

function resumeTask(taskId) {
    updateStatus(taskId, 'in_progress', '/ergon/workflow/resume-task');
}

function updateStatus(taskId, status, endpoint) {
    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Network error'));
}

function completeTask(taskId) {
    document.getElementById('completeTaskId').value = taskId;
    document.getElementById('completeTaskModal').style.display = 'flex';
}

function postponeTask(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    document.getElementById('postponeTaskModal').style.display = 'flex';
}

function startCountdown(taskId) {
    const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskItem) return;
    
    const slaDuration = parseInt(taskItem.dataset.slaDuration);
    const startTime = parseInt(taskItem.dataset.startTime);
    
    if (!startTime || startTime === 0) return;
    
    if (timers[taskId]) clearInterval(timers[taskId]);
    
    timers[taskId] = setInterval(() => {
        updateCountdown(taskId, slaDuration, startTime);
    }, 1000);
    
    updateCountdown(taskId, slaDuration, startTime);
}

function updateCountdown(taskId, slaDuration, startTime) {
    const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
    if (!display) return;
    
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - startTime;
    const remaining = Math.max(0, slaDuration - elapsed);
    
    const hours = Math.floor(remaining / 3600);
    const minutes = Math.floor((remaining % 3600) / 60);
    const seconds = remaining % 60;
    
    display.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (remaining <= 600) display.classList.add('countdown-display--warning');
    if (remaining <= 0) display.classList.add('countdown-display--expired');
}



// Modal Functions
function openQuickTaskModal() {
    document.getElementById('quickTaskModal').style.display = 'flex';
}

function closeQuickTaskModal() {
    document.getElementById('quickTaskModal').style.display = 'none';
    document.getElementById('quickTaskForm').reset();
}

function closeCompleteTaskModal() {
    document.getElementById('completeTaskModal').style.display = 'none';
}

function closePostponeTaskModal() {
    document.getElementById('postponeTaskModal').style.display = 'none';
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.task-item').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        const startTime = parseInt(item.dataset.startTime);
        
        if ((status === 'in_progress' || status === 'on_break') && startTime > 0) {
            startCountdown(taskId);
        }
    });
    
    // Percentage selection
    document.querySelectorAll('.percentage-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.percentage-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('selectedPercentage').value = this.dataset.percentage;
        });
    });
    
    // Form submissions
    document.getElementById('quickTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('scheduled_date', '<?= $selected_date ?>');
        
        fetch('/ergon/workflow/quick-add-task', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to add task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding task');
        });
    });
    
    document.getElementById('completeTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskId = document.getElementById('completeTaskId').value;
        const percentage = document.getElementById('selectedPercentage').value;
        
        fetch('/ergon/workflow/complete-task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, percentage: parseInt(percentage) })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'completed');
                stopTimer(taskId);
                closeCompleteTaskModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Failed to complete task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error completing task');
        });
    });
    
    document.getElementById('postponeTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskId = document.getElementById('postponeTaskId').value;
        const newDate = document.getElementById('newDate').value;
        
        fetch('/ergon/workflow/postpone-task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, new_date: newDate })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'postponed');
                closePostponeTaskModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Failed to postpone task: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error postponing task');
        });
    });
});
</script>



<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>