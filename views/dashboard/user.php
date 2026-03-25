<?php
$title       = 'My Dashboard';
$active_page = 'dashboard';

$stats          = $stats ?? [];
$todayTasks     = $today_tasks ?? [];
$recentActs     = $recent_activities ?? [];
$attStatus      = $attendance_status ?? ['status' => 'not_clocked_in'];
$myTasks        = $stats['my_tasks'] ?? [];
$attMonth       = $stats['attendance_this_month'] ?? 0;
$pendingReqs    = $stats['pending_requests'] ?? 0;
$completedMonth = $stats['completed_tasks_this_month'] ?? 0;
$leaveBalance   = $stats['leave_balance'] ?? 0;

ob_start();
?>
<style>
.ud-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.ud-kpi-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #e5e7eb}
.ud-kpi-card.green{border-top-color:#10b981}
.ud-kpi-card.blue{border-top-color:#3b82f6}
.ud-kpi-card.yellow{border-top-color:#f59e0b}
.ud-kpi-card.purple{border-top-color:#8b5cf6}
.ud-kpi-card.red{border-top-color:#ef4444}
.ud-kpi-val{font-size:22px;font-weight:800;color:#111827}
.ud-kpi-lbl{font-size:11px;color:#6b7280;margin-top:3px;text-transform:uppercase;letter-spacing:.4px;font-weight:500}
.ud-kpi-sub{font-size:12px;color:#9ca3af;margin-top:2px}
[data-theme="dark"] .ud-kpi-card{background:#1f2937}
[data-theme="dark"] .ud-kpi-val{color:#f9fafb}

.ud-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-bottom:20px}
.ud-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden}
.ud-card__head{padding:14px 16px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:space-between}
.ud-card__head a{font-size:12px;color:#3b82f6;text-decoration:none;font-weight:500}
.ud-card__body{padding:14px 16px}
[data-theme="dark"] .ud-card{background:#1f2937}
[data-theme="dark"] .ud-card__head{border-color:#374151;color:#f9fafb}

.att-status{display:flex;align-items:center;gap:12px;padding:14px;border-radius:10px;margin-bottom:14px;font-weight:600;font-size:14px}
.att-status.in{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.att-status.out{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.att-status.done{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}
[data-theme="dark"] .att-status.in{background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.3);color:#6ee7b7}
[data-theme="dark"] .att-status.out{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.3);color:#fca5a5}
[data-theme="dark"] .att-status.done{background:rgba(59,130,246,.12);border-color:rgba(59,130,246,.3);color:#93c5fd}

.qa-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.qa-link{display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;background:#f8fafc;color:#374151;border:1px solid #e5e7eb;transition:all .15s}
.qa-link:hover{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
[data-theme="dark"] .qa-link{background:#374151;color:#d1d5db;border-color:#4b5563}
[data-theme="dark"] .qa-link:hover{background:#1e40af;color:#fff}

.task-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px}
.task-row:last-child{border-bottom:none}
.task-badge{padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.task-badge.high{background:#fef2f2;color:#dc2626}
.task-badge.medium{background:#fffbeb;color:#d97706}
.task-badge.low{background:#f0fdf4;color:#16a34a}
.task-badge.in_progress{background:#eff6ff;color:#1d4ed8}
.task-badge.pending{background:#fffbeb;color:#d97706}
.task-badge.completed{background:#f0fdf4;color:#16a34a}
[data-theme="dark"] .task-row{border-color:#374151;color:#d1d5db}
[data-theme="dark"] .task-badge.high{background:rgba(220,38,38,.2);color:#fca5a5}
[data-theme="dark"] .task-badge.medium{background:rgba(217,119,6,.2);color:#fde68a}
[data-theme="dark"] .task-badge.in_progress{background:rgba(29,78,216,.2);color:#93c5fd}

.act-item{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid #f3f4f6;font-size:12px}
.act-item:last-child{border-bottom:none}
.act-dot{width:7px;height:7px;border-radius:50%;background:#3b82f6;margin-top:4px;flex-shrink:0}
.act-meta{color:#9ca3af;font-size:11px;margin-top:1px}
[data-theme="dark"] .act-item{border-color:#374151;color:#d1d5db}
</style>

<!-- KPI Row -->
<div class="ud-kpi">
    <?php
    $attStatusLabel = 'Not Clocked In';
    $attClass = 'red';
    if ($attStatus['status'] === 'clocked_in') { $attStatusLabel = 'Clocked In'; $attClass = 'green'; }
    elseif ($attStatus['status'] === 'clocked_out') { $attStatusLabel = 'Day Complete'; $attClass = 'blue'; }
    ?>
    <div class="ud-kpi-card <?= $attClass ?>">
        <div class="ud-kpi-val"><?= $attStatusLabel ?></div>
        <div class="ud-kpi-lbl">Today's Status</div>
        <?php if (!empty($attStatus['clock_in'])): ?>
        <div class="ud-kpi-sub">In: <?= date('H:i', strtotime($attStatus['clock_in'])) ?><?= !empty($attStatus['clock_out']) ? ' · Out: '.date('H:i', strtotime($attStatus['clock_out'])) : '' ?></div>
        <?php endif; ?>
    </div>
    <div class="ud-kpi-card blue">
        <div class="ud-kpi-val"><?= (int)($myTasks['in_progress'] ?? 0) ?></div>
        <div class="ud-kpi-lbl">Active Tasks</div>
        <div class="ud-kpi-sub">📋 <?= (int)($myTasks['pending'] ?? 0) ?> pending · <?= (int)($myTasks['overdue'] ?? 0) ?> overdue</div>
    </div>
    <div class="ud-kpi-card <?= $pendingReqs > 0 ? 'yellow' : 'green' ?>">
        <div class="ud-kpi-val"><?= (int)$pendingReqs ?></div>
        <div class="ud-kpi-lbl">Pending Requests</div>
        <div class="ud-kpi-sub">⏳ Awaiting approval</div>
    </div>
    <div class="ud-kpi-card green">
        <div class="ud-kpi-val"><?= (int)$completedMonth ?></div>
        <div class="ud-kpi-lbl">Completed This Month</div>
        <div class="ud-kpi-sub">✅ Tasks done</div>
    </div>
    <div class="ud-kpi-card purple">
        <div class="ud-kpi-val"><?= (int)$attMonth ?></div>
        <div class="ud-kpi-lbl">Days Present</div>
        <div class="ud-kpi-sub">📅 This month</div>
    </div>
    <div class="ud-kpi-card <?= $leaveBalance < 3 ? 'red' : 'green' ?>">
        <div class="ud-kpi-val"><?= (int)$leaveBalance ?></div>
        <div class="ud-kpi-lbl">Leave Balance</div>
        <div class="ud-kpi-sub">🏖️ Days remaining</div>
    </div>
</div>

<div class="ud-grid">
    <!-- Attendance + Quick Actions -->
    <div class="ud-card">
        <div class="ud-card__head">⚡ Quick Actions</div>
        <div class="ud-card__body">
            <?php
            $attClass2 = 'out'; $attIcon = '▶️'; $attMsg = 'Not clocked in today';
            if ($attStatus['status'] === 'clocked_in') { $attClass2 = 'in'; $attIcon = '🟢'; $attMsg = 'Clocked in at '.date('H:i', strtotime($attStatus['clock_in'])); }
            elseif ($attStatus['status'] === 'clocked_out') { $attClass2 = 'done'; $attIcon = '✅'; $attMsg = 'Day complete · '.date('H:i', strtotime($attStatus['clock_in'])).' – '.date('H:i', strtotime($attStatus['clock_out'])); }
            ?>
            <div class="att-status <?= $attClass2 ?>">
                <span style="font-size:20px"><?= $attIcon ?></span>
                <span><?= htmlspecialchars($attMsg, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="qa-grid">
                <a href="/ergon/leaves/create" class="qa-link">📅 Request Leave</a>
                <a href="/ergon/expenses/create" class="qa-link">💰 Submit Expense</a>
                <a href="/ergon/advances/create" class="qa-link">💳 Request Advance</a>
                <a href="/ergon/tasks" class="qa-link">📋 My Tasks</a>
                <a href="/ergon/user/requests" class="qa-link">📁 My Requests</a>
                <a href="/ergon/attendance" class="qa-link">📍 Attendance Log</a>
                <a href="/ergon/workflow/daily-planner" class="qa-link">🌅 Daily Planner</a>
                <a href="/ergon/profile" class="qa-link">👤 My Profile</a>
            </div>
        </div>
    </div>

    <!-- Today's Tasks -->
    <div class="ud-card">
        <div class="ud-card__head">
            📋 Today's Tasks
            <a href="/ergon/tasks">All Tasks →</a>
        </div>
        <div class="ud-card__body">
            <?php if (!empty($todayTasks)): ?>
                <?php foreach (array_slice($todayTasks, 0, 6) as $task): ?>
                <div class="task-row">
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($task['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if (!empty($task['due_date'])): ?>
                        <div style="font-size:11px;color:#9ca3af">Due: <?= date('d M', strtotime($task['due_date'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="task-badge <?= htmlspecialchars($task['priority'] ?? 'low', ENT_QUOTES, 'UTF-8') ?>"><?= ucfirst($task['priority'] ?? 'low') ?></span>
                    <span class="task-badge <?= htmlspecialchars(str_replace(' ', '_', $task['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?>"><?= ucfirst(str_replace('_', ' ', $task['status'] ?? 'pending')) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (count($todayTasks) > 6): ?>
                <div style="text-align:center;margin-top:8px"><a href="/ergon/tasks" style="font-size:12px;color:#3b82f6;text-decoration:none">+<?= count($todayTasks)-6 ?> more tasks →</a></div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px">✅ No tasks due today</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Task Summary -->
    <div class="ud-card">
        <div class="ud-card__head">📊 My Task Summary</div>
        <div class="ud-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <?php
                $taskSummary = [
                    ['label'=>'Total','val'=>$myTasks['total']??0,'color'=>'#374151'],
                    ['label'=>'In Progress','val'=>$myTasks['in_progress']??0,'color'=>'#1d4ed8'],
                    ['label'=>'Pending','val'=>$myTasks['pending']??0,'color'=>'#d97706'],
                    ['label'=>'Completed','val'=>$myTasks['completed']??0,'color'=>'#16a34a'],
                    ['label'=>'Overdue','val'=>$myTasks['overdue']??0,'color'=>'#dc2626'],
                ];
                foreach ($taskSummary as $ts):
                ?>
                <div style="padding:10px;background:#f8fafc;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:<?= $ts['color'] ?>"><?= (int)$ts['val'] ?></div>
                    <div style="font-size:11px;color:#6b7280;margin-top:2px"><?= $ts['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (($myTasks['overdue'] ?? 0) > 0): ?>
            <div style="margin-top:12px;padding:10px;background:#fef2f2;border-radius:8px;font-size:12px;color:#991b1b;font-weight:600">
                ⚠️ You have <?= (int)$myTasks['overdue'] ?> overdue task(s). <a href="/ergon/tasks?filter=overdue" style="color:#dc2626">View now →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="ud-card">
        <div class="ud-card__head">🕐 Recent Activity</div>
        <div class="ud-card__body">
            <?php if (!empty($recentActs)): ?>
                <?php foreach (array_slice($recentActs, 0, 8) as $act): ?>
                <div class="act-item">
                    <div class="act-dot"></div>
                    <div>
                        <div style="font-weight:500"><?= htmlspecialchars($act['description'] ?? $act['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="act-meta"><?= isset($act['created_at']) ? date('d M, H:i', strtotime($act['created_at'])) : '' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px">No recent activity</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
