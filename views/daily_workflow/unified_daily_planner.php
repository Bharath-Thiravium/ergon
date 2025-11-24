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
<link rel="stylesheet" href="/ergon/assets/css/daily-planner.css">
<link rel="stylesheet" href="/ergon/assets/css/daily-planner-modern.css">
<link rel="stylesheet" href="/ergon/assets/css/planner-access-control.css">
<link rel="stylesheet" href="/ergon/assets/css/production-fixes.css">
<link rel="stylesheet" href="/ergon/assets/css/daily-planner-history-fix.css">

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

/* 📜 Historical view styling */
.task-card--historical {
    opacity: 0.8;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-left: 4px solid #6c757d;
    border: 1px solid #dee2e6;
}

.task-card--historical .task-card__actions {
    /* Allow selective interactions for historical view */
}

.task-card--historical .badge--muted {
    background: #6c757d;
    color: white;
}

/* 🎯 Execution mode styling */
.execution-mode .task-card {
    /*border-left: 4px solid #28a745;*/
    background: linear-gradient(135deg, #ffffff, #f8fff9);
}

/* 📅 Planning mode styling */
.planning-mode .task-card {
    border-left: 4px solid #17a2b8;
    background: linear-gradient(135deg, #ffffff, #f0f9ff);
}

/* Visual distinction for different modes */
.historical-view {
    filter: grayscale(20%);
}

.execution-mode {
    /* Full color and interactivity */
}

.planning-mode {
    opacity: 0.9;
}

/* Disabled button styling for past/future dates */
.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    color: #6c757d !important;
}

.btn:disabled:hover {
    opacity: 0.5;
    transform: none;
}

/* Past date specific styling */
.task-card[data-is-past="true"] .btn:not(.btn--secondary):not([onclick*="showTaskHistory"]):not([onclick*="showReadOnlyProgress"]) {
    opacity: 0.4;
    pointer-events: none;
}

/* Read-only progress display */
.progress-display {
    text-align: center;
    padding: 1rem;
}

.progress-display .progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.progress-display .progress-label {
    font-weight: 600;
    color: #374151;
}

.progress-display .progress-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #059669;
}

.task-card--postponed {
    opacity: 0.7;
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
}

.task-card--postponed .task-card__actions {
    pointer-events: auto;
}

.task-card--postponed .btn:not(.btn--secondary):not(.btn--success) {
    opacity: 0.5;
    pointer-events: none;
}

.task-card--postponed-active {
    opacity: 1;
    background: #ecfdf5;
    border-left: 4px solid #10b981;
}

.task-card--postponed-active .btn {
    opacity: 1;
    pointer-events: auto;
}

.task-card__created-info {
    margin-bottom: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 4px;
    border-left: 3px solid #3b82f6;
}

.task-card__rollover-info {
    background: rgba(16, 185, 129, 0.1) !important;
    border-left-color: #10b981 !important;
}

.task-card__rollover-info .text-info {
    color: #059669 !important;
    font-weight: 600;
}

/* Rollover task styling */
.task-card[data-rollover="true"] {
    border-left: 4px solid #10b981;
    background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
}

.task-card[data-rollover="true"]:before {
    content: "🔄";
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.8em;
    opacity: 0.7;
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

.pause-timer {
    font-size: 1.2rem;
    font-weight: 600;
    color: #f59e0b;
    font-family: 'Courier New', monospace;
    margin-top: 0.25rem;
}

.pause-timer-label {
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.1rem;
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

/* Button tooltip fixes for Daily Planner */
.btn:hover::after {
    content: none;
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: rgba(15, 23, 42, 0.95);
    color: white;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    z-index: 99999 !important;
    pointer-events: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.btn:hover::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: rgba(15, 23, 42, 0.95);
    z-index: 99999 !important;
}

.btn {
    position: relative;
}

/* Ensure task cards don't clip tooltips */
.task-card {
    overflow: visible !important;
}

.task-card__actions {
    overflow: visible !important;
    position: relative;
    z-index: 1;
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

/* History Info Modal Styles */
.history-info-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.history-info-modal .modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.history-info-modal .modal-content {
    position: relative;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.history-info-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
    border-radius: 8px 8px 0 0;
}

.history-info-modal .modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.history-info-modal .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.history-info-modal .modal-close:hover {
    color: #1f2937;
    background: #f3f4f6;
}

.history-info-modal .modal-body {
    padding: 24px;
}

.history-info-modal .modal-body ul {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.history-info-modal .modal-body li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #374151;
}

.history-info-modal .modal-body li i {
    color: #6b7280;
    width: 16px;
}

.history-info-modal .modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

/* Date Selector Group Styles */
.date-selector-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin: 0;
}

.loading-indicator {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: normal;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-actions {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .date-selector-group {
        align-items: center;
    }
    
    .history-info-modal .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .history-info-modal .modal-actions {
        flex-direction: column;
    }
}

/* Task History Timeline Styles */
.task-history-timeline {
    position: relative;
    padding-left: 30px;
}

.task-history-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.history-timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 8px;
    height: 8px;
    background: #3b82f6;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #3b82f6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 12px 16px;
    border-radius: 8px;
    border-left: 3px solid #3b82f6;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.timeline-action {
    font-weight: 600;
    color: #1f2937;
}

.timeline-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.timeline-progress {
    font-size: 0.875rem;
    color: #059669;
    font-weight: 600;
    margin-bottom: 4px;
}

.timeline-notes {
    font-size: 0.875rem;
    color: #4b5563;
    font-style: italic;
}

.no-history, .error-message {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

.no-history i, .error-message i {
    font-size: 2rem;
    margin-bottom: 16px;
    display: block;
}

.error-message {
    color: #dc2626;
}
</style>

<script>
let timers = {};
let slaTimers = {};
var currentTaskId;

// Define functions early to prevent undefined errors
function startTask(taskId) {
    return window.startTask ? window.startTask(taskId) : console.error('startTask not ready');
}

function pauseTask(taskId) {
    return window.pauseTask ? window.pauseTask(taskId) : console.error('pauseTask not ready');
}

function resumeTask(taskId) {
    return window.resumeTask ? window.resumeTask(taskId) : console.error('resumeTask not ready');
}

function postponeTask(taskId) {
    return window.postponeTask ? window.postponeTask(taskId) : console.error('postponeTask not ready');
}

function openProgressModal(taskId, progress, status) {
    return window.openProgressModal ? window.openProgressModal(taskId, progress, status) : console.error('openProgressModal not ready');
}

// SLA Timer Functions
function startSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
    }
    
    // Start local countdown that updates every second
    slaTimers[taskId] = setInterval(() => {
        updateLocalCountdown(taskId);
    }, 1000);
    
    // DISABLED - No initial server sync to prevent 429 errors
}

function stopSLATimer(taskId) {
    if (slaTimers[taskId]) {
        clearInterval(slaTimers[taskId]);
        delete slaTimers[taskId];
    }
}

// Enhanced rate limiting for server calls
let lastTimerCall = {};
let taskTimerData = {}; // Store timer data locally
let globalTimerQueue = []; // Queue for timer requests
let isProcessingQueue = false;

// Aggressive rate limiting: 15 seconds between server calls per task, max 2 concurrent requests
const TIMER_THROTTLE = 15000; // 15 seconds between server calls per task
const MAX_CONCURRENT_REQUESTS = 2;
let activeRequests = 0;

// Local countdown update (runs every second)
function updateLocalCountdown(taskId) {
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskCard) return;
    
    const status = taskCard.dataset.status;
    const display = document.querySelector(`#countdown-${taskId} .countdown-display`);
    const pauseTimer = document.querySelector(`#pause-timer-${taskId}`);
    
    if (!display) return;
    
    const now = Date.now();
    
    if (status === 'in_progress') {
        // Update SLA countdown
        const startTime = parseInt(taskCard.dataset.startTime) * 1000;
        const slaDuration = parseInt(taskCard.dataset.slaDuration) * 1000;
        const activeSeconds = parseInt(taskCard.dataset.activeSeconds) || 0;
        
        if (startTime > 0) {
            const elapsed = Math.floor((now - startTime) / 1000);
            const totalUsed = activeSeconds + elapsed;
            const remaining = Math.max(0, parseInt(taskCard.dataset.slaDuration) - totalUsed);
            
            if (remaining <= 0) {
                const overdue = totalUsed - parseInt(taskCard.dataset.slaDuration);
                const hours = Math.floor(overdue / 3600);
                const minutes = Math.floor((overdue % 3600) / 60);
                const seconds = overdue % 60;
                display.textContent = `OVERDUE: ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                display.className = 'countdown-display countdown-display--expired';
                
                // Update countdown label to show overdue timer
                const label = document.querySelector(`#countdown-${taskId} .countdown-label`);
                if (label) {
                    label.textContent = 'Overdue Timer';
                }
            } else {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const secs = remaining % 60;
                display.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                display.className = remaining <= 600 ? 'countdown-display countdown-display--warning' : 'countdown-display';
            }
        }
    } else if (status === 'on_break' && pauseTimer) {
        // Update pause timer - use server-side pause start time if available
        let pauseStart;
        const pauseStartTime = taskCard.dataset.pauseStartTime;
        if (pauseStartTime && pauseStartTime !== '') {
            pauseStart = new Date(pauseStartTime).getTime();
        } else {
            pauseStart = parseInt(taskCard.dataset.pauseStart) || now;
        }
        
        const pauseElapsed = Math.floor((now - pauseStart) / 1000);
        const hours = Math.floor(pauseElapsed / 3600);
        const minutes = Math.floor((pauseElapsed % 3600) / 60);
        const seconds = pauseElapsed % 60;
        pauseTimer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    
    // Sync with server every 30 seconds for accuracy
    const currentTime = Date.now();
    if (!lastServerSync[taskId] || (currentTime - lastServerSync[taskId]) > TIMER_SYNC_INTERVAL) {
        lastServerSync[taskId] = currentTime;
        syncTimerWithServer(taskId);
    }
}

function syncTimerWithServer(taskId) {
    return fetch(`/ergon/api/daily_planner_workflow.php?action=timer&task_id=${taskId}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 429) {
                console.log(`Rate limited for task ${taskId}`);
                return null;
            }
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data || !data.success) return;
        
        // Update local task data with server values
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (taskCard) {
            taskCard.dataset.activeSeconds = data.active_seconds || 0;
            taskCard.dataset.pauseDuration = data.pause_duration || 0;
            
            if (data.pause_start_time) {
                taskCard.dataset.pauseStartTime = data.pause_start_time;
            }
            
            // Update timing display
            updateTaskTiming(taskId, data);
        }
    })
    .catch(error => {
        console.log(`Timer sync failed for task ${taskId}:`, error.message);
    });
}

function updateSLADisplay(taskId) {
    const currentTime = Date.now();
    lastTimerCall[taskId] = currentTime;
    activeRequests++;
    
    return fetch(`/ergon/api/daily_planner_workflow.php?action=timer&task_id=${taskId}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 429) {
                console.log(`Rate limited for task ${taskId}, will retry in 15 seconds`);
                // Schedule retry after rate limit period
                setTimeout(() => queueTimerRequest(taskId), 15000);
                return null;
            }
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (!data) return; // Skip if rate limited
        
        if (data.success) {
            // Update local task data for accurate countdown
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                taskCard.dataset.activeSeconds = data.active_seconds || 0;
                if (data.pause_start) {
                    taskCard.dataset.pauseStart = data.pause_start * 1000;
                }
            }
            
            // Update individual task timing display
            updateTaskTiming(taskId, data);
            
            // Update countdown label based on status
            const label = document.querySelector(`#countdown-${taskId} .countdown-label`);
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
        // Handle rate limiting gracefully
        if (error.message.includes('429')) {
            console.log(`Rate limited for task ${taskId}, scheduling retry`);
            setTimeout(() => queueTimerRequest(taskId), 20000);
        } else {
            console.log(`Timer unavailable for task ${taskId}:`, error.message);
        }
    })
    .finally(() => {
        activeRequests--;
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
            // Use pause duration from API (includes live calculation)
            timePaused.textContent = formatTimeHours(data.pause_duration || 0);
        }
    }
}

function formatTime(seconds) {
    const totalSeconds = Math.floor(Math.abs(seconds || 0));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;
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
let slaUpdateTimeout = null;

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
        console.error('SLA Dashboard error:', error);
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
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8';
    notification.innerHTML = `<div style="position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:10px 20px;border-radius:5px;z-index:9999;">${message}</div>`;
    document.body.appendChild(notification);
    setTimeout(() => document.body.removeChild(notification), 3000);
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
                alert('Progress updated to ' + actualProgress + '% - Task will continue in progress');
            } else {
                alert('Task completed successfully!');
                stopTimer(currentTaskId);
            }
        } else {
            console.error('API Error:', data);
            alert('Error updating progress: ' + (data.message || 'Failed to update progress'));
        }
    })
    .catch(error => {
        console.error('Progress update error:', error);
        alert('Error updating progress: ' + error.message);
    });
}

function changeDate(date) {
    // Validate date format
    if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
        alert('Invalid date format');
        document.getElementById('dateSelector').value = '<?= $selected_date ?>';
        return;
    }
    
    // Allow future dates for planning (up to 30 days ahead)
    const today = new Date().toISOString().split('T')[0];
    const maxFutureDate = new Date();
    maxFutureDate.setDate(maxFutureDate.getDate() + 30);
    const maxDateStr = maxFutureDate.toISOString().split('T')[0];
    
    if (date > maxDateStr) {
        alert('Cannot view dates more than 30 days in the future');
        document.getElementById('dateSelector').value = '<?= $selected_date ?>';
        return;
    }
    
    // Check minimum date (90 days in the past)
    const minDate = new Date();
    minDate.setDate(minDate.getDate() - 90);
    const minDateStr = minDate.toISOString().split('T')[0];
    
    if (date < minDateStr) {
        alert('Cannot view dates more than 90 days in the past');
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
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Activate postponed task error:', error);
        alert('Network error. Please refresh the page.');
    });
}

// Define startTask function globally
window.startTask = function(taskId) {
    if (!taskId) {
        alert('Error: Task ID is missing');
        return;
    }
    
    // Check if task is postponed (but not on target date)
    const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
    if (taskCard && taskCard.dataset.status === 'postponed' && !taskCard.classList.contains('task-card--postponed-active')) {
        alert('Cannot start a postponed task. Please wait until the postponed date.');
        return;
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=start', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ 
            task_id: parseInt(taskId)
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update task card with start time for accurate countdown
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard && data.start_time) {
                taskCard.dataset.startTime = data.start_time;
            }
            updateTaskUI(taskId, 'start');
            // Start local timer with server sync
            if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
            slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
            
            // Update task card with server data
            if (data.start_time) {
                taskCard.dataset.startTime = Math.floor(new Date(data.start_time).getTime() / 1000);
            }
            if (data.sla_end_time) {
                taskCard.dataset.slaEndTime = Math.floor(new Date(data.sla_end_time).getTime() / 1000);
            }
            
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

// Define pauseTask function globally
window.pauseTask = function(taskId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ 
            task_id: parseInt(taskId)
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateTaskUI(taskId, 'pause');
            // Store pause start time from server response
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                const pauseStartTime = data.pause_start ? data.pause_start * 1000 : Date.now();
                taskCard.dataset.pauseStart = pauseStartTime;
                // Also store the server timestamp for accuracy
                if (data.pause_start_time) {
                    taskCard.dataset.pauseStartTime = data.pause_start_time;
                }
            }
            // Keep local timer running for break duration
            if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
            slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
            showNotification('Task paused - break timer started', 'info');
        } else {
            showNotification('Failed to pause task: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Pause task error:', error);
        showNotification('Network error occurred', 'error');
    });
};

// Also define as regular function for compatibility
function pauseTask(taskId) {
    return window.pauseTask(taskId);
}

// Define resumeTask function globally  
window.resumeTask = function(taskId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ 
            task_id: parseInt(taskId)
        })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update task card with resume time and clear pause data
            const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
            if (taskCard) {
                if (data.resume_time) {
                    taskCard.dataset.startTime = data.resume_time;
                }
                // Clear pause-related data
                delete taskCard.dataset.pauseStart;
                delete taskCard.dataset.pauseStartTime;
                taskCard.dataset.pauseTime = '';
            }
            updateTaskUI(taskId, 'resume');
            // Start local timer and sync with server
            if (slaTimers[taskId]) clearInterval(slaTimers[taskId]);
            slaTimers[taskId] = setInterval(() => updateLocalCountdown(taskId), 1000);
            
            // Update task card with resume time
            if (data.start_time) {
                taskCard.dataset.startTime = Math.floor(new Date(data.start_time).getTime() / 1000);
            }
            
            showNotification('Task resumed - break time saved', 'success');
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
        alert('Please select a date');
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
            
        } else {
            alert(data.message || 'Failed to postpone task');
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
        case 'in_progress':
            newStatus = 'in_progress';
            statusBadge.textContent = 'In Progress';
            statusBadge.className = 'badge badge--in_progress';
            taskCard.className = 'task-card task-card--active';
            delete taskCard.dataset.pauseStart; // Clear pause start time
            delete taskCard.dataset.pauseTime;
            // Remove pause timer
            const pauseTimer = document.querySelector(`#pause-timer-${taskId}`);
            const pauseLabel = document.querySelector(`#countdown-${taskId} .pause-timer-label`);
            if (pauseTimer) pauseTimer.remove();
            if (pauseLabel) pauseLabel.remove();
            
            // Get current progress for button display
            const currentProgress = data.percentage || 0;
            newActions = `
                <button class="btn btn--sm btn--warning" onclick="pauseTask(${taskId})">
                    <i class="bi bi-pause"></i> Break
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, ${currentProgress}, 'in_progress')">
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
            // Add pause timer to countdown section
            const countdownTimer = document.querySelector(`#countdown-${taskId}`);
            if (countdownTimer && !countdownTimer.querySelector('.pause-timer')) {
                countdownTimer.innerHTML += `
                    <div class="pause-timer" id="pause-timer-${taskId}">00:00:00</div>
                    <div class="pause-timer-label">Break Time</div>
                `;
            }
            newActions = `
                <button class="btn btn--sm btn--success" onclick="resumeTask(${taskId})">
                    <i class="bi bi-play"></i> Resume
                </button>
                <button class="btn btn--sm btn--primary" onclick="openProgressModal(${taskId}, 0, 'on_break')">
                    <i class="bi bi-percent"></i> Update Progress
                </button>
            `;
            // Keep timer running to update pause duration
            startSLATimer(taskId);
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
            
            // Initial server sync for active tasks
            if (!timerRequestsDisabled) {
                syncTimerWithServer(taskId);
            }
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
        
        // Sync active timers with server every 2 minutes
        if (!timerRequestsDisabled) {
            document.querySelectorAll('.task-card').forEach(item => {
                const taskId = item.dataset.taskId;
                const status = item.dataset.status;
                if (status === 'in_progress' || status === 'on_break') {
                    syncTimerWithServer(taskId);
                }
            });
        }
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
                let message = `Progress updated to ${data.progress}%`;
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
                alert('Failed to update progress: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating progress');
        });
    });
    

});

// Ensure all timer functions are globally accessible
window.startTask = window.startTask || function(taskId) { return startTask(taskId); };
window.pauseTask = window.pauseTask || function(taskId) { return pauseTask(taskId); };
window.resumeTask = window.resumeTask || function(taskId) { return resumeTask(taskId); };
window.postponeTask = window.postponeTask || function(taskId) { return postponeTask(taskId); };
window.openProgressModal = window.openProgressModal || function(taskId, progress, status) { return openProgressModal(taskId, progress, status); };

// Additional compatibility assignments
if (typeof startTask === 'function') window.startTask = startTask;
if (typeof pauseTask === 'function') window.pauseTask = pauseTask;
if (typeof resumeTask === 'function') window.resumeTask = resumeTask;
if (typeof postponeTask === 'function') window.postponeTask = postponeTask;
if (typeof openProgressModal === 'function') window.openProgressModal = openProgressModal;
</script>

<?php renderModalJS(); ?>
<script src="/ergon/assets/js/planner-access-control.js"></script>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>