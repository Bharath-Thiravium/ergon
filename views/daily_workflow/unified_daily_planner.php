<?php
include __DIR__ . '/../shared/modal_component.php';

// Configuration constants for maintainability
if (!defined('DEFAULT_SLA_HOURS')) {
    define('DEFAULT_SLA_HOURS', 0.25); // 15 minutes default SLA
}
if (!defined('DAILY_PLANNER_BASE_URL')) {
    define('DAILY_PLANNER_BASE_URL', '/ergon/workflow/daily-planner/');
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$content = ob_start();
?>
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
<link rel="stylesheet" href="/ergon/assets/css/unified-daily-planner.css">

<?php renderModalCSS(); ?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-calendar-day"></i> Daily Planner</h1>
        <p>Advanced Task Execution Workflow - <?= date('l, F j, Y', strtotime($selected_date)) ?>
        <?php if ($selected_date < date('Y-m-d')): ?>
            <span class="badge badge--muted" style="margin-left: 10px;"><i class="bi bi-archive"></i> 📜 Historical View</span>
        <?php elseif ($selected_date > date('Y-m-d')): ?>
            <span class="badge badge--info" style="margin-left: 10px;"><i class="bi bi-calendar-plus"></i> 📅 Planning Mode</span>
        <?php else: ?>
            <span class="badge badge--success" style="margin-left: 10px;"><i class="bi bi-play-circle"></i> 🎯 Execution Mode</span>
        <?php endif; ?>
        </p>
        <?php if (isset($_SESSION['sync_message'])): ?>
            <div class="alert alert-info" style="margin: 10px 0; padding: 8px 12px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; color: #0c5460;">
                <i class="bi bi-info-circle"></i> <?= $_SESSION['sync_message'] ?>
            </div>
            <?php unset($_SESSION['sync_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-warning" style="margin: 10px 0; padding: 8px 12px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
                <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <div class="date-selector-group">
            <label for="dateSelector" class="date-label">Select Date:</label>
            <input type="date" id="dateSelector" value="<?= $selected_date ?>" min="<?= date('Y-m-d', strtotime('-90 days')) ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>" onchange="changeDate(this.value)" class="form-control" title="Select a date to view daily planner (past dates show historical view, future dates for planning)">
        </div>
        <?php if ($selected_date < date('Y-m-d')): ?>
            <button class="btn btn--secondary" onclick="showHistoryInfo()" title="Information about historical view">
                <i class="bi bi-info-circle"></i> History View Info
            </button>
            <a href="<?= DAILY_PLANNER_BASE_URL . date('Y-m-d') ?>" class="btn btn--primary" title="Go to today's planner">
                <i class="bi bi-calendar-day"></i> Today's Planner
            </a>
        <?php else: ?>
            <a href="<?= DAILY_PLANNER_BASE_URL . $selected_date ?>?refresh=1" class="btn btn--info" title="Add new tasks from Tasks module (preserves existing progress)">
                <i class="bi bi-plus-circle"></i> Sync New Tasks
            </a>
            <a href="/ergon/tasks/create" class="btn btn--secondary">
                <i class="bi bi-plus"></i> Add Task
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="planner-grid <?php 
    if ($selected_date < date('Y-m-d')) echo 'historical-view';
    elseif ($selected_date > date('Y-m-d')) echo 'planning-mode';
    else echo 'execution-mode';
?>">
    <!-- Task Execution Section -->
    <div class="card">
        <div class="card__header">
            <h3 class="card__title"><i class="bi bi-play-circle"></i> Task Execution</h3>
            <span class="badge badge--info"><?= count($planned_tasks) ?> tasks</span>
        </div>
        <div class="card__body">
            <?php if (empty($planned_tasks)): ?>
                <div class="empty-state">
                    <?php if ($selected_date < date('Y-m-d')): ?>
                        <i class="bi bi-archive"></i>
                        <h4>No tasks found for this date</h4>
                        <p>No tasks were assigned to or completed on <?= date('F j, Y', strtotime($selected_date)) ?>.</p>
                        <div class="empty-state-actions">
                            <a href="<?= DAILY_PLANNER_BASE_URL . date('Y-m-d') ?>" class="btn btn--primary">
                                <i class="bi bi-calendar-day"></i> Go to Today's Planner
                            </a>
                        </div>
                    <?php else: ?>
                        <i class="bi bi-calendar-x"></i>
                        <h4>No tasks planned for today</h4>
                        <p>No tasks found for today. Tasks can be:</p>
                        <ul class="empty-state-list">
                            <li><strong>Assigned by others</strong> - Tasks given to you</li>
                            <li><strong>Self-assigned</strong> - Tasks you create for yourself</li>
                            <li><strong>Rolled over</strong> - Unfinished tasks from previous days</li>
                        </ul>
                        <div class="empty-state-actions">
                            <a href="/ergon/tasks/create" class="btn btn--primary btn-spaced">
                                <i class="bi bi-plus"></i> Create Task
                            </a>
                            <a href="<?= DAILY_PLANNER_BASE_URL . $selected_date ?>?refresh=1" class="btn btn--info">
                                <i class="bi bi-arrow-clockwise"></i> Sync Tasks
                            </a>
                        </div>
                    <?php endif; ?>
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
                        // BUSINESS CHANGE: Default SLA changed from 1.0 to 0.25 hours for better granularity
                        $slaHours = (float)($task['sla_hours'] ?? DEFAULT_SLA_HOURS);
                        $slaDuration = (int)(max(0.25, $slaHours) * 3600);
                        $startTime = $task['start_time'] ?? null;
                        $startTimestamp = $startTime ? strtotime($startTime) : 0;
                        $postponeContext = $task['postpone_context'] ?? 'normal';
                        
                        $remainingTime = $slaDuration;
                        if ($startTimestamp > 0 && ($status === 'in_progress' || $status === 'on_break')) {
                            $elapsed = time() - $startTimestamp;
                            $remainingTime = max(0, $slaDuration - $elapsed);
                        }
                        
                        $timeDisplay = sprintf('%02d:%02d:%02d', 
                            (int)floor($remainingTime / 3600), 
                            (int)floor(($remainingTime % 3600) / 60), 
                            (int)floor($remainingTime % 60)
                        );
                        
                        $cssClass = '';
                        if ($status === 'in_progress') $cssClass = 'task-item--active';
                        elseif ($status === 'on_break') $cssClass = 'task-item--break';
                        elseif ($status === 'completed') $cssClass = 'task-item--completed';
                        elseif ($status === 'postponed') {
                            $isCurrentDate = ($selected_date === date('Y-m-d'));
                            $isPostponedToToday = ($postponeContext === 'postponed_to_today');
                            $cssClass = ($isCurrentDate && $isPostponedToToday) ? 'task-card--postponed-active' : 'task-card--postponed';
                        }
                    ?>
                        <?php 
                        $taskSource = 'unknown';
                        if (strpos($task['title'], '[From Others]') === 0) {
                            $taskSource = 'from_others';
                        } elseif (strpos($task['title'], '[Self]') === 0) {
                            $taskSource = 'self_assigned';
                        }
                        ?>
                        <?php 
                        // FIXED: Remove unused variable or ensure proper usage
                        // $isPastDate is used for historical view styling and action restrictions
                        $isPastDate = ($selected_date < date('Y-m-d'));
                        $isFutureDate = ($selected_date > date('Y-m-d'));
                        $historicalClass = $isPastDate ? 'task-card--historical' : '';
                        $modeClass = '';
                        if ($isPastDate) $modeClass = 'historical-view';
                        elseif ($isFutureDate) $modeClass = 'planning-mode';
                        else $modeClass = 'execution-mode';
                        ?>
                        <div class="task-card <?= $cssClass ?> <?= $historicalClass ?> <?= $modeClass ?>" 
                             data-task-id="<?= $taskId ?>" 
                             data-original-task-id="<?= $task['task_id'] ?? '' ?>" 
                             data-sla-duration="<?= $slaDuration ?>" 
                             data-start-time="<?= $startTimestamp ?>" 
                             data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                             data-task-source="<?= htmlspecialchars($taskSource, ENT_QUOTES, 'UTF-8') ?>"
                             data-pause-time="<?= htmlspecialchars($task['pause_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                             data-pause-start-time="<?= htmlspecialchars($task['pause_start_time'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                             data-active-seconds="<?= (int)($task['active_seconds'] ?? 0) ?>"
                             data-pause-duration="<?= (int)($task['pause_duration'] ?? 0) ?>"
                             data-is-past="<?= $isPastDate ? 'true' : 'false' ?>">
                            
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
                                        <?php if ($status === 'on_break'): ?>
                                            <div class="pause-timer" id="pause-timer-<?= $taskId ?>">00:00:00</div>
                                            <div class="pause-timer-label">Break Time</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timing-info">
                                        <span class="timing-label">Time Used:</span>
                                        <span class="timing-value time-used"><?= (int)floor($activeSeconds/3600) ?>h <?= (int)floor(($activeSeconds%3600)/60) ?>m</span>
                                    </div>
                                    <div class="timing-info">
                                        <span class="timing-label">Pause Duration:</span>
                                        <span class="timing-value time-paused"><?= (int)floor($pauseSeconds/3600) ?>h <?= (int)floor(($pauseSeconds%3600)/60) ?>m</span>
                                    </div>
                                </div>
                                
                                <?php 
                                // Show creation info for postponed tasks and rolled-over tasks
                                $isRolledOver = isset($task['postponed_from_date']) && $task['postponed_from_date'] && $task['postponed_from_date'] !== $selected_date;
                                
                                if ($status === 'postponed' || $isRolledOver): 
                                    $createdAt = $task['created_at'] ?? date('Y-m-d H:i:s');
                                ?>
                                    <div class="task-card__created-info <?= $isRolledOver ? 'task-card__rollover-info' : '' ?>">
                                        <?php if ($isRolledOver): ?>
                                            <small class="text-info"><i class="bi bi-arrow-repeat"></i> Rolled over from: <?= date('d/m/Y', strtotime($task['postponed_from_date'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted"><i class="bi bi-calendar"></i> Created on: <?= date('d/m/Y', strtotime($createdAt)) ?></small>
                                        <?php endif; ?>
                                        
                                        <?php if ($postponeContext === 'postponed_to_today' && isset($task['postponed_from_date']) && $task['postponed_from_date']): ?>
                                            <small class="text-muted"><i class="bi bi-arrow-right"></i> Postponed from: <?= date('d/m/Y', strtotime($task['postponed_from_date'])) ?></small>
                                        <?php elseif ($postponeContext === 'postponed_from_today' && isset($task['postponed_to_date']) && $task['postponed_to_date']): ?>
                                            <small class="text-muted"><i class="bi bi-arrow-right"></i> Postponed to: <?= date('d/m/Y', strtotime($task['postponed_to_date'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="task-card__actions" id="actions-<?= $taskId ?>">
                                    <?php 
                                    $isCurrentDate = ($selected_date === date('Y-m-d'));
                                    $isPastDate = ($selected_date < date('Y-m-d'));
                                    
                                    if ($isPastDate): 
                                        // 📜 Historical View - Disable all execution buttons
                                    ?>
                                        <span class="badge badge--muted"><i class="bi bi-archive"></i> Historical View</span>
                                        <button class="btn btn--sm btn--secondary" onclick="showTaskHistory(<?= $taskId ?>, '<?= htmlspecialchars($task['title']) ?>')" title="View this task's history and timeline">
                                            <i class="bi bi-clock-history"></i> History
                                        </button>
                                        <?php if ($status === 'completed'): ?>
                                            <span class="badge badge--success"><i class="bi bi-check-circle"></i> Completed</span>
                                            <button class="btn btn--sm btn--info" onclick="showReadOnlyProgress(<?= $taskId ?>, <?= $task['completed_percentage'] ?? 100 ?>)" title="View completion details (read-only)">
                                                <i class="bi bi-percent"></i> Progress
                                            </button>
                                        <?php else: ?>
                                            <span class="badge badge--warning"><i class="bi bi-arrow-repeat"></i> Rolled Over</span>
                                            <small class="text-muted d-block">🔄 Execution moved to current date</small>
                                        <?php endif; ?>
                                    <?php else: 
                                        // Current/future dates: Full functionality
                                        if ($status === 'postponed'): 
                                            $isPostponedToToday = ($postponeContext === 'postponed_to_today');
                                            $canStart = $isCurrentDate && $isPostponedToToday;
                                        ?>
                                            <?php if ($canStart): ?>
                                                <button class="btn btn--sm btn--success" onclick="activatePostponedTask(<?= $taskId ?>)" title="Start this postponed task">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed</span>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>)" title="Re-postpone to another date">
                                                <i class="bi bi-calendar-plus"></i> Re-postpone
                                            </button>
                                        <?php elseif ($status === 'not_started' || $status === 'assigned'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button class="btn btn--sm btn--success" onclick="startTask(<?= $taskId ?>)" title="Start working on this task">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--success" disabled title="🔒 Start disabled for past/future dates">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'in_progress'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button class="btn btn--sm btn--warning" onclick="pauseTask(<?= $taskId ?>)" title="Take a break from this task">
                                                    <i class="bi bi-pause"></i> Break
                                                </button>
                                                <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= $task['completed_percentage'] ?? 0 ?>, '<?= $status ?>')" title="Update task completion progress">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--warning" disabled title="🔒 Pause disabled for past/future dates">
                                                    <i class="bi bi-pause"></i> Break
                                                </button>
                                                <button class="btn btn--sm btn--primary" disabled title="🔒 Progress updates disabled for past/future dates">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'on_break'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button class="btn btn--sm btn--success" onclick="resumeTask(<?= $taskId ?>)" title="Resume working on this task">
                                                    <i class="bi bi-play"></i> Resume
                                                </button>
                                                <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= $task['completed_percentage'] ?? 0 ?>, '<?= $status ?>')" title="Update task completion progress">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--success" disabled title="🔒 Resume disabled for past/future dates">
                                                    <i class="bi bi-play"></i> Resume
                                                </button>
                                                <button class="btn btn--sm btn--primary" disabled title="🔒 Progress updates disabled for past/future dates">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'completed'): ?>
                                            <span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>
                                        <?php elseif ($status === 'cancelled'): ?>
                                            <span class="badge badge--danger"><i class="bi bi-x-circle"></i> Cancelled</span>
                                        <?php elseif ($status === 'suspended'): ?>
                                            <span class="badge badge--warning"><i class="bi bi-pause-circle"></i> Suspended</span>
                                        <?php endif; ?>
                                        <?php if (!in_array($status, ['completed', 'cancelled', 'suspended', 'postponed'])): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>)" title="Postpone task to another date">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--secondary" disabled title="🔒 Postpone disabled for past/future dates">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
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
            <button class="btn btn--sm btn--secondary" onclick="forceSLARefresh()" title="Manual refresh - automatic updates disabled to prevent rate limiting">
                <i class="bi bi-arrow-clockwise"></i> Manual Refresh
            </button>
            <small class="text-muted" style="display: block; margin-top: 5px;">⚡ Auto-refresh disabled to prevent rate limiting</small>
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



<script>
// SLA Dashboard and Timer Management (using external objects)
let slaDebugMode = false;
let slaUpdateCount = 0;
let lastValidSLAData = null;
let currentTaskId = null;

function updateLocalCountdown(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    const status = taskCard.dataset.status;
    const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900; // 15 minutes default
    const startTime = parseInt(taskCard.dataset.startTime) || 0;
    const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
    
    const countdownDisplay = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
    const pauseTimer = taskCard.querySelector(`#pause-timer-${taskId}`);
    
    if (status === 'in_progress' && startTime > 0) {
        const elapsed = Math.floor((Date.now() - (startTime * 1000)) / 1000);
        const totalUsed = activeSeconds + elapsed;
        const remaining = Math.max(0, slaDuration - totalUsed);
        
        if (countdownDisplay) {
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            countdownDisplay.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Add warning classes
            if (remaining <= 300) { // 5 minutes
                countdownDisplay.classList.add('countdown-display--warning');
            }
            if (remaining <= 0) {
                countdownDisplay.classList.add('countdown-display--expired');
            }
        }
    } else if (status === 'on_break' && pauseTimer) {
        const pauseStart = parseInt(taskCard.dataset.pauseStart) || Date.now();
        const pauseElapsed = Math.floor((Date.now() - pauseStart) / 1000);
        
        const hours = Math.floor(pauseElapsed / 3600);
        const minutes = Math.floor((pauseElapsed % 3600) / 60);
        const seconds = pauseElapsed % 60;
        pauseTimer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
}

function stopSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

function updateSLADashboard(data) {
    if (!data || !data.user_specific) return;
    
    const totalTime = document.querySelector('.sla-total-time');
    const usedTime = document.querySelector('.sla-used-time');
    const remainingTime = document.querySelector('.sla-remaining-time');
    const pauseTime = document.querySelector('.sla-pause-time');
    
    if (totalTime) totalTime.textContent = data.total_sla_time || '0h 0m';
    if (usedTime) usedTime.textContent = data.total_active_time || '0h 0m';
    if (remainingTime) remainingTime.textContent = data.total_remaining_time || '0h 0m';
    if (pauseTime) pauseTime.textContent = data.total_pause_time || '0h 0m';
}

function updateSLADashboardStats(stats) {
    const totalTasks = document.querySelector('.stat-item:nth-child(4) .stat-value');
    const completedTasks = document.querySelector('.stat-item:nth-child(1) .stat-value');
    const inProgressTasks = document.querySelector('.stat-item:nth-child(2) .stat-value');
    const postponedTasks = document.querySelector('.stat-item:nth-child(3) .stat-value');
    
    if (totalTasks) totalTasks.textContent = stats.total_tasks || 0;
    if (completedTasks) completedTasks.textContent = stats.completed_tasks || 0;
    if (inProgressTasks) inProgressTasks.textContent = stats.in_progress_tasks || 0;
    if (postponedTasks) postponedTasks.textContent = stats.postponed_tasks || 0;
}

function setButtonLoadingState(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.dataset.originalText = originalText;
        button.innerHTML = '<i class="bi bi-arrow-clockwise spinner"></i> Loading...';
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

function debugLog(message, data = '') {
    if (slaDebugMode) {
        console.log(`[SLA DEBUG] ${message}`, data || '');
    }
}

// Define missing global functions for onclick handlers
window.pauseTask = function(taskId) {
    updateTaskUI(taskId, 'on_break');
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (taskCard) taskCard.dataset.pauseStart = Date.now();
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
};

window.openProgressModal = function(taskId, progress, status) {
    currentTaskId = taskId;
    document.getElementById('progressSlider').value = progress;
    document.getElementById('progressValue').textContent = progress;
    document.getElementById('progressDialog').style.display = 'flex';
};

window.postponeTask = function(taskId) {
    document.getElementById('postponeTaskId').value = taskId;
    document.getElementById('postponeForm').style.display = 'block';
    document.getElementById('postponeOverlay').style.display = 'block';
    document.getElementById('newDate').focus();
};

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
            task_id: parseInt(currentTaskId), 
            progress: parseInt(progress),
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTaskUI(currentTaskId, status, { percentage: progress });
            closeDialog();
            if (progress >= 100) {
                stopSLATimer(currentTaskId);
            }
        }
    })
    .catch(() => {});
}

function updateTaskUI(taskId, action, data = {}) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    const statusBadge = taskCard?.querySelector('.badge');
    const actionsDiv = taskCard?.querySelector('.task-card__actions');
    
    if (!taskCard || !statusBadge || !actionsDiv) return;
    
    taskCard.dataset.status = action;
    
    switch(action) {
        case 'in_progress':
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--in_progress';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--active';
            break;
        case 'on_break':
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--break';
            break;
        case 'completed':
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'badge badge--success';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--completed';
            break;
    }
}

// Progress slider event listener
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        }
    }
});

function debugLog(message, data = '') {
    if (slaDebugMode) {
        console.log(`[SLA DEBUG] ${message}`, data || '');
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
    console.log('Manual SLA Dashboard refresh...');
    
    // Show loading state
    const refreshBtn = document.querySelector('.card__header button');
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Refreshing...';
    }
    
    // Only refresh SLA dashboard - no timer calls
    refreshSLADashboard().finally(() => {
        // Reset button state
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
        }
        showNotification('SLA Dashboard refreshed (manual)', 'success');
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
    
    return fetch(`/ergon/api/daily_planner_workflow.php?action=sla-dashboard&date=${currentDate}&user_id=${currentUserId}&t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.user_specific) {
            lastValidSLAData = data;
            updateSLADashboard(data);
            updateSLADashboardStats({
                total_tasks: data.total_tasks || 0,
                completed_tasks: data.completed_tasks || 0,
                in_progress_tasks: data.in_progress_tasks || 0,
                postponed_tasks: data.postponed_tasks || 0
            });
        }
    })
    .catch(error => {
        console.log('SLA Dashboard error:', error);
    });
}

// Enforce past date button restrictions
function enforcePastDateRestrictions() {
    const selectedDate = '<?= $selected_date ?>';
    const today = new Date().toISOString().split('T')[0];
    const isPastDate = selectedDate < today;
    
    if (isPastDate) {
        // Disable all execution buttons for past dates
        document.querySelectorAll('.task-card').forEach(taskCard => {
            const buttons = taskCard.querySelectorAll('button[onclick*="startTask"], button[onclick*="pauseTask"], button[onclick*="resumeTask"], button[onclick*="postponeTask"]');
            buttons.forEach(btn => {
                if (!btn.disabled) {
                    btn.disabled = true;
                    btn.title = '🔒 Action disabled for past dates';
                }
            });
        });
    }
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
    
    // Re-enforce past date restrictions after any refresh
    enforcePastDateRestrictions();
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
    // Simple console notification to prevent errors
    console.log('[NOTIFICATION ' + type.toUpperCase() + ']:', message);
    
    // Optional: Create visual notification
    try {
        const notification = document.createElement('div');
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        const bgColor = colors[type] || colors.info;
        notification.innerHTML = '<div style="position:fixed;top:20px;right:20px;background:' + bgColor + ';color:white;padding:10px 20px;border-radius:5px;z-index:9999;box-shadow: 0 2px 10px rgba(0,0,0,0.2);">' + message + '</div>';
        document.body.appendChild(notification);
        setTimeout(function() {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 3000);
    } catch (e) {
        // Fallback to console only
        console.log('[NOTIFICATION FALLBACK]:', message);
    }
}

// Define openProgressModal function globally
window.openProgressModal = function(taskId, progress, status) {
    currentTaskId = taskId;
    document.getElementById('progressSlider').value = progress;
    document.getElementById('progressValue').textContent = progress;
    document.getElementById('progressDialog').style.display = 'flex';
};

// Also define as regular function for compatibility
function openProgressModal(taskId, progress, status) {
    return window.openProgressModal(taskId, progress, status);
}

function closeDialog() {
    document.getElementById('progressDialog').style.display = 'none';
}

function saveProgress() {
    var progress = document.getElementById('progressSlider').value;
    var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'assigned';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    const requestData = { 
        task_id: parseInt(currentTaskId), 
        progress: parseInt(progress),
        status: status,
        reason: 'Progress updated via daily planner',
        csrf_token: csrfToken
    };
    
    console.log('Sending request:', requestData);
    
    fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        const data = JSON.parse(text);
        if (data.success) {
            const actualProgress = data.progress || progress;
            const taskStatus = actualProgress >= 100 ? 'completed' : 'in_progress';
            
            updateTaskUI(currentTaskId, taskStatus, { percentage: actualProgress });
            updateProgressBar(currentTaskId, actualProgress);
            closeDialog();
            
            if (actualProgress < 100) {
                showNotification('Progress updated to ' + actualProgress + '% - Task will continue in progress', 'success');
            } else {
                showNotification('Task completed successfully!', 'success');
                stopTimer(currentTaskId);
            }
        } else {
            console.log('API Error:', data.message || 'Failed to update progress');
        }
    })
    .catch(error => {
        console.log('Progress update error:', error.message);
    });
}

function changeDate(date) {
    // Validate date format
    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        console.log('Invalid date format');
        document.getElementById('dateSelector').value = '<?= $selected_date ?>';
        return;
    }
    
    // Allow future dates for planning (up to 30 days ahead)
    const today = new Date().toISOString().split('T')[0];
    const maxFutureDate = new Date();
    maxFutureDate.setDate(maxFutureDate.getDate() + 30);
    const maxDateStr = maxFutureDate.toISOString().split('T')[0];
    
    if (date > maxDateStr) {
        console.log('Cannot view dates more than 30 days in the future');
        document.getElementById('dateSelector').value = '<?= $selected_date ?>';
        return;
    }
    
    // Check minimum date (90 days in the past)
    const minDate = new Date();
    minDate.setDate(minDate.getDate() - 90);
    const minDateStr = minDate.toISOString().split('T')[0];
    
    if (date < minDateStr) {
        console.log('Cannot view dates more than 90 days in the past');
        document.getElementById('dateSelector').value = '<?= $selected_date ?>';
        return;
    }
    
    // Show loading indicator for history view or planning mode
    const pageTitle = document.querySelector('.page-title h1');
    if (pageTitle) {
        if (date < today) {
            pageTitle.innerHTML = '<i class="bi bi-calendar-day"></i> Daily Planner <span class="loading-indicator">Loading history...</span>';
        } else if (date > today) {
            pageTitle.innerHTML = '<i class="bi bi-calendar-day"></i> Daily Planner <span class="loading-indicator">Loading planning mode...</span>';
        }
    }
    
    // Navigate to selected date using configurable URL
    window.location.href = '<?= DAILY_PLANNER_BASE_URL ?>' + date;
}

function showHistoryInfo() {
    const selectedDate = '<?= $selected_date ?>';
    const today = new Date().toISOString().split('T')[0];
    
    // Only show for past dates
    if (selectedDate >= today) {
        return;
    }
    
    const modal = document.createElement('div');
    modal.className = 'history-info-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeHistoryInfo()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-info-circle"></i> Historical View Information</h3>
                <button onclick="closeHistoryInfo()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>You are viewing historical data for ` + new Date('<?= $selected_date ?>').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) + `.</strong></p>
                <ul>
                    <li><i class="bi bi-eye"></i> This is a read-only view of past tasks</li>
                    <li><i class="bi bi-calendar-check"></i> Shows only tasks that were assigned to this specific date</li>
                    <li><i class="bi bi-check-circle"></i> Includes tasks that were completed on this date</li>
                    <li><i class="bi bi-archive"></i> No actions can be performed on historical tasks</li>
                    <li><i class="bi bi-arrow-left"></i> Return to today's planner to manage current tasks</li>
                </ul>
                <div class="modal-actions">
                    <button onclick="goToToday()" class="btn btn--primary">
                        <i class="bi bi-calendar-day"></i> Go to Today
                    </button>
                    <button onclick="closeHistoryInfo()" class="btn btn--secondary">Close</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeHistoryInfo() {
    const modal = document.querySelector('.history-info-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

function goToToday() {
    // Use configurable base URL constant
    window.location.href = '<?= DAILY_PLANNER_BASE_URL ?>' + new Date().toISOString().split('T')[0];
}

function showReadOnlyProgress(taskId, percentage) {
    const modal = document.createElement('div');
    modal.className = 'history-info-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeReadOnlyProgress()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-percent"></i> Task Progress (Read-Only)</h3>
                <button onclick="closeReadOnlyProgress()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="progress-display">
                    <div class="progress-info">
                        <span class="progress-label">Completion Status</span>
                        <span class="progress-value">${percentage}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${percentage}%"></div>
                    </div>
                    <p class="text-muted"><i class="bi bi-info-circle"></i> This is a historical view. Progress cannot be modified.</p>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="closeReadOnlyProgress()" class="btn btn--secondary">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeReadOnlyProgress() {
    const modal = document.querySelector('.history-info-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

function showTaskHistory(taskId, taskTitle) {
    const modal = document.createElement('div');
    modal.className = 'history-info-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeTaskHistory()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-clock-history"></i> Task History</h3>
                <button onclick="closeTaskHistory()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>${taskTitle}</strong></p>
                <div id="taskHistoryContent">Loading task history...</div>
            </div>
            <div class="modal-actions">
                <button onclick="closeTaskHistory()" class="btn btn--secondary">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Load task history
    fetch(`/ergon/api/daily_planner_workflow.php?action=task-history&task_id=${taskId}`)
    .then(response => response.json())
    .then(data => {
        const content = document.getElementById('taskHistoryContent');
        if (data.success && data.history && data.history.length > 0) {
            content.innerHTML = `
                <div class="task-history-timeline">
                    ${data.history.map(item => `
                        <div class="history-timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-action">${item.action}</span>
                                    <span class="timeline-date">${item.date}</span>
                                </div>
                                ${item.progress ? `<div class="timeline-progress">Progress: ${item.progress}%</div>` : ''}
                                ${item.notes ? `<div class="timeline-notes">${item.notes}</div>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            content.innerHTML = `
                <div class="no-history">
                    <i class="bi bi-clock"></i>
                    <p>No history available for this task.</p>
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById('taskHistoryContent').innerHTML = `
            <div class="error-message">
                <i class="bi bi-exclamation-triangle"></i>
                <p>Error loading task history.</p>
            </div>
        `;
    });
}

function closeTaskHistory() {
    const modal = document.querySelector('.history-info-modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

function activatePostponedTask(taskId) {
    // Activate a postponed task on its target date
    fetch('/ergon/api/daily_planner_workflow.php?action=activate-postponed', {
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
            // Update task to normal state
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.dataset.status = 'not_started';
                taskCard.classList.remove('task-card--postponed', 'task-card--postponed-active');
                
                const statusBadge = taskCard.querySelector('.badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Not Started';
                    statusBadge.className = 'badge badge--not_started';
                }
                
                const actionsDiv = taskCard.querySelector('.task-card__actions');
                if (actionsDiv) {
                    actionsDiv.innerHTML = `
                        <button class="btn btn--sm btn--success" onclick="startTask(${taskId})">
                            <i class="bi bi-play"></i> Start
                        </button>
                        <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
                            <i class="bi bi-calendar-plus"></i> Postpone
                        </button>
                    `;
                }
            }
            showNotification('Task activated and ready to start', 'success');
            refreshSLADashboard();
        }
    })
    .catch(error => {
        console.log('Activate postponed task error:', error.message);
    });
}

// Define startTask function globally
window.startTask = function(taskId) {
    if (!taskId) return;
    
    // Update UI immediately
    updateTaskUI(taskId, 'start');
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    showNotification('Task started', 'success');
    // showNotification('Task started', 'success'); // Removed for better UX, UI change is enough feedback.
    
    // Send to server
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

// Also define as regular function for compatibility
function pauseTask(taskId) {
    return window.pauseTask(taskId);
}

// Define resumeTask function globally  
window.resumeTask = function(taskId) {
    // Update UI immediately
    updateTaskUI(taskId, 'resume');
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (taskCard) delete taskCard.dataset.pauseStart;
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
    showNotification('Task resumed', 'success');
    // showNotification('Task resumed', 'success'); // Removed for better UX
    
    // Send to server
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
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
        }
    })
    .catch(error => { /* Silent error handling */ });
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

// Define postponeTask function globally
window.postponeTask = function(taskId) {
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    if (!newDate) {
        console.log('Please select a date');
        return;
    }
    
    fetch('/ergon/api/daily_planner_workflow.php?action=postpone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            task_id: parseInt(taskId), 
            new_date: newDate,
            reason: reason || 'No reason provided',
            csrf_token: csrfToken
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
            
        }
    })
    .catch(error => {
        // Silent error handling - no console output
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
    const statusBadge = taskCard?.querySelector('.badge');
    const actionsDiv = taskCard?.querySelector('.task-card__actions');
    const countdownLabel = taskCard?.querySelector('.countdown-label');
    
    if (!taskCard || !statusBadge || !actionsDiv) return;
    
    // Update task card dataset
    taskCard.dataset.status = action === 'start' || action === 'resume' ? 'in_progress' : 
                              action === 'pause' ? 'on_break' : action;
    
    switch(action) {
        case 'start':
        case 'resume':
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--in_progress';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--active';
            
            // Update countdown label
            if (countdownLabel) countdownLabel.textContent = 'Remaining';
            
            // Remove pause timer if exists
            const pauseTimer = taskCard.querySelector('.pause-timer');
            const pauseLabel = taskCard.querySelector('.pause-timer-label');
            if (pauseTimer) pauseTimer.remove();
            if (pauseLabel) pauseLabel.remove();
            
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'in_progress')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
            break;
            
        case 'pause':
            statusBadge.textContent = 'On Break';
            statusBadge.className = 'badge badge--on_break';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--break';
            
            // Update countdown label
            if (countdownLabel) countdownLabel.textContent = 'Paused';
            
            // Add pause timer
            const countdownDiv = taskCard.querySelector('.countdown-timer');
            if (countdownDiv && !countdownDiv.querySelector('.pause-timer')) {
                countdownDiv.innerHTML += `
                    <div class="pause-timer" id="pause-timer-${taskId}">00:00:00</div>
                    <div class="pause-timer-label">Break Time</div>
                `;
            }
            
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;
            break;
            
        case 'completed':
            statusBadge.textContent = 'Completed';
            statusBadge.className = 'badge badge--success';
            taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' task-card--completed';
            actionsDiv.innerHTML = `<span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>`;
            break;
    }
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

// Enable controlled timer requests with proper rate limiting
let timerRequestsDisabled = false;
const TIMER_SYNC_INTERVAL = 30000; // 30 seconds between server syncs
let lastServerSync = {};

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Enforce past date restrictions on page load
    enforcePastDateRestrictions();
    
    // Progress slider event listener
    var slider = document.getElementById('progressSlider');
    if (slider) {
        slider.oninput = function() {
            document.getElementById('progressValue').textContent = this.value;
        }
    }
    
    // Initialize timers with server sync
    document.querySelectorAll('.task-card').forEach(item => {
        const taskId = item.dataset.taskId;
        const status = item.dataset.status;
        
        if (status === 'in_progress' || status === 'on_break') {
            // Start local countdown
            if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
            slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
            
            // DISABLED: Initial server sync to prevent 429 errors
        }
        
        // Set pause start time for tasks on break
        if (status === 'on_break') {
            const pauseStartTime = item.dataset.pauseStartTime;
            if (pauseStartTime && pauseStartTime !== '') {
                item.dataset.pauseStart = new Date(pauseStartTime).getTime();
            } else {
                const pauseTime = item.dataset.pauseTime;
                if (pauseTime) {
                    item.dataset.pauseStart = new Date(pauseTime).getTime();
                } else {
                    item.dataset.pauseStart = Date.now();
                }
            }
        }
    });
    
    // Initialize SLA Dashboard ONCE only
    refreshSLADashboard();
    
    // Periodic SLA dashboard refresh and timer sync
    setInterval(() => {
        refreshSLADashboard();
        
        // DISABLED: Timer sync to prevent 429 errors
    }, 120000); // 2 minutes
    
    // DISABLED - Page visibility refresh to prevent timer calls
    // Users can manually refresh if needed
    
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
                console.log('Failed to add task: ' + data.message);
            }
        })
        .catch(error => {
            console.log('Error adding task:', error.message);
        });
    });
    
    document.getElementById('updateProgressForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const taskId = document.getElementById('updateTaskId').value;
        const percentage = document.getElementById('selectedProgressPercentage').value;
        const status = percentage >= 100 ? 'completed' : 'in_progress';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: taskId, 
                progress: parseInt(percentage),
                status: status,
                reason: 'Progress updated via daily planner modal',
                csrf_token: csrfToken
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
                let message = 'Progress updated to ' + data.progress + '%';
                if (data.synced_to_tasks) {
                    message += ' (synced to Tasks module)';
                }
                if (data.progress >= 100) {
                    message += ' - Task completed!';
                } else {
                    message += ' - Task continues in progress';
                }
                
                // Manual refresh only - no automatic calls
                showNotification(message + ' (refresh manually if needed)', 'success');
            } else {
                console.log('Failed to update progress: ' + data.message);
            }
        })
        .catch(error => {
            console.log('Error updating progress:', error.message);
        });
    });
});

// CRITICAL: Make all functions globally accessible for HTML onclick attributes
function startTask(taskId) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (taskCard && !taskCard.dataset.startTime) {
        taskCard.dataset.startTime = Math.floor(Date.now() / 1000);
    }
    updateTaskUI(taskId, 'in_progress');
    startLocalTimer(taskId);
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

function pauseTask(taskId) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (taskCard) {
        taskCard.dataset.pauseStart = Date.now();
        taskCard.dataset.status = 'on_break';
    }
    
    // Stop the remaining timer and start break timer
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    
    updateTaskUI(taskId, 'on_break');
    startPauseTimer(taskId);
    
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

function resumeTask(taskId) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!taskCard) return;

    // Stop break timer and start remaining timer
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    
    taskCard.dataset.status = 'in_progress';
    delete taskCard.dataset.pauseStart;
    
    updateTaskUI(taskId, 'in_progress');
    startLocalTimer(taskId);

    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: parseInt(taskId) })
    }).catch(() => {});
}

// Timer management functions
function startLocalTimer(taskId) {
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(function() { updateLocalCountdown(taskId); }, 1000);
}

function startPauseTimer(taskId) {
    if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
    slaTimers[taskId] = setInterval(function() { updatePauseTimer(taskId); }, 1000);
}

function updatePauseTimer(taskId) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!taskCard || !taskCard.dataset.pauseStart) return;
    
    const pauseStart = parseInt(taskCard.dataset.pauseStart);
    const elapsed = Math.floor((Date.now() - pauseStart) / 1000);
    
    const hours = Math.floor(elapsed / 3600);
    const minutes = Math.floor((elapsed % 3600) / 60);
    const seconds = elapsed % 60;
    
    const timeDisplay = hours.toString().padStart(2, '0') + ':' + 
                       minutes.toString().padStart(2, '0') + ':' + 
                       seconds.toString().padStart(2, '0');
    
    const pauseTimerEl = taskCard.querySelector('#pause-timer-' + taskId);
    if (pauseTimerEl) {
        pauseTimerEl.textContent = timeDisplay;
    }
}

function updateLocalCountdown(taskId) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!taskCard) return;
    
    const status = taskCard.dataset.status;
    const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
    const startTime = parseInt(taskCard.dataset.startTime);
    const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
    
    if (status === 'in_progress' && startTime) {
        const currentTime = Math.floor(Date.now() / 1000);
        const sessionElapsed = currentTime - startTime;
        const totalUsed = activeSeconds + sessionElapsed;
        const remaining = Math.max(0, slaDuration - totalUsed);
        
        const hours = Math.floor(remaining / 3600);
        const minutes = Math.floor((remaining % 3600) / 60);
        const seconds = remaining % 60;
        
        const timeDisplay = hours.toString().padStart(2, '0') + ':' + 
                           minutes.toString().padStart(2, '0') + ':' + 
                           seconds.toString().padStart(2, '0');
        
        const countdownEl = taskCard.querySelector('#countdown-' + taskId + ' .countdown-display');
        if (countdownEl) {
            countdownEl.textContent = timeDisplay;
            
            // Remove existing warning classes
            countdownEl.classList.remove('countdown-display--warning', 'countdown-display--expired');
            
            if (remaining <= 0) {
                countdownEl.classList.add('countdown-display--expired');
                clearInterval(slaTimers[taskId]);
                showNotification('Task ' + taskId + ' SLA expired!', 'warning');
            } else if (remaining <= 300) { // 5 minutes warning
                countdownEl.classList.add('countdown-display--warning');
            }
        }
    }
}

function stopSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

(function() {
    // Store original functions
    const originalAlert = window.alert;
    const originalConsoleError = console.error;
    
    // Override alert to use silent notification instead
    window.alert = function(message) {
        console.log('[ALERT BLOCKED]:', message);
        // Use our notification system instead
        if (typeof showNotification === 'function') {
            showNotification(message, 'info');
        }
    };
    
    // Override console.error to use console.log instead
    console.error = function(...args) {
        console.log('[ERROR]:', ...args);
    };
    
    // Restore functions if needed for debugging (uncomment if needed)
    // window.restoreAlerts = () => { window.alert = originalAlert; console.error = originalConsoleError; };
})();

function updateTaskUI(taskId, newStatus, data = {}) {
    const taskCard = document.querySelector('[data-task-id="' + taskId + '"]');
    if (!taskCard) return;

    const statusBadge = taskCard.querySelector('#status-' + taskId);
    const actionsDiv = taskCard.querySelector('#actions-' + taskId);
    const countdownLabel = taskCard.querySelector('#countdown-' + taskId + ' .countdown-label');

    // Update task card dataset
    taskCard.dataset.status = newStatus;
    
    // Set start time for timer calculations
    if (newStatus === 'in_progress' && !taskCard.dataset.startTime) {
        taskCard.dataset.startTime = Date.now();
    }

    // Define all possible action buttons
    const buttons = {
        start: actionsDiv.querySelector('[onclick*="startTask"]'),
        pause: actionsDiv.querySelector('[onclick*="pauseTask"]'),
        resume: actionsDiv.querySelector('[onclick*="resumeTask"]'),
        update: actionsDiv.querySelector('[onclick*="openProgressModal"]'),
        postpone: actionsDiv.querySelector('[onclick*="postponeTask"]'),
        activate: actionsDiv.querySelector('[onclick*="activatePostponedTask"]')
    };

    // Hide all buttons initially
    Object.values(buttons).forEach(btn => {
        if (btn) btn.style.display = 'none';
    });

    // Reset loading states on all buttons
    Object.values(buttons).forEach(btn => {
        if (btn && typeof setButtonLoadingState === 'function') {
            setButtonLoadingState(btn, false);
        }
    });

    let statusText = newStatus.replace('_', ' ');
    let statusClass = 'badge--' + newStatus;
    let cardClass = 'task-card--' + newStatus;

    switch (newStatus) {
        case 'in_progress':
            statusText = 'In Progress';
            cardClass = 'task-card--active';
            if (countdownLabel) countdownLabel.textContent = 'Remaining';
            if (buttons.pause) buttons.pause.style.display = 'inline-block';
            if (buttons.update) buttons.update.style.display = 'inline-block';
            if (buttons.postpone) buttons.postpone.style.display = 'inline-block';
            
            // Remove pause timer elements
            const pauseTimer = taskCard.querySelector('#pause-timer-' + taskId);
            const pauseLabel = taskCard.querySelector('.pause-timer-label');
            if (pauseTimer) pauseTimer.remove();
            if (pauseLabel) pauseLabel.remove();
            break;

        case 'on_break':
            statusText = 'On Break';
            cardClass = 'task-card--break';
            if (countdownLabel) countdownLabel.textContent = 'Paused';
            
            // Always recreate buttons to ensure consistency
            actionsDiv.innerHTML = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})" title="Resume working on this task">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')" title="Update task completion progress">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
                <button class="btn btn--sm btn--secondary" onclick="postponeTask(${taskId})" title="Postpone task to another date">
                    <i class="bi bi-calendar-plus"></i> Postpone
                </button>
            `;

            // Add pause timer if it doesn't exist
            const countdownDiv = taskCard.querySelector('#countdown-' + taskId);
            if (countdownDiv && !countdownDiv.querySelector('#pause-timer-' + taskId)) {
                countdownDiv.insertAdjacentHTML('beforeend', 
                    '<div class="pause-timer" id="pause-timer-' + taskId + '">00:00:00</div>' +
                    '<div class="pause-timer-label">Break Time</div>'
                );
            }
            break;

        case 'not_started':
        case 'assigned':
            statusText = 'Not Started';
            statusClass = 'badge--not_started';
            if (buttons.start) buttons.start.style.display = 'inline-block';
            if (buttons.postpone) buttons.postpone.style.display = 'inline-block';
            break;

        case 'completed':
            statusText = 'Completed';
            stopSLATimer(taskId);
            actionsDiv.innerHTML = '<span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>';
            break;

        case 'postponed':
            statusText = 'Postponed';
            stopSLATimer(taskId);
            if (buttons.activate) buttons.activate.style.display = 'inline-block';
            if (buttons.postpone) {
                buttons.postpone.style.display = 'inline-block';
                buttons.postpone.innerHTML = '<i class="bi bi-calendar-plus"></i> Re-postpone';
            }
            break;

        default:
            actionsDiv.innerHTML = '<span class="badge badge--muted">' + statusText + '</span>';
            break;
    }

    // Update status badge
    if (statusBadge) {
        statusBadge.textContent = statusText.charAt(0).toUpperCase() + statusText.slice(1);
        statusBadge.className = 'badge ' + statusClass;
    }

    // Update task card class
    taskCard.className = taskCard.className.replace(/task-card--\w+/g, '') + ' ' + cardClass;
}

// Utility function for button loading states
function setButtonLoadingState(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-arrow-clockwise spinner"></i> Loading...';
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }
}

// Initialize timers on page load
document.addEventListener('DOMContentLoaded', function() {
    // Start timers for tasks that are already in progress
    const inProgressTasks = document.querySelectorAll('[data-status="in_progress"]');
    inProgressTasks.forEach(function(taskCard) {
        const taskId = taskCard.dataset.taskId;
        if (taskId) {
            startLocalTimer(taskId);
        }
    });
    
    // Start pause timers for tasks on break
    const pausedTasks = document.querySelectorAll('[data-status="on_break"]');
    pausedTasks.forEach(function(taskCard) {
        const taskId = taskCard.dataset.taskId;
        if (taskId && taskCard.dataset.pauseStart) {
            startPauseTimer(taskId);
        }
    });
});
</script>

<?php renderModalJS(); ?>
<script src="/ergon/assets/js/unified-daily-planner.js"></script>
<script src="/ergon/assets/js/planner-access-control.js"></script>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spinner { animation: spin 1s linear infinite; }
