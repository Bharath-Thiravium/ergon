<?php
$content = ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-day"></i> Daily Planner</h1>
        <p>Advanced Task Execution Workflow - <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="dateSelector" value="<?= $selected_date ?>" onchange="changeDate(this.value)" class="form-control" style="width: auto; display: inline-block;">

        <a href="/ergon/tasks/create" class="btn btn--secondary">
            <i class="bi bi-plus"></i> Full Task
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
                <div class="task-timeline">
                    <?php foreach ($planned_tasks as $task): 
                        $status = $task['status'] ?? $task['completion_status'] ?? 'not_started';
                        $taskId = $task['id'];
                        $isActive = in_array($status, ['in_progress']);
                        $isPaused = $status === 'paused';
                        $isCompleted = $status === 'completed';
                        $isPostponed = $status === 'postponed';
                    ?>
                        <div class="task-item <?= $isActive ? 'task-active' : '' ?> <?= $isCompleted ? 'task-completed' : '' ?>" data-task-id="<?= $taskId ?>">
                            <div class="task-time">
                                <?= $task['planned_start_time'] ? date('H:i', strtotime($task['planned_start_time'])) : 'Flexible' ?>
                            </div>
                            <div class="task-content">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['title']) ?></h4>
                                    <div class="task-badges">
                                        <?php if ($task['priority']): ?>
                                            <span class="badge badge--<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge badge--<?= $status ?>" id="status-badge-<?= $taskId ?>">
                                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                        </span>
                                        <?php if ($isPostponed && !empty($task['postponed_from_date'])): ?>
                                            <span class="badge badge--warning">Postponed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($task['description']): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Timer Display -->
                                <?php if ($isActive || $isPaused || $isCompleted): ?>
                                    <div class="task-timer" id="timer-<?= $taskId ?>">
                                        <i class="bi bi-stopwatch"></i>
                                        <span class="timer-display" data-seconds="<?= $task['active_seconds'] ?? 0 ?>">
                                            <?= gmdate('H:i:s', $task['active_seconds'] ?? 0) ?>
                                        </span>
                                        <?php if ($isCompleted): ?>
                                            <span class="completion-percentage">(<?= $task['completed_percentage'] ?? 0 ?>%)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="task-actions" id="actions-<?= $taskId ?>">
                                    <?php if ($status === 'not_started'): ?>
                                        <button class="btn btn--sm btn--success" onclick="startTask('<?= $taskId ?>')">
                                            <i class="bi bi-play"></i> Start
                                        </button>
                                        <button class="btn btn--sm btn--warning" onclick="postponeTask('<?= $taskId ?>')">
                                            <i class="bi bi-calendar-x"></i> Postpone
                                        </button>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <button class="btn btn--sm btn--warning" onclick="pauseTask('<?= $taskId ?>')">
                                            <i class="bi bi-pause"></i> Break
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="completeTask('<?= $taskId ?>')">
                                            <i class="bi bi-check"></i> Complete
                                        </button>
                                    <?php elseif ($status === 'paused'): ?>
                                        <button class="btn btn--sm btn--success" onclick="resumeTask('<?= $taskId ?>')">
                                            <i class="bi bi-play"></i> Resume
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="completeTask('<?= $taskId ?>')">
                                            <i class="bi bi-check"></i> Complete
                                        </button>
                                    <?php elseif ($status === 'completed'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle"></i> Task Completed</span>
                                    <?php elseif ($status === 'postponed'): ?>
                                        <span class="text-warning"><i class="bi bi-calendar-x"></i> Task Postponed</span>
                                    <?php endif; ?>
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
let activeTimers = {};

function changeDate(newDate) {
    window.location.href = `/ergon/workflow/daily-planner/${newDate}`;
}

// Task Action Functions
function startTask(taskId) {
    fetch('/ergon/workflow/start-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'in_progress');
            startTimer(taskId);
        } else {
            alert('Failed to start task: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error starting task');
    });
}

function pauseTask(taskId) {
    fetch('/ergon/workflow/pause-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'paused');
            stopTimer(taskId);
        } else {
            alert('Failed to pause task: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error pausing task');
    });
}

function resumeTask(taskId) {
    fetch('/ergon/workflow/resume-task', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'in_progress');
            startTimer(taskId);
        } else {
            alert('Failed to resume task: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error resuming task');
    });
}

function completeTask(taskId) {
    document.getElementById('completeTaskId').value = taskId;
    document.getElementById('completeTaskModal').style.display = 'flex';
}

function postponeTask(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    document.getElementById('postponeTaskModal').style.display = 'flex';
}

// Timer Functions
function startTimer(taskId) {
    if (activeTimers[taskId]) {
        clearInterval(activeTimers[taskId]);
    }
    
    activeTimers[taskId] = setInterval(() => {
        updateTimerDisplay(taskId);
    }, 1000);
}

function stopTimer(taskId) {
    if (activeTimers[taskId]) {
        clearInterval(activeTimers[taskId]);
        delete activeTimers[taskId];
    }
}

function updateTimerDisplay(taskId) {
    const timerElement = document.querySelector(`#timer-${taskId} .timer-display`);
    if (timerElement) {
        let seconds = parseInt(timerElement.dataset.seconds) || 0;
        seconds++;
        timerElement.dataset.seconds = seconds;
        timerElement.textContent = formatTime(seconds);
    }
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

// UI Update Functions
function updateTaskUI(taskId, status) {
    const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskItem) {
        location.reload();
        return;
    }
    
    // Update status badge
    const statusBadge = document.getElementById(`status-badge-${taskId}`);
    if (statusBadge) {
        statusBadge.className = `badge badge--${status}`;
        statusBadge.textContent = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Update action buttons
    const actionsContainer = document.getElementById(`actions-${taskId}`);
    if (actionsContainer) {
        let buttonsHTML = '';
        
        switch (status) {
            case 'in_progress':
                buttonsHTML = `
                    <button class="btn btn--sm btn--warning" onclick="pauseTask('${taskId}')">
                        <i class="bi bi-pause"></i> Break
                    </button>
                    <button class="btn btn--sm btn--primary" onclick="completeTask('${taskId}')">
                        <i class="bi bi-check"></i> Complete
                    </button>
                `;
                break;
            case 'paused':
                buttonsHTML = `
                    <button class="btn btn--sm btn--success" onclick="resumeTask('${taskId}')">
                        <i class="bi bi-play"></i> Resume
                    </button>
                    <button class="btn btn--sm btn--primary" onclick="completeTask('${taskId}')">
                        <i class="bi bi-check"></i> Complete
                    </button>
                `;
                break;
            case 'completed':
                buttonsHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Task Completed</span>';
                break;
            case 'postponed':
                buttonsHTML = '<span class="text-warning"><i class="bi bi-calendar-x"></i> Task Postponed</span>';
                break;
        }
        
        actionsContainer.innerHTML = buttonsHTML;
    }
    
    // Add/remove visual classes
    taskItem.classList.toggle('task-active', status === 'in_progress');
    taskItem.classList.toggle('task-completed', status === 'completed');
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
    // Initialize timers for active tasks
    document.querySelectorAll('.task-item.task-active').forEach(item => {
        const taskId = item.dataset.taskId;
        startTimer(taskId);
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

<style>
.planner-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-top: 1rem;
}

.task-timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.task-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-primary);
    transition: all 0.2s ease;
    position: relative;
}

.task-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.task-item.task-active {
    border-left: 4px solid var(--success);
    background: linear-gradient(90deg, rgba(40, 167, 69, 0.05), var(--bg-primary));
}

.task-item.task-completed {
    border-left: 4px solid var(--primary);
    background: linear-gradient(90deg, rgba(0, 123, 255, 0.05), var(--bg-primary));
    opacity: 0.8;
}

.task-time {
    flex-shrink: 0;
    width: 60px;
    font-weight: 600;
    color: var(--primary);
    text-align: center;
    padding: 0.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.task-content {
    flex: 1;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.task-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.task-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.task-description {
    margin: 0.5rem 0;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.task-timer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.75rem 0;
    padding: 0.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    font-family: 'Courier New', monospace;
}

.timer-display {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--primary);
}

.completion-percentage {
    color: var(--success);
    font-weight: 600;
    margin-left: 0.5rem;
}

.task-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-value.text-success { color: var(--success); }
.stat-value.text-primary { color: var(--primary); }
.stat-value.text-warning { color: var(--warning); }

.stat-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.sla-metrics {
    margin: 1rem 0;
}

.metric-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.metric-row:last-child {
    border-bottom: none;
}

.metric-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.metric-value {
    font-weight: 600;
    color: var(--text-primary);
}

.metric-value.text-success { color: var(--success); }
.metric-value.text-warning { color: var(--warning); }

.progress-bars {
    margin-top: 1rem;
}

.progress-item {
    margin-bottom: 1rem;
}

.progress-item label {
    display: block;
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: var(--bg-secondary);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success), var(--primary));
    transition: width 0.3s ease;
}

.progress-fill.progress-over {
    background: linear-gradient(90deg, var(--warning), var(--danger));
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.modal-close:hover {
    background-color: var(--bg-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.percentage-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.percentage-btn {
    padding: 0.75rem;
    border: 2px solid var(--border-color);
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.percentage-btn:hover {
    border-color: var(--primary);
    background: var(--bg-primary);
}

.percentage-btn.active {
    border-color: var(--primary);
    background: var(--primary);
    color: white;
}

.badge--not_started { background-color: #6c757d; color: white; }
.badge--in_progress { background-color: #28a745; color: white; }
.badge--paused { background-color: #ffc107; color: #212529; }
.badge--completed { background-color: #007bff; color: white; }
.badge--postponed { background-color: #fd7e14; color: white; }

.text-success { color: var(--success) !important; }
.text-warning { color: var(--warning) !important; }
.text-primary { color: var(--primary) !important; }

@media (max-width: 768px) {
    .planner-grid {
        grid-template-columns: 1fr;
    }
    
    .task-item {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .task-time {
        width: auto;
        text-align: left;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .task-actions {
        flex-wrap: wrap;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .percentage-options {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>