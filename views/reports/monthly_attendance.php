<?php
$title = 'Monthly Attendance Report';
$active_page = 'reports';
ob_start();

// Build prev/next month links
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;

$totalPresent = array_sum(array_column($report, 'present'));
$totalAbsent  = array_sum(array_column($report, 'absent'));
$totalLeave   = array_sum(array_column($report, 'leave'));
$avgAtt       = count($report) > 0 ? round(array_sum(array_column($report, 'att_pct')) / count($report)) : 0;
?>

<div class="page-header">
    <div class="page-title">
        <h1>📅 Monthly Attendance Report</h1>
        <p><?= htmlspecialchars($month_label) ?> — <?= count($report) ?> employees, <?= $working_days ?> working days</p>
    </div>
    <div class="page-actions">
        <a href="?month=<?= $month ?>&year=<?= $year ?>&export=csv" class="btn btn--primary">📥 Export CSV</a>
        <a href="/ergon/attendance" class="btn btn--secondary">← Attendance</a>
    </div>
</div>

<!-- Month Navigator -->
<div class="card" style="margin-bottom:1rem;">
    <div class="card__body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn--secondary">‹ <?= date('M Y', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
        <form method="GET" style="display:flex;gap:.5rem;align-items:center;flex:1;flex-wrap:wrap;">
            <select name="month" class="form-control" style="width:130px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-control" style="width:90px;">
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn--primary">Go</button>
        </form>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn--secondary"><?= date('M Y', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> ›</a>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-grid" style="margin-bottom:1rem;">
    <div class="kpi-card">
        <div class="kpi-card__icon">👥</div>
        <div class="kpi-card__value"><?= count($report) ?></div>
        <div class="kpi-card__label">Total Employees</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon" style="color:#10b981;">✅</div>
        <div class="kpi-card__value" style="color:#10b981;"><?= $totalPresent ?></div>
        <div class="kpi-card__label">Total Present Days</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon" style="color:#ef4444;">❌</div>
        <div class="kpi-card__value" style="color:#ef4444;"><?= $totalAbsent ?></div>
        <div class="kpi-card__label">Total Absent Days</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon" style="color:#f59e0b;">🏖️</div>
        <div class="kpi-card__value" style="color:#f59e0b;"><?= $totalLeave ?></div>
        <div class="kpi-card__label">Total Leave Days</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__icon" style="color:#3b82f6;">📊</div>
        <div class="kpi-card__value" style="color:#3b82f6;"><?= $avgAtt ?>%</div>
        <div class="kpi-card__label">Avg Attendance</div>
    </div>
</div>

<!-- Legend -->
<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;font-size:.8rem;">
    <span><span class="badge-p">P</span> Present</span>
    <span><span class="badge-a">A</span> Absent</span>
    <span><span class="badge-l">L</span> Leave</span>
    <span><span class="badge-wo">WO</span> Week Off (Sun)</span>
    <span><span class="badge-sat">S</span> Saturday</span>
    <span style="color:#9ca3af;">— Future date</span>
</div>

<!-- Attendance Grid -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Attendance Grid — <?= htmlspecialchars($month_label) ?></h2>
        <div class="card__actions">
            <span class="badge badge--info"><?= count($report) ?> Employees</span>
        </div>
    </div>
    <div class="card__body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="att-table">
                <thead>
                    <tr>
                        <th class="col-name">Employee</th>
                        <th class="col-role">Role</th>
                        <?php foreach ($days as $day): ?>
                        <th class="col-day <?= $day['is_sun'] ? 'sun' : ($day['is_sat'] ? 'sat' : '') ?>" title="<?= $day['date'] ?>">
                            <div><?= $day['label'] ?></div>
                            <div style="font-size:.65rem;font-weight:400;"><?= $day['day'] ?></div>
                        </th>
                        <?php endforeach; ?>
                        <th class="col-sum">P</th>
                        <th class="col-sum">A</th>
                        <th class="col-sum">L</th>
                        <th class="col-sum">Hrs</th>
                        <th class="col-sum">Att%</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($report as $r): ?>
                <tr>
                    <td class="col-name"><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                    <td class="col-role"><span class="role-tag role-<?= $r['role'] ?>"><?= ucfirst($r['role']) ?></span></td>
                    <?php foreach ($days as $day):
                        $d = $r['days'][$day['date']] ?? '-';
                    ?>
                    <td class="col-day <?= $day['is_sun'] ? 'sun' : ($day['is_sat'] ? 'sat' : '') ?>">
                        <?php if ($d === 'WO'): ?>
                            <span class="badge-wo">WO</span>
                        <?php elseif ($d === 'A'): ?>
                            <span class="badge-a">A</span>
                        <?php elseif ($d === 'L'): ?>
                            <span class="badge-l">L</span>
                        <?php elseif (is_array($d)): ?>
                            <span class="badge-p" title="In: <?= $d['in'] ?> Out: <?= $d['out'] ?> (<?= $d['hours'] ?>h)">P</span>
                        <?php else: ?>
                            <span style="color:#d1d5db;">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="col-sum present"><?= $r['present'] ?></td>
                    <td class="col-sum absent"><?= $r['absent'] ?></td>
                    <td class="col-sum leave"><?= $r['leave'] ?></td>
                    <td class="col-sum"><?= $r['total_hrs'] ?></td>
                    <td class="col-sum">
                        <span style="color:<?= $r['att_pct'] >= 75 ? '#10b981' : ($r['att_pct'] >= 50 ? '#f59e0b' : '#ef4444') ?>;">
                            <?= $r['att_pct'] ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.att-table { width:100%; border-collapse:collapse; font-size:.78rem; white-space:nowrap; }
.att-table th, .att-table td { padding:5px 4px; border:1px solid #e5e7eb; text-align:center; }
.att-table thead th { background:#f8fafc; font-weight:600; position:sticky; top:0; z-index:2; }
.att-table tbody tr:hover { background:#f0f9ff; }
.col-name { text-align:left !important; min-width:130px; max-width:160px; overflow:hidden; text-overflow:ellipsis; position:sticky; left:0; background:#fff; z-index:1; }
.att-table thead .col-name { background:#f8fafc; z-index:3; }
.col-role { min-width:70px; }
.col-day { min-width:32px; max-width:36px; }
.col-day.sun { background:#fef2f2 !important; }
.col-day.sat { background:#fffbeb !important; }
.col-sum { min-width:36px; font-weight:600; }
.col-sum.present { color:#10b981; }
.col-sum.absent  { color:#ef4444; }
.col-sum.leave   { color:#f59e0b; }

.badge-p  { display:inline-block; background:#d1fae5; color:#065f46; border-radius:3px; padding:1px 4px; font-size:.7rem; font-weight:700; cursor:default; }
.badge-a  { display:inline-block; background:#fee2e2; color:#991b1b; border-radius:3px; padding:1px 4px; font-size:.7rem; font-weight:700; }
.badge-l  { display:inline-block; background:#fef3c7; color:#92400e; border-radius:3px; padding:1px 4px; font-size:.7rem; font-weight:700; }
.badge-wo { display:inline-block; background:#f3f4f6; color:#6b7280; border-radius:3px; padding:1px 4px; font-size:.65rem; }
.badge-sat{ display:inline-block; background:#fef9c3; color:#854d0e; border-radius:3px; padding:1px 4px; font-size:.7rem; }

.role-tag { display:inline-block; padding:1px 6px; border-radius:10px; font-size:.7rem; font-weight:600; }
.role-admin { background:#dbeafe; color:#1e40af; }
.role-owner { background:#ede9fe; color:#5b21b6; }
.role-user  { background:#f0fdf4; color:#166534; }

@media print {
    .page-actions, .card__actions, form, a.btn { display:none !important; }
    .att-table { font-size:.65rem; }
    .col-name { min-width:100px; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
