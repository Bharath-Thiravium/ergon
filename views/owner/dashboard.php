<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], ['owner', 'company_owner'])) {
    header("Location: /ergon/login");
    exit;
}

require_once __DIR__ . '/../../app/helpers/ModuleManager.php';
$systemAdminDisabled = false;
$reportsDisabled = false;
try {
    $systemAdminDisabled = ModuleManager::isModuleDisabled('system_admin');
    $reportsDisabled     = ModuleManager::isModuleDisabled('reports');
} catch (Exception $e) {}

$title       = 'Executive Dashboard';
$active_page = 'dashboard';

// Shorthand helpers
$d           = $data ?? [];
$stats       = $d['stats'] ?? [];
$alerts      = $d['alerts'] ?? [];
$attToday    = $d['att_today'] ?? 0;
$attPct      = $d['att_pct'] ?? 0;
$onLeave     = $d['on_leave_today'] ?? 0;
$absent      = $d['absent_today'] ?? 0;
$late        = $d['late_today'] ?? 0;
$totalPend   = $d['total_pending'] ?? 0;
$revMonth    = $d['revenue_month'] ?? 0;
$expMonth    = $d['expenses_month'] ?? 0;
$outstanding = $d['outstanding_total'] ?? 0;
$tdsRec      = $d['tds_receivable'] ?? 0;
$tdsPaid     = $d['tds_received'] ?? 0;
$aging       = $d['aging_buckets'] ?? ['0_30'=>0,'31_60'=>0,'61_90'=>0,'90_plus'=>0];
$cashSum     = $d['cash_summary'] ?? ['credits'=>0,'debits'=>0,'balance'=>0];
$netProfit   = $revMonth - $expMonth;
$overdueInv  = $d['overdue_invoices'] ?? [];
$advOut      = $d['advances_outstanding'] ?? [];
$topExpCats  = $d['top_expense_cats'] ?? [];
$attBehavior = $d['attendance_behavior'] ?? [];
$recentActs  = $d['recent_activities'] ?? [];
?>
<style>
.intel-strip{display:flex;flex-wrap:wrap;gap:12px;background:linear-gradient(135deg,#1e3a5f,#0f2340);border-radius:12px;padding:16px 20px;margin-bottom:20px;align-items:center}
.intel-strip__item{display:flex;align-items:center;gap:8px;color:#fff;font-size:13px;font-weight:600;padding:6px 14px;border-radius:20px;background:rgba(255,255,255,0.12)}
.intel-strip__item.green{background:rgba(16,185,129,0.25);color:#6ee7b7}
.intel-strip__item.red{background:rgba(239,68,68,0.25);color:#fca5a5}
.intel-strip__item.yellow{background:rgba(245,158,11,0.25);color:#fde68a}
.intel-strip__item.blue{background:rgba(59,130,246,0.25);color:#93c5fd}
.intel-strip__label{font-size:11px;opacity:.8;font-weight:400}

.priority-alerts{margin-bottom:20px}
.alert-item{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:10px;margin-bottom:8px;font-size:13px;font-weight:500}
.alert-item.danger{background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b}
.alert-item.warning{background:#fffbeb;border-left:4px solid #f59e0b;color:#92400e}
.alert-item.info{background:#eff6ff;border-left:4px solid #3b82f6;color:#1e40af}
[data-theme="dark"] .alert-item.danger{background:rgba(239,68,68,.12);color:#fca5a5}
[data-theme="dark"] .alert-item.warning{background:rgba(245,158,11,.12);color:#fde68a}
[data-theme="dark"] .alert-item.info{background:rgba(59,130,246,.12);color:#93c5fd}

.kpi-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}

.quick-actions-bar{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.qa-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:all .15s}
.qa-btn.primary{background:#1e40af;color:#fff}
.qa-btn.primary:hover{background:#1d4ed8}
.qa-btn.success{background:#059669;color:#fff}
.qa-btn.success:hover{background:#047857}
.qa-btn.warning{background:#d97706;color:#fff}
.qa-btn.warning:hover{background:#b45309}
.qa-btn.danger{background:#dc2626;color:#fff}
.qa-btn.danger:hover{background:#b91c1c}

.intel-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:20px}
.intel-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden}
.intel-card__head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f3f4f6;font-weight:700;font-size:14px}
.intel-card__head a{font-size:12px;color:#3b82f6;text-decoration:none;font-weight:500}
.intel-card__body{padding:14px 16px}
[data-theme="dark"] .intel-card{background:#1f2937}
[data-theme="dark"] .intel-card__head{border-color:#374151;color:#f9fafb}

.risk-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px}
.risk-row:last-child{border-bottom:none}
.risk-badge{padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}
.risk-badge.high{background:#fef2f2;color:#dc2626}
.risk-badge.medium{background:#fffbeb;color:#d97706}
.risk-badge.low{background:#f0fdf4;color:#16a34a}
[data-theme="dark"] .risk-row{border-color:#374151;color:#d1d5db}
[data-theme="dark"] .risk-badge.high{background:rgba(220,38,38,.2);color:#fca5a5}
[data-theme="dark"] .risk-badge.medium{background:rgba(217,119,6,.2);color:#fde68a}
[data-theme="dark"] .risk-badge.low{background:rgba(22,163,74,.2);color:#86efac}

.bar-row{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:12px}
.bar-row__label{width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#374151;font-weight:500}
.bar-row__track{flex:1;background:#f3f4f6;border-radius:4px;height:8px}
.bar-row__fill{height:8px;border-radius:4px;background:#3b82f6;transition:width .4s}
.bar-row__val{width:60px;text-align:right;color:#6b7280;font-weight:600}
[data-theme="dark"] .bar-row__label{color:#d1d5db}
[data-theme="dark"] .bar-row__track{background:#374151}
[data-theme="dark"] .bar-row__val{color:#9ca3af}

.act-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:12px}
.act-item:last-child{border-bottom:none}
.act-dot{width:8px;height:8px;border-radius:50%;background:#3b82f6;margin-top:4px;flex-shrink:0}
.act-meta{color:#9ca3af;font-size:11px;margin-top:2px}
[data-theme="dark"] .act-item{border-color:#374151;color:#d1d5db}
[data-theme="dark"] .act-meta{color:#6b7280}

.empty-state{text-align:center;padding:24px;color:#9ca3af;font-size:13px}
</style>

<!-- Quick Actions Bar -->
<div class="quick-actions-bar">
    <a href="/ergon/owner/approvals" class="qa-btn primary">✅ Approval Center <?php if($totalPend>0): ?><span style="background:rgba(255,255,255,.25);border-radius:10px;padding:1px 7px;font-size:11px"><?= $totalPend ?></span><?php endif; ?></a>
    <a href="/ergon/advances" class="qa-btn primary" style="background:#0369a1">💳 Advances <span style="background:rgba(255,255,255,.2);border-radius:10px;padding:1px 6px;font-size:11px"><?= $stats['pending_advances']??0 ?></span></a>
    <a href="/ergon/leaves" class="qa-btn success">🏖️ Leaves <span style="background:rgba(255,255,255,.2);border-radius:10px;padding:1px 6px;font-size:11px"><?= $stats['pending_leaves']??0 ?></span></a>
    <a href="/ergon/expenses" class="qa-btn warning">💰 Expenses <span style="background:rgba(255,255,255,.2);border-radius:10px;padding:1px 6px;font-size:11px"><?= $stats['pending_expenses']??0 ?></span></a>
    <a href="/ergon/ledgers/project" class="qa-btn primary" style="background:#0f766e">📒 Cash Ledger</a>
    <?php if(!$reportsDisabled): ?>
    <a href="/ergon/reports" class="qa-btn primary" style="background:#7c3aed">📊 Reports</a>
    <?php endif; ?>
    <?php if(!$systemAdminDisabled): ?>
    <a href="/ergon/settings" class="qa-btn danger" style="background:#6b7280">⚙️ Settings</a>
    <?php endif; ?>
</div>

<!-- Priority Alerts -->
<?php if (!empty($alerts)): ?>
<div class="priority-alerts">
    <?php foreach ($alerts as $a): ?>
    <div class="alert-item <?= htmlspecialchars($a['type'], ENT_QUOTES, 'UTF-8') ?>">
        <span style="font-size:18px"><?= $a['icon'] ?></span>
        <span><?= htmlspecialchars($a['msg'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Today Summary Strip -->
<div class="intel-strip">
    <div class="intel-strip__item <?= $attPct >= 80 ? 'green' : ($attPct >= 50 ? 'yellow' : 'red') ?>">👥 Present: <strong><?= $attToday ?></strong><span class="intel-strip__label"> (<?= $attPct ?>%)</span></div>
    <div class="intel-strip__item blue">🏖️ On Leave: <strong><?= $onLeave ?></strong></div>
    <div class="intel-strip__item <?= $absent > 0 ? 'red' : 'green' ?>">❌ Absent: <strong><?= $absent ?></strong></div>
    <div class="intel-strip__item yellow">⏰ Late: <strong><?= $late ?></strong></div>
    <div class="intel-strip__item <?= $totalPend > 0 ? 'red' : 'green' ?>">📋 Pending Approvals: <strong><?= $totalPend ?></strong></div>
    <?php if ($revMonth > 0): ?>
    <div class="intel-strip__item green">💰 Revenue: <strong>₹<?= number_format($revMonth) ?></strong></div>
    <?php endif; ?>
    <?php if ($outstanding > 0): ?>
    <div class="intel-strip__item red">⚠️ Outstanding: <strong>₹<?= number_format($outstanding) ?></strong></div>
    <?php endif; ?>
    <?php if ($cashSum['balance'] != 0): ?>
    <div class="intel-strip__item <?= $cashSum['balance'] >= 0 ? 'green' : 'red' ?>">🏦 Cash Balance: <strong>₹<?= number_format($cashSum['balance']) ?></strong></div>
    <?php endif; ?>
</div>

<!-- Owner KPIs -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">👥</div><div class="kpi-card__trend">↗ Active</div></div>
        <div class="kpi-card__value"><?= htmlspecialchars($stats['total_users'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Employees</div>
        <div class="kpi-card__status"><?= $attPct ?>% present today</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">📋</div><div class="kpi-card__trend">— Tasks</div></div>
        <div class="kpi-card__value"><?= htmlspecialchars($stats['active_tasks'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-card__label">Active Tasks</div>
        <div class="kpi-card__status"><?= $stats['critical']??0 ?> critical</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon"><?= $totalPend > 5 ? '🔴' : '🟡' ?></div><div class="kpi-card__trend">— Pending</div></div>
        <div class="kpi-card__value"><?= $totalPend ?></div>
        <div class="kpi-card__label">Pending Approvals</div>
        <div class="kpi-card__status">🏖️<?= $stats['pending_leaves']??0 ?> · 💰<?= $stats['pending_expenses']??0 ?> · 💳<?= $stats['pending_advances']??0 ?></div>
    </div>
    <?php if ($revMonth > 0 || $expMonth > 0): ?>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">📈</div><div class="kpi-card__trend">↗ Revenue</div></div>
        <div class="kpi-card__value">₹<?= number_format($revMonth/100000,1) ?>L</div>
        <div class="kpi-card__label">Revenue This Month</div>
        <div class="kpi-card__status">Invoiced amount</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">💸</div><div class="kpi-card__trend"><?= $expMonth > $revMonth * 0.8 ? '⚠️ High' : '— Normal' ?></div></div>
        <div class="kpi-card__value">₹<?= number_format($expMonth/100000,1) ?>L</div>
        <div class="kpi-card__label">Expenses This Month</div>
        <div class="kpi-card__status">Approved expenses</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon"><?= $netProfit >= 0 ? '✅' : '⚠️' ?></div><div class="kpi-card__trend"><?= $netProfit >= 0 ? '↗ Positive' : '↘ Negative' ?></div></div>
        <div class="kpi-card__value">₹<?= number_format($netProfit/100000,1) ?>L</div>
        <div class="kpi-card__label">Net Profit</div>
        <div class="kpi-card__status">Revenue - Expenses</div>
    </div>
    <?php endif; ?>
    <?php if ($outstanding > 0): ?>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">⚠️</div><div class="kpi-card__trend">↘ Overdue</div></div>
        <div class="kpi-card__value">₹<?= number_format($outstanding/100000,1) ?>L</div>
        <div class="kpi-card__label">Outstanding</div>
        <div class="kpi-card__status">Unpaid invoices</div>
    </div>
    <?php endif; ?>
    <?php if ($tdsRec > 0): ?>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">📊</div><div class="kpi-card__trend"><?= ($tdsRec-$tdsPaid)>0 ? '— Pending' : '✅ Clear' ?></div></div>
        <div class="kpi-card__value">₹<?= number_format(($tdsRec-$tdsPaid)/1000,1) ?>K</div>
        <div class="kpi-card__label">TDS Pending</div>
        <div class="kpi-card__status">₹<?= number_format($tdsPaid/1000,1) ?>K received</div>
    </div>
    <?php endif; ?>
    <div class="kpi-card">
        <div class="kpi-card__header"><div class="kpi-card__icon">🎯</div><div class="kpi-card__trend">On-time: <?= $stats['ontime_rate']??0 ?>%</div></div>
        <div class="kpi-card__value"><?= htmlspecialchars($stats['completion_rate'] ?? '0', ENT_QUOTES, 'UTF-8') ?>%</div>
        <div class="kpi-card__label">Task Completion</div>
        <div class="kpi-card__status">Overall progress</div>
    </div>
</div>

<!-- Finance Intelligence Row -->
<?php if ($outstanding > 0 || $cashSum['credits'] > 0): ?>
<div class="intel-grid" style="margin-bottom:20px">

    <!-- Aging Analysis -->
    <div class="intel-card">
        <div class="intel-card__head">📅 Outstanding Aging Analysis <a href="/ergon/finance">Finance →</a></div>
        <div class="intel-card__body">
            <?php
            $agingMax = max(array_values($aging)) ?: 1;
            $agingLabels = ['0_30'=>'0–30 days','31_60'=>'31–60 days','61_90'=>'61–90 days','90_plus'=>'90+ days'];
            $agingColors = ['0_30'=>'#10b981','31_60'=>'#f59e0b','61_90'=>'#f97316','90_plus'=>'#ef4444'];
            foreach ($aging as $key => $val):
                $pct = $agingMax > 0 ? round(($val/$agingMax)*100) : 0;
            ?>
            <div class="bar-row">
                <div class="bar-row__label"><?= $agingLabels[$key] ?></div>
                <div class="bar-row__track"><div class="bar-row__fill" style="width:<?= $pct ?>%;background:<?= $agingColors[$key] ?>"></div></div>
                <div class="bar-row__val" style="color:<?= $val>0?$agingColors[$key]:'#9ca3af' ?>"><?= $val>0?'₹'.number_format($val/1000,1).'K':'-' ?></div>
            </div>
            <?php endforeach; ?>
            <div style="margin-top:10px;padding-top:8px;border-top:1px solid #f3f4f6;font-size:12px;color:#6b7280">
                Total Outstanding: <strong style="color:#ef4444">₹<?= number_format($outstanding) ?></strong>
            </div>
        </div>
    </div>

    <!-- Cash Summary -->
    <div class="intel-card">
        <div class="intel-card__head">🏦 Cash Flow Summary <a href="/ergon/ledgers/project">Ledger →</a></div>
        <div class="intel-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
                <div style="text-align:center;padding:10px;background:#f0fdf4;border-radius:8px">
                    <div style="font-size:16px;font-weight:800;color:#16a34a">₹<?= number_format($cashSum['credits']/1000,1) ?>K</div>
                    <div style="font-size:10px;color:#6b7280;margin-top:2px">CREDITS</div>
                </div>
                <div style="text-align:center;padding:10px;background:#fef2f2;border-radius:8px">
                    <div style="font-size:16px;font-weight:800;color:#dc2626">₹<?= number_format($cashSum['debits']/1000,1) ?>K</div>
                    <div style="font-size:10px;color:#6b7280;margin-top:2px">DEBITS</div>
                </div>
                <div style="text-align:center;padding:10px;background:<?= $cashSum['balance']>=0?'#eff6ff':'#fef2f2' ?>;border-radius:8px">
                    <div style="font-size:16px;font-weight:800;color:<?= $cashSum['balance']>=0?'#1d4ed8':'#dc2626' ?>">₹<?= number_format($cashSum['balance']/1000,1) ?>K</div>
                    <div style="font-size:10px;color:#6b7280;margin-top:2px">BALANCE</div>
                </div>
            </div>
            <?php if ($tdsRec > 0): ?>
            <div style="padding:10px;background:#fffbeb;border-radius:8px;font-size:12px">
                <div style="font-weight:700;color:#92400e;margin-bottom:6px">📊 TDS Tracker</div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="color:#6b7280">TDS Receivable</span><span style="font-weight:600">₹<?= number_format($tdsRec) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="color:#6b7280">TDS Received (26AS)</span><span style="font-weight:600;color:#16a34a">₹<?= number_format($tdsPaid) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:4px;border-top:1px solid #fcd34d">
                    <span style="color:#92400e;font-weight:700">Pending TDS</span><span style="font-weight:800;color:#dc2626">₹<?= number_format($tdsRec-$tdsPaid) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- Intelligence Grid -->
<div class="intel-grid">

    <!-- Attendance Intelligence -->
    <div class="intel-card">
        <div class="intel-card__head">
            📍 Attendance Intelligence
            <a href="/ergon/attendance">View All →</a>
        </div>
        <div class="intel-card__body">
            <!-- Live attendance bar -->
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                    <span style="color:#6b7280">Today's Attendance</span>
                    <span style="font-weight:700;color:<?= $attPct>=80?'#16a34a':($attPct>=50?'#d97706':'#dc2626') ?>"><?= $attPct ?>%</span>
                </div>
                <div style="background:#f3f4f6;border-radius:6px;height:10px">
                    <div style="height:10px;border-radius:6px;background:<?= $attPct>=80?'#10b981':($attPct>=50?'#f59e0b':'#ef4444') ?>;width:<?= $attPct ?>%;transition:width .4s"></div>
                </div>
                <div style="display:flex;gap:16px;font-size:12px;margin-top:8px">
                    <div><strong style="color:#10b981"><?= $attToday ?></strong> <span style="color:#6b7280">Present</span></div>
                    <div><strong style="color:#ef4444"><?= $absent ?></strong> <span style="color:#6b7280">Absent</span></div>
                    <div><strong style="color:#f59e0b"><?= $late ?></strong> <span style="color:#6b7280">Late</span></div>
                    <div><strong style="color:#3b82f6"><?= $onLeave ?></strong> <span style="color:#6b7280">On Leave</span></div>
                </div>
            </div>
            <?php if (!empty($attBehavior)): ?>
                <div style="font-size:11px;color:#6b7280;margin-bottom:8px;font-weight:600;text-transform:uppercase">Frequent Late Arrivals (This Month)</div>
                <?php foreach ($attBehavior as $row): ?>
                <div class="risk-row">
                    <span><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="risk-badge <?= $row['late_count'] >= 5 ? 'high' : ($row['late_count'] >= 3 ? 'medium' : 'low') ?>">
                        <?= $row['late_count'] ?>x late
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">✅ No late arrivals this month</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overdue Invoices -->
    <div class="intel-card">
        <div class="intel-card__head">
            💰 Overdue Invoice Risk
            <a href="/ergon/finance">View Finance →</a>
        </div>
        <div class="intel-card__body">
            <?php if (!empty($overdueInv)): ?>
                <?php foreach ($overdueInv as $inv): ?>
                <div class="risk-row">
                    <div>
                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($inv['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:#9ca3af">₹<?= number_format($inv['amount']) ?> · Due <?= date('d M', strtotime($inv['due_date'])) ?></div>
                    </div>
                    <span class="risk-badge <?= $inv['days_overdue'] > 90 ? 'high' : ($inv['days_overdue'] > 30 ? 'medium' : 'low') ?>">
                        <?= $inv['days_overdue'] ?>d overdue
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">✅ No overdue invoices</div>
            <?php endif; ?>
            <?php if ($outstanding > 0): ?>
            <div style="margin-top:12px;padding:10px;background:#fef2f2;border-radius:8px;font-size:12px;color:#991b1b;font-weight:600">
                ⚠️ Total Outstanding: ₹<?= number_format($outstanding) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Advances Outstanding -->
    <div class="intel-card">
        <div class="intel-card__head">
            💳 Advance Recovery Tracker
            <a href="/ergon/advances">View All →</a>
        </div>
        <div class="intel-card__body">
            <?php if (!empty($advOut)): ?>
                <?php foreach ($advOut as $adv): ?>
                <div class="risk-row">
                    <div>
                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($adv['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size:11px;color:#9ca3af">₹<?= number_format($adv['amount']) ?></div>
                    </div>
                    <span class="risk-badge <?= $adv['days_pending'] > 60 ? 'high' : ($adv['days_pending'] > 30 ? 'medium' : 'low') ?>">
                        <?= $adv['days_pending'] ?>d pending
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">✅ No outstanding advances</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Expense Categories -->
    <div class="intel-card">
        <div class="intel-card__head">
            📊 Expense Breakdown (This Month)
            <a href="/ergon/expenses">View All →</a>
        </div>
        <div class="intel-card__body">
            <?php if (!empty($topExpCats)): ?>
                <?php
                $maxExp = max(array_column($topExpCats, 'total')) ?: 1;
                foreach ($topExpCats as $cat):
                    $pct = round(($cat['total'] / $maxExp) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-row__label"><?= htmlspecialchars(ucfirst($cat['category'] ?? 'Other'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="bar-row__track"><div class="bar-row__fill" style="width:<?= $pct ?>%"></div></div>
                    <div class="bar-row__val">₹<?= number_format($cat['total']/1000, 1) ?>K</div>
                </div>
                <?php endforeach; ?>
                <?php if ($expMonth > 0): ?>
                <div style="margin-top:10px;font-size:12px;color:#6b7280">Total: ₹<?= number_format($expMonth) ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">No expense data this month</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project & Task Overview -->
    <div class="intel-card">
        <div class="intel-card__head">
            🎯 Task & Project Status
            <a href="/ergon/tasks">View Tasks →</a>
        </div>
        <div class="intel-card__body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div style="text-align:center;padding:12px;background:#f0fdf4;border-radius:8px">
                    <div style="font-size:22px;font-weight:800;color:#16a34a"><?= htmlspecialchars($stats['completion_rate']??'0', ENT_QUOTES, 'UTF-8') ?>%</div>
                    <div style="font-size:11px;color:#6b7280">Completion Rate</div>
                </div>
                <div style="text-align:center;padding:12px;background:#eff6ff;border-radius:8px">
                    <div style="font-size:22px;font-weight:800;color:#1d4ed8"><?= htmlspecialchars($stats['ontime_rate']??'0', ENT_QUOTES, 'UTF-8') ?>%</div>
                    <div style="font-size:11px;color:#6b7280">On-Time Rate</div>
                </div>
            </div>
            <div class="bar-row">
                <div class="bar-row__label">In Progress</div>
                <div class="bar-row__track"><div class="bar-row__fill" style="width:<?= min(100, ($stats['in_progress']??0) * 5) ?>%;background:#3b82f6"></div></div>
                <div class="bar-row__val"><?= $stats['in_progress']??0 ?></div>
            </div>
            <div class="bar-row">
                <div class="bar-row__label">Pending</div>
                <div class="bar-row__track"><div class="bar-row__fill" style="width:<?= min(100, ($stats['pending']??0) * 5) ?>%;background:#f59e0b"></div></div>
                <div class="bar-row__val"><?= $stats['pending']??0 ?></div>
            </div>
            <div class="bar-row">
                <div class="bar-row__label">Critical</div>
                <div class="bar-row__track"><div class="bar-row__fill" style="width:<?= min(100, ($stats['critical']??0) * 10) ?>%;background:#ef4444"></div></div>
                <div class="bar-row__val"><?= $stats['critical']??0 ?></div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px">
                <a href="/ergon/dashboard/project-overview" style="font-size:12px;color:#3b82f6;text-decoration:none">📁 Project Overview →</a>
                <a href="/ergon/dashboard/delayed-tasks-overview" style="font-size:12px;color:#ef4444;text-decoration:none">⚠️ Delayed Tasks →</a>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="intel-card">
        <div class="intel-card__head">
            ⚡ Recent Activities
        </div>
        <div class="intel-card__body">
            <?php if (!empty($recentActs)): ?>
                <?php foreach ($recentActs as $act): ?>
                <div class="act-item">
                    <div class="act-dot"></div>
                    <div>
                        <div style="font-weight:500;font-size:13px"><?= htmlspecialchars($act['description'] ?? $act['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="act-meta"><?= htmlspecialchars($act['user_name'] ?? 'System', ENT_QUOTES, 'UTF-8') ?> · <?= isset($act['created_at']) ? date('d M, H:i', strtotime($act['created_at'])) : '' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">📝 No recent activities</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
