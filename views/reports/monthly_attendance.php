<?php
$title = 'Monthly Attendance Report';
$active_page = 'reports';
ob_start();

$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;

$totalPresent = array_sum(array_column($report, 'present'));
$totalAbsent  = array_sum(array_column($report, 'absent'));
$totalLeave   = array_sum(array_column($report, 'leave'));
$avgAtt       = count($report) > 0 ? round(array_sum(array_column($report, 'att_pct')) / count($report)) : 0;
?>

<!-- Screen controls — hidden on print -->
<div class="no-print">
    <div class="page-header">
        <div class="page-title">
            <h1>📅 Monthly Attendance Report</h1>
            <p><?= htmlspecialchars($month_label) ?> &mdash; <?= count($report) ?> employees &mdash; <?= $working_days ?> working days</p>
        </div>
        <div class="page-actions">
            <a href="?month=<?= $month ?>&year=<?= $year ?>&export=csv" class="btn btn--secondary">📥 CSV</a>
            <button onclick="window.print()" class="btn btn--primary">🖨️ Print / PDF</button>
            <a href="/ergon/attendance" class="btn btn--secondary">← Back</a>
        </div>
    </div>

    <!-- Month selector -->
    <div class="card" style="margin-bottom:1rem;">
        <div class="card__body" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn--secondary btn--sm">‹ <?= date('M Y', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
            <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
                <select name="month" class="form-control" style="width:120px;padding:.35rem .5rem;font-size:.85rem;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="width:85px;padding:.35rem .5rem;font-size:.85rem;">
                    <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn--primary btn--sm">Go</button>
            </form>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn--secondary btn--sm"><?= date('M Y', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> ›</a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     PRINTABLE REPORT STARTS HERE
═══════════════════════════════════════════════════════════════ -->
<div class="report-page">

    <!-- Report Header -->
    <div class="report-header">
        <div class="report-company">
            <div class="report-company-name">ERGON</div>
            <div class="report-company-sub">Employee Management System</div>
        </div>
        <div class="report-title-block">
            <div class="report-title">MONTHLY ATTENDANCE REGISTER</div>
            <div class="report-subtitle"><?= strtoupper($month_label) ?></div>
        </div>
        <div class="report-meta">
            <div>Working Days: <strong><?= $working_days ?></strong></div>
            <div>Total Staff: <strong><?= count($report) ?></strong></div>
            <div>Generated: <strong><?= date('d M Y') ?></strong></div>
        </div>
    </div>

    <!-- Summary Strip -->
    <div class="summary-strip">
        <div class="summary-item">
            <span class="summary-label">Total Present Days</span>
            <span class="summary-value present"><?= $totalPresent ?></span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <span class="summary-label">Total Absent Days</span>
            <span class="summary-value absent"><?= $totalAbsent ?></span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <span class="summary-label">Total Leave Days</span>
            <span class="summary-value leave"><?= $totalLeave ?></span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <span class="summary-label">Avg Attendance</span>
            <span class="summary-value"><?= $avgAtt ?>%</span>
        </div>
    </div>

    <!-- Legend -->
    <div class="legend-row">
        <span class="leg"><span class="leg-p">P</span> Present</span>
        <span class="leg"><span class="leg-a">A</span> Absent</span>
        <span class="leg"><span class="leg-l">L</span> Leave</span>
        <span class="leg"><span class="leg-wo">H</span> Holiday/Sunday</span>
        <span class="leg"><span class="leg-s">S</span> Saturday</span>
    </div>

    <!-- Attendance Grid -->
    <div class="grid-wrap">
        <table class="att-grid">
            <thead>
                <!-- Row 1: dates -->
                <tr>
                    <th class="th-sno" rowspan="2">S.No</th>
                    <th class="th-name" rowspan="2">Employee Name</th>
                    <th class="th-dept" rowspan="2">Dept</th>
                    <?php foreach ($days as $day): ?>
                    <th class="th-day <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                        <?= $day['label'] ?>
                    </th>
                    <?php endforeach; ?>
                    <th class="th-sum" rowspan="2">P</th>
                    <th class="th-sum" rowspan="2">A</th>
                    <th class="th-sum" rowspan="2">L</th>
                    <th class="th-sum" rowspan="2">H</th>
                    <th class="th-sum" rowspan="2">Hrs</th>
                    <th class="th-sum" rowspan="2">Att%</th>
                </tr>
                <!-- Row 2: day names -->
                <tr>
                    <?php foreach ($days as $day): ?>
                    <th class="th-dayname <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                        <?= substr($day['day'], 0, 1) ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $sno = 1;
            $sundays = count(array_filter($days, fn($d) => $d['is_sun']));
            foreach ($report as $r):
            ?>
            <tr class="<?= $sno % 2 === 0 ? 'row-even' : '' ?>">
                <td class="td-sno"><?= $sno++ ?></td>
                <td class="td-name"><?= htmlspecialchars($r['name']) ?></td>
                <td class="td-dept">N/A</td>


                <?php foreach ($days as $day):
                    $d = $r['days'][$day['date']] ?? '-';
                ?>
                <td class="td-day <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                    <?php if ($d === 'WO'): ?>
                        <span class="cell-wo">H</span>
                    <?php elseif ($d === 'A'): ?>
                        <span class="cell-a">A</span>
                    <?php elseif ($d === 'L'): ?>
                        <span class="cell-l">L</span>
                    <?php elseif (is_array($d)): ?>
                        <span class="cell-p" title="<?= $d['in'] ?>–<?= $d['out'] ?> (<?= $d['hours'] ?>h)">P</span>
                    <?php else: ?>
                        <span class="cell-future">·</span>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>

                <td class="td-sum td-p"><?= $r['present'] ?></td>
                <td class="td-sum td-a"><?= $r['absent'] ?></td>
                <td class="td-sum td-l"><?= $r['leave'] ?></td>
                <td class="td-sum td-wo"><?= $sundays ?></td>
                <td class="td-sum"><?= $r['total_hrs'] ?></td>
                <td class="td-sum td-pct" style="color:<?= $r['att_pct'] >= 75 ? '#166534' : ($r['att_pct'] >= 50 ? '#92400e' : '#991b1b') ?>;">
                    <?= $r['att_pct'] ?>%
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <!-- Totals row -->
            <tfoot>
                <tr class="totals-row">
                    <td colspan="3" class="td-total-label">TOTAL</td>
                    <?php foreach ($days as $day):
                        $dayPresent = 0;
                        foreach ($report as $r) {
                            if (is_array($r['days'][$day['date']] ?? null)) $dayPresent++;
                        }
                    ?>
                    <td class="td-day-total <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                        <?= $day['is_sun'] ? '—' : ($dayPresent > 0 ? $dayPresent : '') ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="td-sum td-p"><strong><?= $totalPresent ?></strong></td>
                    <td class="td-sum td-a"><strong><?= $totalAbsent ?></strong></td>
                    <td class="td-sum td-l"><strong><?= $totalLeave ?></strong></td>
                    <td class="td-sum td-wo"><strong><?= $sundays * count($report) ?></strong></td>
                    <td class="td-sum"><strong><?= round(array_sum(array_column($report, 'total_hrs')), 1) ?></strong></td>
                    <td class="td-sum"><strong><?= $avgAtt ?>%</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signature Block -->
    <div class="sign-block">
        <div class="sign-col">
            <div class="sign-line"></div>
            <div class="sign-label">Prepared By</div>
        </div>
        <div class="sign-col">
            <div class="sign-line"></div>
            <div class="sign-label">Verified By</div>
        </div>
        <div class="sign-col">
            <div class="sign-line"></div>
            <div class="sign-label">Approved By</div>
        </div>
    </div>

    <div class="report-footer">
        This is a system-generated report &mdash; Ergon &mdash; <?= date('d M Y, h:i A') ?>
    </div>

</div><!-- end .report-page -->

<style>
/* ── Screen wrapper ── */
.report-page {
    background: #fff;
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: .78rem;
    color: #1a1a1a;
}

/* ── Report Header ── */
.report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border: 2px solid #1e3a5f;
    padding: 12px 16px;
    margin-bottom: 0;
    background: #f0f4f8;
}
.report-company-name { font-size: 1.3rem; font-weight: 800; color: #1e3a5f; letter-spacing: 2px; }
.report-company-sub  { font-size: .7rem; color: #6b7280; margin-top: 2px; }
.report-title-block  { text-align: center; }
.report-title        { font-size: 1rem; font-weight: 800; color: #1e3a5f; letter-spacing: 1px; }
.report-subtitle     { font-size: .85rem; font-weight: 600; color: #374151; margin-top: 4px; }
.report-meta         { text-align: right; font-size: .75rem; line-height: 1.8; color: #374151; }

/* ── Summary Strip ── */
.summary-strip {
    display: flex;
    border: 1px solid #1e3a5f;
    border-top: none;
    margin-bottom: 8px;
}
.summary-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 6px 4px;
}
.summary-divider { width: 1px; background: #1e3a5f; }
.summary-label { font-size: .65rem; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
.summary-value { font-size: 1.1rem; font-weight: 700; margin-top: 2px; }
.summary-value.present { color: #166534; }
.summary-value.absent  { color: #991b1b; }
.summary-value.leave   { color: #92400e; }

/* ── Legend ── */
.legend-row {
    display: flex;
    gap: 16px;
    font-size: .72rem;
    margin-bottom: 6px;
    flex-wrap: wrap;
    color: #374151;
}
.leg { display: flex; align-items: center; gap: 4px; }
.leg-p  { background: #d1fae5; color: #065f46; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
.leg-a  { background: #fee2e2; color: #991b1b; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
.leg-l  { background: #fef3c7; color: #92400e; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
.leg-wo { background: #e5e7eb; color: #374151; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
.leg-s  { background: #fef9c3; color: #854d0e; padding: 1px 5px; border-radius: 3px; font-weight: 700; }

/* ── Grid ── */
.grid-wrap { overflow-x: auto; }
.att-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: .72rem;
    white-space: nowrap;
}
.att-grid th, .att-grid td {
    border: 1px solid #9ca3af;
    padding: 3px 2px;
    text-align: center;
}
.att-grid thead th {
    background: #1e3a5f;
    color: #fff;
    font-weight: 600;
}
.th-sno  { width: 28px; }
.th-name { min-width: 130px; text-align: left !important; padding-left: 6px !important; }
.th-dept { width: 55px; }
.th-day  { width: 22px; font-size: .68rem; }
.th-dayname { font-size: .6rem; font-weight: 400; background: #2d4f7c !important; }
.th-sum  { width: 30px; font-size: .68rem; }

.col-sun { background: #fca5a5 !important; color: #7f1d1d !important; }
.col-sat { background: #fde68a !important; color: #78350f !important; }

.td-sno  { font-size: .68rem; color: #6b7280; }
.td-name { text-align: left !important; padding-left: 6px !important; font-weight: 600; }
.td-dept { font-size: .65rem; }
.td-day  { padding: 2px 1px; }
.td-sum  { font-weight: 600; font-size: .72rem; }
.td-p    { color: #166534; background: #f0fdf4; }
.td-a    { color: #991b1b; background: #fef2f2; }
.td-l    { color: #92400e; background: #fffbeb; }
.td-wo   { color: #374151; background: #f9fafb; }
.td-pct  { font-weight: 700; }
.td-total-label { text-align: right !important; font-weight: 700; padding-right: 8px !important; background: #f1f5f9; font-size: .72rem; }
.td-day-total { font-size: .65rem; font-weight: 600; color: #1e3a5f; }

.row-even { background: #f8fafc; }
.att-grid tbody tr:hover { background: #eff6ff; }

.totals-row td { background: #e0e7ff !important; font-weight: 700; border-top: 2px solid #1e3a5f; }

/* ── Cell badges ── */
.cell-p   { display:inline-block; background:#d1fae5; color:#065f46; border-radius:2px; padding:0 3px; font-weight:700; font-size:.68rem; cursor:default; }
.cell-a   { display:inline-block; background:#fee2e2; color:#991b1b; border-radius:2px; padding:0 3px; font-weight:700; font-size:.68rem; }
.cell-l   { display:inline-block; background:#fef3c7; color:#92400e; border-radius:2px; padding:0 3px; font-weight:700; font-size:.68rem; }
.cell-wo  { display:inline-block; background:#e5e7eb; color:#6b7280; border-radius:2px; padding:0 3px; font-size:.65rem; }
.cell-future { color: #d1d5db; font-size: .7rem; }

/* ── Dept tags ── */
.dept-admin { color: #1e40af; font-weight: 600; }
.dept-user  { color: #166534; font-weight: 600; }

/* ── Signature ── */
.sign-block {
    display: flex;
    justify-content: space-around;
    margin-top: 32px;
    padding-top: 8px;
}
.sign-col { text-align: center; width: 28%; }
.sign-line { border-bottom: 1px solid #374151; margin-bottom: 6px; height: 30px; }
.sign-label { font-size: .72rem; color: #374151; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

/* ── Footer ── */
.report-footer {
    text-align: center;
    font-size: .65rem;
    color: #9ca3af;
    margin-top: 12px;
    padding-top: 6px;
    border-top: 1px solid #e5e7eb;
}

/* ── Print styles ── */
@media print {
    .no-print { display: none !important; }
    body { margin: 0; padding: 0; background: #fff; }
    .main-content { margin: 0 !important; padding: 8px !important; }
    .main-header, .sidebar, .global-back-btn, .global-forward-btn { display: none !important; }
    .report-page { padding: 0; }
    .grid-wrap { overflow: visible; }
    .att-grid { font-size: .62rem; }
    .th-name { min-width: 100px; }
    .report-header { padding: 8px 12px; }
    .sign-block { margin-top: 20px; }
    @page { size: A3 landscape; margin: 10mm; }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
