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
ob_start();
?>
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
<link rel="stylesheet" href="/ergon/assets/css/unified-daily-planner.css">
<link rel="stylesheet" href="/ergon/assets/css/task-timing.css">
<link rel="stylesheet" href="/ergon/assets/css/sla-dashboard-improvements.css">
<link rel="stylesheet" href="/ergon/assets/css/task-progress-enhanced.css">

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
    data-user-id="<?= htmlspecialchars($_SESSION['user_id'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">
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
                    usort($planned_tasks, function ($a, $b) {
                        $statusOrder = ['overdue' => 1, 'in_progress' => 2, 'on_break' => 3, 'assigned' => 4, 'not_started' => 4, 'completed' => 5, 'cancelled' => 6, 'suspended' => 6];
                        return ($statusOrder[$a['status']] ?? 3) - ($statusOrder[$b['status']] ?? 3);
                    });

                    foreach ($planned_tasks as $task):
                        $status = $task['status'] ?? 'not_started';
                        $taskId = $task['id'];
                        // BUSINESS CHANGE: Default SLA changed from 1.0 to 0.25 hours for better granularity
                        $slaHours = (float)($task['sla_hours'] ?? DEFAULT_SLA_HOURS);
                        // Prefer sla_duration_seconds (written at start), fall back to sla_hours
                        $slaDurationFromDb = (int)($task['sla_duration_seconds'] ?? 0);
                        $slaDuration = $slaDurationFromDb > 0
                            ? $slaDurationFromDb
                            : (int)(max(DEFAULT_SLA_HOURS, $slaHours) * 3600);
                        $startTime = $task['start_time'] ?? null;
                        $startTimestamp = $startTime ? strtotime($startTime) : 0;
                        $postponeContext = $task['postpone_context'] ?? 'normal';

                        // Stopwatch: count up from 0 (elapsed active time)
                        $activeSeconds   = (int)($task['active_seconds'] ?? $task['time_used'] ?? 0);
                        $resumeTime      = $task['resume_time'] ?? $task['last_resume_time'] ?? null;
                        $elapsedSeconds  = $activeSeconds;
                        if ($startTimestamp > 0 && in_array($status, ['in_progress', 'overdue'])) {
                            $refTime        = $resumeTime ? strtotime($resumeTime) : $startTimestamp;
                            $elapsedSeconds = $activeSeconds + max(0, time() - $refTime);
                        }
                        $timeDisplay = sprintf(
                            '%02d:%02d:%02d',
                            floor($elapsedSeconds / 3600),
                            floor(($elapsedSeconds % 3600) / 60),
                            $elapsedSeconds % 60
                        );

                        $cssClass = '';
                        if ($status === 'in_progress' || $status === 'overdue') $cssClass = 'task-card--active';
                        elseif ($status === 'on_break') $cssClass = 'task-card--break';
                        elseif ($status === 'completed') $cssClass = 'task-card--completed';
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
                        <?php
                        // Fix timestamp initialization with ISO format
                        $currentStartTime = $task['start_time'] ? date('c', strtotime($task['start_time'])) : '';
                        $currentResumeTime = $task['resume_time'] ? date('c', strtotime($task['resume_time'])) : '';
                        $currentPauseTime = $task['pause_start_time'] ? date('c', strtotime($task['pause_start_time'])) : '';
                        $currentSlaEndTime = $task['sla_end_time'] ? date('c', strtotime($task['sla_end_time'])) : '';
                        // ms-precision timestamps for the JS timer
                        $startTsMs      = (int)($task['start_ts_ms'] ?? 0);
                        $pauseStartTsMs = (int)($task['pause_start_ts_ms'] ?? 0);
                        // Elapsed active ms: prefer paused_accum_ms, fall back to active_seconds
                        $pausedAccumMs  = (int)($task['paused_accum_ms'] ?? 0);
                        if ($pausedAccumMs <= 0 && isset($task['active_seconds'])) {
                            $pausedAccumMs = (int)$task['active_seconds'] * 1000;
                        }
                        ?>
                        <div class="task-card <?= $cssClass ?> <?= $historicalClass ?> <?= $modeClass ?>"
                            data-task-id="<?= $taskId ?>"
                            data-original-task-id="<?= $task['task_id'] ?? '' ?>"
                            data-sla-duration="<?= $slaDuration ?>"
                            data-sla-hours="<?= $slaHours ?>"
                            data-start-time="<?= $currentStartTime ?>"
                            data-start-ts-ms="<?= $startTsMs ?>"
                            data-resume-time="<?= $currentResumeTime ?>"
                            data-sla-end-time="<?= $currentSlaEndTime ?>"
                            data-status="<?= $status ?>"
                            data-task-source="<?= $taskSource ?>"
                            data-pause-start-time="<?= $currentPauseTime ?>"
                            data-pause-start-ts-ms="<?= $pauseStartTsMs ?>"
                            data-active-seconds="<?= (int)($task['active_seconds'] ?? 0) ?>"
                            data-paused-accum-ms="<?= $pausedAccumMs ?>"
                            data-pause-duration="<?= (int)($task['pause_duration'] ?? 0) ?>"
                            data-completed-percentage="<?= (int)($task['completed_percentage'] ?? 0) ?>"
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
                                        <?php if (($task['source_field'] ?? '') === 'recurring_daily'): ?>
                                            <span class="badge badge--info" title="This task was created automatically from a daily recurring planner task">
                                                <i class="bi bi-arrow-repeat"></i> Auto Daily
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge badge--<?= $task['priority'] ?? 'medium' ?>"><?= ucfirst($task['priority'] ?? 'medium') ?></span>
                                        <span class="badge <?= $status === 'overdue' ? 'badge--danger' : 'badge--' . $status ?>" id="status-<?= $taskId ?>">
                                            <?= $status === 'overdue' ? 'Overdue' : ucfirst(str_replace('_', ' ', $status)) ?>
                                        </span>
                                    </div>
                                </div>

                                <p class="task-card__description"><?= htmlspecialchars($task['description'] ?? 'No description') ?></p>
                                <?php if (($task['source_field'] ?? '') === 'recurring_daily'): ?>
                                    <div class="task-card__meta" style="margin-bottom: 0.75rem;">
                                        <small style="display:inline-flex; align-items:center; gap:0.35rem; color:#2563eb; font-weight:600;">
                                            <i class="bi bi-arrow-repeat"></i> This task is auto-created from your daily recurring planner setup.
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <?php
                                $completedPercentage = (int)($task['completed_percentage'] ?? 0);
                                if ($completedPercentage > 0 || $status === 'in_progress' || $status === 'overdue'):
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

                                // Static pause time only - no live calculation
                                $totalPauseTime = (int)($task['pause_duration'] ?? 0);

                                // Apply formula: Overdue = Duration Exceeding the SLA Time
                                $isOverdue = $activeSeconds > $slaDuration;
                                $overdueSeconds = $isOverdue ? $activeSeconds - $slaDuration : 0;
                                // Apply formula: Time Used = Overdue + SLA Time (when overdue)
                                // Solution 2: Fix PHP Time Used Formula - Time Used = Active Time + Break Time
                                $timeUsedSeconds = $activeSeconds + $totalPauseTime;
                                $remainingSeconds = max(0, $slaDuration - $activeSeconds);

                                $slaTimeDisplay = sprintf(
                                    '%02d:%02d:%02d',
                                    (int)floor($slaDuration / 3600),
                                    (int)floor(($slaDuration % 3600) / 60),
                                    (int)floor($slaDuration % 60)
                                );
                                ?>
                                <div class="task-timing-grid" id="timing-<?= $taskId ?>">
                                    <div class="timing-card timing-card--primary">
                                        <div class="timing-value" id="countdown-<?= $taskId ?>">
                                            <div class="countdown-display"><?= $timeDisplay ?></div>
                                        </div>
                                        <div class="timing-label"><?= $status === 'overdue' ? 'Overdue' : ($status === 'in_progress' ? 'Elapsed' : ($status === 'on_break' ? 'Paused At' : 'Elapsed')) ?></div>
                                    </div>
                                    <div class="timing-card">
                                        <div class="timing-value"><?= $slaTimeDisplay ?></div>
                                        <div class="timing-label">SLA Time</div>
                                    </div>
                                    <div class="timing-card">
                                        <div class="timing-value" id="time-used-<?= $taskId ?>"><?= sprintf('%02d:%02d:%02d', floor($timeUsedSeconds / 3600), floor(($timeUsedSeconds % 3600) / 60), $timeUsedSeconds % 60) ?></div>
                                        <div class="timing-label">Time Used</div>
                                    </div>
                                    <div class="timing-card <?= ($slaDuration > 0 && (int)round(($pausedAccumMs > 0 ? $pausedAccumMs : $activeSeconds * 1000) / 1000) >= $slaDuration) ? 'timing-card--overdue' : '' ?>">
                                        <div class="timing-value" id="remaining-sla-<?= $taskId ?>"><?= sprintf('%02d:%02d:%02d', floor(max(0,$slaDuration-(int)round(($pausedAccumMs>0?$pausedAccumMs:$activeSeconds*1000)/1000))/3600), floor((max(0,$slaDuration-(int)round(($pausedAccumMs>0?$pausedAccumMs:$activeSeconds*1000)/1000))%3600)/60), max(0,$slaDuration-(int)round(($pausedAccumMs>0?$pausedAccumMs:$activeSeconds*1000)/1000))%60) ?></div>
                                        <div class="timing-label">Remaining</div>
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
                                            <button class="btn btn--sm btn--info" onclick="showReadOnlyProgress(<?= $taskId ?>, <?= (int)($task['completed_percentage'] ?? 100) ?>)" title="View completion details (read-only)">
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
                                                <button type="button" class="btn btn--sm btn--success" onclick="activatePostponedTask(<?= $taskId ?>, event)" title="Start this postponed task">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                            <?php else: ?>
                                                <span class="badge badge--warning"><i class="bi bi-calendar-plus"></i> Postponed</span>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>, event)" title="Re-postpone to another date">
                                                <i class="bi bi-calendar-plus"></i> Re-postpone
                                            </button>
                                        <?php elseif ($status === 'not_started' || $status === 'assigned'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button type="button" class="btn btn--sm btn--success" onclick="startTask(<?= $taskId ?>, event)" title="Start working on this task">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                                <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>, event)" title="Postpone task to another date">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--success" disabled title="🔒 Start disabled for past/future dates">
                                                    <i class="bi bi-play"></i> Start
                                                </button>
                                                <button class="btn btn--sm btn--secondary" disabled title="🔒 Postpone disabled for past/future dates">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'in_progress' || $status === 'overdue'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button type="button" class="btn btn--sm btn--warning" onclick="pauseTask(<?= $taskId ?>, event)" title="Take a break from this task">
                                                    <i class="bi bi-pause"></i> Break
                                                </button>
                                                <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= (int)($task['completed_percentage'] ?? 0) ?>, '<?= $status ?>')" title="Update task completion progress">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                                <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>, event)" title="Postpone task to another date">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--warning" disabled title="🔒 Pause disabled for past/future dates">
                                                    <i class="bi bi-pause"></i> Break
                                                </button>
                                                <button class="btn btn--sm btn--primary" disabled title="🔒 Progress updates disabled for past/future dates">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                                <button class="btn btn--sm btn--secondary" disabled title="🔒 Postpone disabled for past/future dates">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'on_break'): ?>
                                            <?php if ($isCurrentDate): ?>
                                                <button type="button" class="btn btn--sm btn--success" onclick="resumeTask(<?= $taskId ?>, event)" title="Resume working on this task">
                                                    <i class="bi bi-play"></i> Resume
                                                </button>
                                                <button class="btn btn--sm btn--primary" onclick="openProgressModal(<?= $taskId ?>, <?= (int)($task['completed_percentage'] ?? 0) ?>, '<?= $status ?>')" title="Update task completion progress">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                                <button type="button" class="btn btn--sm btn--secondary" onclick="postponeTask(<?= $taskId ?>, event)" title="Postpone task to another date">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn--sm btn--success" disabled title="🔒 Resume disabled for past/future dates">
                                                    <i class="bi bi-play"></i> Resume
                                                </button>
                                                <button class="btn btn--sm btn--primary" disabled title="🔒 Progress updates disabled for past/future dates">
                                                    <i class="bi bi-percent"></i> Update Progress
                                                </button>
                                                <button class="btn btn--sm btn--secondary" disabled title="🔒 Postpone disabled for past/future dates">
                                                    <i class="bi bi-calendar-plus"></i> Postpone
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'completed'): ?>
                                            <span class="badge badge--success"><i class="bi bi-check-circle"></i> Done</span>
                                        <?php elseif ($status === 'cancelled'): ?>
                                            <span class="badge badge--danger"><i class="bi bi-x-circle"></i> Cancelled</span>
                                        <?php elseif ($status === 'suspended'): ?>
                                            <span class="badge badge--warning"><i class="bi bi-pause-circle"></i> Suspended</span>
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
        <div class="card__header sla-dashboard-header">
            <div class="sla-header-content">
                <h3 class="card__title"><i class="bi bi-speedometer2"></i> SLA Dashboard</h3>
                <small class="text-muted sla-refresh-note">⚡ Auto-refresh disabled to prevent rate limiting</small>
            </div>
            <div class="sla-header-actions">
                <div class="sla-task-filter">
                    <label for="slaTaskFilter" class="date-label">Task:</label>
                    <select id="slaTaskFilter" class="form-control">
                        <option value="">All Tasks</option>
                        <?php foreach ($planned_tasks as $task): ?>
                            <option value="<?= (int)$task['id'] ?>">
                                <?= htmlspecialchars($task['title'] ?? ('Task #' . $task['id']), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn--sm btn--secondary sla-refresh-btn" onclick="forceSLARefresh()" title="Manual refresh - automatic updates disabled to prevent rate limiting">
                    <i class="bi bi-arrow-clockwise"></i> Manual Refresh
                </button>
            </div>
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

            <div class="stats-grid stats-grid--2x2">
                <div class="stat-item">
                    <div class="stat-value text-success sla-stat-completed"><?= $completedTasks ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-primary sla-stat-active"><?= $inProgressTasks ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-warning sla-stat-postponed"><?= $postponedTasks ?></div>
                    <div class="stat-label">Postponed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value sla-stat-total"><?= $totalTasks ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>

            <div class="sla-metrics">
                <div class="metric-row">
                    <span class="metric-label">Selected Task:</span>
                    <span class="metric-value sla-selected-task-name">All Tasks</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Completion Rate:</span>
                    <span class="metric-value sla-completion-rate"><?= round($completionRate, 1) ?>%</span>
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
    <input type="hidden" name="scheduled_date" value="' . htmlspecialchars($selected_date, ENT_QUOTES, 'UTF-8') . '">
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
    <div class="form-group" style="margin-bottom: 0.5rem;">
        <label style="display:flex; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="quickRepeatDaily" name="repeat_daily" value="1">
            Repeat this task daily
        </label>
        <small style="display:block; color:#6b7280; margin-top:0.25rem;">Create it once and the planner will add it automatically every day.</small>
    </div>
    <div class="form-group" id="quickRepeatEndGroup" style="display:none;">
        <label for="quickEndDate">Repeat Until</label>
        <input type="date" id="quickEndDate" name="end_date" class="form-control" min="' . htmlspecialchars($selected_date, ENT_QUOTES, 'UTF-8') . '">
    </div>
</form>';

$quickTaskFooter = createFormModalFooter('Cancel', 'Add Task', 'quickTaskModal');

renderModal('quickTaskModal', 'Quick Add Task', $quickTaskContent, $quickTaskFooter, ['icon' => '➕']);
?>

<!-- Enhanced Progress Update Modal -->
<div id="progressDialog" class="progress-dialog" style="display: none;">
    <div class="progress-modal">
        <h3>📊 Update Task Progress</h3>

        <div class="progress-form-group">
            <label for="progressSlider">Progress Level</label>
            <div class="progress-slider-container">
                <input type="range" id="progressSlider" class="progress-slider" min="0" max="100" value="0">
                <span id="progressValue" class="progress-value">0%</span>
            </div>
        </div>

        <div class="progress-form-group">
            <label for="progressDescription">Progress Description *</label>
            <textarea id="progressDescription" class="progress-description"
                placeholder="Describe what you've accomplished, current status, or next steps..."
                required></textarea>
        </div>

        <div class="progress-actions">
            <button type="button" class="progress-btn progress-btn-secondary" onclick="closeDialog()">Cancel</button>
            <button type="button" class="progress-btn progress-btn-primary" onclick="saveProgress()">Update Progress</button>
        </div>
    </div>
</div>

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

<?php renderModalJS(); ?>
<script>
    // Global function for modal closing - must be defined before other scripts
    function hideClosestModal(element) {
        const modal = element.closest('.modal-overlay') || element.closest('.notification');
        if (modal && modal.parentElement) {
            modal.remove();
        }
    }

    // Stub functions for historical view buttons
    function showHistoryInfo() {
        if (typeof showNotification === 'function') {
            showNotification('Historical view: task execution is read-only for past dates.', 'info');
        }
    }
    function showTaskHistory(taskId, title) {
        if (typeof showNotification === 'function') {
            showNotification('History for: ' + title, 'info');
        }
    }
    function showReadOnlyProgress(taskId, progress) {
        if (typeof showNotification === 'function') {
            showNotification('Task completed at ' + progress + '%', 'info');
        }
    }

    // Enhanced progress functionality for daily planner
    var currentTaskId;

    function openProgressModal(taskId, progress, status) {
        currentTaskId = taskId;

        var slider = document.getElementById('progressSlider');
        var valueDisplay = document.getElementById('progressValue');
        var description = document.getElementById('progressDescription');
        var dialog = document.getElementById('progressDialog');

        if (slider) slider.value = progress || 0;
        if (valueDisplay) valueDisplay.textContent = (progress || 0) + '%';
        if (description) description.value = '';
        if (dialog) dialog.style.display = 'flex';

        // Focus on description field
        setTimeout(() => {
            if (description) description.focus();
        }, 100);
    }

    function closeDialog() {
        var dialog = document.getElementById('progressDialog');
        if (dialog) dialog.style.display = 'none';
    }

    function saveProgress() {
        var progressSlider = document.getElementById('progressSlider');
        var descriptionEl = document.getElementById('progressDescription');

        if (!progressSlider || !currentTaskId) {
            alert('Error: Missing required elements');
            return;
        }

        var progress = parseInt(progressSlider.value);
        var description = descriptionEl ? descriptionEl.value.trim() : '';

        if (!description) {
            alert('Please provide a description for this progress update.');
            if (descriptionEl) descriptionEl.focus();
            return;
        }

        var status = progress >= 100 ? 'completed' : progress > 0 ? 'in_progress' : 'not_started';
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch('/ergon/api/daily_planner_workflow.php?action=update-progress', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ task_id: parseInt(currentTaskId), progress: progress, status: status })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var taskCard = document.querySelector('[data-task-id="' + currentTaskId + '"]');
                if (taskCard) {
                    taskCard.dataset.status = status;
                    taskCard.dataset.completedPercentage = String(progress);
                    var progressBar = taskCard.querySelector('.progress-fill');
                    if (progressBar) progressBar.style.width = progress + '%';
                    var progressValue = taskCard.querySelector('.progress-value');
                    if (progressValue) progressValue.textContent = progress + '%';
                    var statusBadge = document.getElementById('status-' + currentTaskId);
                    if (statusBadge) {
                        statusBadge.textContent = status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                        statusBadge.className = 'badge badge--' + status;
                    }
                    if (typeof updateTaskUI === 'function') updateTaskUI(currentTaskId, status);
                }
                closeDialog();
                if (typeof showNotification === 'function') showNotification('Progress updated: ' + progress + '%', 'success');
                if (window.forceSLARefresh) window.forceSLARefresh();
            } else {
                alert('Error: ' + (data.error || data.message || 'Failed to update progress'));
            }
        })
        .catch(function(error) { alert('Error updating task progress'); });
    }

    // Update progress slider display
    document.addEventListener('DOMContentLoaded', function() {
        var progressSlider = document.getElementById('progressSlider');
        if (progressSlider) {
            progressSlider.oninput = function() {
                var valueDisplay = document.getElementById('progressValue');
                if (valueDisplay) valueDisplay.textContent = this.value + '%';
            };
        }

        var repeatDailyCheckbox = document.getElementById('quickRepeatDaily');
        var repeatEndGroup = document.getElementById('quickRepeatEndGroup');
        var quickTaskForm = document.getElementById('quickTaskForm');

        if (repeatDailyCheckbox && repeatEndGroup) {
            repeatDailyCheckbox.addEventListener('change', function() {
                repeatEndGroup.style.display = this.checked ? 'block' : 'none';
            });
        }

        if (quickTaskForm) {
            quickTaskForm.addEventListener('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(quickTaskForm);
                var submitButton = quickTaskForm.closest('.dialog')?.querySelector('button[type="submit"]');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                }

                fetch('/ergon/api/quick-add-task', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to save task');
                        }

                        closeModal('quickTaskModal');
                        window.location.href = '<?= DAILY_PLANNER_BASE_URL . $selected_date ?>';
                    })
                    .catch(error => {
                        alert(error.message || 'Failed to save task');
                    })
                    .finally(() => {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = 'Add Task';
                        }
                    });
            });
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDialog();
        }

        // Ctrl+Enter to save progress
        if (e.ctrlKey && e.key === 'Enter') {
            var progressDialog = document.getElementById('progressDialog');
            if (progressDialog && progressDialog.style.display === 'flex') {
                saveProgress();
            }
        }
    });
</script>
<script src="/ergon/assets/js/working-timer.js"></script>
<script src="/ergon/assets/js/sla-dashboard-fix.js"></script>
<script src="/ergon/views/daily_workflow/unified-daily-planner.js"></script>

<?php
$content = ob_get_clean();
$title = 'Daily Planner';
$active_page = 'daily-planner';
include __DIR__ . '/../layouts/dashboard.php';
?>

<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .notification {
        animation: slideIn 0.3s ease;
    }