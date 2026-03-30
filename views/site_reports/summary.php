
<div class="page-header-modern">
    <div class="page-header-content">
        <h1 class="page-title">📊 Site Reports Summary</h1>
        <a href="/ergon/site-reports" class="btn btn--secondary btn--sm">← Back</a>
    </div>
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card__body">
        <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin:0">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
            </div>
            <button type="submit" class="btn btn--primary">Filter</button>
        </form>
    </div>
</div>

<?php
$totalManpower  = array_sum(array_column($rows, 'total_manpower'));
$totalExpenses  = array_sum(array_column($rows, 'total_expenses_requested'));
$totalReports   = count($rows);
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">
    <div class="card"><div class="card__body" style="text-align:center">
        <div style="font-size:1.75rem;font-weight:700"><?= $totalReports ?></div>
        <div style="color:#64748b;font-size:.85rem">Reports Submitted</div>
    </div></div>
    <div class="card"><div class="card__body" style="text-align:center">
        <div style="font-size:1.75rem;font-weight:700"><?= number_format($totalManpower) ?></div>
        <div style="color:#64748b;font-size:.85rem">Total Manpower Days</div>
    </div></div>
    <div class="card"><div class="card__body" style="text-align:center">
        <div style="font-size:1.75rem;font-weight:700">₹<?= number_format($totalExpenses, 0) ?></div>
        <div style="color:#64748b;font-size:.85rem">Expenses Requested</div>
    </div></div>
</div>

<div class="card">
    <div class="card__body" style="padding:0">
        <table class="table" style="margin:0">
            <thead><tr>
                <th>Date</th><th>Site / Project</th><th>Manpower</th><th>Expenses</th><th>Items</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center" style="color:#94a3b8">No reports in this period</td></tr>
            <?php else: foreach ($rows as $r): ?>
            <tr>
                <td><?= date('d M', strtotime($r['report_date'])) ?></td>
                <td>
                    <?= htmlspecialchars($r['site_name']) ?>
                    <?php if ($r['project_name']): ?>
                    <br><small style="color:#94a3b8"><?= htmlspecialchars($r['project_name']) ?></small>
                    <?php endif; ?>
                </td>
                <td><strong><?= $r['total_manpower'] ?></strong></td>
                <td><?= $r['total_expenses_requested'] > 0 ? '₹'.number_format($r['total_expenses_requested'],0) : '—' ?></td>
                <td><?= $r['expense_items'] ?: '—' ?></td>
                <td><a href="/ergon/site-reports/view/<?= $r['id'] ?>" class="btn btn--secondary btn--xs">View</a></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
