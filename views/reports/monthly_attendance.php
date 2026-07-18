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
            <h1>Monthly Attendance Report</h1>
            <p><?= htmlspecialchars($month_label) ?> &mdash; <?= count($report) ?> employees &mdash; <?= $working_days ?> working days</p>
        </div>
        <div class="page-actions">
            <a href="?month=<?= $month ?>&year=<?= $year ?>&export=csv" class="btn btn--secondary">&#8595; CSV</a>
            <button onclick="window.print()" class="btn btn--primary">&#128438; Print / PDF</button>
            <a href="/ergon/attendance" class="btn btn--secondary">&#8592; Back</a>
        </div>
    </div>

    <!-- Month selector -->
    <div class="month-nav-bar">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn--secondary btn--sm">&#8249; <?= date('M Y', mktime(0,0,0,$prevMonth,1,$prevYear)) ?></a>
        <form method="GET" class="month-nav-form">
            <select name="month" class="form-control form-control--sm">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="form-control form-control--sm">
                <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn--primary btn--sm">Go</button>
        </form>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn--secondary btn--sm"><?= date('M Y', mktime(0,0,0,$nextMonth,1,$nextYear)) ?> &#8250;</a>
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
        <span class="leg"><span class="leg-dot leg-p">P</span> Present</span>
        <span class="leg"><span class="leg-dot leg-a">A</span> Absent</span>
        <span class="leg"><span class="leg-dot leg-l">L</span> Leave</span>
        <span class="leg"><span class="leg-dot leg-h">H</span> Holiday</span>
        <span class="leg"><span class="leg-dot leg-wo">WO</span> Sunday</span>
        <span class="leg"><span class="leg-dot leg-s">S</span> Saturday</span>
    </div>

    <!-- Attendance Grid -->
    <div class="grid-wrap" id="attGridWrap">
        <table class="att-grid" id="attGrid">
            <colgroup>
                <col class="col-sno" />
                <col class="col-name" />
                <col class="col-dept" />
                <?php foreach ($days as $day): ?>
                <col class="col-day" />
                <?php endforeach; ?>
                <col class="col-sum" />
                <col class="col-sum" />
                <col class="col-sum" />
                <col class="col-sum" />
                <col class="col-hrs" />
                <col class="col-pct" />
            </colgroup>
            <thead>
                <!-- Row 1: date numbers -->
                <tr class="thead-row1">
                    <th class="th-sno" rowspan="2">S.No</th>
                    <th class="th-name" rowspan="2">Employee Name</th>
                    <th class="th-dept" rowspan="2">Dept</th>
                    <?php foreach ($days as $day): ?>
                    <th class="th-day <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                        <?= $day['label'] ?>
                    </th>
                    <?php endforeach; ?>
                    <th class="th-sum th-sum-first" rowspan="2">P</th>
                    <th class="th-sum" rowspan="2">A</th>
                    <th class="th-sum" rowspan="2">L</th>
                    <th class="th-sum" rowspan="2">H</th>
                    <th class="th-sum" rowspan="2">Hrs</th>
                    <th class="th-sum" rowspan="2">Att%</th>
                </tr>
                <!-- Row 2: day initials -->
                <tr class="thead-row2">
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
            <tr>
                <td class="td-sno"><?= $sno++ ?></td>
                <td class="td-name"><?= htmlspecialchars($r['name']) ?></td>
                <td class="td-dept">N/A</td>

                <?php foreach ($days as $day):
                    $d = $r['days'][$day['date']] ?? '-';
                ?>
                <td class="td-day <?= $day['is_sun'] ? 'col-sun' : ($day['is_sat'] ? 'col-sat' : '') ?>">
                    <?php if ($d === 'WO'): ?>
                        <span class="cell-wo">WO</span>
                    <?php elseif ($d === 'H'): ?>
                        <span class="cell-h">H</span>
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

                <td class="td-sum td-p td-sum-first"><?= $r['present'] ?></td>
                <td class="td-sum td-a"><?= $r['absent'] ?></td>
                <td class="td-sum td-l"><?= $r['leave'] ?></td>
                <td class="td-sum td-wo"><?= $sundays ?></td>
                <td class="td-sum"><?= $r['total_hrs'] ?></td>
                <td class="td-sum td-pct" style="color:<?= $r['att_pct'] >= 75 ? '#15803d' : ($r['att_pct'] >= 50 ? '#b45309' : '#b91c1c') ?>;">
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
                        <?= $day['is_sun'] ? '&mdash;' : ($dayPresent > 0 ? $dayPresent : '') ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="td-sum td-p td-sum-first"><strong><?= $totalPresent ?></strong></td>
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
        System-generated report &mdash; Ergon Employee Management &mdash; <?= date('d M Y, h:i A') ?>
    </div>

</div><!-- end .report-page -->

<style>
/* ── Hide global floating nav buttons on this page ── */
body:has(.report-page) .global-back-btn,
body:has(.report-page) .global-forward-btn {
    display: none !important;
}

/* ── Month nav bar (screen only) ── */
.month-nav-bar {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 1rem;
}
.month-nav-form {
    display: flex;
    gap: .4rem;
    align-items: center;
}
.form-control--sm {
    padding: .3rem .5rem;
    font-size: .82rem;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
}
select.form-control--sm[name="month"] { width: 116px; }
select.form-control--sm[name="year"]  { width: 80px; }

/* ════════════════════════════════════════════
   REPORT PAGE — root
════════════════════════════════════════════ */
.report-page {
    background: #fff;
    max-width: 100%;
    margin: 0 auto;
    padding: 0;
    font-family: 'Segoe UI', system-ui, Arial, sans-serif;
    font-size: .78rem;
    color: #0f172a;
    isolation: isolate;
}

/* ── Report Header ── */
.report-header {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 16px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5282 100%);
    padding: 14px 22px;
    border: 2px solid #1e3a5f;
    border-bottom: none;
}
.report-company-name {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 3px;
    line-height: 1;
}
.report-company-sub {
    font-size: .68rem;
    color: #93c5fd;
    margin-top: 4px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.report-title-block { text-align: center; }
.report-title {
    font-size: 1rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 2.5px;
    text-transform: uppercase;
}
.report-subtitle {
    font-size: .88rem;
    font-weight: 600;
    color: #bfdbfe;
    margin-top: 5px;
    letter-spacing: 1.5px;
}
.report-meta {
    text-align: right;
    font-size: .72rem;
    line-height: 2;
    color: #93c5fd;
}
.report-meta strong { color: #fff; }

/* ── Summary Strip ── */
.summary-strip {
    display: flex;
    border: 2px solid #1e3a5f;
    border-top: none;
    margin-bottom: 10px;
    background: #f8fafc;
}
.summary-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 11px 8px;
    gap: 3px;
}
.summary-divider {
    width: 1px;
    background: #cbd5e1;
    margin: 8px 0;
}
.summary-label {
    font-size: .6rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .9px;
    font-weight: 600;
}
.summary-value {
    font-size: 1.3rem;
    font-weight: 800;
    line-height: 1;
    color: #0f172a;
}
.summary-value.present { color: #15803d; }
.summary-value.absent  { color: #b91c1c; }
.summary-value.leave   { color: #b45309; }

/* ── Legend ── */
.legend-row {
    display: flex;
    gap: 14px;
    font-size: .72rem;
    margin-bottom: 10px;
    flex-wrap: wrap;
    color: #374151;
    align-items: center;
    padding: 7px 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
}
.leg {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
    color: #374151;
}
.leg-dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 7px;
    border-radius: 3px;
    font-weight: 700;
    font-size: .7rem;
    min-width: 22px;
    line-height: 1.4;
}
.leg-p  { background: #dcfce7; color: #15803d; }
.leg-a  { background: #fee2e2; color: #b91c1c; }
.leg-l  { background: #fef3c7; color: #b45309; }
.leg-h  { background: #fce7f3; color: #be185d; }
.leg-wo { background: #f1f5f9; color: #475569; }
.leg-s  { background: #fef9c3; color: #854d0e; }

/* ════════════════════════════════════════════
   GRID WRAPPER — scroll container
════════════════════════════════════════════ */
.grid-wrap {
    overflow-x: auto;
    overflow-y: visible;
    padding-bottom: 4px;
    margin-bottom: 14px;
    position: relative;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    /* Styled scrollbar */
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 #f1f5f9;
}
.grid-wrap::-webkit-scrollbar { height: 7px; }
.grid-wrap::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 0 4px 4px; }
.grid-wrap::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 4px; }
.grid-wrap::-webkit-scrollbar-thumb:hover { background: #64748b; }

/* ════════════════════════════════════════════
   ATTENDANCE TABLE
════════════════════════════════════════════ */
.att-grid {
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
    font-size: .74rem;
    table-layout: fixed;
    white-space: nowrap;
}

/* Base cell */
.att-grid th,
.att-grid td {
    border: 1px solid #d1d9e0;
    padding: 7px 5px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    line-height: 1.3;
}

/* ── Column widths via colgroup ── */
col.col-sno  { width: 38px; }
col.col-name { width: 220px; }
col.col-dept { width: 78px; }
col.col-day  { width: 36px; }
col.col-sum  { width: 42px; }
col.col-hrs  { width: 52px; }
col.col-pct  { width: 54px; }

/* ── THEAD — base ── */
.att-grid thead th {
    background: #1e3a5f;
    color: #fff;
    font-weight: 600;
    position: sticky;
    z-index: 5;
    border-color: rgba(255,255,255,.12);
}
.thead-row1 th { top: 0; }
/* Row-2 top is set dynamically via JS; fallback: */
.thead-row2 th {
    top: 34px;
    background: #243f6a;
    font-size: .64rem;
    font-weight: 500;
    padding: 4px 2px;
    border-color: rgba(255,255,255,.1);
}

/* ── Day header cells ── */
.th-sno  { width: 38px;  min-width: 38px;  font-size: .68rem; }
.th-name { width: 220px; min-width: 220px; text-align: left !important; padding-left: 12px !important; }
.th-dept { width: 78px;  min-width: 78px; }
.th-day  { width: 36px;  min-width: 36px;  font-size: .68rem; line-height: 1.2; padding: 6px 2px; }
.th-dayname { font-size: .62rem; font-weight: 500; }
.th-sum  { width: 42px;  min-width: 42px;  font-size: .7rem; }

/* Summary group left separator */
.th-sum-first { border-left: 2px solid rgba(255,255,255,.35) !important; }
.td-sum-first { border-left: 2px solid #94a3b8 !important; }

/* ── Weekend column highlights ── */
.col-sun { background: #fce7f3 !important; color: #9d174d !important; }
.col-sat { background: #fef9c3 !important; color: #92400e !important; }

/* ════════════════════════════════════════════
   STICKY COLUMNS
   S.No → left:0  |  Name → left:38px  |  Dept → left:258px
════════════════════════════════════════════ */

/* — S.No — */
.att-grid th.th-sno,
.att-grid td.td-sno {
    position: sticky;
    left: 0;
    z-index: 3;
}
.att-grid th.th-sno { z-index: 7; background: #1e3a5f; }
.att-grid td.td-sno {
    background: #f0f4f8;
    color: #64748b;
    font-size: .68rem;
    font-weight: 600;
    border-right-color: #c8d6e5;
}

/* — Name — */
.att-grid th.th-name,
.att-grid td.td-name {
    position: sticky;
    left: 38px;
    z-index: 3;
}
.att-grid th.th-name { z-index: 7; background: #1e3a5f; }
.att-grid td.td-name {
    background: #fff;
    text-align: left !important;
    padding-left: 12px !important;
    font-weight: 600;
    color: #0f172a;
    font-size: .74rem;
}

/* — Dept — */
.att-grid th.th-dept,
.att-grid td.td-dept {
    position: sticky;
    left: 258px; /* 38 + 220 */
    z-index: 3;
    /* shadow signals sticky edge */
    box-shadow: 3px 0 6px -2px rgba(0,0,0,.10);
}
.att-grid th.th-dept { z-index: 7; background: #1e3a5f; box-shadow: 3px 0 8px -2px rgba(0,0,0,.18); }
.att-grid td.td-dept {
    background: #f8fafc;
    font-size: .66rem;
    color: #475569;
    border-right: 2px solid #b8c8d8;
}

/* ── Row alternation (CSS, not PHP class) ── */
.att-grid tbody tr:nth-child(even) td             { background-color: #f8fafc; }
.att-grid tbody tr:nth-child(even) td.td-name     { background-color: #f5f8fb; }
.att-grid tbody tr:nth-child(even) td.td-sno      { background-color: #eaf0f7; }
.att-grid tbody tr:nth-child(even) td.td-dept     { background-color: #f0f4f8; }
.att-grid tbody tr:nth-child(even) td.td-p        { background-color: #ecfdf5; }
.att-grid tbody tr:nth-child(even) td.td-a        { background-color: #fef5f5; }
.att-grid tbody tr:nth-child(even) td.td-l        { background-color: #fffdf0; }

/* ── Row hover ── */
.att-grid tbody tr:hover td             { background-color: #eff6ff !important; }
.att-grid tbody tr:hover td.td-sno      { background-color: #dbeafe !important; }
.att-grid tbody tr:hover td.td-name     { background-color: #eff6ff !important; }
.att-grid tbody tr:hover td.td-dept     { background-color: #e4edfc !important; }

/* ── Data cell classes ── */
.td-sno  { font-size: .68rem; }
.td-name { font-weight: 600; font-size: .74rem; }
.td-dept { font-size: .66rem; }
.td-day  { padding: 6px 2px; }
.td-sum  { font-weight: 600; font-size: .72rem; }

/* Summary column colour coding */
.td-p   { color: #15803d; background: #f0fdf4 !important; }
.td-a   { color: #b91c1c; background: #fef2f2 !important; }
.td-l   { color: #b45309; background: #fffbeb !important; }
.td-wo  { color: #475569; background: #f9fafb !important; }
.td-pct { font-weight: 700; font-size: .73rem; }

/* ── Totals row ── */
.totals-row td {
    background: #e0e9f7 !important;
    font-weight: 700;
    border-top: 2px solid #1e3a5f;
    font-size: .72rem;
}
.totals-row td.td-p { background: #d1fae5 !important; }
.totals-row td.td-a { background: #fee2e2 !important; }
.totals-row td.td-l { background: #fef3c7 !important; }
.totals-row td.td-wo { background: #f1f5f9 !important; }

.td-total-label {
    text-align: right !important;
    font-weight: 800;
    padding-right: 10px !important;
    letter-spacing: .6px;
    font-size: .72rem;
    color: #1e3a5f;
}
.td-day-total {
    font-size: .65rem;
    font-weight: 700;
    color: #1e3a5f;
}

/* ── Cell badges ── */
.cell-p {
    display: inline-flex; align-items: center; justify-content: center;
    background: #dcfce7; color: #15803d;
    border-radius: 3px; padding: 2px 4px;
    font-weight: 700; font-size: .68rem; min-width: 18px;
    cursor: default;
}
.cell-a {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fee2e2; color: #b91c1c;
    border-radius: 3px; padding: 2px 4px;
    font-weight: 700; font-size: .68rem; min-width: 18px;
}
.cell-l {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fef3c7; color: #b45309;
    border-radius: 3px; padding: 2px 4px;
    font-weight: 700; font-size: .68rem; min-width: 18px;
}
.cell-h {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fce7f3; color: #be185d;
    border-radius: 3px; padding: 2px 4px;
    font-weight: 700; font-size: .68rem; min-width: 18px;
}
.cell-wo {
    display: inline-flex; align-items: center; justify-content: center;
    background: #f1f5f9; color: #64748b;
    border-radius: 3px; padding: 2px 4px;
    font-size: .65rem; min-width: 18px;
}
.cell-future { color: #d1d5db; font-size: .7rem; }

/* ── Signature block ── */
.sign-block {
    display: flex;
    justify-content: space-around;
    margin-top: 30px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
}
.sign-col { text-align: center; width: 26%; }
.sign-line {
    border-bottom: 1.5px solid #374151;
    margin-bottom: 8px;
    height: 36px;
}
.sign-label {
    font-size: .68rem;
    color: #475569;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ── Footer ── */
.report-footer {
    text-align: center;
    font-size: .62rem;
    color: #94a3b8;
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
    letter-spacing: .3px;
}

/* ════════════════════════════════════════════
   RESPONSIVE BREAKPOINTS
════════════════════════════════════════════ */
@media (min-width: 1601px) {
    col.col-name { width: 240px; }
    .att-grid th.th-name, .att-grid td.td-name { left: 38px; }
    .att-grid th.th-dept, .att-grid td.td-dept { left: 278px; }
}
@media (max-width: 1400px) {
    .att-grid { font-size: .71rem; }
    col.col-name { width: 190px; }
    .att-grid th.th-name, .att-grid td.td-name { left: 38px; }
    .att-grid th.th-dept, .att-grid td.td-dept { left: 228px; }
}
@media (max-width: 1200px) {
    .att-grid { font-size: .68rem; }
    col.col-name { width: 170px; }
    col.col-dept { width: 64px; }
    .att-grid th.th-dept, .att-grid td.td-dept { left: 208px; }
}
@media (max-width: 1024px) {
    .att-grid { font-size: .65rem; }
    col.col-name { width: 150px; }
    col.col-dept { width: 56px; }
    .att-grid th.th-dept, .att-grid td.td-dept { left: 188px; }
    col.col-day  { width: 32px; }
    col.col-sum  { width: 36px; }
}

/* ════════════════════════════════════════════
   PRINT STYLES — A4 Landscape
════════════════════════════════════════════ */
@media print {
    .no-print { display: none !important; }
    .global-back-btn, .global-forward-btn { display: none !important; }

    body { margin: 0; padding: 0; background: #fff; }
    .main-content { margin: 0 !important; padding: 6px !important; }
    .main-header, .sidebar { display: none !important; }

    .report-page { padding: 0; font-size: .68rem; }
    .report-header {
        background: #1e3a5f !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        padding: 10px 14px;
    }
    .report-header * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .summary-strip { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .grid-wrap {
        overflow: visible;
        border: none;
        scrollbar-width: none;
        margin-bottom: 6px;
    }

    .att-grid {
        width: 100% !important;
        min-width: auto !important;
        table-layout: auto !important;
        font-size: .58rem !important;
        border-collapse: collapse !important;
    }
    .att-grid th,
    .att-grid td {
        border: .5pt solid #94a3b8 !important;
        padding: 3px 3px !important;
        position: static !important;
        box-shadow: none !important;
    }
    .att-grid thead th {
        background: #1e3a5f !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .thead-row2 th {
        background: #243f6a !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    col.col-name { width: 120px !important; }
    col.col-dept { width: 50px !important; }
    col.col-day  { width: auto !important; }

    .col-sun, .col-sat { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cell-p, .cell-a, .cell-l, .cell-wo { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .td-p, .td-a, .td-l, .td-wo { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .totals-row td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    .sign-block { margin-top: 14px; }
    .report-footer { margin-top: 8px; }

    @page { size: A4 landscape; margin: 8mm 10mm; }
}
</style>

<script>
(function () {
    function alignStickyRow2() {
        var row1 = document.querySelector('.att-grid thead .thead-row1');
        if (!row1) return;
        var h = row1.offsetHeight;
        document.querySelectorAll('.att-grid thead .thead-row2 th').forEach(function (th) {
            th.style.top = h + 'px';
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', alignStickyRow2);
    } else {
        alignStickyRow2();
    }
    window.addEventListener('resize', alignStickyRow2);
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>
