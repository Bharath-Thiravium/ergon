<?php
include __DIR__ . '/../shared/modal_component.php';
$content = ob_start();
?>
<link rel="stylesheet" href="/ergon/assets/css/daily-planner.css">
<link rel="stylesheet" href="/ergon/assets/css/daily-planner-modern.css">
<link rel="stylesheet" href="/ergon/assets/css/planner-access-control.css">
<link rel="stylesheet" href="/ergon/assets/css/production-fixes.css">

<?php renderModalCSS(); ?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-day"></i> Daily Planner</h1>
        <p>Advanced Task Execution Workflow - <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="page-actions">
        <input type="date" id="dateSelector" value="<?= $selected_date ?>" onchange="changeDate(this.value)" class="form-control">
        <a href="/ergon/workflow/daily-planner/<?= $selected_date ?>?refresh=1" class="btn btn--info" title="Refresh tasks from Tasks module">
            <i class="bi bi-arrow-clockwise"></i> Sync Tasks
        </a>
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
                    <p>No tasks found for today. Tasks can be:</p>
                    <ul style="text-align: left; display: inline-block; margin: 10px 0;">
                        <li><strong>Assigned by others</strong> - Tasks given to you</li>
                        <li><strong>Self-assigned</strong> - Tasks you create for yourself</li>
                    </ul>
                    <div style="margin-top: 15px;">
                        <a href="/ergon/tasks/create" class="btn btn--primary" style="margin-right: 10px;">
                            <i class="bi bi-plus"></i> Create Task
                        </a>
                        <a href="/ergon/debug_daily_planner.php" class="btn btn--secondary">
                            <i class="bi bi-bug"></i> Debug Info
                        </a>
                    </div>
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
                        <?php 
                        $taskSource = 'unknown';
                        if (strpos($task['title'], '[From Others]') === 0) {
                            $taskSource = 'from_others';
                        } elseif (strpos($task['title'], '[Self]') === 0) {
                            $taskSource = 'self_assigned';
                        }
                        ?>
                        <div class="task-card <?= $cssClass ?>" 
                             data-task-id="<?= $taskId ?>" 
                             data-original-task-id="<?= $task['task_id'] ?? '' ?>" 
                             data-sla-duration="<?= $slaDuration ?>" 
                             data-start-time="<?= $startTimestamp ?>" 
                             data-status="<?= $status ?>"
                             data-task-source="<?= $taskSource ?>"
                             data-pause-time="<?= $task['pause_time'] ?? '' ?>"
                             data-active-seconds="<?= $task['active_seconds'] ?? 0 ?>"
                             data-pause-duration="<?= $task['pause_duration'] ?? 0 ?>">
                            
                            <div class="task-card__content">
                                <div class="task-card__header">
                                    <h4 class="task-card__title">
                                    <?php 
                                    $title = htmlspecialchars($task['title']);
                                    // Add visual indicators for task source
                                    if (strpos($title, '[From Others]') === 0) {
                                        echo '<span class="task-source task-source--others">👥</span> ' . substr($title, 13);
                                    } elseif (strpos($title, '[Self]') === 0) {
                                        echo '<span class="task-source task-source--self">👤</span> ' . substr($title, 6);
                                    } else {
                                        echo $title;
                                    }
                                    ?>
                                </h4>
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
                                
                                <?php
                                $activeSeconds = $task['active_seconds'] ?? 0;
                                $pauseSeconds = $task['pause_duration'] ?? 0;
                                $remainingSeconds = max(0, $slaDuration - $activeSeconds);
                                ?>
                                <div class="task-card__timing" id="timing-<?= $taskId ?>">
                                    <div class="countdown-timer" id="countdown-<?= $taskId ?>">
                                        <div class="countdown-display"><?= $timeDisplay ?></div>
                                        <div class="countdown-label"><?= $status === 'in_progress' ? 'Remaining' : ($status === 'on_break' ? 'Paused' : 'SLA Time') ?></div>
                                    </div>
                                    <div class="timing-info">
                                        <span class="timing-label">Time Used:</span>
                                        <span class="timing-value time-used"><?= floor($activeSeconds/3600) ?>h <?= floor(($activeSeconds%3600)/60) ?>m</span>
                                    </div>
                                    <div class="timing-info">
                                        <span class="timing-label">Pause Duration:</span>
                                        <span class="timing-value time-paused"><?= floor($pauseSeconds/3600) ?>h <?= floor(($pauseSeconds%3600)/60) ?>m</span>
                                    </div>
                                </div>
                                
                                <div class="task-card__actions" id="actions-<?= $taskId ?>">
                                    <?php if ($status === 'not_started' || $status === 'assigned'): ?>
                                        <button class="btn btn--sm btn--success" onclick="startTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Start
                                        </button>
                                    <?php elseif ($status === 'in_progress'): ?>
                                        <button class="btn btn--sm btn--warning" onclick="pauseTask(<?= $taskId ?>)">
                                            <i class="bi bi-pause"></i> Break
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= $task['completed_percentage'] ?? 0 ?>, '<?= $status ?>')">
                                            <i class="bi bi-percent"></i> Update Progress
                                        </button>
                                    <?php elseif ($status === 'on_break'): ?>
                                        <button class="btn btn--sm btn--success" onclick="resumeTask(<?= $taskId ?>)">
                                            <i class="bi bi-play"></i> Resume
                                        </button>
                                        <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= $task['completed_percentage'] ?? 0 ?>, '<?= $status ?>')">
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
            <button class="btn btn--sm btn--secondary" onclick="forceSLARefresh()" title="Refresh SLA Data">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
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
                    <span class="metric-label">SLA Total Time:</span>
                    <span class="metric-value sla-total-time">Loading...</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Time Used:</span>
                    <span class="metric-value sla-used-time">Loading...</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Remaining Time:</span>
                    <span class="metric-value sla-remaining-time">Loading...</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Pause Duration:</span>
                    <span class="metric-value sla-pause-time">Loading...</span>
                </div>
                <div class="metric-row" style="display:none;">
                    <span class="metric-label">Late Time:</span>
                    <span class="metric-value sla-late-time text-danger">--</span>
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

renderModal('updateProgressModal', 'Update Progress', $updateProgressContent, $updateProgressFooter, ['icon' => '📊', 'zIndex' => 999]);
?>

<!-- Inline Postpone Form -->
<div id="postponeForm" style="display: none; position: fixed; top: 60%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 9999; min-width: 300px;">
    <h4>📅 Postpone Task</h4>
    <input type="hidden" id="postponeTaskId">
    <div style="margin: 15px 0;">
        <label>New Date:</label>
        <input type="date" id="newDate" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
    </div>
    <div style="margin: 15px 0;">
        <label>Reason:</label>
        <textarea id="postponeReason" placeholder="Why are you postponing this task?" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px; height: 60px;"></textarea>
    </div>
    <div style="text-align: right; margin-top: 20px;">
        <button onclick="cancelPostpone()" style="padding: 8px 16px; margin-right: 10px; background: #f3f4f6; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">Cancel</button>
        <button onclick="submitPostpone()" style="padding: 8px 16px; background: #f59e0b; color: white; border: none; border-radius: 4px; cursor: pointer;">Postpone</button>
    </div>
</div>
<div id="postponeOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9998;" onclick="cancelPostpone()"></div>

<div id="progressDialog" class="dialog" style="display: none;">
    <div class="dialog-content">
        <h4>Update Progress</h4>
        <p>Progress: <span id="progressValue">0</span>%</p>
        <input type="range" id="progressSlider" min="0" max="100" value="0">
        <div class="dialog-buttons">
            <button onclick="closeDialog()">Cancel</button>
            <button onclick="saveProgress()">Save</button>
        </div>
    </div>
</div>

<style>
.task-source {
    display: inline-block;
    font-size: 0.9em;
    margin-right: 5px;
}

.task-source--others {
    color: #e67e22;
    font-weight: bold;
}

.task-source--self {
    color: #3498db;
    font-weight: bold;
}

.empty-state ul {
    color: #666;
    font-size: 0.9em;
}

.empty-state ul li {
    margin: 5px 0;
}

.dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
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

.dialog-content p {
    margin: 0 0 1rem 0;
    font-weight: 500;
}

#progressSlider {
    width: 100%;
    margin: 1rem 0;
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

/* Task card enhancements */
.task-card {
    position: relative;
}

.task-card[data-task-source="from_others"]:before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    /*background: #e67e22;*/
    border-radius: 2px 0 0 2px;
}

.task-card[data-task-source="self_assigned"]:before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #3498db;
    border-radius: 2px 0 0 2px;
}

/* SLA Display Enhancements */
.sla-info {
    margin-top: 0.5rem;
    text-align: center;
}

.sla-total {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2563eb;
    font-family: 'Courier New', monospace;
}

.sla-total-label {
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sla-time {
    cursor: help;
    transition: all 0.2s ease;
}

.sla-time:hover {
    transform: scale(1.1);
    color: #2563eb;
}

/* Countdown timer enhancements */
.countdown-display--warning {
    color: #f59e0b !important;
    animation: pulse-warning 2s infinite;
}

.countdown-display--expired {
    color: #dc2626 !important;
    animation: pulse-danger 1s infinite;
}

@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes pulse-danger {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Task card SLA section improvements */
.task-card__sla {
    min-width: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: rgba(59, 130, 246, 0.05);
    border-radius: 10px;
    border: 1px solid rgba(59, 130, 246, 0.1);
}

.task-card--active .task-card__sla {
    background: rgba(34, 197, 94, 0.1);
    border-color: rgba(34, 197, 94, 0.2);
}

.task-card--break .task-card__sla {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.2);
}

.task-card--completed .task-card__sla {
    background: rgba(107, 114, 128, 0.1);
    border-color: rgba(107, 114, 128, 0.2);
    opacity: 0.7;
}

/* SLA tooltip enhancement */
.sla-time[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
}

.task-card__sla {
    position: relative;
}

/* History display styles */
.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    margin: 0.25rem 0;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 0.875rem;
}

.history-date {
    color: #6b7280;
    font-weight: 500;
}

.history-action {
    color: #374151;
    font-weight: 600;
}

.history-progress {
    color: #059669;
    font-weight: 700;
}

.history-notes {
    color: #6b7280;
    font-style: italic;
    font-size: 0.8rem;
    margin-top: 0.25rem;
    display: block;
    width: 100%;
}

.postpone-history {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.postpone-history h4 {
    margin: 0 0 0.75rem 0;
    color: #374151;
    font-size: 1rem;
}

.history-list {
    max-height: 200px;
    overflow-y: auto;
}

/* Enhanced progress update modal */
.percentage-options {
    display: flex;
    gap: 0.5rem;
    margin: 0.5rem 0;
    flex-wrap: wrap;
}

.percentage-btn {
    flex: 1;
    min-width: 60px;
    padding: 0.5rem;
    border: 2px solid #e5e7eb;
    background: #f9fafb;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 600;
}

.percentage-btn:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #1d4ed8;
}

.percentage-btn.active {
    border-color: #3b82f6;
    background: #3b82f6;
    color: white;
}

.percentage-btn.active:hover {
    background: #2563eb;
    border-color: #2563eb;
}

/* Task timing display */
.task-card__timing {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.5rem;
    margin: 0.5rem 0;
    padding: 0.4rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    font-size: 0.75rem;
}

.timing-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.timing-label {
    font-size: 0.65rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.1rem;
}

.timing-value {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1e293b;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}

.task-card--active .timing-value {
    color: #059669;
}

.task-card--break .timing-value {
    color: #d97706;
}

.task-card--completed .timing-value {
    color: #6b7280;
    opacity: 0.7;
}



.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px 16px;
    border-bottom: 1px solid var(--border-color, #f3f4f6);
    background: var(--bg-secondary, #f8fafc);
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary, #1f2937);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted, #6b7280);
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    color: var(--text-primary, #1f2937);
    background: var(--bg-hover, #f3f4f6);
}

.modal-body {
    padding: 24px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color, #e5e7eb);
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border-color, #d1d5db);
    border-radius: 6px;
    font-size: 14px;
    background: var(--bg-primary, #ffffff);
    color: var(--text-primary, #1f2937);
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
};
</style>

<script>
let timers = {};
let slaTimers = {};
var currentTaskId;

// SLA Timer Functions
function startSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
    }
    
    slaTimers[taskId] = setInterval(() => {
        updateSLADisplay(taskId);
    }, 1000);
    
    updateSLADisplay(taskId);
}

function stopSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

function updateSLADisplay(taskId) {
    fetch(`/ergon/api/daily_planner_workflow.php?action=timer&task_id=${taskId}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
            if (display) {
                const remaining = data.remaining_seconds;
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                
                display.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Visual warnings and overdue handling
                display.classList.remove('countdown-display--warning', 'countdown-display--expired');
                if (data.is_late) {
                    display.classList.add('countdown-display--expired');
                    display.textContent = 'OVERDUE: ' + formatTime(data.late_seconds);
                } else if (remaining <= 600) {
                    display.classList.add('countdown-display--warning');
                }
            }
            
            // Update individual task timing display
            updateTaskTiming(taskId, data);
            
            // Update countdown label based on status
            const label = document.querySelector(`#countdown-${taskId} .countdown-label`);
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            
            if (label && taskCard) {
                const status = taskCard.dataset.status;
                if (status === 'in_progress') {
                    label.textContent = 'Remaining';
                } else if (status === 'on_break') {
                    label.textContent = 'Paused';
                } else {
                    label.textContent = 'SLA Time';
                }
            }
        }
    })
    .catch(error => {
        console.log(`Timer unavailable for task ${taskId}:`, error.message);
    });
}

function updateTaskTiming(taskId, data) {
    const timingDiv = document.querySelector(`#timing-${taskId}`);
    if (timingDiv) {
        const timeUsed = timingDiv.querySelector('.time-used');
        const timeRemaining = timingDiv.querySelector('.time-remaining');
        const timePaused = timingDiv.querySelector('.time-paused');
        
        if (timeUsed) timeUsed.textContent = formatTimeHours(data.active_seconds);
        if (timeRemaining) timeRemaining.textContent = formatTimeHours(data.remaining_seconds);
        if (timePaused) {
            // Calculate current pause duration for on_break tasks
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard && taskCard.dataset.status === 'on_break') {
                const pauseStart = taskCard.dataset.pauseStart;
                if (pauseStart) {
                    const currentPause = Math.floor((Date.now() - parseInt(pauseStart)) / 1000);
                    const totalPause = (data.pause_duration || 0) + currentPause;
                    timePaused.textContent = formatTimeHours(totalPause);
                } else {
                    timePaused.textContent = formatTimeHours(data.pause_duration || 0);
                }
            } else {
                timePaused.textContent = formatTimeHours(data.pause_duration || 0);
            }
        }
    }
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function updateSLADashboard(data) {
    debugSLA('Updating SLA Dashboard', data);
    
    // Update SLA metrics in dashboard
    const slaTotal = document.querySelector('.sla-total-time');
    const slaUsed = document.querySelector('.sla-used-time');
    const slaRemaining = document.querySelector('.sla-remaining-time');
    const slaPause = document.querySelector('.sla-pause-time');
    
    const newValues = {
        total: formatTimeHours(data.sla_total_seconds || 0),
        used: formatTimeHours(data.active_seconds || 0),
        remaining: formatTimeHours(data.remaining_seconds || 0),
        pause: formatTimeHours(data.pause_seconds || 0)
    };
    
    if (slaTotal) {
        debugSLA(`Total time: ${slaTotal.textContent} → ${newValues.total}`);
        slaTotal.textContent = newValues.total;
    }
    if (slaUsed) {
        debugSLA(`Used time: ${slaUsed.textContent} → ${newValues.used}`);
        slaUsed.textContent = newValues.used;
    }
    if (slaRemaining) {
        debugSLA(`Remaining time: ${slaRemaining.textContent} → ${newValues.remaining}`);
        slaRemaining.textContent = newValues.remaining;
    }
    if (slaPause) {
        debugSLA(`Pause time: ${slaPause.textContent} → ${newValues.pause}`);
        slaPause.textContent = newValues.pause;
    }
    
    console.log('SLA Dashboard metrics updated:', newValues);
}

function formatTimeHours(seconds) {
    if (!seconds || seconds <= 0) return '0h 0m';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}m`;
}

function updateSLADashboardStats(stats) {
    // Update task count statistics in SLA Dashboard
    const statItems = document.querySelectorAll('.stat-item .stat-value');
    if (statItems.length >= 4) {
        statItems[0].textContent = stats.completed_tasks || 0; // Completed
        statItems[1].textContent = stats.in_progress_tasks || 0; // In Progress
        statItems[2].textContent = stats.postponed_tasks || 0; // Postponed
        statItems[3].textContent = stats.total_tasks || 0; // Total
    }
    
    // Update completion rate in the metrics section
    const completionRateEl = document.querySelector('.metric-value');
    if (completionRateEl && stats.total_tasks > 0) {
        const rate = (stats.completed_tasks / stats.total_tasks) * 100;
        completionRateEl.textContent = Math.round(rate) + '%';
    }
    
    // Update progress bars
    const progressFills = document.querySelectorAll('.progress-fill');
    if (progressFills.length > 0 && stats.total_tasks > 0) {
        const completionRate = (stats.completed_tasks / stats.total_tasks) * 100;
        progressFills[0].style.width = completionRate + '%';
    }
    
    // Force immediate visual update
    const event = new CustomEvent('slaStatsUpdated', { detail: stats });
    document.dispatchEvent(event);
    
    console.log('SLA Dashboard stats updated:', stats);
}

// Store the last successful SLA data to prevent reversion
let lastValidSLAData = null;
let slaDebugMode = false; // Set to true for debugging
let slaUpdateCount = 0;

// Debug function to track SLA updates
function debugSLA(message, data = null) {
    if (slaDebugMode) {
        console.log(`[SLA DEBUG ${++slaUpdateCount}] ${message}`, data || '');
    }
}

// Console commands for debugging (use in browser console)
window.enableSLADebug = function() {
    slaDebugMode = true;
    console.log('SLA Debug mode enabled. Use disableSLADebug() to turn off.');
};

window.disableSLADebug = function() {
    slaDebugMode = false;
    console.log('SLA Debug mode disabled.');
};

window.checkSLAStatus = function() {
    console.log('SLA Dashboard Status:', {
        debugMode: slaDebugMode,
        updateCount: slaUpdateCount,
        lastValidData: lastValidSLAData,
        currentValues: {
            total: document.querySelector('.sla-total-time')?.textContent,
            used: document.querySelector('.sla-used-time')?.textContent,
            remaining: document.querySelector('.sla-remaining-time')?.textContent,
            pause: document.querySelector('.sla-pause-time')?.textContent
        }
    });
};

window.forceSLARefresh = function() {
    console.log('Forcing SLA Dashboard refresh...');
    
    // Show loading state
    const refreshBtn = document.querySelector('.card__header button');
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Refreshing...';
    }
    
    // Force refresh both SLA and task statuses
    Promise.all([
        refreshSLADashboard(),
        refreshTaskStatuses()
    ]).finally(() => {
        // Reset button state
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
        }
        showNotification('SLA Dashboard refreshed', 'success');
    });
};

// Add CSS for spin animation
const style = document.createElement('style');
style.textContent = `
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);

// Log available debug commands
console.log('SLA Dashboard Debug Commands Available:');
console.log('- enableSLADebug() - Enable detailed logging');
console.log('- disableSLADebug() - Disable detailed logging');
console.log('- checkSLAStatus() - Show current SLA status');
console.log('- forceSLARefresh() - Force refresh SLA data');

function refreshTaskStatuses() {
    const currentDate = '<?= $selected_date ?>';
    const currentUserId = <?= $current_user_id ?? $_SESSION['user_id'] ?? 1 ?>;
    
    // Return promise for better handling
    return fetch(`/ergon/api/daily_planner_workflow.php?action=task-statuses&date=${currentDate}&user_id=${currentUserId}&t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.tasks) {
            data.tasks.forEach(task => {
                const taskCard = document.querySelector(`[data-task-id="${task.id}"]`);
                if (taskCard && task.status === 'postponed') {
                    // Update postponed task UI if not already updated
                    if (!taskCard.dataset.postponed) {
                        taskCard.dataset.status = 'postponed';
                        taskCard.dataset.postponed = 'true';
                        taskCard.style.opacity = '0.6';
                        taskCard.style.pointerEvents = 'none';
                        
                        const statusBadge = taskCard.querySelector('.badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Postponed';
                            statusBadge.className = 'badge badge--warning';
                        }
                        
                        const actionsDiv = taskCard.querySelector('.task-card__actions');
                        if (actionsDiv) {
                            actionsDiv.innerHTML = `<span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed</span>`;
                        }
                    }
                }
            });
        }
    })
    .catch(error => {
        console.log('Task status refresh failed:', error.message);
    });
}

function refreshSLADashboard() {
    const currentDate = '<?= $selected_date ?>';
    const currentUserId = <?= $current_user_id ?? $_SESSION['user_id'] ?? 1 ?>;
    
    // Return promise for better handling
    return fetch(`/ergon/api/daily_planner_workflow.php?action=sla-dashboard&date=${currentDate}&user_id=${currentUserId}&t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        console.log('User SLA Dashboard:', data);
        
        if (data.success && data.user_specific) {
            debugSLA('Received valid SLA data', data);
            
            // Store valid data to prevent reversion
            lastValidSLAData = data;
            
            // Update SLA metrics for current user only
            updateSLADashboard(data);
            
            // Update task counts for current user
            updateSLADashboardStats({
                total_tasks: data.total_tasks || 0,
                completed_tasks: data.completed_tasks || 0,
                in_progress_tasks: data.in_progress_tasks || 0,
                postponed_tasks: data.postponed_tasks || 0
            });
            
            // Update completion rate for current user
            const completionRateEl = document.querySelector('.metric-value');
            if (completionRateEl) {
                completionRateEl.textContent = (data.completion_rate || 0) + '%';
            }
            
            console.log(`✓ SLA Dashboard updated for User ${data.current_user_id}: ${data.total_tasks} tasks, SLA Total: ${formatTimeHours(data.sla_total_seconds)}`);
            
            // Show user info in dashboard title
            const dashboardTitle = document.querySelector('.card__title');
            if (dashboardTitle && dashboardTitle.textContent.includes('SLA Dashboard')) {
                dashboardTitle.innerHTML = `<i class="bi bi-speedometer2"></i> SLA Dashboard (User ${data.current_user_id})`;
            }
        } else {
            debugSLA('Invalid SLA data received, using cached data', data);
            console.error('Invalid user-specific SLA data, using last valid data:', data);
            // Use last valid data instead of reverting to defaults
            if (lastValidSLAData) {
                debugSLA('Restoring from cache', lastValidSLAData);
                updateSLADashboard(lastValidSLAData);
                updateSLADashboardStats({
                    total_tasks: lastValidSLAData.total_tasks || 0,
                    completed_tasks: lastValidSLAData.completed_tasks || 0,
                    in_progress_tasks: lastValidSLAData.in_progress_tasks || 0,
                    postponed_tasks: lastValidSLAData.postponed_tasks || 0
                });
            }
        }
    })
    .catch(error => {
        console.error('User SLA Dashboard error:', error);
        // Use last valid data instead of reverting to defaults
        if (lastValidSLAData) {
            console.log('Using cached SLA data due to fetch error');
            updateSLADashboard(lastValidSLAData);
        } else {
            // Only set fallback values if no valid data exists
            updateSLADashboard({
                sla_total_seconds: 0,
                active_seconds: 0,
                remaining_seconds: 0,
                pause_seconds: 0
            });
        }
    });
}

// Prevent auto-refresh from overriding postponed tasks and SLA data
function preservePostponedTasks() {
    if (window.postponedTasks) {
        window.postponedTasks.forEach(taskId => {
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard && !taskCard.dataset.postponed) {
                taskCard.dataset.status = 'postponed';
                taskCard.dataset.postponed = 'true';
                taskCard.style.opacity = '0.6';
                taskCard.style.pointerEvents = 'none';
                
                const statusBadge = taskCard.querySelector('.badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Postponed';
                    statusBadge.className = 'badge badge--warning';
                }
            }
        });
    }
}

// Prevent SLA data reversion
function preserveSLAData() {
    if (lastValidSLAData) {
        // Ensure SLA dashboard maintains correct data
        const slaTotal = document.querySelector('.sla-total-time');
        const slaUsed = document.querySelector('.sla-used-time');
        const slaRemaining = document.querySelector('.sla-remaining-time');
        const slaPause = document.querySelector('.sla-pause-time');
        
        // Only update if elements show default/loading values
        if (slaTotal && (slaTotal.textContent === 'Loading...' || slaTotal.textContent === '0h 0m')) {
            updateSLADashboard(lastValidSLAData);
        }
    }
}

// Override any refresh functions that might revert postponed status or SLA data
const originalSetInterval = window.setInterval;
window.setInterval = function(callback, delay) {
    const wrappedCallback = function() {
        callback();
        preservePostponedTasks();
        preserveSLAData();
    };
    return originalSetInterval(wrappedCallback, delay);
};

// Also protect against direct DOM manipulation
const originalInnerHTML = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
Object.defineProperty(Element.prototype, 'innerHTML', {
    set: function(value) {
        originalInnerHTML.set.call(this, value);
        // Restore SLA data if it was overwritten
        if (this.classList && this.classList.contains('sla-metrics') && lastValidSLAData) {
            setTimeout(() => preserveSLAData(), 100);
        }
    },
    get: originalInnerHTML.get
});

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
    notification.innerHTML = `<div style="position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:10px 20px;border-radius:5px;z-index:9999;">${message}</div>`;
    document.body.appendChild(notification);
    setTimeout(() => document.body.removeChild(notification), 3000);
}

function openProgressModal(taskId, progress, status) {
    currentTaskId = taskId;
    document.getElementById('progressSlider').value = progress;
    document.getElementById('progressValue').textContent = progress;
    document.getElementById('progressDialog').style.display = 'flex';
}

function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
}

function saveProgress() {
    var progress = document.getElementById('progressSlider').value;
    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: currentTaskId, 
            progress: parseInt(progress),
            status: status,
            reason: 'Progress updated via daily planner'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(currentTaskId, 'completed', { percentage: data.progress || progress });
            updateProgressBar(currentTaskId, progress);
            closeDialog();
            if (progress < 100) {
                alert('Progress updated to ' + progress + '% - Task will continue in progress');
            } else {
                alert('Task completed successfully!');
                stopTimer(currentTaskId);
            }
        } else {
            alert('Error updating progress: ' + (data.message || 'Failed to update'));
        }
    })
    .catch(() => alert('Network error occurred'));
}

function changeDate(date) {
    window.location.href = `/ergon/workflow/daily-planner/${date}`;
}

function startTask(taskId) {
    if (!taskId) {
        alert('Error: Task ID is missing');
        return;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'start');
            startSLATimer(taskId);
            refreshSLADashboard();
            refreshTaskStatuses();
            showNotification('Task started', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Start task error:', error);
        alert('Network error. Please refresh the page.');
    });
}

function pauseTask(taskId) {
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'pause');
            stopSLATimer(taskId);
            refreshSLADashboard();
            refreshTaskStatuses();
            showNotification('Task paused', 'info');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Pause task error:', error);
        alert('Network error. Please refresh the page.');
    });
}

function resumeTask(taskId) {
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'resume');
            startSLATimer(taskId);
            refreshSLADashboard();
            refreshTaskStatuses();
            showNotification('Task resumed', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Resume task error:', error);
        alert('Network error. Please refresh the page.');
    });
}

function updateTaskStatus(taskId, action) {
    fetch(`/ergon/api/daily_planner_workflow.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId })
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
    fetch(`/ergon/api/daily_planner_workflow.php?action=task-history&task_id=${taskId}`)
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
                    ${item.notes ? `<span class="history-notes">${item.notes}</span>` : ''}
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
    document.getElementById('postponeForm').style.display = 'block';
    document.getElementById('postponeOverlay').style.display = 'block';
    document.getElementById('newDate').focus();
}

function cancelPostpone() {
    document.getElementById('postponeForm').style.display = 'none';
    document.getElementById('postponeOverlay').style.display = 'none';
    document.getElementById('newDate').value = '';
    document.getElementById('postponeReason').value = '';
}

function submitPostpone() {
    const taskId = document.getElementById('postponeTaskId').value;
    const newDate = document.getElementById('newDate').value;
    const reason = document.getElementById('postponeReason').value;
    
    if (!newDate) {
        alert('Please select a date');
        return;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId), 
            new_date: newDate,
            reason: reason || 'No reason provided'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cancelPostpone();
            
            // Update SLA Dashboard with actual database values
            if (data.updated_stats) {
                updateSLADashboardStats(data.updated_stats);
            }
            
            // Mark task as postponed in UI permanently
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.dataset.status = 'postponed';
                taskCard.dataset.postponed = 'true';
                taskCard.style.opacity = '0.6';
                taskCard.style.pointerEvents = 'none';
                
                const statusBadge = taskCard.querySelector('.badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Postponed';
                    statusBadge.className = 'badge badge--warning';
                }
                
                const actionsDiv = taskCard.querySelector('.task-card__actions');
                if (actionsDiv) {
                    actionsDiv.innerHTML = `<span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed to ${newDate}</span>`;
                }
            }
            
            showNotification(`Task postponed to ${newDate}`, 'success');
            
            // Immediately update SLA Dashboard postponed count
            const postponedStat = document.querySelector('.stat-item:nth-child(3) .stat-value');
            if (postponedStat) {
                const currentCount = parseInt(postponedStat.textContent) || 0;
                postponedStat.textContent = currentCount + 1;
            }
            
            // Also refresh SLA Dashboard
            refreshSLADashboard();
            
            // Prevent any auto-refresh by marking as processed
            window.postponedTasks = window.postponedTasks || new Set();
            window.postponedTasks.add(taskId);
            
        } else {
            alert('Failed to postpone task: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error postponing task');
    });
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
            delete taskCard.dataset.pauseStart; // Clear pause start time
            newActions = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'in_progress')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            startSLATimer(taskId);
            break;
        case 'pause':
            newStatus = 'on_break';
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = 'task-card task-card--break';
            taskCard.dataset.pauseStart = Date.now();
            taskCard.dataset.pauseTime = new Date().toISOString(); // Store pause timestamp
            newActions = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            stopSLATimer(taskId);
            break;
        case 'completed':
            newStatus = 'completed';
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'badge badge--success';
            taskCard.className = 'task-card task-card--completed';
            newActions = `<span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>`;
            stopSLATimer(taskId);
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
    // Progress slider event listener
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        }
    }
    // Initialize SLA timers for active tasks immediately
    document.querySelectorAll('.task-card').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        
        if (status === 'in_progress') {
            startSLATimer(taskId);
        }
        
        // Set pause start time for tasks on break from pause_time
        if (status === 'on_break') {
            const pauseTime = item.dataset.pauseTime;
            if (pauseTime) {
                item.dataset.pauseStart = new Date(pauseTime).getTime();
            } else {
                item.dataset.pauseStart = Date.now();
            }
        }
    });
    
    // Initialize SLA Dashboard and force refresh all task data immediately
    refreshSLADashboard();
    
    // Force update all task timings on page load
    document.querySelectorAll('.task-card').forEach(item => {
        const taskId = item.dataset.taskId;
        if (taskId) {
            updateSLADisplay(taskId);
        }
    });
    
    // Auto-refresh every 1 second for real-time updates
    setInterval(() => {
        refreshSLADashboard();
        refreshTaskStatuses();
        // Update all active task timers
        document.querySelectorAll('.task-card[data-status="in_progress"], .task-card[data-status="on_break"]').forEach(item => {
            const taskId = item.dataset.taskId;
            if (taskId) updateSLADisplay(taskId);
        });
    }, 1000);
    
    // Page visibility API to refresh when user returns to tab
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshSLADashboard();
            refreshTaskStatuses();
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
        
        fetch('/ergon/api/daily_planner_workflow.php?action=quick-add', {
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
        const status = percentage >= 100 ? 'completed' : 'in_progress';
        
        fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: taskId, 
                progress: parseInt(percentage),
                status: status,
                reason: 'Progress updated via daily planner modal'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update task UI with new status
                const newStatus = data.status === 'completed' ? 'completed' : 
                                data.status === 'in_progress' ? 'in_progress' : 'assigned';
                updateTaskUI(taskId, newStatus, { percentage: data.progress });
                updateProgressBar(taskId, data.progress);
                
                // Handle timer based on status
                if (data.progress >= 100) {
                    stopSLATimer(taskId);
                } else if (newStatus === 'in_progress') {
                    // Keep timer running for partial progress
                }
                
                closeUpdateProgressModal();
                
                // Show success message with sync info
                let message = `Progress updated to ${data.progress}%`;
                if (data.synced_to_tasks) {
                    message += ' (synced to Tasks module)';
                }
                if (data.progress >= 100) {
                    message += ' - Task completed!';
                } else {
                    message += ' - Task continues in progress';
                }
                
                // Immediate refresh after progress update
                refreshSLADashboard();
                refreshTaskStatuses();
                showNotification(message, 'success');
            } else {
                alert('Failed to update progress: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating progress');
        });
    });
    

});
</script>

<?php renderModalJS(); ?>
<script src="/ergon/assets/js/task-progress-clean.js"></script>
<script src="/ergon/assets/js/planner-access-control.js"></script>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>