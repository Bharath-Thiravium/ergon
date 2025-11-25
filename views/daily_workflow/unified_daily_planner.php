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
?>"
data-selected-date="<?= htmlspecialchars($selected_date, ENT_QUOTES, 'UTF-8') ?>"
data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '1', ENT_QUOTES, 'UTF-8') ?>"
>
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
                                <?php
                                // Calculate overdue status
                                $isOverdue = $activeSeconds > $slaDuration;
                                $overdueSeconds = $isOverdue ? $activeSeconds - $slaDuration : 0;
                                $slaTimeDisplay = sprintf('%02d:%02d:%02d', 
                                    (int)floor($slaDuration / 3600), 
                                    (int)floor(($slaDuration % 3600) / 60), 
                                    (int)floor($slaDuration % 60)
                                );
                                ?>
                                <div class="task-card__timing <?= $isOverdue ? 'timing--overdue' : '' ?>" id="timing-<?= $taskId ?>">
                                    <div class="countdown-timer" id="countdown-<?= $taskId ?>">
                                        <div class="countdown-display <?= $isOverdue ? 'countdown-display--overdue' : '' ?>"><?= $timeDisplay ?></div>
                                        <div class="countdown-label"><?= $status === 'in_progress' ? 'Remaining' : ($status === 'on_break' ? 'Paused' : 'SLA Time') ?></div>
                                    </div>
                                    <div class="timing-info sla-time-info">
                                        <div class="sla-time-display"><?= $slaTimeDisplay ?></div>
                                        <div class="sla-time-label">SLA Time</div>
                                    </div>
                                    <div class="timing-info time-used-info">
                                        <div class="time-used-display" id="time-used-<?= $taskId ?>"><?= sprintf('%02d:%02d:%02d', floor($activeSeconds / 3600), floor(($activeSeconds % 3600) / 60), $activeSeconds % 60) ?></div>
                                        <div class="time-used-label">Time Used</div>
                                    </div>
                                    <div class="timing-info break-timer-info" id="break-timer-<?= $taskId ?>" style="<?= $status !== 'on_break' ? 'display: none;' : '' ?>">
                                        <div class="pause-timer" id="pause-timer-<?= $taskId ?>"><?= sprintf('%02d:%02d:%02d', floor($pauseSeconds / 3600), floor(($pauseSeconds % 3600) / 60), $pauseSeconds % 60) ?></div>
                                        <div class="pause-timer-label">Break Time</div>
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
                    <span class="metric-label">SLA Time:</span>
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

<?php renderModalJS(); ?>
<script src="/ergon/assets/js/unified-daily-planner.js"></script>
<script src="/ergon/assets/js/planner-access-control.js"></script>
<script src="/ergon/fix_timer_display.js"></script>

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
