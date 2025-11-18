<?php
include __DIR__ . '/../shared/modal_component.php';
$content = ob_start();
?>
<link rel="stylesheet" href="/ergon/assets/css/daily-planner.css">
<link rel="stylesheet" href="/ergon/assets/css/daily-planner-modern.css">
<link rel="stylesheet" href="/ergon/assets/css/planner-access-control.css">

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
                             data-task-source="<?= $taskSource ?>">
                            
                            <div class="task-card__content">
                                <div class="task-card__sla">
                                    <div class="sla-time" title="Service Level Agreement: <?= $slaHours ?> hours"><?= $slaHours ?>h</div>
                                    <div class="sla-label">SLA</div>
                                    <?php if ($status === 'in_progress'): ?>
                                        <div class="countdown-timer" id="countdown-<?= $taskId ?>">
                                            <div class="countdown-display"><?= $timeDisplay ?></div>
                                            <div class="countdown-label">Left</div>
                                        </div>
                                    <?php elseif ($status === 'not_started' || $status === 'assigned'): ?>
                                        <div class="sla-info">
                                            <div class="sla-total"><?= sprintf('%02d:%02d:%02d', floor($slaHours), floor(($slaHours * 60) % 60), 0) ?></div>
                                            <div class="sla-total-label">Total</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
                    <span class="metric-value sla-total-time"><?= floor($totalPlannedMinutes / 60) ?>h <?= $totalPlannedMinutes % 60 ?>m</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Time Used:</span>
                    <span class="metric-value sla-used-time"><?= floor($totalActiveMinutes / 60) ?>h <?= round($totalActiveMinutes % 60) ?>m</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Remaining Time:</span>
                    <span class="metric-value sla-remaining-time">--</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Pause Duration:</span>
                    <span class="metric-value sla-pause-time">--</span>
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
    width: 4px;
    background: #e67e22;
    border-radius: 2px 0 0 2px;
}

.task-card[data-task-source="self_assigned"]:before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
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
    border-radius: 6px;
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

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 999999 !important;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    background-color: var(--bg-primary, #ffffff);
    margin: 5% auto;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    border: 1px solid var(--border-color, #e5e7eb);
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
    fetch(`/ergon/api/daily_planner_workflow.php?action=timer&task_id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
            if (display) {
                const remaining = data.remaining_seconds;
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;
                
                display.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Visual warnings
                display.classList.remove('countdown-display--warning', 'countdown-display--expired');
                if (data.is_late) {
                    display.classList.add('countdown-display--expired');
                    display.textContent = 'LATE: ' + formatTime(data.late_seconds);
                } else if (remaining <= 600) {
                    display.classList.add('countdown-display--warning');
                }
            }
            
            // Update SLA dashboard if visible
            updateSLADashboard(data);
        }
    })
    .catch(error => console.error('SLA update error:', error));
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

function updateSLADashboard(data) {
    // Update SLA metrics in dashboard
    const slaTotal = document.querySelector('.sla-total-time');
    const slaUsed = document.querySelector('.sla-used-time');
    const slaRemaining = document.querySelector('.sla-remaining-time');
    const slaPause = document.querySelector('.sla-pause-time');
    const slaLate = document.querySelector('.sla-late-time');
    
    if (slaTotal) slaTotal.textContent = formatTime(data.sla_seconds);
    if (slaUsed) slaUsed.textContent = formatTime(data.active_seconds);
    if (slaRemaining) slaRemaining.textContent = formatTime(data.remaining_seconds);
    if (slaPause) slaPause.textContent = formatTime(data.pause_duration);
    if (slaLate && data.is_late) {
        slaLate.textContent = formatTime(data.late_seconds);
        slaLate.parentElement.style.display = 'block';
    }
}

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
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'start');
            startSLATimer(taskId);
            showNotification('Task started - SLA timer running', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Start task error:', error);
        alert('Network error occurred');
    });
}

function pauseTask(taskId) {
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'pause');
            stopSLATimer(taskId);
            showNotification('Task paused - SLA timer stopped', 'info');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error occurred'));
}

function resumeTask(taskId) {
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'resume');
            startSLATimer(taskId);
            showNotification('Task resumed - SLA timer running', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => alert('Network error occurred'));
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
    // Create modal if it doesn't exist
    let modal = document.getElementById('postponeTaskModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'postponeTaskModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>📅 Postpone Task</h3>
                    <button class="modal-close" onclick="closeModal('postponeTaskModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="postponeTaskForm">
                        <input type="hidden" id="postponeTaskId" name="task_id">
                        <div class="form-group">
                            <label for="newDate">Reschedule to Date</label>
                            <input type="date" id="newDate" name="new_date" class="form-control" required min="${new Date(Date.now() + 86400000).toISOString().split('T')[0]}">
                        </div>
                        <div class="modal-actions">
                            <button type="button" onclick="closeModal('postponeTaskModal')" class="btn btn--secondary">Cancel</button>
                            <button type="submit" class="btn btn--warning">📅 Postpone Task</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add form submit handler
        document.getElementById('postponeTaskForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const taskId = document.getElementById('postponeTaskId').value;
            const newDate = document.getElementById('newDate').value;
            
            fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, new_date: newDate })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateTaskUI(taskId, 'postponed');
                    closeModal('postponeTaskModal');
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
    }
    
    // Set task ID and show modal
    document.getElementById('postponeTaskId').value = taskId;
    modal.style.display = 'block';
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
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'in_progress')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            break;
        case 'pause':
            newStatus = 'on_break';
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = 'task-card task-card--break';
            newActions = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
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
    // Initialize SLA timers for active tasks
    document.querySelectorAll('.task-card').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        
        if (status === 'in_progress') {
            startSLATimer(taskId);
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
                
                setTimeout(() => {
                    showNotification(message, 'success');
                }, 500);
            } else {
                alert('Failed to update progress: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating progress');
        });
    });
    
    document.getElementById('postponeTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskId = document.getElementById('postponeTaskId').value;
        const newDate = document.getElementById('newDate').value;
        
        fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
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
<script src="/ergon/assets/js/task-progress-clean.js"></script>
<script src="/ergon/assets/js/planner-access-control.js"></script>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>