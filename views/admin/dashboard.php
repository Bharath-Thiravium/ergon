<?php
$title       = 'Admin Dashboard';
$active_page = 'dashboard';

$stats           = $stats ?? [];
$pendingApprovals = $pending_approvals ?? [];
$teamData        = $team_data ?? [];
$pendingLeaves   = $pendingApprovals['leaves'] ?? [];
$pendingExpenses = $pendingApprovals['expenses'] ?? [];
$pendingAdvances = $pendingApprovals['advances'] ?? [];
$totalPending    = count($pendingLeaves) + count($pendingExpenses) + count($pendingAdvances);

ob_start();
?>
<style>
.adm-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.adm-kpi-card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #e5e7eb}
.adm-kpi-card.blue{border-top-color:#3b82f6}
.adm-kpi-card.green{border-top-color:#10b981}
.adm-kpi-card.yellow{border-top-color:#f59e0b}
.adm-kpi-card.red{border-top-color:#ef4444}
.adm-kpi-val{font-size:22px;font-weight:800;color:#111827}
.adm-kpi-lbl{font-size:11px;color:#6b7280;margin-top:3px;text-transform:uppercase;letter-spacing:.4px;font-weight:500}
.adm-kpi-sub{font-size:12px;color:#9ca3af;margin-top:2px}
[data-theme="dark"] .adm-kpi-card{background:#1f2937}
[data-theme="dark"] .adm-kpi-val{color:#f9fafb}

.adm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:20px}
.adm-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden}
.adm-card__head{padding:14px 16px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:space-between}
.adm-card__head a{font-size:12px;color:#3b82f6;text-decoration:none;font-weight:500}
.adm-card__body{padding:14px 16px}
[data-theme="dark"] .adm-card{background:#1f2937}
[data-theme="dark"] .adm-card__head{border-color:#374151;color:#f9fafb}

.appr-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f3f4f6;font-size:13px}
.appr-row:last-child{border-bottom:none}
.appr-badge{padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.appr-badge.leave{background:#eff6ff;color:#1d4ed8}
.appr-badge.expense{background:#f0fdf4;color:#16a34a}
.appr-badge.advance{background:#fef3c7;color:#d97706}
.appr-actions{display:flex;gap:6px;margin-left:auto}
.appr-btn{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;border:none;cursor:pointer;text-decoration:none}
.appr-btn.view{background:#3b82f6;color:#fff}
[data-theme="dark"] .appr-row{border-color:#374151;color:#d1d5db}
[data-theme="dark"] .appr-badge.leave{background:rgba(29,78,216,.2);color:#93c5fd}
[data-theme="dark"] .appr-badge.expense{background:rgba(22,163,74,.2);color:#86efac}
[data-theme="dark"] .appr-badge.advance{background:rgba(217,119,6,.2);color:#fde68a}

.qa-bar{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.qa-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .15s}
.qa-btn.blue{background:#1e40af;color:#fff}
.qa-btn.green{background:#059669;color:#fff}
.qa-btn.yellow{background:#d97706;color:#fff}
.qa-btn.gray{background:#6b7280;color:#fff}

.empty-state{text-align:center;padding:20px;color:#9ca3af;font-size:13px}
</style>

<!-- Quick Actions -->
<div class="qa-bar">
    <a href="/ergon/tasks/create" class="qa-btn blue">➕ Create Task</a>
    <a href="/ergon/leaves" class="qa-btn green">🏖️ Review Leaves <?php if(count($pendingLeaves)>0): ?><span style="background:rgba(255,255,255,.25);border-radius:10px;padding:1px 7px;font-size:11px"><?= count($pendingLeaves) ?></span><?php endif; ?></a>
    <a href="/ergon/expenses" class="qa-btn yellow">💰 Review Expenses <?php if(count($pendingExpenses)>0): ?><span style="background:rgba(255,255,255,.25);border-radius:10px;padding:1px 7px;font-size:11px"><?= count($pendingExpenses) ?></span><?php endif; ?></a>
    <a href="/ergon/attendance" class="qa-btn gray">📍 Attendance</a>
    <a href="/ergon/users" class="qa-btn gray">👥 Team</a>
    <a href="/ergon/reports/activity" class="qa-btn gray">📊 Reports</a>
</div>

<!-- KPIs -->
<div class="adm-kpi">
    <div class="adm-kpi-card blue">
        <div class="adm-kpi-val"><?= htmlspecialchars($stats['total_users'] ?? $stats['department_users'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="adm-kpi-lbl">Team Members</div>
        <div class="adm-kpi-sub">👥 Active users</div>
    </div>
    <div class="adm-kpi-card <?= $totalPending > 5 ? 'red' : 'yellow' ?>">
        <div class="adm-kpi-val"><?= $totalPending ?></div>
        <div class="adm-kpi-lbl">Pending Approvals</div>
        <div class="adm-kpi-sub">🏖️<?= count($pendingLeaves) ?> · 💰<?= count($pendingExpenses) ?> · 💳<?= count($pendingAdvances) ?></div>
    </div>
    <div class="adm-kpi-card green">
        <div class="adm-kpi-val"><?= htmlspecialchars($stats['today_attendance'] ?? $stats['department_attendance'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="adm-kpi-lbl">Present Today</div>
        <div class="adm-kpi-sub">📍 Clocked in</div>
    </div>
    <div class="adm-kpi-card blue">
        <div class="adm-kpi-val"><?= htmlspecialchars($stats['pending_tasks'] ?? $stats['department_tasks'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="adm-kpi-lbl">Pending Tasks</div>
        <div class="adm-kpi-sub">📋 Needs action</div>
    </div>
</div>

<div class="adm-grid">

    <!-- Pending Leaves -->
    <div class="adm-card">
        <div class="adm-card__head">
            🏖️ Pending Leave Requests
            <a href="/ergon/leaves">All Leaves →</a>
        </div>
        <div class="adm-card__body">
            <?php if (!empty($pendingLeaves)): ?>
                <?php foreach (array_slice($pendingLeaves, 0, 5) as $leave): ?>
                <div class="appr-row">
                    <span class="appr-badge leave">Leave</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600"><?= htmlspecialchars($leave['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:#9ca3af"><?= htmlspecialchars($leave['leave_type'] ?? $leave['type'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= isset($leave['start_date']) ? date('d M', strtotime($leave['start_date'])) : '' ?> – <?= isset($leave['end_date']) ? date('d M', strtotime($leave['end_date'])) : '' ?></div>
                    </div>
                    <div class="appr-actions">
                        <a href="/ergon/leaves/view/<?= (int)$leave['id'] ?>" class="appr-btn view">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($pendingLeaves) > 5): ?>
                <div style="text-align:center;margin-top:8px"><a href="/ergon/leaves" style="font-size:12px;color:#3b82f6;text-decoration:none">+<?= count($pendingLeaves)-5 ?> more →</a></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">✅ No pending leave requests</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Expenses -->
    <div class="adm-card">
        <div class="adm-card__head">
            💰 Pending Expense Claims
            <a href="/ergon/expenses">All Expenses →</a>
        </div>
        <div class="adm-card__body">
            <?php if (!empty($pendingExpenses)): ?>
                <?php foreach (array_slice($pendingExpenses, 0, 5) as $exp): ?>
                <div class="appr-row">
                    <span class="appr-badge expense">Expense</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600"><?= htmlspecialchars($exp['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:#9ca3af">₹<?= number_format($exp['amount'] ?? 0) ?> · <?= htmlspecialchars(ucfirst($exp['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="appr-actions">
                        <a href="/ergon/expenses/view/<?= (int)$exp['id'] ?>" class="appr-btn view">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($pendingExpenses) > 5): ?>
                <div style="text-align:center;margin-top:8px"><a href="/ergon/expenses" style="font-size:12px;color:#3b82f6;text-decoration:none">+<?= count($pendingExpenses)-5 ?> more →</a></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">✅ No pending expense claims</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Advances -->
    <div class="adm-card">
        <div class="adm-card__head">
            💳 Pending Advance Requests
            <a href="/ergon/advances">All Advances →</a>
        </div>
        <div class="adm-card__body">
            <?php if (!empty($pendingAdvances)): ?>
                <?php foreach (array_slice($pendingAdvances, 0, 5) as $adv): ?>
                <div class="appr-row">
                    <span class="appr-badge advance">Advance</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600"><?= htmlspecialchars($adv['user_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:#9ca3af">₹<?= number_format($adv['amount'] ?? 0) ?> · <?= isset($adv['created_at']) ? date('d M', strtotime($adv['created_at'])) : '' ?></div>
                    </div>
                    <div class="appr-actions">
                        <a href="/ergon/advances/view/<?= (int)$adv['id'] ?>" class="appr-btn view">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($pendingAdvances) > 5): ?>
                <div style="text-align:center;margin-top:8px"><a href="/ergon/advances" style="font-size:12px;color:#3b82f6;text-decoration:none">+<?= count($pendingAdvances)-5 ?> more →</a></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">✅ No pending advance requests</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Team Overview -->
    <div class="adm-card">
        <div class="adm-card__head">
            👥 Team Overview
            <a href="/ergon/users">Manage →</a>
        </div>
        <div class="adm-card__body">
            <?php if (!empty($teamData)): ?>
                <?php foreach (array_slice($teamData, 0, 8) as $member): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f3f4f6;font-size:13px">
                    <div style="width:30px;height:30px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-weight:700;color:#1d4ed8;font-size:12px;flex-shrink:0">
                        <?= strtoupper(substr($member['name'] ?? $member['department_name'] ?? '?', 0, 1)) ?>
                    </div>
                    <div style="flex:1">
                        <div style="font-weight:500"><?= htmlspecialchars($member['name'] ?? $member['department_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (isset($member['role'])): ?>
                        <div style="font-size:11px;color:#9ca3af"><?= ucfirst($member['role']) ?></div>
                        <?php elseif (isset($member['user_count'])): ?>
                        <div style="font-size:11px;color:#9ca3af"><?= $member['user_count'] ?> members</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">No team data available</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Task Status -->
    <div class="adm-card">
        <div class="adm-card__head">
            📋 Task Status Overview
            <a href="/ergon/tasks">All Tasks →</a>
        </div>
        <div class="adm-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <?php
                try {
                    require_once __DIR__ . '/../../app/config/database.php';
                    $db2 = Database::connect();
                    $taskStats = $db2->query("SELECT status, COUNT(*) as cnt FROM tasks GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
                } catch (Exception $e) { $taskStats = []; }
                $statusMap = [
                    'pending'     => ['label'=>'Pending',     'color'=>'#d97706','bg'=>'#fffbeb'],
                    'in_progress' => ['label'=>'In Progress', 'color'=>'#1d4ed8','bg'=>'#eff6ff'],
                    'completed'   => ['label'=>'Completed',   'color'=>'#16a34a','bg'=>'#f0fdf4'],
                    'cancelled'   => ['label'=>'Cancelled',   'color'=>'#6b7280','bg'=>'#f9fafb'],
                ];
                foreach ($statusMap as $key => $cfg):
                ?>
                <div style="padding:10px;background:<?= $cfg['bg'] ?>;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:<?= $cfg['color'] ?>"><?= (int)($taskStats[$key] ?? 0) ?></div>
                    <div style="font-size:11px;color:#6b7280;margin-top:2px"><?= $cfg['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px">
                <a href="/ergon/tasks/create" style="font-size:12px;color:#3b82f6;text-decoration:none">➕ Create Task →</a>
                <a href="/ergon/dashboard/delayed-tasks-overview" style="font-size:12px;color:#ef4444;text-decoration:none">⚠️ Overdue →</a>
            </div>
        </div>
    </div>

    <!-- Attendance Today -->
    <div class="adm-card">
        <div class="adm-card__head">
            📍 Today's Attendance
            <a href="/ergon/attendance">Full Report →</a>
        </div>
        <div class="adm-card__body">
            <?php
            try {
                if (!isset($db2)) { require_once __DIR__ . '/../../app/config/database.php'; $db2 = Database::connect(); }
                $attToday2   = $db2->query("SELECT COUNT(*) FROM attendance WHERE DATE(clock_in)=CURDATE()")->fetchColumn();
                $totalUsers2 = $db2->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
                $onLeave2    = $db2->query("SELECT COUNT(*) FROM leaves WHERE status='approved' AND CURDATE() BETWEEN start_date AND end_date")->fetchColumn();
                $late2       = $db2->query("SELECT COUNT(*) FROM attendance WHERE DATE(clock_in)=CURDATE() AND TIME(clock_in)>'09:30:00'")->fetchColumn();
                $absent2     = max(0, $totalUsers2 - $attToday2 - $onLeave2);
            } catch (Exception $e) { $attToday2=$totalUsers2=$onLeave2=$late2=$absent2=0; }
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                <div style="padding:10px;background:#f0fdf4;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#16a34a"><?= $attToday2 ?></div>
                    <div style="font-size:11px;color:#6b7280">Present</div>
                </div>
                <div style="padding:10px;background:#fef2f2;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#dc2626"><?= $absent2 ?></div>
                    <div style="font-size:11px;color:#6b7280">Absent</div>
                </div>
                <div style="padding:10px;background:#fffbeb;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#d97706"><?= $late2 ?></div>
                    <div style="font-size:11px;color:#6b7280">Late</div>
                </div>
                <div style="padding:10px;background:#eff6ff;border-radius:8px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#1d4ed8"><?= $onLeave2 ?></div>
                    <div style="font-size:11px;color:#6b7280">On Leave</div>
                </div>
            </div>
            <?php if ($absent2 > 0): ?>
            <div style="padding:10px;background:#fef2f2;border-radius:8px;font-size:12px;color:#991b1b;font-weight:600">
                ⚠️ <?= $absent2 ?> employee(s) absent today with no leave record
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
