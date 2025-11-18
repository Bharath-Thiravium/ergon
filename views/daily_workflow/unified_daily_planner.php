<?php
include __DIR__ . '/../shared/modal_component.php';
$content = ob_start();
?>
<link rel="stylesheet" href="/ergon/assets/css/daily-planner.css">

<?php renderModalCSS(); ?>

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
                        $statusOrder = ['in_progress' => 1, 'on_break' => 2, 'assigned' => 3, 'not_started' => 3, 'completed' => 4, 'cancelled' => 5, 'suspended' => 5];
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
                        <div class="task-card" 
                             data-task-id="<?= $taskId ?>" 
                             data-original-task-id="<?= $task['task_id'] ?? '' ?>" 
                             data-sla-duration="<?= $slaDuration ?>" 
                             data-start-time="<?= $startTimestamp ?>" 
                             data-status="<?= $status ?>">
                            <div class="task-card__sla">
                                <div class="sla-time"><?= $slaHours ?>h</div>
                                <div class="sla-label">SLA</div>
                                <?php if ($status === 'in_progress'): ?>
                                    <div class="countdown-timer" id="countdown-<?= $taskId ?>">
                                        <div class="countdown-display"><?= $timeDisplay ?></div>
                                        <div class="countdown-label">Left</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="task-card__content">
                                <div class="task-card__header">
                                    <h4 class="task-card__title"><?= htmlspecialchars($task['title']) ?></h4>
                                    <div class="task-card__badges">
                                        <span class="badge badge--<?= $task['priority'] ?? 'medium' ?>"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
                                        <span class="badge badge--<?= $status ?>" id="status-<?= $taskId ?>">
                                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="task-card__description"><?= htmlspecialchars($task['description'] ?? 'No description') ?></p>
                                
                                <?php 
                                $completedPercentage = $task['completed_percentage'] ?? 0;
                                if ($completedPercentage > 0 || $status === 'in_progress'): 
                                ?>
                                    <div class="task-card__progress">
                                        <div class="progress-info">
                                            <span class="progress-label">Progress</span>
                                            <span class="progress-value"><?= $completedPercentage ?>%</span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $completedPercentage ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-card__actions" id="actions-<?= $taskId ?>">
                                    <?php if ($status === 'not_started' || $status === 'assigned'): ?>
                                        <button class="btn btn--sm btn--success" onclick="startTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Start
                                        </button>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <button class="btn btn--sm btn--warning" onclick="pauseTask(<?= $taskId ?>)">
                                            <i class="bi bi-pause"></i> Break
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="updateProgressTask(<?= $taskId ?>)">
                                            <i class="bi bi-percent"></i> Update Progress
                                        </button>
                                    <?php elseif ($status === 'on_break'): ?>
                                        <button class="btn btn--sm btn--success" onclick="resumeTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Resume
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="updateProgressTask(<?= $taskId ?>)">
                                            <i class="bi bi-percent"></i> Update Progress
                                        </button>
                                    <?php elseif ($status === 'completed'): ?>
                                        <span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>
                                    <?php elseif ($status === 'cancelled'): ?>
                                        <span class="badge badge--danger"><i class="bi bi-x-circle"></i> Cancelled</span>
                                    <?php elseif ($status === 'suspended'): ?>
                                        <span class="badge badge--warning"><i class="bi bi-pause-circle"></i> Suspended</span>
                                    <?php endif; ?>
                                    <?php if (!in_array($status, ['completed', 'cancelled', 'suspended'])): ?>
                                        <button class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>)">
                                            <i class="bi bi-calendar-plus"></i> Postpone
                                        </button>
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

<?php
// Quick Task Modal Content
$quickTaskContent = '
<form id="quickTaskForm">
    <div class="form-group">
        <label for="quickTitle">Task Title</label>
        <input type="text" id="quickTitle" name="title" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="quickDescription">Description</label>
        <textarea id="quickDescription" name="description" class="form-control" rows="2"></textarea>
    </div>
    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
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
</form>';

$quickTaskFooter = createFormModalFooter('Cancel', 'Add Task', 'quickTaskModal');

renderModal('quickTaskModal', 'Quick Add Task', $quickTaskContent, $quickTaskFooter, ['icon' => '➕']);
?>

<?php
// Update Progress Modal Content
$updateProgressContent = '
<div id="postponeHistory" class="postpone-history" style="display: none;">
    <h4>Postpone History</h4>
    <div id="historyList" class="history-list"></div>
    <hr>
</div>
<form id="updateProgressForm">
    <input type="hidden" id="updateTaskId" name="task_id">
    <div class="form-group">
        <label>Completion Percentage</label>
        <div class="percentage-options" style="display: flex; gap: 0.5rem; margin: 0.5rem 0;">
            <button type="button" class="percentage-btn btn btn--secondary" data-percentage="25">25%</button>
            <button type="button" class="percentage-btn btn btn--secondary" data-percentage="50">50%</button>
            <button type="button" class="percentage-btn btn btn--secondary" data-percentage="75">75%</button>
            <button type="button" class="percentage-btn btn btn--primary active" data-percentage="100">100%</button>
        </div>
        <input type="hidden" id="selectedProgressPercentage" name="percentage" value="100">
    </div>
</form>';

$updateProgressFooter = createFormModalFooter('Cancel', 'Update Progress', 'updateProgressModal');

renderModal('updateProgressModal', 'Update Progress', $updateProgressContent, $updateProgressFooter, ['icon' => '📊']);
?>

<?php
// Postpone Task Modal Content
$postponeTaskContent = '
<form id="postponeTaskForm">
    <input type="hidden" id="postponeTaskId" name="task_id">
    <div class="form-group">
        <label for="newDate">Reschedule to Date</label>
        <input type="date" id="newDate" name="new_date" class="form-control" required min="' . date('Y-m-d', strtotime('+1 day')) . '">
    </div>
</form>';

$postponeTaskFooter = createFormModalFooter('Cancel', 'Postpone Task', 'postponeTaskModal', 'warning');

renderModal('postponeTaskModal', 'Postpone Task', $postponeTaskContent, $postponeTaskFooter, ['icon' => '📅']);
?>

<script>
let timers = {};

function changeDate(date) {
    window.location.href = `/ergon/workflow/daily-planner/${date}`;
}

function startTask(taskId) {
    updateTaskStatus(taskId, 'start');
}

function pauseTask(taskId) {
    updateTaskStatus(taskId, 'pause');
}

function resumeTask(taskId) {
    updateTaskStatus(taskId, 'resume');
}

function updateTaskStatus(taskId, action) {
    fetch('/ergon/workflow/update-task-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId, action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, action);
            if (action === 'start' || action === 'resume') {
                startSLACountdown(taskId);
            } else if (action === 'pause') {
                stopTimer(taskId);
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Network error'));
}

function updateProgressTask(taskId) {
    document.getElementById('updateTaskId').value = taskId;
    loadPostponeHistory(taskId);
    showModal('updateProgressModal');
}

function loadPostponeHistory(taskId) {
    fetch(`/ergon/workflow/task-history?task_id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        const historyDiv = document.getElementById('postponeHistory');
        const historyList = document.getElementById('historyList');
        
        if (data.success && data.history && data.history.length > 0) {
            historyList.innerHTML = data.history.map(item => 
                `<div class="history-item">
                    <span class="history-date">${item.date}</span>
                    <span class="history-action">${item.action}</span>
                    <span class="history-progress">${item.progress || 0}%</span>
                </div>`
            ).join('');
            historyDiv.style.display = 'block';
        } else {
            historyDiv.style.display = 'none';
        }
    })
    .catch(() => {
        document.getElementById('postponeHistory').style.display = 'none';
    });
}

function completeTask(taskId) {
    // Legacy function - redirect to updateProgressTask
    updateProgressTask(taskId);
}

function postponeTask(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    showModal('postponeTaskModal');
}

function startSLACountdown(taskId) {
    const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskItem) return;
    
    const slaDuration = parseInt(taskItem.dataset.slaDuration);
    
    if (timers[taskId]) clearInterval(timers[taskId]);
    
    // Set start time to now
    const startTime = Math.floor(Date.now() / 1000);
    taskItem.dataset.startTime = startTime;
    
    timers[taskId] = setInterval(() => {
        updateSLACountdown(taskId, slaDuration, startTime);
    }, 1000);
    
    updateSLACountdown(taskId, slaDuration, startTime);
}

function updateSLACountdown(taskId, slaDuration, startTime) {
    const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
    if (!display) return;
    
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - startTime;
    const remaining = Math.max(0, slaDuration - elapsed);
    
    const hours = Math.floor(remaining / 3600);
    const minutes = Math.floor((remaining % 3600) / 60);
    const seconds = remaining % 60;
    
    display.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    // Visual warnings
    display.classList.remove('countdown-display--warning', 'countdown-display--expired');
    if (remaining <= 600 && remaining > 0) display.classList.add('countdown-display--warning');
    if (remaining <= 0) display.classList.add('countdown-display--expired');
}

function stopTimer(taskId) {
    if (timers[taskId]) {
        clearInterval(timers[taskId]);
        delete timers[taskId];
    }
}

function updateTaskUI(taskId, action, data = {}) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const statusBadge = document.querySelector(`#status-${taskId}`);
    const actionsDiv = document.querySelector(`#actions-${taskId}`);
    
    if (!taskCard || !statusBadge || !actionsDiv) return;
    
    let newStatus, newActions;
    
    switch(action) {
        case 'start':
        case 'resume':
            newStatus = 'in_progress';
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--in_progress';
            taskCard.className = 'task-card task-card--active';
            newActions = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="updateProgressTask(${taskId})">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            // Reset timer for resume
            if (action === 'resume') {
                taskCard.dataset.startTime = Math.floor(Date.now() / 1000);
                startSLACountdown(taskId);
            }
            break;
        case 'pause':
            newStatus = 'on_break';
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = 'task-card task-card--break';
            newActions = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-restart"></i> Resume (Restart)
                </button>
                <button class="btn btn--sm btn--primary" onclick="updateProgressTask(${taskId})">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            break;
        case 'completed':
            const percentage = data.percentage || 100;
            if (percentage < 100) {
                newStatus = 'in_progress';
                statusBadge.textContent = 'Deferred';
                statusBadge.className = 'badge badge--warning';
                taskCard.className = 'task-card task-card--deferred';
                newActions = `<span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Deferred to Next Day</span>`;
                updateProgressBar(taskId, percentage);
            } else {
                newStatus = 'completed';
                statusBadge.textContent = 'Completed';
                statusBadge.className = 'badge badge--success';
                taskCard.className = 'task-card task-card--completed';
                newActions = `<span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>`;
                updateProgressBar(taskId, 100);
            }
            break;
    }
    
    taskCard.dataset.status = newStatus;
    const postponeBtn = newStatus !== 'completed' ? 
        `<button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
            <i class="bi bi-calendar-plus"></i> Postpone
        </button>` : '';
    actionsDiv.innerHTML = newActions + postponeBtn;
}

function updateProgressBar(taskId, percentage) {
    const progressBar = document.querySelector(`[data-task-id="${taskId}"] .progress-fill`);
    const progressValue = document.querySelector(`[data-task-id="${taskId}"] .progress-value`);
    
    if (progressBar) progressBar.style.width = percentage + '%';
    if (progressValue) progressValue.textContent = percentage + '%';
}



// Modal Functions
function openQuickTaskModal() {
    showModal('quickTaskModal');
}

function closeQuickTaskModal() {
    closeModal('quickTaskModal');
    document.getElementById('quickTaskForm').reset();
}

function closeUpdateProgressModal() {
    closeModal('updateProgressModal');
}

function closeCompleteTaskModal() {
    // Legacy function
    closeUpdateProgressModal();
}

function closePostponeTaskModal() {
    closeModal('postponeTaskModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.task-item').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        const startTime = parseInt(item.dataset.startTime);
        
        if (status === 'in_progress' && startTime > 0) {
            startSLACountdown(taskId);
        }
    });
    
    // Percentage selection
    document.querySelectorAll('.percentage-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.percentage-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('selectedProgressPercentage').value = this.dataset.percentage;
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
    
    document.getElementById('updateProgressForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskId = document.getElementById('updateTaskId').value;
        const percentage = document.getElementById('selectedProgressPercentage').value;
        
        fetch('/ergon/workflow/update-task-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, action: 'complete', percentage: parseInt(percentage) })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'completed', { percentage: data.percentage });
                if (data.percentage < 100) {
                    // Keep timer running for partial progress
                } else {
                    stopTimer(taskId);
                }
                closeUpdateProgressModal();
                if (data.percentage < 100) {
                    setTimeout(() => {
                        alert('Progress updated to ' + data.percentage + '% - Task deferred to next working day');
                    }, 500);
                } else {
                    setTimeout(() => {
                        alert('Task completed successfully!');
                    }, 500);
                }
            } else {
                alert('Failed to update progress: ' + data.message);
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

<?php renderModalJS(); ?>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>